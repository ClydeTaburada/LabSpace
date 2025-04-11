<?php
/**
 * Navigation Fix Helper
 * 
 * This file contains functions and utilities to help diagnose and fix navigation issues
 * with modules and activities in the BSIT LabSpace.
 */

/**
 * Add debug classes to module links for easier identification
 * 
 * @param string $url The URL to check
 * @param string $linkText The text of the link
 * @return string HTML attributes to add to the link
 */
function getModuleLinkAttributes($url, $linkText = '') {
    $attributes = 'class="module-link" data-loading="true" data-link-text="' . htmlspecialchars($linkText) . '"';
    return $attributes;
}

/**
 * Add debug classes to activity links for easier identification
 * 
 * @param string $url The URL to check
 * @param string $activityId The ID of the activity
 * @return string HTML attributes to add to the link
 */
function getActivityLinkAttributes($url, $activityId = '') {
    $attributes = 'class="activity-link" data-loading="true" data-activity-id="' . $activityId . '"';
    return $attributes;
}

/**
 * Direct navigation redirect for emergency use
 * Adds JavaScript to force navigation if a user is stuck
 */
function addEmergencyNavigation() {
    // Only add if this is not an AJAX request
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        echo '<script>
        // Check if this page was supposed to navigate somewhere else
        document.addEventListener("DOMContentLoaded", function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has("destination")) {
                const destination = params.get("destination");
                console.log("[Navigation Fix] Redirecting to: " + destination);
                window.location.href = destination;
            }
            
            // Create universal emergency button
            const emergencyButton = document.createElement("button");
            emergencyButton.innerHTML = "<i class=\"fas fa-exclamation-triangle\"></i> Emergency";
            emergencyButton.className = "btn btn-danger position-fixed";
            emergencyButton.style.bottom = "20px";
            emergencyButton.style.left = "20px";
            emergencyButton.style.zIndex = "9999";
            emergencyButton.onclick = function() {
                // Get last activity ID if available
                let lastActivityId = "";
                if (sessionStorage.getItem("last_activity_id")) {
                    lastActivityId = sessionStorage.getItem("last_activity_id");
                } else if (localStorage.getItem("last_activity_id")) {
                    lastActivityId = localStorage.getItem("last_activity_id");
                }
                
                if (lastActivityId) {
                    window.location.href = "../emergency_activity.php?id=" + lastActivityId;
                } else {
                    window.location.href = "../emergency_activity.php";
                }
            };
            document.body.appendChild(emergencyButton);
        });
        </script>';
    }
}

/**
 * Add emergency navigation buttons to all activity items
 * This ensures users can always navigate even if the regular click handlers fail
 */
function addEmergencyActivityNavigation() {
    echo '<script>
    // Add emergency navigation to all activity items
    document.addEventListener("DOMContentLoaded", function() {
        const activityItems = document.querySelectorAll(".activity-item, [data-activity-id]");
        activityItems.forEach(item => {
            const activityId = item.dataset.activityId;
            if (!activityId) return;
            
            // Create emergency button
            const navButton = document.createElement("a");
            navButton.className = "btn btn-sm btn-danger activity-emergency-btn position-absolute";
            navButton.innerHTML = "<i class=\"fas fa-external-link-alt\"></i>";
            navButton.href = "../emergency_activity.php?id=" + activityId;
            navButton.style.right = "10px";
            navButton.style.top = "50%";
            navButton.style.transform = "translateY(-50%)";
            navButton.style.zIndex = "100";
            navButton.title = "Emergency Access to Activity #" + activityId;
            
            // Make parent relative positioned if not already
            if (window.getComputedStyle(item).position === "static") {
                item.style.position = "relative";
            }
            
            // Only append if the item exists in the DOM
            if (item && item.appendChild) {
                item.appendChild(navButton);
                
                try {
                    // Store activity ID in session/local storage for emergency recovery
                    if (window.sessionStorage && window.localStorage) {
                        sessionStorage.setItem("last_activity_id", activityId);
                        localStorage.setItem("last_activity_id", activityId);
                        
                        // Add activity to history array
                        let activityHistory = [];
                        try {
                            const storedHistory = localStorage.getItem("activity_history");
                            if (storedHistory) {
                                try {
                                    activityHistory = JSON.parse(storedHistory);
                                    if (!Array.isArray(activityHistory)) {
                                        console.warn("[Navigation Fix] Stored history is not an array, resetting");
                                        activityHistory = [];
                                    }
                                } catch (parseError) {
                                    console.error("[Navigation Fix] Failed to parse activity history:", parseError);
                                    activityHistory = [];
                                }
                            }
                            
                            // Ensure activityId is properly compared (string vs number handling)
                            const activityIdStr = String(activityId);
                            activityHistory = activityHistory.filter(id => String(id) !== activityIdStr);
                            
                            // Add current activity to the beginning
                            activityHistory.unshift(activityId);
                            
                            // Limit array size
                            if (activityHistory.length > 10) {
                                activityHistory = activityHistory.slice(0, 10);
                            }
                            
                            localStorage.setItem("activity_history", JSON.stringify(activityHistory));
                        } catch (e) {
                            console.error("[Navigation Fix] History storage error:", e);
                        }
                    }
                } catch (storageError) {
                    console.warn("[Navigation Fix] LocalStorage access error:", storageError);
                }
            } else {
                console.warn("[Navigation Fix] Could not append emergency button to activity item");
            }
        });
    });
    </script>';
}

