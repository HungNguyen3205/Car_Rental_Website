<template>
  <div class="dashboard-page">
    <!-- Header info removed or simplified as TopAdmin has the title -->
    <section class="welcome-banner card">
      <div class="welcome-text">
        <h2 class="welcome-title">Chào mừng trở lại, <span>Admin</span></h2>
        <p>Hệ thống đang hoạt động ổn định. Đây là tổng quan hoạt động trong ngày.</p>
      </div>
      <div class="current-date">
        <i class="fa-solid fa-calendar-check"></i>
        <span>{{ todayDate }}</span>
      </div>
    </section>

    <!-- Stats Cards -->
    <section class="stats-grid">
      <div class="stat-card card revenue">
        <div class="stat-icon">
          <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Tổng doanh thu</span>
          <h2 class="stat-value">{{ formatCurrency(stats.summary?.total_revenue || 0) }}</h2>
          <span class="stat-change growth"><i class="fa-solid fa-arrow-up"></i> 12% so với tháng trước</span>
        </div>
      </div>

      <div class="stat-card card bookings">
        <div class="stat-icon">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Tổng đơn đặt xe</span>
          <h2 class="stat-value">{{ stats.summary?.total_bookings || 0 }}</h2>
          <span class="stat-change"><i class="fa-solid fa-clock"></i> {{ stats.summary?.pending_bookings || 0 }} đơn đang chờ</span>
        </div>
      </div>

      <div class="stat-card card cars">
        <div class="stat-icon">
          <i class="fa-solid fa-car"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Phương tiện sẵn có</span>
          <h2 class="stat-value">{{ stats.summary?.available_cars || 0 }}/{{ stats.summary?.total_cars || 0 }}</h2>
          <span class="stat-change"><i class="fa-solid fa-wrench"></i> {{ (stats.summary?.total_cars - stats.summary?.available_cars) || 0 }} xe đang bảo trì</span>
        </div>
      </div>

      <div class="stat-card card users">
        <div class="stat-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
          <span class="stat-label">Khách hàng tin dùng</span>
          <h2 class="stat-value">{{ stats.summary?.total_users || 0 }}</h2>
          <span class="stat-change growth"><i class="fa-solid fa-user-plus"></i> +5 thành viên mới tuần này</span>
        </div>
      </div>
    </section>

    <!-- Charts Section -->
    <section class="charts-section">
      <!-- Revenue Chart Card -->
      <div class="chart-card card">
        <div class="chart-header">
          <h3>Xu hướng doanh thu (6 tháng)</h3>
        </div>
        <div class="chart-container">
          <div class="bar-chart">
            <div v-for="(item, index) in stats.revenue_chart" :key="index" class="bar-wrapper">
              <div class="bar" :style="{ height: (item.total / maxRevenue * 100) + '%' }">
                <span class="bar-tooltip">{{ formatCurrency(item.total) }}</span>
              </div>
              <span class="bar-label">{{ item.month }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Distribution Card -->
      <div class="chart-card card distribution">
        <div class="chart-header">
          <h3>Phân bổ trạng thái đơn</h3>
        </div>
        <div class="status-list">
          <div class="status-item" v-for="s in stats.status_distribution" :key="s.tinh_trang">
            <div class="status-info">
              <span class="status-dot" :class="'status-' + s.tinh_trang"></span>
              <span class="status-name">{{ getStatusLabel(s.tinh_trang) }}</span>
            </div>
            <span class="status-count">{{ s.count }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Bookings Section -->
    <section class="recent-bookings-section">
        <div class="card glass">
            <div class="card-header flex justify-between items-center mb-6 ">
                <h3 >Đơn đặt xe gần đây</h3>
                <router-link to="/admin/bookings" class="btn-text">Xem tất cả <i class="fa-solid fa-arrow-right"></i></router-link>
            </div>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Xe</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in recentBookings" :key="booking.id">
                            <td><span class="code-badge">#{{ booking.ma_dat_xe }}</span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm">{{ booking.ho_ten.charAt(0) }}</div>
                                    <span>{{ booking.ho_ten }}</span>
                                </div>
                            </td>
                            <td>{{ booking.ten_xe }}</td>
                            <td>{{ formatDate(booking.created_at) }}</td>
                            <td class="font-bold">{{ booking.tong_tien.toLocaleString() }}đ</td>
                            <td>
                                <span class="status-tag" :class="booking.is_thanh_toan ? 'paid' : 'pending'">
                                    {{ booking.is_thanh_toan ? 'Đã thanh toán' : 'Chờ xử lý' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn-action"><i class="fa-solid fa-eye"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';

const stats = ref({
  summary: {},
  revenue_chart: [],
  status_distribution: []
});
const recentBookings = ref([]);

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
  if (!stats.value.revenue_chart.length) return 1;
  return Math.max(...stats.value.revenue_chart.map(i => i.total)) * 1.2;
});

const fetchStats = async () => {
  try {
    const res = await baseRequest.get('admin/statistics');
    if (res.data.status) {
      stats.value = res.data.data;
    }
    // Fetch latest bookings
    const resBookings = await baseRequest.get('admin/bookings');
    if (resBookings.data && resBookings.data.status) {
        recentBookings.value = resBookings.data.data.slice(0, 5);
    }
  } catch (err) {
    console.error("Error fetching stats:", err);
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const getStatusLabel = (status) => {
  const labels = ['Đang chờ', 'Xác nhận', 'Đã hủy'];
  return labels[status] || 'Khác';
};

onMounted(() => {
  fetchStats();
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

.status-tag {
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 800;
}

.status-tag.paid { background: #f0fdf4; color: #22c55e; }
.status-tag.pending { background: #fff7ed; color: #f97316; }

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

@media (max-width: 1200px) {
  .charts-section { grid-template-columns: 1fr; }
}
</style>
