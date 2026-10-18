<template>
  <footer class="site-footer" aria-label="Chân trang">
    <!-- Giant Background Watermark (Matching Video Frame 18) -->
    <div class="footer-watermark-bg" aria-hidden="true">
      <span>{{ settings.site_name?.toUpperCase() || 'DRIVERENT' }}</span>
    </div>

    <div class="footer-outer-container app-container">
      <!-- Floating Rounded Footer Card (Matching Video Frame 18 & 20) -->
      <div class="footer-floating-card">
        <div class="footer-grid">
          <!-- Col 1: Brand & Newsletter Pill -->
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
              Hệ sinh thái thuê xe điện thông minh hàng đầu, mang đến trải nghiệm di chuyển xanh, êm ái và đẳng cấp trên mọi hành trình.
            </p>

            <!-- Newsletter Pill Container (Matching Video Frame 20) -->
            <div class="newsletter-block">
              <span class="newsletter-label">Nhận thông tin ưu đãi mới nhất</span>
              <form class="newsletter-pill-form" @submit.prevent="handleSubscribe">
                <input
                  type="email"
                  v-model="subscribeEmail"
                  placeholder="Nhập email của bạn..."
                  required
                  class="newsletter-input"
                />
                <button type="submit" class="newsletter-btn" :disabled="subscribed">
                  <i v-if="subscribed" class="fa-solid fa-check"></i>
                  <span v-else>Đăng ký</span>
                </button>
              </form>
              <span v-if="subscribed" class="subscribe-success-msg">
                <i class="fa-solid fa-circle-check"></i> Cảm ơn bạn đã đăng ký nhận tin!
              </span>
            </div>
          </div>

          <!-- Col 2: Services -->
          <div class="footer-col">
            <h4 class="col-title">Dịch Vụ</h4>
            <ul class="footer-links">
              <li><router-link to="/fleet">Khám phá đội xe</router-link></li>
              <li><router-link to="/process">Quy trình thuê xe</router-link></li>
              <li><router-link to="/about">Về DriveRent</router-link></li>
              <li><router-link to="/contact">Trung tâm hỗ trợ</router-link></li>
            </ul>
          </div>

          <!-- Col 3: Support -->
          <div class="footer-col">
            <h4 class="col-title">Hỗ Trợ & Liên Hệ</h4>
            <ul class="footer-links">
              <li>
                <a :href="'tel:' + (settings.hotline || '19001234')">
                  Hotline: <strong>{{ settings.hotline || '1900 1234' }}</strong>
                </a>
              </li>
              <li>
                <a :href="'mailto:' + (settings.support_email || 'support@driverentals.com')">
                  {{ settings.support_email || 'support@driverentals.com' }}
                </a>
              </li>
              <li><router-link to="/my-bookings">Quản lý chuyến đi</router-link></li>
              <li><span>{{ serviceLocationsText }}</span></li>
            </ul>
          </div>

          <!-- Col 4: Sleek Car Graphic (Matching Video Frame 18) -->
          <div class="footer-col car-visual-col">
            <div class="footer-car-wrapper">
              <img
                src="/assets/images/cars/footer_car.jpg"
                alt="DriveRent Luxury Fleet"
                class="footer-side-car-img"
                loading="lazy"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom-bar">
        <p class="copyright-text">
          © {{ currentYear }} <strong>{{ settings.site_name || 'DriveRent' }}</strong>. Bản quyền thuộc về nền tảng thuê xe điện thông minh.
        </p>
        <div class="bottom-trust-badges">
          <span class="trust-badge"><i class="fa-solid fa-shield-halved"></i> Bảo hiểm trọn gói</span>
          <span class="trust-badge"><i class="fa-solid fa-bolt"></i> 100% Động cơ xanh</span>
          <span class="trust-badge"><i class="fa-solid fa-clock-rotate-left"></i> Hoàn cọc 24h</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSystemSettings } from '../../../core/settingsStore';

const { settings, fetchSettings } = useSystemSettings();
const currentYear = new Date().getFullYear();

const subscribeEmail = ref('');
const subscribed = ref(false);

const handleSubscribe = () => {
  if (subscribeEmail.value) {
    subscribed.value = true;
    setTimeout(() => {
      subscribeEmail.value = '';
    }, 2000);
  }
};

