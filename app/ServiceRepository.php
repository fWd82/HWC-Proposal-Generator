<?php

declare(strict_types=1);

namespace ProposalGenerator;

final class ServiceRepository
{
    public function getServicesForQuote(array $serviceNames): array
    {
        $services = $this->load();
        $found = [];
        foreach ($serviceNames as $name) {
            if (isset($services[$name]) && is_array($services[$name])) {
                $found[$name] = $services[$name];
                continue;
            }
            $found[$name] = [
                'code' => '', 'name' => $name, 'short_description' => '',
                'definition' => '', 'details' => [], 'key_benefits' => [],
                'typical_use_cases' => [], 'proposal_positioning' => '',
                'diagram' => '', 'link' => '', 'is_placeholder' => true,
            ];
        }
        return $found;
    }

    private function load(): array
    {
        if (!is_file(Config::SERVICES_JSON_PATH) || !is_readable(Config::SERVICES_JSON_PATH)) {
            throw new \RuntimeException('The service library is not available.');
        }
        try {
            $data = json_decode((string) file_get_contents(Config::SERVICES_JSON_PATH), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new \RuntimeException('The service library contains invalid JSON.', 0, $error);
        }
        if (!is_array($data)) {
            throw new \RuntimeException('The service library has an invalid structure.');
        }
        return $data;
    }
}