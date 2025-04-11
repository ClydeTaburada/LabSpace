<?php
/**
 * Emergency Activity Access Script
 * Provides direct access to activities through multiple methods
 */

// Check if we're in emergency access mode
$emergencyMode = isset($_GET['emergency']) && $_GET['emergency'] == '1';

// Add emergency activity navigation script
function addEmergencyActivityAccess() {
    global $emergencyMode;
    
    // Add the emergency scripts
    echo '<script src="' . getBaseUrl() . 'assets/js/activity-reliable-click.js"></script>';
    
    // If in emergency mode, show additional UI
    if ($emergencyMode) {
        echo '<div class="alert alert-warning alert-dismissible fade show emergency-notice">
                <strong><i class="fas fa-exclamation-triangle"></i> Emergency Access Mode:</strong> 
                You are viewing this page in emergency access mode.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    
    // Add emergency access panel that is hidden by default
    echo '<div id="emergency-access-panel" style="display: none; position: fixed; bottom: 70px; right: 20px; z-index: 9999; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.2); max-width: 300px;">
            <h5><i class="fas fa-exclamation-triangle text-danger"></i> Emergency Access</h5>
            <p>If you\'re having trouble accessing activities, use this panel:</p>
            <div class="input-group mb-2">
                <input type="number" id="emergency-activity-id" class="form-control" placeholder="Activity ID">
                <button class="btn btn-danger" onclick="emergencyLoadActivity()">Go</button>
            </div>
            <div class="d-flex justify-content-between">
                <button onclick="document.getElementById(\'emergency-access-panel\').style.display=\'none\'" class="btn btn-sm btn-outline-secondary">Close</button>
                <a href="' . getBaseUrl() . 'emergency_navigation.php" class="btn btn-sm btn-outline-danger">More Options</a>
            </div>
          </div>';
    
    // Add emergency button
    echo '<div style="position: fixed; bottom: 20px; right: 20px; z-index: 9998;">
            <button onclick="toggleEmergencyPanel()" class="btn btn-sm btn-danger" title="Emergency Activity Access">
                <i class="fas fa-exclamation-triangle"></i>
            </button>
          </div>';
    
    // Add scripts for emergency functionality
    echo '<script>
            function toggleEmergencyPanel() {
                const panel = document.getElementById("emergency-access-panel");
                if (panel.style.display === "none") {
                    panel.style.display = "block";
                    document.getElementById("emergency-activity-id").focus();
                } else {
                    panel.style.display = "none";
                }
            }
            
            function emergencyLoadActivity() {
                const activityId = document.getElementById("emergency-activity-id").value;
                if (activityId) {
                    // Store for recovery
                    try {
                        localStorage.setItem("last_activity_id", activityId);
                        sessionStorage.setItem("last_activity_id", activityId);
                    } catch (e) {
                        console.error("Storage error:", e);
                    }
                    
                    // Navigate directly to the activity
                    window.location.href = "' . getBaseUrl() . 'direct_activity.php?id=" + activityId;
                }
            }
            
            // Add keyboard shortcut (Alt+E)
            document.addEventListener("keydown", function(e) {
                if (e.altKey && e.key.toLowerCase() === "e") {
                    toggleEmergencyPanel();
                }
            });
            
            // Auto-check for activity ID in URL
            document.addEventListener("DOMContentLoaded", function() {
                const urlParams = new URLSearchParams(window.location.search);
                const activityId = urlParams.get("id");
                if (activityId) {
                    // Store for recovery
                    try {
                        localStorage.setItem("last_activity_id", activityId);
                        sessionStorage.setItem("last_activity_id", activityId);
                    } catch (e) {
                        console.error("Storage error:", e);
                    }
                }
            });
          </script>';
}

// Call the function to add emergency access
addEmergencyActivityAccess();
?>
