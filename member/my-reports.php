<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Report.php';

requireMember();

$user_id = getCurrentUserId();
$db_connection = getDBConnection();
$report_model = new Report($db_connection);

// Get current user
$user = getCurrentUser();

// Get filter status
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;

// Get reports
$reports = $report_model->getAllByUser($user_id, $status_filter);

// Get statistics
$stats = $report_model->getDashboardStats($user_id);

// Define status colors and labels
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
    <title>My Reports - Team Heart</title>
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

        .report-card {
            transition: all 0.3s ease;
        }

        .report-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .tab-button {
            padding: 12px 24px;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            color: #666;
        }

        .tab-button.active {
            border-bottom-color: #2563eb;
            color: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">My Reports</h1>
                <p class="text-gray-600">Manage and track all your mission reports</p>
            </div>
            <a href="./create-report.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                <span>➕</span> Create Report
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Total</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-gray-500"><?php echo $stats['draft'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Drafts</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['submitted'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Submitted</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Approved</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Rejected</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white border-b mb-6">
            <div class="flex gap-4 px-4">
                <button onclick="filterReports('')" class="tab-button <?php echo !$status_filter ? 'active' : ''; ?>">
                    All Reports
                </button>
                <button onclick="filterReports('draft')" class="tab-button <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
                    📝 Drafts
                </button>
                <button onclick="filterReports('submitted')" class="tab-button <?php echo $status_filter === 'submitted' ? 'active' : ''; ?>">
                    📤 Submitted
                </button>
                <button onclick="filterReports('approved')" class="tab-button <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    ✅ Approved
                </button>
                <button onclick="filterReports('rejected')" class="tab-button <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    ❌ Rejected
                </button>
            </div>
        </div>

        <!-- Reports List -->
        <div class="space-y-4">
            <?php if (empty($reports)): ?>
                <div class="bg-white rounded-lg p-8">
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Reports Yet</h3>
                        <p class="text-gray-600 mb-6">
                            <?php 
                            if ($status_filter) {
                                echo "You don't have any " . $status_labels[$status_filter] . " reports.";
                            } else {
                                echo "Start creating your first mission report to document your activities.";
                            }
                            ?>
                        </p>
                        <a href="./create-report.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                            Create Your First Report
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <div class="report-card bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($report['title']); ?>
                                    </h3>
                                    <span class="status-badge <?php echo $status_colors[$report['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $status_labels[$report['status']] ?? $report['status']; ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">
                                    📍 <strong><?php echo htmlspecialchars($report['mission_purpose']); ?></strong>
                                </p>
                                <p class="text-sm text-gray-600 mb-2">
                                    📍 <?php echo htmlspecialchars($report['destination']); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <strong>Mission:</strong> <?php echo htmlspecialchars($report['authorization_number']); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600 mb-3">
                                    📎 <?php echo $report['attachment_count']; ?> file<?php echo $report['attachment_count'] !== 1 ? 's' : ''; ?>
                                </p>
                                <div class="flex gap-2">
                                    <a href="./view-report.php?id=<?php echo $report['report_id']; ?>" 
                                       class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200 transition">
                                        View
                                    </a>
                                    <?php if (in_array($report['status'], ['draft', 'rejected'])): ?>
                                        <a href="./edit-report.php?id=<?php echo $report['report_id']; ?>" 
                                           class="inline-block bg-amber-100 text-amber-700 px-3 py-1 rounded text-sm hover:bg-amber-200 transition">
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t text-xs text-gray-500">
                            <div>
                                <span class="mr-6">📅 Created: <?php echo date('M d, Y', strtotime($report['created_at'])); ?></span>
                                <?php if ($report['submitted_at']): ?>
                                    <span>📤 Submitted: <?php echo date('M d, Y', strtotime($report['submitted_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterReports(status) {
            if (status) {
                window.location.href = '?status=' + status;
            } else {
                window.location.href = './my-reports.php';
            }
        }
    </script>
</body>
</html>
