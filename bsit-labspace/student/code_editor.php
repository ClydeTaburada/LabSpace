<?php
session_start();
require_once '../includes/functions/auth.php';
require_once '../includes/functions/activity_functions.php';

// Check if user is logged in and is a student
requireRole('student');

// Check if activity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$activityId = (int)$_GET['id'];

// Get activity details
$activity = getStudentActivity($activityId, $_SESSION['user_id']);

// Redirect if activity not found or student is not enrolled in the class
if (!$activity) {
    header('Location: dashboard.php?error=Activity not found or not accessible');
    exit;
}

// Get previous submission if any
$submission = getSubmissionDetails($activityId, $_SESSION['user_id']);
$submittedCode = $submission ? $submission['code'] : '';

// Default to starter code if no previous submission
$initialCode = !empty($submittedCode) ? $submittedCode : $activity['coding_starter_code'];

// Determine language mode based on activity type
$languageMode = 'javascript'; // default

// Determine appropriate language based on activity or previous submission
if ($submission && $submission['language']) {
    $languageMode = $submission['language'];
} elseif (strpos(strtolower($activity['title']), 'python') !== false) {
    $languageMode = 'python';
} elseif (strpos(strtolower($activity['title']), 'java') !== false && 
          strpos(strtolower($activity['title']), 'javascript') === false) {
    $languageMode = 'java';
} elseif (strpos(strtolower($activity['title']), 'c++') !== false || 
          strpos(strtolower($activity['title']), 'cpp') !== false) {
    $languageMode = 'cpp';
} elseif (strpos(strtolower($activity['title']), 'php') !== false) {
    $languageMode = 'php';
}

// Make sure we have a default code template if nothing is provided
if (empty($initialCode)) {
    switch ($languageMode) {
        case 'python':
            $initialCode = "# Your Python code here\n\ndef main():\n    print('Hello, World!')\n\nif __name__ == '__main__':\n    main()";
            break;
        case 'java':
            $initialCode = "// Your Java code here\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println(\"Hello, World!\");\n    }\n}";
            break;
        case 'cpp':
            $initialCode = "// Your C++ code here\n#include <iostream>\n\nint main() {\n    std::cout << \"Hello, World!\" << std::endl;\n    return 0;\n}";
            break;
        case 'php':
            $initialCode = "<?php\n// Your PHP code here\necho \"Hello, World!\";\n?>";
            break;
        default:
            $initialCode = "// Your code here\nconsole.log('Hello, World!');";
    }
}

$pageTitle = "Code Editor - " . $activity['title'];
include '../includes/header.php';
?>

