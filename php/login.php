<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/redis_config.php';

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

$identifier = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($identifier === '' || $password === '') {
    respond(400, ['success' => false, 'message' => 'Username/email and password are required.']);
}

$conn = getDbConnection();

$stmt = $conn->prepare(
    'SELECT id, full_name, username, email, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1'
);
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    respond(401, ['success' => false, 'message' => 'Incorrect username/email or password.']);
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!password_verify($password, $user['password_hash'])) {
    respond(401, ['success' => false, 'message' => 'Incorrect username/email or password.']);
}

// Issue a fresh, opaque session token and store it in Redis (server-side session store).
$token = bin2hex(random_bytes(32));

$redis = getRedisConnection();
$redis->set(SESSION_KEY_PREFIX . $token, (string) $user['id'], ['EX' => SESSION_TTL_SECONDS]);
$redis->close();

respond(200, [
    'success' => true,
    'message' => 'Signed in successfully.',
    'token' => $token,
    'user' => [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'email' => $user['email'],
    ],
]);
