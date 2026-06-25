<?php
/**
 ** Header: Authorization: Bearer <token>
 * Deletes the session token from Redis. The client is responsible for
 * clearing localStorage on its side.
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';

$token = getBearerToken();

if ($token !== null && $token !== '') {
    $redis = getRedisConnection();
    $redis->del(SESSION_KEY_PREFIX . $token);
    $redis->close();
}

echo json_encode(['success' => true, 'message' => 'Signed out.']);
