<?php
/**
 * Token-based auth helpers.
 * The client sends its token (issued at login, stored in localStorage)
 * as: Authorization: Bearer <token>
 * The token is looked up in Redis to find the associated user id.
 * No PHP sessions are used anywhere in this project.
 */

declare(strict_types=1);

require_once __DIR__ . '/redis_config.php';

/**
 * Reads the bearer token from the Authorization header, if present.
 */
function getBearerToken(): ?string
{
    $authHeader = null;

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $authHeader = $value;
                break;
            }
        }
    }

    if ($authHeader === null && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if ($authHeader === null) {
        return null;
    }

    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Resolves the authenticated user id from the token via Redis, or null.
 */
function getAuthenticatedUserId(): ?int
{
    $token = getBearerToken();

    if ($token === null || $token === '') {
        return null;
    }

    $redis = getRedisConnection();
    $userId = $redis->get(SESSION_KEY_PREFIX . $token);
    $redis->close();

    if ($userId === false) {
        return null;
    }

    return (int) $userId;
}

/**
 * Returns the authenticated user id, or halts the request with a 401 JSON response.
 */
function requireAuthenticatedUserId(): int
{
    $userId = getAuthenticatedUserId();

    if ($userId === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Your session has expired. Please sign in again.',
        ]);
        exit;
    }

    return $userId;
}
