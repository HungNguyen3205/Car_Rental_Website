<template>
  <header class="header-main" :class="{ 'is-sticky': isSticky }">
    <div class="header-container app-container">
      <!-- Logo -->
      <router-link to="/" class="logo" aria-label="DriveRent Trang chủ">
        <div class="logo-box">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z" />
          </svg>
        </div>
        <span class="logo-text">Drive<span>Rent</span></span>
      </router-link>

      <!-- Desktop Navigation Links -->
      <nav class="nav-links" aria-label="Chính">
        <router-link to="/" exact-active-class="active">Trang chủ</router-link>
        <router-link to="/fleet" active-class="active">Đội xe</router-link>
        <router-link to="/process" active-class="active">Quy trình</router-link>
        <router-link to="/about" active-class="active">Về chúng tôi</router-link>
        <router-link to="/contact" active-class="active">Liên hệ</router-link>
      </nav>

      <!-- Header Actions -->
      <div class="header-actions">
        <!-- Guest State -->
        <template v-if="!user">
          <router-link to="/login" class="login-link">Đăng nhập</router-link>
          <router-link to="/register" class="btn-primary register-btn">
            <span>Đăng ký</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </template>

        <!-- Authenticated State -->
        <div v-else class="user-pill-wrapper" v-click-outside="closeMenu">
          <button 
            type="button" 
            class="user-pill glass" 
            @click="toggleMenu"
            :aria-expanded="showMenu"
            aria-haspopup="true"
            aria-label="Menu tài khoản"
          >
            <div class="user-avatar">
              {{ user.ho_ten?.charAt(0) || 'U' }}
            </div>
            <span class="user-name">{{ user.ho_ten || 'Tài khoản' }}</span>
            <span v-if="user.role === 'admin' && pendingCount > 0" class="admin-badge" :title="`Có ${pendingCount} đơn chờ duyệt`"></span>
            <i class="fa-solid fa-chevron-down dropdown-arrow" :class="{ 'rotate': showMenu }"></i>
          </button>

          <!-- User Dropdown Menu -->
          <Transition name="fade-slide">
            <div v-if="showMenu" class="user-dropdown card">
              <div class="dropdown-header">
                <p class="full-name">{{ user.ho_ten }}</p>
                <p class="email">{{ user.email }}</p>
                <span v-if="user.role === 'admin'" class="role-tag">Quản trị viên</span>
              </div>
              <div class="dropdown-divider"></div>
              
              <router-link v-if="user.role === 'admin'" to="/admin" class="dropdown-item admin-link" @click="closeMenu">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Quản trị hệ thống</span>
                <span v-if="pendingCount > 0" class="badge-count">{{ pendingCount }}</span>
              </router-link>
              
              <router-link to="/profile" class="dropdown-item" @click="closeMenu">
                <i class="fa-solid fa-user-circle"></i>
                <span>Hồ sơ cá nhân</span>
              </router-link>
              
              <router-link to="/my-bookings" class="dropdown-item" @click="closeMenu">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Chuyến đi của tôi</span>
              </router-link>
              
              <div class="dropdown-divider"></div>
              <button type="button" @click="logout" class="dropdown-item logout-item">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Đăng xuất</span>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Mobile Hamburger Button -->
        <button 
          ref="hamburgerBtnRef"
          type="button" 
          class="hamburger-btn" 
          @click="toggleMobileMenu" 
          :aria-expanded="mobileMenuOpen"
          aria-label="Mở danh mục điều hướng"
        >
          <span class="hamburger-bar" :class="{ 'open-top': mobileMenuOpen }"></span>
          <span class="hamburger-bar" :class="{ 'open-mid': mobileMenuOpen }"></span>
          <span class="hamburger-bar" :class="{ 'open-bot': mobileMenuOpen }"></span>
        </button>
      </div>
    </div>

    <!-- Mobile Drawer Overlay & Panel -->
    <Transition name="fade">
      <div v-if="mobileMenuOpen" class="mobile-drawer-overlay" @click="closeMobileMenu"></div>
    </Transition>

    <Transition name="slide-drawer">
      <aside v-if="mobileMenuOpen" class="mobile-drawer" role="dialog" aria-modal="true" aria-label="Menu di động">
        <div class="drawer-header">
          <div class="logo">
            <div class="logo-box">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/></svg>
            </div>
            <span class="logo-text">Drive<span>Rent</span></span>
          </div>
          <button type="button" class="drawer-close-btn" @click="closeMobileMenu" aria-label="Đóng menu">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <nav class="drawer-nav">
          <router-link to="/" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
          </router-link>
          <router-link to="/fleet" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-car-side"></i>
            <span>Đội xe điện</span>
          </router-link>
          <router-link to="/process" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-list-check"></i>
            <span>Quy trình thuê</span>
          </router-link>
          <router-link to="/about" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-circle-info"></i>
            <span>Về chúng tôi</span>
          </router-link>
          <router-link to="/contact" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-headset"></i>
            <span>Liên hệ</span>
          </router-link>
        </nav>

        <div class="drawer-footer">
          <template v-if="!user">
            <router-link to="/login" class="btn-secondary w-full text-center" @click="closeMobileMenu">
              Đăng nhập
            </router-link>
            <router-link to="/register" class="btn-primary w-full text-center mt-2" @click="closeMobileMenu">
              Đăng ký tài khoản
            </router-link>
          </template>
          <template v-else>
            <div class="drawer-user-info">
              <div class="user-avatar">{{ user.ho_ten?.charAt(0) || 'U' }}</div>
              <div>
                <strong>{{ user.ho_ten }}</strong>
                <p>{{ user.email }}</p>
              </div>
            </div>
            <div class="drawer-user-links">
              <router-link v-if="user.role === 'admin'" to="/admin" class="drawer-link admin" @click="closeMobileMenu">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Trang quản trị</span>
              </router-link>
              <router-link to="/profile" class="drawer-link" @click="closeMobileMenu">
                <i class="fa-solid fa-user-circle"></i>
                <span>Hồ sơ cá nhân</span>
              </router-link>
              <router-link to="/my-bookings" class="drawer-link" @click="closeMobileMenu">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Chuyến đi của tôi</span>
              </router-link>
              <button type="button" @click="logout" class="drawer-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Đăng xuất</span>
              </button>
            </div>
          </template>
        </div>
      </aside>
    </Transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import baseRequest from '../../../core/baseRequest';

