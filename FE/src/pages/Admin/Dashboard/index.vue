<template>
  <div class="dashboard-page">
    <!-- Header info removed or simplified as TopAdmin has the title -->
    <section class="welcome-banner card">
      <div class="welcome-text">
        <h2 class="welcome-title">Chào mừng trở lại, <span>Admin</span></h2>
        <p>Hệ thống đang hoạt động ổn định. Đây là tổng quan hoạt động trong ngày.</p>
      </div>
      <div class="welcome-actions" style="display: flex; gap: 12px; align-items: center;">
        <button 
          class="btn-export-csv" 
          @click="handleExportRevenueReport" 
          :disabled="exportingReport"
          title="Xuất báo cáo doanh thu tài chính ra file CSV UTF-8"
        >
          <i class="fa-solid fa-file-csv" :class="{ 'fa-spin': exportingReport }"></i>
          <span>{{ exportingReport ? 'Đang xuất...' : 'Xuất Báo Cáo (CSV)' }}</span>
        </button>
        <div class="current-date">
          <i class="fa-solid fa-calendar-check"></i>
          <span>{{ todayDate }}</span>
        </div>
      </div>
    </section>

    <!-- Pending Bookings Alert -->
    <div v-if="stats.summary?.pending_bookings > 0" class="pending-alert-banner">
      <div class="alert-content">
        <div class="alert-icon">
          <i class="fa-solid fa-bell bell-shake"></i>
        </div>
        <div class="alert-text">
          <h3>Chú ý: Có {{ stats.summary?.pending_bookings }} đơn đặt xe đang chờ xác nhận!</h3>
          <p>Vui lòng kiểm tra mục Đơn đặt xe và phản hồi khách hàng sớm nhất có thể.</p>
        </div>
      </div>
      <router-link to="/admin/bookings" class="btn-alert">
        Xử lý ngay <i class="fa-solid fa-arrow-right"></i>
      </router-link>
    </div>

    <!-- Stats Error Notice with Retry -->
    <div v-if="statsError" class="stats-error-banner card">
      <div class="error-msg">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ statsError }}</span>
      </div>
      <button class="btn-retry" @click="fetchStats" type="button">
        <i class="fa-solid fa-rotate-right"></i> Thử lại
      </button>
    </div>

    <!-- Stats Cards -->
    <section class="stats-grid">
      <div class="stat-card card revenue">
        <div class="stat-icon">
          <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        <div class="stat-info">
          <div class="stat-title-row">
            <span class="stat-label">Doanh thu đã thanh toán</span>
            <span class="stat-badge-method" title="Số liệu dựa trên biên lai và duyệt đối soát thủ công">Duyệt biên lai</span>
          </div>
          <h2 class="stat-value">{{ formatCurrency(stats.summary?.valid_paid_revenue || stats.summary?.total_revenue || 0) }}</h2>
          <div class="stat-sub-metrics">
            <span title="Tổng giá trị toàn bộ đơn được tạo (GMV)">GMV: {{ formatCurrency(stats.summary?.total_booking_value || 0) }}</span>
            <span v-if="stats.summary?.total_deposit_held > 0" class="sub-metric-deposit" title="Tiền cọc bảo đảm đang ký quỹ (tách biệt doanh thu thuê xe)">
              • Cọc giữ: {{ formatCurrency(stats.summary?.total_deposit_held) }}
            </span>
            <span v-if="stats.summary?.refund_review_value > 0" class="sub-metric-refund" title="Đơn đã hủy nhưng đã thu tiền, cần xử lý hoàn tiền">
              • Chờ hoàn: {{ formatCurrency(stats.summary?.refund_review_value) }}
            </span>
            <span v-else class="sub-metric-unpaid" title="Đơn đã duyệt xe nhưng chưa thanh toán">
              • Chờ thu: {{ formatCurrency(stats.summary?.unpaid_confirmed_value || 0) }}
            </span>
          </div>
        </div>
      </div>

      <div class="stat-card card bookings">
        <div class="stat-icon">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Tổng đơn đặt xe</span>
          <h2 class="stat-value">{{ stats.summary?.total_bookings || 0 }}</h2>
          <span class="stat-change"><i class="fa-solid fa-clock"></i> {{ stats.summary?.pending_bookings || 0 }} đơn đang chờ duyệt</span>
        </div>
      </div>

      <div class="stat-card card cars">
        <div class="stat-icon">
          <i class="fa-solid fa-car"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Phương tiện sẵn có</span>
          <h2 class="stat-value">{{ stats.summary?.available_cars || 0 }}/{{ stats.summary?.total_cars || 0 }}</h2>
          <span class="stat-change"><i class="fa-solid fa-wrench"></i> {{ (Number(stats.summary?.total_cars || 0) - Number(stats.summary?.available_cars || 0)) }} xe đang bảo trì</span>
        </div>
      </div>

      <div class="stat-card card users">
        <div class="stat-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Khách hàng hệ thống</span>
          <h2 class="stat-value">{{ stats.summary?.total_users || 0 }}</h2>
          <span class="stat-change"><i class="fa-solid fa-user-check"></i> Tài khoản khách hàng kích hoạt</span>
        </div>
      </div>
    </section>

    <!-- Operational Method Disclosure -->
    <div class="revenue-method-notice">
      <i class="fa-solid fa-circle-info"></i>
      <span><strong>Quy chuẩn tài chính:</strong> Doanh thu phản ánh tiền thuê xe thực tế sau đối soát; tiền cọc bảo đảm (<strong class="text-cyan">{{ formatCurrency(stats.summary?.total_deposit_held || 0) }}</strong>) được quản lý ký quỹ độc lập, tuyệt đối không tính vào doanh thu thuê. Đơn hủy đã thu tiền được bóc tách sang mục chờ xử lý hoàn tiền.</span>
    </div>

    <!-- Charts Section -->
    <section class="charts-section">
      <!-- Revenue Chart Card -->
      <div class="chart-card card">
        <div class="chart-header flex-between">
          <div>
            <h3>Xu hướng doanh thu (6 tháng gần nhất)</h3>
            <p class="chart-sub">
              {{ selectedDateBy === 'payment_date' ? 'Dữ liệu phân bổ theo ngày xác nhận thanh toán thực tế (payment_confirmed_at)' : 'Dữ liệu phân bổ theo ngày đặt đơn (created_at)' }}
            </p>
          </div>
          <div class="chart-filters">
            <div class="date-by-select-wrap">
              <label for="date-by-select"><i class="fa-solid fa-calendar-days"></i> Trục lọc:</label>
              <select id="date-by-select" v-model="selectedDateBy" @change="fetchStats" class="select-dimension">
                <option value="payment_date">Ngày thanh toán (Chuẩn)</option>
                <option value="booking_date">Ngày đặt xe</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Legacy Undated Notice Banner -->
        <div v-if="selectedDateBy === 'payment_date' && stats.legacy_undated && stats.legacy_undated.count > 0" class="legacy-undated-banner">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <div>
            <strong>Dữ liệu lịch sử:</strong> Có {{ stats.legacy_undated.count }} đơn đã thanh toán ({{ formatCurrency(stats.legacy_undated.total) }}) trước khi hệ thống lưu mốc ngày xác nhận. Số tiền này được bảo toàn trong tổng doanh thu nhưng không gán vào cột tháng để tránh bịa đặt ngày thu tiền.
          </div>
        </div>

        <div class="chart-container">
          <div v-if="stats.revenue_chart && stats.revenue_chart.length > 0" class="bar-chart">
            <div v-for="(item, index) in stats.revenue_chart" :key="index" class="bar-wrapper">
              <div 
                class="bar" 
                :style="{ height: Math.max(((item.valid_paid_revenue ?? item.total) / maxRevenue * 100), 4) + '%' }"
                :title="item.month + ': ' + formatCurrency(item.valid_paid_revenue ?? item.total)"
              >
                <span class="bar-tooltip">{{ formatCurrency(item.valid_paid_revenue ?? item.total) }}</span>
              </div>
              <span class="bar-label">{{ item.month }}</span>
            </div>
          </div>
          <div v-else class="empty-chart">
            <i class="fa-solid fa-chart-column"></i>
            <p>Chưa có dữ liệu doanh thu tháng này</p>
          </div>
        </div>
      </div>

      <!-- Status Distribution Card -->
      <div class="chart-card card distribution">
        <div class="chart-header">
          <h3>Phân bổ trạng thái đơn</h3>
        </div>
        <div class="status-list" v-if="stats.status_distribution && stats.status_distribution.length > 0">
          <div class="status-item" v-for="s in stats.status_distribution" :key="s.tinh_trang">
            <div class="status-info">
              <span class="status-dot" :class="'status-' + s.tinh_trang"></span>
              <span class="status-name">{{ getStatusLabel(s.tinh_trang) }}</span>
            </div>
            <span class="status-count">{{ s.count }}</span>
          </div>
        </div>
        <div v-else class="empty-chart">
          <p>Chưa có dữ liệu phân bổ đơn</p>
        </div>
      </div>
    </section>

    <!-- Recent Bookings Section -->
    <section class="recent-bookings-section">
        <div class="card glass">
            <div class="card-header flex justify-between items-center mb-6">
                <div>
                  <h3 style="font-size: 1.25rem; font-weight: 800; font-family: var(--font-heading);">Đơn đặt xe gần đây</h3>
                  <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 2px;">5 giao dịch mới nhất nhận từ khách hàng</p>
                </div>
                <router-link to="/admin/bookings" class="btn-text">
                  Xem tất cả đơn <i class="fa-solid fa-arrow-right"></i>
                </router-link>
            </div>
            <div class="table-responsive">
                <!-- API Error State with Retry -->
                <div v-if="bookingsError" class="bookings-error-box" style="padding: 40px; text-align: center;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.2rem; color: #ef4444; margin-bottom: 12px;"></i>
                    <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 6px;">Không thể tải danh sách đơn</h4>
                    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 16px;">{{ bookingsError }}</p>
                    <button class="btn-retry-primary" @click="fetchRecentBookings" type="button">
                      <i class="fa-solid fa-rotate-right"></i> Thử lại
                    </button>
                </div>

                <!-- Loading State -->
                <div v-else-if="loadingBookings" style="padding: 30px; text-align: center;">
                    <div class="skeleton-shimmer" style="height: 48px; border-radius: 8px; margin-bottom: 8px;"></div>
                    <div class="skeleton-shimmer" style="height: 48px; border-radius: 8px;"></div>
                </div>

                <!-- Table Data State -->
                <table class="premium-table" v-else-if="recentBookings.length > 0">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Xe</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái thuê</th>
                            <th>Thanh toán</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in recentBookings" :key="booking.id">
                            <td><span class="code-badge">#{{ booking.ma_dat_xe }}</span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm">{{ (booking.user_name || booking.ho_ten || 'K').charAt(0).toUpperCase() }}</div>
                                    <span>{{ booking.user_name || booking.ho_ten || 'Khách hàng' }}</span>
                                </div>
                            </td>
                            <td>{{ booking.car_name || booking.ten_xe || 'Xe hệ thống' }}</td>
                            <td>{{ formatDate(booking.created_at) }}</td>
                            <td class="font-bold">{{ formatCurrency(booking.tong_tien) }}</td>
                            <td>
                                <span class="status-tag rental" :class="'rental-' + booking.tinh_trang">
                                    {{ getStatusLabel(booking.tinh_trang) }}
                                </span>
                            </td>
                            <td>
                                <span class="status-tag" :class="getPaymentStatusClass(booking)">
                                    {{ getPaymentStatusText(booking) }}
                                </span>
                            </td>
                            <td>
                                <button class="btn-action" @click="viewBooking" title="Chi tiết đơn">
                                  <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-else class="empty-state-box" style="padding: 40px; text-align: center; color: var(--color-text-muted);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
                    <p>Hiện chưa có đơn đặt xe nào trong hệ thống.</p>
                </div>
            </div>
        </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const router = useRouter();

