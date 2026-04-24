        let pendingDeleteForm = null;
        
        // Show delete confirmation modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                pendingDeleteForm = form;
                document.getElementById('deleteModal').classList.add('active');
            });
        });
        
        // Cancel deletion
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('deleteModal').classList.remove('active');
            pendingDeleteForm = null;
        });
        
        // Confirm deletion
        document.getElementById('confirmBtn').addEventListener('click', function() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                pendingDeleteForm = null;
            }
        });
