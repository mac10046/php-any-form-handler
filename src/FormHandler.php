<?php

declare(strict_types=1);

namespace App;

class FormHandler
{
    private ConfigLoader $configLoader;
    private array $config;
    private ?Database $db = null;
    private ?Mailer $mailer = null;

    private array $specialFields = [
        'configId',
        'tomail',
        'bcc',
        'cc',
        '_formname',
        '_redirect',
        '_honeypot',
        '_subject',
    ];

    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * Process form submission
     */
    public function process(array $postData): array
    {
        try {
            $configId = $postData['configId'] ?? null;
            if (!$configId) {
                return $this->result(false, 'Missing configId');
            }

            $this->config = $this->configLoader->load($configId);

            if (!$this->validateOrigin()) {
                return $this->result(false, 'Origin not allowed');
            }

            if ($this->isBot($postData)) {
                return $this->result(true, 'OK');
            }

            $specialData = $this->extractSpecialFields($postData);
            $formData = $this->sanitizeFormData($postData);

            if (empty($formData)) {
                return $this->result(false, 'No form data received');
            }

            $submissionId = $this->saveToDatabase($formData, $specialData['formname'] ?? 'default');

            $this->sendEmail($formData, $specialData);

            $redirect = $specialData['redirect'] ?? null;

            return $this->result(true, 'Form submitted successfully', $redirect, $submissionId);

        } catch (\Exception $e) {
            error_log('FormHandler Error: ' . $e->getMessage());
            return $this->result(false, 'An error occurred. Please try again.');
        }
    }

    private function validateOrigin(): bool
    {
        $allowedOrigins = $this->config['allowed_origins'] ?? ['*'];

        if (in_array('*', $allowedOrigins, true)) {
            return true;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            return true;
        }

        foreach ($allowedOrigins as $allowed) {
            if ($referer && str_starts_with($referer, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function isBot(array $postData): bool
    {
        $honeypot = $postData['_honeypot'] ?? '';
        return !empty($honeypot);
    }

    private function extractSpecialFields(array &$postData): array
    {
        $special = [];

        foreach ($this->specialFields as $field) {
            $key = $field;
            $cleanKey = ltrim($field, '_');

            if (isset($postData[$field])) {
                $special[$cleanKey] = $postData[$field];
                unset($postData[$field]);
            }
        }

        return $special;
    }

    private function sanitizeFormData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }

            $key = $this->sanitizeKey($key);

            if (is_array($value)) {
                $sanitized[$key] = array_map([$this, 'sanitizeValue'], $value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }

        return $sanitized;
    }

    private function sanitizeKey(string $key): string
    {
        $key = preg_replace('/[^\w\-\s]/', '', $key);
        return substr($key, 0, 100);
    }

    private function sanitizeValue(mixed $value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        $value = trim($value);

        return $value;
    }

    private function saveToDatabase(array $formData, string $formName): int
    {
        $db = $this->getDatabase();
        return $db->saveSubmission($formData, $formName);
    }

    private function sendEmail(array $formData, array $specialData): bool
    {
        $mailer = $this->getMailer();
        return $mailer->send($formData, $specialData);
    }

    private function getDatabase(): Database
    {
        if ($this->db === null) {
            $this->db = new Database($this->config['database']);
        }
        return $this->db;
    }

    private function getMailer(): Mailer
    {
        if ($this->mailer === null) {
            $this->mailer = new Mailer(
                $this->config['email'] ?? [],
                $this->config['smtp'] ?? []
            );
        }
        return $this->mailer;
    }

    private function result(bool $success, string $message, ?string $redirect = null, ?int $submissionId = null): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'redirect' => $redirect,
            'submission_id' => $submissionId,
        ];
    }

    /**
     * Get allowed origins from loaded config (for CORS)
     */
    public function getAllowedOrigins(string $configId): array
    {
        try {
            $config = $this->configLoader->load($configId);
            return $config['allowed_origins'] ?? ['*'];
        } catch (\Exception $e) {
            return ['*'];
        }
    }
}
