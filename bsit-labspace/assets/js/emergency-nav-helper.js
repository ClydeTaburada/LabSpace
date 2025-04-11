/**
 * Emergency Navigation Helper
 * Provides enhanced fallback navigation mechanisms for the LabSpace system
 */

(function() {
    console.log('[Emergency Nav] Initializing helper system...');
    
    // Store for activity information
    window.emergencyNavData = {
        lastAccessedActivities: [],
        maxStoredActivities: 10
    };
    
    // Initialize when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Create emergency button that appears after delay
        createEmergencyButton();
        
        // Add keyboard shortcuts for emergency navigation
        setupKeyboardShortcuts();
        
        // Monitor all activity links to add emergency fallbacks
        monitorActivityLinks();
        
        // Recover from local storage if available
        loadActivityDataFromStorage();
        
        console.log('[Emergency Nav] Helper system initialized');
    });
    
    // Create a floating emergency button that appears after a delay
    function createEmergencyButton() {
        setTimeout(() => {
            // Only create if it doesn't already exist
            if (document.getElementById('emergency-nav-button')) return;
            
            const button = document.createElement('button');
            button.id = 'emergency-nav-button';
            button.className = 'btn btn-danger';
            button.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Emergency';
            button.style.position = 'fixed';
            button.style.bottom = '20px';
            button.style.left = '20px';
            button.style.zIndex = '9999';
            button.style.borderRadius = '50px';
            button.style.padding = '10px 15px';
            button.style.boxShadow = '0 4px 10px rgba(0,0,0,0.3)';
            
            // Add click handler
            button.addEventListener('click', function() {
                navigateToEmergencySystem();
            });
            
            document.body.appendChild(button);
            
            console.log('[Emergency Nav] Emergency button added to the page');
        }, 5000); // Show after 5 seconds
    }
    
    // Set up keyboard shortcuts for emergency navigation
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Alt+E for emergency navigation
            if (e.altKey && e.key.toLowerCase() === 'e') {
                navigateToEmergencySystem();
            }
            
            // Alt+A for activity direct input
            if (e.altKey && e.key.toLowerCase() === 'a') {
                promptForActivityId();
            }
        });
        
        console.log('[Emergency Nav] Keyboard shortcuts registered: Alt+E, Alt+A');
    }
    
    // Monitor activity links and add fallback mechanisms
    function monitorActivityLinks() {
        // Find all links that might be activity links
        const activityLinks = document.querySelectorAll('a[href*="view_activity.php"], a[href*="activity_id="], [data-activity-id]');
        
        activityLinks.forEach(link => {
            // Add click handler to store activity ID
            link.addEventListener('click', function(e) {
                // Extract activity ID from various sources
                let activityId = null;
                
                // From data attribute
                if (link.dataset.activityId) {
                    activityId = link.dataset.activityId;
                }
                // From URL
                else if (link.href) {
                    // Check for view_activity.php?id=X pattern
                    const idMatch = link.href.match(/view_activity\.php\?id=(\d+)/);
                    if (idMatch && idMatch[1]) {
                        activityId = idMatch[1];
                    }
                    // Check for activity_id=X pattern
                    const activityIdMatch = link.href.match(/activity_id=(\d+)/);
                    if (activityIdMatch && activityIdMatch[1]) {
                        activityId = activityIdMatch[1];
                    }
                }
                
                if (activityId) {
                    console.log('[Emergency Nav] Activity link clicked, ID:', activityId);
                    storeActivityId(activityId);
                }
            });
            
            console.log('[Emergency Nav] Monitoring', activityLinks.length, 'activity links');
        });
    }
    
    // Store activity ID in session/local storage and internal memory
    function storeActivityId(activityId) {
        if (!activityId) return;
        
        try {
            // Store in localStorage for persistence across sessions
            localStorage.setItem('last_activity_id', activityId);
            
            // Store in sessionStorage for current session
            sessionStorage.setItem('last_activity_id', activityId);
            
            // Store in internal data structure (keep a history)
            if (!window.emergencyNavData.lastAccessedActivities.includes(activityId)) {
                window.emergencyNavData.lastAccessedActivities.unshift(activityId);
                // Limit the size of the history
                if (window.emergencyNavData.lastAccessedActivities.length > window.emergencyNavData.maxStoredActivities) {
                    window.emergencyNavData.lastAccessedActivities.pop();
                }
            }
            
            // Store the updated array
            localStorage.setItem('activity_history', JSON.stringify(window.emergencyNavData.lastAccessedActivities));
            
            console.log('[Emergency Nav] Stored activity ID:', activityId);
        } catch (e) {
            console.error('[Emergency Nav] Error storing activity ID:', e);
        }
    }
    
    // Load activity data from storage
    function loadActivityDataFromStorage() {
        try {
            // Load last activity ID
            const lastActivityId = localStorage.getItem('last_activity_id') || 
                                   sessionStorage.getItem('last_activity_id');
            
            // Load activity history
            const activityHistory = localStorage.getItem('activity_history');
            if (activityHistory) {
                window.emergencyNavData.lastAccessedActivities = JSON.parse(activityHistory);
            }
            
            if (lastActivityId && !window.emergencyNavData.lastAccessedActivities.includes(lastActivityId)) {
                window.emergencyNavData.lastAccessedActivities.unshift(lastActivityId);
            }
            
            console.log('[Emergency Nav] Loaded activity data from storage, last ID:', lastActivityId);
        } catch (e) {
            console.error('[Emergency Nav] Error loading activity data:', e);
        }
    }
    
    // Navigate to emergency system
    function navigateToEmergencySystem() {
        console.log('[Emergency Nav] Navigating to emergency system');
        
        const base = getBaseUrl();
        const lastActivityId = localStorage.getItem('last_activity_id') || 
                               sessionStorage.getItem('last_activity_id');
        
        let url = base + 'emergency_navigation.php';
        if (lastActivityId) {
            url += '?id=' + lastActivityId;
        }
        
        window.location.href = url;
    }
    
    // Prompt user for activity ID
    function promptForActivityId() {
        const activityId = prompt('Enter Activity ID to access directly:');
        if (activityId && !isNaN(activityId)) {
            const base = getBaseUrl();
            window.location.href = base + 'direct_activity.php?id=' + activityId;
        }
    }
    
    // Helper to get base URL
    function getBaseUrl() {
        const pathParts = window.location.pathname.split('/');
        let baseUrl = '';
        
        // Check if we're in a subdirectory
        if (pathParts.includes('student') || pathParts.includes('teacher') || pathParts.includes('admin')) {
            baseUrl = '../';
        }
        
        return baseUrl;
    }
})();
