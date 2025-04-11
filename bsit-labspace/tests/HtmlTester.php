<?php
namespace LabSpace\Tests;

require_once __DIR__ . '/TestBase.php';

/**
 * HTML Tester
 * Tests HTML code structure using DOM parsing and selectors
 */
class HtmlTester extends TestBase {
    /**
     * Run a single HTML test case
     * 
     * @param array $testCase Test case details
     * @param int $index Test index
     * @return array Test result
     */
    protected function runSingleTest($testCase, $index) {
        $name = $testCase['name'] ?? "HTML Test " . ($index + 1);
        $selector = $testCase['selector'] ?? null;
        $attribute = $testCase['attribute'] ?? null;
        $contains = $testCase['contains'] ?? null;
        $count = $testCase['count'] ?? null;
        $exists = $testCase['exists'] ?? null;
        $category = $testCase['category'] ?? null;
        $exact = $testCase['exact'] ?? null;
        $attrExists = $testCase['attribute_exists'] ?? null;
        $message = $testCase['message'] ?? '';
        
        // Load the DOM
        $dom = new \DOMDocument();
        
        // Suppress HTML5 errors
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->code);
        libxml_clear_errors();
        
        // Create XPath for querying
        $xpath = new \DOMXPath($dom);
        
        // Process the test based on its type
        if ($selector !== null) {
            // Query using selector (convert CSS to XPath if needed)
            $elements = $xpath->query($this->cssToXPath($selector));
            
            // Test if attribute exists
            if ($attrExists !== null) {
                if ($elements->length === 0) {
                    return $this->formatResult($name, false, "No elements found matching '$selector'", $category);
                }
                
                // Get the first matching element
                $element = $elements->item(0);
                
                // Check if element is a DOMElement before calling getAttribute
                if (!($element instanceof \DOMElement)) {
                    return $this->formatResult($name, false, "Found node is not a DOM Element and has no attributes", $category);
                }
                
                $hasAttr = $element->hasAttribute($attrExists);
                $passed = $hasAttr;
                $message = $passed
                    ? "Element has required attribute '$attrExists'"
                    : "Element should have the attribute '$attrExists'";
                
                return $this->formatResult($name, $passed, $message, $category);
            }
            
            // Test element existence
            if ($exists !== null) {
                $hasElements = $elements->length > 0;
                $passed = ($exists && $hasElements) || (!$exists && !$hasElements);
                $message = $passed 
                    ? ($exists ? "Found element matching '$selector'" : "No element matches '$selector'")
                    : ($exists ? "Expected to find element matching '$selector', but none found" : "Expected not to find element matching '$selector', but found it");
                
                return $this->formatResult($name, $passed, $message, $category);
            }
            
            // Test element count
            if ($count !== null) {
                $passed = $elements->length === $count;
                $message = $passed 
                    ? "Found expected $count elements matching '$selector'"
                    : "Expected $count elements matching '$selector', but found {$elements->length}";
                
                return $this->formatResult($name, $passed, $message, $category);
            }
            
            // Test element attribute
            if ($attribute !== null && ($contains !== null || $exact !== null)) {
                if ($elements->length === 0) {
                    return $this->formatResult($name, false, "No elements found matching '$selector'", $category);
                }
                
                // Get the first matching element
                $element = $elements->item(0);
                
                // Check if element is a DOMElement before calling getAttribute
                if (!($element instanceof \DOMElement)) {
                    return $this->formatResult($name, false, "Found node is not a DOM Element and has no attributes", $category);
                }
                
                $attributeValue = $element->getAttribute($attribute);
                
                // Check for exact match if specified
                if ($exact !== null) {
                    $passed = $attributeValue === $exact;
                    $message = $passed
                        ? "Element has attribute '$attribute' with value '$exact'"
                        : "Expected attribute '$attribute' to be exactly '$exact', but got '$attributeValue'";
                    
                    return $this->formatResult($name, $passed, $message, $category);
                }
                
                // Otherwise check for contains match
                $passed = strpos($attributeValue, $contains) !== false;
                $message = $passed
                    ? "Element has attribute '$attribute' containing '$contains'"
                    : "Expected attribute '$attribute' to contain '$contains', but got '$attributeValue'";
                
                return $this->formatResult($name, $passed, $message, $category);
            }
            
            // Test element content
            if ($contains !== null) {
                if ($elements->length === 0) {
                    return $this->formatResult($name, false, "No elements found matching '$selector'", $category);
                }
                
                // Get the first matching element
                $element = $elements->item(0);
                $content = $element->textContent;
                
                $passed = strpos($content, $contains) !== false;
                $message = $passed
                    ? "Element contains text '$contains'"
                    : "Expected element to contain '$contains', but got '$content'";
                
                return $this->formatResult($name, $passed, $message, $category);
            } elseif ($exact !== null) {
                if ($elements->length === 0) {
                    return $this->formatResult($name, false, "No elements found matching '$selector'", $category);
                }
                
                // Get the first matching element
                $element = $elements->item(0);
                $content = $element->textContent;
                
                $passed = trim($content) === trim($exact);
                $message = $passed
                    ? "Element has exact text '$exact'"
                    : "Expected element text to be exactly '$exact', but got '$content'";
                
                return $this->formatResult($name, $passed, $message, $category);
            }
        }
        
