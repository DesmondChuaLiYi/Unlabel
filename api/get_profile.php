<?php
header('Content-Type: application/json');

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

try {
    // Get user profile data
    $stmt = $pdo->prepare("SELECT id, firstName, lastName, email, birthDate, created_dt, last_login_dt FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    
    // Get user address if available
    $stmt = $pdo->prepare("SELECT address, city, state, zipCode, country FROM user_address WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $address = $stmt->fetch();
    
    if ($address) {
        $user['address'] = $address;
    }
    
    // Return user profile data
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Failed to get profile: ' . $e->getMessage()]);
}