<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];
    $data = $_POST;
    $profilePhoto = $_FILES['profile_photo'] ?? null;

    $stmt = $pdo->prepare("UPDATE user SET firstName = ?, lastName = ?, email = ?, phone = ?, birthDate = ? WHERE id = ?");
    $stmt->execute([$data['firstName'], $data['lastName'], $data['email'], $data['phone'], $data['birthDate'], $userId]);

    if ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($profilePhoto['tmp_name']);
        $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$imageData, $userId]);
    }

    echo json_encode(['success' => true, 'profile_picture' => base64_encode($imageData ?? '')]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
}
?>