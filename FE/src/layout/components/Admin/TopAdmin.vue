<template>
    <header class="top-nav">
        <div class="nav-left">
            <button 
                class="mobile-menu-btn" 
                @click="$emit('toggle-sidebar')" 
                aria-label="Mở menu quản trị"
            >
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="title-meta">
                <h2 class="page-name">{{ pageTitle }}</h2>
                <div class="breadcrumb">
                    <span class="root">Admin Portal</span>
                    <i class="fa-solid fa-chevron-right separator"></i>
                    <span class="current">{{ pageTitle }}</span>
                </div>
            </div>
        </div>

        <div class="top-actions">
            <router-link to="/" class="view-site-btn" target="_blank" title="Xem website khách hàng">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span class="site-label">Xem website</span>
            </router-link>

            <div class="user-profile-wrapper" ref="profileWrapperRef">
                <div 
                    class="user-profile" 
                    @click="toggleMenu"
                    :aria-expanded="showMenu"
                    role="button"
                    tabindex="0"
                    @keydown.enter="toggleMenu"
                    @keydown.space.prevent="toggleMenu"
                >
                    <div class="avatar">
                        {{ userInitial }}
                    </div>
                    <div class="profile-info">
                        <span class="name">{{ user?.ho_ten || 'Quản trị viên' }}</span>
                        <span class="role">{{ roleLabel }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-down dropdown-icon" :class="{ 'rotate': showMenu }"></i>
                </div>
                
                <Transition name="dropdown-anim">
                    <div v-if="showMenu" class="user-dropdown card">
                        <div class="dropdown-header">
                            <p class="full-name">{{ user?.ho_ten || 'Quản trị viên' }}</p>
                            <p class="email">{{ user?.email || 'admin@driverentals.com' }}</p>
                        </div>
                        <div class="dropdown-divider"></div>
                        <router-link to="/" class="dropdown-item">
                            <i class="fa-solid fa-house"></i> Về trang chủ
                        </router-link>
                        <router-link to="/profile" class="dropdown-item">
                            <i class="fa-solid fa-user-circle"></i> Hồ sơ cá nhân
                        </router-link>
                        <router-link to="/admin/bookings" class="dropdown-item">
                            <i class="fa-solid fa-calendar-check"></i> Đơn đặt xe
                        </router-link>
                        <div class="dropdown-divider"></div>
                        <button @click="logout" class="dropdown-item logout-item">
                            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { clearAuthStorage } from '../../../core/authStorage';

defineEmits(['toggle-sidebar']);

const route = useRoute();
const router = useRouter();
const showMenu = ref(false);
const user = ref(null);
const profileWrapperRef = ref(null);

const userInitial = computed(() => {
    if (user.value?.ho_ten) {
        return user.value.ho_ten.trim().charAt(0).toUpperCase();
    }
    return 'A';
});

const roleLabel = computed(() => {
    const role = user.value?.admin_role;
    if (role === 'owner') return 'Chủ hệ thống';
    if (role === 'dispatcher') return 'Điều phối viên';
    if (role === 'accountant') return 'Kế toán viên';
    return 'Quản trị viên';
});

const handleClickOutside = (e) => {
    if (profileWrapperRef.value && !profileWrapperRef.value.contains(e.target)) {
        showMenu.value = false;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && showMenu.value) {
        showMenu.value = false;
    }
};

onMounted(() => {
    const userData = localStorage.getItem('user');
    if (userData) {
        try {
            user.value = JSON.parse(userData);
        } catch (e) {
            user.value = null;
        }
    }
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('keydown', handleKeyDown);
});

const toggleMenu = () => {
    showMenu.value = !showMenu.value;
};

const logout = async () => {
    try {
        await baseRequest.post('logout');
    } catch {
        // Bỏ qua lỗi mạng khi logout, vẫn dọn sạch dữ liệu client
    } finally {
        clearAuthStorage();
        router.push('/login');
    }
};

const pageTitle = computed(() => {
    switch (route.name) {
        case 'AdminDashboard': return 'Tổng quan';
        case 'AdminCars': return 'Quản lý kho xe';
        case 'AdminCategories': return 'Danh mục phương tiện';
        case 'AdminCalendar': return 'Lịch xe & Trạng thái';
        case 'AdminBookings': return 'Quản lý đơn đặt xe';
        case 'AdminUsers': return 'Quản lý khách hàng';
        case 'AdminPermissions': return 'Tài khoản quản trị';
        default: return 'Trang quản trị';
    }
});
</script>

<style scoped>
.top-nav {
    height: 80px;
    padding: 0 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    border: 1px solid var(--color-border);
    margin: 20px 20px 0 0;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.mobile-menu-btn {
    display: none;
    width: 42px;
    height: 42px;
    border-radius: var(--radius-sm);
    background: var(--bg-surface-alt);
    border: 1px solid var(--color-border);
    color: var(--color-text-main);
    cursor: pointer;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
}

.mobile-menu-btn:hover {
    background: #E2E8F0;
    color: var(--color-primary);
}

.title-meta {
    display: flex;
    flex-direction: column;
}

.page-name {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--color-text-main);
    letter-spacing: -0.5px;
    font-family: var(--font-heading);
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 2px;
}

.breadcrumb .root {
    color: var(--color-text-muted);
}

.breadcrumb .separator {
    font-size: 0.6rem;
    color: var(--color-border-dark);
    opacity: 0.5;
}

.breadcrumb .current {
    color: var(--color-primary);
    font-weight: 700;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.view-site-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: var(--radius-md);
    background: var(--bg-surface-alt);
    border: 1px solid var(--color-border);
    color: var(--color-text-main);
    font-size: 0.85rem;
    font-weight: 700;
    font-family: var(--font-heading);
    transition: all 0.2s;
}

.view-site-btn:hover {
    background: #E2E8F0;
    color: var(--color-primary);
    border-color: var(--color-primary);
}

.user-profile-wrapper {
    position: relative;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 14px 6px 6px;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.user-profile:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-sm);
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--color-primary), #00C853);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    font-family: var(--font-heading);
}

