<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-user-shield"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Tài Khoản Quản Trị</span></h1>
          <p class="page-subtitle">Quản lý danh sách tài khoản Admin nội bộ và trạng thái phân quyền hệ thống.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal('create')">
        <i class="fa-solid fa-plus"></i> Thêm Admin mới
      </button>
    </header>

    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo tên hoặc email quản trị..." v-model="searchQuery" />
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table" v-if="filteredAdmins.length > 0">
          <thead>
            <tr>
              <th>Quản trị viên</th>
              <th>Thông tin liên hệ</th>
              <th>Ngày khởi tạo</th>
              <th>Trạng thái hoạt động</th>
              <th class="text-center" width="140">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="admin in filteredAdmins" :key="admin.id">
              <td>
                <div class="user-profile-cell">
                  <div class="avatar-circle admin">{{ admin.ho_ten ? admin.ho_ten.charAt(0).toUpperCase() : 'A' }}</div>
                  <div class="info">
                    <span class="name">{{ admin.ho_ten }}</span>
                    <span class="id">Mã Admin: #ADM-{{ admin.id }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="contact-info">
                  <span><i class="fa-solid fa-envelope"></i> {{ admin.email }}</span>
                  <span><i class="fa-solid fa-phone"></i> {{ admin.so_dien_thoai || 'Chưa cập nhật' }}</span>
                </div>
              </td>
              <td>{{ formatDate(admin.created_at) }}</td>
              <td>
                <div class="form-switch-wrapper">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    :id="'switch-' + admin.id"
                    :checked="Number(admin.tinh_trang) === 1" 
                    @change="toggleStatus(admin)"
                  />
                  <label :for="'switch-' + admin.id" class="status-label" :class="Number(admin.tinh_trang) === 1 ? 'active' : 'inactive'">
                    {{ Number(admin.tinh_trang) === 1 ? 'Hoạt động' : 'Tạm khóa' }}
                  </label>
                </div>
              </td>
              <td class="text-center">
                <div class="action-buttons">
                  <button class="btn-icon" @click="openModal('update', admin)" title="Chỉnh sửa thông tin"><i class="fa-solid fa-pen-to-square"></i></button>
                  <button class="btn-icon danger" @click="requestDelete(admin)" title="Xóa tài khoản"><i class="fa-solid fa-trash"></i></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-user-xmark"></i>
          <h4>Không tìm thấy quản trị viên</h4>
          <p>Không có tài khoản nào khớp với từ khóa tìm kiếm.</p>
        </div>
      </div>
      
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Modal Admin -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>{{ modalType === 'create' ? 'Thêm mới Quản trị viên' : 'Cập nhật thông tin Admin' }}</h3>
            <p class="modal-sub">Tài khoản này sẽ có quyền truy cập vào cổng quản trị hệ thống</p>
          </div>
          <button class="btn-close" @click="showModal = false" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Họ và tên <span class="required">*</span></label>
              <input type="text" v-model="currentAdmin.ho_ten" placeholder="Nhập họ tên đầy đủ..." />
            </div>
            <div class="form-group">
              <label>Email đăng nhập <span class="required">*</span></label>
              <input type="email" v-model="currentAdmin.email" placeholder="admin@domain.com" :disabled="modalType === 'update'" />
            </div>
            <div class="form-group">
              <label>Số điện thoại</label>
              <input type="text" v-model="currentAdmin.so_dien_thoai" placeholder="Nhập số điện thoại..." />
            </div>
            <div class="form-group">
              <label>Mật khẩu {{ modalType === 'update' ? '(Để trống nếu không đổi)' : '*' }}</label>
              <input type="password" v-model="currentAdmin.password" placeholder="Tối thiểu 6 ký tự..." />
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select v-model="currentAdmin.tinh_trang" class="form-select">
                    <option :value="1">Hoạt động bình thường</option>
                    <option :value="0">Khóa tài khoản</option>
                </select>
            </div>
            <div class="form-group full">
              <label>Địa chỉ công tác / ghi chú</label>
              <input type="text" v-model="currentAdmin.dia_chi" placeholder="Nhập địa chỉ..." />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showModal = false">Hủy bỏ</button>
          <button class="btn-primary" @click="saveAdmin" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            {{ saving ? 'Đang lưu...' : (modalType === 'create' ? 'Xác nhận thêm' : 'Lưu thay đổi') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="adminToDelete" class="modal-overlay" @click.self="adminToDelete = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3>Xác nhận xóa tài khoản Admin</h3>
            <p class="modal-sub">Hành động này sẽ thu hồi quyền quản trị của tài khoản vĩnh viễn</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa tài khoản quản trị sau?</p>
          <div class="highlight-box">
            <strong>{{ adminToDelete.ho_ten }}</strong>
            <span>Email: {{ adminToDelete.email }} | Mã: #ADM-{{ adminToDelete.id }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="adminToDelete = null">Hủy</button>
          <button class="btn-danger" @click="executeDeleteAdmin" :disabled="deleting">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ deleting ? 'Đang xóa...' : 'Đồng ý xóa' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const admins = ref([]);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const searchQuery = ref('');
const showModal = ref(false);
const modalType = ref('create');
const adminToDelete = ref(null);

const currentAdmin = ref({
    ho_ten: '',
    email: '',
    password: '',
    so_dien_thoai: '',
    dia_chi: '',
    tinh_trang: 1
});

const fetchAdmins = async () => {
    loading.value = true;
    try {
        const res = await baseRequest.get('admin/admins/data');
        if (res.data && res.data.status) {
            admins.value = res.data.data;
        }
    } catch (err) {
        toaster.error("Không thể tải danh sách tài khoản Admin!");
    } finally {
        loading.value = false;
    }
};

const filteredAdmins = computed(() => {
    return admins.value.filter(a => {
        const q = searchQuery.value.toLowerCase().trim();
        if (!q) return true;
        return (a.ho_ten && a.ho_ten.toLowerCase().includes(q)) || 
               (a.email && a.email.toLowerCase().includes(q));
    });
});

const openModal = (type, admin = null) => {
    modalType.value = type;
    if (type === 'create') {
        currentAdmin.value = { ho_ten: '', email: '', password: '', so_dien_thoai: '', dia_chi: '', tinh_trang: 1 };
    } else {
        currentAdmin.value = { ...admin, password: '' };
    }
    showModal.value = true;
};

const saveAdmin = async () => {
    if (!currentAdmin.value.ho_ten?.trim()) {
        toaster.error("Vui lòng nhập họ tên quản trị viên!");
        return;
    }
    if (!currentAdmin.value.email?.trim()) {
        toaster.error("Vui lòng nhập email quản trị viên!");
        return;
    }
    if (modalType.value === 'create' && (!currentAdmin.value.password || currentAdmin.value.password.length < 6)) {
        toaster.error("Mật khẩu tài khoản mới tối thiểu 6 ký tự!");
        return;
    }

    saving.value = true;
    try {
        const url = modalType.value === 'create' ? 'admin/admins/create' : 'admin/admins/update';
        const res = await baseRequest.post(url, currentAdmin.value);
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Lưu tài khoản Admin thành công!");
            showModal.value = false;
            await fetchAdmins();
        } else {
            toaster.error(res.data?.message || "Có lỗi xảy ra khi lưu!");
        }
    } catch (err) {
        toaster.error("Lỗi khi kết nối với máy chủ!");
    } finally {
        saving.value = false;
    }
};

const toggleStatus = async (admin) => {
    try {
        const res = await baseRequest.post('admin/admins/status', { id: admin.id });
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Đã cập nhật trạng thái hoạt động!");
            admin.tinh_trang = Number(admin.tinh_trang) === 1 ? 0 : 1;
        } else {
            toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
        }
    } catch (err) {
        toaster.error("Lỗi hệ thống khi cập nhật trạng thái.");
    }
};

