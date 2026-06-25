<?php
/**
 * Redis connection helper.
 * Requires the phpredis extension (https://github.com/phpredis/phpredis).
 * Update the constants below to match your environment.
 */

declare(strict_types=1);

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;
const REDIS_PASSWORD = null;  // set to a string if your Redis server requires auth
const SESSION_TTL_SECONDS = 86400; // 24 hours
const SESSION_KEY_PREFIX = 'session:';

/**
 * Returns a connected Redis instance.
 * Sends a JSON error response and stops execution on failure.
 */
function getRedisConnection(): Redis
{
    $redis = new Redis();

    try {
        $connected = $redis->connect(REDIS_HOST, REDIS_PORT, 2.0);

        if (!$connected) {
            throw new RedisException('Unable to connect to Redis.');
        }

        if (REDIS_PASSWORD !== null) {
            $redis->auth(REDIS_PASSWORD);
        }
    } catch (RedisException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Session store connection failed.',
        ]);
        exit;
    }

    return $redis;
}
