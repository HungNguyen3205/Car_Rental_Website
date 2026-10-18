<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $ownerAdmin;
    protected Admin $managerAdmin;
    protected Admin $staffAdmin1;
    protected Admin $staffAdmin2;
    protected User $customerUser;
    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerAdmin = Admin::create([
            'ho_ten'        => 'Tổng Quản Trị',
            'email'         => 'owner_contact@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000001',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        $this->managerAdmin = Admin::create([
            'ho_ten'        => 'Quản Lý Đội Xe & Đơn',
            'email'         => 'manager_contact@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000002',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'tinh_trang'    => 1,
        ]);

        $this->staffAdmin1 = Admin::create([
            'ho_ten'        => 'Chuyên Viên Tư Vấn 1',
            'email'         => 'staff1_contact@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000003',
            'role'          => 'customer_support',
            'permissions'   => [PermissionMatrix::PERM_BOOKINGS_VIEW],
            'tinh_trang'    => 1,
        ]);

        $this->staffAdmin2 = Admin::create([
            'ho_ten'        => 'Chuyên Viên Tư Vấn 2',
            'email'         => 'staff2_contact@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000004',
            'role'          => 'customer_support',
            'permissions'   => [PermissionMatrix::PERM_BOOKINGS_VIEW],
            'tinh_trang'    => 1,
        ]);

        $this->customerUser = User::create([
            'ho_ten'        => 'Phạm Văn Khách',
            'email'         => 'phamkhach@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0988776655',
            'dia_chi'       => 'Hà Nội',
            'trang_thai'    => 1,
        ]);

        $cat = CarCategory::create(['name' => 'SUV Điện Cao Cấp', 'is_active' => true]);
        $this->car = Car::create([
            'name'               => 'VinFast VF9 Plus Blue',
            'category_id'        => $cat->id,
            'license_plate'      => '29A-888.99',
            'price_per_day'      => 2200000,
            'status'             => 'available',
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
        ]);
    }

    /**
     * Test 1: Lọc yêu cầu tư vấn theo trạng thái, người phụ trách, khoảng ngày và từ khóa
     */
    public function test_admin_can_filter_contacts_by_status_assignee_date_and_search(): void
    {
        // Tạo 3 contact với metadata khác nhau
        $c1 = Contact::create([
            'ho_ten'        => 'Trần Đăng Khoa',
            'email'         => 'khoa@test.com',
            'so_dien_thoai' => '0911223344',
            'tieu_de'       => 'Thuê xe VinFast VF9 tự lái công tác',
            'noi_dung'      => 'Cần thuê 3 ngày cuối tuần',
            'trang_thai'    => Contact::STATUS_IN_PROGRESS,
            'assigned_to'   => $this->staffAdmin1->id,
            'created_at'    => Carbon::now()->subDays(2),
        ]);

        $c2 = Contact::create([
            'ho_ten'        => 'Lê Thị Thu',
            'email'         => 'thu@test.com',
            'so_dien_thoai' => '0922334455',
            'tieu_de'       => 'Hỏi thủ tục cọc xe cho doanh nghiệp',
            'noi_dung'      => 'Công ty nước ngoài muốn thuê xe dài hạn',
            'trang_thai'    => Contact::STATUS_PENDING,
            'assigned_to'   => null,
            'created_at'    => Carbon::now()->subDays(5),
        ]);

        $c3 = Contact::create([
            'ho_ten'        => 'Ngô Bảo Châu',
            'email'         => 'chau@test.com',
            'so_dien_thoai' => '0933445566',
            'tieu_de'       => 'Phản hồi dịch vụ giao xe',
            'noi_dung'      => 'Rất hài lòng với nhân viên giao xe',
            'trang_thai'    => Contact::STATUS_RESOLVED,
            'assigned_to'   => $this->staffAdmin2->id,
            'created_at'    => Carbon::now(),
        ]);

        // 1. Lọc theo trạng thái Đang xử lý (1)
        $resStatus = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson('/api/admin/contacts?trang_thai=1');
        $resStatus->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $c1->id);

        // 2. Lọc theo người phụ trách (staffAdmin1)
        $resAssignee = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson("/api/admin/contacts?assigned_to={$this->staffAdmin1->id}");
        $resAssignee->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.ho_ten', 'Trần Đăng Khoa');

        // 3. Lọc chưa phân công (unassigned)
        $resUnassigned = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson('/api/admin/contacts?assigned_to=unassigned');
        $resUnassigned->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $c2->id);

        // 4. Tìm kiếm từ khóa 'VinFast VF9'
        $resSearch = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson('/api/admin/contacts?search=VinFast');
        $resSearch->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $c1->id);
    }

    /**
     * Test 2: Phân công và chuyển giao nhân viên phụ trách, cập nhật dòng thời gian và bắn thông báo in-app
     */
    public function test_assign_and_reassign_staff_updates_activities_and_dispatches_in_app_notification(): void
    {
        $contact = Contact::create([
            'user_id'       => $this->customerUser->id,
            'ho_ten'        => $this->customerUser->ho_ten,
            'email'         => $this->customerUser->email,
            'so_dien_thoai' => $this->customerUser->so_dien_thoai,
            'tieu_de'       => 'Cần tư vấn đặt xe VF9',
            'noi_dung'      => 'Xin tư vấn giá và chính sách giao xe tận nơi',
            'trang_thai'    => Contact::STATUS_PENDING,
        ]);

        // 1. Quản lý phân công cho Chuyên viên 1
        $assignRes = $this->actingAs($this->managerAdmin, 'admin')
            ->postJson("/api/admin/contacts/{$contact->id}/assign", [
                'assigned_to' => $this->staffAdmin1->id,
                'note'        => 'Giao cho staff 1 gọi điện tư vấn sớm cho khách.',
            ]);

        $assignRes->assertStatus(200)
            ->assertJsonPath('data.assigned_to', $this->staffAdmin1->id)
            ->assertJsonPath('data.trang_thai', Contact::STATUS_IN_PROGRESS);

        $this->assertDatabaseHas('contacts', [
            'id'          => $contact->id,
            'assigned_to' => $this->staffAdmin1->id,
            'trang_thai'  => Contact::STATUS_IN_PROGRESS,
            'updated_by'  => $this->managerAdmin->id,
        ]);

        // Kiểm tra lịch sử hoạt động được ghi nhận
        $this->assertDatabaseHas('contact_activities', [
            'contact_id' => $contact->id,
            'type'       => ContactActivity::TYPE_ASSIGNED,
            'admin_id'   => $this->managerAdmin->id,
        ]);

        // Đảm bảo thông báo in-app được gửi tới khách hàng
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->customerUser->id,
            'type'    => 'contact_update',
        ]);

        // 2. Chuyển giao sang Chuyên viên 2 (Reassign)
        $reassignRes = $this->actingAs($this->managerAdmin, 'admin')
            ->postJson("/api/admin/contacts/{$contact->id}/assign", [
                'assigned_to' => $this->staffAdmin2->id,
                'note'        => 'Chuyển giao do staff 1 đi công tác.',
            ]);

        $reassignRes->assertStatus(200)
            ->assertJsonPath('data.assigned_to', $this->staffAdmin2->id);

        $this->assertDatabaseHas('contact_activities', [
            'contact_id' => $contact->id,
            'type'       => ContactActivity::TYPE_REASSIGNED,
        ]);
    }

    /**
     * Test 3: Cập nhật trạng thái và thêm ghi chú nội bộ bảo toàn nhiều bản ghi trong dòng thời gian
     */
    public function test_update_status_and_add_note_preserves_multi_entry_history(): void
    {
        $contact = Contact::create([
            'ho_ten'        => 'Nguyễn Minh Quân',
            'email'         => 'quan@test.com',
            'tieu_de'       => 'Tư vấn cọc xe',
            'noi_dung'      => 'Cần biết số tiền cọc',
            'trang_thai'    => Contact::STATUS_PENDING,
            'assigned_to'   => $this->staffAdmin1->id,
        ]);

        // 1. Thêm ghi chú lần 1
        $note1Res = $this->actingAs($this->staffAdmin1, 'admin')
            ->postJson("/api/admin/contacts/{$contact->id}/notes", [
                'content' => 'Lần 1: Đã gọi điện nhưng khách bận, hẹn gọi lại lúc 14h.',
            ]);
        $note1Res->assertStatus(200);

        // 2. Thêm ghi chú lần 2 kèm cập nhật trạng thái sang Đã giải quyết
        $statusRes = $this->actingAs($this->staffAdmin1, 'admin')
            ->putJson("/api/admin/contacts/{$contact->id}/status", [
                'trang_thai'     => Contact::STATUS_RESOLVED,
                'ghi_chu_noi_bo' => 'Lần 2: Khách đã hiểu chính sách cọc và đồng ý gửi hồ sơ.',
            ]);
        $statusRes->assertStatus(200)
            ->assertJsonPath('data.trang_thai', Contact::STATUS_RESOLVED)
            ->assertJsonPath('data.completed_by', $this->staffAdmin1->id);

        $this->assertNotNull($statusRes->json('data.completed_at'));

        // Kiểm tra có ít nhất 3 bản ghi lịch sử hoạt động (1 note trực tiếp, 1 chuyển trạng thái, 1 note kèm cập nhật)
        $this->assertDatabaseCount('contact_activities', 3);
        $this->assertEquals(3, $contact->activities()->count());
    }

    /**
     * Test 4: Chống xung đột sửa đổi đồng thời khi hai nhân viên cùng mở màn hình (409 Conflict)
     */
    public function test_concurrency_conflict_when_two_admins_update_simultaneously(): void
    {
        $contact = Contact::create([
            'ho_ten'     => 'Hoàng Văn Bách',
            'email'      => 'bach@test.com',
            'tieu_de'    => 'Hỏi thuê xe cưới',
            'noi_dung'   => 'Cần thuê đoàn xe VF9 ngày 20/11',
            'trang_thai' => Contact::STATUS_PENDING,
        ]);

        $initialUpdatedAt = $contact->updated_at->toISOString();

        // Thời gian trôi đi 5 giây khi Admin 1 cập nhật
        $this->travel(5)->seconds();

        // Admin 1 cập nhật thành công trước
        $this->actingAs($this->staffAdmin1, 'admin')
            ->putJson("/api/admin/contacts/{$contact->id}/status", [
                'trang_thai'     => Contact::STATUS_IN_PROGRESS,
                'ghi_chu_noi_bo' => 'Admin 1 nhận cuộc gọi tư vấn',
            ]);

        // Admin 2 gửi request với timestamp cũ (chưa refresh màn hình) -> Bị chặn 409
        $conflictRes = $this->actingAs($this->staffAdmin2, 'admin')
            ->putJson("/api/admin/contacts/{$contact->id}/status", [
                'trang_thai'          => Contact::STATUS_RESOLVED,
                'expected_updated_at' => $initialUpdatedAt,
            ]);

        $conflictRes->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_code' => 'CONCURRENCY_CONFLICT',
            ]);
    }

    /**
     * Test 5: Chống tranh chấp nhận việc khi hai nhân viên cùng bấm nhận một yêu cầu chưa phân công
     */
    public function test_race_condition_protection_when_claiming_already_assigned_contact(): void
    {
        $contact = Contact::create([
            'ho_ten'      => 'Vũ Đình Trọng',
            'email'       => 'trong@test.com',
            'tieu_de'     => 'Cần thuê xe tự lái gấp',
            'noi_dung'    => 'Cần nhận xe trong 1 tiếng tới',
            'trang_thai'  => Contact::STATUS_PENDING,
            'assigned_to' => null,
        ]);

        // Admin 1 nhận việc thành công
        $claim1 = $this->actingAs($this->staffAdmin1, 'admin')
            ->postJson("/api/admin/contacts/{$contact->id}/assign", [
                'claim_action' => true,
            ]);
        $claim1->assertStatus(200);

        // Admin 2 bấm nhận cùng một lúc khi contact đã được Admin 1 gán -> Bị chặn 409
        $claim2 = $this->actingAs($this->staffAdmin2, 'admin')
            ->postJson("/api/admin/contacts/{$contact->id}/assign", [
                'claim_action' => true,
            ]);

        $claim2->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_code' => 'ALREADY_ASSIGNED',
            ]);
    }

    /**
     * Test 6: Phân quyền xem: Quản lý xem toàn bộ; Nhân viên thường chỉ xem việc được giao
     */
    public function test_role_based_visibility_manager_sees_all_vs_restricted_staff_sees_assigned_only(): void
    {
        // 1 contact giao cho staff 1
        $cStaff1 = Contact::create([
            'ho_ten'      => 'Khách của Staff 1',
            'email'       => 'k1@test.com',
            'tieu_de'     => 'Tư vấn 1',
            'noi_dung'    => 'Nội dung 1',
            'assigned_to' => $this->staffAdmin1->id,
        ]);

        // 1 contact giao cho staff 2
        $cStaff2 = Contact::create([
            'ho_ten'      => 'Khách của Staff 2',
            'email'       => 'k2@test.com',
            'tieu_de'     => 'Tư vấn 2',
            'noi_dung'    => 'Nội dung 2',
            'assigned_to' => $this->staffAdmin2->id,
        ]);

        // 1. Quản lý (Owner/Dispatcher) xem danh sách -> Thấy cả 2
        $managerRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson('/api/admin/contacts');
        $managerRes->assertStatus(200)
            ->assertJsonPath('counts.all', 2);

        // 2. Staff 1 chỉ có bookings.view xem danh sách -> Chỉ thấy contact của mình (và chưa phân công)
        $staff1Res = $this->actingAs($this->staffAdmin1, 'admin')
            ->getJson('/api/admin/contacts');
        $staff1Res->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $cStaff1->id);

        // 3. Staff 1 cố tình mở xem chi tiết contact của Staff 2 -> Bị chặn 403
        $forbiddenRes = $this->actingAs($this->staffAdmin1, 'admin')
            ->getJson("/api/admin/contacts/{$cStaff2->id}");
        $forbiddenRes->assertStatus(403);
    }

    /**
     * Test 7: Liên kết tự động khách hàng và xe quan tâm
     */
    public function test_contextual_linking_of_customer_and_car_of_interest(): void
    {
        // Khách hàng chưa đăng nhập gửi form tư vấn kèm email trùng với tài khoản trong DB và car_id
        $payload = [
            'ho_ten'        => 'Phạm Văn Khách',
            'email'         => 'phamkhach@test.com', // Trùng customerUser
            'so_dien_thoai' => '0988776655',
            'tieu_de'       => 'Muốn thuê thử chiếc VF9 này',
            'noi_dung'      => 'Báo giá cho tôi 2 ngày',
            'car_id'        => $this->car->id,
        ];

        $res = $this->postJson('/api/contact', $payload);
        $res->assertStatus(201);

        $contact = Contact::where('email', 'phamkhach@test.com')->first();
        $this->assertNotNull($contact);

        // Đảm bảo tự động liên kết đúng user_id và car_id
        $this->assertEquals($this->customerUser->id, $contact->user_id);
        $this->assertEquals($this->car->id, $contact->car_id);
    }
}
