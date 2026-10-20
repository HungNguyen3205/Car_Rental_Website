<template>
    <aside class="sidebar-container" :class="{ 'mobile-open': isOpen }">
        <div class="sidebar glass-dark">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon-wrapper">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"></path></svg>
                    </div>
                    <span class="logo-text">Drive<span>Admin</span></span>
                </div>
                <button 
                    class="mobile-close-btn" 
                    @click="$emit('close')" 
                    aria-label="Đóng thanh điều hướng"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-group">
                    <span class="group-title">Menu Chính</span>
                    <router-link v-if="canAny('reports.view')" to="/admin" class="nav-link" exact-active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-chart-pie"></i></div>
                        <span>Tổng quan</span>
                    </router-link>
                    <router-link v-if="canAny('cars.manage')" to="/admin/cars" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-car"></i></div>
                        <span>Quản lý xe</span>
                    </router-link>
                    <router-link v-if="canAny('cars.manage', 'settings.manage')" to="/admin/categories" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-tags"></i></div>
                        <span>Danh mục xe</span>
                    </router-link>
                    <router-link v-if="canAny('cars.manage', 'settings.manage', 'bookings.view')" to="/admin/media" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-photo-film"></i></div>
                        <span>Thư viện tài nguyên</span>
                    </router-link>
                    <router-link v-if="canAny('cars.manage', 'bookings.view')" to="/admin/calendar" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-calendar-days"></i></div>
                        <span>Lịch xe</span>
                    </router-link>
                    <router-link v-if="canAny('bookings.view')" to="/admin/bookings" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                        <span class="nav-text-container">
                            Đơn đặt xe
                            <span v-if="pendingBookingsCount > 0" class="badge-notification">
                                {{ pendingBookingsCount }}
                            </span>
                        </span>
                    </router-link>
                </div>

                <div v-if="canAny('admins.manage', 'bookings.view', 'audit_logs.view', 'settings.manage')" class="nav-group">
                    <span class="group-title">Hệ Thống</span>
                    <router-link v-if="canAny('admins.manage', 'bookings.view')" to="/admin/users" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-users-gear"></i></div>
                        <span>Khách hàng</span>
                    </router-link>
                    <router-link v-if="canAny('admins.manage', 'bookings.view')" to="/admin/contacts" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-headset"></i></div>
                        <span>Hỗ trợ & Liên hệ</span>
                    </router-link>
                    <router-link v-if="canAny('admins.manage', 'bookings.view')" to="/admin/appointments" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                        <span>Lịch hẹn tư vấn</span>
                    </router-link>
                    <router-link v-if="canAny('posts.view', 'posts.create', 'posts.review', 'posts.publish', 'admins.manage')" to="/admin/posts" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-newspaper"></i></div>
                        <span>Quản lý bài viết</span>
                    </router-link>
                    <router-link v-if="canAny('content.view', 'content.edit', 'content.publish', 'content.manage', 'admins.manage')" to="/admin/content" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-layer-group"></i></div>
                        <span>Nội dung Website (CMS)</span>
                    </router-link>
                    <router-link v-if="canAny('admins.manage', 'audit_logs.view')" to="/admin/permissions" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Tài khoản Admin</span>
                    </router-link>
                    <router-link v-if="canAny('audit_logs.view', 'admins.manage')" to="/admin/audit-logs" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <span>Nhật ký hoạt động</span>
                    </router-link>
                    <router-link v-if="canAny('settings.manage', 'admins.manage')" to="/admin/settings" class="nav-link" active-class="active" @click="$emit('close')">
                        <div class="icon-box"><i class="fa-solid fa-sliders"></i></div>
                        <span>Cấu hình & Danh mục</span>
                    </router-link>
                </div>
            </nav>

            <div class="sidebar-footer">
                <button class="logout-btn" @click="handleLogout">
                    <i class="fa-solid fa-power-off"></i>
                    <span>Đăng xuất</span>
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { ref, onMounted, onUnmounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { clearAuthStorage } from '../../../core/authStorage';

defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);
const router = useRouter();
const pendingBookingsCount = ref(0);
const user = ref(null);
let intervalId = null;

