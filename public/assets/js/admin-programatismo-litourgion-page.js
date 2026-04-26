(function () {
        var deleteScheduleModal = document.getElementById('deleteScheduleModal');
        if (!deleteScheduleModal) {
            return;
        }

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
    })();
