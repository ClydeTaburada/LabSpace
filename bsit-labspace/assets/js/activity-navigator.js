/**
 * Activity Navigator
 * 
 * A simplified approach to activity navigation that focuses on clean UI
 */

(function() {
    // Initialize the navigator when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Activity Navigator] Initializing...');
        
        // Find all activity links and items on the page
        const activityElements = document.querySelectorAll('.activity-link, .activity-item, [data-activity-id]');
        
        activityElements.forEach(element => {
            // Extract activity ID
            const activityId = element.dataset.activityId || 
                              element.querySelector('[data-activity-id]')?.dataset.activityId ||
                              (element.href && element.href.match(/id=(\d+)/) ? element.href.match(/id=(\d+)/)[1] : null);
            
            if (activityId) {
                // Add event listener for clean navigation
                element.addEventListener('click', function(e) {
                    // Skip if already clicking a link or button
                    if (e.target.closest('a:not(.activity-link), button:not(.activity-link)')) {
                        return;
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Add visual feedback
                    this.classList.add('active-click');
                    
                    // Show loading if available
                    if (window.showLoading) {
                        window.showLoading('Loading activity...');
                    }
                    
                    // Navigate to the activity directly without using goToActivity()
                    setTimeout(() => {
                        window.location.href = `view_activity.php?id=${activityId}`;
                    }, 200); // Small delay for visual feedback
                });
                
                // Mark the element as processed
                element.classList.add('activity-nav-processed');
            }
        });
        
        console.log('[Activity Navigator] Initialized');
    });
})();
