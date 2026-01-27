<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Data Export</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">Visitor Data Export</h2>
        
        <p>Dear User,</p>
        
        <p>Please find attached the CSV file containing visitor data from the VMS system.</p>
        
        @if($dateRange)
        <p><strong>Date Range:</strong> {{ $dateRange }}</p>
        @endif
        
        <p><strong>Export Date:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #777;">
            This is an automated email from the Visitor Management System.<br>
            Please do not reply to this email.
        </p>
    </div>
</body>
</html>
