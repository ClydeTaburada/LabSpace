/**
 * Activity Recovery System
 * This script provides emergency recovery of activities and ensures
 * users can always navigate back to activities they were working on
 */

(function() {
    // Only run on pages where it's needed (not on emergency pages)
    if (window.location.href.includes('emergency_navigation.php') || 
        window.location.href.includes('direct_activity.php')) {
        return;
    }
    
    console.log('[Recovery] Initializing activity recovery system');
    
    // Initialize when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Check for stuck loading overlay
        monitorLoadingOverlay();
        
        // Add recovery UI if needed
        setTimeout(checkForRecoveryNeeded, 5000);
        
        // Add global error handler
        window.addEventListener('error', handleGlobalError);
        
        // Store page load status
        window.activityRecoveryData = {
            pageLoaded: true,
            lastErrorTime: 0,
            errorCount: 0
        };
        
        console.log('[Recovery] Activity recovery system initialized');
    });
    
    // Monitor loading overlay and force clear if stuck
    function monitorLoadingOverlay() {
        // Check immediately and then periodically
        checkLoadingOverlay();
        setInterval(checkLoadingOverlay, 3000);
    }
    
    // Check for stuck loading overlay
    function checkLoadingOverlay() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay && overlay.classList.contains('show')) {
            // If overlay has been visible for too long, force hide it
            console.log('[Recovery] Detected active loading overlay, checking duration');
            
            // After 8 seconds, force hide
            setTimeout(() => {
                if (overlay.classList.contains('show')) {
                    console.log('[Recovery] Forcing hide of loading overlay after timeout');
                    overlay.classList.remove('show');
                    
                    // Show recovery UI
                    showRecoveryMessage('Loading seems to be stuck. Navigation recovery is available.');
                }
            }, 8000);
        }
    }
    
    // Check if recovery might be needed based on navigation patterns
    function checkForRecoveryNeeded() {
        // Check if we're on a page where recovery might be useful
        if (window.location.href.includes('dashboard.php') || 
            window.location.href.includes('view_class.php')) {
            
            // Check if user was trying to access an activity
            const lastActivityId = localStorage.getItem('last_activity_id');
            if (lastActivityId) {
                const lastPageAttempt = localStorage.getItem('last_page_attempt');
                
                // If last attempt was to view an activity but we're not on an activity page
                if (lastPageAttempt && lastPageAttempt.includes('view_activity.php')) {
                    showRecoveryMessage(
                        'It looks like you were trying to access Activity #' + lastActivityId + 
                        '. Would you like to recover your navigation?',
                        lastActivityId
                    );
                }
            }
        }
    }
    
    // Handle global errors
    function handleGlobalError(e) {
        console.error('[Recovery] Caught error:', e.message);
        
        // Track error count and timing
        window.activityRecoveryData.errorCount++;
        window.activityRecoveryData.lastErrorTime = Date.now();
        
        // If we get multiple errors in a short time, show recovery UI
        if (window.activityRecoveryData.errorCount >= 3) {
            showRecoveryMessage('Multiple errors detected. Would you like to use emergency navigation?');
        }
        
        // Force hide loading overlay if an error occurs
        const overlay = document.getElementById('loading-overlay');
        if (overlay && overlay.classList.contains('show')) {
            overlay.classList.remove('show');
        }
    }
    
    // Show recovery message with options
    function showRecoveryMessage(message, activityId = null) {
        // Create message div if it doesn't exist
        if (!document.getElementById('activity-recovery-message')) {
            const messageDiv = document.createElement('div');
            messageDiv.id = 'activity-recovery-message';
            messageDiv.style.position = 'fixed';
            messageDiv.style.bottom = '20px';
            messageDiv.style.right = '20px';
            messageDiv.style.maxWidth = '350px';
            messageDiv.style.background = '#f8d7da';
            messageDiv.style.borderLeft = '5px solid #dc3545';
            messageDiv.style.padding = '15px';
            messageDiv.style.borderRadius = '5px';
            messageDiv.style.boxShadow = '0 4px 10px rgba(0,0,0,0.2)';
            messageDiv.style.zIndex = '9999';
            
            document.body.appendChild(messageDiv);
        }
        
        // Update message content
        const messageDiv = document.getElementById('activity-recovery-message');
        messageDiv.innerHTML = `
            <div style="margin-bottom:10px;"><strong><i class="bi bi-exclamation-triangle"></i> Navigation Recovery</strong></div>
            <p style="margin-bottom:10px;">${message}</p>
            <div style="display:flex;gap:10px;">
                ${activityId ? 
                    `<a href="direct_activity.php?id=${activityId}" class="btn btn-danger btn-sm">Recover Activity</a>` :
                    '<a href="emergency_navigation.php" class="btn btn-danger btn-sm">Emergency Navigation</a>'}
                <button id="dismiss-recovery" class="btn btn-outline-secondary btn-sm">Dismiss</button>
            </div>
        `;
        
        // Handle dismiss button
        document.getElementById('dismiss-recovery').addEventListener('click', function() {
            messageDiv.style.display = 'none';
        });
        
        // Auto-hide after 30 seconds
        setTimeout(() => {
            if (messageDiv && messageDiv.style.display !== 'none') {
                messageDiv.style.display = 'none';
            }
        }, 30000);
    }
    
    // Store current page for navigation tracking
    function storeCurrentPage() {
        localStorage.setItem('last_page_visit', window.location.href);
        
        // If we're on an activity page, store the ID
        if (window.location.href.includes('view_activity.php')) {
            const params = new URLSearchParams(window.location.search);
            const activityId = params.get('id');
            if (activityId) {
                localStorage.setItem('last_activity_id', activityId);
            }
        }
    }
    
    // Store current page on load
    storeCurrentPage();
    
    // Before leaving the page, store the destination
    window.addEventListener('beforeunload', function() {
        // Store that we're navigating away, to be used in case navigation fails
        localStorage.setItem('last_page_attempt', window.location.href);
    });
})();
