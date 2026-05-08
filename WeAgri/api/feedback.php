<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    weagri_error('Method not allowed.', 405);
}

$payload = weagri_read_json();

try {
    $feedback = weagri_app()->submitPlatformFeedback($payload, weagri_current_user());

    weagri_json_response([
        'ok' => true,
        'message' => 'Thank you. Your review was saved.',
        'feedback' => $feedback,
        'state' => weagri_bootstrap_state(),
    ]);
} catch (InvalidArgumentException $exception) {
    weagri_error($exception->getMessage(), 422);
} catch (Throwable $exception) {
    weagri_error('Unable to save feedback right now.', 500);
}
