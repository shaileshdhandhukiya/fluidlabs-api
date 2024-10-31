<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
        }
        p {
            color: #666666;
            line-height: 1.5;
        }
        .otp-code {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            display: inline-block;
            margin-top: 20px;
        }
        .footer {
            font-size: 12px;
            color: #999999;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hello, {{ $user->first_name }}!</h2>
        <p>Thank you for registering with us. To complete your registration and verify your email address, please use the OTP code below. This code is valid for the next 10 minutes.</p>
        <p>Your OTP code is:</p>
        <div class="otp-code">{{ $otp_code }}</div>
        <p>If you did not request this code, please ignore this email.</p>
        <p>Thank you!<br>The {{ config('app.name') }} Team</p>
        <div class="footer">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