const router = useRouter();
const route = useRoute();

const isSticky = ref(false);
const showMenu = ref(false);
const mobileMenuOpen = ref(false);
const hamburgerBtnRef = ref(null);
const user = ref(null);
const pendingCount = ref(0);
let pollInterval = null;

// v-click-outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};

const handleScroll = () => {
  const currentScroll = window.scrollY;
  isSticky.value = currentScroll > 20;
};

const toggleMenu = () => {
  showMenu.value = !showMenu.value;
};

const closeMenu = () => {
  showMenu.value = false;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  updateBodyLock();
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
  updateBodyLock();
  if (hamburgerBtnRef.value) {
    hamburgerBtnRef.value.focus();
  }
};

const updateBodyLock = () => {
  if (mobileMenuOpen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    if (showMenu.value) closeMenu();
    if (mobileMenuOpen.value) closeMobileMenu();
  }
};

const fetchPending = async () => {
  if (user.value && user.value.role === 'admin') {
    try {
      const res = await baseRequest.get('admin/bookings/data');
      if (res.data && res.data.status === 1) {
        pendingCount.value = res.data.data.filter(b => Number(b.tinh_trang) === 0).length;
      }
    } catch (e) {
      console.error('Lỗi khi tải thông báo đơn hàng:', e);
    }
  }
};

