# Notification System Troubleshooting Guide

If notifications are not sending, follow these steps to diagnose and fix the issue.

---

## 📋 Quick Diagnostic Steps

### Step 1: Test Basic Email

Visit: `http://your-domain.com/test-mail`

This tests if your mail configuration is working.

**Expected Result:** "Email sent successfully! Check your inbox."
**If Error:** Check mail configuration (see below)

---

### Step 2: Test Visitor Notification

Visit: `http://your-domain.com/test-notification`

This tests the visitor notification system.

**Expected Result:** "Email notification sent successfully! Check your inbox."
**If Error:** See specific error message below

---

### Step 3: Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

Look for errors related to mail or notifications.

---

## 📧 Email Issues

### Issue: "Connection refused" or "Could not connect to host"

**Cause:** SMTP server is not accessible

**Solution:**

1. **Check `.env` mail settings:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

2. **For Gmail, use App Password:**
   - Go to https://myaccount.google.com/security
   - Enable 2-Step Verification
   - Go to App Passwords
   - Generate new app password
   - Use that password in `.env`

3. **Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

---

### Issue: "Email not sent" but no error

**Cause:** Queue worker not running

**Solution:**

1. **Check if queue is configured:**
```bash
php artisan queue:work
```

2. **Check failed jobs:**
```bash
php artisan queue:failed
```

3. **Try without queue for testing:**
   
   In `app/Notifications/VisitorRegistered.php`, remove `implements ShouldQueue`:
```php
class VisitorRegistered extends Notification // Remove "implements ShouldQueue"
{
    use Queueable; // Keep this
```

---

### Issue: "Authentication failed"

**Cause:** Wrong email/password

**Solution:**

1. Verify email and password in `.env`
2. For Gmail, use App Password (not regular password)
3. Check if SMTP port is correct:
   - Gmail TLS: 587
   - Gmail SSL: 465

---

## 📱 SMS Issues

### Issue: "SMS not sending"

**Cause:** SMS provider not configured or disabled

**Solution:**

1. **Check if SMS is enabled in `.env`:**
```env
SMS_ENABLED=true
```

2. **Check SMS provider:**
```env
SMS_PROVIDER=default  # For testing (logs to file)
SMS_PROVIDER=nexmo   # For production
SMS_PROVIDER=twilio   # Alternative
SMS_PROVIDER=bulk     # Bangladesh provider
```

3. **Test with default provider first:**
```env
SMS_PROVIDER=default
SMS_ENABLED=true
```

This will log SMS to `storage/logs/sms.log` instead of sending.

4. **Check SMS logs:**
```bash
tail -f storage/logs/sms.log
```

---

### Issue: "Invalid credentials" for SMS provider

**Cause:** Wrong API credentials

**Solution:**

#### For Nexmo (Vonage):
```env
SMS_PROVIDER=nexmo
NEXMO_API_KEY=your-key
NEXMO_API_SECRET=your-secret
NEXMO_SMS_FROM="UCB Bank"
```

Get credentials from: https://dashboard.nexmo.com

#### For Twilio:
```env
SMS_PROVIDER=twilio
TWILIO_SID=your-sid
TWILIO_TOKEN=your-token
TWILIO_FROM="+1234567890"
```

Get credentials from: https://www.twilio.com/console

#### For BulkSMS BD:
```env
SMS_PROVIDER=bulk
BULKSMS_API_KEY=your-key
BULKSMS_SENDER_ID="YourSenderID"
```

Get credentials from: https://bulksmsbd.net/

---

### Issue: "Phone number format invalid"

**Cause:** Phone number not in correct format

**Solution:**

Phone numbers should be:
- With country code: `+8801712345678`
- Or local format: `01712345678` (auto-converted)

**Valid Examples:**
```php
'+8801712345678'  // ✅ Bangladesh with country code
'01712345678'      // ✅ Bangladesh local
'+16195551234'     // ✅ US with country code
'6195551234'       // ❌ Will fail (no country code)
```

---

## 🔍 Debug Mode

### Enable Detailed Logging

Add to `app/Providers/AppServiceProvider.php`:

```php
public function boot()
{
    // Log all mail
    \Illuminate\Support\Facades\Event::listen(
        \Illuminate\Mail\Events\MessageSending::class,
        function ($event) {
            \Log::info('Email sending to: ' . $event->message->getTo()[0]->getAddress());
        }
    );
    
    // Log all notifications
    \Illuminate\Support\Facades\Event::listen(
        \Illuminate\Notifications\Events\NotificationSent::class,
        function ($event) {
            \Log::info('Notification sent to: ' . $event->notifiable->email);
        }
    );
}
```

---

## 🧪 Test Commands

### Test Email Directly

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;
use App\Models\Visitor;

