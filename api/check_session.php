<?php
header("Access-Control-Allow-Origin: https://unlabel.lovestoblog.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true"); // Ensure credentials are allowed

// Start session
session_start();
error_log("check_session.php: Session data - " . print_r($_SESSION, true)); // Log session data

// Check if user is logged in based on the 'user' array
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $user = [
        'id' => $_SESSION['user']['id'],
        'firstName' => $_SESSION['user']['firstName'] ?? '',
        'lastName' => $_SESSION['user']['lastName'] ?? '',
        'email' => $_SESSION['user']['email'] ?? '',
        'phone' => $_SESSION['user']['phone'] ?? '',
        'address' => $_SESSION['user']['address'] ?? '',
        'city' => $_SESSION['user']['city'] ?? '',
        'state' => $_SESSION['user']['state'] ?? '',
        'zipCode' => $_SESSION['user']['zip_code'] ?? '',
        'country' => $_SESSION['user']['country'] ?? '',
        'birthDate' => $_SESSION['user']['birth_date'] ?? '',
        'profile_picture' => $_SESSION['user']['profile_picture'] ?? ''
    ];
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
}
?>