<?php
/**
 * Header: Authorization: Bearer <token>
 *
 * Identity fields (full_name, username, email) come from MySQL.
 * Extended profile fields (age, dob, contact, address) come from MongoDB.
 */

declare(strict_types=1);
/*ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);*/
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mongo_config.php';
require_once __DIR__ . '/auth.php';

$userId = requireAuthenticatedUserId();

// --- Identity, from MySQL (prepared statement) ---
$conn = getDbConnection();

$stmt = $conn->prepare('SELECT id, full_name, username, email FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$account = $result->fetch_assoc();
$stmt->close();
$conn->close();

// --- Extended profile fields, from MongoDB ---
$profilesCollection = getMongoProfilesCollection();
$profileDoc = $profilesCollection->findOne(['user_id' => $userId]);

$age = null;
$dob = null;
$contact = '';
$address = '';

if ($profileDoc !== null) {
    $age = isset($profileDoc['age']) && $profileDoc['age'] !== null ? (int) $profileDoc['age'] : null;
    $dob = $profileDoc['dob'] ?? null;
    $contact = $profileDoc['contact'] ?? '';
    $address = $profileDoc['address'] ?? '';
}

echo json_encode([
    'success' => true,
    'data' => [
        'id' => (int) $account['id'],
        'full_name' => $account['full_name'],
        'username' => $account['username'],
        'email' => $account['email'],
        'age' => $age,
        'dob' => $dob,
        'contact' => $contact,
        'address' => $address,
    ],
]);
