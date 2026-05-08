<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

function appointment_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);

    return is_array($data) ? $data : [];
}

function appointment_required_string(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

try {
    $pdo = weagri_dashboard_pdo();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $statement = $pdo->prepare(
            'SELECT a.id, a.expert_id, e.full_name AS expert_name, a.farmer_name, a.farmer_email,
                    a.crop_name, a.location, a.concern, a.appointment_date, a.status, a.created_at
             FROM appointments a
             INNER JOIN experts e ON e.id = a.expert_id
             ORDER BY a.appointment_date DESC, a.id DESC
             LIMIT 50'
        );
        $statement->execute();

        echo json_encode([
            'ok' => true,
            'source' => 'mysql',
            'appointments' => $statement->fetchAll(),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $data = appointment_json_input();
    $expertId = (int) ($data['expert_id'] ?? 0);
    $farmerName = appointment_required_string($data, 'farmer_name');
    $farmerEmail = appointment_required_string($data, 'farmer_email');
    $cropName = appointment_required_string($data, 'crop_name');
    $location = appointment_required_string($data, 'location');
    $concern = appointment_required_string($data, 'concern');
    $appointmentDate = appointment_required_string($data, 'appointment_date');

    if (
        $expertId <= 0 ||
        $farmerName === '' ||
        $farmerEmail === '' ||
        $cropName === '' ||
        $location === '' ||
        $concern === '' ||
        $appointmentDate === ''
    ) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Please provide expert, farmer, crop, location, concern, and appointment date.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $normalizedDate = date('Y-m-d H:i:s', strtotime($appointmentDate));

    if ($normalizedDate === '1970-01-01 00:00:00') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Please provide a valid appointment date.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $expertStatement = $pdo->prepare('SELECT id FROM experts WHERE id = :expert_id LIMIT 1');
    $expertStatement->execute(['expert_id' => $expertId]);

    if (!$expertStatement->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Selected expert was not found.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO appointments
            (expert_id, farmer_name, farmer_email, crop_name, location, concern, appointment_date)
         VALUES
            (:expert_id, :farmer_name, :farmer_email, :crop_name, :location, :concern, :appointment_date)'
    );
    $insertStatement->execute([
        'expert_id' => $expertId,
        'farmer_name' => $farmerName,
        'farmer_email' => $farmerEmail,
        'crop_name' => $cropName,
        'location' => $location,
        'concern' => $concern,
        'appointment_date' => $normalizedDate,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Appointment request booked.',
        'appointment_id' => (int) $pdo->lastInsertId(),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode([
        'ok' => false,
        'source' => 'error',
        'message' => 'Appointment database is unavailable. Import database/dashboard_schema.sql to enable booking.',
    ], JSON_UNESCAPED_SLASHES);
}
