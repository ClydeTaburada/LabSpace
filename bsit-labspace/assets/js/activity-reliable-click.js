/**
 * Reliable Activity Click Handler
 * This script ensures activities can always be accessed even if other scripts fail
 */

(function() {
    console.log('[Activity Click] Initializing reliable click handler...');
    
    // Run immediately and also after DOM is loaded to catch all cases
    setupReliableClicks();
    document.addEventListener('DOMContentLoaded', setupReliableClicks);
    
    // For any dynamically added elements
    setInterval(setupReliableClicks, 2000);
    
    function setupReliableClicks() {
        // Find all activity items that haven't been processed yet
        const activityElements = document.querySelectorAll('.activity-item:not(.reliable-click-processed), [data-activity-id]:not(.reliable-click-processed)');
        
        activityElements.forEach(element => {
            // Get activity ID
            const activityId = element.dataset.activityId || 
                              element.querySelector('[data-activity-id]')?.dataset.activityId;
            
            if (activityId) {
                // Mark as processed
                element.classList.add('reliable-click-processed');
                
                // Add direct click handler with capture phase to ensure it runs first
                element.addEventListener('click', function(e) {
                    // Don't interfere with normal link or button clicks
                    if (e.target.closest('a:not(.activity-link), button:not(.activity-btn)')) {
                        return;
                    }
                    
                    // Store activity ID in storage for recovery
                    try {
                        localStorage.setItem('last_activity_id', activityId);
                        sessionStorage.setItem('last_activity_id', activityId);
                    } catch (e) {
                        console.error('[Activity Click] Storage error:', e);
                    }
                    
                    console.log('[Activity Click] Activity clicked:', activityId);
                    
                    // Determine correct URL based on context
                    const isStudent = window.location.pathname.includes('/student/');
                    const isTeacher = window.location.pathname.includes('/teacher/');
                    
                    let url;
                    if (isStudent) {
                        url = 'view_activity.php?id=' + activityId;
                    } else if (isTeacher) {
                        url = 'edit_activity.php?id=' + activityId;
                    } else {
                        // Default to direct activity viewer for admin or unknown contexts
                        url = '../direct_activity.php?id=' + activityId;
                    }
                    
                    // Navigate directly
                    window.location.href = url;
                    
                    // For good measure, prevent any possible default
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }, true); // true = use capture phase for higher priority
                
                // Make it look clickable
                element.style.cursor = 'pointer';
                
                // Add hover class if missing
                if (!element.classList.contains('activity-clickable')) {
                    element.classList.add('activity-clickable');
                }
            }
        });
    }
    
    // Add emergency direct access button
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.createElement('div');
        container.className = 'direct-access-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        
        const button = document.createElement('button');
        button.className = 'btn btn-sm btn-danger';
        button.innerHTML = '<i class="fas fa-external-link-alt"></i> Direct Access';
        button.title = 'Click to directly access an activity by ID';
        
        button.addEventListener('click', function() {
            const activityId = prompt('Enter Activity ID to access directly:');
            if (activityId && !isNaN(activityId)) {
                window.location.href = '../direct_activity.php?id=' + activityId;
            }
        });
        
        container.appendChild(button);
        document.body.appendChild(container);
    });
})();
