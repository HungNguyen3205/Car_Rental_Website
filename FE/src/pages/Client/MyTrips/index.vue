<template>
  <div class="my-trips-page">
    <!-- Header -->
    <section class="trips-header">
      <div class="app-container">
        <span class="eyebrow-pill">Hành trình của bạn</span>
        <h1>Chuyến Đi <span>Của Tôi</span></h1>
        <p>Quản lý các yêu cầu đặt xe, thanh toán và lịch sử di chuyển của bạn.</p>
      </div>
    </section>

    <!-- Main Content -->
    <section class="trips-content app-container">
      <!-- Minimalist Filter Tabs -->
      <div class="tabs-minimal">
        <button 
          v-for="tab in tabs" 
          :key="tab.key" 
          class="tab-item" 
          :class="{ 'active': activeTab === tab.key }" 
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span class="tab-badge">{{ getTabCount(tab.key) }}</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách chuyến đi...</p>
      </div>

      <!-- Booking List -->
      <div v-else-if="filteredBookings.length > 0" class="booking-list">
        <div 
          v-for="booking in filteredBookings" 
          :key="booking.id" 
          class="booking-card card animate-in"
        >
          <div class="booking-main">
            <!-- Car Thumbnail -->
            <div class="car-thumb">
              <img 
                :src="getCarImage(booking.car_name)" 
                alt="car"
                @error="handleImgError($event)"
              />
            </div>

            <!-- Booking Info -->
            <div class="booking-info">
              <div class="status-badges-row">
                <!-- Rental Status -->
                <span class="status-badge" :class="getRentalStatusClass(booking.tinh_trang)">
                  <i class="fa-solid fa-circle-dot"></i>
                  {{ getRentalStatusText(booking.tinh_trang) }}
                </span>

                <!-- 3 Payment States -->
                <span class="status-badge" :class="getPaymentStatus(booking).class">
                  <i class="fa-solid" :class="getPaymentStatus(booking).icon"></i>
                  {{ getPaymentStatus(booking).text }}
                </span>

                <!-- Deposit Held Badge -->
                <span v-if="booking.financial_summary?.deposit_held_amount > 0" class="deposit-tag" title="Tiền cọc bảo đảm đang được giữ ký quỹ">
                  <i class="fa-solid fa-shield-halved"></i> Cọc: {{ Number(booking.financial_summary.deposit_held_amount).toLocaleString('vi-VN') }}đ
                </span>
              </div>

              <h3 class="booking-car-title">{{ booking.car_name }}</h3>

              <div class="booking-meta">
                <span class="meta-item">
                  <i class="fa-solid fa-hashtag"></i> 
                  <strong>{{ booking.ma_dat_xe }}</strong>
                </span>
                <span class="meta-item">
                  <i class="fa-solid fa-calendar-days"></i> 
                  {{ formatDate(booking.start_date) }} → {{ formatDate(booking.end_date) }}
                </span>
                <span class="meta-item">
                  <i class="fa-solid fa-location-dot"></i> 
                  {{ booking.dia_chi_nhan_xe || 'Tại Showroom' }}
                </span>
              </div>
            </div>

            <!-- Price & Actions -->
            <div class="booking-price-area">
              <span class="price-label">Tổng chi phí thuê</span>
              <span class="total-amount">{{ Number(booking.tong_tien).toLocaleString('vi-VN') }}đ</span>

              <!-- Multi-installment Breakdown -->
              <div v-if="booking.financial_summary?.has_transactions" class="client-price-sub">
                <span v-if="booking.financial_summary.paid_rent_amount > 0" class="sub-paid text-success">
                  Đã trả: {{ Number(booking.financial_summary.paid_rent_amount).toLocaleString('vi-VN') }}đ
                </span>
                <span v-if="booking.financial_summary.remaining_rent_amount > 0" class="sub-remaining text-warning">
                  Còn lại: {{ Number(booking.financial_summary.remaining_rent_amount).toLocaleString('vi-VN') }}đ
                </span>
              </div>
              
              <div class="action-btn-group">
                <!-- View Detail Button: Always available -->
                <router-link :to="'/my-bookings/' + booking.id" class="btn-detail">
                  <i class="fa-solid fa-file-lines"></i>
                  Chi tiết đơn
                </router-link>

                <!-- Pay / Upload Bill Button: When not yet paid or partially paid -->
                <button 
                  v-if="(Number(booking.is_thanh_toan) === 0 || Number(booking.is_thanh_toan) === 2) && booking.tinh_trang !== 2" 
                  class="btn-pay" 
                  @click="openPaymentModal(booking)"
                >
                  <i class="fa-solid fa-qrcode"></i>
                  {{ Number(booking.is_thanh_toan) === 2 ? 'Thanh toán tiếp' : 'Thanh toán' }}
                </button>

                <!-- Cancel Button: Only when pending approval -->
                <button 
                  v-if="booking.tinh_trang === 0" 
                  class="btn-cancel" 
                  @click="confirmCancelBooking(booking)"
                >
                  Hủy đơn
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-trips card text-center animate-in">
        <div class="empty-icon"><i class="fa-solid fa-road"></i></div>
        <h3>Không có chuyến đi nào trong mục này</h3>
        <p>Hành trình của bạn sẽ xuất hiện tại đây sau khi bạn hoàn tất đặt xe trên website.</p>
        <router-link to="/fleet" class="btn-primary mt-4">
          <i class="fa-solid fa-car"></i> Tìm xe ngay
        </router-link>
      </div>
    </section>

    <!-- Payment & Receipt Upload Modal -->
    <div v-if="selectedBooking" class="modal-overlay" @click.self="closePaymentModal">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>Thanh Toán & Gửi Biên Lai</h3>
            <p class="modal-sub">Mã đơn: <strong>#{{ selectedBooking.ma_dat_xe }}</strong></p>
          </div>
          <button type="button" class="btn-close" @click="closePaymentModal">&times;</button>
        </div>

        <div class="modal-body">
          <!-- Step 1: QR Code -->
          <div class="payment-card-body">
            <div class="payment-qr-container">
              <img 
                :src="`https://api.vietqr.io/image/${settings.bank_bin || '970422'}-${settings.bank_account_number || '0971120038'}-qr_only.png?accountName=${encodeURIComponent(settings.bank_account_holder || 'NAM HUNG RENTAL')}&amount=${selectedBooking.financial_summary?.remaining_rent_amount && selectedBooking.financial_summary.remaining_rent_amount > 0 ? selectedBooking.financial_summary.remaining_rent_amount : selectedBooking.tong_tien}&addInfo=${selectedBooking.ma_dat_xe}`" 
                alt="VietQR Chuyển Khoản"
                class="qr-image"
              />
              <p class="qr-hint">Quét mã QR bằng ứng dụng ngân hàng bất kỳ để chuyển tiền nhanh</p>
            </div>

            <div class="payment-details-list">
              <div class="pay-row">
                <span>Ngân hàng thụ hưởng:</span>
                <strong>{{ settings.bank_name || 'MBBank (Quân Đội)' }}</strong>
              </div>
              <div class="pay-row">
                <span>Số tài khoản:</span>
                <strong class="highlight-text">{{ settings.bank_account_number || '0971120038' }}</strong>
              </div>
              <div class="pay-row">
                <span>Chủ tài khoản:</span>
                <span>{{ settings.bank_account_holder || 'NAM HUNG RENTAL' }}</span>
              </div>
              <div class="pay-row">
                <span>Số tiền cần thanh toán:</span>
                <strong class="text-primary">
                  {{ Number(selectedBooking.financial_summary?.remaining_rent_amount && selectedBooking.financial_summary.remaining_rent_amount > 0 ? selectedBooking.financial_summary.remaining_rent_amount : selectedBooking.tong_tien).toLocaleString('vi-VN') }} đ
                </strong>
                <small v-if="selectedBooking.financial_summary?.paid_rent_amount > 0" class="text-muted">
                  (Đã thanh toán trước: {{ Number(selectedBooking.financial_summary.paid_rent_amount).toLocaleString('vi-VN') }} đ)
                </small>
              </div>
              <div class="pay-row">
                <span>Nội dung chuyển khoản:</span>
                <strong class="highlight-text">{{ selectedBooking.ma_dat_xe }}</strong>
              </div>
            </div>
          </div>

          <!-- Step 2: Upload Bill Section -->
          <div class="upload-bill-section">
            <h4><i class="fa-solid fa-cloud-arrow-up"></i> Tải lên biên lai chuyển khoản</h4>
            <p class="upload-desc">Sau khi chuyển khoản thành công, quý khách vui lòng chụp màn hình biên lai và tải lên để hệ thống đối soát duyệt đơn.</p>
            
            <div class="file-picker-box" @click="$refs.receiptInput.click()">
              <input 
                type="file" 
                ref="receiptInput" 
                style="display: none" 
                accept="image/jpeg,image/png,image/webp,image/jpg"
                @change="handleReceiptSelected"
              />
              
              <div v-if="!receiptPreview" class="empty-upload">
                <i class="fa-regular fa-image upload-icon"></i>
                <span>Nhấn để chọn ảnh biên lai (JPEG, PNG, WebP tối đa 5MB)</span>
              </div>
              
              <div v-else class="preview-upload">
                <img :src="receiptPreview" alt="Xem trước biên lai" class="preview-img" />
                <span class="change-hint">Nhấn để thay đổi ảnh khác</span>
              </div>
            </div>

            <button 
              type="button" 
              class="btn-primary w-full mt-3" 
              :disabled="!receiptFile || uploadingBill"
              @click="submitReceiptUpload"
            >
              <i v-if="uploadingBill" class="fa-solid fa-spinner fa-spin"></i>
              <span v-else>Xác nhận gửi biên lai</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { useSystemSettings } from '../../../core/settingsStore';

