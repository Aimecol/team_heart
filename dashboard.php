<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'models/Member.php';
require_once 'models/MissionAuthorization.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);
$missionModel = new MissionAuthorization($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();

// Get statistics
$members = $memberModel->getAllByUser($user_id);
$missions = $missionModel->getAllByUser($user_id);
$stats = $missionModel->getDashboardStats($user_id);

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600 mt-2">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Members</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($members); ?></p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Missions</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Approved</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['approved']; ?></p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Upcoming</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['upcoming']; ?></p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="members/create.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 text-center">
                        + Add New Member
                    </a>
                    <a href="missions/create.php" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 text-center">
                        + Create Mission Authorization
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h2>
                <?php if (count($missions) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($missions, 0, 5) as $mission): ?>
                            <div class="flex items-center justify-between border-b pb-2">
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($mission['traveler_name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($mission['destination']); ?></p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?php 
                                    echo $mission['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                         ($mission['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); 
                                ?>">
                                    <?php echo ucfirst($mission['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No missions yet. Create your first one!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>