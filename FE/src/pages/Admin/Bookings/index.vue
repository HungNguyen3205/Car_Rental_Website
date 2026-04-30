<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Đơn Đặt Xe</span></h1>
          <p class="page-subtitle">Xử lý các yêu cầu thuê xe từ khách hàng và quản lý trạng thái thanh toán.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-outline" @click="fetchBookings">
          <i class="fa-solid fa-rotate"></i> Làm mới
        </button>
      </div>
    </header>

    <!-- Summary Statistics -->
    <section class="summary-section">
      <div class="summary-card card">
        <div class="card-icon blue"><i class="fa-solid fa-list-check"></i></div>
        <div class="card-info">
          <span class="label">Tổng đơn hàng</span>
          <h3 class="value">{{ bookings.length }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="card-info">
          <span class="label">Đang chờ duyệt</span>
          <h3 class="value">{{ pendingCount }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="card-info">
          <span class="label">Đã xác nhận</span>
          <h3 class="value">{{ confirmedCount }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon purple"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="card-info">
          <span class="label">Doanh thu dự tính</span>
          <h3 class="value">{{ formatCurrency(totalRevenue) }}</h3>
        </div>
      </div>
    </section>

    <!-- Bookings Table -->
    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo mã đơn, tên khách hàng..." v-model="searchQuery" />
        </div>
        <div class="filter-group">
          <div class="select-wrapper">
            <i class="fa-solid fa-filter"></i>
            <select v-model="filterStatus" class="form-select">
              <option value="">Tất cả trạng thái</option>
              <option value="0">Chờ xác nhận</option>
              <option value="1">Đã xác nhận</option>
              <option value="2">Đã hủy</option>
            </select>
            <i class="fa-solid fa-chevron-down select-arrow"></i>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th width="120">Mã đơn</th>
              <th>Khách hàng</th>
              <th>Phương tiện</th>
              <th>Hóa đơn</th>
              <th>Lịch trình</th>
              <th>Tổng tiền</th>
              <th>Trạng thái</th>
              <th width="150" class="text-center">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td><span class="booking-id">#{{ booking.ma_dat_xe }}</span></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ booking.user_name?.charAt(0) }}</div>
                  <div class="user-info">
                    <span class="name">{{ booking.user_name }}</span>
                    <span class="email">{{ booking.user_email }}</span>
                  </div>
                </div>
              </td>
              <td><i class="fa-solid fa-car"></i> {{ booking.car_name }}</td>
              <td>
                <div v-if="booking.bill_image" class="bill-thumb-container" @click="viewFullImage(booking.bill_image)">
                  <img :src="'http://localhost:8000/' + booking.bill_image" alt="bill" class="bill-thumb">
                  <div class="overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
                </div>
                <span v-else class="no-bill"><i class="fa-solid fa-circle-exclamation"></i> Chưa có bill</span>
              </td>
              <td>
                <div class="date-cell">
                  <div><i class="fa-solid fa-arrow-right-from-bracket"></i> {{ formatDate(booking.start_date) }}</div>
                  <div><i class="fa-solid fa-arrow-right-to-bracket"></i> {{ formatDate(booking.end_date) }}</div>
                </div>
              </td>
              <td class="price-cell">
                <span class="price">{{ formatCurrency(booking.tong_tien) }}</span>
                <span class="pay-badge" :class="booking.is_thanh_toan ? 'paid' : 'unpaid'">
                  {{ booking.is_thanh_toan ? 'Đã trả' : 'Chưa trả' }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="'status-' + booking.tinh_trang">
                  {{ getStatusText(booking.tinh_trang) }}
                </span>
              </td>
              <td class="actions-cell">
                <button 
                  v-if="booking.tinh_trang === 0" 
                  class="btn-action confirm" 
                  @click="updateStatus(booking, 1)" 
                  :disabled="!booking.bill_image"
                  :title="!booking.bill_image ? 'Cần ảnh hóa đơn để xác nhận' : 'Xác nhận đơn hàng'"
                >
                  <i class="fa-solid fa-check"></i>
                </button>
                <button v-if="booking.tinh_trang === 0" class="btn-action cancel" @click="updateStatus(booking, 2)" title="Hủy">
                  <i class="fa-solid fa-ban"></i>
                </button>
                <button class="btn-action delete" @click="deleteBooking(booking.id)" title="Xóa">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Image Viewer Modal -->
    <div v-if="fullImageUrl" class="modal-overlay" @click="fullImageUrl = null">
        <div class="modal-content image-viewer" @click.stop>
            <button class="btn-close-abs" @click="fullImageUrl = null">&times;</button>
            <img :src="'http://localhost:8000/' + fullImageUrl" alt="Full Bill">
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';

const bookings = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const filterStatus = ref('');
const fullImageUrl = ref(null);

const viewFullImage = (url) => {
    fullImageUrl.value = url;
};

const pendingCount = computed(() => bookings.value.filter(b => b.tinh_trang === 0).length);
const confirmedCount = computed(() => bookings.value.filter(b => b.tinh_trang === 1).length);
const totalRevenue = computed(() => bookings.value.filter(b => b.tinh_trang === 1).reduce((sum, b) => sum + Number(b.tong_tien), 0));

const filteredBookings = computed(() => {
  return bookings.value.filter(b => {
    const matchSearch = (b.ma_dat_xe?.toLowerCase().includes(searchQuery.value.toLowerCase())) || 
                        (b.user_name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchStatus = filterStatus.value === '' || b.tinh_trang.toString() === filterStatus.value;
    return matchSearch && matchStatus;
  });
});

const fetchBookings = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/bookings/data');
    if (res.data.status) bookings.value = res.data.data;
  } catch (err) { console.error(err); }
  finally { loading.value = false; }
};

const updateStatus = async (booking, status) => {
  const msg = status === 1 ? 'Xác nhận đơn thuê xe này?' : 'Hủy đơn thuê xe này?';
  if (!confirm(msg)) return;

  try {
    const res = await baseRequest.post('admin/bookings/status', {
      id: booking.id,
      tinh_trang: status,
      is_thanh_toan: status === 1 ? 1 : booking.is_thanh_toan
    });
    if (res.data.status) {
      alert(res.data.message);
      fetchBookings();
    }
  } catch (err) { alert("Lỗi hệ thống"); }
};

const deleteBooking = async (id) => {
  if (!confirm("Xóa vĩnh viễn đơn đặt xe này?")) return;
  try {
    const res = await baseRequest.post('admin/bookings/delete', { id });
    if (res.data.status) {
      alert(res.data.message);
      fetchBookings();
    }
  } catch (err) { alert("Lỗi hệ thống"); }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN');
};

const getStatusText = (status) => {
  const texts = ['Đang chờ', 'Xác nhận', 'Đã hủy'];
  return texts[status] || 'Khác';
};

onMounted(() => {
  fetchBookings();
});
</script>

<style scoped>
.admin-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.header-main { display: flex; align-items: center; gap: 20px; }
.header-icon {
  width: 60px; height: 60px;
  background: white; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #8b5cf6;
  box-shadow: var(--shadow-sm);
}

.page-title { font-size: 1.75rem; font-weight: 800; color: #0f172a; }
.page-title span { color: #8b5cf6; }
.page-subtitle { color: #64748b; margin-top: 4px; }

/* Summary Grid */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: white;
}

.card-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
}

.card-icon.blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.card-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.card-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.card-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.card-info .label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.card-info .value { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: 4px; }

/* Content Table */
.content-card { padding: 0; overflow: hidden; }
.table-tools { padding: 20px 24px; display: flex; justify-content: space-between; gap: 20px; border-bottom: 1px solid #f1f5f9; }

.search-input { position: relative; flex: 1; max-width: 400px; }
.search-input i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-input input { width: 100%; padding: 12px 16px 12px 44px; border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; outline: none; transition: all 0.3s; font-size: 0.9rem; }
.search-input input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }

/* Premium Select Styling */
.select-wrapper { position: relative; display: flex; align-items: center; min-width: 180px; }
.select-wrapper i:not(.select-arrow) { position: absolute; left: 14px; color: #94a3b8; font-size: 0.8rem; z-index: 1; }
.select-arrow { position: absolute; right: 14px; color: #94a3b8; font-size: 0.7rem; pointer-events: none; }

.form-select {
  width: 100%;
  padding: 10px 35px 10px 38px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
  appearance: none;
  cursor: pointer;
  transition: all 0.3s;
}

.form-select:hover { border-color: #cbd5e1; background: #f8fafc; }
.form-select:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }
.data-table th { background: #f8fafc; padding: 14px 24px; text-align: left; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.data-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

.booking-id { font-family: monospace; font-weight: 700; color: var(--color-primary); background: rgba(37, 99, 235, 0.05); padding: 4px 8px; border-radius: 4px; }

.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #475569; }
.user-info .name { display: block; font-weight: 700; color: #0f172a; font-size: 0.9rem; }
.user-info .email { font-size: 0.7rem; color: #94a3b8; }

.date-cell { font-size: 0.8rem; color: #475569; line-height: 1.6; }
.date-cell i { width: 16px; color: #94a3b8; }

.price-cell { display: flex; flex-direction: column; gap: 4px; }
.price-cell .price { font-weight: 800; color: #0f172a; }
.pay-badge { font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; width: fit-content; }
.pay-badge.paid { background: #dcfce7; color: #166534; }
.pay-badge.unpaid { background: #fee2e2; color: #991b1b; }

.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
.status-0 { background: #fff7ed; color: #9a3412; }
.status-1 { background: #f0fdf4; color: #166534; }
.status-2 { background: #fef2f2; color: #991b1b; }

.actions-cell { display: flex; gap: 8px; justify-content: center; }
.btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
.btn-action.confirm { background: #10b981; color: white; }
.btn-action.cancel { background: #f59e0b; color: white; }
.btn-action.delete { background: white; color: #94a3b8; border: 1px solid #e2e8f0; }

.btn-action:disabled {
  background: #f1f5f9;
  color: #cbd5e1;
  cursor: not-allowed;
}

.btn-action:hover:not(:disabled) { opacity: 0.9; transform: scale(1.05); }

/* Bill Style */
.bill-thumb-container {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid #e2e8f0;
}

.bill-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.bill-thumb-container .overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s;
}

.bill-thumb-container:hover .overlay { opacity: 1; }

.no-bill {
  font-size: 0.7rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Viewer Modal */
.image-viewer {
    max-width: 90vw !important;
    max-height: 90vh !important;
    background: transparent !important;
    box-shadow: none !important;
    position: relative;
}

.image-viewer img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}

.btn-close-abs {
    position: absolute;
    top: -40px;
    right: 0;
    background: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 992px) {
  .summary-section { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 640px) {
  .summary-section { grid-template-columns: 1fr; }
}
</style>
