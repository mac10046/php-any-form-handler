<?php

declare(strict_types=1);

namespace App;

class ConfigLoader
{
    private string $configDir;
    private array $cache = [];

    public function __construct(string $configDir)
    {
        $this->configDir = rtrim($configDir, '/\\');
    }

    /**
     * Load configuration by configId
     */
    public function load(string $configId): array
    {
        if (isset($this->cache[$configId])) {
            return $this->cache[$configId];
        }

        $configId = $this->sanitizeConfigId($configId);
        $configFile = $this->configDir . DIRECTORY_SEPARATOR . $configId . '.json';

        if (!file_exists($configFile)) {
            throw new \RuntimeException("Configuration not found: {$configId}");
        }

        $content = file_get_contents($configFile);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in config: {$configId}");
        }

        $this->cache[$configId] = $config;
        return $config;
    }

    /**
     * Load configuration by tenant_id (for dashboard login)
     */
    public function loadByTenantId(string $tenantId): ?array
    {
        $tenantId = trim($tenantId);
        $files = glob($this->configDir . DIRECTORY_SEPARATOR . '*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $config = json_decode($content, true);

            if ($config && isset($config['tenant_id']) && $config['tenant_id'] === $tenantId) {
                $configId = basename($file, '.json');
                $this->cache[$configId] = $config;
                return $config;
            }
        }

        return null;
    }

    /**
     * Get value from config using dot notation
     */
    public function get(array $config, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Sanitize configId to prevent directory traversal
     */
    private function sanitizeConfigId(string $configId): string
    {
        $configId = preg_replace('/[^a-zA-Z0-9_-]/', '', $configId);
        return $configId ?: 'default';
    }
}
