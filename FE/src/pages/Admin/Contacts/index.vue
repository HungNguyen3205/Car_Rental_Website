<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-headset"></i></div>
        <div>
          <h1 class="page-title">Hỗ Trợ & <span>Liên Hệ</span></h1>
          <p class="page-subtitle">Quản lý các yêu cầu liên hệ, tư vấn từ khách hàng và ghi chú nội bộ xử lý.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchContacts" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
      </div>
    </header>

    <!-- KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="kpi-card" :class="{ 'active': filterStatus === '' }" @click="filterStatus = ''">
        <div class="kpi-icon-box total">
          <i class="fa-solid fa-inbox"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Tổng yêu cầu</span>
          <span class="kpi-value">{{ contacts.length }}</span>
        </div>
      </div>

      <div class="kpi-card" :class="{ 'active': filterStatus === '0' }" @click="filterStatus = '0'">
        <div class="kpi-icon-box pending">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Chờ xử lý</span>
          <span class="kpi-value">{{ countByStatus(0) }}</span>
        </div>
      </div>

      <div class="kpi-card" :class="{ 'active': filterStatus === '1' }" @click="filterStatus = '1'">
        <div class="kpi-icon-box processing">
          <i class="fa-solid fa-spinner"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đang xử lý</span>
          <span class="kpi-value">{{ countByStatus(1) }}</span>
        </div>
      </div>

      <div class="kpi-card" :class="{ 'active': filterStatus === '2' }" @click="filterStatus = '2'">
        <div class="kpi-icon-box resolved">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đã giải quyết</span>
          <span class="kpi-value">{{ countByStatus(2) }}</span>
        </div>
      </div>
    </div>

    <!-- Main Content Table -->
    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input 
            type="text" 
            placeholder="Tìm theo họ tên, email, SĐT, tiêu đề hoặc nội dung..." 
            v-model="searchQuery" 
          />
        </div>
        <div class="filter-group">
          <select v-model="filterStatus" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <option value="0">Chờ xử lý (Mới)</option>
            <option value="1">Đang xử lý</option>
            <option value="2">Đã giải quyết</option>
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table class="premium-table" v-if="filteredContacts.length > 0">
          <thead>
            <tr>
              <th width="100">Mã số</th>
              <th width="220">Khách hàng</th>
              <th>Tiêu đề & Nội dung</th>
              <th width="140">Thời gian</th>
              <th width="130">Trạng thái</th>
              <th width="180">Người xử lý / Ghi chú</th>
              <th class="text-center" width="110">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in filteredContacts" :key="c.id" :class="{ 'unread-row': Number(c.trang_thai) === 0 }">
              <td>
                <span class="id-tag">#REQ-{{ c.id }}</span>
              </td>
              <td>
                <div class="user-cell">
                  <div class="name-bold">{{ c.ho_ten }}</div>
                  <div class="contact-sub">
                    <span :title="c.email"><i class="fa-solid fa-envelope"></i> {{ c.email }}</span>
                    <span v-if="c.so_dien_thoai"><i class="fa-solid fa-phone"></i> {{ c.so_dien_thoai }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="content-cell">
                  <div class="subject-line">{{ c.tieu_de }}</div>
                  <div class="message-preview">{{ c.noi_dung }}</div>
                </div>
              </td>
              <td>
                <div class="time-cell">
                  <span>{{ formatDate(c.created_at) }}</span>
                  <small>{{ formatTime(c.created_at) }}</small>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="getStatusClass(c.trang_thai)">
                  <i class="fa-solid" :class="getStatusIcon(c.trang_thai)"></i>
                  {{ getStatusText(c.trang_thai) }}
                </span>
              </td>
              <td>
                <div class="handler-cell" v-if="c.handler || c.ghi_chu_noi_bo">
                  <span v-if="c.handler" class="handler-name">
                    <i class="fa-solid fa-user-check"></i> {{ c.handler.ho_ten }}
                  </span>
                  <p v-if="c.ghi_chu_noi_bo" class="note-preview" :title="c.ghi_chu_noi_bo">
                    <i class="fa-solid fa-note-sticky"></i> {{ c.ghi_chu_noi_bo }}
                  </p>
                </div>
                <span v-else class="text-muted-dash">-</span>
              </td>
              <td class="text-center">
                <button class="btn-action-view" @click="openDetail(c)" title="Xem chi tiết & Xử lý">
                  <i class="fa-solid fa-pen-to-square"></i>
                  <span>Xử lý</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-clipboard-check"></i>
          <h4>Không có yêu cầu nào</h4>
          <p>Không tìm thấy liên hệ nào phù hợp với bộ lọc hiện tại.</p>
        </div>
      </div>

      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Contact Detail & Processing Modal -->
    <div v-if="selectedContact" class="modal-overlay" @click.self="closeDetail">
      <div class="modal-content card detail-modal animate-in">
        <div class="modal-header">
          <div class="header-icon-badge" :class="getStatusClass(selectedContact.trang_thai)">
            <i class="fa-solid fa-headset"></i>
          </div>
          <div>
            <h3>Chi tiết Yêu cầu #REQ-{{ selectedContact.id }}</h3>
            <p class="modal-sub">Gửi lúc {{ formatDateTime(selectedContact.created_at) }}</p>
          </div>
          <button class="btn-close-modal" @click="closeDetail"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <!-- Customer Info Box -->
          <div class="customer-info-box">
            <div class="info-row">
              <span class="info-label"><i class="fa-solid fa-user"></i> Người gửi:</span>
              <strong class="info-value">{{ selectedContact.ho_ten }}</strong>
            </div>
            <div class="info-row">
              <span class="info-label"><i class="fa-solid fa-envelope"></i> Email:</span>
              <a :href="`mailto:${selectedContact.email}`" class="info-link">{{ selectedContact.email }}</a>
            </div>
            <div class="info-row" v-if="selectedContact.so_dien_thoai">
              <span class="info-label"><i class="fa-solid fa-phone"></i> Điện thoại:</span>
              <a :href="`tel:${selectedContact.so_dien_thoai}`" class="info-link">{{ selectedContact.so_dien_thoai }}</a>
            </div>
          </div>

          <!-- Message Box -->
          <div class="message-full-box">
            <div class="subject-banner">
              <span class="sub-label">Tiêu đề:</span>
              <span class="sub-title">{{ selectedContact.tieu_de }}</span>
            </div>
            <div class="message-content">
              {{ selectedContact.noi_dung }}
            </div>
          </div>

          <!-- Processing Section -->
          <div class="processing-section">
            <h4 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Cập nhật trạng thái xử lý</h4>
            
            <div class="status-options">
              <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 0 }">
                <input type="radio" :value="0" v-model="editForm.trang_thai" />
                <div class="radio-content">
                  <span class="dot pending"></span>
                  <div>
                    <strong>Chờ xử lý (Mới)</strong>
                    <p>Yêu cầu mới tiếp nhận, chưa giải quyết</p>
                  </div>
                </div>
              </label>

              <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 1 }">
                <input type="radio" :value="1" v-model="editForm.trang_thai" />
                <div class="radio-content">
                  <span class="dot processing"></span>
                  <div>
                    <strong>Đang xử lý</strong>
                    <p>Đã liên hệ khách hoặc đang điều phối hỗ trợ</p>
                  </div>
                </div>
              </label>

              <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 2 }">
                <input type="radio" :value="2" v-model="editForm.trang_thai" />
                <div class="radio-content">
                  <span class="dot resolved"></span>
                  <div>
                    <strong>Đã giải quyết</strong>
                    <p>Đã hỗ trợ xong và đóng yêu cầu</p>
                  </div>
                </div>
              </label>
            </div>

            <!-- Internal Note Input -->
            <div class="internal-note-group">
              <label for="adminNote">
                <i class="fa-solid fa-lock"></i> Ghi chú nội bộ (Chỉ quản trị viên nhìn thấy):
              </label>
              <textarea 
                id="adminNote" 
                rows="3" 
                v-model="editForm.ghi_chu_noi_bo" 
                placeholder="Nhập ghi chú diễn biến, kết quả trao đổi với khách..."
                maxlength="1000"
              ></textarea>
              <small class="char-count">{{ (editForm.ghi_chu_noi_bo || '').length }}/1000 ký tự</small>
            </div>

            <div v-if="selectedContact.handler" class="last-handled-info">
              <i class="fa-solid fa-clock-rotate-left"></i>
              Người cập nhật gần nhất: <strong>{{ selectedContact.handler.ho_ten }}</strong> 
              <span v-if="selectedContact.handled_at">({{ formatDateTime(selectedContact.handled_at) }})</span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="closeDetail" :disabled="saving">Đóng</button>
          <button class="btn-primary" @click="saveStatus" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            {{ saving ? 'Đang lưu...' : 'Lưu cập nhật' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const contacts = ref([]);
const loading = ref(true);
const saving = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const selectedContact = ref(null);

const editForm = ref({
  trang_thai: 0,
  ghi_chu_noi_bo: ''
});

const fetchContacts = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/contacts');
    if (res.data && res.data.status) {
      contacts.value = res.data.data;
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách liên hệ!");
  } finally {
    loading.value = false;
  }
};

const countByStatus = (st) => {
  return contacts.value.filter(c => Number(c.trang_thai) === st).length;
};

const filteredContacts = computed(() => {
  return contacts.value.filter(c => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchSearch = !q ||
      (c.ho_ten && c.ho_ten.toLowerCase().includes(q)) ||
      (c.email && c.email.toLowerCase().includes(q)) ||
      (c.so_dien_thoai && c.so_dien_thoai.includes(q)) ||
      (c.tieu_de && c.tieu_de.toLowerCase().includes(q)) ||
      (c.noi_dung && c.noi_dung.toLowerCase().includes(q));

    const matchStatus = filterStatus.value === '' || Number(c.trang_thai) === Number(filterStatus.value);
    return matchSearch && matchStatus;
  });
});

