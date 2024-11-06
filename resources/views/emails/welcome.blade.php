<!-- resources/views/emails/welcome.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ env('APP_NAME') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h1 style="text-align: center; color: #007bff;">Welcome to {{ env('APP_NAME') }}, {{ $name }}!</h1>
        <p>We're excited to have you on board! Your account has been successfully created, and you can start using our platform right away.</p>
        
        <h2>Login Credentials</h2>
        <p>Here are your account details:</p>
        <p><strong>Email:</strong> {{ $email }}</p>
        <p><strong>Password:</strong> {{ $password }}</p>

        <h2>How to Log In</h2>
        <p>You have two options for logging in:</p>
        
        <ol>
            <li><strong>Login with Google:</strong> Use your Google account to log in for quick and secure access. Just click "Login with Google" on the login page.</li>
            <li><strong>Login with Email and Password:</strong> You can also log in using the email and password provided above.</li>
        </ol>

        <p>Visit our login page to get started: <a href="{{ env('APP_FRONTEND_URL') }}/login" style="color: #007bff; text-decoration: none;">{{ env('APP_FRONTEND_URL') }}/login</a></p>

        <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
        
        <p>Welcome again to {{ env('APP_NAME') }}! We look forward to helping you achieve great things.</p>
        
        <p>Best regards,</p>
        <p>The {{ env('APP_NAME') }} Team</p>
    </div>
</body>
</html>
