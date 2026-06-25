<?php
/**
 * MongoDB connection helper.
 * Requires:
 *   - the mongodb PHP extension (pecl install mongodb)
 *   - the mongodb/mongodb composer package (composer require mongodb/mongodb),
 *     run from the project root so vendor/ lands next to this api/ folder.
 *
 * Extended profile fields (age, dob, contact, address) are stored here,
 * one document per user, keyed by the MySQL user id:
 *   { user_id: 7, age: 29, dob: "1996-04-12", contact: "...", address: "...",
 *     created_at: <UTCDateTime>, updated_at: <UTCDateTime> }
 *
 * Account/identity fields (full_name, username, email, password_hash) stay
 * in MySQL — see config.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

const MONGO_URI = 'mongodb://localhost:27017';
const MONGO_DB_NAME = 'profilo';
const MONGO_PROFILES_COLLECTION = 'profiles';

/**
 * Returns the "profiles" collection handle.
 * Sends a JSON error response and stops execution on failure.
 */
function getMongoProfilesCollection(): \MongoDB\Collection
{
    try {
        $client = new \MongoDB\Client(MONGO_URI);

        return $client
            ->selectDatabase(MONGO_DB_NAME)
            ->selectCollection(MONGO_PROFILES_COLLECTION);
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Profile store connection failed.',
        ]);
        exit;
    }
}
