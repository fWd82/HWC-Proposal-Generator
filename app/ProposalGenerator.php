<?php

declare(strict_types=1);

namespace ProposalGenerator;

use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

final class ProposalGenerator
{
    public function generate(array $data): string
    {
        if (!is_file(Config::TEMPLATE_PATH)) {
            throw new \RuntimeException('The Word template could not be found.');
        }
        Config::ensureDirectories();
        $template = new TemplateProcessor(Config::TEMPLATE_PATH);
        foreach (['customer_name','proposal_title','proposal_date','prepared_by','customer_industry','currency','validity_period','payment_terms','executive_summary','scope_of_work','out_of_scope','assumptions','additional_notes','quote_total_monthly','quote_total_annual'] as $field) {
            $template->setValue($field, $this->safe((string) ($data[$field] ?? '')));
        }
        $this->populateQuoteTable($template, $data['quote_rows'], $data['currency']);
        $this->populateServiceSections($template, $data['services']);
        $filename = $this->makeOutputFilename($data['customer_name']);
        $outputPath = Config::GENERATED_DIR . $filename;
        $template->saveAs($outputPath);
        $this->repairServiceHyperlinks($outputPath, $data['services']);
        return $filename;
    }

    private function populateQuoteTable(TemplateProcessor $template, array $rows, string $fallbackCurrency): void
    {
        $template->cloneRow('quote_service_name', count($rows));
        foreach (array_values($rows) as $index => $row) {
            $i = $index + 1;
            $currency = trim((string) $row['currency']) ?: $fallbackCurrency;
            $values = [
                'quote_service_name'=>$row['service_name'], 'quote_sku'=>$row['sku'],
                'quote_region'=>$row['region'], 'quote_billing_mode'=>$row['billing_mode'],
                'quote_specification'=>$row['specification'], 'quote_quantity'=>$this->number($row['quantity']),
                'quote_unit_price'=>$currency.' '.number_format((float)$row['unit_price'],2),
                'quote_monthly_total'=>$currency.' '.number_format((float)$row['monthly_total'],2),
                'quote_discounted_price'=>$currency.' '.number_format((float)$row['monthly_total'],2),
                'quote_annual_total'=>$currency.' '.number_format((float)$row['annual_total'],2),
                'quote_currency'=>$currency, 'quote_remarks'=>$row['remarks'],
            ];
            foreach ($values as $key => $value) {
                $template->setValue($key.'#'.$i, $this->safe((string)$value));
            }
        }
    }

    private function populateServiceSections(TemplateProcessor $template, array $services): void
    {
        if ($services === []) {
            $template->deleteBlock('services_block');
            return;
        }
        $template->cloneBlock('services_block', count($services), true, true);
        foreach (array_values($services) as $index => $service) {
            $i = $index + 1;
            $name = (string)($service['name'] ?? '');
            $code = (string)($service['code'] ?? '');
            $placeholder = (bool)($service['is_placeholder'] ?? false);
            $values = [
                'service_heading'=>trim($name.($code !== '' ? " ({$code})" : '')),
                'service_short_description'=>$service['short_description'] ?? '',
                'service_definition_label'=>$placeholder ? '' : 'Definition',
                'service_definition'=>$service['definition'] ?? '',
                'service_details_label'=>$placeholder ? '' : 'Details',
                'service_details'=>$this->bullets($service['details'] ?? []),
                'service_key_benefits_label'=>$placeholder ? '' : 'Key Benefits',
                'service_key_benefits'=>$this->bullets($service['key_benefits'] ?? []),
                'service_typical_use_cases_label'=>$placeholder ? '' : 'Typical Use Cases',
                'service_typical_use_cases'=>$this->bullets($service['typical_use_cases'] ?? []),
                'service_proposal_positioning_label'=>$placeholder ? '' : 'Proposal Positioning',
                'service_proposal_positioning'=>$service['proposal_positioning'] ?? '',
            ];
            foreach ($values as $key => $value) {
                $template->setValue($key.'#'.$i, $this->safe((string)$value));
            }
            $diagram = $this->diagramPath((string)($service['diagram'] ?? ''));
            if ($diagram !== null) {
                $template->setImageValue('service_diagram#'.$i, ['path'=>$diagram,'width'=>560,'ratio'=>true]);
            } else {
                $template->setValue('service_diagram#'.$i, '');
            }
            $link = filter_var(trim((string)($service['link'] ?? '')), FILTER_VALIDATE_URL);
            if ($link !== false && str_starts_with($link, 'https://www.huaweicloud.com/')) {
                $text = new TextRun();
                $text->addText('For more information, visit the ');
                $text->addLink($link, 'official Huawei Cloud website', ['color'=>'C7002B','underline'=>'single']);
                $text->addText('.');
                $template->setComplexValue('service_official_link#'.$i, $text);
            } else {
                $template->setValue('service_official_link#'.$i, '');
            }
        }
    }

