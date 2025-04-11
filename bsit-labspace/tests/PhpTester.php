<?php
namespace LabSpace\Tests;

require_once __DIR__ . '/TestBase.php';

/**
 * PHP Tester
 * Safely evaluates PHP code and runs test cases
 */
class PhpTester extends TestBase {
    /**
     * Run a single PHP test case
     * 
     * @param array $testCase Test case details
     * @param int $index Test index
     * @return array Test result
     */
    protected function runSingleTest($testCase, $index) {
        $name = $testCase['name'] ?? "PHP Test " . ($index + 1);
        $test = $testCase['test'] ?? null;
        $message = $testCase['message'] ?? '';
        $contains = $testCase['contains'] ?? null;
        $function = $testCase['function'] ?? null;
        $input = $testCase['input'] ?? null;
        $expected = $testCase['expected'] ?? null;
        $category = $testCase['category'] ?? null;
        
        // If no test specified, try to construct one from function/input/expected
        if ($test === null && $function !== null && $expected !== null) {
            // Create a test assertion based on the function and expected output
            $inputStr = '';
            if (is_array($input)) {
                $inputParts = [];
                foreach ($input as $i) {
                    if (is_string($i)) {
                        $inputParts[] = "'$i'";
                    } elseif (is_bool($i)) {
                        $inputParts[] = $i ? 'true' : 'false';
                    } elseif (is_null($i)) {
                        $inputParts[] = 'null';
                    } elseif (is_array($i)) {
                        // Handle nested arrays
                        $inputParts[] = var_export($i, true);
                    } else {
                        $inputParts[] = $i;
                    }
                }
                $inputStr = implode(', ', $inputParts);
            } elseif ($input !== null) {
                if (is_string($input)) {
                    $inputStr = "'$input'";
                } elseif (is_bool($input)) {
                    $inputStr = $input ? 'true' : 'false';
                } elseif (is_null($input)) {
                    $inputStr = 'null';
                } elseif (is_array($input)) {
                    // Handle arrays as input
                    $inputStr = var_export($input, true);
                } else {
                    $inputStr = $input;
                }
            }
            
            // Create a test that compares the function output to the expected value
            if (is_string($expected)) {
                $test = "\$result = $function($inputStr); return \$result === '$expected';";
            } elseif (is_bool($expected)) {
                $test = "\$result = $function($inputStr); return \$result === " . ($expected ? 'true' : 'false') . ";";
            } elseif (is_null($expected)) {
                $test = "\$result = $function($inputStr); return \$result === null;";
            } elseif (is_array($expected)) {
                // Handle array comparison
                $expectedStr = var_export($expected, true);
                $test = "\$result = $function($inputStr); return \$result == $expectedStr;"; 
            } else {
                $test = "\$result = $function($inputStr); return \$result === $expected;";
            }
        }
        
        if ($test !== null) {
            try {
                // Execute the PHP test
                $result = $this->executePhpTest($this->code, $test);
                $displayMessage = $result['passed'] ? $message : ($result['output'] ?? 'Test failed');
                
                return $this->formatResult($name, $result['passed'], $displayMessage, $category);
            } catch (\Exception $e) {
                return $this->formatResult($name, false, "Error: " . $e->getMessage(), $category);
            }
        }
        
        // Code content check
        if ($contains !== null) {
            $code = preg_replace('/(\/\/.*?$)|\/\*[\s\S]*?\*\//m', '', $this->code); // Remove comments
            $passed = stripos($code, $contains) !== false;
            return $this->formatResult($name, $passed, $passed 
                ? "Code contains required element: '{$contains}'" 
                : "Code should contain '{$contains}'", $category);
        }
        
        // If we reach here, the test case was invalid
        return $this->formatResult($name, false, "Invalid test case definition", $category);
    }
    
    /**
     * Get language-specific code patterns for PHP
     * 
     * @return array PHP-specific patterns and messages
     */
    protected function getCommonCodePatterns() {
        $patterns = parent::getCommonCodePatterns();
        
        // Add PHP-specific patterns
        $phpPatterns = [
            '/mysql_(?:connect|query|select_db|fetch)/' => 'Avoid using deprecated mysql_ functions. Use PDO or mysqli instead.',
            '/\$_GET\[[\'"][^\'"]+[\'"]\]/' => 'Make sure to sanitize $_GET inputs to prevent security vulnerabilities.',
            '/\$_POST\[[\'"][^\'"]+[\'"]\]/' => 'Make sure to sanitize $_POST inputs to prevent security vulnerabilities.',
            '/echo \$_/' => 'Avoid directly outputting superglobal values without sanitization.',
            '/<?(?!php|=)/' => 'Use proper PHP opening tags (<?php) instead of short tags.',
            '/include\s*\([\'"]/' => 'Consider using require_once for files that are required for the application to run.'
        ];
        
        return array_merge($patterns, $phpPatterns);
    }
    
