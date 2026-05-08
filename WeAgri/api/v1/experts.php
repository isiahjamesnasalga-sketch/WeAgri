<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

function demo_experts_payload(string $source, string $message): array
{
    return [
        'ok' => $source !== 'error',
        'source' => $source,
        'message' => $message,
        'experts' => [
            [
                'id' => 1,
                'full_name' => 'Dr. Liza Santos',
                'specialty' => 'Plant Pathology',
                'email' => 'liza.santos@weagri.local',
                'location' => 'Laguna',
                'status' => 'online',
                'response_minutes' => 8,
                'bio' => 'Helps diagnose crop disease symptoms and safe field management steps.',
            ],
            [
                'id' => 2,
                'full_name' => 'Marco Reyes',
                'specialty' => 'Soil Health',
                'email' => 'marco.reyes@weagri.local',
                'location' => 'Nueva Ecija',
                'status' => 'online',
                'response_minutes' => 12,
                'bio' => 'Advises on soil testing, composting, fertilizer timing, and water management.',
            ],
        ],
    ];
}

try {
    $pdo = weagri_dashboard_pdo();
    $statement = $pdo->prepare(
        'SELECT id, full_name, specialty, email, location, status, response_minutes, bio
         FROM experts
         ORDER BY FIELD(status, "online", "busy", "offline"), full_name ASC'
    );
    $statement->execute();

    $experts = array_map(
        static fn (array $row): array => [
            'id' => (int) $row['id'],
            'full_name' => (string) $row['full_name'],
            'specialty' => (string) $row['specialty'],
            'email' => (string) ($row['email'] ?? ''),
            'location' => (string) ($row['location'] ?? ''),
            'status' => (string) $row['status'],
            'response_minutes' => (int) $row['response_minutes'],
            'bio' => (string) $row['bio'],
        ],
        $statement->fetchAll()
    );

    echo json_encode([
        'ok' => true,
        'source' => 'mysql',
        'experts' => $experts,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode(
        demo_experts_payload('error', 'Expert database is unavailable. Import database/dashboard_schema.sql to enable live expert profiles.'),
        JSON_UNESCAPED_SLASHES
    );
}
