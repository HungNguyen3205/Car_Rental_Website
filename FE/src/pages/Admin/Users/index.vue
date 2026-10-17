<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-users-gear"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Khách Hàng</span></h1>
          <p class="page-subtitle">Theo dõi danh sách tài khoản khách hàng, trạng thái hoạt động và phân quyền.</p>
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
        <table class="premium-table" v-if="filteredUsers.length > 0">
          <thead>
            <tr>
              <th>Người dùng</th>
              <th>Liên hệ</th>
              <th>Vai trò</th>
              <th>Ngày tham gia</th>
              <th>Trạng thái</th>
              <th class="text-center" width="160">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td>
                <div class="user-profile-cell">
                  <div class="avatar-circle">{{ u.ho_ten ? u.ho_ten.charAt(0).toUpperCase() : 'U' }}</div>
                  <div class="info">
                    <span class="name">{{ u.ho_ten }}</span>
                    <span class="id">Mã khách: #USR-{{ u.id }}</span>
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
                  <button 
                    v-if="u.role !== 'admin'" 
                    class="btn-icon" 
                    @click="requestPromote(u)" 
                    title="Nâng cấp quyền Admin"
                  >
                    <i class="fa-solid fa-user-shield"></i>
                  </button>
                  <button 
                    class="btn-icon" 
                    :class="u.is_active ? 'danger' : 'success'" 
                    @click="toggleUserStatus(u)" 
                    :title="u.is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'"
                  >
                    <i class="fa-solid" :class="u.is_active ? 'fa-user-lock' : 'fa-user-check'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-users-slash"></i>
          <h4>Không tìm thấy người dùng</h4>
          <p>Không có tài khoản nào khớp với bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
      </div>
      
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Promote Modal -->
    <div v-if="userToPromote" class="modal-overlay" @click.self="userToPromote = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper blue">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h3>Nâng cấp quyền Quản trị</h3>
            <p class="modal-sub">Tài khoản này sẽ có toàn quyền truy cập cổng Quản trị viên</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc muốn nâng cấp tài khoản sau thành <strong>Admin</strong>?</p>
          <div class="highlight-box blue-box">
            <strong>{{ userToPromote.ho_ten }}</strong>
            <span>Email: {{ userToPromote.email }} | ID: #USR-{{ userToPromote.id }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="userToPromote = null">Hủy</button>
          <button class="btn-primary" @click="confirmPromote" :disabled="promoting">
            <i v-if="promoting" class="fa-solid fa-spinner fa-spin"></i>
            {{ promoting ? 'Đang xử lý...' : 'Xác nhận nâng cấp' }}
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

const users = ref([]);
const loading = ref(true);
const promoting = ref(false);
const searchQuery = ref('');
const filterRole = ref('');
const userToPromote = ref(null);

const fetchUsers = async () => {
    loading.value = true;
    try {
        const res = await baseRequest.get('admin/users/data');
        if (res.data && res.data.status) {
            users.value = res.data.data;
        }
    } catch (err) {
        toaster.error("Không thể tải danh sách người dùng!");
    } finally {
        loading.value = false;
    }
};

const filteredUsers = computed(() => {
    return users.value.filter(u => {
        const q = searchQuery.value.toLowerCase().trim();
        const matchSearch = !q || 
                          (u.ho_ten && u.ho_ten.toLowerCase().includes(q)) || 
                          (u.email && u.email.toLowerCase().includes(q)) ||
                          (u.so_dien_thoai && u.so_dien_thoai.includes(q));
        const matchRole = filterRole.value === '' || u.role === filterRole.value;
        return matchSearch && matchRole;
    });
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('vi-VN');
};

const requestPromote = (user) => {
    userToPromote.value = user;
};

const confirmPromote = async () => {
    if (!userToPromote.value) return;
    promoting.value = true;
    try {
        const res = await baseRequest.post('admin/users/promote', { id: userToPromote.value.id });
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Đã nâng cấp người dùng thành Admin!");
            userToPromote.value = null;
            await fetchUsers();
        } else {
            toaster.error(res.data?.message || "Không thể nâng cấp tài khoản này!");
        }
    } catch (err) {
        toaster.error("Lỗi khi kết nối với máy chủ!");
    } finally {
        promoting.value = false;
    }
};

const toggleUserStatus = async (user) => {
    try {
        const res = await baseRequest.post('admin/users/status', { id: user.id });
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Đã cập nhật trạng thái người dùng!");
            user.is_active = user.is_active ? 0 : 1;
        } else {
            toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
        }
    } catch (err) {
        toaster.error("Lỗi hệ thống khi cập nhật trạng thái.");
    }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    userToPromote.value = null;
  }
};

