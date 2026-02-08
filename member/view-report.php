<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Report.php';

requireMember();

$user_id = getCurrentUserId();
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$report_id) {
    header('Location: ./my-reports.php');
    exit;
}

$db_connection = getDBConnection();
$report_model = new Report($db_connection);

// Get report
$report = $report_model->getById($report_id);

if (!$report || ($report['user_id'] != $user_id && !isAdmin())) {
    header('HTTP/1.0 404 Not Found');
    echo 'Report not found';
    exit;
}

// Get attachments
$attachments = $report_model->getAttachments($report_id);

// Status info
$status_colors = [
    'draft' => 'bg-gray-100 text-gray-800',
    'submitted' => 'bg-blue-100 text-blue-800',
    'under-review' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800'
];

$status_labels = [
    'draft' => '📝 Draft',
    'submitted' => '📤 Submitted',
    'under-review' => '🔍 Under Review',
    'approved' => '✅ Approved',
    'rejected' => '❌ Rejected'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['title']); ?> - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-content {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
        }

        .report-content h1 { font-size: 28px; font-weight: 700; margin: 24px 0 16px; }
        .report-content h2 { font-size: 24px; font-weight: 600; margin: 20px 0 12px; }
        .report-content h3 { font-size: 20px; font-weight: 600; margin: 16px 0 10px; }
        .report-content p { margin-bottom: 12px; }
        .report-content ul, .report-content ol { margin: 12px 0 12px 24px; }
        .report-content li { margin-bottom: 8px; }
        .report-content blockquote {
            border-left: 4px solid #2563eb;
            margin: 12px 0;
            padding-left: 16px;
            color: #666;
            font-style: italic;
        }
        .report-content code {
            background-color: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Monaco', 'Menlo', monospace;
        }
        .report-content pre {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .report-content table {
            border-collapse: collapse;
            margin: 12px 0;
            width: 100%;
        }
        .report-content td, .report-content th {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .report-content th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        .report-content img {
            max-width: 100%;
            height: auto;
            margin: 12px 0;
            border-radius: 4px;
        }
        .report-content a {
            color: #2563eb;
            text-decoration: none;
            border-bottom: 1px solid #2563eb;
        }
        .report-content a:hover {
            color: #1d4ed8;
        }

        .document-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 24px;
            flex-shrink: 0;
        }

        .document-icon.pdf { background-color: #fee2e2; }
        .document-icon.doc { background-color: #dbeafe; }
        .document-icon.sheet { background-color: #dcfce7; }
        .document-icon.image { background-color: #fef3c7; }
        .document-icon.archive { background-color: #f3e8ff; }
        .document-icon.other { background-color: #e5e7eb; }

        @media (max-width: 768px) {
            .report-content h1 { font-size: 24px; }
            .report-content h2 { font-size: 20px; }
            .report-content h3 { font-size: 18px; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($report['title']); ?>
                    </h1>
                    <p class="text-lg text-gray-700 mb-2">
                        📍 <?php echo htmlspecialchars($report['mission_purpose']); ?>
                    </p>
                    <p class="text-gray-600">
                        📌 <?php echo htmlspecialchars($report['destination']); ?>
                    </p>
                </div>
                <span class="status-badge <?php echo $status_colors[$report['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                    <?php echo $status_labels[$report['status']] ?? $report['status']; ?>
                </span>
            </div>

            <!-- Metadata -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t">
                <div>
                    <p class="text-sm text-gray-600">Author</p>
                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($report['member_name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($report['employee_id']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Position</p>
                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($report['position']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Created</p>
                    <p class="font-semibold text-gray-900"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Mission</p>
                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($report['authorization_number']); ?></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if ($report['user_id'] == $user_id && in_array($report['status'], ['draft', 'rejected'])): ?>
                <div class="flex gap-2 mt-4 pt-4 border-t">
                    <a href="./edit-report.php?id=<?php echo $report['report_id']; ?>" 
                       class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition">
                        Edit Report
                    </a>
                    <a href="./my-reports.php" 
                       class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition">
                        Back
                    </a>
                </div>
            <?php else: ?>
                <div class="flex gap-2 mt-4 pt-4 border-t">
                    <a href="./my-reports.php" 
                       class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition">
                        Back to Reports
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Report Content -->
        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <div class="report-content">
                <?php echo $report['content']; ?>
            </div>
        </div>

        <!-- Attachments -->
        <?php if (!empty($attachments)): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">📎 Attachments (<?php echo count($attachments); ?>)</h2>
                
                <div class="space-y-3">
                    <?php foreach ($attachments as $attachment): ?>
                        <?php
                        $file_ext = strtolower($attachment['file_extension'] ?? 'txt');
                        $icon_class = 'other';
                        $icon = '📄';
                        
                        if ($file_ext === 'pdf') {
                            $icon_class = 'pdf';
                            $icon = '📕';
                        } elseif (in_array($file_ext, ['doc', 'docx', 'txt'])) {
                            $icon_class = 'doc';
                            $icon = '📄';
                        } elseif (in_array($file_ext, ['xls', 'xlsx', 'csv'])) {
                            $icon_class = 'sheet';
                            $icon = '📊';
                        } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $icon_class = 'image';
                            $icon = '🖼️';
                        } elseif (in_array($file_ext, ['zip', 'rar', '7z'])) {
                            $icon_class = 'archive';
                            $icon = '📦';
                        }
                        ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100 transition">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="document-icon <?php echo $icon_class; ?>">
                                    <?php echo $icon; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo round($attachment['file_size'] / 1024, 2); ?> KB • 
                                        Uploaded <?php echo date('M d, Y', strtotime($attachment['created_at'])); ?>
                                    </p>
                                    <?php if (!empty($attachment['description'])): ?>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($attachment['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($attachment['file_url']); ?>" 
                               download class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-medium">
                                ⬇ Download
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Approval/Rejection Notes (for admin view) -->
        <?php if (isset($report['review_notes']) && !empty($report['review_notes']) && isAdmin()): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">📝 Review Notes</h3>
                <p class="text-blue-800">
                    <?php echo nl2br(htmlspecialchars($report['review_notes'])); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
