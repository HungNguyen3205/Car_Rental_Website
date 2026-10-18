<template>
  <footer class="site-footer">
    <div class="footer-top app-container">
      <!-- Col 1: Brand Info -->
      <div class="footer-col brand-col">
        <router-link to="/" class="footer-logo">
          <div class="logo-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
            </svg>
          </div>
          <span class="logo-text">Drive<span>Rent</span></span>
        </router-link>
        <p class="brand-desc">
          Nền tảng thuê xe điện thông minh hàng đầu, mang đến trải nghiệm di chuyển xanh, êm ái và đẳng cấp trên từng chặng đường.
        </p>
        <div class="brand-features">
          <div class="feat-badge">
            <i class="fa-solid fa-leaf"></i> 100% Xe Điện Xanh
          </div>
          <div class="feat-badge">
            <i class="fa-solid fa-bolt"></i> Xác nhận nhanh chóng
          </div>
        </div>
      </div>

      <!-- Col 2: Navigation Links -->
      <div class="footer-col">
        <h4 class="col-title">Dịch Vụ</h4>
        <ul class="footer-links">
          <li><router-link to="/fleet">Khám phá đội xe</router-link></li>
          <li><router-link to="/process">Quy trình thuê xe</router-link></li>
          <li><router-link to="/about">Về DriveRent</router-link></li>
          <li><router-link to="/contact">Trung tâm hỗ trợ</router-link></li>
        </ul>
      </div>

      <!-- Col 3: Customer Links -->
      <div class="footer-col">
        <h4 class="col-title">Khách Hàng</h4>
        <ul class="footer-links">
          <li><router-link to="/my-bookings">Quản lý chuyến đi</router-link></li>
          <li><router-link to="/profile">Hồ sơ cá nhân</router-link></li>
          <li><router-link to="/login">Đăng nhập tài khoản</router-link></li>
          <li><router-link to="/register">Đăng ký thành viên</router-link></li>
        </ul>
      </div>

      <!-- Col 4: Contact & Support -->
      <div class="footer-col contact-col">
        <h4 class="col-title">Kết Nối 24/7</h4>
        <div class="contact-card">
          <div class="contact-item">
            <i class="fa-solid fa-phone-volume"></i>
            <div>
              <span class="label">Hotline tư vấn</span>
              <strong>{{ settings.hotline || '1900 1234' }}</strong>
            </div>
          </div>
          <div class="contact-item">
            <i class="fa-solid fa-robot"></i>
            <div>
              <span class="label">Trợ lý ảo thông minh</span>
              <span>Hỗ trợ trực tuyến 24/7</span>
            </div>
          </div>
          <div class="contact-item">
            <i class="fa-solid fa-location-dot"></i>
            <div>
              <span class="label">Mạng lưới giao xe</span>
              <span>{{ serviceLocationsText }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
      <div class="app-container bottom-container">
        <p class="copyright">
          © {{ currentYear }} <strong>{{ settings.site_name || 'DriveRent' }}</strong>. Bản quyền thuộc về nền tảng thuê xe điện thông minh.
        </p>
        <div class="bottom-links">
          <span>Tiêu chuẩn vận hành thông minh & an toàn</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useSystemSettings } from '../../../core/settingsStore';

const { settings, fetchSettings } = useSystemSettings();

const currentYear = new Date().getFullYear();

const serviceLocationsText = computed(() => {
  if (Array.isArray(settings.service_locations)) {
    return settings.service_locations.join(', ');
  }
  return 'Hà Nội, TP. HCM, Đà Nẵng';
});

onMounted(() => {
  fetchSettings();
});
</script>

<script>
export default {
  name: "BotClient"
}
</script>

<style scoped>
.site-footer {
  background: var(--bg-dark);
  color: #94a3b8;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-top: 80px;
  position: relative;
}

.footer-top {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 48px;
  padding: 72px 32px 56px;
}

/* Brand Col */
.footer-logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
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
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.03em;
}

.logo-text span {
  color: var(--color-primary-glow);
}

.brand-desc {
  color: #94a3b8;
  font-size: 0.925rem;
  line-height: 1.65;
  max-width: 360px;
  margin-bottom: 24px;
}

.brand-features {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.feat-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-full);
  font-size: 0.775rem;
  font-weight: 600;
  color: #e2e8f0;
}

.feat-badge i {
  color: var(--color-primary-glow);
}

/* Columns */
.col-title {
  color: #ffffff;
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 20px;
  letter-spacing: -0.01em;
}

.footer-links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  font-size: 0.9rem;
  color: #94a3b8;
  transition: color var(--duration-fast), transform var(--duration-fast);
  display: inline-block;
}

.footer-links a:hover {
  color: var(--color-primary-glow);
  transform: translateX(3px);
}

/* Contact Card */
.contact-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 20px;
  border-radius: var(--radius-lg);
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.contact-item i {
  color: var(--color-primary-glow);
  font-size: 1rem;
  margin-top: 3px;
}

.contact-item .label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
}

.contact-item strong {
  color: #ffffff;
  font-size: 1rem;
  letter-spacing: 0.02em;
}

.contact-item span {
  color: #cbd5e1;
  font-size: 0.85rem;
}

/* Bottom Bar */
.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding: 24px 0;
  font-size: 0.85rem;
}

.bottom-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.copyright strong {
  color: #ffffff;
}

.bottom-links {
  color: #64748b;
  font-size: 0.825rem;
}

@media (max-width: 1024px) {
  .footer-top {
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }
}

@media (max-width: 640px) {
  .footer-top {
    grid-template-columns: 1fr;
    padding: 48px 20px 40px;
    gap: 36px;
  }
  .bottom-container {
    flex-direction: column;
    text-align: center;
  }
}
</style>
