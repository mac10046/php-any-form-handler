<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['name']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Save form submission to database
     */
    public function saveSubmission(array $formData, string $formName = 'default'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO submissions (form_name, form_data, sender_ip, user_agent, referer_url)
             VALUES (:form_name, :form_data, :ip, :ua, :referer)'
        );

        $stmt->execute([
            'form_name' => $formName,
            'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get submissions with pagination
     */
    public function getSubmissions(int $limit = 50, int $offset = 0, ?string $formName = null): array
    {
        if ($formName) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM submissions WHERE form_name = :form_name
                 ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue('form_name', $formName, PDO::PARAM_STR);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM submissions ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
            );
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $submissions = $stmt->fetchAll();

        foreach ($submissions as &$row) {
            if (isset($row['form_data'])) {
                $row['form_data'] = json_decode($row['form_data'], true);
            }
        }

        return $submissions;
    }

    /**
     * Get single submission by ID
     */
    public function getSubmission(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM submissions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row && isset($row['form_data'])) {
            $row['form_data'] = json_decode($row['form_data'], true);
        }

        return $row ?: null;
    }

    /**
     * Count total submissions
     */
    public function countSubmissions(?string $formName = null): int
    {
        if ($formName) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM submissions WHERE form_name = :form_name');
            $stmt->execute(['form_name' => $formName]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM submissions');
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get distinct form names
     */
    public function getFormNames(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT form_name FROM submissions ORDER BY form_name');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
