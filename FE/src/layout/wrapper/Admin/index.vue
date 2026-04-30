<template>
    <div class="admin-wrapper">
        <SidebarAdmin />
        <div class="admin-main">
            <TopAdmin />
            <main class="admin-content">
                <router-view v-slot="{ Component }">
                    <transition name="fade-slide" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </div>
        <ChatWidget />
    </div>
</template>

<script setup>
import SidebarAdmin from '../../components/Admin/SidebarAdmin.vue';
import TopAdmin from '../../components/Admin/TopAdmin.vue';
import ChatWidget from '../../../components/Common/ChatWidget.vue';
</script>

<style scoped>
.admin-wrapper {
    display: flex;
    min-height: 100vh;
    background-color: var(--bg-main);
}

.admin-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding-right: 24px;
}

.admin-content {
    flex: 1;
    padding: 32px 16px 40px 40px;
}

/* Page Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

@media (max-width: 1024px) {
    .admin-content {
        padding: 20px;
    }
}
</style>
