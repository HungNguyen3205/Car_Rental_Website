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
        <router-link to="/blog" active-class="active">Bài viết</router-link>
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
        <template v-else>
          <!-- In-App Notification Bell -->
          <div class="notification-wrapper" v-click-outside="closeNotificationMenu">
            <button 
              type="button" 
              class="notification-bell-btn glass" 
              @click="toggleNotificationMenu"
              :aria-expanded="showNotifications"
              aria-label="Thông báo"
              :title="unreadNotifCount > 0 ? `${unreadNotifCount} thông báo mới` : 'Thông báo'"
            >
              <i class="fa-solid fa-bell"></i>
              <span v-if="unreadNotifCount > 0" class="notif-badge">
                {{ unreadNotifCount > 99 ? '99+' : unreadNotifCount }}
              </span>
            </button>

            <!-- Notification Dropdown Menu -->
            <Transition name="fade-slide">
              <div v-if="showNotifications" class="notification-dropdown card">
                <div class="notif-header">
                  <div class="notif-title-group">
                    <h4>Thông Báo</h4>
                    <span v-if="unreadNotifCount > 0" class="notif-counter">{{ unreadNotifCount }} mới</span>
                  </div>
                  <button 
                    v-if="unreadNotifCount > 0" 
                    type="button"
                    class="btn-mark-all" 
                    @click="markAllNotificationsAsRead"
                    :disabled="markingAllRead"
                  >
                    <i class="fa-solid fa-check-double"></i> Đã đọc tất cả
                  </button>
                </div>
                
                <div class="notif-divider"></div>

                <!-- Notification List -->
                <div class="notif-list" v-if="notifications.length > 0">
                  <div 
                    v-for="item in notifications" 
                    :key="item.id" 
                    class="notif-item" 
                    :class="{ 'unread': !item.is_read }"
                    @click="handleClickNotification(item)"
                  >
                    <div class="notif-icon-box" :class="item.type">
                      <i class="fa-solid" :class="getNotifIcon(item.type)"></i>
                    </div>
                    <div class="notif-content-box">
                      <div class="notif-item-title">{{ item.title }}</div>
                      <div class="notif-item-text">{{ item.content }}</div>
                      <div class="notif-item-time">{{ formatNotifTime(item.created_at) }}</div>
                    </div>
                    <div v-if="!item.is_read" class="unread-dot" title="Chưa đọc"></div>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-else class="notif-empty">
                  <div class="empty-icon"><i class="fa-solid fa-bell-slash"></i></div>
                  <p>Bạn chưa có thông báo nào</p>
                </div>
              </div>
            </Transition>
          </div>

          <div class="user-pill-wrapper" v-click-outside="closeMenu">
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
      </template>

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
          <router-link to="/blog" class="drawer-link" @click="closeMobileMenu">
            <i class="fa-solid fa-newspaper"></i>
            <span>Bài viết & Cẩm nang</span>
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
import { clearAuthStorage } from '../../../core/authStorage';

const router = useRouter();
const route = useRoute();

const isSticky = ref(false);
const showMenu = ref(false);
const mobileMenuOpen = ref(false);
const hamburgerBtnRef = ref(null);
const user = ref(null);
const pendingCount = ref(0);
let pollInterval = null;

// Notification state
const notifications = ref([]);
const unreadNotifCount = ref(0);
const showNotifications = ref(false);
const markingAllRead = ref(false);

const toggleNotificationMenu = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value) {
    showMenu.value = false;
    fetchNotifications();
  }
};

const closeNotificationMenu = () => {
  showNotifications.value = false;
};

const fetchNotifications = async () => {
  if (!user.value) return;
  try {
    const res = await baseRequest.get('user/notifications?per_page=10');
    if (res.data && res.data.status) {
      notifications.value = res.data.data?.data || [];
      unreadNotifCount.value = res.data.unread_count || 0;
    }
  } catch (e) {
    // Silent catch
  }
};

const markNotificationAsRead = async (item) => {
  if (item.is_read) return;
  try {
    const res = await baseRequest.put(`user/notifications/${item.id}/read`);
    if (res.data && res.data.status) {
      item.is_read = true;
      item.read_at = new Date().toISOString();
      if (unreadNotifCount.value > 0) unreadNotifCount.value--;
    }
  } catch (e) {
    // Silent catch
  }
};

const handleClickNotification = async (item) => {
  await markNotificationAsRead(item);
  showNotifications.value = false;
  if (item.action_url) {
    router.push(item.action_url);
  }
};

const markAllNotificationsAsRead = async () => {
  markingAllRead.value = true;
  try {
    const res = await baseRequest.put('user/notifications/read-all');
    if (res.data && res.data.status) {
      notifications.value.forEach(n => {
        n.is_read = true;
        n.read_at = new Date().toISOString();
      });
      unreadNotifCount.value = 0;
    }
  } catch (e) {
    // Silent catch
  } finally {
    markingAllRead.value = false;
  }
};

const formatNotifTime = (dt) => {
  if (!dt) return '';
  const date = new Date(dt);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);

  if (diffSec < 60) return 'Vừa xong';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)} phút trước`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} giờ trước`;
  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
};

