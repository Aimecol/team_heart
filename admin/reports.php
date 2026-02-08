<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Report.php';

requireAdmin();

$db_connection = getDBConnection();
$report_model = new Report($db_connection);

// Get filter status and type
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;

// Get reports
$reports = $report_model->getAllReports($status_filter, $type_filter);

// Get statistics
$stats = $report_model->getStatistics();

// Handle report actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Security verification failed');
    }

    $report_id = intval($_POST['report_id'] ?? 0);
    $action = sanitizeInput($_POST['action']);
    $current_user_id = getCurrentUserId();

    if ($action === 'approve') {
        $comments = sanitizeInput($_POST['approval_notes'] ?? '');
        if ($report_model->approve($report_id, $current_user_id, $comments)) {
            setFlashMessage('success', 'Report approved successfully');
        }
    } elseif ($action === 'reject') {
        $reason = sanitizeInput($_POST['rejection_reason'] ?? '');
        if ($report_model->reject($report_id, $current_user_id, $reason)) {
            setFlashMessage('success', 'Report rejected');
        }
    }

    header('Location: ./reports.php' . ($status_filter ? '?status=' . $status_filter : ''));
    exit;
}

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

$csrf_token = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Team Heart Admin</title>
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

        .report-row {
            transition: all 0.3s ease;
        }

        .report-row:hover {
            background-color: #f9fafb;
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
            padding: 24px;
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

        .modal-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 16px;
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

        .modal-button.approve {
            background-color: #10b981;
            color: white;
        }

        .modal-button.approve:hover {
            background-color: #059669;
        }

        .modal-button.reject {
            background-color: #ef4444;
            color: white;
        }

        .modal-button.reject:hover {
            background-color: #dc2626;
        }

        .modal-button.cancel {
            background-color: #e5e7eb;
            color: #333;
        }

        .modal-button.cancel:hover {
            background-color: #d1d5db;
        }

        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📋 Reports Management</h1>
            <p class="text-gray-600">Review and approve mission reports from team members</p>
        </div>

        <!-- Flash Messages -->
        <?php 
        $message = getFlashMessage();
        if ($message):
        ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_reports'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Reports</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-gray-500"><?php echo $stats['draft_count'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Drafts</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['submitted_count'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Pending Review</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved_count'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Approved</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected_count'] ?? 0; ?></p>
                <p class="text-sm text-gray-600 mt-1">Rejected</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white border-b mb-6">
            <div class="flex gap-4 px-4 overflow-x-auto">
                <button onclick="filterReports('')" class="tab-button <?php echo !$status_filter ? 'active' : ''; ?>">
                    All Reports
                </button>
                <button onclick="filterReports('submitted')" class="tab-button <?php echo $status_filter === 'submitted' ? 'active' : ''; ?>">
                    📤 Pending Review
                </button>
                <button onclick="filterReports('under-review')" class="tab-button <?php echo $status_filter === 'under-review' ? 'active' : ''; ?>">
                    🔍 Under Review
                </button>
                <button onclick="filterReports('approved')" class="tab-button <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    ✅ Approved
                </button>
                <button onclick="filterReports('rejected')" class="tab-button <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    ❌ Rejected
                </button>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (empty($reports)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p class="text-lg">No reports found</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Report Title</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 hide-on-mobile">Author</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 hide-on-mobile">Mission</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 hide-on-mobile">Created</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr class="report-row border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs">
                                            <p class="font-medium text-gray-900 truncate">
                                                <?php echo htmlspecialchars($report['title']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                <?php echo htmlspecialchars(substr(strip_tags($report['description']), 0, 100)); ?>...
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hide-on-mobile">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($report['member_name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($report['employee_id']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hide-on-mobile">
                                        <p class="text-sm text-gray-700">
                                            <?php echo htmlspecialchars($report['authorization_number']); ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="status-badge <?php echo $status_colors[$report['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $status_labels[$report['status']] ?? $report['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 hide-on-mobile text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($report['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex gap-2 justify-end">
                                            <a href="../member/view-report.php?id=<?php echo $report['report_id']; ?>" 
                                               class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200 transition">
                                                View
                                            </a>
                                            <?php if ($report['status'] === 'submitted'): ?>
                                                <button onclick="openApproveModal(<?php echo $report['report_id']; ?>)" 
                                                        class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded text-sm hover:bg-green-200 transition">
                                                    Approve
                                                </button>
                                                <button onclick="openRejectModal(<?php echo $report['report_id']; ?>)" 
                                                        class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded text-sm hover:bg-red-200 transition">
                                                    Reject
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">Approve Report</div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="report_id" id="approve_report_id">
                
                <label class="block text-sm font-medium text-gray-700 mb-2">Optional Comments</label>
                <textarea name="approval_notes" class="modal-textarea" placeholder="Add any approval comments or notes..."></textarea>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-button cancel" onclick="closeApproveModal()">Cancel</button>
                    <button type="submit" class="modal-button approve">✓ Approve</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">Reject Report</div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="report_id" id="reject_report_id">
                
                <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea name="rejection_reason" class="modal-textarea" placeholder="Explain why the report is being rejected..." required></textarea>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-button cancel" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="modal-button reject">✕ Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterReports(status) {
            if (status) {
                window.location.href = '?status=' + status;
            } else {
                window.location.href = './reports.php';
            }
        }

        function openApproveModal(reportId) {
            document.getElementById('approve_report_id').value = reportId;
            document.getElementById('approveModal').classList.add('active');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.remove('active');
        }

        function openRejectModal(reportId) {
            document.getElementById('reject_report_id').value = reportId;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        // Close modals on background click
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
    </script>
</body>
</html>
