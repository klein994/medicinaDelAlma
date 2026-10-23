(function () {
    const modal = document.getElementById('attachment-preview-modal');

    document.addEventListener('change', (event) => {
        const input = event.target.closest('input[type="file"][multiple]');
        if (!input) return;
        const feedback = input.closest('label')?.querySelector('[data-upload-feedback]');
        if (!feedback) return;
        const files = Array.from(input.files || []);
        feedback.textContent = files.length
            ? `${files.length} archivo${files.length === 1 ? '' : 's'} seleccionado${files.length === 1 ? '' : 's'}: ${files.map((file) => file.name).join(', ')}`
            : '';
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
