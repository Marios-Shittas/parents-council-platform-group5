// Clear all possible ways to go back to protected pages
        (function() {
            // Clear session and local storage
            try {
                sessionStorage.clear();
                localStorage.clear();
            } catch(e) {}
            
            // Replace current state multiple times to bury history
            for (let i = 0; i < 10; i++) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Push many forward states
            for (let i = 0; i < 50; i++) {
                window.history.pushState({state: i}, null, window.location.href);
            }
            
            // Redirect without adding to history
            window.location.replace(window.LOGOUT_REDIRECT_URL || 'login.php');
        })();
