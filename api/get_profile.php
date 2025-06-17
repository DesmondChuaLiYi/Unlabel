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
    error_log('get_profile.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start();

if (!isset($_SESSION['user'])) {
    error_log('get_profile.php: Not authenticated');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT u.id, u.firstName, u.lastName, u.email, u.birthDate, u.phone, u.profile_picture,
                          ua.address, ua.city, ua.state, ua.zipCode, ua.country 
                          FROM user u 
                          LEFT JOIN user_address ua ON u.id = ua.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        // Convert profile picture to base64 if it exists
        if ($user['profile_picture']) {
            $user['profile_picture'] = 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']);
        }
        error_log('get_profile.php: User data fetched successfully for userId ' . $userId);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        error_log('get_profile.php: User not found for userId ' . $userId);
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    error_log('get_profile.php: Database error - ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch profile: ' . $e->getMessage()]);
} finally {
    ob_end_flush();
}
?>