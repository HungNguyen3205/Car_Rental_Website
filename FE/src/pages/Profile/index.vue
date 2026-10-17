<template>
  <div class="profile-page">
    <!-- Header -->
    <section class="profile-header">
      <div class="app-container">
        <span class="eyebrow-pill">Tài khoản & Thiết lập</span>
        <h1>Hồ Sơ <span>Cá Nhân</span></h1>
        <p>Quản lý thông tin tài khoản, cài đặt bảo mật và thông tin liên hệ của bạn.</p>
      </div>
    </section>

    <!-- Main Content -->
    <section class="profile-content app-container">
      <div class="profile-grid">
        <!-- Sidebar Profile Card -->
        <div class="profile-sidebar">
          <div class="user-card card">
            <div class="avatar-large">
              {{ user.ho_ten?.charAt(0) || 'U' }}
            </div>
            <h3 class="user-display-name">{{ user.ho_ten || 'Người dùng' }}</h3>
            <p class="user-display-email">{{ user.email }}</p>
            <span class="role-pill" :class="user.role === 'admin' ? 'admin' : 'client'">
              {{ user.role === 'admin' ? 'Quản Trị Viên' : 'Khách Hàng Thân Thiết' }}
            </span>
          </div>
          
          <nav class="side-nav card">
            <button 
              type="button"
              class="nav-item" 
              :class="{ 'active': activeTab === 'general' }" 
              @click="activeTab = 'general'"
            >
              <i class="fa-solid fa-user-pen"></i>
              <span>Thông tin chung</span>
            </button>
            <button 
              type="button"
              class="nav-item" 
              :class="{ 'active': activeTab === 'security' }" 
              @click="activeTab = 'security'"
            >
              <i class="fa-solid fa-shield-halved"></i>
              <span>Bảo mật & Mật khẩu</span>
            </button>
          </nav>
        </div>

        <!-- Main Form Area -->
        <div class="profile-main">
          <!-- General Info Tab -->
          <div v-if="activeTab === 'general'" class="form-card card animate-in">
            <div class="card-header">
              <h3>Thông Tin Cá Nhân</h3>
              <p>Cập nhật thông tin chi tiết để quá trình giao nhận xe diễn ra nhanh nhất.</p>
            </div>

            <form @submit.prevent="saveProfile" class="form-body">
              <div class="form-grid">
                <div class="form-group">
                  <label><i class="fa-solid fa-user"></i> Họ và tên</label>
                  <input 
                    type="text" 
                    v-model="user.ho_ten" 
                    placeholder="Nguyễn Văn A" 
                    class="form-control"
                    required
                  />
                </div>

                <div class="form-group">
                  <label><i class="fa-solid fa-envelope"></i> Email (Không thể thay đổi)</label>
                  <input 
                    type="email" 
                    :value="user.email" 
                    disabled 
                    class="form-control disabled-input" 
                  />
                </div>

                <div class="form-group">
                  <label><i class="fa-solid fa-phone"></i> Số điện thoại</label>
                  <input 
                    type="tel" 
                    v-model="user.so_dien_thoai" 
                    placeholder="09xx xxx xxx" 
                    class="form-control"
                  />
                </div>

                <div class="form-group">
                  <label><i class="fa-solid fa-calendar-days"></i> Ngày sinh</label>
                  <input 
                    type="date" 
                    v-model="user.ngay_sinh" 
                    class="form-control" 
                  />
                </div>

                <div class="form-group">
                  <label><i class="fa-solid fa-venus-mars"></i> Giới tính</label>
                  <select v-model="user.gioi_tinh" class="form-select">
                    <option :value="1">Nam</option>
                    <option :value="0">Nữ</option>
                    <option :value="2">Khác</option>
                  </select>
                </div>

                <div class="form-group full-width">
                  <label><i class="fa-solid fa-location-dot"></i> Địa chỉ thường trú</label>
                  <input 
                    type="text" 
                    v-model="user.dia_chi" 
                    placeholder="Số nhà, tên đường, quận/huyện, tỉnh/thành..." 
                    class="form-control"
                  />
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="savingProfile">
                  <i v-if="savingProfile" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else>Lưu thay đổi</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="form-card card animate-in">
            <div class="card-header">
              <h3>Đổi Mật Khẩu</h3>
              <p>Khuyến nghị sử dụng mật khẩu mạnh với ít nhất 6 ký tự để bảo vệ tài khoản.</p>
            </div>

            <form @submit.prevent="changePassword" class="form-body">
              <div class="password-form-grid">
                <div class="form-group">
                  <label><i class="fa-solid fa-lock"></i> Mật khẩu mới</label>
                  <div class="password-input-wrap">
                    <input 
                      :type="showNewPass ? 'text' : 'password'" 
                      v-model="passForm.newPassword" 
                      placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                      class="form-control"
                      autocomplete="new-password"
                      required
                      minlength="6"
                    />
                    <button 
                      type="button" 
                      class="toggle-pass-btn" 
                      @click="showNewPass = !showNewPass"
                      :aria-label="showNewPass ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                    >
                      <i :class="showNewPass ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fa-solid fa-lock-open"></i> Nhập lại mật khẩu mới</label>
                  <div class="password-input-wrap">
                    <input 
                      :type="showConfirmPass ? 'text' : 'password'" 
                      v-model="passForm.confirmPassword" 
                      placeholder="Xác nhận lại mật khẩu mới"
                      class="form-control"
                      autocomplete="new-password"
                      required
                    />
                    <button 
                      type="button" 
                      class="toggle-pass-btn" 
                      @click="showConfirmPass = !showConfirmPass"
                      :aria-label="showConfirmPass ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                    >
                      <i :class="showConfirmPass ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <div v-if="passError" class="pass-error-banner">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <span>{{ passError }}</span>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="savingPassword">
                  <i v-if="savingPassword" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else>Cập nhật mật khẩu</span>
                </button>
              </div>
            </form>

            <div class="security-divider"></div>

            <!-- Future roadmap section -->
            <div class="security-info-box">
              <div class="info-icon">
                <i class="fa-solid fa-shield-halved"></i>
              </div>
              <div>
                <h4>Xác thực 2 yếu tố (2FA)</h4>
                <p>Tính năng bảo mật nâng cao qua SMS và Authenticator đang trong lộ trình phát triển và sẽ sớm sẵn sàng.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const activeTab = ref('general');

