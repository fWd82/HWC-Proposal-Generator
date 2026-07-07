<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$flash = take_flash();
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

function old(array $values, string $key, string $default = ''): string
{
    return e((string) ($values[$key] ?? $default));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Generate a Huawei Cloud commercial proposal from an Excel quote.">
    <title>Huawei Cloud Proposal Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand"><span class="brand-mark">H</span><span>Huawei Cloud<br><small>Proposal Studio</small></span></div>
    <span class="secure-label">Secure document generation</span>
</header>
<main>
    <section class="hero">
        <p class="eyebrow">COMMERCIAL PROPOSALS</p>
        <h1>Turn a cloud quote into a<br><span>client-ready proposal.</span></h1>
        <p>Upload your Huawei Cloud quote, add the customer context, and generate a professionally structured Word document in moments.</p>
        <div class="steps">
            <span><b>1</b> Proposal details</span><i></i>
            <span><b>2</b> Upload quote</span><i></i>
            <span><b>3</b> Download Word file</span>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>" role="alert"><?= nl2br(e($flash['message'])) ?></div>
    <?php endif; ?>

    <form method="post" action="generate.php" enctype="multipart/form-data" class="proposal-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

        <section class="card">
            <div class="card-heading"><span>01</span><div><h2>Customer & proposal</h2><p>The essentials shown on the proposal cover.</p></div></div>
            <div class="grid">
                <label class="wide">Customer name <em>*</em><input name="customer_name" value="<?= old($old, 'customer_name') ?>" placeholder="e.g. Acme Corporation" required maxlength="150"></label>
                <label>Proposal title <em>*</em><input name="proposal_title" value="<?= old($old, 'proposal_title') ?>" placeholder="Huawei Cloud Commercial Proposal" required maxlength="200"></label>
                <label>Proposal date <em>*</em><input type="date" name="proposal_date" value="<?= old($old, 'proposal_date', date('Y-m-d')) ?>" required></label>
                <label>Prepared by <em>*</em><input name="prepared_by" value="<?= old($old, 'prepared_by') ?>" placeholder="Name or company" required maxlength="150"></label>
                <label>Customer industry<input name="customer_industry" value="<?= old($old, 'customer_industry') ?>" placeholder="e.g. Financial Services" maxlength="150"></label>
            </div>
        </section>

        <section class="card">
            <div class="card-heading"><span>02</span><div><h2>Commercial details</h2><p>Define the terms that accompany the quote.</p></div></div>
            <div class="grid three">
                <label>Currency<input name="currency" value="<?= old($old, 'currency', 'USD') ?>" maxlength="10"></label>
                <label>Validity period<input name="validity_period" value="<?= old($old, 'validity_period', '30 days') ?>" maxlength="100"></label>
                <label>Payment terms<input name="payment_terms" value="<?= old($old, 'payment_terms') ?>" placeholder="e.g. Net 30 days" maxlength="200"></label>
            </div>
        </section>

        <section class="card">
            <div class="card-heading"><span>03</span><div><h2>Proposal narrative</h2><p>Add context and boundaries. Leave the summary empty to generate a factual one automatically.</p></div></div>
            <div class="grid">
                <label class="wide">Executive summary <small>Optional</small><textarea name="executive_summary_optional" placeholder="Leave blank for an automatically generated summary..."><?= old($old, 'executive_summary_optional') ?></textarea></label>
                <label>Scope of work<textarea name="scope_of_work" placeholder="Services and activities included..."><?= old($old, 'scope_of_work') ?></textarea></label>
                <label>Out of scope<textarea name="out_of_scope" placeholder="Items explicitly excluded..."><?= old($old, 'out_of_scope') ?></textarea></label>
                <label>Assumptions<textarea name="assumptions" placeholder="Key assumptions and dependencies..."><?= old($old, 'assumptions') ?></textarea></label>
                <label>Additional notes<textarea name="additional_notes" placeholder="Any other relevant information..."><?= old($old, 'additional_notes') ?></textarea></label>
            </div>
        </section>

        <section class="card upload-card">
            <div class="card-heading"><span>04</span><div><h2>Huawei Cloud quote</h2><p>Upload the commercial source data used in the proposal.</p></div></div>
            <label class="dropzone">
                <span class="upload-icon">↑</span>
                <strong>Choose an Excel quote</strong>
                <span>or drag and drop it here</span>
                <small>.XLSX or .XLS · maximum 10 MB</small>
                <input type="file" name="quote_file" accept=".xlsx,.xls" required>
            </label>
            <p class="file-name" aria-live="polite"></p>
        </section>

        <div class="submit-row">
            <p><b>Your files stay private.</b><br>Uploads are stored outside public access and are never executed.</p>
            <button type="submit">Generate Word proposal <span>→</span></button>
        </div>
    </form>
</main>
<footer>Huawei Cloud Proposal Studio <span>•</span> With ❤️ by Fawad Iqbal f00928374</footer>
<script>
const input = document.querySelector('input[type=file]');
const zone = document.querySelector('.dropzone');
const label = document.querySelector('.file-name');
input.addEventListener('change', () => label.textContent = input.files[0] ? `Selected: ${input.files[0].name}` : '');
['dragenter','dragover'].forEach(event => zone.addEventListener(event, e => { e.preventDefault(); zone.classList.add('dragging'); }));
['dragleave','drop'].forEach(event => zone.addEventListener(event, e => { e.preventDefault(); zone.classList.remove('dragging'); }));
zone.addEventListener('drop', e => { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); } });
</script>
</body>
</html>
