<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'sql100.infinityfree.com';
$port = '3306';
$dbname = 'if0_39221256_unlabel';
$username = 'if0_39221256';
$password = '04Cly1102nhn';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password, $options);
    $stmt = $pdo->query("SELECT 1"); // Test query
    if ($stmt === false) {
        throw new PDOException("Test query failed");
    }
} catch (PDOException $e) {
    // Log to a file if possible
    if (is_writable('.')) {
        file_put_contents('db_error.log', 'Connection error: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>