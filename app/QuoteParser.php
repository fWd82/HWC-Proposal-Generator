<?php

declare(strict_types=1);

namespace ProposalGenerator;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class QuoteParser
{
    private const ALIASES = [
        'service_name' => ['service name', 'product name', 'cloud service', 'description'],
        'service_label' => ['service'],
        'sku' => ['sku', 'product sku'],
        'region' => ['region'],
        'billing_mode' => ['billing mode', 'billing'],
        'specification' => ['specification', 'specifications', 'spec'],
        'quantity' => ['quantity', 'qty'],
        'purchase_amount' => ['purchase amount'],
        'unit_price' => ['discounted unit price usd', 'unit price', 'price'],
        'discounted_price' => ['discounted price usd', 'monthly total', 'monthly amount'],
        'annual_total' => ['annual total', 'annual amount', 'yearly total'],
        'currency' => ['currency'],
        'remarks' => ['remarks', 'notes'],
        'discount' => ['discount amount usd', 'discount'],
    ];

    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        try {
            [$sheet, $headerRow, $headers] = $this->findQuoteSheet($spreadsheet->getAllSheets());
            $rows = $sheet->toArray(null, true, true, true);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        $grouped = [];
        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= $headerRow) {
                continue;
            }
            $name = trim($this->value($row, $headers, 'service_name'));
            $label = trim($this->value($row, $headers, 'service_label'));
            if ($name === '' || strcasecmp($label, 'Total Price') === 0) {
                continue;
            }

            $price = $this->number($this->value($row, $headers, 'discounted_price'));
            $quantity = $this->number($this->value($row, $headers, 'quantity'));
            if ($quantity <= 0) {
                $quantity = $this->quantityFromPurchaseAmount($this->value($row, $headers, 'purchase_amount'));
            }
            $quantity = $quantity > 0 ? $quantity : 1.0;
            $billing = $this->value($row, $headers, 'billing_mode');
            $annual = isset($headers['annual_total'])
                ? $this->number($this->value($row, $headers, 'annual_total'))
                : ($this->isMonthly($billing) ? $price * 12 : $price);

            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'service_name' => $name,
                    'sku' => $this->value($row, $headers, 'sku'),
                    'region' => $this->value($row, $headers, 'region'),
                    'billing_mode' => $billing,
                    'specifications' => [],
                    'quantity' => 0.0,
                    'unit_price' => 0.0,
                    'monthly_total' => 0.0,
                    'annual_total' => 0.0,
                    'discount' => 0.0,
                    'currency' => $this->value($row, $headers, 'currency') ?: 'USD',
                    'remarks' => $this->value($row, $headers, 'remarks'),
                ];
            }

            $spec = trim($this->value($row, $headers, 'specification'));
            if ($spec !== '') {
                $grouped[$name]['specifications'][$spec] = true;
            }
            $grouped[$name]['quantity'] += $quantity;
            $grouped[$name]['monthly_total'] += $price;
            $grouped[$name]['annual_total'] += $annual;
            $grouped[$name]['discount'] += $this->number($this->value($row, $headers, 'discount'));
        }

        if ($grouped === []) {
            throw new \RuntimeException('The Excel file does not contain any recognizable quote rows.');
        }

        $quoteRows = [];
        foreach ($grouped as $row) {
            $row['unit_price'] = $row['quantity'] > 0 ? $row['monthly_total'] / $row['quantity'] : $row['monthly_total'];
            $row['specification'] = implode("\n", array_keys($row['specifications']));
            unset($row['specifications']);
            $quoteRows[] = $row;
        }

        return [
            'rows' => $quoteRows,
            'unique_services' => array_keys($grouped),
            'totals' => [
                'monthly_total' => array_sum(array_column($quoteRows, 'monthly_total')),
                'annual_total' => array_sum(array_column($quoteRows, 'annual_total')),
            ],
        ];
    }

    private function findQuoteSheet(array $sheets): array
    {
        usort($sheets, static fn ($a, $b): int =>
            (int) str_contains(strtolower($b->getTitle()), 'simplified') <=>
            (int) str_contains(strtolower($a->getTitle()), 'simplified')
        );
        foreach ($sheets as $sheet) {
            foreach (array_slice($sheet->toArray(null, true, true, true), 0, 25, true) as $rowNumber => $row) {
                $headers = $this->headers($row);
                if (isset($headers['service_name'], $headers['discounted_price'])) {
                    return [$sheet, (int) $rowNumber, $headers];
                }
            }
        }
        throw new \RuntimeException('No quote table with Description and Discounted Price (USD) columns was found.');
    }

    private function headers(array $row): array
    {
        $lookup = [];
        foreach (self::ALIASES as $key => $aliases) {
            foreach ($aliases as $alias) {
                $lookup[$this->normalize($alias)] = $key;
            }
        }
        $headers = [];
        foreach ($row as $column => $label) {
            $normalized = $this->normalize((string) $label);
            if (isset($lookup[$normalized]) && !isset($headers[$lookup[$normalized]])) {
                $headers[$lookup[$normalized]] = $column;
            }
        }
        if (!isset($headers['service_name']) && isset($headers['service_label'])) {
            $headers['service_name'] = $headers['service_label'];
            unset($headers['service_label']);
        }
        return $headers;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim($value))) ?? '');
    }

    private function value(array $row, array $headers, string $key): string
    {
        return isset($headers[$key]) ? trim((string) ($row[$headers[$key]] ?? '')) : '';
    }

    private function number(string $value): float
    {
        $negative = str_starts_with(trim($value), '(') && str_ends_with(trim($value), ')');
        $cleaned = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $value)) ?? '';
        $number = is_numeric($cleaned) ? (float) $cleaned : 0.0;
        return $negative ? -abs($number) : $number;
    }

    private function quantityFromPurchaseAmount(string $value): float
    {
        return preg_match('/,\s*(\d+(?:\.\d+)?)\s+/i', $value, $match) ? (float) $match[1] : 1.0;
    }

    private function isMonthly(string $billing): bool
    {
        return $billing === '' || str_contains(strtolower($billing), 'month');
    }
}
