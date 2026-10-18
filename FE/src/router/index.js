import { createRouter, createWebHistory } from 'vue-router';
import baseRequest from '../core/baseRequest';
import { clearAuthStorage } from '../core/authStorage';

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
                path: 'cars/create',
                name: 'AdminCarCreate',
                component: () => import('../pages/Admin/Cars/CarEditor.vue'),
            },
            {
                path: 'cars/edit/:id',
                name: 'AdminCarEdit',
                component: () => import('../pages/Admin/Cars/CarEditor.vue'),
            },
            {
                path: 'categories',
                name: 'AdminCategories',
                component: () => import('../pages/Admin/Categories/index.vue'),
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
            },
            {
                path: 'media',
                name: 'AdminMedia',
                component: () => import('../pages/Admin/Media/index.vue'),
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Bộ đệm trạng thái xác thực phiên admin trong bộ nhớ (tránh gọi API lặp lại khi chuyển tab nhanh)
let verifiedAdminSession = {
    token: null,
    verifiedAt: 0,
};

// Navigation Guard bảo vệ phân quyền thực tế, chống leo quyền bằng sửa đổi localStorage
router.beforeEach(async (to, from, next) => {
    // 1. Quản lý truy cập khu vực Admin
    if (to.path.startsWith('/admin')) {
        const tokenAdmin = localStorage.getItem('token_admin');
        const tokenClient = localStorage.getItem('token_client');
        const currentToken = tokenAdmin || tokenClient;
        const redirectParam = encodeURIComponent(to.fullPath);

        // Bắt buộc phải có token xác thực
        if (!currentToken) {
            return next(`/login?required=admin&redirect=${redirectParam}`);
        }

        // Kiểm tra cache xác thực phiên gần nhất (trong vòng 45 giây và cùng token)
        const isRecentlyVerified = 
            verifiedAdminSession.token === currentToken &&
            (Date.now() - verifiedAdminSession.verifiedAt < 45000);

        if (isRecentlyVerified) {
            return next();
        }

        // Xác minh phiên trực tiếp với backend (Tuyệt đối không tin vai trò lưu tĩnh trong localStorage)
        try {
            const res = await baseRequest.get('check-login');
            if (res.data?.status === 1 && res.data?.user?.role === 'admin') {
                // Xác nhận hợp lệ từ backend
                verifiedAdminSession = {
                    token: currentToken,
                    verifiedAt: Date.now()
                };
                // Đồng bộ dữ liệu định danh chính chủ từ backend
                localStorage.setItem('user', JSON.stringify(res.data.user));
                localStorage.setItem('user_role', 'admin');
                if (res.data.user.permissions) {
                    localStorage.setItem('admin_permissions', JSON.stringify(res.data.user.permissions));
                }
                return next();
            } else {
                // Backend trả về không phải vai trò admin (Ví dụ: khách hàng bình thường cố truy cập admin)
                verifiedAdminSession = { token: null, verifiedAt: 0 };
                return next(`/login?required=admin&forbidden=1&redirect=${redirectParam}`);
            }
        } catch (error) {
            verifiedAdminSession = { token: null, verifiedAt: 0 };
            const status = error.response?.status;
            if (status === 401) {
                clearAuthStorage();
                return next(`/login?expired=1&redirect=${redirectParam}`);
            }
            if (status === 403) {
                clearAuthStorage();
                return next('/login?locked=1');
            }
            // Lỗi mạng hoặc server không phản hồi
            return next(`/login?required=admin&redirect=${redirectParam}`);
        }
    }

    // 2. Quản lý truy cập khu vực Khách hàng cần xác thực (chuyến đi của tôi, hồ sơ cá nhân)
    if (to.path.startsWith('/my-bookings') || to.path === '/profile') {
        const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
        if (!token) {
            const redirectParam = encodeURIComponent(to.fullPath);
            return next(`/login?required=auth&redirect=${redirectParam}`);
        }
    }

    next();
});

export default router;
