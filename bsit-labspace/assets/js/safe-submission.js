/**
 * Safe Submission Handler
 * Safely handles submissions with improved error handling
 */

(function() {
    // Global submission function
    window.submitCodeSafely = function(options) {
        const {
            editor,
            activityId,
            onSuccess,
            onError
        } = options;
        
        // Validate required parameters
        if (!editor || !activityId) {
            console.error('Missing required parameters for submission');
            if (onError) onError('Missing required parameters');
            return;
        }
        
        // Get code and language
        const code = editor.getValue();
        const language = document.getElementById('language-select')?.value || 'javascript';
        
        // Show loading state
        const submitButton = document.getElementById('confirm-submit-btn');
        const submitMessage = document.getElementById('submit-message');
        
        if (submitButton) submitButton.disabled = true;
        if (submitMessage) {
            submitMessage.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-spinner fa-spin me-1"></i> Submitting code...
                </div>
            `;
        }
        
        // Create safety backup first
        try {
            localStorage.setItem('emergency_code_backup', code);
            localStorage.setItem('emergency_code_time', Date.now());
            localStorage.setItem('emergency_activity_id', activityId);
        } catch(e) {
            console.warn('Failed to create emergency backup:', e);
        }
        
        // Submit using fetch with text response first (not JSON)
        fetch('../includes/submit_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                activity_id: activityId,
                code: code,
                language: language
            })
        })
        .then(response => {
            // First get the response as text to check what we got back
            return response.text().then(text => {
                // Check if response looks like HTML (common error indicator)
                const isHtml = text.trim().startsWith('<!DOCTYPE') || 
                              text.trim().startsWith('<html') ||
                              text.includes('<body') ||
                              text.includes('</head>');
                
                if (isHtml) {
                    // We received HTML instead of JSON - likely a PHP error
                    console.error('Server returned HTML instead of JSON');
                    
                    // Extract error message if possible
                    let errorMessage = 'The server returned HTML instead of JSON. This usually indicates a PHP error.';
                    
                    // Try to extract the specific PHP error
                    const errorMatch = text.match(/<b>([^<]+)<\/b> on line <b>(\d+)<\/b>/);
                    if (errorMatch) {
                        errorMessage = `PHP Error: ${errorMatch[1]} on line ${errorMatch[2]}`;
                    }
                    
                    throw {
                        htmlContent: text,
                        message: errorMessage,
                        isHtmlError: true
                    };
                }
                
                // Otherwise try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw {
                        message: `Error: Unexpected token '${text.charAt(0)}', "${text.substring(0, 20)}..." is not valid JSON`,
                        rawContent: text
                    };
                }
            });
        })
        .then(data => {
            // Handle successful response
            if (data.error) {
                // Server returned error in JSON format
                throw { message: data.error };
            }
            
            // Reset submit button
            if (submitButton) submitButton.disabled = false;
            
            // Show success message
            if (submitMessage) {
                submitMessage.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-1"></i> Code submitted successfully!
                    </div>
                `;
            }
            
            // Call success callback
            if (onSuccess) onSuccess(data);
            
            // Clean up emergency backup
            try {
                localStorage.removeItem('emergency_code_backup');
                localStorage.removeItem('emergency_code_time');
            } catch(e) {
                console.warn('Failed to clean up emergency backup:', e);
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            
            // Reset submit button
            if (submitButton) submitButton.disabled = false;
            
            // Prepare error message
            let errorMessage = '';
            let detailedMessage = '';
            
            if (error.isHtmlError) {
                errorMessage = 'Server returned HTML instead of JSON. This is likely a PHP error.';
                detailedMessage = error.message || 'Unknown PHP error';
            } else if (error.message) {
                errorMessage = error.message;
                if (error.rawContent) {
                    detailedMessage = `Raw response: ${error.rawContent.substring(0, 100)}...`;
                }
            } else {
                errorMessage = 'Unknown submission error';
            }
            
            // Show error message
            if (submitMessage) {
                submitMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i> ${errorMessage}
                        ${detailedMessage ? `<div class="small mt-2">${detailedMessage}</div>` : ''}
                        <div class="mt-2 small">
                            If this error persists, please try:
                            <ul class="mb-0 ms-3">
                                <li>Refreshing the page</li>
                                <li>Copying your code to a text file</li>
                                <li>Contacting your instructor</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button id="download-backup-btn" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i> Download Code Backup
                        </button>
                    </div>
                `;
                
                // Add event listener to download button
                document.getElementById('download-backup-btn')?.addEventListener('click', function() {
                    downloadCodeBackup(code, activityId);
                });
            }
            
            // Call error callback
            if (onError) onError(error);
        });
    };
    
    // Helper to download code backup
    function downloadCodeBackup(code, activityId) {
        const filename = `activity_${activityId}_backup_${Date.now()}.txt`;
        const blob = new Blob([code], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        
        document.body.appendChild(a);
        a.click();
        
        // Clean up
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
        
        return true;
    }
})();
