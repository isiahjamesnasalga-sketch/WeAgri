<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

$app = weagri_app();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    weagri_json_response([
        'ok' => true,
        'state' => weagri_bootstrap_state(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    weagri_error('Method not allowed.', 405);
}

$payload = weagri_read_json();
$action = (string) ($payload['action'] ?? '');

try {
    if ($action === 'login') {
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $user = $app->authenticateUser($email, $password);

        if (!$user) {
            weagri_error('Invalid email or password.', 401);
        }

        $app->updateUserPresence($user, true);
        weagri_login_user($user);

        weagri_json_response([
            'ok' => true,
            'message' => 'Logged in successfully.',
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'register') {
        $user = $app->registerUser($payload);
        $app->updateUserPresence($user, true);
        weagri_login_user($user);

        weagri_json_response([
            'ok' => true,
            'message' => 'Account created successfully.',
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'logout') {
        $currentUser = weagri_current_user();
        if ($currentUser) {
            $app->updateUserPresence($currentUser, false);
        }
        weagri_logout_user();

        weagri_json_response([
            'ok' => true,
            'message' => 'Logged out successfully.',
            'state' => weagri_bootstrap_state(),
        ]);
    }
} catch (InvalidArgumentException $exception) {
    weagri_error($exception->getMessage(), 422);
} catch (Throwable $exception) {
    weagri_error($exception->getMessage(), 500);
}

weagri_error('Unsupported auth action.');