$visitor = Visitor::first();
Mail::raw('Test email', function($message) use ($visitor) {
    $message->to($visitor->email)
            ->subject('Test Email');
});
```

### Test Notification Directly

```bash
php artisan tinker
```

```php
use App\Models\Visitor;
use App\Models\Visit;

$visitor = Visitor::first();
$visit = Visit::first();
$visitor->notify(new \App\Notifications\VisitorRegistered($visitor, $visit));
```

### Test SMS Directly

```bash
php artisan tinker
```

```php
use App\Helpers\NotificationHelper;

NotificationHelper::sendSms('+8801712345678', 'Test SMS message');
```

---

## 📊 Common Issues & Solutions

### Issue 1: Notifications queued but not processed

**Cause:** Queue worker not running

**Solution:**
```bash
# Run queue worker
php artisan queue:work

# Or run in background
nohup php artisan queue:work &
```

---

### Issue 2: Visitor has no email or phone

**Cause:** Visitor record missing email/phone

**Solution:**
```php
// Check visitor data
php artisan tinker
>>> $visitor = Visitor::first();
>>> $visitor->email;
>>> $visitor->phone;
```

Ensure visitor has:
- `email` field filled
- `phone` field filled (for SMS)

---

### Issue 3: Phone number exists but SMS not sent

**Cause:** SMS disabled or provider issue

**Solution:**

1. Check if SMS is enabled:
```bash
php artisan tinker
>>> config('sms.enabled');
// Should return: true
```

2. Check SMS provider:
```bash
php artisan tinker
>>> config('sms.provider');
// Should return: default, nexmo, twilio, or bulk
```

3. Test with default provider:
```env
SMS_PROVIDER=default
SMS_ENABLED=true
```

4. Check logs:
```bash
tail -f storage/logs/sms.log
```

---

### Issue 4: Email sent but not received

**Cause:** Email blocked or in spam folder

**Solution:**

1. Check spam folder
2. Check if email address is correct
3. Try sending to a different email (e.g., Gmail)
4. Check if mail server IP is blacklisted:
   - Visit: https://mxtoolbox.com/blacklists.aspx
   - Enter your mail server IP

---

## 🛠️ Configuration Checklist

### ✅ Mail Configuration (.env)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Use App Password for Gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ucbbank.com
MAIL_FROM_NAME="UCB Bank"
```

### ✅ SMS Configuration (.env)

```env
SMS_ENABLED=true
SMS_PROVIDER=default  # Options: default, nexmo, twilio, bulk
SMS_FROM="UCB Bank"

# Nexmo (if using)
NEXMO_API_KEY=your-key
NEXMO_API_SECRET=your-secret
NEXMO_SMS_FROM="UCB Bank"

# Twilio (if using)
TWILIO_SID=your-sid
TWILIO_TOKEN=your-token
TWILIO_FROM="+1234567890"

# BulkSMS (if using)
BULKSMS_API_KEY=your-key
BULKSMS_SENDER_ID="YourID"
```

---

## 📝 Testing Flow

### Complete Testing Process:

1. **Test Basic Email:**
   ```
   http://your-domain.com/test-mail
   ```

2. **Check Email Inbox:**
   - Look for "Test Email" from your app
   - Check spam folder

3. **Test Visitor Notification:**
   ```
   http://your-domain.com/test-notification
   ```

4. **Check Email Inbox Again:**
   - Look for "Visitor Registration Confirmation"
   - Verify details are correct

5. **Test SMS (if phone exists):**
   - Register new visitor with phone number
   - Check SMS logs: `storage/logs/sms.log`

6. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 🆘 Still Not Working?

### Enable Debug Mode

Add to `.env`:
```env
APP_DEBUG=true
LOG_CHANNEL=daily
LOG_LEVEL=debug
```

### Check All Logs:

```bash
# Laravel log
tail -f storage/logs/laravel.log

# SMS log
tail -f storage/logs/sms.log

# Queue log
tail -f storage/logs/queue.log

# PHP error log
tail -f /var/log/php/error.log
```

### Get Error Details:

```bash
php artisan tinker
```

```php
try {
    $visitor = Visitor::first();
    $visitor->notify(new \App\Notifications\VisitorRegistered($visitor, $visitor->visits()->first()));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
```

---

## 📞 Need More Help?

### Check Documentation:
- Laravel Notifications: https://laravel.com/docs/notifications
- Laravel Mail: https://laravel.com/docs/mail
- Nexmo API: https://developer.nexmo.com/
- Twilio API: https://www.twilio.com/docs/sms

### Common Solutions:

1. **Always clear cache after changing `.env`:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

2. **Verify database has visitor with email:**
```bash
php artisan tinker
>>> Visitor::first()->email;
```

3. **Check if visitor has visit:**
```bash
php artisan tinker
>>> Visitor::first()->visits->count();
```

---

**Last Updated:** January 22, 2026
