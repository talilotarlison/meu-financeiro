<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection) return self::$connection;
        $config = require dirname(__DIR__) . '/config/database.php';
        $directory = dirname($config['path']);
        if (!is_dir($directory)) mkdir($directory, 0770, true);
        self::$connection = new PDO('sqlite:' . $config['path'], null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$connection->exec('PRAGMA foreign_keys = ON');
        self::$connection->exec('PRAGMA journal_mode = WAL');
        self::$connection->exec('PRAGMA busy_timeout = 5000');
        return self::$connection;
    }
}
