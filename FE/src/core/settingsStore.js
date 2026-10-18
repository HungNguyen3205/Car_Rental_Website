import { reactive, readonly } from 'vue';
import baseRequest from './baseRequest';

const CACHE_KEY = 'driverent_public_settings';
const CACHE_TIME_KEY = 'driverent_public_settings_time';
const CACHE_TTL_MS = 60 * 60 * 1000; // 1 giờ

// Giá trị mặc định chuẩn phòng trường hợp mạng chậm hoặc lỗi kết nối
const defaultSettings = {
  site_name: 'DriveRent',
  site_logo: '',
  hotline: '1900 1234',
  support_email: 'support@driverentals.com',
  company_address: 'Tòa nhà Bitexco, Q.1, TP. HCM',
  service_locations: ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'],
  bank_name: 'MBBank (Ngân hàng TMCP Quân Đội)',
  bank_account_number: '0971120038',
  bank_account_holder: 'NAM HUNG RENTAL',
  bank_bin: '970422',
  bank_transfer_instruction: 'Quét mã VietQR hoặc chuyển khoản với nội dung là Mã đơn hàng để kế toán đối soát nhanh nhất.',
  rental_policy: {
    deposit_policy: 'Đặt cọc bảo đảm khi nhận xe. Hoàn lại toàn bộ sau 24h đối soát trả xe.',
    document_requirements: 'CCCD gắn chip (hoặc Hộ chiếu còn hạn) + GPLX B2 trở lên còn hiệu lực.',
    rental_time_rules: 'Tính theo ngày (24 giờ). Bàn giao và trả xe theo biên bản kiểm tra thực tế.',
    vehicle_rules: 'Không hút thuốc lá trong xe, không vận chuyển hàng cấm hoặc chất gây mùi.',
  }
};

const state = reactive({
  settings: { ...defaultSettings },
  loaded: false,
  loading: false,
});

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
    }
  } catch (err) {
    console.warn('Không thể tải cấu hình từ máy chủ, sử dụng cấu hình mặc định:', err);
  } finally {
    state.loading = false;
    state.loaded = true;
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

export function useSystemSettings() {
  return {
    settings: readonly(state.settings),
    isLoaded: readonly(state.loaded),
    isLoading: readonly(state.loading),
    fetchSettings,
    refreshSettings,
  };
}
