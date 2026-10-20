import { reactive, readonly, computed } from 'vue';
import baseRequest from './baseRequest';

const CACHE_KEY = 'driverent_public_settings';
const CACHE_TIME_KEY = 'driverent_public_settings_time';
const CACHE_TTL_MS = 60 * 60 * 1000; // 1 giờ

// Giá trị mặc định chuẩn phòng trường hợp mạng chậm hoặc lỗi kết nối.
// Tuyệt đối không dùng số tài khoản hay số tiền mẫu giả định gây hiểu lầm cho khách hàng.
const defaultSettings = {
  site_name: 'DriveRent',
  site_logo: '',
  hotline: '1900 1234',
  support_email: 'support@driverentals.com',
  company_address: 'Tòa nhà Bitexco, Q.1, TP. HCM',
  service_locations: ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'],
  bank_name: '',
  bank_account_number: '',
  bank_account_holder: '',
  bank_bin: '',
  bank_transfer_instruction: '',
  rental_policy: {
    deposit_policy: 'Đặt cọc bảo đảm khi nhận xe. Hoàn lại toàn bộ sau 24h đối soát trả xe.',
    document_requirements: 'CCCD gắn chip (hoặc Hộ chiếu còn hạn) + GPLX B2 trở lên còn hiệu lực.',
    rental_time_rules: 'Tính theo ngày (24 giờ). Bàn giao và trả xe theo biên bản kiểm tra thực tế.',
    vehicle_rules: 'Không hút thuốc lá trong xe, không vận chuyển hàng cấm hoặc chất gây mùi.',
  },
  rental_buffer_hours: 2,
  booking_auto_cancel_unpaid_hours: 24,
  seo_meta_title_suffix: ' - Dịch Vụ Thuê Xe Điện VinFast Uy Tín',
  seo_meta_description: 'DriveRentals - Dịch vụ cho thuê xe điện tự lái VinFast VF3, VF5, VF8, VF9 thông minh, giá niêm yết minh bạch, giao xe tận nơi 24/7.',
  seo_og_image: '/assets/images/bento_main.jpg',
  map_provider: 'google_maps',
  map_embed_url: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.5178265431627!2d106.70188237586828!3d10.771594959281696!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f41639d679f%3A0x8e5f2a0833923a19!2sBitexco%20Financial%20Tower!5e0!3m2!1sen!2svn!4v1710000000000!5m2!1sen!2svn',
  map_coordinates: '10.771595, 106.704457',
  animation_mode: 'standard', // 'standard' | 'reduced' | 'off'
  animation_duration_scale: '1.0', // '0.5' | '1.0' | '1.5'
  notification_sound_enabled: true,
};

const state = reactive({
  settings: { ...defaultSettings },
  loaded: false,
  loading: false,
});

/**
 * Áp dụng các consumer giao diện thực tế từ tham số cấu hình:
 * 1. Chuyển động: Thiết lập thuộc tính data-animation-mode và biến CSS thời lượng lên :root
 * 2. SEO: Cập nhật meta description và OpenGraph image vào DOM head
 */
export function applySettingsConsumers(settings) {
  if (typeof document === 'undefined') return;

  try {
    const root = document.documentElement;
    // Chuyển động (Animation Consumer)
    const animMode = ['standard', 'reduced', 'off'].includes(settings.animation_mode) 
      ? settings.animation_mode 
      : 'standard';
    root.setAttribute('data-animation-mode', animMode);

    const durationScale = ['0.5', '1.0', '1.5'].includes(String(settings.animation_duration_scale))
      ? String(settings.animation_duration_scale)
      : '1.0';
    root.style.setProperty('--app-transition-scale', durationScale);

    // Tối ưu SEO (SEO Meta Consumer)
    if (settings.seo_meta_description) {
      let metaDesc = document.querySelector('meta[name="description"]');
      if (!metaDesc) {
        metaDesc = document.createElement('meta');
        metaDesc.setAttribute('name', 'description');
        document.head.appendChild(metaDesc);
      }
      metaDesc.setAttribute('content', settings.seo_meta_description);
    }

    if (settings.seo_og_image) {
      let ogImg = document.querySelector('meta[property="og:image"]');
      if (!ogImg) {
        ogImg = document.createElement('meta');
        ogImg.setAttribute('property', 'og:image');
        document.head.appendChild(ogImg);
      }
      ogImg.setAttribute('content', settings.seo_og_image);
    }
  } catch (err) {
    console.warn('Lỗi áp dụng cài đặt hệ thống:', err);
  }
}

/**
 * Tải cấu hình công khai từ Cache LocalStorage hoặc gọi API nếu hết hạn / yêu cầu làm mới.
 */
export async function fetchSettings(forceRefresh = false) {
  if (!forceRefresh) {
    try {
      const cached = localStorage.getItem(CACHE_KEY);
      const cachedTime = localStorage.getItem(CACHE_TIME_KEY);
      if (cached && cachedTime && (Date.now() - Number(cachedTime) < CACHE_TTL_MS)) {
        Object.assign(state.settings, JSON.parse(cached));
        state.loaded = true;
        applySettingsConsumers(state.settings);
        return state.settings;
      }
    } catch (e) {
      // Bỏ qua lỗi parse cache
    }
  }

  state.loading = true;
  try {
    const res = await baseRequest.get('settings/public');
    if (res.data && res.data.status && res.data.data) {
      Object.assign(state.settings, res.data.data);
      localStorage.setItem(CACHE_KEY, JSON.stringify(res.data.data));
      localStorage.setItem(CACHE_TIME_KEY, String(Date.now()));
      applySettingsConsumers(state.settings);
    }
  } catch (err) {
    console.warn('Không thể tải cấu hình từ máy chủ, sử dụng cấu hình mặc định:', err);
  } finally {
    state.loading = false;
    state.loaded = true;
    applySettingsConsumers(state.settings);
  }

  return state.settings;
}

/**
 * Xóa cache cục bộ và kéo dữ liệu mới nhất từ backend.
 */
export async function refreshSettings() {
  localStorage.removeItem(CACHE_KEY);
  localStorage.removeItem(CACHE_TIME_KEY);
  return await fetchSettings(true);
}

/**
 * Kiểm tra xem thông tin ngân hàng thụ hưởng đã được cấu hình hợp lệ chưa
 */
export function isBankConfigured(settings = state.settings) {
  return Boolean(
    settings &&
    settings.bank_bin &&
    String(settings.bank_bin).trim().length > 0 &&
    settings.bank_account_number &&
    String(settings.bank_account_number).trim().length > 0
  );
}

export function useSystemSettings() {
  const bankReady = computed(() => isBankConfigured(state.settings));

  return {
    settings: readonly(state.settings),
    isLoaded: readonly(state.loaded),
    isLoading: readonly(state.loading),
    isBankConfigured: bankReady,
    fetchSettings,
    refreshSettings,
  };
}
