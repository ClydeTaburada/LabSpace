# LabSpace Testing Framework

## Overview

This testing framework is designed to evaluate student code submissions for different programming languages. It provides automated grading functionality similar to platforms like FreeCodeCamp.

## Supported Languages

- HTML
- CSS 
- JavaScript
- PHP

## Key Components

- `TestRunner.php`: Main entry point that routes tests to appropriate language testers
- `TestBase.php`: Base class with common functionality for all testers
- `HtmlTester.php`: Validates HTML structure and content using DOM parsing
- `CssTester.php`: Tests CSS rules and properties
- `JsTester.php`: Tests JavaScript functionality using a JS execution engine
- `PhpTester.php`: Safely evaluates PHP code and validates output

## Test Case Structure

Test cases are defined in JSON format, with different structures depending on the language being tested.

### Common Test Case Properties

| Property | Description |
|----------|-------------|
| `name` | Name/description of the test |
| `message` | Feedback message shown when test fails |
| `category` | Optional grouping for related tests |

### HTML Test Case Structure

```json
[
  {
    "name": "Test name",
    "selector": "CSS selector",
    "contains": "Text to look for",
    "exists": true,
    "count": 3,
    "attribute": "href",
    "contains": "value",
    "category": "structure"
  }
]
```

### CSS Test Case Structure

```json
[
  {
    "name": "Test name",
    "selector": "CSS selector",
    "property": "CSS property",
    "value": "expected value",
    "contains": "partial value",
    "category": "typography"
  }
]
```

### JavaScript Test Case Structure

```json
[
  {
    "name": "Test name",
    "function": "functionName",
    "input": [1, 2],
    "expected": 3,
    "message": "Error message",
    "category": "core"
  },
  {
    "name": "Test name",
    "test": "assert(functionName() === expected);",
    "message": "Error message",
    "category": "validation"
  },
  {
    "name": "Test name",
    "contains": "code snippet",
    "message": "Error message",
    "category": "structure"
  }
]
```

### PHP Test Case Structure

```json
[
  {
    "name": "Test function works correctly",
    "function": "functionName",
    "input": [1, 2],
    "expected": 3,
    "message": "The function should return the sum of two numbers",
    "category": "core"
  },
  {
    "name": "Custom test for PHP code",
    "test": "$result = myFunction(); return $result === 'expected';",
    "message": "Function should return the expected string",
    "category": "validation"
  },
  {
    "name": "Code includes required structure",
    "contains": "foreach",
    "message": "Your code should use a foreach loop",
    "category": "structure"
  }
]
```

## Test Types

### 1. Function Tests

Tests that functions return the expected values given specific inputs. Define using `function`, `input`, and `expected` properties.

### 2. Custom Tests

Tests using custom code that evaluates to true/false. Define using the `test` property containing executable code.

### 3. Content Tests

Tests that code contains specific elements or patterns. Define using the `contains` property.

### 4. Structure Tests

For HTML/CSS, tests that verify proper document structure or styling. Define using `selector` and related properties.

## Sample Test Files

The framework includes sample test files for reference:

- Basic tests: `html_tests.json`, `css_tests.json`, `js_tests.json`, `php_tests.json`
- Advanced tests: `html_advanced_tests.json`, `css_advanced_tests.json`, `js_advanced_tests.json`, `php_advanced_tests.json`
- Specialized tests: `responsive_web_tests.json`, `form_validation_tests.json`, `database_queries_tests.json`, `api_testing_tests.json`

## Security Features

The testing framework includes multiple security measures:

1. **PHP Tests**: 
   - Runs student code in isolated environments
   - Blocks dangerous functions like `exec`, `system`, `eval`, etc.
   - Sets time limits to prevent infinite loops

2. **JavaScript Tests**:
   - Uses a sandbox environment for execution
   - Provides controlled access to mock objects
   - Prevents access to sensitive browser APIs

## Usage Example

```php
// Include the TestRunner
require_once 'tests/TestRunner.php';

// Student code to test
$studentCode = '
function add(a, b) {
  return a + b;
}
';

// Test cases in JSON format
$testCases = '[
  {
    "name": "Function adds numbers correctly",
    "function": "add",
    "input": [5, 3],
    "expected": 8,
    "message": "The add function should return the sum of two numbers"
  }
]';

// Run the tests
$results = \LabSpace\Tests\TestRunner::runTests($studentCode, $testCases, 'javascript');

// $results now contains the test results with score and detailed feedback
```

## Creating Test Cases

When creating activities that require code submissions, include test cases in the appropriate JSON format. The system will automatically validate student submissions against these test cases and provide a score.

Here are some best practices for creating effective test cases:

1. **Start Simple**: Begin with basic tests that verify core functionality
2. **Be Progressive**: Order tests from simple to complex
3. **Give Clear Feedback**: Write helpful `message` properties that guide students
4. **Use Categories**: Group related tests with the `category` property
5. **Test Edge Cases**: Include tests for boundary conditions and error handling

## Sample Student Feedback

The testing framework provides detailed feedback to help students improve their code:

```json
{
  "score": 75,
  "results": [
    {
      "name": "Function adds numbers correctly",
      "passed": true,
      "message": "The add function should return the sum of two numbers"
    },
    {
      "name": "Function handles negative numbers",
      "passed": false,
      "message": "The add function should work with negative numbers"
    }
  ],
  "summary": {
    "total": 4,
    "passed": 3,
    "failed": 1,
    "suggestions": [
      "Make sure your function works with negative numbers",
      "Check the edge cases in your solution"
    ]
  }
}
```

## Best Practices

1. **Test One Thing Per Test**: Each test should verify exactly one aspect of the code
2. **Provide Helpful Messages**: Error messages should guide students to fix their code
3. **Plan Your Tests**: Consider what skills and knowledge you want to assess
4. **Balance Strictness**: Tests should be strict enough to ensure learning but flexible enough to allow different approaches
5. **Review Test Cases**: Ensure test cases themselves are error-free before assigning

## Troubleshooting

If tests are not working as expected:

1. Verify test case JSON is valid
2. Check selector syntax for HTML/CSS tests
3. Ensure test functions exist in the student code
4. Verify expected values match the specified requirements
