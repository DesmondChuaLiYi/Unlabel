<?php
ini_set('display_errors', 0); // Disable direct error output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to file
error_reporting(E_ALL);

ob_start(); // Start output buffering
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com'); // Specific origin
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Methods used
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true'); // Enable credentials for session

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('logout.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start(); // Moved after CORS headers

session_destroy(); // Destroy session

$response = json_encode([
    'success' => true,
    'message' => 'Logout successful'
]);
ob_end_clean();
echo $response;

// Catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('logout.php: Fatal error - ' . json_encode($error));
        http_response_code(500);
        ob_end_clean();
        echo json_encode(['error' => 'Server error: Fatal error occurred']);
    }
});
?>