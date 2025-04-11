<?php
/**
 * Stub definitions for V8Js and V8JsException classes
 * Used for IDE type hinting when the actual V8Js extension is not available
 */

if (!class_exists('V8Js')) {
    /**
     * V8Js stub class for IDE type hinting
     */
    class V8Js {
        /**
         * Execute a string of JavaScript code
         * 
         * @param string $script JavaScript code to execute
         * @param string $identifier Identifier for the script (default: 'V8Js::executeString()')
         * @param int $flags Execution flags (default: V8Js::FLAG_NONE)
         * @param int $time_limit Time limit for execution in milliseconds (default: 0 = no limit)
         * @param int $memory_limit Memory limit for execution in bytes (default: 0 = no limit)
         * @return mixed Result of the JavaScript execution
         */
        public function executeString($script, $identifier = 'V8Js::executeString()', $flags = 0, $time_limit = 0, $memory_limit = 0) {}
    }
    
    /**
     * V8JsException stub class for IDE type hinting
     */
    class V8JsException extends Exception {}
}