const user = ref({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  ngay_sinh: '',
  gioi_tinh: 1,
  dia_chi: '',
  role: ''
});

const savingProfile = ref(false);
const savingPassword = ref(false);

const passForm = reactive({
  newPassword: '',
  confirmPassword: ''
});

const showNewPass = ref(false);
const showConfirmPass = ref(false);
const passError = ref('');

const fetchProfile = async () => {
  try {
    const res = await baseRequest.get('user/profile/data');
    if (res.data && res.data.status) {
      const storedUser = JSON.parse(localStorage.getItem('user') || '{}');
      user.value = {
        ...res.data.data,
        role: res.data.data.role || storedUser.role || 'client'
      };
      localStorage.setItem('user', JSON.stringify(user.value));
    }
  } catch (err) {
    const userData = localStorage.getItem('user');
    if (userData) {
      try {
        user.value = JSON.parse(userData);
      } catch (e) {
        console.error("Lỗi đọc cache hồ sơ:", e);
      }
    }
  }
};

const saveProfile = async () => {
  savingProfile.value = true;
  try {
    const res = await baseRequest.post('user/profile/update', {
      ho_ten: user.value.ho_ten,
      so_dien_thoai: user.value.so_dien_thoai,
      ngay_sinh: user.value.ngay_sinh,
      gioi_tinh: user.value.gioi_tinh,
      dia_chi: user.value.dia_chi
    });

    if (res.data && res.data.status) {
      localStorage.setItem('user', JSON.stringify(user.value));
      toaster.success(res.data.message || "Cập nhật hồ sơ thành công!");
    } else {
      toaster.error(res.data?.message || "Cập nhật thất bại!");
    }
  } catch (err) {
    toaster.error("Không thể kết nối đến máy chủ!");
  } finally {
    savingProfile.value = false;
  }
};

