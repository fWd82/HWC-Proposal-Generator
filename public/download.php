<?php

declare(strict_types=1);

use ProposalGenerator\Config;

require_once __DIR__ . '/../vendor/autoload.php';

start_secure_session();
$token = $_GET['token'] ?? '';
$filename = $_SESSION['download_file'] ?? '';
$valid = is_string($token)
    && is_string($filename)
    && isset($_SESSION['download_token'])
    && hash_equals($_SESSION['download_token'], $token)
    && preg_match('/\A[A-Za-z0-9-]+\.docx\z/', $filename)
    && basename($filename) === $filename;

if (!$valid) {
    http_response_code(404);
    exit('This download link is invalid or has expired.');
}

$path = Config::GENERATED_DIR . $filename;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('The generated proposal is no longer available.');
}

unset($_SESSION['download_token'], $_SESSION['download_file']);
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
