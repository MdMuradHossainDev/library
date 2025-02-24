<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['member_id'])) {
    echo json_encode(['error' => 'Member ID is required']);
    exit();
}

$member_id = (int)$_GET['member_id'];

// Check if member exists and is active
$member_query = mysqli_query($conn, "SELECT status FROM members WHERE id = $member_id");
$member = mysqli_fetch_assoc($member_query);

if (!$member || $member['status'] !== 'active') {
    echo json_encode(['error' => 'Member not found or inactive']);
    exit();
}

// Check for overdue books
$has_overdue = has_overdue_books($conn, $member_id);

// Get member's current borrowing count
$stats = get_member_borrowing_stats($conn, $member_id);

echo json_encode([
    'success' => true,
    'has_overdue' => $has_overdue,
    'current_borrowings' => $stats['current_borrowings'],
    'total_fines' => $stats['total_fines']
]);
