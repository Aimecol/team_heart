<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Member.php';

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$memberModel = new Member($db);

$user = getCurrentUser();
$allMembers = $userModel->getAllMembers();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid request');
    } else {
        $user_id = $_POST['user_id'] ?? null;
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            $result = $userModel->updateStatus($user_id, 'active');
            if ($result) {
                setFlashMessage('success', 'Member approved successfully');
            } else {
                setFlashMessage('error', 'Failed to approve member');
            }
        } elseif ($action === 'reject') {
            $result = $userModel->updateStatus($user_id, 'rejected');
            if ($result) {
                setFlashMessage('success', 'Member rejected');
            } else {
                setFlashMessage('error', 'Failed to reject member');
            }
        } elseif ($action === 'suspend') {
            $result = $userModel->updateStatus($user_id, 'suspended');
            if ($result) {
                setFlashMessage('success', 'Member suspended');
            } else {
                setFlashMessage('error', 'Failed to suspend member');
            }
        }
        
        header("Location: users.php");
        exit();
    }
}

$flash = getFlashMessage();

// Filter options
$status_filter = $_GET['status'] ?? 'all';
$filtered_members = $allMembers;

if ($status_filter !== 'all') {
    $filtered_members = array_filter($allMembers, function($m) use ($status_filter) {
        return $m['status'] === $status_filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Members</h1>
            <p class="text-gray-600 mt-2">View and manage all registered members</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="mb-6 flex gap-2">
            <a href="users.php?status=all" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                All (<?php echo count($allMembers); ?>)
            </a>
            <a href="users.php?status=active" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'active' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Active (<?php echo count(array_filter($allMembers, function($m) { return $m['status'] === 'active'; })); ?>)
            </a>
            <a href="users.php?status=pending" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Pending (<?php echo count(array_filter($allMembers, function($m) { return $m['status'] === 'pending'; })); ?>)
            </a>
            <a href="users.php?status=rejected" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'rejected' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Rejected (<?php echo count(array_filter($allMembers, function($m) { return $m['status'] === 'rejected'; })); ?>)
            </a>
        </div>

        <!-- Members Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (count($filtered_members) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($filtered_members as $member): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($member['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($member['position'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($member['department'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($member['employee_id'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                                echo $member['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                     ($member['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                     ($member['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                                     ($member['status'] === 'suspended' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))); 
                                            ?>">
                                            <?php echo ucfirst($member['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($member['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <?php if ($member['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Approve this member?');">Approve</button>
                                            </form>
                                            |
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Reject this member?');">Reject</button>
                                            </form>
                                        <?php elseif ($member['status'] === 'active'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="text-orange-600 hover:text-orange-900" onclick="return confirm('Suspend this member?');">Suspend</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-xl font-semibold text-gray-900">No members found</h3>
                    <p class="mt-1 text-gray-500">No members match the selected filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
