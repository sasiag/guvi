<?php
/**
 * POST /php/register.php
 * Body (JSON): { full_name, username, email, password }
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Invalid request body.']);
}

$fullName = trim((string) ($input['full_name'] ?? ''));
$username = trim((string) ($input['username'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($fullName === '' || $username === '' || $email === '' || $password === '') {
    respond(400, ['success' => false, 'message' => 'All fields are required.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['success' => false, 'message' => 'Please enter a valid email address.']);
}

if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)) {
    respond(400, ['success' => false, 'message' => 'Username must be 3-50 characters: letters, numbers, dot or underscore only.']);
}

if (strlen($password) < 6) {
    respond(400, ['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

$conn = getDbConnection();

// Check for an existing username or email — prepared statement, no string concatenation.
$checkStmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$checkStmt->bind_param('ss', $username, $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    $conn->close();
    respond(409, ['success' => false, 'message' => 'That username or email is already registered.']);
}
$checkStmt->close();

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$insertStmt = $conn->prepare(
    'INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)'
);
$insertStmt->bind_param('ssss', $fullName, $username, $email, $passwordHash);

if ($insertStmt->execute()) {
    $insertStmt->close();
    $conn->close();
    respond(201, ['success' => true, 'message' => 'Account created successfully.']);
}

$insertStmt->close();
$conn->close();
respond(500, ['success' => false, 'message' => 'Could not create the account. Please try again.']);
