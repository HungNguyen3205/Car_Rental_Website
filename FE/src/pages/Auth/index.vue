<template>
  <div class="auth-page">
    <div class="auth-card card animate-in">
      <!-- Header -->
      <div class="auth-header text-center">
        <router-link to="/" class="logo">
          <div class="logo-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
            </svg>
          </div>
          <span class="logo-text">Drive<span>Rent</span></span>
        </router-link>
        <h1 v-if="isForgot">Khôi Phục Mật Khẩu</h1>
        <h1 v-else>{{ isLogin ? 'Chào Mừng Trở Lại' : 'Tạo Tài Khoản Mới' }}</h1>
        <p v-if="isForgot">Nhập địa chỉ email để nhận liên kết khôi phục mật khẩu bảo mật (có hiệu lực trong 60 phút).</p>
        <p v-else>{{ isLogin ? 'Đăng nhập để tiếp tục hành trình và quản lý các chuyến đi' : 'Bắt đầu trải nghiệm thuê xe điện xanh cùng DriveRent' }}</p>
      </div>

      <!-- Status Notice Banner from Query Parameters -->
      <div v-if="authNotice" :class="['auth-notice-banner', authNotice.type, 'animate-in']">
        <i :class="authNotice.icon"></i>
        <div class="notice-content">
          <strong>{{ authNotice.title }}</strong>
          <p>{{ authNotice.message }}</p>
        </div>
        <button type="button" class="close-notice-btn" @click="authNotice = null" aria-label="Đóng thông báo">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- FORGOT PASSWORD VIEW -->
      <div v-if="isForgot" class="forgot-wrapper animate-in">
        <!-- Success Notice (Non-revealing) -->
        <div v-if="forgotSubmitted" class="forgot-success-box text-center">
          <div class="success-icon-badge">
            <i class="fa-solid fa-envelope-circle-check"></i>
          </div>
          <h3>Yêu cầu đã được ghi nhận!</h3>
          <p>
            Nếu địa chỉ email <strong>{{ forgotForm.email }}</strong> khớp với tài khoản 
            <strong>{{ forgotForm.account_type === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}</strong> 
            hợp lệ trên hệ thống, một hướng dẫn đặt lại mật khẩu với token một lần đã được gửi đến hộp thư của bạn.
          </p>
          <div class="forgot-tip">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Liên kết có hiệu lực trong 60 phút và chỉ sử dụng được 01 lần.</span>
          </div>
          <button type="button" class="btn-primary w-full mt-3" @click="resetForgotFlow">
            <i class="fa-solid fa-arrow-left"></i> Quay lại Đăng nhập
          </button>
        </div>

        <!-- Forgot Form -->
        <form v-else @submit.prevent="handleForgotPassword" class="auth-form">
          <!-- Account Type Selector -->
          <div class="form-group">
            <label>Loại tài khoản khôi phục</label>
            <div class="account-type-tabs">
              <button 
                type="button" 
                class="type-tab-btn" 
                :class="{ active: forgotForm.account_type === 'customer' }" 
                @click="forgotForm.account_type = 'customer'"
              >
                <i class="fa-solid fa-user"></i> Khách hàng
              </button>
              <button 
                type="button" 
                class="type-tab-btn" 
                :class="{ active: forgotForm.account_type === 'admin' }" 
                @click="forgotForm.account_type = 'admin'"
              >
                <i class="fa-solid fa-shield-halved"></i> Quản trị viên
              </button>
            </div>
            <span class="type-hint">Chọn đúng loại tài khoản để hệ thống xác thực độc lập, tránh nhầm lẫn.</span>
          </div>

          <!-- Email Input -->
          <div class="form-group">
            <label for="forgot-email">Địa chỉ Email đăng ký</label>
            <div class="input-wrap">
              <i class="fa-regular fa-envelope"></i>
              <input 
                id="forgot-email"
                type="email" 
                v-model="forgotForm.email" 
                placeholder="email@example.com" 
                autocomplete="email"
                class="form-control"
                required 
              />
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-primary w-full mt-2" :disabled="forgotLoading">
            <i v-if="forgotLoading" class="fa-solid fa-spinner fa-spin"></i>
            <span v-else><i class="fa-regular fa-paper-plane"></i> Gửi liên kết đặt lại mật khẩu</span>
          </button>

          <!-- Back to Login -->
          <div class="text-center mt-3">
            <a role="button" class="back-link" @click="isForgot = false">
              <i class="fa-solid fa-arrow-left"></i> Quay lại Đăng nhập
            </a>
          </div>
        </form>
      </div>

      <!-- LOGIN / REGISTER FORMS -->
      <template v-else>
        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="auth-form">
          <!-- General Form Error Banner -->
          <div v-if="errors.general" class="error-banner animate-in">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ errors.general }}</span>
          </div>

          <!-- Full Name (Only in Register mode) -->
          <div class="form-group" v-if="!isLogin">
            <label for="register-name">Họ và tên</label>
            <div class="input-wrap" :class="{ 'has-error': errors.ho_ten }">
              <i class="fa-regular fa-user"></i>
              <input 
                id="register-name"
                type="text" 
                v-model="registerForm.ho_ten" 
                @input="errors.ho_ten = ''; errors.general = ''"
                placeholder="Nguyễn Văn A" 
                autocomplete="name"
                class="form-control"
                required 
              />
            </div>
            <span v-if="errors.ho_ten" class="field-error-text">{{ errors.ho_ten }}</span>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="auth-email">Địa chỉ Email</label>
            <div class="input-wrap" :class="{ 'has-error': errors.email }">
              <i class="fa-regular fa-envelope"></i>
              <input 
                id="auth-email"
                type="email" 
                v-model="activeEmail" 
                @input="errors.email = ''; errors.general = ''"
                placeholder="email@example.com" 
                autocomplete="email"
                class="form-control"
                required 
              />
            </div>
            <span v-if="errors.email" class="field-error-text">{{ errors.email }}</span>
          </div>

          <!-- Password -->
          <div class="form-group">
            <div class="label-row flex-between">
              <label for="auth-password">Mật khẩu</label>
              <a v-if="isLogin" role="button" class="forgot-link" @click="openForgot">Quên mật khẩu?</a>
            </div>
            <div class="input-wrap" :class="{ 'has-error': errors.password }">
              <i class="fa-solid fa-lock"></i>
              <input 
                id="auth-password"
                :type="showPassword ? 'text' : 'password'" 
                v-model="activePassword" 
                @input="errors.password = ''; errors.general = ''"
                placeholder="••••••••" 
                :autocomplete="isLogin ? 'current-password' : 'new-password'"
                class="form-control"
                required 
              />
              <button 
                type="button" 
                class="toggle-pass-btn" 
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
              >
                <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
              </button>
            </div>
            <span v-if="errors.password" class="field-error-text">{{ errors.password }}</span>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-primary w-full mt-2" :disabled="loading">
            <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
            <span v-else>{{ isLogin ? 'Đăng Nhập' : 'Tạo Tài Khoản' }}</span>
          </button>
        </form>

        <!-- Footer -->
        <div class="auth-footer text-center">
          <p class="toggle-mode-text">
            {{ isLogin ? 'Chưa có tài khoản?' : 'Đã có tài khoản?' }}
            <a role="button" class="toggle-link" @click="toggleMode">
              {{ isLogin ? 'Đăng ký ngay' : 'Đăng nhập tại đây' }}
            </a>
          </p>
          
          <div class="social-divider">
            <span>Hoặc tiếp tục với</span>
          </div>
          
          <div class="social-auth-grid">
            <button type="button" class="btn-social google" @click="handleSocialLogin('google')">
              <i class="fa-brands fa-google"></i>
              <span>Google</span>
            </button>
            <button type="button" class="btn-social facebook" @click="handleSocialLogin('facebook')">
              <i class="fa-brands fa-facebook-f"></i>
              <span>Facebook</span>
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { setAuthSession, getSafePostLoginRedirect } from '../../core/authStorage';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const router = useRouter();
const route = useRoute();
const isLogin = ref(true);
const isForgot = ref(false);
const forgotSubmitted = ref(false);
const forgotLoading = ref(false);
const showPassword = ref(false);
const loading = ref(false);
const authNotice = ref(null);

