<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu</title>
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
            color: white !important; /* Force white text color */
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #3b5ef8;
            color: white !important; /* Keep text white on hover */
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
            <h1>Đặt Lại Mật Khẩu</h1>
        </div>
        
        <div class="content">
            <div class="welcome">
                Xin chào {{ $name }},
            </div>
            
            <div class="message">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Vui lòng nhấp vào nút bên dưới để tiếp tục quá trình đặt lại mật khẩu.
            </div>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button" style="color: white; text-decoration: none;">Đặt Lại Mật Khẩu</a>
            </div>
            
            <div class="message">
                Liên kết này sẽ hết hạn trong 60 phút. Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
            </div>
            
            <div class="link-fallback">
                Nếu nút ở trên không hoạt động, hãy sao chép và dán liên kết sau vào trình duyệt của bạn:
                <div class="link-text">{{ $resetUrl }}</div>
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} E-Commerce Laptop. Đã được bảo lưu mọi quyền.</p>
            <p>280 An Dương Vương, Quận 5, Thành phố Hồ Chí Minh, Việt Nam</p>
        </div>
    </div>
</body>
</html>
