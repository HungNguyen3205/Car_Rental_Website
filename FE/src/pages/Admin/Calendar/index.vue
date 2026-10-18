<template>
  <div class="calendar-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-calendar-days"></i></div>
        <div>
          <h1 class="page-title">Lịch <span>Đội Xe</span> & Điều Phối</h1>
          <p class="page-subtitle">Theo dõi lịch thuê xe nửa mở [nhận, trả), kiểm tra tình trạng giữ lịch và bảo dưỡng thực tế.</p>
        </div>
      </div>
      <div class="header-actions">
        <router-link to="/admin/cars" class="btn-outline">
          <i class="fa-solid fa-car-side"></i> Quản lý kho xe
        </router-link>
        <button class="btn-primary" @click="fetchSchedule" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i> Làm mới
        </button>
      </div>
    </header>

    <!-- Quick Stats Summary -->
    <section class="summary-section">
      <div class="summary-card card">
        <div class="card-icon blue"><i class="fa-solid fa-car"></i></div>
        <div class="card-info">
          <span class="label">Tổng xe đội</span>
          <h3 class="value">{{ pagination.total_cars || cars.length }}</h3>
          <small class="sub-label">Trong bộ lọc hiện tại</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon green"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="card-info">
          <span class="label">Đơn giữ lịch</span>
          <h3 class="value">{{ stats.active_bookings_count || 0 }}</h3>
          <small class="sub-label">Chờ duyệt + Đã duyệt</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon indigo"><i class="fa-solid fa-road"></i></div>
        <div class="card-info">
          <span class="label">Đang lăn bánh</span>
          <h3 class="value">{{ stats.in_progress_count || 0 }}</h3>
          <small class="sub-label">Khách đang thuê xe</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="card-info">
          <span class="label">Đơn chờ duyệt</span>
          <h3 class="value text-warning">{{ stats.pending_count || 0 }}</h3>
          <small class="sub-label">Cần xử lý phê duyệt</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon gray"><i class="fa-solid fa-wrench"></i></div>
        <div class="card-info">
          <span class="label">Xe bảo dưỡng</span>
          <h3 class="value">{{ stats.maintenance_cars || 0 }}</h3>
          <small class="sub-label">Tạm khóa nhận lịch</small>
        </div>
      </div>
    </section>

    <!-- Toolbar: Date Navigation + View Mode + Filters -->
    <div class="content-card card controls-card">
      <div class="controls-row">
        <!-- Date Navigator -->
        <div class="date-navigator">
          <button class="nav-btn" @click="navigatePrev" title="Khoảng trước">
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          <button class="btn-today" @click="jumpToday" :class="{ 'is-active': isAnchorToday }">
            Hôm nay
          </button>
          <button class="nav-btn" @click="navigateNext" title="Khoảng tiếp theo">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
          
          <div class="current-period-display">
            <i class="fa-solid fa-calendar-week"></i>
            <span class="period-text">{{ currentPeriodLabel }}</span>
          </div>

          <div class="date-picker-input-wrap">
            <input 
              type="date" 
              class="date-picker-input" 
              :value="formatIsoDate(anchorDate)"
              @change="onDateInputChange"
              title="Chọn ngày cụ thể"
            />
          </div>
        </div>

        <!-- View Mode Switcher -->
        <div class="view-mode-tabs">
          <button 
            class="tab-btn" 
            :class="{ active: viewMode === 'week' }"
            @click="switchViewMode('week')"
            title="Chế độ xem theo tuần (Khuyên dùng cho đội xe)"
          >
            <i class="fa-solid fa-table-columns"></i>
            <span>Tuần</span>
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: viewMode === 'month' }"
            @click="switchViewMode('month')"
            title="Chế độ xem toàn tháng"
          >
            <i class="fa-solid fa-calendar-days"></i>
            <span>Tháng</span>
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: viewMode === 'day' }"
            @click="switchViewMode('day')"
            title="Chế độ xem lịch biểu chi tiết từng ngày (Tối ưu Mobile)"
          >
            <i class="fa-solid fa-list-ul"></i>
            <span>Lịch biểu</span>
          </button>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="filter-row">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input 
            type="text" 
            placeholder="Tìm theo tên xe, thành phố..." 
            v-model="searchQuery"
            @input="onSearchInput"
          />
        </div>

        <div class="filter-group">
          <div class="select-wrapper">
            <i class="fa-solid fa-layer-group"></i>
            <select v-model="filterCategory" @change="fetchSchedule" class="form-select">
              <option value="">Tất cả danh mục xe</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="select-wrapper">
            <i class="fa-solid fa-filter"></i>
            <select v-model="filterStatus" @change="fetchSchedule" class="form-select">
              <option value="all">Tất cả trạng thái lịch</option>
              <option value="pending">⏳ Chờ duyệt (Giữ lịch)</option>
              <option value="confirmed">📅 Đã duyệt (Sắp tới)</option>
              <option value="in_progress">🚗 Đang thuê (Lăn bánh)</option>
              <option value="completed">✓ Đã hoàn tất</option>
              <option value="maintenance">🔧 Xe đang bảo trì</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Semantic Legend Bar (Icons + Explicit Text, Not Just Color) -->
      <div class="semantic-legend-bar">
        <span class="legend-title"><i class="fa-solid fa-circle-info"></i> Chú giải:</span>
        <div class="legend-items">
          <div class="legend-item status-pending">
            <i class="fa-solid fa-hourglass-half"></i>
            <span>Chờ duyệt (Đang giữ lịch)</span>
          </div>
          <div class="legend-item status-confirmed">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Đã duyệt (Giữ lịch chắc chắn)</span>
          </div>
          <div class="legend-item status-in_progress">
            <i class="fa-solid fa-car-side"></i>
            <span>Đang thuê (Đang trên đường)</span>
          </div>
          <div class="legend-item status-completed">
            <i class="fa-solid fa-circle-check"></i>
            <span>Đã hoàn tất</span>
          </div>
          <div class="legend-item status-maintenance">
            <i class="fa-solid fa-wrench"></i>
            <span>Xe bảo dưỡng (Khóa lịch)</span>
          </div>
          <div class="legend-item status-available">
            <i class="fa-solid fa-circle-plus"></i>
            <span>Trống (Sẵn sàng nhận lịch)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-banner card">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <div class="error-content">
        <h4>Không thể tải dữ liệu lịch xe</h4>
        <p>{{ error }}</p>
      </div>
      <button class="btn-retry" @click="fetchSchedule">Thử lại</button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="card loading-card">
      <div class="skeleton-shimmer" style="height: 60px; margin-bottom: 12px; border-radius: 8px;"></div>
      <div class="skeleton-shimmer" style="height: 120px; margin-bottom: 12px; border-radius: 8px;"></div>
      <div class="skeleton-shimmer" style="height: 120px; border-radius: 8px;"></div>
    </div>

    <!-- MAIN VIEW: Grid Timeline (Tuần & Tháng) -->
    <div v-else-if="viewMode === 'week' || viewMode === 'month'" class="content-card card calendar-grid-card">
      <div class="timeline-table-wrapper">
        <table class="timeline-table">
          <thead>
            <tr>
              <th class="car-column-header">
                <div class="car-header-wrap">
                  <i class="fa-solid fa-car"></i>
                  <span>Đội xe hệ thống ({{ cars.length }})</span>
                </div>
              </th>
              <th 
                v-for="day in currentDays" 
                :key="day.iso" 
                class="day-column-header"
                :class="{ 'is-today': day.isToday, 'is-weekend': day.isWeekend }"
              >
                <div class="day-header-content">
                  <span class="day-name">{{ day.dayName }}</span>
                  <span class="day-num">{{ day.dayNum }}</span>
                  <span v-if="day.isToday" class="today-badge">Hôm nay</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cars.length === 0">
              <td :colspan="currentDays.length + 1" class="empty-state-cell">
                <i class="fa-solid fa-car-tunnel"></i>
                <p>Không tìm thấy xe nào phù hợp với bộ lọc.</p>
              </td>
            </tr>
            <tr v-for="car in cars" :key="car.id" class="car-timeline-row">
              <!-- Left Sticky Car Info Cell -->
              <td class="car-cell">
                <div class="car-summary-box">
                  <div class="car-thumb-wrap">
                    <img 
                      :src="getCarImg(car)" 
                      :alt="car.name" 
                      class="car-thumb"
                      @error="onImgError" 
                    />
                  </div>
                  <div class="car-meta">
                    <span class="car-name" :title="car.name">{{ car.name }}</span>
                    <div class="car-sub-meta">
                      <span class="car-category-tag" v-if="car.category_name">{{ car.category_name }}</span>
                      <span class="car-price">{{ formatCurrency(car.price_per_day) }}/ngày</span>
                    </div>
                    <span v-if="car.is_maintenance" class="car-badge-maintenance" title="Xe đang bảo trì tổng quát">
                      <i class="fa-solid fa-wrench"></i> Bảo dưỡng
                    </span>
                  </div>
                </div>
              </td>

              <!-- If Car is in Maintenance: Render hatched full-width row -->
              <td 
                v-if="car.is_maintenance" 
                :colspan="currentDays.length" 
                class="maintenance-full-cell"
              >
                <div class="maintenance-ribbon">
                  <i class="fa-solid fa-wrench"></i>
                  <span><strong>Xe bảo dưỡng tổng quát:</strong> Tạm khóa nhận lịch thuê trong thời gian kỹ thuật kiểm tra xe.</span>
                </div>
              </td>

              <!-- Day Timeline Cells -->
              <template v-else>
                <td 
                  v-for="day in currentDays" 
                  :key="day.iso" 
                  class="day-cell"
                  :class="{ 'is-today': day.isToday, 'is-weekend': day.isWeekend }"
                >
                  <!-- Bookings intersecting this day -->
                  <div class="cell-bookings-wrap">
                    <div 
                      v-for="booking in getBookingsForDay(car, day)" 
                      :key="booking.id"
                      class="booking-pill"
                      :class="['pill-' + booking.status_key, { 'pill-span-start': isBookingStartDay(booking, day), 'pill-span-end': isBookingEndDay(booking, day) }]"
                      @click="openBookingModal(booking, car)"
                      :title="getBookingTooltip(booking, car)"
                    >
                      <i :class="booking.status_icon"></i>
                      <span class="pill-code">#{{ booking.ma_dat_xe }}</span>
                      <span class="pill-name">{{ booking.user_name || 'Khách' }}</span>
                      <span class="pill-time">({{ formatShortTime(booking.start_date) }} - {{ formatShortTime(booking.end_date) }})</span>
                    </div>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="pagination-bar" v-if="pagination.total_cars > pagination.per_page">
        <span class="pagination-info">
          Hiển thị trang {{ pagination.current_page }} / {{ pagination.last_page }} (Tổng cộng {{ pagination.total_cars }} xe)
        </span>
        <div class="pagination-actions">
          <button 
            class="btn-page" 
            :disabled="pagination.current_page <= 1"
            @click="changePage(pagination.current_page - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i> Trước
          </button>
          <button 
            class="btn-page" 
            :disabled="pagination.current_page >= pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
          >
            Sau <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- MOBILE / AGENDA VIEW: Lịch biểu theo ngày -->
    <div v-else class="content-card card agenda-card">
      <div class="agenda-day-selector">
        <div class="agenda-scroll-wrap">
          <button 
            v-for="day in currentDays" 
            :key="day.iso"
            class="agenda-day-pill"
            :class="{ active: selectedDayIso === day.iso, 'is-today': day.isToday }"
            @click="selectedDayIso = day.iso"
          >
            <span class="pill-day-name">{{ day.dayName }}</span>
            <span class="pill-day-num">{{ day.dayNum }}</span>
            <span v-if="day.isToday" class="pill-today-dot"></span>
          </button>
        </div>
      </div>

      <div class="agenda-header-meta">
        <h3><i class="fa-solid fa-calendar-day"></i> Lịch xe ngày {{ formatDisplayDate(selectedDayIso) }}</h3>
        <span class="agenda-count">{{ getAgendaDayBookingsCount }} đơn hoạt động</span>
      </div>

      <div class="agenda-cars-list">
        <div v-for="car in cars" :key="car.id" class="agenda-car-item">
          <div class="agenda-car-header">
            <img :src="getCarImg(car)" :alt="car.name" class="agenda-car-thumb" @error="onImgError" />
            <div class="agenda-car-info">
              <h4>{{ car.name }}</h4>
              <span class="agenda-car-price">{{ formatCurrency(car.price_per_day) }}/ngày</span>
            </div>
            <span v-if="car.is_maintenance" class="badge-status maintenance">
              <i class="fa-solid fa-wrench"></i> Bảo dưỡng
            </span>
          </div>

          <!-- Car Maintenance Alert -->
          <div v-if="car.is_maintenance" class="agenda-maintenance-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Xe đang ở chế độ bảo dưỡng tổng quát, không nhận lịch thuê.</span>
          </div>

          <!-- Car Bookings for this Day -->
          <div v-else-if="getBookingsForDayIso(car, selectedDayIso).length > 0" class="agenda-bookings-container">
            <div 
              v-for="b in getBookingsForDayIso(car, selectedDayIso)" 
              :key="b.id"
              class="agenda-booking-card"
              :class="'status-' + b.status_key"
              @click="openBookingModal(b, car)"
            >
              <div class="card-top">
                <span class="badge-status" :class="b.status_key">
                  <i :class="b.status_icon"></i> {{ b.status_label }}
                </span>
                <span class="booking-code">#{{ b.ma_dat_xe }}</span>
              </div>
              <div class="card-details">
                <div class="detail-line">
                  <i class="fa-solid fa-user"></i>
                  <span>{{ b.user_name || 'Khách vãng lai' }} ({{ b.user_phone || 'Chưa có SĐT' }})</span>
                </div>
                <div class="detail-line">
                  <i class="fa-solid fa-clock"></i>
                  <span>{{ formatDateTime(b.start_date) }} → {{ formatDateTime(b.end_date) }}</span>
                </div>
                <div class="detail-line">
                  <i class="fa-solid fa-sack-dollar"></i>
                  <span class="price-val">{{ formatCurrency(b.tong_tien) }}</span>
                  <span class="payment-badge" :class="b.is_thanh_toan === 1 ? 'paid' : 'unpaid'">
                    {{ b.is_thanh_toan === 1 ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                  </span>
                </div>
              </div>
              <button class="btn-view-card">Xem chi tiết đơn <i class="fa-solid fa-arrow-right"></i></button>
            </div>
          </div>

          <!-- Empty Car Day Slot -->
          <div v-else class="agenda-empty-slot">
            <i class="fa-solid fa-circle-check"></i>
            <span>Xe trống trong ngày này, sẵn sàng cho khách thuê.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL: Xem chi tiết đơn khi click -->
    <div v-if="selectedBooking" class="modal-overlay" @click.self="selectedBooking = null">
      <div class="modal-content card modal-detail animate-in">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <div class="modal-icon-badge" :class="'badge-' + selectedBooking.status_key">
              <i :class="selectedBooking.status_icon"></i>
            </div>
            <div>
              <h3>Chi tiết đơn #{{ selectedBooking.ma_dat_xe }}</h3>
              <p class="modal-sub">Trạng thái: <strong>{{ selectedBooking.status_label }}</strong></p>
            </div>
          </div>
          <button class="btn-close" @click="selectedBooking = null" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <!-- Car Block -->
          <div class="modal-car-card">
            <img :src="getCarImg(selectedBookingCar)" :alt="selectedBookingCar?.name" class="modal-car-img" @error="onImgError" />
            <div>
              <h4>{{ selectedBookingCar?.name }}</h4>
              <p>{{ selectedBookingCar?.category_name || 'Xe hệ thống' }} • {{ selectedBookingCar?.city }}</p>
              <span class="modal-price">{{ formatCurrency(selectedBookingCar?.price_per_day) }}/ngày</span>
            </div>
          </div>

          <!-- Schedule Range Info -->
          <div class="info-group">
            <span class="info-title"><i class="fa-solid fa-calendar-range"></i> Lịch trình thuê xe nửa mở [nhận, trả):</span>
            <div class="schedule-box">
              <div class="schedule-point">
                <span class="point-label">Nhận xe:</span>
                <strong>{{ formatDateTime(selectedBooking.start_date) }}</strong>
                <span class="point-addr" v-if="selectedBooking.dia_chi_nhan_xe">
                  <i class="fa-solid fa-location-dot"></i> {{ selectedBooking.dia_chi_nhan_xe }}
                </span>
              </div>
              <div class="schedule-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
              <div class="schedule-point">
                <span class="point-label">Trả xe:</span>
                <strong>{{ formatDateTime(selectedBooking.end_date) }}</strong>
                <span class="point-addr" v-if="selectedBooking.dia_chi_tra_xe">
                  <i class="fa-solid fa-location-dot"></i> {{ selectedBooking.dia_chi_tra_xe }}
                </span>
              </div>
            </div>
          </div>

          <!-- Customer & Finance Info -->
          <div class="info-grid">
            <div class="info-item">
              <span class="item-label">Khách hàng:</span>
              <strong>{{ selectedBooking.user_name || 'Khách vãng lai' }}</strong>
            </div>
            <div class="info-item">
              <span class="item-label">Số điện thoại:</span>
              <strong>{{ selectedBooking.user_phone || 'Chưa cung cấp' }}</strong>
            </div>
            <div class="info-item">
              <span class="item-label">Email liên hệ:</span>
              <strong>{{ selectedBooking.user_email || 'Chưa cung cấp' }}</strong>
            </div>
            <div class="info-item">
              <span class="item-label">Tổng số tiền:</span>
              <strong class="text-success">{{ formatCurrency(selectedBooking.tong_tien) }}</strong>
            </div>
            <div class="info-item">
              <span class="item-label">Thanh toán:</span>
              <span class="status-tag" :class="selectedBooking.is_thanh_toan === 1 ? 'paid' : 'pending'">
                {{ selectedBooking.is_thanh_toan === 1 ? 'Đã thanh toán' : 'Chưa thanh toán' }}
              </span>
            </div>
            <div class="info-item">
              <span class="item-label">Giữ lịch xe:</span>
              <span class="status-tag" :class="selectedBooking.holds_schedule ? 'paid' : 'refund'">
                {{ selectedBooking.holds_schedule ? 'Có giữ lịch' : 'Không giữ lịch (Đã hủy)' }}
              </span>
            </div>
          </div>

          <div v-if="selectedBooking.ly_do_thue" class="notes-box">
            <span class="item-label">Ghi chú chuyến đi:</span>
            <p>{{ selectedBooking.ly_do_thue }}</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="selectedBooking = null">Đóng</button>
          <button class="btn-primary" @click="goToBookingsManagement(selectedBooking)">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Quản lý đơn này
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { resolveCarImage, handleCarImgError } from '../../../core/carImageHelper';

const router = useRouter();

// View State
const viewMode = ref('week'); // 'week' | 'month' | 'day'
const anchorDate = ref(new Date());
const selectedDayIso = ref('');
const loading = ref(false);
const error = ref(null);

// Data
const cars = ref([]);
const categories = ref([]);
const stats = ref({
  active_bookings_count: 0,
  in_progress_count: 0,
  pending_count: 0,
  maintenance_cars: 0,
});
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total_cars: 0,
});

