<?php
declare(strict_types=1);

$csrf = campus_csrf_token();
$flash = $flash ?: null;
$attachmentsByPost = $attachmentsByPost ?? [];
$pageTitle = 'Campus · Medicina del Alma';
$roleLabels = ['admin' => 'Administración', 'facilitator' => 'Facilitación', 'member' => 'Participante'];
$statusLabels = ['submitted' => 'Recibida', 'in_review' => 'En revisión', 'needs_changes' => 'Requiere ajustes', 'approved' => 'Completada'];
$homeCategories = ['workshops' => 'Talleres y formaciones', 'therapies' => 'Terapias', 'objects' => 'Objetos para acompañar tu práctica'];
$newHomeCategory = (string)($_GET['new_category'] ?? $_GET['category'] ?? 'workshops');
if (!isset($homeCategories[$newHomeCategory])) $newHomeCategory = 'workshops';
$editingHomeItem = $editingHomeItem ?: ['id' => 0, 'category' => $newHomeCategory, 'title' => '', 'description' => '', 'image_url' => '', 'target_url' => '', 'link_label' => 'Saber más', 'sort_order' => 0, 'status' => 'published'];
$activeHomeCategory = (string)($_GET['category'] ?? $newHomeCategory);
if (!empty($editingHomeItem['id'])) $activeHomeCategory = (string)$editingHomeItem['category'];
if (!isset($homeCategories[$activeHomeCategory])) $activeHomeCategory = 'workshops';
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
    <link rel="stylesheet" href="assets/campus.css?v=20261025-3">
    <script src="assets/campus.js?v=20261025-3" defer></script>
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
            <nav class="section-nav" aria-label="Secciones del campus">
                <div class="section-nav__group">
                    <div class="sidebar-label">Tus espacios</div>
                    <?php foreach ($sections as $item): ?>
                        <a class="section-nav__item <?= $page === 'campus' && $section && (int)$section['id'] === (int)$item['id'] ? 'is-active' : '' ?>" href="?section=<?= (int)$item['id'] ?>" <?= $page === 'campus' && $section && (int)$section['id'] === (int)$item['id'] ? 'aria-current="page"' : '' ?>>
                            <span class="section-nav__dot"></span>
                            <span><?= campus_h($item['title']) ?></span>
                            <?php if ($item['status'] === 'archived'): ?><small>Archivada</small><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (campus_is_admin($user)): ?>
                    <div class="section-nav__admin" role="group" aria-label="Administrar el sitio">
                        <div class="sidebar-label">Administrar el sitio</div>
                        <?php foreach ($homeCategories as $categoryKey => $categoryTitle): ?>
                            <a class="section-nav__item section-nav__item--admin section-nav__item--<?= campus_h($categoryKey) ?> <?= $page === 'home_content' && $activeHomeCategory === $categoryKey ? 'is-active' : '' ?>" href="?page=home_content&amp;category=<?= campus_h($categoryKey) ?>" <?= $page === 'home_content' && $activeHomeCategory === $categoryKey ? 'aria-current="page"' : '' ?>><span class="section-nav__dot"></span><span><?= campus_h($categoryTitle) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-nav__admin section-nav__settings" role="group" aria-label="Configuración del campus">
                        <div class="sidebar-label">Configuración</div>
                        <a class="section-nav__item section-nav__item--admin <?= $page === 'groups' ? 'is-active' : '' ?>" href="?page=groups" <?= $page === 'groups' ? 'aria-current="page"' : '' ?>><span class="section-nav__dot"></span><span>Grupos</span></a>
                        <a class="section-nav__item section-nav__item--admin <?= $page === 'users' ? 'is-active' : '' ?>" href="?page=users" <?= $page === 'users' ? 'aria-current="page"' : '' ?>><span class="section-nav__dot"></span><span>Usuarios</span></a>
                    </div>
                <?php endif; ?>
            </nav>
            <?php if (!$sections): ?><p class="sidebar-empty">Aún no tienes grupos asignados.</p><?php endif; ?>
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
                    <section class="section-hero home-admin-hero"><div><span class="eyebrow">Web pública</span><h1><?= campus_h($homeCategories[$activeHomeCategory]) ?></h1><p><?= $activeHomeCategory === 'workshops' ? 'Administra las formaciones y edita su ficha completa desde el mismo lugar.' : ($activeHomeCategory === 'therapies' ? 'Organiza las terapias que acompañan los procesos de tu comunidad.' : 'Gestiona los objetos y recursos disponibles para acompañar la práctica.') ?></p></div><a class="button button-primary" href="?page=home_content&amp;new_category=<?= campus_h($activeHomeCategory) ?>">+ <?= $activeHomeCategory === 'workshops' ? 'Crear taller' : ($activeHomeCategory === 'therapies' ? 'Agregar terapia' : 'Agregar objeto') ?></a></section>
                    <?php if ($workshopPageSyncError): ?><div class="alert alert-error" role="alert"><?= campus_h($workshopPageSyncError) ?></div><?php endif; ?>
                    <div class="home-admin-layout">
                        <section class="panel home-editor home-editor--home-content">
                            <div class="panel-heading"><div><span class="eyebrow"><?= !empty($editingHomeItem['id']) ? 'Editar propuesta' : 'Agregar propuesta' ?></span><h2><?= !empty($editingHomeItem['id']) ? campus_h($editingHomeItem['title']) : 'Nueva propuesta' ?></h2></div><span class="panel-icon">✦</span></div>
                            <?php if (!empty($editingHomeItem['id'])): ?><a class="button button-secondary home-new-link" href="?page=home_content&amp;new_category=<?= campus_h($editingHomeItem['category']) ?>">+ <?= $editingHomeItem['category'] === 'workshops' ? 'Crear otro taller' : ($editingHomeItem['category'] === 'therapies' ? 'Agregar otra terapia' : 'Agregar otro objeto') ?></a><?php endif; ?>
                            <form method="post" enctype="multipart/form-data" class="form-stack">
                                <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="save_home_item"><input type="hidden" name="item_id" value="<?= (int)$editingHomeItem['id'] ?>"><input type="hidden" name="category" value="<?= campus_h($editingHomeItem['category']) ?>">
                                <label>Nombre<input name="title" value="<?= campus_h($editingHomeItem['title']) ?>" maxlength="200" required></label>
                                <label>Descripción<textarea name="description" rows="4"><?= campus_h($editingHomeItem['description'] ?? '') ?></textarea></label>
                                <label>Subir imagen (opcional)<input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" data-upload-feedback-source><small>JPG, PNG, WebP o GIF · hasta 12 MB.</small><small class="upload-feedback" data-upload-feedback aria-live="polite"></small></label>
                                <label>O pegar URL de imagen<input type="text" name="image_url" value="<?= campus_h($editingHomeItem['image_url'] ?? '') ?>" placeholder="https://..."><small>Si subes un archivo, ese reemplaza esta URL.</small></label>
                                <?php if (!empty($editingHomeItem['image_url'])): ?><img class="home-image-preview" src="<?= campus_h($editingHomeItem['image_url']) ?>" alt="Imagen actual de <?= campus_h($editingHomeItem['title'] ?: 'la propuesta') ?>" loading="lazy"><?php endif; ?>
                                <div data-workshop-fields <?= $editingHomeItem['category'] !== 'workshops' ? 'hidden' : '' ?>>
                                    <input type="hidden" name="target_url" value="<?= campus_h($editingHomeItem['target_url'] ?? '') ?>" <?= $editingHomeItem['category'] !== 'workshops' ? 'disabled' : '' ?>><input type="hidden" name="link_label" value="Saber más" <?= $editingHomeItem['category'] !== 'workshops' ? 'disabled' : '' ?>><p class="muted home-auto-link-note">El enlace y el texto «Saber más» se generan automáticamente al crear el taller.</p>
                                </div>
                                <div data-other-category-fields <?= $editingHomeItem['category'] === 'workshops' ? 'hidden' : '' ?>>
                                    <label>Enlace de destino<input name="target_url" value="<?= campus_h($editingHomeItem['target_url'] ?? '') ?>" placeholder="https://... o #contact" <?= $editingHomeItem['category'] === 'workshops' ? 'disabled' : '' ?>><small>En terapias u objetos puedes usar una URL o un ancla como <code>#contact</code>.</small></label>
                                    <label>Texto del enlace<input name="link_label" value="<?= campus_h($editingHomeItem['link_label']) ?>" maxlength="80" <?= $editingHomeItem['category'] === 'workshops' ? 'disabled' : '' ?>></label>
                                </div>
                                <?php if ($editingHomeItem['category'] === 'workshops'): ?>
                                    <div class="workshop-detail-callout">
                                        <strong>Ficha completa del taller</strong>
                                        <?php if ($homeItemPublicPage): ?>
                                            <p>La ficha completa está vinculada a esta tarjeta. Puedes editar presentación, contenidos, fechas e inversión aquí mismo.</p>
                                            <a class="text-link" href="#workshop-detail-editor">Ir a la ficha completa ↓</a>
                                        <?php else: ?>
                                            <p>Al guardar esta tarjeta se creará y asociará automáticamente su ficha. Después podrás completar el detalle aquí mismo.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <label class="checkbox-label"><input type="checkbox" name="is_visible" value="1" <?= $editingHomeItem['status'] === 'published' ? 'checked' : '' ?>> Mostrar en la home</label>
                                <button class="button button-primary" type="submit"><?= $editingHomeItem['category'] === 'workshops' ? (empty($editingHomeItem['id']) ? 'Guardar tarjeta y crear ficha' : 'Guardar tarjeta del taller') : 'Guardar contenido' ?></button>
                            </form>
                            <?php if (!empty($editingHomeItem['id']) && $editingHomeItem['category'] === 'workshops'): ?>
                                <?php if ($editingPublicPage): ?>
                                    <section class="workshop-detail-editor" id="workshop-detail-editor">
                                        <div class="panel-heading"><div><span class="eyebrow">Mismo taller · /talleres/<?= campus_h(campus_slugify((string)$editingPublicPage['slug'])) ?>/</span><h2>Ficha completa</h2></div><span class="panel-icon">✦</span></div>
                                        <form method="post" class="form-stack">
                                            <input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="save_public_page"><input type="hidden" name="page_id" value="<?= (int)$editingPublicPage['id'] ?>">
                                            <fieldset class="workshop-form-group"><legend>Presentación</legend>
                                                <label>Título de la página<input name="title" value="<?= campus_h($editingPublicPage['title']) ?>" maxlength="200" required></label>
                                                <label>Frase pequeña del encabezado<input name="eyebrow" value="<?= campus_h($editingPublicPage['eyebrow'] ?? '') ?>" maxlength="160"></label>
                                                <label>Titular principal<input name="headline" value="<?= campus_h($editingPublicPage['headline']) ?>" maxlength="240" required></label>
                                                <label>Texto de presentación<textarea name="lead" rows="3"><?= campus_h($editingPublicPage['lead'] ?? '') ?></textarea></label>
                                                <label>Imagen principal<input name="image_url" value="<?= campus_h($editingPublicPage['image_url'] ?? '') ?>" placeholder="images/features/imagen.jpg o https://..."><small>Usa una ruta desde la raíz del sitio o una URL HTTP/HTTPS.</small></label>
                                            </fieldset>
                                            <fieldset class="workshop-form-group"><legend>Descripción y contenidos</legend>
                                                <label>Título de la descripción<input name="intro_title" value="<?= campus_h($editingPublicPage['intro_title'] ?? '') ?>" maxlength="200"></label>
                                                <label>Descripción<textarea name="intro_body" rows="6"><?= campus_h($editingPublicPage['intro_body'] ?? '') ?></textarea></label>
                                                <label>Título de temas o aprendizajes<input name="topics_title" value="<?= campus_h($editingPublicPage['topics_title'] ?? '') ?>" maxlength="200"></label>
                                                <label>Temas o aprendizajes<small>Escribe un elemento por línea; cada línea aparece como una tarjeta.</small><textarea name="topics" rows="5"><?= campus_h($editingPublicPage['topics'] ?? '') ?></textarea></label>
                                                <label>Título de lo que incluye<input name="program_title" value="<?= campus_h($editingPublicPage['program_title'] ?? '') ?>" maxlength="200"></label>
                                                <label>Contenido del programa<small>Escribe un elemento por línea.</small><textarea name="program_details" rows="7"><?= campus_h($editingPublicPage['program_details'] ?? '') ?></textarea></label>
                                                <label>Nota aclaratoria (opcional)<textarea name="disclaimer" rows="3"><?= campus_h($editingPublicPage['disclaimer'] ?? '') ?></textarea></label>
                                            </fieldset>
                                            <fieldset class="workshop-form-group"><legend>Fechas e inscripción</legend>
                                                <div class="form-row"><label>Modalidad<input name="modality" value="<?= campus_h($editingPublicPage['modality'] ?? '') ?>" maxlength="160"></label><label>Horario<input name="schedule" value="<?= campus_h($editingPublicPage['schedule'] ?? '') ?>" maxlength="200"></label></div>
                                                <div class="form-row"><label>Duración<input name="duration" value="<?= campus_h($editingPublicPage['duration'] ?? '') ?>" maxlength="120"></label><label>Próxima fecha<input name="next_date" value="<?= campus_h($editingPublicPage['next_date'] ?? '') ?>" maxlength="160"></label></div>
                                                <div class="form-row"><label>Inversión<input name="price" value="<?= campus_h($editingPublicPage['price'] ?? '') ?>" maxlength="120"></label><label>Texto del botón<input name="cta_label" value="<?= campus_h($editingPublicPage['cta_label']) ?>" maxlength="100"></label></div>
                                                <label class="checkbox-label"><input type="checkbox" name="is_visible" value="1" <?= $editingPublicPage['status'] === 'published' ? 'checked' : '' ?>> Publicar esta página</label>
                                            </fieldset>
                                            <button class="button button-primary" type="submit">Guardar ficha del taller</button>
                                        </form>
                                    </section>
                                <?php elseif (!$publicPagesTableReady): ?>
                                    <div class="alert alert-error" role="alert">No se pudo cargar la ficha del taller. Comprueba la tabla campus_public_pages.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </section>
                        <div class="home-items-column home-items-column--home-content">
                                <?php $categoryKey = $activeHomeCategory; $categoryTitle = $homeCategories[$categoryKey]; $categoryItems = array_values(array_filter($homeItems, static fn(array $item): bool => $item['category'] === $categoryKey)); ?>
                                <section class="panel home-items-panel"><div class="home-items-heading"><div><span class="eyebrow">Home pública</span><h2><?= campus_h($categoryTitle) ?></h2></div><div class="home-items-heading__actions"><a class="button button-small" href="?page=home_content&amp;new_category=<?= campus_h($categoryKey) ?>">+ <?= $categoryKey === 'workshops' ? 'Nuevo taller' : ($categoryKey === 'therapies' ? 'Nueva terapia' : 'Nuevo objeto') ?></a><span class="item-count"><?= count($categoryItems) ?></span></div></div>
                                    <?php if (!$categoryItems): ?><p class="muted">Todavía no hay elementos en esta área.</p><?php else: ?><p class="muted home-sort-help">Arrastra el control <span aria-hidden="true">⠿</span> para cambiar el orden.</p><?php endif; ?>
                                    <div class="home-sort-list" data-sortable-list data-category="<?= campus_h($categoryKey) ?>" data-csrf="<?= campus_h($csrf) ?>">
                                        <?php foreach ($categoryItems as $homeItem): ?>
                                            <article class="home-item-row <?= $homeItem['status'] === 'hidden' ? 'is-hidden' : '' ?>" data-sortable-item data-id="<?= (int)$homeItem['id'] ?>">
                                                <button class="drag-handle" type="button" data-drag-handle aria-label="Arrastrar <?= campus_h($homeItem['title']) ?> para cambiar el orden" title="Arrastra para reordenar">⠿</button>
                                                <div class="home-item-row__copy"><strong><?= campus_h($homeItem['title']) ?></strong><small><?= $homeItem['status'] === 'published' ? 'Visible' : 'Oculto' ?><?php if ($homeItem['category'] === 'workshops' && isset($workshopPagesByHomeItem[(int)$homeItem['id']])): ?> · ficha <?= $workshopPagesByHomeItem[(int)$homeItem['id']]['status'] === 'published' ? 'publicada' : 'oculta' ?> · /talleres/<?= campus_h(campus_slugify((string)$workshopPagesByHomeItem[(int)$homeItem['id']]['slug'])) ?>/<?php endif; ?></small></div>
                                                <div class="home-item-row__actions"><a class="text-link" href="?page=home_content&amp;edit_home_item=<?= (int)$homeItem['id'] ?>"><?= $homeItem['category'] === 'workshops' ? 'Editar taller y ficha' : 'Editar contenido' ?></a><form method="post" onsubmit="return confirm('¿Quitar este elemento de la home?');"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="delete_home_item"><input type="hidden" name="item_id" value="<?= (int)$homeItem['id'] ?>"><input type="hidden" name="category" value="<?= campus_h($homeItem['category']) ?>"><button class="remove-member" type="submit">Quitar</button></form></div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                        </div>
                    </div>
                <?php elseif ($page === 'groups' && campus_is_admin($user)): ?>
                    <section class="section-hero home-admin-hero"><div><span class="eyebrow">Configuración</span><h1>Grupos</h1><p>Crea y organiza los espacios del campus. Para publicar y compartir actividades, entra al grupo desde «Tus espacios».</p></div></section>
                    <div class="settings-layout">
                        <section class="panel settings-create-panel"><div class="panel-heading"><div><span class="eyebrow">Nuevo espacio</span><h2>Crear grupo</h2></div><span class="panel-icon">✦</span></div>
                            <form method="post" class="form-stack"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="create_section"><label>Nombre del grupo<input name="title" required maxlength="160" placeholder="Ej. Reiki · Grupo de práctica"></label><label>Descripción<textarea name="description" rows="3" placeholder="Cuenta brevemente qué encontrarán aquí"></textarea></label><button class="button button-primary" type="submit">Crear grupo</button></form>
                        </section>
                        <section class="panel settings-list-panel"><div class="home-items-heading"><div><span class="eyebrow">Espacios del campus</span><h2>Grupos creados</h2></div><span class="item-count"><?= count($sections) ?></span></div>
                            <?php if (!$sections): ?><p class="muted">Todavía no hay grupos. Crea el primero desde este formulario.</p><?php else: ?><div class="group-settings-list">
                                <?php foreach ($sections as $group): ?><article class="group-settings-row <?= $group['status'] === 'archived' ? 'is-archived' : '' ?>"><div class="group-settings-row__copy"><strong><?= campus_h($group['title']) ?></strong><small><?= campus_h($group['description'] ?: 'Sin descripción') ?></small><span><?= $group['status'] === 'published' ? 'Activo' : 'Archivado' ?> · <?= (int)$group['member_count'] ?> participantes</span></div><div class="group-settings-row__actions"><?php if ($group['status'] === 'published'): ?><a class="text-link" href="?section=<?= (int)$group['id'] ?>">Abrir grupo</a><form method="post" onsubmit="return confirm('¿Archivar este grupo? Su contenido quedará guardado.');"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="archive_section"><input type="hidden" name="section_id" value="<?= (int)$group['id'] ?>"><button class="remove-member" type="submit">Archivar</button></form><?php else: ?><span class="muted">Contenido conservado</span><?php endif; ?></div></article><?php endforeach; ?>
                            </div><?php endif; ?>
                        </section>
                    </div>
                <?php elseif ($page === 'users' && campus_is_admin($user)): ?>
                    <section class="section-hero home-admin-hero"><div><span class="eyebrow">Configuración</span><h1>Usuarios</h1><p>Crea accesos y administra quién participa en cada grupo. El contenido y las publicaciones se gestionan dentro de cada espacio.</p></div></section>
                    <div class="settings-users-layout">
                        <section class="panel settings-create-panel"><div class="panel-heading"><div><span class="eyebrow">Nuevo acceso</span><h2>Crear usuario</h2></div><span class="panel-icon">✦</span></div>
                            <form method="post" class="form-stack"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="create_account"><label>Nombre<input name="name" required maxlength="160" autocomplete="name"></label><label>Correo<input type="email" name="email" required maxlength="190" autocomplete="email"></label><label>Rol<select name="role"><option value="member">Participante</option><option value="facilitator">Facilitación</option></select></label><label>Contraseña temporal<input type="password" name="temporary_password" required minlength="12" autocomplete="new-password"><small>Mínimo 12 caracteres. Deberá cambiarla al ingresar.</small></label>
                                <fieldset class="user-group-options"><legend>Grupos de acceso</legend><?php if ($publishedSections): ?><?php foreach ($publishedSections as $group): ?><label class="checkbox-label"><input type="checkbox" name="section_ids[]" value="<?= (int)$group['id'] ?>"><span><?= campus_h($group['title']) ?></span></label><?php endforeach; ?><?php else: ?><p class="muted">No hay grupos activos. Puedes crear el usuario y asignarlo más tarde.</p><?php endif; ?></fieldset>
                                <button class="button button-primary" type="submit">Crear usuario</button></form>
                        </section>
                        <section class="panel settings-list-panel users-list-panel"><div class="home-items-heading"><div><span class="eyebrow">Cuentas del campus</span><h2>Usuarios y grupos</h2></div><span class="item-count"><?= count($accounts) ?></span></div>
                            <?php if (!$accounts): ?><p class="muted">Todavía no hay usuarios creados. Usa el formulario para agregar el primero.</p><?php else: ?><label class="user-search">Buscar por nombre, correo o grupo<input type="search" data-user-search placeholder="Ej. Ana, correo o Reiki" autocomplete="off"></label><p class="user-search-count" data-user-results aria-live="polite"><?= count($accounts) ?> usuarios</p><p class="muted user-search-empty" data-user-empty hidden>No hay usuarios que coincidan con la búsqueda.</p><div class="user-admin-list">
                                <?php foreach ($accounts as $account): ?>
                                    <?php $accountGroups = $membershipsByUser[(int)$account['id']] ?? []; $accountGroupIds = array_map(static fn(array $membership): int => (int)$membership['section_id'], $accountGroups); $searchableUser = mb_strtolower(implode(' ', array_merge([(string)$account['name'], (string)$account['email']], array_column($accountGroups, 'title')))); ?>
                                    <article class="user-admin-row" id="user-<?= (int)$account['id'] ?>" data-user-row data-search="<?= campus_h($searchableUser) ?>">
                                        <div class="user-admin-row__identity"><span class="avatar"><?= campus_h(mb_strtoupper(mb_substr((string)$account['name'], 0, 1))) ?></span><div><strong><?= campus_h($account['name']) ?></strong><small><?= campus_h($account['email']) ?> · <?= campus_h($roleLabels[$account['role']] ?? '') ?></small></div></div>
                                        <div class="user-admin-row__groups"><span class="user-admin-row__label">Grupos</span><?php if ($accountGroups): ?><div class="group-badges"><?php foreach ($accountGroups as $membership): ?><span class="group-badge <?= $membership['status'] === 'archived' ? 'is-archived' : '' ?>"><?= campus_h($membership['title']) ?><?= $membership['status'] === 'archived' ? ' · Archivado' : '' ?></span><?php endforeach; ?></div><?php else: ?><span class="muted">Sin grupos asignados</span><?php endif; ?></div>
                                        <details class="user-groups-editor" <?= (int)($_GET['edit_user'] ?? 0) === (int)$account['id'] ? 'open' : '' ?>><summary>Gestionar grupos</summary><form method="post" class="user-groups-form"><input type="hidden" name="_csrf" value="<?= campus_h($csrf) ?>"><input type="hidden" name="action" value="save_user_groups"><input type="hidden" name="member_id" value="<?= (int)$account['id'] ?>"><div class="user-group-options user-group-options--inline"><?php foreach ($publishedSections as $group): ?><label class="checkbox-label"><input type="checkbox" name="section_ids[]" value="<?= (int)$group['id'] ?>" <?= in_array((int)$group['id'], $accountGroupIds, true) ? 'checked' : '' ?>><span><?= campus_h($group['title']) ?></span></label><?php endforeach; ?><?php foreach ($accountGroups as $membership): if ($membership['status'] !== 'archived') continue; ?><label class="checkbox-label archived-group-option"><input type="checkbox" name="section_ids[]" value="<?= (int)$membership['section_id'] ?>" checked><span><?= campus_h($membership['title']) ?> · Archivado (puedes quitarlo)</span></label><?php endforeach; ?><?php if (!$publishedSections && !$accountGroups): ?><p class="muted">No hay grupos disponibles.</p><?php endif; ?></div><small class="muted">Selecciona uno o varios grupos activos. Puedes quitar las asignaciones históricas a grupos archivados.</small><button class="button button-small" type="submit">Guardar grupos</button></form></details>
                                    </article>
                                <?php endforeach; ?>
                            </div><?php endif; ?>
                        </section>
                    </div>
                <?php elseif (!$section): ?>
                    <section class="welcome-panel">
                        <span class="eyebrow">Bienvenida, <?= campus_h(explode(' ', (string)$user['name'])[0]) ?></span>
                        <h1>Un camino se recorre mejor en compañía.</h1>
                        <p>Elige un grupo para ver sus publicaciones y actividades.</p>
                        <?php if (!$sections && campus_is_admin($user)): ?>
                            <div class="empty-state"><span class="empty-state__icon">✳</span><h2>Crea tu primer grupo</h2><p>Organiza el campus por taller, comunidad o recorrido.</p><a class="button button-primary" href="?page=groups">Configurar grupos</a></div>
                        <?php elseif (!$sections): ?>
                            <div class="empty-state"><span class="empty-state__icon">✳</span><h2>Pronto habrá novedades</h2><p>Cuando te asignen a un grupo, lo verás aquí.</p></div>
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
                        <div class="agenda-heading"><div><span class="eyebrow">Próximos encuentros</span><h2>Agenda del grupo</h2></div><span class="agenda-heading__icon" aria-hidden="true">◷</span></div>
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
                            <p class="agenda-empty">Todavía no hay encuentros programados para este grupo.</p>
                        <?php endif; ?>
                    </section>

                    <div class="content-grid">
                        <section class="feed-column" id="post-feed" aria-label="Muro del grupo">
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

                        </aside>
                    </div>
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
