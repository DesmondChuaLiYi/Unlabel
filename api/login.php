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
header('Access-Control-Allow-Credentials: true'); // Enable credentials for session

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('login.php: Handling OPTIONS request');
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

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    $response = json_encode(['error' => 'Email and password are required']);
    ob_end_clean();
    echo $response;
    exit;
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

try {
    $stmt = $pdo->prepare("SELECT id, firstName, lastName, email, password_hash FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($data['password'], $user['password_hash'])) {
        $stmt = $pdo->prepare("UPDATE user SET last_login_dt = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'email' => $user['email']
        ];

        $response = json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $_SESSION['user']
        ]);
        ob_end_clean();
        echo $response;
    } else {
        http_response_code(401);
        $response = json_encode(['success' => false, 'message' => 'Invalid email or password']);
        ob_end_clean();
        echo $response;
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response = json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    ob_end_clean();
    echo $response;
    error_log('login.php: PDO Error - ' . $e->getMessage());
}

// Catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('login.php: Fatal error - ' . json_encode($error));
        http_response_code(500);
        ob_end_clean();
        echo json_encode(['error' => 'Server error: Fatal error occurred']);
    }
});
?>