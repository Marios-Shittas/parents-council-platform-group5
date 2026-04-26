// Arxeio: public\assets\js\admin-applications.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-template-editor-close]').forEach(function (button) {
        // Kleinei ton protypo editor xoris inline JavaScript sto PHP provoli.
        button.addEventListener('click', function () {
            var selector = document.getElementById('template_selector');
            var editor = document.getElementById('template_editor');

            if (selector) {
                selector.value = '';
            }

            if (editor) {
                editor.classList.add('admin-hidden');
            }
        });
    });

    var createInstructionFiles = document.getElementById('create_instruction_files');
    var createInstructionFilesList = document.getElementById('create_instruction_files_list');
    var createInstructionSelectedFiles = [];
    var createApplicationOpenDate = document.getElementById('create_application_open_date');
    var createApplicationCloseDate = document.getElementById('create_application_close_date');

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateCloseDateMin() {
        if (!createApplicationOpenDate || !createApplicationCloseDate) {
            return;
        }

        var openValue = String(createApplicationOpenDate.value || '').trim();
        createApplicationCloseDate.min = openValue;
        if (openValue !== '' && createApplicationCloseDate.value !== '' && createApplicationCloseDate.value < openValue) {
            createApplicationCloseDate.value = '';
        }
    }

    if (createApplicationOpenDate && createApplicationCloseDate) {
        createApplicationOpenDate.addEventListener('change', syncCreateCloseDateMin);
        syncCreateCloseDateMin();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateInstructionInputFiles(nextFiles) {
        if (!createInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                createInstructionFiles.value = '';
            }
            return;
        }

        var transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach(function (file) {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        createInstructionFiles.files = transfer.files;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderCreateInstructionFilesList() {
        if (!createInstructionFilesList) {
            return;
        }

        if (!Array.isArray(createInstructionSelectedFiles) || createInstructionSelectedFiles.length === 0) {
            createInstructionFilesList.innerHTML = '<div class="text-muted small">Î”ÎµÎ½ Î­Ï‡Î¿Ï…Î½ ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯ Î±ÏÏ‡ÎµÎ¯Î±.</div>';
            return;
        }

        createInstructionFilesList.innerHTML = createInstructionSelectedFiles.map(function (file, index) {
            var fileName = file && file.name ? String(file.name) : 'Î‘ÏÏ‡ÎµÎ¯Î¿';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-create-instruction-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Î‘Ï†Î±Î¯ÏÎµÏƒÎ·' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        createInstructionFilesList.querySelectorAll('.js-remove-create-instruction-file').forEach(function (button) {
            button.addEventListener('click', function () {
                var fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= createInstructionSelectedFiles.length) {
                    return;
                }

                createInstructionSelectedFiles.splice(fileIndex, 1);
                syncCreateInstructionInputFiles(createInstructionSelectedFiles);
                renderCreateInstructionFilesList();
            });
        });
    }

    if (createInstructionFiles) {
        createInstructionFiles.addEventListener('change', function () {
            var selectedFiles = Array.from(createInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            if (selectedFiles.length > 4) {
                alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± ÎµÏ€Î¹Î»Î­Î¾ÎµÏ„Îµ Î­Ï‰Ï‚ 4 Î±ÏÏ‡ÎµÎ¯Î± Î¿Î´Î·Î³Î¹ÏŽÎ½.');
                createInstructionFiles.value = '';
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            var allowedExtensions = ['pdf', 'doc', 'docx'];
            var hasInvalidFile = selectedFiles.some(function (file) {
                var fileName = String((file && file.name) || '');
                var extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return allowedExtensions.indexOf(extension) === -1;
            });

            if (hasInvalidFile) {
                alert('Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½ Î¿Î´Î·Î³Î¹ÏŽÎ½: pdf, doc, docx.');
                createInstructionFiles.value = '';
                createInstructionSelectedFiles = [];
                renderCreateInstructionFilesList();
                return;
            }

            createInstructionSelectedFiles = selectedFiles;
            renderCreateInstructionFilesList();
        });

        renderCreateInstructionFilesList();
    }

    var createModalEl = document.getElementById('createApplicationModal');
    if (createModalEl) {
        createModalEl.addEventListener('hidden.bs.modal', function () {
            var createApplicationTitle = document.getElementById('create_application_title');
            var createApplicationDescription = document.getElementById('create_application_description');
            if (createApplicationTitle) {
                createApplicationTitle.value = '';
            }
            if (createApplicationDescription) {
                createApplicationDescription.value = '';
            }
            if (createApplicationOpenDate) {
                createApplicationOpenDate.value = String(window.ADMIN_APPLICATIONS_TODAY_UI_DATE || '');
            }
            if (createApplicationCloseDate) {
                createApplicationCloseDate.value = '';
            }
            syncCreateCloseDateMin();
            if (createInstructionFiles) {
                createInstructionFiles.value = '';
            }
            createInstructionSelectedFiles = [];
            renderCreateInstructionFilesList();

            var createStatusSelect = document.getElementById('create_application_status');
            if (createStatusSelect) {
                createStatusSelect.value = 'active';
            }
        });
    }

    var urlParams = new URLSearchParams(window.location.search);
    var scrollYParam = parseInt(urlParams.get('scroll_y') || '0', 10);
    if (!Number.isNaN(scrollYParam) && scrollYParam > 0) {
        window.scrollTo({ top: scrollYParam, behavior: 'auto' });
    }

    var activeTabParam = (urlParams.get('active_tab') || '').toLowerCase();
    var tabMap = {
        manage: 'tab-manage-link',
        templates: 'tab-templates-link'
    };
    var tabButtonId = tabMap[activeTabParam] || '';
    if (tabButtonId) {
        var tabButton = document.getElementById(tabButtonId);
        if (tabButton && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabButton).show();
        }
    }

    document.querySelectorAll('.js-create-publish-btn').forEach(function (createPublishButton) {
        createPublishButton.addEventListener('click', function () {
            var createStatusSelect = document.getElementById('create_application_status');
            if (createStatusSelect) {
                createStatusSelect.value = 'active';
            }
        });
    });

    var editModal = document.getElementById('editApplicationModal');
    var editForm = document.getElementById('edit_application_form');
    var editInstructionFiles = document.getElementById('edit_instruction_files');
    var editFilesList = document.getElementById('edit_application_files_list');
    var editFormFieldsTabButton = document.getElementById('edit-form-fields-tab');
    var editFormFieldsContainer = document.getElementById('edit_application_form_fields_container');
    var editFormFieldsLockedNotice = document.getElementById('edit_application_form_fields_locked');
    var addEditFormFieldBtn = document.getElementById('add_edit_application_form_field_btn');
    var editFormFieldsJsonInput = document.getElementById('edit_application_form_fields_json');
    var editReturnApplicationIdInput = document.getElementById('edit_return_application_id');
    var editReturnTabInput = document.getElementById('edit_return_tab');
    var deleteDocumentConfirmModalEl = document.getElementById('deleteDocumentConfirmModal');
    var confirmDeleteDocumentBtn = document.getElementById('confirm_delete_document_btn');
    var deleteDocumentConfirmModal = deleteDocumentConfirmModalEl ? new bootstrap.Modal(deleteDocumentConfirmModalEl) : null;
    var pendingDeleteDocumentForm = null;
    var editApplicationFormSchema = [];
    var editApplicationIsPublished = false;
    var editApplicationExistingFiles = [];
    var editApplicationOpenDateInput = document.getElementById('edit_application_open_date');
    var editApplicationCloseDateInput = document.getElementById('edit_application_close_date');

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncEditCloseDateMin() {
        if (!editApplicationOpenDateInput || !editApplicationCloseDateInput) {
            return;
        }

        var openValue = String(editApplicationOpenDateInput.value || '').trim();
        editApplicationCloseDateInput.min = openValue;
        if (openValue !== '' && editApplicationCloseDateInput.value !== '' && editApplicationCloseDateInput.value < openValue) {
            editApplicationCloseDateInput.value = '';
        }
    }

    if (editApplicationOpenDateInput) {
        editApplicationOpenDateInput.addEventListener('change', syncEditCloseDateMin);
    }

    var editApplicationFieldTypes = {
        text: 'ÎšÎµÎ¯Î¼ÎµÎ½Î¿',
        email: 'Email',
        phone: 'Î¤Î·Î»Î­Ï†Ï‰Î½Î¿',
        date: 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î±',
        textarea: 'ÎœÎµÎ³Î¬Î»Î¿ ÎšÎµÎ¯Î¼ÎµÎ½Î¿',
        checkbox: 'Tick Box',
        file_upload: 'Î‘ÏÏ‡ÎµÎ¯Î¿'
    };

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function normalizeEditApplicationFieldType(type) {
        var normalized = String(type || 'text').toLowerCase();
        if (normalized === 'tel') {
            normalized = 'phone';
        }
        if (normalized === 'file') {
            normalized = 'file_upload';
        }
        return Object.prototype.hasOwnProperty.call(editApplicationFieldTypes, normalized) ? normalized : 'text';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncEditApplicationFormSchemaFromInputs() {
        if (!editFormFieldsContainer) {
            return;
        }

        var nextSchema = [];
        editFormFieldsContainer.querySelectorAll('.js-edit-app-field-card').forEach(function (card) {
            var nameInput = card.querySelector('.js-edit-app-field-name');
            var typeSelect = card.querySelector('.js-edit-app-field-type');
            var requiredCheck = card.querySelector('.js-edit-app-field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: normalizeEditApplicationFieldType(typeSelect ? typeSelect.value : 'text'),
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        editApplicationFormSchema = nextSchema;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getCurrentEditTabKey() {
        var activeTabButton = document.querySelector('#editApplicationTabs .nav-link.active');
        var activeTabId = activeTabButton ? String(activeTabButton.id || '') : '';

        if (activeTabId === 'edit-files-tab') {
            return 'files';
        }
        if (activeTabId === 'edit-form-fields-tab') {
            return 'form_fields';
        }

        return 'info';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function normalizeEditTabKey(tabKey) {
        var normalizedTabKey = String(tabKey || '').toLowerCase();
        if (normalizedTabKey === 'files' || normalizedTabKey === 'edit-files' || normalizedTabKey === 'edit-files-pane') {
            return 'files';
        }
        if (normalizedTabKey === 'form_fields' || normalizedTabKey === 'form-fields' || normalizedTabKey === 'fields' || normalizedTabKey === 'edit-form-fields' || normalizedTabKey === 'edit-form-fields-pane') {
            return 'form_fields';
        }

        return 'info';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function showEditTabByKey(tabKey) {
        var normalizedTabKey = String(tabKey || '').toLowerCase();
        var targetTabId = 'edit-info-tab';

        if (normalizedTabKey === 'files') {
            targetTabId = 'edit-files-tab';
        } else if (normalizedTabKey === 'form_fields') {
            targetTabId = 'edit-form-fields-tab';
        }

        var targetTabButton = document.getElementById(targetTabId);
        if (!targetTabButton || targetTabButton.disabled || !(window.bootstrap && bootstrap.Tab)) {
            targetTabButton = document.getElementById('edit-info-tab');
        }

        if (targetTabButton && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(targetTabButton).show();
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderEditApplicationFormFields() {
        if (!editFormFieldsContainer) {
            return;
        }

        editFormFieldsContainer.innerHTML = '';

        if (!editApplicationIsPublished) {
            if (editFormFieldsLockedNotice) {
                editFormFieldsLockedNotice.classList.remove('d-none');
            }
            return;
        }

        if (editFormFieldsLockedNotice) {
            editFormFieldsLockedNotice.classList.add('d-none');
        }

        if (!Array.isArray(editApplicationFormSchema) || editApplicationFormSchema.length === 0) {
            editFormFieldsContainer.innerHTML = '<div class="text-muted">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î±ÎºÏŒÎ¼Î· Ï€ÎµÎ´Î¯Î± Ï†ÏŒÏÎ¼Î±Ï‚.</div>';
            return;
        }

        editApplicationFormSchema.forEach(function (field, index) {
            var fieldType = normalizeEditApplicationFieldType(field.type || 'text');
            var fieldCard = document.createElement('div');
            fieldCard.className = 'card border-0 bg-white js-edit-app-field-card';

            var typeOptions = Object.keys(editApplicationFieldTypes).map(function (value) {
                var selected = fieldType === value ? ' selected' : '';
                return '<option value="' + escapeHtml(value) + '"' + selected + '>' + escapeHtml(editApplicationFieldTypes[value]) + '</option>';
            }).join('');

            fieldCard.innerHTML =
                '<div class="card-body py-2 px-3">' +
                    '<div class="row g-2 align-items-center">' +
                        '<div class="col-12 col-md-4">' +
                            '<input type="text" class="form-control form-control-sm js-edit-app-field-name" value="' + escapeHtml(field.name || '') + '" placeholder="Ï€.Ï‡. ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿">' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<select class="form-select form-select-sm js-edit-app-field-type">' + typeOptions + '</select>' +
                        '</div>' +
                        '<div class="col-12 col-md-3">' +
                            '<div class="form-check">' +
                                '<input type="checkbox" class="form-check-input js-edit-app-field-required" id="edit_app_required_' + String(index) + '"' + (field.required ? ' checked' : '') + '>' +
                                '<label class="form-check-label" for="edit_app_required_' + String(index) + '">Î¥Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÏŒ</label>' +
                            '</div>' +
                        '</div>' +
                        '<div class="col-12 col-md-2 text-md-end">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-app-field"><i class="fas fa-trash-alt"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            editFormFieldsContainer.appendChild(fieldCard);

            var nameInput = fieldCard.querySelector('.js-edit-app-field-name');
            var typeSelect = fieldCard.querySelector('.js-edit-app-field-type');
            var requiredCheck = fieldCard.querySelector('.js-edit-app-field-required');
            var removeBtn = fieldCard.querySelector('.js-remove-edit-app-field');

            var persistField = function () {
                editApplicationFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: normalizeEditApplicationFieldType(typeSelect ? typeSelect.value : 'text'),
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistField);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistField);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistField);
            }
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    syncEditApplicationFormSchemaFromInputs();
                    editApplicationFormSchema.splice(index, 1);
                    renderEditApplicationFormFields();
                });
            }
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function humanizeSubmissionDetailLabel(key) {
        var labels = {
            parent_name: 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ Î“Î¿Î½Î­Î±',
            parent_email: 'Email Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
            parent_phone: 'Î¤Î·Î»Î­Ï†Ï‰Î½Î¿ Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
            student_name: 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎœÎ±Î¸Î·Ï„Î®/ÎœÎ±Î¸Î®Ï„ÏÎ¹Î±Ï‚',
            student_class: 'Î¤Î¼Î®Î¼Î± / Î¤Î¬Î¾Î·',
            manual_application_text: 'ÎšÎµÎ¯Î¼ÎµÎ½Î¿ Î‘Î¯Ï„Î·ÏƒÎ·Ï‚',
            applied_at: 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î¥Ï€Î¿Î²Î¿Î»Î®Ï‚',
            _submission_mode: 'Î¤ÏÏŒÏ€Î¿Ï‚ Î¥Ï€Î¿Î²Î¿Î»Î®Ï‚'
        };

        return labels[key] || String(key || '').replace(/_/g, ' ');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function buildSubmissionFieldDefinitionsLookup(fieldDefinitions) {
        var lookup = {};
        if (!Array.isArray(fieldDefinitions)) {
            return lookup;
        }

        fieldDefinitions.forEach(function (fieldDefinition) {
            if (!fieldDefinition || typeof fieldDefinition !== 'object') {
                return;
            }

            var key = String(fieldDefinition.key || '').trim();
            if (!key) {
                return;
            }

            lookup[key] = {
                name: String(fieldDefinition.name || '').trim(),
                type: String(fieldDefinition.type || 'text').toLowerCase()
            };
        });

        return lookup;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function buildUploadedFileLinksLookup(uploadedFileLinks) {
        var lookup = {};
        if (!Array.isArray(uploadedFileLinks)) {
            return lookup;
        }

        uploadedFileLinks.forEach(function (uploadedFile) {
            if (!uploadedFile || typeof uploadedFile !== 'object') {
                return;
            }

            var name = String(uploadedFile.name || '').trim();
            var url = String(uploadedFile.url || '').trim();
            if (!name || !url) {
                return;
            }

            lookup[name] = url;
        });

        return lookup;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName) {
        if (key === '_submission_mode') {
            return value === 'manual' ? 'Online Î£Ï…Î¼Ï€Î»Î®ÏÏ‰ÏƒÎ·' : 'Î‘Î½Î­Î²Î±ÏƒÎ¼Î± Î‘ÏÏ‡ÎµÎ¯Î¿Ï…';
        }

        var raw = Array.isArray(value) ? value.join(', ') : String(value == null ? '' : value);
        raw = raw.trim();

        var normalizedType = fieldMeta && fieldMeta.type ? String(fieldMeta.type).toLowerCase() : '';
        if (normalizedType === 'checkbox') {
            var checked = ['1', 'true', 'on', 'yes'].indexOf(raw.toLowerCase()) !== -1;
            return '<input type="checkbox" class="detail-checkbox-input" disabled' + (checked ? ' checked' : '') + '>';
        }

        if (!raw) {
            return 'â€”';
        }

        if (normalizedType === 'file_upload') {
            var fileUrl = uploadedFileLinksByName && uploadedFileLinksByName[raw] ? uploadedFileLinksByName[raw] : '';
            if (fileUrl) {
                return '<a href="' + escapeHtml(fileUrl) + '" target="_blank" rel="noopener noreferrer" class="detail-field-link">' + escapeHtml(raw) + '</a>';
            }
        }

        return escapeHtml(raw).replace(/\r?\n/g, '<br>');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function buildSubmissionDetailHtml(fields, fieldDefinitions, uploadedFileLinks) {
        if (!fields || typeof fields !== 'object') {
            return '<div class="text-muted">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï†ÏŒÏÎ¼Î±Ï‚.</div>';
        }

        var fieldDefinitionsLookup = buildSubmissionFieldDefinitionsLookup(fieldDefinitions);
        var uploadedFileLinksByName = buildUploadedFileLinksLookup(uploadedFileLinks);
        var html = '';
        var renderedKeys = {};

        if (Array.isArray(fieldDefinitions)) {
            fieldDefinitions.forEach(function (fieldDefinition) {
                if (!fieldDefinition || typeof fieldDefinition !== 'object') {
                    return;
                }

                var key = String(fieldDefinition.key || '').trim();
                if (!key || renderedKeys[key]) {
                    return;
                }

                var fieldMeta = fieldDefinitionsLookup[key] || null;
                var label = fieldMeta && fieldMeta.name ? fieldMeta.name : humanizeSubmissionDetailLabel(key);
                var hasValue = Object.prototype.hasOwnProperty.call(fields, key);
                var value = hasValue ? fields[key] : '';
                var formattedValue = formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName);
                var isCheckbox = fieldMeta && fieldMeta.type === 'checkbox';

                if (formattedValue === 'â€”' && !isCheckbox) {
                    return;
                }

                renderedKeys[key] = true;
                html += '<div class="detail-field-item"><span>' +
                    escapeHtml(label) +
                    '</span><strong>' + formattedValue + '</strong></div>';
            });
        }

        Object.keys(fields).forEach(function (key) {
            if (renderedKeys[key]) {
                return;
            }

            if (key === '_formType' || key === '_category' || key === '_uploaded_files' || key === '_uploaded_file_names') {
                return;
            }

            var value = fields[key];
            if (value == null) {
                return;
            }

            if (Array.isArray(value) && value.length === 0) {
                return;
            }

            var fieldMeta = fieldDefinitionsLookup[key] || null;
            var label = fieldMeta && fieldMeta.name ? fieldMeta.name : humanizeSubmissionDetailLabel(key);
            var formattedValue = formatSubmissionDetailValue(key, value, fieldMeta, uploadedFileLinksByName);
            if (formattedValue === 'â€”') {
                return;
            }

            html += '<div class="detail-field-item"><span>' +
                escapeHtml(label) +
                '</span><strong>' + formattedValue + '</strong></div>';
        });

        return html || '<div class="text-muted">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï†ÏŒÏÎ¼Î±Ï‚.</div>';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderEditApplicationFiles(documents, pendingFiles) {
        if (!editFilesList) {
            return;
        }

        var existingDocs = Array.isArray(documents) ? documents : [];
        var stagedFiles = Array.isArray(pendingFiles) ? pendingFiles : [];

        if (existingDocs.length === 0 && stagedFiles.length === 0) {
            editFilesList.innerHTML = '<div class="text-muted">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î±ÏÏ‡ÎµÎ¯Î± Î³Î¹Î± Î±Ï…Ï„Î® Ï„Î·Î½ Î±Î¯Ï„Î·ÏƒÎ·.</div>';
            return;
        }

        var existingHtml = existingDocs.map(function (doc) {
            var fileName = doc && doc.name ? doc.name : 'Î‘ÏÏ‡ÎµÎ¯Î¿';
            var fileUrl = doc && doc.url ? doc.url : '#';
            var documentId = doc && doc.id ? String(doc.id) : '0';

            return '' +
                '<div class="card border-0 bg-light">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<a href="' + escapeHtml(fileUrl) + '" target="_blank" class="fw-semibold text-decoration-none">' +
                                '<i class="fas fa-file-alt me-1"></i>' + escapeHtml(fileName) +
                            '</a>' +
                            '<form method="POST" class="js-delete-doc-form m-0">' +
                                '<input type="hidden" name="action" value="delete_document">' +
                                '<input type="hidden" name="ap_document_id" value="' + escapeHtml(documentId) + '">' +
                                '<button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt me-1"></i>Î‘Ï†Î±Î¯ÏÎµÏƒÎ·</button>' +
                            '</form>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        var stagedHtml = stagedFiles.map(function (file) {
            var stagedName = file && file.name ? String(file.name) : 'ÎÎ­Î¿ Î±ÏÏ‡ÎµÎ¯Î¿';

            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(stagedName) +
                            '</div>' +
                            '<span class="badge bg-primary-subtle text-primary">Î£Îµ Î±Î½Î±Î¼Î¿Î½Î® Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ·Ï‚</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        editFilesList.innerHTML = existingHtml + stagedHtml;
        editFilesList.querySelectorAll('.js-delete-doc-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!deleteDocumentConfirmModal) {
                    if (confirm('Î˜Î­Î»ÎµÏ„Îµ ÏƒÎ¯Î³Î¿Ï…ÏÎ± Î½Î± Î±Ï†Î±Î¹ÏÎ­ÏƒÎµÏ„Îµ Î±Ï…Ï„ÏŒ Ï„Î¿ Î±ÏÏ‡ÎµÎ¯Î¿;')) {
                        return;
                    }
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                pendingDeleteDocumentForm = form;
                deleteDocumentConfirmModal.show();
            });
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getCurrentStagedInstructionFiles() {
        if (!editInstructionFiles) {
            return [];
        }

        return Array.from(editInstructionFiles.files || []);
    }

    if (confirmDeleteDocumentBtn) {
        confirmDeleteDocumentBtn.addEventListener('click', function () {
            if (!pendingDeleteDocumentForm) {
                if (deleteDocumentConfirmModal) {
                    deleteDocumentConfirmModal.hide();
                }
                return;
            }

            var formToSubmit = pendingDeleteDocumentForm;
            pendingDeleteDocumentForm = null;

            if (deleteDocumentConfirmModal) {
                deleteDocumentConfirmModal.hide();
            }

            var formData = new FormData(formToSubmit);
            formData.append('ajax_delete_document', '1');

            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (response) {
                return response.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (parseError) {
                        throw new Error(text ? text.slice(0, 260) : 'Empty response');
                    }
                });
            })
            .then(function (payload) {
                if (!payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'Î£Ï†Î¬Î»Î¼Î± ÎºÎ±Ï„Î¬ Ï„Î· Î´Î¹Î±Î³ÏÎ±Ï†Î® Ï„Î¿Ï… Î±ÏÏ‡ÎµÎ¯Î¿Ï….');
                }

                var docIdInput = formToSubmit.querySelector('input[name="ap_document_id"]');
                var deletedDocId = docIdInput ? String(docIdInput.value || '') : '';
                if (deletedDocId !== '') {
                    editApplicationExistingFiles = editApplicationExistingFiles.filter(function (doc) {
                        return String((doc && doc.id) || '') !== deletedDocId;
                    });
                }

                renderEditApplicationFiles(editApplicationExistingFiles, getCurrentStagedInstructionFiles());
            })
            .catch(function (error) {
                alert(error && error.message ? error.message : 'Î£Ï†Î¬Î»Î¼Î± ÎºÎ±Ï„Î¬ Ï„Î· Î´Î¹Î±Î³ÏÎ±Ï†Î® Ï„Î¿Ï… Î±ÏÏ‡ÎµÎ¯Î¿Ï….');
            });
        });
    }

    if (deleteDocumentConfirmModalEl) {
        deleteDocumentConfirmModalEl.addEventListener('hidden.bs.modal', function () {
            pendingDeleteDocumentForm = null;
        });
    }

    if (editInstructionFiles) {
        editInstructionFiles.addEventListener('change', function () {
            var selectedFiles = Array.from(editInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            var remainingSlots = Math.max(0, 4 - editApplicationExistingFiles.length);
            if (selectedFiles.length > remainingSlots) {
                alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î­Ï‰Ï‚ ' + String(remainingSlots) + ' Î±ÎºÏŒÎ¼Î· Î±ÏÏ‡ÎµÎ¯Î±.');
                editInstructionFiles.value = '';
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            var allowedExtensions = ['pdf', 'doc', 'docx'];
            var hasInvalidFile = selectedFiles.some(function (file) {
                var name = String((file && file.name) || '');
                var ext = name.indexOf('.') !== -1 ? name.split('.').pop().toLowerCase() : '';
                return allowedExtensions.indexOf(ext) === -1;
            });

            if (hasInvalidFile) {
                alert('Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹: pdf, doc, docx.');
                editInstructionFiles.value = '';
                renderEditApplicationFiles(editApplicationExistingFiles, []);
                return;
            }

            renderEditApplicationFiles(editApplicationExistingFiles, selectedFiles);
        });
    }

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var applicationId = button.getAttribute('data-application-id') || '';
            document.getElementById('edit_application_id').value = applicationId;
            document.getElementById('edit_application_title').value = button.getAttribute('data-application-title') || '';
            document.getElementById('edit_application_description').value = button.getAttribute('data-application-description') || '';
            if (editApplicationOpenDateInput) {
                editApplicationOpenDateInput.value = button.getAttribute('data-application-open-date') || '';
            }
            if (editApplicationCloseDateInput) {
                editApplicationCloseDateInput.value = button.getAttribute('data-application-close-date') || '';
            }
            syncEditCloseDateMin();

            if (editInstructionFiles) {
                editInstructionFiles.value = '';
            }

            var documentsRaw = button.getAttribute('data-application-documents') || '[]';
            var documents = [];
            try {
                documents = JSON.parse(documentsRaw);
            } catch (error) {
                documents = [];
            }
            editApplicationExistingFiles = Array.isArray(documents) ? documents : [];
            renderEditApplicationFiles(editApplicationExistingFiles, []);

            var formFieldsRaw = button.getAttribute('data-application-form-fields') || '[]';
            var formFields = [];
            try {
                formFields = JSON.parse(formFieldsRaw);
            } catch (error) {
                formFields = [];
            }

            editApplicationIsPublished = button.getAttribute('data-application-published') === '1';
            editApplicationFormSchema = Array.isArray(formFields)
                ? formFields.map(function (field) {
                    return {
                        name: String((field && field.name) || ''),
                        type: normalizeEditApplicationFieldType((field && field.type) || 'text'),
                        required: Boolean(field && field.required)
                    };
                })
                : [];

            if (addEditFormFieldBtn) {
                addEditFormFieldBtn.disabled = !editApplicationIsPublished;
            }
            if (editFormFieldsTabButton) {
                editFormFieldsTabButton.disabled = !editApplicationIsPublished;
                editFormFieldsTabButton.title = editApplicationIsPublished ? '' : 'Î”Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î¿ Î¼ÏŒÎ½Î¿ Î³Î¹Î± Î´Î·Î¼Î¿ÏƒÎ¹ÎµÏ…Î¼Î­Î½ÎµÏ‚ Î±Î¹Ï„Î®ÏƒÎµÎ¹Ï‚';
            }

            renderEditApplicationFormFields();

            var infoTabButton = document.getElementById('edit-info-tab');
            if (infoTabButton && window.bootstrap && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(infoTabButton).show();
            }
        });
    }

    if (addEditFormFieldBtn) {
        addEditFormFieldBtn.addEventListener('click', function () {
            if (!editApplicationIsPublished) {
                return;
            }

            syncEditApplicationFormSchemaFromInputs();
            editApplicationFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderEditApplicationFormFields();
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            var editOpenDateValue = editApplicationOpenDateInput ? String(editApplicationOpenDateInput.value || '').trim() : '';
            var editCloseDateValue = editApplicationCloseDateInput ? String(editApplicationCloseDateInput.value || '').trim() : '';
            if (editOpenDateValue === '') {
                event.preventDefault();
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚ ÎµÎ¯Î½Î±Î¹ Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÎ®.');
                if (editApplicationOpenDateInput) {
                    editApplicationOpenDateInput.focus();
                }
                return;
            }
            if (editCloseDateValue !== '' && editCloseDateValue < editOpenDateValue) {
                event.preventDefault();
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± ÎºÎ»ÎµÎ¹ÏƒÎ¯Î¼Î±Ï„Î¿Ï‚ Î´ÎµÎ½ Î¼Ï€Î¿ÏÎµÎ¯ Î½Î± ÎµÎ¯Î½Î±Î¹ Ï€ÏÎ¹Î½ Î±Ï€ÏŒ Ï„Î·Î½ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚.');
                if (editApplicationCloseDateInput) {
                    editApplicationCloseDateInput.focus();
                }
                return;
            }

            if (editReturnApplicationIdInput) {
                var editApplicationIdInput = document.getElementById('edit_application_id');
                editReturnApplicationIdInput.value = editApplicationIdInput ? String(editApplicationIdInput.value || '0') : '0';
            }
            if (editReturnTabInput) {
                editReturnTabInput.value = getCurrentEditTabKey();
            }

            if (!editFormFieldsJsonInput) {
                return;
            }

            if (editInstructionFiles && editInstructionFiles.files && editInstructionFiles.files.length > 0) {
                var stagedFiles = Array.from(editInstructionFiles.files || []);
                var remainingSlots = Math.max(0, 4 - editApplicationExistingFiles.length);

                if (stagedFiles.length > remainingSlots) {
                    event.preventDefault();
                    alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î­Ï‰Ï‚ ' + String(remainingSlots) + ' Î±ÎºÏŒÎ¼Î· Î±ÏÏ‡ÎµÎ¯Î±.');

                    var filesTabButton = document.getElementById('edit-files-tab');
                    if (filesTabButton && window.bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(filesTabButton).show();
                    }
                    return;
                }
            }

            if (!editApplicationIsPublished) {
                editFormFieldsJsonInput.value = '';
                return;
            }

            syncEditApplicationFormSchemaFromInputs();
            var fieldsForSave = editApplicationFormSchema
                .map(function (field) {
                    return {
                        name: String(field.name || '').trim(),
                        type: normalizeEditApplicationFieldType(field.type || 'text'),
                        required: Boolean(field.required)
                    };
                })
                .filter(function (field) {
                    return field.name !== '';
                });

            editFormFieldsJsonInput.value = JSON.stringify(fieldsForSave);
        });
    }

    var activeEditApplicationParam = parseInt(urlParams.get('active_edit_application_id') || '0', 10);
    var activeEditTabParam = normalizeEditTabKey(urlParams.get('active_edit_tab') || 'info');
    if (!Number.isNaN(activeEditApplicationParam) && activeEditApplicationParam > 0 && editModal) {
        var editTriggerButton = document.querySelector('.js-edit-application[data-application-id="' + String(activeEditApplicationParam) + '"]');
        if (editTriggerButton) {
            var onEditModalShown = function () {
                showEditTabByKey(activeEditTabParam);
                editModal.removeEventListener('shown.bs.modal', onEditModalShown);
            };

            editModal.addEventListener('shown.bs.modal', onEditModalShown);
            editTriggerButton.click();

            urlParams.delete('active_edit_application_id');
            urlParams.delete('active_edit_tab');
            var nextQuery = urlParams.toString();
            var nextUrl = window.location.pathname + (nextQuery ? '?' + nextQuery : '') + window.location.hash;
            window.history.replaceState({}, document.title, nextUrl);
        }
    }

    var deleteModal = document.getElementById('deleteApplicationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var applicationId = button.getAttribute('data-application-id') || '';
            var applicationTitle = button.getAttribute('data-application-title') || 'â€”';

            var deleteIdInput = document.getElementById('delete_application_id');
            var deleteTitleDisplay = document.getElementById('delete_application_title_display');

            if (deleteIdInput) {
                deleteIdInput.value = applicationId;
            }

            if (deleteTitleDisplay) {
                deleteTitleDisplay.textContent = applicationTitle;
            }
        });
    }

    var deleteSubmissionModalEl = document.getElementById('deleteSubmissionModal');
    var deleteSubmissionModal = deleteSubmissionModalEl ? new bootstrap.Modal(deleteSubmissionModalEl) : null;
    var deleteSubmissionApplicationId = document.getElementById('delete_submission_application_id');
    var deleteSubmissionUserId = document.getElementById('delete_submission_user_id');
    var deleteSubmissionReturnView = document.getElementById('delete_submission_return_view_submissions');
    var deleteSubmissionReturnSort = document.getElementById('delete_submission_return_submission_sort');
    var deleteSubmissionReturnScroll = document.getElementById('delete_submission_return_scroll_y');
    var deleteSubmissionDetails = document.getElementById('delete_submission_details');

    document.querySelectorAll('.js-submission-action-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var scrollInput = form.querySelector('input[name="return_scroll_y"]');
            if (scrollInput) {
                scrollInput.value = String(window.scrollY || window.pageYOffset || 0);
            }

            var statusField = form.querySelector('select[name="sub_status"], input[name="sub_status"]');
            var selectedStatus = statusField ? String(statusField.value || '') : '';
            if (selectedStatus !== 'delete' || !deleteSubmissionModal) {
                return;
            }

            event.preventDefault();

            var applicationIdInput = form.querySelector('input[name="application_id"]');
            var userIdInput = form.querySelector('input[name="user_id"]');
            var returnViewInput = form.querySelector('input[name="return_view_submissions"]');
            var returnSortInput = form.querySelector('input[name="return_submission_sort"]');
            var applicationTitle = form.getAttribute('data-application-title') || 'â€”';
            var parentName = form.getAttribute('data-parent-name') || 'â€”';

            if (deleteSubmissionApplicationId) {
                deleteSubmissionApplicationId.value = applicationIdInput ? applicationIdInput.value : '';
            }
            if (deleteSubmissionUserId) {
                deleteSubmissionUserId.value = userIdInput ? userIdInput.value : '';
            }
            if (deleteSubmissionReturnView) {
                deleteSubmissionReturnView.value = returnViewInput ? returnViewInput.value : '0';
            }
            if (deleteSubmissionReturnSort) {
                deleteSubmissionReturnSort.value = returnSortInput ? returnSortInput.value : 'newest';
            }
            if (deleteSubmissionReturnScroll) {
                deleteSubmissionReturnScroll.value = String(window.scrollY || window.pageYOffset || 0);
            }
            if (deleteSubmissionDetails) {
                deleteSubmissionDetails.textContent = 'Î‘Î¯Ï„Î·ÏƒÎ·: ' + applicationTitle + ' | Î“Î¿Î½Î­Î±Ï‚: ' + parentName;
            }

            deleteSubmissionModal.show();
        });
    });

    var deleteSubmissionConfirmForm = document.getElementById('delete_submission_confirm_form');
    if (deleteSubmissionConfirmForm) {
        deleteSubmissionConfirmForm.addEventListener('submit', function () {
            if (deleteSubmissionReturnScroll) {
                deleteSubmissionReturnScroll.value = String(window.scrollY || window.pageYOffset || 0);
            }
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatApplicationNotificationCount(count) {
        var value = Number(count) || 0;
        if (value <= 0) {
            return '';
        }
        return value > 9 ? '9+' : String(value);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatSidebarNotificationCount(count) {
        var value = Number(count) || 0;
        if (value <= 0) {
            return '';
        }
        return value > 10 ? '10+' : String(value);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function removeElementSmooth(element, delayMs) {
        if (!element) {
            return;
        }

        if (element.getAttribute('data-removing') === '1') {
            return;
        }

        element.setAttribute('data-removing', '1');
        element.classList.add('notification-badge-fade-out');

        window.setTimeout(function () {
            if (element && element.parentNode) {
                element.remove();
            }
        }, delayMs || 280);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function updateApplicationNotificationBadge(applicationId, waitingCount) {
        var selector = '.application-new-submission-badge[data-application-notification-badge="1"][data-application-id="' + String(applicationId) + '"]';
        var badge = document.querySelector(selector);
        var badgeText = formatApplicationNotificationCount(waitingCount);

        if (badgeText === '') {
            if (badge) {
                removeElementSmooth(badge, 280);
            }
            return;
        }

        if (!badge) {
            var actionButton = document.querySelector('.js-edit-application[data-application-id="' + String(applicationId) + '"]');
            var titleWrap = actionButton ? actionButton.closest('tr').querySelector('td .d-flex.align-items-center.flex-wrap.gap-2') : null;
            if (!titleWrap) {
                return;
            }

            badge = document.createElement('span');
            badge.className = 'application-new-submission-badge';
            badge.setAttribute('data-application-notification-badge', '1');
            badge.setAttribute('data-application-id', String(applicationId));
            titleWrap.appendChild(badge);
        }

        badge.textContent = badgeText;
        badge.setAttribute('aria-label', 'ÎÎ­ÎµÏ‚ Î±Î¹Ï„Î®ÏƒÎµÎ¹Ï‚ Ï€ÏÎ¿Ï‚ Î­Î»ÎµÎ³Ï‡Î¿: ' + String(waitingCount));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function updateSidebarApplicationsBadge(waitingCount) {
        var applicationsNavLink = document.querySelector('#adminSidebar a[href="applications.php"]');
        if (!applicationsNavLink) {
            return;
        }

        var badge = applicationsNavLink.querySelector('.admin-notification-badge');
        var badgeText = formatSidebarNotificationCount(waitingCount);

        if (badgeText === '') {
            if (badge) {
                removeElementSmooth(badge, 280);
            }
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'admin-notification-badge';
            applicationsNavLink.appendChild(badge);
        }

        badge.textContent = badgeText;
        badge.setAttribute('aria-label', 'ÎÎ­ÎµÏ‚ Î±Î¹Ï„Î®ÏƒÎµÎ¹Ï‚ Ï€ÏÎ¿Ï‚ Î­Î»ÎµÎ³Ï‡Î¿: ' + String(waitingCount));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function markSubmissionAsSeenOnHover(row) {
        if (!row || row.getAttribute('data-is-new') !== '1' || row.getAttribute('data-seen-request-running') === '1') {
            return;
        }

        var applicationId = parseInt(row.getAttribute('data-application-id') || '0', 10);
        var userId = parseInt(row.getAttribute('data-user-id') || '0', 10);
        if (Number.isNaN(applicationId) || Number.isNaN(userId) || applicationId <= 0 || userId <= 0) {
            return;
        }

        row.setAttribute('data-seen-request-running', '1');

        var payload = new URLSearchParams();
        payload.set('action', 'mark_submission_seen');
        payload.set('application_id', String(applicationId));
        payload.set('user_id', String(userId));

        fetch('applications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: payload.toString()
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP error');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || data.success !== true) {
                    throw new Error('Request failed');
                }

                row.setAttribute('data-is-new', '0');

                var newBadge = row.querySelector('.submission-new-label');
                if (newBadge) {
                    removeElementSmooth(newBadge, 300);
                    window.setTimeout(function () {
                        row.classList.remove('submission-row-new');
                    }, 300);
                } else {
                    row.classList.remove('submission-row-new');
                }

                updateApplicationNotificationBadge(applicationId, Number(data.application_waiting_count || 0));
                updateSidebarApplicationsBadge(Number(data.global_waiting_count || 0));
            })
            .catch(function () {
                row.setAttribute('data-seen-request-running', '0');
            });
    }

    document.querySelectorAll('tr.submission-row-new[data-is-new="1"]').forEach(function (row) {
        row.addEventListener('mouseenter', function handleHover() {
            markSubmissionAsSeenOnHover(row);
        }, { passive: true });
    });

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getSubmissionNoteKey(applicationId, userId) {
        return 'admin_submission_note_' + String(applicationId) + '_' + String(userId);
    }

    var currentSubmissionNoteKey = '';
    var detailFields = document.getElementById('detail_submission_fields');
    var noteField = document.getElementById('submission_internal_note');
    var noteSaveButton = document.getElementById('save_submission_note_btn');

    document.querySelectorAll('.js-view-submission').forEach(function (button) {
        button.addEventListener('click', function () {
            var raw = button.getAttribute('data-submission');
            if (!raw) return;

            try {
                var data = JSON.parse(raw);
                document.getElementById('detail_application_title').textContent = data.application_title || 'â€”';
                document.getElementById('detail_parent_name').textContent = data.parent_name || data.parent_account || 'â€”';
                document.getElementById('detail_submitted_at').textContent = data.submitted_at || 'â€”';

                var fields = data.submission_data || {};
                var fieldDefinitions = Array.isArray(data.form_field_definitions) ? data.form_field_definitions : [];
                var uploadedFileLinks = Array.isArray(data.uploaded_file_links) ? data.uploaded_file_links : [];
                var html = buildSubmissionDetailHtml(fields, fieldDefinitions, uploadedFileLinks);
                detailFields.innerHTML = html || '<div class="text-muted">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î´Î¹Î±Î¸Î­ÏƒÎ¹Î¼Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï†ÏŒÏÎ¼Î±Ï‚.</div>';

                currentSubmissionNoteKey = getSubmissionNoteKey(data.application_id, data.user_id);
                noteField.value = localStorage.getItem(currentSubmissionNoteKey) || '';
            } catch (error) {
                detailFields.innerHTML = '<div class="text-danger">Î”ÎµÎ½ Î®Ï„Î±Î½ Î´Ï…Î½Î±Ï„Î® Î· Ï†ÏŒÏÏ„Ï‰ÏƒÎ· Ï„Ï‰Î½ Î»ÎµÏ€Ï„Î¿Î¼ÎµÏÎµÎ¹ÏŽÎ½.</div>';
            }
        });
    });

    if (noteSaveButton) {
        noteSaveButton.addEventListener('click', function () {
            if (!currentSubmissionNoteKey) return;
            localStorage.setItem(currentSubmissionNoteKey, noteField.value || '');
            noteSaveButton.classList.remove('btn-outline-primary');
            noteSaveButton.classList.add('btn-success');
            noteSaveButton.innerHTML = '<i class="fas fa-check me-1"></i>Î‘Ï€Î¿Î¸Î·ÎºÎµÏÏ„Î·ÎºÎµ';
            window.setTimeout(function () {
                noteSaveButton.classList.remove('btn-success');
                noteSaveButton.classList.add('btn-outline-primary');
                noteSaveButton.innerHTML = '<i class="fas fa-save me-1"></i>Î‘Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ· Î£Î·Î¼ÎµÎ¯Ï‰ÏƒÎ·Ï‚';
            }, 1800);
        });
    }
});

// Diaxeirisi leitourgion toy editor protypou.
document.addEventListener('DOMContentLoaded', function() {
    const templateSelector = document.getElementById('template_selector');
    const templateEditor = document.getElementById('template_editor');
    const templateEmptyState = document.getElementById('template_empty_state');
    const templateFieldsContainer = document.getElementById('template_fields_container');
    const addTemplateFieldBtn = document.getElementById('add_template_field_btn');
    const editTemplateForm = document.getElementById('editTemplateForm');
    const editTemplateId = document.getElementById('edit_template_id');
    const editTemplateName = document.getElementById('edit_template_name');
    const editTemplateDescription = document.getElementById('edit_template_description');
    const editTemplateOpenDate = document.getElementById('edit_template_open_date');
    const editTemplateCloseDate = document.getElementById('edit_template_close_date');
    const editTemplateInstructionFiles = document.getElementById('edit_template_instruction_files');
    const editTemplateInstructionFilesList = document.getElementById('edit_template_instruction_files_list');
    const editTemplateRemovedInstructionFiles = document.getElementById('edit_template_removed_instruction_files');
    const editTemplateSchema = document.getElementById('edit_template_schema');
    const publishTemplateBtn = document.getElementById('publish_template_btn');
    const deleteTemplateForm = document.getElementById('deleteTemplateForm');
    const deleteTemplateId = document.getElementById('delete_template_id');
    const deleteTemplateBtn = document.getElementById('delete_template_btn');
    const createTemplateForm = document.getElementById('createTemplateForm');
    const createTemplateSchema = document.getElementById('create_template_schema');
    const createTemplateOpenDate = document.getElementById('create_template_open_date');
    const createTemplateCloseDate = document.getElementById('create_template_close_date');
    const createTemplateInstructionFiles = document.getElementById('create_template_instruction_files');
    const createTemplateInstructionFilesList = document.getElementById('create_template_instruction_files_list');
    const createTemplateFieldsContainer = document.getElementById('create_template_fields_container');
    const addCreateTemplateFieldBtn = document.getElementById('add_create_template_field_btn');
    const closeCreateTemplateBtn = document.getElementById('close_create_template_btn');
    const createTemplateCollapse = document.getElementById('create-template-collapse');
    const closeCreateTemplateConfirmModalEl = document.getElementById('closeCreateTemplateConfirmModal');
    const confirmCloseCreateTemplateBtn = document.getElementById('confirm_close_create_template_btn');
    const closeCreateTemplateConfirmModal = closeCreateTemplateConfirmModalEl ? new bootstrap.Modal(closeCreateTemplateConfirmModalEl) : null;
    const deleteTemplateConfirmModalEl = document.getElementById('deleteTemplateConfirmModal');
    const confirmDeleteTemplateBtn = document.getElementById('confirm_delete_template_btn');
    const deleteTemplateNamePreview = document.getElementById('delete_template_name_preview');
    const deleteTemplateConfirmModal = deleteTemplateConfirmModalEl ? new bootstrap.Modal(deleteTemplateConfirmModalEl) : null;

    // Ta protypo data erxontai apo to PHP rythmiseis asset.
    const templatesData = Array.isArray(window.ADMIN_APPLICATIONS_TEMPLATES_DATA) ? window.ADMIN_APPLICATIONS_TEMPLATES_DATA : [];

    const fieldTypes = {
        'text': 'ÎšÎµÎ¯Î¼ÎµÎ½Î¿',
        'email': 'Email',
        'phone': 'Î¤Î·Î»Î­Ï†Ï‰Î½Î¿',
        'date': 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î±',
        'checkbox': 'Tick Box',
        'textarea': 'ÎœÎµÎ³Î¬Î»Î¿ ÎšÎµÎ¯Î¼ÎµÎ½Î¿',
        'file_upload': 'Î‘ÏÏ‡ÎµÎ¯Î¿'
    };

    let currentFormSchema = [];
    let createFormSchema = [];
    let createTemplateSelectedFiles = [];
    let editTemplateInitialInstructionFiles = [];
    let editTemplateExistingInstructionFiles = [];
    let editTemplateSelectedFiles = [];

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateTemplateCloseDateMin() {
        if (!createTemplateOpenDate || !createTemplateCloseDate) {
            return;
        }

        const openDate = String(createTemplateOpenDate.value || '').trim();
        createTemplateCloseDate.min = openDate;
        if (openDate !== '' && createTemplateCloseDate.value !== '' && createTemplateCloseDate.value < openDate) {
            createTemplateCloseDate.value = '';
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncEditTemplateCloseDateMin() {
        if (!editTemplateOpenDate || !editTemplateCloseDate) {
            return;
        }

        const openDate = String(editTemplateOpenDate.value || '').trim();
        editTemplateCloseDate.min = openDate;
        if (openDate !== '' && editTemplateCloseDate.value !== '' && editTemplateCloseDate.value < openDate) {
            editTemplateCloseDate.value = '';
        }
    }

    if (createTemplateOpenDate && createTemplateCloseDate) {
        createTemplateOpenDate.addEventListener('change', syncCreateTemplateCloseDateMin);
        syncCreateTemplateCloseDateMin();
    }

    if (editTemplateOpenDate && editTemplateCloseDate) {
        editTemplateOpenDate.addEventListener('change', syncEditTemplateCloseDateMin);
        syncEditTemplateCloseDateMin();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function escapeHtml(value) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return String(value == null ? '' : value).replace(/[&<>"']/g, (char) => map[char]);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateTemplateInputFiles(nextFiles) {
        if (!createTemplateInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                createTemplateInstructionFiles.value = '';
            }
            return;
        }

        const transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach((file) => {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        createTemplateInstructionFiles.files = transfer.files;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderCreateTemplateInstructionFilesList() {
        if (!createTemplateInstructionFilesList) {
            return;
        }

        if (!Array.isArray(createTemplateSelectedFiles) || createTemplateSelectedFiles.length === 0) {
            createTemplateInstructionFilesList.innerHTML = '<div class="text-muted small">Î”ÎµÎ½ Î­Ï‡Î¿Ï…Î½ ÎµÏ€Î¹Î»ÎµÎ³ÎµÎ¯ Î±ÏÏ‡ÎµÎ¯Î±.</div>';
            return;
        }

        createTemplateInstructionFilesList.innerHTML = createTemplateSelectedFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'Î‘ÏÏ‡ÎµÎ¯Î¿';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-create-template-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Î‘Ï†Î±Î¯ÏÎµÏƒÎ·' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        createTemplateInstructionFilesList.querySelectorAll('.js-remove-create-template-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= createTemplateSelectedFiles.length) {
                    return;
                }

                createTemplateSelectedFiles.splice(fileIndex, 1);
                syncCreateTemplateInputFiles(createTemplateSelectedFiles);
                renderCreateTemplateInstructionFilesList();
            });
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncEditTemplateInputFiles(nextFiles) {
        if (!editTemplateInstructionFiles) {
            return;
        }

        if (typeof DataTransfer === 'undefined') {
            if (!Array.isArray(nextFiles) || nextFiles.length === 0) {
                editTemplateInstructionFiles.value = '';
            }
            return;
        }

        const transfer = new DataTransfer();
        (Array.isArray(nextFiles) ? nextFiles : []).forEach((file) => {
            if (!file) {
                return;
            }
            transfer.items.add(file);
        });
        editTemplateInstructionFiles.files = transfer.files;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getEditTemplateRemovedInstructionPaths() {
        const activePaths = new Set(
            (Array.isArray(editTemplateExistingInstructionFiles) ? editTemplateExistingInstructionFiles : [])
                .map((file) => String((file && file.path) || '').trim())
                .filter((path) => path !== '')
        );

        return (Array.isArray(editTemplateInitialInstructionFiles) ? editTemplateInitialInstructionFiles : [])
            .map((file) => String((file && file.path) || '').trim())
            .filter((path) => path !== '' && !activePaths.has(path));
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderEditTemplateInstructionFilesList() {
        if (!editTemplateInstructionFilesList) {
            return;
        }

        const existingFiles = Array.isArray(editTemplateExistingInstructionFiles) ? editTemplateExistingInstructionFiles : [];
        const stagedFiles = Array.isArray(editTemplateSelectedFiles) ? editTemplateSelectedFiles : [];

        if (existingFiles.length === 0 && stagedFiles.length === 0) {
            editTemplateInstructionFilesList.innerHTML = '<div class="text-muted small">Î”ÎµÎ½ Ï…Ï€Î¬ÏÏ‡Î¿Ï…Î½ Î±ÏÏ‡ÎµÎ¯Î± Î¿Î´Î·Î³Î¹ÏŽÎ½.</div>';
            if (editTemplateRemovedInstructionFiles) {
                editTemplateRemovedInstructionFiles.value = '[]';
            }
            return;
        }

        const existingHtml = existingFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'Î‘ÏÏ‡ÎµÎ¯Î¿';
            const fileUrl = file && file.url ? String(file.url) : '#';
            return '' +
                '<div class="card border-0 bg-light">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<a href="' + escapeHtml(fileUrl) + '" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">' +
                                '<i class="fas fa-file-alt me-1"></i>' + escapeHtml(fileName) +
                            '</a>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-template-existing-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Î‘Ï†Î±Î¯ÏÎµÏƒÎ·' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        const stagedHtml = stagedFiles.map((file, index) => {
            const fileName = file && file.name ? String(file.name) : 'ÎÎ­Î¿ Î±ÏÏ‡ÎµÎ¯Î¿';
            return '' +
                '<div class="card border-0" style="background:#eef6ff; border:1px dashed #9fc2ea !important;">' +
                    '<div class="card-body py-2 px-3">' +
                        '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">' +
                            '<div class="fw-semibold text-primary">' +
                                '<i class="fas fa-file-upload me-1"></i>' + escapeHtml(fileName) +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-remove-edit-template-staged-file" data-file-index="' + String(index) + '">' +
                                '<i class="fas fa-trash-alt me-1"></i>Î‘Ï†Î±Î¯ÏÎµÏƒÎ·' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        const slotsLeft = Math.max(0, 4 - existingFiles.length);
        const slotsInfo = '<div class="text-muted small">Î”Î¹Î±Î¸Î­ÏƒÎ¹Î¼ÎµÏ‚ Î¸Î­ÏƒÎµÎ¹Ï‚ Î³Î¹Î± Î½Î­Î± Î±ÏÏ‡ÎµÎ¯Î±: ' + String(slotsLeft) + ' / 4</div>';
        editTemplateInstructionFilesList.innerHTML = existingHtml + stagedHtml + slotsInfo;

        editTemplateInstructionFilesList.querySelectorAll('.js-remove-edit-template-existing-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= editTemplateExistingInstructionFiles.length) {
                    return;
                }

                editTemplateExistingInstructionFiles.splice(fileIndex, 1);
                const maxNewFiles = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
                if (editTemplateSelectedFiles.length > maxNewFiles) {
                    editTemplateSelectedFiles = editTemplateSelectedFiles.slice(0, maxNewFiles);
                    syncEditTemplateInputFiles(editTemplateSelectedFiles);
                }
                renderEditTemplateInstructionFilesList();
            });
        });

        editTemplateInstructionFilesList.querySelectorAll('.js-remove-edit-template-staged-file').forEach((button) => {
            button.addEventListener('click', () => {
                const fileIndex = parseInt(button.getAttribute('data-file-index') || '-1', 10);
                if (Number.isNaN(fileIndex) || fileIndex < 0 || fileIndex >= editTemplateSelectedFiles.length) {
                    return;
                }

                editTemplateSelectedFiles.splice(fileIndex, 1);
                syncEditTemplateInputFiles(editTemplateSelectedFiles);
                renderEditTemplateInstructionFilesList();
            });
        });

        if (editTemplateRemovedInstructionFiles) {
            editTemplateRemovedInstructionFiles.value = JSON.stringify(getEditTemplateRemovedInstructionPaths());
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCurrentFormSchemaFromInputs() {
        if (!templateFieldsContainer) {
            return;
        }

        const nextSchema = [];
        templateFieldsContainer.querySelectorAll('.card').forEach((card) => {
            const nameInput = card.querySelector('.field-name');
            const typeSelect = card.querySelector('.field-type');
            const requiredCheck = card.querySelector('.field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: typeSelect ? typeSelect.value : 'text',
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        currentFormSchema = nextSchema;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateFormSchemaFromInputs() {
        if (!createTemplateFieldsContainer) {
            return;
        }

        const nextSchema = [];
        createTemplateFieldsContainer.querySelectorAll('.card').forEach((card) => {
            const nameInput = card.querySelector('.create-field-name');
            const typeSelect = card.querySelector('.create-field-type');
            const requiredCheck = card.querySelector('.create-field-required');

            nextSchema.push({
                name: nameInput ? nameInput.value : '',
                type: typeSelect ? typeSelect.value : 'text',
                required: requiredCheck ? requiredCheck.checked : true
            });
        });

        createFormSchema = nextSchema;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderFormFields() {
        templateFieldsContainer.innerHTML = '';
        currentFormSchema.forEach((field, index) => {
            const fieldEl = document.createElement('div');
            fieldEl.className = 'card border-0 bg-white mb-2';
            
            fieldEl.innerHTML = `
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <input type="text" class="form-control form-control-sm field-name" value="${field.name || ''}" placeholder="Ï€.Ï‡. ÏŒÎ½Î¿Î¼Î± Ï€ÎµÎ´Î¯Î¿Ï…">
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm field-type">
                                ${Object.entries(fieldTypes).map(([val, label]) => 
                                    `<option value="${val}" ${field.type === val ? 'selected' : ''}>${label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input field-required" id="required_${index}" ${field.required ? 'checked' : ''}>
                                <label class="form-check-label" for="required_${index}">Î¥Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÏŒ</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 text-md-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            templateFieldsContainer.appendChild(fieldEl);

            const nameInput = fieldEl.querySelector('.field-name');
            const typeSelect = fieldEl.querySelector('.field-type');
            const requiredCheck = fieldEl.querySelector('.field-required');

            const persistFieldValues = () => {
                currentFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: typeSelect ? typeSelect.value : 'text',
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistFieldValues);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistFieldValues);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistFieldValues);
            }
            
            fieldEl.querySelector('.remove-field').addEventListener('click', () => {
                syncCurrentFormSchemaFromInputs();
                currentFormSchema.splice(index, 1);
                renderFormFields();
            });
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function renderCreateTemplateFields() {
        if (!createTemplateFieldsContainer) {
            return;
        }

        createTemplateFieldsContainer.innerHTML = '';
        createFormSchema.forEach((field, index) => {
            const fieldEl = document.createElement('div');
            fieldEl.className = 'card border-0 bg-white mb-2';
            fieldEl.innerHTML = `
                <div class="card-body py-2 px-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <input type="text" class="form-control form-control-sm create-field-name" value="${field.name || ''}" placeholder="Ï€.Ï‡. ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎœÎ±Î¸Î·Ï„Î®">
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select form-select-sm create-field-type">
                                ${Object.entries(fieldTypes).map(([val, label]) => `<option value="${val}" ${field.type === val ? 'selected' : ''}>${label}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input create-field-required" id="create_required_${index}" ${field.required ? 'checked' : ''}>
                                <label class="form-check-label" for="create_required_${index}">Î¥Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÏŒ</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 text-md-end">
                            <button type="button" class="btn btn-sm btn-outline-danger create-remove-field">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            createTemplateFieldsContainer.appendChild(fieldEl);

            const nameInput = fieldEl.querySelector('.create-field-name');
            const typeSelect = fieldEl.querySelector('.create-field-type');
            const requiredCheck = fieldEl.querySelector('.create-field-required');

            const persistFieldValues = () => {
                createFormSchema[index] = {
                    name: nameInput ? nameInput.value : '',
                    type: typeSelect ? typeSelect.value : 'text',
                    required: requiredCheck ? requiredCheck.checked : true
                };
            };

            if (nameInput) {
                nameInput.addEventListener('input', persistFieldValues);
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', persistFieldValues);
            }
            if (requiredCheck) {
                requiredCheck.addEventListener('change', persistFieldValues);
            }

            fieldEl.querySelector('.create-remove-field').addEventListener('click', () => {
                syncCreateFormSchemaFromInputs();
                createFormSchema.splice(index, 1);
                renderCreateTemplateFields();
            });
        });
    }

    if (templateSelector) {
        templateSelector.addEventListener('change', function() {
            const selectedId = this.value;
            
            if (!selectedId) {
                templateEditor.style.display = 'none';
                templateEmptyState.style.display = 'block';
                if (deleteTemplateId) {
                    deleteTemplateId.value = '';
                }
                if (editTemplateOpenDate) {
                    editTemplateOpenDate.value = '';
                }
                if (editTemplateCloseDate) {
                    editTemplateCloseDate.value = '';
                }
                syncEditTemplateCloseDateMin();
                if (editTemplateInstructionFiles) {
                    editTemplateInstructionFiles.value = '';
                }
                editTemplateInitialInstructionFiles = [];
                editTemplateExistingInstructionFiles = [];
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }
            
            const template = templatesData.find(t => t.id == selectedId);
            if (!template) return;
            
            editTemplateId.value = selectedId;
            if (deleteTemplateId) {
                deleteTemplateId.value = selectedId;
            }
            editTemplateName.value = template.name;
            editTemplateDescription.value = template.description;
            if (editTemplateOpenDate) {
                editTemplateOpenDate.value = String(template.open_date || String(window.ADMIN_APPLICATIONS_TODAY_UI_DATE || ''));
            }
            if (editTemplateCloseDate) {
                editTemplateCloseDate.value = String(template.close_date || '');
            }
            syncEditTemplateCloseDateMin();
            currentFormSchema = template.form_schema || [];
            editTemplateInitialInstructionFiles = Array.isArray(template.instruction_files)
                ? template.instruction_files.map((file) => ({
                    path: String((file && file.path) || ''),
                    name: String((file && file.name) || ''),
                    url: String((file && file.url) || '#')
                }))
                : [];
            editTemplateExistingInstructionFiles = editTemplateInitialInstructionFiles.map((file) => ({ ...file }));
            editTemplateSelectedFiles = [];
            if (editTemplateInstructionFiles) {
                editTemplateInstructionFiles.value = '';
            }
            
            renderFormFields();
            renderEditTemplateInstructionFilesList();
            
            templateEmptyState.style.display = 'none';
            templateEditor.style.display = 'block';
        });

        const templateParams = new URLSearchParams(window.location.search);
        const activeTemplateIdParam = templateParams.get('active_template_id') || '';
        if (activeTemplateIdParam !== '') {
            templateSelector.value = activeTemplateIdParam;
            templateSelector.dispatchEvent(new Event('change'));
        }
    }

    if (addTemplateFieldBtn) {
        addTemplateFieldBtn.addEventListener('click', () => {
            syncCurrentFormSchemaFromInputs();
            currentFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderFormFields();
        });
    }

    if (editTemplateInstructionFiles) {
        editTemplateInstructionFiles.addEventListener('change', function () {
            const selectedFiles = Array.from(editTemplateInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            const remainingSlots = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
            if (selectedFiles.length > remainingSlots) {
                alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î­Ï‰Ï‚ ' + String(remainingSlots) + ' Î±ÎºÏŒÎ¼Î· Î±ÏÏ‡ÎµÎ¯Î± Î¿Î´Î·Î³Î¹ÏŽÎ½.');
                editTemplateInstructionFiles.value = '';
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            const allowedExtensions = ['pdf', 'doc', 'docx'];
            const hasInvalidFile = selectedFiles.some((file) => {
                const fileName = String((file && file.name) || '');
                const extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return !allowedExtensions.includes(extension);
            });

            if (hasInvalidFile) {
                alert('Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½ Î¿Î´Î·Î³Î¹ÏŽÎ½: pdf, doc, docx.');
                editTemplateInstructionFiles.value = '';
                editTemplateSelectedFiles = [];
                renderEditTemplateInstructionFilesList();
                return;
            }

            editTemplateSelectedFiles = selectedFiles;
            renderEditTemplateInstructionFilesList();
        });

        renderEditTemplateInstructionFilesList();
    }

    if (createTemplateInstructionFiles) {
        createTemplateInstructionFiles.addEventListener('change', function () {
            const selectedFiles = Array.from(createTemplateInstructionFiles.files || []);
            if (selectedFiles.length === 0) {
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            if (selectedFiles.length > 4) {
                alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± ÎµÏ€Î¹Î»Î­Î¾ÎµÏ„Îµ Î­Ï‰Ï‚ 4 Î±ÏÏ‡ÎµÎ¯Î± Î¿Î´Î·Î³Î¹ÏŽÎ½.');
                createTemplateInstructionFiles.value = '';
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            const allowedExtensions = ['pdf', 'doc', 'docx'];
            const hasInvalidFile = selectedFiles.some((file) => {
                const fileName = String((file && file.name) || '');
                const extension = fileName.indexOf('.') !== -1 ? fileName.split('.').pop().toLowerCase() : '';
                return !allowedExtensions.includes(extension);
            });

            if (hasInvalidFile) {
                alert('Î•Ï€Î¹Ï„ÏÎµÏ€ÏŒÎ¼ÎµÎ½Î¿Î¹ Ï„ÏÏ€Î¿Î¹ Î±ÏÏ‡ÎµÎ¯Ï‰Î½ Î¿Î´Î·Î³Î¹ÏŽÎ½: pdf, doc, docx.');
                createTemplateInstructionFiles.value = '';
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                return;
            }

            createTemplateSelectedFiles = selectedFiles;
            renderCreateTemplateInstructionFilesList();
        });

        renderCreateTemplateInstructionFilesList();
    }

    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const editOpenDateValue = editTemplateOpenDate ? String(editTemplateOpenDate.value || '').trim() : '';
            const editCloseDateValue = editTemplateCloseDate ? String(editTemplateCloseDate.value || '').trim() : '';
            if (editOpenDateValue === '') {
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚ ÎµÎ¯Î½Î±Î¹ Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÎ®.');
                if (editTemplateOpenDate) {
                    editTemplateOpenDate.focus();
                }
                return;
            }
            if (editCloseDateValue !== '' && editCloseDateValue < editOpenDateValue) {
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± ÎºÎ»ÎµÎ¹ÏƒÎ¯Î¼Î±Ï„Î¿Ï‚ Î´ÎµÎ½ Î¼Ï€Î¿ÏÎµÎ¯ Î½Î± ÎµÎ¯Î½Î±Î¹ Ï€ÏÎ¹Î½ Î±Ï€ÏŒ Ï„Î·Î½ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚.');
                if (editTemplateCloseDate) {
                    editTemplateCloseDate.focus();
                }
                return;
            }

            syncCurrentFormSchemaFromInputs();
            const fields = currentFormSchema
                .map((field) => ({
                    name: String(field.name || '').trim(),
                    type: field.type || 'text',
                    required: Boolean(field.required)
                }))
                .filter((field) => field.name !== '');

            if (editTemplateInstructionFiles && editTemplateSelectedFiles.length !== Array.from(editTemplateInstructionFiles.files || []).length) {
                syncEditTemplateInputFiles(editTemplateSelectedFiles);
            }

            const remainingSlots = Math.max(0, 4 - editTemplateExistingInstructionFiles.length);
            if (editTemplateSelectedFiles.length > remainingSlots) {
                alert('ÎœÏ€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿ÏƒÎ¸Î­ÏƒÎµÏ„Îµ Î­Ï‰Ï‚ ' + String(remainingSlots) + ' Î±ÎºÏŒÎ¼Î· Î±ÏÏ‡ÎµÎ¯Î± Î¿Î´Î·Î³Î¹ÏŽÎ½.');
                return;
            }

            if (editTemplateRemovedInstructionFiles) {
                editTemplateRemovedInstructionFiles.value = JSON.stringify(getEditTemplateRemovedInstructionPaths());
            }

            editTemplateSchema.value = JSON.stringify(fields);
            this.submit();
        });
    }

    if (publishTemplateBtn) {
        publishTemplateBtn.addEventListener('click', function() {
            if (editTemplateForm) {
                let publishInput = editTemplateForm.querySelector('input[name="publish"]');
                if (!publishInput) {
                    publishInput = document.createElement('input');
                    publishInput.type = 'hidden';
                    publishInput.name = 'publish';
                    editTemplateForm.appendChild(publishInput);
                }
                publishInput.value = '1';

                if (typeof editTemplateForm.requestSubmit === 'function') {
                    editTemplateForm.requestSubmit();
                } else {
                    const submitBtn = editTemplateForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        editTemplateForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            }
        });
    }

    if (deleteTemplateBtn) {
        deleteTemplateBtn.addEventListener('click', function () {
            if (!deleteTemplateId || !deleteTemplateId.value) {
                return;
            }

            if (deleteTemplateNamePreview) {
                deleteTemplateNamePreview.textContent = editTemplateName && editTemplateName.value
                    ? editTemplateName.value
                    : 'Î•Ï€Î¹Î»ÎµÎ³Î¼Î­Î½Î¿ Ï€ÏÏŒÏ„Ï…Ï€Î¿';
            }

            if (deleteTemplateConfirmModal) {
                deleteTemplateConfirmModal.show();
            }
        });
    }

    if (confirmDeleteTemplateBtn) {
        confirmDeleteTemplateBtn.addEventListener('click', function () {
            if (deleteTemplateConfirmModal) {
                deleteTemplateConfirmModal.hide();
            }

            if (deleteTemplateForm) {
                deleteTemplateForm.submit();
            }
        });
    }

    if (addCreateTemplateFieldBtn) {
        addCreateTemplateFieldBtn.addEventListener('click', function () {
            syncCreateFormSchemaFromInputs();
            createFormSchema.push({
                name: '',
                type: 'text',
                required: true
            });
            renderCreateTemplateFields();
        });
    }

    if (createTemplateForm) {
        createTemplateForm.addEventListener('submit', function (event) {
            const createOpenDateValue = createTemplateOpenDate ? String(createTemplateOpenDate.value || '').trim() : '';
            const createCloseDateValue = createTemplateCloseDate ? String(createTemplateCloseDate.value || '').trim() : '';
            if (createOpenDateValue === '') {
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚ ÎµÎ¯Î½Î±Î¹ Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÎ®.');
                if (createTemplateOpenDate) {
                    createTemplateOpenDate.focus();
                }
                event.preventDefault();
                return;
            }
            if (createCloseDateValue !== '' && createCloseDateValue < createOpenDateValue) {
                alert('Î— Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± ÎºÎ»ÎµÎ¹ÏƒÎ¯Î¼Î±Ï„Î¿Ï‚ Î´ÎµÎ½ Î¼Ï€Î¿ÏÎµÎ¯ Î½Î± ÎµÎ¯Î½Î±Î¹ Ï€ÏÎ¹Î½ Î±Ï€ÏŒ Ï„Î·Î½ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±Î½Î¿Î¯Î³Î¼Î±Ï„Î¿Ï‚.');
                if (createTemplateCloseDate) {
                    createTemplateCloseDate.focus();
                }
                event.preventDefault();
                return;
            }

            syncCreateFormSchemaFromInputs();

            if (createTemplateInstructionFiles && createTemplateSelectedFiles.length !== Array.from(createTemplateInstructionFiles.files || []).length) {
                syncCreateTemplateInputFiles(createTemplateSelectedFiles);
            }

            const fields = createFormSchema
                .map((field) => ({
                    name: String(field.name || '').trim(),
                    type: field.type || 'text',
                    required: Boolean(field.required)
                }))
                .filter((field) => field.name !== '');

            createTemplateSchema.value = JSON.stringify(fields);
        });

        createTemplateForm.addEventListener('reset', function () {
            createFormSchema = [];
            if (createTemplateOpenDate) {
                createTemplateOpenDate.value = String(window.ADMIN_APPLICATIONS_TODAY_UI_DATE || '');
            }
            if (createTemplateCloseDate) {
                createTemplateCloseDate.value = '';
            }
            syncCreateTemplateCloseDateMin();
            if (createTemplateInstructionFiles) {
                createTemplateInstructionFiles.value = '';
            }
            createTemplateSelectedFiles = [];
            renderCreateTemplateInstructionFilesList();
            window.setTimeout(renderCreateTemplateFields, 0);
        });
    }

    if (createTemplateFieldsContainer) {
        createFormSchema = [
            { name: '', type: 'text', required: true }
        ];
        renderCreateTemplateFields();
    }

    if (closeCreateTemplateBtn) {
        closeCreateTemplateBtn.addEventListener('click', function () {
            if (closeCreateTemplateConfirmModal) {
                closeCreateTemplateConfirmModal.show();
            }
        });
    }

    if (confirmCloseCreateTemplateBtn) {
        confirmCloseCreateTemplateBtn.addEventListener('click', function () {
            if (createTemplateCollapse) {
                bootstrap.Collapse.getOrCreateInstance(createTemplateCollapse).hide();
            }

            if (createTemplateForm) {
                createTemplateForm.reset();
                if (createTemplateOpenDate) {
                    createTemplateOpenDate.value = String(window.ADMIN_APPLICATIONS_TODAY_UI_DATE || '');
                }
                if (createTemplateCloseDate) {
                    createTemplateCloseDate.value = '';
                }
                syncCreateTemplateCloseDateMin();
                if (createTemplateInstructionFiles) {
                    createTemplateInstructionFiles.value = '';
                }
                createTemplateSelectedFiles = [];
                renderCreateTemplateInstructionFilesList();
                createFormSchema = [{ name: '', type: 'text', required: true }];
                renderCreateTemplateFields();
            }

            if (closeCreateTemplateConfirmModal) {
                closeCreateTemplateConfirmModal.hide();
            }
        });
    }
});
