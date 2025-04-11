/**
 * Code Editor Helper Functions
 * Provides utility functions for the code editor
 */

// Global message display system
window.showPersistentMessage = function(message, type = 'info') {
    const messageArea = document.getElementById('status-message');
    if (!messageArea) return;
    
    const iconClass = {
        'info': 'fa-info-circle',
        'success': 'fa-check-circle',
        'warning': 'fa-exclamation-triangle',
        'error': 'fa-times-circle'
    }[type] || 'fa-info-circle';
    
    const colorClass = {
        'info': 'alert-info',
        'success': 'alert-success',
        'warning': 'alert-warning',
        'error': 'alert-danger'
    }[type] || 'alert-info';
    
    messageArea.innerHTML = `<i class="fas ${iconClass} me-2"></i>${message}`;
    messageArea.className = `alert ${colorClass}`;
    messageArea.style.display = 'block';
    messageArea.classList.add('show');
    
    // Auto-hide info and success messages after 5 seconds
    if (type === 'info' || type === 'success') {
        setTimeout(() => {
            messageArea.classList.remove('show');
            setTimeout(() => {
                messageArea.style.display = 'none';
            }, 300);
        }, 5000);
    }
};

// Global error handler for code editor
window.handleEditorError = function(error, context = '') {
    console.error(`[Editor Error${context ? ': ' + context : ''}]`, error);
    
    // Display in console output
    const consoleOutput = document.getElementById('console-output');
    if (consoleOutput) {
        consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> Error: ${error.message || error}</div>`;
        consoleOutput.scrollTop = consoleOutput.scrollHeight; // Auto-scroll to latest message
    }
    
    // Create a persistent error notification
    if (window.showPersistentMessage) {
        window.showPersistentMessage(`Editor error: ${error.message || error}`, 'error');
    }
    
    // Hide any loading indicators
    window.hideLoadingState();
};

// Show loading state in the editor
window.showLoadingState = function(message = 'Processing...') {
    const runButton = document.getElementById('run-code');
    const formatButton = document.getElementById('format-code');
    const submitButton = document.getElementById('submit-code');
    
    // Disable buttons
    if (runButton) {
        runButton.disabled = true;
        runButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Running...';
    }
    
    if (formatButton) {
        formatButton.disabled = true;
    }
    
    if (submitButton) {
        submitButton.disabled = true;
    }
    
    // Update console
    const consoleOutput = document.getElementById('console-output');
    if (consoleOutput) {
        consoleOutput.innerHTML = `<div class="text-muted"><i class="fas fa-circle-notch fa-spin me-1"></i> ${message}</div>`;
    }
    
    // Show loading bar
    const loadingBar = document.getElementById('editor-loading-bar');
    if (loadingBar) {
        loadingBar.style.display = 'block';
    }
};

// Hide loading state in the editor
window.hideLoadingState = function() {
    const runButton = document.getElementById('run-code');
    const formatButton = document.getElementById('format-code');
    const submitButton = document.getElementById('submit-code');
    
    // Re-enable buttons
    if (runButton) {
        runButton.disabled = false;
        runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
    }
    
    if (formatButton) {
        formatButton.disabled = false;
    }
    
    if (submitButton) {
        submitButton.disabled = false;
    }
    
    // Hide loading bar
    const loadingBar = document.getElementById('editor-loading-bar');
    if (loadingBar) {
        loadingBar.style.display = 'none';
    }
};

// Format code using the Ace beautify extension
window.formatCode = function(editor) {
    if (!editor) return;
    
    try {
        // Make sure beautify extension is loaded
        if (typeof ace === 'undefined' || !ace.require) {
            throw new Error("Ace editor not properly loaded");
        }
        
        const beautify = ace.require("ace/ext/beautify");
        if (beautify && typeof beautify.beautify === 'function') {
            beautify.beautify(editor.session);
            const consoleOutput = document.getElementById('console-output');
            if (consoleOutput) {
                consoleOutput.innerHTML += '<div class="text-success"><i class="fas fa-check me-1"></i> Code formatted successfully</div>';
                consoleOutput.scrollTop = consoleOutput.scrollHeight;
            }
            
            // Show a confirmation message
            window.showPersistentMessage('Code formatted successfully', 'success');
        } else {
            throw new Error("Beautify extension not available");
        }
    } catch (e) {
        console.error("Formatting error:", e);
        if (window.handleEditorError) {
            window.handleEditorError(e, 'Code Formatting');
        } else {
            alert("Error formatting code: " + e.message);
        }
    }
};

// Resize editor to fit viewport
window.adjustEditorSize = function() {
    try {
        const isMobile = window.innerWidth < 768;
        const isTablet = window.innerWidth >= 768 && window.innerWidth < 992;
        const editorElem = document.getElementById('editor');
        const previewFrame = document.getElementById('preview-frame');
        
        if (!editorElem || !previewFrame) return;
        
        if (isMobile) {
            editorElem.style.height = '300px';
            previewFrame.style.height = '300px';
        } else if (isTablet) {
            editorElem.style.height = '350px';
            previewFrame.style.height = '350px';
        } else {
            editorElem.style.height = '400px';
            previewFrame.style.height = '400px';
        }
        
        // Try to notify the editor about the resize
        if (window.editor && typeof window.editor.resize === 'function') {
            window.editor.resize();
        }
    } catch (e) {
        console.error("Error adjusting editor size:", e);
    }
};

// Render HTML output safely
window.renderHtmlOutput = function(code, outputFrame) {
    if (!outputFrame) return;
    
    try {
        // Get the document inside the iframe
        const doc = outputFrame.contentWindow.document;
        
        // Clear previous content
        doc.open();
        
        // Fix HTML entity issues before rendering
        code = code.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
        
        // Write the new content
        doc.write(code);
        doc.close();
        
        return true;
    } catch (e) {
        console.error("Error rendering HTML output:", e);
        if (window.handleEditorError) {
            window.handleEditorError(e, 'HTML Rendering');
        }
        return false;
    }
};

// Fix HTML code that has been mangled
window.fixMangleHtmlCode = function(code) {
    if (!code) return code;
    
    // Fix common HTML entity issues
    let fixedCode = code;
    fixedCode = fixedCode.replace(/&lt;/g, '<')
                          .replace(/&gt;/g, '>')
                          .replace(/&amp;/g, '&')
                          .replace(/&#39;/g, "'")
                          .replace(/&quot;/g, '"');
    
    // Fix broken tags
    fixedCode = fixedCode.replace(/<div(\s+[^>]*)?>\s*<\/div>/g, '<div$1></div>');
    
    // Fix self-closing tags
    fixedCode = fixedCode.replace(/<(img|br|hr|input|link|meta|source|track|wbr)([^>]*)>\s*<\/\1>/gi, '<$1$2 />');
    
    return fixedCode;
};

// Save code to localStorage with error handling
window.saveCodeToLocalStorage = function(editor, activityId) {
    if (!editor || !activityId) return;
    
    try {
        const code = editor.getValue();
        localStorage.setItem(`code_backup_${activityId}`, code);
        localStorage.setItem(`code_backup_time_${activityId}`, new Date().getTime());
        
        // Show feedback
        const statusMessage = document.getElementById('status-message');
        if (statusMessage) {
            statusMessage.innerHTML = '<i class="fas fa-save me-1"></i> Code backed up automatically';
            statusMessage.className = 'alert alert-info';
            statusMessage.style.display = 'block';
            statusMessage.classList.add('show');
            
            setTimeout(() => {
                statusMessage.classList.remove('show');
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 300);
            }, 2000);
        }
        
        return true;
    } catch (e) {
        console.error('Failed to save code backup:', e);
        return false;
    }
};

// Load code from localStorage with improved reliability
window.loadCodeFromLocalStorage = function(editor, activityId, initialCode) {
    if (!editor || !activityId) return false;
    
    try {
        const savedCode = localStorage.getItem(`code_backup_${activityId}`);
        const savedTime = localStorage.getItem(`code_backup_time_${activityId}`);
        
        if (!savedCode || !savedTime) {
            // No saved code found, use initial code
            return false;
        }
        
        // Check if backup is older than 7 days
        const now = new Date().getTime();
        const backupAge = now - parseInt(savedTime, 10);
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        
        if (backupAge > sevenDays) {
            // Backup is too old, remove it
            localStorage.removeItem(`code_backup_${activityId}`);
            localStorage.removeItem(`code_backup_time_${activityId}`);
            return false;
        }
        
        // Format time difference in a human-readable way
        const formatTimeDiff = function(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
            if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            return 'just now';
        };
        
        // Set the value in the editor
        editor.setValue(savedCode, -1); // -1 puts cursor at start
        
        // Notify user
        window.showPersistentMessage(
            `Loaded your unsaved code from ${formatTimeDiff(backupAge)}. 
            <button class="btn btn-sm btn-link p-0 ms-2" onclick="window.editor.setValue('${initialCode.replace(/'/g, "\\'")}', -1)">
                Restore starter code
            </button>`, 
            'info'
        );
        
        return true;
    } catch (e) {
        console.error('Failed to load code backup:', e);
        return false;
    }
};

// Initialize editor event listeners
window.initEditorEventListeners = function(editor) {
    if (!editor) return;
    
    // Resize handler
    window.addEventListener('resize', function() {
        if (window.adjustEditorSize) {
            window.adjustEditorSize();
        }
    });
    
    // Initialize once
    if (window.adjustEditorSize) {
        window.adjustEditorSize();
    }
    
    // Check for unsaved changes before leaving the page
    window.addEventListener('beforeunload', function(e) {
        // Check if there are unsaved changes that haven't been backed up recently
        const lastSaveTime = localStorage.getItem(`code_backup_time_${window.currentActivityId}`);
        if (lastSaveTime) {
            const now = new Date().getTime();
            const timeSinceLastSave = now - parseInt(lastSaveTime, 10);
            
            // If saved within the last 5 seconds, don't show warning
            if (timeSinceLastSave < 5000) return;
        }
        
        // Show standard confirmation dialog
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    });
};

// Download code as a file
window.downloadCode = function(editor, filename = 'code') {
    if (!editor) return;
    
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
        const fullFilename = `${filename}.${extension}`;
        
        // Create blob and download link
        const blob = new Blob([code], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const downloadLink = document.createElement('a');
        downloadLink.href = url;
        downloadLink.download = fullFilename;
        downloadLink.style.display = 'none';
        
        document.body.appendChild(downloadLink);
        downloadLink.click();
        
        // Clean up
        setTimeout(() => {
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);
        }, 100);
        
        window.showPersistentMessage(`Code downloaded as ${fullFilename}`, 'success');
    } catch (e) {
        console.error('Error downloading code:', e);
        window.handleEditorError(e, 'Download Code');
    }
};

// Create a download button and add it to the editor toolbar
window.addDownloadButton = function() {
    const editorToolbar = document.querySelector('.card-footer .btn-group');
    if (!editorToolbar) return;
    
    const downloadBtn = document.createElement('button');
    downloadBtn.id = 'download-code';
    downloadBtn.className = 'btn btn-outline-secondary';
    downloadBtn.innerHTML = '<i class="fas fa-download me-1"></i> Download';
    downloadBtn.title = 'Download code as a file';
    
    downloadBtn.addEventListener('click', function() {
        if (window.editor && window.downloadCode) {
            // Get activity title for filename
            const title = document.querySelector('.h3.mb-0')?.textContent || 'code';
            const safeTitle = title.replace(/[^a-z0-9]/gi, '_').toLowerCase().substring(0, 20);
            
            window.downloadCode(window.editor, safeTitle);
        }
    });
    
    editorToolbar.appendChild(downloadBtn);
};

// Add a button and function for taking screenshots of code
window.addScreenshotButton = function() {
    if (typeof html2canvas === 'undefined') {
        // Load html2canvas library if not already loaded
        const script = document.createElement('script');
        script.src = 'https://html2canvas.hertzen.com/dist/html2canvas.min.js';
        script.async = true;
        script.onload = function() {
            // Create and add the button after library is loaded
            createScreenshotButton();
        };
        document.head.appendChild(script);
    } else {
        // Create button immediately if library is already loaded
        createScreenshotButton();
    }
    
    function createScreenshotButton() {
        const buttonGroup = document.querySelector('.card-header .d-flex');
        if (!buttonGroup) return;
        
        const screenshotBtn = document.createElement('button');
        screenshotBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
        screenshotBtn.innerHTML = '<i class="fas fa-camera"></i>';
        screenshotBtn.title = 'Take screenshot of code';
        
        screenshotBtn.addEventListener('click', function() {
            if (window.html2canvas && window.editor) {
                // Show loading state
                window.showPersistentMessage('Taking screenshot...', 'info');
                
                // Get the editor DOM element
                const editorElement = document.querySelector('.ace_editor');
                
                if (editorElement) {
                    html2canvas(editorElement).then(canvas => {
                        // Convert to image and download
                        const image = canvas.toDataURL('image/png');
                        const downloadLink = document.createElement('a');
                        
                        // Get activity title for filename
                        const title = document.querySelector('.h3.mb-0')?.textContent || 'code';
                        const safeTitle = title.replace(/[^a-z0-9]/gi, '_').toLowerCase().substring(0, 20);
                        
                        downloadLink.href = image;
                        downloadLink.download = `${safeTitle}_screenshot.png`;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        
                        window.showPersistentMessage('Screenshot saved', 'success');
                    }).catch(error => {
                        console.error('Error taking screenshot:', error);
                        window.handleEditorError(error, 'Screenshot');
                    });
                }
            } else {
                window.showPersistentMessage('Screenshot feature not available', 'error');
            }
        });
        
        buttonGroup.appendChild(screenshotBtn);
    }
};

// Initialize additional features
document.addEventListener('DOMContentLoaded', function() {
    // Add editor enhancements after a short delay to ensure editor is initialized
    setTimeout(function() {
        if (window.addDownloadButton) {
            window.addDownloadButton();
        }
    }, 1000);
});
