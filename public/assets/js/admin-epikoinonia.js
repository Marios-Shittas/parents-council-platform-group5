// Arxeio: public\assets\js\admin-epikoinonia.js
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: einai gia admin, opote kratame elegxous rolou kai feedback kathara gia ton diaxeiristi.
let pendingDeleteForm = null;
        
        // Emfanizei modal epivevaiosis prin ti diagrafi.
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                pendingDeleteForm = form;
                document.getElementById('deleteModal').classList.add('active');
            });
        });
        
        // Akyrwnei tin energeia diagrafis.
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('deleteModal').classList.remove('active');
            pendingDeleteForm = null;
        });
        
        // Epivevaionei ti diagrafi kai synechizei tin energeia.
        document.getElementById('confirmBtn').addEventListener('click', function() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        });
        
        // Kleinei to modal otan o xristis kanei klik ektos perioxhs.
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                pendingDeleteForm = null;
            }
        });
