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
    $stmt = $pdo->prepare("SELECT u.id, u.firstName, u.lastName, u.email, u.birthDate, u.phone, u.profile_picture,
                          ua.address, ua.city, ua.state, ua.zipCode, ua.country 
                          FROM user u 
                          LEFT JOIN user_address ua ON u.id = ua.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        // Convert profile picture to base64 if it exists
        if ($user['profile_picture']) {
            $user['profile_picture'] = 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']);
        }
        echo json_encode(['user' => $user]);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch profile: ' . $e->getMessage()]);
}