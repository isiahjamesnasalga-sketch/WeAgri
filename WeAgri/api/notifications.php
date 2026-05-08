<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

$app = weagri_app();
$currentUser = weagri_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    weagri_json_response([
        'ok' => true,
        'notifications' => $app->getNotifications($currentUser),
        'state' => weagri_bootstrap_state(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    weagri_error('Method not allowed.', 405);
}

$payload = weagri_read_json();
$notificationId = (int) ($payload['id'] ?? 0);

if ($notificationId <= 0) {
    weagri_error('Notification id is required.');
}

$user = weagri_require_auth();
$app->markNotificationRead($notificationId, $user);

weagri_json_response([
    'ok' => true,
    'message' => 'Notification updated.',
    'state' => weagri_bootstrap_state(),
]);
