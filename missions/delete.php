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
    setFlashMessage('error', 'Invalid authorization ID');
    header("Location: index.php");
    exit();
}

// Verify ownership and draft status
$mission = $missionModel->getById($authorization_id, $user_id);
if (!$mission) {
    setFlashMessage('error', 'Mission authorization not found');
    header("Location: index.php");
    exit();
}

if ($mission['status'] !== 'draft') {
    setFlashMessage('error', 'Only draft authorizations can be deleted');
    header("Location: index.php");
    exit();
}

if ($missionModel->delete($authorization_id, $user_id)) {
    setFlashMessage('success', 'Mission authorization deleted successfully');
} else {
    setFlashMessage('error', 'Failed to delete authorization');
}

header("Location: index.php");
exit();
?>