<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .welcome-text {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .details {
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .details p {
            margin: 8px 0;
            color: #555;
            font-size: 14px;
        }
        .details strong {
            color: #1e3a8a;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 20px;
            font-weight: 600;
        }
        .footer {
            background-color: #1e293b;
            color: #94a3b8;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        .footer a {
            color: #60a5fa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to UCB Bank</h1>
            <p>Visitor Management System</p>
        </div>

        <div class="content">
            <p class="welcome-text">
                Dear <strong>{{ $name }}</strong>,
            </p>

            <p class="welcome-text">
                Welcome aboard! We're thrilled to have you as part of the UCB Bank community. Your account has been successfully created and is ready to use.
            </p>

            <div class="details">
                <p><strong>Email:</strong> {{ $email }}</p>
                @if($password)
                <p><strong>Password:</strong> {{ $password }}</p>
                @endif
            </div>

            <p class="welcome-text">
                You can now log in to your dashboard to manage visits, view reports, and access all the features of our visitor management system.
            </p>

            <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>

            <p style="margin-top: 30px; color: #666; font-size: 14px;">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} UCB Bank. All rights reserved.</p>
            <p>This is an automated message. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
