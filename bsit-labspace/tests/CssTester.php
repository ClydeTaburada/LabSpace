<?php
namespace LabSpace\Tests;

require_once __DIR__ . '/TestBase.php';

/**
 * CSS Tester
 * Validates CSS code by parsing rules and properties
 */
class CssTester extends TestBase {
    /**
     * Run a single CSS test case
     * 
     * @param array $testCase Test case details
     * @param int $index Test index
     * @return array Test result
     */
    protected function runSingleTest($testCase, $index) {
        $name = $testCase['name'] ?? "CSS Test " . ($index + 1);
        $selector = $testCase['selector'] ?? null;
        $property = $testCase['property'] ?? null;
        $value = $testCase['value'] ?? null;
        $contains = $testCase['contains'] ?? null;
        $category = $testCase['category'] ?? null;
        $message = $testCase['message'] ?? '';
        
        // Handle special case for @media queries
        if ($selector === '@media') {
            // For media queries, just check if the CSS contains this pattern
            $hasMediaQuery = false;
            
            if ($contains !== null) {
                // Look for a media query with the specified content
                $mediaPattern = '/@media\s+[^{]*' . preg_quote($contains, '/') . '[^{]*{/i';
                $hasMediaQuery = preg_match($mediaPattern, $this->code);
            } else {
                // Just look for any media query
                $hasMediaQuery = preg_match('/@media\s+[^{]*{/i', $this->code);
            }
            
            $passed = $hasMediaQuery;
            $displayMessage = $passed 
                ? ($contains ? "CSS includes media query with '$contains'" : "CSS includes media queries")
                : ($contains ? "CSS should include media query with '$contains'" : "CSS should include media queries");
            
            return $this->formatResult($name, $passed, $displayMessage, $category);
        }
        
        // Parse CSS code
        $cssRules = $this->parseCSS($this->code);
        
        if ($selector !== null && $property !== null) {
            // Find the rule for the selector
            $selectorFound = false;
            $propertyFound = false;
            $actualValue = null;
            
            foreach ($cssRules as $rule) {
                if ($this->selectorMatches($rule['selector'], $selector)) {
                    $selectorFound = true;
                    
                    // Look for the property
                    foreach ($rule['properties'] as $propName => $propValue) {
                        if (strtolower(trim($propName)) === strtolower(trim($property))) {
                            $propertyFound = true;
                            $actualValue = trim($propValue);
                            
                            // Test exact value
                            if ($value !== null) {
                                $passed = strtolower(trim($actualValue)) === strtolower(trim($value));
                                $displayMessage = $passed 
                                    ? "Property '$property' has value '$value'"
                                    : "Expected property '$property' to have value '$value', but got '$actualValue'";
                                if (!empty($message) && !$passed) {
                                    $displayMessage = $message;
                                }
                                
                                return $this->formatResult($name, $passed, $displayMessage, $category);
                            }
                            
                            // Test contains value
                            if ($contains !== null) {
                                $passed = stripos($actualValue, $contains) !== false;
                                $displayMessage = $passed 
                                    ? "Property '$property' contains '$contains'"
                                    : "Expected property '$property' to contain '$contains', but got '$actualValue'";
                                if (!empty($message) && !$passed) {
                                    $displayMessage = $message;
                                }
                                
                                return $this->formatResult($name, $passed, $displayMessage, $category);
                            }
                        }
                    }
                }
            }
            
            if (!$selectorFound) {
                return $this->formatResult($name, false, "Selector '$selector' not found", $category);
            }
            
            if (!$propertyFound) {
                return $this->formatResult($name, false, "Property '$property' not found in selector '$selector'", $category);
            }
        } else if ($selector !== null) {
            // Just check if selector exists
            $selectorFound = false;
            foreach ($cssRules as $rule) {
                if ($this->selectorMatches($rule['selector'], $selector)) {
                    $selectorFound = true;
                    break;
                }
            }
            
            $passed = $selectorFound;
            $displayMessage = $passed 
                ? "Selector '$selector' exists in CSS"
                : "Expected selector '$selector' was not found in CSS";
            if (!empty($message) && !$passed) {
                $displayMessage = $message;
            }
            
            return $this->formatResult($name, $passed, $displayMessage, $category);
        }
        
        // If we reach here, the test case was invalid
        return $this->formatResult($name, false, "Invalid test case definition", $category);
    }
    
