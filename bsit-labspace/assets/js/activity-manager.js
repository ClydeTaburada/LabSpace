/**
 * Activity Manager
 * Centralized system for reliable activity navigation
 */

(function() {
    // Create a global ActivityManager object
    window.ActivityManager = {
        registry: {},
        version: '1.0.0',
        initialized: false,
        
        // Initialize the activity manager
        init: function() {
            if (this.initialized) return;
            
            console.log('[ActivityManager] Initializing activity manager');
            this.setupActivityItems();
            this.setupMutationObserver();
            this.initialized = true;
        },
        
        // Process all activity items on the page
        setupActivityItems: function() {
            const activityItems = document.querySelectorAll('.activity-item, [data-activity-id]');
            console.log('[ActivityManager] Found ' + activityItems.length + ' activity items to process');
            
            activityItems.forEach(item => this.processActivityItem(item));
        },
        
        // Process a single activity item
        processActivityItem: function(item) {
            // Skip if already processed
            if (item.classList.contains('activity-manager-processed')) return;
            
            // Get activity ID
            const activityId = item.dataset.activityId || 
                              item.getAttribute('data-activity-id') || 
                              item.querySelector('[data-activity-id]')?.dataset.activityId;
            
            if (!activityId) return;
            
            console.log('[ActivityManager] Processing activity item with ID: ' + activityId);
            
            // Register the activity
            this.registerActivity(activityId, item);
            
            // Add click handler
            item.addEventListener('click', (e) => {
                // Only handle clicks on the item itself, not on nested links/buttons
                if (e.target.closest('a:not(.activity-link), button:not(.activity-button)')) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                this.navigateToActivity(activityId);
            });
            
            // Mark as processed and make it look clickable
            item.classList.add('activity-manager-processed');
            item.style.cursor = 'pointer';
        },
        
        // Register an activity in the central registry
        registerActivity: function(activityId, element) {
            // Extract activity title if available
            let title = '';
            const titleElement = element.querySelector('.activity-title, h5, h6, .card-title');
            
            if (titleElement) {
                title = titleElement.textContent.trim();
            } else if (element.textContent) {
                title = element.textContent.trim().substring(0, 50);
            }
            
            this.registry[activityId] = {
                id: activityId,
                element: element,
                title: title || 'Activity ' + activityId
            };
        },
        
        // Navigate to an activity
        navigateToActivity: function(activityId) {
            if (!activityId) {
                console.error('[ActivityManager] No activity ID provided');
                return;
            }
            
            console.log('[ActivityManager] Navigating to activity: ' + activityId);
            
            // Show loading indicator if available
            if (window.showLoading) {
                window.showLoading('Loading activity...');
            }
            
            // Store activity ID for emergency recovery
            try {
                localStorage.setItem('last_activity_id', activityId);
                sessionStorage.setItem('last_activity_id', activityId);
                sessionStorage.setItem('activity_access_time', Date.now());
            } catch (e) {
                console.error('[ActivityManager] Storage error:', e);
            }
            
            // Determine correct URL based on user role
            let url = this.getActivityUrl(activityId);
            
            // Navigate to the activity
            try {
                window.location.href = url;
                
                // Set fallback navigation in case of issues
                setTimeout(() => {
                    if (document.getElementById('loading-overlay')?.classList.contains('show')) {
                        console.log('[ActivityManager] Using fallback navigation');
                        window.location.replace(url);
                    }
                }, 1000);
            } catch (e) {
                console.error('[ActivityManager] Navigation error:', e);
                
                // Emergency recovery 
                try {
                    window.location.href = `../direct_activity.php?id=${activityId}&emergency=1`;
                } catch (e2) {
                    alert('Navigation failed. Please try again or use the emergency recovery link.');
                    if (window.hideLoading) window.hideLoading();
                }
            }
        },
        
        // Get the appropriate URL for an activity based on user role
        getActivityUrl: function(activityId) {
            const path = window.location.pathname;
            
            if (path.includes('/student/')) {
                return `view_activity.php?id=${activityId}`;
            } else if (path.includes('/teacher/')) {
                return `edit_activity.php?id=${activityId}`;
            } else {
                return `../direct_activity.php?id=${activityId}`;
            }
        },
        
        // Set up mutation observer to process dynamically added activity items
        setupMutationObserver: function() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach((node) => {
                            // Check if the node is an element and has the activity class or attribute
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                if (node.classList?.contains('activity-item') || node.hasAttribute('data-activity-id')) {
                                    this.processActivityItem(node);
                                }
                                
                                // Also check children
                                const activityItems = node.querySelectorAll?.('.activity-item, [data-activity-id]');
                                if (activityItems?.length) {
                                    activityItems.forEach(item => this.processActivityItem(item));
                                }
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            console.log('[ActivityManager] Mutation observer setup complete');
        }
    };
    
    // Initialize the activity manager when the DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        window.ActivityManager.init();
    });
    
    // Also export a global function for direct navigation
    window.goToActivity = function(activityId) {
        if (window.ActivityManager) {
            window.ActivityManager.navigateToActivity(activityId);
        } else {
            // Fallback if ActivityManager isn't initialized
            console.warn('[Navigation] ActivityManager not available, using fallback');
            try {
                localStorage.setItem('last_activity_id', activityId);
                window.location.href = `view_activity.php?id=${activityId}`;
            } catch (e) {
                window.location.href = `view_activity.php?id=${activityId}`;
            }
        }
    };
})();
