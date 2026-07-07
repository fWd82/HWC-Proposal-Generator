<?php

declare(strict_types=1);

use ProposalGenerator\FileUploadValidator;
use ProposalGenerator\ProposalDataBuilder;
use ProposalGenerator\ProposalGenerator;
use ProposalGenerator\QuoteParser;
use ProposalGenerator\ServiceRepository;

require_once __DIR__ . '/../vendor/autoload.php';

start_secure_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

$uploadPath = null;
try {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('Your session expired. Please refresh the form and try again.');
    }

    $_SESSION['old'] = array_map(
        static fn ($value) => is_string($value) ? $value : '',
        array_diff_key($_POST, ['csrf_token' => true])
    );
    foreach (['customer_name' => 'Customer name', 'proposal_title' => 'Proposal title', 'proposal_date' => 'Proposal date', 'prepared_by' => 'Prepared by'] as $key => $label) {
        if (trim((string) ($_POST[$key] ?? '')) === '') {
            throw new RuntimeException("{$label} is required.");
        }
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $_POST['proposal_date']);
    if (!$date || $date->format('Y-m-d') !== $_POST['proposal_date']) {
        throw new RuntimeException('Please provide a valid proposal date.');
    }

    $uploadPath = (new FileUploadValidator())->validateAndStore($_FILES['quote_file'] ?? []);
    $quote = (new QuoteParser())->parse($uploadPath);
    $services = (new ServiceRepository())->getServicesForQuote($quote['unique_services']);
    $data = (new ProposalDataBuilder())->build($_POST, $quote, $services);
    $filename = (new ProposalGenerator())->generate($data);

    unset($_SESSION['old']);
    $_SESSION['download_token'] = bin2hex(random_bytes(32));
    $_SESSION['download_file'] = $filename;
    header('Location: download.php?token=' . urlencode($_SESSION['download_token']), true, 303);
    exit;
} catch (RuntimeException $error) {
    app_log($error);
    flash('error', $error->getMessage());
} catch (Throwable $error) {
    app_log($error);
    flash('error', 'The proposal could not be generated. Please contact the administrator.');
} finally {
    if ($uploadPath !== null && is_file($uploadPath)) {
        @unlink($uploadPath);
    }
}

header('Location: index.php', true, 303);
exit;