// Filters
const searchQuery = ref('');
const filterCategory = ref('');
const filterStatus = ref('all');
let searchDebounceTimer = null;

// Modal
const selectedBooking = ref(null);
const selectedBookingCar = ref(null);

// ==================== DATE COMPUTATIONS ====================

const isAnchorToday = computed(() => {
  const today = new Date();
  return anchorDate.value.toDateString() === today.toDateString();
});

const getStartOfWeek = (d) => {
  const date = new Date(d);
  const day = date.getDay();
  // Quy ước Thứ Hai là ngày bắt đầu tuần (1 = T2, 0 = CN)
  const diff = date.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(date.setDate(diff));
};

const currentDays = computed(() => {
  const days = [];
  const todayIso = formatIsoDate(new Date());

  if (viewMode.value === 'week') {
    const monday = getStartOfWeek(anchorDate.value);
    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(d.getDate() + i);
      const iso = formatIsoDate(d);
      days.push({
        date: d,
        iso,
        dayNum: d.getDate(),
        dayName: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][d.getDay()],
        isToday: iso === todayIso,
        isWeekend: d.getDay() === 0 || d.getDay() === 6,
      });
    }
  } else if (viewMode.value === 'month') {
    const year = anchorDate.value.getFullYear();
    const month = anchorDate.value.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    for (let i = 1; i <= daysInMonth; i++) {
      const d = new Date(year, month, i);
      const iso = formatIsoDate(d);
      days.push({
        date: d,
        iso,
        dayNum: i,
        dayName: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][d.getDay()],
        isToday: iso === todayIso,
        isWeekend: d.getDay() === 0 || d.getDay() === 6,
      });
    }
  } else {
    // Day view
    const d = new Date(anchorDate.value);
    const iso = formatIsoDate(d);
    days.push({
      date: d,
      iso,
      dayNum: d.getDate(),
      dayName: ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'][d.getDay()],
      isToday: iso === todayIso,
      isWeekend: d.getDay() === 0 || d.getDay() === 6,
    });
  }

  return days;
});

