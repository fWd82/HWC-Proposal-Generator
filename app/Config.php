<?php

declare(strict_types=1);

namespace ProposalGenerator;

final class Config
{
    public const TEMPLATE_PATH = __DIR__ . '/../templates/huawei-commercial-proposal-template.docx';
    public const SERVICES_JSON_PATH = __DIR__ . '/../data/services.json';
    public const UPLOAD_DIR = __DIR__ . '/../storage/uploads/';
    public const GENERATED_DIR = __DIR__ . '/../storage/generated/';
    public const LOG_DIR = __DIR__ . '/../storage/logs/';
    public const MAX_UPLOAD_SIZE_BYTES = 10 * 1024 * 1024;
    public const ALLOW_PARTIAL_SERVICES = false;

    public static function ensureDirectories(): void
    {
        foreach ([self::UPLOAD_DIR, self::GENERATED_DIR, self::LOG_DIR] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new \RuntimeException('A required storage directory could not be created.');
            }
        }
    }
}
