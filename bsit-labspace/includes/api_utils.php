<?php
/**
 * Utility functions for API responses
 */

/**
 * Send a standardized JSON API response
 *
 * @param bool $success Whether the request was successful
 * @param string $message Message to include in the response
 * @param array $data Additional data to include in the response
 * @param int $statusCode HTTP status code to send
 */
function sendApiResponse($success, $message = '', $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    // Add additional data if provided
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    // Set content type and output JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Send an error response with appropriate status code
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code (default: 400 Bad Request)
 * @param array $data Additional data to include in the response
 */
function sendApiError($message, $statusCode = 400, $data = []) {
    sendApiResponse(false, $message, $data, $statusCode);
}

/**
 * Send a success response
 *
 * @param string $message Success message
 * @param array $data Additional data to include in the response
 */
function sendApiSuccess($message, $data = []) {
    sendApiResponse(true, $message, $data, 200);
}

/**
 * Validate required API parameters
 *
 * @param array $required Array of required parameter names
 * @param array $data Data to validate
 * @return bool True if all required parameters are present, false otherwise
 */
function validateApiParams($required, $data) {
    foreach ($required as $param) {
        if (!isset($data[$param]) || (is_string($data[$param]) && trim($data[$param]) === '')) {
            return false;
        }
    }
    return true;
}
?>