const toaster = createToaster({ position: "top-right" });
const { settings, fetchSettings } = useSystemSettings();

const loading = ref(true);
const bookings = ref([]);
const activeTab = ref('all');
const selectedBooking = ref(null);

const receiptFile = ref(null);
const receiptPreview = ref('');
const uploadingBill = ref(false);

const tabs = [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ duyệt' },
  { key: 'confirmed', label: 'Đã duyệt' },
  { key: 'in_progress', label: 'Đang thuê' },
  { key: 'completed', label: 'Hoàn tất' },
  { key: 'cancelled', label: 'Đã hủy' }
];

const CAR_IMAGE_MAP = {
  'vf9': '/assets/images/vf9.png',
  'vf8': '/assets/images/cars/vf8.png',
  'vf7': '/assets/images/cars/vf7.png',
  'camry': '/assets/images/cars/camry.png',
  'xpander': '/assets/images/cars/xpander.png'
};

const handleImgError = (event) => {
  event.target.src = '/assets/images/vf9.png';
};

const getCarImage = (name) => {
  const n = (name || '').toLowerCase();
  for (const [key, path] of Object.entries(CAR_IMAGE_MAP)) {
    if (n.includes(key)) return path;
  }
  return '/assets/images/vf9.png';
};

const fetchBookings = async () => {
  loading.value = true;
  try {
    const response = await baseRequest.get('user/bookings/data');
    if (response.data && response.data.status) {
      bookings.value = response.data.data;
    }
  } catch (error) {
    console.error("Lỗi khi tải lịch sử chuyến đi:", error);
    toaster.error("Không thể tải danh sách chuyến đi!");
  } finally {
    loading.value = false;
  }
};

