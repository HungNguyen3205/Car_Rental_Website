<template>
  <header class="header" :class="{ 'scrolled': isScrolled }">
    <div class="app-container header-inner">
      <router-link to="/" class="logo">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="logo-icon"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"></path></svg>
        <span class="logo-text">Drive<span>Rentals</span></span>
      </router-link>
      
      <nav class="nav-links">
        <router-link to="/" class="nav-item">Trang chủ</router-link>
        <router-link to="/models" class="nav-item">Dòng xe</router-link>
        <router-link v-if="isAdmin" to="/admin" class="nav-item">Trang Admin</router-link>
        <router-link to="/about" class="nav-item">Về chúng tôi</router-link>
        <router-link to="/contact" class="nav-item">Liên hệ</router-link>
      </nav>

      <div class="auth-buttons">
        <!-- If not logged in -->
        <template v-if="!isLoggedIn">
          <button class="nav-item login-btn" @click="mockLogin('admin')">Admin Login (Demo)</button>
          <button class="btn-primary" @click="mockLogin('user')">User Login (Demo)</button>
        </template>
        <!-- If logged in -->
        <template v-else>
          <span class="user-greeting">Xin chào, {{ isAdmin ? 'Admin' : 'Khách' }}</span>
          <button class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem;" @click="logout">Đăng xuất</button>
        </template>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const isScrolled = ref(false);
const isLoggedIn = ref(false);
const isAdmin = ref(false);

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20;
};

const checkAuth = () => {
  const role = localStorage.getItem('user_role');
  if (role) {
    isLoggedIn.value = true;
    isAdmin.value = role === 'admin';
  } else {
    isLoggedIn.value = false;
    isAdmin.value = false;
  }
};

const mockLogin = (role) => {
  localStorage.setItem('user_role', role);
  checkAuth();
  if (role === 'admin') {
    router.push('/admin');
  } else {
    router.push('/');
  }
};

const logout = () => {
  localStorage.removeItem('user_role');
  checkAuth();
  router.push('/');
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  checkAuth();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  background: transparent;
  transition: all 0.3s ease;
  padding: 20px 0;
}

.header.scrolled {
  background: var(--bg-surface);
  box-shadow: var(--shadow-sm);
  padding: 12px 0;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  color: var(--color-primary);
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-main);
  letter-spacing: -0.5px;
}

.logo-text span {
  color: var(--color-primary);
}

.nav-links {
  display: flex;
  gap: 32px;
  align-items: center;
}

.nav-item {
  color: var(--color-text-main);
  font-weight: 500;
  font-size: 0.95rem;
  transition: color 0.2s;
  background: none;
  border: none;
}

.nav-item:hover, .nav-item.router-link-active {
  color: var(--color-primary);
}

.auth-buttons {
  display: flex;
  gap: 16px;
  align-items: center;
}

.login-btn {
  padding: 8px 16px;
}

@media (max-width: 768px) {
  .nav-links, .auth-buttons {
    display: none;
  }
}
</style>
