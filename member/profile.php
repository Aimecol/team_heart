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
$password_error = '';
$password_success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
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

        // If employee_id is empty, auto-generate one
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $memberModel->generateEmployeeId();
        }

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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $password_error = 'Invalid request';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = 'Please fill in all password fields';
        } elseif (!$userModel->verifyPassword($user_id, $current_password)) {
            $password_error = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $password_error = 'New password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'Passwords do not match';
        } elseif ($current_password === $new_password) {
            $password_error = 'New password must be different from current password';
        } else {
            if ($userModel->updatePassword($user_id, $new_password)) {
                $password_success = 'Password changed successfully';
            } else {
                $password_error = 'Failed to change password';
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
                <p class="text-gray-600 mt-2">Update your profile information and manage your account</p>
            </div>

            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <form method="POST" action="" class="p-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_profile">

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
                                <div class="flex gap-2">
                                    <input type="text" name="employee_id" id="employee_id"
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        value="<?php echo htmlspecialchars($member['employee_id'] ?? ''); ?>" placeholder="Auto-generated">
                                    <?php if (empty($member['employee_id'])): ?>
                                        <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                            onclick="generateEmployeeId()">Generate</button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate on save</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-4">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                            Save Profile Changes
                        </button>
                        <a href="../member-dashboard.php"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 text-center">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <form method="POST" action="" class="p-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="change_password">

                    <h2 class="text-xl font-bold text-gray-800 mb-6">Change Password</h2>

                    <?php if ($password_error): ?>
                        <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700">
                            <?php echo htmlspecialchars($password_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($password_success): ?>
                        <div class="mb-6 p-4 rounded-lg bg-green-100 border border-green-400 text-green-700">
                            <?php echo htmlspecialchars($password_success); ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">
                                Current Password <span class="text-red-600">*</span>
                            </label>
                            <input type="password" name="current_password" id="current_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                                New Password <span class="text-red-600">*</span>
                            </label>
                            <input type="password" name="new_password" id="new_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Minimum 6 characters">
                            <p class="text-xs text-gray-600 mt-1">Must be different from current password</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                                Confirm Password <span class="text-red-600">*</span>
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-6 flex gap-4">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Additional Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Security Tips</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Use a strong password with mixed characters</li>
                        <li>• Change your password regularly</li>
                        <li>• Never share your password</li>
                        <li>• Log out when using shared computers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateEmployeeId() {
            // Get the employee ID input field
            const employeeIdField = document.getElementById('employee_id');
            
            // Generate format: TH-YYYY-XXXX
            const year = new Date().getFullYear();
            const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            
            employeeIdField.value = `TH-${year}-${randomNum}`;
        }
    </script>
</body>
</html>
