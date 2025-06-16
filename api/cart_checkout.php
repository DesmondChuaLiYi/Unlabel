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

if (!isset($data['shipping_option_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field']);
    exit;
}

try {
    $pdo->beginTransaction();
    $userId = $_SESSION['user']['id'];
    $orderId = 'ORD-' . strtoupper(substr(uniqid(), -8));
    $shippingOptionId = (int)$data['shipping_option_id'];

    // Define shipping options
    $shippingOptions = [
        1 => ['price' => 5.99],
        2 => ['price' => 15.99],
        3 => ['price' => 29.99]
    ];

    if (!isset($shippingOptions[$shippingOptionId])) {
        throw new Exception('Invalid shipping option');
    }

    // Get cart items
    $stmt = $pdo->prepare("SELECT id, product_id, product_name, product_price, product_image, quantity FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) {
        throw new Exception('Cart is empty');
    }

    // Calculate total
    $subtotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['product_price'] * $item['quantity']), 0);
    $totalAmount = $subtotal + $shippingOptions[$shippingOptionId]['price'];

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (order_id, user_id, total_amount, status) VALUES (?, ?, ?, 'completed')");
    $stmt->execute([$orderId, $userId, $totalAmount]);

    // Insert purchased items
    $stmt = $pdo->prepare("INSERT INTO user_purchases (user_id, order_id, product_id, product_name, product_price, product_image, quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
    foreach ($cartItems as $item) {
        $stmt->execute([$userId, $orderId, $item['product_id'], $item['product_name'], $item['product_price'], $item['product_image'], $item['quantity']]);
    }

    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Checkout successful', 'orderId' => $orderId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>