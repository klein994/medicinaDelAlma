<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pdo = campus_db();
$setupStmt = $pdo->query("SELECT setting_value FROM campus_settings WHERE setting_key = 'setup_complete'");
$setupDone = $setupStmt->fetchColumn() === '1';

if (($_GET['api'] ?? '') === 'home_content') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Vary: Origin');
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $configuredOrigin = rtrim((string)($campusConfig['public_site_url'] ?? ''), '/');
    $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
    $localOrigin = in_array($originHost, ['localhost', '127.0.0.1', '::1'], true)
        && strtolower((string)parse_url($origin, PHP_URL_SCHEME)) === 'http';
    if ($origin !== '' && ($origin === $configuredOrigin || $localOrigin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    try {
        $ready = $pdo->query("SELECT setting_value FROM campus_settings WHERE setting_key = 'home_content_ready'")->fetchColumn() === '1';
        if (!$ready) {
            echo json_encode(['managed' => false]);
            exit;
        }
        $rows = $pdo->query("SELECT id, category, title, description, image_url, target_url, link_label
            FROM campus_home_items WHERE status = 'published' ORDER BY category, sort_order, id")->fetchAll();
        $response = ['managed' => true, 'workshops' => [], 'therapies' => [], 'objects' => []];
        foreach ($rows as $row) $response[$row['category']][] = $row;
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $exception) {
        error_log('Campus home content error: ' . $exception->getMessage());
        http_response_code(503);
        echo json_encode(['error' => 'El contenido aún no está disponible.']);
    }
    exit;
}

$page = (string)($_GET['page'] ?? ($setupDone ? 'campus' : 'setup'));
$errorMessage = '';
$flash = $_SESSION['campus_flash'] ?? null;
unset($_SESSION['campus_flash']);
$uploadsPendingCommit = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    campus_verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'setup') {
            if ($setupDone) throw new RuntimeException('El campus ya fue configurado.');
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Completa un nombre y un correo válido.');
            if (strlen($password) < 12) throw new RuntimeException('La contraseña debe tener al menos 12 caracteres.');

            $pdo->beginTransaction();
            $lock = $pdo->query("SELECT setting_value FROM campus_settings WHERE setting_key = 'setup_complete' FOR UPDATE");
            if ($lock->fetchColumn() === '1' || (int)$pdo->query('SELECT COUNT(*) FROM campus_users')->fetchColumn() > 0) {
                $pdo->rollBack();
                throw new RuntimeException('El campus ya fue configurado.');
            }
            $insert = $pdo->prepare("INSERT INTO campus_users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int)$pdo->lastInsertId();
            $pdo->exec("UPDATE campus_settings SET setting_value = '1' WHERE setting_key = 'setup_complete'");
            $pdo->commit();

            session_regenerate_id(true);
            $_SESSION['campus_user'] = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'admin', 'must_change_password' => 0];
            campus_flash('Tu espacio quedó listo. Ya puedes crear la primera sección.');
            campus_redirect();
        }

        if ($action === 'login') {
            if (!$setupDone) throw new RuntimeException('Primero hay que configurar el campus.');
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, must_change_password FROM campus_users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) throw new RuntimeException('Correo o contraseña incorrectos.');
            session_regenerate_id(true);
            unset($user['password_hash']);
            $_SESSION['campus_user'] = $user;
            campus_redirect(!empty($user['must_change_password']) ? 'page=password' : '');
        }

        if ($action === 'logout') {
            campus_require_login();
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            campus_redirect('page=login');
        }

        $user = campus_require_login();
        if (!empty($user['must_change_password']) && $action !== 'change_password') {
            campus_redirect('page=password');
        }

        if ($action === 'change_password') {
            $current = (string)($_POST['current_password'] ?? '');
            $next = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            $stmt = $pdo->prepare('SELECT password_hash FROM campus_users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$user['id']]);
            $hash = (string)$stmt->fetchColumn();
            if (!password_verify($current, $hash)) throw new RuntimeException('La contraseña actual no coincide.');
            if (strlen($next) < 12) throw new RuntimeException('La nueva contraseña debe tener al menos 12 caracteres.');
            if (!hash_equals($next, $confirm)) throw new RuntimeException('Las contraseñas nuevas no coinciden.');
            $stmt = $pdo->prepare('UPDATE campus_users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
            $stmt->execute([password_hash($next, PASSWORD_DEFAULT), (int)$user['id']]);
            $_SESSION['campus_user']['must_change_password'] = 0;
            campus_flash('Contraseña actualizada.');
            campus_redirect();
        }

        if ($action === 'save_home_item') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede cambiar el contenido de la home.');
            $itemId = (int)($_POST['item_id'] ?? 0);
            $category = (string)($_POST['category'] ?? '');
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $imageUrl = campus_home_url($_POST['image_url'] ?? '', 'La imagen');
            $targetUrl = campus_home_url($_POST['target_url'] ?? '', 'El enlace');
            $linkLabel = trim((string)($_POST['link_label'] ?? '')) ?: 'Saber más';
            $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
            $visible = isset($_POST['is_visible']) ? 'published' : 'hidden';
            if (!in_array($category, ['workshops', 'therapies', 'objects'], true)) throw new RuntimeException('Elige una sección de la home válida.');
            if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('El título es obligatorio y admite hasta 200 caracteres.');
            if ($sortOrder === false || $sortOrder < 0) throw new RuntimeException('El orden debe ser un número igual o mayor que cero.');
            if (mb_strlen($linkLabel) > 80) throw new RuntimeException('El texto del enlace admite hasta 80 caracteres.');
            if ($itemId > 0) {
                $stmt = $pdo->prepare('UPDATE campus_home_items SET category = ?, title = ?, description = ?, image_url = ?, target_url = ?, link_label = ?, sort_order = ?, status = ? WHERE id = ?');
                $stmt->execute([$category, $title, $description ?: null, $imageUrl, $targetUrl, $linkLabel, $sortOrder, $visible, $itemId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO campus_home_items (category, title, description, image_url, target_url, link_label, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$category, $title, $description ?: null, $imageUrl, $targetUrl, $linkLabel, $sortOrder, $visible]);
            }
            $pdo->exec("UPDATE campus_settings SET setting_value = '1' WHERE setting_key = 'home_content_ready'");
            campus_flash('Contenido de la home guardado.');
            campus_redirect('page=home_content');
        }

        if ($action === 'delete_home_item') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede quitar contenido de la home.');
            $itemId = (int)($_POST['item_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM campus_home_items WHERE id = ?');
            $stmt->execute([$itemId]);
            campus_flash('Se quitó el elemento de la home.');
            campus_redirect('page=home_content');
        }

        if ($action === 'create_section') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede crear secciones.');
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            if ($title === '' || mb_strlen($title) > 160) throw new RuntimeException('Escribe un nombre de sección de hasta 160 caracteres.');
            $stmt = $pdo->prepare("INSERT INTO campus_sections (title, description, created_by, status) VALUES (?, ?, ?, 'published')");
            $stmt->execute([$title, $description ?: null, (int)$user['id']]);
            $sectionId = (int)$pdo->lastInsertId();
            campus_flash('Sección creada. Ya puedes publicar en ella.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'archive_section') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede archivar secciones.');
            $sectionId = (int)($_POST['section_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE campus_sections SET status = 'archived' WHERE id = ?");
            $stmt->execute([$sectionId]);
            campus_flash('Sección archivada.');
            campus_redirect();
        }

        if ($action === 'remove_member') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede gestionar participantes.');
            $sectionId = (int)($_POST['section_id'] ?? 0);
            $memberId = (int)($_POST['member_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM campus_section_members WHERE section_id = ? AND user_id = ?');
            $stmt->execute([$sectionId, $memberId]);
            campus_flash('Se quitó el acceso de esa persona a la sección.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'create_account') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede crear accesos.');
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $role = (string)($_POST['role'] ?? 'member');
            $tempPassword = (string)($_POST['temporary_password'] ?? '');
            $sectionId = (int)($_POST['section_id'] ?? 0);
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Completa el nombre y un correo válido.');
            if (!in_array($role, ['member', 'facilitator'], true)) throw new RuntimeException('El rol seleccionado no es válido.');
            if (strlen($tempPassword) < 12) throw new RuntimeException('La contraseña temporal debe tener al menos 12 caracteres.');
            if ($sectionId > 0 && !campus_can_view_section($sectionId, $user)) throw new RuntimeException('La sección elegida no existe.');
            if ($sectionId > 0) {
                $activeSection = $pdo->prepare("SELECT 1 FROM campus_sections WHERE id = ? AND status = 'published'");
                $activeSection->execute([$sectionId]);
                if (!$activeSection->fetchColumn()) throw new RuntimeException('No puedes asignar cuentas a una sección archivada.');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO campus_users (name, email, password_hash, role, must_change_password) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $email, password_hash($tempPassword, PASSWORD_DEFAULT), $role]);
            $newUserId = (int)$pdo->lastInsertId();
            if ($sectionId > 0) {
                $memberRole = $role === 'facilitator' ? 'facilitator' : 'member';
                $stmt = $pdo->prepare('INSERT INTO campus_section_members (section_id, user_id, section_role, added_by) VALUES (?, ?, ?, ?)');
                $stmt->execute([$sectionId, $newUserId, $memberRole, (int)$user['id']]);
            }
            $pdo->commit();
            campus_flash('Acceso creado. Comparte la contraseña temporal con la persona; deberá cambiarla al ingresar.');
            campus_redirect($sectionId ? 'section=' . $sectionId : '');
        }

        if ($action === 'add_member') {
            if (!campus_is_admin($user)) throw new RuntimeException('Solo la administración puede gestionar participantes.');
            $sectionId = (int)($_POST['section_id'] ?? 0);
            $memberId = (int)($_POST['member_id'] ?? 0);
            $sectionCheck = $pdo->prepare("SELECT 1 FROM campus_sections WHERE id = ? AND status = 'published'");
            $sectionCheck->execute([$sectionId]);
            if (!$sectionCheck->fetchColumn()) throw new RuntimeException('No se encontró una sección activa.');
            $accountCheck = $pdo->prepare("SELECT role FROM campus_users WHERE id = ? AND role IN ('member', 'facilitator') AND status = 'active'");
            $accountCheck->execute([$memberId]);
            $sectionRole = $accountCheck->fetchColumn();
            if (!$sectionRole) throw new RuntimeException('Elige una cuenta activa de participante o facilitación.');
            $stmt = $pdo->prepare("INSERT INTO campus_section_members (section_id, user_id, section_role, added_by) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE section_role = VALUES(section_role)");
            $stmt->execute([$sectionId, $memberId, $sectionRole, (int)$user['id']]);
            campus_flash('La persona quedó asignada a la sección.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'save_event') {
            $eventId = (int)($_POST['event_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $sectionState = $pdo->prepare("SELECT status FROM campus_sections WHERE id = ? LIMIT 1");
            $sectionState->execute([$sectionId]);
            if ($sectionState->fetchColumn() !== 'published') throw new RuntimeException('No puedes programar encuentros en una sección archivada.');

            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $startInput = trim((string)($_POST['starts_at'] ?? ''));
            $endInput = trim((string)($_POST['ends_at'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $meetingUrl = trim((string)($_POST['meeting_url'] ?? ''));
            if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('Escribe un título de hasta 200 caracteres para el encuentro.');
            $startsAt = DateTime::createFromFormat('Y-m-d\\TH:i', $startInput);
            if (!$startsAt || $startsAt->format('Y-m-d\\TH:i') !== $startInput) throw new RuntimeException('Indica una fecha y hora válidas para el encuentro.');
            $endsAt = null;
            $endDate = null;
            if ($endInput !== '') {
                $endDate = DateTime::createFromFormat('Y-m-d\\TH:i', $endInput);
                if (!$endDate || $endDate->format('Y-m-d\\TH:i') !== $endInput || $endDate <= $startsAt) throw new RuntimeException('La hora de finalización debe ser válida y posterior al inicio.');
                $endsAt = $endDate->format('Y-m-d H:i:s');
            }
            if (($endDate ?? $startsAt) <= new DateTime()) throw new RuntimeException('El encuentro debe ser futuro o estar en curso.');
            if (mb_strlen($location) > 255) throw new RuntimeException('El lugar admite hasta 255 caracteres.');
            if (strlen($meetingUrl) > 1000 || ($meetingUrl !== '' && (!filter_var($meetingUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($meetingUrl, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
                throw new RuntimeException('El enlace del encuentro debe ser una URL HTTP o HTTPS válida.');
            }

            if ($eventId > 0) {
                $eventCheck = $pdo->prepare('SELECT id FROM campus_events WHERE id = ? AND section_id = ? LIMIT 1');
                $eventCheck->execute([$eventId, $sectionId]);
                if (!$eventCheck->fetchColumn()) throw new RuntimeException('No se encontró el encuentro de esta sección.');
                $stmt = $pdo->prepare('UPDATE campus_events SET title = ?, description = ?, starts_at = ?, ends_at = ?, location = ?, meeting_url = ? WHERE id = ? AND section_id = ?');
                $stmt->execute([$title, $description ?: null, $startsAt->format('Y-m-d H:i:s'), $endsAt, $location ?: null, $meetingUrl ?: null, $eventId, $sectionId]);
                campus_flash('Encuentro actualizado.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO campus_events (section_id, created_by, title, description, starts_at, ends_at, location, meeting_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$sectionId, (int)$user['id'], $title, $description ?: null, $startsAt->format('Y-m-d H:i:s'), $endsAt, $location ?: null, $meetingUrl ?: null]);
                campus_flash('Encuentro agregado a la agenda.');
            }
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'delete_event') {
            $eventId = (int)($_POST['event_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $sectionState = $pdo->prepare("SELECT status FROM campus_sections WHERE id = ? LIMIT 1");
            $sectionState->execute([$sectionId]);
            if ($sectionState->fetchColumn() !== 'published') throw new RuntimeException('No puedes quitar encuentros de una sección archivada.');
            $stmt = $pdo->prepare('DELETE FROM campus_events WHERE id = ? AND section_id = ?');
            $stmt->execute([$eventId, $sectionId]);
            if ($stmt->rowCount() < 1) throw new RuntimeException('No se encontró el encuentro de esta sección.');
            campus_flash('Encuentro eliminado de la agenda.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'create_post') {
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $type = (string)($_POST['post_type'] ?? 'post');
            $title = trim((string)($_POST['title'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            if (!in_array($type, ['post', 'announcement', 'activity'], true)) throw new RuntimeException('El tipo de publicación no es válido.');
            if ($title === '' || $body === '') throw new RuntimeException('Completa el título y el contenido.');
            $dueAt = null;
            if ($type === 'activity' && trim((string)($_POST['due_at'] ?? '')) !== '') {
                $date = DateTime::createFromFormat('Y-m-d\TH:i', (string)$_POST['due_at']);
                if (!$date) throw new RuntimeException('La fecha de entrega no es válida.');
                $dueAt = $date->format('Y-m-d H:i:s');
            }
            $uploads = campus_store_uploads($_FILES['attachments'] ?? []);
            foreach ($uploads as $upload) $uploadsPendingCommit[] = $upload['stored_name'];
            if ($type !== 'activity') $dueAt = null;
            $sectionState = $pdo->prepare("SELECT status FROM campus_sections WHERE id = ? LIMIT 1");
            $sectionState->execute([$sectionId]);
            if ($sectionState->fetchColumn() !== 'published') throw new RuntimeException('No puedes publicar dentro de una sección archivada.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO campus_posts (section_id, author_id, post_type, title, body, due_at, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
            $stmt->execute([$sectionId, (int)$user['id'], $type, mb_substr($title, 0, 200), $body, $dueAt]);
            $postId = (int)$pdo->lastInsertId();
            foreach ($uploads as $upload) {
                $stmt = $pdo->prepare('INSERT INTO campus_post_attachments (post_id, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$postId, $upload['original_name'], $upload['stored_name'], $upload['mime_type'], $upload['file_size']]);
            }
            $pdo->commit();
            $uploadsPendingCommit = [];
            campus_flash('Publicado en la sección.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'update_post') {
            $postId = (int)($_POST['post_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $postStmt = $pdo->prepare("SELECT id, post_type FROM campus_posts WHERE id = ? AND section_id = ? AND status = 'published' LIMIT 1");
            $postStmt->execute([$postId, $sectionId]);
            $existingPost = $postStmt->fetch();
            if (!$existingPost) throw new RuntimeException('No se encontró la publicación o ya no está disponible.');

            $sectionState = $pdo->prepare("SELECT status FROM campus_sections WHERE id = ? LIMIT 1");
            $sectionState->execute([$sectionId]);
            if ($sectionState->fetchColumn() !== 'published') throw new RuntimeException('No puedes editar publicaciones de una sección archivada.');

            $type = (string)($_POST['post_type'] ?? 'post');
            $title = trim((string)($_POST['title'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            if (!in_array($type, ['post', 'announcement', 'activity'], true)) throw new RuntimeException('El tipo de publicación no es válido.');
            if ($title === '' || $body === '') throw new RuntimeException('Completa el título y el contenido.');

            if ($existingPost['post_type'] === 'activity' && $type !== 'activity') {
                $submissionStmt = $pdo->prepare('SELECT COUNT(*) FROM campus_submissions WHERE post_id = ?');
                $submissionStmt->execute([$postId]);
                if ((int)$submissionStmt->fetchColumn() > 0) throw new RuntimeException('No puedes cambiar el tipo de una actividad que ya tiene entregas.');
            }

            $dueAt = null;
            if ($type === 'activity' && trim((string)($_POST['due_at'] ?? '')) !== '') {
                $date = DateTime::createFromFormat('Y-m-d\\TH:i', (string)$_POST['due_at']);
                if (!$date) throw new RuntimeException('La fecha de entrega no es válida.');
                $dueAt = $date->format('Y-m-d H:i:s');
            }

            $attachmentStmt = $pdo->prepare('SELECT id, stored_name FROM campus_post_attachments WHERE post_id = ?');
            $attachmentStmt->execute([$postId]);
            $existingAttachments = $attachmentStmt->fetchAll();
            $requestedKeepIds = array_values(array_unique(array_filter(
                array_map('intval', (array)($_POST['keep_attachment_ids'] ?? [])),
                static fn(int $id): bool => $id > 0
            )));
            $requestedKeepLookup = array_fill_keys($requestedKeepIds, true);
            $keptAttachments = array_values(array_filter(
                $existingAttachments,
                static fn(array $attachment): bool => isset($requestedKeepLookup[(int)$attachment['id']])
            ));
            $removedAttachments = array_values(array_filter(
                $existingAttachments,
                static fn(array $attachment): bool => !isset($requestedKeepLookup[(int)$attachment['id']])
            ));

            $uploads = campus_store_uploads($_FILES['attachments'] ?? []);
            foreach ($uploads as $upload) $uploadsPendingCommit[] = $upload['stored_name'];
            if (count($keptAttachments) + count($uploads) > 10) throw new RuntimeException('Una publicación puede conservar hasta 10 archivos. Quita alguno antes de agregar más.');

            $pdo->beginTransaction();
            $updateStmt = $pdo->prepare('UPDATE campus_posts SET post_type = ?, title = ?, body = ?, due_at = ? WHERE id = ? AND section_id = ?');
            $updateStmt->execute([$type, mb_substr($title, 0, 200), $body, $dueAt, $postId, $sectionId]);
            if ($removedAttachments) {
                $removeIds = array_map(static fn(array $attachment): int => (int)$attachment['id'], $removedAttachments);
                $marks = implode(',', array_fill(0, count($removeIds), '?'));
                $deleteAttachments = $pdo->prepare("DELETE FROM campus_post_attachments WHERE post_id = ? AND id IN ({$marks})");
                $deleteAttachments->execute(array_merge([$postId], $removeIds));
            }
            foreach ($uploads as $upload) {
                $insertAttachment = $pdo->prepare('INSERT INTO campus_post_attachments (post_id, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?)');
                $insertAttachment->execute([$postId, $upload['original_name'], $upload['stored_name'], $upload['mime_type'], $upload['file_size']]);
            }
            $pdo->commit();
            $uploadsPendingCommit = [];
            foreach ($removedAttachments as $attachment) {
                $removedPath = dirname(__DIR__) . '/storage/uploads/' . basename((string)$attachment['stored_name']);
                if (is_file($removedPath)) @unlink($removedPath);
            }
            campus_flash('Publicación actualizada.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'delete_post') {
            $postId = (int)($_POST['post_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $sectionState = $pdo->prepare("SELECT status FROM campus_sections WHERE id = ? LIMIT 1");
            $sectionState->execute([$sectionId]);
            if ($sectionState->fetchColumn() !== 'published') throw new RuntimeException('No puedes eliminar publicaciones de una sección archivada.');

            $postStmt = $pdo->prepare("SELECT id FROM campus_posts WHERE id = ? AND section_id = ? AND status = 'published' LIMIT 1");
            $postStmt->execute([$postId, $sectionId]);
            if (!$postStmt->fetchColumn()) throw new RuntimeException('No se encontró la publicación o ya fue eliminada.');

            $fileStmt = $pdo->prepare('SELECT stored_name FROM campus_post_attachments WHERE post_id = ? UNION ALL SELECT stored_name FROM campus_submissions WHERE post_id = ? AND stored_name IS NOT NULL');
            $fileStmt->execute([$postId, $postId]);
            $filesToRemove = $fileStmt->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            $deleteStmt = $pdo->prepare('DELETE FROM campus_posts WHERE id = ? AND section_id = ?');
            $deleteStmt->execute([$postId, $sectionId]);
            $pdo->commit();

            foreach ($filesToRemove as $storedName) {
                $filePath = dirname(__DIR__) . '/storage/uploads/' . basename((string)$storedName);
                if (is_file($filePath)) @unlink($filePath);
            }
            campus_flash('Publicación y entregas vinculadas eliminadas.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'submit_activity') {
            $postId = (int)($_POST['post_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId);
            if (($user['role'] ?? '') !== 'member') throw new RuntimeException('Solo los participantes pueden enviar actividades.');
            $stmt = $pdo->prepare("SELECT id, due_at FROM campus_posts WHERE id = ? AND section_id = ? AND post_type = 'activity' AND status = 'published' LIMIT 1");
            $stmt->execute([$postId, $sectionId]);
            $activity = $stmt->fetch();
            if (!$activity) throw new RuntimeException('No se encontró la actividad.');
            if ($activity['due_at'] && strtotime($activity['due_at']) < time()) throw new RuntimeException('El plazo de esta actividad ya terminó.');
            $response = trim((string)($_POST['response'] ?? ''));
            $upload = campus_store_upload($_FILES['attachment'] ?? []);
            if ($upload) $uploadsPendingCommit[] = $upload['stored_name'];
            if ($response === '' && !$upload) throw new RuntimeException('Escribe una respuesta o adjunta un archivo.');
            $stmt = $pdo->prepare('SELECT status, stored_name, original_name, mime_type, file_size FROM campus_submissions WHERE post_id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([$postId, (int)$user['id']]);
            $existing = $stmt->fetch();
            if ($existing && $existing['status'] !== 'needs_changes') throw new RuntimeException('Ya enviaste esta actividad. La facilitadora debe solicitar cambios para habilitar otro envío.');
            $pdo->beginTransaction();
            if ($existing) {
                $storedName = $upload['stored_name'] ?? $existing['stored_name'];
                $stmt = $pdo->prepare("UPDATE campus_submissions SET response = ?, stored_name = ?, original_name = ?, mime_type = ?, file_size = ?, status = 'submitted', feedback = NULL, reviewed_by = NULL, reviewed_at = NULL, submitted_at = CURRENT_TIMESTAMP WHERE post_id = ? AND member_id = ?");
                $stmt->execute([$response ?: null, $storedName, $upload['original_name'] ?? $existing['original_name'], $upload['mime_type'] ?? $existing['mime_type'], $upload['file_size'] ?? $existing['file_size'], $postId, (int)$user['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO campus_submissions (post_id, member_id, response, stored_name, original_name, mime_type, file_size, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')");
                $stmt->execute([$postId, (int)$user['id'], $response ?: null, $upload['stored_name'] ?? null, $upload['original_name'] ?? null, $upload['mime_type'] ?? null, $upload['file_size'] ?? null]);
            }
            $pdo->commit();
            if ($upload && !empty($existing['stored_name'])) {
                $oldPath = dirname(__DIR__) . '/storage/uploads/' . basename((string)$existing['stored_name']);
                if (is_file($oldPath)) @unlink($oldPath);
            }
            $uploadsPendingCommit = [];
            campus_flash('Tu actividad fue enviada.');
            campus_redirect('section=' . $sectionId);
        }

        if ($action === 'review_submission') {
            $submissionId = (int)($_POST['submission_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);
            campus_require_section($sectionId, true);
            $status = (string)($_POST['status'] ?? 'in_review');
            $feedback = trim((string)($_POST['feedback'] ?? ''));
            if (!in_array($status, ['in_review', 'needs_changes', 'approved'], true)) throw new RuntimeException('El estado de revisión no es válido.');
            $stmt = $pdo->prepare("UPDATE campus_submissions s JOIN campus_posts p ON p.id = s.post_id
                SET s.status = ?, s.feedback = ?, s.reviewed_by = ?, s.reviewed_at = CURRENT_TIMESTAMP
                WHERE s.id = ? AND p.section_id = ? AND p.post_type = 'activity'");
            $stmt->execute([$status, $feedback ?: null, (int)$user['id'], $submissionId, $sectionId]);
            campus_flash('Revisión guardada.');
            campus_redirect('section=' . $sectionId);
        }

        throw new RuntimeException('No se reconoció la acción solicitada.');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($uploadsPendingCommit as $storedName) {
            $orphanPath = dirname(__DIR__) . '/storage/uploads/' . basename((string)$storedName);
            if (is_file($orphanPath)) @unlink($orphanPath);
        }
        if ($exception instanceof PDOException) {
            error_log('Campus database error: ' . $exception->getMessage());
            if ((string)$exception->getCode() === '23000' && $action === 'create_account') {
                $errorMessage = 'Ese correo ya tiene una cuenta. Búscala en «Asignar cuenta existente» para volver a darle acceso a esta sección; conservará su contraseña actual.';
            } elseif ((string)$exception->getCode() === '23000' && $action === 'save_home_item') {
                $errorMessage = 'Ya existe un elemento con ese título en esa sección de la home.';
            } else {
                $errorMessage = 'No se pudo guardar la información. Revisa la configuración de la base de datos.';
            }
        } else {
            $errorMessage = $exception->getMessage();
        }
        $page = match ($action) {
            'setup' => 'setup',
            'login' => 'login',
            'change_password' => 'password',
            'save_home_item', 'delete_home_item' => 'home_content',
            default => 'campus',
        };
    }
}

// Las descargas y vistas previas privadas vuelven a comprobar el acceso a la sección.
if (isset($_GET['download']) || isset($_GET['preview'])) {
    $user = campus_require_login();
    $isPreview = isset($_GET['preview']);
    $kind = (string)($isPreview ? $_GET['preview'] : $_GET['download']);
    $fileId = (int)($_GET['id'] ?? 0);
    if ($kind === 'post') {
        $stmt = $pdo->prepare('SELECT a.*, p.section_id FROM campus_post_attachments a JOIN campus_posts p ON p.id = a.post_id WHERE a.id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        if (!$file || !campus_can_view_section((int)$file['section_id'], $user)) { http_response_code(404); exit; }
    } elseif ($kind === 'submission') {
        $stmt = $pdo->prepare('SELECT s.*, p.section_id FROM campus_submissions s JOIN campus_posts p ON p.id = s.post_id WHERE s.id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        $allowed = $file && ((int)$file['member_id'] === (int)$user['id'] || campus_can_manage_section((int)$file['section_id'], $user));
        if (!$allowed || empty($file['stored_name'])) { http_response_code(404); exit; }
    } else {
        http_response_code(404);
        exit;
    }
    $path = dirname(__DIR__) . '/storage/uploads/' . basename((string)$file['stored_name']);
    if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
    $mimeType = (string)($file['mime_type'] ?: 'application/octet-stream');
    if ($isPreview && !in_array(campus_attachment_kind($file), ['image', 'pdf', 'html', 'video', 'audio', 'text'], true)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $mimeType . (str_starts_with($mimeType, 'text/') ? '; charset=utf-8' : ''));
    header('Content-Length: ' . filesize($path));
    $filename = rawurlencode((string)$file['original_name']);
    header('Content-Disposition: ' . ($isPreview ? 'inline' : 'attachment') . "; filename*=UTF-8''{$filename}");
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    if ($isPreview && campus_attachment_kind($file) === 'html') {
        header_remove('X-Frame-Options');
        header("Content-Security-Policy: default-src 'self' data: blob: https:; img-src * data: blob:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; media-src * data: blob:; object-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'; sandbox allow-scripts allow-downloads allow-modals allow-forms allow-popups");
        header('Referrer-Policy: no-referrer');
    } elseif ($isPreview) {
        header('X-Frame-Options: SAMEORIGIN');
    }
    readfile($path);
    exit;
}

if (!$setupDone) $page = 'setup';
$user = campus_current_user();
if ($setupDone && $page !== 'login' && !$user) campus_redirect('page=login');
if ($setupDone && $user && !empty($user['must_change_password']) && $page !== 'password') campus_redirect('page=password');
if ($page === 'login' && $user && empty($user['must_change_password'])) campus_redirect();

$sections = [];
$section = null;
$posts = [];
$sectionPostCount = 0;
$filteredPostCount = 0;
$postPage = 1;
$postPageCount = 1;
$postSearch = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$paginationStart = 1;
$paginationEnd = 1;
$events = [];
$editingEvent = null;
$accounts = [];
$sectionMembers = [];
$members = [];
$submissionsByPost = [];
$homeItems = [];
$editingHomeItem = null;

if ($setupDone && $user && $page !== 'password') {
    if (campus_is_admin($user)) {
        $homeItems = $pdo->query('SELECT * FROM campus_home_items ORDER BY FIELD(category, \'workshops\', \'therapies\', \'objects\'), sort_order, id')->fetchAll();
        $editItemId = (int)($_GET['edit_home_item'] ?? 0);
        if ($editItemId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM campus_home_items WHERE id = ? LIMIT 1');
            $stmt->execute([$editItemId]);
            $editingHomeItem = $stmt->fetch() ?: null;
        }
    }
    if (campus_is_admin($user)) {
        $stmt = $pdo->query('SELECT s.*, (SELECT COUNT(*) FROM campus_section_members sm WHERE sm.section_id = s.id AND sm.section_role = \'member\') AS member_count FROM campus_sections s ORDER BY (s.status = \'published\') DESC, s.created_at DESC');
        $sections = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM campus_section_members sm2 WHERE sm2.section_id = s.id AND sm2.section_role = 'member') AS member_count
            FROM campus_sections s JOIN campus_section_members sm ON sm.section_id = s.id
            WHERE sm.user_id = ? AND s.status = 'published' ORDER BY s.created_at DESC");
        $stmt->execute([(int)$user['id']]);
        $sections = $stmt->fetchAll();
    }

    $selectedId = (int)($_GET['section'] ?? ($sections[0]['id'] ?? 0));
    foreach ($sections as $candidate) if ((int)$candidate['id'] === $selectedId) $section = $candidate;
    if ($section && !campus_can_view_section($selectedId, $user)) $section = null;

    if ($section) {
        $postsPerPage = 8;
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM campus_posts WHERE section_id = ? AND status = 'published'");
        $countStmt->execute([$selectedId]);
        $sectionPostCount = (int)$countStmt->fetchColumn();
        $wherePosts = "p.section_id = ? AND p.status = 'published'";
        $postQueryParams = [$selectedId];
        if ($postSearch !== '') {
            $wherePosts .= ' AND (p.title LIKE ? OR p.body LIKE ?)';
            $searchPattern = '%' . $postSearch . '%';
            $postQueryParams[] = $searchPattern;
            $postQueryParams[] = $searchPattern;
        }
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM campus_posts p WHERE {$wherePosts}");
        $countStmt->execute($postQueryParams);
        $filteredPostCount = (int)$countStmt->fetchColumn();
        $requestedPostPage = filter_var($_GET['post_page'] ?? 1, FILTER_VALIDATE_INT);
        $postPage = is_int($requestedPostPage) && $requestedPostPage > 0 ? $requestedPostPage : 1;
        $postPageCount = max(1, (int)ceil($filteredPostCount / $postsPerPage));
        $postPage = min($postPage, $postPageCount);
        $postOffset = ($postPage - 1) * $postsPerPage;
        $paginationStart = max(1, $postPage - 2);
        $paginationEnd = min($postPageCount, $postPage + 2);

        $stmt = $pdo->prepare("SELECT p.*, u.name AS author_name FROM campus_posts p JOIN campus_users u ON u.id = p.author_id
            WHERE {$wherePosts} ORDER BY p.created_at DESC, p.id DESC LIMIT ? OFFSET ?");
        $parameterIndex = 1;
        foreach ($postQueryParams as $parameter) {
            $stmt->bindValue($parameterIndex++, $parameter, is_int($parameter) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue($parameterIndex++, $postsPerPage, PDO::PARAM_INT);
        $stmt->bindValue($parameterIndex, $postOffset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();
        $ids = array_map(static fn(array $post): int => (int)$post['id'], $posts);
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM campus_post_attachments WHERE post_id IN ({$marks}) ORDER BY id ASC");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $attachment) $attachmentsByPost[(int)$attachment['post_id']][] = $attachment;
            if (campus_can_manage_section($selectedId, $user)) {
                $stmt = $pdo->prepare("SELECT s.*, u.name AS member_name, p.id AS activity_id FROM campus_submissions s
                    JOIN campus_users u ON u.id = s.member_id JOIN campus_posts p ON p.id = s.post_id
                    WHERE p.section_id = ? ORDER BY s.submitted_at DESC");
                $stmt->execute([$selectedId]);
                foreach ($stmt->fetchAll() as $submission) $submissionsByPost[(int)$submission['post_id']][] = $submission;
            } else {
                $stmt = $pdo->prepare('SELECT s.* FROM campus_submissions s JOIN campus_posts p ON p.id = s.post_id WHERE p.section_id = ? AND s.member_id = ?');
                $stmt->execute([$selectedId, (int)$user['id']]);
                foreach ($stmt->fetchAll() as $submission) $submissionsByPost[(int)$submission['post_id']][] = $submission;
            }
        }
        $now = date('Y-m-d H:i:s');
        $eventStmt = $pdo->prepare('SELECT e.* FROM campus_events e WHERE e.section_id = ? AND COALESCE(e.ends_at, e.starts_at) >= ? ORDER BY e.starts_at ASC');
        $eventStmt->execute([$selectedId, $now]);
        $events = $eventStmt->fetchAll();
        if (campus_can_manage_section($selectedId, $user)) {
            $editEventId = (int)($_GET['edit_event'] ?? 0);
            if ($editEventId > 0) {
                $eventStmt = $pdo->prepare('SELECT * FROM campus_events WHERE id = ? AND section_id = ? LIMIT 1');
                $eventStmt->execute([$editEventId, $selectedId]);
                $editingEvent = $eventStmt->fetch() ?: null;
            }
        }
        if (campus_is_admin($user)) {
            $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.role, sm.user_id IS NOT NULL AS assigned_to_section
                FROM campus_users u
                LEFT JOIN campus_section_members sm ON sm.user_id = u.id AND sm.section_id = ?
                WHERE u.status = 'active' AND u.role <> 'admin' ORDER BY u.name");
            $stmt->execute([$selectedId]);
            $accounts = $stmt->fetchAll();
            $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, sm.section_role FROM campus_section_members sm JOIN campus_users u ON u.id = sm.user_id WHERE sm.section_id = ? ORDER BY sm.section_role, u.name');
            $stmt->execute([$selectedId]);
            $sectionMembers = $stmt->fetchAll();
        }
    }
}

require __DIR__ . '/view.php';