onMounted(() => {
  fetchUsers();
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
  width: 100%; padding: 10px 14px 10px 40px; 
  border-radius: var(--radius-sm); border: 1px solid var(--color-border); 
  background: var(--bg-surface-alt); outline: none; font-size: 0.88rem;
  transition: 0.2s;
}
.search-input input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

.form-select {
  padding: 10px 16px; border-radius: var(--radius-sm);
  border: 1px solid var(--color-border); background: white;
  font-size: 0.88rem; font-weight: 600; color: var(--color-text-main);
  outline: none; cursor: pointer;
}

.premium-table { width: 100%; border-collapse: collapse; }
.premium-table th { 
  background: var(--bg-surface-alt); padding: 14px 20px; 
  text-align: left; font-size: 0.75rem; font-weight: 700; 
  color: var(--color-text-muted); text-transform: uppercase; 
  letter-spacing: 0.5px; border-bottom: 1px solid var(--color-border);
}
.premium-table td { padding: 14px 20px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }

.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 38px; height: 38px; border-radius: 50%; background: var(--grad-active); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; }
.info .name { display: block; font-weight: 700; color: var(--color-text-main); font-size: 0.9rem; }
.info .id { font-size: 0.72rem; color: var(--color-text-muted); font-family: monospace; }

.contact-info { display: flex; flex-direction: column; gap: 3px; font-size: 0.82rem; color: var(--color-text-muted); }
.contact-info i { width: 14px; color: var(--color-primary); }

.role-badge { padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
.role-badge.admin { background: #FEE2E2; color: #991B1B; }
.role-badge.user { background: #DCFCE7; color: #166534; }

.status-indicator { display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 600; }
.status-indicator::before { content: ""; width: 8px; height: 8px; border-radius: 50%; }
.status-indicator.active::before { background: #22C55E; box-shadow: 0 0 6px #22C55E; }
.status-indicator.inactive::before { background: #94A3B8; }

.action-buttons { display: flex; gap: 8px; justify-content: center; }
.btn-icon { 
  width: 32px; height: 32px; 
  border-radius: var(--radius-sm); border: 1px solid var(--color-border); 
  background: white; color: var(--color-text-muted); cursor: pointer; 
  display: inline-flex; align-items: center; justify-content: center;
  transition: 0.15s; 
}
.btn-icon:hover { background: var(--bg-surface-alt); color: var(--color-primary); border-color: var(--color-primary); }
.btn-icon.danger:hover { color: #DC2626; border-color: #DC2626; background: #FEF2F2; }
.btn-icon.success:hover { color: #15803D; border-color: #15803D; background: #DCFCE7; }

/* Modals */
.modal-overlay { 
  position: fixed; inset: 0; 
  background: rgba(11, 18, 32, 0.65); 
  backdrop-filter: blur(4px); 
  display: flex; align-items: center; justify-content: center; 
  z-index: 1000; padding: 20px;
}
.modal-content { 
  width: 100%; max-width: 480px; 
  padding: 0; background: white; 
  border-radius: var(--radius-lg); 
  box-shadow: var(--shadow-lg); 
}

.modal-header { padding: 20px 24px; display: flex; align-items: center; justify-content: flex-start; border-bottom: 1px solid var(--color-border); }
.modal-header h3 { font-size: 1.25rem; font-weight: 800; color: var(--color-text-main); font-family: var(--font-heading); }
.modal-sub { color: var(--color-text-muted); font-size: 0.82rem; margin-top: 2px; }

.delete-icon-wrapper.blue {
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; margin-right: 12px; flex-shrink: 0;
}

.modal-body { padding: 24px; font-size: 0.92rem; }

.highlight-box.blue-box {
  margin-top: 12px; padding: 12px 16px;
  background: var(--bg-surface-alt); border-radius: var(--radius-sm);
  border-left: 4px solid var(--color-primary); display: flex; flex-direction: column;
}
.highlight-box strong { font-size: 0.95rem; color: var(--color-text-main); }
.highlight-box span { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px; }

.modal-footer { padding: 14px 24px; background: var(--bg-surface-alt); border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 12px; }

.empty-state-box { padding: 48px 24px; text-align: center; color: var(--color-text-muted); }
.empty-state-box i { font-size: 2.4rem; opacity: 0.4; margin-bottom: 12px; }
.empty-state-box h4 { font-weight: 700; color: var(--color-text-main); margin-bottom: 4px; }

.loading-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.75); display: flex; align-items: center; justify-content: center; }
.spinner { width: 36px; height: 36px; border: 3px solid rgba(37, 99, 235, 0.2); border-top-color: var(--color-primary); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .table-tools { flex-direction: column; }
  .search-input { max-width: 100%; }
}
</style>
