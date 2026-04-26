// Katharizei kathe dynato tropo epistrofis se prostÏ„ÎµÏ…omenes selides
        (function() {
            // Katharizei synedria kai topiko apothikeftiko
            try {
                sessionStorage.clear();
                localStorage.clear();
            } catch(e) {}
            
            // Antikatistai pollapla i trexousa katastasi gia na xathei to istoriko
            for (let i = 0; i < 10; i++) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Prosthikeuei polles epomenes katastaseis
            for (let i = 0; i < 50; i++) {
                window.history.pushState({state: i}, null, window.location.href);
            }
            
            // Kanei anakatethynsi xoris nea eggrafi sto istoriko
            window.location.replace(window.LOGOUT_REDIRECT_URL || 'login.php');
        })();
