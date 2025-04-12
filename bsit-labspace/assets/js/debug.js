/**
 * Debug utilities for LabSpace
 */

(function() {
    // Add universal error handler
    window.addEventListener('error', function(e) {
        console.error('[Error Caught]:', e.message);
        // If errors happen during loading, cancel loading state
        if (window.hideLoading) {
            window.hideLoading();
        }
        
        // Show error in persistent display
        showPersistentMessage('Error: ' + e.message, 'error');
    });
    
    // Track page load time
    const startTime = Date.now();
    window.addEventListener('load', function() {
        const loadTime = Date.now() - startTime;
        console.log(`[Performance] Page loaded in ${loadTime}ms`);
        
        // Add direct click event monitors after page load
        monitorActivityClicks();
        
        // Create persistent message container
        createPersistentMessageContainer();
    });
    
    // Create a container for persistent messages
    function createPersistentMessageContainer() {
        if (document.getElementById('persistent-message-container')) {
            return;
        }
        
        const container = document.createElement('div');
        container.id = 'persistent-message-container';
        container.style.position = 'fixed';
        container.style.top = '10px';
        container.style.right = '10px';
        container.style.maxWidth = '400px';
        container.style.maxHeight = '80vh';
        container.style.overflowY = 'auto';
        container.style.zIndex = '10000';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }
    
    // Show a persistent message that stays visible until manually dismissed
    window.showPersistentMessage = function(message, type = 'info') {
        createPersistentMessageContainer();
        
        const container = document.getElementById('persistent-message-container');
        
        const msgElement = document.createElement('div');
        msgElement.className = 'persistent-message';
        msgElement.style.background = type === 'error' ? '#f8d7da' : 
                                      type === 'warning' ? '#fff3cd' : 
                                      type === 'success' ? '#d4edda' : '#cff4fc';
        msgElement.style.color = type === 'error' ? '#721c24' : 
                                type === 'warning' ? '#856404' : 
                                type === 'success' ? '#155724' : '#0c5460';
        msgElement.style.border = '1px solid ' + 
                                 (type === 'error' ? '#f5c6cb' : 
                                  type === 'warning' ? '#ffeeba' : 
                                  type === 'success' ? '#c3e6cb' : '#bee5eb');
        msgElement.style.borderRadius = '4px';
        msgElement.style.padding = '12px 15px';
        msgElement.style.margin = '0';
        msgElement.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        msgElement.style.position = 'relative';
        msgElement.style.maxWidth = '100%';
        msgElement.style.wordBreak = 'break-word';
        
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '5px';
        closeBtn.style.right = '5px';
        closeBtn.style.background = 'transparent';
        closeBtn.style.border = 'none';
        closeBtn.style.fontSize = '20px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontWeight = 'bold';
        closeBtn.style.color = 'inherit';
        closeBtn.style.opacity = '0.7';
        closeBtn.style.padding = '0 5px';
        closeBtn.style.lineHeight = '1';
        
        closeBtn.addEventListener('mouseover', () => {
            closeBtn.style.opacity = '1';
        });
        
        closeBtn.addEventListener('mouseout', () => {
            closeBtn.style.opacity = '0.7';
        });
        
        closeBtn.addEventListener('click', () => {
            msgElement.remove();
        });
        
        const icon = document.createElement('i');
        icon.className = type === 'error' ? 'fas fa-exclamation-circle' : 
                        type === 'warning' ? 'fas fa-exclamation-triangle' : 
                        type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
        icon.style.marginRight = '10px';
        
        const content = document.createElement('span');
        content.textContent = message;
        
        msgElement.appendChild(closeBtn);
        msgElement.appendChild(icon);
        msgElement.appendChild(content);
        container.appendChild(msgElement);
        
        // Auto-hide after a long time (2 minutes) with fade effect
        setTimeout(() => {
            msgElement.style.transition = 'opacity 1s';
            msgElement.style.opacity = '0.7';
            setTimeout(() => {
                msgElement.remove();
            }, 1000);
        }, 120000);
        
        return msgElement;
    };

    function monitorActivityClicks() {
        // Create a message container for click debugging with much better visibility
        const debugContainer = document.createElement('div');
        debugContainer.style.position = 'fixed';
        debugContainer.style.top = '50%';
        debugContainer.style.left = '50%';
        debugContainer.style.transform = 'translate(-50%, -50%)';
        debugContainer.style.padding = '20px';
        debugContainer.style.background = 'rgba(0, 0, 0, 0.9)';
        debugContainer.style.color = 'white';
        debugContainer.style.fontSize = '16px';
        debugContainer.style.zIndex = '9999';
        debugContainer.style.display = 'none';
        debugContainer.style.borderRadius = '8px';
        debugContainer.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.5)';
        debugContainer.style.minWidth = '300px';
        debugContainer.style.maxWidth = '90%';
        debugContainer.style.transition = 'opacity 0.3s';
        debugContainer.id = 'click-debug-container';
        document.body.appendChild(debugContainer);
        
        // Monitor all clicks on the page
        document.addEventListener('click', function(e) {
            const activityItem = e.target.closest('.activity-item, [data-activity-id]');
            if (activityItem) {
                const activityId = activityItem.dataset.activityId || 
                                   activityItem.querySelector('[data-activity-id]')?.dataset.activityId;

                if (activityId) {
                    console.log(`[Debug] Activity clicked: ID=${activityId}`);
                } else {
                    console.warn('[Debug] Clicked activity item has no ID:', activityItem);
                }
            }
        });

        document.addEventListener('click', function(e) {
            // Check for activity clicks
            const activityItem = e.target.closest('.activity-item, .activity-link, [data-activity-id]');
            if (activityItem) {
                // Show the debug container
                debugContainer.style.display = 'block';
                debugContainer.style.opacity = '1';
                
                // Get activity info from multiple possible sources
                const activityId = activityItem.dataset.activityId || 
                                  activityItem.querySelector('.activity-id-data')?.dataset.activityId || 
                                  (activityItem.href && activityItem.href.match(/id=(\d+)/) ? 
                                   activityItem.href.match(/id=(\d+)/)[1] : 'unknown');
                                  
                const activityLink = activityItem.querySelector('.activity-link');
                const activityIdData = activityItem.querySelector('.activity-id-data');
                
                // Check several places for URL
                let url = activityLink ? activityLink.getAttribute('href') : null;
                if (!url && activityItem.href) {
                    url = activityItem.href;
                }
                if (!url && activityIdData && activityIdData.dataset.url) {
                    url = activityIdData.dataset.url;
                }
                if (!url && activityId !== 'unknown') {
                    url = `view_activity.php?id=${activityId}`;
                }
                
                const displayUrl = url || 'unknown';
                
                // Show more prominent info
                debugContainer.innerHTML = `
                    <h3 style="margin-top:0; color: #4da6ff;">Activity Navigation</h3>
                    <div style="margin: 15px 0; font-size: 18px;">
                        <div style="margin-bottom: 10px;">
                            <strong style="display: inline-block; width: 100px; color: #aaa;">Activity ID:</strong> 
                            <span style="font-weight: bold; color: #ffcc00;">${activityId}</span>
                        </div>
                        <div>
                            <strong style="display: inline-block; width: 100px; color: #aaa;">Target URL:</strong> 
                            <span style="word-break: break-all; color: #66ff66;">${displayUrl}</span>
                        </div>
                    </div>`;
                
                console.log(`[Debug] Activity clicked: ID=${activityId}, URL=${displayUrl}`);
                
                // Create button group
                const emergencyBtnGroup = document.createElement('div');
                emergencyBtnGroup.style.display = 'flex';
                emergencyBtnGroup.style.gap = '10px';
                emergencyBtnGroup.style.marginTop = '20px';
                
                // Direct navigation button (prominent)
                const emergencyBtn = document.createElement('button');
                emergencyBtn.textContent = 'Navigate Now';
                emergencyBtn.style.flex = '1';
                emergencyBtn.style.padding = '12px';
                emergencyBtn.style.fontSize = '16px';
                emergencyBtn.style.fontWeight = 'bold';
                emergencyBtn.style.background = '#007bff';
                emergencyBtn.style.color = 'white';
                emergencyBtn.style.border = 'none';
                emergencyBtn.style.borderRadius = '5px';
                emergencyBtn.style.cursor = 'pointer';
                emergencyBtn.style.transition = 'background 0.2s';
                
                // Keep visible button
                const keepVisibleBtn = document.createElement('button');
                keepVisibleBtn.textContent = 'Keep Open';
                keepVisibleBtn.style.padding = '12px';
                keepVisibleBtn.style.fontSize = '16px';
                keepVisibleBtn.style.background = '#6c757d';
                keepVisibleBtn.style.color = 'white';
                keepVisibleBtn.style.border = 'none';
                keepVisibleBtn.style.borderRadius = '5px';
                keepVisibleBtn.style.cursor = 'pointer';
                
                // Close button
                const closeBtn = document.createElement('button');
                closeBtn.textContent = 'Close';
                closeBtn.style.flex = '1';
                closeBtn.style.padding = '12px';
                closeBtn.style.fontSize = '16px';
                closeBtn.style.background = '#dc3545';
                closeBtn.style.color = 'white';
                closeBtn.style.border = 'none';
                closeBtn.style.borderRadius = '5px';
                closeBtn.style.cursor = 'pointer';
                
                // Button hover effects
                emergencyBtn.addEventListener('mouseover', () => {
                    emergencyBtn.style.background = '#0069d9';
                });
                emergencyBtn.addEventListener('mouseout', () => {
                    emergencyBtn.style.background = '#007bff';
                });
                
                keepVisibleBtn.addEventListener('mouseover', () => {
                    keepVisibleBtn.style.background = '#5a6268';
                });
                keepVisibleBtn.addEventListener('mouseout', () => {
                    keepVisibleBtn.style.background = '#6c757d';
                });
                
                closeBtn.addEventListener('mouseover', () => {
                    closeBtn.style.background = '#c82333';
                });
                closeBtn.addEventListener('mouseout', () => {
                    closeBtn.style.background = '#dc3545';
                });
                
                // Use the URL we found or construct it from ID
                emergencyBtn.onclick = function() {
                    const navigateUrl = url || `view_activity.php?id=${activityId}`;
                    console.log('[Emergency] Navigating to:', navigateUrl);
                    window.location.href = navigateUrl;
                };
                
                // Keep dialog visible permanently
                keepVisibleBtn.onclick = function() {
                    // Cancel any auto-hide
                    clearTimeout(window.debugHideTimeout);
                    
                    // Change button text and disable the button
                    keepVisibleBtn.textContent = 'Staying Open';
                    keepVisibleBtn.style.background = '#28a745';
                    keepVisibleBtn.disabled = true;
                    
                    // Add a note to the container
                    const noteDiv = document.createElement('div');
                    noteDiv.textContent = 'This dialog will stay open until closed manually.';
                    noteDiv.style.marginTop = '10px';
                    noteDiv.style.fontSize = '14px';
                    noteDiv.style.color = '#aaa';
                    noteDiv.style.fontStyle = 'italic';
                    noteDiv.style.textAlign = 'center';
                    
                    debugContainer.appendChild(noteDiv);
                    
                    // Create a persistent message with navigation option
                    showPersistentMessage(
                        `Activity ID: ${activityId} detected. Click here to navigate.`, 
                        'info'
                    ).addEventListener('click', function() {
                        window.location.href = url || `view_activity.php?id=${activityId}`;
                    });
                };
                
                closeBtn.onclick = function() {
                    debugContainer.style.opacity = '0';
                    setTimeout(() => {
                        debugContainer.style.display = 'none';
                    }, 300);
                };
                
                emergencyBtnGroup.appendChild(emergencyBtn);
                emergencyBtnGroup.appendChild(keepVisibleBtn);
                emergencyBtnGroup.appendChild(closeBtn);
                debugContainer.appendChild(emergencyBtnGroup);
                
                // Important: MUCH longer auto-hide time or never auto-hide
                clearTimeout(window.debugHideTimeout);
                window.debugHideTimeout = setTimeout(() => {
                    if (debugContainer.style.display !== 'none') {
                        // Start fading out
                        debugContainer.style.opacity = '0';
                        setTimeout(() => {
                            debugContainer.style.display = 'none';
                        }, 300);
                    }
                }, 300000); // Stay visible for 5 minutes by default
            }
        });
    }
    
    // Add emergency cancel loading button functionality with better visibility
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Debug] Skipping redundant activity click debug logging.');
        // Removed redundant debug logic

        const cancelBtn = document.getElementById('cancel-loading');
        if (cancelBtn) {
            // Make cancel button more prominent
            cancelBtn.style.padding = '8px 15px';
            cancelBtn.style.fontSize = '14px';
            cancelBtn.style.fontWeight = 'bold';
            
            cancelBtn.addEventListener('click', function() {
                console.log('[Debug] Loading cancelled by user');
                if (window.hideLoading) {
                    window.hideLoading();
                }
                
                showPersistentMessage('Loading cancelled by user.', 'warning');
            });
        }
        
        // Add extended wait detection with more visible indication
        setTimeout(function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay && overlay.classList.contains('show')) {
                overlay.classList.add('extended-wait');
                console.log('[Debug] Extended loading detected - showing cancel option');
                
                // Create a more visible cancel option
                if (!document.getElementById('extended-cancel')) {
                    const extendedCancel = document.createElement('div');
                    extendedCancel.id = 'extended-cancel';
                    extendedCancel.style.position = 'fixed';
                    extendedCancel.style.top = '70%';
                    extendedCancel.style.left = '50%';
                    extendedCancel.style.transform = 'translate(-50%, -50%)';
                    extendedCancel.style.background = 'rgba(220, 53, 69, 0.9)';
                    extendedCancel.style.color = 'white';
                    extendedCancel.style.padding = '15px 25px';
                    extendedCancel.style.borderRadius = '8px';
                    extendedCancel.style.zIndex = '10000';
                    extendedCancel.style.cursor = 'pointer';
                    extendedCancel.style.boxShadow = '0 0 20px rgba(0,0,0,0.5)';
                    extendedCancel.innerHTML = '<i class="fas fa-exclamation-triangle" style="margin-right:10px;"></i> Loading taking too long - Click to cancel';
                    
                    extendedCancel.addEventListener('click', function() {
                        if (window.hideLoading) {
                            window.hideLoading();
                        }
                        this.remove();
                        
                        // Show permanent message about cancelled loading
                        showPersistentMessage('Loading cancelled due to excessive wait time.', 'warning');
                    });
                    
                    document.body.appendChild(extendedCancel);
                }
                
                if (cancelBtn) cancelBtn.style.display = 'inline-block';
            }
        }, 5000);
    });
    
    // Add direct navigation emergency function
    window.navigateEmergency = function(url) {
        hideLoading();
        console.log('[Emergency] Direct navigation to:', url);
        window.location.href = url;
    };
    
    console.log('[Debug] LabSpace debug utilities loaded');
})();
