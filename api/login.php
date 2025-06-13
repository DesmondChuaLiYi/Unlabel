<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db_connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

// Sanitize input
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$password = $data['password'];

try {
    // Get user by email
    $stmt = $pdo->prepare("SELECT u.id, u.firstName, u.lastName, u.email, uc.password_hash 
                          FROM user u 
                          JOIN user_credentials uc ON u.id = uc.user_id 
                          WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Check if user exists and verify password
    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login timestamp
        $stmt = $pdo->prepare("UPDATE user SET last_login_dt = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Remove password hash from response
        unset($user['password_hash']);
        
        // Start session and store user info
        session_start();
        $_SESSION['user'] = $user;
        
        // Return success response with user data
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);
    } else {
        echo json_encode(['error' => 'Invalid email or password']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
}