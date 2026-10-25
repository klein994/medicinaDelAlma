<?php
declare(strict_types=1);

$campusConfigPath = dirname(__DIR__) . '/config.php';
if (!is_file($campusConfigPath)) {
    http_response_code(503);
    exit('El campus aún no está configurado. Revisa campus/README.md.');
}
$campusConfig = require $campusConfigPath;
$campusTimezone = (string)($campusConfig['timezone'] ?? 'America/Argentina/Buenos_Aires');
date_default_timezone_set(in_array($campusTimezone, timezone_identifiers_list(), true) ? $campusTimezone : 'America/Argentina/Buenos_Aires');
define('CAMPUS_PUBLIC_URL', rtrim((string)($campusConfig['public_site_url'] ?? 'https://medicinadelalma.netlify.app/'), '/') . '/');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('medicina_campus');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

function campus_db(): PDO
{
    static $pdo = null;
    global $campusConfig;
    if ($pdo instanceof PDO) return $pdo;

    $host = (string)($campusConfig['db_host'] ?? 'localhost');
    $name = (string)($campusConfig['db_name'] ?? '');
    $charset = (string)($campusConfig['db_charset'] ?? 'utf8mb4');
    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $configuredPort = $campusConfig['db_port'] ?? null;
    if ($configuredPort !== null && $configuredPort !== '') {
        $port = filter_var($configuredPort, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port === false) throw new RuntimeException('El puerto de MySQL en campus/config.php no es válido.');
        $dsn .= ";port={$port}";
    }
    $pdo = new PDO($dsn, (string)($campusConfig['db_user'] ?? ''), (string)($campusConfig['db_pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function campus_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function campus_csrf_token(): string
{
    if (empty($_SESSION['campus_csrf'])) $_SESSION['campus_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['campus_csrf'];
}

function campus_verify_csrf(): void
{
    $sent = (string)($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(campus_csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión del formulario venció. Regresa e inténtalo otra vez.');
    }
}

function campus_flash(string $message, string $type = 'success'): void
{
    $_SESSION['campus_flash'] = ['message' => $message, 'type' => $type];
}

function campus_redirect(string $query = ''): never
{
    $base = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    header('Location: ' . $base . ($query !== '' ? '?' . $query : ''));
    exit;
}

function campus_current_user(): ?array
{
    return isset($_SESSION['campus_user']) && is_array($_SESSION['campus_user'])
        ? $_SESSION['campus_user']
        : null;
}

function campus_require_login(): array
{
    $user = campus_current_user();
    if (!$user) campus_redirect('page=login');
    return $user;
}

function campus_is_admin(?array $user = null): bool
{
    $user ??= campus_current_user();
    return ($user['role'] ?? '') === 'admin';
}

function campus_can_manage_section(int $sectionId, ?array $user = null): bool
{
    $user ??= campus_current_user();
    if (!$user) return false;
    if (campus_is_admin($user)) return true;
    if (($user['role'] ?? '') !== 'facilitator') return false;

    $stmt = campus_db()->prepare("SELECT 1 FROM campus_section_members
        WHERE section_id = ? AND user_id = ? AND section_role = 'facilitator' LIMIT 1");
    $stmt->execute([$sectionId, (int)$user['id']]);
    return (bool)$stmt->fetchColumn();
}

function campus_can_view_section(int $sectionId, ?array $user = null): bool
{
    $user ??= campus_current_user();
    if (!$user) return false;
    if (campus_is_admin($user)) return true;

    $stmt = campus_db()->prepare('SELECT 1 FROM campus_section_members WHERE section_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$sectionId, (int)$user['id']]);
    return (bool)$stmt->fetchColumn();
}

function campus_require_section(int $sectionId, bool $manage = false): void
{
    $allowed = $manage ? campus_can_manage_section($sectionId) : campus_can_view_section($sectionId);
    if (!$allowed) {
        http_response_code(403);
        exit('No tienes acceso a esta sección.');
    }
}

function campus_store_upload(array $upload): array
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo cargar el archivo.');
    if (($upload['size'] ?? 0) < 1 || $upload['size'] > 15 * 1024 * 1024) throw new RuntimeException('Cada archivo debe pesar menos de 15 MB.');
    if (!is_uploaded_file((string)$upload['tmp_name'])) throw new RuntimeException('El archivo recibido no es válido.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string)$finfo->file($upload['tmp_name']));
    $extension = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
    $allowedTypes = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
        'png' => ['image/png'], 'webp' => ['image/webp'], 'gif' => ['image/gif'],
        'txt' => ['text/plain'], 'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'html' => ['text/html', 'application/xhtml+xml'], 'htm' => ['text/html', 'application/xhtml+xml'],
        'mp4' => ['video/mp4'], 'webm' => ['video/webm'], 'mov' => ['video/quicktime'],
        'mp3' => ['audio/mpeg'], 'wav' => ['audio/wav', 'audio/x-wav'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'], 'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
        'oga' => ['audio/ogg'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'rtf' => ['application/rtf', 'text/rtf'],
    ];
    if (!isset($allowedTypes[$extension]) || !in_array($mime, $allowedTypes[$extension], true)) {
        throw new RuntimeException('Formato no permitido. Puedes adjuntar imágenes, PDF, HTML, audio, video o documentos de Office.');
    }

    $storedExtension = match ($extension) { 'jpeg' => 'jpg', 'htm' => 'html', default => $extension };
    $storedName = bin2hex(random_bytes(24)) . '.' . $storedExtension;
    $directory = dirname(__DIR__) . '/storage/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento de archivos.');
    }
    if (!move_uploaded_file((string)$upload['tmp_name'], $directory . '/' . $storedName)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }

    return [
        'stored_name' => $storedName,
        'original_name' => mb_substr(basename((string)$upload['name']), 0, 255),
        'mime_type' => $mime,
        'file_size' => (int)$upload['size'],
    ];
}

function campus_store_home_image(array $upload): array
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo subir la imagen. Comprueba su tamaño e inténtalo de nuevo.');
    if (($upload['size'] ?? 0) < 1 || $upload['size'] > 12 * 1024 * 1024) throw new RuntimeException('La imagen debe pesar menos de 12 MB.');
    if (!is_uploaded_file((string)$upload['tmp_name'])) throw new RuntimeException('La imagen recibida no es válida.');

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^(?:[A-Za-z0-9.-]+|\[[A-Fa-f0-9:]+\])(?::[0-9]{1,5})?$/D', $host)) {
        throw new RuntimeException('No se pudo determinar la dirección pública del campus para guardar la imagen.');
    }

    $extension = strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION));
    $allowedMimes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string)$finfo->file($upload['tmp_name']));
    $imageInfo = @getimagesize((string)$upload['tmp_name']);
    if (!isset($allowedMimes[$extension]) || $allowedMimes[$extension] !== $mime || !$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) {
        throw new RuntimeException('Formato de imagen no válido. Usa JPG, PNG, WebP o GIF.');
    }

    $storedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
    $storedName = bin2hex(random_bytes(24)) . '.' . $storedExtension;
    $directory = dirname(__DIR__) . '/public/uploads/home';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar la carpeta pública de imágenes.');
    }
    if (!move_uploaded_file((string)$upload['tmp_name'], $directory . '/' . $storedName)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (string)($_SERVER['REQUEST_SCHEME'] ?? '') === 'https'
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    return [
        'stored_name' => $storedName,
        'url' => ($secure ? 'https://' : 'http://') . $host . '/uploads/home/' . $storedName,
    ];
}

function campus_store_uploads(array $uploads): array
{
    if (!isset($uploads['name']) || !is_array($uploads['name'])) return [];
    $fileCount = count(array_filter($uploads['error'] ?? [], static fn($error): bool => (int)$error !== UPLOAD_ERR_NO_FILE));
    if ($fileCount > 10) throw new RuntimeException('Puedes adjuntar hasta 10 archivos por publicación.');

    $stored = [];
    try {
        foreach ($uploads['name'] as $index => $name) {
            $file = [
                'name' => $name,
                'type' => $uploads['type'][$index] ?? '',
                'tmp_name' => $uploads['tmp_name'][$index] ?? '',
                'error' => $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploads['size'][$index] ?? 0,
            ];
            $saved = campus_store_upload($file);
            if ($saved) $stored[] = $saved;
        }
    } catch (Throwable $exception) {
        foreach ($stored as $file) {
            $path = dirname(__DIR__) . '/storage/uploads/' . basename($file['stored_name']);
            if (is_file($path)) @unlink($path);
        }
        throw $exception;
    }
    return $stored;
}

function campus_attachment_kind(array $file): string
{
    $mime = strtolower((string)($file['mime_type'] ?? ''));
    $extension = strtolower(pathinfo((string)($file['original_name'] ?? ''), PATHINFO_EXTENSION));
    if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) return 'image';
    if ($mime === 'application/pdf' || $extension === 'pdf') return 'pdf';
    if (in_array($extension, ['html', 'htm'], true) || $mime === 'text/html') return 'html';
    if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'mov'], true)) return 'video';
    if (str_starts_with($mime, 'audio/') || in_array($extension, ['mp3', 'wav', 'm4a', 'ogg', 'oga'], true)) return 'audio';
    if (str_starts_with($mime, 'text/')) return 'text';
    return 'document';
}

