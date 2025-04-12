/**
 * Submission Error Helper
 * Provides recovery options when code submissions fail
 */

(function() {
    console.log('[Submission Helper] Initializing...');
    
    // Create a function to download code as backup when submission fails
    window.backupCodeBeforeSubmission = function(editor, activityId) {
        if (!editor || !activityId) return false;
        
        try {
            const code = editor.getValue();
            const language = document.getElementById('language-select')?.value || 'txt';
            
            // Determine file extension based on language
            const extensions = {
                'javascript': 'js',
                'html': 'html',
                'css': 'css',
                'php': 'php',
                'python': 'py',
                'java': 'java',
                'cpp': 'cpp'
            };
            
            const extension = extensions[language] || 'txt';
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `activity_${activityId}_backup_${timestamp}.${extension}`;
            
            // Store to localStorage as well
            localStorage.setItem(`code_emergency_backup_${activityId}`, code);
            localStorage.setItem(`code_emergency_backup_time_${activityId}`, Date.now());
            
            // Create blob and download link
            const blob = new Blob([code], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = filename;
            downloadLink.style.display = 'none';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(downloadLink);
                URL.revokeObjectURL(url);
            }, 100);
            
            return true;
        } catch (e) {
            console.error('Error creating backup:', e);
            return false;
        }
    };
    
    // Add a submission error recovery dialog
    window.showSubmissionErrorDialog = function(error, editor, activityId) {
        // Create modal if it doesn't exist
        if (!document.getElementById('submission-error-modal')) {
            const modalHTML = `
                <div class="modal fade" id="submission-error-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Submission Error
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <p id="error-message">There was an error submitting your code.</p>
                                </div>
                                
                                <h6 class="fw-bold mt-4">Troubleshooting Steps:</h6>
                                <ol>
                                    <li>Check your code for syntax errors</li>
                                    <li>Make a backup of your code using the button below</li>
                                    <li>Try refreshing the page and submitting again</li>
                                    <li>If the problem persists, contact your instructor</li>
                                </ol>
                                
                                <div class="mt-4">
                                    <button id="backup-code-btn" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download Code Backup
                                    </button>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show-technical-details">
                                        <label class="form-check-label" for="show-technical-details">
                                            Show technical details
                                        </label>
                                    </div>
                                    <div id="technical-details" class="mt-3" style="display:none;">
                                        <div class="card">
                                            <div class="card-header bg-dark text-white">
                                                Error Details
                                            </div>
                                            <div class="card-body">
                                                <pre id="error-details" class="p-3 bg-light" style="max-height:200px;overflow:auto;"></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-danger" id="try-submit-again-btn">Try Submit Again</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHTML;
            document.body.appendChild(modalContainer);
            
            // Set up event handlers
            document.getElementById('show-technical-details').addEventListener('change', function() {
                document.getElementById('technical-details').style.display = this.checked ? 'block' : 'none';
            });
        }
        
        // Update modal content with current error
        const modal = document.getElementById('submission-error-modal');
        const errorMessageElem = document.getElementById('error-message');
        const errorDetailsElem = document.getElementById('error-details');
        const backupBtn = document.getElementById('backup-code-btn');
        const tryAgainBtn = document.getElementById('try-submit-again-btn');
        
        // Set error message
        if (errorMessageElem) {
            errorMessageElem.textContent = typeof error === 'string' ? error : 
                (error.message || 'There was an error submitting your code.');
        }
        
        // Set error details
        if (errorDetailsElem) {
            let detailsText = '';
            
            if (typeof error === 'object') {
                if (error.htmlResponse) {
                    detailsText = 'Server returned HTML instead of JSON:\n\n' + error.htmlResponse;
                } else {
                    try {
                        detailsText = JSON.stringify(error, null, 2);
                    } catch (e) {
                        detailsText = error.toString();
                    }
                }
            } else {
                detailsText = error.toString();
            }
            
            errorDetailsElem.textContent = detailsText;
        }
        
        // Setup backup button
        if (backupBtn) {
            backupBtn.onclick = function() {
                if (window.backupCodeBeforeSubmission && editor && activityId) {
                    const success = window.backupCodeBeforeSubmission(editor, activityId);
                    if (success) {
                        backupBtn.innerHTML = '<i class="fas fa-check me-2"></i>Backup Downloaded';
                        backupBtn.classList.remove('btn-primary');
                        backupBtn.classList.add('btn-success');
                    }
                }
            };
        }
        
        // Setup try again button
        if (tryAgainBtn) {
            tryAgainBtn.onclick = function() {
                // Close the modal
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
                
                // Trigger submit again
                const submitBtn = document.getElementById('submit-code');
                if (submitBtn) {
                    submitBtn.click();
                }
            };
        }
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    };
    
    // Hook into form submission error handlers
    document.addEventListener('DOMContentLoaded', function() {
        const submitButton = document.getElementById('submit-code');
        const editor = window.editor; // Ace editor instance
        
        if (submitButton && editor) {
            // Get activity ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const activityId = urlParams.get('id');
            
            // Create a backup before submission
            submitButton.addEventListener('click', function() {
                if (window.backupCodeBeforeSubmission && activityId) {
                    window.backupCodeBeforeSubmission(editor, activityId);
                }
            });
        }
    });
})();
