<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';

weagri_dashboard_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    weagri_auth_error('POST is required.', 405);
}

try {
    $pdo = weagri_dashboard_pdo();
    $user = weagri_auth_current_user($pdo);

    if ($user) {
        weagri_auth_mark_offline($pdo, (int) $user['id']);
    }

    weagri_auth_clear_session();

    echo json_encode([
        'ok' => true,
        'message' => 'Logged out successfully.',
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Logout failed because the authentication service is unavailable.',
    ], JSON_UNESCAPED_SLASHES);
}
