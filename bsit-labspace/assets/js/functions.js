/**
 * LabSpace specialized functions
 */

// ENHANCED: Improved function to handle activity navigation with safety measures
function navigateToActivity(activityId, url) {
    console.log(`[Activity] Navigating to activity ID: ${activityId}`);
    
    // If URL not provided, construct it
    if (!url && activityId) {
        url = `view_activity.php?id=${activityId}`;
    }
    
    if (!url) {
        console.error('[Activity] Navigation failed: No URL provided');
        return;
    }
    
    // Show loading indicator
    if (window.showLoading) {
        window.showLoading('Loading activity...');
    }
    
    // IMPROVED: Force navigation with a more reliable method and longer timeout
    // This should ensure the navigation happens even if other scripts interfere
    window.activityLoadTimeout = setTimeout(() => {
        console.log('[Activity] Forcing navigation to:', url);
        window.location.href = url;
    }, 300); // Longer timeout for more reliable navigation
}

// Function to reset/cancel any pending activity navigation
function cancelActivityNavigation() {
    if (window.activityLoadTimeout) {
        clearTimeout(window.activityLoadTimeout);
        window.activityLoadTimeout = null;
    }
    
    if (window.hideLoading) {
        window.hideLoading();
    }
    
    console.log('[Activity] Navigation cancelled');
}

// Toggle module collapse directly using Bootstrap's API
function toggleModuleCollapse(moduleId) {
    const collapseElement = document.getElementById(`module-${moduleId}`);
    if (collapseElement) {
        const bsCollapse = new bootstrap.Collapse(collapseElement);
        bsCollapse.toggle();
        return true;
    }
    return false;
}

// Direct handler for activity clicks to ensure they always work
function handleActivityClick(activityId) {
    const url = `view_activity.php?id=${activityId}`;
    console.log(`[Activity] Direct handler called for ID: ${activityId}`);
    
    // Show loading with a special class to indicate direct navigation
    if (window.showLoading) {
        window.showLoading('Loading activity...');
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('direct-navigation');
        }
    }
    
    // Use a more robust navigation approach
    try {
        // First attempt: standard navigation
        setTimeout(() => {
            window.location.href = url;
        }, 100);
        
        // Backup approach if the first one fails
        setTimeout(() => {
            if (window.location.href.indexOf('view_activity.php') === -1) {
                console.log('[Activity] Fallback navigation triggered');
                window.location.replace(url);
            }
        }, 500);
    } catch (e) {
        // Last resort emergency navigation
        console.error('[Activity] Navigation error:', e);
        window.location = url;
    }
}

// Add the functions.js to the header.php file before script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Functions] LabSpace functions loaded');
    
    // Add direct click handlers to all activity items with improved reliability
    document.querySelectorAll('.activity-item, .list-group-item.activity-link').forEach(activity => {
        // Extract activity ID from various possible sources
        const activityId = activity.dataset.activityId || 
                          activity.querySelector('.activity-id-data')?.dataset.activityId ||
                          (activity.href && activity.href.match(/id=(\d+)/) ? activity.href.match(/id=(\d+)/)[1] : null);
        
        if (activityId) {
            // IMPROVED: Add a more robust click handler with multiple fallbacks
            activity.addEventListener('click', function(e) {
                // Only proceed if not clicking on an existing link or button
                if (e.target.closest('a:not(.activity-link), button, .btn')) {
                    return;
                }
                
                console.log('[Activity] Click detected on activity ID:', activityId);
                
                e.preventDefault();
                e.stopPropagation();
                
                // Show user visual feedback
                this.classList.add('active-click');
                
                // Get the URL from multiple possible sources
                const activityLink = this.querySelector('.activity-link') || 
                                    (this.classList.contains('activity-link') ? this : null);
                                    
                const url = activityLink && activityLink.getAttribute('href') ? 
                          activityLink.getAttribute('href') : 
                          `view_activity.php?id=${activityId}`;
                          
                // Use the improved navigation function
                handleActivityClick(activityId);
            });
            
            // Add fallback direct links to ensure navigation is always possible
            if (!activity.querySelector('.emergency-link') && !activity.classList.contains('emergency-link')) {
                const directLinkWrapper = document.createElement('div');
                directLinkWrapper.className = 'emergency-link-wrapper';
                directLinkWrapper.style.display = 'none'; // Hidden by default
                
                const directLink = document.createElement('a');
                directLink.href = `view_activity.php?id=${activityId}`;
                directLink.className = 'emergency-link btn btn-sm btn-danger';
                directLink.innerHTML = '<i class="fas fa-external-link-alt"></i> Open Activity';
                directLink.dataset.activityId = activityId;
                
                directLinkWrapper.appendChild(directLink);
                activity.appendChild(directLinkWrapper);
            }
        }
    });
    
    // Universal handler for any element with activity-link class
    document.addEventListener('click', function(e) {
        const activityLink = e.target.closest('.activity-link');
        if (activityLink) {
            const activityId = activityLink.dataset.activityId || 
                              activityLink.closest('.activity-item')?.dataset.activityId ||
                              activityLink.closest('[data-activity-id]')?.dataset.activityId ||
                              (activityLink.href && activityLink.href.match(/id=(\d+)/) ? 
                               activityLink.href.match(/id=(\d+)/)[1] : null);
            
            console.log('[Activity] Direct link click detected, ID:', activityId);
            
            if (activityId) {
                e.preventDefault();
                handleActivityClick(activityId);
            } else if (activityLink.href) {
                e.preventDefault();
                showLoading('Loading activity...');
                window.location.href = activityLink.href;
            }
        }
    });
    
    // Avoid adding emergency navigation options multiple times
    const emergencyWrappers = document.querySelectorAll('.emergency-link-wrapper');
    if (emergencyWrappers.length > 0) {
        console.log('[Functions] Emergency navigation options already exist.');
        return;
    }

    // Add emergency navigation options
    setTimeout(() => {
        const activityItems = document.querySelectorAll('.activity-item');
        activityItems.forEach(item => {
            const activityId = item.dataset.activityId;

            // Validate activity ID
            if (!activityId || isNaN(activityId) || activityId <= 0) {
                console.error('[Functions] Invalid activity ID for emergency navigation:', activityId);
                return;
            }

            if (!item.querySelector('.emergency-link')) {
                const emergencyLink = document.createElement('a');
                emergencyLink.href = `view_activity.php?id=${activityId}`;
                emergencyLink.className = 'emergency-link btn btn-sm btn-danger';
                emergencyLink.textContent = 'Emergency Open';
                item.appendChild(emergencyLink);
            }
        });
        console.log('[Activity] Revealing emergency navigation options');
    }, 3000);
});
