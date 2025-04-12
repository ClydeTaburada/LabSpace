/**
 * Reliable Activity Click Handler
 * This script ensures activities can always be accessed even if other scripts fail
 */

(function() {
    console.log('[Activity Click] Initializing reliable click handler...');
    
    // Run immediately and also after DOM is loaded to catch all cases
    setupReliableClicks();
    document.addEventListener('DOMContentLoaded', setupReliableClicks);
    
    function setupReliableClicks() {
        console.log('[Activity Click] Setting up reliable clicks...');
        
        // Find all activity items that haven't been processed yet
        const activityElements = document.querySelectorAll('.activity-item:not(.reliable-click-processed), [data-activity-id]:not(.reliable-click-processed)');
        
        if (activityElements.length === 0) {
            console.warn('[Activity Click] No activity items found to process.');
            return;
        }

        activityElements.forEach(element => {
            // Get activity ID
            const activityId = element.dataset.activityId || 
                              element.getAttribute('data-activity-id');
            
            if (activityId) {
                console.log(`[Activity Click] Processing activity ID: ${activityId}`);
                
                // Mark as processed
                element.classList.add('reliable-click-processed');
                
                // Add direct click handler
                element.addEventListener('click', function(e) {
                    // Prevent default behavior if not clicking on a link or button
                    if (!e.target.closest('a, button')) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        console.log('[Activity Click] Navigating to activity ID:', activityId);
                        
                        // Navigate directly
                        window.location.href = `view_activity.php?id=${activityId}`;
                    }
                });
                
                // Make it look clickable
                element.style.cursor = 'pointer';
            } else {
                console.warn('[Activity Click] Activity ID not found for an element:', element);
            }
        });
    }
})();
