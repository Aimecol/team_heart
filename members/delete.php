<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Member.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$memberModel = new Member($db);

$user_id = getCurrentUserId();
$member_id = $_GET['id'] ?? null;

if (!$member_id) {
    setFlashMessage('error', 'Invalid member ID');
    header("Location: index.php");
    exit();
}

// Verify ownership
$member = $memberModel->getById($member_id, $user_id);
if (!$member) {
    setFlashMessage('error', 'Member not found');
    header("Location: index.php");
    exit();
}

if ($memberModel->delete($member_id, $user_id)) {
    setFlashMessage('success', 'Member deleted successfully');
} else {
    setFlashMessage('error', 'Failed to delete member');
}

header("Location: index.php");
exit();
?>