<?php
header('Content-Type: application/json');

// Start session
session_start();

// Destroy session
session_destroy();

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Logout successful'
]);