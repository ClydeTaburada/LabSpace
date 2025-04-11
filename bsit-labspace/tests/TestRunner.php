<?php
namespace LabSpace\Tests;

require_once __DIR__ . '/TestBase.php';
require_once __DIR__ . '/HtmlTester.php';
require_once __DIR__ . '/CssTester.php';
require_once __DIR__ . '/JsTester.php';
require_once __DIR__ . '/PhpTester.php';

/**
 * Test Runner
 * Main interface for parsing and running tests
 */
class TestRunner {
    /**
     * Run tests on student code
     * 
     * @param string $code Student submitted code
     * @param string $testCasesJson JSON string of test cases
     * @param string $language Programming language (html, css, javascript, php)
     * @return array Test results with score and details
     */
    public static function runTests($code, $testCasesJson, $language = 'javascript') {
        try {
            // Parse test cases
            $testCases = json_decode($testCasesJson, true);
            
            if (!is_array($testCases) || json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'score' => 0,
                    'results' => [
                        [
                            'name' => 'Test Case Error',
                            'passed' => false,
                            'message' => 'Invalid test cases format: ' . json_last_error_msg()
                        ]
                    ],
                    'summary' => [
                        'total' => 1,
                        'passed' => 0,
                        'failed' => 1,
                        'suggestions' => ['Check the format of your test cases JSON']
                    ]
                ];
            }
            
            // Create appropriate tester for the language
            switch (strtolower($language)) {
                case 'html':
                    $tester = new HtmlTester($code, $testCases);
                    break;
                case 'css':
                    $tester = new CssTester($code, $testCases);
                    break;
                case 'javascript':
                case 'js':
                    $tester = new JsTester($code, $testCases);
                    break;
                case 'php':
                    $tester = new PhpTester($code, $testCases);
                    break;
                default:
                    return [
                        'score' => 0,
                        'results' => [
                            [
                                'name' => 'Language Error',
                                'passed' => false,
                                'message' => "Unsupported language: $language"
                            ]
                        ],
                        'summary' => [
                            'total' => 1,
                            'passed' => 0,
                            'failed' => 1,
                            'suggestions' => ['Use a supported programming language (html, css, javascript, php)']
                        ]
                    ];
            }
            
            // Run the tests and ensure the summary is included
            $result = $tester->runTests();
            
            // Make sure summary includes suggestions if not already present
            if (!isset($result['summary']['suggestions']) && isset($result['summary'])) {
                $result['summary']['suggestions'] = self::generateSuggestions($result);
            }
            
            return $result;
        } catch (\Exception $e) {
            return [
                'score' => 0,
                'results' => [
                    [
                        'name' => 'Test Execution Error',
                        'passed' => false,
                        'message' => 'Error running tests: ' . $e->getMessage()
                    ]
                ],
                'summary' => [
                    'total' => 1,
                    'passed' => 0,
                    'failed' => 1,
                    'suggestions' => ['Fix syntax errors in your code', 'Error details: ' . $e->getMessage()]
                ]
            ];
        }
    }
    
    /**
     * Generate helpful suggestions based on test results
     * 
     * @param array $result Test results
     * @return array Array of suggestions
     */
    private static function generateSuggestions($result) {
        $suggestions = [];
        $failedTests = [];
        
        // Find failed tests
        if (isset($result['results']) && is_array($result['results'])) {
            $failedTests = array_filter($result['results'], function($test) {
                return isset($test['passed']) && $test['passed'] === false;
            });
        }
        
        // Calculate failure statistics
        $totalTests = isset($result['summary']['total']) ? $result['summary']['total'] : count($result['results'] ?? []);
        $failedCount = count($failedTests);
        $passedCount = $totalTests - $failedCount;
        $passRate = $totalTests > 0 ? ($passedCount / $totalTests) * 100 : 0;
        
        // If all tests passed, congrats!
        if ($failedCount === 0 && $totalTests > 0) {
            $suggestions[] = "Great job! All tests have passed.";
            return $suggestions;
        }
        
        // If all tests failed, suggest reviewing the entire solution
        if ($failedCount === $totalTests && $totalTests > 0) {
            $suggestions[] = "All tests have failed. Consider reviewing the requirements carefully and starting with a simpler solution.";
        }
        
        // Add specific suggestions based on failure messages
        $categorySuggestions = [];
        
        foreach ($failedTests as $test) {
            if (!empty($test['message']) && !in_array($test['message'], $suggestions)) {
                $suggestions[] = $test['message'];
            }
            
            // Group failures by category if available
            if (!empty($test['category']) && isset($test['name'])) {
                $category = $test['category'];
                if (!isset($categorySuggestions[$category])) {
                    $categorySuggestions[$category] = [];
                }
                $categorySuggestions[$category][] = $test['name'];
            }
        }
        
        // Add category-specific suggestions
        foreach ($categorySuggestions as $category => $testNames) {
            if (count($testNames) > 1) {
                $suggestions[] = "Multiple tests failed in the '{$category}' category. Focus on improving this area.";
            }
        }
        
        // Add general suggestions based on pass rate
        if ($passRate < 25 && $totalTests >= 4) {
            $suggestions[] = "You're just getting started. Try focusing on one test at a time.";
        } else if ($passRate >= 25 && $passRate < 75) {
            $suggestions[] = "You're making good progress. Keep refining your solution to address the remaining issues.";
        } else if ($passRate >= 75 && $passRate < 100) {
            $suggestions[] = "You're almost there! Just a few more adjustments needed.";
        }
        
        // Add pattern-based suggestions if available (from the appropriate tester)
        if (isset($result['code_patterns']) && is_array($result['code_patterns'])) {
            foreach ($result['code_patterns'] as $pattern) {
                $suggestions[] = $pattern;
            }
        }
        
        // Limit the number of suggestions to avoid overwhelming the student
        $maxSuggestions = 5;
        if (count($suggestions) > $maxSuggestions) {
            $suggestions = array_slice($suggestions, 0, $maxSuggestions);
        }
        
        // If still no suggestions, add a generic one
        if (empty($suggestions)) {
            $suggestions[] = "Review your code and make sure it meets all the requirements.";
        }
        
        return $suggestions;
    }
}