    /**
     * Safely execute PHP code in a sandbox
     * 
     * @param string $studentCode Student submitted code
     * @param string $testCode Test assertion code
     * @return array Execution result
     */
    private function executePhpTest($studentCode, $testCode) {
        // Check for dangerous functions
        $dangerousFunctions = [
            'exec', 'shell_exec', 'system', 'passthru', 'eval',
            'popen', 'proc_open', 'unlink', 'rmdir', 'file_put_contents',
            'chmod', 'chgrp', 'chown', 'copy', 'file_get_contents',
            'mkdir', 'rename', 'symlink', 'tempnam', 'touch', 
            'header', 'setcookie', 'http_response_code'
        ];
        
        foreach ($dangerousFunctions as $func) {
            if (preg_match('/\b' . $func . '\s*\(/i', $studentCode)) {
                return [
                    'passed' => false,
                    'output' => "Security error: Use of prohibited function '$func'"
                ];
            }
        }

        // Create a temporary file for execution
        $tempFile = tempnam(sys_get_temp_dir(), 'php_test_');
        
        try {
            // Add PHP opening tag if missing
            if (strpos($studentCode, '<?php') === false) {
                $studentCode = "<?php\n" . $studentCode;
            }
            
            // Create test execution wrapper with improved error handling and debugging
            $execCode = "<?php
            // Error handling
            set_error_handler(function(\$severity, \$message, \$file, \$line) {
                throw new \ErrorException(\$message, 0, \$severity, \$file, \$line);
            });
            
            // Capture output and errors
            ob_start();
            try {
                // Define test helper functions
                function assertEquals(\$expected, \$actual, \$message = '') {
                    if (\$expected !== \$actual) {
                        throw new \Exception(\$message ?: \"Expected '\$expected' but got '\$actual'\");
                    }
                    return true;
                }
                
                function assertContains(\$needle, \$haystack, \$message = '') {
                    \$found = false;
                    if (is_string(\$haystack)) {
                        \$found = strpos(\$haystack, \$needle) !== false;
                    } elseif (is_array(\$haystack)) {
                        \$found = in_array(\$needle, \$haystack);
                    }
                    
                    if (!\$found) {
                        throw new \Exception(\$message ?: \"Expected '\$needle' to be in haystack\");
                    }
                    return true;
                }
                
                // Student code
                " . str_replace('<?php', '', $studentCode) . "
                
                // Test case
                \$testResult = function() {
                    " . $testCode . "
                };
                
                \$passed = (bool) \$testResult();
                \$output = ob_get_clean();
                
                echo json_encode(['passed' => \$passed, 'output' => \$output]);
                
            } catch (\Throwable \$e) {
                \$output = ob_get_clean();
                echo json_encode([
                    'passed' => false, 
                    'output' => 'Error: ' . \$e->getMessage() . ' on line ' . \$e->getLine() . 
                            (\$output ? \"\\nOutput: \$output\" : '')
                ]);
            }
            ?>";
            
            // Write code to temp file
            file_put_contents($tempFile, $execCode);
            
            // Execute with limited execution time
            $currentDir = getcwd();
            chdir(dirname($tempFile));
            
            $output = [];
            $exitCode = 0;
            
            // Execute in a safe environment
            $command = escapeshellcmd(PHP_BINARY . ' -d display_errors=1 -f ' . escapeshellarg(basename($tempFile)));
            exec($command, $output, $exitCode);
            
            chdir($currentDir);
            
            // Process result
            $result = implode("\n", $output);
            $jsonResult = json_decode($result, true);
            
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            if ($jsonResult && isset($jsonResult['passed'])) {
                return $jsonResult;
            }
            
            return [
                'passed' => false,
                'output' => 'Invalid test result: ' . $result
            ];
        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return [
                'passed' => false,
                'output' => 'Test execution error: ' . $e->getMessage()
            ];
        }
    }
}