const errors = reactive({
  email: '',
  password: '',
  ho_ten: '',
  general: ''
});

const loginForm = reactive({
  email: '',
  password: ''
});

const registerForm = reactive({
  ho_ten: '',
  email: '',
  password: '',
  so_dien_thoai: '',
  dia_chi: ''
});

const forgotForm = reactive({
  email: '',
  account_type: 'customer'
});

onMounted(() => {
  const query = route.query;
  if (query.expired === '1') {
    authNotice.value = {
      type: 'warning',
      icon: 'fa-solid fa-clock-rotate-left',
      title: 'Phiên làm việc đã hết hạn',
      message: 'Vui lòng đăng nhập lại để tiếp tục phiên làm việc an toàn.'
    };
  } else if (query.required === 'admin' && query.forbidden === '1') {
    authNotice.value = {
      type: 'danger',
      icon: 'fa-solid fa-ban',
      title: 'Thiếu quyền quản trị',
      message: 'Tài khoản hiện tại không có quyền truy cập khu vực Quản trị. Vui lòng đăng nhập bằng tài khoản Quản trị viên!'
    };
  } else if (query.required === 'admin') {
    authNotice.value = {
      type: 'info',
      icon: 'fa-solid fa-shield-halved',
      title: 'Yêu cầu quyền Quản trị viên',
      message: 'Khu vực quản trị yêu cầu bạn đăng nhập bằng tài khoản Quản trị viên.'
    };
  } else if (query.locked === '1') {
    authNotice.value = {
      type: 'danger',
      icon: 'fa-solid fa-user-lock',
      title: 'Tài khoản tạm ngưng',
      message: 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt. Vui lòng liên hệ ban quản trị.'
    };
  } else if (query.required === 'auth') {
    authNotice.value = {
      type: 'info',
      icon: 'fa-solid fa-circle-info',
      title: 'Yêu cầu đăng nhập',
      message: 'Vui lòng đăng nhập để tiếp tục truy cập chuyến đi hoặc hồ sơ của bạn.'
    };
  }
});

