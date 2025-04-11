<?php
namespace LabSpace\Tests;

require_once __DIR__ . '/TestBase.php';
// Include V8Js stubs for IDE type hinting - will be ignored if real V8Js exists
require_once __DIR__ . '/stubs/V8JsStubs.php';

/**
 * JavaScript Tester
 * Uses Node.js or a JavaScript sandbox to execute and test JavaScript code
 * 
 * @uses \V8Js V8Js class from the V8Js PHP extension if available
 */
class JsTester extends TestBase {
    /**
     * Run a single JavaScript test case
     * 
     * @param array $testCase Test case details
     * @param int $index Test index
     * @return array Test result
     */
    protected function runSingleTest($testCase, $index) {
        $name = $testCase['name'] ?? "JavaScript Test " . ($index + 1);
        $test = $testCase['test'] ?? null;
        $message = $testCase['message'] ?? '';
        $contains = $testCase['contains'] ?? null;
        $functionName = $testCase['function'] ?? null;
        $input = $testCase['input'] ?? null;
        $expected = $testCase['expected'] ?? null;
        $category = $testCase['category'] ?? null;
        
        // If no test specified, try to construct one from function/input/expected
        if ($test === null && $functionName !== null && $expected !== null) {
            // Create a test assertion based on the function and expected output
            if (is_array($input)) {
                // Handle nested arrays and objects in input
                $inputStr = $this->formatJsValue($input);
                $expectedStr = $this->formatJsValue($expected);
                $test = "assert(deepEquals($functionName($inputStr), $expectedStr));";
            } else if ($input !== null) {
                $inputValue = is_string($input) ? "'$input'" : $input;
                $expectedValue = is_string($expected) ? "'$expected'" : $expected;
                $test = "assert($functionName($inputValue) === $expectedValue);";
            } else {
                $expectedValue = is_string($expected) ? "'$expected'" : $expected;
                $test = "assert($functionName() === $expectedValue);";
            }
        }
        
        if ($test !== null) {
            try {
                // Prepare the code for execution
                $fullCode = $this->prepareJavaScriptTest($this->code, $test);
                
                // Execute the JavaScript
                $result = $this->executeJavaScript($fullCode);
                
                $passed = $result['passed'];
                $output = $result['output'] ?? '';
                
                // Format the message based on pass/fail status
                $displayMessage = $passed ? $message : ($output ?: "Test failed");
                
                // For failed tests, try to provide more specific feedback
                if (!$passed && empty($output)) {
                    if (strpos($test, 'assert(') !== false) {
                        $displayMessage = "Assertion failed: " . trim(str_replace('assert(', '', str_replace(');', '', $test)));
                    }
                }
                
                return $this->formatResult($name, $passed, $displayMessage, $category);
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
     * Executes JavaScript code using available engines
     * Tries V8Js first, falls back to Node.js
     * 
     * @param string $code JavaScript code to execute
     * @return array Execution results
     * @throws \Exception If execution fails
     */
    protected function executeJavaScript($code) {
        // Try using V8Js extension if available
        if (class_exists('V8Js')) {
            try {
                return $this->executeWithV8($code);
            } catch (\Exception $e) {
                // Fall back to Node.js if V8Js fails
                error_log("V8Js execution failed: " . $e->getMessage() . ". Falling back to Node.js.");
            }
        }
        
        // Fall back to Node.js
        return $this->executeWithNode($code);
    }
    
    /**
     * Execute JavaScript code using V8Js extension
     * 
     * @param string $code JavaScript code to execute
     * @return array Execution result
     */
    private function executeWithV8($code) {
        $v8 = new \V8Js();
        
        // Create a PHP context variable to capture output
        $context = ['capturedOutput' => ''];
        
        // Create a JavaScript handler that properly passes data to PHP
        $preCode = '
            var console = { 
                log: function(msg) { 
                    if (typeof msg !== "string") { 
                        msg = JSON.stringify(msg);
                    }
                    PHP.capturedOutput = PHP.capturedOutput + msg;
                }
            };
        ';
        
        // Execute the code and handle any errors
        try {
            // Execute setup code first
            $v8->executeString($preCode);
            
            // Then execute the main test code
            // Use 0 instead of FLAG_NONE as it's the default flag value
            $v8->executeString($code, null, 0, 1000, $context);
            
            // Parse the output to determine success
            $result = json_decode(trim($context['capturedOutput']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['passed' => false, 'output' => 'Invalid output format: ' . $context['capturedOutput']];
            }
            
            return [
                'passed' => isset($result['result']) ? $result['result'] : false,
                'output' => isset($result['error']) ? $result['error'] : ''
            ];
        } catch (\V8JsException $e) {
            return ['passed' => false, 'output' => 'JavaScript error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Execute JavaScript code using Node.js
     * 
     * @param string $code JavaScript code to execute
     * @return array Execution result
     * @throws \Exception If Node.js is not available or execution fails
     */
    private function executeWithNode($code) {
        // Check if Node.js is installed
        exec('node --version 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \Exception("Node.js is not available. Please install Node.js or the V8Js PHP extension.");
        }
        
        // Create a temporary file with the code
        $tempFile = tempnam(sys_get_temp_dir(), 'js_test_');
        file_put_contents($tempFile, $code);
        
        // Execute with Node.js
        $command = 'node ' . escapeshellarg($tempFile) . ' 2>&1';
        $output = shell_exec($command);
        
        // Clean up temporary file
        @unlink($tempFile);
        
        // Parse the output
        $result = json_decode(trim($output), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['passed' => false, 'output' => 'Invalid output format: ' . $output];
        }
        
        return [
            'passed' => isset($result['result']) ? $result['result'] : false,
            'output' => isset($result['error']) ? $result['error'] : ''
        ];
    }
    
    /**
     * Format a JS value for insertion into test code
     * 
     * @param mixed $value The value to format
     * @return string JavaScript representation of the value
     */
    private function formatJsValue($value) {
        if (is_null($value)) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            return "'" . str_replace("'", "\\'", $value) . "'";
        } elseif (is_array($value)) {
            // Check if associative array to convert to object
            if (array_keys($value) !== range(0, count($value) - 1)) {
                // It's an associative array, convert to JS object
                $pairs = [];
                foreach ($value as $k => $v) {
                    $pairs[] = "'" . $k . "': " . $this->formatJsValue($v);
                }
                return '{' . implode(', ', $pairs) . '}';
            } else {
                // Sequential array
                return '[' . implode(', ', array_map([$this, 'formatJsValue'], $value)) . ']';
            }
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return json_encode($value);
        }
    }
    
    /**
     * Prepares JavaScript test by combining student code with test assertions
     * 
     * @param string $code Student code
     * @param string $test Test assertion
     * @return string Combined code ready for execution
     */
    private function prepareJavaScriptTest($code, $test) {
        return <<<EOT
// Test framework functions
function assert(condition) {
    if (!condition) {
        throw new Error("Assertion failed");
    }
    return true;
}

function assertEquals(actual, expected) {
    if (actual !== expected) {
        throw new Error("Expected " + expected + " but got " + actual);
    }
    return true;
}

function deepEquals(a, b) {
    if (a === b) return true;
    
    if (typeof a !== typeof b) return false;
    
    if (a === null || b === null) return a === b;
    
    if (Array.isArray(a) && Array.isArray(b)) {
        if (a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) {
            if (!deepEquals(a[i], b[i])) return false;
        }
        return true;
    }
    
    if (typeof a === 'object' && typeof b === 'object') {
        const keysA = Object.keys(a);
        const keysB = Object.keys(b);
        
        if (keysA.length !== keysB.length) return false;
        
        for (const key of keysA) {
            if (!b.hasOwnProperty(key)) return false;
            if (!deepEquals(a[key], b[key])) return false;
        }
        
        return true;
    }
    
    return false;
}

// Mock DOM environment if needed for tests
if (typeof document === 'undefined') {
    class MockElement {
        constructor(tag) {
            this.tagName = tag.toUpperCase();
            this.children = [];
            this.attributes = {};
            this.style = {};
            this.textContent = '';
            this.innerHTML = '';
            this.id = '';
            this.className = '';
        }
        
        setAttribute(name, value) {
            this.attributes[name] = value;
            if (name === 'id') this.id = value;
            if (name === 'class') this.className = value;
        }
        
        getAttribute(name) {
            return this.attributes[name];
        }
        
        appendChild(child) {
            this.children.push(child);
            return child;
        }
    }
    
    global.document = {
        createElement: function(tag) {
            return new MockElement(tag);
        },
        getElementById: function(id) {
            return new MockElement('div');
        },
        body: new MockElement('body')
    };
}

// Student code
$code

// Test cases
try {
    $test
    console.log(JSON.stringify({result: true}));
} catch (e) {
    console.log(JSON.stringify({result: false, error: e.message}));
}
EOT;
    }
    
    /**
     * Get language-specific code patterns for JavaScript
     * 
     * @return array JavaScript-specific patterns and messages
     */
    protected function getCommonCodePatterns() {
        $patterns = parent::getCommonCodePatterns();
        
        // Add JavaScript-specific patterns
        $jsPatterns = [
            '/var\s+[a-zA-Z0-9_]+\s*=/' => 'Consider using const or let instead of var for variable declarations.',
            '/document\.write\(/' => 'Avoid using document.write() as it can lead to security issues and poor performance.',
            '/==(?!=)/' => 'Consider using === for strict equality comparison instead of ==.',
            '/\!\=(?!\=)/' => 'Consider using !== for strict inequality comparison instead of !=.',
            '/setTimeout\s*\(\s*["\'][^"\']+["\']\s*,/' => 'Avoid passing strings to setTimeout/setInterval; use functions instead.'
        ];
        
        return array_merge($patterns, $jsPatterns);
    }
    
    // ... [rest of the JsTester class remains unchanged]
}
