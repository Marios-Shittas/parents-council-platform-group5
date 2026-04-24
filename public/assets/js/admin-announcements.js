function ensureNoticeElements() {
    if (document.getElementById('page-notice-overlay')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'page-notice-overlay';
    overlay.className = 'page-notice-overlay';
    overlay.innerHTML = '' +
        '<div class="page-notice-card" id="page-notice-card" role="dialog" aria-modal="true" aria-labelledby="page-notice-title">' +
            '<h3 class="page-notice-title" id="page-notice-title">Ειδοποίηση</h3>' +
            '<div class="page-notice-message" id="page-notice-message">—</div>' +
            '<div class="page-notice-actions"><button type="button" class="page-notice-btn" id="page-notice-close">Εντάξει</button></div>' +
        '</div>';

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            overlay.classList.remove('is-open');
            document.body.style.overflow = overlay.getAttribute('data-prev-overflow') || '';
        }
    });

    document.body.appendChild(overlay);

    const closeBtn = document.getElementById('page-notice-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            overlay.classList.remove('is-open');
            document.body.style.overflow = overlay.getAttribute('data-prev-overflow') || '';
        });
    }
}

function showNotice(message, options) {
    ensureNoticeElements();

    const overlay = document.getElementById('page-notice-overlay');
    const card = document.getElementById('page-notice-card');
    const title = document.getElementById('page-notice-title');
    const body = document.getElementById('page-notice-message');
    const opts = options || {};

    if (!overlay || !card || !title || !body) {
        console.error(message);
        return;
    }

    card.classList.remove('is-error', 'is-warning');
    if (opts.variant === 'error') card.classList.add('is-error');
    if (opts.variant === 'warning') card.classList.add('is-warning');

    title.textContent = opts.title || 'Ειδοποίηση';
    body.textContent = message || 'Συνέβη ένα απρόσμενο σφάλμα.';

    overlay.setAttribute('data-prev-overflow', document.body.style.overflow || '');
    document.body.style.overflow = 'hidden';
    overlay.classList.add('is-open');
}

function showConfirm(message, onConfirm, options) {
    const opts = options || {};
    const overlay = document.createElement('div');
    const previousOverflow = document.body.style.overflow || '';

    overlay.className = 'page-confirm-overlay is-open';

    overlay.innerHTML = '' +
        '<div class="page-confirm-card" role="dialog" aria-modal="true">' +
            '<h3 class="page-confirm-title">' + (opts.title || 'Επιβεβαίωση') + '</h3>' +
            '<div class="page-confirm-message">' + (message || 'Είστε σίγουροι;') + '</div>' +
            '<div class="page-confirm-actions">' +
                '<button type="button" data-action="cancel" class="page-confirm-btn page-confirm-btn--cancel">Όχι</button>' +
                '<button type="button" data-action="confirm" class="page-confirm-btn page-confirm-btn--confirm">Ναι</button>' +
            '</div>' +
        '</div>';

    function closeOverlay() {
        document.body.style.overflow = previousOverflow;
        overlay.remove();
    }

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeOverlay();
        }
    });

    const cancelBtn = overlay.querySelector('[data-action="cancel"]');
    const confirmBtn = overlay.querySelector('[data-action="confirm"]');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeOverlay);
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            closeOverlay();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    document.body.style.overflow = 'hidden';
    document.body.appendChild(overlay);
}

// Κάνει διαγραφή εικόνας με ξεχωριστό POST, χωρίς να χαλάει η φόρμα επεξεργασίας
function deleteAnnouncementImage(imageId, announcementId) {
    showConfirm('Διαγραφή εικόνας;', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_image';

        const imageIdInput = document.createElement('input');
        imageIdInput.type = 'hidden';
        imageIdInput.name = 'image_id';
        imageIdInput.value = String(imageId);

        const announcementIdInput = document.createElement('input');
        announcementIdInput.type = 'hidden';
        announcementIdInput.name = 'announcement_id';
        announcementIdInput.value = String(announcementId);

        form.appendChild(actionInput);
        form.appendChild(imageIdInput);
        form.appendChild(announcementIdInput);
        document.body.appendChild(form);
        form.submit();
    }, {
        title: 'Επιβεβαίωση διαγραφής'
    });
}

