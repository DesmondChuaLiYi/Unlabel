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
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    $response = json_encode(['error' => 'Not authenticated']);
    ob_end_clean();
    echo $response;
    exit;
}

require_once 'db_connect.php';
error_log('DB connection attempt');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response = json_encode(['error' => 'Only POST requests are allowed']);
    ob_end_clean();
    echo $response;
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
error_log('Received data: ' . print_r($data, true));

if (!isset($data['product_id']) || !isset($data['quantity']) || $data['quantity'] < 1) {
    http_response_code(400);
    $response = json_encode(['error' => 'Missing or invalid required fields']);
    ob_end_clean();
    echo $response;
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    $productId = filter_var($data['product_id'], FILTER_SANITIZE_STRING);
    $quantity = (int)$data['quantity'];

    $productsFile = __DIR__ . '/../assets/data/products.json';
    error_log('Checking products file: ' . $productsFile);
    if (!file_exists($productsFile)) {
        throw new Exception('Products data not found at ' . $productsFile);
    }
    $products = json_decode(file_get_contents($productsFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to decode products.json: ' . json_last_error_msg());
    }
    error_log('Products loaded: ' . print_r($products, true));
    $product = array_filter($products, fn($p) => $p['id'] == $productId);
    $product = array_values($product)[0] ?? null;

    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($product['stock'] < $quantity) {
        throw new Exception('Insufficient stock');
    }

    $stmt = $pdo->prepare("SELECT id, quantity FROM user_cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch();
    error_log('Cart item query result: ' . print_r($cartItem, true));

    if ($cartItem) {
        $newQuantity = $cartItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock']) {
            throw new Exception('Requested quantity exceeds available stock');
        }
        $stmt = $pdo->prepare("UPDATE user_cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_cart (user_id, product_id, product_name, product_price, product_image, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $productId, $product['name'], $product['price'], $product['image'], $quantity]);
    }

    ob_end_clean(); // Clear buffer before response
    $response = json_encode(['success' => true, 'message' => 'Item added to cart']);
    echo $response;
} catch (Exception $e) {
    ob_end_clean(); // Clear buffer on error
    http_response_code(400);
    $response = json_encode(['error' => $e->getMessage()]);
    echo $response;
    error_log('Cart add error: ' . $e->getMessage());
}

exit; // Ensure no extra output
?>