const checkAuth = () => {
  const token = localStorage.getItem('token_client');
  const userData = localStorage.getItem('user');

  if (token && userData) {
    try {
      user.value = JSON.parse(userData);
      if (user.value.role === 'admin') {
        fetchPending();
        if (!pollInterval) pollInterval = setInterval(fetchPending, 30000);
      }
    } catch (e) {
      user.value = null;
    }
  } else {
    user.value = null;
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }
};

// Auto close drawers & re-check auth on route change
watch(() => route.path, () => {
  closeMenu();
  if (mobileMenuOpen.value) {
    closeMobileMenu();
  }
  checkAuth();
});

const logout = () => {
  localStorage.removeItem('token_client');
  localStorage.removeItem('user_role');
  localStorage.removeItem('user');
  user.value = null;
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
  closeMenu();
  if (mobileMenuOpen.value) closeMobileMenu();
  router.push('/');
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  window.addEventListener('keydown', handleKeyDown);
  checkAuth();
  handleScroll();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('keydown', handleKeyDown);
  document.body.style.overflow = '';
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
.header-main {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: var(--z-sticky);
  padding: 16px 0;
  transition: padding var(--duration-normal) var(--ease-smooth),
              background var(--duration-normal) var(--ease-smooth),
              border-color var(--duration-normal) var(--ease-smooth),
              box-shadow var(--duration-normal) var(--ease-smooth);
}

.header-main.is-sticky {
  padding: 10px 0;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--color-border);
  box-shadow: 0 4px 20px -2px rgba(16, 24, 40, 0.05);
}

.header-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 32px;
}

/* Logo */
.logo {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
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
  box-shadow: var(--shadow-glow);
  transition: transform var(--duration-fast) var(--ease-smooth);
}

.logo:hover .logo-box {
  transform: scale(1.05);
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main);
  letter-spacing: -0.03em;
}

.logo-text span {
  color: var(--color-primary);
}

/* Nav Links Desktop */
.nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav-links a {
  font-size: 0.935rem;
  font-weight: 600;
  color: var(--color-text-muted);
  padding: 6px 4px;
  position: relative;
}

.nav-links a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--color-primary);
  border-radius: 2px;
  transition: width var(--duration-fast) var(--ease-smooth);
}

.nav-links a:hover,
.nav-links a.active {
  color: var(--color-text-main);
}

.nav-links a.active::after,
.nav-links a:hover::after {
  width: 100%;
}

/* Actions */
.header-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.login-link {
  font-weight: 600;
  font-size: 0.935rem;
  color: var(--color-text-main);
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  transition: background var(--duration-fast);
}

.login-link:hover {
  background: var(--bg-surface-sub);
  color: var(--color-primary);
}

.register-btn {
  padding: 10px 20px;
  font-size: 0.925rem;
}

/* User Pill */
.user-pill-wrapper {
  position: relative;
}

.user-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 14px 4px 6px;
  border-radius: var(--radius-full);
  background: #ffffff;
  border: 1px solid var(--color-border);
  cursor: pointer;
  box-shadow: var(--shadow-xs);
  transition: all var(--duration-fast) var(--ease-smooth);
}

.user-pill:hover {
  border-color: var(--color-primary-border);
  box-shadow: var(--shadow-soft);
  transform: translateY(-1px);
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: var(--grad-active);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.user-name {
  font-weight: 600;
  font-size: 0.885rem;
  color: var(--color-text-main);
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dropdown-arrow {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  transition: transform var(--duration-fast);
}

.dropdown-arrow.rotate {
  transform: rotate(180deg);
}

.admin-badge {
  width: 8px;
  height: 8px;
  background: var(--color-danger);
  border-radius: 50%;
  box-shadow: 0 0 0 2px white;
  animation: pulseGlow 2s infinite;
}

/* Dropdown */
.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 250px;
  padding: 10px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-premium);
  background: #ffffff;
  border: 1px solid var(--color-border);
  z-index: var(--z-dropdown);
  transform-origin: top right;
}

.dropdown-header {
  padding: 12px 14px;
}

