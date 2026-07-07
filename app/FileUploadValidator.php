<?php

declare(strict_types=1);

namespace ProposalGenerator;

final class FileUploadValidator
{
    private const EXTENSIONS = ['xlsx', 'xls'];
    private const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/zip',
        'application/octet-stream',
    ];

    public function validateAndStore(array $file): string
    {
        if (!isset($file['error'], $file['size'], $file['tmp_name'], $file['name'])
            || is_array($file['error'])) {
            throw new \RuntimeException('Please upload a valid Excel file.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $message = match ((int) $file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded quote is too large.',
                UPLOAD_ERR_NO_FILE => 'Please select an Excel quote file.',
                default => 'The Excel quote could not be uploaded.',
            };
            throw new \RuntimeException($message);
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > Config::MAX_UPLOAD_SIZE_BYTES) {
            throw new \RuntimeException('The Excel quote is empty or exceeds the 10 MB limit.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new \RuntimeException('Please upload an .xlsx or .xls quote file.');
        }

        if (!is_uploaded_file((string) $file['tmp_name'])) {
            throw new \RuntimeException('The uploaded quote could not be verified.');
        }

        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
            if ($mime !== false && !in_array($mime, self::MIME_TYPES, true)) {
                throw new \RuntimeException('The uploaded file is not a recognized Excel document.');
            }
        }

        Config::ensureDirectories();
        $destination = Config::UPLOAD_DIR . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new \RuntimeException('The uploaded quote could not be stored.');
        }

        return $destination;
    }
}
