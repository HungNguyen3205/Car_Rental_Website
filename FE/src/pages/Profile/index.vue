<template>
  <div class="profile-page">
    <section class="profile-header">
      <div class="app-container">
        <h1>Hồ Sơ <span>Cá Nhân</span></h1>
        <p>Quản lý thông tin tài khoản và bảo mật của bạn.</p>
      </div>
    </section>

    <section class="profile-content app-container">
      <div class="profile-grid">
        <!-- Sidebar -->
        <div class="profile-sidebar">
          <div class="user-card card glass">
            <div class="avatar-large">
              {{ user.ho_ten?.charAt(0) || 'U' }}
            </div>
            <h3>{{ user.ho_ten }}</h3>
            <p>{{ user.email }}</p>
            <div class="user-badge client">Khách hàng Thân thiết</div>
          </div>
          
          <nav class="side-nav card glass">
            <button class="nav-item" :class="{ 'active': activeTab === 'general' }" @click="activeTab = 'general'">
                <i class="fa-solid fa-user-pen"></i> Thông tin chung
            </button>
            <button class="nav-item" :class="{ 'active': activeTab === 'security' }" @click="activeTab = 'security'">
                <i class="fa-solid fa-shield-halved"></i> Bảo mật & Mật khẩu
            </button>
            <button class="nav-item" :class="{ 'active': activeTab === 'notifications' }" @click="activeTab = 'notifications'">
                <i class="fa-solid fa-bell"></i> Thông báo
            </button>
          </nav>
        </div>

        <!-- Main Content Area -->
        <div class="profile-main">
          <!-- General Info Tab -->
          <div v-if="activeTab === 'general'" class="form-card card glass animate-in">
            <div class="card-header">
              <h3>Cập nhật thông tin</h3>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label>Họ và tên</label>
                <input type="text" v-model="user.ho_ten" placeholder="Nhập họ tên...">
              </div>
              <div class="form-group">
                <label>Email (Không thể thay đổi)</label>
                <input type="email" v-model="user.email" disabled class="disabled-input">
              </div>
              <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" v-model="user.so_dien_thoai" placeholder="Nhập số điện thoại...">
              </div>
              <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" v-model="user.dia_chi" placeholder="Nhập địa chỉ của bạn...">
              </div>
            </div>
            <div class="form-actions">
                <button class="btn-primary" @click="saveProfile">Lưu thay đổi</button>
            </div>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="form-card card glass animate-in">
            <div class="card-header">
              <h3>Bảo mật tài khoản</h3>
            </div>
            <div class="security-sections">
                <div class="security-item">
                    <div class="sec-info">
                        <h4>Đổi mật khẩu</h4>
                        <p>Thay đổi mật khẩu định kỳ để bảo vệ tài khoản của bạn.</p>
                    </div>
                    <button class="btn-outline-sm">Cập nhật</button>
                </div>
                <div class="dropdown-divider"></div>
                <div class="security-item">
                    <div class="sec-info">
                        <h4>Xác thực 2 lớp (2FA)</h4>
                        <p>Thêm một lớp bảo mật bằng cách yêu cầu mã xác nhận từ điện thoại.</p>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" id="2fa">
                        <label for="2fa"></label>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="security-item">
                    <div class="sec-info">
                        <h4>Thiết bị đang đăng nhập</h4>
                        <p>Quản lý các trình duyệt và thiết bị đang truy cập tài khoản này.</p>
                    </div>
                    <button class="btn-outline-sm">Xem danh sách</button>
                </div>
            </div>
          </div>

          <!-- Notifications Tab -->
          <div v-if="activeTab === 'notifications'" class="form-card card glass animate-in">
            <div class="card-header">
              <h3>Cài đặt thông báo</h3>
            </div>
            <div class="notif-settings">
                <div class="setting-row">
                    <div class="setting-info">
                        <h4>Thông báo đặt xe</h4>
                        <p>Nhận tin nhắn khi đơn đặt xe của bạn được xác nhận.</p>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" id="notif-booking" checked>
                        <label for="notif-booking"></label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-info">
                        <h4>Ưu đãi & Khuyến mãi</h4>
                        <p>Cập nhật những chương trình giảm giá và xe mới nhất.</p>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" id="notif-promo">
                        <label for="notif-promo"></label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-info">
                        <h4>Email hệ thống</h4>
                        <p>Nhận các thông tin quan trọng về bảo mật qua Email.</p>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" id="notif-email" checked>
                        <label for="notif-email"></label>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const activeTab = ref('general');
const user = ref({
    ho_ten: '',
    email: '',
    so_dien_thoai: '',
    dia_chi: ''
});

onMounted(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
        user.value = JSON.parse(userData);
    }
});

const saveProfile = () => {
    // In a real app, call API here
    localStorage.setItem('user', JSON.stringify(user.value));
    alert("Cập nhật thông tin thành công!");
};
</script>

<style scoped>
.profile-page {
  padding-top: 100px;
  background: #f1f5f9;
  min-height: 100vh;
}

.profile-header {
  padding: 60px 0;
  background: white;
  margin-bottom: 40px;
}

.profile-header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.profile-header h1 span {
  color: var(--color-primary);
}

.profile-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 30px;
  padding-bottom: 100px;
}

.user-card {
  padding: 40px 20px;
  text-align: center;
  background: white !important;
  margin-bottom: 20px;
}

.avatar-large {
  width: 100px;
  height: 100px;
  background: var(--grad-active);
  color: white;
  border-radius: 50%;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 800;
  box-shadow: var(--shadow-glow);
}

.user-card h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 8px; }
.user-card p { font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 16px; }

.user-badge {
  display: inline-block;
  padding: 6px 16px;
  background: #f0fdf4;
  color: #22c55e;
  border-radius: 100px;
  font-size: 0.8rem;
  font-weight: 700;
}

.side-nav {
  background: white !important;
  padding: 10px;
}

.nav-item {
  width: 100%;
  text-align: left;
  padding: 14px 20px;
  background: none;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.3s;
}

.nav-item.active {
  background: #f1f5f9;
  color: var(--color-primary);
}

.form-card {
  background: white !important;
  padding: 40px;
}

.card-header {
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.form-group label {
  display: block;
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 8px;
  color: #0f172a;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  outline: none;
  transition: all 0.3s;
}

.form-group input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.disabled-input {
  background: #f8fafc;
  cursor: not-allowed;
}

.form-actions {
  margin-top: 40px;
  display: flex;
  justify-content: flex-end;
}

/* Security & Notifications */
.security-item, .setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
}

.sec-info h4, .setting-info h4 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.sec-info p, .setting-info p {
    font-size: 0.85rem;
    color: var(--color-text-muted);
}

.btn-outline-sm {
    padding: 8px 16px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input { display: none; }

.toggle-switch label {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #e2e8f0;
    border-radius: 34px;
    cursor: pointer;
    transition: 0.4s;
}

.toggle-switch label::before {
    content: "";
    position: absolute;
    height: 18px; width: 18px;
    left: 4px; bottom: 4px;
    background: white;
    border-radius: 50%;
    transition: 0.4s;
}

.toggle-switch input:checked + label {
    background: var(--color-primary);
}

.toggle-switch input:checked + label::before {
    transform: translateX(24px);
}

@media (max-width: 1024px) {
  .profile-grid { grid-template-columns: 1fr; }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
