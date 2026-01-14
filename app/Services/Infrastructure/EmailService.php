<?php

namespace App\Services\Infrastructure;

use App\Support\ServiceResult;

class EmailService
{
    /**
     * Send email notification
     */
    public function send(string $to, string $subject, string $content, array $data = []): ServiceResult
    {
        try {
            // Basic email implementation
            // In a real application, you would integrate with your email service
            \Log::info('Email notification sent', [
                'to' => $to,
                'subject' => $subject,
                'content' => $content,
                'data' => $data,
            ]);

            return ServiceResult::success([], 'Email sent successfully');
        } catch (\Exception $e) {
            return ServiceResult::error('Failed to send email: '.$e->getMessage());
        }
    }
}
