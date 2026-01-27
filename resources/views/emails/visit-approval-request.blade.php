<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Approval Request - UCB Bank</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #060b1d;
            color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 10px 40px rgba(59, 130, 246, 0.3);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
        }
        .content {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0 0 20px 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #94a3b8;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #e2e8f0;
        }
        .info-card {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #94a3b8;
            font-size: 14px;
            font-weight: 600;
        }
        .info-value {
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #4f46e5 100%);
            color: white;
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 100px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 14px;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
            transition: all 0.3s;
        }
        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
        }
        .footer {
            text-align: center;
            padding: 30px;
            color: #64748b;
            font-size: 12px;
        }
        .highlight {
            color: #3b82f6;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Visit Approval Request</h1>
        </div>
        <div class="content">
            <p class="greeting">Dear {{ $hostName }},</p>
            <p class="message">
                A new visit request has been submitted and is waiting for your approval.
                Please review the details below and take appropriate action.
            </p>

            <div class="info-card">
                <div class="info-item">
                    <span class="info-label">Visitor Name:</span>
                    <span class="info-value highlight">{{ $visitorName }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address:</span>
                    <span class="info-value">{{ $visitorEmail }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone Number:</span>
                    <span class="info-value">{{ $visitorPhone }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Visit Type:</span>
                    <span class="info-value">{{ $visitType }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Purpose:</span>
                    <span class="info-value">{{ $purpose }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Scheduled Date:</span>
                    <span class="info-value highlight">{{ $visitDate }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $approvalLink }}" class="button">
                    Review & Approve Visit
                </a>
            </div>

            <p class="message">
                Once you approve the visit, an <strong>RFID badge</strong> will be generated automatically
                and the visitor will be notified via email with the badge details.
            </p>
        </div>
        <div class="footer">
            <p>UCB Bank Visitor Management System</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
