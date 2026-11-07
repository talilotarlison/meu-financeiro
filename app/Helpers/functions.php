<?php
declare(strict_types=1);

function e(string|int|null $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419); exit('Sua sessão expirou. Atualize a página e tente novamente.');
    }
}
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = false;
    if ($user !== false) return $user;
    $stmt = Database::connection()->prepare('SELECT id, name, email FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    if (!$user) unset($_SESSION['user_id']);
    return $user;
}
function require_auth(): array
{
    $user = current_user();
    if (!$user) redirect('/login');
    return $user;
}
function money(int $cents): string { return FinancialService::formatMoney($cents); }
function money_input(int $cents): string { return number_format($cents / 100, 2, ',', '.'); }
function render(string $view, array $data = []): void
{
    if (!array_key_exists('settings', $data) && current_user()) {
        $stmt = Database::connection()->prepare('SELECT currency, theme FROM user_settings WHERE user_id = :user');
        $stmt->execute(['user' => $_SESSION['user_id']]);
        $data['settings'] = $stmt->fetch() ?: ['currency' => 'BRL', 'theme' => 'light'];
    }
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
    require dirname(__DIR__, 2) . '/resources/views/layouts/main.php';
}
