import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        component: () => import('../layout/wrapper/Client/index.vue'),
        children: [
            {
                path: '',
                name: 'Home',
                component: () => import('../pages/Home/index.vue'),
            },
            {
                path: 'admin',
                name: 'Admin',
                component: () => import('../pages/Admin/Cars/index.vue'),
            },
            {
                path: 'about',
                name: 'About',
                component: () => import('../pages/About/index.vue'),
            },
            {
                path: 'contact',
                name: 'Contact',
                component: () => import('../pages/Contact/index.vue'),
            },
            {
                path: 'models',
                name: 'Models',
                component: () => import('../pages/Models/index.vue'),
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Simple navigation guard for demo
router.beforeEach((to, from, next) => {
    if (to.path.startsWith('/admin')) {
        const role = localStorage.getItem('user_role');
        if (role === 'admin') {
            next();
        } else {
            next('/');
        }
    } else {
        next();
    }
});

export default router;
