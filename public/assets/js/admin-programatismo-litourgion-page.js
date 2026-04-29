// Arxeio: public\assets\js\admin-programatismo-litourgion-page.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
// Kanei initialize tin admin selida programmatismou leitourgion otan fortothei to DOM.
(function () {
        // Einai plain JS giati aplos syndeei Bootstrap modal me ta diagrafi buttons tis selidas.
        var deleteScheduleModal = document.getElementById('deleteScheduleModal');
        if (!deleteScheduleModal) {
            return;
        }

        deleteScheduleModal.addEventListener('show.bs.modal', function (event) {
            // Prin anoiksei to modal, pairnoume label/imerominias/forma id apo to koumpi pou patithike.
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
                // To confirm koumpi vriskei kai stelnei tin pragmatiki formaa diagrafis.
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