    private function repairServiceHyperlinks(string $docxPath, array $services): void
    {
        $links = [];
        foreach ($services as $service) {
            $link = filter_var(trim((string)($service['link'] ?? '')), FILTER_VALIDATE_URL);
            if ($link !== false && str_starts_with($link, 'https://www.huaweicloud.com/')) $links[] = $link;
        }
        if ($links === []) return;
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) throw new \RuntimeException('The generated Word file could not be finalized.');
        try {
            $documentXml = $zip->getFromName('word/document.xml');
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            if ($documentXml === false || $relsXml === false) throw new \RuntimeException('The generated Word file has an invalid structure.');
            $document = new \DOMDocument();
            $relationships = new \DOMDocument();
            $document->loadXML($documentXml);
            $relationships->loadXML($relsXml);
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $linkIndex = 0;
            foreach ($xpath->query('//w:hyperlink[@r:id]') ?: [] as $hyperlink) {
                if ($linkIndex >= count($links) || !str_contains($hyperlink->textContent, 'official Huawei Cloud website')) continue;
                $id = 'rIdServiceLink'.($linkIndex + 1);
                $hyperlink->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:id', $id);
                $relationship = $relationships->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
                $relationship->setAttribute('Id', $id);
                $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink');
                $relationship->setAttribute('Target', $links[$linkIndex]);
                $relationship->setAttribute('TargetMode', 'External');
                $relationships->documentElement->appendChild($relationship);
                $linkIndex++;
            }
            $zip->addFromString('word/document.xml', (string)$document->saveXML());
            $zip->addFromString('word/_rels/document.xml.rels', (string)$relationships->saveXML());
        } finally {
            $zip->close();
        }
    }
    private function diagramPath(string $relative): ?string
    {
        if ($relative === '' || str_contains($relative, '..') || preg_match('/^[A-Za-z]:|^[\/\\\\]/', $relative)) return null;
        $path = dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace(['/','\\'],DIRECTORY_SEPARATOR,$relative);
        return is_file($path) ? $path : null;
    }

    private function bullets(array $items): string
    {
        return implode("\n", array_map(static fn($item): string => '- '.trim((string)$item), $items));
    }

    private function safe(string $value): string
    {
        return htmlspecialchars(str_replace(["\r\n","\r"],"\n",$value), ENT_QUOTES|ENT_XML1|ENT_SUBSTITUTE, 'UTF-8');
    }

    private function number(float|int|string $value): string
    {
        $number=(float)$value;
        return floor($number)===$number ? (string)(int)$number : rtrim(rtrim(number_format($number,2,'.',''),'0'),'.');
    }

    private function makeOutputFilename(string $customerName): string
    {
        $slug=trim(preg_replace('/[^A-Za-z0-9]+/','-',trim($customerName)) ?? '', '-') ?: 'Customer';
        return sprintf('Huawei-Cloud-Commercial-Proposal-%s-%s-%s.docx',substr($slug,0,60),date('Ymd'),bin2hex(random_bytes(3)));
    }
}