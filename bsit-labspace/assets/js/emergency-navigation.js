/**
 * Emergency Activity Navigation
 * This script adds robust emergency navigation for activities when normal navigation fails
 */

(function() {
    console.log('[Emergency Nav] Initializing emergency navigation system...');
    
    // IMMEDIATE ACTIONS - Fix any existing loading overlays
    if (document.getElementById('loading-overlay')?.classList.contains('show')) {
        console.log('[Emergency Nav] Found active loading overlay on page load - forcing hide');
        document.getElementById('loading-overlay').classList.remove('show');
    }
    
    // Create global ultra-reliable navigation function
    window.forceNavigateToActivity = function(activityId) {
        if (!activityId) {
            alert('Please enter a valid activity ID');
            return;
        }
        
        console.log('[Emergency Nav] Force navigating to activity ID:', activityId);
        
        // Store activity ID for recovery
        try {
            localStorage.setItem('last_activity_id', activityId);
            sessionStorage.setItem('last_activity_id', activityId);
            
            // Update history array
            let activityHistory = JSON.parse(localStorage.getItem('activity_history') || '[]');
            if (!activityHistory.includes(activityId)) {
                activityHistory.unshift(activityId);
                if (activityHistory.length > 10) activityHistory.pop();
                localStorage.setItem('activity_history', JSON.stringify(activityHistory));
            }
        } catch (e) {
            console.error('[Emergency Nav] Storage error:', e);
        }
        
        // Try multiple navigation methods
        const url = `view_activity.php?id=${activityId}`;
        
        try {
            // Method 1: Basic redirection 
            window.location.href = url;
            
            // Method 2: After delay, try a more forceful approach
            setTimeout(() => {
                window.location.replace(url);
            }, 200);
            
            // Method 3: Create and click a link
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = url;
                link.target = '_self';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }, 400);
            
            // Method 4: Form submission approach
            setTimeout(() => {
                const form = document.createElement('form');
                form.action = url;
                form.method = 'get';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = activityId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }, 600);
        } catch(e) {
            console.error('[Emergency Nav] Navigation error:', e);
            alert('Error navigating to activity. Please try entering the activity URL manually: ' + url);
        }
    };
    
    // Add emergency UI immediately
    function addEmergencyUI() {
        // Skip adding emergency UI on dashboard pages
        if (window.location.pathname.includes('/dashboard.php')) {
            console.log('[Emergency Nav] Skipping emergency UI on dashboard page');
            return;
        }
        
        const container = document.createElement('div');
        container.id = 'emergency-nav-container';
        container.style.position = 'fixed';
        container.style.bottom = '50px';
        container.style.right = '20px';
        container.style.zIndex = '99999';
        container.style.background = 'rgba(220, 53, 69, 0.95)';
        container.style.color = 'white';
        container.style.padding = '15px';
        container.style.borderRadius = '8px';
        container.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';
        container.style.maxWidth = '300px';
        container.style.transition = 'transform 0.3s ease';
        container.style.transform = 'translateY(200px)';
        
        container.innerHTML = `
            <h5 style="margin-top:0;"><i class="fas fa-exclamation-triangle"></i> Emergency Navigation</h5>
            <p style="font-size:14px;margin-bottom:10px;">If you're having trouble opening activities, use this panel:</p>
            <div style="margin-bottom:10px;">
                <input type="number" id="emergency-activity-id" style="width:70%;padding:6px;border-radius:4px;border:none;" placeholder="Enter activity ID">
                <button id="emergency-nav-go" style="width:28%;padding:6px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;">Go</button>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <button id="emergency-nav-close" style="background:transparent;color:white;border:1px solid white;padding:3px 8px;border-radius:4px;cursor:pointer;">Hide</button>
                <button id="emergency-nav-clear-loading" style="background:#ffc107;color:black;border:none;padding:3px 8px;border-radius:4px;cursor:pointer;">Clear Loading</button>
            </div>
            <div style="margin-top:10px;font-size:12px;opacity:0.8;">You can open this panel anytime by pressing <strong>Alt+A</strong></div>
        `;
        
        // Start hidden but show after delay
        setTimeout(() => {
            container.style.transform = 'translateY(0)';
        }, 2000);
        
        document.body.appendChild(container);
        
        // Set up event handlers
        document.getElementById('emergency-nav-go')?.addEventListener('click', function() {
            const activityId = document.getElementById('emergency-activity-id')?.value;
            forceNavigateToActivity(activityId);
        });
        
        document.getElementById('emergency-activity-id')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const activityId = this.value;
                forceNavigateToActivity(activityId);
            }
        });
        
        document.getElementById('emergency-nav-close')?.addEventListener('click', function() {
            container.style.transform = 'translateY(200px)';
        });
        
        document.getElementById('emergency-nav-clear-loading')?.addEventListener('click', function() {
            console.log('[Emergency Nav] Manually clearing loading state');
            document.getElementById('loading-overlay')?.classList.remove('show');
            alert('Loading overlay has been cleared');
        });
        
        // Add keyboard shortcut to show panel
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key.toLowerCase() === 'a') {
                container.style.transform = 'translateY(0)';
            }
        });
    }
    
    // Add emergency navigation UI
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addEmergencyUI);
    } else {
        addEmergencyUI();
    }
    
    // Auto-collect activity IDs from the page
    function collectActivityIds() {
        const activities = [];
        
        // Look for activity IDs in various elements
        document.querySelectorAll('[data-activity-id], .activity-item, .activity-link, a[href*="view_activity.php?id="]').forEach(el => {
            let id = el.dataset.activityId;
            
            // Try to extract from href if not found in data attribute
            if (!id && el.href && el.href.includes('view_activity.php?id=')) {
                const match = el.href.match(/id=(\d+)/);
                if (match && match[1]) {
                    id = match[1];
                }
            }
            
            if (id && !activities.includes(id)) {
                activities.push(id);
            }
        });
        
        console.log('[Emergency Nav] Found activities on page:', activities);
        
        // Store for emergency use
        window.pageActivities = activities;
        return activities;
    }
    
    // Collect activity IDs when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', collectActivityIds);
    } else {
        collectActivityIds();
    }
})();
