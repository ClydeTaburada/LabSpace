/**
 * Emergency submission fix for JSON parsing errors
 */
(function() {
    // Wait for page to be fully loaded
    window.addEventListener('DOMContentLoaded', function() {
        console.log('[Submission Fix] Initializing...');
        
        // Find the confirm submit button
        const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
        
        if (confirmSubmitBtn) {
            // Add our patched submit function
            confirmSubmitBtn.addEventListener('click', function(e) {
                console.log('[Submission Fix] Submit button clicked');
                e.preventDefault();
                
                // Get required elements
                const editor = window.editor;
                const submitMessage = document.getElementById('submit-message');
                
                if (!editor) {
                    console.error('[Submission Fix] Editor not initialized');
                    if (submitMessage) {
                        submitMessage.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-1"></i> Error: Code editor not initialized
                            </div>
                        `;
                    }
                    return;
                }
                
                // Get data
                const code = editor.getValue();
                const language = document.getElementById('language-select')?.value || 'javascript';
                const activityId = new URLSearchParams(window.location.search).get('id');
                
                // Disable submit button and show loading
                confirmSubmitBtn.disabled = true;
                if (submitMessage) {
                    submitMessage.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-spinner fa-spin me-1"></i> Submitting code...
                        </div>
                    `;
                }
                
                // First, make an emergency backup
                try {
                    localStorage.setItem('emergency_code_' + activityId, code);
                    localStorage.setItem('emergency_time_' + activityId, Date.now());
                } catch(e) {
                    console.warn('[Submission Fix] Failed to save emergency backup:', e);
                }
                
                // Submit using a more reliable approach
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../includes/functions/submit_activity.php');
                xhr.setRequestHeader('Content-Type', 'application/json');
                
                // Handle the response
                xhr.onload = function() {
                    // First, ensure submit button is re-enabled
                    confirmSubmitBtn.disabled = false;
                    
                    const responseText = xhr.responseText;
                    
                    // Check if it's HTML instead of JSON (common error)
                    const isHtml = responseText.trim().startsWith('<!DOCTYPE') || 
                                 responseText.trim().startsWith('<html') ||
                                 responseText.includes('<body');
                    
                    if (isHtml) {
                        console.error('[Submission Fix] Server returned HTML instead of JSON:', responseText.substring(0, 200));
                        
                        if (submitMessage) {
                            submitMessage.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i> Error: Server returned HTML instead of JSON
                                    <div class="mt-2 small">
                                        If this error persists, please try:
                                        <ul class="mb-0 ms-3">
                                            <li>Refreshing the page</li>
                                            <li>Copying your code to a text file</li>
                                            <li>Try submitting a simpler version of your code</li>
                                            <li>Contacting your instructor</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button id="emergency-download-btn" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download me-1"></i> Download Code Backup
                                    </button>
                                </div>
                            `;
                            
                            // Add download button functionality
                            document.getElementById('emergency-download-btn')?.addEventListener('click', function() {
                                downloadCodeBackup(code);
                            });
                        }
                        return;
                    }
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(responseText);
                        
                        if (data.error) {
                            console.error('[Submission Fix] Server returned error:', data.error);
                            if (submitMessage) {
                                submitMessage.innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i> ${data.error || 'Unknown error'}
                                    </div>
                                `;
                            }
                        } else {
                            console.log('[Submission Fix] Submission successful');
                            if (submitMessage) {
                                submitMessage.innerHTML = `
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-1"></i> Code submitted successfully!
                                    </div>
                                `;
                            }
                            
                            // Redirect after a delay
                            setTimeout(() => {
                                window.location.href = `view_activity.php?id=${activityId}&submitted=1`;
                            }, 1500);
                        }
                    } catch (e) {
                        console.error('[Submission Fix] JSON parse error:', e, 'Raw response:', responseText);
                        
                        if (submitMessage) {
                            submitMessage.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i> Error: Could not understand server response
                                    <div class="small mt-2">
                                        Invalid JSON response. Please try again or contact your instructor.
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button id="emergency-download-btn" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download me-1"></i> Download Code Backup
                                    </button>
                                </div>
                            `;
                            
                            // Add download button functionality
                            document.getElementById('emergency-download-btn')?.addEventListener('click', function() {
                                downloadCodeBackup(code);
                            });
                        }
                    }
                };
                
                // Handle network errors
                xhr.onerror = function() {
                    console.error('[Submission Fix] Network error during submission');
                    confirmSubmitBtn.disabled = false;
                    
                    if (submitMessage) {
                        submitMessage.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-1"></i> Network error. Please check your internet connection.
                            </div>
                            <div class="mt-3">
                                <button id="emergency-download-btn" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download me-1"></i> Download Code Backup
                                </button>
                            </div>
                        `;
                        
                        // Add download button functionality
                        document.getElementById('emergency-download-btn')?.addEventListener('click', function() {
                            downloadCodeBackup(code);
                        });
                    }
                };
                
                // Send the request
                xhr.send(JSON.stringify({
                    activity_id: activityId,
                    code: code,
                    language: language
                }));
            });
            
            console.log('[Submission Fix] Submit button intercepted');
        } else {
            console.warn('[Submission Fix] Submit button not found');
        }
    });
    
    // Helper to download code backup
    function downloadCodeBackup(code) {
        const timestamp = new Date().toISOString().replace(/[:\.]/g, '-');
        const filename = `code_backup_${timestamp}.txt`;
        
        // Create blob and download link
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
    }
})();
