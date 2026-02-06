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

$user_id = getCurrentUserId();
$error = '';

// Get all members for dropdown
$members = $memberModel->getAllByUser($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $data = [
            'member_id' => $_POST['member_id'] ?? '',
            'mission_purpose' => $_POST['mission_purpose'] ?? '',
            'destination' => $_POST['destination'] ?? '',
            'departure_date' => $_POST['departure_date'] ?? '',
            'return_date' => $_POST['return_date'] ?? ''
        ];

        // Validate
        if (empty($data['member_id']) || empty($data['mission_purpose']) || empty($data['destination']) || 
            empty($data['departure_date']) || empty($data['return_date'])) {
            $error = 'Please fill in all required fields';
        } elseif (strtotime($data['return_date']) < strtotime($data['departure_date'])) {
            $error = 'Return date must be after departure date';
        } else {
            $authorization_id = $missionModel->create($data, $user_id);
            if ($authorization_id) {
                setFlashMessage('success', 'Mission authorization created successfully');
                header("Location: view.php?id=" . $authorization_id);
                exit();
            } else {
                $error = 'Failed to create mission authorization';
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
    <title>Create Mission Authorization - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Create Mission Authorization</h1>
            <p class="text-gray-600 mt-2">Generate a new mission authorization document</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (count($members) === 0): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 p-4 rounded-lg mb-6">
                <p class="font-semibold">No members available</p>
                <p>You need to add at least one member before creating a mission authorization.</p>
                <a href="../members/create.php" class="inline-block mt-2 text-blue-600 hover:text-blue-800 font-semibold">
                    + Add Member
                </a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="member_id">
                        Select Traveler *
                    </label>
                    <select name="member_id" id="member_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>" 
                                <?php echo (isset($_POST['member_id']) && $_POST['member_id'] == $member['member_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' - ' . $member['position']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="mission_purpose">
                        Purpose of Mission *
                    </label>
                    <textarea name="mission_purpose" id="mission_purpose" required rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="e.g., October pre-screening activities"><?php echo htmlspecialchars($_POST['mission_purpose'] ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="destination">
                        Destination(s) *
                    </label>
                    <input type="text" name="destination" id="destination" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="e.g., Kigali – Kibogora – Kabyayi"
                        value="<?php echo htmlspecialchars($_POST['destination'] ?? ''); ?>">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="departure_date">
                            Departure Date *
                        </label>
                        <input type="date" name="departure_date" id="departure_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['departure_date'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="return_date">
                            Return Date *
                        </label>
                        <input type="date" name="return_date" id="return_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['return_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="index.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                        Create Authorization
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-calculate and display duration
        document.getElementById('departure_date').addEventListener('change', calculateDuration);
        document.getElementById('return_date').addEventListener('change', calculateDuration);

        function calculateDuration() {
            const departure = document.getElementById('departure_date').value;
            const returnDate = document.getElementById('return_date').value;
            
            if (departure && returnDate) {
                const start = new Date(departure);
                const end = new Date(returnDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays > 0) {
                    console.log('Duration: ' + diffDays + ' days');
                }
            }
        }
    </script>
</body>
</html>