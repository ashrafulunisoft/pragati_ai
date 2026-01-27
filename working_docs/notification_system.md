# Notification System Documentation

A complete, reusable email and SMS notification system for Laravel applications.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Examples](#examples)
6. [SMS Providers](#sms-providers)
7. [Email Templates](#email-templates)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

This notification system provides:
- ✅ Email notifications with customizable templates
- ✅ SMS notifications with multiple provider support
- ✅ Queue support for async processing
- ✅ Helper functions for easy usage
- ✅ Laravel notification system integration
- ✅ Error handling and logging

---

## ✨ Features

### Email Features
- HTML email templates
- Markdown support
- Attachments support
- Queue support (async)
- Customizable via Blade templates

### SMS Features
- Multiple providers (Nexmo, Twilio, BulkSMS)
- Automatic phone number formatting
- Country code handling (Bangladesh default)
- Fallback to logging (development)
- Error tracking

### Helper Functions
- Send email only
- Send SMS only
- Send both email and SMS
- Laravel notification wrapper
- Pre-built notification templates

---

## ⚙️ Configuration

### 1. Email Configuration (`.env`)

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ucbbank.com
MAIL_FROM_NAME="UCB Bank"
```

### 2. SMS Configuration (`.env`)

```env
# Enable/Disable SMS
SMS_ENABLED=true

# Choose Provider: nexmo, twilio, bulk, default
SMS_PROVIDER=default

# Sender Name/Number
SMS_FROM="UCB Bank"

# Nexmo (Vonage)
NEXMO_API_KEY=your-nexmo-api-key
NEXMO_API_SECRET=your-nexmo-api-secret
NEXMO_SMS_FROM="UCB Bank"

# Twilio
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM="+1234567890"

# BulkSMS BD
BULKSMS_API_KEY=your-bulksms-api-key
BULKSMS_SENDER_ID="UCBBank"
```

### 3. Configuration File (`config/sms.php`)

The configuration file provides detailed options for each SMS provider. See the file for more details.

---

## 🚀 Usage

### Method 1: Using NotificationHelper (Easiest)

The `NotificationHelper` class provides static methods for common notification tasks.

#### Send Email Only

```php
use App\Helpers\NotificationHelper;

$result = NotificationHelper::sendEmail(
    'user@example.com',                              // to
    'Welcome to Our App',                            // subject
    'emails.welcome',                                // view
    ['name' => 'John Doe', 'email' => 'user@example.com'] // data
);

// Returns: true (success) or false (failed)
```

#### Send SMS Only

```php
use App\Helpers\NotificationHelper;

$result = NotificationHelper::sendSms(
    '+8801234567890',               // to
    'Your appointment is confirmed'     // message
);

// Returns: ['success' => bool, 'message' => string]
```

#### Send Both Email and SMS

```php
use App\Helpers\NotificationHelper;

$result = NotificationHelper::sendBoth(
    'user@example.com',                      // email
    '+8801234567890',                      // phone
    'Appointment Confirmed',                   // email subject
    'emails.appointment',                      // email view
    'Your appointment is confirmed',             // SMS message
    ['name' => 'John Doe']                   // email data
);

// Returns: ['email' => bool, 'sms' => bool, 'messages' => array]
```

#### Send Welcome Email

```php
use App\Helpers\NotificationHelper;

NotificationHelper::sendWelcomeEmail(
    $user,              // User model
    'password123'       // optional: plain text password
);
```

#### Send Appointment Reminder

```php
use App\Helpers\NotificationHelper;

NotificationHelper::sendAppointmentReminder(
    'user@example.com',              // email
    '+8801234567890',              // phone
    'John Doe',                     // visitor name
    'Jan 25, 2026 2:00 PM'       // appointment date
);
```

#### Send Status Update

```php
use App\Helpers\NotificationHelper;

NotificationHelper::sendStatusUpdate(
    'user@example.com',      // email
    '+8801234567890',      // phone
    'John Doe',             // visitor name
    'Approved'              // new status
);
```

---

### Method 2: Using Laravel Notifications

Create a notification class and use Laravel's notification system.

#### Create Notification Class

```bash
php artisan make:notification CustomNotification
```

#### Example: VisitorRegistered Notification

```php
use Illuminate\Notifications\Notification;

// Send to visitor
$visitor->notify(new VisitorRegistered($visitor, $visit));

// Send using NotificationHelper
NotificationHelper::notifyVisitor($visitor, $visit);
```

---

### Method 3: Using NotificationHelper with Custom Notifications

```php
use App\Helpers\NotificationHelper;
use App\Notifications\CustomNotification;

// Send to any notifiable entity
$notifiable = User::find(1);
NotificationHelper::notify($notifiable, new CustomNotification($data));
```

---

## 📚 Examples

### Example 1: Send Registration Confirmation

```php
// In Controller
public function register(Request $request)
{
    $user = User::create($request->validated());
    
    // Send welcome email
    NotificationHelper::sendWelcomeEmail($user);
    
    return redirect()->route('dashboard');
}
```

### Example 2: Send Appointment Reminder (Scheduled)

```php
// In Console Command
public function handle()
{
    $appointments = Visit::where('schedule_time', '>', now())
                        ->where('schedule_time', '<', now()->addDay())
                        ->get();
    
    foreach ($appointments as $appointment) {
        $visitor = $appointment->visitor;
        $date = $appointment->schedule_time->format('M j, Y g:i A');
        
        NotificationHelper::sendAppointmentReminder(
            $visitor->email,
            $visitor->phone,
            $visitor->name,
            $date
        );
    }
    
    $this->info('Reminders sent successfully');
}
```

### Example 3: Send Status Update

```php
// In Controller
public function updateStatus(Request $request, $id)
{
    $visit = Visit::findOrFail($id);
    $visit->update(['status' => $request->status]);
    
    // Notify visitor
    NotificationHelper::sendStatusUpdate(
        $visit->visitor->email,
        $visit->visitor->phone,
        $visit->visitor->name,
        $visit->status
    );
    
    return back()->with('success', 'Status updated');
}
```

### Example 4: Custom Email with Attachment

```php
// Create custom email view: resources/views/emails/report.blade.php

// In Controller
use Illuminate\Support\Facades\Mail;

Mail::send('emails.report', $data, function ($message) use ($email, $pdfPath) {
    $message->to($email)
            ->subject('Your Monthly Report')
            ->attach($pdfPath, [
                'as' => 'monthly-report.pdf',
                'mime' => 'application/pdf'
            ]);
});
```

---

## 📱 SMS Providers

### 1. Default (Development Mode)

Logs SMS to file instead of sending. Perfect for development and testing.

```env
SMS_PROVIDER=default
```

### 2. Nexmo (Vonage)

Professional SMS service with global coverage.

**Setup:**
1. Go to [dashboard.nexmo.com](https://dashboard.nexmo.com)
2. Create account and get API credentials
3. Add credentials to `.env`

```env
SMS_PROVIDER=nexmo
NEXMO_API_KEY=your-api-key
NEXMO_API_SECRET=your-api-secret
```

### 3. Twilio

Leading cloud communications platform.

**Setup:**
1. Go to [twilio.com/console](https://www.twilio.com/console)
2. Create account and get credentials
3. Add credentials to `.env`

```env
SMS_PROVIDER=twilio
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM=+1234567890
```

### 4. BulkSMS BD

Local SMS provider for Bangladesh.

**Setup:**
1. Go to [bulksmsbd.net](https://bulksmsbd.net/)
2. Create account and get API key
3. Add credentials to `.env`

```env
SMS_PROVIDER=bulk
BULKSMS_API_KEY=your-api-key
BULKSMS_SENDER_ID=YourSenderID
```

---

## 📧 Email Templates

### Location
All email templates are in: `resources/views/emails/`

### Available Templates

1. **`welcome.blade.php`** - Welcome email for new users
2. **`visitor-registered.blade.php`** - Auto-generated by Laravel notifications

### Creating Custom Templates

```bash
# Create directory if not exists
mkdir -p resources/views/emails

# Create template
touch resources/views/emails/custom.blade.php
```

**Template Structure:**

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Title</title>
    <!-- Add CSS here -->
</head>
<body>
    <div class="container">
        <!-- Email content -->
        <h1>Hello {{ $name }}!</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
```

**Use Template:**

```php
NotificationHelper::sendEmail(
    'user@example.com',
    'Subject Line',
    'emails.custom',
    ['name' => 'John', 'message' => 'Welcome!']
);
```

---

## 🔧 Troubleshooting

### Email Not Sending

1. **Check Mail Configuration:**
```bash
php artisan config:cache --clear
php artisan cache:clear
```

2. **Verify `.env` Settings:**
```bash
php artisan tinker
>>> config('mail');
```

3. **Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

### SMS Not Sending

1. **Check SMS is Enabled:**
```bash
php artisan tinker
>>> config('sms.enabled');
```

2. **Verify Credentials:**
```bash
php artisan tinker
>>> config('sms.nexmo');
```

3. **Test with Default Provider:**
```env
SMS_PROVIDER=default  # Logs to file instead
```

4. **Check Phone Number Format:**
- Should include country code: `+8801234567890`
- Or local format: `01712345678` (auto-converted)

### Queue Not Processing

1. **Check Queue Worker:**
```bash
php artisan queue:work
```

2. **Clear Failed Jobs:**
```bash
php artisan queue:flush
```

3. **Check Failed Jobs Table:**
```bash
php artisan tinker
>>> DB::table('failed_jobs')->get();
```

---

## 📖 Best Practices

1. **Always Queue Notifications:**
   ```php
   class VisitorRegistered extends Notification implements ShouldQueue
   {
       // ...
   }
   ```

2. **Use NotificationHelper for Common Tasks:**
   ```php
   NotificationHelper::sendWelcomeEmail($user);
   ```

3. **Test with Default Provider First:**
   ```env
   SMS_PROVIDER=default  # Development
   SMS_PROVIDER=nexmo  # Production
   ```

4. **Handle Errors Gracefully:**
   ```php
   $result = NotificationHelper::sendEmail(...);
   
   if (!$result) {
       Log::error('Email failed');
   }
   ```

5. **Use Environment Variables:**
   ```php
   // Never hardcode credentials
   config('sms.nexmo.api_key');  // ✅ Good
   'your-api-key';                  // ❌ Bad
   ```

---

## 🎉 Complete Example

```php
<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\Visitor;
use App\Models\Visit;
use App\Notifications\VisitorRegistered;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function store(Request $request)
    {
        // Validate
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'nullable',
            'visit_date' => 'required|date',
        ]);
        
        // Create visitor
        $visitor = Visitor::create($validated);
        
        // Create visit
        $visit = Visit::create([
            'visitor_id' => $visitor->id,
            'schedule_time' => $request->visit_date,
            'status' => 'approved',
        ]);
        
        // Send notification (Method 1: Laravel Notification)
        $visitor->notify(new VisitorRegistered($visitor, $visit));
        
        // OR Send notification (Method 2: Helper)
        // NotificationHelper::notifyVisitor($visitor, $visit);
        
        // OR Send manually (Method 3: Helper)
        // NotificationHelper::sendBoth(
        //     $visitor->email,
        //     $visitor->phone,
        //     'Visit Confirmed',
        //     'emails.visit-confirmed',
        //     'Your visit is confirmed',
        //     ['name' => $visitor->name]
        // );
        
        return back()->with('success', 'Visitor registered!');
    }
}
```

---

## 📞 Support

For issues or questions:
- Check Laravel documentation: [https://laravel.com/docs/notifications](https://laravel.com/docs/notifications)
- Check SMS provider documentation
- Check `storage/logs/laravel.log` for errors

---

**Created by:** AI Assistant  
**Date:** January 22, 2026  
**Version:** 1.0.0
