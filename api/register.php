<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $birthDate = isset($data['birthDate']) ? $data['birthDate'] : null;
    $profilePicture = isset($data['profilePicture']) && !empty($data['profilePicture'])
        ? base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['profilePicture']))
        : null;

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user data including password_hash
    $stmt = $pdo->prepare("INSERT INTO user (firstName, lastName, email, password_hash, profile_picture, birthDate) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $email, $passwordHash, $profilePicture, $birthDate]);
    $userId = $pdo->lastInsertId();

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'userId' => $userId
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}