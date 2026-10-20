<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $newSettings = [
            // Nhóm Vận hành (Operations)
            [
                'key'         => 'rental_buffer_hours',
                'value'       => '2',
                'type'        => 'integer',
                'group'       => 'operations',
                'is_public'   => true,
                'description' => 'Thời gian đệm vệ sinh & kiểm định giữa hai lượt thuê liên tiếp (giờ)',
            ],
            [
                'key'         => 'booking_auto_cancel_unpaid_hours',
                'value'       => '24',
                'type'        => 'integer',
                'group'       => 'operations',
                'is_public'   => true,
                'description' => 'Thời hạn giữ chỗ chờ chuyển khoản cọc trước khi đơn tự động hủy (giờ)',
            ],

            // Nhóm SEO & Meta
            [
                'key'         => 'seo_meta_title_suffix',
                'value'       => ' - Dịch Vụ Thuê Xe Điện VinFast Uy Tín',
                'type'        => 'string',
                'group'       => 'seo',
                'is_public'   => true,
                'description' => 'Hậu tố tiêu đề trang (Title Suffix) phục vụ tối ưu hóa tìm kiếm',
            ],
            [
                'key'         => 'seo_meta_description',
                'value'       => 'DriveRentals - Dịch vụ cho thuê xe điện tự lái VinFast VF3, VF5, VF8, VF9 thông minh, giá niêm yết minh bạch, giao xe tận nơi 24/7.',
                'type'        => 'string',
                'group'       => 'seo',
                'is_public'   => true,
                'description' => 'Thẻ meta description mặc định cho công cụ tìm kiếm',
            ],
            [
                'key'         => 'seo_og_image',
                'value'       => '/assets/images/bento_main.jpg',
                'type'        => 'string',
                'group'       => 'seo',
                'is_public'   => true,
                'description' => 'Hình ảnh OpenGraph xem trước khi chia sẻ mạng xã hội',
            ],

            // Nhóm Bản đồ (Map)
            [
                'key'         => 'map_provider',
                'value'       => 'google_maps',
                'type'        => 'string',
                'group'       => 'map',
                'is_public'   => true,
                'description' => 'Nhà cung cấp bản đồ nhúng (google_maps hoặc openstreetmap)',
            ],
            [
                'key'         => 'map_embed_url',
                'value'       => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.5178265431627!2d106.70188237586828!3d10.771594959281696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f41639d679f%3A0x8e5f2a0833923a19!2sBitexco%20Financial%20Tower!5e0!3m2!1sen!2svn!4v1710000000000!5m2!1sen!2svn',
                'type'        => 'string',
                'group'       => 'map',
                'is_public'   => true,
                'description' => 'Đường dẫn URL nhúng bản đồ trụ sở chính (đã qua kiểm duyệt bảo mật)',
            ],
            [
                'key'         => 'map_coordinates',
                'value'       => '10.771595, 106.704457',
                'type'        => 'string',
                'group'       => 'map',
                'is_public'   => true,
                'description' => 'Tọa độ GPS trụ sở (Vĩ độ, Kinh độ)',
            ],

            // Nhóm Chuyển động (Animation)
            [
                'key'         => 'animation_mode',
                'value'       => 'standard',
                'type'        => 'string',
                'group'       => 'animation',
                'is_public'   => true,
                'description' => 'Mức độ chuyển động giao diện: standard (tiêu chuẩn), reduced (giảm thiểu), off (tắt)',
            ],
            [
                'key'         => 'animation_duration_scale',
                'value'       => '1.0',
                'type'        => 'string',
                'group'       => 'animation',
                'is_public'   => true,
                'description' => 'Hệ số điều chỉnh thời lượng hiệu ứng (0.5x, 1.0x, 1.5x)',
            ],

            // Nhóm Thông báo (Notifications)
            [
                'key'         => 'notification_email_alerts',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'is_public'   => false, // Bí mật nội bộ, không công khai
                'description' => 'Gửi email tự động khi có yêu cầu đặt xe hoặc liên hệ mới',
            ],
            [
                'key'         => 'notification_admin_recipient_email',
                'value'       => 'admin@driverentals.com',
                'type'        => 'string',
                'group'       => 'notifications',
                'is_public'   => false, // Không trả ra public API
                'description' => 'Email tiếp nhận cảnh báo vận hành nội bộ',
            ],
            [
                'key'         => 'notification_sound_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'is_public'   => true,
                'description' => 'Bật âm báo khi có thông báo hệ thống',
            ],
        ];

        foreach ($newSettings as $item) {
            $exists = DB::table('system_settings')->where('key', $item['key'])->exists();
            if (!$exists) {
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
                DB::table('system_settings')->insert($item);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'rental_buffer_hours',
            'booking_auto_cancel_unpaid_hours',
            'seo_meta_title_suffix',
            'seo_meta_description',
            'seo_og_image',
            'map_provider',
            'map_embed_url',
            'map_coordinates',
            'animation_mode',
            'animation_duration_scale',
            'notification_email_alerts',
            'notification_admin_recipient_email',
            'notification_sound_enabled',
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
