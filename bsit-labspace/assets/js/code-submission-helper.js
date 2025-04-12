/**
 * Code Submission Helper
 * Provides enhanced error handling and diagnostic tools for code submission
 */

// Max number of submission attempts before showing detailed troubleshooting
const MAX_SUBMISSION_ATTEMPTS = 3;
let submissionAttempts = 0;

// Store submission errors for diagnostics
const submissionErrors = [];

/**
 * Submit code with enhanced error handling
 * @param {Object} options Configuration options
 * @returns {Promise} Promise resolving to submission result
 */
function submitCodeWithRetry(options) {
    const {
        code,
        language,
        activityId,
        endpoint,
        onProgress,
        maxRetries = 2
    } = options;
    
    let currentRetry = 0;
    submissionAttempts++;
    
    // Show progress
    if (typeof onProgress === 'function') {
        onProgress(`Preparing submission (attempt ${submissionAttempts})...`);
    }
    
    // Check code size and split if needed
    const isLargeSubmission = code.length > 100000;
    const submittableCode = code;
    
    // Record diagnostics to help with troubleshooting
    const diagnosticInfo = {
        browserInfo: navigator.userAgent,
        submissionTime: new Date().toISOString(),
        codeSize: code.length,
        language: language,
        attempt: submissionAttempts
    };
    
    // Function to handle retry logic
    function attemptSubmission(retryCount) {
        if (typeof onProgress === 'function') {
            onProgress(`Submitting code${retryCount > 0 ? ` (retry ${retryCount})` : ''}...`);
        }
        
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Submission-Attempt': submissionAttempts,
                'X-Retry-Count': retryCount
            },
            body: JSON.stringify({
                activity_id: activityId,
                code: submittableCode,
                language: language,
                diagnostic_info: diagnosticInfo
            })
        })
        .then(response => {
            // Process the response
            return response.text().then(text => {
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    return {
                        json: data,
                        status: response.status,
                        ok: response.ok
                    };
                } catch (e) {
                    // Record parsing error
                    console.warn('JSON parse error:', e);
                    
                    // Determine if it's an HTML error
                    const isHtml = text.trim().startsWith('<!DOCTYPE') || 
                                  text.trim().startsWith('<html') || 
                                  text.includes('<br');
                    
                    return {
                        text: text,
                        status: response.status,
                        ok: false,
                        isHtml: isHtml,
                        parseError: e.message
                    };
                }
            });
        })
        .catch(error => {
            // Network or other fetch error
            console.error('Fetch error:', error);
            
            // Store error for diagnostics
            submissionErrors.push({
                type: 'network',
                message: error.message,
                timestamp: new Date().toISOString(),
                attempt: submissionAttempts,
                retry: retryCount
            });
            
            // Retry logic
            if (retryCount < maxRetries) {
                if (typeof onProgress === 'function') {
                    onProgress(`Network error, retrying (${retryCount + 1}/${maxRetries})...`);
                }
                
                // Exponential backoff for retries
                const delay = Math.pow(2, retryCount) * 1000;
                return new Promise(resolve => setTimeout(resolve, delay))
                    .then(() => attemptSubmission(retryCount + 1));
            }
            
            throw error;
        });
    }
    
    // Start the submission process
    return attemptSubmission(currentRetry);
}

/**
 * Extracts PHP errors from HTML responses
 * @param {string} htmlContent The HTML content
 * @returns {string} Extracted error message
 */
