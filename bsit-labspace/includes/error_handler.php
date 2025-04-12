<?php
/**
 * Error handler to ensure JSON responses
 * Include this file in any API endpoint that should always return JSON
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering
ob_start();

// Set custom error handler for PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Convert PHP errors to exceptions
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => true,
            'message' => 'Fatal Error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
    ob_end_flush();
});

/**
 * Function to safely output JSON error response
 */
function outputJsonError($message, $code = 500) {
    ob_clean();
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $message,
        'code' => $code
    ]);
    exit;
}

/**
 * Function to safely output JSON success response
 */
function outputJsonSuccess($data = []) {
    ob_clean();
    $response = array_merge(['success' => true], $data);
    echo json_encode($response);
    exit;
}
?>
