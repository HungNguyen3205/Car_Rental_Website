<template>
  <div class="dashboard-page">
    <!-- 1. Header Trung Tâm Điều Hành -->
    <header class="ops-header card">
      <div class="header-left">
        <div class="header-badge">
          <span class="pulse-dot"></span>
          <span>Trung Tâm Điều Hành Thuê Xe</span>
        </div>
        <h1 class="header-title">Tổng Quan <span>Vận Hành Đội Xe</span></h1>
        <p class="header-subtitle">
          Giám sát lịch trình giao nhận xe, xử lý yêu cầu tức thời và theo dõi dòng tiền minh bạch.
        </p>
      </div>

      <div class="header-controls">
        <!-- Bộ lọc ngày thống nhất -->
        <div class="filter-bar">
          <div class="preset-buttons">
            <button 
              v-for="p in datePresets" 
              :key="p.id" 
              type="button" 
              class="btn-preset" 
              :class="{ active: selectedPreset === p.id }"
              @click="applyPreset(p.id)"
            >
              {{ p.label }}
            </button>
          </div>

          <!-- Tùy chọn ngày nếu chọn 'custom' -->
          <div v-if="selectedPreset === 'custom'" class="custom-dates">
            <input 
              type="date" 
              v-model="customStartDate" 
              class="date-input" 
              title="Từ ngày"
            />
            <span class="date-sep">→</span>
            <input 
              type="date" 
              v-model="customEndDate" 
              class="date-input" 
              title="Đến ngày"
            />
            <button class="btn-apply-date" @click="fetchStats" type="button">
              Áp dụng
            </button>
          </div>

          <!-- Trục ngày -->
          <div class="dimension-picker">
            <select v-model="selectedDateBy" @change="fetchStats" class="dimension-select" title="Trục phân bổ thời gian">
              <option value="payment_date">Trục: Ngày thanh toán</option>
              <option value="booking_date">Trục: Ngày đặt xe</option>
            </select>
          </div>
        </div>

        <!-- Các nút thao tác Header -->
        <div class="action-buttons">
          <button 
            class="btn-action-refresh" 
            @click="fetchStats" 
            :disabled="loadingStats"
            title="Làm mới dữ liệu toàn hệ thống"
          >
            <i class="fa-solid fa-rotate-right" :class="{ 'fa-spin': loadingStats }"></i>
            <span>{{ loadingStats ? 'Đang tải...' : 'Làm mới' }}</span>
          </button>

          <button 
            v-if="canViewFinancial"
            class="btn-action-export" 
            @click="handleExportCsv" 
            :disabled="exportingReport"
            title="Xuất báo cáo doanh thu tài chính theo bộ lọc hiện tại (CSV UTF-8)"
          >
            <i class="fa-solid fa-file-csv" :class="{ 'fa-spin': exportingReport }"></i>
            <span>{{ exportingReport ? 'Đang xuất...' : 'Xuất CSV' }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Scope Disclosure Banner -->
    <div class="scope-notice">
      <div class="notice-left">
        <i class="fa-solid fa-circle-info"></i>
        <span>
          <strong>Phạm vi báo cáo:</strong> Số liệu doanh thu và GMV phản ánh trong 
          <strong>{{ currentRangeLabel }}</strong> ({{ selectedDateBy === 'payment_date' ? 'theo ngày thanh toán' : 'theo ngày đặt xe' }}). 
          Các chỉ số giao nhận, việc cần xử lý và hiện trạng xe phản ánh <strong>thời điểm hiện tại</strong>.
        </span>
      </div>
      <div class="notice-time">
        <i class="fa-regular fa-clock"></i>
        <span>{{ currentTimeStr }}</span>
      </div>
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

    <!-- 2. Hàng KPI Ưu Tiên Vận Hành -->
    <section class="kpi-grid">
      <!-- KPI 1: Đơn chờ duyệt -->
      <div class="kpi-card pending-card pointer" @click="navigateToBookings('status=0')" title="Bấm để mở danh sách đơn chờ duyệt">
        <div class="kpi-icon-wrap amber">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div class="kpi-body">
          <span class="kpi-label">Đơn chờ duyệt</span>
          <div class="kpi-value-row">
            <h2 class="kpi-value">{{ stats.summary?.pending_bookings || 0 }}</h2>
            <span v-if="(stats.summary?.pending_bookings || 0) > 0" class="kpi-badge urgent">Cần xử lý</span>
            <span v-else class="kpi-badge ok">Ổn định</span>
          </div>
          <span class="kpi-subtext">Đơn đặt mới đang chờ nhân viên xác nhận</span>
        </div>
      </div>

      <!-- KPI 2: Giao xe hôm nay -->
      <div class="kpi-card delivery-card pointer" @click="navigateToBookings('status=1')" title="Bấm để mở danh sách giao xe">
        <div class="kpi-icon-wrap emerald">
          <i class="fa-solid fa-key"></i>
        </div>
        <div class="kpi-body">
          <span class="kpi-label">Giao xe hôm nay</span>
          <div class="kpi-value-row">
            <h2 class="kpi-value">{{ stats.summary?.deliveries_today || 0 }}</h2>
            <span class="kpi-badge info">Lịch hẹn</span>
          </div>
          <span class="kpi-subtext">
            Chờ giao: <strong>{{ stats.summary?.deliveries_pending || 0 }}</strong> • Đã bàn giao: <strong>{{ stats.summary?.deliveries_completed || 0 }}</strong>
          </span>
        </div>
      </div>

      <!-- KPI 3: Nhận trả hôm nay -->
      <div class="kpi-card return-card pointer" @click="navigateToBookings('status=3')" title="Bấm để mở danh sách xe đang thuê">
        <div class="kpi-icon-wrap sky">
          <i class="fa-solid fa-car-side"></i>
        </div>
        <div class="kpi-body">
          <span class="kpi-label">Nhận trả hôm nay</span>
          <div class="kpi-value-row">
            <h2 class="kpi-value">{{ stats.summary?.returns_today || 0 }}</h2>
            <span class="kpi-badge sky">Dự kiến</span>
          </div>
          <span class="kpi-subtext">
            Đang lưu hành: <strong>{{ stats.summary?.returns_pending || 0 }}</strong> • Đã nhận: <strong>{{ stats.summary?.returns_received || 0 }}</strong>
          </span>
        </div>
      </div>

      <!-- KPI 4: Thực thu tiền thuê (hoặc Tỷ lệ xe sẵn sàng nếu không có quyền tài chính) -->
      <div v-if="canViewFinancial" class="kpi-card revenue-card">
        <div class="kpi-icon-wrap green">
          <i class="fa-solid fa-sack-dollar"></i>
        </div>
        <div class="kpi-body">
          <div class="kpi-label-row">
            <span class="kpi-label">Thực thu thuê xe</span>
            <span class="kpi-badge-reconciled" title="Tiền thuê đã đối soát sau trừ hoàn tiền (không bao gồm tiền cọc)">Thực thu</span>
          </div>
          <div class="kpi-value-row">
            <h2 class="kpi-value highlight">
              {{ formatCurrency(stats.summary?.net_rental_collected ?? stats.summary?.valid_paid_revenue ?? 0) }}
            </h2>
          </div>
          <div class="kpi-finance-breakdown">
            <span title="Tổng giá trị đơn được tạo trong kỳ (GMV)">
              GMV: <strong>{{ formatCurrency(stats.summary?.total_booking_value || 0) }}</strong>
            </span>
            <span class="deposit-pill" title="Tiền cọc bảo đảm đang ký quỹ tính đến mốc báo cáo (tách biệt doanh thu thuê)">
              • Cọc giữ: <strong>{{ formatCurrency(stats.summary?.total_deposit_held || 0) }}</strong>
            </span>
          </div>
        </div>
      </div>

      <!-- KPI 4 (Dành cho Điều phối viên không có quyền tài chính): Tỷ lệ xe sẵn sàng -->
      <div v-else class="kpi-card fleet-card">
        <div class="kpi-icon-wrap indigo">
          <i class="fa-solid fa-car"></i>
        </div>
        <div class="kpi-body">
          <span class="kpi-label">Tỷ lệ xe sẵn sàng</span>
          <div class="kpi-value-row">
            <h2 class="kpi-value highlight">{{ stats.fleet_breakdown?.available_rate || stats.summary?.available_rate || 0 }}%</h2>
            <span class="kpi-badge ok">Điều phối</span>
          </div>
          <span class="kpi-subtext">
            <strong>{{ stats.fleet_breakdown?.available_cars || 0 }}</strong> xe sẵn sàng trên tổng <strong>{{ stats.fleet_breakdown?.total_cars || 0 }}</strong> xe
          </span>
        </div>
      </div>
    </section>

    <!-- 3. Khối Việc Cần Xử Lý Ngay (Action Center) -->
    <section class="action-center card">
      <div class="section-header">
        <div class="title-with-badge">
          <i class="fa-solid fa-bolt-lightning text-amber"></i>
          <h3>Việc Cần Xử Lý Ngay</h3>
          <span v-if="totalActionCount > 0" class="badge-count-urgent">{{ totalActionCount }}</span>
          <span v-else class="badge-count-ok">Tất cả ổn định</span>
        </div>
        <p class="section-sub">
          Các yêu cầu và cảnh báo vận hành cần nhân viên can thiệp xử lý kịp thời.
        </p>
      </div>

      <div class="action-grid">
        <!-- Action Item 1: Xe quá hạn trả -->
        <div class="action-card overdue" :class="{ 'has-alerts': (stats.operational_actions?.overdue_returns || 0) > 0 }">
          <div class="action-card-header">
            <div class="action-card-title">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>Xe quá hạn trả</span>
            </div>
            <span class="action-count-pill red">{{ stats.operational_actions?.overdue_returns || 0 }} xe</span>
          </div>
          <p class="action-desc">
            Xe đang cho thuê nhưng đã vượt quá giờ hẹn kết thúc hợp đồng theo lịch.
          </p>

          <!-- Danh sách xe quá hạn nếu có -->
          <div v-if="(stats.operational_actions?.overdue_bookings || []).length > 0" class="overdue-list">
            <div 
              v-for="b in stats.operational_actions.overdue_bookings" 
              :key="b.id" 
              class="overdue-item pointer"
              @click="navigateToBookings(`search=${b.ma_dat_xe}`)"
              title="Bấm để xem chi tiết đơn"
            >
              <div class="overdue-car-info">
                <strong>{{ b.car_name }}</strong>
                <span class="code">#{{ b.ma_dat_xe }}</span>
              </div>
              <div class="overdue-customer">
                <span>{{ b.user_name }}</span>
                <a v-if="b.user_phone" :href="'tel:' + b.user_phone" class="phone-link" @click.stop title="Gọi điện">
                  <i class="fa-solid fa-phone"></i> {{ b.user_phone }}
                </a>
              </div>
              <div class="overdue-time-tag">
                <i class="fa-regular fa-clock"></i>
                <span>Trễ +{{ b.hours_overdue }}h</span>
              </div>
            </div>
          </div>
          <div v-else class="action-empty">
            <i class="fa-solid fa-circle-check text-emerald"></i>
            <span>Không có xe nào quá hạn trả</span>
          </div>
        </div>

        <!-- Action Item 2: Biên lai chờ đối soát -->
        <div class="action-card receipts" :class="{ 'has-alerts': (stats.operational_actions?.pending_receipts || 0) > 0 }">
          <div class="action-card-header">
            <div class="action-card-title">
              <i class="fa-solid fa-receipt"></i>
              <span>Biên lai chờ duyệt</span>
            </div>
            <span class="action-count-pill amber">{{ stats.operational_actions?.pending_receipts || 0 }} đơn</span>
          </div>
          <p class="action-desc">
            Khách hàng đã chuyển khoản và tải ảnh biên lai lên, đang chờ kế toán đối soát xác nhận.
          </p>
          <div class="action-footer">
            <button class="btn-action-link" @click="navigateToBookings('payment=verifying')">
              <span>Mở danh sách đối soát</span>
              <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Action Item 3: Yêu cầu tư vấn mới -->
        <div class="action-card inquiries" :class="{ 'has-alerts': (stats.operational_actions?.new_contacts || 0) > 0 }">
          <div class="action-card-header">
            <div class="action-card-title">
              <i class="fa-solid fa-comments"></i>
              <span>Yêu cầu tư vấn mới</span>
            </div>
            <span class="action-count-pill sky">{{ stats.operational_actions?.new_contacts || 0 }} yêu cầu</span>
          </div>
          <p class="action-desc">
            Tin nhắn liên hệ, yêu cầu báo giá hoặc tư vấn dịch vụ gửi qua biểu mẫu liên hệ chưa xử lý.
          </p>
          <div class="action-footer">
            <router-link to="/admin/contacts" class="btn-action-link">
              <span>Xem liên hệ & tư vấn</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. Tổng Quan Đội Xe (Fleet Overview Bar) -->
    <section class="fleet-overview card">
      <div class="section-header flex-between">
        <div>
          <h3>Hiện Trạng Đội Xe</h3>
          <p class="section-sub">
            Phân định rõ xe sẵn sàng theo lịch, xe đang cho thuê và xe bảo trì tại thời điểm hiện tại.
          </p>
        </div>
        <router-link to="/admin/cars" class="btn-text">
          Quản lý đội xe <i class="fa-solid fa-arrow-right"></i>
        </router-link>
      </div>

      <!-- Thanh trạng thái tỷ lệ đa màu -->
      <div class="fleet-progress-track">
        <div 
          class="segment available" 
          :style="{ width: availablePercent + '%' }"
          :title="`Sẵn sàng: ${stats.fleet_breakdown?.available_cars || 0} xe (${availablePercent}%)`"
        ></div>
        <div 
          class="segment renting" 
          :style="{ width: rentingPercent + '%' }"
          :title="`Đang lưu hành: ${stats.fleet_breakdown?.renting_cars || 0} xe (${rentingPercent}%)`"
        ></div>
        <div 
          class="segment maintenance" 
          :style="{ width: maintenancePercent + '%' }"
          :title="`Bảo trì/Tạm dừng: ${stats.fleet_breakdown?.maintenance_cars || 0} xe (${maintenancePercent}%)`"
        ></div>
      </div>

      <!-- Thẻ chú thích 3 nhóm -->
      <div class="fleet-legend-grid">
        <div class="legend-card">
          <div class="legend-header">
            <span class="legend-dot dot-emerald"></span>
            <span class="legend-name">Sẵn sàng theo lịch</span>
          </div>
          <div class="legend-value-row">
            <span class="legend-count">{{ stats.fleet_breakdown?.available_cars || 0 }}</span>
            <span class="legend-unit">xe ({{ availablePercent }}%)</span>
          </div>
          <span class="legend-note">Không trùng lịch thuê, sẵn sàng phục vụ khách</span>
        </div>

        <div class="legend-card">
          <div class="legend-header">
            <span class="legend-dot dot-sky"></span>
            <span class="legend-name">Đang lưu hành (Cho thuê)</span>
          </div>
          <div class="legend-value-row">
            <span class="legend-count">{{ stats.fleet_breakdown?.renting_cars || 0 }}</span>
            <span class="legend-unit">xe ({{ rentingPercent }}%)</span>
          </div>
          <span class="legend-note">Hợp đồng đang chạy hoặc đã giao khách</span>
        </div>

        <div class="legend-card">
          <div class="legend-header">
            <span class="legend-dot dot-amber"></span>
            <span class="legend-name">Bảo trì & Tạm ngưng</span>
          </div>
          <div class="legend-value-row">
            <span class="legend-count">{{ stats.fleet_breakdown?.maintenance_cars || 0 }}</span>
            <span class="legend-unit">xe ({{ maintenancePercent }}%)</span>
          </div>
          <span class="legend-note">Đang kiểm định, bảo dưỡng hoặc tạm ẩn</span>
        </div>

        <div class="legend-card summary-box">
          <div class="legend-header">
            <span class="legend-dot dot-gray"></span>
            <span class="legend-name">Quy mô toàn đội xe</span>
          </div>
          <div class="legend-value-row">
            <span class="legend-count">{{ stats.fleet_breakdown?.total_cars || 0 }}</span>
            <span class="legend-unit">xe</span>
          </div>
          <span class="legend-note">Tỷ lệ khả dụng: {{ stats.fleet_breakdown?.available_rate || 0 }}%</span>
        </div>
      </div>
    </section>

    <!-- 5. Biểu Đồ Thu Tiền Thực Tế -->
    <section class="chart-section">
      <!-- Biểu đồ thu tiền thực tế (Nếu có quyền tài chính) -->
      <div v-if="canViewFinancial" class="chart-card card">
        <div class="chart-header flex-between">
          <div>
            <div class="chart-title-wrap">
              <h3>Dòng Tiền Thu Thực Tế</h3>
              <span class="granularity-tag">
                {{ stats.chart_granularity === 'daily' ? 'Thống kê theo ngày' : 'Thống kê theo tháng' }}
              </span>
            </div>
            <p class="chart-sub">
              {{ selectedDateBy === 'payment_date' ? 'Dữ liệu phân bổ theo ngày xác nhận thanh toán thực tế (chuẩn đối soát)' : 'Dữ liệu phân bổ theo ngày đặt đơn' }}
            </p>
          </div>
          <div class="chart-unit-badge">
            <span>Đơn vị: VNĐ</span>
          </div>
        </div>

        <!-- Legacy Undated Notice Banner -->
        <div v-if="selectedDateBy === 'payment_date' && stats.legacy_undated && stats.legacy_undated.count > 0" class="legacy-undated-banner">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <div>
            <strong>Dữ liệu lịch sử:</strong> Có {{ stats.legacy_undated.count }} đơn đã thanh toán ({{ formatCurrency(stats.legacy_undated.total) }}) trước khi hệ thống lưu mốc ngày xác nhận. Số tiền này được bảo toàn trong tổng doanh thu nhưng không gán vào cột để tránh sai lệch mốc thời gian.
          </div>
        </div>

        <!-- Biểu đồ cột CSS Bars -->
        <div class="chart-container">
          <div v-if="stats.revenue_chart && stats.revenue_chart.length > 0" class="bar-chart">
            <div v-for="(item, index) in stats.revenue_chart" :key="index" class="bar-wrapper">
              <div 
                class="bar-track"
                :title="`${item.label || item.month}: ${formatCurrency(item.valid_paid_revenue ?? item.total)}`"
              >
                <div 
                  class="bar-fill" 
                  :style="{ height: getBarHeightPercent(item) + '%' }"
                >
                  <span class="bar-tooltip">{{ formatCurrency(item.valid_paid_revenue ?? item.total) }}</span>
                </div>
              </div>
              <span class="bar-label">{{ item.label || item.month }}</span>
            </div>
          </div>
          <div v-else class="empty-chart">
            <i class="fa-solid fa-chart-column"></i>
            <p>Không có dữ liệu phát sinh trong khoảng thời gian đã chọn</p>
          </div>
        </div>
      </div>

      <!-- Nếu không có quyền tài chính: Thông báo dành riêng cho Điều phối viên -->
      <div v-else class="chart-card card dispatcher-notice-card">
        <div class="dispatcher-notice-content">
          <div class="notice-icon">
            <i class="fa-solid fa-user-shield"></i>
          </div>
          <h3>Chế Độ Điều Phối Vận Hành</h3>
          <p>
            Bạn đang đăng nhập với vai trò Điều Phối Viên. Phân hệ tài chính và biểu đồ doanh thu chỉ hiển thị cho Kế Toán và Chủ Hệ Thống.
          </p>
          <div class="dispatcher-quick-actions">
            <router-link to="/admin/bookings" class="btn-dispatcher">
              <i class="fa-solid fa-calendar-check"></i> Xử lý đơn đặt xe
            </router-link>
            <router-link to="/admin/cars" class="btn-dispatcher secondary">
              <i class="fa-solid fa-car-side"></i> Quản lý đội xe
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 6. Bảng 5 Đơn Đặt Xe Gần Đây -->
    <section class="recent-bookings-section">
      <div class="recent-bookings-card card">
        <div class="card-header flex-between">
          <div>
            <h3>Đơn Đặt Xe Mới Nhất</h3>
            <p class="section-sub">5 giao dịch phát sinh gần nhất trong hệ thống (nhấp vào hàng để xem chi tiết)</p>
          </div>
          <router-link to="/admin/bookings" class="btn-text">
            Xem tất cả đơn <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>

        <div class="table-responsive">
          <table class="ops-table" v-if="recentBookings.length > 0">
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Xe</th>
                <th>Thời gian</th>
                <th v-if="canViewFinancial">Số tiền</th>
                <th>Trạng thái thuê</th>
                <th>Thanh toán</th>
                <th class="text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="b in recentBookings" 
                :key="b.id" 
                class="table-row pointer"
                @click="navigateToBookings(`search=${b.ma_dat_xe}`)"
                title="Bấm để mở chi tiết đơn này"
              >
                <td>
                  <span class="code-badge">#{{ b.ma_dat_xe }}</span>
                </td>
                <td>
                  <div class="user-inline">
                    <span class="avatar-sm">{{ (b.user_name || 'K').charAt(0).toUpperCase() }}</span>
                    <div class="user-details">
                      <span class="name">{{ b.user_name || 'Khách hàng' }}</span>
                      <small class="phone">{{ b.user_phone }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="car-name-text">{{ b.car_name || 'Xe hệ thống' }}</span>
                </td>
                <td>
                  <span class="time-text">{{ b.created_at }}</span>
                </td>
                <td v-if="canViewFinancial">
                  <strong class="amount-text">{{ formatCurrency(b.tong_tien) }}</strong>
                </td>
                <td>
                  <span class="status-pill rental" :class="'rental-' + b.tinh_trang">
                    {{ getRentalStatusLabel(b.tinh_trang) }}
                  </span>
                </td>
                <td>
                  <span class="status-pill payment" :class="getPaymentStatusClass(b)">
                    {{ getPaymentStatusText(b) }}
                  </span>
                </td>
                <td class="text-right">
                  <button 
                    class="btn-row-action" 
                    @click.stop="navigateToBookings(`search=${b.ma_dat_xe}`)" 
                    title="Mở đơn này"
                  >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else class="empty-table">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p>Chưa có đơn đặt xe nào trong hệ thống</p>
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

// Bộ lọc ngày
const datePresets = [
  { id: 'today', label: 'Hôm nay' },
  { id: '7days', label: '7 ngày qua' },
  { id: '30days', label: '30 ngày qua' },
  { id: 'this_month', label: 'Tháng này' },
  { id: 'custom', label: 'Tùy chọn' },
  { id: 'all', label: 'Toàn bộ' }
];

const selectedPreset = ref('30days');
const selectedDateBy = ref('payment_date');
const customStartDate = ref('');
const customEndDate = ref('');
const exportingReport = ref(false);
const loadingStats = ref(true);
const statsError = ref(null);

// Dữ liệu thống kê
const stats = ref({
  summary: {
    total_bookings: 0,
    pending_bookings: 0,
    valid_paid_revenue: 0,
    net_rental_collected: 0,
    total_booking_value: 0,
    total_deposit_held: 0,
    refund_review_value: 0,
    unpaid_confirmed_value: 0,
    deliveries_today: 0,
    deliveries_pending: 0,
    deliveries_completed: 0,
    returns_today: 0,
    returns_pending: 0,
    returns_received: 0,
    overdue_returns_count: 0,
    available_rate: 0,
  },
  operational_actions: {
    pending_receipts: 0,
    new_contacts: 0,
    overdue_returns: 0,
    overdue_bookings: [],
  },
  fleet_breakdown: {
    total_cars: 0,
    available_cars: 0,
    renting_cars: 0,
    maintenance_cars: 0,
    available_rate: 0,
  },
  recent_bookings: [],
  revenue_chart: [],
  chart_granularity: 'monthly',
  legacy_undated: { count: 0, total: 0 },
});

const canViewFinancial = ref(true);

const recentBookings = computed(() => {
  return stats.value.recent_bookings || [];
});

// Tính toán thời gian & nhãn bộ lọc
const currentTimeStr = computed(() => {
  return new Intl.DateTimeFormat('vi-VN', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date());
});

const currentRangeLabel = computed(() => {
  const map = {
    today: 'Hôm nay',
    '7days': '7 ngày vừa qua',
    '30days': '30 ngày vừa qua',
    this_month: 'Tháng hiện tại',
    all: 'Toàn bộ thời gian',
    custom: customStartDate.value && customEndDate.value
      ? `Từ ${customStartDate.value} đến ${customEndDate.value}`
      : 'Khoảng tùy chọn'
  };
  return map[selectedPreset.value] || 'Khoảng thời gian đã chọn';
});

const totalActionCount = computed(() => {
  const actions = stats.value.operational_actions || {};
  return (actions.overdue_returns || 0) + (actions.pending_receipts || 0) + (actions.new_contacts || 0);
});

// Tỷ lệ phân bổ đội xe (%)
const availablePercent = computed(() => {
  const total = stats.value.fleet_breakdown?.total_cars || 0;
  if (!total) return 0;
  const avail = stats.value.fleet_breakdown?.available_cars || 0;
  return Math.round((avail / total) * 100);
});

const rentingPercent = computed(() => {
  const total = stats.value.fleet_breakdown?.total_cars || 0;
  if (!total) return 0;
  const renting = stats.value.fleet_breakdown?.renting_cars || 0;
  return Math.round((renting / total) * 100);
});

const maintenancePercent = computed(() => {
  const total = stats.value.fleet_breakdown?.total_cars || 0;
  if (!total) return 0;
  const maint = stats.value.fleet_breakdown?.maintenance_cars || 0;
  return Math.round((maint / total) * 100);
});

// Chiều cao cột biểu đồ
const maxRevenueValue = computed(() => {
  const chart = stats.value.revenue_chart || [];
  if (!chart.length) return 1;
  const max = Math.max(...chart.map(i => Number(i.valid_paid_revenue ?? i.total ?? 0)));
  return max > 0 ? max * 1.15 : 1;
});

const getBarHeightPercent = (item) => {
  const val = Number(item.valid_paid_revenue ?? item.total ?? 0);
  if (val <= 0) return 4;
  return Math.min(100, Math.max(6, Math.round((val / maxRevenueValue.value) * 100)));
};

// Áp dụng Preset ngày
const applyPreset = (presetId) => {
  selectedPreset.value = presetId;
  const today = new Date();

  if (presetId === 'today') {
    const ymd = today.toISOString().slice(0, 10);
    customStartDate.value = ymd;
    customEndDate.value = ymd;
  } else if (presetId === '7days') {
    const past = new Date(today);
    past.setDate(past.getDate() - 6);
    customStartDate.value = past.toISOString().slice(0, 10);
    customEndDate.value = today.toISOString().slice(0, 10);
  } else if (presetId === '30days') {
    const past = new Date(today);
    past.setDate(past.getDate() - 29);
    customStartDate.value = past.toISOString().slice(0, 10);
    customEndDate.value = today.toISOString().slice(0, 10);
  } else if (presetId === 'this_month') {
    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    customStartDate.value = start.toISOString().slice(0, 10);
    customEndDate.value = end.toISOString().slice(0, 10);
  } else if (presetId === 'all') {
    customStartDate.value = '';
    customEndDate.value = '';
  }

  fetchStats();
};

// Gọi API lấy dữ liệu thống kê
const fetchStats = async () => {
  loadingStats.value = true;
  statsError.value = null;

  try {
    const params = new URLSearchParams();
    params.append('date_by', selectedDateBy.value);

    if (selectedPreset.value !== 'all' && customStartDate.value && customEndDate.value) {
      params.append('start_date', customStartDate.value);
      params.append('end_date', customEndDate.value);
    }

    const res = await baseRequest.get(`admin/statistics?${params.toString()}`);
    if (res.data && res.data.status) {
      stats.value = res.data.data;
      canViewFinancial.value = res.data.can_view_financial ?? (res.data.data?.can_view_financial ?? true);
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

// Xuất file CSV báo cáo tài chính
const handleExportCsv = async () => {
  exportingReport.value = true;
  try {
    const params = new URLSearchParams();
    params.append('date_by', selectedDateBy.value);

    if (selectedPreset.value !== 'all' && customStartDate.value && customEndDate.value) {
      params.append('start_date', customStartDate.value);
      params.append('end_date', customEndDate.value);
    }

    const filename = `bao_cao_doanh_thu_${new Date().toISOString().slice(0, 10)}.csv`;
    await baseRequest.downloadFile(`admin/reports/export?${params.toString()}`, filename);
    toaster.success("Xuất báo cáo doanh thu thành công!");
  } catch (err) {
    if (err.response?.status === 403) {
      toaster.error("Bạn không có quyền xuất báo cáo doanh thu tài chính!");
    } else {
      toaster.error("Không thể kết nối đến máy chủ để tải báo cáo!");
    }
  } finally {
    exportingReport.value = false;
  }
};

// Điều hướng nhanh đến danh sách đơn
const navigateToBookings = (queryStr) => {
  router.push(`/admin/bookings?${queryStr}`);
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const getRentalStatusLabel = (status) => {
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
  if (Number(b.tinh_trang) === 2 && Number(b.is_thanh_toan) === 1) return 'Đã thu (Cần hoàn)';
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

onMounted(() => {
  applyPreset('30days');
});
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 0;
  color: #1e293b;
  font-family: inherit;
}

/* Card chuẩn */
.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* 1. Header Điều Hành */
.ops-header {
  padding: 24px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.header-left {
  max-width: 520px;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ecfdf5;
  color: #059669;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 8px;
  border: 1px solid #a7f3d0;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: #059669;
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
}

.header-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 6px 0;
}

.header-title span {
  color: #059669;
}

.header-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0;
}

.header-controls {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: flex-end;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.preset-buttons {
  display: flex;
  background: #f1f5f9;
  padding: 3px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.btn-preset {
  background: transparent;
  border: none;
  padding: 6px 12px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-preset:hover {
  color: #0f172a;
}

.btn-preset.active {
  background: #ffffff;
  color: #059669;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

.custom-dates {
  display: flex;
  align-items: center;
  gap: 6px;
}

.date-input {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  padding: 5px 8px;
  border-radius: 6px;
  font-size: 0.82rem;
  color: #1e293b;
}

.date-sep {
  color: #94a3b8;
  font-weight: bold;
}

.btn-apply-date {
  background: #059669;
  color: #ffffff;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.dimension-select {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-action-refresh,
.btn-action-export {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-action-refresh {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
}

.btn-action-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-action-export {
  background: #059669;
  border: 1px solid #059669;
  color: #ffffff;
}

.btn-action-export:hover:not(:disabled) {
  background: #047857;
}

.btn-action-refresh:disabled,
.btn-action-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Scope Disclosure */
.scope-notice {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  color: #475569;
  gap: 12px;
  flex-wrap: wrap;
}

.notice-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notice-left i {
  color: #059669;
  font-size: 1rem;
}

.notice-time {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
}

/* Error Banner */
.stats-error-banner {
  background: #fef2f2;
  border-color: #fecaca;
  padding: 14px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.error-msg {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #dc2626;
  font-size: 0.9rem;
  font-weight: 600;
}

.btn-retry {
  background: #dc2626;
  color: #ffffff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
}

/* 2. KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.kpi-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.kpi-card.pointer {
  cursor: pointer;
}

.kpi-card.pointer:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  border-color: #cbd5e1;
}

.kpi-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.kpi-icon-wrap.amber { background: #fffbeb; color: #d97706; }
.kpi-icon-wrap.emerald { background: #ecfdf5; color: #059669; }
.kpi-icon-wrap.sky { background: #f0f9ff; color: #0284c7; }
.kpi-icon-wrap.green { background: #f0fdf4; color: #16a34a; }
.kpi-icon-wrap.indigo { background: #eef2ff; color: #4f46e5; }

.kpi-body {
  flex: 1;
  min-width: 0;
}

.kpi-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.kpi-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.kpi-badge-reconciled {
  font-size: 0.72rem;
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
}

.kpi-value-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin: 4px 0;
}

.kpi-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
  margin: 0;
}

.kpi-value.highlight {
  color: #059669;
}

.kpi-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
}

.kpi-badge.urgent { background: #fee2e2; color: #dc2626; }
.kpi-badge.ok { background: #ecfdf5; color: #059669; }
.kpi-badge.info { background: #f0fdf4; color: #15803d; }
.kpi-badge.sky { background: #e0f2fe; color: #0369a1; }

.kpi-subtext {
  font-size: 0.8rem;
  color: #64748b;
  display: block;
  margin-top: 4px;
}

.kpi-finance-breakdown {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 6px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.deposit-pill {
  color: #0284c7;
}

/* 3. Action Center */
.action-center {
  padding: 24px;
}

.section-header {
  margin-bottom: 20px;
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-with-badge h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.text-amber { color: #f59e0b; }
.text-emerald { color: #059669; }

.badge-count-urgent {
  background: #ef4444;
  color: #ffffff;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.78rem;
  font-weight: 700;
}

.badge-count-ok {
  background: #ecfdf5;
  color: #059669;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.78rem;
  font-weight: 700;
  border: 1px solid #a7f3d0;
}

.section-sub {
  color: #64748b;
  font-size: 0.85rem;
  margin: 4px 0 0 0;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.action-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 18px;
  display: flex;
  flex-direction: column;
}

.action-card.overdue.has-alerts {
  background: #fff5f5;
  border-color: #fecaca;
}

.action-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.action-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 0.95rem;
  color: #1e293b;
}

.action-card.overdue.has-alerts .action-card-title {
  color: #b91c1c;
}

.action-count-pill {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 10px;
}

.action-count-pill.red { background: #fee2e2; color: #dc2626; }
.action-count-pill.amber { background: #fef3c7; color: #d97706; }
.action-count-pill.sky { background: #e0f2fe; color: #0284c7; }

.action-desc {
  font-size: 0.82rem;
  color: #64748b;
  margin: 0 0 14px 0;
  line-height: 1.4;
}

.overdue-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: auto;
}

.overdue-item {
  background: #ffffff;
  border: 1px solid #fca5a5;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 0.8rem;
  display: flex;
  flex-direction: column;
  gap: 3px;
  transition: all 0.15s ease;
}

.overdue-item:hover {
  border-color: #ef4444;
  background: #fef2f2;
}

.overdue-car-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.overdue-car-info strong {
  color: #0f172a;
}

.overdue-car-info .code {
  color: #ef4444;
  font-weight: 700;
  font-size: 0.75rem;
}

.overdue-customer {
  display: flex;
  justify-content: space-between;
  color: #475569;
  font-size: 0.78rem;
}

.phone-link {
  color: #0284c7;
  text-decoration: none;
  font-weight: 600;
}

.overdue-time-tag {
  color: #dc2626;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
}

.action-empty {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: #059669;
  font-weight: 600;
  padding: 10px 0;
}

.action-footer {
  margin-top: auto;
  padding-top: 10px;
}

.btn-action-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #059669;
  background: transparent;
  border: none;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0;
  cursor: pointer;
  text-decoration: none;
  transition: gap 0.15s ease;
}

.btn-action-link:hover {
  gap: 10px;
  color: #047857;
}

/* 4. Fleet Overview */
.fleet-overview {
  padding: 24px;
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-text {
  color: #059669;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.fleet-progress-track {
  height: 12px;
  background: #f1f5f9;
  border-radius: 8px;
  display: flex;
  overflow: hidden;
  margin: 16px 0 20px 0;
  border: 1px solid #e2e8f0;
}

.fleet-progress-track .segment {
  height: 100%;
  transition: width 0.3s ease;
}

.segment.available { background: #059669; }
.segment.renting { background: #0284c7; }
.segment.maintenance { background: #f59e0b; }

.fleet-legend-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.legend-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px 16px;
}

.legend-card.summary-box {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.legend-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot-emerald { background: #059669; }
.dot-sky { background: #0284c7; }
.dot-amber { background: #f59e0b; }
.dot-gray { background: #64748b; }

.legend-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
}

.legend-value-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.legend-count {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
}

.legend-unit {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
}

.legend-note {
  font-size: 0.75rem;
  color: #64748b;
  display: block;
  margin-top: 4px;
  line-height: 1.3;
}

/* 5. Charts & Recent Grid */
.charts-and-recent-grid {
  display: grid;
  grid-template-columns: 1fr 1.25fr;
  gap: 20px;
}

.chart-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
}

.chart-header {
  margin-bottom: 16px;
}

.chart-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.chart-title-wrap h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.granularity-tag {
  font-size: 0.72rem;
  font-weight: 700;
  background: #e0f2fe;
  color: #0284c7;
  padding: 2px 8px;
  border-radius: 12px;
}

.chart-sub {
  color: #64748b;
  font-size: 0.82rem;
  margin: 4px 0 0 0;
}

.chart-unit-badge {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 600;
}

.legacy-undated-banner {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  padding: 10px 14px;
  border-radius: 8px;
  display: flex;
  gap: 10px;
  font-size: 0.78rem;
  color: #475569;
  margin-bottom: 16px;
}

.legacy-undated-banner i {
  color: #f59e0b;
  font-size: 1rem;
}

.chart-container {
  height: 260px;
  position: relative;
  display: flex;
  align-items: flex-end;
  padding: 10px 0 0 0;
  margin-top: auto;
}

.bar-chart {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  gap: 8px;
}

.bar-wrapper {
  flex: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  min-width: 24px;
}

.bar-track {
  width: 100%;
  max-width: 38px;
  height: calc(100% - 24px);
  display: flex;
  align-items: flex-end;
  background: #f8fafc;
  border-radius: 6px 6px 0 0;
  position: relative;
}

.bar-fill {
  width: 100%;
  background: #059669;
  border-radius: 6px 6px 0 0;
  position: relative;
  transition: height 0.3s ease;
  min-height: 4px;
}

.bar-fill:hover {
  background: #047857;
}

.bar-tooltip {
  position: absolute;
  bottom: 105%;
  left: 50%;
  transform: translateX(-50%);
  background: #0f172a;
  color: #ffffff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s ease;
  z-index: 10;
}

.bar-wrapper:hover .bar-tooltip {
  opacity: 1;
}

.bar-label {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 600;
  margin-top: 6px;
  text-align: center;
  white-space: nowrap;
}

.empty-chart {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  gap: 8px;
  font-size: 0.85rem;
}

.empty-chart i {
  font-size: 2rem;
  opacity: 0.4;
}

/* Dispatcher Notice Card */
.dispatcher-notice-card {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.dispatcher-notice-content {
  max-width: 360px;
  padding: 20px;
}

.notice-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #ecfdf5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin: 0 auto 14px auto;
}

.dispatcher-notice-content h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.dispatcher-notice-content p {
  color: #64748b;
  font-size: 0.85rem;
  line-height: 1.5;
  margin: 0 0 16px 0;
}

.dispatcher-quick-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-dispatcher {
  background: #059669;
  color: #ffffff;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-dispatcher.secondary {
  background: #f1f5f9;
  color: #334155;
}

/* Recent Bookings Card */
.recent-bookings-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
}

.card-header {
  margin-bottom: 16px;
}

.card-header h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.table-responsive {
  overflow-x: auto;
}

.ops-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.ops-table th {
  text-align: left;
  padding: 10px 12px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #e2e8f0;
}

.ops-table td {
  padding: 12px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table-row:hover td {
  background: #f8fafc;
}

.code-badge {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 700;
  color: #059669;
  background: #ecfdf5;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.8rem;
}

.user-inline {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar-sm {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.user-details {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.user-details .name {
  font-weight: 600;
  color: #0f172a;
}

.user-details .phone {
  font-size: 0.72rem;
  color: #64748b;
}

.car-name-text {
  font-weight: 600;
  color: #334155;
}

.time-text {
  color: #64748b;
  font-size: 0.8rem;
}

.amount-text {
  color: #0f172a;
  font-weight: 700;
}

.status-pill {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

/* Trạng thái thuê */
.status-pill.rental-0 { background: #fef3c7; color: #b45309; }
.status-pill.rental-1 { background: #ecfdf5; color: #047857; }
.status-pill.rental-2 { background: #fee2e2; color: #b91c1c; }
.status-pill.rental-3 { background: #e0f2fe; color: #0369a1; }
.status-pill.rental-4 { background: #f3e8ff; color: #7e22ce; }
.status-pill.rental-5 { background: #dcfce7; color: #15803d; }

/* Trạng thái thanh toán */
.status-pill.payment.paid { background: #dcfce7; color: #166534; }
.status-pill.payment.pending { background: #f1f5f9; color: #64748b; }
.status-pill.payment.verifying { background: #fef3c7; color: #d97706; }
.status-pill.payment.refund { background: #fee2e2; color: #dc2626; }

.btn-row-action {
  background: transparent;
  border: 1px solid #cbd5e1;
  color: #475569;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-row-action:hover {
  background: #059669;
  border-color: #059669;
  color: #ffffff;
}

.empty-table {
  padding: 30px;
  text-align: center;
  color: #94a3b8;
}

.empty-table i {
  font-size: 2rem;
  margin-bottom: 8px;
  opacity: 0.4;
}

/* Responsive */
@media (max-width: 1200px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .fleet-legend-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .charts-and-recent-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .ops-header {
    flex-direction: column;
    align-items: stretch;
  }
  .header-controls {
    align-items: stretch;
  }
  .action-grid {
    grid-template-columns: 1fr;
  }
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .fleet-legend-grid {
    grid-template-columns: 1fr;
  }
}
</style>