const getTabCount = (key) => {
  if (key === 'all') return bookings.value.length;
  if (key === 'pending') return bookings.value.filter(b => Number(b.tinh_trang) === 0).length;
  if (key === 'confirmed') return bookings.value.filter(b => Number(b.tinh_trang) === 1).length;
  if (key === 'in_progress') return bookings.value.filter(b => [3, 4].includes(Number(b.tinh_trang))).length;
  if (key === 'completed') return bookings.value.filter(b => Number(b.tinh_trang) === 5).length;
  if (key === 'cancelled') return bookings.value.filter(b => Number(b.tinh_trang) === 2).length;
  return 0;
};

const filteredBookings = computed(() => {
  if (activeTab.value === 'all') return bookings.value;
  if (activeTab.value === 'pending') return bookings.value.filter(b => Number(b.tinh_trang) === 0);
  if (activeTab.value === 'confirmed') return bookings.value.filter(b => Number(b.tinh_trang) === 1);
  if (activeTab.value === 'in_progress') return bookings.value.filter(b => [3, 4].includes(Number(b.tinh_trang)));
  if (activeTab.value === 'completed') return bookings.value.filter(b => Number(b.tinh_trang) === 5);
  if (activeTab.value === 'cancelled') return bookings.value.filter(b => Number(b.tinh_trang) === 2);
  return bookings.value;
});

const getRentalStatusText = (status) => {
  const texts = {
    0: 'Chờ duyệt',
    1: 'Đã duyệt',
    2: 'Đã hủy',
    3: 'Đang thuê',
    4: 'Đã trả xe',
    5: 'Hoàn tất'
  };
  return texts[Number(status)] || 'Đang xử lý';
};

