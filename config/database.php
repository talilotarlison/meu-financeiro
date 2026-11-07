<?php
declare(strict_types=1);

$configuredPath = getenv('DATABASE_PATH');
return ['path' => $configuredPath !== false && $configuredPath !== '' ? $configuredPath : dirname(__DIR__) . '/storage/database.sqlite'];