const openForgot = () => {
  isForgot.value = true;
  forgotSubmitted.value = false;
  if (loginForm.email) {
    forgotForm.email = loginForm.email;
  }
};

const resetForgotFlow = () => {
  isForgot.value = false;
  forgotSubmitted.value = false;
};

const activeEmail = computed({
  get: () => isLogin.value ? loginForm.email : registerForm.email,
  set: (val) => {
    if (isLogin.value) loginForm.email = val;
    else registerForm.email = val;
  }
});

const activePassword = computed({
  get: () => isLogin.value ? loginForm.password : registerForm.password,
  set: (val) => {
    if (isLogin.value) loginForm.password = val;
    else registerForm.password = val;
  }
});

const toggleMode = () => {
  isLogin.value = !isLogin.value;
  errors.email = '';
  errors.password = '';
  errors.ho_ten = '';
  errors.general = '';
};

const handleForgotPassword = async () => {
  if (!forgotForm.email) {
    toaster.error('Vui lòng nhập địa chỉ email!');
    return;
  }

  forgotLoading.value = true;
  try {
    const res = await baseRequest.post('forgot-password', {
      email: forgotForm.email,
      account_type: forgotForm.account_type
    });

    // Luôn hiển thị thông báo thành công thống nhất (chống enumeration email)
    forgotSubmitted.value = true;
    toaster.info(res.data.message || 'Yêu cầu khôi phục mật khẩu đã được tiếp nhận.');
  } catch (error) {
    if (error.response?.status === 429) {
      toaster.error('Bạn đã gửi quá nhiều yêu cầu. Vui lòng đợi 1 phút trước khi thử lại!');
    } else {
      toaster.error(error.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại sau!');
    }
  } finally {
    forgotLoading.value = false;
  }
};

const handleSubmit = async () => {
  errors.email = '';
  errors.password = '';
  errors.ho_ten = '';
  errors.general = '';

  if (isLogin.value) {
    if (!loginForm.email) {
      errors.email = 'Vui lòng nhập địa chỉ email!';
      return;
    }
    if (!loginForm.password) {
      errors.password = 'Vui lòng nhập mật khẩu!';
      return;
    }
  } else {
    if (!registerForm.ho_ten) {
      errors.ho_ten = 'Vui lòng nhập họ và tên!';
      return;
    }
    if (!registerForm.email) {
      errors.email = 'Vui lòng nhập địa chỉ email!';
      return;
    }
    if (!registerForm.password || registerForm.password.length < 6) {
      errors.password = 'Mật khẩu phải có ít nhất 6 ký tự!';
      return;
    }
  }

  loading.value = true;
  try {
    const url = isLogin.value ? 'login' : 'register';
    const payload = isLogin.value ? loginForm : registerForm;
    
    const res = await baseRequest.post(url, payload);
    
    if (res.data.status) {
      toaster.success(res.data.message || (isLogin.value ? 'Đăng nhập thành công!' : 'Đăng ký thành công!'));
      if (isLogin.value) {
        setAuthSession({
          token: res.data.token,
          user: res.data.user,
          role: res.data.user.role
        });

        authNotice.value = null;
        const targetPath = getSafePostLoginRedirect(route.query.redirect, res.data.user.role);
        router.push(targetPath);
      } else {
        isLogin.value = true;
      }
    } else {
      errors.general = res.data.message || 'Thao tác không thành công!';
      toaster.error(errors.general);
    }
  } catch (error) {
    const status = error.response?.status;
    const data = error.response?.data;

    if (status === 401) {
      errors.general = data?.message || 'Tài khoản hoặc mật khẩu không chính xác!';
      toaster.error(errors.general);
    } else if (status === 403) {
      errors.general = data?.message || 'Tài khoản chưa được kích hoạt hoặc đã bị khóa!';
      toaster.error(errors.general);
    } else if (status === 422) {
      const fieldErrors = data?.errors || {};
      if (fieldErrors.email) errors.email = fieldErrors.email[0];
      if (fieldErrors.password) errors.password = fieldErrors.password[0];
      if (fieldErrors.ho_ten) errors.ho_ten = fieldErrors.ho_ten[0];
      if (!errors.email && !errors.password && !errors.ho_ten) {
        errors.general = data?.message || 'Dữ liệu không hợp lệ!';
      }
    } else if (status === 429) {
      errors.general = data?.message || 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau!';
      toaster.error(errors.general);
    } else {
      errors.general = data?.message || 'Có lỗi xảy ra, vui lòng thử lại sau!';
      toaster.error(errors.general);
    }
  } finally {
    loading.value = false;
  }
};

const handleSocialLogin = (provider) => {
  const apiUrl = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api";
  window.location.href = `${apiUrl}/auth/${provider}/redirect`;
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at 80% 20%, rgba(5, 150, 105, 0.08), transparent 40%),
              radial-gradient(circle at 20% 80%, rgba(2, 132, 199, 0.05), transparent 40%),
              var(--bg-main);
  padding: 100px 20px 40px;
}

