<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->string('group')->default('general'); // general, bank, policy, contact
            $table->boolean('is_public')->default(true);
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        // Seed default system configurations
        $defaultSettings = [
            [
                'key'         => 'site_name',
                'value'       => 'DriveRent',
                'type'        => 'string',
                'group'       => 'general',
                'is_public'   => true,
                'description' => 'Tên thương hiệu nền tảng',
            ],
            [
                'key'         => 'site_logo',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'is_public'   => true,
                'description' => 'URL logo hiển thị trên hệ thống',
            ],
            [
                'key'         => 'hotline',
                'value'       => '1900 1234',
                'type'        => 'string',
                'group'       => 'contact',
                'is_public'   => true,
                'description' => 'Số hotline tổng đài tư vấn & hỗ trợ',
            ],
            [
                'key'         => 'support_email',
                'value'       => 'support@driverentals.com',
                'type'        => 'string',
                'group'       => 'contact',
                'is_public'   => true,
                'description' => 'Email tiếp nhận khiếu nại và phản ánh',
            ],
            [
                'key'         => 'company_address',
                'value'       => 'Tòa nhà Bitexco, Q.1, TP. HCM',
                'type'        => 'string',
                'group'       => 'contact',
                'is_public'   => true,
                'description' => 'Trụ sở chính của doanh nghiệp',
            ],
            [
                'key'         => 'service_locations',
                'value'       => json_encode(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'], JSON_UNESCAPED_UNICODE),
                'type'        => 'json',
                'group'       => 'general',
                'is_public'   => true,
                'description' => 'Danh sách các tỉnh/thành phố và trạm phục vụ',
            ],
            [
                'key'         => 'bank_name',
                'value'       => 'MBBank (Ngân hàng TMCP Quân Đội)',
                'type'        => 'string',
                'group'       => 'bank',
                'is_public'   => true,
                'description' => 'Tên ngân hàng thụ hưởng',
            ],
            [
                'key'         => 'bank_account_number',
                'value'       => '0971120038',
                'type'        => 'string',
                'group'       => 'bank',
                'is_public'   => true,
                'description' => 'Số tài khoản ngân hàng thụ hưởng',
            ],
            [
                'key'         => 'bank_account_holder',
                'value'       => 'NAM HUNG RENTAL',
                'type'        => 'string',
                'group'       => 'bank',
                'is_public'   => true,
                'description' => 'Chủ tài khoản ngân hàng',
            ],
            [
                'key'         => 'bank_bin',
                'value'       => '970422',
                'type'        => 'string',
                'group'       => 'bank',
                'is_public'   => true,
                'description' => 'Mã BIN ngân hàng VietQR',
            ],
            [
                'key'         => 'bank_transfer_instruction',
                'value'       => 'Quét mã VietQR hoặc chuyển khoản với nội dung là Mã đơn hàng để kế toán đối soát nhanh nhất.',
                'type'        => 'string',
                'group'       => 'bank',
                'is_public'   => true,
                'description' => 'Hướng dẫn chuyển khoản cho khách',
            ],
            [
                'key'         => 'rental_policy',
                'value'       => json_encode([
                    'deposit_policy' => 'Đặt cọc bảo đảm khi nhận xe. Hoàn lại toàn bộ sau 24h đối soát trả xe.',
                    'document_requirements' => 'CCCD gắn chip (hoặc Hộ chiếu còn hạn) + GPLX B2 trở lên còn hiệu lực.',
                    'rental_time_rules' => 'Tính theo ngày (24 giờ). Bàn giao và trả xe theo biên bản kiểm tra thực tế.',
                    'vehicle_rules' => 'Không hút thuốc lá trong xe, không vận chuyển hàng cấm hoặc chất gây mùi.',
                ], JSON_UNESCAPED_UNICODE),
                'type'        => 'json',
                'group'       => 'policy',
                'is_public'   => true,
                'description' => 'Chính sách và điều khoản thuê xe',
            ],
        ];

        $now = now();
        foreach ($defaultSettings as &$item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }

        DB::table('system_settings')->insert($defaultSettings);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
