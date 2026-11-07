<?php
declare(strict_types=1);

final class SettingsController
{
    public function theme(): void
    {
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        verify_csrf();
        $theme = (string) ($_POST['theme'] ?? '');
        if (!in_array($theme, ['light', 'dark'], true)) { http_response_code(422); exit; }
        $stmt = Database::connection()->prepare('UPDATE user_settings SET theme = :theme, updated_at = :at WHERE user_id = :id');
        $stmt->execute(['theme' => $theme, 'at' => gmdate('c'), 'id' => $_SESSION['user_id']]);
        header('Content-Type: application/json'); echo json_encode(['theme' => $theme]);
    }
}
