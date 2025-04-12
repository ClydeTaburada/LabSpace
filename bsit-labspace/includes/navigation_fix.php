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
