/**
 * Core Navigation Functions
 * Provides universal activity navigation that works everywhere in the system
 */

// Initialize emergency navigation system
(function() {
    console.log('[Navigation Core] Initializing universal navigation system');
    
    // Create a global navigation function that works across the entire site
    window.goToActivity = function(activityId, options = {}) {
        if (!activityId) {
            console.error('[Navigation] No activity ID provided');
            return false;
        }
        
        // Default options
        const defaults = {
            showLoading: true,
            storeInStorage: true,
            userType: getUserType(),
            emergencyFallback: true,
            timeout: 500
        };
        
        const settings = {...defaults, ...options};
        const userType = settings.userType;
        
        console.log(`[Navigation] Navigating to activity #${activityId} as ${userType}`);
        
        // Store activity ID for emergency recovery
        if (settings.storeInStorage) {
            try {
                localStorage.setItem('last_activity_id', activityId);
                sessionStorage.setItem('last_activity_id', activityId);
                console.log('[Navigation] Activity ID stored in storage');
            } catch (e) {
                console.error('[Navigation] Storage error:', e);
            }
        }
        
        // Show loading indicator if available
        if (settings.showLoading && typeof showLoading === 'function') {
            showLoading(`Loading activity #${activityId}...`);
        }
        
        // Determine destination based on user type
        let destination;
        if (userType === 'student') {
            destination = `${getBasePath()}student/view_activity.php?id=${activityId}`;
        } else if (userType === 'teacher') {
            destination = `${getBasePath()}teacher/edit_activity.php?id=${activityId}`;
        } else {
            // General case - direct activity view
            destination = `${getBasePath()}direct_activity.php?id=${activityId}`;
        }
        
        // Navigate using multiple approaches for reliability
        try {
            // Primary navigation method
            window.location.href = destination;
            
            // Set timeout for fallback methods
            if (settings.emergencyFallback) {
                setTimeout(function() {
                    // Check if we're still on the same page
                    if (!document.location.href.includes(`activity`) || 
                        !document.location.href.includes(`id=${activityId}`)) {
                        console.log('[Navigation] Primary navigation may have failed, trying fallback');
                        window.location.replace(destination);
                        
                        // Final emergency fallback after another delay
                        setTimeout(function() {
                            // Check if navigation is still stuck
                            if (!document.location.href.includes(`activity`) || 
                                !document.location.href.includes(`id=${activityId}`)) {
                                console.log('[Navigation] Emergency fallback activated');
                                window.location.href = `${getBasePath()}emergency_activity.php?id=${activityId}`;
                            }
                        }, 1000);
                    }
                }, settings.timeout);
            }
        } catch (e) {
            console.error('[Navigation] Error during navigation:', e);
            if (settings.emergencyFallback) {
                // In case of error, go to emergency navigation
                window.location.href = `${getBasePath()}emergency_activity.php?id=${activityId}`;
            }
        }
        
        return false; // Prevent default behavior if used in an onclick
    };
    
    // Helper to detect user type based on URL or session
    function getUserType() {
        const path = window.location.pathname;
        
        if (path.includes('/student/')) {
            return 'student';
        } else if (path.includes('/teacher/')) {
            return 'teacher';
        } else if (path.includes('/admin/')) {
            return 'admin';
        }
        
        // Default fallback
        return 'unknown';
    }
    
    // Helper to get base path depending on current directory
    function getBasePath() {
        const path = window.location.pathname;
        
        if (path.includes('/student/') || path.includes('/teacher/') || path.includes('/admin/')) {
            return '../';
        }
        
        return '';
    }
    
    // Add keyboard shortcut for emergency navigation
    document.addEventListener('keydown', function(e) {
        // Alt+E to access emergency navigation
        if (e.altKey && e.key.toLowerCase() === 'e') {
            window.location.href = `${getBasePath()}emergency_navigation.php`;
        }
        
        // Alt+A to prompt for activity ID input
        if (e.altKey && e.key.toLowerCase() === 'a') {
            const activityId = prompt('Enter activity ID to navigate to:');
            if (activityId && !isNaN(activityId)) {
                goToActivity(activityId);
            }
        }
    });
    
    // Add to window load to ensure it runs after all other scripts
    window.addEventListener('load', function() {
        console.log('[Navigation Core] Navigation system ready');
        
        // Fix any broken activity links on the page
        document.querySelectorAll('a[href*="view_activity.php"], a[href*="edit_activity.php"]').forEach(link => {
            // Extract activity ID from href
            const match = link.href.match(/id=(\d+)/);
            if (match && match[1]) {
                const activityId = match[1];
                
                // Add a data attribute for quick access
                link.setAttribute('data-activity-id', activityId);
                
                // Add click handler with emergency fallback
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    goToActivity(activityId);
                });
            }
        });
    });
})();

// Add this script to all pages using the include mechanism
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Navigation Core] Activity navigation core loaded');
});
