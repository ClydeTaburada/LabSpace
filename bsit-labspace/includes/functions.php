/**
 * Verify that an activity is being accessed properly (not via direct URL access)
 * 
 * @param int $activityId The activity ID to verify
 * @return bool True if access is valid, false otherwise
 */
function verifyActivityAccess($activityId) {
    // Always allow access in development environment for testing
    if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
        return true;
    }
    
    // Check for session-based verification
    if (isset($_SESSION['last_activity_id']) && $_SESSION['last_activity_id'] == $activityId) {
        // Check if access was recent (within 30 minutes)
        if (isset($_SESSION['activity_access_time']) && 
            (time() - $_SESSION['activity_access_time'] < 1800)) {
            return true;
        }
    }
    
    // Check for token-based verification
    if (isset($_GET['token']) && isset($_SESSION['activity_access_token']) && 
        $_GET['token'] === $_SESSION['activity_access_token']) {
        return true;
    }
    
    // If emergency mode is enabled, bypass verification
    if (isset($_GET['emergency']) && $_GET['emergency'] == '1') {
        return true;
    }
    
    // If recovered mode is enabled, bypass verification 
    if (isset($_GET['recovered']) && $_GET['recovered'] == '1') {
        return true;
    }
    
    // Check for referrer to allow navigation from legitimate pages
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $allowedPatterns = [
            '/module_activities\.php/',
            '/view_class\.php/',
            '/dashboard\.php/',
            '/edit_module\.php/',
            '/direct_activity\.php/',
            '/direct_activity_fix\.php/',
            '/emergency_activity\.php/'
        ];
        
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $referer)) {
                return true;
            }
        }
    }
    
    // All verification methods failed
    return false;
}
