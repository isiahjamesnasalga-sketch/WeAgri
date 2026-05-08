<?php
declare(strict_types=1);

require_once __DIR__ . '/AgroRagEngine.php';
require_once __DIR__ . '/AgroAssistant.php';
require_once __DIR__ . '/WeAgriDataStore.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionPath = session_save_path();
    $needsProjectSessionPath = PHP_SAPI === 'cli'
        || $sessionPath === ''
        || !is_dir($sessionPath)
        || !is_writable($sessionPath);
    if ($needsProjectSessionPath) {
        $fallbackSessionPath = __DIR__ . '/../storage/sessions';
        if (!is_dir($fallbackSessionPath)) {
            @mkdir($fallbackSessionPath, 0777, true);
        }
        if (is_dir($fallbackSessionPath) && is_writable($fallbackSessionPath)) {
            session_save_path($fallbackSessionPath);
        }
    }

    session_name('weagri_session');
    session_start();
}

function weagri_config(): array
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    return $config;
}

function weagri_app(): WeAgriDataStore
{
    static $app;

    if ($app === null) {
        $app = new WeAgriDataStore(weagri_config());
    }

    return $app;
}

function weagri_current_user(): ?array
{
    $userId = (int) ($_SESSION['weagri_user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $user = weagri_app()->getUserById($userId);
    if (!$user) {
        unset($_SESSION['weagri_user_id']);
        return null;
    }

    return $user;
}

function weagri_login_user(array $user): void
{
    $_SESSION['weagri_user_id'] = (int) $user['id'];
    session_regenerate_id(true);
}

function weagri_logout_user(): void
{
    unset($_SESSION['weagri_user_id']);
    session_regenerate_id(true);
}

function weagri_bootstrap_state(): array
{
    return weagri_app()->getBootstrap(weagri_current_user());
}

function weagri_require_auth(): array
{
    $user = weagri_current_user();
    if (!$user) {
        weagri_error('Please log in first.', 401);
    }

    return $user;
}

function weagri_require_role(array $roles): array
{
    $user = weagri_require_auth();
    if (!in_array($user['role'], $roles, true)) {
        weagri_error('You do not have permission for this action.', 403);
    }

    return $user;
}

function weagri_read_json(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return [];
    }

    return is_array($decoded) ? $decoded : [];
}

function weagri_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function weagri_error(string $message, int $status = 400): void
{
    weagri_json_response([
        'ok' => false,
        'message' => $message,
    ], $status);
}
