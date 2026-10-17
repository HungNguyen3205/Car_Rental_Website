import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const apiUrl = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api";

// Interceptor xử lý phản hồi bảo mật: hết phiên (401) hoặc thiếu quyền (403)
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const { status, data } = error.response;
            if (status === 401) {
                // Hết hạn hoặc token không hợp lệ: dọn sạch session
                window.localStorage.removeItem('token_client');
                window.localStorage.removeItem('token_admin');
                window.localStorage.removeItem('user');
                window.localStorage.removeItem('user_role');

                toaster.error(data?.message || 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!');

                if (!window.location.pathname.includes('/login')) {
                    setTimeout(() => {
                        window.location.href = '/login?expired=1';
                    }, 1000);
                }
            } else if (status === 403) {
                toaster.error(data?.message || 'Truy cập bị từ chối: Bạn không có quyền thực hiện thao tác này!');
            }
        }
        return Promise.reject(error);
    }
);

export default {
    getHeader() {
        let token = null;
        if (window.location.pathname.startsWith('/admin')) {
            token = window.localStorage.getItem('token_admin') || window.localStorage.getItem('token_client');
        } else {
            token = window.localStorage.getItem('token_client') || window.localStorage.getItem('token_admin');
        }

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
};
