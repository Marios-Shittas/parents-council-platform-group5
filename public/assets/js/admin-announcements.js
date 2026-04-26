// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function ensureNoticeElements() {
    if (document.getElementById('page-notice-overlay')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'page-notice-overlay';
    overlay.className = 'page-notice-overlay';
    overlay.innerHTML = '' +
        '<div class="page-notice-card" id="page-notice-card" role="dialog" aria-modal="true" aria-labelledby="page-notice-title">' +
            '<h3 class="page-notice-title" id="page-notice-title">Î•Î¹Î´Î¿Ï€Î¿Î¯Î·ÏƒÎ·</h3>' +
            '<div class="page-notice-message" id="page-notice-message">â€”</div>' +
            '<div class="page-notice-actions"><button type="button" class="page-notice-btn" id="page-notice-close">Î•Î½Ï„Î¬Î¾ÎµÎ¹</button></div>' +
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

    title.textContent = opts.title || 'Î•Î¹Î´Î¿Ï€Î¿Î¯Î·ÏƒÎ·';
    body.textContent = message || 'Î£Ï…Î½Î­Î²Î· Î­Î½Î± Î±Ï€ÏÏŒÏƒÎ¼ÎµÎ½Î¿ ÏƒÏ†Î¬Î»Î¼Î±.';

    overlay.setAttribute('data-prev-overflow', document.body.style.overflow || '');
    document.body.style.overflow = 'hidden';
    overlay.classList.add('is-open');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function showConfirm(message, onConfirm, options) {
    const opts = options || {};
    const overlay = document.createElement('div');
    const previousOverflow = document.body.style.overflow || '';

    overlay.className = 'page-confirm-overlay is-open';

    overlay.innerHTML = '' +
        '<div class="page-confirm-card" role="dialog" aria-modal="true">' +
            '<h3 class="page-confirm-title">' + (opts.title || 'Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ·') + '</h3>' +
            '<div class="page-confirm-message">' + (message || 'Î•Î¯ÏƒÏ„Îµ ÏƒÎ¯Î³Î¿Ï…ÏÎ¿Î¹;') + '</div>' +
            '<div class="page-confirm-actions">' +
                '<button type="button" data-action="cancel" class="page-confirm-btn page-confirm-btn--cancel">ÎŒÏ‡Î¹</button>' +
                '<button type="button" data-action="confirm" class="page-confirm-btn page-confirm-btn--confirm">ÎÎ±Î¹</button>' +
            '</div>' +
        '</div>';

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function deleteAnnouncementImage(imageId, announcementId) {
    showConfirm('Î”Î¹Î±Î³ÏÎ±Ï†Î® ÎµÎ¹ÎºÏŒÎ½Î±Ï‚;', function () {
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
        title: 'Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ· Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚'
    });
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function deleteAnnouncementAttachment(attachmentId, announcementId) {
    showConfirm('Î”Î¹Î±Î³ÏÎ±Ï†Î® ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿Ï…;', function () {
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
        title: 'Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ· Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚'
    });
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAnnouncementImageLimit() {
    return Number(window.ADMIN_ANNOUNCEMENT_IMAGE_LIMIT || 0);
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAnnouncementImageFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function truncatePreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function validateAnnouncementImageFile(file) {
    const warnings = [];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const maxFileSize = 5 * 1024 * 1024;
    const fileExt = (file.name.split('.').pop() || '').toLowerCase();

    if (!allowedExtensions.includes(fileExt)) {
        warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ· ÎµÏ€Î­ÎºÏ„Î±ÏƒÎ·. Î•Ï€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ Î¼ÏŒÎ½Î¿ JPG, JPEG, PNG, GIF.`);
    }

    if (file.size > maxFileSize) {
        warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" ÎµÎ¯Î½Î±Î¹ Ï€Î¿Î»Ï Î¼ÎµÎ³Î¬Î»Î¿ (${(file.size / 1024 / 1024).toFixed(2)}MB). ÎœÎ­Î³Î¹ÏƒÏ„Î¿ Î¼Î­Î³ÎµÎ¸Î¿Ï‚: 5MB.`);
    }

    if (!String(file.type || '').startsWith('image/')) {
        warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î´ÎµÎ½ Ï†Î±Î¯Î½ÎµÏ„Î±Î¹ Î½Î± ÎµÎ¯Î½Î±Î¹ ÎµÎ¹ÎºÏŒÎ½Î±.`);
    }

    return warnings;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function syncAnnouncementImageInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
        deleteBtn.setAttribute('aria-label', `Î‘Ï†Î±Î¯ÏÎµÏƒÎ· ${file.name}`);
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function setupAnnouncementImageInput(input, previewId) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const announcementImageLimit = getAnnouncementImageLimit();
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function updateInputState() {
        if (existingCount + stagedFiles.length >= announcementImageLimit) {
            input.disabled = true;
        } else if (existingCount < announcementImageLimit) {
            input.disabled = false;
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
            warnings.push(`Î— Î±Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ· Î­Ï‡ÎµÎ¹ Î®Î´Î· ${announcementImageLimit} ÎµÎ¹ÎºÏŒÎ½ÎµÏ‚. Î”Î¹Î±Î³ÏÎ¬ÏˆÏ„Îµ Ï€ÏÏŽÏ„Î± ÎºÎ¬Ï€Î¿Î¹Î± ÎµÎ¹ÎºÏŒÎ½Î± Î³Î¹Î± Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î½Î­Î±.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getAnnouncementImageFileKey(file);
                const validationWarnings = validateAnnouncementImageFile(file);

                if (validationWarnings.length > 0) {
                    warnings.push(...validationWarnings);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î­Ï‡ÎµÎ¹ Î®Î´Î· ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= announcementImageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, announcementImageLimit - existingCount - stagedFiles.length);
                        warnings.push(`ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î¼ÏŒÎ½Î¿ ${remainingSlots} Î±ÎºÏŒÎ¼Î· ÎµÎ¹ÎºÏŒÎ½Î±/ÎµÏ‚ ÏƒÎµ Î±Ï…Ï„Î® Ï„Î·Î½ Î±Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ·.`);
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
            showNotice('Î ÏÎ¿ÎµÎ¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚:\n\n' + warnings.join('\n\n'), {
                title: 'ÎˆÎ»ÎµÎ³Ï‡Î¿Ï‚ Î±ÏÏ‡ÎµÎ¯Ï‰Î½',
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getAnnouncementAttachmentFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function validateAnnouncementAttachmentFile(file) {
    const warnings = [];
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const maxFileSize = 8 * 1024 * 1024;
    const fileExt = (file.name.split('.').pop() || '').toLowerCase();
    const fileType = String(file.type || '');

    if (!allowedExtensions.includes(fileExt)) {
        warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ· ÎµÏ€Î­ÎºÏ„Î±ÏƒÎ·. Î•Ï€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ Î¼ÏŒÎ½Î¿ PDF, JPG, JPEG, PNG.`);
    }

    if (file.size > maxFileSize) {
        warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" ÎµÎ¯Î½Î±Î¹ Ï€Î¿Î»Ï Î¼ÎµÎ³Î¬Î»Î¿ (${(file.size / 1024 / 1024).toFixed(2)}MB). ÎœÎ­Î³Î¹ÏƒÏ„Î¿ Î¼Î­Î³ÎµÎ¸Î¿Ï‚: 8MB.`);
    }

    if (fileType !== '' && fileType !== 'application/pdf' && !fileType.startsWith('image/')) {
        warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ¿ Ï„ÏÏ€Î¿ Î±ÏÏ‡ÎµÎ¯Î¿Ï….`);
    }

    return warnings;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
        deleteBtn.setAttribute('aria-label', `Î‘Ï†Î±Î¯ÏÎµÏƒÎ· ${file.name}`);
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

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function setupAnnouncementAttachmentInput(input, previewId) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncFiles() {
        if (typeof DataTransfer === 'undefined') {
            return;
        }

        const dataTransfer = new DataTransfer();
        stagedFiles.forEach((file) => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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
                warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î­Ï‡ÎµÎ¹ Î®Î´Î· ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯.`);
                return;
            }

            stagedFiles.push(file);
            stagedKeys.add(fileKey);
        });

        syncFiles();
        renderAnnouncementAttachmentPreview(preview, stagedFiles, removeStagedFile);

        if (warnings.length > 0) {
            showNotice('Î ÏÎ¿ÎµÎ¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚:\n\n' + warnings.join('\n\n'), {
                title: 'ÎˆÎ»ÎµÎ³Ï‡Î¿Ï‚ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Ï‰Î½',
                variant: 'warning'
            });
        }
    });
}

setupAnnouncementImageInput(document.getElementById('images'), 'imagePreview');
setupAnnouncementImageInput(document.getElementById('edit_images'), 'editPreview');
setupAnnouncementAttachmentInput(document.getElementById('attachments'), 'attachmentPreview');
setupAnnouncementAttachmentInput(document.getElementById('edit_attachments'), 'editAttachmentPreview');

document.querySelectorAll('[data-delete-announcement-image]').forEach(function (button) {
    // Syndeei ta diagrafi image buttons xoris inline onclick sto PHP protypo.
    button.addEventListener('click', function () {
        deleteAnnouncementImage(Number(button.getAttribute('data-image-id') || 0), Number(button.getAttribute('data-announcement-id') || 0));
    });
});

document.querySelectorAll('[data-delete-announcement-attachment]').forEach(function (button) {
    // Syndeei ta diagrafi attachment buttons xoris inline onclick sto PHP protypo.
    button.addEventListener('click', function () {
        deleteAnnouncementAttachment(Number(button.getAttribute('data-attachment-id') || 0), Number(button.getAttribute('data-announcement-id') || 0));
    });
});

document.querySelectorAll('form.js-confirm-submit').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = form.getAttribute('data-confirm-message') || 'Î•Î¯ÏƒÏ„Îµ ÏƒÎ¯Î³Î¿Ï…ÏÎ¿Î¹;';
        const title = form.getAttribute('data-confirm-title') || 'Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ·';

        showConfirm(message, function () {
            form.submit();
        }, {
            title: title
        });
    });
});
