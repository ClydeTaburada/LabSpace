<?php
namespace LabSpace\Tests;

/**
 * Base class for all test runners
 * Provides common functionality for running tests and reporting results
 */
abstract class TestBase {
    protected $code;
    protected $testCases;
    protected $results = [];
    
    /**
     * Constructor
     * 
     * @param string $code Student submitted code
     * @param array $testCases Array of test cases to run
     */
    public function __construct($code, $testCases) {
        $this->code = $code;
        $this->testCases = $testCases;
    }
    
    /**
     * Run all tests and return results
     * 
     * @return array Array with score and test results
     */
    public function runTests() {
        $this->results = [];
        $passedTests = 0;
        $totalTests = count($this->testCases);
        $suggestions = [];
        $testsByCategory = [];
        
        foreach ($this->testCases as $index => $testCase) {
            $result = $this->runSingleTest($testCase, $index);
            $this->results[] = $result;
            
            // Track tests by category if provided
            if (isset($testCase['category'])) {
                $category = $testCase['category'];
                if (!isset($testsByCategory[$category])) {
                    $testsByCategory[$category] = ['total' => 0, 'passed' => 0];
                }
                $testsByCategory[$category]['total']++;
                if ($result['passed']) {
                    $testsByCategory[$category]['passed']++;
                }
            }
            
            if ($result['passed']) {
                $passedTests++;
            } else if (!empty($result['message'])) {
                // Collect suggestions from failed tests
                $suggestions[] = $result['message'];
            }
        }
        
        // Calculate percentage score
        $score = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
        
        // Generate general suggestions based on score
        if ($score < 100) {
            if (count($suggestions) === 0) {
                $suggestions[] = "Try reviewing the requirements and test cases to improve your solution.";
            }
            
            // Add specific suggestions for partially completed work
            if ($score > 0 && $score < 100) {
                $suggestions[] = "You've made progress! Keep working on the failed tests.";
            }
        }
        
        // Create a summary with category breakdown if available
        $summary = [
            'total' => $totalTests,
            'passed' => $passedTests,
            'failed' => $totalTests - $passedTests,
            'suggestions' => $suggestions
        ];
        
        if (!empty($testsByCategory)) {
            $summary['categories'] = $testsByCategory;
        }
        
        return [
            'score' => $score,
            'results' => $this->results,
            'summary' => $summary
        ];
    }
    
    /**
     * Run a single test case
     * 
     * @param array $testCase Test case details
     * @param int $index Test index
     * @return array Test result
     */
    abstract protected function runSingleTest($testCase, $index);
    
    /**
     * Format a test result
     * 
     * @param string $name Test name
     * @param bool $passed Whether the test passed
     * @param string $message Optional message
     * @param string $category Optional category for grouping tests
     * @return array Formatted test result
     */
    protected function formatResult($name, $passed, $message = '', $category = null) {
        $result = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message
        ];
        
        if ($category !== null) {
            $result['category'] = $category;
        }
        
        return $result;
    }
    
    /**
     * Get code patterns to identify common issues
     * 
     * @return array Common patterns and their helpful messages
     */
    protected function getCommonCodePatterns() {
        return [
            // General patterns
            '/console\.log|alert\(/' => 'Consider removing debugging statements like console.log or alert from your final code.',
            '/\/\/\s*TODO|FIXME/' => 'Remove TODO comments from your final submission.',
            
            // Language-specific patterns can be overridden in child classes
        ];
    }
    
    /**
     * Check code for common issues and provide suggestions
     * 
     * @return array Array of suggestions based on code patterns
     */
    protected function analyzeCodePatterns() {
        $suggestions = [];
        $patterns = $this->getCommonCodePatterns();
        
        foreach ($patterns as $pattern => $message) {
            if (preg_match($pattern, $this->code)) {
                $suggestions[] = $message;
            }
        }
        
        return $suggestions;
    }
}
