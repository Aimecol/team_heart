<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Member.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);

$user = getCurrentUser();
$user_id = getCurrentUserId();

$members = $memberModel->getAllByUser($user_id);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include './includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Members</h1>
                <p class="text-gray-600 mt-2">Manage organization members</p>
            </div>
            <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                + Add Member
            </a>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (count($members) > 0): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($members as $member): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($member['employee_id'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($member['position']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($member['department'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $member['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view.php?id=<?php echo $member['member_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <a href="edit.php?id=<?php echo $member['member_id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                                    <a href="delete.php?id=<?php echo $member['member_id']; ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Are you sure you want to delete this member?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <h3 class="mt-2 text-xl font-semibold text-gray-900">No members yet</h3>
                <p class="mt-1 text-gray-500">Get started by adding your first member.</p>
                <div class="mt-6">
                    <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        + Add Member
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>