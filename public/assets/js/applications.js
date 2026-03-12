document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("submission_file");
    const fileName = document.getElementById("selectedFileName");

    if (fileInput && fileName) {
        fileInput.addEventListener("change", function () {
            fileName.textContent = this.files.length ? this.files[0].name : "Δεν έχει επιλεγεί αρχείο";
        });
    }

    $('#submitModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const submissionType = button.data('submission-type') || 'file';

        $('#modal_application_id').val(button.data('application-id'));
        $('#modal_application_title').val(button.data('application-title'));
        $('#modal_application_description').val(button.data('application-description'));
        $('#modal_submission_type').val(submissionType);

        if (submissionType === 'text') {
            $('#modal_file_section').hide();
            $('#submission_file').removeAttr('required');
            $('#modal_text_section').show();
            $('#text_content').attr('required', 'required');
        } else {
            $('#modal_text_section').hide();
            $('#text_content').removeAttr('required');
            $('#modal_file_section').show();
            $('#submission_file').attr('required', 'required');
        }

        // Reset fields on open
        if (fileInput) fileInput.value = '';
        if (fileName) fileName.textContent = 'Δεν έχει επιλεγεί αρχείο';
        $('#text_content').val('');
    });
});
