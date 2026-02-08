<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/MissionAuthorization.php';

requireAdmin();

$database = new Database();
$db = $database->getConnection();
$missionModel = new MissionAuthorization($db);

$user = getCurrentUser();
$allMissions = $missionModel->getAllMissions();

// Handle rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid request');
    } else {
        $authorization_id = $_POST['authorization_id'] ?? null;
        $action = $_POST['action'];
        
        if ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            $result = $missionModel->reject($authorization_id, $rejection_reason, getCurrentUserId());
            if ($result) {
                setFlashMessage('success', 'Mission rejected successfully');
            } else {
                setFlashMessage('error', 'Failed to reject mission');
            }
        }
        
        header("Location: missions.php");
        exit();
    }
}

$flash = getFlashMessage();

// Filter options
$status_filter = $_GET['status'] ?? 'all';
$filtered_missions = $allMissions;

if ($status_filter !== 'all') {
    $filtered_missions = array_filter($allMissions, function($m) use ($status_filter) {
        return $m['status'] === $status_filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Missions - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Mission Authorizations</h1>
            <p class="text-gray-600 mt-2">Review and manage all mission authorizations</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="mb-6 flex gap-2 flex-wrap">
            <a href="missions.php?status=all" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                All (<?php echo count($allMissions); ?>)
            </a>
            <a href="missions.php?status=pending" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Pending (<?php echo count(array_filter($allMissions, function($m) { return $m['status'] === 'pending'; })); ?>)
            </a>
            <a href="missions.php?status=approved" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'approved' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Approved (<?php echo count(array_filter($allMissions, function($m) { return $m['status'] === 'approved'; })); ?>)
            </a>
            <a href="missions.php?status=rejected" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'rejected' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Rejected (<?php echo count(array_filter($allMissions, function($m) { return $m['status'] === 'rejected'; })); ?>)
            </a>
            <a href="missions.php?status=completed" class="px-4 py-2 rounded-lg font-semibold text-sm transition <?php echo $status_filter === 'completed' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                Completed (<?php echo count(array_filter($allMissions, function($m) { return $m['status'] === 'completed'; })); ?>)
            </a>
        </div>

        <!-- Missions Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (count($filtered_missions) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auth #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Traveler</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($filtered_missions as $mission): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($mission['authorization_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($mission['traveler_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($mission['member_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($mission['traveler_position']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars(substr($mission['mission_purpose'], 0, 30) . '...'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($mission['destination']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d', strtotime($mission['departure_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($mission['return_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                                echo $mission['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                     ($mission['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                     ($mission['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                                     ($mission['status'] === 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))); 
                                            ?>">
                                            <?php echo ucfirst($mission['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($mission['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="approve-mission.php?id=<?php echo $mission['authorization_id']; ?>" class="text-blue-600 hover:text-blue-900">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-xl font-semibold text-gray-900">No missions found</h3>
                    <p class="mt-1 text-gray-500">No missions match the selected filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
