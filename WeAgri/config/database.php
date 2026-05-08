<?php
declare(strict_types=1);

function weagri_api_cors_headers(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=UTF-8');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        exit;
    }
}

function weagri_api_pdo(): PDO
{
    $host = getenv('WEAGRI_DASHBOARD_DB_HOST') ?: (getenv('WEAGRI_DB_HOST') ?: '127.0.0.1');
    $port = getenv('WEAGRI_DASHBOARD_DB_PORT') ?: (getenv('WEAGRI_DB_PORT') ?: '3306');
    $database = getenv('WEAGRI_DASHBOARD_DB_NAME') ?: (getenv('WEAGRI_DB_NAME') ?: 'weagri_db');
    $username = getenv('WEAGRI_DASHBOARD_DB_USER') ?: (getenv('WEAGRI_DB_USER') ?: 'root');
    $password = getenv('WEAGRI_DASHBOARD_DB_PASS');

    if ($password === false) {
        $password = getenv('WEAGRI_DB_PASS');
    }

    if ($password === false) {
        $password = '';
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    return new PDO($dsn, $username, (string) $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
