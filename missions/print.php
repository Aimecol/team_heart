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

if (!$authorization_id) {
    header("Location: index.php");
    exit();
}

$mission = $missionModel->getById($authorization_id, $user_id);

if (!$mission || $mission['status'] !== 'approved') {
    setFlashMessage('error', 'Mission authorization not found or not approved');
    header("Location: index.php");
    exit();
}

// Format dates
$departure_date = date('d/m/Y', strtotime($mission['departure_date']));
$return_date = date('d/m/Y', strtotime($mission['return_date']));
$authorization_date = date('d/m/Y', strtotime($mission['authorization_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Authorization - <?php echo htmlspecialchars($mission['authorization_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            html, body {
                margin: 0;
                padding: 0;
                background: white;
                width: 210mm;
                height: 297mm;
            }
            .page-container {
                margin: 0 !important;
                box-shadow: none !important;
                width: 210mm !important;
                height: 297mm !important;
                padding: 15mm 20mm !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
                visibility: hidden !important;
            }
            body {
                padding: 0 !important;
                background: white !important;
            }
            .authorization-section {
                margin-top: 30px !important;
            }
            table {
                margin-bottom: 30px !important;
            }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
        }
        .handwritten {
            font-family: 'Brush Script MT', cursive;
            font-style: italic;
        }
        @media screen and (max-width: 640px) {
            .page-container {
                width: 100% !important;
                height: auto !important;
                min-height: 100vh;
                padding: 5mm 10mm !important;
            }
        }
        @media screen and (min-width: 641px) and (max-width: 1024px) {
            .page-container {
                width: 90% !important;
                max-width: 210mm;
                height: auto !important;
                min-height: 297mm;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-start sm:items-center justify-center p-2 sm:p-4">
    
    <!-- Print Button -->
    <button onclick="window.print()" class="no-print fixed top-2 right-2 sm:top-4 sm:right-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 sm:py-3 sm:px-6 rounded-lg shadow-lg transition duration-200 flex items-center gap-2 text-sm sm:text-base z-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span class="hidden sm:inline">Print Document</span>
        <span class="sm:hidden">Print</span>
    </button>

    <div class="page-container bg-white shadow-lg sm:shadow-xl my-4 sm:my-8" style="width: 210mm; height: 297mm; padding: 15mm 20mm;">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-start gap-4 sm:gap-0 mb-4 sm:mb-6">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="https://images.aimecol.com/uploads/large/team-heart_698542149457a_large.jpg" alt="Team Heart Logo" class="h-6 sm:h-8 w-auto object-contain">
            </div>
            
            <!-- Contact Info -->
            <div class="text-left sm:text-right text-xs w-full sm:w-auto" style="color: #5eb3d6;">
                <div class="font-semibold">KG 685 St, 4 Kigali, Rwanda</div>
                <div class="mt-1">info@teamheartrw.org</div>
                <div>www.teamheartrw.org</div>
                <div class="mt-1">+ (250) 788 919 482</div>
            </div>
        </div>

        <!-- Divider -->
        <hr class="border-t border-gray-400 mb-6 sm:mb-8">

        <!-- Document Title -->
        <h1 class="text-center text-base sm:text-lg font-bold mb-6 sm:mb-8 tracking-wide">MISSION AUTHORIZATION</h1>

        <!-- Table -->
        <div class="overflow-x-auto mb-6 sm:mb-8">
            <table class="w-full border-collapse" style="border: 1px solid #ccc; min-width: 280px;">
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="width: 40%; border: 1px solid #ccc;">Name of traveler:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="width: 60%; border: 1px solid #ccc;"><?php echo htmlspecialchars($mission['traveler_name']); ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Position of traveler:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo htmlspecialchars($mission['traveler_position']); ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Purpose of the mission:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo htmlspecialchars($mission['mission_purpose']); ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Destination(s):</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo htmlspecialchars($mission['destination']); ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Date of departure:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo $departure_date; ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Return date:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo $return_date; ?></td>
                </tr>
                <tr style="border: 1px solid #ccc;">
                    <td class="font-bold py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;">Duration of the mission:</td>
                    <td class="py-2 px-2 sm:py-3 sm:px-4 text-xs sm:text-base" style="border: 1px solid #ccc;"><?php echo $mission['duration_days']; ?> days</td>
                </tr>
            </table>
        </div>

        <!-- Authorization Section -->
        <div class="authorization-section flex flex-col sm:flex-row justify-between items-start gap-8 sm:gap-0 mt-6 sm:mt-10">
            <!-- Left Column -->
            <div class="w-full sm:w-1/2">
                <div class="font-bold mb-2 text-xs sm:text-base">Authorized by:</div>
                <div class="handwritten text-2xl mb-2" style="color: #3b5998;">
                    <hr class="border-t border-gray-400 mt-6 sm:mt-8 w-32 sm:w-48">
                </div>
                <div class="text-xs sm:text-sm flex flex-col gap-3 sm:gap-6 mt-3 sm:mt-6">
                    <div class="font-semibold">Name: <span class="handwritten font-normal"><?php echo htmlspecialchars($mission['authorized_by']); ?></span></div>
                    <div class="font-semibold">Position: <span class="font-normal"><?php echo htmlspecialchars($mission['authorized_by_position']); ?></span></div>
                    <div class="font-semibold">Team Heart, Inc</div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="w-full sm:w-1/2 sm:text-left sm:pl-8">
                <div class="font-bold mb-2 text-xs sm:text-base whitespace-nowrap">Date:</div>
                <div class="handwritten text-xl" style="color: #3b5998;"><?php echo $authorization_date; ?></div>
                <hr class="border-t border-gray-400 mt-6 sm:mt-8 w-32 sm:w-48">
            </div>
        </div>

    </div>
</body>
</html>