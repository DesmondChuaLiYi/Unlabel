<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://unlabel.lovestoblog.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
    $data = $_POST;
    $profilePhoto = $_FILES['profile_photo'] ?? null;
    $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] === 'true';
    $action = $data['action'] ?? 'update_profile';

    if ($action === 'update_address') {
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
        echo json_encode(['success' => true]);
    } else {
        $updateFields = [];
        $params = [];
        if (isset($data['firstName'])) {
            $updateFields[] = 'firstName = ?';
            $params[] = $data['firstName'];
        }
        if (isset($data['lastName'])) {
            $updateFields[] = 'lastName = ?';
            $params[] = $data['lastName'];
        }
        if (isset($data['email'])) {
            $updateFields[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (isset($data['phone'])) {
            $updateFields[] = 'phone = ?';
            $params[] = $data['phone'];
        }
        if (isset($data['birthDate'])) {
            $updateFields[] = 'birthDate = ?';
            $params[] = $data['birthDate'];
        }
        if (!empty($updateFields)) {
            $stmt = $pdo->prepare("UPDATE user SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $params[] = $userId;
            $stmt->execute($params);
        }

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

        $profilePicture = null;
        if ($removePhoto) {
            $stmt = $pdo->prepare("UPDATE user SET profile_picture = NULL WHERE id = ?");
            $stmt->execute([$userId]);
        } elseif ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
            $imageData = file_get_contents($profilePhoto['tmp_name']);
            $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$imageData, $userId]);
            $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $profilePicture = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $profilePicture = $stmt->fetchColumn();
        }

        $base64Image = $profilePicture ? 'data:image/jpeg;base64,' . base64_encode($profilePicture) : '';
        echo json_encode(['success' => true, 'profile_picture' => $base64Image]);
    }
} catch (PDOException | Exception $e) {
    ob_clean();
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
}
?>