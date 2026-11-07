<?php
declare(strict_types=1);

final class ProfileController
{
    public function index(): void
    {
        $user = require_auth();
        $stmt = Database::connection()->prepare('SELECT id, name, email, status, last_login_at, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        render('profile/index', ['title' => 'Meu perfil', 'profile' => $stmt->fetch()]);
    }
}
