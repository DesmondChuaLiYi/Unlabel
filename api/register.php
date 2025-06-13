<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db_connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and validate input
$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// Check if email already exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Hash password
$passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

// Optional fields
$birthDate = isset($data['birthDate']) ? $data['birthDate'] : null;

// Handle profile picture if provided (base64 encoded)
$profilePicture = null;
if (isset($data['profilePicture']) && !empty($data['profilePicture'])) {
    // Decode base64 image
    $profilePicture = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['profilePicture']));
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert user data
    $stmt = $pdo->prepare("INSERT INTO user (firstName, lastName, email, profile_picture, birthDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $email, $profilePicture, $birthDate]);
    
    // Get the inserted user ID
    $userId = $pdo->lastInsertId();
    
    // Create a separate table for user credentials (better security practice)
    $stmt = $pdo->prepare("CREATE TABLE IF NOT EXISTS user_credentials (
        user_id VARCHAR(36) PRIMARY KEY,
        password_hash VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    )");
    $stmt->execute();
    
    // Insert password hash
    $stmt = $pdo->prepare("INSERT INTO user_credentials (user_id, password_hash) VALUES (?, ?)");
    $stmt->execute([$userId, $passwordHash]);
    
    // If address information is provided, insert it
    if (isset($data['address']) && isset($data['city']) && isset($data['state']) && isset($data['zipCode']) && isset($data['country'])) {
        $stmt = $pdo->prepare("INSERT INTO user_address (user_id, address, city, state, zipCode, country) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            filter_var($data['address'], FILTER_SANITIZE_STRING),
            filter_var($data['city'], FILTER_SANITIZE_STRING),
            filter_var($data['state'], FILTER_SANITIZE_STRING),
            filter_var($data['zipCode'], FILTER_SANITIZE_STRING),
            filter_var($data['country'], FILTER_SANITIZE_STRING)
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'userId' => $userId
    ]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}