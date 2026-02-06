<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'models/User.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();
$error = '';
$success = '';

// Get user details
$userDetails = $userModel->getUserById($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';

        // Validate
        if (empty($first_name) || empty($last_name)) {
            $error = 'Please fill in all required fields';
        } elseif (!empty($phone) && !preg_match('/^[0-9\s\-\+\(\)]+$/', $phone)) {
            $error = 'Invalid phone number format';
        } else {
            // Update user object
            $userModel->first_name = $first_name;
            $userModel->last_name = $last_name;
            $userModel->phone = $phone;

            if ($userModel->updateProfile($user_id)) {
                $success = 'Profile updated successfully';
                // Update session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                // Refresh user details
                $userDetails = $userModel->getUserById($user_id);
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Edit Profile</h1>
            <p class="text-gray-600 mt-2">Update your account information</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- Name Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">Name</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                                First Name *
                            </label>
                            <input type="text" name="first_name" id="first_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                value="<?php echo htmlspecialchars($userDetails['first_name']); ?>">
                            <p class="text-gray-500 text-xs mt-1">Your first name as it appears in the system</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                                Last Name *
                            </label>
                            <input type="text" name="last_name" id="last_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                value="<?php echo htmlspecialchars($userDetails['last_name']); ?>">
                            <p class="text-gray-500 text-xs mt-1">Your last name as it appears in the system</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">Contact Information</h2>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email Address
                        </label>
                        <input type="email" id="email" disabled
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed"
                            value="<?php echo htmlspecialchars($userDetails['email']); ?>">
                        <p class="text-gray-500 text-xs mt-1">Email cannot be changed. Contact an administrator if you need to update it.</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Phone Number
                        </label>
                        <input type="tel" name="phone" id="phone"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="e.g., +1 (555) 123-4567"
                            value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>">
                        <p class="text-gray-500 text-xs mt-1">Optional. Include country code if internationally dialing</p>
                    </div>
                </div>

                <!-- Account Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">Account Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Role
                            </label>
                            <div class="px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-900 font-semibold">
                                <?php echo ucfirst($userDetails['role']); ?>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Role cannot be changed. Contact an administrator to request role changes.</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Account Status
                            </label>
                            <div class="px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    <?php echo ucfirst($userDetails['status']); ?>
                                </span>
                            </div>
                            <p class="text-gray-500 text-xs mt-1">Your account status is managed by administrators</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Member Since
                            </label>
                            <div class="px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-900">
                                <?php echo formatDate($userDetails['created_at']); ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                User ID
                            </label>
                            <div class="px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-900 font-mono">
                                #<?php echo $userDetails['user_id']; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">Security</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="font-semibold text-blue-900 mb-2">Password Management</h3>
                        <p class="text-blue-800 text-sm mb-4">To change your password, use the password reset feature.</p>
                        <a href="#" class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 text-sm font-semibold">
                            Reset Password
                        </a>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-8 border-t">
                    <a href="profile.php" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200 font-semibold">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 font-semibold">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white rounded-lg shadow p-8 mt-8 border-t-4 border-red-500">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Danger Zone</h2>
            <p class="text-gray-600 mb-6">These actions cannot be undone. Please proceed with caution.</p>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="font-semibold text-red-900 mb-2">Account Security</h3>
                <p class="text-red-800 text-sm mb-4">If your account has been compromised or you no longer use this system, contact an administrator to deactivate or delete your account.</p>
                <a href="logout.php" class="inline-block px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200 text-sm font-semibold">
                    Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>
