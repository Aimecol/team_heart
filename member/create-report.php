<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Member.php';
require_once '../models/Report.php';

requireMember();

$user_id = getCurrentUserId();
$db_connection = getDBConnection();

$user_model = new User($db_connection);
$member_model = new Member($db_connection);
$report_model = new Report($db_connection);

// Get current user profile
$user = getCurrentUser();
$member = $member_model->getByUserId($user_id);

// Get user's missions for dropdown
$query = "SELECT ma.authorization_id, ma.authorization_number, ma.mission_purpose, ma.destination
          FROM mission_authorizations ma
          WHERE ma.user_id = :user_id AND ma.status IN ('approved', 'completed')
          ORDER BY ma.departure_date DESC";
$stmt = $db_connection->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $authorization_id = intval($_POST['mission_id'] ?? 0);
        $action = $_POST['action'] ?? 'draft';
        
        // Validate inputs
        if (empty($title)) {
            $error = 'Report title is required.';
        } elseif (empty($content)) {
            $error = 'Report content is required.';
        } elseif ($authorization_id <= 0) {
            $error = 'Please select a mission.';
        } else {
            // Create report
            $report_data = [
                'authorization_id' => $authorization_id,
                'member_id' => $member['member_id'],
                'title' => $title,
                'content' => $content,
                'description' => substr(strip_tags($content), 0, 500),
                'status' => $action === 'submit' ? 'submitted' : 'draft',
                'report_type' => 'mission',
                'priority' => 'normal'
            ];
            
            $report_id = $report_model->create($report_data, $user_id, $member['member_id']);
            
            if ($report_id) {
                // Handle file uploads if present
                if (!empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                    $file_count = count($_FILES['attachments']['name']);
                    for ($i = 0; $i < $file_count; $i++) {
                        if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['attachments']['name'][$i],
                                'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                                'size' => $_FILES['attachments']['size'][$i],
                                'error' => $_FILES['attachments']['error'][$i]
                            ];
                            
                            $upload_result = $report_model->uploadAttachment(
                                $report_id,
                                $file,
                                $_POST['attachment_description'][$i] ?? ''
                            );
                            
                            if (!$upload_result['success']) {
                                error_log("File upload failed: " . $upload_result['message']);
                            }
                        }
                    }
                }
                
                if ($action === 'submit') {
                    $success = 'Report submitted successfully! You can view it in My Reports.';
                } else {
                    $success = 'Report saved as draft. You can edit it later.';
                }
                setFlashMessage('success', $success);
                header('Location: ./my-reports.php');
                exit;
            } else {
                $error = 'Failed to create report. Please try again.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Mission Report - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .editor-container {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            background-color: #fafafa;
            align-items: center;
        }

        .toolbar-group {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .toolbar-separator {
            width: 1px;
            height: 32px;
            background-color: #ddd;
            margin: 0 4px;
        }

        .toolbar-button {
            width: 36px;
            height: 36px;
            padding: 6px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
        }

        .toolbar-button:hover {
            background-color: #e8e8e8;
            border-color: #999;
        }

        .toolbar-button.active {
            background-color: #2563eb;
            color: white;
            border-color: #1d4ed8;
        }

        .select-input {
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
        }

        .editor-content {
            min-height: 400px;
            padding: 16px;
            outline: none;
            overflow-y: auto;
        }

        .editor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-top: 1px solid #ddd;
            background-color: #fafafa;
            font-size: 12px;
            color: #666;
        }

        .editor-stats {
            display: flex;
            gap: 16px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #333;
        }

        .modal-body {
            margin-bottom: 16px;
        }

        .modal-input, .modal-textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }

        .modal-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .modal-button.primary {
            background-color: #2563eb;
            color: white;
        }

        .modal-button.primary:hover {
            background-color: #1d4ed8;
        }

        .modal-button.secondary {
            background-color: #e5e7eb;
            color: #333;
        }

        .modal-button.secondary:hover {
            background-color: #d1d5db;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .editor-toolbar {
                gap: 2px;
            }

            .toolbar-button {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .toolbar-separator {
                display: none;
            }

            .editor-content {
                min-height: 300px;
            }
        }

        .ql-table {
            border-collapse: collapse;
            margin: 10px 0;
        }

        .ql-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        blockquote {
            border-left: 4px solid #2563eb;
            margin-left: 0;
            padding-left: 16px;
            color: #666;
        }

        code {
            background-color: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
        }

        pre {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }

        pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Mission Report</h1>
            <p class="text-gray-600">Document your mission activities and experiences with rich text formatting and file attachments.</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Mission Selection -->
            <div class="bg-white p-6 rounded-lg shadow">
                <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Mission <span class="text-red-500">*</span>
                </label>
                <select name="mission_id" id="mission_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Choose a mission --</option>
                    <?php foreach ($missions as $mission): ?>
                        <option value="<?php echo htmlspecialchars($mission['authorization_id']); ?>">
                            <?php echo htmlspecialchars($mission['authorization_number'] . ' - ' . $mission['mission_purpose']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($missions)): ?>
                    <p class="mt-2 text-sm text-amber-600">No approved missions available. Complete a mission first to create a report.</p>
                <?php endif; ?>
            </div>

            <!-- Report Title -->
            <div class="bg-white p-6 rounded-lg shadow">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Report Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" placeholder="Enter report title..." required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    maxlength="500">
            </div>

            <!-- WYSIWYG Editor -->
            <div class="bg-white rounded-lg shadow">
                <label for="editor" class="block text-sm font-medium text-gray-700 px-6 pt-6 mb-2">
                    Report Content <span class="text-red-500">*</span>
                </label>

                <div class="editor-container mx-6 mb-6">
                    <div class="editor-toolbar">
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="formatBlock" data-value="h1" title="Heading 1">H1</button>
                            <button type="button" class="toolbar-button" data-command="formatBlock" data-value="h2" title="Heading 2">H2</button>
                            <button type="button" class="toolbar-button" data-command="formatBlock" data-value="h3" title="Heading 3">H3</button>
                            <button type="button" class="toolbar-button" data-command="formatBlock" data-value="p" title="Paragraph">¶</button>
                        </div>

                        <div class="toolbar-separator"></div>

                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="bold" title="Bold"><strong>B</strong></button>
                            <button type="button" class="toolbar-button" data-command="italic" title="Italic"><em>I</em></button>
                            <button type="button" class="toolbar-button" data-command="underline" title="Underline"><u>U</u></button>
                            <button type="button" class="toolbar-button" data-command="strikeThrough" title="Strikethrough"><s>S</s></button>
                        </div>

                        <div class="toolbar-separator"></div>

                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="insertUnorderedList" title="Bullet List">•</button>
                            <button type="button" class="toolbar-button" data-command="insertOrderedList" title="Numbered List">1.</button>
                        </div>

                        <div class="toolbar-separator"></div>

                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="createLink" title="Insert Link">🔗</button>
                            <button type="button" class="toolbar-button" data-command="insertImage" title="Insert Image">🖼️</button>
                            <button type="button" class="toolbar-button" data-command="insertTable" title="Insert Table">📊</button>
                        </div>

                        <div class="toolbar-separator"></div>

                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="justifyLeft" title="Align Left">⬅</button>
                            <button type="button" class="toolbar-button" data-command="justifyCenter" title="Align Center">⬍</button>
                            <button type="button" class="toolbar-button" data-command="justifyRight" title="Align Right">➡</button>
                            <button type="button" class="toolbar-button" data-command="justifyFull" title="Justify">⤛</button>
                        </div>

                        <div class="toolbar-separator"></div>

                        <div class="toolbar-group">
                            <button type="button" class="toolbar-button" data-command="undo" title="Undo">↶</button>
                            <button type="button" class="toolbar-button" data-command="redo" title="Redo">↷</button>
                            <button type="button" class="toolbar-button" data-command="removeFormat" title="Clear Formatting">✕</button>
                        </div>
                    </div>

                    <div id="editor" class="editor-content" contenteditable="true" required></div>

                    <div class="editor-footer">
                        <span>✓ Professional rich-text editor with full formatting support</span>
                        <div class="editor-stats">
                            <span>Words: <span id="word-count">0</span></span>
                            <span>Characters: <span id="char-count">0</span></span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="content" id="content-input">
            </div>

            <!-- File Attachments -->
            <div class="bg-white p-6 rounded-lg shadow">
                <label class="block text-sm font-medium text-gray-700 mb-4">
                    📎 Attach Supporting Documents (Optional)
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:bg-gray-100 transition" id="drop-zone">
                    <div class="text-gray-600 mb-2">
                        <p class="text-lg font-medium">Drag and drop files here</p>
                        <p class="text-sm">or</p>
                    </div>
                    <input type="file" name="attachments[]" id="file-input" multiple class="hidden" accept=".pdf,.docx,.doc,.xlsx,.jpg,.jpeg,.png,.txt,.zip">
                    <label for="file-input" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-blue-700">
                        Browse Files
                    </label>
                    <p class="text-xs text-gray-500 mt-4">Accepted: PDF, DOCX, XLSX, JPG, PNG, TXT, ZIP (Max 10MB per file)</p>
                </div>
                <div id="file-list" class="mt-4 space-y-2"></div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white p-6 rounded-lg shadow flex gap-4 justify-between">
                <a href="./my-reports.php" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="draft" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition">
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Submit Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modals -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Insert Link</div>
            <div class="modal-body">
                <input type="text" id="linkUrl" class="modal-input" placeholder="https://example.com" value="https://">
                <input type="text" id="linkText" class="modal-input" placeholder="Link text (optional)">
            </div>
            <div class="modal-buttons">
                <button class="modal-button secondary" onclick="closeLinkModal()">Cancel</button>
                <button class="modal-button primary" onclick="insertLink()">Insert</button>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Insert Image</div>
            <div class="modal-body">
                <input type="text" id="imageUrl" class="modal-input" placeholder="https://example.com/image.jpg">
                <input type="text" id="imageAlt" class="modal-input" placeholder="Alt text (for accessibility)">
            </div>
            <div class="modal-buttons">
                <button class="modal-button secondary" onclick="closeImageModal()">Cancel</button>
                <button class="modal-button primary" onclick="insertImage()">Insert</button>
            </div>
        </div>
    </div>

    <div id="tableModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Insert Table</div>
            <div class="modal-body">
                <label class="block text-sm font-medium mb-2">Rows:</label>
                <input type="number" id="tableRows" class="modal-input" value="3" min="1" max="20">
                <label class="block text-sm font-medium mb-2">Columns:</label>
                <input type="number" id="tableCols" class="modal-input" value="3" min="1" max="10">
            </div>
            <div class="modal-buttons">
                <button class="modal-button secondary" onclick="closeTableModal()">Cancel</button>
                <button class="modal-button primary" onclick="insertTable()">Insert</button>
            </div>
        </div>
    </div>

    <script>
        const editor = document.getElementById('editor');
        const contentInput = document.getElementById('content-input');
        const wordCountEl = document.getElementById('word-count');
        const charCountEl = document.getElementById('char-count');

        // Update stats on input
        editor.addEventListener('input', updateStats);
        editor.addEventListener('paste', handlePaste);

        function updateStats() {
            const text = editor.innerText;
            const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
            const chars = text.length;
            wordCountEl.textContent = words;
            charCountEl.textContent = chars;
        }

        function handlePaste(e) {
            e.preventDefault();
            let text = (e.originalEvent || e).clipboardData.getData('text/html') || 
                      (e.originalEvent || e).clipboardData.getData('text/plain');
            
            if (text && text.startsWith('http')) {
                // Likely a URL
                document.execCommand('insertText', false, text);
            } else {
                document.execCommand('insertHTML', false, text);
            }
        }

        // Toolbar buttons
        document.querySelectorAll('.toolbar-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const command = btn.getAttribute('data-command');
                const value = btn.getAttribute('data-value');

                if (command === 'createLink') {
                    openLinkModal();
                } else if (command === 'insertImage') {
                    openImageModal();
                } else if (command === 'insertTable') {
                    openTableModal();
                } else {
                    editor.focus();
                    if (value) {
                        document.execCommand(command, false, value);
                    } else {
                        document.execCommand(command, false, null);
                    }
                }
            });
        });

        // Link modal
        function openLinkModal() {
            document.getElementById('linkModal').classList.add('active');
            document.getElementById('linkUrl').focus();
        }

        function closeLinkModal() {
            document.getElementById('linkModal').classList.remove('active');
        }

        function insertLink() {
            const url = document.getElementById('linkUrl').value;
            const text = document.getElementById('linkText').value;
            
            if (!url) return;
            
            editor.focus();
            if (text) {
                document.execCommand('insertHTML', false, `<a href="${url}" target="_blank">${text}</a>`);
            } else {
                document.execCommand('createLink', false, url);
            }
            closeLinkModal();
        }

        // Image modal
        function openImageModal() {
            document.getElementById('imageModal').classList.add('active');
            document.getElementById('imageUrl').focus();
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        function insertImage() {
            const url = document.getElementById('imageUrl').value;
            const alt = document.getElementById('imageAlt').value;
            
            if (!url) return;
            
            editor.focus();
            document.execCommand('insertImage', false, url);
            
            // Update alt text on the last inserted image
            const images = editor.querySelectorAll('img');
            if (images.length > 0) {
                images[images.length - 1].setAttribute('alt', alt);
            }
            closeImageModal();
        }

        // Table modal
        function openTableModal() {
            document.getElementById('tableModal').classList.add('active');
        }

        function closeTableModal() {
            document.getElementById('tableModal').classList.remove('active');
        }

        function insertTable() {
            const rows = parseInt(document.getElementById('tableRows').value) || 3;
            const cols = parseInt(document.getElementById('tableCols').value) || 3;
            
            let table = '<table class="ql-table" style="border-collapse: collapse; width: 100%;">';
            for (let r = 0; r < rows; r++) {
                table += '<tr>';
                for (let c = 0; c < cols; c++) {
                    table += '<td style="border: 1px solid #ccc; padding: 8px;"></td>';
                }
                table += '</tr>';
            }
            table += '</table><p></p>';
            
            editor.focus();
            document.execCommand('insertHTML', false, table);
            closeTableModal();
        }

        // Form submission
        document.querySelector('form').addEventListener('submit', (e) => {
            contentInput.value = editor.innerHTML;
            
            if (!contentInput.value.trim()) {
                e.preventDefault();
                alert('Report content is required');
            }
        });

        // File handling
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileList = document.getElementById('file-list');

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-blue-50');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('bg-blue-50');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('bg-blue-50');
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        });

        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            fileList.innerHTML = '';
            Array.from(fileInput.files).forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200';
                item.innerHTML = `
                    <div class="flex-1">
                        <p class="font-medium text-sm">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024).toFixed(2)} KB</p>
                    </div>
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="removeFile(${index})">✕</button>
                `;
                fileList.appendChild(item);
            });
        }

        function removeFile(index) {
            const dt = new DataTransfer();
            Array.from(fileInput.files).forEach((file, i) => {
                if (i !== index) dt.items.add(file);
            });
            fileInput.files = dt.files;
            updateFileList();
        }

        // Modal close on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Close modals on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            }
        });

        // Keyboard shortcuts
        editor.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key.toLowerCase()) {
                    case 'b':
                        e.preventDefault();
                        document.execCommand('bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        document.execCommand('italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        document.execCommand('underline');
                        break;
                    case 'z':
                        e.preventDefault();
                        document.execCommand('undo');
                        break;
                    case 'y':
                        e.preventDefault();
                        document.execCommand('redo');
                        break;
                }
            }
        });

        // Prevent submissions with empty content
        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const text = editor.innerText.trim();
                if (!text) {
                    e.preventDefault();
                    alert('Please enter report content');
                }
            });
        });
    </script>
</body>
</html>
