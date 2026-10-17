<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Đơn Đặt Xe</span></h1>
          <p class="page-subtitle">Duyệt yêu cầu thuê xe, kiểm tra biên lai và xác nhận thanh toán minh bạch.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-outline" @click="fetchBookings" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i> Làm mới dữ liệu
        </button>
      </div>
    </header>

    <!-- Summary Statistics -->
    <section class="summary-section">
      <div class="summary-card card">
        <div class="card-icon blue"><i class="fa-solid fa-list-check"></i></div>
        <div class="card-info">
          <span class="label">Tổng đơn nhận</span>
          <h3 class="value">{{ bookings.length }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="card-info">
          <span class="label">Chờ xác nhận</span>
          <h3 class="value">{{ pendingCount }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="card-info">
          <span class="label">Đã duyệt lịch</span>
          <h3 class="value">{{ confirmedCount }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon purple"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="card-info">
          <span class="label">Doanh thu thực thu</span>
          <h3 class="value">{{ formatCurrency(realizedRevenue) }}</h3>
          <small class="sub-label">Đã thanh toán & duyệt</small>
        </div>
      </div>
    </section>

    <!-- Bookings Table Card -->
    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo mã đơn, khách hàng, xe..." v-model="searchQuery" />
        </div>
        <div class="filter-group">
          <div class="select-wrapper">
            <i class="fa-solid fa-filter"></i>
            <select v-model="filterStatus" class="form-select">
              <option value="">Tất cả trạng thái lịch</option>
              <option value="0">Chờ xác nhận</option>
              <option value="1">Đã xác nhận</option>
              <option value="2">Đã hủy</option>
            </select>
          </div>
          <div class="select-wrapper">
            <i class="fa-solid fa-credit-card"></i>
            <select v-model="filterPayment" class="form-select">
              <option value="">Tất cả thanh toán</option>
              <option value="1">Đã xác nhận thanh toán</option>
              <option value="verifying">Chờ đối soát biên lai</option>
              <option value="unpaid">Chưa thanh toán</option>
            </select>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table" v-if="filteredBookings.length > 0">
          <thead>
            <tr>
              <th width="110">Mã đơn</th>
              <th>Khách hàng</th>
              <th>Phương tiện</th>
              <th width="140">Biên lai tải lên <small style="display:block;font-size:0.65rem;color:var(--color-text-muted);font-weight:normal;">(Chờ đối soát)</small></th>
              <th>Lịch trình thuê</th>
              <th>Tổng tiền</th>
              <th width="160">Thanh toán</th>
              <th>Trạng thái lịch</th>
              <th width="220" class="text-center">Thao tác xử lý</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td><span class="booking-id">#{{ booking.ma_dat_xe }}</span></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ booking.user_name ? booking.user_name.charAt(0).toUpperCase() : 'U' }}</div>
                  <div class="user-info">
                    <span class="name">{{ booking.user_name || 'Khách vãng lai' }}</span>
                    <span class="email">{{ booking.user_email || 'Chưa có email' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="car-title"><i class="fa-solid fa-car"></i> {{ booking.car_name }}</span>
              </td>
              <td>
                <div v-if="booking.bill_image" class="bill-thumb-container" @click="viewFullImage(booking.bill_image, booking)" title="Bấm để xem ảnh phóng to & đối soát">
                  <img :src="getBillUrl(booking.bill_image, booking)" alt="biên lai" class="bill-thumb" />
                  <div class="overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
                </div>
                <span v-else class="no-bill"><i class="fa-solid fa-circle-exclamation"></i> Chưa tải bill</span>
              </td>
              <td>
                <div class="date-cell">
                  <div><i class="fa-solid fa-calendar-day"></i> Nhận: {{ formatDate(booking.start_date) }}</div>
                  <div><i class="fa-solid fa-calendar-check"></i> Trả: {{ formatDate(booking.end_date) }}</div>
                </div>
              </td>
              <td class="price-cell">
                <span class="price">{{ formatCurrency(booking.tong_tien) }}</span>
              </td>
              <td>
                <div class="pay-cell-wrap">
                  <span class="pay-badge" :class="getPaymentStatus(booking).class">
                    <i class="fa-solid" :class="getPaymentStatus(booking).icon"></i>
                    {{ getPaymentStatus(booking).text }}
                  </span>
                  <span v-if="Number(booking.is_thanh_toan) === 1 && booking.confirmed_by_name" class="audit-note" title="Người duyệt thu">
                    <i class="fa-solid fa-user-check"></i> {{ booking.confirmed_by_name }}
                  </span>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="'status-' + booking.tinh_trang">
                  {{ getStatusText(booking.tinh_trang) }}
                </span>
              </td>
              <td class="actions-cell">
                <div class="action-btn-group">
                  <!-- Xem chi tiết -->
                  <button class="btn-action view" @click="openDetailModal(booking)" title="Xem chi tiết đơn">
                    <i class="fa-solid fa-eye"></i>
                  </button>

                  <!-- Duyệt đơn: chỉ đổi tinh_trang = 1, giữ nguyên is_thanh_toan -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 0" 
                    class="btn-action confirm" 
                    @click="updateRentalStatus(booking, 1)" 
                    title="Duyệt lịch xe"
                  >
                    <i class="fa-solid fa-check"></i>
                    <span>Duyệt</span>
                  </button>

                  <!-- Xác nhận đã thanh toán độc lập sau khi đối soát sao kê -->
                  <button 
                    v-if="Number(booking.is_thanh_toan) === 0 && Number(booking.tinh_trang) !== 2" 
                    class="btn-action pay" 
                    @click="openConfirmPaymentModal(booking)" 
                    title="Đối soát & Duyệt thu tiền thủ công"
                  >
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Duyệt thu</span>
                  </button>

                  <!-- Hủy đơn -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 0" 
                    class="btn-action cancel" 
                    @click="updateRentalStatus(booking, 2)" 
                    title="Hủy đơn này"
                  >
                    <i class="fa-solid fa-ban"></i>
                  </button>

                  <!-- Xóa đơn có cảnh báo -->
                  <button class="btn-action delete" @click="requestDeleteBooking(booking)" title="Xóa vĩnh viễn">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-folder-open"></i>
          <h4>Không tìm thấy đơn đặt xe</h4>
          <p>Không có đơn nào khớp với bộ lọc hoặc từ khóa tìm kiếm hiện tại.</p>
        </div>

        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
        </div>
      </div>
    </div>

    <!-- Booking Detail Modal -->
    <div v-if="selectedBooking" class="modal-overlay" @click.self="selectedBooking = null">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>Chi Tiết Đơn Đặt Xe #{{ selectedBooking.ma_dat_xe }}</h3>
            <p class="modal-sub">Ngày đặt: {{ formatDate(selectedBooking.created_at) }}</p>
          </div>
          <button class="btn-close" @click="selectedBooking = null" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-section">
              <h4>Thông tin khách hàng</h4>
              <p><strong>Họ tên:</strong> {{ selectedBooking.user_name || 'N/A' }}</p>
              <p><strong>Email:</strong> {{ selectedBooking.user_email || 'N/A' }}</p>
              <p><strong>Mã khách:</strong> #USR-{{ selectedBooking.user_id }}</p>
            </div>
            <div class="detail-section">
              <h4>Phương tiện & Chi phí</h4>
              <p><strong>Tên xe:</strong> {{ selectedBooking.car_name }}</p>
              <p><strong>Mã xe:</strong> #CAR-{{ selectedBooking.car_id }}</p>
              <p><strong>Tổng thanh toán:</strong> <span class="text-primary font-bold">{{ formatCurrency(selectedBooking.tong_tien) }}</span></p>
            </div>
            <div class="detail-section full">
              <h4>Lịch trình & Địa chỉ nhận xe</h4>
              <p><strong>Nhận xe:</strong> {{ formatDate(selectedBooking.start_date) }}</p>
              <p><strong>Trả xe:</strong> {{ formatDate(selectedBooking.end_date) }}</p>
              <p><strong>Địa điểm:</strong> {{ selectedBooking.dia_diem || 'Giao nhận tại showroom' }}</p>
            </div>
            <div class="detail-section full">
              <h4>Trạng thái đối soát</h4>
              <div class="status-summary-row">
                <div>
                  <span class="label-muted">Trạng thái lịch:</span>
                  <span class="status-badge" :class="'status-' + selectedBooking.tinh_trang">
                    {{ getStatusText(selectedBooking.tinh_trang) }}
                  </span>
                </div>
                <div>
                  <span class="label-muted">Thanh toán:</span>
                  <span class="pay-badge" :class="getPaymentStatus(selectedBooking).class">
                    <i class="fa-solid" :class="getPaymentStatus(selectedBooking).icon"></i>
                    {{ getPaymentStatus(selectedBooking).text }}
                  </span>
                  <div v-if="Number(selectedBooking.is_thanh_toan) === 1" class="audit-detail mt-2">
                    <div v-if="selectedBooking.confirmed_by_name"><i class="fa-solid fa-user-check"></i> <strong>Duyệt bởi:</strong> {{ selectedBooking.confirmed_by_name }}</div>
                    <div v-if="selectedBooking.payment_confirmed_at"><i class="fa-solid fa-clock"></i> <strong>Thời điểm:</strong> {{ formatDate(selectedBooking.payment_confirmed_at) }}</div>
                    <div v-if="selectedBooking.payment_note" class="text-muted"><i class="fa-solid fa-note-sticky"></i> <em>{{ selectedBooking.payment_note }}</em></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="detail-section full" v-if="selectedBooking.bill_image">
              <h4>Ảnh chứng từ khách tải lên (Cần đối soát)</h4>
              <div class="receipt-audit-warning mini mb-2">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Ảnh biên lai là chứng từ tham khảo do khách tải lên. Kế toán/Admin cần đối soát số dư trên app ngân hàng trước khi xác nhận.</span>
              </div>
              <div class="bill-preview-large" @click="viewFullImage(selectedBooking.bill_image, selectedBooking)">
                <img :src="getBillUrl(selectedBooking.bill_image, selectedBooking)" alt="Biên lai" />
                <span class="click-hint"><i class="fa-solid fa-magnifying-glass-plus"></i> Bấm để phóng to biên lai & đối soát</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            v-if="Number(selectedBooking.is_thanh_toan) === 0 && Number(selectedBooking.tinh_trang) !== 2" 
            class="btn-primary" 
            @click="openConfirmPaymentModal(selectedBooking)"
          >
            <i class="fa-solid fa-file-invoice-dollar"></i> Duyệt thu tiền đơn này
          </button>
          <button class="btn-outline" @click="selectedBooking = null">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Image Viewer Modal with Zoom & Operational Warning -->
    <div v-if="fullImageUrl" class="modal-overlay" @click.self="fullImageUrl = null">
        <div class="image-viewer-container" @click.stop>
            <div class="viewer-toolbar">
              <button class="tool-btn" @click="zoomLevel = Math.max(zoomLevel - 0.25, 0.5)" title="Thu nhỏ">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
              </button>
              <span class="zoom-value">{{ Math.round(zoomLevel * 100) }}%</span>
              <button class="tool-btn" @click="zoomLevel = Math.min(zoomLevel + 0.25, 3)" title="Phóng to">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
              </button>
              <button class="tool-btn" @click="zoomLevel = 1" title="Khôi phục kích thước chuẩn">
                <i class="fa-solid fa-compress"></i>
              </button>
              <button class="tool-btn close-tool" @click="fullImageUrl = null" title="Đóng">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <!-- Operational Security Disclaimer -->
            <div class="viewer-disclaimer">
              <i class="fa-solid fa-shield-halved"></i>
              <span><strong>Cảnh báo nghiệp vụ:</strong> Ảnh biên lai là tài liệu do khách tải lên, KHÔNG được xem là bằng chứng chắc chắn tiền đã vào tài khoản. Kế toán bắt buộc phải đối soát số dư trên app ngân hàng thật trước khi bấm duyệt thu.</span>
            </div>

            <div class="image-scroll-box">
              <img 
                :src="getBillUrl(fullImageUrl, fullImageBooking)" 
                alt="Full Bill" 
                :style="{ transform: `scale(${zoomLevel})` }"
              />
            </div>
        </div>
    </div>

    <!-- Modal Duyệt Thu Tiền Thủ Công Sau Đối Soát -->
    <div v-if="bookingToConfirmPayment" class="modal-overlay" @click.self="bookingToConfirmPayment = null">
      <div class="modal-content card confirm-modal animate-in">
        <div class="modal-header">
          <div class="confirm-icon-wrapper">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div>
            <h3>Duyệt Thu Tiền Thủ Công</h3>
            <p class="modal-sub">Xác nhận thanh toán sau khi đã đối soát sao kê ngân hàng</p>
          </div>
          <button class="btn-close" @click="bookingToConfirmPayment = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="confirm-summary-box">
            <div class="summary-line">
              <span>Mã đơn đặt xe:</span>
              <strong>#{{ bookingToConfirmPayment.ma_dat_xe }}</strong>
            </div>
            <div class="summary-line">
              <span>Khách hàng:</span>
              <span>{{ bookingToConfirmPayment.user_name || 'Khách vãng lai' }}</span>
            </div>
            <div class="summary-line">
              <span>Phương tiện:</span>
              <span>{{ bookingToConfirmPayment.car_name }}</span>
            </div>
            <div class="summary-line highlight">
              <span>Số tiền thực thu:</span>
              <span class="price-val">{{ formatCurrency(bookingToConfirmPayment.tong_tien) }}</span>
            </div>
          </div>

          <div class="receipt-audit-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Lưu ý nghiệp vụ đối soát:</strong>
              <p>Chỉ bấm duyệt khi bạn đã nhìn thấy tiền thực tế trong tài khoản ngân hàng của doanh nghiệp. Hệ thống sẽ lưu vết định danh tài khoản Admin của bạn và thời điểm xác nhận này.</p>
            </div>
          </div>

          <div class="form-group mt-3">
            <label class="form-label font-bold text-xs">Ghi chú đối soát (tùy chọn):</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="paymentConfirmNote" 
              placeholder="VD: Khớp biến động số dư Vietcombank lúc 15:20..." 
            />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="bookingToConfirmPayment = null">Hủy bỏ</button>
          <button class="btn-confirm-action" @click="executeConfirmPayment" :disabled="confirmingPayment">
            <i v-if="confirmingPayment" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check-double"></i>
            {{ confirmingPayment ? 'Đang duyệt...' : 'Xác nhận đã nhận đủ tiền' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="bookingToDelete" class="modal-overlay" @click.self="bookingToDelete = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3>Xác nhận xóa đơn</h3>
            <p class="modal-sub">Dữ liệu đơn hàng sẽ bị xóa vĩnh viễn khỏi cơ sở dữ liệu</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa vĩnh viễn đơn thuê xe sau?</p>
          <div class="highlight-box">
            <strong>Mã đơn: #{{ bookingToDelete.ma_dat_xe }}</strong>
            <span>Khách hàng: {{ bookingToDelete.user_name || 'Khách vãng lai' }} | Xe: {{ bookingToDelete.car_name }}</span>
            <span>Tổng số tiền: {{ formatCurrency(bookingToDelete.tong_tien) }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="bookingToDelete = null">Hủy</button>
          <button class="btn-danger" @click="executeDeleteBooking" :disabled="deleting">
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

const bookings = ref([]);
const loading = ref(true);
const deleting = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const filterPayment = ref('');

const selectedBooking = ref(null);
const fullImageUrl = ref(null);
const zoomLevel = ref(1);
const bookingToDelete = ref(null);
const bookingToConfirmPayment = ref(null);
const paymentConfirmNote = ref('');
const confirmingPayment = ref(false);

const fullImageBooking = ref(null);

const getBillUrl = (path, booking = null) => {
  if (!path && !booking) return '';
  const token = localStorage.getItem('token_admin') || localStorage.getItem('token_client');
  if (booking && booking.receipt_url) {
    return token ? `${booking.receipt_url}?token=${encodeURIComponent(token)}` : booking.receipt_url;
  }
  if (booking && booking.id) {
    const url = `http://localhost:8000/api/bookings/${booking.id}/receipt`;
    return token ? `${url}?token=${encodeURIComponent(token)}` : url;
  }
  if (path && (path.startsWith('http://') || path.startsWith('https://'))) {
    return token ? `${path}?token=${encodeURIComponent(token)}` : path;
  }
  return '';
};

const viewFullImage = (url, booking = null) => {
  fullImageUrl.value = url;
  fullImageBooking.value = booking;
  zoomLevel.value = 1;
};

const openDetailModal = (booking) => {
  selectedBooking.value = booking;
};

const getPaymentStatus = (booking) => {
  if (!booking) return { class: 'unpaid', icon: 'fa-circle-exclamation', text: 'Chưa thanh toán' };
  
  if (Number(booking.is_thanh_toan) === 1) {
    return {
      class: 'paid',
      icon: 'fa-circle-check',
      text: 'Đã xác nhận thanh toán',
      subtext: booking.confirmed_by_name ? `Bởi: ${booking.confirmed_by_name}` : 'Đã đối soát'
    };
  }
  
  if (booking.bill_image) {
    return {
      class: 'verifying',
      icon: 'fa-clock-rotate-left',
      text: 'Chờ đối soát biên lai',
      subtext: 'Đã có biên lai'
    };
  }
  
  return {
    class: 'unpaid',
    icon: 'fa-circle-exclamation',
    text: 'Chưa thanh toán',
    subtext: 'Chưa có bill'
  };
};

const pendingCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 0).length);
const confirmedCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 1).length);

// Synchronized definition with BE/app/Http/Controllers/Admin/DashboardController.php line 25:
// where('tinh_trang', 1)->where('is_thanh_toan', 1)->sum('tong_tien')
const realizedRevenue = computed(() => {
  return bookings.value
    .filter(b => Number(b.tinh_trang) === 1 && Number(b.is_thanh_toan) === 1)
    .reduce((sum, b) => sum + Number(b.tong_tien || 0), 0);
});

const filteredBookings = computed(() => {
  return bookings.value.filter(b => {
    const query = searchQuery.value.toLowerCase().trim();
    const matchSearch = !query || 
      (b.ma_dat_xe && b.ma_dat_xe.toLowerCase().includes(query)) || 
      (b.user_name && b.user_name.toLowerCase().includes(query)) ||
      (b.car_name && b.car_name.toLowerCase().includes(query));

    const matchStatus = filterStatus.value === '' || Number(b.tinh_trang) === Number(filterStatus.value);
    
    let matchPayment = true;
    if (filterPayment.value === '1') {
      matchPayment = Number(b.is_thanh_toan) === 1;
    } else if (filterPayment.value === 'verifying') {
      matchPayment = Number(b.is_thanh_toan) === 0 && Boolean(b.bill_image);
    } else if (filterPayment.value === 'unpaid') {
      matchPayment = Number(b.is_thanh_toan) === 0 && !b.bill_image;
    }

    return matchSearch && matchStatus && matchPayment;
  });
});

const fetchBookings = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/bookings/data');
    if (res.data && res.data.status && Array.isArray(res.data.data)) {
      bookings.value = res.data.data;
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách đơn đặt xe!");
  } finally {
    loading.value = false;
  }
};

// Duyệt đơn lịch hẹn: giữ nguyên is_thanh_toan hiện tại
const updateRentalStatus = async (booking, status) => {
  try {
    const res = await baseRequest.post('admin/bookings/status', {
      id: booking.id,
      tinh_trang: status,
      is_thanh_toan: booking.is_thanh_toan // Keep current payment state!
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || (status === 1 ? "Đã duyệt lịch đặt xe!" : "Đã hủy đơn đặt xe!"));
      booking.tinh_trang = status;
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái.");
    }
  } catch (err) {
    toaster.error("Lỗi khi kết nối với máy chủ.");
  }
};

// Mở modal xác nhận duyệt thu sau khi đối soát
const openConfirmPaymentModal = (booking) => {
  bookingToConfirmPayment.value = booking;
  paymentConfirmNote.value = 'Đã đối soát biến động số dư ngân hàng';
};

// Xác nhận thanh toán độc lập qua endpoint có phân quyền và lưu vết kiểm toán
const executeConfirmPayment = async () => {
  if (!bookingToConfirmPayment.value) return;
  confirmingPayment.value = true;
  try {
    const res = await baseRequest.post('admin/bookings/confirm-payment', {
      id: bookingToConfirmPayment.value.id,
      payment_note: paymentConfirmNote.value
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xác nhận thanh toán thành công!");
      bookingToConfirmPayment.value.is_thanh_toan = 1;
      bookingToConfirmPayment.value.payment_confirmed_at = new Date().toISOString();
      if (res.data.data?.confirmed_by_name) {
        bookingToConfirmPayment.value.confirmed_by_name = res.data.data.confirmed_by_name;
      }
      bookingToConfirmPayment.value = null;
      if (selectedBooking.value) {
        selectedBooking.value.is_thanh_toan = 1;
      }
      await fetchBookings();
    } else {
      toaster.error(res.data?.message || "Không thể xác nhận thanh toán.");
    }
  } catch (err) {
    toaster.error("Lỗi khi xác nhận thanh toán.");
  } finally {
    confirmingPayment.value = false;
  }
};

const requestDeleteBooking = (booking) => {
  bookingToDelete.value = booking;
};

const executeDeleteBooking = async () => {
  if (!bookingToDelete.value) return;
  deleting.value = true;
  try {
    const res = await baseRequest.post('admin/bookings/delete', { id: bookingToDelete.value.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa đơn đặt xe!");
      bookingToDelete.value = null;
      await fetchBookings();
    } else {
      toaster.error(res.data?.message || "Không thể xóa đơn đặt xe.");
    }
  } catch (err) {
    toaster.error("Lỗi hệ thống khi xóa đơn.");
  } finally {
    deleting.value = false;
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const getStatusText = (status) => {
  const texts = ['Chờ xác nhận', 'Đã xác nhận', 'Đã hủy'];
  return texts[Number(status)] || 'Khác';
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    selectedBooking.value = null;
    fullImageUrl.value = null;
    bookingToDelete.value = null;
  }
};

onMounted(() => {
  fetchBookings();
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
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
}

.header-main { display: flex; align-items: center; gap: 16px; }
.header-icon {
  width: 52px; height: 52px;
  background: white; border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #8B5CF6;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

.page-title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-main); font-family: var(--font-heading); }
.page-title span { color: #8B5CF6; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.9rem; margin-top: 2px; }

/* Summary Grid */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
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

.card-info { display: flex; flex-direction: column; }
.card-info .label { font-size: 0.75rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; }
.card-info .value { font-size: 1.3rem; font-weight: 800; color: var(--color-text-main); margin-top: 2px; font-family: var(--font-heading); }
.card-info .sub-label { font-size: 0.7rem; color: #10B981; font-weight: 600; margin-top: 2px; }

/* Content Table */
.content-card { padding: 0; overflow: hidden; position: relative; }
.table-tools { 
  padding: 18px 24px; 
  display: flex; 
  justify-content: space-between; 
  gap: 16px; 
  border-bottom: 1px solid var(--color-border);
  background: white;
}

.search-input { position: relative; flex: 1; max-width: 360px; }
.search-input i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); }
.search-input input { 
  width: 100%; 
  padding: 10px 14px 10px 40px; 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  background: var(--bg-surface-alt); 
  outline: none; 
  font-size: 0.88rem;
  transition: all 0.2s; 
}
.search-input input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12); }

.filter-group { display: flex; gap: 12px; }
.select-wrapper { position: relative; display: flex; align-items: center; }
.select-wrapper i { position: absolute; left: 12px; color: var(--color-text-muted); font-size: 0.8rem; pointer-events: none; }

.form-select {
  padding: 9px 16px 9px 34px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-main);
  outline: none;
  cursor: pointer;
}

.form-select:focus { border-color: #8b5cf6; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { 
  background: var(--bg-surface-alt); 
  padding: 14px 18px; 
  text-align: left; 
  font-size: 0.75rem; 
  font-weight: 700; 
  color: var(--color-text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}
.data-table td { padding: 14px 18px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }

.booking-id { font-family: monospace; font-weight: 700; color: var(--color-primary); background: rgba(37, 99, 235, 0.08); padding: 4px 8px; border-radius: 4px; font-size: 0.82rem; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; color: #475569; }
.user-info .name { display: block; font-weight: 700; color: var(--color-text-main); font-size: 0.88rem; }
.user-info .email { font-size: 0.72rem; color: var(--color-text-muted); }

.car-title { font-weight: 700; color: var(--color-text-main); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; }
.car-title i { color: var(--color-primary); }

.date-cell { font-size: 0.78rem; color: var(--color-text-muted); line-height: 1.5; }
.date-cell i { width: 14px; color: var(--color-primary); }

.price-cell .price { font-weight: 800; color: var(--color-text-main); font-size: 0.92rem; }

.pay-badge { 
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem; 
  font-weight: 700; 
  padding: 4px 10px; 
  border-radius: 100px; 
}
.pay-badge.paid { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
.pay-badge.verifying { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
.pay-badge.unpaid { background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }

.pay-cell-wrap {
  display: flex;
  flex-direction: column;
  gap: 3px;
  align-items: flex-start;
}

.audit-note {
  font-size: 0.68rem;
  color: #15803D;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 3px;
}

.status-badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
.status-0 { background: #FEF3C7; color: #B45309; }
.status-1 { background: #DCFCE7; color: #15803D; }
.status-2 { background: #FEE2E2; color: #B91C1C; }

.actions-cell { text-align: center; }
.action-btn-group { display: inline-flex; align-items: center; gap: 6px; }

.btn-action { 
  height: 32px; 
  padding: 0 10px;
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  gap: 5px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer; 
  transition: all 0.15s; 
  background: white;
  color: var(--color-text-main);
}

.btn-action.view:hover { background: var(--bg-surface-alt); color: var(--color-primary); border-color: var(--color-primary); }
.btn-action.confirm { background: #10B981; color: white; border-color: #10B981; }
.btn-action.confirm:hover { background: #059669; }
.btn-action.pay { background: #8B5CF6; color: white; border-color: #8B5CF6; }
.btn-action.pay:hover { background: #7C3AED; }
.btn-action.cancel { color: #B45309; }
.btn-action.cancel:hover { background: #FEF3C7; border-color: #F59E0B; }
.btn-action.delete { color: #DC2626; }
.btn-action.delete:hover { background: #FEF2F2; border-color: #DC2626; }

/* Bill Style */
.bill-thumb-container {
  position: relative;
  width: 52px;
  height: 52px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid var(--color-border);
}

.bill-thumb { width: 100%; height: 100%; object-fit: cover; }
.bill-thumb-container .overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s;
  font-size: 0.85rem;
}
.bill-thumb-container:hover .overlay { opacity: 1; }

.no-bill {
  font-size: 0.72rem;
  color: var(--color-text-muted);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(11, 18, 32, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  width: 100%;
  max-width: 600px;
  padding: 0;
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.modal-sub { color: var(--color-text-muted); font-size: 0.82rem; margin-top: 2px; }

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
}

.modal-body { padding: 24px; }

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-section {
  padding: 14px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.detail-section.full { grid-column: span 2; }

.detail-section h4 {
  font-size: 0.82rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--color-text-muted);
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.detail-section p {
  font-size: 0.88rem;
  color: var(--color-text-main);
  margin-bottom: 4px;
}

.status-summary-row {
  display: flex;
  gap: 24px;
  align-items: center;
  margin-top: 4px;
}

.label-muted {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-right: 8px;
}

.bill-preview-large {
  margin-top: 8px;
  text-align: center;
  cursor: pointer;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px dashed var(--color-border);
  padding: 10px;
  background: white;
}

.bill-preview-large img {
  max-width: 100%;
  max-height: 240px;
  object-fit: contain;
  border-radius: 6px;
}

.click-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--color-primary);
  margin-top: 6px;
  font-weight: 600;
}

.modal-footer {
  padding: 14px 24px;
  background: var(--bg-surface-alt);
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Image Viewer with Zoom */
.image-viewer-container {
  display: flex;
  flex-direction: column;
  max-width: 90vw;
  max-height: 90vh;
  background: rgba(11, 18, 32, 0.95);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
}

.viewer-toolbar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tool-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.tool-btn:hover { background: rgba(255, 255, 255, 0.3); }
.tool-btn.close-tool { margin-left: 20px; background: rgba(239, 68, 68, 0.4); }
.tool-btn.close-tool:hover { background: #EF4444; }

.zoom-value { color: white; font-size: 0.85rem; font-weight: 700; min-width: 50px; text-align: center; }

.image-scroll-box {
  padding: 20px;
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  max-height: calc(90vh - 60px);
}

.image-scroll-box img {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
  transition: transform 0.2s ease-out;
  border-radius: 8px;
}

/* Operational Disclaimer & Audit Warning */
.viewer-disclaimer {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #FFFBEB;
  border-bottom: 1px solid #FDE68A;
  padding: 12px 20px;
  font-size: 0.82rem;
  color: #92400E;
  line-height: 1.45;
}

.viewer-disclaimer i {
  font-size: 1.25rem;
  color: #D97706;
  flex-shrink: 0;
}

.receipt-audit-warning {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  font-size: 0.82rem;
  color: #92400E;
  line-height: 1.45;
}

.receipt-audit-warning.mini {
  padding: 8px 12px;
  font-size: 0.78rem;
}

.receipt-audit-warning i {
  color: #D97706;
  font-size: 1.1rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.audit-detail {
  font-size: 0.76rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

/* Confirm Payment Modal */
.confirm-modal { max-width: 520px; }

.confirm-icon-wrapper {
  width: 44px;
  height: 44px;
  background: rgba(139, 92, 246, 0.12);
  color: #8B5CF6;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.confirm-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.confirm-summary-box {
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.86rem;
  color: var(--color-text-muted);
}

.summary-line strong { color: var(--color-text-main); font-family: monospace; }
.summary-line.highlight {
  border-top: 1px dashed var(--color-border);
  padding-top: 8px;
  margin-top: 4px;
  font-size: 0.92rem;
}

.summary-line.highlight .price-val {
  font-size: 1.1rem;
  font-weight: 800;
  color: #10B981;
}

.btn-confirm-action {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 9px 20px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
  transition: all 0.2s;
}

.btn-confirm-action:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
}

.btn-confirm-action:disabled { opacity: 0.6; cursor: not-allowed; }

/* Delete Modal */
.delete-modal { max-width: 480px; }
.delete-icon-wrapper {
  width: 44px;
  height: 44px;
  background: #FEE2E2;
  color: #DC2626;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.delete-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.highlight-box {
  margin-top: 12px;
  padding: 12px 16px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border-left: 4px solid #DC2626;
  display: flex;
  flex-direction: column;
}

.highlight-box strong { font-size: 0.95rem; color: var(--color-text-main); }
.highlight-box span { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px; }

.btn-danger {
  background: #DC2626;
  color: white;
  border: none;
  padding: 9px 18px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover { background: #B91C1C; }

.empty-state-box {
  padding: 48px 24px;
  text-align: center;
  color: var(--color-text-muted);
}

.empty-state-box i { font-size: 2.4rem; opacity: 0.4; margin-bottom: 12px; }
.empty-state-box h4 { font-weight: 700; color: var(--color-text-main); margin-bottom: 4px; }

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(139, 92, 246, 0.2);
  border-top-color: #8B5CF6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 992px) {
  .summary-section { grid-template-columns: 1fr 1fr; }
  .table-tools { flex-direction: column; }
  .search-input { max-width: 100%; }
}

@media (max-width: 640px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-section.full { grid-column: span 1; }
}
</style>