const getRentalStatusClass = (status) => {
  const num = Number(status);
  if (num === 0) return 'pending';
  if (num === 1) return 'confirmed';
  if (num === 2) return 'cancelled';
  if (num === 3) return 'in-progress';
  if (num === 4) return 'returned';
  if (num === 5) return 'completed';
  return 'pending';
};

const getPaymentStatus = (booking) => {
  if (!booking) return { class: 'unpaid', icon: 'fa-credit-card', text: 'Chưa thanh toán' };

  // Đơn đã hủy nhưng đã thu tiền: KHÔNG coi là đã hoàn tiền, hiển thị cần xử lý / đối soát hoàn tiền
  if (Number(booking.tinh_trang) === 2 && Number(booking.is_thanh_toan) === 1) {
    return {
      class: 'refund-review',
      icon: 'fa-triangle-exclamation',
      text: 'Đã thu tiền (Cần đối soát hoàn tiền)'
    };
  }

  // Đã thanh toán một phần
  if (Number(booking.is_thanh_toan) === 2) {
    return {
      class: 'partial',
      icon: 'fa-chart-pie',
      text: 'Đã thanh toán 1 phần'
    };
  }
  
  if (Number(booking.is_thanh_toan) === 1) {
    return {
      class: 'paid',
      icon: 'fa-circle-check',
      text: 'Đã xác nhận thanh toán'
    };
  }
  
  if (booking.bill_image) {
    return {
      class: 'verifying',
      icon: 'fa-clock-rotate-left',
      text: 'Chờ đối soát biên lai'
    };
  }
  
  return {
    class: 'unpaid',
    icon: 'fa-credit-card',
    text: 'Chưa thanh toán'
  };
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const openPaymentModal = (booking) => {
  selectedBooking.value = booking;
  receiptFile.value = null;
  receiptPreview.value = '';
};

const closePaymentModal = () => {
  selectedBooking.value = null;
  receiptFile.value = null;
  receiptPreview.value = '';
};

const handleReceiptSelected = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toaster.warning("Kích thước ảnh vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn!");
    return;
  }

  receiptFile.value = file;
  receiptPreview.value = URL.createObjectURL(file);
};

const submitReceiptUpload = async () => {
  if (!receiptFile.value || !selectedBooking.value) return;

  uploadingBill.value = true;
  try {
    const formData = new FormData();
    formData.append('booking_id', selectedBooking.value.id);
    formData.append('bill_image', receiptFile.value);

    const res = await baseRequest.post('user/bookings/upload-bill', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.status) {
      toaster.success(res.data.message || "Tải biên lai thành công!");
      closePaymentModal();
      await fetchBookings();
    } else {
      toaster.error(res.data.message || "Không thể tải biên lai!");
    }
  } catch (error) {
    console.error("Lỗi khi tải biên lai:", error);
    toaster.error("Có lỗi xảy ra khi tải ảnh lên!");
  } finally {
    uploadingBill.value = false;
  }
};

