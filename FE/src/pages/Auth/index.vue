<template>
  <div class="auth-page">
    <div class="auth-container glass">
      <div class="auth-header">
        <router-link to="/" class="logo">
          <div class="logo-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"></path></svg>
          </div>
          <span class="logo-text">Drive<span>Rent</span></span>
        </router-link>
        <h1>{{ isLogin ? 'Chào mừng trở lại' : 'Tham gia cùng chúng tôi' }}</h1>
        <p>{{ isLogin ? 'Đăng nhập để quản lý các chuyến đi của bạn' : 'Bắt đầu hành trình xanh cùng VinFast' }}</p>
      </div>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <div class="form-group" v-if="!isLogin">
          <label>Họ và tên</label>
          <div class="input-wrapper">
            <i class="fa-regular fa-user"></i>
            <input type="text" v-model="registerForm.ho_ten" placeholder="Nguyễn Văn A" required />
          </div>
        </div>

        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" v-model="loginForm.email" placeholder="email@example.com" required v-if="isLogin" />
            <input type="email" v-model="registerForm.email" placeholder="email@example.com" required v-else />
          </div>
        </div>

        <div class="form-group">
          <label>Mật khẩu</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock"></i>
            <input :type="showPassword ? 'text' : 'password'" v-model="loginForm.password" placeholder="••••••••" required v-if="isLogin" />
            <input :type="showPassword ? 'text' : 'password'" v-model="registerForm.password" placeholder="••••••••" required v-else />
            <i :class="showPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'" @click="showPassword = !showPassword" class="toggle-pass"></i>
          </div>
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
          {{ loading ? 'Đang xử lý...' : (isLogin ? 'Đăng nhập' : 'Tạo tài khoản') }}
        </button>
      </form>

      <div class="auth-footer">
        <p>
          {{ isLogin ? 'Chưa có tài khoản?' : 'Đã có tài khoản?' }}
          <a @click="isLogin = !isLogin">{{ isLogin ? 'Đăng ký ngay' : 'Đăng nhập tại đây' }}</a>
        </p>
        
        <div class="divider"><span>Hoặc tiếp tục với</span></div>
        
        <div class="social-auth">
          <button type="button" class="social-btn glass" @click="handleSocialLogin('google')"><i class="fa-brands fa-google"></i></button>
          <button type="button" class="social-btn glass" @click="handleSocialLogin('facebook')"><i class="fa-brands fa-facebook-f"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
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

const handleSubmit = async () => {
  loading.value = true;
  try {
    const url = isLogin.value ? 'http://127.0.0.1:8000/api/login' : 'http://127.0.0.1:8000/api/register';
    const payload = isLogin.value ? loginForm : registerForm;
    
    const res = await axios.post(url, payload);
    
    if (res.data.status) {
      toaster.success(res.data.message);
      if (isLogin.value) {
        localStorage.setItem('token_client', res.data.token);
        localStorage.setItem('user', JSON.stringify(res.data.user));
        localStorage.setItem('user_role', res.data.user.role);
        router.push('/');
      } else {
        isLogin.value = true;
      }
    } else {
      toaster.error(res.data.message);
    }
  } catch (error) {
    toaster.error('Có lỗi xảy ra, vui lòng thử lại!');
  } finally {
    loading.value = false;
  }
};

const handleSocialLogin = (provider) => {
  window.location.href = `http://127.0.0.1:8000/api/auth/${provider}/redirect`;
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent),
              radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.05), transparent);
  padding: 24px;
}

.auth-container {
  width: 100%;
  max-width: 480px;
  padding: 48px;
  border-radius: var(--radius-lg);
}

.auth-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}

.logo-box {
  width: 44px;
  height: 44px;
  background: var(--grad-active);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: var(--shadow-glow);
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.logo-text span {
  color: var(--color-primary);
}

h1 {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--color-text-main);
  letter-spacing: -1px;
}

p {
  color: var(--color-text-muted);
  margin-top: 8px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  margin-bottom: 8px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i:not(.toggle-pass) {
  position: absolute;
  left: 16px;
  color: var(--color-text-muted);
}

.input-wrapper input {
  width: 100%;
  padding: 14px 16px 14px 48px;
  background: rgba(255, 255, 255, 0.5);
  border: 1.5px solid var(--color-border);
  border-radius: 14px;
  font-family: var(--font-body);
  transition: all 0.3s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.05);
}

.toggle-pass {
  position: absolute;
  right: 16px;
  cursor: pointer;
  color: var(--color-text-muted);
}

.auth-footer {
  margin-top: 32px;
  text-align: center;
}

.auth-footer p {
  font-size: 0.95rem;
}

.auth-footer a {
  color: var(--color-primary);
  font-weight: 700;
  cursor: pointer;
}

.divider {
  margin: 32px 0;
  position: relative;
  border-top: 1px solid var(--color-border);
}

.divider span {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: var(--bg-main);
  padding: 0 16px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.social-auth {
  display: flex;
  gap: 16px;
}

.social-btn {
  flex: 1;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: var(--color-text-main);
  transition: all 0.3s;
}

.social-btn:hover {
  background: white;
  transform: translateY(-2px);
  box-shadow: var(--shadow-soft);
}
</style>
