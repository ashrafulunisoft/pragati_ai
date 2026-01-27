<?php

namespace App\Helpers;

use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationHelper
{
    /**
     * Send email notification
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $view Email view template
     * @param array $data Data to pass to view
     * @return bool
     */
    public static function sendEmail(string $to, string $subject, string $view, array $data = []): bool
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Email Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification
     *
     * @param string $to Recipient phone number
     * @param string $message SMS content
     * @return array
     */
    public static function sendSms(string $to, string $message): array
    {
        $smsService = new SmsNotificationService();
        return $smsService->send($to, $message);
    }

    /**
     * Send both email and SMS
     *
     * @param string $email Recipient email
     * @param string $phone Recipient phone
     * @param string $subject Email subject
     * @param string $view Email view template
     * @param string $smsMessage SMS content
     * @param array $data Data to pass to email view
     * @return array
     */
    public static function sendBoth(
        string $email,
        string $phone,
        string $subject,
        string $view,
        string $smsMessage,
        array $data = []
    ): array {
        $result = [
            'email' => false,
            'sms' => false,
            'messages' => []
        ];

        // Send email
        if ($email) {
            $emailSent = self::sendEmail($email, $subject, $view, $data);
            $result['email'] = $emailSent;
            $result['messages'][] = $emailSent ? 'Email sent successfully' : 'Email failed';
        }

        // Send SMS
        if ($phone) {
            $smsResult = self::sendSms($phone, $smsMessage);
            $result['sms'] = $smsResult['success'];
            $result['messages'][] = $smsResult['message'];
        }

        return $result;
    }

    /**
     * Send Laravel notification to notifiable entity
     *
     * @param mixed $notifiable User, Visitor, or any Notifiable model
     * @param mixed $notification Notification instance
     * @return void
     */
    public static function notify($notifiable, $notification): void
    {
        $notifiable->notify($notification);
    }

    /**
     * Quick send visitor notification (email + SMS)
     *
     * @param mixed $visitor Visitor model
     * @param mixed $visit Visit model
     * @return void
     */
    public static function notifyVisitor($visitor, $visit): void
    {
        $visitor->notify(new \App\Notifications\VisitorRegistered($visitor, $visit));
    }

    /**
     * Send welcome email to new user
     *
     * @param mixed $user User model
     * @param string $password Plain text password (for new users)
     * @return bool
     */
    public static function sendWelcomeEmail($user, string $password = ''): bool
    {
        return self::sendEmail(
            $user->email,
            'Welcome to UCB Bank Visitor Management System',
            'emails.welcome',
            [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $password,
            ]
        );
    }

    /**
     * Send password reset email
     *
     * @param string $to Recipient email
     * @param string $token Reset token
     * @return bool
     */
    public static function sendPasswordReset(string $to, string $token): bool
    {
        return self::sendEmail(
            $to,
            'Reset Password - UCB Bank',
            'emails.password-reset',
            [
                'token' => $token,
                'email' => $to,
            ]
        );
    }

    /**
     * Send appointment reminder
     *
     * @param string $email Recipient email
     * @param string $phone Recipient phone
     * @param string $visitorName Visitor name
     * @param string $appointmentDate Appointment date
     * @return array
     */
    public static function sendAppointmentReminder(
        string $email,
        string $phone,
        string $visitorName,
        string $appointmentDate
    ): array {
        $subject = '📅 Appointment Reminder - UCB Bank';
        $view = 'emails.appointment-reminder';
        $data = ['name' => $visitorName, 'date' => $appointmentDate];
        $smsMessage = "Reminder: Your visit to UCB Bank is scheduled for {$appointmentDate}. Please arrive 10 mins early.";

        return self::sendBoth($email, $phone, $subject, $view, $smsMessage, $data);
    }

    /**
     * Send visit status update
     *
     * @param string $email Recipient email
     * @param string $phone Recipient phone
     * @param string $visitorName Visitor name
     * @param string $status New status
     * @return array
     */
    public static function sendStatusUpdate(
        string $email,
        string $phone,
        string $visitorName,
        string $status
    ): array {
        $subject = '📊 Visit Status Update - UCB Bank';
        $view = 'emails.status-update';
        $data = ['name' => $visitorName, 'status' => $status];
        $smsMessage = "UCB Bank: Your visit status has been updated to: {$status}";

        return self::sendBoth($email, $phone, $subject, $view, $smsMessage, $data);
    }
}
