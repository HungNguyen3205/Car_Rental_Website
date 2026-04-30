import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    // Client Area
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
                path: 'fleet',
                name: 'Fleet',
                component: () => import('../pages/Fleet/index.vue'),
            },
            {
                path: 'process',
                name: 'Process',
                component: () => import('../pages/Process/index.vue'),
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
                path: 'profile',
                name: 'Profile',
                component: () => import('../pages/Profile/index.vue'),
            },
            {
                path: 'my-bookings',
                name: 'MyBookings',
                component: () => import('../pages/Client/MyTrips/index.vue'),
            },
            {
                path: 'my-bookings/:id',
                name: 'BookingDetail',
                component: () => import('../pages/Client/MyTrips/BookingDetail.vue'),
            },
            {
                path: 'login',
                name: 'Login',
                component: () => import('../pages/Auth/index.vue'),
            },
            {
                path: 'auth/callback',
                name: 'SocialCallback',
                component: () => import('../pages/Auth/SocialCallback.vue'),
            },
            {
                path: 'register',
                name: 'Register',
                component: () => import('../pages/Auth/Register.vue'),
            }
        ]
    },
    // Admin Area
    {
        path: '/admin',
        component: () => import('../layout/wrapper/Admin/index.vue'),
        children: [
            {
                path: '',
                name: 'AdminDashboard',
                component: () => import('../pages/Admin/Dashboard/index.vue'),
            },
            {
                path: 'cars',
                name: 'AdminCars',
                component: () => import('../pages/Admin/Cars/index.vue'),
            },
            {
                path: 'bookings',
                name: 'AdminBookings',
                component: () => import('../pages/Admin/Bookings/index.vue'),
            },
            {
                path: 'users',
                name: 'AdminUsers',
                component: () => import('../pages/Admin/Users/index.vue'),
            },
            {
                path: 'permissions',
                name: 'AdminPermissions',
                component: () => import('../pages/Admin/Permissions/index.vue'),
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
        if (role && role.toLowerCase() === 'admin') {
            next();
        } else {
            next('/');
        }
    } else {
        next();
    }
});

export default router;
