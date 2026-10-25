(function () {
    const modal = document.getElementById('attachment-preview-modal');

    document.addEventListener('change', (event) => {
        const input = event.target.closest('input[type="file"]');
        if (!input) return;
        const feedback = input.closest('label')?.querySelector('[data-upload-feedback]');
        if (!feedback) return;
        const files = Array.from(input.files || []);
        feedback.textContent = files.length
            ? `${files.length} archivo${files.length === 1 ? '' : 's'} seleccionado${files.length === 1 ? '' : 's'}: ${files.map((file) => file.name).join(', ')}`
            : '';
    });

    const userSearch = document.querySelector('[data-user-search]');
    if (userSearch) {
        const rows = Array.from(document.querySelectorAll('[data-user-row]'));
        const count = document.querySelector('[data-user-results]');
        const empty = document.querySelector('[data-user-empty]');
        const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        userSearch.addEventListener('input', () => {
            const query = normalize(userSearch.value);
            let visible = 0;
            rows.forEach((row) => {
                const matches = normalize(row.dataset.search || '').includes(query);
                row.hidden = !matches;
                if (matches) visible += 1;
            });
            if (count) count.textContent = `${visible} de ${rows.length} usuarios`;
            if (empty) empty.hidden = visible > 0;
        });
    }

    document.querySelectorAll('[data-sortable-list]').forEach((list) => {
        let draggedItem = null;
        let originalOrder = '';
        const getOrder = () => Array.from(list.querySelectorAll('[data-sortable-item]'), (item) => item.dataset.id).join(',');

        const finishDrag = async () => {
            if (!draggedItem) return;
            draggedItem.classList.remove('is-dragging');
            draggedItem = null;
            if (originalOrder === getOrder()) return;

            const form = new FormData();
            form.set('_csrf', list.dataset.csrf || '');
            form.set('action', 'sort_home_items');
            form.set('category', list.dataset.category || '');
            form.set('item_ids', JSON.stringify(Array.from(list.querySelectorAll('[data-sortable-item]'), (item) => Number(item.dataset.id))));
            form.set('return_item_id', new URLSearchParams(window.location.search).get('edit_home_item') || '0');
            list.setAttribute('aria-busy', 'true');
            list.classList.add('is-saving-order');
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('No se pudo guardar el orden.');
                const result = await response.json();
                if (!result.success) throw new Error('No se pudo guardar el orden.');
                window.location.reload();
            } catch (error) {
                window.alert('No se pudo guardar el nuevo orden. Se volverá a cargar la lista.');
                window.location.reload();
            }
        };

        list.querySelectorAll('[data-drag-handle]').forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => {
                if (event.pointerType === 'mouse' && event.button !== 0) return;
                draggedItem = handle.closest('[data-sortable-item]');
                if (!draggedItem) return;
                originalOrder = getOrder();
                draggedItem.classList.add('is-dragging');
                handle.setPointerCapture(event.pointerId);
                event.preventDefault();
            });

            handle.addEventListener('pointermove', (event) => {
                if (!draggedItem) return;
                const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-sortable-item]');
                if (!target || target === draggedItem || target.parentElement !== list) return;
                const rect = target.getBoundingClientRect();
                list.insertBefore(draggedItem, event.clientY < rect.top + rect.height / 2 ? target : target.nextElementSibling);
            });

            handle.addEventListener('pointerup', finishDrag);
            handle.addEventListener('pointercancel', finishDrag);
        });
    });

    if (modal) {
        const title = modal.querySelector('#attachment-preview-title');
        const image = modal.querySelector('.attachment-modal__image');
        const frame = modal.querySelector('.attachment-modal__frame');
        const video = modal.querySelector('.attachment-modal__video');
        const audio = modal.querySelector('.attachment-modal__audio');
        const download = modal.querySelector('[data-modal-download]');
        const previous = modal.querySelector('[data-gallery-previous]');
        const next = modal.querySelector('[data-gallery-next]');
        let gallery = [];
        let galleryIndex = 0;
        let previousFocus = null;

        function showAttachment(item) {
            [image, frame, video, audio].forEach((element) => {
                element.hidden = true;
                if (element !== image) element.removeAttribute('src');
            });
            video.pause();
            audio.pause();
            title.textContent = item.dataset.previewTitle || 'Vista previa';
            download.href = item.dataset.downloadUrl || item.dataset.previewUrl || '#';
            download.hidden = !item.dataset.downloadUrl;
            previous.hidden = gallery.length < 2;
            next.hidden = gallery.length < 2;

            const kind = item.dataset.previewKind;
            const url = item.dataset.previewUrl;
            if (kind === 'image') {
                image.src = url;
                image.alt = item.dataset.previewTitle || '';
                image.hidden = false;
            } else if (kind === 'video') {
                video.src = url;
                video.hidden = false;
            } else if (kind === 'audio') {
                audio.src = url;
                audio.hidden = false;
            } else {
                frame.title = item.dataset.previewTitle || 'Vista previa del archivo';
                if (kind === 'html' || kind === 'text') {
                    frame.setAttribute('sandbox', 'allow-scripts allow-downloads allow-modals allow-forms allow-popups');
                } else {
                    frame.removeAttribute('sandbox');
                }
                frame.src = url;
                frame.hidden = false;
            }
        }

        function openAttachment(item) {
            previousFocus = item;
            gallery = [];
            galleryIndex = 0;
            if (item.dataset.galleryId) {
                gallery = Array.from(document.querySelectorAll('[data-gallery-id]'))
                    .filter((candidate) => candidate.dataset.galleryId === item.dataset.galleryId);
                galleryIndex = gallery.indexOf(item);
            }
            modal.hidden = false;
            document.body.classList.add('attachment-modal-open');
            showAttachment(item);
            modal.querySelector('.attachment-modal__close').focus();
        }

        function closeAttachment() {
            modal.hidden = true;
            document.body.classList.remove('attachment-modal-open');
            video.pause();
            audio.pause();
            video.removeAttribute('src');
            audio.removeAttribute('src');
            frame.src = 'about:blank';
            gallery = [];
            if (previousFocus) previousFocus.focus();
        }

        document.addEventListener('click', (event) => {
            const preview = event.target.closest('[data-preview-kind][data-preview-url]');
            if (preview) {
                event.preventDefault();
                openAttachment(preview);
                return;
            }
            if (event.target.closest('[data-modal-close]')) closeAttachment();
            if (event.target.closest('[data-gallery-previous]') && gallery.length > 1) {
                galleryIndex = (galleryIndex - 1 + gallery.length) % gallery.length;
                showAttachment(gallery[galleryIndex]);
            }
            if (event.target.closest('[data-gallery-next]') && gallery.length > 1) {
                galleryIndex = (galleryIndex + 1) % gallery.length;
                showAttachment(gallery[galleryIndex]);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (modal.hidden) return;
            if (event.key === 'Escape') closeAttachment();
            if (event.key === 'ArrowLeft' && gallery.length > 1) {
                galleryIndex = (galleryIndex - 1 + gallery.length) % gallery.length;
                showAttachment(gallery[galleryIndex]);
            }
            if (event.key === 'ArrowRight' && gallery.length > 1) {
                galleryIndex = (galleryIndex + 1) % gallery.length;
                showAttachment(gallery[galleryIndex]);
            }
        });
    }

})();
