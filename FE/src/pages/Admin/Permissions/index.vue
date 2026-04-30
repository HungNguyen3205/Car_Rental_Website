<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <h1 class="page-title">Phân Quyền <span>Hệ Thống</span></h1>
          <p class="page-subtitle">Quản lý danh sách Quản trị viên và quyền hạn truy cập.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal('create')">
        <i class="fa-solid fa-plus"></i> Thêm Admin
      </button>
    </header>

    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo tên, email..." v-model="searchQuery" />
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Quản trị viên</th>
              <th>Liên hệ</th>
              <th>Ngày tạo</th>
              <th>Trạng thái</th>
              <th class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="admin in filteredAdmins" :key="admin.id">
              <td>
                <div class="user-profile-cell">
                  <div class="avatar-circle admin">{{ admin.ho_ten.charAt(0) }}</div>
                  <div class="info">
                    <span class="name">{{ admin.ho_ten }}</span>
                    <span class="id">ID: #ADM-{{ admin.id }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="contact-info">
                  <span><i class="fa-solid fa-envelope"></i> {{ admin.email }}</span>
                  <span><i class="fa-solid fa-phone"></i> {{ admin.so_dien_thoai || 'N/A' }}</span>
                </div>
              </td>
              <td>{{ formatDate(admin.created_at) }}</td>
              <td>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" :checked="admin.tinh_trang" @change="toggleStatus(admin)">
                  <label class="status-label" :class="admin.tinh_trang ? 'active' : 'inactive'">
                    {{ admin.tinh_trang ? 'Đang hoạt động' : 'Tạm khóa' }}
                  </label>
                </div>
              </td>
              <td class="text-center">
                <div class="action-buttons">
                  <button class="btn-icon" @click="openModal('update', admin)" title="Chỉnh sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                  <button class="btn-icon danger" @click="confirmDelete(admin)" title="Xóa"><i class="fa-solid fa-trash"></i></button>
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

    <!-- Modal Admin -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content glass-card">
        <div class="modal-header">
          <h3>{{ modalType === 'create' ? 'Thêm mới Quản trị viên' : 'Cập nhật thông tin Admin' }}</h3>
          <button class="close-btn" @click="showModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Họ và tên</label>
              <input type="text" v-model="currentAdmin.ho_ten" placeholder="Nhập họ tên đầy đủ..." class="form-control" />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" v-model="currentAdmin.email" placeholder="email@example.com" class="form-control" :disabled="modalType === 'update'" />
            </div>
            <div class="form-group">
              <label>Số điện thoại</label>
              <input type="text" v-model="currentAdmin.so_dien_thoai" placeholder="Nhập số điện thoại..." class="form-control" />
            </div>
            <div class="form-group">
              <label>Mật khẩu {{ modalType === 'update' ? '(Để trống nếu không đổi)' : '' }}</label>
              <input type="password" v-model="currentAdmin.password" placeholder="********" class="form-control" />
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select v-model="currentAdmin.tinh_trang" class="form-select">
                    <option :value="1">Hoạt động</option>
                    <option :value="0">Khóa</option>
                </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showModal = false">Hủy bỏ</button>
          <button class="btn-primary" @click="saveAdmin">
            {{ modalType === 'create' ? 'Xác nhận thêm' : 'Lưu thay đổi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const admins = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const showModal = ref(false);
const modalType = ref('create');
const currentAdmin = ref({
    ho_ten: '',
    email: '',
    password: '',
    so_dien_thoai: '',
    tinh_trang: 1
});

const fetchAdmins = async () => {
    loading.value = true;
    try {
        const res = await baseRequest.get('admin/admins/data');
        if (res.data.status) {
            admins.value = res.data.data;
        }
    } catch (err) {
        console.error("Error fetching admins:", err);
    } finally {
        loading.value = false;
    }
};

const filteredAdmins = computed(() => {
    return admins.value.filter(a => {
        return a.ho_ten?.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
               a.email?.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

const openModal = (type, admin = null) => {
    modalType.value = type;
    if (type === 'create') {
        currentAdmin.value = { ho_ten: '', email: '', password: '', so_dien_thoai: '', tinh_trang: 1 };
    } else {
        currentAdmin.value = { ...admin, password: '' };
    }
    showModal.value = true;
};

const saveAdmin = async () => {
    try {
        const url = modalType.value === 'create' ? 'admin/admins/create' : 'admin/admins/update';
        const res = await baseRequest.post(url, currentAdmin.value);
        if (res.data.status) {
            toaster.success(res.data.message);
            showModal.value = false;
            fetchAdmins();
        } else {
            toaster.error(res.data.message);
        }
    } catch (err) {
        toaster.error("Có lỗi xảy ra!");
    }
};

const toggleStatus = async (admin) => {
    try {
        const res = await baseRequest.post('admin/admins/status', { id: admin.id });
        if (res.data.status) {
            toaster.success(res.data.message);
            fetchAdmins();
        }
    } catch (err) {
        toaster.error("Lỗi khi đổi trạng thái!");
    }
};

const confirmDelete = async (admin) => {
    if (confirm(`Bạn có chắc muốn xóa admin ${admin.ho_ten}?`)) {
        try {
            const res = await baseRequest.post('admin/admins/delete', { id: admin.id });
            if (res.data.status) {
                toast.success(res.data.message);
                fetchAdmins();
            }
        } catch (err) {
            toaster.error("Lỗi khi xóa!");
        }
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('vi-VN');
};

onMounted(fetchAdmins);
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.header-main { display: flex; align-items: center; gap: 20px; }
.header-icon { width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--color-primary); box-shadow: var(--shadow-sm); }
.page-title { font-size: 1.5rem; font-weight: 800; }
.page-title span { color: var(--color-primary); }

.content-card { padding: 0; position: relative; }
.table-tools { padding: 20px; display: flex; justify-content: space-between; gap: 20px; }
.search-input { position: relative; flex: 1; max-width: 400px; }
.search-input i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-input input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; outline: none; transition: 0.3s; }
.search-input input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th { background: #f8fafc; padding: 15px 20px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
.premium-table td { padding: 15px 20px; border-top: 1px solid #f1f5f9; }

.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 40px; height: 40px; border-radius: 50%; background: var(--grad-active); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
.avatar-circle.admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
.info .name { display: block; font-weight: 700; color: #0f172a; }
.info .id { font-size: 0.7rem; color: #94a3b8; }

.contact-info { display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem; color: #475569; }
.contact-info i { width: 16px; color: #94a3b8; }

.action-buttons { display: flex; gap: 10px; justify-content: center; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; transition: 0.2s; }
.btn-icon:hover { background: #f1f5f9; color: var(--color-primary); border-color: var(--color-primary); }
.btn-icon.danger:hover { color: #ef4444; border-color: #ef4444; }

/* Status Switch */
.form-switch { display: flex; align-items: center; gap: 10px; }
.form-check-input { width: 40px; height: 20px; cursor: pointer; }
.status-label { font-size: 0.8rem; font-weight: 700; }
.status-label.active { color: #10b981; }
.status-label.inactive { color: #94a3b8; }

/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { width: 100%; max-width: 600px; padding: 30px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.modal-header h3 { font-size: 1.25rem; font-weight: 800; color: #0f172a; }
.close-btn { background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
.form-control, .form-select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; outline: none; transition: 0.3s; }
.form-control:focus { border-color: var(--color-primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

.modal-footer { margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; }

.btn-primary { background: var(--color-primary); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
.btn-primary:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
.btn-secondary { background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }

.loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; }
.spinner { width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid var(--color-primary); border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
