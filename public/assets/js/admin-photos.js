document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('gallery-images');
    var preview = document.getElementById('parents-upload-preview');
    var status = document.getElementById('parents-upload-status');
    var dropzone = document.getElementById('parents-upload-dropzone');

    if (input && preview && status && dropzone) {
        // Leitourgia renderPreview: krataei tin antistoixi symperifora tou UI.
        function renderPreview(files) {
            preview.innerHTML = '';

            if (!files || files.length === 0) {
                status.textContent = 'Δεν έχουν επιλεγεί ακόμη αρχεία.';
                dropzone.classList.remove('is-active');
                return;
            }

            status.textContent = files.length + (files.length === 1 ? ' φωτογραφία έτοιμη για ανέβασμα.' : ' φωτογραφίες έτοιμες για ανέβασμα.');
            dropzone.classList.add('is-active');

            Array.prototype.forEach.call(files, function (file) {
                var item = document.createElement('div');
                item.className = 'parents-upload-preview__item';

                var thumb = document.createElement('img');
                thumb.className = 'parents-upload-preview__thumb';
                thumb.alt = file.name;
                thumb.src = URL.createObjectURL(file);
                thumb.onload = function () {
                    URL.revokeObjectURL(thumb.src);
                };

                var meta = document.createElement('div');
                meta.className = 'parents-upload-preview__meta';

                var name = document.createElement('strong');
                name.textContent = file.name;

                var size = document.createElement('span');
                size.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

                meta.appendChild(name);
                meta.appendChild(size);
                item.appendChild(thumb);
                item.appendChild(meta);
                preview.appendChild(item);
            });
        }

        input.addEventListener('change', function () {
            renderPreview(input.files);
        });
    }

    var galleryDeleteForms = document.querySelectorAll('.parents-gallery-delete-form');
    var galleryDeleteLabel = document.getElementById('deleteGalleryImageLabel');
    var galleryDeletePath = document.getElementById('deleteGalleryImagePath');
    var galleryDeleteThumb = document.getElementById('deleteGalleryImageThumb');
    var galleryDeleteSource = document.getElementById('deleteGalleryImageSource');
    var galleryDeleteConfirmButton = document.getElementById('confirmDeleteGalleryImageButton');
    var pendingGalleryDeleteForm = null;

    if (galleryDeleteForms.length > 0 && galleryDeleteLabel && galleryDeletePath && galleryDeleteThumb && galleryDeleteSource && galleryDeleteConfirmButton && window.jQuery) {
        Array.prototype.forEach.call(galleryDeleteForms, function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingGalleryDeleteForm = form;
                galleryDeleteLabel.textContent = form.getAttribute('data-image-label') || 'φωτογραφία';
                galleryDeletePath.textContent = form.getAttribute('data-image-path') || '';
                galleryDeleteThumb.src = form.getAttribute('data-image-thumb') || '';
                galleryDeleteThumb.alt = form.getAttribute('data-image-label') || 'φωτογραφία';
                galleryDeleteSource.textContent = form.getAttribute('data-image-source') || '';
                jQuery('#deleteGalleryImageConfirmModal').modal('show');
            });
        });

        galleryDeleteConfirmButton.addEventListener('click', function () {
            if (!pendingGalleryDeleteForm) {
                return;
            }

            var formToSubmit = pendingGalleryDeleteForm;
            pendingGalleryDeleteForm = null;
            jQuery('#deleteGalleryImageConfirmModal').modal('hide');
            formToSubmit.submit();
        });

        jQuery('#deleteGalleryImageConfirmModal').on('hidden.bs.modal', function () {
            pendingGalleryDeleteForm = null;
            galleryDeletePath.textContent = '';
            galleryDeleteThumb.src = '';
            galleryDeleteThumb.alt = '';
            galleryDeleteSource.textContent = '';
        });
    }
});
