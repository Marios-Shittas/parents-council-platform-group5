// Arxeio: public\assets\js\admin-users.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-auto-submit-on-change]').forEach(function (field) {
        // Kanei submit to goneas forma otan allazei select me dedomena attribute.
        field.addEventListener('change', function () {
            if (field.form) {
                field.form.submit();
            }
        });
    });

    var searchInput = document.getElementById('usersSearchInput');
    var tableRows = document.querySelectorAll('#usersTable tbody tr.user-data-row');
    var urlParams = new URLSearchParams(window.location.search);
    var managedParentId = urlParams.get('manage_children');
    var seenNewUsersStorageKey = 'adminUsersSeenNewRegistrations';
    var registrationScheduleCard = document.getElementById('registrationScheduleCard');
    var registrationScheduleToggle = document.getElementById('registrationScheduleToggle');
    var registrationScheduleContent = document.getElementById('registrationScheduleContent');

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function formatDateTimeLocal(dateObj) {
        var year = String(dateObj.getFullYear());
        var month = String(dateObj.getMonth() + 1).padStart(2, '0');
        var day = String(dateObj.getDate()).padStart(2, '0');
        var hours = String(dateObj.getHours()).padStart(2, '0');
        var minutes = String(dateObj.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function isSingleMomentFeature(featureValue) {
        return featureValue === 'delete_users' || featureValue === 'cleanup_submissions';
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function setRegistrationScheduleExpanded(shouldExpand) {
        if (!registrationScheduleCard || !registrationScheduleToggle || !registrationScheduleContent) {
            return;
        }

        registrationScheduleCard.classList.toggle('registration-schedule-card--collapsed', !shouldExpand);
        registrationScheduleContent.classList.toggle('d-none', !shouldExpand);
        registrationScheduleToggle.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
    }

    if (registrationScheduleToggle) {
        registrationScheduleToggle.addEventListener('click', function () {
            var isExpanded = registrationScheduleToggle.getAttribute('aria-expanded') === 'true';
            setRegistrationScheduleExpanded(!isExpanded);
        });
    }

    setRegistrationScheduleExpanded(false);

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function syncScheduleRowInputs(formId) {
        if (!formId) {
            return;
        }

        var featureSelect = document.querySelector('select[name="schedule_feature"][form="' + formId + '"]');
        var startInput = document.querySelector('input[name="registration_start_date"][form="' + formId + '"]');
        var endInput = document.querySelector('input[name="registration_end_date"][form="' + formId + '"]');

        if (!featureSelect || !startInput || !endInput) {
            return;
        }

        var forceSameDate = isSingleMomentFeature(featureSelect.value);
        endInput.readOnly = forceSameDate;

        if (forceSameDate && startInput.value !== '') {
            var startDate = new Date(startInput.value);
            if (!isNaN(startDate.getTime())) {
                startDate.setMinutes(startDate.getMinutes() + 10);
                endInput.value = formatDateTimeLocal(startDate);
            } else {
                endInput.value = startInput.value;
            }
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function bindScheduleRowAutoSync() {
        var featureSelects = document.querySelectorAll('select[name="schedule_feature"][form]');
        featureSelects.forEach(function (featureSelect) {
            var formId = featureSelect.getAttribute('form') || '';
            if (!formId) {
                return;
            }

            var startInput = document.querySelector('input[name="registration_start_date"][form="' + formId + '"]');
            if (startInput) {
                startInput.addEventListener('change', function () {
                    syncScheduleRowInputs(formId);
                });
            }

            featureSelect.addEventListener('change', function () {
                syncScheduleRowInputs(formId);
            });

            syncScheduleRowInputs(formId);
        });
    }

    bindScheduleRowAutoSync();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function getSeenNewUsers() {
        try {
            var raw = window.localStorage.getItem(seenNewUsersStorageKey);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch (error) {
            return [];
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function saveSeenNewUsers(userIds) {
        try {
            window.localStorage.setItem(seenNewUsersStorageKey, JSON.stringify(userIds));
        } catch (error) {
            // Agnoei sfalmata apothikefsis gia na paramenei xrisimi i selida.
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function updateUsersSidebarNotification() {
        var usersNavLink = document.querySelector('#adminSidebar a[href="users.php"]');
        if (!usersNavLink) {
            return;
        }

        var unseenNewUsersCount = document.querySelectorAll('#usersTable tbody tr.user-data-row.new-user-row[data-is-new-registration="1"]').length;
        var badge = usersNavLink.querySelector('.admin-notification-badge');

        if (unseenNewUsersCount <= 0) {
            if (badge) {
                badge.remove();
            }
            return;
        }

        var badgeText = unseenNewUsersCount > 10 ? '10+' : String(unseenNewUsersCount);
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'admin-notification-badge';
            usersNavLink.appendChild(badge);
        }

        badge.setAttribute('aria-label', 'Νέες εγγραφές χρηστών: ' + badgeText);
        badge.textContent = badgeText;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function dismissNewUserRow(row, persistState) {
        if (!row) {
            return;
        }

        row.classList.remove('new-user-row');

        var badge = row.querySelector('.js-new-user-badge');
        if (badge) {
            badge.remove();
        }

        updateUsersSidebarNotification();

        if (!persistState) {
            return;
        }

        var userId = String(row.getAttribute('data-user-id') || '');
        if (!userId) {
            return;
        }

        var seenUsers = getSeenNewUsers();
        if (seenUsers.indexOf(userId) === -1) {
            seenUsers.push(userId);
            saveSeenNewUsers(seenUsers);
        }
    }

    var seenNewUsers = getSeenNewUsers();
    tableRows.forEach(function (row) {
        if (row.getAttribute('data-is-new-registration') !== '1') {
            return;
        }

        var userId = String(row.getAttribute('data-user-id') || '');
        if (userId && seenNewUsers.indexOf(userId) !== -1) {
            dismissNewUserRow(row, false);
            return;
        }

        row.addEventListener('mouseenter', function handleNewUserHover() {
            dismissNewUserRow(row, true);
        }, { once: true });
    });

    updateUsersSidebarNotification();

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function setPreviewState(button, targetRow, shouldExpand) {
        if (!button || !targetRow) {
            return;
        }

        targetRow.classList.toggle('d-none', !shouldExpand);
        button.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    function collapseUserPreviewRows(userId) {
        document.querySelectorAll('.user-preview-row[data-preview-for="' + userId + '"]').forEach(function (row) {
            row.classList.add('d-none');
            row.style.display = '';
        });

        document.querySelectorAll('[data-target="history-preview-' + userId + '"], [data-target="children-preview-' + userId + '"]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var query = (searchInput.value || '').trim().toLowerCase();

            tableRows.forEach(function (row) {
                var haystack = (row.getAttribute('data-search') || '').toLowerCase();
                var isMatch = haystack.indexOf(query) !== -1;
                var userId = row.getAttribute('data-user-id');
                row.style.display = isMatch ? '' : 'none';

                if (!isMatch && userId) {
                    collapseUserPreviewRows(userId);
                }
            });
        });
    }

    document.querySelectorAll('.js-toggle-history-preview').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            var targetRow = document.getElementById(targetId);
            if (!targetRow) {
                return;
            }

            var isExpanded = button.getAttribute('aria-expanded') === 'true';
            setPreviewState(button, targetRow, !isExpanded);
        });
    });

    document.querySelectorAll('.js-toggle-children-preview').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            var targetRow = document.getElementById(targetId);
            if (!targetRow) {
                return;
            }

            var isExpanded = button.getAttribute('aria-expanded') === 'true';
            setPreviewState(button, targetRow, !isExpanded);
        });
    });

    if (managedParentId) {
        var managedButton = document.querySelector('.js-toggle-children-preview[data-target="children-preview-' + managedParentId + '"]');
        var managedRow = document.getElementById('children-preview-' + managedParentId);
        setPreviewState(managedButton, managedRow, true);

        if (managedRow) {
            managedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    var editModal = document.getElementById('editUserModal');
    if (editModal) {
        var statusSelect = document.getElementById('edit_status');
        var rejectionGroup = document.getElementById('rejectionMessageGroup');
        var rejectionInput = document.getElementById('edit_rejection_message');

        // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
        function toggleRejectionMessageField() {
            if (!statusSelect || !rejectionGroup || !rejectionInput) {
                return;
            }

            var show = statusSelect.value === 'rejected' && !statusSelect.disabled;
            rejectionGroup.classList.toggle('d-none', !show);
            rejectionInput.required = show;

            if (!show) {
                rejectionInput.value = '';
            }
        }

        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }

            var isProtected = button.getAttribute('data-user-protected') === '1';
            var isSelf = button.getAttribute('data-user-self') === '1';

            document.getElementById('edit_user_id').value = button.getAttribute('data-user-id') || '';
            document.getElementById('edit_name').value = button.getAttribute('data-user-name') || '';
            document.getElementById('edit_surname').value = button.getAttribute('data-user-surname') || '';
            document.getElementById('edit_email').value = button.getAttribute('data-user-email') || '';
            document.getElementById('edit_phone').value = button.getAttribute('data-user-phone') || '';
            document.getElementById('edit_role').value = button.getAttribute('data-user-role') || 'parent';
            statusSelect.value = button.getAttribute('data-user-status') || 'pending';
            document.getElementById('edit_password').value = '';
            if (rejectionInput) {
                rejectionInput.value = '';
            }

            document.getElementById('edit_role').disabled = isProtected || isSelf;
            statusSelect.disabled = isProtected || isSelf;

            document.getElementById('editProtectedNotice').classList.toggle('d-none', !isProtected);
            document.getElementById('editSelfNotice').classList.toggle('d-none', !isSelf);

            toggleRejectionMessageField();
        });

        if (statusSelect) {
            statusSelect.addEventListener('change', toggleRejectionMessageField);
        }
    }

    var deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal) {
        var deleteUserForm = document.getElementById('deleteUserForm');
        var deleteUserStatusField = document.getElementById('delete_user_status');
        var deleteUserStatusLabelField = document.getElementById('delete_user_status_label');
        var deleteUserExtraWarning = document.getElementById('deleteUserExtraWarning');
        var deleteUserWarningStatus = document.getElementById('delete_user_warning_status');
        var deleteUserHistoryWarning = document.getElementById('delete_user_history_warning');
        var deleteUserHistoryCounts = document.getElementById('delete_user_history_counts');
        var deleteUserFinalConfirmModalElement = document.getElementById('deleteUserFinalConfirmModal');
        var deleteUserFinalConfirmMessage = document.getElementById('deleteUserFinalConfirmMessage');
        var deleteUserFinalConfirmButton = document.getElementById('deleteUserFinalConfirmButton');
        var deleteUserFinalConfirmModal = deleteUserFinalConfirmModalElement
            ? new bootstrap.Modal(deleteUserFinalConfirmModalElement)
            : null;
        var isDeleteUserFinalConfirmed = false;

        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }

            isDeleteUserFinalConfirmed = false;

            var status = button.getAttribute('data-user-status') || 'pending';
            var statusLabel = button.getAttribute('data-user-status-label') || 'Σε Αναμονή';
            var orderCount = parseInt(button.getAttribute('data-user-order-count') || '0', 10);
            var paymentCount = parseInt(button.getAttribute('data-user-payment-count') || '0', 10);
            var hasHistory = orderCount > 0 || paymentCount > 0;
            var requiresExtraConfirmation = status === 'active' || status === 'rejected' || hasHistory;

            document.getElementById('delete_user_id').value = button.getAttribute('data-user-id') || '';
            document.getElementById('delete_user_name').textContent = button.getAttribute('data-user-name') || '—';
            document.getElementById('delete_user_email').textContent = button.getAttribute('data-user-email') || '—';
            deleteUserStatusField.value = status;
            deleteUserStatusLabelField.value = statusLabel;

            if (deleteUserExtraWarning && deleteUserWarningStatus && deleteUserHistoryWarning && deleteUserHistoryCounts) {
                deleteUserWarningStatus.textContent = statusLabel;
                deleteUserHistoryCounts.textContent = orderCount + ' παραγγελίες / ' + paymentCount + ' πληρωμές';
                deleteUserExtraWarning.classList.toggle('d-none', !requiresExtraConfirmation);
                deleteUserHistoryWarning.classList.toggle('d-none', !hasHistory);
            }
        });

        if (deleteUserForm) {
            deleteUserForm.addEventListener('submit', function (event) {
                var status = deleteUserStatusField ? deleteUserStatusField.value : '';
                var statusLabel = deleteUserStatusLabelField ? deleteUserStatusLabelField.value : 'άγνωστη';
                var userName = document.getElementById('delete_user_name').textContent || 'τον χρήστη';
                var hasHistory = deleteUserHistoryWarning && !deleteUserHistoryWarning.classList.contains('d-none');

                if ((status === 'active' || status === 'rejected' || hasHistory) && !isDeleteUserFinalConfirmed) {
                    event.preventDefault();

                    var confirmMessage = 'Ο χρήστης "' + userName + '" είναι σε κατάσταση "' + statusLabel + '".';
                    if (hasHistory) {
                        confirmMessage += ' Θα διαγραφούν επίσης οι σχετικές παραγγελίες και πληρωμές του.';
                    }
                    confirmMessage += ' Η ενέργεια αυτή είναι οριστική.';

                    if (deleteUserFinalConfirmMessage) {
                        deleteUserFinalConfirmMessage.textContent = confirmMessage;
                    }

                    if (deleteUserFinalConfirmModal) {
                        deleteUserFinalConfirmModal.show();
                    }
                }
            });
        }

        if (deleteUserFinalConfirmButton) {
            deleteUserFinalConfirmButton.addEventListener('click', function () {
                isDeleteUserFinalConfirmed = true;

                if (deleteUserFinalConfirmModal) {
                    deleteUserFinalConfirmModal.hide();
                }

                deleteUserForm.requestSubmit();
            });
        }

        if (deleteUserFinalConfirmModalElement) {
            deleteUserFinalConfirmModalElement.addEventListener('hidden.bs.modal', function () {
                if (!isDeleteUserFinalConfirmed) {
                    return;
                }
            });
        }
    }

    document.querySelectorAll('.js-open-create-child').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('childModalTitle').innerHTML = '<i class="fas fa-child me-2"></i>Προσθήκη Παιδιού';
            document.getElementById('child_form_action').value = 'create_child';
            document.getElementById('child_parent_user_id').value = button.getAttribute('data-parent-id') || '';
            document.getElementById('child_parent_name_display').textContent = button.getAttribute('data-parent-name') || '—';
            document.getElementById('child_id').value = '';
            document.getElementById('child_name').value = '';
            document.getElementById('child_surname').value = '';
            document.getElementById('child_date_of_birth').value = '';
            document.getElementById('child_school_class').value = '';
        });
    });

    document.querySelectorAll('.js-open-edit-child').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('childModalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Επεξεργασία Παιδιού';
            document.getElementById('child_form_action').value = 'update_child';
            document.getElementById('child_parent_user_id').value = button.getAttribute('data-parent-id') || '';
            document.getElementById('child_parent_name_display').textContent = button.getAttribute('data-parent-name') || '—';
            document.getElementById('child_id').value = button.getAttribute('data-child-id') || '';
            document.getElementById('child_name').value = button.getAttribute('data-child-name') || '';
            document.getElementById('child_surname').value = button.getAttribute('data-child-surname') || '';
            document.getElementById('child_date_of_birth').value = button.getAttribute('data-child-dob') || '';
            document.getElementById('child_school_class').value = button.getAttribute('data-child-class') || '';
        });
    });

    document.querySelectorAll('.js-open-delete-child').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('delete_child_parent_user_id').value = button.getAttribute('data-parent-id') || '';
            document.getElementById('delete_child_id').value = button.getAttribute('data-child-id') || '';
            document.getElementById('delete_child_name').textContent = button.getAttribute('data-child-name') || '—';
        });
    });

    var deleteScheduleModal = document.getElementById('deleteScheduleModal');
    if (deleteScheduleModal) {
        deleteScheduleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }

            var featureLabel = button.getAttribute('data-schedule-feature') || '—';
            var startDate = button.getAttribute('data-schedule-start') || '';
            var endDate = button.getAttribute('data-schedule-end') || '';
            var formId = button.getAttribute('data-schedule-delete-form-id') || '';

            var featureEl = document.getElementById('delete_schedule_feature');
            var datesEl = document.getElementById('delete_schedule_dates');
            var formIdEl = document.getElementById('delete_schedule_form_id');

            if (featureEl) {
                featureEl.textContent = 'Λειτουργία: ' + featureLabel;
            }

            if (datesEl) {
                datesEl.textContent = 'Διάστημα: ' + (startDate || '—') + ' έως ' + (endDate || '—');
            }

            if (formIdEl) {
                formIdEl.value = formId;
            }
        });

        var confirmDeleteScheduleButton = document.getElementById('confirmDeleteScheduleButton');
        if (confirmDeleteScheduleButton) {
            confirmDeleteScheduleButton.addEventListener('click', function () {
                var formIdEl = document.getElementById('delete_schedule_form_id');
                var formId = formIdEl ? formIdEl.value : '';
                if (!formId) {
                    return;
                }

                var form = document.getElementById(formId);
                if (!form) {
                    return;
                }

                form.submit();
            });
        }
    }
});
