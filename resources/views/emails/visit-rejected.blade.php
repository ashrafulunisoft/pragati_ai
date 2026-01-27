<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 0;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            min-width: 150px;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .reason-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .reason-box h3 {
            margin-top: 0;
            color: #856404;
        }
        .reason-text {
            color: #856404;
            font-style: italic;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Visit Rejected</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $emailData['visitor_name'] }}</strong>,</p>

            <div class="error-message">
                We regret to inform you that your visit request has been rejected by the host.
            </div>

            <div class="reason-box">
                <h3>Reason for Rejection:</h3>
                <p class="reason-text">
                    "{{ $emailData['reason'] }}"
                </p>
            </div>

            <div class="info-section">
                <h3>Visit Details</h3>
                <div class="info-row">
                    <span class="info-label">Visitor:</span>
                    <span class="info-value">{{ $emailData['visitor_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Host:</span>
                    <span class="info-value">{{ $emailData['host_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $emailData['visitor_email'] }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>What You Can Do:</h3>
                <ul>
                    <li>Review the rejection reason above</li>
                    <li>Contact the host directly if you need clarification</li>
                    <li>Submit a new visit request if needed</li>
                    <li>Ensure your visit details are accurate</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Need Assistance?</h3>
                <p>If you believe this rejection was made in error or need further assistance, please contact our reception team.</p>
            </div>

            <p style="text-align: center; color: #777; margin-top: 30px;">
                We apologize for any inconvenience caused.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated email from {{ config('app.name') }}</p>
            <p>If you didn't request this visit, please ignore this email.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