const selectedDateBy = ref('payment_date');
const exportingReport = ref(false);

const handleExportRevenueReport = async () => {
  exportingReport.value = true;
  try {
    const token = localStorage.getItem('token_admin') || localStorage.getItem('token_client');
    const response = await fetch(`/api/admin/reports/export?date_by=${selectedDateBy.value}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'text/csv',
      }
    });

    if (!response.ok) {
      if (response.status === 403) {
        toaster.error("Bạn không có quyền xuất báo cáo doanh thu tài chính!");
      } else {
        toaster.error("Có lỗi xảy ra khi xuất báo cáo!");
      }
      return;
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bao_cao_doanh_thu_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
    toaster.success("Xuất báo cáo doanh thu thành công!");
  } catch (err) {
    toaster.error("Không thể kết nối đến máy chủ để tải báo cáo!");
  } finally {
    exportingReport.value = false;
  }
};

const stats = ref({
  summary: {
    total_revenue: 0,
    valid_paid_revenue: 0,
    total_booking_value: 0,
    confirmed_booking_value: 0,
    paid_booking_value: 0,
    refund_review_value: 0,
    unpaid_confirmed_value: 0,
    total_bookings: 0,
    pending_bookings: 0,
    available_cars: 0,
    total_cars: 0,
    total_users: 0
  },
  revenue_chart: [],
  legacy_undated: { count: 0, total: 0 },
  status_distribution: [],
  payment_distribution: []
});
const recentBookings = ref([]);
const loadingStats = ref(true);
const loadingBookings = ref(true);
const statsError = ref(null);
const bookingsError = ref(null);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('vi-VN');
};

const todayDate = computed(() => {
  return new Intl.DateTimeFormat('vi-VN', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  }).format(new Date());
});

const maxRevenue = computed(() => {
  if (!stats.value.revenue_chart || stats.value.revenue_chart.length === 0) return 1;
  const max = Math.max(...stats.value.revenue_chart.map(i => Number(i.valid_paid_revenue ?? i.total) || 0));
  return max > 0 ? max * 1.2 : 1;
});

const fetchStats = async () => {
  loadingStats.value = true;
  statsError.value = null;
  try {
    const res = await baseRequest.get(`admin/statistics?date_by=${selectedDateBy.value}`);
    if (res.data && res.data.status) {
      stats.value = res.data.data;
    } else {
      statsError.value = res.data?.message || "Không thể tải dữ liệu thống kê từ máy chủ.";
    }
  } catch (err) {
    statsError.value = "Không thể kết nối máy chủ để tải số liệu thống kê.";
    console.error("Lỗi khi tải dữ liệu thống kê:", err);
  } finally {
    loadingStats.value = false;
  }
};

const fetchRecentBookings = async () => {
  loadingBookings.value = true;
  bookingsError.value = null;
  try {
    // Explicit route verified with BE/routes/api.php
    const resBookings = await baseRequest.get('admin/bookings/data');
    if (resBookings.data && resBookings.data.status && Array.isArray(resBookings.data.data)) {
      recentBookings.value = resBookings.data.data.slice(0, 5);
    } else {
      bookingsError.value = resBookings.data?.message || "Không thể tải danh sách đơn đặt xe gần đây.";
    }
  } catch (err) {
    bookingsError.value = "Lỗi kết nối máy chủ khi tải đơn đặt gần đây.";
    console.error("Lỗi khi tải đơn đặt gần đây:", err);
  } finally {
    loadingBookings.value = false;
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const getStatusLabel = (status) => {
  const labels = {
    0: 'Chờ duyệt',
    1: 'Đã duyệt',
    2: 'Đã hủy',
    3: 'Đang thuê',
    4: 'Đã nhận trả',
    5: 'Hoàn tất'
  };
  return labels[Number(status)] || 'Khác';
};

const getPaymentStatusText = (b) => {
  if (!b) return 'Chưa thanh toán';
  if (Number(b.tinh_trang) === 2 && Number(b.is_thanh_toan) === 1) return 'Đã thu (Cần hoàn tiền)';
  if (Number(b.is_thanh_toan) === 1) return 'Đã thanh toán';
  if (b.bill_image) return 'Chờ duyệt bill';
  return 'Chưa thanh toán';
};

const getPaymentStatusClass = (b) => {
  if (!b) return 'pending';
  if (Number(b.tinh_trang) === 2 && Number(b.is_thanh_toan) === 1) return 'refund';
  if (Number(b.is_thanh_toan) === 1) return 'paid';
  if (b.bill_image) return 'verifying';
  return 'pending';
};

const viewBooking = () => {
  router.push('/admin/bookings');
};

onMounted(() => {
  // Stats and Recent Bookings load independently so one failure does not affect the other
  fetchStats();
  fetchRecentBookings();
});
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 32px;
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.welcome-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 40px;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-soft);
  position: relative;
  overflow: hidden;
  border-radius: 20px;
}

.welcome-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: var(--color-text-main);
  letter-spacing: -1px;
}

.welcome-title span {
  color: var(--color-primary);
}

.welcome-banner p {
  color: var(--color-text-muted);
  font-size: 1.1rem;
  margin-top: 8px;
  font-weight: 500;
}

.current-date {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 24px;
  background: var(--bg-main);
  border-radius: 14px;
  font-weight: 700;
  color: var(--color-primary);
  font-family: var(--font-heading);
}

.btn-export-csv {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-weight: 700;
  font-size: 0.95rem;
  font-family: var(--font-heading);
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
  white-space: nowrap;
}

.btn-export-csv:hover:not(:disabled) {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
}

.btn-export-csv:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Pending Alert Banner */
.pending-alert-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
  border-left: 4px solid #ef4444;
  border-radius: var(--radius-md);
  margin-top: -8px;
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 20px;
}

.alert-icon {
  width: 48px;
  height: 48px;
  background: #ef4444;
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.bell-shake {
  animation: shake 2s infinite ease-in-out;
}

@keyframes shake {
  0% { transform: rotate(0); }
  10% { transform: rotate(15deg); }
  20% { transform: rotate(-15deg); }
  30% { transform: rotate(10deg); }
  40% { transform: rotate(-10deg); }
  50% { transform: rotate(5deg); }
  60% { transform: rotate(0); }
  100% { transform: rotate(0); }
}

.alert-text h3 {
  color: #b91c1c;
  font-size: 1.15rem;
  font-weight: 800;
}

.alert-text p {
  color: #991b1b;
  font-size: 0.9rem;
  margin-top: 4px;
}

.btn-alert {
  background: #ef4444;
  color: white;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
}

.btn-alert:hover {
  background: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.stat-card {
  padding: 32px;
  position: relative;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 24px;
}

.stat-icon {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  transition: all 0.3s;
}

.revenue .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.bookings .stat-icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.cars .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.users .stat-icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--color-text-main);
  margin: 4px 0;
  letter-spacing: -1px;
}

.stat-change {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-text-muted);
}

.stat-sub-metrics {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  font-weight: 600;
  margin-top: 2px;
}

.sub-metric-deposit {
  color: #0284c7;
  font-weight: 700;
}

.sub-metric-refund {
  color: #dc2626;
  font-weight: 700;
}

.sub-metric-unpaid {
  color: #d97706;
}

.text-cyan {
  color: #0891b2;
}

.stat-card:hover .stat-icon {
  transform: scale(1.1) rotate(5deg);
}

/* Charts */
.charts-section {
  display: grid;
  grid-template-columns: 1.8fr 1.2fr;
  gap: 32px;
}

.chart-card {
  padding: 40px;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.chart-card h3 {
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.bar-chart {
  width: 100%;
  height: 300px;
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  gap: 20px;
}

.bar-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}

.bar {
  width: 100%;
  max-width: 44px;
  background: var(--grad-active);
  border-radius: 10px 10px 4px 4px;
  position: relative;
  transition: all 0.5s ease;
}

.bar-label {
  margin-top: 16px;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-text-muted);
}

.status-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-item {
  background: var(--bg-main);
  padding: 20px;
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.02);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 12px;
}

.status-0 { background: #f59e0b; }
.status-1 { background: #10b981; }
.status-2 { background: #ef4444; }

.status-name {
  font-weight: 700;
  font-family: var(--font-heading);
  color: var(--color-text-main);
}

.status-count {
  font-weight: 800;
  font-size: 1.1rem;
}

/* Recent Bookings Table */
.recent-bookings-section {
    margin-top: 32px;
}

.premium-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.premium-table th {
    padding: 16px;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.premium-table td {
    padding: 16px;
    background: #f8fafc;
    border-top: 1px solid rgba(0, 0, 0, 0.02);
    border-bottom: 1px solid rgba(0, 0, 0, 0.02);
}

.premium-table tr td:first-child { border-radius: 12px 0 0 12px; border-left: 1px solid rgba(0, 0, 0, 0.02); }
.premium-table tr td:last-child { border-radius: 0 12px 12px 0; border-right: 1px solid rgba(0, 0, 0, 0.02); }

.code-badge {
    padding: 6px 12px;
    background: #e2e8f0;
    color: #475569;
    border-radius: 8px;
    font-family: monospace;
    font-weight: 700;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    background: var(--grad-active);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.stat-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.stat-badge-method {
  font-size: 0.68rem;
  padding: 2px 8px;
  border-radius: 6px;
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
  font-weight: 700;
  text-transform: none;
  letter-spacing: normal;
}

.stat-sub-metrics {
  display: flex;
  gap: 6px;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  font-weight: 600;
  margin-top: 2px;
}

.sub-metric-refund {
  color: #ef4444;
  font-weight: 700;
}

.sub-metric-unpaid {
  color: #f59e0b;
  font-weight: 600;
}

.revenue-method-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  background: rgba(59, 130, 246, 0.05);
  border: 1px dashed rgba(59, 130, 246, 0.3);
  border-radius: 14px;
  font-size: 0.85rem;
  color: var(--color-text-main);
  line-height: 1.5;
}

.revenue-method-notice i {
  color: #3b82f6;
  font-size: 1.1rem;
}

.date-by-select-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.select-dimension {
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--bg-surface);
  color: var(--color-text-main);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  outline: none;
}

.select-dimension:focus {
  border-color: var(--color-primary);
}

.legacy-undated-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 18px;
  margin-bottom: 20px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #f59e0b;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #92400e;
  line-height: 1.45;
}

.status-tag {
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 800;
}

.status-tag.paid { background: #f0fdf4; color: #22c55e; }
.status-tag.pending { background: #fff7ed; color: #f97316; }
.status-tag.refund { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.status-tag.verifying { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

.status-tag.rental {
  font-weight: 700;
  border: 1px solid transparent;
}
.status-tag.rental-0 { background: #fffbeb; color: #b45309; border-color: #fef3c7; }
.status-tag.rental-1 { background: #e0e7ff; color: #4338ca; border-color: #c7d2fe; }
.status-tag.rental-2 { background: #fef2f2; color: #b91c1c; border-color: #fee2e2; }
.status-tag.rental-3 { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
.status-tag.rental-4 { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; }
.status-tag.rental-5 { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: white;
    color: var(--color-text-muted);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-action:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.btn-text {
    font-weight: 700;
    color: var(--color-primary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stats-error-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  border-radius: var(--radius-md);
  margin-top: -8px;
}

.stats-error-banner .error-msg {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #b91c1c;
  font-weight: 600;
  font-size: 0.95rem;
}

.btn-retry {
  background: white;
  color: #b91c1c;
  border: 1px solid #f87171;
  padding: 7px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: #fee2e2;
}

.bookings-error-box {
  background: #fff5f5;
  border-radius: 12px;
  border: 1px dashed #fca5a5;
  margin: 12px 0;
}

.btn-retry-primary {
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 9px 20px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-retry-primary:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
}

@media (max-width: 1200px) {
  .charts-section { grid-template-columns: 1fr; }
}
</style>