/**
 * Special function to properly construct module links
 * 
 * @param array $module Module data
 * @return string Properly constructed HTML link
 */
function createModuleLink($module) {
    $url = '../student/view_module.php?id=' . $module['id'];
    $attributes = getModuleLinkAttributes($url, $module['title']);
    
    return '<a href="' . $url . '" ' . $attributes . '>' . 
           htmlspecialchars($module['title']) . '</a>';
}

/**
 * Special function to properly construct activity links
 * 
 * @param array $activity Activity data
 * @return string Properly constructed HTML link
 */
function createActivityLink($activity) {
    $url = '../student/view_activity.php?id=' . $activity['id'];
    $attributes = getActivityLinkAttributes($url, $activity['id']);
    
    return '<a href="' . $url . '" ' . $attributes . '>' . 
           htmlspecialchars($activity['title']) . '</a>';
}

/**
 * Add direct navigation elements to all activity items on the page
 */
function addDirectNavigationLinks() {
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Find all activity links
        const activityLinks = document.querySelectorAll(".activity-link, [data-activity-id]");
        
        activityLinks.forEach(link => {
            // Get activity ID from multiple possible sources
            const activityId = link.dataset.activityId || 
                               link.querySelector("[data-activity-id]")?.dataset.activityId ||
                               (link.href && link.href.match(/id=(\\d+)/) ? link.href.match(/id=(\\d+)/)[1] : null);
            
            if (activityId) {
                // Make sure clicking always works
                link.addEventListener("click", function(e) {
                    if (e.target.closest("a:not(.activity-link), button")) {
                        return;
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log("Activity direct navigation: " + activityId);
                    if (window.showLoading) {
                        window.showLoading("Loading activity...");
                    }
                    
                    // Force navigation
                    setTimeout(() => {
                        window.location.href = "view_activity.php?id=" + activityId;
                    }, 100);
                });
                
                // Add an invisible direct link as backup
                const directLink = document.createElement("a");
                directLink.href = "view_activity.php?id=" + activityId;
                directLink.className = "direct-activity-link";
                directLink.style.position = "absolute";
                directLink.style.opacity = "0";
                directLink.style.pointerEvents = "none";
                directLink.dataset.activityId = activityId;
                link.appendChild(directLink);
            }
        });
        
        // Add extra navigation helper
        window.navigateToActivityDirect = function(activityId) {
            if (window.showLoading) {
                window.showLoading("Loading activity...");
            }
            window.location.href = "view_activity.php?id=" + activityId;
        };
    });
    </script>';
}

/**
 * Insert a direct navigation button for activities that is always visible
 * Use this as a last resort when normal navigation is not working
 */
function addEmergencyNavigationButton() {
    echo '<div id="emergency-nav-container" style="position: fixed; bottom: 20px; left: 20px; z-index: 9999; display: none;">
        <button id="emergency-nav-toggle" class="btn btn-danger">
            <i class="fas fa-exclamation-triangle"></i> Navigation Help
        </button>
        <div id="emergency-nav-panel" style="display: none; background: rgba(0,0,0,0.9); color: white; padding: 15px; margin-top: 10px; border-radius: 5px;">
            <h5>Activity Navigation</h5>
            <p>Enter activity ID to navigate directly:</p>
            <div class="input-group mb-3">
                <input type="number" id="emergency-activity-id" class="form-control" placeholder="Activity ID">
                <button id="emergency-nav-go" class="btn btn-danger">Go</button>
            </div>
            <button id="emergency-nav-close" class="btn btn-sm btn-secondary">Close</button>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Show emergency panel after a delay
        setTimeout(() => {
            document.getElementById("emergency-nav-container").style.display = "block";
        }, 5000);
        
        // Toggle panel visibility
        document.getElementById("emergency-nav-toggle").addEventListener("click", function() {
            const panel = document.getElementById("emergency-nav-panel");
            panel.style.display = panel.style.display === "none" ? "block" : "none";
        });
        
        // Close button
        document.getElementById("emergency-nav-close").addEventListener("click", function() {
            document.getElementById("emergency-nav-panel").style.display = "none";
        });
        
        // Navigation button
        document.getElementById("emergency-nav-go").addEventListener("click", function() {
            const activityId = document.getElementById("emergency-activity-id").value;
            if (activityId) {
                window.location.href = "view_activity.php?id=" + activityId;
            }
        });
        
        // Also allow Enter key
        document.getElementById("emergency-activity-id").addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                document.getElementById("emergency-nav-go").click();
            }
        });
    });
    </script>';
}
