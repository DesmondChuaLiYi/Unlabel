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

if (!isset($data['product_id']) || !isset($data['quantity']) || $data['quantity'] < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid required fields']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    $productId = filter_var($data['product_id'], FILTER_SANITIZE_STRING);
    $quantity = (int)$data['quantity'];

    // Fetch product details from products.json
    $productsFile = __DIR__ . '/../src/assets/data/products.json'; // Updated path
    if (!file_exists($productsFile)) {
        throw new Exception('Products data not found');
    }
    $products = json_decode(file_get_contents($productsFile), true);
    $product = array_filter($products, fn($p) => $p['id'] == $productId);
    $product = array_values($product)[0] ?? null;

    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($product['stock'] < $quantity) {
        throw new Exception('Insufficient stock');
    }

    // Check if product already in cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock']) {
            throw new Exception('Requested quantity exceeds available stock');
        }
        $stmt = $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        // Insert new cart item
        $stmt = $pdo->prepare("INSERT INTO user_cart (user_id, product_id, product_name, product_price, product_image, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $productId, $product['name'], $product['price'], $product['image'], $quantity]);
    }

    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>