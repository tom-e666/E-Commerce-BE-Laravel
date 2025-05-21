<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        /* Reset styles */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background-color: #4a6cf7;
            padding: 24px;
            text-align: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            color: white;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        
        /* Content */
        .content {
            background-color: #ffffff;
            padding: 30px;
            border-left: 1px solid #e8e8e8;
            border-right: 1px solid #e8e8e8;
        }
        
        .welcome {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .message {
            margin-bottom: 25px;
            color: #555;
        }
        
        /* Button */
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .button {
            display: inline-block;
            background-color: #4a6cf7;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #3b5ef8;
        }
        
        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            border: 1px solid #e8e8e8;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        /* Link Fallback */
        .link-fallback {
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .link-text {
            word-break: break-all;
            color: #4a6cf7;
        }
        
        /* Responsive */
        @media screen and (max-width: 600px) {
            .container {
                width: 100%;
                padding: 10px;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Replace with your logo URL -->
            <img src="{{ asset('images/logo.png') }}" alt="E-Commerce Logo">
            <h1>Email Verification</h1>
        </div>
        
        <div class="content">
            <div class="welcome">
                Hello {{ $name }},
            </div>
            
            <div class="message">
                Thank you for registering! To activate your account and get started, please verify your email address by clicking the button below.
            </div>
            
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
            </div>
            
            <div class="message">
                This verification link will expire in 60 minutes. If you did not create an account, no further action is required.
            </div>
            
            <div class="link-fallback">
                If the button above doesn't work, copy and paste the following link into your browser:
                <div class="link-text">{{ $verificationUrl }}</div>
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Your E-Commerce Store. All rights reserved.</p>
            <p>123 E-Commerce Street, City, Country</p>
        </div>
    </div>
</body>
</html>