const canAny = (...perms) => {
    if (!user.value) return true;
    if (user.value.admin_role === 'owner') return true;
    if (Array.isArray(user.value.permissions)) {
        return perms.some(p => user.value.permissions.includes(p));
    }
    return true;
};

const fetchPendingBookings = async () => {
    // Only dispatchers, accountants, or owners can view bookings
    if (!canAny('bookings.view')) return;
    try {
        const res = await baseRequest.get('admin/bookings/data');
        if (res.data && res.data.status === 1) {
            const count = res.data.data.filter(b => Number(b.tinh_trang) === 0).length;
            pendingBookingsCount.value = count;
        }
    } catch (e) {
        // Silently catch polling error in background
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        emit('close');
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
    fetchPendingBookings();
    intervalId = setInterval(fetchPendingBookings, 30000);
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
    window.removeEventListener('keydown', handleKeyDown);
});

const handleLogout = async () => {
    try {
        await baseRequest.post('logout');
    } catch {
        // Bỏ qua lỗi mạng
    } finally {
        clearAuthStorage();
        router.push('/login');
    }
};
</script>

<style scoped>
.sidebar-container {
    padding: 24px;
    height: 100vh;
    position: sticky;
    top: 0;
    z-index: 999;
}

.sidebar {
    width: 260px;
    height: 100%;
    border-radius: var(--radius-xl);
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    background: #0B1220;
    border: 1px solid rgba(255, 255, 255, 0.08);
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.sidebar::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}

.sidebar-header {
    padding: 32px 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon-wrapper {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, var(--color-primary), #00C853);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 0 16px rgba(37, 99, 235, 0.35);
}

.logo-text {
    font-size: 1.3rem;
    font-weight: 800;
    font-family: var(--font-heading);
    color: white;
    letter-spacing: -0.5px;
}

.logo-text span {
    color: #38BDF8;
}

.mobile-close-btn {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s;
}

.mobile-close-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.sidebar-nav {
    flex: 1;
    padding: 0 16px;
    display: flex;
    flex-direction: column;
    gap: 28px;
    overflow-y: auto;
    /* Completely hide scrollbars while preserving full scrollability */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.sidebar-nav::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
    width: 0;
    height: 0;
}

.nav-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.group-title {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(255, 255, 255, 0.35);
    padding-left: 12px;
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 600;
    font-size: 0.9rem;
    font-family: var(--font-heading);
    transition: all 0.2s ease;
}

.icon-box {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s;
    font-size: 0.9rem;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.08);
}

.nav-link.active {
    color: white;
    background: rgba(37, 99, 235, 0.18);
    border: 1px solid rgba(37, 99, 235, 0.3);
}

.nav-link.active .icon-box {
    background: var(--color-primary);
    color: white;
    box-shadow: 0 0 12px rgba(37, 99, 235, 0.5);
}

.nav-text-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.badge-notification {
    background: #EF4444;
    color: white;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

.sidebar-footer {
    padding: 20px 16px;
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(239, 68, 68, 0.1);
    color: #F87171;
    border: 1px solid rgba(239, 68, 68, 0.2);
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    font-family: var(--font-heading);
    cursor: pointer;
    transition: all 0.2s;
}

.logout-btn:hover {
    background: #EF4444;
    color: white;
    border-color: #EF4444;
    box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.35);
}

@media (max-width: 1024px) {
    .sidebar-container {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        padding: 0;
        transform: translateX(-100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
    }

    .sidebar-container.mobile-open {
        transform: translateX(0);
    }

    .sidebar {
        width: 280px;
        border-radius: 0;
        height: 100dvh;
    }

    .mobile-close-btn {
        display: flex;
    }
}
</style>
