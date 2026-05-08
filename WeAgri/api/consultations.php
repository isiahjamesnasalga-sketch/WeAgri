<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

$app = weagri_app();
$currentUser = weagri_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $consultationId = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $consultation = $consultationId ? $app->getConsultation($consultationId, $currentUser) : null;

    weagri_json_response([
        'ok' => true,
        'consultation' => $consultation,
        'state' => weagri_bootstrap_state(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    weagri_error('Method not allowed.', 405);
}

$payload = weagri_read_json();
$action = (string) ($payload['action'] ?? '');

try {
    if ($action === 'create') {
        $user = weagri_require_role(['farmer']);
        $consultation = $app->createConsultation($payload, $user);
        weagri_json_response([
            'ok' => true,
            'message' => 'Consultation created successfully.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'message' || $action === 'farmer_message') {
        $consultationId = (int) ($payload['consultation_id'] ?? 0);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($consultationId <= 0) {
            weagri_error('Consultation id is required.');
        }

        $user = weagri_require_role(['farmer']);
        $consultation = $app->addFarmerMessage($consultationId, $message, $user);
        if (!$consultation) {
            weagri_error('Consultation not found.', 404);
        }

        weagri_json_response([
            'ok' => true,
            'message' => 'Message sent successfully.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'consultant_response') {
        $consultationId = (int) ($payload['consultation_id'] ?? 0);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($consultationId <= 0) {
            weagri_error('Consultation id is required.');
        }

        $user = weagri_require_role(['consultant']);
        $consultation = $app->addConsultantResponse($consultationId, $message, $user);
        if (!$consultation) {
            weagri_error('Consultation not found.', 404);
        }

        weagri_json_response([
            'ok' => true,
            'message' => 'Consultant response sent successfully.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'assign') {
        $consultationId = (int) ($payload['consultation_id'] ?? 0);
        $consultantId = (int) ($payload['consultant_id'] ?? 0);

        if ($consultationId <= 0 || $consultantId <= 0) {
            weagri_error('Consultation and consultant are required.');
        }

        $user = weagri_require_role(['admin']);
        $consultation = $app->assignConsultant($consultationId, $consultantId, $user);
        if (!$consultation) {
            weagri_error('Consultation not found.', 404);
        }

        weagri_json_response([
            'ok' => true,
            'message' => 'Consultant assignment updated.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'status') {
        $consultationId = (int) ($payload['consultation_id'] ?? 0);
        $status = trim((string) ($payload['status'] ?? ''));

        if ($consultationId <= 0) {
            weagri_error('Consultation id is required.');
        }

        $user = weagri_require_role(['admin']);
        $consultation = $app->updateConsultationStatus($consultationId, $status, $user);
        if (!$consultation) {
            weagri_error('Consultation not found.', 404);
        }

        weagri_json_response([
            'ok' => true,
            'message' => 'Consultation status updated.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }

    if ($action === 'feedback') {
        $user = weagri_require_role(['farmer']);
        $consultation = $app->submitFeedback($payload, $user);
        if (!$consultation) {
            weagri_error('Consultation not found.', 404);
        }

        weagri_json_response([
            'ok' => true,
            'message' => 'Thank you. Your feedback helps improve AgroLLM and advisor support.',
            'consultation' => $consultation,
            'state' => weagri_bootstrap_state(),
        ]);
    }
} catch (InvalidArgumentException $exception) {
    weagri_error($exception->getMessage(), 422);
} catch (Throwable $exception) {
    weagri_error($exception->getMessage(), 500);
}

weagri_error('Unsupported consultation action.');
