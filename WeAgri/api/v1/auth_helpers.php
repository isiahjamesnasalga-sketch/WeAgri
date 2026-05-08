<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

const WEAGRI_AUTH_SESSION_KEY = 'weagri_auth';

function weagri_auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('WEAGRISESSID');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function weagri_auth_token_secret(): string
{
    $secret = getenv('WEAGRI_API_TOKEN_SECRET');
    return $secret !== false && $secret !== ''
        ? $secret
        : 'weagri-local-dev-secret-change-me';
}

function weagri_auth_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function weagri_auth_base64url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function weagri_auth_public_user(array $user): array
{
    $specialtyTags = $user['specialty_tags'] !== null
        ? array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $user['specialty_tags'])
        )))
        : null;

    return [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'specialty_tags' => $specialtyTags,
        'is_online' => (bool) $user['is_online'],
        'last_active' => (string) $user['last_active'],
    ];
}

function weagri_auth_issue_token(array $user): string
{
    $header = weagri_auth_base64url_encode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT',
    ], JSON_UNESCAPED_SLASHES));

    $payload = weagri_auth_base64url_encode(json_encode([
        'sub' => (int) $user['id'],
        'role' => (string) $user['role'],
        'exp' => time() + (7 * 24 * 60 * 60),
    ], JSON_UNESCAPED_SLASHES));

    $signature = hash_hmac('sha256', $header . '.' . $payload, weagri_auth_token_secret(), true);
    return $header . '.' . $payload . '.' . weagri_auth_base64url_encode($signature);
}

function weagri_auth_parse_token(string $token): ?array
{
    $segments = explode('.', $token);
    if (count($segments) !== 3) {
        return null;
    }

    [$header, $payload, $signature] = $segments;
    $expectedSignature = weagri_auth_base64url_encode(
        hash_hmac('sha256', $header . '.' . $payload, weagri_auth_token_secret(), true)
    );

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $decodedPayload = weagri_auth_base64url_decode($payload);
    if ($decodedPayload === false) {
        return null;
    }

    $data = json_decode($decodedPayload, true);
    if (!is_array($data)) {
        return null;
    }

    if ((int) ($data['exp'] ?? 0) < time()) {
        return null;
    }

    return $data;
}

function weagri_auth_extract_token(): ?string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
        return trim((string) $matches[1]);
    }

    $headerToken = (string) ($_SERVER['HTTP_X_WEAGRI_TOKEN'] ?? '');
    return $headerToken !== '' ? trim($headerToken) : null;
}

function weagri_auth_fetch_user(PDO $pdo, int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role, specialty_tags, is_online, last_active
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $userId]);

    $row = $statement->fetch();
    return $row ?: null;
}

function weagri_auth_find_user_by_email(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role, specialty_tags, is_online, last_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => mb_strtolower(trim($email))]);

    $row = $statement->fetch();
    return $row ?: null;
}

function weagri_auth_set_session(array $user): void
{
    weagri_auth_start_session();
    session_regenerate_id(true);
    $_SESSION[WEAGRI_AUTH_SESSION_KEY] = [
        'user_id' => (int) $user['id'],
        'role' => (string) $user['role'],
    ];
}

function weagri_auth_clear_session(): void
{
    weagri_auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function weagri_auth_mark_online(PDO $pdo, int $userId): void
{
    $statement = $pdo->prepare(
        'UPDATE users
         SET is_online = 1, last_active = NOW()
         WHERE id = :id'
    );
    $statement->execute(['id' => $userId]);
}

function weagri_auth_mark_offline(PDO $pdo, int $userId): void
{
    $statement = $pdo->prepare(
        'UPDATE users
         SET is_online = 0, last_active = NOW()
         WHERE id = :id'
    );
    $statement->execute(['id' => $userId]);
}

function weagri_auth_current_user(PDO $pdo): ?array
{
    weagri_auth_start_session();

    $session = $_SESSION[WEAGRI_AUTH_SESSION_KEY] ?? null;
    if (is_array($session) && isset($session['user_id'])) {
        $user = weagri_auth_fetch_user($pdo, (int) $session['user_id']);
        if ($user) {
            return $user;
        }
    }

    $token = weagri_auth_extract_token();
    if ($token === null) {
        return null;
    }

    $payload = weagri_auth_parse_token($token);
    if (!$payload) {
        return null;
    }

    return weagri_auth_fetch_user($pdo, (int) ($payload['sub'] ?? 0));
}

function weagri_auth_error(string $message, int $statusCode = 401): never
{
    http_response_code($statusCode);
    echo json_encode([
        'ok' => false,
        'message' => $message,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

function weagri_auth_require_user(PDO $pdo): array
{
    $user = weagri_auth_current_user($pdo);
    if (!$user) {
        weagri_auth_error('Authentication required.', 401);
    }

    return $user;
}

function weagri_auth_require_role(PDO $pdo, array $roles): array
{
    $user = weagri_auth_require_user($pdo);
    if (!in_array((string) $user['role'], $roles, true)) {
        weagri_auth_error('You do not have permission for this action.', 403);
    }

    return $user;
}