const currentPeriodLabel = computed(() => {
  if (viewMode.value === 'week') {
    const days = currentDays.value;
    if (days.length === 0) return '';
    const first = days[0].date;
    const last = days[days.length - 1].date;
    return `Tuần ${first.getDate()}/${first.getMonth() + 1} - ${last.getDate()}/${last.getMonth() + 1}/${last.getFullYear()}`;
  } else if (viewMode.value === 'month') {
    return `Tháng ${anchorDate.value.getMonth() + 1}, ${anchorDate.value.getFullYear()}`;
  } else {
    const d = anchorDate.value;
    const dayName = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'][d.getDay()];
    return `${dayName}, ${d.getDate()} Tháng ${d.getMonth() + 1}, ${d.getFullYear()}`;
  }
});

const getAgendaDayBookingsCount = computed(() => {
  let count = 0;
  for (const car of cars.value) {
    count += getBookingsForDayIso(car, selectedDayIso.value).length;
  }
  return count;
});

// ==================== API FETCHING ====================

const fetchSchedule = async () => {
  loading.value = true;
  error.value = null;

  try {
    const days = currentDays.value;
    if (days.length === 0) return;

    const startDate = days[0].iso;
    const endDate = days[days.length - 1].iso;

    const params = new URLSearchParams({
      start_date: startDate,
      end_date: endDate,
      page: String(pagination.value.current_page),
      per_page: String(pagination.value.per_page),
    });

    if (searchQuery.value.trim()) {
      params.append('search', searchQuery.value.trim());
    }
    if (filterCategory.value) {
      params.append('category_id', String(filterCategory.value));
    }
    if (filterStatus.value && filterStatus.value !== 'all') {
      params.append('status', filterStatus.value);
    }

    const res = await baseRequest.get(`admin/calendar/schedule?${params.toString()}`);
    if (res.data && res.data.status === 1) {
      cars.value = res.data.data.cars || [];
      pagination.value = res.data.data.pagination || pagination.value;
      stats.value = res.data.data.stats || stats.value;
      categories.value = res.data.data.categories || categories.value;

      if (!selectedDayIso.value && days.length > 0) {
        selectedDayIso.value = days[0].iso;
      }
    } else {
      error.value = res.data?.message || 'Không thể tải dữ liệu lịch xe.';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Lỗi kết nối máy chủ khi lấy dữ liệu lịch xe.';
    console.error('Lỗi tải lịch xe:', err);
  } finally {
    loading.value = false;
  }
};

// ==================== INTERACTION METHODS ====================

const switchViewMode = (mode) => {
  viewMode.value = mode;
  fetchSchedule();
};

const navigatePrev = () => {
  const d = new Date(anchorDate.value);
  if (viewMode.value === 'week') {
    d.setDate(d.getDate() - 7);
  } else if (viewMode.value === 'month') {
    d.setMonth(d.getMonth() - 1);
  } else {
    d.setDate(d.getDate() - 1);
  }
  anchorDate.value = d;
  fetchSchedule();
};

const navigateNext = () => {
  const d = new Date(anchorDate.value);
  if (viewMode.value === 'week') {
    d.setDate(d.getDate() + 7);
  } else if (viewMode.value === 'month') {
    d.setMonth(d.getMonth() + 1);
  } else {
    d.setDate(d.getDate() + 1);
  }
  anchorDate.value = d;
  fetchSchedule();
};

const jumpToday = () => {
  anchorDate.value = new Date();
  selectedDayIso.value = formatIsoDate(new Date());
  fetchSchedule();
};

const onDateInputChange = (e) => {
  if (e.target.value) {
    anchorDate.value = new Date(e.target.value + 'T00:00:00');
    selectedDayIso.value = e.target.value;
    fetchSchedule();
  }
};

const onSearchInput = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    pagination.value.current_page = 1;
    fetchSchedule();
  }, 350);
};

