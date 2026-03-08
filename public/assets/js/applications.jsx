document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("submission_file");
    const fileName = document.getElementById("selectedFileName");

    if (fileInput && fileName) {
        fileInput.addEventListener("change", function () {
            fileName.textContent = this.files.length ? this.files[0].name : "No file selected";
        });
    }

    $('#submitModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);

        $('#modal_application_id').val(button.data('application-id'));
        $('#modal_application_title').val(button.data('application-title'));
        $('#modal_application_description').val(button.data('application-description'));
    });
});