const requestDelete = (admin) => {
    adminToDelete.value = admin;
};

const executeDeleteAdmin = async () => {
    if (!adminToDelete.value) return;
    deleting.value = true;
    try {
        const res = await baseRequest.post('admin/admins/delete', { id: adminToDelete.value.id });
        if (res.data && res.data.status) {
            // FIXED: Corrected bug where old code called undefined toast.success
            toaster.success(res.data.message || "Đã xóa tài khoản Admin!");
            adminToDelete.value = null;
            await fetchAdmins();
        } else {
            toaster.error(res.data?.message || "Không thể xóa tài khoản này!");
        }
    } catch (err) {
        toaster.error("Lỗi hệ thống khi xóa tài khoản.");
    } finally {
        deleting.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('vi-VN');
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    showModal.value = false;
    adminToDelete.value = null;
  }
};

onMounted(() => {
    fetchAdmins();
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; }
.header-main { display: flex; align-items: center; gap: 16px; }
.header-icon { 
  width: 52px; height: 52px; 
  background: white; border-radius: var(--radius-md); 
  display: flex; align-items: center; justify-content: center; 
  font-size: 1.4rem; color: var(--color-primary); 
  box-shadow: var(--shadow-sm); 
  border: 1px solid var(--color-border);
}

.page-title { font-size: 1.6rem; font-weight: 800; font-family: var(--font-heading); color: var(--color-text-main); }
.page-title span { color: var(--color-primary); }
.page-subtitle { color: var(--color-text-muted); font-size: 0.9rem; margin-top: 2px; }

.content-card { padding: 0; position: relative; overflow: hidden; }
.table-tools { padding: 18px 24px; display: flex; justify-content: space-between; gap: 16px; border-bottom: 1px solid var(--color-border); background: white; }
.search-input { position: relative; flex: 1; max-width: 380px; }
.search-input i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); }
.search-input input { 
  width: 100%; 
  padding: 10px 14px 10px 40px; 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  background: var(--bg-surface-alt); 
  outline: none; 
  font-size: 0.88rem;
  transition: 0.2s; 
}
.search-input input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th { 
  background: var(--bg-surface-alt); 
  padding: 14px 20px; 
  text-align: left; 
  font-size: 0.75rem; 
  font-weight: 700; 
  color: var(--color-text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}
.premium-table td { padding: 14px 20px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }

.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 38px; height: 38px; border-radius: 50%; background: var(--grad-active); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; }
.avatar-circle.admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
.info .name { display: block; font-weight: 700; color: var(--color-text-main); font-size: 0.9rem; }
.info .id { font-size: 0.72rem; color: var(--color-text-muted); font-family: monospace; }

