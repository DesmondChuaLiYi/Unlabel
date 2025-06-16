<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log('update_profile.php: Handling OPTIONS request');
    http_response_code(200);
    ob_end_clean();
    exit;
}

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db_connect.php';

try {
    $userId = $_SESSION['user']['id'];

    // Handle multipart/form-data (FormData from frontend)
    $input = file_get_contents('php://input');
    parse_str($input, $data);
    $profilePhoto = $_FILES['profile_photo'] ?? null;
    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === 'true';
    $action = $data['action'] ?? 'update_profile';

    if ($action === 'update_address') {
        // Handle address update
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
        // Update session with new address data
        $stmt = $pdo->prepare("SELECT address, city, state, zipCode, country FROM user_address WHERE user_id = ?");
        $stmt->execute([$userId]);
        $addressData = $stmt->fetch();
        if ($addressData) {
            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'address' => $addressData['address'] ?? '',
                'city' => $addressData['city'] ?? '',
                'state' => $addressData['state'] ?? '',
                'zipCode' => $addressData['zipCode'] ?? '',
                'country' => $addressData['country'] ?? '',
            ]);
        }
        echo json_encode(['success' => true]);
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
            if ($user && password_verify($data['currentPassword'], $user['password_hash']) && $data['newPassword'] === $data['confirmPassword']) {
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
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_' . $userId . '.jpg';
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
        } elseif ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'profile_' . $userId . '.jpg';
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($profilePhoto['tmp_name'], $targetFile)) {
                $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
                $stmt->execute(['https://unlabel.lovestoblog.com/uploads/' . $fileName, $userId]);
                $profilePicture = 'https://unlabel.lovestoblog.com/uploads/' . $fileName;
            }
        } else {
            $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $profilePicture = $stmt->fetchColumn();
            if ($profilePicture && strpos($profilePicture, 'http') !== 0) {
                $profilePicture = 'https://unlabel.lovestoblog.com' . $profilePicture;
            }
        }

        // Update session with new profile data
        $stmt = $pdo->prepare("SELECT firstName, lastName, email, phone, birthDate, profile_picture FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        $profileData = $stmt->fetch();
        if ($profileData) {
            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'firstName' => $profileData['firstName'] ?? '',
                'lastName' => $profileData['lastName'] ?? '',
                'email' => $profileData['email'] ?? '',
                'phone' => $profileData['phone'] ?? '',
                'birthDate' => $profileData['birthDate'] ?? '',
                'profile_picture' => $profilePicture ?? $profileData['profile_picture'] ?? '',
            ]);
        }
        echo json_encode(['success' => true, 'profile_picture' => $profilePicture]);
    }
} catch (PDOException | Exception $e) {
    ob_clean();
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
}
?>