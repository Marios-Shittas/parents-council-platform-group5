// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function ensureDashboardNoticeElements() {
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
function showDashboardNotice(message, options) {
    ensureDashboardNoticeElements();

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
function truncateDashboardPreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function getDashboardFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function syncDashboardInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function renderDashboardImagePreview(preview, stagedFiles, onRemove) {
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
        caption.textContent = truncateDashboardPreviewFileName(file.name, 18);

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
function renderDashboardAttachmentPreview(preview, stagedFiles, onRemove) {
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
        text.textContent = truncateDashboardPreviewFileName(file.name, 40);

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
function setupDashboardImageInput(input, previewId, imageLimit, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function updateInputState() {
        if (existingCount + stagedFiles.length >= imageLimit) {
            input.disabled = true;
        } else if (existingCount < imageLimit) {
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
        stagedKeys.delete(getDashboardFileKey(removedFile));
        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();
    }

    input.addEventListener('change', function () {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        const maxFileSize = 5 * 1024 * 1024;
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];
        let reachedLimit = false;

        if (incomingFiles.length === 0) {
            return;
        }

        if (existingCount >= imageLimit) {
            warnings.push(`ÎˆÏ‡ÎµÎ¹ Î®Î´Î· ÏƒÏ…Î¼Ï€Î»Î·ÏÏ‰Î¸ÎµÎ¯ Ï„Î¿ ÏŒÏÎ¹Î¿ Ï„Ï‰Î½ ${imageLimit} ÎµÎ¹ÎºÏŒÎ½Ï‰Î½.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getDashboardFileKey(file);
                const fileExt = (file.name.split('.').pop() || '').toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ· ÎµÏ€Î­ÎºÏ„Î±ÏƒÎ·. Î•Ï€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ Î¼ÏŒÎ½Î¿ JPG, JPEG, PNG, GIF.`);
                    return;
                }

                if (file.size > maxFileSize) {
                    warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" ÎµÎ¯Î½Î±Î¹ Ï€Î¿Î»Ï Î¼ÎµÎ³Î¬Î»Î¿ (${(file.size / 1024 / 1024).toFixed(2)}MB). ÎœÎ­Î³Î¹ÏƒÏ„Î¿ Î¼Î­Î³ÎµÎ¸Î¿Ï‚: 5MB.`);
                    return;
                }

                if (!String(file.type || '').startsWith('image/')) {
                    warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î´ÎµÎ½ Ï†Î±Î¯Î½ÎµÏ„Î±Î¹ Î½Î± ÎµÎ¯Î½Î±Î¹ ÎµÎ¹ÎºÏŒÎ½Î±.`);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Î¤Î¿ Î±ÏÏ‡ÎµÎ¯Î¿ "${file.name}" Î­Ï‡ÎµÎ¹ Î®Î´Î· ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= imageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, imageLimit - existingCount - stagedFiles.length);
                        warnings.push(`ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î¼ÏŒÎ½Î¿ ${remainingSlots} Î±ÎºÏŒÎ¼Î· ÎµÎ¹ÎºÏŒÎ½Î±/ÎµÏ‚.`);
                        reachedLimit = true;
                    }
                    return;
                }

                stagedFiles.push(file);
                stagedKeys.add(fileKey);
            });
        }

        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardImagePreview(preview, stagedFiles, removeStagedFile);
        updateInputState();

        if (warnings.length > 0) {
            showDashboardNotice('Î ÏÎ¿ÎµÎ¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

// Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
function setupDashboardAttachmentInput(input, previewId, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function removeStagedFile(index) {
        const removedFile = stagedFiles[index];
        if (!removedFile) {
            return;
        }

        stagedFiles.splice(index, 1);
        stagedKeys.delete(getDashboardFileKey(removedFile));
        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardAttachmentPreview(preview, stagedFiles, removeStagedFile);
    }

    input.addEventListener('change', function () {
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const maxFileSize = 8 * 1024 * 1024;
        const incomingFiles = Array.from(input.files || []);
        const warnings = [];

        if (incomingFiles.length === 0) {
            return;
        }

        incomingFiles.forEach((file) => {
            const fileKey = getDashboardFileKey(file);
            const fileExt = (file.name.split('.').pop() || '').toLowerCase();
            const fileType = String(file.type || '');

            if (!allowedExtensions.includes(fileExt)) {
                warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ· ÎµÏ€Î­ÎºÏ„Î±ÏƒÎ·. Î•Ï€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ Î¼ÏŒÎ½Î¿ PDF, JPG, JPEG, PNG.`);
                return;
            }

            if (file.size > maxFileSize) {
                warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" ÎµÎ¯Î½Î±Î¹ Ï€Î¿Î»Ï Î¼ÎµÎ³Î¬Î»Î¿ (${(file.size / 1024 / 1024).toFixed(2)}MB). ÎœÎ­Î³Î¹ÏƒÏ„Î¿ Î¼Î­Î³ÎµÎ¸Î¿Ï‚: 8MB.`);
                return;
            }

            if (fileType !== '' && fileType !== 'application/pdf' && !fileType.startsWith('image/')) {
                warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î´ÎµÎ½ Î­Ï‡ÎµÎ¹ Î­Î³ÎºÏ…ÏÎ¿ Ï„ÏÏ€Î¿ Î±ÏÏ‡ÎµÎ¯Î¿Ï….`);
                return;
            }

            if (stagedKeys.has(fileKey)) {
                warnings.push(`Î¤Î¿ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Î¿ "${file.name}" Î­Ï‡ÎµÎ¹ Î®Î´Î· ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯.`);
                return;
            }

            stagedFiles.push(file);
            stagedKeys.add(fileKey);
        });

        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardAttachmentPreview(preview, stagedFiles, removeStagedFile);

        if (warnings.length > 0) {
            showDashboardNotice('Î ÏÎ¿ÎµÎ¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });
}

setupDashboardImageInput(document.getElementById('calendar_event_images'), 'calendarEventImagePreview', 6, 'ÎˆÎ»ÎµÎ³Ï‡Î¿Ï‚ ÎµÎ¹ÎºÏŒÎ½Ï‰Î½ ÎµÎºÎ´Î®Î»Ï‰ÏƒÎ·Ï‚');
setupDashboardImageInput(document.getElementById('calendar_announcement_images'), 'calendarAnnouncementImagePreview', 6, 'ÎˆÎ»ÎµÎ³Ï‡Î¿Ï‚ ÎµÎ¹ÎºÏŒÎ½Ï‰Î½ Î±Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ·Ï‚');
setupDashboardAttachmentInput(document.getElementById('calendar_announcement_attachments'), 'calendarAnnouncementAttachmentPreview', 'ÎˆÎ»ÎµÎ³Ï‡Î¿Ï‚ ÏƒÏ…Î½Î·Î¼Î¼Î­Î½Ï‰Î½ Î±Î½Î±ÎºÎ¿Î¯Î½Ï‰ÏƒÎ·Ï‚');
