<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration Confirmation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 20px;
            margin-bottom: 20px;
            color: #667eea;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #667eea;
            font-size: 16px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            flex: 0 0 120px;
        }
        .info-value {
            color: #333;
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .important-note h4 {
            margin-top: 0;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .button {
            display: inline-block;
            background-color: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #5568d3;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                flex: none;
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Visitor Registration Confirmed</h1>
        </div>

        <div class="content">
            <p class="greeting">
                Dear {{ $data['visitor_name'] ?? 'Visitor' }},
            </p>

            <p>
                Thank you for registering your visit to <strong>UCB Bank</strong>. Your visit has been successfully scheduled!
            </p>

            <div class="info-box">
                <h3>📋 Visit Details</h3>

                <div class="info-row">
                    <span class="info-label">📅 Date & Time:</span>
                    <span class="info-value">{{ $data['visit_date'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">👤 Visitor Name:</span>
                    <span class="info-value">{{ $data['visitor_name'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">📧 Email:</span>
                    <span class="info-value">{{ $data['visitor_email'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">📱 Phone:</span>
                    <span class="info-value">{{ $data['visitor_phone'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">🏢 Company:</span>
                    <span class="info-value">{{ $data['visitor_company'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">👔 Visit Type:</span>
                    <span class="info-value">{{ $data['visit_type'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">🎯 Purpose:</span>
                    <span class="info-value">{{ $data['purpose'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">👨‍💼 Host:</span>
                    <span class="info-value">{{ $data['host_name'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">📊 Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ $data['status'] ?? 'pending' }}">
                            {{ ucfirst($data['status'] ?? 'pending') }}
                        </span>
                    </span>
                </div>
            </div>

            <div class="important-note">
                <h4>⚠️ Important Information</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Please arrive <strong>10-15 minutes</strong> before your scheduled time</li>
                    <li>Bring a valid photo ID for verification</li>
                    <li>Check-in at the reception desk upon arrival</li>
                    <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
                </ul>
            </div>

            <p style="text-align: center;">
                <a href="{{ url('/admin/visitor/list') }}" class="button">View Visit Details</a>
            </p>

            <p>
                If you have any questions or need to make changes to your visit, please contact our visitor management team.
            </p>
        </div>

        <div class="footer">
            <p><strong>UCB Bank Visitor Management System</strong></p>
            <p>United Commercial Bank PLC</p>
            <p>
                📧 Email: {{ config('mail.from.address') }} |
                📞 Phone: +880 2-XXXXXXX
            </p>
            <p style="margin-top: 15px;">
                This is an automated message. Please do not reply to this email.
            </p>
            <p style="margin-top: 10px; font-size: 12px;">
                {{ date('Y') }} UCB Bank. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
