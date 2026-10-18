/**
 * authStorage.js
 * Quản lý phiên xác thực an toàn, tập trung cho Car_Rental_Website.
 * Đảm bảo không bao giờ gọi localStorage.clear() làm mất cấu hình/dữ liệu khác của người dùng.
 */

export const AUTH_KEYS = [
    'token_client',
    'token_admin',
    'user',
    'user_role',
    'admin_permissions',
];

/**
 * Thu hồi và dọn dẹp CHỈ các khóa xác thực và vai trò người dùng.
 * Giữ nguyên các dữ liệu trình duyệt không liên quan (giao diện, bộ lọc, v.v.)
 */
export function clearAuthStorage() {
    AUTH_KEYS.forEach((key) => {
        window.localStorage.removeItem(key);
    });
}

/**
 * Lấy token xác thực phù hợp theo ngữ cảnh đường dẫn hiện tại.
 * Ưu tiên token_admin khi ở khu vực quản trị.
 */
export function getActiveToken() {
    const isAdminRoute = window.location.pathname.startsWith('/admin');
    if (isAdminRoute) {
        return window.localStorage.getItem('token_admin') || window.localStorage.getItem('token_client');
    }
    return window.localStorage.getItem('token_client') || window.localStorage.getItem('token_admin');
}

/**
 * Đọc thông tin người dùng từ localStorage an toàn với try/catch.
 */
export function getStoredUser() {
    try {
        const raw = window.localStorage.getItem('user');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/**
 * Lưu trữ phiên đăng nhập an toàn, phân tách rõ token_admin và token_client.
 */
export function setAuthSession({ token, user, role }) {
    if (!token) return;

    const userRole = role || user?.role || 'customer';

    if (userRole === 'admin') {
        window.localStorage.setItem('token_admin', token);
        // Đồng thời gán token_client nếu admin cũng duyệt giao diện khách hàng
        window.localStorage.setItem('token_client', token);
    } else {
        window.localStorage.setItem('token_client', token);
        window.localStorage.removeItem('token_admin');
    }

    window.localStorage.setItem('user_role', userRole);

    if (user) {
        window.localStorage.setItem('user', JSON.stringify(user));
        if (user.permissions && Array.isArray(user.permissions)) {
            window.localStorage.setItem('admin_permissions', JSON.stringify(user.permissions));
        } else {
            window.localStorage.removeItem('admin_permissions');
        }
    }
}

/**
 * Làm sạch và xác minh URL điều hướng nội bộ an toàn (Chống lỗ hổng Open Redirect).
 * - Bắt buộc phải bắt đầu bằng 1 dấu '/' duy nhất.
 * - Không cho phép protocol-relative URLs (//example.com).
 * - Không cho phép backslash (\\evil.com).
 * - Không cho phép scheme (http:, https:, javascript:, data:).
 */
export function sanitizeRedirect(url, fallback = '/admin') {
    if (!url || typeof url !== 'string') {
        return fallback;
    }

    const trimmed = url.trim();

    if (trimmed === '/') {
        return '/';
    }

    // Bắt đầu bằng 1 dấu '/' duy nhất, ký tự kế tiếp không phải '/' hoặc '\', không chứa dấu hai chấm ':'
    const isSafeInternal = /^\/[^/\\]/.test(trimmed) && !trimmed.includes(':');

    return isSafeInternal ? trimmed : fallback;
}

/**
 * Xác định đích đến sau đăng nhập dựa trên vai trò tài khoản thực tế và URL chuyển hướng an toàn.
 * - Khách hàng thông thường KHÔNG BAO GIỜ được điều hướng vào khu vực /admin.
 */
export function getSafePostLoginRedirect(rawRedirect, userRole) {
    if (userRole === 'admin') {
        const target = sanitizeRedirect(rawRedirect, '/admin');
        return target;
    }

    // Khách hàng thông thường
    const target = sanitizeRedirect(rawRedirect, '/');
    if (target.startsWith('/admin')) {
        return '/';
    }
    return target;
}
