<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('cart_remove.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

$input = file_get_contents('php://input');
error_log("cart_remove.php: Received input - " . $input);
$data = json_decode($input, true);

if (!isset($data['cart_item_id']) || !is_numeric($data['cart_item_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid cart_item_id']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    $cartItemId = (int)$data['cart_item_id'];

    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartItemId, $userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Cart item not found or does not belong to user');
    }

    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>