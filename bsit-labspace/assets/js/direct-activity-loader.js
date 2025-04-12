/**
 * Direct Activity Loader
 * A simplified approach for directly loading activities without any popups or delays
 */

(function() {
    console.log('[Direct Loader] Initializing...');
    
    // Create a simple global function to navigate directly to activities
    window.loadActivity = function(activityId) {
        if (!activityId) {
            console.error('[Direct Loader] No activity ID provided');
            return;
        }
        
        console.log('[Direct Loader] Loading activity:', activityId);
        
        // Store activity ID for potential recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
        } catch (e) {
            console.error('[Direct Loader] Storage error:', e);
        }
        
        // Determine correct URL based on user type
        const isStudent = window.location.pathname.includes('/student/');
        const isTeacher = window.location.pathname.includes('/teacher/');
        const isAdmin = window.location.pathname.includes('/admin/');
        
        let url;
        if (isStudent) {
            url = 'view_activity.php?id=' + activityId + '&direct=1';
        } else if (isTeacher) {
            url = 'edit_activity.php?id=' + activityId + '&direct=1';
        } else if (isAdmin) {
            url = '../direct_activity.php?id=' + activityId;
        } else {
            // Default to direct activity access
            url = 'direct_activity.php?id=' + activityId;
        }
        
        // Navigate directly without any delays or extra handling
        window.location.href = url;
    };
    
    // Add a more reliable version that bypasses any potential issues
    window.directLoadActivity = function(activityId) {
        if (!activityId) return;
        
        // Create a form and submit it - this bypasses any script interference
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'direct_activity.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = activityId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    };
    
    (function() {
        console.log('[Direct Loader] Skipping redundant activity click handler initialization.');
        // Removed redundant click handler logic
    })();
})();
