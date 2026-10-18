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
        <h1>Thiết Lập Mật Khẩu Mới</h1>
        <p>Vui lòng nhập mật khẩu mới bảo mật cao để hoàn tất quá trình khôi phục tài khoản.</p>
      </div>

      <!-- Success State -->
      <div v-if="success" class="reset-success-box text-center animate-in">
        <div class="success-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3>Mật Khẩu Đã Được Cập Nhật!</h3>
        <p>Tất cả các phiên đăng nhập cũ trên thiết bị khác đã được thu hồi an toàn. Bạn đã được tự động đăng nhập vào hệ thống.</p>
        <router-link :to="form.account_type === 'admin' ? '/admin' : '/'" class="btn-primary w-full mt-3">
          {{ form.account_type === 'admin' ? 'Tiếp tục đến Bảng Điều Khiển Quản Trị' : 'Tiếp tục đến Trang Chủ' }}
        </router-link>
      </div>

      <!-- Form State -->
      <form v-else @submit.prevent="handleReset" class="auth-form">
        <!-- Error Banner -->
        <div v-if="errorMessage" class="error-banner animate-in">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Email (Read-only if passed via URL) -->
        <div class="form-group">
          <label for="reset-email">Địa chỉ Email</label>
          <div class="input-wrap">
            <i class="fa-regular fa-envelope"></i>
            <input 
              id="reset-email"
              type="email" 
              v-model="form.email" 
              placeholder="email@example.com" 
              class="form-control"
              required 
            />
          </div>
        </div>

        <!-- New Password -->
        <div class="form-group">
          <label for="reset-password">Mật khẩu mới</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input 
              id="reset-password"
              :type="showPassword ? 'text' : 'password'" 
              v-model="form.password" 
              placeholder="Tối thiểu 8 ký tự (chữ và số)" 
              class="form-control"
              required 
              minlength="8"
              autocomplete="new-password"
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
          <!-- Password strength hints -->
          <div class="password-hints">
            <span :class="{ 'valid': form.password.length >= 8 }">
              <i :class="form.password.length >= 8 ? 'fa-solid fa-check text-success' : 'fa-solid fa-circle-dot'"></i> Ít nhất 8 ký tự
            </span>
            <span :class="{ 'valid': /[A-Za-z]/.test(form.password) && /[0-9]/.test(form.password) }">
              <i :class="/[A-Za-z]/.test(form.password) && /[0-9]/.test(form.password) ? 'fa-solid fa-check text-success' : 'fa-solid fa-circle-dot'"></i> Có cả chữ và số
            </span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="reset-password-confirm">Nhập lại mật khẩu mới</label>
          <div class="input-wrap">
            <i class="fa-solid fa-shield-halved"></i>
            <input 
              id="reset-password-confirm"
              :type="showConfirm ? 'text' : 'password'" 
              v-model="form.password_confirmation" 
              placeholder="Xác nhận lại mật khẩu mới" 
              class="form-control"
              required 
              autocomplete="new-password"
            />
            <button 
              type="button" 
              class="toggle-pass-btn" 
              @click="showConfirm = !showConfirm"
              :aria-label="showConfirm ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
            >
              <i :class="showConfirm ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
          <span v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="field-error-text">
            Mật khẩu xác nhận chưa khớp!
          </span>
        </div>

        <button type="submit" class="btn-primary w-full mt-2" :disabled="loading || (form.password && form.password_confirmation && form.password !== form.password_confirmation)">
          <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
          <span v-else>Xác Nhận Đặt Lại Mật Khẩu</span>
        </button>

        <div class="text-center mt-3">
          <router-link to="/login" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Quay lại trang đăng nhập
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { setAuthSession } from '../../core/authStorage';
import { createToaster } from "@meforma/vue-toaster";

const route = useRoute();
const router = useRouter();
const toaster = createToaster({ position: "top-right" });

const loading = ref(false);
const success = ref(false);
const errorMessage = ref('');
const showPassword = ref(false);
const showConfirm = ref(false);

const form = reactive({
  email: '',
  token: '',
  password: '',
  password_confirmation: '',
  account_type: 'customer'
});

onMounted(() => {
  if (route.query.token) {
    form.token = String(route.query.token);
  }
  if (route.query.email) {
    form.email = String(route.query.email);
  }
  if (route.query.type) {
    form.account_type = String(route.query.type);
  }
});

const handleReset = async () => {
  errorMessage.value = '';

  if (!form.token) {
    errorMessage.value = 'Mã token khôi phục không tìm thấy trong liên kết. Vui lòng kiểm tra lại liên kết trong email!';
    return;
  }

  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Xác nhận mật khẩu mới không khớp!';
    return;
  }

  if (form.password.length < 8) {
    errorMessage.value = 'Mật khẩu phải có ít nhất 8 ký tự!';
    return;
  }

  loading.value = true;
  try {
    const res = await baseRequest.post('reset-password', {
      email: form.email,
      token: form.token,
      password: form.password,
      password_confirmation: form.password_confirmation,
      account_type: form.account_type
    });

    if (res.data && res.data.status) {
      toaster.success("Đặt lại mật khẩu thành công!");
      success.value = true;

      // Lưu phiên mới được cấp một cách chuẩn hóa
      if (res.data.token) {
        setAuthSession({
          token: res.data.token,
          user: res.data.user,
          role: form.account_type === 'admin' ? 'admin' : (res.data.user?.role || 'customer')
        });
      }
    } else {
      errorMessage.value = res.data?.message || "Đặt lại mật khẩu thất bại!";
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || "Có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại!";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 80px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: var(--bg-main, #f8fafc);
}

.auth-card {
  width: 100%;
  max-width: 460px;
  padding: 40px;
  background: #ffffff;
  border-radius: var(--radius-xl, 16px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--color-border, #e2e8f0);
}

.auth-header {
  margin-bottom: 28px;
}

.logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  margin-bottom: 20px;
}

.logo-box {
  width: 38px;
  height: 38px;
  background: var(--color-primary, #0052cc);
  color: #ffffff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-text {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
}

.logo-text span {
  color: var(--color-primary, #0052cc);
}

.auth-header h1 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
}

.auth-header p {
  color: #64748b;
  font-size: 0.9rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrap i:first-child {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  font-size: 1rem;
}

.form-control {
  width: 100%;
  padding: 12px 14px 12px 42px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.form-control:focus {
  outline: none;
  border-color: #0052cc;
  box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.15);
}

.toggle-pass-btn {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
}

.password-hints {
  display: flex;
  gap: 14px;
  margin-top: 4px;
  font-size: 0.78rem;
  color: #94a3b8;
}

.password-hints span.valid {
  color: #16a34a;
  font-weight: 600;
}

.field-error-text {
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 2px;
}

.error-banner {
  padding: 12px 16px;
  background: #fef2f2;
  border: 1px solid #fecdd3;
  color: #b91c1c;
  border-radius: 8px;
  font-size: 0.88rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.back-link {
  color: #64748b;
  text-decoration: none;
  font-size: 0.88rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.back-link:hover {
  color: #0052cc;
}

.reset-success-box {
  padding: 20px 0;
}

.success-icon {
  font-size: 3rem;
  color: #16a34a;
  margin-bottom: 14px;
}

.reset-success-box h3 {
  font-size: 1.25rem;
  color: #0f172a;
  margin-bottom: 8px;
}

.reset-success-box p {
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.5;
}
</style>
