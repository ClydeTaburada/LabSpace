<?php
session_start();
require_once 'functions/auth.php';

// Only allow logged-in students to run tests
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Check if necessary data is available
if (!isset($data['code']) || !isset($data['test_cases']) || !isset($data['language'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$code = $data['code'];
$testCases = $data['test_cases'];
$language = $data['language'];

// Load TestRunner
require_once '../tests/TestRunner.php';

// Run the tests
$result = \LabSpace\Tests\TestRunner::runTests($code, json_encode($testCases), $language);

// If the summary doesn't include suggestions, add them
if (!isset($result['summary']['suggestions'])) {
    $suggestions = [];
    
    // Get suggestions from failed tests
    if (isset($result['results'])) {
        foreach ($result['results'] as $test) {
            if (!$test['passed'] && !empty($test['message'])) {
                $suggestions[] = $test['message'];
            }
        }
    }
    
    // Add a generic suggestion if none found
    if (empty($suggestions)) {
        $suggestions[] = "Review the requirements and test cases to improve your solution.";
    }
    
    // Add the suggestions to the result
    if (!isset($result['summary'])) {
        $result['summary'] = [];
    }
    $result['summary']['suggestions'] = $suggestions;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
