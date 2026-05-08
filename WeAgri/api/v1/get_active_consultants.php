<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';

weagri_dashboard_headers();

try {
    $pdo = weagri_dashboard_pdo();
    weagri_auth_require_role($pdo, ['farmer', 'consultant', 'admin']);

    $statement = $pdo->prepare(
        "SELECT id, full_name, specialty_tags, is_online, last_active
         FROM users
         WHERE role = 'consultant'
         ORDER BY is_online DESC, last_active DESC, full_name ASC"
    );
    $statement->execute();

    $consultants = array_map(
        static function (array $row): array {
            $specialties = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) ($row['specialty_tags'] ?? ''))
            )));

            return [
                'id' => (int) $row['id'],
                'full_name' => (string) $row['full_name'],
                'specialty_tags' => $specialties,
                'is_online' => (bool) $row['is_online'],
                'last_active' => (string) $row['last_active'],
            ];
        },
        $statement->fetchAll()
    );

    echo json_encode([
        'ok' => true,
        'consultants' => $consultants,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to load consultants from the database.',
        'consultants' => [],
    ], JSON_UNESCAPED_SLASHES);
}
