<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $emailConfig;
    private array $smtpConfig;

    public function __construct(array $emailConfig, array $smtpConfig = [])
    {
        $this->emailConfig = $emailConfig;
        $this->smtpConfig = $smtpConfig;
    }

    /**
     * Send form submission notification email
     */
    public function send(array $formData, array $overrides = []): bool
    {
        if (!($this->emailConfig['enabled'] ?? true)) {
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            $this->configureSMTP($mail);
            $this->setRecipients($mail, $overrides);
            $this->setContent($mail, $formData, $overrides);

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private function configureSMTP(PHPMailer $mail): void
    {
        if (!empty($this->smtpConfig['host'])) {
            $mail->isSMTP();
            $mail->Host = $this->smtpConfig['host'];
            $mail->Port = $this->smtpConfig['port'] ?? 587;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpConfig['username'] ?? '';
            $mail->Password = $this->smtpConfig['password'] ?? '';

            if (!empty($this->smtpConfig['encryption'])) {
                $mail->SMTPSecure = $this->smtpConfig['encryption'];
            }
        }

        $mail->CharSet = 'UTF-8';
    }

    private function setRecipients(PHPMailer $mail, array $overrides): void
    {
        $mail->setFrom(
            $this->emailConfig['from_email'] ?? 'noreply@localhost',
            $this->emailConfig['from_name'] ?? 'Form Handler'
        );

        $toRecipients = $this->parseEmails($overrides['tomail'] ?? $this->emailConfig['to'] ?? []);
        if (empty($toRecipients)) {
            throw new Exception('No valid email recipients configured');
        }
        foreach ($toRecipients as $to) {
            $mail->addAddress($to);
        }

        $ccRecipients = $this->parseEmails($overrides['cc'] ?? $this->emailConfig['cc'] ?? []);
        foreach ($ccRecipients as $cc) {
            $mail->addCC($cc);
        }

        $bccRecipients = $this->parseEmails($overrides['bcc'] ?? $this->emailConfig['bcc'] ?? []);
        foreach ($bccRecipients as $bcc) {
            $mail->addBCC($bcc);
        }
    }

    private function setContent(PHPMailer $mail, array $formData, array $overrides): void
    {
        $subjectPrefix = $this->emailConfig['subject_prefix'] ?? '[Form Submission]';
        $formName = $overrides['formname'] ?? 'Form';
        $subject = $overrides['subject'] ?? "{$subjectPrefix} {$formName}";

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $this->buildHtmlBody($formData);
        $mail->AltBody = $this->buildTextBody($formData);
    }

    private function parseEmails(mixed $emails): array
    {
        if (is_string($emails)) {
            $emails = array_map('trim', explode(',', $emails));
        }

        return array_filter((array) $emails, function ($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
        });
    }

    private function buildHtmlBody(array $formData): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>New Form Submission</h2>';
        $html .= '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">';

        foreach ($formData as $key => $value) {
            $key = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
            $value = is_array($value) ? json_encode($value) : (string) $value;
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $value = nl2br($value);

            $html .= "<tr><td style=\"background:#f5f5f5;\"><strong>{$key}</strong></td><td>{$value}</td></tr>";
        }

        $html .= '</table>';
        $html .= '<p style="color:#666;font-size:12px;">Submitted at: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '</body></html>';

        return $html;
    }

    private function buildTextBody(array $formData): string
    {
        $text = "New Form Submission\n";
        $text .= str_repeat('=', 40) . "\n\n";

        foreach ($formData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : (string) $value;
            $text .= "{$key}: {$value}\n";
        }

        $text .= "\nSubmitted at: " . date('Y-m-d H:i:s');

        return $text;
    }
}
