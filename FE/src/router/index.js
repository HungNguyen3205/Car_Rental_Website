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
                path: 'fleet/:id',
                name: 'CarDetail',
                component: () => import('../pages/Fleet/CarDetail.vue'),
            },
            {
                path: 'car/:id',
                redirect: to => `/fleet/${to.params.id}`,
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
            },
            {
                path: 'reset-password',
                name: 'ResetPassword',
                component: () => import('../pages/Auth/ResetPassword.vue'),
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
                path: 'calendar',
                name: 'AdminCalendar',
                component: () => import('../pages/Admin/Calendar/index.vue'),
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
            },
            {
                path: 'contacts',
                name: 'AdminContacts',
                component: () => import('../pages/Admin/Contacts/index.vue'),
            },
            {
                path: 'settings',
                name: 'AdminSettings',
                component: () => import('../pages/Admin/Settings/index.vue'),
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation Guard bảo vệ phân quyền thực tế, chống leo quyền bằng sửa đổi localStorage
router.beforeEach((to, from, next) => {
    // 1. Quản lý truy cập khu vực Admin
    if (to.path.startsWith('/admin')) {
        const tokenAdmin = localStorage.getItem('token_admin');
        const tokenClient = localStorage.getItem('token_client');
        const rawUser = localStorage.getItem('user');

        // Bắt buộc phải có token xác thực
        if (!tokenAdmin && !tokenClient) {
            return next('/login?required=admin');
        }

        let user = null;
        try {
            user = rawUser ? JSON.parse(rawUser) : null;
        } catch (e) {
            localStorage.clear();
            return next('/login');
        }

        // Ngăn chặn đổi vai trò chỉ bằng localStorage: Yêu cầu cả token_admin và user.role === 'admin'
        if ((tokenAdmin || tokenClient) && user?.role === 'admin') {
            return next();
        }

        // Thiếu quyền admin thực tế
        return next('/');
    }

    // 2. Quản lý truy cập khu vực Khách hàng cần xác thực (chuyến đi của tôi, hồ sơ cá nhân)
    if (to.path.startsWith('/my-bookings') || to.path === '/profile') {
        const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
        if (!token) {
            return next('/login?required=auth');
        }
    }

    next();
});

export default router;
