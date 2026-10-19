<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 64)->unique();
            $table->string('title', 150);
            $table->json('draft_content');
            $table->json('published_content');
            $table->string('status', 20)->default('published'); // 'published', 'draft'
            $table->boolean('has_unpublished_changes')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('publisher_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('website_content_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('website_contents')->cascadeOnDelete();
            $table->string('section_key', 64)->index();
            $table->unsignedInteger('version');
            $table->json('content');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        // Seed initial baseline version 1
        $now = now();
        $sections = [
            [
                'section_key' => 'home_hero',
                'title'       => 'Trang chủ - Hero Banner',
                'content'     => [
                    'eyebrow'            => 'DRIVERENT / CHỌN HÀNH TRÌNH CỦA BẠN',
                    'title'              => 'XE SANG CHO MỌI HÀNH TRÌNH',
                    'description'        => 'Một lựa chọn phù hợp. Một hành trình đáng nhớ.',
                    'primary_cta_text'   => 'Tìm xe cho chuyến đi',
                    'primary_cta_action' => 'scroll_to_search',
                    'secondary_cta_text' => 'Xem đội xe',
                    'secondary_cta_url'  => '/fleet',
                    'visual_note'        => 'Khám phá xe. Chọn hành trình.',
                ],
            ],
            [
                'section_key' => 'home_rental_steps',
                'title'       => 'Trang chủ - Quy trình 3 bước',
                'content'     => [
                    'eyebrow'     => 'QUY TRÌNH TINH GỌN',
                    'title'       => '3 Bước Đơn Giản Lăn Bánh',
                    'description' => 'Không thủ tục rườm rà, không chờ đợi giấy tờ. Toàn bộ quy trình được số hóa minh bạch từ lúc chọn xe đến khi bàn giao.',
                    'steps'       => [
                        [
                            'step_number' => '01',
                            'icon'        => 'fa-solid fa-car',
                            'title'       => 'Chọn xe & Lịch trình',
                            'desc'        => 'Lựa chọn dòng xe phù hợp nhu cầu, chọn ngày giờ nhận/trả xe và điểm giao linh hoạt tại Hà Nội, TP.HCM hoặc Đà Nẵng.',
                        ],
                        [
                            'step_number' => '02',
                            'icon'        => 'fa-solid fa-file-shield',
                            'title'       => 'Xác nhận & Đặt cọc',
                            'desc'        => 'Kiểm tra biểu phí minh bạch, chuyển khoản an toàn qua mã VietQR và nhận thông báo xác nhận đơn đặt ngay trên ứng dụng.',
                        ],
                        [
                            'step_number' => '03',
                            'icon'        => 'fa-solid fa-key',
                            'title'       => 'Nhận xe & Khởi hành',
                            'desc'        => 'Nhận bàn giao xe tận nơi với biên bản điện tử chi tiết, kiểm tra số km, lượng pin/xăng và an tâm tận hưởng hành trình.',
                        ],
                    ],
                ],
            ],
            [
                'section_key' => 'home_bento',
                'title'       => 'Trang chủ - Bento Grid Đặc quyền',
                'content'     => [
                    'eyebrow'              => 'TIÊU CHUẨN ĐẲNG CẤP',
                    'title'                => 'Trải Nghiệm Khác Biệt Tại DriveRent',
                    'featured_badge'       => '100% Động Cơ Xanh',
                    'featured_title'       => 'Kiến Tạo Hành Trình Thông Minh & Đẳng Cấp',
                    'featured_desc'        => 'Khám phá cảm giác lái êm ái, tĩnh lặng tuyệt đối cùng công nghệ hỗ trợ lái tiên tiến. Mỗi chặng đường đều là một trải nghiệm thư thái trọn vẹn.',
                    'featured_image'       => '/assets/images/bento_main.jpg',
                    'featured_features'    => [
                        'Pin sạc chuẩn hoá',
                        'Cứu hộ 24/7 toàn quốc',
                    ],
                    'card_network_badge'   => 'Mạng Lưới Phục Vụ',
                    'card_network_title'   => 'Phủ Sóng 3 Miền Toàn Quốc',
                    'card_network_desc'    => 'Mạng lưới bàn giao xe linh hoạt tại Hà Nội, Đà Nẵng, TP. Hồ Chí Minh và đón trả tận nơi tại các sân bay lớn: Nội Bài, Tân Sơn Nhất, Đà Nẵng.',
                    'card_policy_badge'    => 'Minh Bạch Tuyệt Đối',
                    'card_policy_title'    => 'Chính Sách Hoàn Cọc Trong 24H',
                    'card_policy_desc'     => 'Bảo đảm hoàn cọc an ninh trong 24 giờ sau khi đối soát biên bản trả xe. Không phụ phí ẩn, không phát sinh ngoài hợp đồng số.',
                    'card_policy_cta_text' => 'Khám phá ngay',
                    'card_policy_cta_url'  => '/fleet',
                ],
            ],
            [
                'section_key' => 'home_faq',
                'title'       => 'Trang chủ - Câu hỏi thường gặp',
                'content'     => [
                    'eyebrow'     => 'GIẢI ĐÁP THẮC MẮC',
                    'title'       => 'Câu Hỏi Thường Gặp',
                    'description' => 'Mọi thông tin cần biết để hành trình của quý khách luôn an tâm và trọn vẹn nhất',
                    'faqs'        => [
                        [
                            'question' => 'Tôi cần chuẩn bị những giấy tờ gì khi nhận xe?',
                            'answer'   => 'Quý khách chỉ cần chuẩn bị Căn cước công dân gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B1/B2 còn hiệu lực để đối chiếu thông tin khi giao nhận xe.',
                        ],
                        [
                            'question' => 'Xe có được bàn giao đầy pin/nhiên liệu không?',
                            'answer'   => 'Tất cả các dòng xe tại DriveRent đều được kiểm tra kỹ thuật, vệ sinh khử khuẩn và sạc đầy tối thiểu 80% - 100% pin hoặc nhiên liệu trước khi bàn giao.',
                        ],
                        [
                            'question' => 'Hình thức đặt cọc và thanh toán như thế nào?',
                            'answer'   => 'Hệ thống hỗ trợ thanh toán linh hoạt bằng mã VietQR liên kết ngân hàng. Quý khách hoàn tất đặt cọc trực tuyến và biên lai được hệ thống xác nhận tự động.',
                        ],
                        [
                            'question' => 'Chính sách hoàn cọc sau khi trả xe được xử lý ra sao?',
                            'answer'   => 'Tiền cọc an ninh được hoàn trả lại tài khoản ngân hàng của quý khách trong vòng 24 giờ sau khi hai bên ký biên bản đối soát hoàn tất chuyến đi.',
                        ],
                        [
                            'question' => 'Nếu có sự cố phát sinh trên đường thì hỗ trợ thế nào?',
                            'answer'   => 'DriveRent cung cấp gói cứu hộ khẩn cấp 24/7 trên toàn quốc. Đội ngũ kỹ thuật viên luôn túc trực và xe thay thế sẽ được điều động nhanh chóng.',
                        ],
                    ],
                ],
            ],
            [
                'section_key' => 'about_page',
                'title'       => 'Trang Giới thiệu (About Us)',
                'content'     => [
                    'badge'               => 'Về chúng tôi',
                    'title'               => 'Hành Trình Chuyển Đổi Di Chuyển Xanh',
                    'hero_desc'           => 'Tiên phong mang đến dịch vụ thuê xe điện tự lái thông minh, an toàn và thân thiện với môi trường tại Việt Nam.',
                    'mission_title'       => 'Sứ mệnh của DriveRentals',
                    'mission_paragraph_1' => 'Được thành lập với tầm nhìn phát triển giao thông bền vững, DriveRentals kết nối khách hàng với dòng xe điện cao cấp, mang lại sự tự do và tiện nghi tối đa cho mọi chuyến đi.',
                    'mission_paragraph_2' => 'Chúng tôi tin rằng mỗi chuyến đi không chỉ là hành trình di chuyển giữa hai điểm đến, mà là trải nghiệm công nghệ tinh tế, êm ái và không phát thải. Đội xe được bảo dưỡng định kỳ với tiêu chuẩn khắt khe, cam kết an tâm trọn vẹn.',
                    'commitments'         => [
                        [
                            'icon'  => 'fa-solid fa-leaf',
                            'title' => '100% Động Cơ Điện',
                            'desc'  => 'Giảm thiểu phát thải CO2, vận hành êm ái và mượt mà.',
                        ],
                        [
                            'icon'  => 'fa-solid fa-shield-halved',
                            'title' => 'Bảo Hiểm Toàn Diện',
                            'desc'  => 'Mỗi chuyến đi đều được bảo vệ với gói bảo hiểm chuẩn quốc tế.',
                        ],
                        [
                            'icon'  => 'fa-solid fa-headset',
                            'title' => 'Hỗ Trợ Thông Minh 24/7',
                            'desc'  => 'Trợ lý ảo AI và đội ngũ cứu hộ kỹ thuật sẵn sàng hỗ trợ mọi lúc.',
                        ],
                    ],
                    'team_image'          => 'https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1200',
                ],
            ],
            [
                'section_key' => 'contact_page',
                'title'       => 'Trang Liên hệ (Contact)',
                'content'     => [
                    'badge'          => 'Trung tâm hỗ trợ khách hàng',
                    'title'          => 'Liên Hệ & Góp Ý Dịch Vụ',
                    'hero_desc'      => 'Đội ngũ DriveRentals luôn sẵn sàng lắng nghe mọi ý kiến đóng góp, tư vấn hành trình và giải quyết yêu cầu hỗ trợ của bạn 24/7.',
                    'form_title'     => 'Gửi Yêu Cầu Trực Tuyến',
                    'form_subtitle'  => 'Điền thông tin bên dưới, nhân viên hỗ trợ sẽ liên hệ lại với bạn trong 24 giờ làm việc.',
                    'sidebar_title'  => 'Kênh Liên Hệ Khác',
                    'sidebar_sub'    => 'Bạn cũng có thể kết nối nhanh với chúng tôi qua các kênh trực tiếp:',
                    'ai_badge'       => 'Trực tuyến 24/7',
                    'ai_title'       => 'Hỗ Trợ Tức Thì Qua DriveBot',
                    'ai_desc'        => 'Tra cứu thông số xe, bảng giá hoặc hướng dẫn đặt cọc ngay trong 5 giây mà không cần chờ đợi.',
                ],
            ],
            [
                'section_key' => 'process_page',
                'title'       => 'Trang Quy trình (Process)',
                'content'     => [
                    'badge'              => 'Quy trình đơn giản',
                    'title'              => '4 Bước Thuê Xe Nhanh Chóng',
                    'hero_desc'          => 'Bắt đầu hành trình của bạn chỉ trong vài phút với quy trình tối giản, trực quan và bảo mật.',
                    'steps'              => [
                        [
                            'step_number' => '01',
                            'icon'        => 'fa-solid fa-car-side',
                            'title'       => 'Khám phá & Chọn xe',
                            'desc'        => 'Duyệt qua danh mục xe điện cao cấp VinFast (VF3, VF5, VF8, VF9) với thông tin giá thuê, khu vực hoạt động và hình ảnh thực tế minh bạch.',
                        ],
                        [
                            'step_number' => '02',
                            'icon'        => 'fa-solid fa-calendar-check',
                            'title'       => 'Đặt lịch & Chọn địa điểm',
                            'desc'        => 'Chọn ngày bắt đầu, ngày kết thúc và địa chỉ nhận xe. Hệ thống tự động tính số ngày và hiển thị tổng tiền tạm tính chính xác.',
                        ],
                        [
                            'step_number' => '03',
                            'icon'        => 'fa-solid fa-qrcode',
                            'title'       => 'Thanh toán VietQR & Tải bill',
                            'desc'        => 'Quét mã VietQR tiện lợi với nội dung chuyển khoản tự động kèm mã đơn, sau đó tải ảnh biên lai lên hệ thống để quản trị viên duyệt đơn.',
                        ],
                        [
                            'step_number' => '04',
                            'icon'        => 'fa-solid fa-key',
                            'title'       => 'Nhận xe & Lên đường',
                            'desc'        => 'Quản trị viên xác nhận đơn. Xe được kiểm tra kỹ thuật và bàn giao tận nơi đúng thời gian đã hẹn để bạn bắt đầu hành trình xanh.',
                        ],
                    ],
                    'cta_title'          => 'Sẵn sàng bắt đầu chuyến đi của bạn?',
                    'cta_desc'           => 'Đội ngũ xe điện thông minh đã sẵn sàng đồng hành cùng bạn trên mọi nẻo đường.',
                    'primary_cta_text'   => 'Khám phá đội xe ngay',
                    'primary_cta_url'    => '/fleet',
                    'secondary_cta_text' => 'Tìm xe theo ngày',
                    'secondary_cta_url'  => '/',
                ],
            ],
            [
                'section_key' => 'policies_page',
                'title'       => 'Trang Chính sách & Quy định',
                'content'     => [
                    'badge'    => 'Điều khoản & Quy định',
                    'title'    => 'Chính Sách & Quy Định Thuê Xe',
                    'intro'    => 'Tổng quan các điều khoản và quyền lợi minh bạch khi trải nghiệm dịch vụ thuê xe tại DriveRent.',
                    'sections' => [
                        [
                            'title'   => '1. Điều kiện đối với người thuê xe',
                            'content' => 'Người lái xe cần có Giấy phép lái xe hạng B1/B2 trở lên còn hiệu lực và CCCD gắn chip (hoặc Hộ chiếu còn hạn). Tuổi từ 21 trở lên.',
                        ],
                        [
                            'title'   => '2. Quy định nhận & bàn giao xe',
                            'content' => 'Hai bên cùng kiểm tra tình trạng ngoại thất, nội thất, mức pin/xăng và ký biên bản bàn giao điện tử trước khi khởi hành.',
                        ],
                        [
                            'title'   => '3. Chính sách đặt cọc và hoàn trả',
                            'content' => 'Tiền đặt cọc an ninh được hoàn trả trong vòng 24 giờ sau khi đối soát hoàn tất và không phát sinh vi phạm giao thông.',
                        ],
                        [
                            'title'   => '4. Bảo hiểm & Cứu hộ khẩn cấp',
                            'content' => 'Tất cả các xe được trang bị bảo hiểm thân vỏ và gói cứu hộ 24/7 trên phạm vi toàn quốc.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($sections as $s) {
            $contentJson = json_encode($s['content'], JSON_UNESCAPED_UNICODE);
            $contentId = DB::table('website_contents')->insertGetId([
                'section_key'             => $s['section_key'],
                'title'                   => $s['title'],
                'draft_content'           => $contentJson,
                'published_content'       => $contentJson,
                'status'                  => 'published',
                'has_unpublished_changes' => false,
                'version'                 => 1,
                'published_at'            => $now,
                'publisher_id'            => null,
                'updated_by'              => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);

            DB::table('website_content_versions')->insert([
                'content_id'  => $contentId,
                'section_key' => $s['section_key'],
                'version'     => 1,
                'content'     => $contentJson,
                'note'        => 'Phiên bản khởi tạo ban đầu từ cấu trúc hệ thống',
                'created_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_content_versions');
        Schema::dropIfExists('website_contents');
    }
};
