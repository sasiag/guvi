<?php
/**
 * MySQL connection helper.
 * MySQL owns identity/auth data only (the `users` table: full_name,
 * username, email, password_hash). Extended profile fields (age, dob,
 * contact, address) live in MongoDB — see mongo_config.php.
 * Update the constants below to match your environment.
 */

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'profilo_db';
const DB_USER = 'root';
const DB_PASS = 'mysql';
const DB_CHARSET = 'utf8mb4';

/**
 * Returns a connected mysqli instance.
 * Sends a JSON error response and stops execution on failure.
 */
function getDbConnection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_errno) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed.',
        ]);
        exit;
    }

    $conn->set_charset(DB_CHARSET);

    return $conn;
}
