<template>
    <header class="top-nav glass">
        <div class="nav-left">
            <h2 class="page-name">{{ pageTitle }}</h2>
            <div class="breadcrumb">
                <span class="root">Admin</span>
                <i class="fa-solid fa-chevron-right separator"></i>
                <span class="current">{{ pageTitle }}</span>
            </div>
        </div>

        <div class="top-actions">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Tìm kiếm nhanh..." />
            </div>

            <div class="action-buttons">
                <button class="icon-btn">
                    <i class="fa-solid fa-bell"></i>
                    <span class="badge"></span>
                </button>
            </div>

            <div class="user-profile">
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=fff&bold=true" alt="avatar" />
                </div>
                <div class="profile-info">
                    <span class="name">Quản trị viên</span>
                    <span class="role">Hệ thống</span>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const pageTitle = computed(() => {
    switch (route.name) {
        case 'AdminDashboard': return 'Tổng quan';
        case 'AdminCars': return 'Kho xe';
        case 'AdminBookings': return 'Đơn đặt';
        default: return 'Quản trị';
    }
});
</script>

<style scoped>
.top-nav {
    height: 90px;
    padding: 0 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 50;
    border-bottom: 1px solid var(--color-border);
    margin: 24px 24px 0 0;
    border-radius: 24px;
}

.nav-left {
    display: flex;
    flex-direction: column;
}

.page-name {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--color-text-main);
    letter-spacing: -0.5px;
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
    opacity: 0.3;
}

.breadcrumb .current {
    color: var(--color-primary);
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 32px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    width: 280px;
    background: #f1f5f9;
    border-radius: 14px;
    transition: all 0.3s;
}

.search-box:focus-within {
    background: white;
    box-shadow: 0 0 0 2px var(--color-primary);
}

.search-box i {
    color: var(--color-text-muted);
}

.search-box input {
    background: transparent;
    border: none;
    outline: none;
    width: 100%;
    font-size: 0.85rem;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 12px;
}

.icon-btn {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-main);
    border: 1px solid var(--color-border);
    transition: all 0.2s;
}

.icon-btn:hover {
    background: #eff6ff;
    color: var(--color-primary);
    transform: translateY(-2px);
}

.icon-btn .badge {
    position: absolute;
    top: 10px;
    right: 12px;
    background: var(--color-primary);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 2px solid white;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px;
    padding-right: 16px;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.user-profile:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-soft);
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
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
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
}

.dropdown-icon {
    font-size: 0.7rem;
    color: var(--color-text-muted);
    margin-left: 4px;
}

@media (max-width: 768px) {
    .search-box, .profile-info {
        display: none;
    }
}
</style>
