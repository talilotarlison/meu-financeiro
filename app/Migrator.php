<?php
declare(strict_types=1);

final class Migrator
{
    public static function run(PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS migrations (version TEXT PRIMARY KEY, applied_at TEXT NOT NULL)');
        $files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
        sort($files);
        foreach ($files as $file) {
            $version = basename($file);
            $check = $db->prepare('SELECT 1 FROM migrations WHERE version = :version');
            $check->execute(['version' => $version]);
            if ($check->fetchColumn()) continue;
            $db->beginTransaction();
            try {
                $db->exec((string) file_get_contents($file));
                $insert = $db->prepare('INSERT INTO migrations(version, applied_at) VALUES(:version, :at)');
                $insert->execute(['version' => $version, 'at' => gmdate('c')]);
                $db->commit();
            } catch (Throwable $error) {
                $db->rollBack();
                throw $error;
            }
        }
    }
}
