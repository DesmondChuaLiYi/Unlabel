<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];
    
    // Begin transaction
    $pdo->beginTransaction();

    // Delete from user_address
    $stmt = $pdo->prepare("DELETE FROM user_address WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user_cart
    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user_purchases (corrected from purchases)
    $stmt = $pdo->prepare("DELETE FROM user_purchases WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from orders
    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    // Destroy session
    session_destroy();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete account error: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['error' => 'Failed to delete account: ' . $e->getMessage()]);
}
?>