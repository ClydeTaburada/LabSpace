/**
 * Submission Handler
 * Improved handling of form submissions with better error processing
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const submitButton = document.getElementById('submit-code');
        const codeForm = document.getElementById('code-form');
        
        if (submitButton && codeForm) {
            // Intercept the default form submission
            codeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitCode();
            });
            
            // Also handle direct button click
            submitButton.addEventListener('click', function(e) {
                e.preventDefault();
                submitCode();
            });
        }
        
        function submitCode() {
            // Show loading state
            if (typeof showLoading === 'function') {
                showLoading('Submitting your code...');
            }
            
            // Backup code before submission
            if (window.editor) {
                const code = window.editor.getValue();
                try {
                    localStorage.setItem('code_backup_emergency', code);
                    localStorage.setItem('code_backup_time', new Date().toISOString());
                } catch (e) {
                    console.error('Failed to save code backup:', e);
                }
            }
            
            // Gather form data
            const formData = new FormData(codeForm);
            
            // Convert to JSON
            const jsonData = {};
            formData.forEach((value, key) => {
                jsonData[key] = value;
            });
            
            // If using Ace editor, get code from there
            if (window.editor) {
                jsonData.code = window.editor.getValue();
            }
            
            // Send with fetch for better error handling
            fetch(codeForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => {
                // Check if response is valid
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }
                
                // Try to parse JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Not valid JSON - show the HTML content
                        throw {
                            htmlResponse: text,
                            message: 'Server returned HTML instead of JSON. This is likely a PHP error.'
                        };
                    }
                });
            })
            .then(data => {
                // Hide loading state
                if (typeof hideLoading === 'function') {
                    hideLoading();
                }
                
                // Check if we have a success response
                if (data.success) {
                    // Handle successful submission
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (typeof showResults === 'function') {
                        showResults(data);
                    } else {
                        alert('Code submitted successfully!');
                    }
                } else {
                    // Handle error response
                    if (typeof showSubmissionErrorDialog === 'function') {
                        showSubmissionErrorDialog(data.message || 'Error submitting code', window.editor, jsonData.activity_id);
                    } else {
                        alert(`Error: ${data.message || 'Failed to submit code'}`);
                    }
                }
            })
            .catch(error => {
                // Hide loading state
                if (typeof hideLoading === 'function') {
                    hideLoading();
                }
                
                // Show error dialog with detailed information
                if (typeof showSubmissionErrorDialog === 'function') {
                    showSubmissionErrorDialog(error, window.editor, jsonData.activity_id);
                } else {
                    // Fallback to simple alert
                    let errorMessage = 'Failed to submit code. Please try again or contact your instructor.';
                    if (error.htmlResponse) {
                        errorMessage = 'Server error. Please try again or copy your code to a text file.';
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    alert(errorMessage);
                }
                
                console.error('Submission error:', error);
            });
        }
    });
})();
