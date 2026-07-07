<?php

declare(strict_types=1);

use ProposalGenerator\Config;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    start_secure_session();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $type, string $message): void
{
    start_secure_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    start_secure_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function app_log(\Throwable $error): void
{
    try {
        Config::ensureDirectories();
        $line = sprintf(
            "[%s] %s in %s:%d\n",
            date('c'),
            str_replace(["\r", "\n"], ' ', $error->getMessage()),
            $error->getFile(),
            $error->getLine()
        );
        error_log($line, 3, Config::LOG_DIR . 'app.log');
    } catch (\Throwable) {
        error_log($error->getMessage());
    }
}
