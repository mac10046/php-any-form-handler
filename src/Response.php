<?php

declare(strict_types=1);

namespace App;

class Response
{
    /**
     * Handle CORS headers
     */
    public static function handleCors(array $allowedOrigins = ['*']): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin && in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            exit;
        }
    }

    /**
     * Send success response
     */
    public static function success(string $message, ?string $redirect = null): void
    {
        if ($redirect) {
            header("Location: {$redirect}");
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send error response
     */
    public static function error(string $message, int $code = 400, ?string $redirect = null): void
    {
        if ($redirect) {
            $separator = str_contains($redirect, '?') ? '&' : '?';
            header("Location: {$redirect}{$separator}error=" . urlencode($message));
            exit;
        }

        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send JSON response
     */
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