const changePage = (page) => {
  pagination.value.current_page = page;
  fetchSchedule();
};

// ==================== BOOKING GRID HELPERS ====================

/**
 * Lấy các booking giao thoa với ngày cụ thể [day 00:00:00, day 23:59:59]
 * Quy ước nửa mở: booking.start_date < dayEnd AND booking.end_date > dayStart
 */
const getBookingsForDay = (car, day) => {
  if (!car || !car.bookings) return [];
  const dayStartStr = `${day.iso} 00:00:00`;
  const dayEndStr = `${day.iso} 23:59:59`;

  return car.bookings.filter(b => {
    return b.start_date < dayEndStr && b.end_date > dayStartStr;
  });
};

const getBookingsForDayIso = (car, iso) => {
  if (!car || !car.bookings || !iso) return [];
  const dayStartStr = `${iso} 00:00:00`;
  const dayEndStr = `${iso} 23:59:59`;

  return car.bookings.filter(b => {
    return b.start_date < dayEndStr && b.end_date > dayStartStr;
  });
};

const isBookingStartDay = (booking, day) => {
  if (!booking?.start_date || !day?.iso) return false;
  return booking.start_date.startsWith(day.iso);
};

const isBookingEndDay = (booking, day) => {
  if (!booking?.end_date || !day?.iso) return false;
  return booking.end_date.startsWith(day.iso);
};