        // If we reach here, the test case was invalid
        return $this->formatResult($name, false, "Invalid test case definition", $category);
    }
    
    /**
     * Get language-specific code patterns for HTML
     * 
     * @return array HTML-specific patterns and messages
     */
    protected function getCommonCodePatterns() {
        $patterns = parent::getCommonCodePatterns();
        
        // Add HTML-specific patterns
        $htmlPatterns = [
            '/<img[^>]+(?!alt=)[^>]*>/' => 'All images should have alt attributes for accessibility.',
            '/<a[^>]+(?!href=)[^>]*>/' => 'Links should have href attributes.',
            '/align=|bgcolor=/' => 'Avoid using deprecated HTML attributes like align and bgcolor; use CSS instead.',
            '/<table[^>]*>(?!.*<th)/' => 'Tables should include header elements (th) for better accessibility.',
            '/<center>/' => 'The center tag is deprecated; use CSS for centering content.',
            '/<font/' => 'The font tag is deprecated; use CSS for text styling.',
            '/<b>|<i>/' => 'Consider using semantic HTML elements like strong and em instead of b and i tags.',
            '/<br>/' => 'Use self-closing tags with a space: <br /> for better compatibility.'
        ];
        
        return array_merge($patterns, $htmlPatterns);
    }
    
    /**
     * Convert a simple CSS selector to XPath
     * Note: Only supports basic selectors, for complex selectors use a library
     * 
     * @param string $cssSelector CSS selector
     * @return string XPath equivalent
     */
    private function cssToXPath($cssSelector) {
        // Special case for @media rules in CSS
        if ($cssSelector === '@media') {
            // This is a placeholder for CSS media queries - not directly selectable in DOM
            // Just returning a basic XPath that will find <style> tags containing media queries
            return "//style[contains(., '@media')]";
        }
        
        // Simple ID selector
        if (strpos($cssSelector, '#') === 0) {
            return "//*[@id='" . substr($cssSelector, 1) . "']";
        }
        
        // Simple class selector
        if (strpos($cssSelector, '.') === 0) {
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . substr($cssSelector, 1) . " ')]";
        }
        
        // Attribute selectors [attr=value]
        if (preg_match('/^([a-z0-9]+)\[([a-z0-9\-\@\_\:\.]+)=[\'"](.*)[\'"]/', $cssSelector, $matches)) {
            $element = $matches[1];
            $attribute = $matches[2];
            $value = $matches[3];
            // Handle values containing quotes by properly escaping them
            $value = str_replace("'", "\'", $value);
            return "//{$element}[@{$attribute}='{$value}']";
        }
        
        // Attribute selectors [attr]
        if (preg_match('/^([a-z0-9]+)\[([a-z0-9\-\@\_\:\.]+)\]/', $cssSelector, $matches)) {
            $element = $matches[1];
            $attribute = $matches[2];
            // Simple attribute existence check using curly braces for safer variable interpolation
            return "//{$element}[@{$attribute}]";
        }
        
        // Child selector (direct child)
        if (strpos($cssSelector, ' > ') !== false) {
            $parts = explode(' > ', $cssSelector);
            $xpath = '';
            foreach ($parts as $i => $part) {
                $partXpath = $this->cssToXPath($part);
                if ($i === 0) {
                    $xpath = $partXpath;
                } else {
                    // Remove the leading '//' from subsequent parts and add '/'
                    $xpath .= '/' . substr($partXpath, 2);
                }
            }
            return $xpath;
        }
        
        // Descendant selector (any level)
        if (strpos($cssSelector, ' ') !== false) {
            $parts = explode(' ', $cssSelector);
            $xpath = '';
            foreach ($parts as $i => $part) {
                if (trim($part) === '') continue;
                $partXpath = $this->cssToXPath($part);
                if ($i === 0) {
                    $xpath = $partXpath;
                } else {
                    // Remove the leading '//' from subsequent parts and add '//'
                    $xpath .= '//' . substr($partXpath, 2);
                }
            }
            return $xpath;
        }
        
        // Element selector
        return "//" . $cssSelector;
    }
}
