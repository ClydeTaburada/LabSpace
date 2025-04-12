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
    
    // Consolidate activity click handlers
    console.log('[Script] Checking for ActivityManager...');
    
    // Only add activity click handlers if ActivityManager is not available
    if (!window.ActivityManager) {
        console.log('[Script] ActivityManager not found, using fallback handlers');
        document.querySelectorAll('.activity-item, [data-activity-id]').forEach(activity => {
            if (activity.classList.contains('activity-click-processed') || 
                activity.classList.contains('activity-manager-processed')) {
                return;
            }

            const activityId = activity.dataset.activityId || 
                              activity.querySelector('[data-activity-id]')?.dataset.activityId;

            if (activityId) {
                activity.addEventListener('click', function(e) {
                    if (!e.target.closest('a, button')) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log(`[Script] Navigating to activity ID: ${activityId}`);
                        window.location.href = `view_activity.php?id=${activityId}`;
                    }
                });

                activity.style.cursor = 'pointer';
                activity.classList.add('activity-click-processed');
            }
        });
    } else {
        console.log('[Script] ActivityManager found, skipping duplicate handlers');
    }

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