const changePassword = async () => {
  passError.value = '';

  if (passForm.newPassword.length < 6) {
    passError.value = 'Mật khẩu mới phải có tối thiểu 6 ký tự!';
    return;
  }

  if (passForm.newPassword !== passForm.confirmPassword) {
    passError.value = 'Mật khẩu xác nhận không trùng khớp!';
    return;
  }

  savingPassword.value = true;
  try {
    const res = await baseRequest.post('user/profile/doi-mat-khau', {
      password: passForm.newPassword
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đổi mật khẩu thành công!");
      passForm.newPassword = '';
      passForm.confirmPassword = '';
    } else {
      toaster.error(res.data?.message || "Đổi mật khẩu thất bại!");
    }
  } catch (err) {
    toaster.error("Lỗi máy chủ khi đổi mật khẩu!");
  } finally {
    savingPassword.value = false;
  }
};

onMounted(fetchProfile);
</script>

<style scoped>
.profile-page {
  padding-bottom: 80px;
  min-height: 100vh;
}

.profile-header {
  padding: 130px 0 40px;
  background: linear-gradient(180deg, #ffffff 0%, var(--bg-main) 100%);
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 40px;
}

.eyebrow-pill {
  display: inline-block;
  padding: 4px 12px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: 0.775rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.profile-header h1 {
  font-size: clamp(2rem, 4vw, 2.8rem);
  margin-bottom: 8px;
}

.profile-header h1 span {
  color: var(--color-primary);
}

/* Grid Layout */
.profile-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 32px;
  align-items: flex-start;
}

/* Sidebar */
.user-card {
  padding: 32px 20px;
  text-align: center;
  margin-bottom: 20px;
  background: #ffffff;
}

.avatar-large {
  width: 80px;
  height: 80px;
  background: var(--grad-active);
  color: #ffffff;
  border-radius: 50%;
  margin: 0 auto 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  box-shadow: var(--shadow-glow);
}

.user-display-name {
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.user-display-email {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin-bottom: 14px;
  word-break: break-all;
}

.role-pill {
  display: inline-block;
  padding: 4px 14px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
}

.role-pill.client {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.role-pill.admin {
  background: #eff6ff;
  color: #2563eb;
}

/* Nav Menu */
.side-nav {
  padding: 10px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--color-text-muted);
  transition: all var(--duration-fast);
}

.nav-item i {
  font-size: 1rem;
  width: 20px;
}

.nav-item:hover {
  background: var(--bg-surface-sub);
  color: var(--color-text-main);
}

.nav-item.active {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.nav-item.active i {
  color: var(--color-primary);
}

/* Form Card */
.form-card {
  padding: 36px;
  background: #ffffff;
}

.card-header {
  margin-bottom: 28px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--color-border);
}

.card-header h3 {
  font-size: 1.4rem;
  font-weight: 800;
  margin-bottom: 6px;
}

.card-header p {
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.full-width {
  grid-column: span 2;
}

.disabled-input {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-actions {
  margin-top: 28px;
  display: flex;
  justify-content: flex-end;
}

/* Password Form */
.password-form-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 480px;
}

.password-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrap input {
  padding-right: 44px;
}

.toggle-pass-btn {
  position: absolute;
  right: 12px;
  color: var(--color-text-muted);
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
}

.pass-error-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: var(--color-danger-bg);
  color: var(--color-danger);
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  font-weight: 600;
}

.security-divider {
  height: 1px;
  background: var(--color-border);
  margin: 36px 0 24px;
}

.security-info-box {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-lg);
}

.info-icon {
  width: 44px;
  height: 44px;
  background: #ffffff;
  color: var(--color-primary);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: var(--shadow-xs);
  flex-shrink: 0;
}

.security-info-box h4 {
  font-size: 0.95rem;
  margin-bottom: 4px;
}

.security-info-box p {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

@media (max-width: 900px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  .form-grid {
    grid-template-columns: 1fr;
  }
  .full-width {
    grid-column: span 1;
  }
  .form-card {
    padding: 24px;
  }
}
</style>