const serviceLocationsText = computed(() => {
  if (Array.isArray(settings.service_locations)) {
    return settings.service_locations.join(' • ');
  }
  return 'Hà Nội • TP. HCM • Đà Nẵng';
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
  position: relative;
  background-color: var(--bg-dark, #0b1220);
  padding: 80px 0 40px 0;
  overflow: hidden;
  color: #94a3b8;
}

/* Giant Outline Watermark Typography (Video Frame 18) */
.footer-watermark-bg {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  text-align: center;
  font-family: var(--font-heading);
  font-size: clamp(4rem, 14vw, 15rem);
  font-weight: 900;
  letter-spacing: 0.1em;
  line-height: 0.8;
  color: transparent;
  -webkit-text-stroke: 1px rgba(255, 255, 255, 0.035);
  pointer-events: none;
  user-select: none;
  z-index: 1;
}

.footer-outer-container {
  position: relative;
  z-index: 2;
  max-width: 1320px;
}

/* Floating Rounded Card */
.footer-floating-card {
  background: rgba(18, 25, 42, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 28px;
  padding: 48px 48px 40px 48px;
  box-shadow: 0 24px 60px -10px rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  margin-bottom: 36px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr 1.2fr 1.4fr;
  gap: 40px;
  align-items: center;
}

/* Brand */
.footer-logo {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.logo-box {
  width: 38px;
  height: 38px;
  background: var(--grad-active, linear-gradient(135deg, #059669 0%, #10b981 100%));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.4rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.logo-text span {
  color: var(--color-primary-glow, #10b981);
}

.brand-desc {
  color: #94a3b8;
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 24px;
}

/* Newsletter Pill (Video Frame 20) */
.newsletter-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.newsletter-label {
  font-size: 0.78rem;
  color: #cbd5e1;
  font-weight: 600;
}

.newsletter-pill-form {
  display: flex;
  align-items: center;
  background: rgba(11, 18, 32, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 9999px;
  padding: 4px;
  transition: border-color 200ms ease;
  max-width: 360px;
}

.newsletter-pill-form:focus-within {
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
}

.newsletter-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  padding: 8px 16px;
  color: #ffffff;
  font-size: 0.85rem;
  font-family: var(--font-body);
}

.newsletter-input::placeholder {
  color: #64748b;
}

.newsletter-btn {
  padding: 8px 20px;
  background: var(--color-primary, #059669);
  color: #ffffff;
  border: none;
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  transition: all 200ms ease;
  white-space: nowrap;
}

.newsletter-btn:hover:not(:disabled) {
  background: #10b981;
}

.subscribe-success-msg {
  font-size: 0.78rem;
  color: #34d399;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Links Columns */
.col-title {
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 18px;
  letter-spacing: -0.01em;
  font-family: var(--font-heading);
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a,
.footer-links span {
  font-size: 0.88rem;
  color: #94a3b8;
  transition: all 180ms ease;
  display: inline-block;
  text-decoration: none;
}

.footer-links a:hover {
  color: #ffffff;
  transform: translateX(4px);
}

.footer-links strong {
  color: #34d399;
}

/* Car Graphic on Right (Video Frame 18) */
.car-visual-col {
  display: flex;
  align-items: center;
  justify-content: center;
}

.footer-car-wrapper {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.footer-side-car-img {
  width: 100%;
  max-width: 280px;
  object-fit: contain;
  filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.6));
  opacity: 0.92;
  transition: transform 300ms ease;
}

.footer-side-car-img:hover {
  transform: scale(1.05);
}

/* Bottom Bar */
.footer-bottom-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 12px;
  flex-wrap: wrap;
  gap: 16px;
}

.copyright-text {
  font-size: 0.82rem;
  color: #64748b;
}

.copyright-text strong {
  color: #cbd5e1;
}

.bottom-trust-badges {
  display: flex;
  align-items: center;
  gap: 20px;
}

.trust-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: #94a3b8;
}

.trust-badge i {
  color: var(--color-primary-glow, #10b981);
}

/* Responsive */
@media (max-width: 1080px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 36px;
  }

  .car-visual-col {
    display: none;
  }
}

@media (max-width: 640px) {
  .footer-floating-card {
    padding: 28px 20px;
    border-radius: 20px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .footer-bottom-bar {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .bottom-trust-badges {
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
  }
}
</style>
