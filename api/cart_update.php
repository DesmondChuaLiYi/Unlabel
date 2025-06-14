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

if (!isset($data['cart_item_id']) || !isset($data['quantity']) || $data['quantity'] < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid required fields']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    $cartItemId = (int)$data['cart_item_id'];
    $quantity = (int)$data['quantity'];

    // Get cart item
    $stmt = $pdo->prepare("SELECT product_id FROM user_cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartItemId, $userId]);
    $cartItem = $stmt->fetch();

    if (!$cartItem) {
        throw new Exception('Cart item not found');
    }

    // Fetch product stock
    $productsFile = __DIR__ . '/../assets/data/products.json';
    $products = json_decode(file_get_contents($productsFile), true);
    $product = array_filter($products, fn($p) => $p['id'] == $cartItem['product_id']);
    $product = array_values($product)[0] ?? null;

    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($quantity > $product['stock']) {
        throw new Exception('Requested quantity exceeds available stock');
    }

    // Update quantity
    $stmt = $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$quantity, $cartItemId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Cart updated']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>