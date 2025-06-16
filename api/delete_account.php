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
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];
    
    // Begin transaction
    $pdo->beginTransaction();

    // Delete from user_address
    $stmt = $pdo->prepare("DELETE FROM user_address WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user_cart
    $stmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user_purchases (corrected from purchases)
    $stmt = $pdo->prepare("DELETE FROM user_purchases WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from orders
    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete from user
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    // Destroy session
    session_destroy();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Delete account error: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['error' => 'Failed to delete account: ' . $e->getMessage()]);
}
?>