// Leitourgia ensureDashboardNoticeElements: krataei tin antistoixi symperifora tou UI.
function ensureDashboardNoticeElements() {
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

// Leitourgia showDashboardNotice: krataei tin antistoixi symperifora tou UI.
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

    title.textContent = opts.title || 'Ειδοποίηση';
    body.textContent = message || 'Συνέβη ένα απρόσμενο σφάλμα.';

    overlay.setAttribute('data-prev-overflow', document.body.style.overflow || '');
    document.body.style.overflow = 'hidden';
    overlay.classList.add('is-open');
}

// Leitourgia truncateDashboardPreviewFileName: krataei tin antistoixi symperifora tou UI.
function truncateDashboardPreviewFileName(fileName, maxLength) {
    if (fileName.length <= maxLength) {
        return fileName;
    }

    return fileName.slice(0, Math.max(0, maxLength - 3)) + '...';
}

// Leitourgia getDashboardFileKey: krataei tin antistoixi symperifora tou UI.
function getDashboardFileKey(file) {
    return [file.name, file.size, file.lastModified, file.type].join('::');
}

// Leitourgia syncDashboardInputFiles: krataei tin antistoixi symperifora tou UI.
function syncDashboardInputFiles(input, stagedFiles) {
    if (typeof DataTransfer === 'undefined') {
        return;
    }

    const dataTransfer = new DataTransfer();
    stagedFiles.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

// Leitourgia renderDashboardImagePreview: krataei tin antistoixi symperifora tou UI.
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
        deleteBtn.setAttribute('aria-label', `Αφαίρεση ${file.name}`);
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

// Leitourgia renderDashboardAttachmentPreview: krataei tin antistoixi symperifora tou UI.
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

// Leitourgia setupDashboardImageInput: krataei tin antistoixi symperifora tou UI.
function setupDashboardImageInput(input, previewId, imageLimit, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const existingCount = Number.parseInt(input.dataset.existingCount || '0', 10) || 0;
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Leitourgia updateInputState: krataei tin antistoixi symperifora tou UI.
    function updateInputState() {
        if (existingCount + stagedFiles.length >= imageLimit) {
            input.disabled = true;
        } else if (existingCount < imageLimit) {
            input.disabled = false;
        }
    }

    // Leitourgia removeStagedFile: krataei tin antistoixi symperifora tou UI.
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
            warnings.push(`Έχει ήδη συμπληρωθεί το όριο των ${imageLimit} εικόνων.`);
        } else {
            incomingFiles.forEach((file) => {
                const fileKey = getDashboardFileKey(file);
                const fileExt = (file.name.split('.').pop() || '').toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    warnings.push(`Το αρχείο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο JPG, JPEG, PNG, GIF.`);
                    return;
                }

                if (file.size > maxFileSize) {
                    warnings.push(`Το αρχείο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 5MB.`);
                    return;
                }

                if (!String(file.type || '').startsWith('image/')) {
                    warnings.push(`Το αρχείο "${file.name}" δεν φαίνεται να είναι εικόνα.`);
                    return;
                }

                if (stagedKeys.has(fileKey)) {
                    warnings.push(`Το αρχείο "${file.name}" έχει ήδη επιλεγεί.`);
                    return;
                }

                if (existingCount + stagedFiles.length >= imageLimit) {
                    if (!reachedLimit) {
                        const remainingSlots = Math.max(0, imageLimit - existingCount - stagedFiles.length);
                        warnings.push(`Μπορείτε να προσθέσετε μόνο ${remainingSlots} ακόμη εικόνα/ες.`);
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
            showDashboardNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });

    updateInputState();
}

// Leitourgia setupDashboardAttachmentInput: krataei tin antistoixi symperifora tou UI.
function setupDashboardAttachmentInput(input, previewId, noticeTitle) {
    if (!input) {
        return;
    }

    const preview = document.getElementById(previewId);
    const stagedFiles = [];
    const stagedKeys = new Set();

    // Leitourgia removeStagedFile: krataei tin antistoixi symperifora tou UI.
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
                warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρη επέκταση. Επιτρέπονται μόνο PDF, JPG, JPEG, PNG.`);
                return;
            }

            if (file.size > maxFileSize) {
                warnings.push(`Το συνημμένο "${file.name}" είναι πολύ μεγάλο (${(file.size / 1024 / 1024).toFixed(2)}MB). Μέγιστο μέγεθος: 8MB.`);
                return;
            }

            if (fileType !== '' && fileType !== 'application/pdf' && !fileType.startsWith('image/')) {
                warnings.push(`Το συνημμένο "${file.name}" δεν έχει έγκυρο τύπο αρχείου.`);
                return;
            }

            if (stagedKeys.has(fileKey)) {
                warnings.push(`Το συνημμένο "${file.name}" έχει ήδη επιλεγεί.`);
                return;
            }

            stagedFiles.push(file);
            stagedKeys.add(fileKey);
        });

        syncDashboardInputFiles(input, stagedFiles);
        renderDashboardAttachmentPreview(preview, stagedFiles, removeStagedFile);

        if (warnings.length > 0) {
            showDashboardNotice('Προειδοποιήσεις:\n\n' + warnings.join('\n\n'), {
                title: noticeTitle,
                variant: 'warning'
            });
        }
    });
}

setupDashboardImageInput(document.getElementById('calendar_event_images'), 'calendarEventImagePreview', 6, 'Έλεγχος εικόνων εκδήλωσης');
setupDashboardImageInput(document.getElementById('calendar_announcement_images'), 'calendarAnnouncementImagePreview', 6, 'Έλεγχος εικόνων ανακοίνωσης');
setupDashboardAttachmentInput(document.getElementById('calendar_announcement_attachments'), 'calendarAnnouncementAttachmentPreview', 'Έλεγχος συνημμένων ανακοίνωσης');