const getNotifIcon = (type) => {
  switch (type) {
    case 'booking_created': return 'fa-car';
    case 'booking_confirmed': return 'fa-circle-check';
    case 'booking_cancelled': return 'fa-ban';
    case 'payment_confirmed': return 'fa-credit-card';
    case 'upcoming_pickup': return 'fa-key';
    case 'upcoming_return': return 'fa-clock-rotate-left';
    default: return 'fa-bell';
  }
};

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
      fetchNotifications();
      if (user.value.role === 'admin') {
        fetchPending();
      }
      if (!pollInterval) {
        pollInterval = setInterval(() => {
          fetchNotifications();
          if (user.value?.role === 'admin') {
            fetchPending();
          }
        }, 30000);
      }
    } catch (e) {
      user.value = null;
    }
  } else {
    user.value = null;
    notifications.value = [];
    unreadNotifCount.value = 0;
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

const logout = async () => {
  try {
    await baseRequest.post('logout');
  } catch {
    // Bỏ qua lỗi mạng
  } finally {
    clearAuthStorage();
    user.value = null;
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
    closeMenu();
    if (mobileMenuOpen.value) closeMobileMenu();
    router.push('/');
  }
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
  background: rgba(11, 18, 32, 0.75);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  transition: padding var(--duration-normal) var(--ease-smooth),
              background var(--duration-normal) var(--ease-smooth),
              border-color var(--duration-normal) var(--ease-smooth),
              box-shadow var(--duration-normal) var(--ease-smooth);
}

.header-main.is-sticky {
  padding: 10px 0;
  background: rgba(11, 18, 32, 0.94);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 10px 30px -4px rgba(0, 0, 0, 0.5);
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
  color: #ffffff;
  letter-spacing: -0.03em;
}

.logo-text span {
  color: var(--color-primary-glow, #10b981);
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
  color: #94a3b8;
  padding: 6px 4px;
  position: relative;
  transition: color var(--duration-fast) ease;
}

.nav-links a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--color-primary, #059669);
  border-radius: 2px;
  transition: width var(--duration-fast) var(--ease-smooth);
}

.nav-links a:hover,
.nav-links a.active {
  color: #ffffff;
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
  color: #cbd5e1;
  padding: 8px 16px;
  border-radius: 9999px;
  transition: all var(--duration-fast);
}

.login-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.register-btn {
  border-radius: 9999px;
  padding: 10px 22px;
}

.register-btn {
  padding: 10px 20px;
  font-size: 0.925rem;
}

/* Notification Bell & Dropdown */
.notification-wrapper {
  position: relative;
}

.notification-bell-btn {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  background: #ffffff;
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-main);
  font-size: 1.05rem;
  cursor: pointer;
  position: relative;
  transition: all var(--duration-fast) var(--ease-smooth);
  box-shadow: var(--shadow-xs);
}

.notification-bell-btn:hover,
.notification-bell-btn.active {
  color: var(--color-primary);
  border-color: var(--color-primary-border);
  background: var(--color-primary-light);
  transform: translateY(-1px);
  box-shadow: var(--shadow-soft);
}

.notif-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--color-danger, #ef4444);
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  padding: 0 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
  animation: pulseGlow 2s infinite;
}

.notification-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: -60px;
  width: 360px;
  max-width: 90vw;
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: 0 16px 40px -8px rgba(15, 23, 42, 0.16), 0 0 0 1px rgba(0, 0, 0, 0.04);
  z-index: var(--z-dropdown);
  transform-origin: top right;
  overflow: hidden;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background: #fafbfc;
}

.notif-header-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-title-text {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-main);
}

.notif-count-tag {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-primary);
  background: var(--color-primary-light);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

.btn-mark-all {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 4px 6px;
  border-radius: var(--radius-xs);
  transition: background var(--duration-fast);
}

.btn-mark-all:hover:not(:disabled) {
  background: var(--color-primary-light);
}

.btn-mark-all:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.notif-divider {
  height: 1px;
  background: var(--color-border-subtle);
}

.notif-list {
  max-height: 380px;
  overflow-y: auto;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border-subtle);
  cursor: pointer;
  position: relative;
  transition: background var(--duration-fast) var(--ease-smooth);
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background: #f8fafc;
}

.notif-item.unread {
  background: rgba(0, 102, 255, 0.035);
}

.notif-item.unread:hover {
  background: rgba(0, 102, 255, 0.07);
}

.notif-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.notif-icon-box.booking_created {
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
}

.notif-icon-box.booking_confirmed {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.notif-icon-box.booking_cancelled {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.notif-icon-box.payment_confirmed {
  background: rgba(139, 92, 246, 0.12);
  color: #7c3aed;
}

.notif-icon-box.upcoming_pickup,
.notif-icon-box.upcoming_return {
  background: rgba(245, 158, 11, 0.12);
  color: #d97706;
}

.notif-content-box {
  flex: 1;
  min-width: 0;
}

.notif-item-title {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 2px;
  line-height: 1.35;
}

.notif-item.unread .notif-item-title {
  color: #0f172a;
}

.notif-item-text {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 4px;
}

.notif-item-time {
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 500;
}

.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-primary, #0066ff);
  flex-shrink: 0;
  margin-top: 6px;
}

.notif-empty {
  padding: 36px 20px;
  text-align: center;
  color: var(--color-text-muted);
}

.notif-empty .empty-icon {
  font-size: 2rem;
  color: #cbd5e1;
  margin-bottom: 8px;
}

.notif-empty p {
  font-size: 0.85rem;
  margin: 0;
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
