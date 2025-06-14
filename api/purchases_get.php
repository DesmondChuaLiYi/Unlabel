<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];

    // Get orders with their items
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.total_amount, o.order_date, o.status,
               p.product_id, p.product_name, p.product_price, p.product_image, p.quantity
        FROM orders o
        LEFT JOIN user_purchases p ON o.order_id = p.order_id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();

    // Group by order
    $purchases = [];
    foreach ($results as $row) {
        $orderId = $row['order_id'];
        if (!isset($purchases[$orderId])) {
            $purchases[$orderId] = [
                'id' => $orderId,
                'date' => $row['order_date'],
                'status' => $row['status'],
                'total' => $row['total_amount'],
                'items' => [],
                'shippingAddress' => 'Retrieved from user_address', // Placeholder
                'trackingNumber' => null // Placeholder
            ];
        }
        if ($row['product_id']) {
            $purchases[$orderId]['items'][] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'price' => $row['product_price'],
                'quantity' => $row['quantity'],
                'image' => $row['product_image']
            ];
        }
    }

    // Get user address for shipping
    $stmt = $pdo->prepare("SELECT address, city, state, zipCode, country FROM user_address WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $address = $stmt->fetch();

    if ($address) {
        $addressString = sprintf(
            '%s, %s, %s %s, %s',
            $address['address'],
            $address['city'],
            $address['state'],
            $address['zipCode'],
            $address['country']
        );
        foreach ($purchases as &$purchase) {
            $purchase['shippingAddress'] = $addressString;
        }
    }

    echo json_encode(['success' => true, 'purchases' => array_values($purchases)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch purchases: ' . $e->getMessage()]);
}
?>