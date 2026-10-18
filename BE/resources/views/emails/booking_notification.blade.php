<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectTitle ?? 'Thông báo từ DriveRentals' }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 24px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: #0f172a; padding: 24px; color: #ffffff; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 32px 24px; line-height: 1.6; font-size: 15px; }
        .title { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
        .body-text { margin-bottom: 24px; color: #475569; }
        .info-box { background: #f1f5f9; border-radius: 8px; padding: 16px; margin-bottom: 24px; border-left: 4px solid #2563eb; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 600; color: #64748b; }
        .info-value { font-weight: 700; color: #0f172a; }
        .btn-wrapper { text-align: center; margin-top: 24px; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 700; font-size: 14px; }
        .footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>DriveRentals Vietnam</h1>
        </div>
        <div class="content">
            <div class="title">{{ $title }}</div>
            <div class="body-text">{{ $bodyContent }}</div>

            @if(!empty($details))
            <div class="info-box">
                @foreach($details as $label => $value)
                <div class="info-row">
                    <span class="info-label">{{ $label }}:</span>
                    <span class="info-value">{{ $value }}</span>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($actionUrl))
            <div class="btn-wrapper">
                <a href="{{ $actionUrl }}" class="btn">Xem chi tiết đơn hàng</a>
            </div>
            @endif
        </div>
        <div class="footer">
            <p>Đây là thông báo tự động từ hệ thống DriveRentals. Quý khách vui lòng không trả lời thư này.</p>
            <p>&copy; {{ date('Y') }} DriveRentals. Hotline: 1900 1234 56</p>
        </div>
    </div>
</body>
</html>
