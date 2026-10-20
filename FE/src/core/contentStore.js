import { reactive, readonly } from 'vue';
import baseRequest from './baseRequest';

const CACHE_KEY = 'driverent_public_content';
const CACHE_TIME_KEY = 'driverent_public_content_time';
const CACHE_TTL_MS = 60 * 60 * 1000; // 1 giờ

// Dữ liệu mặc định gốc cho 8 khối nội dung (bảo đảm không bao giờ bị trắng trang)
export const defaultContents = {
  home_hero: {
    eyebrow: 'DRIVERENT / CHỌN HÀNH TRÌNH CỦA BẠN',
    title: 'XE SANG CHO MỌI HÀNH TRÌNH',
    description: 'Một lựa chọn phù hợp. Một hành trình đáng nhớ.',
    primary_cta_text: 'Tìm xe cho chuyến đi',
    primary_cta_action: 'scroll_to_search',
    secondary_cta_text: 'Xem đội xe',
    secondary_cta_url: '/fleet',
    visual_note: 'Khám phá xe. Chọn hành trình.',
  },
  home_rental_steps: {
    eyebrow: 'QUY TRÌNH TINH GỌN',
    title: '3 Bước Đơn Giản Lăn Bánh',
    description: 'Không thủ tục rườm rà, không chờ đợi giấy tờ. Toàn bộ quy trình được số hóa minh bạch từ lúc chọn xe đến khi bàn giao.',
    steps: [
      {
        step_number: '01',
        icon: 'fa-solid fa-car',
        title: 'Chọn xe & Lịch trình',
        desc: 'Lựa chọn dòng xe phù hợp nhu cầu, chọn ngày giờ nhận/trả xe và điểm giao linh hoạt tại Hà Nội, TP.HCM hoặc Đà Nẵng.',
      },
      {
        step_number: '02',
        icon: 'fa-solid fa-file-shield',
        title: 'Xác nhận & Đặt cọc',
        desc: 'Kiểm tra biểu phí minh bạch, chuyển khoản an toàn qua mã VietQR và nhận thông báo xác nhận đơn đặt ngay trên ứng dụng.',
      },
      {
        step_number: '03',
        icon: 'fa-solid fa-key',
        title: 'Nhận xe & Khởi hành',
        desc: 'Nhận bàn giao xe tận nơi với biên bản điện tử chi tiết, kiểm tra số km, lượng pin/xăng và an tâm tận hưởng hành trình.',
      },
    ],
  },
  home_bento: {
    eyebrow: 'TIÊU CHUẨN ĐẲNG CẤP',
    title: 'Trải Nghiệm Khác Biệt Tại DriveRent',
    featured_badge: '100% Động Cơ Xanh',
    featured_title: 'Kiến Tạo Hành Trình Thông Minh & Đẳng Cấp',
    featured_desc: 'Khám phá cảm giác lái êm ái, tĩnh lặng tuyệt đối cùng công nghệ hỗ trợ lái tiên tiến. Mỗi chặng đường đều là một trải nghiệm thư thái trọn vẹn.',
    featured_image: '/assets/images/bento_main.jpg',
    featured_features: [
      'Pin sạc chuẩn hoá',
      'Cứu hộ 24/7 toàn quốc',
    ],
    card_network_badge: 'Mạng Lưới Phục Vụ',
    card_network_title: 'Phủ Sóng 3 Miền Toàn Quốc',
    card_network_desc: 'Mạng lưới bàn giao xe linh hoạt tại Hà Nội, Đà Nẵng, TP. Hồ Chí Minh và đón trả tận nơi tại các sân bay lớn: Nội Bài, Tân Sơn Nhất, Đà Nẵng.',
    card_policy_badge: 'Minh Bạch Tuyệt Đối',
    card_policy_title: 'Chính Sách Hoàn Cọc Trong 24H',
    card_policy_desc: 'Bảo đảm hoàn cọc an ninh trong 24 giờ sau khi đối soát biên bản trả xe. Không phụ phí ẩn, không phát sinh ngoài hợp đồng số.',
    card_policy_cta_text: 'Khám phá ngay',
    card_policy_cta_url: '/fleet',
  },
  home_faq: {
    eyebrow: 'GIẢI ĐÁP THẮC MẮC',
    title: 'Câu Hỏi Thường Gặp',
    description: 'Mọi thông tin cần biết để hành trình của quý khách luôn an tâm và trọn vẹn nhất',
    faqs: [
      {
        question: 'Tôi cần chuẩn bị những giấy tờ gì khi nhận xe?',
        answer: 'Quý khách chỉ cần chuẩn bị Căn cước công dân gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B1/B2 còn hiệu lực để đối chiếu thông tin khi giao nhận xe.',
      },
      {
        question: 'Xe có được bàn giao đầy pin/nhiên liệu không?',
        answer: 'Tất cả các dòng xe tại DriveRent đều được kiểm tra kỹ thuật, vệ sinh khử khuẩn và sạc đầy tối thiểu 80% - 100% pin hoặc nhiên liệu trước khi bàn giao.',
      },
      {
        question: 'Hình thức đặt cọc và thanh toán như thế nào?',
        answer: 'Hệ thống hỗ trợ thanh toán linh hoạt bằng mã VietQR liên kết ngân hàng. Quý khách hoàn tất đặt cọc trực tuyến và biên lai được hệ thống xác nhận tự động.',
      },
      {
        question: 'Chính sách hoàn cọc sau khi trả xe được xử lý ra sao?',
        answer: 'Tiền cọc an ninh được hoàn trả lại tài khoản ngân hàng của quý khách trong vòng 24 giờ sau khi hai bên ký biên bản đối soát hoàn tất chuyến đi.',
      },
      {
        question: 'Nếu có sự cố phát sinh trên đường thì hỗ trợ thế nào?',
        answer: 'DriveRent cung cấp gói cứu hộ khẩn cấp 24/7 trên toàn quốc. Đội ngũ kỹ thuật viên luôn túc trực và xe thay thế sẽ được điều động nhanh chóng.',
      },
    ],
  },
  about_page: {
    badge: 'VỀ CHÚNG TÔI',
    title: 'Hành Trình Di Chuyển Thông Minh & Bền Vững',
    hero_desc: 'DriveRent mang đến giải pháp thuê xe điện và xe sang tự lái tiện lợi, minh bạch về chi phí và số hóa toàn diện từ khâu chọn xe đến bàn giao thực tế.',
    mission_title: 'Sứ mệnh và Giá trị cốt lõi',
    mission_paragraph_1: 'DriveRent được định hình với mục tiêu đơn giản hóa quy trình thuê xe tại Việt Nam. Chúng tôi kết nối khách hàng với phương tiện thông minh, đáp ứng nhu cầu du lịch, công tác và di chuyển gia đình linh hoạt.',
    mission_paragraph_2: 'Mỗi phương tiện trong hệ thống đều trải qua quy trình kiểm tra kỹ thuật định kỳ, khử khuẩn và sạc đầy trước khi bàn giao. Toàn bộ thông tin giá niêm yết, tình trạng xe và quy định đặt cọc đều được thể hiện rõ ràng trên hợp đồng số.',
    commitments: [
      {
        icon: 'fa-solid fa-bolt',
        title: 'Năng Lượng Sạch & Hiện Đại',
        desc: 'Hệ sinh thái xe điện thông minh giúp tối ưu chi phí vận hành và mang lại trải nghiệm lái êm ái, tĩnh lặng.',
      },
      {
        icon: 'fa-solid fa-receipt',
        title: 'Minh Bạch Chi Phí & Snapshot',
        desc: 'Biểu phí niêm yết công khai đã gồm VAT, chốt giá tự động theo ngày và cam kết hoàn cọc an ninh trong 24 giờ.',
      },
      {
        icon: 'fa-solid fa-headset',
        title: 'Hỗ Trợ Đồng Hành 24/7',
        desc: 'Đội ngũ tư vấn viên và kỹ thuật viên luôn túc trực, sẵn sàng hỗ trợ khách hàng trong suốt thời gian thuê xe.',
      },
    ],
    team_image: '/assets/images/bento_main.jpg',
  },
  contact_page: {
    badge: 'Trung tâm hỗ trợ khách hàng',
    title: 'Liên Hệ & Góp Ý Dịch Vụ',
    hero_desc: 'Đội ngũ DriveRentals luôn sẵn sàng lắng nghe mọi ý kiến đóng góp, tư vấn hành trình và giải quyết yêu cầu hỗ trợ của bạn 24/7.',
    form_title: 'Gửi Yêu Cầu Trực Tuyến',
    form_subtitle: 'Điền thông tin bên dưới, nhân viên hỗ trợ sẽ liên hệ lại với bạn trong 24 giờ làm việc.',
    sidebar_title: 'Kênh Liên Hệ Khác',
    sidebar_sub: 'Bạn cũng có thể kết nối nhanh với chúng tôi qua các kênh trực tiếp:',
    ai_badge: 'Trực tuyến 24/7',
    ai_title: 'Hỗ Trợ Tức Thì Qua DriveBot',
    ai_desc: 'Tra cứu thông số xe, bảng giá hoặc hướng dẫn đặt cọc ngay trong 5 giây mà không cần chờ đợi.',
  },
  process_page: {
    badge: 'MINH BẠCH • TINH GỌN • SỐ HÓA',
    title: 'Quy Trình Thuê Xe Thông Minh',
    hero_desc: 'Bắt đầu hành trình của bạn chỉ trong vài phút với quy trình số hóa 5 giai đoạn minh bạch, trực quan và an toàn tuyệt đối.',
    steps: [
      {
        step_number: '01',
        icon: 'fa-solid fa-car-side',
        title: 'Khám phá & Chọn xe',
        desc: 'Duyệt qua danh mục xe điện cao cấp VinFast (VF3, VF5, VF8, VF9) với thông tin giá thuê, khu vực hoạt động và hình ảnh thực tế minh bạch.',
      },
      {
        step_number: '02',
        icon: 'fa-solid fa-calendar-check',
        title: 'Đặt lịch & Chọn địa điểm',
        desc: 'Chọn ngày bắt đầu, ngày kết thúc và địa chỉ nhận xe. Hệ thống tự động tính số ngày và hiển thị tổng tiền tạm tính chính xác.',
      },
      {
        step_number: '03',
        icon: 'fa-solid fa-qrcode',
        title: 'Thanh toán VietQR & Tải bill',
        desc: 'Quét mã VietQR tiện lợi với nội dung chuyển khoản tự động kèm mã đơn, sau đó tải ảnh biên lai lên hệ thống để quản trị viên duyệt đơn.',
      },
      {
        step_number: '04',
        icon: 'fa-solid fa-key',
        title: 'Nhận xe & Lên đường',
        desc: 'Quản trị viên xác nhận đơn. Xe được kiểm tra kỹ thuật và bàn giao tận nơi đúng thời gian đã hẹn để bạn bắt đầu hành trình xanh.',
      },
    ],
    cta_title: 'Sẵn sàng bắt đầu chuyến đi của bạn?',
    cta_desc: 'Đội ngũ xe điện thông minh đã sẵn sàng đồng hành cùng bạn trên mọi nẻo đường.',
    primary_cta_text: 'Khám phá đội xe ngay',
    primary_cta_url: '/fleet',
    secondary_cta_text: 'Tìm xe theo ngày',
    secondary_cta_url: '/',
  },
  policies_page: {
    badge: 'Điều khoản & Quy định',
    title: 'Chính Sách & Quy Định Thuê Xe',
    intro: 'Tổng quan các điều khoản và quyền lợi minh bạch khi trải nghiệm dịch vụ thuê xe tại DriveRent.',
    sections: [
      {
        title: '1. Điều kiện đối với người thuê xe',
        content: 'Người lái xe cần có Giấy phép lái xe hạng B1/B2 trở lên còn hiệu lực và CCCD gắn chip (hoặc Hộ chiếu còn hạn). Tuổi từ 21 trở lên.',
      },
      {
        title: '2. Quy định nhận & bàn giao xe',
        content: 'Hai bên cùng kiểm tra tình trạng ngoại thất, nội thất, mức pin/xăng và ký biên bản bàn giao điện tử trước khi khởi hành.',
      },
      {
        title: '3. Chính sách đặt cọc và hoàn trả',
        content: 'Tiền đặt cọc an ninh được hoàn trả trong vòng 24 giờ sau khi đối soát hoàn tất và không phát sinh vi phạm giao thông.',
      },
      {
        title: '4. Bảo hiểm & Cứu hộ khẩn cấp',
        content: 'Tất cả các xe được trang bị bảo hiểm thân vỏ và gói cứu hộ 24/7 trên phạm vi toàn quốc.',
      },
    ],
  },
};

