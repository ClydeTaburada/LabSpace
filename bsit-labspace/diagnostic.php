<?php
session_start();
$pageTitle = "System Diagnostic";
require_once 'includes/functions/auth.php';
require_once 'includes/db/config.php';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">System Diagnostic Tool</h5>
        </div>
        <div class="card-body">
            <h5>PHP Information</h5>
            <table class="table table-striped table-bordered">
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>Database Connection</td>
                    <td>
                        <?php 
                        try {
                            $pdo = getDbConnection();
                            echo '<span class="text-success">Connected</span> (' . 
                                $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ' ' . 
                                $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ')';
                        } catch (Exception $e) {
                            echo '<span class="text-danger">Failed: ' . $e->getMessage() . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Session Status</td>
                    <td>
                        <?php 
                        echo session_status() == PHP_SESSION_ACTIVE ? 
                            '<span class="text-success">Active</span>' : 
                            '<span class="text-warning">Not active</span>'; 
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Bootstrap Status</td>
                    <td>
                        <button class="btn btn-primary" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapseTest" 
                                aria-expanded="false" 
                                aria-controls="collapseTest">
                          Test Bootstrap Collapse
                        </button>
                        <div class="collapse mt-2" id="collapseTest">
                            <div class="card card-body">
                                If you can see this, Bootstrap JS is working properly.
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <h5 class="mt-4">Module Loading Test</h5>
            <div class="card">
                <div class="card-body">
                    <div class="accordion" id="testAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#testCollapse" 
                                        aria-expanded="false" 
                                        aria-controls="testCollapse">
                                    Test Accordion Module
                                </button>
                            </h2>
                            <div id="testCollapse" class="accordion-collapse collapse" 
                                 data-bs-parent="#testAccordion">
                                <div class="accordion-body">
                                    <p>If you can see this content, accordion is working properly.</p>
                                    
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Column 1</th>
                                                    <th>Column 2</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Data 1</td>
                                                    <td>Data 2</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4">Browser Information</h5>
            <div id="browser-info">Loading browser information...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Display browser information
    const browserInfoDiv = document.getElementById('browser-info');
    if (browserInfoDiv) {
        browserInfoDiv.innerHTML = `
            <table class="table table-striped table-bordered">
                <tr>
                    <td>User Agent</td>
                    <td>${navigator.userAgent}</td>
                </tr>
                <tr>
                    <td>Browser</td>
                    <td>${navigator.appName} ${navigator.appVersion}</td>
                </tr>
                <tr>
                    <td>Screen Size</td>
                    <td>${window.innerWidth}x${window.innerHeight} (viewport), ${screen.width}x${screen.height} (screen)</td>
                </tr>
            </table>
        `;
    }
    
    // Test the accordion functionality
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
        button.addEventListener('click', function() {
            console.log('Accordion button clicked: ' + this.getAttribute('data-bs-target'));
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
