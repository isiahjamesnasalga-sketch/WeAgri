<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';

weagri_json_response([
    'ok' => true,
    'state' => weagri_bootstrap_state(),
]);
