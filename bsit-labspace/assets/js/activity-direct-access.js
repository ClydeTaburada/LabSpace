/**
 * Activity Direct Access
 * This script provides direct activity access functionality with an improved UI
 */

(function() {
    // Set up a simple global function for direct activity access
    window.goToActivity = function(activityId) {
        // When called without a parameter, it might be from an incorrect call
        if (!activityId) {
            // Try to extract activity ID from event or element if available
            if (window.event && window.event.currentTarget) {
                const element = window.event.currentTarget;
                activityId = element.dataset.activityId || 
                            element.getAttribute('href')?.match(/id=(\d+)/)?.[1] ||
                            element.querySelector('[data-activity-id]')?.dataset.activityId;
            }
            
            // If still no activity ID and it's called from the direct input form
            if (!activityId && document.getElementById('direct-activity-input')) {
                activityId = document.getElementById('direct-activity-input').value;
                if (!activityId) {
                    // Remove the alert to prevent unwanted prompts
                    console.error("No activity ID provided in input field");
                    return;
                }
            }
            
            // If we still don't have an activity ID, exit silently
            if (!activityId) {
                console.error("No activity ID provided");
                return;
            }
        }
        
        console.log("Direct navigation to activity ID: " + activityId);
        
        // Store ID in storage for recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
        } catch (e) {
            console.error("Storage error:", e);
        }
        
        // Determine user type from URL
        const isStudent = window.location.href.includes('/student/');
        const isTeacher = window.location.href.includes('/teacher/');
        
        // Build URL based on user type
        let url;
        if (isStudent) {
            url = "view_activity.php?id=" + activityId + "&direct=1";
        } else if (isTeacher) {
            url = "edit_activity.php?id=" + activityId + "&direct=1";
        } else {
            // Fallback to direct activity viewer
            url = "../direct_activity.php?id=" + activityId;
        }
        
        // Navigate with immediate redirect
        window.location.href = url;
    };
    
    // Function to get current URL parameters
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
    
    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        // Add improved direct access form to pages
        const container = document.createElement('div');
        container.className = 'position-fixed';
        container.style.top = '70px';
        container.style.right = '20px';
        container.style.zIndex = '1000';
        container.innerHTML = `
            <div class="direct-activity-access shadow" style="display: none; background: white; padding: 15px; border-radius: 8px; width: 280px;">
                <h6 class="mb-3"><i class="fas fa-bolt text-primary me-2"></i>Direct Activity Access</h6>
                <div class="input-group mb-2">
                    <input type="number" id="direct-activity-input" class="form-control" placeholder="Enter Activity ID">
                    <button class="btn btn-primary" onclick="goToActivity(document.getElementById('direct-activity-input').value)">Go</button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Press Alt+A for quick access</small>
                    <button class="btn btn-sm btn-light direct-access-close">Close</button>
                </div>
            </div>
            <button class="btn btn-primary direct-access-toggle shadow" title="Quick Activity Access">
                <i class="fas fa-bolt"></i>
            </button>
        `;
        
        document.body.appendChild(container);
        
        // Toggle direct access form with improved animations
        const toggleButton = container.querySelector('.direct-access-toggle');
        const closeButton = container.querySelector('.direct-access-close');
        const accessForm = container.querySelector('.direct-activity-access');
        
        toggleButton.addEventListener('click', function() {
            if (accessForm.style.display === 'none') {
                accessForm.style.display = 'block';
                setTimeout(() => {
                    accessForm.style.opacity = '1';
                    accessForm.style.transform = 'translateY(0)';
                }, 10);
                document.getElementById('direct-activity-input').focus();
            } else {
                closeAccessForm();
            }
        });
        
        closeButton.addEventListener('click', closeAccessForm);
        
        function closeAccessForm() {
            accessForm.style.opacity = '0';
            accessForm.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                accessForm.style.display = 'none';
            }, 300);
        }
        
        // Style the form with CSS
        const style = document.createElement('style');
        style.textContent = `
            .direct-activity-access {
                transition: opacity 0.3s ease, transform 0.3s ease;
                opacity: 0;
                transform: translateY(-10px);
                box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            }
            .direct-access-toggle {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .direct-access-toggle:hover {
                transform: scale(1.1);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .direct-access-toggle {
                transition: transform 0.2s, box-shadow 0.2s;
            }
        `;
        document.head.appendChild(style);
        
        // Add keyboard shortcut Alt+A
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key.toLowerCase() === 'a') {
                if (accessForm.style.display === 'none') {
                    accessForm.style.display = 'block';
                    setTimeout(() => {
                        accessForm.style.opacity = '1';
                        accessForm.style.transform = 'translateY(0)';
                    }, 10);
                    document.getElementById('direct-activity-input').focus();
                } else {
                    closeAccessForm();
                }
            }
        });
        
        // Listen for Enter key in input
        document.getElementById('direct-activity-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                goToActivity(this.value);
            }
        });
        
        // Auto-navigate if direct=1 and id parameter exists
        if (getUrlParameter('direct') === '1' && getUrlParameter('id')) {
            console.log("Auto-navigation detected");
            // Store the ID
            try {
                localStorage.setItem('last_activity_id', getUrlParameter('id'));
                sessionStorage.setItem('last_activity_id', getUrlParameter('id'));
            } catch (e) {
                console.error("Storage error:", e);
            }
        }
    });
})();
