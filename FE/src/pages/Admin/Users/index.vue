<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-users-gear"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Người Dùng</span></h1>
          <p class="page-subtitle">Quản lý tài khoản khách hàng và phân quyền hệ thống.</p>
        </div>
      </div>
    </header>

    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo tên, email, số điện thoại..." v-model="searchQuery" />
        </div>
        <div class="filter-group">
          <select v-model="filterRole" class="form-select">
            <option value="">Tất cả vai trò</option>
            <option value="admin">Quản trị viên</option>
            <option value="user">Khách hàng</option>
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Người dùng</th>
              <th>Liên hệ</th>
              <th>Vai trò</th>
              <th>Ngày tham gia</th>
              <th>Trạng thái</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td>
                <div class="user-profile-cell">
                  <div class="avatar-circle">{{ u.ho_ten.charAt(0) }}</div>
                  <div class="info">
                    <span class="name">{{ u.ho_ten }}</span>
                    <span class="id">ID: #USR-{{ u.id }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="contact-info">
                  <span><i class="fa-solid fa-envelope"></i> {{ u.email }}</span>
                  <span><i class="fa-solid fa-phone"></i> {{ u.so_dien_thoai || 'Chưa cập nhật' }}</span>
                </div>
              </td>
              <td>
                <span class="role-badge" :class="u.role">
                  {{ u.role === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}
                </span>
              </td>
              <td>{{ formatDate(u.created_at) }}</td>
              <td>
                <span class="status-indicator" :class="u.is_active ? 'active' : 'inactive'">
                  {{ u.is_active ? 'Đang hoạt động' : 'Đã khóa' }}
                </span>
              </td>
              <td class="text-center">
                <div class="action-buttons">
                  <button v-if="u.role !== 'admin'" class="btn-icon" @click="promoteToAdmin(u)" title="Nâng cấp Admin"><i class="fa-solid fa-user-shield"></i></button>
                  <button class="btn-icon danger" @click="toggleUserStatus(u)" :title="u.is_active ? 'Khóa tài khoản' : 'Mở khóa'">
                    <i class="fa-solid" :class="u.is_active ? 'fa-user-lock' : 'fa-user-check'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const users = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const filterRole = ref('');

const fetchUsers = async () => {
    loading.value = true;
    try {
        const res = await baseRequest.get('admin/users/data');
        if (res.data.status) {
            users.value = res.data.data;
        }
    } catch (err) {
        console.error("Error fetching users:", err);
    } finally {
        loading.value = false;
    }
};

const filteredUsers = computed(() => {
    return users.value.filter(u => {
        const matchSearch = u.ho_ten?.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          u.email?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchRole = filterRole.value === '' || u.role === filterRole.value;
        return matchSearch && matchRole;
    });
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('vi-VN');
};

const promoteToAdmin = async (user) => {
    if (confirm(`Bạn có chắc muốn nâng cấp ${user.ho_ten} thành Admin? Sau khi nâng cấp, tài khoản này sẽ được chuyển sang danh sách Admin.`)) {
        try {
            const res = await baseRequest.post('admin/users/promote', { id: user.id });
            if (res.data.status) {
                toaster.success(res.data.message);
                fetchUsers();
            } else {
                toaster.error(res.data.message);
            }
        } catch (err) {
            toaster.error("Có lỗi xảy ra!");
        }
    }
};

const toggleUserStatus = async (user) => {
    try {
        const res = await baseRequest.post('admin/users/status', { id: user.id });
        if (res.data.status) {
            toaster.success(res.data.message);
            fetchUsers();
        } else {
            toaster.error(res.data.message);
        }
    } catch (err) {
        toaster.error("Có lỗi xảy ra!");
    }
};

onMounted(fetchUsers);
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.header-main { display: flex; align-items: center; gap: 20px; }
.header-icon { width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--color-primary); box-shadow: var(--shadow-sm); }
.page-title { font-size: 1.5rem; font-weight: 800; }
.page-title span { color: var(--color-primary); }

.content-card { padding: 0; }
.table-tools { padding: 20px; display: flex; justify-content: space-between; gap: 20px; }
.search-input { position: relative; flex: 1; max-width: 400px; }
.search-input i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-input input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; outline: none; }

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th { background: #f8fafc; padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
.premium-table td { padding: 15px 20px; border-top: 1px solid #f1f5f9; }

.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 40px; height: 40px; border-radius: 50%; background: var(--grad-active); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
.info .name { display: block; font-weight: 700; color: #0f172a; }
.info .id { font-size: 0.7rem; color: #94a3b8; }

.contact-info { display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; color: #475569; }
.contact-info i { width: 16px; color: #94a3b8; }

.role-badge { padding: 4px 12px; border-radius: 100px; font-size: 0.7rem; font-weight: 700; }
.role-badge.admin { background: #fee2e2; color: #991b1b; }
.role-badge.user { background: #dcfce7; color: #166534; }

.status-indicator { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; }
.status-indicator::before { content: ""; width: 8px; height: 8px; border-radius: 50%; }
.status-indicator.active::before { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
.status-indicator.inactive::before { background: #94a3b8; }

.action-buttons { display: flex; gap: 10px; justify-content: center; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; transition: 0.2s; }
.btn-icon:hover { background: #f1f5f9; color: var(--color-primary); border-color: var(--color-primary); }
.btn-icon.danger:hover { color: #ef4444; border-color: #ef4444; }

.loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; }
.spinner { width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
