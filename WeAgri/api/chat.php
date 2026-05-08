<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    weagri_error('Method not allowed.', 405);
}

$payload = weagri_read_json();
$message = trim((string) ($payload['message'] ?? ''));

if ($message === '') {
    weagri_error('Message is required.');
}

weagri_json_response([
    'ok' => true,
    'assistant' => weagri_app()->askAssistant($message),
    'state' => weagri_bootstrap_state(),
]);
