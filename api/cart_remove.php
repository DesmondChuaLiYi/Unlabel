<?php
header('Content-Type: application/json');
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