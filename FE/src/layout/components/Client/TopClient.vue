<template>
    <header class="header-main" :class="{ 'is-sticky': isSticky }">
        <div class="header-container app-container">
            <router-link to="/" class="logo">
                <div class="logo-box">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"></path></svg>
                </div>
                <span class="logo-text">Drive<span>Rent</span></span>
            </router-link>

            <nav class="nav-links">
                <router-link to="/">Trang chủ</router-link>
                <router-link to="/fleet">Đội xe</router-link>
                <router-link to="/process">Quy trình</router-link>
                <router-link to="/contact">Liên hệ</router-link>
            </nav>

            <div class="header-actions">
                <template v-if="!user">
                    <router-link to="/login" class="login-link">Đăng nhập</router-link>
                    <router-link to="/register" class="btn-primary">Đăng ký ngay</router-link>
                </template>
                <div v-else class="user-pill-wrapper">
                    <div class="user-pill glass" @click="toggleMenu">
                        <div class="user-avatar">
                            {{ user.ho_ten?.charAt(0) || 'U' }}
                        </div>
                        <span class="user-name">{{ user.ho_ten || 'User' }}</span>
                        <i class="fa-solid fa-chevron-down ml-2" :class="{ 'rotate': showMenu }"></i>
                    </div>
                    
                    <Transition name="fade-slide">
                        <div v-if="showMenu" class="user-dropdown card glass">
                            <div class="dropdown-header">
                                <p class="full-name">{{ user.ho_ten }}</p>
                                <p class="email">{{ user.email }}</p>
                            </div>
                            <div class="dropdown-divider"></div>
                            <router-link v-if="user.role === 'admin'" to="/admin" class="dropdown-item admin-link">
                                <i class="fa-solid fa-shield-halved"></i> Quản trị hệ thống
                            </router-link>
                            <router-link to="/profile" class="dropdown-item">
                                <i class="fa-solid fa-user-circle"></i> Hồ sơ cá nhân
                            </router-link>
                            <router-link to="/my-bookings" class="dropdown-item">
                                <i class="fa-solid fa-clock-rotate-left"></i> Chuyến đi của tôi
                            </router-link>
                            <div class="dropdown-divider"></div>
                            <button @click="logout" class="dropdown-item logout-item">
                                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const isSticky = ref(false);
const showMenu = ref(false);
const user = ref(null);

const handleScroll = () => {
    isSticky.value = window.scrollY > 50;
};

const toggleMenu = () => {
    showMenu.value = !showMenu.value;
};

const checkAuth = () => {
    const token = localStorage.getItem('token_client');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
        try {
            user.value = JSON.parse(userData);
        } catch (e) {
            user.value = null;
        }
    } else {
        user.value = null;
    }
};

// Update auth state whenever route changes (e.g. after login redirect)
watch(() => route.path, () => {
    checkAuth();
});

const logout = () => {
    localStorage.removeItem('token_client');
    localStorage.removeItem('user_role');
    user.value = null;
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
.header-main {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 20px 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-main.is-sticky {
    padding: 12px 0;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 40px;
}

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
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: var(--shadow-glow);
}

.logo-text {
    font-family: var(--font-heading);
    font-size: 1.4rem;
    font-weight: 800;
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

.nav-links a {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--color-text-muted);
}

.nav-links a:hover,
.nav-links .router-link-active {
    color: var(--color-primary);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
}

.login-link {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--color-text-main);
    padding: 10px 16px;
}

.btn-primary {
  padding: 12px 24px;
}

/* User Pill */
.user-pill-wrapper {
    position: relative;
}

.user-pill {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 14px 6px 6px;
    border-radius: 40px;
    cursor: pointer;
    transition: all 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.user-pill:hover {
    background: white;
    box-shadow: var(--shadow-soft);
    transform: translateY(-2px);
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: var(--grad-active);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.9rem;
}

.user-name {
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--color-text-main);
}

/* Dropdown */
.user-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 260px;
    padding: 12px;
    border-radius: 20px;
    z-index: 1001;
    transform-origin: top right;
}

.dropdown-header {
    padding: 16px;
}

.full-name {
    font-weight: 800;
    color: var(--color-text-main);
}

.email {
    font-size: 0.8rem;
    color: var(--color-text-muted);
}

.dropdown-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.05);
    margin: 8px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--color-text-muted);
    transition: all 0.2s;
}

.dropdown-item:hover {
    background: var(--bg-main);
    color: var(--color-primary);
}

.logout-item:hover {
    background: #fff1f2;
    color: #e11d48;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
}

@media (max-width: 1024px) {
    .nav-links { display: none; }
}
</style>
