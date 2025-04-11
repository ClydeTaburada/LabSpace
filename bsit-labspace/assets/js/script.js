// Main JavaScript file for BSIT LabSpace

document.addEventListener('DOMContentLoaded', function() {
    // Enable all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Enable all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add fade effect for alerts with auto-close
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-auto-close');
        alerts.forEach(function(alert) {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
    
    // Mobile menu functionality 
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    const closeMobileMenuBtn = document.getElementById('close-mobile-menu');
    
    if (mobileMenuBtn && mobileMenu && mobileMenuOverlay) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.classList.add('active');
            document.body.classList.add('menu-open');
        });
        
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
        
        if (closeMobileMenuBtn) {
            closeMobileMenuBtn.addEventListener('click', closeMobileMenu);
        }
        
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        
        // Close mobile menu when clicking links
        const mobileLinks = mobileMenu.querySelectorAll('.nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }
    
    // Simple loading overlay functionality
    window.showLoading = function(message = 'Loading...') {
        console.log('[Loading] Showing loading overlay:', message);
        
        const overlay = document.getElementById('loading-overlay');
        const loadingText = overlay?.querySelector('.loading-text');
        
        if (loadingText) {
            loadingText.textContent = message;
        }
        
        if (overlay) {
            overlay.classList.add('show');
        }
        
        // Set a safety timeout to hide loading after 10 seconds
        if (window.loadingSafetyTimeout) {
            clearTimeout(window.loadingSafetyTimeout);
        }
        
        window.loadingSafetyTimeout = setTimeout(() => {
            console.log('[Loading] Safety timeout triggered - hiding overlay');
            hideLoading();
        }, 10000);
    };

    // Hide loading overlay
    window.hideLoading = function() {
        if (window.loadingSafetyTimeout) {
            clearTimeout(window.loadingSafetyTimeout);
        }
        
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    };
    
    // Enhanced button loading state with animation
    window.setBtnLoading = function(button, isLoading) {
        if (!button) return;
        
        if (isLoading) {
            const btnText = button.innerHTML;
            button.setAttribute('data-original-text', btnText);
            button.classList.add('btn-loading');
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="btn-text ms-2">' + btnText + '</span>';
            button.disabled = true;
        } else {
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    };
    
    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('no-loading')) {
                setBtnLoading(submitBtn, true);
            }
            
            // If the form has data-show-loading attribute
            if (this.getAttribute('data-show-loading') !== null) {
                showLoading();
            }
        });
    });
    
    // Add loading for links with data-loading attribute
    document.querySelectorAll('a[data-loading="true"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Only show loading if not prevented default
            if (!e.defaultPrevented) {
                showLoading();
            }
        });
    });
    
    // Improved activity click functionality
    document.querySelectorAll('.activity-item').forEach(activity => {
        activity.addEventListener('click', function(e) {
            // Don't handle if clicking on a nested clickable element
            if (e.target.closest('a:not(.activity-link), button')) {
                return;
            }
            
            // Get activity ID and URL
            const activityId = this.dataset.activityId || 
                             this.querySelector('[data-activity-id]')?.dataset.activityId;
            
            if (activityId) {
                e.preventDefault();
                e.stopPropagation();
                
                // Visual feedback that item was clicked
                this.classList.add('active-click');
                
                // Show loading state
                showLoading('Loading activity...');
                
                // Use the global navigation function instead of direct navigation
                if (window.navigateToActivity) {
                    window.navigateToActivity(activityId);
                } else {
                    // Fallback to standard navigation
                    const isStudent = window.location.pathname.indexOf('/student/') >= 0;
                    const isTeacher = window.location.pathname.indexOf('/teacher/') >= 0;
                    
                    let path = 'view_activity.php?id=' + activityId;
                    if (isTeacher) {
                        path = 'edit_activity.php?id=' + activityId;
                    }
                    
                    window.location.href = path;
                }
            }
        });
        
        // Add cursor pointer to show it's clickable
        activity.classList.add('clickable-card');
    });
    
    // Detect screen size changes for responsive adjustments
    function handleScreenSizeChange() {
        const isMobile = window.innerWidth < 768;
        if (isMobile && document.body.classList.contains('sidebar-active')) {
            document.body.classList.remove('sidebar-active');
        }
    }
    
    window.addEventListener('resize', handleScreenSizeChange);
    handleScreenSizeChange(); // Call once on load
});