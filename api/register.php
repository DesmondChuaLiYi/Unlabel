<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db_connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize and validate input
$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

    // Optional fields
    $birthDate = isset($data['birthDate']) ? $data['birthDate'] : null;

    // Handle profile picture if provided
    $profilePicture = null;
    if (isset($data['profilePicture']) && !empty($data['profilePicture'])) {
        $profilePicture = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['profilePicture']));
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user data
    $stmt = $pdo->prepare("INSERT INTO user (firstName, lastName, email, profile_picture, birthDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $email, $profilePicture, $birthDate]);
    $userId = $pdo->lastInsertId();

    // Create user_credentials table if not exists
    $stmt = $pdo->prepare("CREATE TABLE IF NOT EXISTS user_credentials (
        user_id VARCHAR(36) PRIMARY KEY,
        password_hash VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    )");
    $stmt->execute();

    // Insert password hash
    $stmt = $pdo->prepare("INSERT INTO user_credentials (user_id, password_hash) VALUES (?, ?)");
    $stmt->execute([$userId, $passwordHash]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'userId' => $userId
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}