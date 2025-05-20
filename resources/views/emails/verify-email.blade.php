<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
    <style>
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3182ce;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Verify Your Email Address</h1>
    
    <p>Hi {{ $name }},</p>
    
    <p>Please click the button below to verify your email address.</p>
    
    <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
    
    <p>If you did not create an account, no further action is required.</p>
    
    <p>Thanks,<br>
    {{ config('app.name') }}</p>
    
    <small>This verification link will expire in 60 minutes.</small>
</body>
</html>