.profile-info {
    display: flex;
    flex-direction: column;
}

.profile-info .name {
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--color-text-main);
}

.profile-info .role {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--color-text-muted);
}

.dropdown-icon {
    font-size: 0.7rem;
    color: var(--color-text-muted);
    margin-left: 4px;
    transition: transform 0.2s ease;
}

.dropdown-icon.rotate {
    transform: rotate(180deg);
}

/* Dropdown */
.user-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 240px;
    padding: 10px;
    border-radius: var(--radius-md);
    z-index: 1001;
    box-shadow: var(--shadow-lg);
    background: white;
    border: 1px solid var(--color-border);
}

.dropdown-header {
    padding: 10px 14px;
}

.full-name {
    font-weight: 800;
    color: var(--color-text-main);
    font-size: 0.92rem;
    font-family: var(--font-heading);
}

.email {
    font-size: 0.78rem;
    color: var(--color-text-muted);
    margin-top: 2px;
    word-break: break-all;
}

.dropdown-divider {
    height: 1px;
    background: var(--color-border);
    margin: 6px 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-text-muted);
    transition: all 0.15s;
    width: 100%;
    cursor: pointer;
    border: none;
    background: none;
}

.dropdown-item:hover {
    background: var(--bg-surface-alt);
    color: var(--color-primary);
}

.logout-item {
    color: #EF4444;
}

.logout-item:hover {
    background: #FEF2F2;
    color: #DC2626;
}

.dropdown-anim-enter-active,
.dropdown-anim-leave-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.dropdown-anim-enter-from,
.dropdown-anim-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}

@media (max-width: 1024px) {
    .top-nav {
        margin: 12px 16px 0;
        padding: 0 16px;
    }

    .mobile-menu-btn {
        display: flex;
    }
}

@media (max-width: 640px) {
    .profile-info {
        display: none;
    }
    .site-label {
        display: none;
    }
    .view-site-btn {
        padding: 9px 12px;
    }
}
</style>
