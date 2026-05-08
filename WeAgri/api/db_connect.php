<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function weagri_dashboard_headers(): void
{
    weagri_api_cors_headers();
}

function weagri_dashboard_pdo(): PDO
{
    return weagri_api_pdo();
}
