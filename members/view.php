<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Member.php';
require_once '../models/MissionAuthorization.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);
$missionModel = new MissionAuthorization($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();
$member_id = $_GET['id'] ?? null;

if (!$member_id) {
    header("Location: index.php");
    exit();
}

$member = $memberModel->getById($member_id, $user_id);

if (!$member) {
    header("Location: index.php");
    exit();
}

// Get member's missions
$missions = $missionModel->getMissionsByMember($member_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include './includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                    </h1>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($member['position']); ?></p>
                </div>
                <div class="flex space-x-4">
                    <a href="edit.php?id=<?php echo $member['member_id']; ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Edit
                    </a>
                    <a href="index.php" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Personal Information Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Personal Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">First Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($member['first_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Last Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($member['last_name']); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Middle Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['middle_name'] ? htmlspecialchars($member['middle_name']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Date of Birth</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['date_of_birth'] ? formatDate($member['date_of_birth']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Gender</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['gender'] ? htmlspecialchars(ucfirst($member['gender'])) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Nationality</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['nationality'] ? htmlspecialchars($member['nationality']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Contact Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Email</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $member['email'] ? '<a href="mailto:' . htmlspecialchars($member['email']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($member['email']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Phone</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $member['phone'] ? '<a href="tel:' . htmlspecialchars($member['phone']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($member['phone']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Alternate Phone</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $member['alternate_phone'] ? '<a href="tel:' . htmlspecialchars($member['alternate_phone']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($member['alternate_phone']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Country</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($member['country'] ?? 'Rwanda'); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Address</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php 
                                $address = [];
                                if ($member['address_line1']) $address[] = htmlspecialchars($member['address_line1']);
                                if ($member['address_line2']) $address[] = htmlspecialchars($member['address_line2']);
                                if ($member['city']) $address[] = htmlspecialchars($member['city']);
                                if ($member['state_province']) $address[] = htmlspecialchars($member['state_province']);
                                if ($member['postal_code']) $address[] = htmlspecialchars($member['postal_code']);
                                
                                echo count($address) > 0 ? implode(', ', $address) : '<span class="text-gray-400">Not provided</span>';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Professional Information Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Professional Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Employee ID</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($member['employee_id']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Position</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($member['position']); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Department</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['department'] ? htmlspecialchars($member['department']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Employment Status</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['employment_status'] ? htmlspecialchars(ucfirst(str_replace('-', ' ', $member['employment_status']))) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Date Hired</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['date_hired'] ? formatDate($member['date_hired']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Status</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $member['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Emergency Contact</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Name</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['emergency_contact_name'] ? htmlspecialchars($member['emergency_contact_name']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Relationship</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['emergency_contact_relationship'] ? htmlspecialchars($member['emergency_contact_relationship']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Phone</p>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo $member['emergency_contact_phone'] ? '<a href="tel:' . htmlspecialchars($member['emergency_contact_phone']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($member['emergency_contact_phone']) . '</a>' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Documents</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Passport Number</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['passport_number'] ? htmlspecialchars($member['passport_number']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">Passport Expiry</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['passport_expiry'] ? formatDate($member['passport_expiry']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-wide">ID Number</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $member['id_number'] ? htmlspecialchars($member['id_number']) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <?php if ($member['notes']): ?>
                <div class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Notes</h2>
                    <p class="text-gray-900 text-lg"><?php echo nl2br(htmlspecialchars($member['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Info</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-4 border-b">
                            <span class="text-gray-600">Missions</span>
                            <span class="text-2xl font-bold text-blue-600"><?php echo count($missions); ?></span>
                        </div>
                        <div class="flex justify-between items-center pb-4 border-b">
                            <span class="text-gray-600">Member Since</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo formatDate($member['created_at']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Last Updated</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo formatDate($member['updated_at']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Missions -->
                <?php if (count($missions) > 0): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Missions</h3>
                    
                    <div class="space-y-3">
                        <?php foreach (array_slice($missions, 0, 5) as $mission): ?>
                        <div class="pb-3 border-b last:border-b-0">
                            <a href="/team_heart/missions/view.php?id=<?php echo $mission['authorization_id']; ?>" 
                               class="text-blue-600 hover:text-blue-800 font-semibold text-sm block">
                                <?php echo htmlspecialchars($mission['authorization_number']); ?>
                            </a>
                            <p class="text-gray-600 text-xs mt-1">
                                <?php echo htmlspecialchars(substr($mission['mission_purpose'], 0, 50)) . '...'; ?>
                            </p>
                            <p class="text-gray-500 text-xs mt-1">
                                <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">
                                    <?php echo ucfirst($mission['status']); ?>
                                </span>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($missions) > 5): ?>
                    <div class="mt-4">
                        <a href="/team_heart/missions/index.php?member=<?php echo $member['member_id']; ?>" 
                           class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                            View All Missions →
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
