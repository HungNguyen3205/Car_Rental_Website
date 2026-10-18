<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\CustomerNote;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $ownerAdmin;
    protected Admin $staffAdmin1;
    protected Admin $staffAdmin2;
    protected Admin $restrictedAdmin;
    protected User $customer;
    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // 1. Owner Admin (Toàn quyền)
        $this->ownerAdmin = Admin::create([
            'ho_ten'     => 'Owner Admin',
            'email'      => 'owner_apt@example.com',
            'password'   => Hash::make('Password123!'),
            'role'       => PermissionMatrix::ROLE_OWNER,
            'tinh_trang' => 1,
        ]);

        // 2. Staff Admin 1 (Nhân viên tư vấn có quyền bookings.view & bookings.manage)
        $this->staffAdmin1 = Admin::create([
            'ho_ten'      => 'Nguyen Van Tu Van 1',
            'email'       => 'staff1_apt@example.com',
            'password'    => Hash::make('Password123!'),
            'role'        => PermissionMatrix::ROLE_DISPATCHER,
            'permissions' => [
                PermissionMatrix::PERM_BOOKINGS_VIEW,
                PermissionMatrix::PERM_BOOKINGS_MANAGE,
            ],
            'tinh_trang'  => 1,
        ]);

        // 3. Staff Admin 2 (Nhân viên tư vấn 2)
        $this->staffAdmin2 = Admin::create([
            'ho_ten'      => 'Tran Thi Tu Van 2',
            'email'       => 'staff2_apt@example.com',
            'password'    => Hash::make('Password123!'),
            'role'        => PermissionMatrix::ROLE_DISPATCHER,
            'permissions' => [
                PermissionMatrix::PERM_BOOKINGS_VIEW,
                PermissionMatrix::PERM_BOOKINGS_MANAGE,
            ],
            'tinh_trang'  => 1,
        ]);

        // 4. Restricted Admin (Chỉ có quyền cars.manage, không có quyền xem hay sửa đơn/hẹn)
        $this->restrictedAdmin = Admin::create([
            'ho_ten'      => 'Restricted Admin',
            'email'       => 'restricted_apt@example.com',
            'password'    => Hash::make('Password123!'),
            'role'        => 'technician',
            'permissions' => [
                PermissionMatrix::PERM_CARS_MANAGE,
            ],
            'tinh_trang'  => 1,
        ]);

        // 5. Khách hàng
        $this->customer = User::create([
            'ho_ten'            => 'Pham Van Khach',
            'email'             => 'khach_apt@example.com',
            'so_dien_thoai'     => '0988776655',
            'mat_khau'          => Hash::make('Password123!'),
            'tinh_trang'        => 1,
            'nhu_cau_thue'      => User::DEMAND_SELF_DRIVE,
            'ngan_sach_du_kien' => 2000000,
        ]);

        // 6. Danh mục & Xe
        $category = CarCategory::create([
            'name'        => 'SUV Luxury',
            'description' => 'Xe sang',
            'is_active'   => true,
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF9 Plus',
            'license_plate' => '51K-999.88',
            'price_per_day' => 2500000,
            'category_id'   => $category->id,
            'status'        => 'available',
            'city'          => 'Hồ Chí Minh',
            'type'          => 'SUV điện',
        ]);
    }

    /**
     * 1. Kiểm tra phân quyền truy cập và thao tác trên API lịch hẹn.
     */
    public function test_permission_required_to_view_and_manage_appointments(): void
    {
        // Admin không có quyền -> 403 Forbidden
        Sanctum::actingAs($this->restrictedAdmin, ['*'], 'admin');

        $resGet = $this->getJson('/api/admin/appointments');
        $resGet->assertStatus(403);

        $resPost = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Khach Test',
            'customer_phone' => '0912345678',
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom VinFast',
            'start_time'     => '2026-10-25 09:00:00',
            'end_time'       => '2026-10-25 10:00:00',
        ]);
        $resPost->assertStatus(403);

        // Staff có quyền -> Thành công
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $resSuccess = $this->getJson('/api/admin/appointments');
        $resSuccess->assertStatus(200)
            ->assertJsonPath('status', 1);

        $resCalendar = $this->getJson('/api/admin/appointments/calendar?view_type=week');
        $resCalendar->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    /**
     * 2. Kiểm tra phát hiện xung đột lịch nhân viên (Staff Schedule Conflict Rule).
     */
    public function test_create_appointment_and_staff_schedule_conflict_detection(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        // Tạo cuộc hẹn đầu tiên cho Staff 1: 09:00 - 10:00 ngày 25/10/2026
        $res1 = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Le Van A',
            'customer_phone' => '0901234567',
            'customer_email' => 'a@example.com',
            'assigned_to'    => $this->staffAdmin1->id,
            'car_id'         => $this->car->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Tân Bình',
            'start_time'     => '2026-10-25 09:00:00',
            'end_time'       => '2026-10-25 10:00:00',
        ]);

        $res1->assertStatus(201)
            ->assertJsonPath('status', 1);
        $this->assertDatabaseHas('appointments', [
            'customer_name' => 'Le Van A',
            'assigned_to'   => $this->staffAdmin1->id,
            'status'        => Appointment::STATUS_SCHEDULED,
        ]);

        // Cố tình tạo cuộc hẹn thứ 2 trùng khung giờ (09:30 - 10:30) cho cùng Staff 1 -> Phải bị từ chối 422
        $resConflict = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Nguyen Thi B',
            'customer_phone' => '0909876543',
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'consultation',
            'location'       => 'Showroom Tân Bình',
            'start_time'     => '2026-10-25 09:30:00',
            'end_time'       => '2026-10-25 10:30:00',
        ]);

        $resConflict->assertStatus(422)
            ->assertJsonPath('status', 0);
        $this->assertStringContainsString('Xung đột lịch', $resConflict->json('message'));

        // Cùng khung giờ đó nhưng phân công cho Staff 2 -> Phải thành công
        $resStaff2 = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Nguyen Thi B',
            'customer_phone' => '0909876543',
            'assigned_to'    => $this->staffAdmin2->id,
            'meeting_type'   => 'consultation',
            'location'       => 'Showroom Tân Bình',
            'start_time'     => '2026-10-25 09:30:00',
            'end_time'       => '2026-10-25 10:30:00',
        ]);

        $resStaff2->assertStatus(201)
            ->assertJsonPath('status', 1);

        // Kiểm tra thời lượng dưới 15 phút -> Bị từ chối
        $resShort = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Khach Ngan',
            'customer_phone' => '0909876543',
            'assigned_to'    => $this->staffAdmin2->id,
            'meeting_type'   => 'consultation',
            'location'       => 'Showroom Tân Bình',
            'start_time'     => '2026-10-25 11:00:00',
            'end_time'       => '2026-10-25 11:10:00', // chỉ 10 phút
        ]);
        $resShort->assertStatus(422);
    }

    /**
     * 3. Kiểm tra chỉnh sửa / đổi lịch và phân công lại nhân viên.
     */
    public function test_reschedule_and_reassign_staff_with_conflict_and_notification(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $apt = Appointment::create([
            'customer_name'  => 'Tran Van C',
            'customer_phone' => '0911223344',
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => Carbon::parse('2026-10-26 14:00:00'),
            'end_time'       => Carbon::parse('2026-10-26 15:00:00'),
            'status'         => Appointment::STATUS_SCHEDULED,
        ]);

        // Tạo cuộc hẹn khác cho Staff 2 lúc 16:00 - 17:00
        Appointment::create([
            'customer_name'  => 'Nguoi Khac',
            'customer_phone' => '0900000000',
            'assigned_to'    => $this->staffAdmin2->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => Carbon::parse('2026-10-26 16:00:00'),
            'end_time'       => Carbon::parse('2026-10-26 17:00:00'),
            'status'         => Appointment::STATUS_SCHEDULED,
        ]);

        // Đổi lịch hẹn của C sang 16:30 và gán cho Staff 2 -> Phải bị xung đột
        $resConflict = $this->putJson("/api/admin/appointments/{$apt->id}", [
            'customer_name'  => 'Tran Van C',
            'customer_phone' => '0911223344',
            'assigned_to'    => $this->staffAdmin2->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => '2026-10-26 16:30:00',
            'end_time'       => '2026-10-26 17:30:00',
        ]);
        $resConflict->assertStatus(422);

        // Đổi lịch sang khung giờ rảnh (14:30 - 15:30) và gán cho Staff 2 -> Thành công
        $resSuccess = $this->putJson("/api/admin/appointments/{$apt->id}", [
            'customer_name'  => 'Tran Van C',
            'customer_phone' => '0911223344',
            'assigned_to'    => $this->staffAdmin2->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q7',
            'start_time'     => '2026-10-26 14:30:00',
            'end_time'       => '2026-10-26 15:30:00',
        ]);
        $resSuccess->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('appointments', [
            'id'          => $apt->id,
            'assigned_to' => $this->staffAdmin2->id,
            'location'    => 'Showroom Q7',
        ]);

        // Kiểm tra thông báo nội bộ được gửi cho Staff 2
        $this->assertDatabaseHas('admin_notifications', [
            'admin_id' => $this->staffAdmin2->id,
            'type'     => AdminNotification::TYPE_APPOINTMENT_ASSIGNED,
        ]);
    }

    /**
     * 4. Kiểm tra hủy lịch hẹn giải phóng khung giờ cho nhân viên.
     */
    public function test_cancel_appointment_releases_staff_schedule(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $apt = Appointment::create([
            'customer_name'  => 'Do Van D',
            'customer_phone' => '0944556677',
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => Carbon::parse('2026-10-27 10:00:00'),
            'end_time'       => Carbon::parse('2026-10-27 11:00:00'),
            'status'         => Appointment::STATUS_SCHEDULED,
        ]);

        // Hủy lịch hẹn
        $resCancel = $this->postJson("/api/admin/appointments/{$apt->id}/cancel", [
            'cancel_reason' => 'Khách hàng có việc bận gia đình đột xuất',
        ]);

        $resCancel->assertStatus(200)
            ->assertJsonPath('status', 1);

        $apt->refresh();
        $this->assertEquals(Appointment::STATUS_CANCELLED, $apt->status);
        $this->assertEquals('Khách hàng có việc bận gia đình đột xuất', $apt->cancel_reason);
        $this->assertNotNull($apt->cancelled_at);

        // Giờ đây, tạo lịch hẹn mới cho Staff 1 trong chính khung giờ 10:00 - 11:00 ngày 27/10 phải thành công!
        $resNew = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Khach Moi',
            'customer_phone' => '0933221100',
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'consultation',
            'location'       => 'Showroom Q1',
            'start_time'     => '2026-10-27 10:00:00',
            'end_time'       => '2026-10-27 11:00:00',
        ]);

        $resNew->assertStatus(201)
            ->assertJsonPath('status', 1);
    }

    /**
     * 5. Kiểm tra chuyển từ yêu cầu tư vấn (Contact) sang lịch hẹn và giữ liên kết hai chiều.
     */
    public function test_create_appointment_from_contact_and_preserve_linkage(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $contact = Contact::create([
            'ho_ten'        => 'Nguyen Thi Lead',
            'email'         => 'lead@example.com',
            'so_dien_thoai' => '0977889900',
            'tieu_de'       => 'Muốn xem thực tế xe VF9 màu xanh',
            'noi_dung'      => 'Tôi muốn đến showroom xem nội thất và lái thử',
            'trang_thai'    => Contact::STATUS_PENDING,
            'car_id'        => $this->car->id,
        ]);

        // Tạo lịch hẹn từ Contact này
        $res = $this->postJson('/api/admin/appointments', [
            'contact_id'     => $contact->id,
            'customer_name'  => $contact->ho_ten,
            'customer_phone' => $contact->so_dien_thoai,
            'customer_email' => $contact->email,
            'car_id'         => $contact->car_id,
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom VinFast Landmark 81',
            'start_time'     => '2026-10-28 15:00:00',
            'end_time'       => '2026-10-28 16:00:00',
            'notes'          => 'Chuẩn bị xe sạch sẽ và hồ sơ trả góp',
        ]);

        $res->assertStatus(201);
        $aptId = $res->json('data.id');

        // 1. Lịch hẹn lưu contact_id
        $this->assertDatabaseHas('appointments', [
            'id'         => $aptId,
            'contact_id' => $contact->id,
            'car_id'     => $this->car->id,
        ]);

        // 2. Trạng thái Contact tự động chuyển sang Đang xử lý
        $contact->refresh();
        $this->assertEquals(Contact::STATUS_IN_PROGRESS, $contact->trang_thai);

        // 3. ContactActivity ghi nhận lịch sử tạo hẹn
        $this->assertDatabaseHas('contact_activities', [
            'contact_id' => $contact->id,
            'type'       => 'appointment_scheduled',
        ]);
    }

    /**
     * 6. Kiểm tra tạo lịch hẹn từ Hồ sơ khách hàng và hiển thị trên CRM Profile.
     */
    public function test_create_appointment_from_customer_profile(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $res = $this->postJson('/api/admin/appointments', [
            'user_id'        => $this->customer->id,
            'customer_name'  => $this->customer->ho_ten,
            'customer_phone' => $this->customer->so_dien_thoai,
            'customer_email' => $this->customer->email,
            'car_id'         => $this->car->id,
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Tân Bình',
            'start_time'     => '2026-10-29 09:00:00',
            'end_time'       => '2026-10-29 10:00:00',
        ]);

        $res->assertStatus(201);
        $aptId = $res->json('data.id');

        // Nhật ký chăm sóc ghi nhận
        $this->assertDatabaseHas('customer_notes', [
            'user_id' => $this->customer->id,
        ]);

        // Truy vấn hồ sơ khách hàng thấy lịch hẹn này
        $profileRes = $this->getJson("/api/admin/customers/{$this->customer->id}");
        $profileRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $appointmentsInProfile = $profileRes->json('data.appointments');
        $this->assertNotEmpty($appointmentsInProfile);
        $this->assertEquals($aptId, $appointmentsInProfile[0]['id']);
    }

    /**
     * 7. Quy tắc cốt lõi: Một lịch hẹn tư vấn KHÔNG tự tạo hoặc giữ lịch thuê xe (Booking).
     */
    public function test_appointment_does_not_create_or_alter_car_booking_calendar(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $initialBookingsCount = Booking::count();

        // Tạo cuộc hẹn xem xe
        $res = $this->postJson('/api/admin/appointments', [
            'customer_name'  => 'Khach Xem Xe',
            'customer_phone' => '0988001122',
            'car_id'         => $this->car->id,
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => '2026-10-30 10:00:00',
            'end_time'       => '2026-10-30 11:00:00',
        ]);

        $res->assertStatus(201);

        // Số lượng booking phải giữ nguyên tuyệt đối (không sinh booking ảo)
        $this->assertEquals($initialBookingsCount, Booking::count());

        // Tạo một booking thuê xe thật cho chiếc xe này trong khung giờ 2026-10-30 08:00:00 đến 2026-10-30 18:00:00
        Booking::create([
            'user_id'         => $this->customer->id,
            'car_id'          => $this->car->id,
            'ma_dat_xe'       => 'BK-REAL-001',
            'start_date'      => '2026-10-30 08:00:00',
            'end_date'        => '2026-10-30 18:00:00',
            'tong_tien'       => 2500000,
            'tinh_trang'      => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'   => 1,
            'dia_chi_nhan_xe' => 'Showroom Q1',
            'dia_chi_tra_xe'  => 'Showroom Q1',
        ]);

        // Khi truy vấn chi tiết lịch hẹn xem xe, hệ thống gắn cờ cảnh báo mềm car_rental_conflict: true
        // để nhân viên biết xe đang có khách thuê lăn bánh
        $aptDetail = $this->getJson("/api/admin/appointments/" . $res->json('data.id'));
        $aptDetail->assertStatus(200)
            ->assertJsonPath('data.car_rental_conflict', true);
    }

    /**
     * 8. Kiểm tra thông báo nội bộ chống gửi trùng và không gửi SMS/Email thật.
     */
    public function test_internal_notifications_anti_duplicate_and_no_real_emails_or_sms(): void
    {
        Sanctum::actingAs($this->staffAdmin1, ['*'], 'admin');

        $apt = Appointment::create([
            'user_id'        => $this->customer->id,
            'customer_name'  => $this->customer->ho_ten,
            'customer_phone' => $this->customer->so_dien_thoai,
            'assigned_to'    => $this->staffAdmin1->id,
            'meeting_type'   => 'viewing',
            'location'       => 'Showroom Q1',
            'start_time'     => Carbon::now()->addHours(2),
            'end_time'       => Carbon::now()->addHours(3),
            'status'         => Appointment::STATUS_SCHEDULED,
        ]);

        // Kích hoạt nhắc hẹn lần 1
        $resRemind1 = $this->postJson("/api/admin/appointments/{$apt->id}/remind");
        $resRemind1->assertStatus(200);

        $notifCountUser1 = AppNotification::count();
        $notifCountAdmin1 = AdminNotification::count();

        $this->assertGreaterThan(0, $notifCountAdmin1);
        $this->assertGreaterThan(0, $notifCountUser1);

        // Kích hoạt nhắc hẹn lần 2 trong cùng ngày -> Chống gửi trùng lặp nhờ dedup_key
        $resRemind2 = $this->postJson("/api/admin/appointments/{$apt->id}/remind");
        $resRemind2->assertStatus(200);

        $this->assertEquals($notifCountUser1, AppNotification::count());
        $this->assertEquals($notifCountAdmin1, AdminNotification::count());

        // Đảm bảo không có email thật nào được gửi đi
        Mail::assertNothingSent();
    }
}
