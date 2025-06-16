<?php
ini_set('display_errors', 0); // Disable direct error output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to file
error_reporting(E_ALL);

ob_start(); // Start output buffering
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com'); // Specific origin
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Methods used
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true'); // Enable credentials for potential session use

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('register.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start(); // Moved after CORS headers 

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response = json_encode(['error' => 'Only POST requests are allowed']);
    ob_end_clean();
    echo $response;
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    $response = json_encode(['error' => 'Missing required fields']);
    ob_end_clean();
    echo $response;
    exit;
}

$firstName = filter_var($data['firstName'], FILTER_SANITIZE_STRING);
$lastName = filter_var($data['lastName'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response = json_encode(['error' => 'Invalid email format']);
    ob_end_clean();
    echo $response;
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        $response = json_encode(['error' => 'Email already registered']);
        ob_end_clean();
        echo $response;
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

    $response = json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'userId' => $userId
    ]);
    ob_end_clean();
    echo $response;
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    $response = json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
    ob_end_clean();
    echo $response;
    error_log('register.php: PDO Error - ' . $e->getMessage());
}

// Catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('register.php: Fatal error - ' . json_encode($error));
        http_response_code(500);
        ob_end_clean();
        echo json_encode(['error' => 'Server error: Fatal error occurred']);
    }
});
?>