<!-- Add a hidden loading bar that appears when operations are in progress -->
<div id="editor-loading-bar" class="loading-bar" style="display: none;"></div>
<div id="status-message" class="alert" style="display: none; position: fixed; top: 70px; right: 20px; z-index: 1000;"></div>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($activity['title']); ?></h1>
                <div>
                    <a href="view_activity.php?id=<?php echo $activityId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Activity
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left column: Editor + Console -->
        <div class="col-lg-6 mb-4">
            <!-- Editor Card with improved styling -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                    <span><i class="fas fa-code"></i> Code Editor</span>
                    <div class="d-flex align-items-center">
                        <select id="language-select" class="form-select form-select-sm me-2" aria-label="Select language">
                            <option value="javascript" <?php echo $languageMode == 'javascript' ? 'selected' : ''; ?>>JavaScript</option>
                            <option value="html" <?php echo $languageMode == 'html' ? 'selected' : ''; ?>>HTML</option>
                            <option value="css" <?php echo $languageMode == 'css' ? 'selected' : ''; ?>>CSS</option>
                            <option value="php" <?php echo $languageMode == 'php' ? 'selected' : ''; ?>>PHP</option>
                            <option value="python" <?php echo $languageMode == 'python' ? 'selected' : ''; ?>>Python</option>
                            <option value="java" <?php echo $languageMode == 'java' ? 'selected' : ''; ?>>Java</option>
                            <option value="cpp" <?php echo $languageMode == 'cpp' ? 'selected' : ''; ?>>C++</option>
                        </select>
                        <button id="editor-settings-btn" class="btn btn-sm btn-outline-secondary" title="Editor settings">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 editor-container">
                    <!-- The editor container with improved dimensions -->
                    <div id="editor" class="ace-editor"><?php echo htmlspecialchars($initialCode); ?></div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between flex-wrap">
                        <div class="btn-group mb-2 mb-md-0">
                            <button id="run-code" class="btn btn-primary">
                                <i class="fas fa-play me-1"></i> Run Code
                            </button>
                            <button id="format-code" class="btn btn-outline-secondary">
                                <i class="fas fa-indent me-1"></i> Format
                            </button>
                        </div>
                        <div>
                            <button id="submit-code" class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Console Output with improved styling -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-light py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-terminal me-1"></i> Console Output</span>
                        <button id="clear-console" class="btn btn-sm btn-outline-light" title="Clear console">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="console-output" class="p-3 bg-dark text-light custom-scrollbar console-font" style="min-height: 180px; max-height: 250px; overflow-y: auto;">
                        <!-- Console output will be displayed here -->
                        <div class="text-muted small">Run your code to see output here...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column: Preview + Instructions -->
        <div class="col-lg-6 mb-4">
            <!-- Preview Card with improved styling -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                    <span><i class="fas fa-desktop me-1"></i> Output Preview</span>
                    <div>
                        <button id="reload-preview" class="btn btn-sm btn-outline-secondary me-1" title="Reload preview">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button id="toggle-fullscreen" class="btn btn-sm btn-outline-secondary" title="Toggle fullscreen">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="output-wrapper" class="preview-container">
                        <iframe id="preview-frame" style="width:100%; height:400px; border:none;" title="Code preview"></iframe>
                    </div>
                </div>
            </div>

            <!-- Instructions Card with improved styling -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <i class="fas fa-info-circle me-1"></i> Instructions
                </div>
                <div class="card-body">
                    <div class="custom-scrollbar" style="max-height: 350px; overflow-y: auto;">
                        <?php echo nl2br(htmlspecialchars($activity['instructions'])); ?>
                        
                        <?php if (!empty($activity['test_cases'])): ?>
                        <div class="mt-3 p-2 bg-light rounded">
                            <h6 class="mb-2"><i class="fas fa-vial me-1"></i> Test Cases:</h6>
                            <pre class="bg-light p-2 mb-0 small"><?php echo htmlspecialchars($activity['test_cases']); ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($submission): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i> You already have a submission for this activity.
                        <?php if ($submission['graded']): ?>
                        <br>Grade: <strong><?php echo $submission['grade']; ?>%</strong>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Keyboard shortcuts help card -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-3 collapsed-card" id="shortcuts-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 cursor-pointer" onclick="toggleCard('shortcuts-card')">
                    <h5 class="mb-0"><i class="fas fa-keyboard me-1"></i> Keyboard Shortcuts</h5>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div class="card-body" style="display: none;">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="shortcut-item d-flex justify-content-between">
                                <span>Run Code</span>
                                <kbd>Ctrl+Enter</kbd>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="shortcut-item d-flex justify-content-between">
                                <span>Format Code</span>
                                <kbd>Ctrl+Shift+F</kbd>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="shortcut-item d-flex justify-content-between">
                                <span>Find</span>
                                <kbd>Ctrl+F</kbd>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="shortcut-item d-flex justify-content-between">
                                <span>Replace</span>
                                <kbd>Ctrl+H</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submission Modal with improved styling -->
<div class="modal fade" id="submit-modal" tabindex="-1" aria-labelledby="submit-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="submit-modal-label">Submit Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i> 
                    Are you sure you want to submit your code? This will be recorded as your submission for grading.
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirm-submission">
                    <label class="form-check-label" for="confirm-submission">
                        I confirm my code is complete and ready for submission
                    </label>
                </div>
                <div id="submit-message" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-submit-btn" disabled>Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Editor Settings Modal -->