const confirmCancelBooking = async (booking) => {
  const reason = prompt(`Xác nhận hủy đơn đặt xe #${booking.ma_dat_xe}.\nVui lòng nhập lý do hủy đơn (tùy chọn):`, "Khách hàng đổi kế hoạch");
  if (reason === null) {
    return;
  }

  try {
    const res = await baseRequest.post('user/bookings/huy-lich', { 
      id: booking.id,
      cancel_reason: reason.trim() || 'Khách hàng yêu cầu hủy đơn'
    });
    if (res.data.status) {
      toaster.success(res.data.message || "Hủy đơn đặt xe thành công!");
      fetchBookings();
    } else {
      toaster.error(res.data.message || "Không thể hủy đơn này!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi hủy đơn đặt xe!");
  }
};

onMounted(() => {
  fetchSettings();
  fetchBookings();
});
</script>

<style scoped>
.my-trips-page {
  padding-bottom: 80px;
  min-height: 100vh;
}

.trips-header {
  padding: 130px 0 40px;
  background: linear-gradient(180deg, #ffffff 0%, var(--bg-main) 100%);
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 32px;
}

.eyebrow-pill {
  display: inline-block;
  padding: 4px 12px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: 0.775rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.trips-header h1 {
  font-size: clamp(2rem, 4vw, 2.8rem);
  margin-bottom: 8px;
}

.trips-header h1 span {
  color: var(--color-primary);
}

/* Tabs */
.tabs-minimal {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 12px;
  margin-bottom: 24px;
}

.tab-item {
  padding: 10px 18px;
  border-radius: var(--radius-full);
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  font-size: 0.885rem;
  font-weight: 600;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  transition: all var(--duration-fast);
}

.tab-badge {
  font-size: 0.75rem;
  background: var(--bg-surface-sub);
  padding: 2px 7px;
  border-radius: 10px;
}

.tab-item:hover {
  background: var(--bg-surface-sub);
  color: var(--color-text-main);
}

.tab-item.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.tab-item.active .tab-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* Booking List */
.booking-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.booking-card {
  padding: 24px;
  background: #ffffff;
}

.booking-main {
  display: grid;
  grid-template-columns: 180px 1fr auto;
  gap: 24px;
  align-items: center;
}

.car-thumb {
  background: var(--bg-surface-sub);
  border-radius: var(--radius-md);
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 16 / 10;
}

.car-thumb img {
  max-height: 100%;
  object-fit: contain;
}

.booking-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.status-badges-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.booking-car-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.booking-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 0.885rem;
  color: var(--color-text-muted);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-item i {
  color: var(--color-primary);
}

/* Price & Buttons */
.booking-price-area {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  min-width: 200px;
}

.price-label {
  font-size: 0.775rem;
  color: var(--color-text-muted);
}

.total-amount {
  font-size: 1.5rem;
  font-weight: 800;
  font-family: var(--font-heading);
  color: var(--color-text-main);
}

.action-btn-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  margin-top: 6px;
}

.btn-detail {
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  background: var(--bg-surface-sub);
  color: var(--color-text-main);
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all var(--duration-fast);
}

.btn-detail:hover {
  background: #e2e8f0;
}

.btn-pay {
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  background: var(--grad-active);
  color: #ffffff;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.btn-pay:hover {
  filter: brightness(1.08);
}

.btn-cancel {
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  color: var(--color-danger);
  font-size: 0.825rem;
  font-weight: 600;
  background: transparent;
  text-align: center;
}

.btn-cancel:hover {
  background: var(--color-danger-bg);
}

/* Empty State */
.empty-trips {
  padding: 80px 24px;
  background: #ffffff;
  max-width: 600px;
  margin: 40px auto;
}

.empty-icon {
  width: 72px;
  height: 72px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto 20px;
}

.loading-state {
  text-align: center;
  padding: 80px 0;
}

.loading-state .spinner {
  margin: 0 auto 16px;
}

/* Modal Payment */
.modal-sub {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.payment-card-body {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 20px;
  align-items: center;
  background: var(--bg-surface-sub);
  padding: 16px;
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
}

.payment-qr-container {
  text-align: center;
}

.qr-image {
  width: 100%;
  max-width: 180px;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  margin: 0 auto 8px;
}

.qr-hint {
  font-size: 0.725rem;
  color: var(--color-text-muted);
  line-height: 1.4;
}

.payment-details-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  font-size: 0.9rem;
}

.pay-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.pay-row span {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  text-transform: uppercase;
  font-weight: 700;
}

.highlight-text {
  color: var(--color-primary);
  font-family: var(--font-mono);
  font-size: 1.1rem;
}

/* Upload Bill */
.upload-bill-section h4 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.upload-desc {
  font-size: 0.825rem;
  color: var(--color-text-muted);
  margin-bottom: 14px;
}

.file-picker-box {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: 20px;
  text-align: center;
  cursor: pointer;
  background: #ffffff;
  transition: all var(--duration-fast);
}

.file-picker-box:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-light);
}

.upload-icon {
  font-size: 2rem;
  color: var(--color-text-muted);
  margin-bottom: 8px;
  display: block;
}

.empty-upload span {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.preview-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.preview-img {
  max-height: 160px;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-sm);
}

.change-hint {
  font-size: 0.775rem;
  color: var(--color-primary);
  font-weight: 600;
}

.deposit-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #E0F2FE;
  color: #0369A1;
  border: 1px solid #BAE6FD;
  padding: 3px 8px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
}

.client-price-sub {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: -4px;
  margin-bottom: 4px;
}

.text-success { color: #16A34A !important; }
.text-warning { color: #D97706 !important; }

.status-badge.partial {
  background: #FEF3C7;
  color: #B45309;
  border: 1px solid #FCD34D;
}

@media (max-width: 900px) {
  .booking-main {
    grid-template-columns: 1fr;
  }
  .car-thumb {
    max-width: 240px;
  }
  .booking-price-area {
    align-items: flex-start;
  }
  .action-btn-group {
    flex-direction: row;
    flex-wrap: wrap;
  }
  .payment-card-body {
    grid-template-columns: 1fr;
  }
}
</style>
