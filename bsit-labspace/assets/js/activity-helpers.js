/**
 * Activity Helpers
 * Helper functions to improve activity navigation and debugging
 */

(function() {
    // Store activity information for quick access
    window.activityRegistry = {};
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Activity Helpers] Initializing...');
        
        // Register all activity links on the page
        registerActivityLinks();
        
        // Make debug container stay visible much longer
        extendDebugVisibility();
        
        // Add direct navigation panel if many activities are found
        if (Object.keys(window.activityRegistry).length > 3) {
            addActivityNavigationPanel();
        }
        
        // Add emergency buttons to activities
        addEmergencyButtons();
        
        // Add global click handler for activities
        document.addEventListener('click', function(e) {
            // Find closest activity element
            const activityElement = e.target.closest('.activity-item, .activity-link, [data-activity-id]');
            
            // If clicking on an activity (but not on another interactive element)
            if (activityElement && !e.target.closest('a:not(.activity-link), button:not(.activity-emergency-btn)')) {
                const activityId = activityElement.dataset.activityId || 
                                 activityElement.querySelector('[data-activity-id]')?.dataset.activityId;
                
                if (activityId) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Use direct navigation
                    window.goToActivity(activityId);
                }
            }
        });
    });
    
    // Register all activity links to a central registry
    function registerActivityLinks() {
        document.querySelectorAll('[data-activity-id], .activity-link').forEach(function(element) {
            const activityId = element.dataset.activityId || 
                             (element.href && element.href.match(/id=(\d+)/) ? element.href.match(/id=(\d+)/)[1] : null);
            
            if (activityId) {
                // Extract activity title from element content if available
                let title = '';
                const titleElement = element.querySelector('.activity-title, h6, strong');
                if (titleElement) {
                    title = titleElement.textContent.trim();
                } else if (element.textContent) {
                    title = element.textContent.trim().split('\n')[0];
                }
                
                // Limit title length
                title = title.substring(0, 40) || `Activity ${activityId}`;
                
                // Store in registry
                window.activityRegistry[activityId] = {
                    id: activityId,
                    title: title,
                    element: element,
                    url: `view_activity.php?id=${activityId}`
                };
                
                // Mark element as registered
                element.classList.add('activity-registered');
                
                // Make sure clicks are properly handled
                element.addEventListener('click', function(e) {
                    if (e.target.closest('a:not(.activity-link), button')) {
                        return;
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    navigateToRegisteredActivity(activityId);
                });
            }
        });
        
        console.log(`[Activity Helpers] Registered ${Object.keys(window.activityRegistry).length} activities`);
    }
    
    // Function to navigate to a registered activity
    window.navigateToRegisteredActivity = function(activityId) {
        if (!window.activityRegistry[activityId]) {
            console.error('[Activity Helpers] Activity not found in registry:', activityId);
            return;
        }
        
        const activity = window.activityRegistry[activityId];
        console.log(`[Activity Helpers] Navigating to activity: ${activity.title} (ID: ${activityId})`);
        
        // Show loading if available
        if (window.showLoading) {
            window.showLoading(`Loading activity: ${activity.title}...`);
        }
        
        // Show a persistent message for navigation feedback
        if (window.showPersistentMessage) {
            window.showPersistentMessage(
                `Navigating to: ${activity.title}`, 
                'info'
            );
        }
        
        // Perform navigation with delay for visual feedback
        setTimeout(function() {
            window.location.href = activity.url;
        }, 300);
    };
    
    // Add reliable direct navigation function
    window.goToActivity = function(activityId) {
        if (!activityId) {
            console.error('[Activity Helpers] Cannot navigate: No activity ID provided');
            return;
        }
        
        // Show loading with special class for direct navigation
        if (window.showLoading) {
            window.showLoading('Loading activity...');
        }
        
        console.log(`[Navigation] Direct navigation to activity ID: ${activityId}`);
        
        // Use multiple approaches for maximum reliability
        try {
            const url = `view_activity.php?id=${activityId}`;
            
            // Try window.location approach
            setTimeout(() => {
                window.location.href = url;
            }, 50);
            
            // Store activity ID for emergency recovery
            try {
                localStorage.setItem('last_activity_id', activityId);
                sessionStorage.setItem('last_activity_id', activityId);
                
                // Update activity history
                let activityHistory = JSON.parse(localStorage.getItem('activity_history') || '[]');
                if (!activityHistory.includes(activityId)) {
                    activityHistory.unshift(activityId);
                    if (activityHistory.length > 10) activityHistory.pop();
                    localStorage.setItem('activity_history', JSON.stringify(activityHistory));
                }
            } catch (e) {
                console.error('[Navigation] Storage error:', e);
            }
            
            // As a fallback, try a more aggressive approach
            setTimeout(() => {
                if (document.getElementById('loading-overlay')?.classList.contains('show')) {
                    console.log('[Navigation] Using fallback navigation method');
                    window.location.replace(url);
                }
            }, 500);
            
            // Last resort - create and click a link
            setTimeout(() => {
                if (document.getElementById('loading-overlay')?.classList.contains('show')) {
                    console.log('[Navigation] Using emergency navigation');
                    const a = document.createElement('a');
                    a.href = url;
                    a.target = '_self';
                    a.click();
                }
            }, 1000);
        } catch (e) {
            console.error('[Navigation] Error during navigation:', e);
            alert('Navigation failed. Please try clicking again or refresh the page.');
            if (window.hideLoading) window.hideLoading();
        }
    };
    
    // Make debug messages stay visible much longer
    function extendDebugVisibility() {
        // Override click debug timeout to be much longer
        const originalSetTimeout = window.setTimeout;
        window.setTimeout = function(callback, delay, ...args) {
            // If this is the debug hide timeout, extend it significantly
            if (callback.toString().includes('debugContainer.style') && delay < 60000) {
                console.log('[Activity Helpers] Extending debug visibility timeout');
                // 5 minutes instead of a few seconds
                delay = 300000;
            }
            return originalSetTimeout(callback, delay, ...args);
        };
    }
    
    // Add a floating navigation panel for all registered activities
    function addActivityNavigationPanel() {
        const panel = document.createElement('div');
        panel.className = 'activity-navigation-panel';
        panel.style.position = 'fixed';
        panel.style.bottom = '20px';
        panel.style.left = '20px';
        panel.style.zIndex = '9995';
        panel.style.transition = 'all 0.3s ease';
        
        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-primary rounded-circle';
        toggleBtn.style.width = '50px';
        toggleBtn.style.height = '50px';
        toggleBtn.style.boxShadow = '0 2px 10px rgba(0,0,0,0.3)';
        toggleBtn.innerHTML = '<i class="fas fa-cube"></i>';
        
        // Create panel content (initially hidden)
        const panelContent = document.createElement('div');
        panelContent.className = 'card activity-nav-content';
        panelContent.style.display = 'none';
        panelContent.style.position = 'absolute';
        panelContent.style.bottom = '60px';
        panelContent.style.left = '0';
        panelContent.style.width = '300px';
        panelContent.style.maxHeight = '400px';
        panelContent.style.overflowY = 'auto';
        panelContent.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
        
        // Add header
        panelContent.innerHTML = `
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Activity Navigation</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush activity-nav-list">
                    <!-- Activities will be added here -->
                </div>
            </div>
        `;
        
        // Add activities to the list
        const activityList = panelContent.querySelector('.activity-nav-list');
        
        Object.values(window.activityRegistry).forEach(function(activity) {
            const item = document.createElement('a');
            item.href = activity.url;
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <span><i class="fas fa-cube me-2"></i>${activity.title}</span>
                    <span class="badge bg-primary rounded-pill">${activity.id}</span>
                </div>
            `;
            
            // Add click handler
            item.addEventListener('click', function(e) {
                e.preventDefault();
                navigateToRegisteredActivity(activity.id);
            });
            
            activityList.appendChild(item);
        });
        
        // Toggle panel visibility
        toggleBtn.addEventListener('click', function() {
            if (panelContent.style.display === 'none') {
                panelContent.style.display = 'block';
            } else {
                panelContent.style.display = 'none';
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (panelContent.style.display !== 'none' && 
                !panel.contains(e.target)) {
                panelContent.style.display = 'none';
            }
        });
        
        // Add to panel and append to body
        panel.appendChild(toggleBtn);
        panel.appendChild(panelContent);
        document.body.appendChild(panel);
    }
    
    // Add direct navigation buttons to all activity items
    function addEmergencyButtons() {
        document.querySelectorAll('.activity-item, [data-activity-id]').forEach(item => {
            // Skip if already has an emergency button
            if (item.querySelector('.activity-emergency-btn')) return;
            
            const activityId = item.dataset.activityId || 
                             item.querySelector('[data-activity-id]')?.dataset.activityId;
            
            if (!activityId) return;
            
            // Create emergency button
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-danger activity-emergency-btn';
            btn.innerHTML = '<i class="fas fa-external-link-alt"></i> Open';
            btn.style.position = 'absolute';
            btn.style.right = '10px';
            btn.style.top = '50%';
            btn.style.transform = 'translateY(-50%)';
            btn.style.zIndex = '100';
            btn.style.opacity = '0';
            btn.style.transition = 'opacity 0.3s';
            
            // Add click handler
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.goToActivity(activityId);
            });
            
            // Make button visible on hover
            item.addEventListener('mouseenter', () => {
                btn.style.opacity = '1';
            });
            
            item.addEventListener('mouseleave', () => {
                btn.style.opacity = '0';
            });
            
            // Add to item
            item.style.position = 'relative';
            item.appendChild(btn);
        });
    }
})();
