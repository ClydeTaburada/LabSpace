/**
 * Error Recovery Dialog
 * Shows a friendly dialog with options to recover from submission errors
 */
(function() {
    // Create modal HTML
    const modalHTML = `
    <div class="modal fade" id="error-recovery-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Submission Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <p id="error-message">There was an error submitting your code.</p>
                    </div>
                    
                    <h6 class="fw-bold mt-4">Recovery Options:</h6>
                    <ul>
                        <li>Your code has been automatically backed up in your browser</li>
                        <li>You can download your code using the button below</li>
                        <li>Try refreshing the page and submitting again</li>
                        <li>If the problem persists, contact your instructor with the error details</li>
                    </ul>
                    
                    <div class="d-grid mt-4">
                        <button id="download-backup-btn" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Download Your Code
                        </button>
                    </div>
                    
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show-error-details">
                            <label class="form-check-label" for="show-error-details">
                                Show technical details
                            </label>
                        </div>
                        <div id="error-details-container" class="mt-3" style="display:none;">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    Error Details
                                </div>
                                <div class="card-body">
                                    <pre id="error-details" class="bg-light p-3" style="max-height:200px;overflow:auto;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="try-again-btn">Try Again</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Add to document when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Setup events
        document.getElementById('show-error-details')?.addEventListener('change', function() {
            document.getElementById('error-details-container').style.display = 
                this.checked ? 'block' : 'none';
        });
        
        document.getElementById('try-again-btn')?.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('error-recovery-modal')).hide();
            document.getElementById('submit-code')?.click();
        });
    });
    
    // Create global function to show error dialog
    window.showSubmissionErrorDialog = function(error, editor, activityId) {
        const modal = document.getElementById('error-recovery-modal');
        if (!modal) return;
        
        // Set error message
        const errorMsgElement = document.getElementById('error-message');
        if (errorMsgElement) {
            let errorMessage = 'There was an error submitting your code.';
            
            if (typeof error === 'string') {
                errorMessage = error;
            } else if (error.message) {
                errorMessage = error.message;
            } else if (error.htmlResponse) {
                errorMessage = 'The server returned an HTML error page instead of JSON. This usually indicates a PHP error.';
            }
            
            errorMsgElement.textContent = errorMessage;
        }
        
        // Set technical details
        const errorDetailsElement = document.getElementById('error-details');
        if (errorDetailsElement) {
            let detailsContent = '';
            
            if (error.htmlResponse) {
                // Extract the key parts of the HTML error
                const htmlError = error.htmlResponse;
                const errorMatch = htmlError.match(/<b>([^<]+)<\/b> on line <b>(\d+)<\/b>/);
                if (errorMatch) {
                    detailsContent = `PHP Error: ${errorMatch[1]} on line ${errorMatch[2]}\n\n`;
                }
                
                // Add a truncated version of the HTML response
                detailsContent += 'HTML Response (truncated):\n' + 
                    htmlError.substring(0, 500) + 
                    (htmlError.length > 500 ? '...' : '');
            } else if (typeof error === 'object') {
                try {
                    detailsContent = JSON.stringify(error, null, 2);
                } catch (e) {
                    detailsContent = 'Error: ' + (error.message || error.toString());
                }
            } else {
                detailsContent = error.toString();
            }
            
            errorDetailsElement.textContent = detailsContent;
        }
        
        // Setup download button handler
        const downloadBtn = document.getElementById('download-backup-btn');
        if (downloadBtn) {
            downloadBtn.onclick = function() {
                if (!editor) {
                    alert('No editor available for downloading code.');
                    return;
                }
                
                const code = editor.getValue();
                const filename = `activity_code_backup_${Date.now()}.txt`;
                
                // Create blob and download link
                const blob = new Blob([code], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                // Show confirmation
                downloadBtn.innerHTML = '<i class="fas fa-check me-2"></i>Code Downloaded';
                downloadBtn.classList.remove('btn-primary');
                downloadBtn.classList.add('btn-success');
            };
        }
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    };
})();
