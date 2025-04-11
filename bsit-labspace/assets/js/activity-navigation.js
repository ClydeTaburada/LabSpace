/**
 * Activity Navigation Module
 * Provides reliable navigation to activities with error handling
 */

(function() {
    console.log('[Activity Nav] Initializing...');

    // Create a globally accessible navigation function
    window.navigateToActivity = function(activityId) {
        if (!activityId) {
            console.error('[Activity Nav] No activity ID provided');
            return false;
        }

        console.log('[Activity Nav] Navigating to activity ID:', activityId);
        
        // Save ID in session storage for server-side validation
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('activity_access_time', Date.now());
            
            // Add to recent activities for recovery
            try {
                const recentActivities = JSON.parse(localStorage.getItem('recent_activities') || '[]');
                // Don't add duplicates and keep most recent at the start
                const filteredActivities = recentActivities.filter(id => id != activityId);
                filteredActivities.unshift(activityId);
                // Keep only the 10 most recent
                const trimmedActivities = filteredActivities.slice(0, 10);
                localStorage.setItem('recent_activities', JSON.stringify(trimmedActivities));
            } catch (e) {
                console.error('[Activity Nav] Recent activities error:', e);
            }
        } catch (e) {
            console.error('[Activity Nav] Storage error:', e);
        }
        
        // Determine the correct path based on user role - avoid direct access
        const isStudent = window.location.pathname.indexOf('/student/') >= 0;
        const isTeacher = window.location.pathname.indexOf('/teacher/') >= 0;
        let baseUrl = '';
        
        if (isStudent) {
            baseUrl = 'view_activity.php?id=';
        } else if (isTeacher) {
            baseUrl = 'edit_activity.php?id=';
        } else {
            // Use student portal for viewing on any other page
            if (window.location.pathname.split('/').length > 2) {
                baseUrl = '../student/view_activity.php?id=';
            } else {
                baseUrl = 'student/view_activity.php?id=';
            }
        }
        
        // Show loading state if available
        if (window.showLoading) {
            window.showLoading('Loading activity...');
        }
        
        // Navigate with basic approach - direct navigation
        try {
            // Navigate directly without token for better compatibility
            window.location.href = baseUrl + activityId;
            return true;
        } catch (e) {
            console.error('[Activity Nav] Navigation error:', e);
            
            // Fallback to standard path navigation as a last resort
            window.location.href = '../student/view_activity.php?id=' + activityId;
            return false;
        }
    };
    
    // Attach the navigation to all activity elements
    document.addEventListener('DOMContentLoaded', function() {
        attachActivityNavigation();
    });
    
    // Function to attach navigation to activity elements
    function attachActivityNavigation() {
        const activityElements = document.querySelectorAll('.activity-item, [data-activity-id], .activity-link');
        
        activityElements.forEach(element => {
            // Skip if already processed
            if (element.classList.contains('activity-nav-processed')) {
                return;
            }
            
            // Extract activity ID
            const activityId = element.dataset.activityId || 
                              element.querySelector('[data-activity-id]')?.dataset.activityId || 
                              (element.href && element.href.match(/id=(\d+)/) ? element.href.match(/id=(\d+)/)[1] : null);
            
            if (activityId) {
                element.classList.add('activity-nav-processed');
                
                // Add click handler for direct navigation
                element.addEventListener('click', function(e) {
                    // Skip if clicking on a nested link or button
                    if (e.target.closest('a:not(.activity-link), button:not(.activity-button)')) {
                        return;
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Add visual feedback
                    this.classList.add('active-click');
                    
                    // Navigate to activity with direct method
                    navigateToActivity(activityId);
                });
                
                // Make it visually appear clickable
                element.classList.add('activity-clickable');
                
                // Add cursor style
                element.style.cursor = 'pointer';
            }
        });
    }
    
    // Global helper function for simple activity access
    window.goToActivity = function(activityId) {
        if (!activityId) {
            return;
        }
        
        navigateToActivity(activityId);
    };
    
    // Run on page load to fix all links immediately
    if (document.readyState !== 'loading') {
        attachActivityNavigation();
    }
})();
