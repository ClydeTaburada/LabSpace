<!-- Emergency Navigation Panel -->
<div id="emergency-navigation" class="position-fixed bottom-0 end-0 p-3 bg-white shadow" style="display:none; z-index:9999; max-width:350px; border-radius:5px;">
    <div class="card border-0">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0 d-flex align-items-center justify-content-between">
                <span><i class="fas fa-exclamation-triangle me-2"></i> Emergency Navigation</span>
                <button id="hide-emergency" class="btn btn-sm btn-outline-light">Hide</button>
            </h6>
        </div>
        <div class="card-body">
            <p class="small text-muted">If you're having trouble opening activities, use this panel:</p>
            
            <div class="input-group mb-3">
                <input type="number" id="emergency-activity-id" class="form-control" placeholder="Enter activity ID">
                <button class="btn btn-outline-primary activity-go-btn">Go</button>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <button id="clear-loading" class="btn btn-sm btn-outline-secondary">Clear Loading</button>
                <span class="small text-muted">You can open this panel anytime by pressing Alt+A</span>
            </div>
        </div>
    </div>
</div>

<!-- Include the navigation helper -->
<script src="<?php echo getBaseUrl(); ?>assets/js/activity-navigation.js"></script>
