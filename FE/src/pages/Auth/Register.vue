<template>
  <div class="auth-page register-page">
    <div class="auth-card card animate-in">
      <div class="auth-header text-center">
        <router-link to="/" class="logo">
          <div class="logo-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
            </svg>
          </div>
          <span class="logo-text">Drive<span>Rent</span></span>
        </router-link>
        <h1>Tạo Tài Khoản Mới</h1>
        <p>Tham gia cộng đồng thuê xe điện thông minh cùng DriveRent</p>
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-grid">
          <!-- Full Name -->
          <div class="form-group">
            <label for="reg-name"><i class="fa-solid fa-user"></i> Họ và tên</label>
            <input 
              id="reg-name"
              type="text" 
              v-model="registerData.ho_ten" 
              placeholder="Nguyễn Văn A" 
              class="form-control"
              autocomplete="name"
              required
            />
          </div>
          
          <!-- Email -->
          <div class="form-group">
            <label for="reg-email"><i class="fa-solid fa-envelope"></i> Email</label>
            <input 
              id="reg-email"
              type="email" 
              v-model="registerData.email" 
              placeholder="example@gmail.com" 
              class="form-control"
              autocomplete="email"
              required
            />
          </div>

          <!-- Phone -->
          <div class="form-group">
            <label for="reg-phone"><i class="fa-solid fa-phone"></i> Số điện thoại</label>
            <input 
              id="reg-phone"
              type="tel" 
              v-model="registerData.so_dien_thoai" 
              placeholder="09xx xxx xxx" 
              class="form-control"
              autocomplete="tel"
              required
            />
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="reg-pass"><i class="fa-solid fa-lock"></i> Mật khẩu</label>
            <div class="password-input-wrap">
              <input 
                id="reg-pass"
                :type="showPassword ? 'text' : 'password'" 
                v-model="registerData.password" 
                placeholder="••••••••" 
                class="form-control"
                autocomplete="new-password"
                minlength="6"
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

          <!-- Birth Date -->
          <div class="form-group">
            <label for="reg-bday"><i class="fa-solid fa-calendar-days"></i> Ngày sinh (tùy chọn)</label>
            <input 
              id="reg-bday"
              type="date" 
              v-model="registerData.ngay_sinh" 
              class="form-control" 
            />
          </div>

          <!-- Gender -->
          <div class="form-group">
            <label for="reg-gender"><i class="fa-solid fa-venus-mars"></i> Giới tính</label>
            <select id="reg-gender" v-model="registerData.gioi_tinh" class="form-select">
              <option :value="1">Nam</option>
              <option :value="0">Nữ</option>
              <option :value="2">Khác</option>
            </select>
          </div>

          <!-- Address -->
          <div class="form-group full-width">
            <label for="reg-addr"><i class="fa-solid fa-location-dot"></i> Địa chỉ (tùy chọn)</label>
            <input 
              id="reg-addr"
              type="text" 
              v-model="registerData.dia_chi" 
              placeholder="Số nhà, tên đường, quận/huyện..." 
              class="form-control"
            />
          </div>
        </div>

        <div class="form-terms">
          <label class="checkbox-label">
            <input type="checkbox" v-model="registerData.agreed" required />
            <span>Tôi đồng ý với <router-link to="/about">Điều khoản sử dụng</router-link> và chính sách của DriveRent</span>
          </label>
        </div>

        <button type="submit" class="btn-primary w-full mt-3" :disabled="loading || !registerData.agreed">
          <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
          <span v-else>Đăng Ký Tài Khoản</span>
        </button>
      </form>

      <div class="auth-divider">
        <span>Hoặc đăng ký nhanh qua</span>
      </div>

      <div class="social-auth-grid">
        <button type="button" @click="loginSocial('google')" class="btn-social google">
          <i class="fa-brands fa-google"></i>
          <span>Google</span>
        </button>
        <button type="button" @click="loginSocial('facebook')" class="btn-social facebook">
          <i class="fa-brands fa-facebook-f"></i>
          <span>Facebook</span>
        </button>
      </div>

      <p class="auth-footer-text text-center">
        Đã có tài khoản? <router-link to="/login">Đăng nhập tại đây</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const router = useRouter();
const loading = ref(false);
const showPassword = ref(false);

const registerData = reactive({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  password: '',
  ngay_sinh: '',
  gioi_tinh: 1,
  dia_chi: '',
  agreed: true
});

const handleRegister = async () => {
  if (registerData.password.length < 6) {
    toaster.warning("Mật khẩu cần tối thiểu 6 ký tự!");
    return;
  }

  loading.value = true;
  try {
    const res = await baseRequest.post('register', registerData);
    if (res.data && res.data.status) {
      toaster.success("Đăng ký thành công! Vui lòng kiểm tra email kích hoạt tài khoản.");
      router.push('/login');
    } else {
      toaster.error(res.data?.message || "Đăng ký không thành công!");
    }
  } catch (error) {
    const err = error.response?.data?.message || "Lỗi kết nối máy chủ khi đăng ký!";
    toaster.error(err);
  } finally {
    loading.value = false;
  }
};

const loginSocial = (provider) => {
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
  padding: 120px 20px 60px;
}

.auth-card {
  width: 100%;
  max-width: 640px;
  padding: 44px 40px;
  background: #ffffff;
  border-radius: var(--radius-xl);
}

.auth-header {
  margin-bottom: 28px;
}

.logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.logo-box {
  width: 38px;
  height: 38px;
  background: var(--grad-active);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.35rem;
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
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.full-width {
  grid-column: span 2;
}

.password-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrap input {
  padding-right: 42px;
}

.toggle-pass-btn {
  position: absolute;
  right: 12px;
  color: var(--color-text-muted);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.form-terms {
  margin: 12px 0 16px;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.85rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.checkbox-label input {
  margin-top: 3px;
  accent-color: var(--color-primary);
}

.checkbox-label a {
  color: var(--color-primary);
  font-weight: 600;
}

.auth-divider {
  position: relative;
  text-align: center;
  margin: 24px 0 20px;
}

.auth-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--color-border);
}

.auth-divider span {
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

.auth-footer-text {
  margin-top: 24px;
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.auth-footer-text a {
  color: var(--color-primary);
  font-weight: 700;
}

.auth-footer-text a:hover {
  text-decoration: underline;
}

.w-full {
  width: 100%;
}

.mt-3 {
  margin-top: 12px;
}

.text-center {
  text-align: center;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .full-width {
    grid-column: span 1;
  }
  .auth-card {
    padding: 28px 20px;
  }
}
</style>
