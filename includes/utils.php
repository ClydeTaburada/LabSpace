<?php

/**
 * Returns the appropriate badge color class based on completion percentage
 * 
 * @param int|float $completion The completion percentage (0-100)
 * @return string CSS class name for the badge color
 */
function getCompletionBadgeColor($completion) {
    if ($completion >= 100) {
        return 'bg-success'; // Green for completed (100%)
    } elseif ($completion >= 75) {
        return 'bg-info'; // Blue for mostly done (75-99%)
    } elseif ($completion >= 50) {
        return 'bg-primary'; // Primary color for halfway (50-74%) 
    } elseif ($completion >= 25) {
        return 'bg-warning'; // Yellow for started (25-49%)
    } else {
        return 'bg-danger'; // Red for little progress (0-24%)
    }
}

?>