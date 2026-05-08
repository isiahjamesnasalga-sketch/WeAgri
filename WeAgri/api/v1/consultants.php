<?php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/bootstrap.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.',
        'consultants' => [],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $consultants = weagri_app()->getDirectChatDirectory(weagri_current_user());
    echo json_encode([
        'ok' => true,
        'consultants' => $consultants,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load consultants from the current WeAgri account store.',
        'consultants' => [],
    ], JSON_UNESCAPED_SLASHES);
}
