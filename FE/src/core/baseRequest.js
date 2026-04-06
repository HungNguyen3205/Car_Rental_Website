import axios from "axios";

// Khởi tạo baseRequest để tái cấu trúc logic gọi API giống FE_Hospital_Management
const apiUrl = import.meta.env.VITE_API_BASE_URL || "http://localhost:5000/api";

export default {
    getHeader() {
        let token = window.localStorage.getItem('token_client');
        if (token == null) {
            return {}
        }
        return { Authorization: 'Bearer ' + token }
    },
    get(url) {
        return axios.get(`${apiUrl}/${url}`, { headers: this.getHeader() });
    },
    post(url, data) {
        return axios.post(`${apiUrl}/${url}`, data, { headers: this.getHeader() });
    },
    delete(url) {
        return axios.delete(`${apiUrl}/${url}`, { headers: this.getHeader() });
    },
    put(url, data) {
        return axios.put(`${apiUrl}/${url}`, data, { headers: this.getHeader() });
    },
}
