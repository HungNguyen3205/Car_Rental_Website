<template>
    <aside class="sidebar-container">
        <div class="sidebar glass-dark">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon-wrapper">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"></path></svg>
                    </div>
                    <span class="logo-text">Drive<span>Admin</span></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-group">
                    <span class="group-title">Menu Chính</span>
                    <router-link to="/admin" class="nav-link" exact-active-class="active">
                        <div class="icon-box"><i class="fa-solid fa-chart-pie"></i></div>
                        <span>Tổng quan</span>
                    </router-link>
                    <router-link to="/admin/cars" class="nav-link" active-class="active">
                        <div class="icon-box"><i class="fa-solid fa-car"></i></div>
                        <span>Quản lý xe</span>
                    </router-link>
                    <router-link to="/admin/bookings" class="nav-link" active-class="active">
                        <div class="icon-box"><i class="fa-solid fa-calendar-check"></i></div>
                        <span>Đơn đặt xe</span>
                    </router-link>
                </div>

                <div class="nav-group">
                    <span class="group-title">Hệ Thống</span>
                    <router-link to="/admin/users" class="nav-link" active-class="active">
                        <div class="icon-box"><i class="fa-solid fa-users-gear"></i></div>
                        <span>Người dùng</span>
                    </router-link>
                    <router-link to="/admin/permissions" class="nav-link" active-class="active">
                        <div class="icon-box"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Phân quyền</span>
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

const router = useRouter();

const handleLogout = () => {
    localStorage.removeItem('user_role');
    router.push('/');
};
</script>

<style scoped>
.sidebar-container {
    padding: 24px;
    height: 100vh;
    position: sticky;
    top: 0;
}

.sidebar {
    width: 260px;
    height: 100%;
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

.sidebar-header {
    padding: 40px 24px 32px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 14px;
}

.logo-icon-wrapper {
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
    font-size: 1.4rem;
    font-weight: 800;
    font-family: var(--font-heading);
    color: white;
    letter-spacing: -0.5px;
}

.logo-text span {
    color: var(--color-primary-glow);
}

.sidebar-nav {
    flex: 1;
    padding: 0 16px;
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.nav-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.group-title {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(255, 255, 255, 0.3);
    padding-left: 12px;
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 16px;
    color: rgba(255, 255, 255, 0.5);
    font-weight: 600;
    font-family: var(--font-heading);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-box {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.3s;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.08);
}

.nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.nav-link.active .icon-box {
    background: var(--color-primary);
    color: white;
    box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
}

.sidebar-footer {
    padding: 24px;
    margin-top: auto;
}

.logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    padding: 14px;
    border-radius: 16px;
    font-weight: 700;
    font-family: var(--font-heading);
    transition: all 0.3s;
}

.logout-btn:hover {
    background: #ef4444;
    color: white;
    box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
}

@media (max-width: 1024px) {
    .sidebar-container {
        display: none;
    }
}
</style>