.auth-card {
  width: 100%;
  max-width: 480px;
  padding: 44px 40px;
  background: #ffffff;
  border-radius: var(--radius-xl);
}

.auth-header {
  margin-bottom: 32px;
}

.logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.logo-box {
  width: 40px;
  height: 40px;
  background: var(--grad-active);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.logo-text span {
  color: var(--color-primary);
}

.auth-header h1 {
  font-size: 1.8rem;
  font-weight: 800;
  margin-bottom: 6px;
}

.auth-header p {
  font-size: 0.925rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrap i:first-child {
  position: absolute;
  left: 16px;
  color: var(--color-text-muted);
  font-size: 1rem;
}

.input-wrap .form-control {
  padding-left: 44px;
  padding-right: 44px;
}

.toggle-pass-btn {
  position: absolute;
  right: 14px;
  color: var(--color-text-muted);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.toggle-pass-btn:hover {
  color: var(--color-text-main);
}

.auth-footer {
  margin-top: 28px;
}

.toggle-mode-text {
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.toggle-link {
  color: var(--color-primary);
  font-weight: 700;
  cursor: pointer;
  margin-left: 4px;
}

.toggle-link:hover {
  text-decoration: underline;
}

.social-divider {
  position: relative;
  text-align: center;
  margin: 24px 0 20px;
}

.social-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--color-border);
}

.social-divider span {
  position: relative;
  background: #ffffff;
  padding: 0 14px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.social-auth-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.btn-social {
  padding: 11px 16px;
  border-radius: var(--radius-md);
  background: var(--bg-surface-sub);
  border: 1px solid var(--color-border);
  font-weight: 600;
  font-size: 0.885rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--color-text-main);
  transition: all var(--duration-fast);
}

.btn-social:hover {
  background: #e2e8f0;
}

.btn-social.google i {
  color: #ea4335;
}

.btn-social.facebook i {
  color: #1877f2;
}

.w-full {
  width: 100%;
}

.mt-2 {
  margin-top: 8px;
}

.mt-3 {
  margin-top: 16px;
}

.text-center {
  text-align: center;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.forgot-link {
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-primary);
  cursor: pointer;
  transition: opacity var(--duration-fast);
}

.forgot-link:hover {
  text-decoration: underline;
  opacity: 0.85;
}

.account-type-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  background: var(--bg-surface-sub);
  padding: 4px;
  border-radius: var(--radius-md);
  margin-bottom: 6px;
}

.type-tab-btn {
  padding: 8px 12px;
  border-radius: calc(var(--radius-md) - 2px);
  border: none;
  background: transparent;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all var(--duration-fast);
}

.type-tab-btn.active {
  background: #ffffff;
  color: var(--color-primary);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.type-hint {
  font-size: 0.775rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  display: block;
}

.forgot-success-box {
  background: rgba(16, 185, 129, 0.06);
  border: 1px solid rgba(16, 185, 129, 0.2);
  border-radius: var(--radius-lg);
  padding: 24px 20px;
}

.success-icon-badge {
  width: 56px;
  height: 56px;
  margin: 0 auto 16px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.15);
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.forgot-success-box h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #065f46;
  margin-bottom: 8px;
}

.forgot-success-box p {
  font-size: 0.885rem;
  color: #047857;
  line-height: 1.5;
  margin-bottom: 12px;
}

.forgot-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.8rem;
  color: #059669;
  background: #ffffff;
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  margin-bottom: 8px;
}

