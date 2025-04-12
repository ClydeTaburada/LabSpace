<?php
/**
 * Error handler for activity submissions
 * Provides better error reporting and recovery options
 */

/**
 * Handle errors that might occur during submission
 *
 * @param Exception $error The caught exception
 * @param int|null $activityId The activity ID if available
 * @return array Formatted error response
 */
function handleSubmissionError($error, $activityId = null) {
    // Log the error for server-side debugging
    error_log('Submission error: ' . $error->getMessage() . ' in ' . $error->getFile() . ' on line ' . $error->getLine());
    
    // Format a user-friendly error message
    return [
        'success' => false,
        'error' => true,
        'message' => 'Error processing submission: ' . $error->getMessage(),
        'activity_id' => $activityId,
        'code' => 500,
        'error_details' => [
            'file' => basename($error->getFile()),
            'line' => $error->getLine()
        ]
    ];
}

/**
 * Create an error response with JSON format
 *
 * @param Exception $e The caught exception
 * @param int|null $activityId The activity ID if available
 */
function outputErrorResponse($e, $activityId = null) {
    // Ensure we're sending JSON
    header('Content-Type: application/json');
    
    // Get formatted error response
    $response = handleSubmissionError($e, $activityId);
    
    // Output as JSON
    echo json_encode($response);
    exit;
}