function campus_format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    return number_format(max(1, $bytes) / 1024, 0, ',', '.') . ' KB';
}

function campus_home_url(?string $value, string $fieldLabel): ?string
{
    $url = trim((string)$value);
    if ($url === '') return null;
    if (strlen($url) > 1000 || preg_match('/[\x00-\x20]/', $url) || str_contains($url, chr(92))) {
        throw new RuntimeException($fieldLabel . ' no es válido.');
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme !== null) {
        if (!in_array(strtolower((string)$scheme), ['http', 'https'], true) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException($fieldLabel . ' debe usar una dirección HTTPS o HTTP válida.');
        }
        return $url;
    }

    if (str_starts_with($url, '//') || !preg_match('/^(?:\/|#|[A-Za-z0-9])/', $url) || preg_match('/[<>"\']/', $url)) {
        throw new RuntimeException($fieldLabel . ' debe ser un enlace del sitio o una dirección HTTPS.');
    }
    return $url;
}

function campus_slugify(string $value): string
{
    $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', trim($value)) ?? $value;
    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) $value = $ascii;
    }
    $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? $value);
    return trim(substr($slug, 0, 100), '-');
}

function campus_workshop_slug(string $targetUrl, string $title): string
{
    $query = (string)(parse_url($targetUrl, PHP_URL_QUERY) ?? '');
    parse_str($query, $queryParameters);
    if (!empty($queryParameters['slug'])) {
        return campus_slugify((string)$queryParameters['slug']);
    }

    $path = (string)(parse_url($targetUrl, PHP_URL_PATH) ?: $targetUrl);
    $segments = array_values(array_filter(explode('/', rawurldecode($path)), static fn(string $part): bool => $part !== ''));
    $workshopsIndex = array_search('talleres', $segments, true);
    $targetHost = strtolower((string)parse_url($targetUrl, PHP_URL_HOST));
    $isExternalDestination = parse_url($targetUrl, PHP_URL_SCHEME) !== null
        && !in_array($targetHost, ['localhost', '127.0.0.1', '::1'], true);
    if ($workshopsIndex !== false && isset($segments[$workshopsIndex + 1])) {
        $candidate = $segments[$workshopsIndex + 1];
    } elseif ($isExternalDestination) {
        $candidate = '';
    } else {
        $candidate = (string)end($segments);
        if (preg_match('/^index\.html?$/i', $candidate) && count($segments) > 1) {
            $candidate = $segments[count($segments) - 2];
        }
    }
    $candidate = preg_replace('/\.html?$/i', '', $candidate) ?? $candidate;
    return campus_slugify($candidate !== '' ? $candidate : $title);
}

