<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu - DriveRent</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: #0b1220; padding: 24px 32px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .content { padding: 32px; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: #0052cc; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .note { font-size: 13px; color: #64748b; line-height: 1.5; margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
        .footer { background: #f8fafc; padding: 16px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>DriveRent - Hệ Thống Thuê Xe</h1>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $recipientName }}</strong>,</p>
            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản <strong>{{ $accountType === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}</strong> của bạn trên hệ thống DriveRent.</p>
            <p>Vui lòng nhấp vào nút bên dưới để tiến hành thiết lập mật khẩu mới (liên kết có hiệu lực trong vòng <strong>{{ $expiresInMinutes }} phút</strong> và chỉ sử dụng được một lần):</p>
            
            <div class="btn-wrap">
                <a href="{{ $resetUrl }}" class="btn" target="_blank">Đặt Lại Mật Khẩu</a>
            </div>

            <p class="note">
                Nếu bạn không gửi yêu cầu này, vui lòng bỏ qua email. Mật khẩu hiện tại của bạn vẫn được bảo mật và an toàn.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} DriveRent. Mọi quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
