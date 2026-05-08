<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

weagri_dashboard_headers();

$partnerId = (int) ($_GET['partner_id'] ?? 0);
$consultantId = (int) ($_GET['consultant_id'] ?? 0);
$afterCursor = trim((string) ($_GET['after'] ?? ''));

$legacyUser = weagri_current_user();
$legacyPartnerId = $partnerId > 0 ? $partnerId : $consultantId;
if ($legacyUser !== null && $legacyPartnerId > 0) {
    try {
        $messages = weagri_app()->getDirectMessages($legacyPartnerId, $legacyUser);
        echo json_encode([
            'ok' => true,
            'messages' => $messages,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => 'Unable to load direct consultant messages.',
            'messages' => [],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

try {
    $pdo = weagri_dashboard_pdo();

    $authenticatedUser = weagri_auth_current_user($pdo);
    if ($authenticatedUser && $partnerId > 0) {
        $query = 'SELECT id, sender_id, receiver_id, message_text, created_at, is_read
                  FROM messages
                  WHERE (
                        (sender_id = :user_id AND receiver_id = :partner_id)
                     OR (sender_id = :partner_id AND receiver_id = :user_id)
                  )';

        $params = [
            'user_id' => (int) $authenticatedUser['id'],
            'partner_id' => $partnerId,
        ];

        if ($afterCursor !== '') {
            $query .= ' AND created_at > :after_cursor';
            $params['after_cursor'] = $afterCursor;
        }

        $query .= ' ORDER BY created_at ASC, id ASC';

        $statement = $pdo->prepare($query);
        $statement->execute($params);
        $rows = $statement->fetchAll();

        $markRead = $pdo->prepare(
            'UPDATE messages
             SET is_read = 1
             WHERE sender_id = :partner_id
               AND receiver_id = :user_id
               AND is_read = 0'
        );
        $markRead->execute([
            'partner_id' => $partnerId,
            'user_id' => (int) $authenticatedUser['id'],
        ]);

        $messages = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'sender_id' => (int) $row['sender_id'],
                'receiver_id' => (int) $row['receiver_id'],
                'message_text' => (string) $row['message_text'],
                'created_at' => (string) $row['created_at'],
                'is_read' => (bool) $row['is_read'],
            ],
            $rows
        );

        echo json_encode([
            'ok' => true,
            'messages' => $messages,
            'cursor' => $messages !== [] ? end($messages)['created_at'] : $afterCursor,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($consultantId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'partner_id is required.'], JSON_UNESCAPED_SLASHES);
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
    $legacyFarmerId = (int) $legacyFarmerIdStatement->fetchColumn();

    $statement = $pdo->prepare(
        'SELECT id, sender_id, receiver_id, message_text, created_at
         FROM messages
         WHERE (sender_id = :farmer_id AND receiver_id = :consultant_id)
            OR (sender_id = :consultant_id AND receiver_id = :farmer_id)
         ORDER BY created_at ASC, id ASC'
    );
    $statement->execute([
        'farmer_id' => $legacyFarmerId,
        'consultant_id' => $consultantId,
    ]);

    $messages = array_map(
        static fn (array $row): array => [
            'id' => (int) $row['id'],
            'sender_id' => (int) $row['sender_id'],
            'receiver_id' => (int) $row['receiver_id'],
            'sender_type' => (int) $row['sender_id'] === $consultantId ? 'consultant' : 'farmer',
            'message_text' => (string) $row['message_text'],
            'created_at' => (string) $row['created_at'],
        ],
        $statement->fetchAll()
    );

    echo json_encode([
        'ok' => true,
        'messages' => $messages,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load messages from the database.',
        'messages' => [],
    ], JSON_UNESCAPED_SLASHES);
}