const state = reactive({
  contents: JSON.parse(JSON.stringify(defaultContents)),
  loaded: false,
  loading: false,
});

/**
 * Tải nội dung công khai từ Cache LocalStorage hoặc gọi API nếu hết hạn / forceRefresh
 */
export async function fetchWebsiteContent(forceRefresh = false) {
  if (!forceRefresh) {
    try {
      const cached = localStorage.getItem(CACHE_KEY);
      const cachedTime = localStorage.getItem(CACHE_TIME_KEY);
      if (cached && cachedTime && (Date.now() - Number(cachedTime) < CACHE_TTL_MS)) {
        const parsed = JSON.parse(cached);
        applyContentData(parsed);
        state.loaded = true;
        return state.contents;
      }
    } catch (e) {
      // Bỏ qua lỗi cache
    }
  }

  state.loading = true;
  try {
    const res = await baseRequest.get('content');
    if (res.data && res.data.status && res.data.data) {
      applyContentData(res.data.data);
      localStorage.setItem(CACHE_KEY, JSON.stringify(res.data.data));
      localStorage.setItem(CACHE_TIME_KEY, String(Date.now()));
    }
  } catch (err) {
    console.warn('Không thể tải nội dung CMS từ máy chủ, sử dụng nội dung mặc định:', err);
  } finally {
    state.loading = false;
    state.loaded = true;
  }

  return state.contents;
}

function applyContentData(dataMap) {
  for (const key of Object.keys(defaultContents)) {
    if (dataMap[key] && dataMap[key].content) {
      state.contents[key] = { ...defaultContents[key], ...dataMap[key].content };
    }
  }
}

/**
 * Hook truy xuất nội dung phản ứng (Reactive) của một section
 */
export function useWebsiteContent(sectionKey) {
  if (!state.loaded && !state.loading) {
    fetchWebsiteContent();
  }
  return state.contents[sectionKey] || defaultContents[sectionKey];
}

/**
 * Xóa cache CMS phía Client (dùng khi admin xuất bản hoặc khôi phục)
 */
export function invalidateClientContentCache() {
  localStorage.removeItem(CACHE_KEY);
  localStorage.removeItem(CACHE_TIME_KEY);
  return fetchWebsiteContent(true);
}

export const contentStore = readonly(state);
