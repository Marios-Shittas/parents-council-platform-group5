document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-product-btn');
    const deleteProductIdInput = document.getElementById('deleteProductId');
    const deleteProductName = document.getElementById('deleteProductName');

    deleteButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');

            deleteProductIdInput.value = productId;
            deleteProductName.textContent = productName;

            $('#deleteConfirmModal').modal('show');
        });
    });

    if (document.body.getAttribute('data-edit-product-open') === '1') {
        $('#editProductModal').modal('show');
    }
});