.full-name {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--color-text-main);
}

.email {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-top: 2px;
  word-break: break-all;
}

.role-tag {
  display: inline-block;
  margin-top: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--color-primary);
  background: var(--color-primary-light);
  padding: 2px 8px;
  border-radius: var(--radius-xs);
}

.dropdown-divider {
  height: 1px;
  background: var(--color-border-subtle);
  margin: 6px 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  font-size: 0.885rem;
  font-weight: 600;
  color: var(--color-text-main);
  transition: background var(--duration-fast), color var(--duration-fast);
  width: 100%;
  cursor: pointer;
}

.dropdown-item i {
  font-size: 1rem;
  color: var(--color-text-muted);
  width: 18px;
  text-align: center;
}

.dropdown-item:hover {
  background: var(--bg-surface-sub);
  color: var(--color-primary);
}

.dropdown-item:hover i {
  color: var(--color-primary);
}

.admin-link {
  color: var(--color-primary);
}

.admin-link i {
  color: var(--color-primary);
}

.badge-count {
  margin-left: auto;
  background: var(--color-danger);
  color: white;
  font-size: 0.7rem;
  padding: 1px 7px;
  border-radius: 10px;
  font-weight: 700;
}

.logout-item:hover {
  background: var(--color-danger-bg);
  color: var(--color-danger);
}

.logout-item:hover i {
  color: var(--color-danger);
}

/* Hamburger Button */
.hamburger-btn {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 40px;
  height: 40px;
  padding: 8px;
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  cursor: pointer;
}

.hamburger-bar {
  display: block;
  width: 100%;
  height: 2px;
  background-color: var(--color-text-main);
  border-radius: 2px;
  transition: transform var(--duration-fast) var(--ease-smooth),
              opacity var(--duration-fast) var(--ease-smooth);
}

.hamburger-bar.open-top {
  transform: translateY(7px) rotate(45deg);
}

.hamburger-bar.open-mid {
  opacity: 0;
}

.hamburger-bar.open-bot {
  transform: translateY(-7px) rotate(-45deg);
}

/* Mobile Drawer */
.mobile-drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(11, 18, 32, 0.6);
  backdrop-filter: blur(4px);
  z-index: var(--z-drawer);
}

.mobile-drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 310px;
  max-width: 85vw;
  background: #ffffff;
  z-index: calc(var(--z-drawer) + 1);
  display: flex;
  flex-direction: column;
  padding: 24px;
  box-shadow: var(--shadow-premium);
  overflow-y: auto;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
}

.drawer-close-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: var(--color-text-muted);
  border-radius: var(--radius-sm);
  background: var(--bg-surface-sub);
}

.drawer-nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.drawer-link {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  font-weight: 600;
  color: var(--color-text-main);
  transition: all var(--duration-fast);
}

.drawer-link i {
  font-size: 1.1rem;
  color: var(--color-text-muted);
  width: 22px;
}

.drawer-link:hover,
.drawer-link.router-link-exact-active {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.drawer-link:hover i,
.drawer-link.router-link-exact-active i {
  color: var(--color-primary);
}

.drawer-footer {
  margin-top: auto;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
}

.drawer-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.drawer-user-info p {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.drawer-user-links {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.drawer-link.logout {
  color: var(--color-danger);
  width: 100%;
}

.drawer-link.logout i {
  color: var(--color-danger);
}

.drawer-link.admin {
  color: var(--color-primary);
}

/* Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all var(--duration-fast) var(--ease-spring);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.96);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity var(--duration-normal);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-drawer-enter-active,
.slide-drawer-leave-active {
  transition: transform var(--duration-normal) var(--ease-smooth);
}

.slide-drawer-enter-from,
.slide-drawer-leave-to {
  transform: translateX(100%);
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

@media (max-width: 992px) {
  .nav-links {
    display: none;
  }
  .hamburger-btn {
    display: flex;
  }
  .login-link, .register-btn {
    display: none;
  }
}
</style>
