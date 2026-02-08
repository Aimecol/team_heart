<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Member.php';
require_once '../models/User.php';

requireMember();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);
$userModel = new User($db);

$user_id = getCurrentUserId();
$user = getCurrentUser();
$member = $memberModel->getByUserId($user_id);

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'position' => $_POST['position'] ?? '',
            'department' => $_POST['department'] ?? '',
            'employee_id' => $_POST['employee_id'] ?? ''
        ];

        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['position'])) {
            $error = 'Please fill in all required fields';
        } else {
            // Update both user and member records
            $userModel->first_name = $data['first_name'];
            $userModel->last_name = $data['last_name'];
            $userModel->phone = $data['phone'];
            
            if ($userModel->updateProfile($user_id)) {
                // Update member record
                if ($memberModel->update($member['member_id'], $data, $user_id)) {
                    $_SESSION['first_name'] = $data['first_name'];
                    $_SESSION['last_name'] = $data['last_name'];
                    $success = 'Profile updated successfully';
                    // Refresh member data
                    $member = $memberModel->getByUserId($user_id);
                } else {
                    $error = 'Failed to update profile';
                }
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
                <p class="text-gray-600 mt-2">Update your profile information</p>
            </div>

            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 border border-green-400 text-green-700">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg">
                <form method="POST" action="" class="p-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <!-- Account Information Section -->
                    <div class="mb-8 pb-8 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Account Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                                    First Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="first_name" id="first_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                                    Last Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="last_name" id="last_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="middle_name">
                                    Middle Name
                                </label>
                                <input type="text" name="middle_name" id="middle_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($member['middle_name'] ?? ''); ?>">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                    Email <span class="text-red-600">*</span>
                                </label>
                                <input type="email" name="email" id="email" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                                    Phone Number
                                </label>
                                <input type="tel" name="phone" id="phone"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+250 xxx xxx xxx">
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information Section -->
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Professional Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="position">
                                    Position <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="position" id="position" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($member['position'] ?? ''); ?>" placeholder="e.g., Cardiac Nurse Educator">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="department">
                                    Department
                                </label>
                                <input type="text" name="department" id="department"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($member['department'] ?? ''); ?>" placeholder="e.g., Medical">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="employee_id">
                                    Employee ID
                                </label>
                                <input type="text" name="employee_id" id="employee_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-gray-100"
                                    value="<?php echo htmlspecialchars($member['employee_id'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-900">
                                <strong>Note:</strong> Contact your administrator if you need to update your Employee ID or other restricted fields.
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-4">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                            Save Changes
                        </button>
                        <a href="../member-dashboard.php"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Additional Information -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Account Status</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">Active</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Role:</span>
                            <span class="font-semibold">Team Member</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="font-semibold"><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Security</h3>
                    <div class="space-y-3">
                        <p class="text-sm text-gray-600">To change your password, please contact your administrator.</p>
                        <button type="button" disabled
                            class="w-full bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg cursor-not-allowed">
                            Change Password (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
