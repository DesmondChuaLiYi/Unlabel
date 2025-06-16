<?php
ini_set('display_errors', 0); // Disable direct error output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to file
error_reporting(E_ALL);

ob_start(); // Start output buffering
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com'); // Specific origin
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Methods used
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true'); // Enable credentials for potential session use

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('register.php: Handling OPTIONS request');
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
    $data = $_POST;
    $profilePhoto = $_FILES['profile_photo'] ?? null;
    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === 'true';
    $action = $data['action'] ?? 'update_profile'; // Default to profile update

    if ($action === 'update_address') {
        // Handle address update only
        if (isset($data['address'])) {
            $stmt = $pdo->prepare("SELECT id FROM user_address WHERE user_id = ?");
            $stmt->execute([$userId]);
            $addressId = $stmt->fetchColumn();

            if ($addressId) {
                $stmt = $pdo->prepare("UPDATE user_address SET address = ?, city = ?, state = ?, zipCode = ?, country = ? WHERE id = ?");
                $stmt->execute([
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['zipCode'] ?? null,
                    $data['country'] ?? null,
                    $addressId
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO user_address (user_id, address, city, state, zipCode, country) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['zipCode'] ?? null,
                    $data['country'] ?? null
                ]);
            }
        }
        echo json_encode(['success' => true]); // No profile_picture needed for address update
    } else {
        // Handle profile update (including password and photo)
        $stmt = $pdo->prepare("UPDATE user SET firstName = ?, lastName = ?, email = ?, phone = ?, birthDate = ? WHERE id = ?");
        $stmt->execute([
            $data['firstName'] ?? null,
            $data['lastName'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['birthDate'] ?? null,
            $userId
        ]);

        // Update password if provided
        if (!empty($data['currentPassword']) && !empty($data['newPassword']) && !empty($data['confirmPassword'])) {
            $stmt = $pdo->prepare("SELECT password_hash FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (password_verify($data['currentPassword'], $user['password_hash']) && $data['newPassword'] === $data['confirmPassword']) {
                $newPasswordHash = password_hash($data['newPassword'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newPasswordHash, $userId]);
            } else {
                throw new Exception('Invalid current password or passwords do not match.');
            }
        }

        // Handle profile picture update or removal
        $profilePicture = null;
        if ($removePhoto) {
            $stmt = $pdo->prepare("UPDATE user SET profile_picture = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_' . $userId . '.jpg')) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_' . $userId . '.jpg');
            }
        } elseif ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = 'profile_' . $userId . '.jpg';
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($profilePhoto['tmp_name'], $targetFile)) {
                $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
                $stmt->execute(['/uploads/' . $fileName, $userId]);
                $profilePicture = '/uploads/' . $fileName;
            }
        } else {
            $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $profilePicture = $stmt->fetchColumn();
        }

        echo json_encode(['success' => true, 'profile_picture' => $profilePicture]);
    }
} catch (PDOException | Exception $e) {
    ob_clean(); // Clear any output buffer
    error_log("Update error: " . $e->getMessage()); // Log the error
    echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
}
?>