<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'models/User.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        $user->email = $_POST['email'] ?? '';
        $user->password_hash = $_POST['password'] ?? '';
        $user->first_name = $_POST['first_name'] ?? '';
        $user->last_name = $_POST['last_name'] ?? '';
        $user->phone = $_POST['phone'] ?? '';
        $user->role = 'staff'; // Default role

        // Validate
        if (empty($user->email) || empty($user->password_hash) || empty($user->first_name) || empty($user->last_name)) {
            $error = 'Please fill in all required fields';
        } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } elseif (strlen($user->password_hash) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($user->emailExists()) {
            $error = 'Email already registered';
        } else {
            if ($user->register()) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - Team Heart Mission Authorization</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <img src="https://images.aimecol.com/uploads/large/team-heart_698542149457a_large.jpg" alt="Team Heart Logo" class="h-16 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Create Account</h1>
            <p class="text-gray-600 mt-2">Mission Authorization System</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                    First Name *
                </label>
                <input type="text" name="first_name" id="first_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                    Last Name *
                </label>
                <input type="text" name="last_name" id="last_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email *
                </label>
                <input type="email" name="email" id="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                    Phone
                </label>
                <input type="tel" name="phone" id="phone"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password *
                </label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-gray-600 text-xs mt-1">Minimum 6 characters</p>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                Register
            </button>
        </form>

        <p class="mt-4 text-center text-gray-600 text-sm">
            Already have an account? 
            <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold">Login here</a>
        </p>
    </div>
</body>
</html>