# Email Notification Service Guide

## Overview

The `EmailNotificationService` is a reusable service class for sending various types of email notifications throughout the VMS application. It provides a centralized way to manage email communications with built-in logging and error handling.

## Features

- **Reusable Methods**: Pre-built methods for common email scenarios
- **Built-in Logging**: Automatic logging of all email operations
- **Error Handling**: Graceful error handling with detailed error logging
- **Queue Support**: All email mailables implement `ShouldQueue` for background processing
- **Bulk Email Support**: Send emails to multiple recipients at once

## Installation & Configuration

### Prerequisites

Ensure your `.env` file is properly configured with SMTP settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtppro.zoho.com
MAIL_PORT=465
MAIL_USERNAME=stbl.erp@smartbd.com
MAIL_PASSWORD=vdm8*bfH
MAIL_FROM_ADDRESS=stbl.erp@smartbd.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ENCRYPTION=ssl
```

### Dependencies

The service uses Laravel's built-in Mail facade and Log facade, which are already included in Laravel.

## Service Methods

### 1. Send Visitor Registration Email

Sends a confirmation email when a visitor registers.

```php
$emailService = new EmailNotificationService();
$emailData = [
    'visitor_name' => 'John Doe',
    'visitor_email' => 'john@example.com',
    'visitor_phone' => '+8801234567890',
    'visitor_company' => 'ABC Corp',
    'visit_date' => 'January 25, 2026 - 2:30 PM',
    'visit_type' => 'Business Meeting',
    'purpose' => 'Discuss project requirements',
    'host_name' => 'Admin User',
    'status' => 'approved',
];
$emailSent = $emailService->sendVisitorRegistrationEmail($emailData);
```

**Required Data Fields:**
- `visitor_name`: Name of the visitor
- `visitor_email`: Email address of the visitor
- `visit_date`: Formatted visit date and time
- `status`: Current visit status

**Optional Data Fields:**
- `visitor_phone`: Visitor's phone number
- `visitor_company`: Visitor's company name
- `visit_type`: Type of visit
- `purpose`: Purpose of visit
- `host_name`: Name of the host

### 2. Send Visit Status Email

Sends an email when visit status changes (approved, completed, cancelled, pending).

```php
$emailService = new EmailNotificationService();
$emailData = [
    'visitor_name' => 'John Doe',
    'visitor_email' => 'john@example.com',
    'visitor_company' => 'ABC Corp',
    'visit_date' => 'January 25, 2026 - 2:30 PM',
    'visit_type' => 'Business Meeting',
    'purpose' => 'Discuss project requirements',
    'host_name' => 'Admin User',
    'status' => 'completed',
    'remarks' => 'Visit completed successfully',
];
$emailSent = $emailService->sendVisitStatusEmail($emailData);
```

**Status Values:**
- `approved`: Visit has been approved
- `pending`: Visit is pending review
- `completed`: Visit has been completed
- `cancelled`: Visit has been cancelled

### 3. Send Custom Email

Send a custom email with any view and data.

```php
$emailService = new EmailNotificationService();
$emailSent = $emailService->sendCustomEmail(
    'recipient@example.com',
    'Custom Subject',
    'emails.custom-template',
    [
        'variable1' => 'value1',
        'variable2' => 'value2',
    ]
);
```

**Parameters:**
- `$recipient`: Email address of the recipient
- `$subject`: Email subject line
- `$view`: Blade view path for email content
- `$data`: Array of data to pass to the view

### 4. Send Bulk Email

Send the same email to multiple recipients.

```php
$emailService = new EmailNotificationService();
$recipients = [
    'user1@example.com',
    'user2@example.com',
    'user3@example.com',
];

$result = $emailService->sendBulkEmail(
    $recipients,
    'Bulk Email Subject',
    'emails.bulk-template',
    ['message' => 'This is a bulk email']
);

// Result: ['success' => 3, 'failed' => 0]
```

## Usage in Controllers

### Example 1: Visitor Registration

```php
use App\Services\EmailNotificationService;

public function registerVisitor(Request $request)
{
    // Validate and create visitor...
    
    // Send confirmation email
    $emailService = new EmailNotificationService();
    $emailData = [
        'visitor_name' => $visitor->name,
        'visitor_email' => $visitor->email,
        'visit_date' => \Carbon\Carbon::parse($visit->schedule_time)->format('F j, Y - g:i A'),
        'status' => $visit->status,
        // ... other fields
    ];
    
    $emailService->sendVisitorRegistrationEmail($emailData);
    
    // Return response...
}
```

### Example 2: Update Visit Status

```php
use App\Services\EmailNotificationService;

