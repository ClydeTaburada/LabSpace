<div class="mobile-menu-toggle d-lg-none">
    <button class="btn btn-primary rounded-circle" id="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>
</div>

<div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Menu</h5>
            <button class="btn-close" id="close-mobile-menu" aria-label="Close menu"></button>
        </div>
    </div>
    <div class="mobile-menu-body">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'student/dashboard.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo getBaseUrl(); ?>student/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'student/my_classes.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo getBaseUrl(); ?>student/my_classes.php">
                    <i class="fas fa-chalkboard me-2"></i> My Classes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'student/activities.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo getBaseUrl(); ?>student/activities.php">
                    <i class="fas fa-tasks me-2"></i> Activities
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'student/submissions.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo getBaseUrl(); ?>student/submissions.php">
                    <i class="fas fa-file-alt me-2"></i> My Submissions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'student/profile.php') !== false ? 'active' : ''; ?>" 
                   href="<?php echo getBaseUrl(); ?>student/profile.php">
                    <i class="fas fa-user me-2"></i> My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo getBaseUrl(); ?>direct_activity_viewer.php">
                    <i class="fas fa-bolt me-2"></i> Quick Activity Access
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo getBaseUrl(); ?>logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Clean and simplified mobile menu styling */
.mobile-menu-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1040;
}

.mobile-menu-toggle button {
    width: 60px;
    height: 60px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.5);
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
}

.mobile-menu {
    position: fixed;
    top: 0;
    right: -280px;
    width: 280px;
    height: 100%;
    background-color: white;
    z-index: 1060;
    transition: right 0.3s ease;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.mobile-menu.show {
    right: 0;
}

.mobile-menu-overlay.show {
    opacity: 1;
    visibility: visible;
}

.mobile-menu-header {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.mobile-menu-body {
    padding: 1rem 0;
    overflow-y: auto;
    flex: 1;
}

.mobile-menu .nav-link {
    padding: 0.75rem 1rem;
    color: #333;
    border-left: 3px solid transparent;
}

.mobile-menu .nav-link.active {
    background-color: rgba(9, 44, 160, 0.1);
    color: #092CA0;
    border-left-color: #092CA0;
}

.mobile-menu .nav-link:hover {
    background-color: rgba(9, 44, 160, 0.05);
}

@media (min-width: 992px) {
    .mobile-menu-toggle,
    .mobile-menu,
    .mobile-menu-overlay {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeMenuBtn = document.getElementById('close-mobile-menu');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    
    function openMenu() {
        mobileMenu.classList.add('show');
        mobileMenuOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        mobileMenu.classList.remove('show');
        mobileMenuOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openMenu);
    }
    
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', closeMenu);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMenu);
    }
});
</script>