<div class="modal fade" id="editor-settings-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Editor Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editor-theme" class="form-label">Theme</label>
                    <select id="editor-theme" class="form-select">
                        <option value="monokai">Monokai (Dark)</option>
                        <option value="github">GitHub (Light)</option>
                        <option value="tomorrow_night">Tomorrow Night (Dark)</option>
                        <option value="textmate">Textmate (Light)</option>
                        <option value="dracula">Dracula</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editor-font-size" class="form-label">Font Size</label>
                    <input type="range" class="form-range" id="editor-font-size" min="12" max="24" step="1" value="14">
                    <div class="text-center" id="font-size-value">14px</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="editor-wrap" checked>
                    <label class="form-check-label" for="editor-wrap">Word Wrap</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="editor-autocomplete" checked>
                    <label class="form-check-label" for="editor-autocomplete">Auto-completion</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-settings">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<!-- Load Ace Editor with improved reliability -->
<!-- Remove integrity attributes that are causing issues -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.0/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.0/ext-language_tools.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.0/ext-beautify.js"></script>

<!-- Add a fallback for CDN failure -->
<script>
// Check if Ace loaded correctly, if not, try an alternative CDN
if (typeof ace === 'undefined') {
    console.warn('Primary Ace Editor CDN failed, trying alternative source...');
    
    // Create and append alternative script sources
    const scriptSources = [
        'https://unpkg.com/ace-builds@1.23.0/src-min-noconflict/ace.js',
        'https://unpkg.com/ace-builds@1.23.0/src-min-noconflict/ext-language_tools.js',
        'https://unpkg.com/ace-builds@1.23.0/src-min-noconflict/ext-beautify.js'
    ];
    
    scriptSources.forEach(src => {
        const script = document.createElement('script');
        script.src = src;
        script.async = false; // Keep execution order
        document.head.appendChild(script);
    });
    
    // Check again after a short delay and if still failed, show error message
    setTimeout(() => {
        if (typeof ace === 'undefined') {
            document.getElementById('editor').innerHTML = `
                <div class="alert alert-danger p-3">
                    <h5>Error: Code Editor Failed to Load</h5>
                    <p>We couldn't load the code editor component. This might be due to:</p>
                    <ul>
                        <li>Network connectivity issues</li>
                        <li>Content blocking by your browser or network</li>
                        <li>Temporary CDN outage</li>
                    </ul>
                    <p>Please try refreshing the page or try again later.</p>
                    <button class="btn btn-primary mt-2" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh Page
                    </button>
                </div>
            `;
        }
    }, 2000);
}
</script>

