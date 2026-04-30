<template>
  <div class="my-trips-page">
    <section class="trips-header">
      <div class="app-container">
        <h1>Chuyến Đi <span>Của Tôi</span></h1>
        <p>Quản lý các yêu cầu thuê xe và lịch sử hành trình của bạn.</p>
      </div>
    </section>

    <section class="trips-content app-container">
      <div class="tabs-minimal">
        <button v-for="tab in tabs" :key="tab" class="tab-item" :class="{ 'active': activeTab === tab }" @click="activeTab = tab">
          {{ tab }}
        </button>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách chuyến đi...</p>
      </div>

      <div v-else-if="filteredBookings.length > 0" class="booking-list">
        <div v-for="booking in filteredBookings" :key="booking.id" class="booking-card card glass animate-in">
          <div class="booking-main">
            <div class="car-thumb">
                <img :src="getCarImage(booking.car_name)" alt="car">
            </div>
            <div class="booking-info">
              <div class="status-badge" :class="getStatusClass(booking.tinh_trang)">
                {{ getStatusText(booking.tinh_trang) }}
              </div>
              <h3>{{ booking.car_name }}</h3>
              <div class="booking-meta">
                <span class="meta-item"><i class="fa-solid fa-hashtag"></i> {{ booking.ma_dat_xe }}</span>
                <span class="meta-item"><i class="fa-solid fa-calendar-days"></i> {{ formatDate(booking.start_date) }} - {{ formatDate(booking.end_date) }}</span>
                <span class="meta-item"><i class="fa-solid fa-location-dot"></i> {{ booking.dia_chi_nhan_xe || 'Tại showroom' }}</span>
              </div>
            </div>
            <div class="booking-price">
              <span class="price-label">Tổng thanh toán</span>
              <span class="total-amount">{{ Number(booking.tong_tien).toLocaleString() }}đ</span>
              <button v-if="booking.is_thanh_toan === 0" class="btn-pay" @click="showPaymentInfo(booking)">
                Thanh toán ngay
              </button>
              <router-link :to="'/my-bookings/' + booking.id" v-else class="btn-detail">Xem chi tiết</router-link>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-trips">
        <div class="empty-icon"><i class="fa-solid fa-road"></i></div>
        <h3>Chưa có chuyến đi nào</h3>
        <p>Hành trình của bạn sẽ xuất hiện tại đây sau khi bạn thực hiện đặt xe.</p>
        <router-link to="/fleet" class="btn-primary mt-4">Tìm xe ngay</router-link>
      </div>
    </section>

    <!-- Payment Modal (Simplified) -->
    <div v-if="selectedBooking" class="modal-overlay" @click="selectedBooking = null">
        <div class="modal-content card glass" @click.stop>
            <h3>Thông tin thanh toán</h3>
            <p>Mã đơn hàng: <strong>{{ selectedBooking.ma_dat_xe }}</strong></p>
            <div class="payment-qr">
                <img :src="`https://api.vietqr.io/image/970436-123456789-V3N0a6.jpg?accountName=NAM%20HUNG%20RENTAL&amount=${selectedBooking.tong_tien}&addInfo=${selectedBooking.ma_dat_xe}`" alt="QR Payment">
            </div>
            <p class="instruction">Vui lòng quét mã QR để thanh toán. Sau khi chuyển khoản, hãy báo cho Chatbot để xác nhận tự động.</p>
            <button class="btn-close" @click="selectedBooking = null">Đóng</button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';

const loading = ref(true);
const bookings = ref([]);
const activeTab = ref('Tất cả');
const tabs = ['Tất cả', 'Chưa thanh toán', 'Đã thanh toán', 'Hoàn thành'];
const selectedBooking = ref(null);

const CAR_IMAGE_MAP = {
    'vf9': '/assets/images/vf9.png',
    'vf8': '/assets/images/cars/vf8.png',
    'vf7': '/assets/images/cars/vf7.png',
};

const getCarImage = (name) => {
    const n = (name || '').toLowerCase();
    if (n.includes('vf9')) return CAR_IMAGE_MAP.vf9;
    if (n.includes('vf8')) return CAR_IMAGE_MAP.vf8;
    if (n.includes('vf7')) return CAR_IMAGE_MAP.vf7;
    return '/assets/images/cars/camry.png';
};

const fetchBookings = async () => {
  loading.value = true;
  try {
    const response = await baseRequest.get('user/bookings/data');
    if (response.data && response.data.status) {
      bookings.value = response.data.data;
    }
  } catch (error) {
    console.error("Error fetching bookings:", error);
  } finally {
    loading.value = false;
  }
};

const filteredBookings = computed(() => {
    if (activeTab.value === 'Tất cả') return bookings.value;
    if (activeTab.value === 'Chưa thanh toán') return bookings.value.filter(b => b.is_thanh_toan === 0);
    if (activeTab.value === 'Đã thanh toán') return bookings.value.filter(b => b.is_thanh_toan === 1);
    return bookings.value.filter(b => b.trang_thai === 'Hoàn thành');
});

const getStatusText = (status) => {
    const texts = ['Chờ xác nhận', 'Đã xác nhận', 'Đã hủy'];
    return texts[status] || 'Khác';
};

const getStatusClass = (status) => {
    if (status === 0) return 'pending';
    if (status === 1) return 'paid';
    return 'cancelled';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('vi-VN');
};

const showPaymentInfo = (booking) => {
    selectedBooking.value = booking;
};

onMounted(() => {
  fetchBookings();
});
</script>

<style scoped>
.my-trips-page {
  padding-top: 100px;
  background: #f1f5f9;
  min-height: 100vh;
}

.trips-header {
  padding: 60px 0;
  background: white;
  margin-bottom: 40px;
}

.trips-header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.trips-header h1 span {
  color: var(--color-primary);
}

.tabs-minimal {
  display: flex;
  gap: 30px;
  margin-bottom: 30px;
  border-bottom: 1px solid #e2e8f0;
}

.tab-item {
  padding: 15px 5px;
  font-weight: 700;
  color: var(--color-text-muted);
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  transition: all 0.3s;
}

.tab-item.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.booking-card {
  margin-bottom: 20px;
  padding: 24px;
  border-radius: 20px;
  background: white !important;
}

.booking-main {
  display: flex;
  gap: 24px;
  align-items: center;
}

.car-thumb {
  width: 140px;
  height: 90px;
  background: #f8fafc;
  border-radius: 12px;
  overflow: hidden;
}

.car-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.booking-info {
  flex: 1;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.status-badge.pending { background: #fff7ed; color: #f97316; }
.status-badge.paid { background: #f0fdf4; color: #22c55e; }

.booking-info h3 {
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: 8px;
}

.booking-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.meta-item i { margin-right: 6px; }

.booking-price {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.total-amount {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
}

.btn-pay {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-pay:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
}

.btn-detail {
  background: white;
  color: #64748b;
  border: 1.5px solid #e2e8f0;
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-detail:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #cbd5e1;
  transform: translateY(-2px);
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    width: 100%;
    max-width: 450px;
    padding: 40px;
    text-align: center;
    background: white !important;
}

.payment-qr img {
    width: 250px;
    margin: 20px 0;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.instruction { font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 20px; }
.btn-close { width: 100%; padding: 12px; background: #f1f5f9; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; }

@media (max-width: 768px) {
  .booking-main { flex-direction: column; text-align: center; }
  .booking-price { text-align: center; margin-top: 20px; }
  .car-thumb { margin: 0 auto; }
}
</style>
