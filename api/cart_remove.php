<?php
  ini_set('display_errors', 0); // Disable direct error output
  ini_set('log_errors', 1);
  ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to file
  error_reporting(E_ALL);

  ob_start(); // Start output buffering
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com'); // Specific origin
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
  header('Access-Control-Allow-Credentials: true'); // Enable credentials

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      error_log('checkout.php: Handling OPTIONS request');
      http_response_code(200);
      ob_end_clean();
      exit;
  }

  session_start(); // Moved after CORS headers

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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart_item_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    $cartItemId = (int)$data['cart_item_id'];

    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartItemId, $userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Cart item not found');
    }

    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>