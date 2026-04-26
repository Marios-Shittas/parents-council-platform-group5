// Arxeio: public\assets\js\admin_applications_pedia.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
/**
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 */

(function() {
    'use strict';

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    const FIELD_TYPES = {
        'text': 'Κείμενο',
        'email': 'Email',
        'phone': 'Τηλέφωνο',
        'date': 'Ημερομηνία',
        'checkbox': 'Tick Box',
        'textarea': 'Μεγάλο Κείμενο',
        'file_upload': 'Αρχείο'
    };

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    let formFields = [];
    let fieldIdCounter = 0;

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    const onlineFormFieldsContainer = document.getElementById('online_form_fields_container');
    const addFormFieldBtn = document.getElementById('add_form_field_btn');
    const publishApplicationBtn = document.getElementById('publish_application_btn');
    const createApplicationTitle = document.getElementById('create_application_title');
    const createApplicationDescription = document.getElementById('create_application_description');
    const createApplicationOpenDate = document.getElementById('create_application_open_date');
    const createApplicationCloseDate = document.getElementById('create_application_close_date');
    const createInstructionFiles = document.getElementById('create_instruction_files');

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncCreateCloseDateMin() {
        if (!createApplicationOpenDate || !createApplicationCloseDate) {
            return;
        }

        const openDate = (createApplicationOpenDate.value || '').trim();
        createApplicationCloseDate.min = openDate;
        if (openDate !== '' && createApplicationCloseDate.value && createApplicationCloseDate.value < openDate) {
            createApplicationCloseDate.value = '';
        }
    }

    if (createApplicationOpenDate && createApplicationCloseDate) {
        createApplicationOpenDate.addEventListener('change', syncCreateCloseDateMin);
        syncCreateCloseDateMin();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    if (addFormFieldBtn) {
        addFormFieldBtn.addEventListener('click', function() {
            addFormField();
        });
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function addFormField(fieldName = '', fieldType = 'text', isRequired = true) {
        const fieldId = fieldIdCounter++;
        formFields.push({
            id: fieldId,
            name: fieldName,
            type: fieldType,
            required: isRequired
        });

        renderFormFields();
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function removeFormField(fieldId) {
        formFields = formFields.filter(f => f.id !== fieldId);
        renderFormFields();
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function updateFormField(fieldId, fieldName, fieldType, isRequired) {
        const field = formFields.find(f => f.id === fieldId);
        if (field) {
            field.name = fieldName;
            field.type = fieldType;
            field.required = isRequired;
        }
        renderFormFields();
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function renderFormFields() {
        if (!onlineFormFieldsContainer) return;

        if (formFields.length === 0) {
            onlineFormFieldsContainer.innerHTML = '';
            return;
        }

        let html = '';
        formFields.forEach((field, index) => {
            html += `
                <div class="card border-0 bg-light mb-2" data-field-id="${field.id}">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Όνομα Πεδίου</label>
                                <input type="text" class="form-control form-control-sm field-name" value="${escapeHtml(field.name)}" placeholder="π.χ. Όνομα">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small mb-1">Τύπος</label>
                                <select class="form-select form-select-sm field-type">
                                    ${Object.entries(FIELD_TYPES).map(([val, label]) => 
                                        `<option value="${val}" ${field.type === val ? 'selected' : ''}>${label}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <div class="form-check mt-3">
                                    <input class="form-check-input field-required" type="checkbox" id="field_required_${field.id}" ${field.required ? 'checked' : ''}>
                                    <label class="form-check-label" for="field_required_${field.id}">
                                        <small>Υποχρεωτικό</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-3 text-md-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn" data-field-id="${field.id}">
                                    <i class="fas fa-trash-alt me-1"></i>Διαγραφή
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        onlineFormFieldsContainer.innerHTML = html;

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        document.querySelectorAll('[data-field-id]').forEach(fieldElement => {
            const fieldId = parseInt(fieldElement.dataset.fieldId);
            
            const nameInput = fieldElement.querySelector('.field-name');
            const typeSelect = fieldElement.querySelector('.field-type');
            const requiredCheckbox = fieldElement.querySelector('.field-required');
            const removeBtn = fieldElement.querySelector('.remove-field-btn');

            nameInput?.addEventListener('change', () => {
                updateFormField(fieldId, nameInput.value, typeSelect.value, requiredCheckbox.checked);
            });

            typeSelect?.addEventListener('change', () => {
                updateFormField(fieldId, nameInput.value, typeSelect.value, requiredCheckbox.checked);
            });

            requiredCheckbox?.addEventListener('change', () => {
                updateFormField(fieldId, nameInput.value, typeSelect.value, requiredCheckbox.checked);
            });

            removeBtn?.addEventListener('click', () => {
                removeFormField(fieldId);
            });
        });
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    if (publishApplicationBtn) {
        publishApplicationBtn.addEventListener('click', function() {
            publishApplication();
        });
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function publishApplication() {
        const title = (createApplicationTitle?.value || '').trim();
        const description = (createApplicationDescription?.value || '').trim();
        const openDate = (createApplicationOpenDate?.value || '').trim();
        const closeDate = (createApplicationCloseDate?.value || '').trim();

        if (!title) {
            alert('Ο τίτλος της αίτησης είναι υποχρεωτικός.');
            createApplicationTitle?.focus();
            return;
        }

        if (!openDate) {
            alert('Η ημερομηνία ανοίγματος είναι υποχρεωτική.');
            createApplicationOpenDate?.focus();
            return;
        }

        if (closeDate && closeDate < openDate) {
            alert('Η ημερομηνία κλεισίματος δεν μπορεί να είναι πριν από την ημερομηνία ανοίγματος.');
            createApplicationCloseDate?.focus();
            return;
        }

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        const form = document.createElement('form');
        form.method = 'POST';
        form.enctype = 'multipart/form-data';

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        form.innerHTML = `
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="application_title" value="${escapeHtml(title)}">
            <input type="hidden" name="application_description" value="${escapeHtml(description)}">
            <input type="hidden" name="application_open_date_ui" value="${escapeHtml(openDate)}">
            <input type="hidden" name="application_close_date_ui" value="${escapeHtml(closeDate)}">
            <input type="hidden" name="form_fields_json" value="${escapeHtml(JSON.stringify(formFields))}">
        `;

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        if (createInstructionFiles && createInstructionFiles.files.length > 0) {
            createInstructionFiles.setAttribute('name', 'instruction_file[]');
            form.appendChild(createInstructionFiles);
        }

        document.body.appendChild(form);
        form.submit();
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, m => map[m]);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    window.ApplicationFormFields = {
        addField: addFormField,
        removeField: removeFormField,
        updateField: updateFormField,
        getFields: () => formFields,
        clearFields: () => { formFields = []; renderFormFields(); }
    };

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    renderFormFields();
})();
