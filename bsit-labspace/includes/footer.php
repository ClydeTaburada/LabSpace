</div><!-- /.main-content -->
    
    <?php if (!isLoginPage()): ?>
    <footer class="footer py-4 bg-light mt-auto">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-muted">&copy; <?php echo date('Y'); ?> BSIT LabSpace. All rights reserved.</span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-decoration-none text-muted me-3">Terms</a>
                    <a href="#" class="text-decoration-none text-muted me-3">Privacy</a>
                    <a href="#" class="text-decoration-none text-muted">Help</a>
                </div>
            </div>
        </div>
    </footer>
    <?php else: ?>
    <footer class="auth-footer text-center">
        <div class="container">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> BSIT LabSpace. All rights reserved.</span>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Emergency Navigation Link -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5; opacity: 0.6;">
        <a href="<?php echo getBaseUrl(); ?>emergency_navigation.php" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-exclamation-triangle"></i>
        </a>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/script.js"></script>
    <!-- Debug utilities - remove in production -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/debug.js"></script>

    <script>
    // Clean up any stuck loading overlays
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay && overlay.classList.contains('show')) {
                console.log('[Footer] Detected stuck loading overlay, forcing hide');
                overlay.classList.remove('show');
            }
            
            // Disable any debug containers that might interfere
            const debugContainer = document.getElementById('click-debug-container');
            if (debugContainer) {
                debugContainer.style.display = 'none';
            }
        }, 2000);
    });

    // Use ActivityManager for navigation if available
    document.addEventListener('DOMContentLoaded', function() {
        if (window.ActivityManager) {
            console.log('[Footer] ActivityManager handling activity clicks, skipping redundant handlers');
            return;
        }
        
        console.log('[Footer] ActivityManager not found, using fallback handlers');
        
        // Only add click handlers if ActivityManager is not available
        document.querySelectorAll('.activity-item, [data-activity-id]').forEach(function(item) {
            if (item.classList.contains('reliable-click-processed') || 
                item.classList.contains('activity-manager-processed') || 
                item.classList.contains('activity-click-processed')) {
                return;
            }

            const activityId = item.dataset.activityId || 
                              item.querySelector('[data-activity-id]')?.dataset.activityId;

            if (activityId) {
                item.addEventListener('click', function(e) {
                    if (e.target.closest('a, button')) {
                        return;
                    }

                    e.preventDefault();
                    e.stopPropagation();

                    console.log('[Footer] Navigating to activity ID:', activityId);

                    // Determine the proper URL
                    const isStudent = window.location.pathname.indexOf('/student/') >= 0;
                    const isTeacher = window.location.pathname.indexOf('/teacher/') >= 0;
                    
                    let url;
                    if (isStudent) {
                        url = 'view_activity.php?id=' + activityId;
                    } else if (isTeacher) {
                        url = 'view_activity.php?id=' + activityId;
                    } else {
                        url = '../direct_activity.php?id=' + activityId;
                    }
                    
                    // Store the ID for emergency recovery
                    try {
                        localStorage.setItem('last_activity_id', activityId);
                        sessionStorage.setItem('last_activity_id', activityId);
                    } catch (e) {
                        console.error('[Footer] Storage error:', e);
                    }
                    
                    window.location.href = url;
                });

                item.style.cursor = 'pointer';
                item.classList.add('reliable-click-processed');
            }
        });
    });
    </script>
    
    <!-- Add direct activity loader for simpler navigation -->
    <script src="<?php echo getBaseUrl(); ?>assets/js/direct-activity-loader.js"></script>
</body>
</html>