document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-product-btn');
            const deleteProductIdInput = document.getElementById('deleteProductId');
            const deleteProductName = document.getElementById('deleteProductName');

            // Leitourgia splitCustomSizes: krataei tin antistoixi symperifora tou UI.
            function splitCustomSizes(value) {
                return String(value || '')
                    .split(/[\r\n,;]+/)
                    .map(function(item) {
                        return item.replace(/\s+/g, ' ').trim();
                    })
                    .filter(Boolean);
            }

            // Leitourgia bindCustomSizeBuilder: krataei tin antistoixi symperifora tou UI.
            function bindCustomSizeBuilder(scopeEl) {
                const builder = scopeEl.querySelector('.js-custom-size-builder');

                if (!builder) {
                    return null;
                }

                const input = builder.querySelector('.js-custom-size-input');
                const addButton = builder.querySelector('.js-add-custom-size');
                const chips = builder.querySelector('.js-custom-size-chips');
                const hiddenField = builder.querySelector('.js-custom-size-options');
                let values = splitCustomSizes(hiddenField ? hiddenField.value : '');

                // Leitourgia syncHiddenField: krataei tin antistoixi symperifora tou UI.
                function syncHiddenField() {
                    if (hiddenField) {
                        hiddenField.value = values.join('\n');
                    }
                }

                // Leitourgia render: krataei tin antistoixi symperifora tou UI.
                function render() {
                    if (!chips) {
                        syncHiddenField();
                        return;
                    }

                    chips.innerHTML = '';

                    if (values.length === 0) {
                        const empty = document.createElement('span');
                        empty.className = 'custom-size-empty';
                        empty.textContent = 'Δεν έχουν προστεθεί άλλα μεγέθη.';
                        chips.appendChild(empty);
                        syncHiddenField();
                        return;
                    }

                    values.forEach(function(value, index) {
                        const chip = document.createElement('span');
                        chip.className = 'custom-size-chip';

                        const text = document.createElement('span');
                        text.textContent = value;

                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'custom-size-chip-remove';
                        removeButton.setAttribute('aria-label', 'Αφαίρεση ' + value);
                        removeButton.innerHTML = '<i class="fas fa-times"></i>';
                        removeButton.addEventListener('click', function() {
                            values.splice(index, 1);
                            render();
                        });

                        chip.appendChild(text);
                        chip.appendChild(removeButton);
                        chips.appendChild(chip);
                    });

                    syncHiddenField();
                }

                // Leitourgia addValue: krataei tin antistoixi symperifora tou UI.
                function addValue(shouldFocus) {
                    if (!input) {
                        return;
                    }

                    const nextValues = splitCustomSizes(input.value);

                    nextValues.forEach(function(nextValue) {
                        const exists = values.some(function(value) {
                            return value.toLowerCase() === nextValue.toLowerCase();
                        });

                        if (!exists) {
                            values.push(nextValue);
                        }
                    });

                    input.value = '';
                    render();

                    if (shouldFocus !== false) {
                        input.focus();
                    }
                }

                if (addButton) {
                    addButton.addEventListener('click', function() {
                        addValue(true);
                    });
                }

                if (input) {
                    input.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            addValue(true);
                        }
                    });
                }

                render();

                return {
                    clear: function() {
                        values = [];
                        if (input) {
                            input.value = '';
                        }
                        render();
                    },
                    hasValues: function() {
                        return values.length > 0;
                    },
                    commit: function() {
                        addValue(false);
                    },
                    sync: syncHiddenField
                };
            }

            // Leitourgia bindSizeVisibility: krataei tin antistoixi symperifora tou UI.
            function bindSizeVisibility(scopeEl) {
                if (!scopeEl) {
                    return;
                }

                const toggle = scopeEl.querySelector('.js-has-sizes-toggle');
                const optionsGroup = scopeEl.querySelector('.js-size-options-group');
                const options = scopeEl.querySelectorAll('.js-size-option');
                const customOptions = scopeEl.querySelector('.js-custom-size-options');
                const customBuilder = bindCustomSizeBuilder(scopeEl);
                const shouldPreselectDefaults = scopeEl.closest('#createProductModal') !== null;

                if (!toggle || !optionsGroup) {
                    return;
                }

                const sync = function(clearValues) {
                    const enabled = toggle.checked;
                    optionsGroup.style.display = enabled ? 'block' : 'none';

                    if (enabled && shouldPreselectDefaults) {
                        const hasCheckedOption = Array.prototype.some.call(options, function(option) {
                            return option.checked;
                        });
                        const hasCustomValues = customBuilder ? customBuilder.hasValues() : splitCustomSizes(customOptions ? customOptions.value : '').length > 0;

                        if (!hasCheckedOption && !hasCustomValues) {
                            options.forEach(function(option) {
                                option.checked = true;
                            });
                        }
                    }

                    if (!enabled && clearValues) {
                        options.forEach(function(option) {
                            option.checked = false;
                        });

                        if (customOptions) {
                            customOptions.value = '';
                        }

                        if (customBuilder) {
                            customBuilder.clear();
                        }
                    }
                };

                toggle.addEventListener('change', function() {
                    sync(true);
                });

                scopeEl.addEventListener('submit', function(event) {
                    if (!toggle.checked) {
                        return;
                    }

                    if (customBuilder) {
                        customBuilder.commit();
                    }

                    const hasClassicSize = Array.prototype.some.call(options, function(option) {
                        return option.checked;
                    });
                    const hasCustomSize = customBuilder ? customBuilder.hasValues() : splitCustomSizes(customOptions ? customOptions.value : '').length > 0;

                    if (!hasClassicSize && !hasCustomSize) {
                        event.preventDefault();
                        alert('Επίλεξε τουλάχιστον ένα μέγεθος ή άφησε ανενεργό το πεδίο "Το προϊόν έχει διαθέσιμα μεγέθη".');
                    } else if (customBuilder) {
                        customBuilder.sync();
                    }
                });

                sync(false);
            }

            bindSizeVisibility(document.querySelector('#createProductModal form'));
            bindSizeVisibility(document.querySelector('#editProductModal form'));

            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');

                    deleteProductIdInput.value = productId;
                    deleteProductName.textContent = productName;

                    $('#deleteConfirmModal').modal('show');
                });
            });

            if (window.ADMIN_ESHOP_OPEN_EDIT_MODAL) {
                $('#editProductModal').modal('show');
            }
        });
