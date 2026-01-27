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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .rfid-code {
            background: #fff3cd;
            border: 2px dashed #ffc107;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #856404;
            border-radius: 4px;
            margin: 20px 0;
            letter-spacing: 2px;
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
            <h1>🎉 Visit Approved!</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $emailData['visitor_name'] }}</strong>,</p>

            <div class="success-message">
                Great news! Your visit has been approved by the host.
            </div>

            <div class="info-section">
                <h3>Your RFID Badge</h3>
                <p>Please use the following RFID code for check-in:</p>
                <div class="rfid-code">
                    {{ $emailData['rfid'] }}
                </div>
            </div>

            <div class="info-section">
                <h3>Visit Details</h3>
                <div class="info-row">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ $emailData['visit_date'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Host:</span>
                    <span class="info-value">{{ $emailData['host_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">RFID Code:</span>
                    <span class="info-value">{{ $emailData['rfid'] }}</span>
                </div>
            </div>

            <div class="info-section">
                <h3>Check-in Instructions</h3>
                <ol>
                    <li>Save your RFID code: <strong>{{ $emailData['rfid'] }}</strong></li>
                    <li>Arrive at the reception desk on time</li>
                    <li>Present your RFID code for verification</li>
                    <li>Your RFID badge will be activated for check-in</li>
                </ol>
            </div>

            <div class="info-section">
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>Your RFID code is valid only for this visit</li>
                    <li>Please carry a valid ID proof</li>
                    <li>Contact reception if you need any assistance</li>
                </ul>
            </div>

            <p style="text-align: center;">
                We look forward to welcoming you!
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
