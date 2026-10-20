<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Contact;
use App\Models\User;
use App\Services\Booking\BookingLifecycleService;
use App\Services\Notification\NotificationService;
use App\Services\Payment\BookingPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Admin $admin;
    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $category = CarCategory::create([
            'name'       => 'Sedan Điện',
            'is_active'  => 1,
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF8 Luxury',
            'category_id'   => $category->id,
            'price_per_day' => 1200000,
            'status'        => 'available',
            'city'          => 'TP. Hồ Chí Minh',
        ]);

        $this->user = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'khach@test.com',
            'password'      => bcrypt('password123'),
            'so_dien_thoai' => '0912345678',
            'tinh_trang'    => 1,
        ]);

        $this->otherUser = User::create([
            'ho_ten'        => 'Trần Thị Người Lạ',
            'email'         => 'nguoila@test.com',
            'password'      => bcrypt('password123'),
            'so_dien_thoai' => '0987654321',
            'tinh_trang'    => 1,
        ]);

        $this->admin = Admin::create([
            'ho_ten'     => 'Chủ Hệ Thống',
            'email'      => 'admin_owner@test.com',
            'password'   => bcrypt('Admin@123456'),
            'role'       => 'owner',
            'tinh_trang' => 1,
        ]);
    }

    /**
     * 1. Test lưu liên hệ thật vào database với đầy đủ thông tin cần thiết
     */
    public function test_contact_form_validates_and_stores_in_database(): void
    {
        $payload = [
            'ho_ten'        => 'Lê Hoàng Minh',
            'email'         => 'minh.le@example.com',
            'so_dien_thoai' => '0901234567',
            'tieu_de'       => 'Tư vấn thuê xe VinFast VF8 tự lái cuối tuần',
            'noi_dung'      => 'Tôi muốn thuê xe VinFast VF8 từ sáng thứ 7 đến chiều chủ nhật tuần này tại Q1 TP.HCM, xin tư vấn thủ tục đặt cọc.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 1,
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'ho_ten',
                    'email',
                    'tieu_de',
                    'trang_thai',
                ],
            ]);

        $this->assertDatabaseHas('contacts', [
            'email'      => 'minh.le@example.com',
            'ho_ten'     => 'Lê Hoàng Minh',
            'tieu_de'    => 'Tư vấn thuê xe VinFast VF8 tự lái cuối tuần',
            'trang_thai' => Contact::STATUS_PENDING,
        ]);
    }

    /**
     * 2. Test chống spam / Rate limit cho form liên hệ
     */
    public function test_contact_form_spam_protection_rate_limit(): void
    {
        RateLimiter::clear('contact_submit:127.0.0.1');

        $payload = [
            'ho_ten'   => 'Spammer Test',
            'email'    => 'spam@test.com',
            'tieu_de'  => 'Tin nhắn spam lặp lại',
            'noi_dung' => 'Nội dung spam thử nghiệm liên tục trong thời gian ngắn.',
        ];

        // Gửi 5 lần hợp lệ (ngưỡng tối đa 5/phút)
        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/contact', $payload, ['REMOTE_ADDR' => '127.0.0.1']);
            $res->assertStatus(201);
        }

        // Lần thứ 6 phải bị chặn HTTP 429
        $blockedResponse = $this->postJson('/api/contact', $payload, ['REMOTE_ADDR' => '127.0.0.1']);
        $blockedResponse->assertStatus(429)
            ->assertJson([
                'status' => 0,
            ]);

        RateLimiter::clear('contact_submit:127.0.0.1');
    }

    /**
     * 2b. Test chặn bot spam tự động điền trường honeypot (website_hp)
     */
    public function test_contact_form_blocks_honeypot_bot_submission(): void
    {
        $payload = [
            'ho_ten'     => 'Spam Bot',
            'email'      => 'bot@spam.com',
            'tieu_de'    => 'Mua bán dịch vụ quảng cáo',
            'noi_dung'   => 'Nội dung spam quảng cáo tự động điền toàn bộ trường',
            'website_hp' => 'http://spam-link.example.com',
        ];

        $response = $this->postJson('/api/contact', $payload);
        $response->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);

        $this->assertDatabaseMissing('contacts', [
            'email' => 'bot@spam.com',
        ]);
    }

    /**
     * 2c. Test lưu thông tin xe quan tâm và ngày dự kiến thuê vào cơ sở dữ liệu
     */
    public function test_contact_form_stores_car_and_rental_date(): void
    {
        $payload = [
            'ho_ten'        => 'Hoàng Nhật Minh',
            'email'         => 'nhatminh@example.com',
            'so_dien_thoai' => '0933445566',
            'car_id'        => $this->car->id,
            'rental_date'   => '2026-11-15',
            'source'        => 'Trang Liên hệ',
            'tieu_de'       => 'Tư vấn thuê xe VF8 đi công tác',
            'noi_dung'      => 'Xin báo giá trọn gói 3 ngày có tài xế hoặc tự lái.',
        ];

        $response = $this->postJson('/api/contact', $payload);
        $response->assertStatus(201)
            ->assertJson([
                'status' => 1,
            ]);

        $this->assertDatabaseHas('contacts', [
            'email'   => 'nhatminh@example.com',
            'car_id'  => $this->car->id,
            'tieu_de' => 'Tư vấn thuê xe VF8 đi công tác',
        ]);

        $contact = Contact::where('email', 'nhatminh@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertStringContainsString('15/11/2026', $contact->noi_dung);
        $this->assertStringContainsString('Trang Liên hệ', $contact->noi_dung);
    }

    /**
     * 3. Test quyền đọc và cập nhật trạng thái liên hệ của Admin
     */
    public function test_admin_contact_authorization_and_status_update(): void
    {
        $contact = Contact::create([
            'ho_ten'     => 'Khách Hàng Hỏi Thuê',
            'email'      => 'hoi.thue@test.com',
            'tieu_de'    => 'Cần thuê xe tháng',
            'noi_dung'   => 'Công ty tôi muốn thuê 2 xe VF8 theo tháng.',
            'trang_thai' => Contact::STATUS_PENDING,
        ]);

        // Khách thường không có quyền truy cập API quản trị contacts (Bị chặn 401 hoặc 403)
        $unauthRes = $this->actingAs($this->user, 'sanctum')->getJson('/api/admin/contacts');
        $this->assertTrue(in_array($unauthRes->status(), [401, 403], true));

        // Admin có quyền xem danh sách
        $adminRes = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/contacts');
        $adminRes->assertStatus(200)
            ->assertJson(['status' => 1]);

        // Admin cập nhật trạng thái và ghi chú nội bộ
        $updateRes = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/contacts/{$contact->id}/status", [
            'trang_thai'     => Contact::STATUS_IN_PROGRESS,
            'ghi_chu_noi_bo' => 'Đã gọi điện tư vấn và gửi báo giá qua email cho khách.',
        ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => [
                    'trang_thai'     => Contact::STATUS_IN_PROGRESS,
                    'ghi_chu_noi_bo' => 'Đã gọi điện tư vấn và gửi báo giá qua email cho khách.',
                    'handled_by'     => $this->admin->id,
                ],
            ]);

        $this->assertDatabaseHas('contacts', [
            'id'             => $contact->id,
            'trang_thai'     => Contact::STATUS_IN_PROGRESS,
            'handled_by'     => $this->admin->id,
            'ghi_chu_noi_bo' => 'Đã gọi điện tư vấn và gửi báo giá qua email cho khách.',
        ]);

        // Đảm bảo Audit Log được tạo
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'contact.status_updated',
            'target_type' => 'Contact',
            'target_id'   => (string) $contact->id,
        ]);
    }

    /**
     * 4. Test đọc thông báo: Người dùng chỉ xem thông báo của mình, có chưa đọc/đã đọc
     */
    public function test_user_notification_access_and_mark_as_read(): void
    {
        // Tạo thông báo cho user chính
        $notif1 = AppNotification::create([
            'user_id' => $this->user->id,
            'type'    => AppNotification::TYPE_BOOKING_CREATED,
            'title'   => 'Đơn đặt xe mới',
            'content' => 'Đơn đặt xe #BK001 đã tạo thành công.',
            'is_read' => false,
        ]);

        $notif2 = AppNotification::create([
            'user_id' => $this->user->id,
            'type'    => AppNotification::TYPE_BOOKING_CONFIRMED,
            'title'   => 'Đơn xe đã được duyệt',
            'content' => 'Đơn đặt xe #BK001 đã được duyệt.',
            'is_read' => false,
        ]);

        // Tạo thông báo cho người khác
        $otherNotif = AppNotification::create([
            'user_id' => $this->otherUser->id,
            'type'    => AppNotification::TYPE_BOOKING_CREATED,
            'title'   => 'Thông báo của người khác',
            'content' => 'Nội dung riêng tư của người khác.',
            'is_read' => false,
        ]);

        // 1. User lấy danh sách thông báo
        $listRes = $this->actingAs($this->user, 'sanctum')->getJson('/api/user/notifications');
        $listRes->assertStatus(200)
            ->assertJson([
                'status'       => 1,
                'unread_count' => 2,
            ]);

        // Xác nhận không nhìn thấy thông báo của người khác
        $returnedIds = collect($listRes->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($notif1->id, $returnedIds);
        $this->assertContains($notif2->id, $returnedIds);
        $this->assertNotContains($otherNotif->id, $returnedIds);

        // 2. User cố tình đánh dấu thông báo của người khác => Bị chặn 403
        $hackRes = $this->actingAs($this->user, 'sanctum')->putJson("/api/user/notifications/{$otherNotif->id}/read");
        $hackRes->assertStatus(403);

        // 3. User đánh dấu thông báo của chính mình là đã đọc
        $readRes = $this->actingAs($this->user, 'sanctum')->putJson("/api/user/notifications/{$notif1->id}/read");
        $readRes->assertStatus(200)
            ->assertJson([
                'status'       => 1,
                'unread_count' => 1,
            ]);

        $this->assertTrue($notif1->fresh()->is_read);
        $this->assertNotNull($notif1->fresh()->read_at);

        // 4. Đánh dấu tất cả đã đọc
        $readAllRes = $this->actingAs($this->user, 'sanctum')->putJson('/api/user/notifications/read-all');
        $readAllRes->assertStatus(200)
            ->assertJson([
                'status'       => 1,
                'unread_count' => 0,
            ]);

        $this->assertTrue($notif2->fresh()->is_read);
        $this->assertEquals(0, AppNotification::where('user_id', $this->user->id)->unread()->count());
    }

    /**
     * 5. Test Rollback: Không phát thông báo khi transaction bị rollback
     */
    public function test_no_notification_sent_on_transaction_rollback(): void
    {
        $notificationService = app(NotificationService::class);

        $initialNotificationCount = AppNotification::count();

        try {
            DB::transaction(function () use ($notificationService) {
                $booking = Booking::create([
                    'user_id'       => $this->user->id,
                    'car_id'        => $this->car->id,
                    'start_date'    => Carbon::now()->addDays(2),
                    'end_date'      => Carbon::now()->addDays(4),
                    'tong_tien'     => 2400000,
                    'tinh_trang'    => Booking::STATUS_PENDING,
                    'is_thanh_toan' => 0,
                    'ma_dat_xe'     => 'RB_TEST_' . time(),
                ]);

                // Đăng ký thông báo qua afterCommit
                $notificationService->notifyBookingCreated($booking);

                // Cố ý tung lỗi để ép rollback transaction
                throw new \RuntimeException('Giao dịch lỗi buộc phải rollback dữ liệu!');
            });
        } catch (\RuntimeException $e) {
            // Đã bắt lỗi rollback
        }

        // Đảm bảo không có thông báo nào được sinh ra vì transaction đã rollback
        $this->assertEquals($initialNotificationCount, AppNotification::count());
    }

    /**
     * 6. Test chống nhắc lịch trùng (Deduplication) của Scheduler Command
     */
    public function test_scheduler_reminders_anti_duplicate_deduplication(): void
    {
        $now = Carbon::now();

        // 1. Tạo đơn sắp nhận xe trong 12 tiếng tới (Đã duyệt)
        $pickupBooking = Booking::create([
            'user_id'       => $this->user->id,
            'car_id'        => $this->car->id,
            'start_date'    => $now->copy()->addHours(12),
            'end_date'      => $now->copy()->addDays(2),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'PICKUP_' . time(),
        ]);

        // 2. Tạo đơn sắp trả xe trong 10 tiếng tới (Đang thuê)
        $returnBooking = Booking::create([
            'user_id'       => $this->user->id,
            'car_id'        => $this->car->id,
            'start_date'    => $now->copy()->subDays(2),
            'end_date'      => $now->copy()->addHours(10),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => 1,
            'ma_dat_xe'     => 'RETURN_' . time(),
        ]);

        // Chạy lệnh nhắc lịch lần 1
        Artisan::call('bookings:send-reminders');

        // Xác nhận đã tạo đúng 2 thông báo: 1 nhắc nhận và 1 nhắc trả
        $this->assertDatabaseHas('app_notifications', [
            'booking_id' => $pickupBooking->id,
            'type'       => AppNotification::TYPE_UPCOMING_PICKUP,
            'dedup_key'  => "upcoming_pickup_{$pickupBooking->id}",
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'booking_id' => $returnBooking->id,
            'type'       => AppNotification::TYPE_UPCOMING_RETURN,
            'dedup_key'  => "upcoming_return_{$returnBooking->id}",
        ]);

        $countAfterFirstRun = AppNotification::count();
        $this->assertEquals(2, $countAfterFirstRun);

        // Chạy lệnh nhắc lịch lần 2 ngay sau đó (Scheduler quét định kỳ)
        Artisan::call('bookings:send-reminders');

        // Khóa dedup_key phải ngăn chặn hoàn toàn việc gửi trùng lặp
        $this->assertEquals($countAfterFirstRun, AppNotification::count());
    }

    /**
     * 7. Test thông báo duyệt đơn, hủy đơn và thanh toán được kích hoạt tự động
     */
    public function test_lifecycle_and_payment_events_trigger_notifications(): void
    {
        $lifecycleService = app(BookingLifecycleService::class);
        $paymentService = app(BookingPaymentService::class);

        $booking = Booking::create([
            'user_id'       => $this->user->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(7),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'AUTO_NOTIF_' . time(),
        ]);

        // 1. Duyệt đơn => tạo thông báo BOOKING_CONFIRMED
        $lifecycleService->transition($booking, Booking::STATUS_CONFIRMED, $this->admin);

        $this->assertDatabaseHas('app_notifications', [
            'booking_id' => $booking->id,
            'type'       => AppNotification::TYPE_BOOKING_CONFIRMED,
        ]);

        // 2. Xác nhận thanh toán => tạo thông báo PAYMENT_CONFIRMED
        $paymentService->confirmManualPayment($booking, $this->admin->id);

        $this->assertDatabaseHas('app_notifications', [
            'booking_id' => $booking->id,
            'type'       => AppNotification::TYPE_PAYMENT_CONFIRMED,
        ]);

        // 3. Hủy đơn => tạo thông báo BOOKING_CANCELLED
        $lifecycleService->transition($booking, Booking::STATUS_CANCELLED, $this->admin, [
            'cancel_reason' => 'Khách hàng đổi lịch trình công tác đột xuất',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'booking_id' => $booking->id,
            'type'       => AppNotification::TYPE_BOOKING_CANCELLED,
        ]);
    }
}
