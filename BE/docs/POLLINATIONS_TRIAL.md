# Thử ảnh xe bằng Pollinations

Tích hợp có chọn lọc từ `HungNguyen3205/TOOL-MKT` — `PollinationsImageProvider` (đọc nhánh main ngày 20/10/2026). Giữ yêu cầu POST keyed, giải mã base64 và kiểm tra MIME; không mang cookie, fallback trả phí, Facebook hoặc workspace sang.

## Bật thử

Trong `.env` backend (không commit giá trị key):

```dotenv
POLLINATIONS_ENABLED=true
POLLINATIONS_MODE=keyed
POLLINATIONS_API_KEY=your_server_side_key
POLLINATIONS_IMAGE_MODEL=flux
POLLINATIONS_DAILY_LIMIT=10
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
CACHE_STORE=database
```

Dùng key riêng có ngân sách/model giới hạn tại nhà cung cấp. Keyed API dùng Pollen, **không cam kết miễn phí**. Không sao chép key khỏi repo/tool khác hoặc gửi key qua chat. Không tự nạp tiền, đổi model hay fallback khi hết ngân sách.

Chạy các migration thông thường của dự án và đảm bảo bảng jobs/cache đã có. Không cần migration mới cho tính năng này.

```bash
php artisan config:clear
php artisan storage:link
php artisan queue:work --queue=marketing-images --timeout=110 --tries=20
php artisan schedule:run
```

Đặt `retry_after` của kết nối queue lớn hơn timeout worker (ví dụ 180 giây); khởi động lại worker sau khi đổi config. Web/worker cần dùng chung cache bền vững và storage. Không dùng cache array hoặc queue sync. Scheduler production phải chạy mỗi phút theo hướng dẫn Laravel. Ảnh thử private được dọn sau 2 ngày; cache yêu cầu hết hạn sau 24 giờ. Ảnh bìa đã chọn được giữ ở public như các ảnh bài viết thông thường.

## Không có key

Có thể chủ động chọn `POLLINATIONS_MODE=classic` để thử endpoint Classic không key. Bản này bị giới hạn theo IP, có logo, giới hạn kích thước và best-effort. Ứng dụng cách lượt tối thiểu 50 giây trong Classic và chỉ chạy một job ảnh tại một thời điểm. Giới hạn từ dịch vụ vẫn có thể chặt hơn. Không giả định đây là API miễn phí không giới hạn, không dùng nhiều key/IP để né giới hạn.

## Thao tác

Admin → Bài viết → Soạn/sửa bài → Ảnh đại diện → **Tạo ảnh xe thử bằng Pollinations**.

Nhập tiêu đề, mở khối tạo ảnh, sửa mô tả tiếng Anh theo xe/bối cảnh mong muốn. “Lấy gợi ý từ tiêu đề” chỉ tạo prompt bằng mẫu, **chưa gọi Gemini**. Không nhập dữ liệu khách hoặc nội dung nội bộ. Pollinations nhận prompt sau khi bấm tạo. Chỉ người tạo xem được preview qua endpoint có xác thực và quyền biên tập. Tính năng mặc định tắt khi chưa cấu hình.

Khi ảnh sẵn sàng, kiểm tra model xe, bánh, gương, logo và phối cảnh; ảnh AI có thể sai. “Dùng làm ảnh bìa” tạo file công khai với UUID và đưa URL vào form; chưa lưu/xuất bản bài. Lưu/duyệt bài theo luồng sẵn có. Không có hành động đăng Facebook hay cập nhật ảnh xe thật.

Để không có người trong ảnh, prompt bổ sung ràng buộc không người/khuôn mặt, nhưng model vẫn có thể không tuân thủ. Không dùng ảnh sinh như bằng chứng xe thật có đúng màu/trang bị. Muốn giữ chính xác xe cần pipeline ghép xe thật, nằm ngoài bản thử này.

## Kiểm tra

```bash
php artisan test --filter=PollinationsImageServiceTest
```

Test dùng HTTP fake, không tiêu Pollen. Cần test triển khai riêng: quyền người khác xem UUID nhận 404, worker offline, ảnh ready, áp dụng ảnh, lưu nháp và public vẫn giữ ảnh cũ tới khi duyệt. Browser và API upstream cần cấu hình thực để xác nhận chất lượng/tốc độ; không xem build thành công là đã thử tạo ảnh thật.
