    <!-- Footer -->
    <footer class="bg-white py-4 mt-auto border-top">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">© 2023 LabSpace. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-github"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Handle responsive sidebar behavior
            function checkWidth() {
                if (window.innerWidth < 992) {
                    if (sidebar) sidebar.classList.remove('show');
                    if (mainContent) mainContent.classList.remove('expanded');
                }
            }
            
            // Check on load
            checkWidth();
            
            // Check on resize
            window.addEventListener('resize', checkWidth);
            
            // Form submission loading indicator
            const forms = document.querySelectorAll('form');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    // Show loading overlay for forms that don't have the 'no-loading' class
                    if (!this.classList.contains('no-loading')) {
                        if (loadingOverlay) loadingOverlay.classList.add('show');
                    }
                });
            });
            
            // Hide loading overlay when page is fully loaded
            window.addEventListener('load', function() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                }
            });
            
            // Add loading animation for AJAX requests
            const ajaxButtons = document.querySelectorAll('.ajax-button');
            
            ajaxButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
                    this.disabled = true;
                    
                    // Simulate AJAX call completion after 2 seconds (replace with actual AJAX)
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