function deleteAnnouncementAttachment(attachmentId, announcementId) {
    showConfirm('Διαγραφή συνημμένου;', function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_attachment';

        const attachmentIdInput = document.createElement('input');
        attachmentIdInput.type = 'hidden';
        attachmentIdInput.name = 'attachment_id';
        attachmentIdInput.value = String(attachmentId);

        const announcementIdInput = document.createElement('input');
        announcementIdInput.type = 'hidden';
        announcementIdInput.name = 'announcement_id';
        announcementIdInput.value = String(announcementId);

        form.appendChild(actionInput);
        form.appendChild(attachmentIdInput);
        form.appendChild(announcementIdInput);
        document.body.appendChild(form);
        form.submit();
    }, {
        title: 'Επιβεβαίωση διαγραφής'
    });
}

function getAnnouncementImageLimit() {
    return Number.parseInt(document.body.getAttribute('data-announcement-image-limit') || '0', 10) || 0;
}

function getAnnouncementImageFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function truncatePreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

function validateAnnouncementImageFile(file) {
    const warnings = [];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024;
    const fileExt = (file.name.split('.').pop() || '').toLowerCase();

    if (!allowedExtensions.includes(fileExt)) {
        warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
    }

    if (file.size > maxFileSize) {
        warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
    }

    if (!String(file.type || '').startsWith('image/')) {
        warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
    }

    return warnings;
}

function syncAnnouncementImageInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

function renderAnnouncementImagePreview(preview, stagedFiles, onRemove) {
    if (!preview) {
        return;
    }

    preview.innerHTML = '';

    stagedFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'image-preview-item';

        const image = document.createElement('img');
        image.alt = file.name;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'delete-btn';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.setAttribute('aria-label', `Αφαίρεση ${file.name}`);
        deleteBtn.addEventListener('click', function () {
            onRemove(index);
        });

        const caption = document.createElement('div');
        caption.className = 'preview-file-caption';
        caption.textContent = truncatePreviewFileName(file.name, 18);

        const reader = new FileReader();
        reader.onload = function (event) {
            image.src = String(event.target && event.target.result ? event.target.result : '');
        };
        reader.readAsDataURL(file);

        item.appendChild(image);
        item.appendChild(deleteBtn);
        item.appendChild(caption);
        preview.appendChild(item);
    });
}