.back-link {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: color var(--duration-fast);
}

.back-link:hover {
  color: var(--color-primary);
}

/* Auth Notice Banners & Field Error Styles */
.auth-notice-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-md, 10px);
  margin-bottom: 20px;
  font-size: 0.875rem;
  line-height: 1.45;
  position: relative;
  text-align: left;
}

.auth-notice-banner i {
  font-size: 1.15rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.auth-notice-banner.warning {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #b45309;
}
.auth-notice-banner.warning i {
  color: #f59e0b;
}

.auth-notice-banner.danger {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.28);
  color: #b91c1c;
}
.auth-notice-banner.danger i {
  color: #ef4444;
}

.auth-notice-banner.info {
  background: rgba(14, 165, 233, 0.1);
  border: 1px solid rgba(14, 165, 233, 0.28);
  color: #0369a1;
}
.auth-notice-banner.info i {
  color: #0ea5e9;
}

.notice-content {
  flex: 1;
}

.notice-content strong {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  margin-bottom: 2px;
}

.notice-content p {
  margin: 0;
  font-size: 0.85rem;
  opacity: 0.95;
}

.close-notice-btn {
  background: none;
  border: none;
  color: currentColor;
  opacity: 0.6;
  cursor: pointer;
  padding: 2px 4px;
  font-size: 0.9rem;
  line-height: 1;
}
.close-notice-btn:hover {
  opacity: 1;
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: var(--radius-sm, 8px);
  color: #dc2626;
  font-size: 0.875rem;
  margin-bottom: 16px;
  text-align: left;
}

.input-wrap.has-error .form-control {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2) !important;
}

.field-error-text {
  display: block;
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 5px;
  padding-left: 4px;
  text-align: left;
}
</style>
