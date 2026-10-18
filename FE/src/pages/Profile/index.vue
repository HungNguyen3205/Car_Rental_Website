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
              <p>Cập nhật mật khẩu định kỳ để nâng cao tính bảo mật cho tài khoản của bạn.</p>
            </div>

            <form @submit.prevent="changePassword" class="form-body">
              <div class="password-form-grid">
                <!-- Current Password -->
                <div class="form-group">
                  <label><i class="fa-solid fa-key"></i> Mật khẩu hiện tại</label>
                  <div class="password-input-wrap">
                    <input 
                      :type="showCurrentPass ? 'text' : 'password'" 
                      v-model="passForm.current_password" 
                      placeholder="Nhập mật khẩu đang dùng"
                      class="form-control"
                      autocomplete="current-password"
                      required
                    />
                    <button 
                      type="button" 
                      class="toggle-pass-btn" 
                      @click="showCurrentPass = !showCurrentPass"
                      :aria-label="showCurrentPass ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                    >
                      <i :class="showCurrentPass ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                    </button>
                  </div>
                  <span class="field-hint">Nếu tài khoản của bạn đăng ký qua mạng xã hội và chưa có mật khẩu, bạn có thể để trống trường này.</span>
                </div>

                <!-- New Password -->
                <div class="form-group">
                  <label><i class="fa-solid fa-lock"></i> Mật khẩu mới</label>
                  <div class="password-input-wrap">
                    <input 
                      :type="showNewPass ? 'text' : 'password'" 
                      v-model="passForm.newPassword" 
                      placeholder="Tối thiểu 8 ký tự (chữ và số)"
                      class="form-control"
                      autocomplete="new-password"
                      required
                      minlength="8"
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
                  <!-- Password strength hints -->
                  <div class="password-strength-indicators">
                    <span :class="{ valid: passForm.newPassword.length >= 8 }">
                      <i :class="passForm.newPassword.length >= 8 ? 'fa-solid fa-check text-success' : 'fa-solid fa-circle-dot'"></i> Ít nhất 8 ký tự
                    </span>
                    <span :class="{ valid: /[A-Za-z]/.test(passForm.newPassword) && /[0-9]/.test(passForm.newPassword) }">
                      <i :class="/[A-Za-z]/.test(passForm.newPassword) && /[0-9]/.test(passForm.newPassword) ? 'fa-solid fa-check text-success' : 'fa-solid fa-circle-dot'"></i> Bao gồm chữ cái & số
                    </span>
                  </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                  <label><i class="fa-solid fa-shield-halved"></i> Xác nhận mật khẩu mới</label>
                  <div class="password-input-wrap">
                    <input 
                      :type="showConfirmPass ? 'text' : 'password'" 
                      v-model="passForm.confirmPassword" 
                      placeholder="Nhập lại mật khẩu mới"
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
                  <span v-if="passForm.confirmPassword && passForm.newPassword === passForm.confirmPassword" class="text-success text-sm mt-1">
                    <i class="fa-solid fa-circle-check"></i> Mật khẩu xác nhận trùng khớp
                  </span>
                </div>

                <!-- Revoke Other Sessions Option -->
                <div class="checkbox-option-group">
                  <label class="custom-checkbox-label">
                    <input type="checkbox" v-model="passForm.revoke_other_sessions" />
                    <span class="checkbox-text">
                      <strong>Đăng xuất khỏi tất cả các thiết bị khác</strong>
                      <small>Sau khi đổi mật khẩu, phiên trên mọi máy tính và điện thoại khác sẽ bị thu hồi ngay lập tức để phòng tránh truy cập trái phép.</small>
                    </span>
                  </label>
                </div>

                <div v-if="passError" class="pass-error-banner">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <span>{{ passError }}</span>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="savingPassword">
                  <i v-if="savingPassword" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else><i class="fa-solid fa-floppy-disk"></i> Cập nhật mật khẩu</span>
                </button>
              </div>
            </form>

            <div class="security-divider"></div>

            <!-- Session Management Section -->
            <div class="session-management-section">
              <div class="section-title-wrap">
                <div class="title-icon">
                  <i class="fa-solid fa-laptop-file"></i>
                </div>
                <div>
                  <h4>Quản Lý Phiên Đăng Nhập</h4>
                  <p>Xem trạng thái phiên làm việc hiện tại và thu hồi quyền truy cập khi cần thiết.</p>
                </div>
              </div>

              <div class="session-card">
                <div class="session-details">
                  <div class="session-badge active">
                    <span class="pulse-dot"></span>
                    Phiên hiện tại (Thiết bị này)
                  </div>
                  <p class="session-sub">
                    Được xác thực qua Bearer Token bảo mật. Nếu tài khoản bị quản trị viên khóa, các phiên đang mở sẽ lập tức bị vô hiệu hóa.
                  </p>
                </div>
                <div class="session-actions">
                  <button 
                    type="button" 
                    class="btn-logout-current" 
                    @click="handleLogoutCurrent" 
                    :disabled="loggingOutCurrent || loggingOutAll"
                  >
                    <i v-if="loggingOutCurrent" class="fa-solid fa-spinner fa-spin"></i>
                    <span v-else><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất thiết bị này</span>
                  </button>
                  <button 
                    type="button" 
                    class="btn-logout-all" 
                    @click="handleLogoutAll" 
                    :disabled="loggingOutCurrent || loggingOutAll"
                  >
                    <i v-if="loggingOutAll" class="fa-solid fa-spinner fa-spin"></i>
                    <span v-else><i class="fa-solid fa-power-off"></i> Đăng xuất tất cả thiết bị</span>
                  </button>
                </div>
              </div>

              <div class="session-policy-note">
                <i class="fa-solid fa-shield-cat"></i>
                <span>
                  <strong>Chính sách thu hồi phiên:</strong> "Đăng xuất thiết bị này" chỉ hủy mã token của trình duyệt hiện tại. "Đăng xuất tất cả thiết bị" sẽ thu hồi triệt để tất cả các phiên đăng nhập đang hoạt động trên máy tính bảng, điện thoại và các trình duyệt khác.
                </span>
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
import { useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const router = useRouter();
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
const loggingOutCurrent = ref(false);
const loggingOutAll = ref(false);

const passForm = reactive({
  current_password: '',
  newPassword: '',
  confirmPassword: '',
  revoke_other_sessions: true
});

const showCurrentPass = ref(false);
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

  if (passForm.newPassword.length < 8) {
    passError.value = 'Mật khẩu mới phải có ít nhất 8 ký tự!';
    return;
  }

  const hasLetter = /[A-Za-z]/.test(passForm.newPassword);
  const hasNumber = /[0-9]/.test(passForm.newPassword);
  if (!hasLetter || !hasNumber) {
    passError.value = 'Mật khẩu mới phải bao gồm cả chữ cái và số!';
    return;
  }

  if (passForm.newPassword !== passForm.confirmPassword) {
    passError.value = 'Mật khẩu xác nhận không trùng khớp!';
    return;
  }

  savingPassword.value = true;
  try {
    const res = await baseRequest.post('change-password', {
      current_password: passForm.current_password,
      password: passForm.newPassword,
      password_confirmation: passForm.confirmPassword,
      revoke_other_sessions: passForm.revoke_other_sessions
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đổi mật khẩu thành công!");
      passForm.current_password = '';
      passForm.newPassword = '';
      passForm.confirmPassword = '';
    } else {
      passError.value = res.data?.message || "Đổi mật khẩu thất bại!";
      toaster.error(res.data?.message || "Đổi mật khẩu thất bại!");
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || "Lỗi máy chủ khi đổi mật khẩu!";
    passError.value = errorMsg;
    toaster.error(errorMsg);
  } finally {
    savingPassword.value = false;
  }
};

const handleLogoutCurrent = async () => {
  loggingOutCurrent.value = true;
  try {
    const res = await baseRequest.post('logout');
    toaster.success(res.data?.message || 'Đã đăng xuất khỏi thiết bị này!');
  } catch (e) {
    console.warn('Lỗi khi đăng xuất:', e);
  } finally {
    localStorage.removeItem('token_client');
    localStorage.removeItem('token_admin');
    localStorage.removeItem('user');
    localStorage.removeItem('user_role');
    loggingOutCurrent.value = false;
    router.push('/login');
  }
};

const handleLogoutAll = async () => {
  if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi tất cả các thiết bị?')) return;
  loggingOutAll.value = true;
  try {
    const res = await baseRequest.post('logout-all');
    toaster.success(res.data?.message || 'Đã đăng xuất khỏi tất cả các thiết bị thành công!');
  } catch (e) {
    toaster.error(e.response?.data?.message || 'Có lỗi xảy ra khi đăng xuất khỏi tất cả thiết bị.');
  } finally {
    localStorage.removeItem('token_client');
    localStorage.removeItem('token_admin');
    localStorage.removeItem('user');
    localStorage.removeItem('user_role');
    loggingOutAll.value = false;
    router.push('/login');
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

.field-hint {
  font-size: 0.775rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  margin-top: 4px;
  display: block;
}

.password-strength-indicators {
  display: flex;
  gap: 12px;
  margin-top: 6px;
  font-size: 0.775rem;
  color: var(--color-text-muted);
}

.password-strength-indicators span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.password-strength-indicators span.valid {
  color: #059669;
  font-weight: 600;
}

.text-success {
  color: #059669;
}

.text-sm {
  font-size: 0.8rem;
}

.mt-1 {
  margin-top: 4px;
}

.checkbox-option-group {
  margin-top: 4px;
  padding: 12px 14px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.custom-checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}

.custom-checkbox-label input[type="checkbox"] {
  margin-top: 3px;
  accent-color: var(--color-primary);
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.checkbox-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.checkbox-text strong {
  font-size: 0.875rem;
  color: var(--color-text-main);
}

.checkbox-text small {
  font-size: 0.775rem;
  color: var(--color-text-muted);
  line-height: 1.4;
}

/* Session Management */
.session-management-section {
  margin-top: 8px;
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}

.title-icon {
  width: 42px;
  height: 42px;
  border-radius: var(--radius-md);
  background: rgba(16, 185, 129, 0.1);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.section-title-wrap h4 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 2px;
}

.section-title-wrap p {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.session-card {
  background: var(--bg-surface-sub);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

.session-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  background: rgba(16, 185, 129, 0.15);
  color: #047857;
  font-size: 0.825rem;
  font-weight: 700;
  border-radius: var(--radius-full);
  margin-bottom: 6px;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
}

.session-sub {
  font-size: 0.825rem;
  color: var(--color-text-muted);
  max-width: 480px;
  line-height: 1.45;
}

.session-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-logout-current {
  padding: 9px 16px;
  border-radius: var(--radius-md);
  background: #ffffff;
  border: 1px solid var(--color-border);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-main);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all var(--duration-fast);
}

.btn-logout-current:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-logout-all {
  padding: 9px 16px;
  border-radius: var(--radius-md);
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  font-size: 0.85rem;
  font-weight: 600;
  color: #dc2626;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all var(--duration-fast);
}

.btn-logout-all:hover:not(:disabled) {
  background: #ef4444;
  color: #ffffff;
}

.session-policy-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #f8fafc;
  border-left: 3px solid var(--color-primary);
  padding: 12px 14px;
  border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  margin-top: 14px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  line-height: 1.45;
}

.session-policy-note i {
  color: var(--color-primary);
  font-size: 0.95rem;
  margin-top: 2px;
  flex-shrink: 0;
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