function extractPHPErrorFromHTML(htmlContent) {
    let phpError = "Unknown server error";
    
    try {
        // Common PHP error patterns
        if (htmlContent.includes("Fatal error:")) {
            const errorMatch = htmlContent.match(/Fatal error:(.*?)(?:in|<br|$)/);
            if (errorMatch && errorMatch[1]) {
                phpError = "PHP Fatal Error: " + errorMatch[1].trim();
            }
        } else if (htmlContent.includes("Parse error:")) {
            const errorMatch = htmlContent.match(/Parse error:(.*?)(?:in|<br|$)/);
            if (errorMatch && errorMatch[1]) {
                phpError = "PHP Parse Error: " + errorMatch[1].trim();
            }
        } else if (htmlContent.includes("Warning:")) {
            const errorMatch = htmlContent.match(/Warning:(.*?)(?:in|<br|$)/);
            if (errorMatch && errorMatch[1]) {
                phpError = "PHP Warning: " + errorMatch[1].trim();
            }
        } else if (htmlContent.includes("Notice:")) {
            const errorMatch = htmlContent.match(/Notice:(.*?)(?:in|<br|$)/);
            if (errorMatch && errorMatch[1]) {
                phpError = "PHP Notice: " + errorMatch[1].trim();
            }
        }
        
        // Look for MySQL errors
        if (htmlContent.includes("MySQL Error")) {
            const mysqlMatch = htmlContent.match(/MySQL Error[:\s]+(.*?)(?:<br|$)/i);
            if (mysqlMatch && mysqlMatch[1]) {
                phpError = "Database Error: " + mysqlMatch[1].trim();
            }
        }
    } catch (e) {
        console.warn("Error extracting PHP error:", e);
    }
    
    return phpError;
}

/**
 * Error handling function to check for PHP errors in responses
 * @param {Object|string} response The server response
 * @returns {Object} Parsed error information
 */
function detectServerError(response) {
    // Check if we have HTML output instead of JSON
    if (typeof response === 'string' && 
       (response.includes('<!DOCTYPE') || response.includes('<html') || response.includes('PHP Error'))) {
        return {
            isError: true,
            message: extractPHPErrorFromHTML(response),
            htmlContent: response
        };
    }
    
    // Check if we have a text response that isn't valid JSON
    if (typeof response === 'string' && response.trim() !== '') {
        try {
            // See if we can parse it
            JSON.parse(response);
            return { isError: false };
        } catch (e) {
            return {
                isError: true,
                message: "Invalid JSON response from server",
                rawContent: response
            };
        }
    }
    
    return { isError: false };
}

/**
 * Function to safely submit code with comprehensive error handling
 * @param {Object} options The submission options
 * @returns {Promise} Promise resolving to submission result
 */
function safeSubmitCode(options) {
    const { code, language, activityId, endpoint } = options;
    
    return new Promise((resolve, reject) => {
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint || '../includes/submit_code.php');
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = function() {
                try {
                    // Check for PHP errors first
                    const errorCheck = detectServerError(xhr.responseText);
                    if (errorCheck.isError) {
                        return reject({
                            message: "Server error: " + errorCheck.message,
                            details: errorCheck
                        });
                    }
                    
                    // Parse the response
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    reject({
                        message: "Error processing response: " + e.message,
                        originalText: xhr.responseText
                    });
                }
            };
            
            xhr.onerror = function() {
                reject({
                    message: "Network error during submission",
                    status: xhr.status
                });
            };
            
            // Send the data
            xhr.send(JSON.stringify({
                activity_id: activityId,
                code: code,
                language: language
            }));
        } catch (e) {
            reject({
                message: "Error preparing submission: " + e.message
            });
        }
    });
}

/**
 * Provides comprehensive diagnostics and troubleshooting info
 * @returns {Object} Diagnostic information
 */
function getSubmissionDiagnostics() {
    // Get detailed diagnostics about the environment and previous errors
    return {
        browser: {
            userAgent: navigator.userAgent,
            vendor: navigator.vendor,
            platform: navigator.platform,
            language: navigator.language,
            cookiesEnabled: navigator.cookieEnabled
        },
        screen: {
            width: window.screen.width,
            height: window.screen.height,
            colorDepth: window.screen.colorDepth,
            orientation: window.screen.orientation ? window.screen.orientation.type : 'unknown'
        },
        errors: submissionErrors,
        submission: {
            attempts: submissionAttempts,
            timestamp: new Date().toISOString()
        },
        connection: {
            type: navigator.connection ? navigator.connection.effectiveType : 'unknown',
            downlink: navigator.connection ? navigator.connection.downlink : 'unknown'
        }
    };
}

// Export for global use
window.CodeSubmissionHelper = {
    submitCodeWithRetry,
    extractPHPErrorFromHTML,
    getSubmissionDiagnostics,
    safeSubmitCode,
    detectServerError
};