.contact-info { display: flex; flex-direction: column; gap: 3px; font-size: 0.82rem; color: var(--color-text-muted); }
.contact-info i { width: 14px; color: var(--color-primary); }

.action-buttons { display: flex; gap: 8px; justify-content: center; }
.btn-icon { 
  width: 32px; height: 32px; 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  background: white; 
  color: var(--color-text-muted); 
  cursor: pointer; 
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: 0.15s; 
}
.btn-icon:hover { background: var(--bg-surface-alt); color: var(--color-primary); border-color: var(--color-primary); }
.btn-icon.danger:hover { color: #DC2626; border-color: #DC2626; background: #FEF2F2; }

/* Status Switch */
.form-switch-wrapper { display: flex; align-items: center; gap: 10px; }
.form-check-input { width: 36px; height: 20px; cursor: pointer; accent-color: var(--color-primary); }
.status-label { font-size: 0.8rem; font-weight: 700; cursor: pointer; }
.status-label.active { color: #15803D; }
.status-label.inactive { color: var(--color-text-muted); }

/* Modal Styles */
.modal-overlay { 
  position: fixed; inset: 0; 
  background: rgba(11, 18, 32, 0.65); 
  backdrop-filter: blur(4px); 
  display: flex; align-items: center; justify-content: center; 
  z-index: 1000; padding: 20px;
}
.modal-content { 
  width: 100%; max-width: 580px; 
  padding: 0; background: white; 
  border-radius: var(--radius-lg); 
  box-shadow: var(--shadow-lg); 
  max-height: 90vh; overflow-y: auto; 
}

.modal-header { padding: 20px 24px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--color-border); }
.modal-header h3 { font-size: 1.25rem; font-weight: 800; color: var(--color-text-main); font-family: var(--font-heading); }
.modal-sub { color: var(--color-text-muted); font-size: 0.82rem; margin-top: 2px; }

.btn-close { background: none; border: none; font-size: 1.2rem; color: var(--color-text-muted); cursor: pointer; padding: 4px; }
.modal-body { padding: 24px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 6px; }
.form-group input, .form-select { 
  width: 100%; padding: 10px 14px; 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  outline: none; font-size: 0.88rem; 
  transition: 0.2s; 
}
.form-group input:focus, .form-select:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

.required { color: #DC2626; }

.modal-footer { padding: 14px 24px; background: var(--bg-surface-alt); border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 12px; }

/* Delete Modal */
.delete-modal { max-width: 480px; }
.delete-icon-wrapper {
  width: 44px; height: 44px;
  background: #FEE2E2; color: #DC2626;
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; margin-right: 12px; flex-shrink: 0;
}
.delete-modal .modal-header { display: flex; align-items: center; justify-content: flex-start; }

.highlight-box {
  margin-top: 12px; padding: 12px 16px;
  background: var(--bg-surface-alt); border-radius: var(--radius-sm);
  border-left: 4px solid #DC2626; display: flex; flex-direction: column;
}
.highlight-box strong { font-size: 0.95rem; color: var(--color-text-main); }
.highlight-box span { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px; }

.btn-danger {
  background: #DC2626; color: white; border: none;
  padding: 9px 18px; border-radius: var(--radius-sm);
  font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.btn-danger:hover { background: #B91C1C; }

.empty-state-box { padding: 48px 24px; text-align: center; color: var(--color-text-muted); }
.empty-state-box i { font-size: 2.4rem; opacity: 0.4; margin-bottom: 12px; }
.empty-state-box h4 { font-weight: 700; color: var(--color-text-main); margin-bottom: 4px; }

.loading-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.75); display: flex; align-items: center; justify-content: center; }
.spinner { width: 36px; height: 36px; border: 3px solid rgba(37, 99, 235, 0.2); border-top-color: var(--color-primary); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: span 1; }
  .table-tools { flex-direction: column; }
}
</style>
