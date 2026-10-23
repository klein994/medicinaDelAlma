<?php
declare(strict_types=1);

$csrf = campus_csrf_token();
$flash = $flash ?: null;
$attachmentsByPost = $attachmentsByPost ?? [];
$pageTitle = 'Campus · Medicina del Alma';
$roleLabels = ['admin' => 'Administración', 'facilitator' => 'Facilitación', 'member' => 'Participante'];
$statusLabels = ['submitted' => 'Recibida', 'in_review' => 'En revisión', 'needs_changes' => 'Requiere ajustes', 'approved' => 'Completada'];
$homeCategories = ['workshops' => 'Talleres y formaciones', 'therapies' => 'Terapias', 'objects' => 'Objetos para acompañar tu práctica'];
$editingHomeItem = $editingHomeItem ?: ['id' => 0, 'category' => 'workshops', 'title' => '', 'description' => '', 'image_url' => '', 'target_url' => '', 'link_label' => 'Saber más', 'sort_order' => 0, 'status' => 'published'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f2ec">
    <title><?= campus_h($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/campus.css">
    <script src="assets/campus.js" defer></script>
</head>
<body class="campus-body">
<?php if (!$setupDone || !$user || $page === 'login' || $page === 'setup' || $page === 'password'): ?>
    <main class="auth-shell">
        <section class="auth-card">
            <a class="brand-mark" href="./" aria-label="Campus Medicina del Alma">
                <img src="assets/medicina-logo.png" alt="">
            </a>
            <span class="eyebrow">Medicina del Alma</span>
            <?php if (!$setupDone || $page === 'setup'): ?>
                <h1>Tu espacio de encuentro empieza aquí.</h1>
                <p class="muted">Crea la cuenta de administración para empezar a organizar talleres, actividades y comunidad.</p>
                <?php if ($errorMessage): ?><div class="alert alert-error"><?= campus_h($errorMessage) ?></div><?php endif; ?>
                <form method="post" class="form-stack">
                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>">
                    <input type="hidden" name="action" value="setup">
                    <label>Nombre<input name="name" autocomplete="name" required maxlength="160"></label>
                    <label>Correo de administración<input type="email" name="email" autocomplete="email" required maxlength="190"></label>
                    <label>Contraseña segura<input type="password" name="password" autocomplete="new-password" minlength="12" required><small>Usa al menos 12 caracteres.</small></label>
                    <button class="button button-primary" type="submit">Crear administración</button>
                </form>
            <?php elseif ($page === 'password'): ?>
                <h1>Actualiza tu contraseña</h1>
                <p class="muted">Por seguridad, cambia la contraseña temporal antes de continuar.</p>
                <?php if ($errorMessage): ?><div class="alert alert-error"><?= campus_h($errorMessage) ?></div><?php endif; ?>
                <form method="post" class="form-stack">
                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>">
                    <input type="hidden" name="action" value="change_password">
                    <label>Contraseña temporal<input type="password" name="current_password" autocomplete="current-password" required></label>
                    <label>Nueva contraseña<input type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
                    <label>Repite la nueva contraseña<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></label>
                    <button class="button button-primary" type="submit">Guardar contraseña</button>
                </form>
            <?php else: ?>
                <h1>Un lugar para acompañar tu proceso.</h1>
                <p class="muted">Ingresa al campus para encontrar tus espacios, materiales y actividades.</p>
                <?php if ($errorMessage): ?><div class="alert alert-error"><?= campus_h($errorMessage) ?></div><?php endif; ?>
                <form method="post" class="form-stack">
                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>">
                    <input type="hidden" name="action" value="login">
                    <label>Correo electrónico<input type="email" name="email" autocomplete="username" required></label>
                    <label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
                    <button class="button button-primary" type="submit">Entrar al campus</button>
                </form>
                <a class="text-link back-link" href="<?= campus_h(CAMPUS_PUBLIC_URL) ?>">Volver a Medicina del Alma</a>
            <?php endif; ?>
            <p class="auth-footnote">Un espacio cuidado para aprender, compartir y crecer.</p>
        </section>
    </main>
<?php else: ?>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="sidebar-brand" href="./">
                <img src="assets/medicina-logo.png" alt="">
                <span><strong>Medicina del Alma</strong><small>Campus</small></span>
            </a>
            <div class="sidebar-label">Tus espacios</div>
            <nav class="section-nav" aria-label="Secciones del campus">
                <?php foreach ($sections as $item): ?>
                    <a class="section-nav__item <?= $section && (int)$section['id'] === (int)$item['id'] ? 'is-active' : '' ?>" href="?section=<?= (int)$item['id'] ?>">
                        <span class="section-nav__dot"></span>
                        <span><?= campus_h($item['title']) ?></span>
                        <?php if ($item['status'] === 'archived'): ?><small>Archivada</small><?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if (campus_is_admin($user)): ?><a class="section-nav__item <?= $page === 'home_content' ? 'is-active' : '' ?>" href="?page=home_content"><span class="section-nav__dot"></span><span>Contenido de la home</span></a><?php endif; ?>
            </nav>
            <?php if (!$sections): ?><p class="sidebar-empty">Aún no tienes secciones asignadas.</p><?php endif; ?>
            <div class="sidebar-bottom">
                <div class="user-chip"><span class="avatar"><?= campus_h(mb_strtoupper(mb_substr((string)$user['name'], 0, 1))) ?></span><span><strong><?= campus_h($user['name']) ?></strong><small><?= campus_h($roleLabels[$user['role']] ?? 'Participante') ?></small></span></div>
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button class="text-button" type="submit">Cerrar sesión <span aria-hidden="true">↗</span></button>
                </form>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <a class="mobile-brand" href="./"><img src="assets/medicina-logo.png" alt=""><span>Medicina del Alma <small>Campus</small></span></a>
                <span class="topbar-note">Un espacio para acompañar tu camino</span>
                <span class="topbar-user"><?= campus_h($user['name']) ?></span>
            </header>

            <div class="page-wrap">
                <?php if ($flash): ?><div class="alert alert-<?= campus_h($flash['type'] ?? 'success') ?>" role="status"><?= campus_h($flash['message'] ?? '') ?></div><?php endif; ?>
                <?php if ($errorMessage): ?><div class="alert alert-error" role="alert"><?= campus_h($errorMessage) ?></div><?php endif; ?>

                <?php if ($page === 'home_content' && campus_is_admin($user)): ?>
                    <section class="section-hero home-admin-hero"><div><span class="eyebrow">Web pública</span><h1>Contenido de la home</h1><p>Administra las propuestas que aparecen en talleres, terapias y objetos.</p></div></section>
                    <div class="home-admin-layout">
                        <section class="panel home-editor">
                            <div class="panel-heading"><div><span class="eyebrow"><?= !empty($editingHomeItem['id']) ? 'Editar propuesta' : 'Agregar propuesta' ?></span><h2><?= !empty($editingHomeItem['id']) ? campus_h($editingHomeItem['title']) : 'Nueva tarjeta' ?></h2></div><span class="panel-icon">✦</span></div>
                            <?php if (!empty($editingHomeItem['id'])): ?><a class="text-link home-new-link" href="?page=home_content">+ Agregar otra</a><?php endif; ?>
                            <form method="post" class="form-stack">
                                <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="save_home_item"><input type="hidden" name="item_id" value="<?= (int)$editingHomeItem['id'] ?>">
                                <label>Área<select name="category"><option value="workshops" <?= $editingHomeItem['category'] === 'workshops' ? 'selected' : '' ?>>Talleres y formaciones</option><option value="therapies" <?= $editingHomeItem['category'] === 'therapies' ? 'selected' : '' ?>>Terapias</option><option value="objects" <?= $editingHomeItem['category'] === 'objects' ? 'selected' : '' ?>>Objetos para acompañar tu práctica</option></select></label>
                                <label>Nombre<input name="title" value="<?= campus_h($editingHomeItem['title']) ?>" maxlength="200" required></label>
                                <label>Descripción<textarea name="description" rows="4"><?= campus_h($editingHomeItem['description'] ?? '') ?></textarea></label>
                                <label>URL de imagen<input type="text" name="image_url" value="<?= campus_h($editingHomeItem['image_url'] ?? '') ?>" placeholder="https://... o images/features/imagen.jpg"><small>Puede ser una URL pública o una ruta dentro del sitio.</small></label>
                                <label>Enlace de destino<input name="target_url" value="<?= campus_h($editingHomeItem['target_url'] ?? '') ?>" placeholder="reikiUnitario/ o URL de Mercado Libre"><small>Para terapias puedes usar #contact.</small></label>
                                <div class="form-row"><label>Texto del enlace<input name="link_label" value="<?= campus_h($editingHomeItem['link_label']) ?>" maxlength="80"></label><label>Orden<input type="number" name="sort_order" min="0" value="<?= (int)$editingHomeItem['sort_order'] ?>"></label></div>
                                <label class="checkbox-label"><input type="checkbox" name="is_visible" value="1" <?= $editingHomeItem['status'] === 'published' ? 'checked' : '' ?>> Mostrar en la home</label>
                                <button class="button button-primary" type="submit">Guardar contenido</button>
                            </form>
                        </section>
                        <div class="home-items-column">
                            <?php foreach ($homeCategories as $categoryKey => $categoryTitle): ?>
                                <?php $categoryItems = array_values(array_filter($homeItems, static fn(array $item): bool => $item['category'] === $categoryKey)); ?>
                                <section class="panel home-items-panel"><div class="home-items-heading"><div><span class="eyebrow">Home pública</span><h2><?= campus_h($categoryTitle) ?></h2></div><span class="item-count"><?= count($categoryItems) ?></span></div>
                                    <?php if (!$categoryItems): ?><p class="muted">No hay elementos en esta área.</p><?php endif; ?>
                                    <?php foreach ($categoryItems as $homeItem): ?>
                                        <article class="home-item-row <?= $homeItem['status'] === 'hidden' ? 'is-hidden' : '' ?>">
                                            <div class="home-item-row__copy"><strong><?= campus_h($homeItem['title']) ?></strong><small><?= $homeItem['status'] === 'published' ? 'Visible' : 'Oculto' ?> · orden <?= (int)$homeItem['sort_order'] ?></small></div>
                                            <div class="home-item-row__actions"><a class="text-link" href="?page=home_content&amp;edit_home_item=<?= (int)$homeItem['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Quitar este elemento de la home?');"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="delete_home_item"><input type="hidden" name="item_id" value="<?= (int)$homeItem['id'] ?>"><button class="remove-member" type="submit">Quitar</button></form></div>
                                        </article>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif (!$section): ?>
                    <section class="welcome-panel">
                        <span class="eyebrow">Bienvenida, <?= campus_h(explode(' ', (string)$user['name'])[0]) ?></span>
                        <h1>Un camino se recorre mejor en compañía.</h1>
                        <p>Elige una sección para ver sus publicaciones y actividades.</p>
                        <?php if (!$sections && campus_is_admin($user)): ?>
                            <div class="empty-state"><span class="empty-state__icon">✳</span><h2>Crea tu primera sección</h2><p>Organiza el campus por taller, comunidad o recorrido.</p></div>
                        <?php elseif (!$sections): ?>
                            <div class="empty-state"><span class="empty-state__icon">✳</span><h2>Pronto habrá novedades</h2><p>Cuando te asignen a una sección, la verás aquí.</p></div>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <?php $canManageSelectedSection = campus_can_manage_section((int)$section['id'], $user); ?>
                    <section class="section-hero">
                        <div>
                            <span class="eyebrow">Tu espacio de comunidad</span>
                            <h1><?= campus_h($section['title']) ?></h1>
                            <?php if (!empty($section['description'])): ?><p><?= nl2br(campus_h($section['description'])) ?></p><?php else: ?><p>Ideas, prácticas y acompañamiento para avanzar a tu ritmo.</p><?php endif; ?>
                        </div>
                        <div class="section-hero__meta"><span><?= (int)$sectionPostCount ?> publicaciones</span><?php if (campus_is_admin($user)): ?><span><?= (int)$section['member_count'] ?> participantes</span><?php endif; ?></div>
                    </section>

                    <section class="panel agenda-panel" id="agenda" aria-label="Agenda de encuentros">
                        <div class="agenda-heading"><div><span class="eyebrow">Próximos encuentros</span><h2>Agenda de la sección</h2></div><span class="agenda-heading__icon" aria-hidden="true">◷</span></div>
                        <?php if ($canManageSelectedSection && $section['status'] === 'published'): ?>
                            <details class="agenda-editor" <?= $editingEvent ? 'open' : '' ?>>
                                <summary><?= $editingEvent ? 'Editar encuentro' : 'Agregar encuentro' ?></summary>
                                <?php if ($editingEvent): ?><a class="text-link agenda-cancel-edit" href="?section=<?= (int)$section['id'] ?>#agenda">Cancelar edición</a><?php endif; ?>
                                <form method="post" class="form-stack agenda-form">
                                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="save_event"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="event_id" value="<?= (int)($editingEvent['id'] ?? 0) ?>">
                                    <label>Nombre del encuentro<input name="title" required maxlength="200" value="<?= campus_h($editingEvent['title'] ?? '') ?>" placeholder="Ej. Meditación grupal"></label>
                                    <label>Descripción<textarea name="description" rows="2" placeholder="¿Qué haremos en este encuentro?"><?= campus_h($editingEvent['description'] ?? '') ?></textarea></label>
                                    <div class="form-row"><label>Comienza<input type="datetime-local" name="starts_at" required value="<?= !empty($editingEvent['starts_at']) ? campus_h(date('Y-m-d\\TH:i', strtotime($editingEvent['starts_at']))) : '' ?>"></label><label>Finaliza <span class="muted">(opcional)</span><input type="datetime-local" name="ends_at" value="<?= !empty($editingEvent['ends_at']) ? campus_h(date('Y-m-d\\TH:i', strtotime($editingEvent['ends_at']))) : '' ?>"></label></div>
                                    <div class="form-row"><label>Lugar <span class="muted">(opcional)</span><input name="location" maxlength="255" value="<?= campus_h($editingEvent['location'] ?? '') ?>" placeholder="Online o dirección"></label><label>Enlace <span class="muted">(opcional)</span><input type="url" name="meeting_url" maxlength="1000" value="<?= campus_h($editingEvent['meeting_url'] ?? '') ?>" placeholder="https://..."></label></div>
                                    <button class="button button-primary" type="submit"><?= $editingEvent ? 'Guardar encuentro' : 'Agregar a la agenda' ?></button>
                                </form>
                            </details>
                        <?php endif; ?>
                        <?php if ($events): ?>
                            <div class="agenda-list">
                                <?php foreach ($events as $event): ?>
                                    <article class="agenda-event">
                                        <time class="agenda-event__date" datetime="<?= campus_h(date('Y-m-d\\TH:i:s', strtotime($event['starts_at']))) ?>"><strong><?= campus_h(date('d', strtotime($event['starts_at']))) ?></strong><span><?= campus_h(date('m/Y', strtotime($event['starts_at']))) ?></span></time>
                                        <div class="agenda-event__body"><h3><?= campus_h($event['title']) ?></h3><p class="agenda-event__time"><?= campus_h(date('d/m/Y · H:i', strtotime($event['starts_at']))) ?><?= $event['ends_at'] ? ' – ' . campus_h(date('H:i', strtotime($event['ends_at']))) : '' ?></p>
                                            <?php if (!empty($event['description'])): ?><p class="agenda-event__description"><?= nl2br(campus_h($event['description'])) ?></p><?php endif; ?>
                                            <?php if (!empty($event['location'])): ?><p class="agenda-event__location"><?= campus_h($event['location']) ?></p><?php endif; ?>
                                            <?php if (!empty($event['meeting_url'])): ?><a class="button button-small" href="<?= campus_h($event['meeting_url']) ?>" target="_blank" rel="noopener noreferrer">Ir al encuentro <span aria-hidden="true">↗</span></a><?php endif; ?>
                                        </div>
                                        <?php if ($canManageSelectedSection && $section['status'] === 'published'): ?><div class="agenda-event__actions"><a class="text-link" href="?section=<?= (int)$section['id'] ?>&amp;edit_event=<?= (int)$event['id'] ?>#agenda">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar este encuentro de la agenda?');"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="delete_event"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>"><button class="text-button" type="submit">Eliminar</button></form></div><?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="agenda-empty">Todavía no hay encuentros programados para esta sección.</p>
                        <?php endif; ?>
                    </section>

                    <div class="content-grid">
                        <section class="feed-column" id="post-feed" aria-label="Muro de la sección">
                            <?php if ($canManageSelectedSection && $section['status'] === 'published'): ?>
                                <section class="panel composer-panel">
                                    <div class="panel-heading"><div><span class="eyebrow">Comparte con la comunidad</span><h2>Nueva publicación</h2></div><span class="panel-icon">✦</span></div>
                                    <form method="post" enctype="multipart/form-data" class="form-stack">
                                        <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>">
                                        <input type="hidden" name="action" value="create_post">
                                        <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                                        <div class="form-row"><label>Tipo<select name="post_type"><option value="post">Publicación</option><option value="announcement">Aviso importante</option><option value="activity">Actividad</option></select></label><label class="due-field">Fecha límite<input type="datetime-local" name="due_at"></label></div>
                                        <label>Título<input name="title" required maxlength="200" placeholder="¿Qué quieres compartir?"></label>
                                        <label>Contenido<textarea name="body" rows="4" required placeholder="Escribe un mensaje claro y cercano..."></textarea></label>
                                        <div class="form-row form-row--bottom"><label class="file-label">Adjuntar archivos <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.html,.htm,.txt,.csv,.mp4,.webm,.mov,.mp3,.wav,.m4a,.ogg,.oga,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.rtf"><small>Hasta 10 archivos · 15 MB cada uno · imágenes, PDF, HTML, audio, video y documentos</small><small class="upload-feedback" data-upload-feedback aria-live="polite"></small></label><button class="button button-primary" type="submit">Publicar <span aria-hidden="true">↗</span></button></div>
                                    </form>
                                </section>
                            <?php endif; ?>

                            <form method="get" action="#post-feed" class="post-search" role="search" aria-label="Buscar publicaciones">
                                <input type="hidden" name="section" value="<?= (int)$section['id'] ?>">
                                <label for="post-search-input">Buscar en las publicaciones</label>
                                <div><input id="post-search-input" type="search" name="q" maxlength="100" value="<?= campus_h($postSearch) ?>" placeholder="Título o contenido"><button class="button button-secondary" type="submit">Buscar</button></div>
                                <?php if ($postSearch !== ''): ?><a class="text-link" href="?section=<?= (int)$section['id'] ?>#post-feed">Limpiar búsqueda</a><?php endif; ?>
                            </form>

                            <?php if (!$posts): ?>
                                <div class="empty-state feed-empty"><span class="empty-state__icon">✳</span><h2><?= $postSearch !== '' ? 'No encontramos publicaciones' : 'Este espacio está comenzando' ?></h2><p><?= $postSearch !== '' ? 'Prueba con otras palabras o limpia la búsqueda.' : 'Cuando haya nuevas publicaciones, aparecerán aquí.' ?></p></div>
                            <?php endif; ?>

                            <?php foreach ($posts as $post): ?>
                                <?php $submission = $submissionsByPost[(int)$post['id']][0] ?? null; ?>
                                <article class="panel post-card">
                                    <?php if ($canManageSelectedSection && $section['status'] === 'published'): ?>
                                        <details class="post-management">
                                            <summary>Editar o eliminar</summary>
                                            <form method="post" enctype="multipart/form-data" class="form-stack post-edit-form">
                                                <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="update_post"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                                <div class="form-row"><label>Tipo<select name="post_type"><option value="post" <?= $post['post_type'] === 'post' ? 'selected' : '' ?>>Publicación</option><option value="announcement" <?= $post['post_type'] === 'announcement' ? 'selected' : '' ?>>Aviso importante</option><option value="activity" <?= $post['post_type'] === 'activity' ? 'selected' : '' ?>>Actividad</option></select></label><label>Fecha límite<input type="datetime-local" name="due_at" value="<?= $post['due_at'] ? campus_h(date('Y-m-d\\TH:i', strtotime($post['due_at']))) : '' ?>"><small>Solo se usa para las actividades.</small></label></div>
                                                <label>Título<input name="title" required maxlength="200" value="<?= campus_h($post['title']) ?>"></label>
                                                <label>Contenido<textarea name="body" rows="4" required><?= campus_h($post['body']) ?></textarea></label>
                                                <?php $postAttachments = $attachmentsByPost[(int)$post['id']] ?? []; ?>
                                                <?php if ($postAttachments): ?>
                                                    <fieldset class="post-edit-attachments"><legend>Archivos actuales</legend><small>Deja marcado lo que quieras conservar.</small>
                                                        <?php foreach ($postAttachments as $attachment): ?>
                                                            <label><input type="checkbox" name="keep_attachment_ids[]" value="<?= (int)$attachment['id'] ?>" checked><span><?= campus_h($attachment['original_name']) ?></span></label>
                                                        <?php endforeach; ?>
                                                    </fieldset>
                                                <?php endif; ?>
                                                <label class="file-label">Agregar archivos<input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.html,.htm,.txt,.csv,.mp4,.webm,.mov,.mp3,.wav,.m4a,.ogg,.oga,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.rtf"><small>Hasta 10 archivos en total · 15 MB cada uno</small></label>
                                                <button class="button button-primary" type="submit">Guardar cambios</button>
                                            </form>
                                            <form method="post" class="post-delete-form" onsubmit="return confirm('¿Eliminar esta publicación? También se borrarán las entregas y sus archivos. Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="delete_post"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                                <button class="remove-member" type="submit">Eliminar publicación</button><span>También elimina las entregas recibidas.</span>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                    <div class="post-meta"><span class="post-kind post-kind--<?= campus_h($post['post_type']) ?>"><?= $post['post_type'] === 'activity' ? 'Actividad' : ($post['post_type'] === 'announcement' ? 'Aviso' : 'Comunidad') ?></span><time><?= campus_h(date('d/m/Y', strtotime($post['created_at']))) ?></time></div>
                                    <h2><?= campus_h($post['title']) ?></h2>
                                    <p class="post-author">Por <?= campus_h($post['author_name']) ?></p>
                                    <div class="post-body"><?= nl2br(campus_h($post['body'])) ?></div>
                                    <?php if ($post['due_at']): ?><div class="due-date"><span>Fecha de entrega</span><strong><?= campus_h(date('d/m/Y · H:i', strtotime($post['due_at']))) ?></strong></div><?php endif; ?>
                                    <?php
                                        $postAttachments = $attachmentsByPost[(int)$post['id']] ?? [];
                                        $imageAttachments = array_values(array_filter($postAttachments, static fn(array $file): bool => campus_attachment_kind($file) === 'image'));
                                        $otherAttachments = array_values(array_filter($postAttachments, static fn(array $file): bool => campus_attachment_kind($file) !== 'image'));
                                    ?>
                                    <?php if ($imageAttachments): ?>
                                        <div class="post-attachment-gallery" aria-label="Imágenes adjuntas">
                                            <?php foreach ($imageAttachments as $imageIndex => $attachment): ?>
                                                <?php $previewUrl = '?preview=post&amp;id=' . (int)$attachment['id']; ?>
                                                <button type="button" class="attachment-image-thumb" data-preview-kind="image" data-gallery-id="post-<?= (int)$post['id'] ?>" data-preview-url="<?= $previewUrl ?>" data-preview-title="<?= campus_h($attachment['original_name']) ?>" data-download-url="?download=post&amp;id=<?= (int)$attachment['id'] ?>" aria-label="Ver imagen <?= $imageIndex + 1 ?>: <?= campus_h($attachment['original_name']) ?>">
                                                    <img src="<?= $previewUrl ?>" alt="<?= campus_h($attachment['original_name']) ?>" loading="lazy">
                                                    <span><?= campus_h($attachment['original_name']) ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php foreach ($otherAttachments as $attachment): ?>
                                        <?php
                                            $attachmentKind = campus_attachment_kind($attachment);
                                            $extension = strtoupper(pathinfo((string)$attachment['original_name'], PATHINFO_EXTENSION));
                                            $typeLabel = $extension !== '' ? $extension : strtoupper($attachmentKind);
                                            $canPreview = in_array($attachmentKind, ['pdf', 'html', 'video', 'audio', 'text'], true);
                                        ?>
                                        <div class="attachment-file-card">
                                            <span class="attachment-file-type attachment-file-type--<?= campus_h($attachmentKind) ?>"><?= campus_h($typeLabel) ?></span>
                                            <span class="attachment-file-info"><strong><?= campus_h($attachment['original_name']) ?></strong><small><?= campus_h(campus_format_bytes((int)$attachment['file_size'])) ?> · <?= campus_h($typeLabel) ?></small></span>
                                            <span class="attachment-file-actions">
                                                <?php if ($canPreview): ?><button type="button" class="button button-small attachment-open-preview" data-preview-kind="<?= campus_h($attachmentKind) ?>" data-preview-url="?preview=post&amp;id=<?= (int)$attachment['id'] ?>" data-preview-title="<?= campus_h($attachment['original_name']) ?>" data-download-url="?download=post&amp;id=<?= (int)$attachment['id'] ?>">Vista previa</button><?php endif; ?>
                                                <a class="button button-small button-secondary" href="?download=post&amp;id=<?= (int)$attachment['id'] ?>">Descargar</a>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if ($post['post_type'] === 'activity' && $user['role'] === 'member'): ?>
                                        <div class="activity-area">
                                            <?php if ($submission): ?>
                                                <div class="submission-summary"><span class="status-pill status-<?= campus_h($submission['status']) ?>"><?= campus_h($statusLabels[$submission['status']] ?? $submission['status']) ?></span>
                                                    <?php if (!empty($submission['feedback'])): ?><p><strong>Devolución:</strong> <?= nl2br(campus_h($submission['feedback'])) ?></p><?php endif; ?>
                                                    <?php if (!empty($submission['stored_name'])): ?>
                                                        <?php if (in_array(campus_attachment_kind($submission), ['image', 'pdf', 'html', 'video', 'audio', 'text'], true)): ?><button type="button" class="button button-small attachment-open-preview" data-preview-kind="<?= campus_h(campus_attachment_kind($submission)) ?>" data-preview-url="?preview=submission&amp;id=<?= (int)$submission['id'] ?>" data-preview-title="<?= campus_h($submission['original_name']) ?>" data-download-url="?download=submission&amp;id=<?= (int)$submission['id'] ?>">Ver mi archivo: <?= campus_h($submission['original_name']) ?></button><?php else: ?><a class="text-link" href="?download=submission&amp;id=<?= (int)$submission['id'] ?>">Descargar mi archivo: <?= campus_h($submission['original_name']) ?></a><?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!$submission || $submission['status'] === 'needs_changes'): ?>
                                                <form method="post" enctype="multipart/form-data" class="form-stack submission-form">
                                                    <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="submit_activity"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                                    <label>Tu respuesta<textarea name="response" rows="3" placeholder="Comparte lo que quieras registrar..."></textarea></label>
                                                    <div class="form-row form-row--bottom"><label class="file-label">Adjuntar respuesta <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.html,.htm,.txt,.csv,.mp4,.webm,.mov,.mp3,.wav,.m4a,.ogg,.oga,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.rtf"><small>Opcional si escribes una respuesta · máximo 15 MB</small></label><button class="button button-secondary" type="submit">Enviar actividad</button></div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($post['post_type'] === 'activity' && $canManageSelectedSection): ?>
                                        <div class="review-list"><h3>Entregas · <?= count($submissionsByPost[(int)$post['id']] ?? []) ?></h3>
                                            <?php foreach (($submissionsByPost[(int)$post['id']] ?? []) as $entry): ?>
                                                <div class="review-item"><div class="review-item__head"><strong><?= campus_h($entry['member_name']) ?></strong><span class="status-pill status-<?= campus_h($entry['status']) ?>"><?= campus_h($statusLabels[$entry['status']] ?? $entry['status']) ?></span></div>
                                                    <?php if ($entry['response']): ?><p><?= nl2br(campus_h($entry['response'])) ?></p><?php endif; ?>
                                                    <?php if ($entry['stored_name']): ?>
                                                        <?php if (in_array(campus_attachment_kind($entry), ['image', 'pdf', 'html', 'video', 'audio', 'text'], true)): ?><button type="button" class="button button-small attachment-open-preview" data-preview-kind="<?= campus_h(campus_attachment_kind($entry)) ?>" data-preview-url="?preview=submission&amp;id=<?= (int)$entry['id'] ?>" data-preview-title="<?= campus_h($entry['original_name']) ?>" data-download-url="?download=submission&amp;id=<?= (int)$entry['id'] ?>">Vista previa · <?= campus_h($entry['original_name']) ?></button><?php else: ?><a class="text-link" href="?download=submission&amp;id=<?= (int)$entry['id'] ?>">Descargar <?= campus_h($entry['original_name']) ?></a><?php endif; ?>
                                                    <?php endif; ?>
                                                    <form method="post" class="review-form"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="review_submission"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="submission_id" value="<?= (int)$entry['id'] ?>">
                                                        <label>Devolución<textarea name="feedback" rows="2" placeholder="Escribe una orientación o reconocimiento..."><?= campus_h($entry['feedback'] ?? '') ?></textarea></label>
                                                        <div class="review-form__actions"><select name="status" aria-label="Estado de la entrega"><option value="in_review" <?= $entry['status'] === 'in_review' ? 'selected' : '' ?>>En revisión</option><option value="needs_changes" <?= $entry['status'] === 'needs_changes' ? 'selected' : '' ?>>Solicitar ajustes</option><option value="approved" <?= $entry['status'] === 'approved' ? 'selected' : '' ?>>Marcar completada</option></select><button class="button button-small" type="submit">Guardar revisión</button></div>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($submissionsByPost[(int)$post['id']])): ?><p class="muted">Todavía no hay entregas.</p><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                            <?php if ($filteredPostCount > 0): ?>
                                <?php $paginationBase = ['section' => (int)$section['id']]; if ($postSearch !== '') $paginationBase['q'] = $postSearch; ?>
                                <div class="post-pagination"><span>Mostrando <?= (int)(($postPage - 1) * 8 + 1) ?>–<?= (int)(($postPage - 1) * 8 + count($posts)) ?> de <?= (int)$filteredPostCount ?></span>
                                    <?php if ($postPageCount > 1): ?><nav aria-label="Paginación de publicaciones">
                                        <?php if ($postPage > 1): ?><a href="<?= campus_h('?' . http_build_query($paginationBase + ['post_page' => $postPage - 1]) . '#post-feed') ?>" aria-label="Página anterior">Anterior</a><?php endif; ?>
                                        <?php for ($pageNumber = $paginationStart; $pageNumber <= $paginationEnd; $pageNumber++): ?><a href="<?= campus_h('?' . http_build_query($paginationBase + ['post_page' => $pageNumber]) . '#post-feed') ?>" <?= $pageNumber === $postPage ? 'aria-current="page"' : '' ?>><?= (int)$pageNumber ?></a><?php endfor; ?>
                                        <?php if ($postPage < $postPageCount): ?><a href="<?= campus_h('?' . http_build_query($paginationBase + ['post_page' => $postPage + 1]) . '#post-feed') ?>" aria-label="Página siguiente">Siguiente</a><?php endif; ?>
                                    </nav><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <aside class="right-column">
                            <section class="panel side-panel">
                                <span class="eyebrow">El espacio</span><h2><?= campus_h($section['title']) ?></h2>
                                <p><?= campus_h($section['description'] ?: 'Un espacio para aprender y compartir a tu ritmo.') ?></p>
                                <?php if (campus_is_admin($user)): ?><div class="side-stat"><strong><?= (int)$section['member_count'] ?></strong><span>participantes</span></div><?php endif; ?>
                            </section>

                            <?php if (campus_is_admin($user)): ?>
                                <details class="panel management-panel" open><summary><span class="eyebrow">Administración</span><strong>Crear acceso</strong></summary>
                                    <form method="post" class="form-stack management-form">
                                        <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="create_account"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                                        <label>Nombre<input name="name" required maxlength="160"></label><label>Correo<input type="email" name="email" required maxlength="190"></label>
                                        <label>Rol<select name="role"><option value="member">Participante</option><option value="facilitator">Facilitación</option></select></label>
                                        <label>Contraseña temporal<input type="password" name="temporary_password" required minlength="12"><small>Mínimo 12 caracteres. Deberá cambiarla al ingresar.</small></label>
                                        <button class="button button-secondary" type="submit">Crear cuenta y asignar</button>
                                    </form>
                                </details>
                                <details class="panel management-panel" open><summary><span class="eyebrow">Administración · <?= count($accounts) ?> cuentas</span><strong>Asignar cuenta existente</strong></summary>
                                    <form method="post" class="form-stack management-form"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="add_member"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                                        <label>Cuenta<select name="member_id" required><option value="" disabled selected>Elige una cuenta…</option><?php foreach ($accounts as $account): ?><option value="<?= (int)$account['id'] ?>" <?= !empty($account['assigned_to_section']) ? 'disabled' : '' ?>><?= campus_h($account['name'] . ' · ' . $account['email'] . ' · ' . ($roleLabels[$account['role']] ?? '') . (!empty($account['assigned_to_section']) ? ' · Ya tiene acceso' : ' · Sin acceso a esta sección')) ?></option><?php endforeach; ?></select><small>Las cuentas siguen aquí aunque les quites el acceso. Busca por correo para asignar de nuevo; conserva su contraseña.</small></label>
                                        <button class="button button-secondary" type="submit">Asignar a la sección</button>
                                    </form>
                                </details>
                                <details class="panel management-panel" open><summary><span class="eyebrow">Administración</span><strong>Participantes de esta sección (<?= count($sectionMembers) ?>)</strong></summary>
                                    <div class="member-list">
                                        <?php foreach ($sectionMembers as $sectionMember): ?>
                                            <div class="member-row"><span><strong><?= campus_h($sectionMember['name']) ?></strong><small><?= campus_h(($roleLabels[$sectionMember['section_role'] === 'facilitator' ? 'facilitator' : 'member'] ?? 'Participante') . ' · ' . $sectionMember['email']) ?></small></span><form method="post"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="remove_member"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><input type="hidden" name="member_id" value="<?= (int)$sectionMember['id'] ?>"><button type="submit" class="remove-member" aria-label="Quitar acceso a <?= campus_h($sectionMember['name']) ?>">Quitar</button></form></div>
                                        <?php endforeach; ?>
                                        <?php if (!$sectionMembers): ?><p class="muted">Todavía no hay accesos asignados.</p><?php endif; ?>
                                    </div>
                                </details>
                                <form method="post" class="archive-form" onsubmit="return confirm('¿Archivar esta sección? El contenido quedará guardado.');"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="archive_section"><input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>"><button class="text-button" type="submit">Archivar sección</button></form>
                            <?php endif; ?>
                        </aside>
                    </div>
                <?php endif; ?>

                <?php if (campus_is_admin($user) && $page !== 'home_content'): ?>
                    <section class="admin-create-section panel">
                        <div><span class="eyebrow">Organiza el campus</span><h2>Crear una sección</h2><p>Abre un espacio para un taller, una comunidad o un recorrido.</p></div>
                        <form method="post" class="form-stack section-create-form"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="create_section"><label>Nombre de la sección<input name="title" required maxlength="160" placeholder="Ej. Reiki · Grupo de práctica"></label><label>Descripción<textarea name="description" rows="2" placeholder="Cuenta brevemente qué encontrarán aquí"></textarea></label><button class="button button-primary" type="submit">Crear sección <span aria-hidden="true">↗</span></button></form>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <div class="attachment-modal" id="attachment-preview-modal" hidden>
        <button class="attachment-modal__backdrop" type="button" data-modal-close aria-label="Cerrar vista previa"></button>
        <section class="attachment-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="attachment-preview-title">
            <header class="attachment-modal__header"><h2 id="attachment-preview-title"></h2><button class="attachment-modal__close" type="button" data-modal-close aria-label="Cerrar">×</button></header>
            <div class="attachment-modal__stage">
                <button class="attachment-modal__nav attachment-modal__nav--previous" type="button" data-gallery-previous aria-label="Imagen anterior" hidden>‹</button>
                <img class="attachment-modal__image" alt="" hidden>
                <iframe class="attachment-modal__frame" title="Vista previa del archivo" referrerpolicy="no-referrer" hidden></iframe>
                <video class="attachment-modal__video" controls playsinline hidden></video>
                <audio class="attachment-modal__audio" controls hidden></audio>
                <button class="attachment-modal__nav attachment-modal__nav--next" type="button" data-gallery-next aria-label="Imagen siguiente" hidden>›</button>
            </div>
            <footer class="attachment-modal__footer"><a class="button button-secondary" data-modal-download download>Descargar archivo</a></footer>
        </section>
    </div>
<?php endif; ?>
</body>
</html>
