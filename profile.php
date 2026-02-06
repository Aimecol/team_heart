<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Member.php';
require_once 'models/MissionAuthorization.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$memberModel = new Member($db);
$missionModel = new MissionAuthorization($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();

// Get user details
$userDetails = $userModel->getUserById($user_id);

// Get user's members count
$members = $memberModel->getAllByUser($user_id);
$member_count = count($members);

// Get user's missions
$missions = $missionModel->getAllByUser($user_id);
$mission_count = count($missions);

// Get stats
$stats = $missionModel->getDashboardStats($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Profile Header -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-12">
                <div class="flex justify-between items-start">
                    <div class="text-white">
                        <h1 class="text-4xl font-bold">
                            <?php echo htmlspecialchars($userDetails['first_name'] . ' ' . $userDetails['last_name']); ?>
                        </h1>
                        <p class="text-blue-100 mt-2">
                            <span class="inline-block px-3 py-1 bg-blue-500 rounded-full text-sm font-semibold">
                                <?php echo ucfirst($userDetails['role']); ?>
                            </span>
                        </p>
                    </div>
                    <a href="edit-profile.php" 
                       class="bg-white hover:bg-gray-100 text-blue-600 font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Edit Profile
                    </a>
                </div>
            </div>

            <!-- Profile Info Cards -->
            <div class="px-8 py-8 border-b">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Email Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg">
                        <p class="text-sm text-gray-600 uppercase tracking-wide font-semibold">Email</p>
                        <p class="text-lg font-semibold text-gray-900 mt-2">
                            <a href="mailto:<?php echo htmlspecialchars($userDetails['email']); ?>" 
                               class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($userDetails['email']); ?>
                            </a>
                        </p>
                    </div>

                    <!-- Phone Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg">
                        <p class="text-sm text-gray-600 uppercase tracking-wide font-semibold">Phone</p>
                        <p class="text-lg font-semibold text-gray-900 mt-2">
                            <?php echo $userDetails['phone'] ? '<a href="tel:' . htmlspecialchars($userDetails['phone']) . '" class="text-green-600 hover:underline">' . htmlspecialchars($userDetails['phone']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                        </p>
                    </div>

                    <!-- Status Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg">
                        <p class="text-sm text-gray-600 uppercase tracking-wide font-semibold">Status</p>
                        <p class="text-lg font-semibold text-gray-900 mt-2">
                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                <?php echo ucfirst($userDetails['status']); ?>
                            </span>
                        </p>
                    </div>

                    <!-- Member Since Card -->
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg">
                        <p class="text-sm text-gray-600 uppercase tracking-wide font-semibold">Member Since</p>
                        <p class="text-lg font-semibold text-gray-900 mt-2">
                            <?php echo formatDate($userDetails['created_at']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Account Information Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Account Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">First Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($userDetails['first_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Last Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($userDetails['last_name']); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Email Address</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <a href="mailto:<?php echo htmlspecialchars($userDetails['email']); ?>" 
                                   class="text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($userDetails['email']); ?>
                                </a>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Phone Number</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $userDetails['phone'] ? '<a href="tel:' . htmlspecialchars($userDetails['phone']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($userDetails['phone']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Role & Permissions Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Role & Permissions</h2>
                    
                    <div class="mb-6">
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-2">Current Role</p>
                        <p class="text-lg font-semibold text-gray-900 mb-4">
                            <span class="inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-lg text-base font-semibold">
                                <?php echo ucfirst($userDetails['role']); ?>
                            </span>
                        </p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-4">Permissions:</h3>
                        <ul class="space-y-2">
                            <?php if ($userDetails['role'] === 'admin'): ?>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Full administrative access to all system functions
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Manage users and roles
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    View audit logs
                                </li>
                            <?php elseif ($userDetails['role'] === 'manager'): ?>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Manage members and missions
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Approve mission authorizations
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    View team reports
                                </li>
                            <?php else: ?>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Create and submit missions
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    View your own missions and members
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Edit your profile
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Account Activity Section -->
                <div class="bg-white rounded-lg shadow p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Account Activity</h2>
                    
                    <div class="space-y-6">
                        <div class="flex justify-between items-center pb-4 border-b">
                            <span class="text-gray-700">Account Created</span>
                            <span class="font-semibold text-gray-900"><?php echo formatDate($userDetails['created_at']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Last Login</span>
                            <span class="font-semibold text-gray-900">
                                <?php 
                                // Note: We would need to fetch this from the database, for now showing created date
                                echo formatDate($userDetails['created_at']); 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Quick Stats</h3>
                    
                    <div class="space-y-4">
                        <!-- Members Count -->
                        <a href="members/index.php" class="block p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition">
                            <p class="text-sm text-gray-600 uppercase tracking-wide">Total Members</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $member_count; ?></p>
                        </a>

                        <!-- Missions Count -->
                        <a href="missions/index.php" class="block p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition">
                            <p class="text-sm text-gray-600 uppercase tracking-wide">Total Missions</p>
                            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $mission_count; ?></p>
                        </a>

                        <!-- Pending Missions -->
                        <div class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg">
                            <p class="text-sm text-gray-600 uppercase tracking-wide">Pending Missions</p>
                            <p class="text-3xl font-bold text-orange-600 mt-2"><?php echo $stats['pending'] ?? 0; ?></p>
                        </div>

                        <!-- Approved Missions -->
                        <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg">
                            <p class="text-sm text-gray-600 uppercase tracking-wide">Approved Missions</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo $stats['approved'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <a href="members/create.php" class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 font-semibold">
                            Add Member
                        </a>
                        <a href="missions/create.php" class="block w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 font-semibold">
                            Create Mission
                        </a>
                        <a href="edit-profile.php" class="block w-full text-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition duration-200 font-semibold">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
