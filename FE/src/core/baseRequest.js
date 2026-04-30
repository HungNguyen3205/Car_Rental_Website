import axios from "axios";

// Khởi tạo baseRequest để tái cấu trúc logic gọi API giống FE_Hospital_Management
const apiUrl = import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api";

export default {
    getHeader() {
        let token = window.localStorage.getItem('token_client');
        if (token == null) {
            return {}
        }
        return { Authorization: 'Bearer ' + token }
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
}
