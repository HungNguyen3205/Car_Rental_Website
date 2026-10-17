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
        <h1>{{ isLogin ? 'Chào Mừng Trở Lại' : 'Tạo Tài Khoản Mới' }}</h1>
        <p>{{ isLogin ? 'Đăng nhập để tiếp tục hành trình và quản lý các chuyến đi' : 'Bắt đầu trải nghiệm thuê xe điện xanh cùng DriveRent' }}</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="auth-form">
        <!-- Full Name (Only in Register mode) -->
        <div class="form-group" v-if="!isLogin">
          <label for="register-name">Họ và tên</label>
          <div class="input-wrap">
            <i class="fa-regular fa-user"></i>
            <input 
              id="register-name"
              type="text" 
              v-model="registerForm.ho_ten" 
              placeholder="Nguyễn Văn A" 
              autocomplete="name"
              class="form-control"
              required 
            />
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="auth-email">Địa chỉ Email</label>
          <div class="input-wrap">
            <i class="fa-regular fa-envelope"></i>
            <input 
              id="auth-email"
              type="email" 
              v-model="activeEmail" 
              placeholder="email@example.com" 
              autocomplete="email"
              class="form-control"
              required 
            />
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <div class="label-row flex-between">
            <label for="auth-password">Mật khẩu</label>
          </div>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input 
              id="auth-password"
              :type="showPassword ? 'text' : 'password'" 
              v-model="activePassword" 
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
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const router = useRouter();
const isLogin = ref(true);
const showPassword = ref(false);
const loading = ref(false);

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
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    const url = isLogin.value ? 'login' : 'register';
    const payload = isLogin.value ? loginForm : registerForm;
    
    const res = await baseRequest.post(url, payload);
    
    if (res.data.status) {
      toaster.success(res.data.message || (isLogin.value ? 'Đăng nhập thành công!' : 'Đăng ký thành công!'));
      if (isLogin.value) {
        localStorage.setItem('token_client', res.data.token);
        localStorage.setItem('user', JSON.stringify(res.data.user));
        localStorage.setItem('user_role', res.data.user.role);
        
        if (res.data.user.role === 'admin') {
          localStorage.setItem('token_admin', res.data.token);
          router.push('/admin');
        } else {
          router.push('/');
        }
      } else {
        isLogin.value = true;
      }
    } else {
      toaster.error(res.data.message || 'Thao tác không thành công!');
    }
  } catch (error) {
    const err = error.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại sau!';
    toaster.error(err);
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

.text-center {
  text-align: center;
}
</style>