function setupAnnouncementImageInput(input, previewId) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const announcementImageLimit = getAnnouncementImageLimit();
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    function updateInputState() {
        if (existingCount + stagedFiles.length >= announcementImageLimit) {
            input.disabled = true;
        } else if (existingCount < announcementImageLimit) {
            input.disabled = false;
        }
    }

    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getAnnouncementImageFileKey(removedFile));
        syncAnnouncementImageInputFiles(input, stagedFiles);
        renderAnnouncementImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();
    }

    input.addEventListener('change', function () {
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];
        let reachedLimit = false;

        if (incomingFiles.length === 0) {
            return;
        }

        if (existingCount >= announcementImageLimit) {
            warnings.push(`Η ανακοίνωση έχει ήδη ${announcementImageLimit} εικόνες. Διαγράψτε πρώτα κάποια εικόνα για να προσθέσετε νέα.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getAnnouncementImageFileKey(file);
                const validationWarnings = validateAnnouncementImageFile(file);

                if (validationWarnings.length > 0) {
                    warnings.push(...validationWarnings);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Το αρχείο "${file.name}" έχει ήδη επιλεγεί.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= announcementImageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, announcementImageLimit - existingCount - stagedFiles.length);
                        warnings.push(`Μπορείτε να προσθέσετε μόνο ${remainingSlots} ακόμη εικόνα/ες σε αυτή την ανακοίνωση.`);
                        reachedLimit = true;
                    }
                    return;
                }

                stagedFiles.push(file);
                stagedKeys.add(fileKey);
            });
        }

        syncAnnouncementImageInputFiles(input, stagedFiles);
        renderAnnouncementImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();

        if (warnings.length > 0) {
            showNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: 'Έλεγχος αρχείων',
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

function getAnnouncementAttachmentFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

function validateAnnouncementAttachmentFile(file) {
    const warnings = [];
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const maxFileSize = 8 * 1024 * 1024;
    const fileExt = (file.name.split('.').pop() || '').toLowerCase();
    const fileType = String(file.type || '');

    if (!allowedExtensions.includes(fileExt)) {
        warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο PDF, JPG, JPEG, PNG.`);
    }

    if (file.size > maxFileSize) {
        warnings.push(`Το συνημμένο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 8MB.`);
    }

    if (fileType !== '' && fileType !== 'application/pdf' && !fileType.startsWith('image/')) {
        warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρο τύπο αρχείου.`);
    }

    return warnings;
}

function renderAnnouncementAttachmentPreview(preview, stagedFiles, onRemove) {
    if (!preview) {
        return;
    }

    preview.innerHTML = '';

    stagedFiles.forEach((file, index) => {
        const fileExt = (file.name.split('.').pop() || '').toLowerCase();
        const item = document.createElement('div');
        item.className = 'attachment-preview-item';

        const info = document.createElement('div');
        info.className = 'attachment-preview-info';

        const icon = document.createElement('i');
        icon.className = fileExt === 'pdf' ? 'fas fa-file-pdf' : 'fas fa-file-image';

        const text = document.createElement('span');
        text.className = 'attachment-preview-name';
        text.textContent = truncatePreviewFileName(file.name, 40);

        const size = document.createElement('span');
        size.className = 'attachment-preview-size';
        size.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'attachment-remove-btn';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.setAttribute('aria-label', `Αφαίρεση ${file.name}`);
        deleteBtn.addEventListener('click', function () {
            onRemove(index);
        });

        info.appendChild(icon);
        info.appendChild(text);
        info.appendChild(size);
        item.appendChild(info);
        item.appendChild(deleteBtn);
        preview.appendChild(item);
    });
}

function setupAnnouncementAttachmentInput(input, previewId) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const stagedFiles = [];
    const stagedKeys = new Set();

    function syncFiles() {
        if (typeof DataTransfer === 'undefined') {
            return;
        }

        const dataTransfer = new DataTransfer();
        stagedFiles.forEach((file) => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    }

    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getAnnouncementAttachmentFileKey(removedFile));
        syncFiles();
        renderAnnouncementAttachmentPreview(preview, stagedFiles, removeStagedFile);
    }

    input.addEventListener('change', function () {
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];

        if (incomingFiles.length === 0) {
            return;
        }

        incomingFiles.forEach((file) => {
            const fileKey = getAnnouncementAttachmentFileKey(file);
            const validationWarnings = validateAnnouncementAttachmentFile(file);

            if (validationWarnings.length > 0) {
                warnings.push(...validationWarnings);
                return;
            }

            if (stagedKeys.has(fileKey)) {
                warnings.push(`Το συνημμένο "${file.name}" έχει ήδη επιλεγεί.`);
                return;
            }

            stagedFiles.push(file);
            stagedKeys.add(fileKey);
        });

        syncFiles();
        renderAnnouncementAttachmentPreview(preview, stagedFiles, removeStagedFile);

        if (warnings.length > 0) {
            showNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: 'Έλεγχος συνημμένων',
                variant: 'warning'
            });
        }
    });
}

setupAnnouncementImageInput(document.getElementById('images'), 'imagePreview');
setupAnnouncementImageInput(document.getElementById('edit_images'), 'editPreview');
setupAnnouncementAttachmentInput(document.getElementById('attachments'), 'attachmentPreview');
setupAnnouncementAttachmentInput(document.getElementById('edit_attachments'), 'editAttachmentPreview');

document.querySelectorAll('form.js-confirm-submit').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = form.getAttribute('data-confirm-message') || 'Είστε σίγουροι;';
        const title = form.getAttribute('data-confirm-title') || 'Επιβεβαίωση';

        showConfirm(message, function () {
            form.submit();
        }, {
            title: title
        });
    });
});
