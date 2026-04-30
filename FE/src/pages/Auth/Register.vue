<template>
  <div class="auth-page register-theme">
    <!-- Animated Background -->
    <div class="auth-bg">
      <div class="glass-morph"></div>
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
    </div>

    <div class="auth-container">
      <div class="auth-card glass animate-in">
        <div class="auth-header">
          <router-link to="/" class="auth-logo">
            <i class="fa-solid fa-car-side"></i>
            <span>DriveRent</span>
          </router-link>
          <h2>Tạo Tài Khoản Mới</h2>
          <p>Tham gia cộng đồng thuê xe điện thông minh cùng DriveRent</p>
        </div>

        <form @submit.prevent="handleRegister" class="auth-form">
          <div class="form-grid">
            <div class="form-group">
              <label><i class="fa-solid fa-user"></i> Họ và tên</label>
              <input 
                type="text" 
                v-model="registerData.ho_ten" 
                placeholder="Nguyễn Văn A" 
                required
              />
            </div>
            
            <div class="form-group">
              <label><i class="fa-solid fa-envelope"></i> Email</label>
              <input 
                type="email" 
                v-model="registerData.email" 
                placeholder="example@gmail.com" 
                required
              />
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-phone"></i> Số điện thoại</label>
              <input 
                type="tel" 
                v-model="registerData.so_dien_thoai" 
                placeholder="09xx xxx xxx" 
                required
              />
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-lock"></i> Mật khẩu</label>
              <input 
                type="password" 
                v-model="registerData.password" 
                placeholder="••••••••" 
                required
              />
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-calendar-days"></i> Ngày sinh (tùy chọn)</label>
              <input type="date" v-model="registerData.ngay_sinh" />
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-venus-mars"></i> Giới tính</label>
              <select v-model="registerData.gioi_tinh" class="auth-select">
                <option value="1">Nam</option>
                <option value="0">Nữ</option>
                <option value="2">Khác</option>
              </select>
            </div>

            <div class="form-group full-width">
              <label><i class="fa-solid fa-location-dot"></i> Địa chỉ (tùy chọn)</label>
              <input 
                type="text" 
                v-model="registerData.dia_chi" 
                placeholder="Số nhà, tên đường, quận/huyện..." 
              />
            </div>
          </div>

          <div class="form-options">
            <label class="checkbox-container">
              <input type="checkbox" v-model="registerData.agreed">
              <span class="checkmark"></span>
              Tôi đồng ý với <a href="#">Điều khoản sử dụng</a>
            </label>
          </div>

          <button type="submit" class="btn-auth" :disabled="loading || !registerData.agreed">
            <span v-if="!loading">ĐĂNG KÝ NGAY</span>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
          </button>
        </form>

        <div class="auth-divider">
          <span>Hoặc đăng ký nhanh qua</span>
        </div>

        <div class="social-auth">
          <button @click="loginSocial('google')" class="btn-social google">
            <img src="https://www.gstatic.com/images/branding/product/2x/googleg_48dp.png" alt="Google">
            Google
          </button>
          <button @click="loginSocial('facebook')" class="btn-social facebook">
            <i class="fa-brands fa-facebook"></i>
            Facebook
          </button>
        </div>

        <p class="auth-footer">
          Đã có tài khoản? <router-link to="/login">Đăng nhập</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';

const router = useRouter();
const loading = ref(false);

const registerData = reactive({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  password: '',
  ngay_sinh: '',
  gioi_tinh: 1,
  dia_chi: '',
  agreed: false
});

const handleRegister = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.post('register', registerData);
    if (res.data.status) {
      alert("Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.");
      router.push('/login');
    } else {
      alert(res.data.message || "Đăng ký không thành công");
    }
  } catch (error) {
    alert(error.response?.data?.message || "Lỗi kết nối máy chủ");
  } finally {
    loading.value = false;
  }
};

const loginSocial = (provider) => {
  window.location.href = `http://localhost:8000/api/auth/${provider}/redirect`;
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  background: #0f172a;
  font-family: 'Inter', sans-serif;
  padding: 40px 20px;
}

.auth-bg {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.glass-morph {
  position: absolute;
  inset: 0;
  backdrop-filter: blur(100px);
  background: rgba(15, 23, 42, 0.7);
}

.circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
}

.circle-1 {
  width: 400px;
  height: 400px;
  background: rgba(37, 99, 235, 0.2);
  top: -100px;
  right: -100px;
}

.circle-2 {
  width: 500px;
  height: 500px;
  background: rgba(139, 92, 246, 0.15);
  bottom: -150px;
  left: -150px;
}

.auth-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 520px;
}

.auth-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 32px;
  padding: 48px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.auth-header {
  margin-bottom: 32px;
}

.auth-logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: white;
  text-decoration: none;
  font-weight: 800;
  font-size: 1.5rem;
  margin-bottom: 24px;
}

.auth-logo i { color: #3b82f6; }

.auth-header h2 {
  color: white;
  font-size: 1.8rem;
  font-weight: 800;
  margin-bottom: 8px;
}

.auth-header p {
  color: #94a3b8;
  font-size: 0.95rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  text-align: left;
  margin-bottom: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #cbd5e1;
  font-size: 0.85rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-group label i { color: #3b82f6; font-size: 0.8rem; }

.form-group input, .auth-select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 16px;
  border-radius: 12px;
  color: white;
  outline: none;
  transition: all 0.3s;
}

.auth-select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px;
}

.auth-select option {
  background: #1e293b;
  color: white;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-group input:focus, .auth-select:focus {
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

.form-options {
  margin-bottom: 24px;
  text-align: left;
}

/* Custom Checkbox */
.checkbox-container {
  display: block;
  position: relative;
  padding-left: 28px;
  cursor: pointer;
  font-size: 0.85rem;
  color: #94a3b8;
  user-select: none;
}

.checkbox-container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 18px;
  width: 18px;
  background-color: rgba(255,255,255,0.1);
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,0.1);
}

.checkbox-container:hover input ~ .checkmark {
  background-color: rgba(255,255,255,0.15);
}

.checkbox-container input:checked ~ .checkmark {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

.checkbox-container input:checked ~ .checkmark:after {
  display: block;
}

.checkbox-container .checkmark:after {
  left: 6px;
  top: 2px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-container a { color: #3b82f6; text-decoration: none; font-weight: 600; }

.btn-auth {
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  font-weight: 800;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
}

.btn-auth:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(37, 99, 235, 0.3);
}

.btn-auth:disabled { opacity: 0.6; cursor: not-allowed; }

.auth-divider {
  margin: 32px 0;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-divider::before {
  content: '';
  position: absolute;
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
}

.auth-divider span {
  position: relative;
  background: #111a2e;
  padding: 0 16px;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.social-auth {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 32px;
}

.btn-social {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-social:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.btn-social img { width: 18px; }
.btn-social i { font-size: 1.1rem; }
.btn-social.facebook i { color: #1877f2; }

.auth-footer {
  color: #94a3b8;
  font-size: 0.95rem;
}

.auth-footer a {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 700;
  margin-left: 4px;
}

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .auth-card { padding: 32px 24px; }
}

.animate-in {
  animation: fadeInDown 0.6s ease-out;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
