<?php
/**
 * Header: Authorization: Bearer <token>
 * Body (JSON): { full_name, age, dob, contact, address }
 *
 * full_name is an identity field -> updated in MySQL (prepared statement).
 * age/dob/contact/address are extended profile fields -> upserted into
 * MongoDB, one document per user keyed by user_id.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mongo_config.php';
require_once __DIR__ . '/auth.php';

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$userId = requireAuthenticatedUserId();

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Invalid request body.']);
}

$fullName = trim((string) ($input['full_name'] ?? ''));
$ageRaw = $input['age'] ?? '';
$dobRaw = trim((string) ($input['dob'] ?? ''));
$contact = trim((string) ($input['contact'] ?? ''));
$address = trim((string) ($input['address'] ?? ''));

if ($fullName === '') {
    respond(400, ['success' => false, 'message' => 'Full name is required.']);
}

$age = null;
if ($ageRaw !== '' && $ageRaw !== null) {
    if (!is_numeric($ageRaw) || (int) $ageRaw < 0 || (int) $ageRaw > 130) {
        respond(400, ['success' => false, 'message' => 'Enter a valid age between 0 and 130.']);
    }
    $age = (int) $ageRaw;
}

$dob = null;
if ($dobRaw !== '') {
    $parsedDob = DateTime::createFromFormat('Y-m-d', $dobRaw);
    if (!$parsedDob || $parsedDob->format('Y-m-d') !== $dobRaw) {
        respond(400, ['success' => false, 'message' => 'Date of birth must be a valid date (YYYY-MM-DD).']);
    }
    $dob = $dobRaw;
}

if ($contact !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $contact)) {
    respond(400, ['success' => false, 'message' => 'Enter a valid contact number.']);
}

if (strlen($address) > 255) {
    respond(400, ['success' => false, 'message' => 'Address must be 255 characters or fewer.']);
}

// --- Update the identity field in MySQL (prepared statement) ---
$conn = getDbConnection();

$stmt = $conn->prepare('UPDATE users SET full_name = ? WHERE id = ?');
$stmt->bind_param('si', $fullName, $userId);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    respond(500, ['success' => false, 'message' => 'Could not update your name.']);
}
$stmt->close();
$conn->close();

// --- Upsert the extended profile fields in MongoDB ---
$profilesCollection = getMongoProfilesCollection();

try {
    $profilesCollection->updateOne(
        ['user_id' => $userId],
        [
            '$set' => [
                'age' => $age,
                'dob' => $dob,
                'contact' => $contact,
                'address' => $address,
                'updated_at' => new \MongoDB\BSON\UTCDateTime(),
            ],
            '$setOnInsert' => [
                'user_id' => $userId,
                'created_at' => new \MongoDB\BSON\UTCDateTime(),
            ],
        ],
        ['upsert' => true]
    );
} catch (\Throwable $e) {
    respond(500, ['success' => false, 'message' => 'Could not update your profile details.']);
}

respond(200, ['success' => true, 'message' => 'Profile updated successfully.']);
