<?php

declare(strict_types=1);

namespace ProposalGenerator;

final class ProposalDataBuilder
{
    public function build(array $form, array $quote, array $services): array
    {
        $currency = trim((string) ($form['currency'] ?? 'USD')) ?: 'USD';
        $summary = trim((string) ($form['executive_summary_optional'] ?? ''));
        if ($summary === '') {
            $summary = sprintf(
                'This commercial proposal has been prepared for %s to outline the Huawei Cloud services, scope, and commercial details included in the proposed solution. The proposal includes %s, based on the uploaded commercial quote and the requirements provided.',
                trim((string) $form['customer_name']),
                $this->humanList($quote['unique_services'])
            );
        }

        return [
            'customer_name' => trim((string) $form['customer_name']),
            'proposal_title' => trim((string) $form['proposal_title']),
            'proposal_date' => trim((string) $form['proposal_date']),
            'prepared_by' => trim((string) $form['prepared_by']),
            'customer_industry' => trim((string) ($form['customer_industry'] ?? '')),
            'currency' => $currency,
            'validity_period' => trim((string) ($form['validity_period'] ?? '')),
            'payment_terms' => trim((string) ($form['payment_terms'] ?? '')),
            'executive_summary' => $summary,
            'scope_of_work' => trim((string) ($form['scope_of_work'] ?? '')),
            'out_of_scope' => trim((string) ($form['out_of_scope'] ?? '')),
            'assumptions' => trim((string) ($form['assumptions'] ?? '')),
            'additional_notes' => trim((string) ($form['additional_notes'] ?? '')),
            'quote_total_monthly' => $currency . ' ' . number_format((float) $quote['totals']['monthly_total'], 2),
            'quote_total_annual' => $currency . ' ' . number_format((float) $quote['totals']['annual_total'], 2),
            'quote_rows' => $quote['rows'],
            'services' => $services,
        ];
    }

    private function humanList(array $items): string
    {
        if (count($items) < 2) {
            return $items[0] ?? 'the selected Huawei Cloud services';
        }
        $last = array_pop($items);
        return implode(', ', $items) . ' and ' . $last;
    }
}
