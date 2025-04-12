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

        console.log(`[Activity Nav] Navigating to activity ID: ${activityId}`);

        // Show loading state
        if (window.showLoading) {
            window.showLoading('Loading activity...');
        }

        // Determine the correct URL
        const url = `view_activity.php?id=${activityId}`;

        // Navigate to the activity
        try {
            window.location.href = url;
        } catch (e) {
            console.error('[Activity Nav] Navigation error:', e);
        }
    };

    // Debugging: Ensure the function is defined globally
    console.log('[Activity Nav] navigateToActivity function is defined:', typeof window.navigateToActivity === 'function');

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
