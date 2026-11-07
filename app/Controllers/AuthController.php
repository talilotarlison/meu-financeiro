<?php
declare(strict_types=1);

final class AuthController
{
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') { render('auth/register', ['title' => 'Criar conta']); return; }
        verify_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Informe seu nome (de 2 a 120 caracteres).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'Informe um e-mail válido.';
        if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) $errors[] = 'Use uma senha com pelo menos 10 caracteres, letras e números.';
        if ($password !== $confirmation) $errors[] = 'A confirmação da senha não confere.';
        $db = Database::connection();
        if (!$errors) {
            $check = $db->prepare('SELECT 1 FROM users WHERE email = :email'); $check->execute(['email' => $email]);
            if ($check->fetchColumn()) $errors[] = 'Este e-mail já está cadastrado.';
        }
        if ($errors) { render('auth/register', ['title' => 'Criar conta', 'errors' => $errors, 'old' => ['name' => $name, 'email' => $email]]); return; }
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO users(name,email,password_hash,created_at,updated_at) VALUES(:name,:email,:hash,:created,:updated)');
            $createdAt=gmdate('c');$stmt->execute(['name'=>$name,'email'=>$email,'hash'=>password_hash($password,PASSWORD_DEFAULT),'created'=>$createdAt,'updated'=>$createdAt]);
            $userId = (int) $db->lastInsertId();
            $settings = $db->prepare('INSERT INTO user_settings(user_id,currency,date_format,theme,created_at,updated_at) VALUES(:id,"BRL","d/m/Y","light",:created,:updated)');
            $settings->execute(['id'=>$userId,'created'=>$createdAt,'updated'=>$createdAt]);
            $seed = $db->prepare('INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at) VALUES(:user_id,:name,:type,:color,:icon,:created,:updated)');
            $defaults = [
                ['Salário','income','#168b68','↗'], ['Freelance','income','#4d9d7d','↗'], ['Outros','income','#78909c','◈'],
                ['Alimentação','expense','#d7854d','◈'], ['Moradia','expense','#5478d4','⌂'], ['Transporte','expense','#8b72c3','↗'],
                ['Saúde','expense','#d46d75','✚'], ['Educação','expense','#548caa','▤'], ['Lazer','expense','#a077bb','☆'], ['Outros','expense','#78909c','◈'],
            ];
            foreach ($defaults as [$categoryName,$categoryType,$color,$icon]) $seed->execute(['user_id'=>$userId,'name'=>$categoryName,'type'=>$categoryType,'color'=>$color,'icon'=>$icon,'created'=>$createdAt,'updated'=>$createdAt]);
            $db->commit();
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
        session_regenerate_id(true); $_SESSION['user_id'] = $userId;
        redirect('/dashboard');
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') { render('auth/login', ['title' => 'Entrar']); return; }
        verify_csrf();
        $now = time(); $attempts = $_SESSION['_login_attempts'] ?? ['count' => 0, 'since' => $now];
        if ($now - (int) $attempts['since'] > 900) $attempts = ['count' => 0, 'since' => $now];
        if ((int) $attempts['count'] >= 8) {
            render('auth/login', ['title' => 'Entrar', 'errors' => ['Muitas tentativas. Aguarde 15 minutos e tente novamente.']]); return;
        }
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $stmt = Database::connection()->prepare('SELECT id,password_hash,status FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]); $user = $stmt->fetch();
        if (!$user || !password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            $attempts['count']++; $_SESSION['_login_attempts'] = $attempts;
            render('auth/login', ['title' => 'Entrar', 'errors' => ['E-mail ou senha incorretos.'], 'old' => ['email' => $email]]); return;
        }
        if ($user['status'] !== 'active') {
            render('auth/login', ['title' => 'Entrar', 'errors' => ['Esta conta está desativada. Entre em contato com o suporte.'], 'old' => ['email' => $email]]); return;
        }
        $lastLogin = Database::connection()->prepare('UPDATE users SET last_login_at = :at WHERE id = :id');
        $lastLogin->execute(['at' => gmdate('c'), 'id' => $user['id']]);
        unset($_SESSION['_login_attempts']); session_regenerate_id(true); $_SESSION['user_id'] = (int) $user['id'];
        redirect('/dashboard');
    }

    public function logout(): void { verify_csrf(); $_SESSION = []; session_regenerate_id(true); redirect('/login'); }
}