function campus_find_public_page(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM campus_public_pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
    if ($page) return $page;

    foreach ($pdo->query('SELECT id, slug FROM campus_public_pages')->fetchAll() as $candidate) {
        if (campus_slugify((string)$candidate['slug']) !== $slug) continue;
        $stmt = $pdo->prepare('SELECT * FROM campus_public_pages WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$candidate['id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

function campus_ensure_workshop_page(PDO $pdo, array $item): array
{
    $slug = campus_workshop_slug((string)($item['target_url'] ?? ''), (string)$item['title']);
    $page = campus_find_public_page($pdo, $slug);
    if ($page) return $page;

    $title = mb_substr(trim((string)$item['title']), 0, 200);
    $description = trim((string)($item['description'] ?? ''));
    $imageUrl = $item['image_url'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO campus_public_pages
        (slug, title, eyebrow, headline, `lead`, image_url, intro_title, intro_body, status, sort_order)
        VALUES (?, ?, 'Taller · Formación', ?, ?, ?, 'Sobre esta propuesta', ?, 'published', ?)");
    $stmt->execute([$slug, $title, mb_substr($title, 0, 240), $description ?: null,
        $imageUrl, $description ?: null, (int)($item['sort_order'] ?? 0)]);
    $pageId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM campus_public_pages WHERE id = ? LIMIT 1');
    $stmt->execute([$pageId]);
    $page = $stmt->fetch();
    if (!$page) throw new RuntimeException('No se pudo crear la ficha del taller.');
    return $page;
}
