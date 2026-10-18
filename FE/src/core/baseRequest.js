import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";
import { clearAuthStorage, getActiveToken } from "./authStorage";

const toaster = createToaster({ position: "top-right" });
const apiUrl = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api";

// Cờ chống lặp vô hạn và chống spam nhiều toast khi nhiều request 401 cùng lúc
let isHandling401 = false;

// Các API khách không thuộc luồng kiểm tra phiên đăng nhập đã có
const GUEST_AUTH_ENDPOINTS = [
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/active/'
];

// Interceptor xử lý phản hồi bảo mật: hết phiên (401) hoặc thiếu quyền (403)
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const { status, data, config } = error.response;
            const requestUrl = config?.url || '';

            // Kiểm tra xem request này có phải từ endpoint đăng nhập / khách không
            const isGuestEndpoint = GUEST_AUTH_ENDPOINTS.some(endpoint => requestUrl.includes(endpoint));

            if (status === 401 && !isGuestEndpoint) {
                // Hết hạn hoặc token không hợp lệ: dọn sạch session xác thực (chỉ các key liên quan auth)
                clearAuthStorage();

                if (!isHandling401) {
                    isHandling401 = true;
                    toaster.error(data?.message || 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!');

                    const currentPath = window.location.pathname;
                    if (!currentPath.includes('/login')) {
                        const currentFullUrl = currentPath + window.location.search;
                        const redirectParam = encodeURIComponent(currentFullUrl);
                        setTimeout(() => {
                            window.location.href = `/login?expired=1&redirect=${redirectParam}`;
                        }, 700);
                    } else {
                        setTimeout(() => {
                            isHandling401 = false;
                        }, 1000);
                    }
                }
            } else if (status === 403) {
                // Tài khoản bị khóa hoặc phiên đã bị thu hồi
                const msg = data?.message || '';
                const isAccountLocked = msg.includes('khóa') || msg.includes('thu hồi') || msg.includes('kích hoạt');

                if (isAccountLocked) {
                    clearAuthStorage();
                    if (!window.location.pathname.includes('/login')) {
                        setTimeout(() => {
                            window.location.href = '/login?locked=1';
                        }, 700);
                    }
                }
                toaster.error(msg || 'Truy cập bị từ chối: Bạn không có quyền thực hiện thao tác này!');
            }
        }
        return Promise.reject(error);
    }
);

export default {
    getHeader() {
        const token = getActiveToken();
        if (!token) {
            return {};
        }
        return { Authorization: 'Bearer ' + token };
    },
    get(url, config = {}) {
        return axios.get(`${apiUrl}/${url}`, { ...config, headers: { ...this.getHeader(), ...config.headers } });
    },
    post(url, data, config = {}) {
        return axios.post(`${apiUrl}/${url}`, data, { ...config, headers: { ...this.getHeader(), ...config.headers } });
    },
    patch(url, data, config = {}) {
        return axios.patch(`${apiUrl}/${url}`, data, { ...config, headers: { ...this.getHeader(), ...config.headers } });
    },
    put(url, data, config = {}) {
        return axios.put(`${apiUrl}/${url}`, data, { ...config, headers: { ...this.getHeader(), ...config.headers } });
    },
    delete(url, config = {}) {
        return axios.delete(`${apiUrl}/${url}`, { ...config, headers: { ...this.getHeader(), ...config.headers } });
    },
    getApiUrl() {
        return apiUrl;
    },
    async downloadFile(url, filename, config = {}) {
        const response = await axios.get(`${apiUrl}/${url}`, {
            ...config,
            responseType: 'blob',
            headers: { ...this.getHeader(), ...config.headers }
        });
        const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: response.headers['content-type'] || 'text/csv' }));
        const link = document.createElement('a');
        link.href = blobUrl;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(blobUrl);
        return response;
    },
};
