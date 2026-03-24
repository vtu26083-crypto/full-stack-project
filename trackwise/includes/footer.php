<?php
/**
 * TrackWise Footer Component
 * Contains page footer and closing tags
 */

$user_logged_in = isLoggedIn();
?>

            <?php if ($user_logged_in): ?>
            </div>
        </main>
    </div>
    
    <!-- Success/Error Message Container -->
    <div id="message-container" class="message-container"></div>
    
    <?php else: ?>
        </div>
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <?php if ($user_logged_in): ?>
    <script>
        // Initialize dashboard if on dashboard page
        if (window.location.pathname.includes('dashboard.php')) {
            document.addEventListener('DOMContentLoaded', function() {
                initializeDashboard();
            });
        }
        
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            if (window.location.pathname.includes('dashboard.php')) {
                location.reload();
            }
        }, 300000);
    </script>
    <?php endif; ?>
</body>
</html>
