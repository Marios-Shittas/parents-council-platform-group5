/**
 * Form Renderer Component
 * 
 * Takes a JSON schema from ApplicationTemplate form_schema field
 * and renders it as an interactive HTML5 form
 * 
 * Usage:
 *   const renderer = new FormRenderer('form-container-id', formSchemaJson);
 *   renderer.render();
 *   const formData = renderer.getFormData();
 *   const isValid = renderer.validate();
 */

class FormRenderer {
    constructor(containerId, formSchema) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container with ID "${containerId}" not found`);
        }
        this.schema = formSchema;
        this.fields = {};
        this.signaturePad = null;
    }

    /**
     * Render the form from schema
     */
    render() {
        this.container.innerHTML = '';
        
        if (!this.schema || !this.schema.sections) {
            console.warn('Invalid schema structure');
            return;
        }

        const form = document.createElement('form');
        form.className = 'form-renderer needs-validation';
        form.noValidate = true;

        this.schema.sections.forEach((section, sectionIndex) => {
            const sectionDiv = this._renderSection(section, sectionIndex);
            form.appendChild(sectionDiv);
        });

        this.container.appendChild(form);

        // Add form submission handler
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    /**
     * Render a section with fields
     */
    _renderSection(section, sectionIndex) {
        const sectionDiv = document.createElement('div');
        sectionDiv.className = 'form-section mb-4 p-4 border rounded-1 bg-light';

        if (section.title) {
            const titleEl = document.createElement('h5');
            titleEl.className = 'mb-3 fw-bold text-primary';
            titleEl.textContent = section.title;
            sectionDiv.appendChild(titleEl);
        }

        if (section.description) {
            const descEl = document.createElement('p');
            descEl.className = 'text-muted small mb-3';
            descEl.textContent = section.description;
            sectionDiv.appendChild(descEl);
        }

        if (section.fields && Array.isArray(section.fields)) {
            section.fields.forEach((field) => {
                if (field.type === 'section-header') {
                    const headerEl = this._renderSectionHeader(field);
                    sectionDiv.appendChild(headerEl);
                } else {
                    const fieldEl = this._renderField(field);
                    sectionDiv.appendChild(fieldEl);
                }
            });
        }

        return sectionDiv;
    }

    /**
     * Render a section header (divider/subheading)
     */
    _renderSectionHeader(field) {
        const headerDiv = document.createElement('div');
        headerDiv.className = 'my-3';

        const headerEl = document.createElement('h6');
        headerEl.className = 'border-bottom pb-2 text-secondary';
        headerEl.textContent = field.label || field.name;

        if (field.description) {
            const descEl = document.createElement('small');
            descEl.className = 'd-block text-muted mt-1';
            descEl.textContent = field.description;
            headerDiv.appendChild(descEl);
        }

        headerDiv.appendChild(headerEl);
        return headerDiv;
    }

    /**
     * Render individual field based on type
     */
    _renderField(field) {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'mb-3';

        const { name, label, type, required, placeholder, options } = field;

        // Store reference to field for later access
        this.fields[name] = { element: fieldDiv, type, required, field };

        // Create label
        if (label) {
            const labelEl = document.createElement('label');
            labelEl.className = 'form-label';
            labelEl.htmlFor = name;
            labelEl.innerHTML = label + (required ? ' <span class="text-danger">*</span>' : '');
            fieldDiv.appendChild(labelEl);
        }

        // Create field based on type
        let inputEl;
        switch (type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                inputEl = this._createTextInput(name, type, required, placeholder);
                break;
            case 'date':
                inputEl = this._createDateInput(name, required);
                break;
            case 'textarea':
                inputEl = this._createTextarea(name, required, placeholder);
                break;
            case 'select':
                inputEl = this._createSelect(name, options, required);
                break;
            case 'radio':
                inputEl = this._createRadioGroup(name, options, required);
                fieldDiv.appendChild(inputEl);
                return fieldDiv;
            case 'checkbox':
                inputEl = this._createCheckboxGroup(name, options);
                fieldDiv.appendChild(inputEl);
                return fieldDiv;
            case 'signature':
                inputEl = this._createSignatureField(name, required);
                fieldDiv.appendChild(inputEl);
                return fieldDiv;
            case 'file_upload':
                inputEl = this._createFileUpload(name, required);
                break;
            default:
                console.warn(`Unknown field type: ${type}`);
                return fieldDiv;
        }

        if (inputEl) {
            fieldDiv.appendChild(inputEl);
        }

        return fieldDiv;
    }

    /**
     * Create text/email/tel/number input
     */
    _createTextInput(name, type = 'text', required = false, placeholder = '') {
        const input = document.createElement('input');
        input.type = type;
        input.className = 'form-control';
        input.id = name;
        input.name = name;
        input.required = required;
        if (placeholder) input.placeholder = placeholder;
        
        // Add HTML5 validation
        if (type === 'email') {
            input.pattern = '[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$';
        } else if (type === 'tel') {
            input.pattern = '[0-9-+()\\s]*';
        }

        return input;
    }

    /**
     * Create date input
     */
    _createDateInput(name, required = false) {
        const input = document.createElement('input');
        input.type = 'date';
        input.className = 'form-control';
        input.id = name;
        input.name = name;
        input.required = required;
        return input;
    }

    /**
     * Create textarea
     */
    _createTextarea(name, required = false, placeholder = '') {
        const textarea = document.createElement('textarea');
        textarea.className = 'form-control';
        textarea.id = name;
        textarea.name = name;
        textarea.required = required;
        textarea.rows = 4;
        if (placeholder) textarea.placeholder = placeholder;
        return textarea;
    }

    /**
     * Create select dropdown
     */
    _createSelect(name, options = [], required = false) {
        const select = document.createElement('select');
        select.className = 'form-select';
        select.id = name;
        select.name = name;
        select.required = required;

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = '-- Επιλέξτε --';
        select.appendChild(emptyOption);

        if (options && Array.isArray(options)) {
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = typeof opt === 'string' ? opt : opt.value;
                option.textContent = typeof opt === 'string' ? opt : opt.label;
                select.appendChild(option);
            });
        }

        return select;
    }

    /**
     * Create radio button group
     */
    _createRadioGroup(name, options = [], required = false) {
        const groupDiv = document.createElement('div');

        if (options && Array.isArray(options)) {
            options.forEach((opt, index) => {
                const radioDiv = document.createElement('div');
                radioDiv.className = 'form-check';

                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.className = 'form-check-input';
                radio.id = `${name}_${index}`;
                radio.name = name;
                radio.value = typeof opt === 'string' ? opt : opt.value;
                radio.required = required;

                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = `${name}_${index}`;
                label.textContent = typeof opt === 'string' ? opt : opt.label;

                radioDiv.appendChild(radio);
                radioDiv.appendChild(label);
                groupDiv.appendChild(radioDiv);
            });
        }

        return groupDiv;
    }

    /**
     * Create checkbox group
     */
    _createCheckboxGroup(name, options = []) {
        const groupDiv = document.createElement('div');

        if (options && Array.isArray(options)) {
            options.forEach((opt, index) => {
                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input';
                checkbox.id = `${name}_${index}`;
                checkbox.name = `${name}[]`;
                checkbox.value = typeof opt === 'string' ? opt : opt.value;

                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = `${name}_${index}`;
                label.textContent = typeof opt === 'string' ? opt : opt.label;

                checkDiv.appendChild(checkbox);
                checkDiv.appendChild(label);
                groupDiv.appendChild(checkDiv);
            });
        }

        return groupDiv;
    }

    /**
     * Create file upload field
     */
    _createFileUpload(name, required = false) {
        const input = document.createElement('input');
        input.type = 'file';
        input.className = 'form-control';
        input.id = name;
        input.name = name;
        input.required = required;
        input.accept = '.pdf,.doc,.docx,.jpg,.jpeg,.png';
        return input;
    }

    /**
     * Create signature field with pad + fallback upload
     */
    _createSignatureField(name, required = false) {
        const wrapperDiv = document.createElement('div');

        // Signature pad canvas
        const padDiv = document.createElement('div');
        padDiv.className = 'card mb-3';
        padDiv.innerHTML = `
            <div class="card-body p-2">
                <canvas id="${name}-canvas" class="border" 
                    style="width: 100%; height: 200px; background: white; cursor: crosshair;"></canvas>
                <div class="mt-2 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="${name}-clear">
                        <i class="fas fa-eraser"></i> Καθαρισμός
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="collapse" 
                        data-bs-target="#${name}-upload-section">
                        <i class="fas fa-upload"></i> Ή ανέβασμα αρχείου
                    </button>
                </div>
            </div>
        `;
        wrapperDiv.appendChild(padDiv);

        // Hidden input to store signature data
        const signatureInput = document.createElement('input');
        signatureInput.type = 'hidden';
        signatureInput.id = `${name}-data`;
        signatureInput.name = `${name}_data`;
        wrapperDiv.appendChild(signatureInput);

        // File upload fallback
        const uploadDiv = document.createElement('div');
        uploadDiv.className = 'collapse';
        uploadDiv.id = `${name}-upload-section`;
        uploadDiv.innerHTML = `
            <div class="card card-body">
                <label class="form-label">Ανέβασμα υπογεγραμμένου αρχείου</label>
                <input type="file" class="form-control" id="${name}-file" 
                    name="${name}_file" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
        `;
        wrapperDiv.appendChild(uploadDiv);

        // Initialize signature pad after rendering
        setTimeout(() => this._initSignaturePad(name), 0);

        this.fields[name] = { element: wrapperDiv, type: 'signature', required };

        return wrapperDiv;
    }

    /**
     * Initialize signature pad on canvas
     */
    _initSignaturePad(name) {
        const canvas = document.getElementById(`${name}-canvas`);
        if (!canvas) return;

        // Set canvas resolution
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * window.devicePixelRatio;
        canvas.height = rect.height * window.devicePixelRatio;

        const ctx = canvas.getContext('2d');
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        const startDrawing = (e) => {
            isDrawing = true;
            [lastX, lastY] = this._getMousePos(canvas, e);
        };

        const draw = (e) => {
            if (!isDrawing) return;

            const [currentX, currentY] = this._getMousePos(canvas, e);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#000';

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(currentX, currentY);
            ctx.stroke();

            [lastX, lastY] = [currentX, currentY];
        };

        const stopDrawing = () => {
            isDrawing = false;
            document.getElementById(`${name}-data`).value = canvas.toDataURL('image/png');
        };

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Touch support
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            startDrawing(e.touches[0]);
        });
        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            draw(e.touches[0]);
        });
        canvas.addEventListener('touchend', stopDrawing);

        // Clear button
        const clearBtn = document.getElementById(`${name}-clear`);
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById(`${name}-data`).value = '';
            });
        }
    }

    /**
     * Get mouse position relative to canvas
     */
    _getMousePos(canvas, event) {
        const rect = canvas.getBoundingClientRect();
        return [
            event.clientX - rect.left,
            event.clientY - rect.top
        ];
    }

    /**
     * Get all form data as object
     */
    getFormData() {
        const formData = new FormData(this.container.querySelector('form'));
        const data = {};

        for (let [key, value] of formData.entries()) {
            if (key.endsWith('[]')) {
                const arrayKey = key.slice(0, -2);
                if (!data[arrayKey]) data[arrayKey] = [];
                data[arrayKey].push(value);
            } else {
                data[key] = value;
            }
        }

        return data;
    }

    /**
     * Validate form before submission
     */
    validate() {
        const form = this.container.querySelector('form');
        if (!form) return false;
        
        const isValid = form.checkValidity();
        return isValid === false ? false : true;
    }

    /**
     * Populate form with existing data
     */
    setFormData(data) {
        if (!data || typeof data !== 'object') return;

        Object.entries(data).forEach(([key, value]) => {
            const element = document.getElementById(key);
            if (!element) return;

            if (element.type === 'checkbox') {
                if (Array.isArray(value)) {
                    value.forEach(v => {
                        const checkbox = document.querySelector(`input[name="${key}[]"][value="${v}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
            } else if (element.type === 'radio') {
                const radio = document.querySelector(`input[name="${key}"][value="${value}"]`);
                if (radio) radio.checked = true;
            } else {
                element.value = value;
            }
        });
    }

    /**
     * Clear all form fields
     */
    clear() {
        const form = this.container.querySelector('form');
        if (form) form.reset();
    }

    /**
     * Disable all form fields
     */
    disable() {
        const form = this.container.querySelector('form');
        if (form) {
            form.querySelectorAll('input, select, textarea, button').forEach(el => {
                if (el.type !== 'button' && el.type !== 'submit') {
                    el.disabled = true;
                }
            });
        }
    }

    /**
     * Enable all form fields
     */
    enable() {
        const form = this.container.querySelector('form');
        if (form) {
            form.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = false;
            });
        }
    }
}
