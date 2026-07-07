# Huawei Cloud Commercial Proposal Generator

A PHP 8.1+ application that combines customer inputs, an Excel quote, an approved service catalog, and a branded Word template to produce a downloadable `.docx` commercial proposal.

Live Demo: 
https://fawadiqbal.com/tools/HWC-Proposal-Generator/public/


## Requirements

- PHP 8.1 or newer with `dom`, `fileinfo`, `gd`, `mbstring`, `xml`, `xmlreader`, `xmlwriter`, `zip`, and `zlib`
- Composer 2
- Apache/cPanel hosting (or PHP's development server for local testing)

## Install

```bash
composer install --no-dev --optimize-autoloader
```

The web root should point to `public/`. If the cPanel document root cannot be changed, upload the project under a private directory and configure a subdomain or rewrite to `public/`.

Make `storage/uploads`, `storage/generated`, and `storage/logs` writable by PHP (typically `750` or `770`, depending on the host). The included `storage/.htaccess` blocks all direct web access.

## Add the Word template

Place the supplied template at:

```text
templates/huawei-commercial-proposal-template.docx
```

Use these fixed placeholders:

```text
${customer_name} ${proposal_title} ${proposal_date} ${prepared_by}
${customer_industry} ${currency} ${validity_period} ${payment_terms}
${executive_summary} ${scope_of_work} ${out_of_scope} ${assumptions}
${additional_notes} ${quote_total_monthly} ${quote_total_annual}
```

Create a Word table with a single sample data row. Its anchor placeholder must be `${quote_service_name}`; optional cells may contain:

```text
${quote_sku} ${quote_region} ${quote_billing_mode} ${quote_specification}
${quote_quantity} ${quote_unit_price} ${quote_monthly_total}
${quote_annual_total} ${quote_currency} ${quote_remarks}
```

Create the repeatable service section as a PHPWord block:

```text
${services_block}
${service_heading}
${service_short_description}
${service_definition}
${service_details}
${service_key_benefits}
${service_typical_use_cases}
${service_proposal_positioning}
${service_diagram}
${/services_block}
```

Keep each `${placeholder}` as one continuous run in Word; applying mixed formatting inside a placeholder can split it and prevent replacement. The template owns all layout, styles, branding, headers, footers, and static terms.

Add a native Microsoft Word table-of-contents field to the template. After generation, right-click the TOC in Word and choose **Update Field → Update entire table**.

## Quote format

The first worksheet and first row are used. Required headers are:

```text
Service Name | Quantity | Unit Price | Monthly Total | Annual Total
```

Optional headers include SKU, Region, Billing Mode, Specification, Description, Discount, Currency, and Remarks. A few reasonable header variants are recognized, but service values must exactly match keys in `data/services.json`.

## Service catalog and diagrams

Edit `data/services.json` to add approved service information. Missing services stop generation; the application never invents content.

Place optional diagram PNG files in `assets/diagrams/` and reference them using a project-relative path such as:

```json
"diagram": "assets/diagrams/elastic-cloud-server.png"
```

Generation continues without an image when the referenced file is absent.

## Local use

```bash
php -S localhost:8080 -t public
```

Open `http://localhost:8080`.

## Production notes

- Set PHP `upload_max_filesize` and `post_max_size` to at least `10M`.
- Keep `display_errors=Off` and enable PHP error logging.
- Serve the site over HTTPS.
- Periodically delete old files from `storage/generated/`; uploads are removed after each request.
- Back up the template and service catalog before updating them.
- The application has no API keys, Word automation, LibreOffice, Node.js, Python, worker, login, PDF, or LLM dependency.

The download link is session-bound, single-use, constrained to generated `.docx` filenames, and does not expose server paths.
