<?php
/**
 * Server-side code execution handler
 * This script executes code submitted by students and returns the output
 */
session_start();
require_once 'functions/auth.php';

// Only allow logged-in students to execute code
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if necessary data is available
if (!isset($data['code']) || !isset($data['language'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$code = $data['code'];
$language = $data['language'];

// Function to safely execute PHP code in a controlled environment
function executePhpCode($code) {
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'php_exec_');
    
    // Add PHP opening tag if missing
    if (strpos($code, '<?php') === false) {
        $code = "<?php\n" . $code;
    }
    
    // Some basic security: prevent dangerous functions
    $blockedFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 
                         'unlink', 'rmdir', 'chmod', 'mkdir', 'rename', 'copy', 
                         'file_put_contents', 'fopen', 'file_get_contents', 'include', 
                         'require', 'include_once', 'require_once'];
    
    foreach ($blockedFunctions as $func) {
        // Check if the function is being called
        if (preg_match('/\b' . $func . '\s*\(/i', $code)) {
            return ["error" => "Security error: Use of function '$func' is not allowed."];
        }
    }
    
    // Write code to the temporary file
    file_put_contents($tempFile, $code);
    
    // Capture output
    ob_start();
    
    try {
        // Execute with limited execution time
        set_time_limit(5); // 5 seconds max
        include $tempFile;
        $output = ob_get_clean();
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        return ["output" => $output];
    } catch (Throwable $e) {
        ob_end_clean();
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        return ["error" => "PHP Error: " . $e->getMessage() . " on line " . $e->getLine()];
    }
}

// Execute code based on language
$result = [];

switch ($language) {
    case 'php':
        $result = executePhpCode($code);
        break;
        
    default:
        // For HTML/CSS/JS, we'll let the front-end handle execution in the iframe
        $result = ["error" => "Server-side execution not supported for $language. Use client-side preview."];
        break;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