const openDetail = (contact) => {
  selectedContact.value = contact;
  editForm.value = {
    trang_thai: Number(contact.trang_thai),
    ghi_chu_noi_bo: contact.ghi_chu_noi_bo || ''
  };
};

const closeDetail = () => {
  selectedContact.value = null;
};

const saveStatus = async () => {
  if (!selectedContact.value) return;
  saving.value = true;
  try {
    const res = await baseRequest.put(`admin/contacts/${selectedContact.value.id}/status`, {
      trang_thai: editForm.value.trang_thai,
      ghi_chu_noi_bo: editForm.value.ghi_chu_noi_bo
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Cập nhật trạng thái thành công!");
      // Update local item
      const idx = contacts.value.findIndex(c => c.id === selectedContact.value.id);
      if (idx !== -1) {
        contacts.value[idx] = res.data.data;
      }
      selectedContact.value = res.data.data;
      closeDetail();
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Có lỗi xảy ra khi cập nhật!";
    toaster.error(msg);
  } finally {
    saving.value = false;
  }
};

const getStatusText = (st) => {
  switch (Number(st)) {
    case 0: return 'Chờ xử lý';
    case 1: return 'Đang xử lý';
    case 2: return 'Đã giải quyết';
    default: return 'Không xác định';
  }
};

const getStatusClass = (st) => {
  switch (Number(st)) {
    case 0: return 'badge-pending';
    case 1: return 'badge-processing';
    case 2: return 'badge-resolved';
    default: return 'badge-muted';
  }
};

const getStatusIcon = (st) => {
  switch (Number(st)) {
    case 0: return 'fa-clock';
    case 1: return 'fa-spinner';
    case 2: return 'fa-check';
    default: return 'fa-question';
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

const formatTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const formatDateTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchContacts();
});
</script>

<style scoped>
.admin-page {
  padding-bottom: 40px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: var(--grad-active, linear-gradient(135deg, #0066ff 0%, #00d2ff 100%));
  color: white;
  border-radius: var(--radius-md, 12px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 12px rgba(0, 102, 255, 0.25);
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-main, #0f172a);
  margin: 0;
}

.page-title span {
  color: var(--color-primary, #0066ff);
}

.page-subtitle {
  font-size: 0.9rem;
  color: var(--color-text-muted, #64748b);
  margin: 4px 0 0;
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-sm, 8px);
  border: 1px solid var(--color-border, #e2e8f0);
  background: white;
  color: var(--color-text-main, #0f172a);
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all var(--duration-fast, 0.2s);
}

.btn-refresh:hover:not(:disabled) {
  background: var(--bg-surface-sub, #f8fafc);
  border-color: var(--color-primary, #0066ff);
  color: var(--color-primary, #0066ff);
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-card {
  background: #ffffff;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 16px);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(0,0,0,0.06);
  border-color: var(--color-primary-border, #bfdbfe);
}

.kpi-card.active {
  border-color: var(--color-primary, #0066ff);
  background: rgba(0, 102, 255, 0.02);
  box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.15);
}

.kpi-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.kpi-icon-box.total {
  background: #eff6ff;
  color: #2563eb;
}

.kpi-icon-box.pending {
  background: #fffbeb;
  color: #d97706;
}

.kpi-icon-box.processing {
  background: #f0fdf4;
  color: #0284c7;
}

.kpi-icon-box.resolved {
  background: #ecfdf5;
  color: #059669;
}

.kpi-info {
  display: flex;
  flex-direction: column;
}

.kpi-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted, #64748b);
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-main, #0f172a);
}

/* Content Card */
.content-card {
  background: white;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
  position: relative;
  overflow: hidden;
}

.table-tools {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 24px;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
  gap: 16px;
  background: #fafbfc;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 440px;
}

.search-input i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.9rem;
}

.search-input input {
  width: 100%;
  padding: 9px 14px 9px 38px;
  border-radius: 8px;
  border: 1px solid var(--color-border, #cbd5e1);
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.search-input input:focus {
  border-color: var(--color-primary, #0066ff);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
}

.form-select {
  padding: 9px 14px;
  border-radius: 8px;
  border: 1px solid var(--color-border, #cbd5e1);
  font-size: 0.88rem;
  outline: none;
  background: white;
  cursor: pointer;
}

/* Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.premium-table th {
  padding: 14px 20px;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
}

.premium-table td {
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
  vertical-align: middle;
}

.unread-row {
  background: rgba(245, 158, 11, 0.02);
}

.id-tag {
  display: inline-block;
  font-family: monospace;
  font-weight: 700;
  font-size: 0.82rem;
  color: #0f172a;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}

.user-cell .name-bold {
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--color-text-main, #0f172a);
}

.contact-sub {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 3px;
  font-size: 0.78rem;
  color: #64748b;
}

.contact-sub span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.content-cell {
  max-width: 320px;
}

.subject-line {
  font-weight: 700;
  font-size: 0.88rem;
  color: #0f172a;
  margin-bottom: 3px;
}

.message-preview {
  font-size: 0.8rem;
  color: #64748b;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.time-cell {
  display: flex;
  flex-direction: column;
  font-size: 0.85rem;
  color: #334155;
}

.time-cell small {
  color: #94a3b8;
  font-size: 0.75rem;
}

/* Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 700;
}

.badge-pending {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}

.badge-processing {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.badge-resolved {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.handler-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.handler-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #0284c7;
  display: flex;
  align-items: center;
  gap: 4px;
}

.note-preview {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 170px;
}

.text-muted-dash {
  color: #cbd5e1;
}

.btn-action-view {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: var(--color-primary, #0066ff);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action-view:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}

/* Empty & Loading */
.empty-state-box {
  padding: 60px 20px;
  text-align: center;
  color: #94a3b8;
}

.empty-state-box i {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 12px;
}

.empty-state-box h4 {
  font-size: 1.1rem;
  color: #475569;
  margin-bottom: 6px;
}

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary, #0066ff);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.detail-modal {
  width: 100%;
  max-width: 650px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.header-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.modal-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 2px 0 0;
}

.btn-close-modal {
  margin-left: auto;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #e2e8f0;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.customer-info-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 18px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.info-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.info-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.info-value {
  font-size: 0.88rem;
  color: #0f172a;
}

.info-link {
  font-size: 0.88rem;
  color: var(--color-primary, #0066ff);
  font-weight: 600;
  text-decoration: none;
}

.info-link:hover {
  text-decoration: underline;
}

.message-full-box {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.subject-banner {
  background: #f1f5f9;
  padding: 10px 16px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  gap: 8px;
  align-items: center;
}

.sub-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.sub-title {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f172a;
}

.message-content {
  padding: 16px;
  font-size: 0.9rem;
  line-height: 1.6;
  color: #334155;
  white-space: pre-wrap;
  background: white;
}

.section-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.status-radio-card {
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
}

.status-radio-card input {
  position: absolute;
  opacity: 0;
}

.status-radio-card.selected {
  border-color: var(--color-primary, #0066ff);
  background: #eff6ff;
}

.radio-content {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.radio-content strong {
  display: block;
  font-size: 0.85rem;
  color: #0f172a;
  margin-bottom: 2px;
}

.radio-content p {
  font-size: 0.72rem;
  color: #64748b;
  margin: 0;
  line-height: 1.3;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-top: 4px;
  flex-shrink: 0;
}

.dot.pending { background: #f59e0b; }
.dot.processing { background: #0284c7; }
.dot.resolved { background: #10b981; }

.internal-note-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.internal-note-group label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
}

.internal-note-group textarea {
  width: 100%;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.88rem;
  outline: none;
  font-family: inherit;
  resize: vertical;
}

.internal-note-group textarea:focus {
  border-color: var(--color-primary, #0066ff);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
}

.char-count {
  font-size: 0.72rem;
  color: #94a3b8;
  text-align: right;
}

.last-handled-info {
  margin-top: 10px;
  font-size: 0.78rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #f8fafc;
  border-radius: 6px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.btn-outline {
  padding: 8px 18px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
}

.btn-primary {
  padding: 8px 22px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .status-options {
    grid-template-columns: 1fr;
  }
}
</style>
