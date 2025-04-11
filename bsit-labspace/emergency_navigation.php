<?php
/**
 * Emergency Navigation Redirector
 * 
 * This file redirects to the new emergency activity browser
 */

session_start();

// Get any passed activity ID
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Redirect to emergency activity browser with ID if provided
if ($activityId) {
    header("Location: emergency_activity.php?id={$activityId}");
} else {
    header("Location: emergency_activity.php");
}
exit;
?>