    /**
     * Check if a CSS selector matches the test selector
     * Supports simple wildcard matching with * for class selectors
     * 
     * @param string $cssSelector The selector from parsed CSS
     * @param string $testSelector The selector from test case
     * @return bool Whether they match
     */
    private function selectorMatches($cssSelector, $testSelector) {
        // If they're identical, it's a match
        if (trim($cssSelector) === trim($testSelector)) {
            return true;
        }
        
        // Check for wildcard class selectors
        if (strpos($testSelector, '.*') !== false) {
            $pattern = '/^' . str_replace('.*', '\.[a-zA-Z0-9_-]+', preg_quote($testSelector, '/')) . '$/i';
            return preg_match($pattern, trim($cssSelector));
        }
        
        // Case insensitive comparison
        return strtolower(trim($cssSelector)) === strtolower(trim($testSelector));
    }
    
    /**
     * Get CSS-specific code patterns
     * 
     * @return array CSS-specific patterns and messages
     */
    protected function getCommonCodePatterns() {
        $patterns = parent::getCommonCodePatterns();
        
        // Add CSS-specific patterns
        $cssPatterns = [
            '/!important/' => 'Avoid using !important as it breaks the natural cascading of CSS and makes maintenance difficult.',
            '/@import url/' => 'Consider combining CSS files instead of using @import which can slow page loading.',
            '/position\s*:\s*absolute/' => 'Use absolute positioning sparingly as it can make layouts fragile and hard to maintain.',
            '/float\s*:/' => 'Consider using flexbox or grid instead of float for layout.',
            '/#[a-fA-F0-9]{3,6}/' => 'Consider using CSS variables to maintain consistent colors throughout your stylesheet.',
            '/px\s*\)/' => 'Consider using relative units like em or rem for better accessibility and responsiveness.'
        ];
        
        return array_merge($patterns, $cssPatterns);
    }
    
    /**
     * Parse CSS code into rules and properties
     * 
     * @param string $css CSS code
     * @return array Parsed CSS rules
     */
    private function parseCSS($css) {
        $rules = [];
        
        // Remove comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        
        // Handle media queries separately to avoid losing them
        $mediaBlocks = [];
        preg_match_all('/@media[^{]+\{([^}]+\{[^}]+\})+[^}]+\}/s', $css, $mediaMatches);
        
        if (!empty($mediaMatches[0])) {
            foreach ($mediaMatches[0] as $idx => $mediaBlock) {
                // Replace media block with placeholder
                $placeholder = "MEDIA_PLACEHOLDER_$idx";
                $css = str_replace($mediaBlock, $placeholder, $css);
                $mediaBlocks[$placeholder] = $mediaBlock;
                
                // Extract media query info
                if (preg_match('/@media\s+([^{]+)\s*\{/s', $mediaBlock, $mqMatch)) {
                    $mediaQuery = trim($mqMatch[1]);
                    
                    // Extract rules within media query
                    preg_match_all('/([^{]+)\s*{\s*([^}]+)\s*}/s', $mediaBlock, $mqRuleMatches, PREG_SET_ORDER);
                    
                    foreach ($mqRuleMatches as $mqMatch) {
                        if (count($mqMatch) >= 3) {
                            $mqSelector = trim($mqMatch[1]);
                            $mqPropertiesStr = trim($mqMatch[2]);
                            
                            // Skip the outer media query block
                            if ($mqSelector === $mediaQuery) continue;
                            
                            // Parse properties
                            $mqProperties = $this->parseProperties($mqPropertiesStr);
                            
                            // Add to rules with media query context
                            $rules[] = [
                                'selector' => $mqSelector,
                                'properties' => $mqProperties,
                                'media' => $mediaQuery
                            ];
                        }
                    }
                }
            }
        }
        
        // Match all regular CSS rules
        preg_match_all('/([^{]+)\s*{\s*([^}]+)\s*}/s', $css, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            // Check if this is a placeholder for a media block
            $selector = trim($match[1]);
            if (isset($mediaBlocks[$selector])) {
                continue; // Skip placeholders, we've already processed them
            }
            
            $propertiesStr = trim($match[2]);
            $properties = $this->parseProperties($propertiesStr);
            
            $rules[] = [
                'selector' => $selector,
                'properties' => $properties
            ];
        }
        
        return $rules;
    }
    
    /**
     * Parse CSS properties string into key-value pairs
     * 
     * @param string $propertiesStr CSS properties string
     * @return array Parsed properties
     */
    private function parseProperties($propertiesStr) {
        $properties = [];
        $propMatches = explode(';', $propertiesStr);
        
        foreach ($propMatches as $propMatch) {
            $propMatch = trim($propMatch);
            if (empty($propMatch)) continue;
            
            $propParts = explode(':', $propMatch, 2);
            if (count($propParts) === 2) {
                $properties[trim($propParts[0])] = trim($propParts[1]);
            }
        }
        
        return $properties;
    }
}
