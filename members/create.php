<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Member.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);

$user_id = getCurrentUserId();
$error = '';

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

        // Validate
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['position'])) {
            $error = 'Please fill in all required fields';
        } elseif (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } elseif (!empty($data['employee_id']) && $memberModel->employeeIdExists($data['employee_id'])) {
            $error = 'Employee ID already exists';
        } else {
            $member_id = $memberModel->create($data, $user_id);
            if ($member_id) {
                setFlashMessage('success', 'Member created successfully');
                header("Location: index.php");
                exit();
            } else {
                $error = 'Failed to create member';
            }
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Add New Member</h1>
            <p class="text-gray-600 mt-2">Create a new organization member</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                            First Name *
                        </label>
                        <input type="text" name="first_name" id="first_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                            Last Name *
                        </label>
                        <input type="text" name="last_name" id="last_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="middle_name">
                        Middle Name
                    </label>
                    <input type="text" name="middle_name" id="middle_name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email
                        </label>
                        <input type="email" name="email" id="email"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Phone
                        </label>
                        <input type="tel" name="phone" id="phone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="position">
                        Position *
                    </label>
                    <input type="text" name="position" id="position" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="department">
                            Department
                        </label>
                        <input type="text" name="department" id="department"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="employee_id">
                            Employee ID
                        </label>
                        <input type="text" name="employee_id" id="employee_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="index.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                        Create Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>