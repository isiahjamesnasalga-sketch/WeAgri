<?php
declare(strict_types=1);

return [
    'app_name' => 'WeAgri',
    'timezone' => 'Asia/Manila',
    'storage_path' => __DIR__ . '/../storage/data.json',
    'db' => [
        'host' => getenv('WEAGRI_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('WEAGRI_DB_PORT') ?: '3306',
        'name' => getenv('WEAGRI_DB_NAME') ?: 'weagri',
        'user' => getenv('WEAGRI_DB_USER') ?: 'root',
        'pass' => getenv('WEAGRI_DB_PASS') !== false ? getenv('WEAGRI_DB_PASS') : '',
    ],
];
