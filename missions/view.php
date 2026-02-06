<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/MissionAuthorization.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$missionModel = new MissionAuthorization($db);

$user_id = getCurrentUserId();
$authorization_id = $_GET['id'] ?? null;
$error = '';
$success = '';

if (!$authorization_id) {
    header("Location: index.php");
    exit();
}

$mission = $missionModel->getById($authorization_id, $user_id);

if (!$mission) {
    setFlashMessage('error', 'Mission authorization not found');
    header("Location: index.php");
    exit();
}

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $approvalData = [
            'authorized_by' => $_POST['authorized_by'] ?? '',
            'authorized_by_position' => $_POST['authorized_by_position'] ?? '',
            'authorization_date' => $_POST['authorization_date'] ?? date('Y-m-d')
        ];

        if (empty($approvalData['authorized_by']) || empty($approvalData['authorized_by_position'])) {
            $error = 'Please fill in all authorization fields';
        } else {
            if ($missionModel->approve($authorization_id, $approvalData, $user_id)) {
                setFlashMessage('success', 'Mission authorization approved successfully');
                header("Location: view.php?id=" . $authorization_id);
                exit();
            } else {
                $error = 'Failed to approve authorization';
            }
        }
    }
}

$user = getCurrentUser();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Authorization - Team Heart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include './includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Mission Authorization</h1>
                <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($mission['authorization_number']); ?></p>
            </div>
            <div class="flex space-x-3">
                <?php if ($mission['status'] === 'approved'): ?>
                    <a href="print.php?id=<?php echo $authorization_id; ?>" target="_blank"
                       class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Print Document
                    </a>
                <?php endif; ?>
                <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                    Back to List
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Status</h3>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                        <?php 
                            echo $mission['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                 ($mission['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); 
                        ?>">
                        <?php echo ucfirst($mission['status']); ?>
                    </span>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Authorization Number</h3>
                    <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($mission['authorization_number']); ?></p>
                </div>
            </div>

            <hr class="my-6">

            <h2 class="text-xl font-bold text-gray-800 mb-4">Traveler Information</h2>
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Name</h3>
                    <p class="text-gray-900"><?php echo htmlspecialchars($mission['traveler_name']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Position</h3>
                    <p class="text-gray-900"><?php echo htmlspecialchars($mission['traveler_position']); ?></p>
                </div>
            </div>

            <hr class="my-6">

            <h2 class="text-xl font-bold text-gray-800 mb-4">Mission Details</h2>
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-1">Purpose of Mission</h3>
                <p class="text-gray-900"><?php echo htmlspecialchars($mission['mission_purpose']); ?></p>
            </div>

            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-1">Destination(s)</h3>
                <p class="text-gray-900"><?php echo htmlspecialchars($mission['destination']); ?></p>
            </div>

            <div class="grid grid-cols-3 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Departure Date</h3>
                    <p class="text-gray-900"><?php echo date('F d, Y', strtotime($mission['departure_date'])); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Return Date</h3>
                    <p class="text-gray-900"><?php echo date('F d, Y', strtotime($mission['return_date'])); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 mb-1">Duration</h3>
                    <p class="text-gray-900"><?php echo $mission['duration_days']; ?> days</p>
                </div>
            </div>

            <?php if ($mission['status'] === 'approved'): ?>
                <hr class="my-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Authorization</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Authorized By</h3>
                        <p class="text-gray-900"><?php echo htmlspecialchars($mission['authorized_by']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($mission['authorized_by_position']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-600 mb-1">Authorization Date</h3>
                        <p class="text-gray-900"><?php echo date('F d, Y', strtotime($mission['authorization_date'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($mission['status'] === 'draft'): ?>
            <div class="bg-white rounded-lg shadow p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Approve Authorization</h2>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="authorized_by">
                            Authorized By (Name) *
                        </label>
                        <input type="text" name="authorized_by" id="authorized_by" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="e.g., MUREKEZI Dan Rene">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="authorized_by_position">
                            Position *
                        </label>
                        <input type="text" name="authorized_by_position" id="authorized_by_position" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="e.g., Finance and Administration Officer">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="authorization_date">
                            Authorization Date *
                        </label>
                        <input type="date" name="authorization_date" id="authorization_date" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <button type="submit" name="approve" value="1"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200">
                        Approve Authorization
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>