<link rel="stylesheet" href="../assets/css/code-editor.css">
<script src="../assets/js/code-editor-helpers.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize editor with improved error handling and delayed initialization
    let editor;
    let editorInitAttempts = 0;
    const maxInitAttempts = 3;
    
    function initializeEditor() {
        if (editorInitAttempts >= maxInitAttempts) {
            console.error('Failed to initialize editor after multiple attempts');
            document.getElementById('editor').innerHTML = '<div class="alert alert-danger p-3">Error loading code editor after multiple attempts. Please refresh the page or try a different browser.</div>';
            return;
        }
        
        editorInitAttempts++;
        
        try {
            // Check if Ace is loaded
            if (typeof ace === 'undefined') {
                console.warn(`Ace not loaded yet (attempt ${editorInitAttempts}), retrying in 500ms...`);
                setTimeout(initializeEditor, 500);
                return;
            }
            
            // Initialize Ace editor
            editor = ace.edit("editor");
            editor.setTheme("ace/theme/monokai");
            editor.session.setMode("ace/mode/<?php echo $languageMode; ?>");
            
            // Enable auto-completion and advanced features
            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
                fontSize: "14px",
                showPrintMargin: false,
                highlightActiveLine: true,
                wrap: true
            });
            
            // Make editor globally accessible
            window.editor = editor;
            
            // Load saved code from local storage if available
            if (window.loadCodeFromLocalStorage) {
                window.loadCodeFromLocalStorage(editor, <?php echo $activityId; ?>, editor.getValue());
            }
            
            // Auto-save every 30 seconds
            setInterval(function() {
                if (window.saveCodeToLocalStorage) {
                    window.saveCodeToLocalStorage(editor, <?php echo $activityId; ?>);
                }
            }, 30000);
            
            console.log('Editor initialized successfully');
            
            // Load editor settings
            loadEditorSettings();
            
        } catch (e) {
            console.error('Error initializing editor:', e);
            
            if (editorInitAttempts < maxInitAttempts) {
                console.warn(`Retrying editor initialization in 800ms (attempt ${editorInitAttempts})...`);
                setTimeout(initializeEditor, 800);
            } else {
                document.getElementById('editor').innerHTML = '<div class="alert alert-danger p-3">Error loading code editor. Please refresh the page or try a different browser.</div>';
                
                // Add a retry button
                const retryBtn = document.createElement('button');
                retryBtn.className = 'btn btn-warning mt-2';
                retryBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Retry Loading Editor';
                retryBtn.onclick = function() { window.location.reload(); };
                document.getElementById('editor').appendChild(retryBtn);
            }
        }
    }
    
    // Load editor settings
    function loadEditorSettings() {
        try {
            if (editor) {
                const theme = localStorage.getItem('editor_theme') || 'monokai';
                const fontSize = localStorage.getItem('editor_font_size') || '14';
                const wordWrap = localStorage.getItem('editor_word_wrap') !== 'false';
                const autocomplete = localStorage.getItem('editor_autocomplete') !== 'false';
                
                // Set form values
                const themeEl = document.getElementById('editor-theme');
                const fontSizeEl = document.getElementById('editor-font-size');
                const fontSizeValueEl = document.getElementById('font-size-value');
                const wordWrapEl = document.getElementById('editor-wrap');
                const autocompleteEl = document.getElementById('editor-autocomplete');
                
                if (themeEl) themeEl.value = theme;
                if (fontSizeEl) fontSizeEl.value = fontSize;
                if (fontSizeValueEl) fontSizeValueEl.textContent = `${fontSize}px`;
                if (wordWrapEl) wordWrapEl.checked = wordWrap;
                if (autocompleteEl) autocompleteEl.checked = autocomplete;
                
                // Apply settings to editor
                editor.setTheme(`ace/theme/${theme}`);
                editor.setFontSize(`${fontSize}px`);
                editor.session.setUseWrapMode(wordWrap);
                editor.setOptions({
                    enableBasicAutocompletion: autocomplete,
                    enableLiveAutocompletion: autocomplete
                });
            }
        } catch (e) {
            console.error('Error loading editor settings:', e);
        }
    }
    
    // Start editor initialization after a short delay to ensure DOM and scripts are loaded
    setTimeout(initializeEditor, 300);

    // Language selector - with improved error handling
    const languageSelect = document.getElementById('language-select');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            if (!editor || !editor.session) {
                console.error('Cannot change language: Editor not initialized');
                return;
            }
            
            try {
                const mode = "ace/mode/" + this.value;
                editor.session.setMode(mode);
                
                // Save the language preference
                localStorage.setItem(`language_preference_${<?php echo $activityId; ?>}`, this.value);
                
                // Update the console output
                const consoleOutput = document.getElementById('console-output');
                if (consoleOutput) {
                    consoleOutput.innerHTML = `<div class="text-info small py-1"><i class="fas fa-info-circle me-1"></i> Language changed to ${this.value}</div>`;
                }
            } catch (e) {
                console.error('Error changing language mode:', e);
            }
        });
    }

    // Run code function with null checks for editor
    function runCode() {
        if (!editor || typeof editor.getValue !== 'function') {
            console.error('Cannot run code: Editor not properly initialized');
            const consoleOutput = document.getElementById('console-output');
            if (consoleOutput) {
                consoleOutput.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> Error: Code editor not initialized. Please refresh the page.</div>';
            }
            return;
        }
        
        const code = editor.getValue();
        const language = document.getElementById('language-select').value;
        const consoleOutput = document.getElementById('console-output');
        const runButton = document.getElementById('run-code');
        
        // Show loading state
        if (window.showLoadingState) {
            window.showLoadingState('Running code...');
        } else {
            runButton.disabled = true;
            runButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
            consoleOutput.innerHTML = '<div class="text-muted">Running code...</div>';
        }
        
        // Reset and clear any previous console messages
        window._lastConsoleMessages = [];
        
        try {
            // Handle based on language type
            if (language === 'html' || language === 'css' || language === 'javascript') {
                // IMPORTANT: Create a new iframe instead of reusing the existing one
                // This completely isolates each execution and prevents state leakage
                try {
                    // Get parent container
                    const container = document.getElementById('output-wrapper');
                    if (!container) {
                        throw new Error('Output container not found');
                    }
                    
                    // Get the existing iframe reference before removing it
                    const previewFrame = document.getElementById('preview-frame');
                    
                    // Remove old iframe completely
                    if (previewFrame) {
                        try {
                            // Try to stop any ongoing operations in the iframe
                            if (previewFrame.contentWindow) {
                                previewFrame.contentWindow.stop();
                            }
                            previewFrame.remove();
                        } catch (e) {
                            console.warn('Error removing old iframe:', e);
                        }
                    }
                    
                    // Create a new iframe with a unique name to prevent caching issues
                    const newFrame = document.createElement('iframe');
                    newFrame.id = 'preview-frame';
                    newFrame.style = 'width:100%; height:400px; border:none;';
                    newFrame.title = 'Code preview';
                    newFrame.sandbox = 'allow-scripts allow-same-origin';  // Important security feature
                    newFrame.name = 'preview_' + Date.now(); // Unique name
                    container.appendChild(newFrame);
                    
                    // Get a reference to the new iframe
                    const frameDoc = newFrame.contentDocument || newFrame.contentWindow.document;
                    
                    // Create a bundle with all the code and necessary setup
                    let htmlContent = '';
                    
                    if (language === 'html') {
                        // For HTML, just use the code directly but add validation warnings
                        if (!code.toLowerCase().includes('<!doctype html>') && !code.toLowerCase().includes('<html')) {
                            consoleOutput.innerHTML += '<div class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Your HTML may be missing doctype or html tags</div>';
                        }
                        htmlContent = code;
                    } 
                    else if (language === 'javascript') {
                        // For JavaScript, create a safe execution environment
                        htmlContent = `
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset="UTF-8">
                                <title>JavaScript Output</title>
                                <style>
                                    body { 
                                        font-family: sans-serif; 
                                        padding: 20px;
                                        color: #333;
                                    }
                                    .output { 
                                        white-space: pre-wrap;
                                        background-color: #f5f5f5;
                                        padding: 10px;
                                        border-radius: 4px;
                                        border: 1px solid #ddd;
                                        min-height: 50px;
                                    }
                                    .error {
                                        color: #e74c3c;
                                        margin-top: 10px;
                                        padding: 8px;
                                        background-color: #ffeaea;
                                        border-radius: 4px;
                                    }
                                </style>
                            </head>
                            <body>
                                <h4>JavaScript Output:</h4>
                                <div id="output" class="output"></div>
                                <div id="error" class="error" style="display: none;"></div>
                            </body>
                            </html>
                        `;
                    }
                    else if (language === 'css') {
                        // For CSS, create a preview with sample elements
                        htmlContent = `
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset="UTF-8">
                                <title>CSS Preview</title>
                                <style>
                                    /* Base styles for preview */
                                    body {
                                        font-family: sans-serif;
                                        padding: 20px;
                                        margin: 10px;
                                    }
                                    .sample-element, .box {
                                        margin: 20px 0;
                                        padding: 15px;
                                        border: 1px dashed #ccc;
                                    }
                                    /* User CSS will be injected via JS */
                                </style>
                            </head>
                            <body>
                                <h4>CSS Preview</h4>
                                <p>The following elements can be styled with your CSS:</p>
                                <div class="container">
                                    <div class="sample-element">Sample Element</div>
                                    <div class="box">Box Element</div>
                                    <p class="text">Text Element</p>
                                    <button class="button">Button Element</button>
                                </div>
                                <div id="css-error" style="color: red; margin-top: 20px; display: none;"></div>
                            </body>
                            </html>
                        `;
                    }
                    
                    // Write the initial HTML content
                    frameDoc.open();
                    frameDoc.write(htmlContent);
                    frameDoc.close();
                    
                    // Add a small delay to ensure the document is fully loaded before executing scripts
                    setTimeout(() => {
                        try {
                            if (language === 'javascript') {
                                // Execute JavaScript in the isolated environment
                                const scriptEl = frameDoc.createElement('script');
                                scriptEl.textContent = `
                                    // Set up safe console logging
                                    (function() {
                                        const output = document.getElementById('output');
                                        const originalConsole = console.log;
                                        
                                        // Create a queue for messages that will be sent to parent
                                        const messageQueue = [];
                                        let sendingMessages = false;
                                        
                                        // Send messages to parent in batches
                                        function flushMessages() {
                                            if (sendingMessages || messageQueue.length === 0) return;
                                            
                                            sendingMessages = true;
                                            try {
                                                window.parent.postMessage({
                                                    type: 'console-batch',
                                                    content: messageQueue.slice()
                                                }, '*');
                                                messageQueue.length = 0;
                                            } catch (e) {
                                                console.error('Error sending messages to parent:', e);
                                            }
                                            sendingMessages = false;
                                        }
                                        
                                        // Send messages periodically
                                        setInterval(flushMessages, 100);
                                        
                                        // Override console.log
                                        console.log = function(...args) {
                                            // Call original to ensure browser console still works
                                            originalConsole.apply(console, args);
                                            
                                            try {
                                                if (output) {
                                                    const text = args.map(arg => {
                                                        if (typeof arg === 'object') {
                                                            try {
                                                                return JSON.stringify(arg, null, 2);
                                                            } catch (e) {
                                                                return String(arg);
                                                            }
                                                        }
                                                        return String(arg);
                                                    }).join(' ');
                                                    
                                                    output.textContent += text + '\\n';
                                                    
                                                    // Queue the message to be sent to parent
                                                    messageQueue.push(text);
                                                }
                                            } catch (e) {
                                                // Silent fail
                                            }
                                        };
                                        
                                        // Set up error handling
                                        window.onerror = function(message, url, line, col) {
                                            const errorMsg = 'Error: ' + message + ' (line ' + line + ')';
                                            const errorElem = document.getElementById('error');
                                            if (errorElem) {
                                                errorElem.textContent = errorMsg;
                                                errorElem.style.display = 'block';
                                            }
                                            
                                            try {
                                                window.parent.postMessage({
                                                    type: 'error',
                                                    content: errorMsg
                                                }, '*');
                                            } catch (e) {
                                                // Silent fail
                                            }
                                            
                                            return true; // Prevent default handling
                                        };
                                        
                                        // Execute user code in a safe way
                                        try {
                                            ${code}
                                        } catch(e) {
                                            console.log('Error:', e.message);
                                            const errorElem = document.getElementById('error');
                                            if (errorElem) {
                                                errorElem.textContent = 'Error: ' + e.message;
                                                errorElem.style.display = 'block';
                                            }
                                            
                                            try {
                                                window.parent.postMessage({
                                                    type: 'error',
                                                    content: 'Error: ' + e.message
                                                }, '*');
                                            } catch (err) {
                                                // Silent fail
                                            }
                                        }
                                    })();
                                `;
                                frameDoc.body.appendChild(scriptEl);
                            } else if (language === 'css') {
                                // Apply CSS safely
                                const styleEl = frameDoc.createElement('style');
                                styleEl.textContent = code;
                                frameDoc.head.appendChild(styleEl);
                                
                                // Add error detection script
                                const scriptEl = frameDoc.createElement('script');
                                scriptEl.textContent = `
                                    // Check for CSS errors
                                    window.onerror = function(message) {
                                        const errorElem = document.getElementById('css-error');
                                        if (errorElem) {
                                            errorElem.style.display = 'block';
                                            errorElem.textContent = 'CSS Error: ' + message;
                                        }
                                        
                                        try {
                                            window.parent.postMessage({
                                                type: 'error',
                                                content: 'CSS Error: ' + message
                                            }, '*');
                                        } catch (e) {
                                            // Silent fail
                                        }
                                        
                                        return true;
                                    };
                                `;
                                frameDoc.body.appendChild(scriptEl);
                            }
                            
                            consoleOutput.innerHTML += '<div class="text-success"><i class="fas fa-check-circle me-1"></i> Code executed successfully</div>';
                        } catch (codeExecError) {
                            console.error('Error executing code:', codeExecError);
                            consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> Error executing code: ${codeExecError.message}</div>`;
                        }
                        
                        // Reset loading state regardless of execution success
                        if (window.hideLoadingState) {
                            window.hideLoadingState();
                        } else {
                            runButton.disabled = false;
                            runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
                        }
                    }, 200);
                } catch (setupError) {
                    console.error('Error setting up execution environment:', setupError);
                    consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> Error preparing environment: ${setupError.message}</div>`;
                    
                    // Reset loading state
                    if (window.hideLoadingState) {
                        window.hideLoadingState();
                    } else {
                        runButton.disabled = false;
                        runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
                    }
                }
            } else {
                // Server-side execution
                fetch('../includes/execute_code.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        code: code,
                        language: language
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> ${data.error}</div>`;
                    } else {
                        consoleOutput.innerHTML += `<div class="text-success"><i class="fas fa-check-circle me-1"></i> Output:</div><pre class="bg-dark text-light p-2 mt-2 rounded">${data.output || '(No output)'}</pre>`;
                        
                        // Also display in iframe for visual output
                        const container = document.getElementById('output-wrapper');
                        const oldFrame = document.getElementById('preview-frame');
                        if (oldFrame) oldFrame.remove();
                        
                        const newFrame = document.createElement('iframe');
                        newFrame.id = 'preview-frame';
                        newFrame.style = 'width:100%; height:400px; border:none;';
                        newFrame.title = 'Code preview';
                        container.appendChild(newFrame);
                        
                        const frameDoc = newFrame.contentDocument || newFrame.contentWindow.document;
                        frameDoc.open();
                        frameDoc.write(`
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <style>
                                    body { 
                                        font-family: monospace; 
                                        padding: 20px; 
                                        white-space: pre-wrap;
                                        line-height: 1.5;
                                    }
                                    .output {
                                        background-color: #f8f9fa;
                                        padding: 15px;
                                        border-radius: 4px;
                                        border: 1px solid #e9ecef;
                                    }
                                </style>
                            </head>
                            <body><div class="output">${data.output || '(No output)'}</div></body>
                            </html>
                        `);
                        frameDoc.close();
                    }
                    
                    if (window.hideLoadingState) {
                        window.hideLoadingState();
                    } else {
                        runButton.disabled = false;
                        runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
                    }
                })
                .catch(error => {
                    consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> Error: ${error.message}</div>`;
                    if (window.hideLoadingState) {
                        window.hideLoadingState();
                    } else {
                        runButton.disabled = false;
                        runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
                    }
                });
            }
        } catch (e) {
            console.error('Global error in runCode:', e);
            consoleOutput.innerHTML += `<div class="text-danger"><i class="fas fa-times-circle me-1"></i> Error: ${e.message}</div>`;
            if (window.hideLoadingState) {
                window.hideLoadingState();
            } else {
                runButton.disabled = false;
                runButton.innerHTML = '<i class="fas fa-play me-1"></i> Run Code';
            }
        }
    }
    
    // Add run button event listener
    const runButton = document.getElementById('run-code');
    if (runButton) {
        runButton.addEventListener('click', function() {
            runCode();
        });
    }
    
    // Add reload preview button event listener
    const reloadPreviewButton = document.getElementById('reload-preview');
    if (reloadPreviewButton) {
        reloadPreviewButton.addEventListener('click', function() {
            runCode();
        });
    }
    
    // Format code button
    const formatButton = document.getElementById('format-code');
    if (formatButton) {
        formatButton.addEventListener('click', function() {
            if (window.formatCode) {
                window.formatCode(editor);
            } else {
                console.warn('Format function not found');
                // Basic formatting fallback using Ace's built-in beautify
                try {
                    const beautify = ace.require("ace/ext/beautify");
                    if (beautify && typeof beautify.beautify === 'function') {
                        beautify.beautify(editor.session);
                    }
                } catch (e) {
                    console.error('Error formatting code:', e);
                }
            }
        });
    }
    
    // Add other missing button event listeners
    const clearConsoleButton = document.getElementById('clear-console');
    if (clearConsoleButton) {
        clearConsoleButton.addEventListener('click', function() {
            const consoleOutput = document.getElementById('console-output');
            if (consoleOutput) {
                consoleOutput.innerHTML = '<div class="text-muted small">Console cleared</div>';
            }
        });
    }
    
    // Submit code button
    const submitButton = document.getElementById('submit-code');
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('submit-modal'));
            modal.show();
        });
    }
    
    // Confirm submission
    const confirmSubmission = document.getElementById('confirm-submission');
    const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
    if (confirmSubmission && confirmSubmitBtn) {
        confirmSubmission.addEventListener('change', function() {
            confirmSubmitBtn.disabled = !this.checked;
        });
        
        confirmSubmitBtn.addEventListener('click', function() {
            submitCode();
        });
    }
    
    // Add the missing submitCode function
    function submitCode() {
        if (!editor || typeof editor.getValue !== 'function') {
            console.error('Cannot submit code: Editor not properly initialized');
            const submitMessage = document.getElementById('submit-message');
            if (submitMessage) {
                submitMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-1"></i> Error: Code editor not initialized. Please refresh the page.
                    </div>
                `;
            }
            return;
        }
        
        const code = editor.getValue();
        const language = document.getElementById('language-select').value;
        const submitMessage = document.getElementById('submit-message');
        const confirmSubmitBtn = document.getElementById('confirm-submit-btn');
        
        confirmSubmitBtn.disabled = true;
        submitMessage.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin me-1"></i> Submitting code...
            </div>
        `;
        
        // Update the path to the correct location
        fetch('../includes/functions/submit_activity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                activity_id: <?php echo $activityId; ?>,
                code: code,
                language: language
            })
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error(`Cannot find submission endpoint (404). Please contact your instructor.`);
                }
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                submitMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-1"></i> ${data.error}
                    </div>
                `;
                confirmSubmitBtn.disabled = false;
            } else {
                submitMessage.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-1"></i> Code submitted successfully!
                    </div>
                `;
                
                // Remove backup
                try {
                    localStorage.removeItem(`code_backup_${<?php echo $activityId; ?>}`);
                    localStorage.removeItem(`code_backup_time_${<?php echo $activityId; ?>}`);
                } catch (e) {
                    console.error('Error removing backup:', e);
                }
                
                // Redirect after a delay
                setTimeout(() => {
                    window.location.href = `view_activity.php?id=${<?php echo $activityId; ?>}&submitted=1`;
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            submitMessage.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i> Error: ${error.message}
                    <div class="mt-2 small">
                        If this error persists, please try:
                        <ul class="mb-0 ms-3">
                            <li>Refreshing the page</li>
                            <li>Copying your code to a text file</li>
                            <li>Contacting your instructor</li>
                        </ul>
                    </div>
                </div>
            `;
            confirmSubmitBtn.disabled = false;
        });
    }
    
    // Toggle fullscreen
    const fullscreenButton = document.getElementById('toggle-fullscreen');
    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', function() {
            const outputWrapper = document.getElementById('output-wrapper');
            if (outputWrapper.classList.contains('fullscreen')) {
                outputWrapper.classList.remove('fullscreen');
                this.innerHTML = '<i class="fas fa-expand"></i>';
            } else {
                outputWrapper.classList.add('fullscreen');
                this.innerHTML = '<i class="fas fa-compress"></i>';
            }
        });
    }

    // Add keyboard shortcuts for consistency
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter to run code
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            runCode();
        }
        
        // Ctrl+Shift+F to format code
        if (e.ctrlKey && e.shiftKey && e.key === 'F') {
            e.preventDefault();
            if (window.formatCode && editor) {
                window.formatCode(editor);
            }
        }
    });
});
</script>

<style>
/* Additional styles to fix potential UI issues */
#editor {
    min-height: 400px;
    border-radius: 0;
}

.cursor-pointer {
    cursor: pointer;
}

.collapsed-card .card-header {
    border-bottom: 0;
}

@media (max-width: 992px) {
    #editor, #preview-frame {
        height: 350px !important;
    }
}

@media (max-width: 768px) {
    #editor, #preview-frame {
        height: 300px !important;
    }
    
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}
</style>

<?php include '../includes/footer.php'; ?>