const getBookingTooltip = (booking, car) => {
  return `Mã đơn: #${booking.ma_dat_xe}\nXe: ${car.name}\nKhách: ${booking.user_name || 'Khách'}\nTrạng thái: ${booking.status_label}\nThời gian: ${formatDateTime(booking.start_date)} - ${formatDateTime(booking.end_date)}`;
};

const openBookingModal = (booking, car) => {
  selectedBooking.value = booking;
  selectedBookingCar.value = car;
};

const goToBookingsManagement = (booking) => {
  selectedBooking.value = null;
  router.push('/admin/bookings');
};

// ==================== FORMATTERS ====================

const formatIsoDate = (d) => {
  const date = new Date(d);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatDisplayDate = (isoStr) => {
  if (!isoStr) return '';
  const [year, month, day] = isoStr.split('-');
  return `${day}/${month}/${year}`;
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const pad = (n) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const formatShortTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const getCarImg = (car) => {
  return resolveCarImage(car);
};

const onImgError = (e) => {
  handleCarImgError(e);
};

onMounted(() => {
  selectedDayIso.value = formatIsoDate(new Date());
  fetchSchedule();
});
</script>

<style scoped>
.calendar-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 18px;
}

.header-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  border: 1px solid var(--color-primary-border);
}

.page-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: var(--color-text-main);
  letter-spacing: -0.5px;
}

