<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

weagri_dashboard_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST is required.'], JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);

if (!is_array($payload)) {
    $payload = [];
}

$receiverId = (int) ($payload['receiver_id'] ?? ($payload['consultant_id'] ?? 0));
$messageText = trim((string) ($payload['message_text'] ?? ''));

if ($receiverId <= 0 || $messageText === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'receiver_id and message_text are required.'], JSON_UNESCAPED_SLASHES);
    exit;
}

$legacyUser = weagri_current_user();
if ($legacyUser !== null && $receiverId > 0) {
    try {
        $message = weagri_app()->sendDirectMessage($receiverId, $messageText, $legacyUser);
        echo json_encode([
            'ok' => true,
            'message' => 'Message sent.',
            'message_id' => (int) ($message['id'] ?? 0),
            'sent_message' => $message,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => 'Unable to send the direct consultant message.',
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

try {
    $pdo = weagri_dashboard_pdo();

    $authenticatedUser = weagri_auth_current_user($pdo);
    $senderId = $authenticatedUser ? (int) $authenticatedUser['id'] : 0;

    if ($authenticatedUser) {
        $receiverStatement = $pdo->prepare(
            'SELECT id, role
             FROM users
             WHERE id = :receiver_id
             LIMIT 1'
        );
        $receiverStatement->execute(['receiver_id' => $receiverId]);
        $receiver = $receiverStatement->fetch();

        if (!$receiver) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Message recipient not found.'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $insertStatement = $pdo->prepare(
            'INSERT INTO messages (sender_id, receiver_id, message_text, created_at, is_read)
             VALUES (:sender_id, :receiver_id, :message_text, NOW(), 0)'
        );
        $insertStatement->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_text' => $messageText,
        ]);

        echo json_encode([
            'ok' => true,
            'message' => 'Message sent.',
            'message_id' => (int) $pdo->lastInsertId(),
            'sent_message' => [
                'id' => (int) $pdo->lastInsertId(),
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message_text' => $messageText,
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => false,
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $legacyFarmerIdStatement = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE role = 'farmer'
         ORDER BY id ASC
         LIMIT 1"
    );
    $legacyFarmerIdStatement->execute();
    $senderId = (int) $legacyFarmerIdStatement->fetchColumn();

    $consultantStatement = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE id = :consultant_id
           AND role = 'consultant'
         LIMIT 1"
    );
    $consultantStatement->execute(['consultant_id' => $receiverId]);

    if (!$consultantStatement->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Consultant not found.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO messages (sender_id, receiver_id, message_text, created_at)
         VALUES (:sender_id, :receiver_id, :message_text, NOW())'
    );
    $insertStatement->execute([
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'message_text' => $messageText,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Message sent.',
        'message_id' => (int) $pdo->lastInsertId(),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'source' => 'error',
        'message' => 'Message database is unavailable. Import database/dashboard_schema.sql to enable live chat.',
    ], JSON_UNESCAPED_SLASHES);
}
