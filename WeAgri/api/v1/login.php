<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';

weagri_dashboard_headers();

try {
    $pdo = weagri_dashboard_pdo();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $user = weagri_auth_current_user($pdo);
        if (!$user) {
            weagri_auth_error('No active session.', 401);
        }

        echo json_encode([
            'ok' => true,
            'user' => weagri_auth_public_user($user),
            'token' => weagri_auth_issue_token($user),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        weagri_auth_error('Method not allowed.', 405);
    }

    $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
    $password = (string) ($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        weagri_auth_error('Email and password are required.', 422);
    }

    $user = weagri_auth_find_user_by_email($pdo, $email);
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        weagri_auth_error('Invalid email or password.', 401);
    }

    weagri_auth_mark_online($pdo, (int) $user['id']);
    $user = weagri_auth_fetch_user($pdo, (int) $user['id']) ?? $user;
    weagri_auth_set_session($user);

    echo json_encode([
        'ok' => true,
        'message' => 'Login successful.',
        'token' => weagri_auth_issue_token($user),
        'user' => weagri_auth_public_user($user),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Login failed because the authentication service is unavailable.',
    ], JSON_UNESCAPED_SLASHES);
}