.page-title span {
  color: var(--color-primary);
}

.page-subtitle {
  color: var(--color-text-muted);
  font-size: 0.92rem;
  margin-top: 3px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Summary Statistics */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.summary-card {
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-xs);
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.card-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.card-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.card-icon.indigo { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.card-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.card-icon.gray { background: rgba(100, 116, 139, 0.1); color: #64748b; }

.card-info {
  display: flex;
  flex-direction: column;
}

.card-info .label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card-info .value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-main);
  line-height: 1.2;
  margin: 2px 0;
}

.card-info .sub-label {
  font-size: 0.75rem;
  color: var(--color-text-subtle);
}

/* Controls Card */
.controls-card {
  padding: 20px 24px;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.controls-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.date-navigator {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--bg-surface);
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-btn:hover {
  background: var(--bg-surface-sub);
  border-color: var(--color-primary);
}

.btn-today {
  padding: 7px 16px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--bg-surface);
  color: var(--color-text-main);
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-today:hover, .btn-today.is-active {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.current-period-display {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 14px;
  background: var(--bg-surface-subtle);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-weight: 700;
  color: var(--color-text-main);
  font-size: 0.92rem;
}

.current-period-display i {
  color: var(--color-primary);
}

.date-picker-input-wrap {
  position: relative;
}

.date-picker-input {
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.82rem;
  color: var(--color-text-muted);
  outline: none;
  cursor: pointer;
}

.date-picker-input:focus {
  border-color: var(--color-primary);
}

/* View Mode Tabs */
.view-mode-tabs {
  display: flex;
  align-items: center;
  background: var(--bg-surface-subtle);
  padding: 4px;
  border-radius: 10px;
  border: 1px solid var(--color-border);
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  font-size: 0.85rem;
  font-weight: 700;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn.active {
  background: white;
  color: var(--color-primary);
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* Filters Row */
.filter-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding-top: 14px;
  border-top: 1px solid var(--color-border-subtle);
}

.search-input {
  position: relative;
  flex: 1;
  min-width: 260px;
}

.search-input i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  font-size: 0.88rem;
}

.search-input input {
  width: 100%;
  padding: 9px 14px 9px 38px;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
  background: var(--bg-surface-subtle);
}

.search-input input:focus {
  background: white;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.select-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.select-wrapper i {
  position: absolute;
  left: 12px;
  color: var(--color-text-muted);
  font-size: 0.82rem;
  pointer-events: none;
}

.form-select {
  padding: 9px 14px 9px 34px;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-main);
  background: var(--bg-surface-subtle);
  outline: none;
  cursor: pointer;
  transition: all 0.2s;
}

.form-select:focus {
  border-color: var(--color-primary);
  background: white;
}

/* Semantic Legend Bar */
.semantic-legend-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-top: 12px;
  border-top: 1px dashed var(--color-border-subtle);
  font-size: 0.82rem;
  flex-wrap: wrap;
}

.legend-title {
  font-weight: 700;
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-title i {
  color: #3b82f6;
}

.legend-items {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  color: var(--color-text-muted);
}

.legend-item.status-pending i { color: #f59e0b; }
.legend-item.status-confirmed i { color: #10b981; }
.legend-item.status-in_progress i { color: #6366f1; }
.legend-item.status-completed i { color: #64748b; }
.legend-item.status-maintenance i { color: #ef4444; }
.legend-item.status-available i { color: #94a3b8; }

/* Timeline Table Grid */
.calendar-grid-card {
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  overflow: hidden;
  box-shadow: var(--shadow-soft);
}

.timeline-table-wrapper {
  overflow-x: auto;
  position: relative;
  max-width: 100%;
}

.timeline-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
  table-layout: fixed;
}

/* Headers */
.timeline-table th {
  padding: 12px;
  background: var(--bg-surface-subtle);
  border-bottom: 2px solid var(--color-border);
  border-right: 1px solid var(--color-border-subtle);
  text-align: center;
}

.car-column-header {
  width: 250px;
  text-align: left !important;
  position: sticky;
  left: 0;
  z-index: 10;
  background: var(--bg-surface-subtle) !important;
}

.car-header-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  color: var(--color-text-main);
  font-size: 0.88rem;
}

.day-column-header {
  vertical-align: middle;
}

.day-column-header.is-today {
  background: #ecfdf5 !important;
  border-bottom-color: var(--color-primary);
}

.day-column-header.is-weekend {
  background: #f8fafc;
}

.day-header-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.day-name {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
}

.day-num {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.is-today .day-num {
  color: var(--color-primary);
}

.today-badge {
  font-size: 0.65rem;
  padding: 1px 6px;
  border-radius: 4px;
  background: var(--color-primary);
  color: white;
  font-weight: 700;
}

/* Body Rows */
.car-timeline-row {
  border-bottom: 1px solid var(--color-border);
}

.car-timeline-row:hover {
  background: rgba(0, 0, 0, 0.01);
}

/* Left Sticky Car Cell */
.car-cell {
  position: sticky;
  left: 0;
  z-index: 5;
  background: white;
  padding: 14px 16px;
  border-right: 2px solid var(--color-border);
  box-shadow: 2px 0 6px rgba(0, 0, 0, 0.02);
}

.car-summary-box {
  display: flex;
  align-items: center;
  gap: 12px;
}

.car-thumb-wrap {
  width: 52px;
  height: 38px;
  border-radius: 6px;
  overflow: hidden;
  background: #f1f5f9;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.car-meta {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.car-name {
  font-weight: 800;
  color: var(--color-text-main);
  font-size: 0.88rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.car-sub-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.car-category-tag {
  font-size: 0.68rem;
  padding: 1px 6px;
  border-radius: 4px;
  background: #e2e8f0;
  color: #475569;
  font-weight: 600;
}

.car-price {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-primary);
}

.car-badge-maintenance {
  font-size: 0.65rem;
  color: #dc2626;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

/* Maintenance Full Row Cell */
.maintenance-full-cell {
  padding: 12px 18px;
  background: repeating-linear-gradient(
    -45deg,
    #fffbeb,
    #fffbeb 10px,
    #fef3c7 10px,
    #fef3c7 20px
  );
  border-right: 1px solid var(--color-border-subtle);
}

.maintenance-ribbon {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #92400e;
  font-size: 0.85rem;
}

.maintenance-ribbon i {
  font-size: 1.1rem;
  color: #d97706;
}

/* Day Timeline Cell */
.day-cell {
  padding: 8px 6px;
  border-right: 1px solid var(--color-border-subtle);
  vertical-align: top;
  height: 80px;
  position: relative;
}

.day-cell.is-today {
  background: rgba(16, 185, 129, 0.03);
}

.cell-bookings-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Booking Pill */
.booking-pill {
  padding: 5px 8px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  box-shadow: 0 1px 2px rgba(0,0,0,0.06);
  transition: all 0.2s;
  border: 1px solid transparent;
}

.booking-pill:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(0,0,0,0.12);
  filter: brightness(0.95);
}

.booking-pill.pill-pending {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.booking-pill.pill-confirmed {
  background: #dcfce7;
  color: #14532d;
  border-color: #bbf7d0;
}

.booking-pill.pill-in_progress {
  background: #e0e7ff;
  color: #3730a3;
  border-color: #c7d2fe;
}

.booking-pill.pill-completed {
  background: #f1f5f9;
  color: #475569;
  border-color: #e2e8f0;
}

.booking-pill.pill-cancelled {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fecaca;
  text-decoration: line-through;
  opacity: 0.7;
}

.pill-code {
  font-family: monospace;
}

.pill-name {
  font-weight: 600;
}

.pill-time {
  font-size: 0.65rem;
  opacity: 0.85;
}

.empty-state-cell {
  text-align: center;
  padding: 48px;
  color: var(--color-text-muted);
}

.empty-state-cell i {
  font-size: 2.5rem;
  margin-bottom: 8px;
  opacity: 0.4;
}

/* Pagination Bar */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-top: 1px solid var(--color-border);
  background: var(--bg-surface-subtle);
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.pagination-actions {
  display: flex;
  gap: 8px;
}

.btn-page {
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: white;
  color: var(--color-text-main);
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
  background: var(--color-primary-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Agenda View */
.agenda-card {
  padding: 20px;
}

.agenda-day-selector {
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 16px;
  margin-bottom: 20px;
}

.agenda-scroll-wrap {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.agenda-day-pill {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 18px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-subtle);
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  min-width: 68px;
  position: relative;
}

.agenda-day-pill.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}

.pill-day-name {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.pill-day-num {
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1.2;
}

.pill-today-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
  margin-top: 2px;
}

.agenda-header-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.agenda-header-meta h3 {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  gap: 8px;
}

.agenda-count {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.agenda-cars-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.agenda-car-item {
  padding: 16px;
  background: var(--bg-surface-subtle);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.agenda-car-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.agenda-car-thumb {
  width: 60px;
  height: 42px;
  object-fit: cover;
  border-radius: 8px;
  background: white;
}

.agenda-car-info {
  flex: 1;
}

.agenda-car-info h4 {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.agenda-car-price {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-primary);
}

.agenda-maintenance-box {
  padding: 12px 16px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #f59e0b;
  border-radius: 8px;
  color: #92400e;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.agenda-empty-slot {
  padding: 12px 16px;
  background: #f0fdf4;
  border: 1px dashed #bbf7d0;
  border-radius: 8px;
  color: #15803d;
  font-size: 0.85rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.agenda-bookings-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.agenda-booking-card {
  padding: 14px;
  background: white;
  border-radius: 10px;
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: all 0.2s;
}

.agenda-booking-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border-color: var(--color-primary);
}

.agenda-booking-card .card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.booking-code {
  font-family: monospace;
  font-weight: 800;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--color-text-main);
}

.detail-line {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-line i {
  color: var(--color-text-muted);
  font-size: 0.8rem;
  width: 14px;
}

.btn-view-card {
  margin-top: 10px;
  padding: 6px 12px;
  background: var(--bg-surface-sub);
  border: none;
  border-radius: 6px;
  color: var(--color-primary);
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Badges */
.badge-status {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.badge-status.pending { background: #fef3c7; color: #92400e; }
.badge-status.confirmed { background: #dcfce7; color: #15803d; }
.badge-status.in_progress { background: #e0e7ff; color: #3730a3; }
.badge-status.completed { background: #f1f5f9; color: #475569; }
.badge-status.cancelled { background: #fee2e2; color: #991b1b; }
.badge-status.maintenance { background: #fee2e2; color: #b91c1c; }

.payment-badge {
  font-size: 0.72rem;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 700;
}

.payment-badge.paid { background: #dcfce7; color: #15803d; }
.payment-badge.unpaid { background: #fee2e2; color: #b91c1c; }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(11, 18, 32, 0.65);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-detail {
  width: 100%;
  max-width: 580px;
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: var(--shadow-premium);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 14px;
}

.modal-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.modal-icon-badge.badge-pending { background: #fef3c7; color: #92400e; }
.modal-icon-badge.badge-confirmed { background: #dcfce7; color: #15803d; }
.modal-icon-badge.badge-in_progress { background: #e0e7ff; color: #3730a3; }
.modal-icon-badge.badge-completed { background: #f1f5f9; color: #475569; }
.modal-icon-badge.badge-cancelled { background: #fee2e2; color: #991b1b; }

.modal-title-wrap h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.modal-sub {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.modal-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  max-height: 70vh;
  overflow-y: auto;
}

.modal-car-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px;
  background: var(--bg-surface-subtle);
  border-radius: 12px;
  border: 1px solid var(--color-border);
}

.modal-car-img {
  width: 90px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  background: white;
}

.modal-car-card h4 {
  font-size: 1.05rem;
  font-weight: 800;
}

.modal-car-card p {
  font-size: 0.82rem;
  color: var(--color-text-muted);
}

.modal-price {
  font-weight: 800;
  color: var(--color-primary);
  font-size: 0.9rem;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  gap: 8px;
}

.schedule-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: var(--bg-surface-subtle);
  border-radius: 12px;
  border: 1px solid var(--color-border);
  gap: 12px;
}

.schedule-point {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
}

.point-label {
  font-size: 0.72rem;
  color: var(--color-text-muted);
  text-transform: uppercase;
  font-weight: 700;
}

.point-addr {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.schedule-arrow {
  color: var(--color-primary);
  font-size: 1.2rem;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 16px;
  background: var(--bg-surface-subtle);
  border-radius: 12px;
  border: 1px solid var(--color-border);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-label {
  font-size: 0.72rem;
  color: var(--color-text-muted);
  font-weight: 700;
  text-transform: uppercase;
}

.notes-box {
  padding: 12px 14px;
  background: #f8fafc;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  font-size: 0.85rem;
}

.notes-box p {
  color: var(--color-text-main);
  margin-top: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
  background: var(--bg-surface-subtle);
}

/* Common UI */
.btn-primary {
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 9px 18px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  filter: brightness(1.1);
  transform: translateY(-1px);
}

.btn-outline {
  background: white;
  color: var(--color-text-main);
  border: 1px solid var(--color-border);
  padding: 9px 18px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.error-banner {
  padding: 16px 20px;
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  gap: 14px;
  color: #b91c1c;
}

.error-content h4 {
  font-weight: 800;
}

.btn-retry {
  margin-left: auto;
  padding: 6px 14px;
  background: white;
  border: 1px solid #f87171;
  color: #b91c1c;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}

@media (max-width: 992px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .controls-row {
    flex-direction: column;
    align-items: flex-start;
  }
  .filter-row {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