public function updateVisitStatus(Request $request, $id)
{
    $visit = Visit::findOrFail($id);
    $oldStatus = $visit->status;
    
    // Update visit...
    $visit->update(['status' => $request->status]);
    
    // Send status update email if status changed
    if ($oldStatus !== $visit->status) {
        $emailService = new EmailNotificationService();
        $emailData = [
            'visitor_name' => $visit->visitor->name,
            'visitor_email' => $visit->visitor->email,
            'visit_date' => \Carbon\Carbon::parse($visit->schedule_time)->format('F j, Y - g:i A'),
            'status' => $visit->status,
            // ... other fields
        ];
        
        $emailService->sendVisitStatusEmail($emailData);
    }
    
    // Return response...
}
```

## Logging

All email operations are automatically logged to Laravel's default log channel. Logs include:

### Successful Email Send
```
[2026-01-22 21:30:00] local.INFO: Preparing to send visitor registration email {"visitor_email":"john@example.com","visitor_name":"John Doe","visit_date":"January 25, 2026 - 2:30 PM","sent_by":"Admin User"}
[2026-01-22 21:30:01] local.INFO: Visitor registration email sent successfully {"visitor_email":"john@example.com","visit_date":"January 25, 2026 - 2:30 PM"}
```

### Failed Email Send
```
[2026-01-22 21:30:00] local.ERROR: Failed to send visitor registration email {"error":"Connection timeout","visitor_email":"john@example.com","trace":"..."}
```

### Log Levels Used
- `Log::info()`: Normal operations (preparing to send, sent successfully)
- `Log::error()`: Failed operations with error details

## Email Templates

### Visitor Registration Email
**View:** `resources/views/emails/visitor-registration.blade.php`

Features:
- Professional HTML email design
- Responsive layout for mobile devices
- Color-coded status badges
- Complete visit details display
- Call-to-action button
- UCB Bank branding

### Visit Status Email
**View:** `resources/views/emails/visit-status.blade.php`

Features:
- Status-specific messages
- Dynamic content based on status
- Visit history display
- Professional formatting
- Responsive design

## Creating Custom Email Templates

1. Create a new Blade view in `resources/views/emails/`:

```html
<!-- resources/views/emails/custom.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Custom Email</title>
</head>
<body>
    <h1>Hello {{ $name }}</h1>
    <p>{{ $message }}</p>
</body>
</html>
```

2. Use the `sendCustomEmail` method:

```php
$emailService = new EmailNotificationService();
$emailService->sendCustomEmail(
    'recipient@example.com',
    'Custom Email Subject',
    'emails.custom',
    [
        'name' => 'John Doe',
        'message' => 'This is a custom email',
    ]
);
```

## Testing Email Functionality

### 1. Test Route

Add a test route to `routes/web.php`:

```php
Route::get('/test-email', function () {
    $emailService = new \App\Services\EmailNotificationService();
    
    $emailData = [
        'visitor_name' => 'Test Visitor',
        'visitor_email' => 'your-email@example.com',
        'visit_date' => 'January 25, 2026 - 2:30 PM',
        'status' => 'approved',
    ];
    
    $result = $emailService->sendVisitorRegistrationEmail($emailData);
    
    return $result ? 'Email sent successfully!' : 'Failed to send email';
});
```

### 2. Check Logs

After sending an email, check the logs:

```bash
tail -f storage/logs/laravel.log
```

### 3. Mail Testing in Development

For testing without actually sending emails, use the log driver:

```env
MAIL_MAILER=log
```

Emails will be written to `storage/logs/laravel.log` instead of being sent.

## Troubleshooting

### Email Not Sending

1. **Check SMTP Configuration**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

2. **Verify Credentials**
   Ensure MAIL_USERNAME and MAIL_PASSWORD are correct in `.env`

3. **Check Firewall/Network**
   Ensure port 465 (or your configured port) is open

4. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Queue Issues

If emails are queued but not processing:

```bash
# Start queue worker
php artisan queue:work

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Email Not Reaching Inbox

1. **Check Spam Folder**
2. **Verify SPF/DKIM Records** (production)
3. **Check Email Reputation**
4. **Use Test Mode** during development

## Best Practices

1. **Always Use Service**: Use `EmailNotificationService` instead of Mail facade directly
2. **Validate Data**: Validate email addresses before sending
3. **Handle Errors**: Always check the return value and handle failures gracefully
4. **Use Queues**: All mailables implement `ShouldQueue` for better performance
5. **Log Everything**: The service handles logging automatically, but you can add custom logs
6. **Test Thoroughly**: Always test email functionality before deploying to production
7. **Monitor Logs**: Regularly check logs for failed emails
8. **Rate Limiting**: For bulk emails, consider implementing rate limiting

## Security Considerations

1. **Never Log Passwords**: Ensure sensitive data is not logged
2. **Validate Input**: Always validate user input before including in emails
3. **Use HTTPS**: Ensure SMTP connections use SSL/TLS
4. **Protect .env**: Never commit `.env` file to version control
5. **Sanitize Content**: Sanitize user-generated content before including in emails

## Performance Optimization

1. **Queue Emails**: All mailables implement `ShouldQueue`
2. **Batch Operations**: Use `sendBulkEmail` for multiple recipients
3. **Cache Templates**: Laravel automatically caches compiled views
4. **Monitor Queue Size**: Keep an eye on queue backlog

## Future Enhancements

Potential improvements to consider:

- Email templates for different languages
- Email tracking (open rates, click rates)
- Email attachments support
- Scheduled email sending
- Email templates with dynamic sections
- A/B testing for email content
- Email digest for multiple notifications
- Integration with email marketing platforms

## Support

For issues or questions:
1. Check Laravel Mail documentation: https://laravel.com/docs/mail
2. Review logs in `storage/logs/laravel.log`
3. Consult this guide for common use cases

## Changelog

### Version 1.0.0 (January 22, 2026)
- Initial release
- Visitor registration email
- Visit status email
- Custom email support
- Bulk email support
- Built-in logging
- Error handling
