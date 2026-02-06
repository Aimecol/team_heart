<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/MissionAuthorization.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$missionModel = new MissionAuthorization($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();

$missions = $missionModel->getAllByUser($user_id);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Authorizations - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Mission Authorizations</h1>
                <p class="text-gray-600 mt-2">Manage mission authorizations</p>
            </div>
            <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                + Create Authorization
            </a>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (count($missions) > 0): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Authorization #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traveler</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($missions as $mission): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($mission['authorization_number']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($mission['traveler_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($mission['traveler_position']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars(substr($mission['mission_purpose'], 0, 50)) . (strlen($mission['mission_purpose']) > 50 ? '...' : ''); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($mission['destination']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($mission['departure_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($mission['return_date'])); ?>
                                    <div class="text-xs text-gray-500"><?php echo $mission['duration_days']; ?> days</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                            echo $mission['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                 ($mission['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                 ($mission['status'] === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800')); 
                                        ?>">
                                        <?php echo ucfirst($mission['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view.php?id=<?php echo $mission['authorization_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <?php if ($mission['status'] === 'draft'): ?>
                                        <a href="edit.php?id=<?php echo $mission['authorization_id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                                        <a href="delete.php?id=<?php echo $mission['authorization_id']; ?>" 
                                           class="text-red-600 hover:text-red-900"
                                           onclick="return confirm('Are you sure you want to delete this authorization?');">Delete</a>
                                    <?php endif; ?>
                                    <?php if ($mission['status'] === 'approved'): ?>
                                        <a href="print.php?id=<?php echo $mission['authorization_id']; ?>" target="_blank" class="text-purple-600 hover:text-purple-900">Print</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-xl font-semibold text-gray-900">No mission authorizations yet</h3>
                <p class="mt-1 text-gray-500">Get started by creating your first mission authorization.</p>
                <div class="mt-6">
                    <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        + Create Authorization
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>