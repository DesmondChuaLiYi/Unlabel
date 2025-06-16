<?php
ini_set('display_errors', 0); // Disable direct error output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to file
error_reporting(E_ALL);

ob_start(); // Start output buffering

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com'); // Specific origin
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true'); // Enable credentials

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('cart_get.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start(); // Moved after CORS headers

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    $response = ['error' => 'Not authenticated'];
    ob_end_clean();
    echo json_encode($response);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT id, product_id, product_name, product_price, product_image, quantity FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC); // Ensure associative array

    // Sanitize data for JSON
    $sanitizedItems = array_map(function ($item) {
        return array_map(function ($value) {
            return $value === null ? '' : $value; // Replace NULL with empty string
        }, $item);
    }, $cartItems);

    $response = ['success' => true, 'cartItems' => $sanitizedItems];
    ob_end_clean(); // Clear buffer
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Failed to fetch cart: ' . $e->getMessage()];
    ob_end_clean(); // Clear buffer on error
    echo json_encode($response);
}

exit; // Ensure no further output
?>