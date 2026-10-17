<template>
    <div class="admin-wrapper">
        <SidebarAdmin :is-open="isMobileNavOpen" @close="isMobileNavOpen = false" />
        <div 
            v-if="isMobileNavOpen" 
            class="admin-backdrop" 
            @click="isMobileNavOpen = false"
            aria-hidden="true"
        ></div>
        <div class="admin-main">
            <TopAdmin @toggle-sidebar="isMobileNavOpen = !isMobileNavOpen" />
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
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import SidebarAdmin from '../../components/Admin/SidebarAdmin.vue';
import TopAdmin from '../../components/Admin/TopAdmin.vue';
import ChatWidget from '../../../components/Common/ChatWidget.vue';

const isMobileNavOpen = ref(false);
const route = useRoute();

// Close sidebar on route change
watch(() => route.path, () => {
    isMobileNavOpen.value = false;
});
</script>

<style scoped>
.admin-wrapper {
    display: flex;
    min-height: 100vh;
    background-color: var(--bg-main);
    position: relative;
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

.admin-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(11, 18, 32, 0.6);
    backdrop-filter: blur(4px);
    z-index: 998;
    animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Page Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(16px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-16px);
}

@media (max-width: 1024px) {
    .admin-main {
        padding-right: 0;
    }
    .admin-content {
        padding: 20px 16px 32px;
    }
}
</style>
