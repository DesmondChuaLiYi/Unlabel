<?php
header("Access-Control-Allow-Origin: https://unlabel.lovestoblog.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user = [
        'id' => $_SESSION['user_id'],
        'firstName' => $_SESSION['first_name'] ?? '',
        'lastName' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'address' => $_SESSION['address'] ?? '',
        'city' => $_SESSION['city'] ?? '',
        'state' => $_SESSION['state'] ?? '',
        'zipCode' => $_SESSION['zip_code'] ?? '',
        'country' => $_SESSION['country'] ?? '',
        'birthDate' => $_SESSION['birth_date'] ?? '',
        'profile_picture' => $_SESSION['profile_picture'] ?? ''
    ];
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
}
?>