<?php
declare(strict_types=1);

return [
    'name' => 'Meu Financeiro',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
    'timezone' => 'America/Fortaleza',
    'base_path' => dirname(__DIR__),
];
