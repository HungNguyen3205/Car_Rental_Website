<template>
  <div class="home-page">
    <!-- A + B: Reference Hero Section -->
    <HomeHero
      @scroll-to-search="scrollToSearch"
      @category-change="onHeroCategoryChange"
    />

    <!-- C: Horizontal Search & Booking Panel -->
    <BookingSearchPanel
      :provinces="provinces"
      :loading="loading"
      @search="handleSearchSubmit"
    />

    <!-- D: Featured Fleet Showcase -->
    <FeaturedFleet
      :cars="cars"
      :categories="categories"
      :loading="loading"
      @select-car-for-booking="openBookingModal"
    />

    <!-- E: Three Steps to the Open Road -->
    <RentalSteps />

    <!-- F: Bento Grid Feature Showcase -->
    <HomeBento />

    <!-- FAQ Accordion Section -->
    <section class="home-faq-section app-container" aria-label="Câu hỏi thường gặp">
      <div class="faq-heading-row">
        <span class="eyebrow-text">GIẢI ĐÁP THẮC MẮC</span>
        <h2 class="section-title">Câu Hỏi Thường Gặp</h2>
        <p class="section-desc">Mọi thông tin cần biết để hành trình của quý khách luôn an tâm và trọn vẹn nhất</p>
      </div>

      <div class="faq-list-grid">
        <div
          v-for="(faq, index) in faqs"
          :key="index"
          class="faq-card"
          :class="{ 'is-open': openFaqIndex === index }"
        >
          <button
            type="button"
            class="faq-trigger"
            :aria-expanded="openFaqIndex === index"
            @click="toggleFaq(index)"
          >
            <span class="faq-q-text">{{ faq.question }}</span>
            <i class="fa-solid fa-chevron-down faq-chevron"></i>
          </button>
          <div v-show="openFaqIndex === index" class="faq-body">
            <p>{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal 1: Booking Confirmation Modal -->
    <div v-if="showBookingModal && bookingTargetCar" class="modal-backdrop-dark" @click.self="closeBookingModal">
      <div class="booking-modal-card">
        <div class="modal-head">
          <div class="head-title-wrap">
            <span class="head-eyebrow">XÁC NHẬN ĐẶT LỊCH</span>
            <h3>{{ bookingTargetCar.name }}</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="closeBookingModal">&times;</button>
        </div>

        <div class="modal-body-content">
          <!-- Car Mini Stage -->
          <div class="modal-car-preview">
            <img :src="resolveCarImage(bookingTargetCar)" :alt="bookingTargetCar.name" @error="handleCarImgError" />
            <div class="car-badge-info">
              <span class="cat-pill">{{ bookingTargetCar.category_name || 'SUV Hạng Sang' }}</span>
              <strong class="rate-text">{{ formatVND(bookingTargetCar.price_per_day) }}/ngày</strong>
            </div>
          </div>

          <!-- Trip Specifications -->
          <div class="booking-config-fields">
            <div class="field-item">
              <label><i class="fa-solid fa-location-dot"></i> Điểm giao xe:</label>
              <input
                type="text"
                v-model="bookingLocation"
                placeholder="Nhập địa chỉ giao xe hoặc nhận tại showroom..."
                class="modal-input"
              />
            </div>

            <div class="fields-two-col">
              <div class="field-item">
                <label><i class="fa-solid fa-calendar-plus"></i> Nhận xe:</label>
                <VueDatePicker
                  v-model="bookingStartDate"
                  :min-date="new Date()"
                  placeholder="Chọn ngày giờ"
                  auto-apply
                  :teleport="true"
                  format="dd/MM/yyyy HH:mm"
                  class="dark-datepicker-modal"
                />
              </div>
              <div class="field-item">
                <label><i class="fa-solid fa-calendar-check"></i> Trả xe:</label>
                <VueDatePicker
                  v-model="bookingEndDate"
                  :min-date="bookingStartDate || new Date()"
                  placeholder="Chọn ngày giờ"
                  auto-apply
                  :teleport="true"
                  format="dd/MM/yyyy HH:mm"
                  class="dark-datepicker-modal"
                />
              </div>
            </div>
          </div>

          <!-- Price Calculation Box -->
          <div class="price-calculation-card">
            <div class="calc-row">
              <span>Thời gian thuê tính toán:</span>
              <strong>{{ modalRentalDays }} ngày</strong>
            </div>
            <div class="calc-row">
              <span>Đơn giá niêm yết:</span>
              <span>{{ formatVND(bookingTargetCar.price_per_day) }} / ngày</span>
            </div>
            <div class="calc-divider"></div>
            <div class="calc-row grand-total">
              <span>Tổng tiền tạm tính:</span>
              <span class="price-total">{{ formatVND(modalTotalCost) }}</span>
            </div>
            <small class="note-text">* Chưa bao gồm phụ phí giao nhận ngoài khu vực trung tâm nếu có.</small>
          </div>
        </div>

        <div class="modal-footer-actions">
          <button type="button" class="btn-cancel-modal" @click="closeBookingModal">Hủy bỏ</button>
          <button
            type="button"
            class="btn-confirm-booking"
            :disabled="bookingLoading"
            @click="executeBooking"
          >
            <i v-if="bookingLoading" class="fa-solid fa-spinner fa-spin"></i>
            <template v-else>
              <span>Xác nhận đặt đơn</span>
              <i class="fa-solid fa-arrow-right"></i>
            </template>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal 2: Booking Success Receipt Modal -->
    <div v-if="bookingSuccessData" class="modal-backdrop-dark" @click.self="bookingSuccessData = null">
      <div class="receipt-modal-card">
        <div class="success-header">
          <div class="success-ring">
            <i class="fa-solid fa-check"></i>
          </div>
          <h3>Đặt Xe Thành Công!</h3>
          <p>Hệ thống đã tiếp nhận yêu cầu thuê xe của quý khách.</p>
        </div>

        <div class="receipt-details-box">
          <div class="receipt-line">
            <span>Mã đơn đặt:</span>
            <strong class="receipt-code">#{{ bookingSuccessData.ma_dat_xe }}</strong>
          </div>
          <div class="receipt-line">
            <span>Phương tiện:</span>
            <strong>{{ bookingSuccessData.carName }}</strong>
          </div>
          <div class="receipt-line">
            <span>Thời gian nhận:</span>
            <span>{{ formatDisplayDate(bookingSuccessData.startDate) }}</span>
          </div>
          <div class="receipt-line">
            <span>Thời gian trả:</span>
            <span>{{ formatDisplayDate(bookingSuccessData.endDate) }}</span>
          </div>
          <div class="receipt-line">
            <span>Địa chỉ bàn giao:</span>
            <span>{{ bookingSuccessData.address || 'Tại Showroom chính hãng' }}</span>
          </div>
          <div class="receipt-divider"></div>
          <div class="receipt-line total-line">
            <span>Tổng số tiền:</span>
            <strong class="total-emerald">{{ formatVND(bookingSuccessData.tong_tien || bookingSuccessData.price) }}</strong>
          </div>
        </div>

        <div class="receipt-notice-box">
          <i class="fa-solid fa-circle-info"></i>
          <span>Quý khách có thể xem hóa đơn và chuyển khoản đặt cọc qua VietQR tại trang <strong>Chuyến đi của tôi</strong>.</span>
        </div>

        <div class="receipt-actions">
          <button type="button" class="btn-cancel-modal" @click="bookingSuccessData = null">Đóng</button>
          <router-link to="/my-bookings" class="btn-confirm-booking">
            <span>Xem chuyến đi của tôi</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Modal 3: Auth Prompt Dialog -->
    <div v-if="showAuthPrompt" class="modal-backdrop-dark" @click.self="showAuthPrompt = false">
      <div class="auth-dialog-card">
        <div class="auth-dialog-icon">
          <i class="fa-solid fa-lock"></i>
        </div>
        <h3>Yêu Cầu Đăng Nhập</h3>
        <p>Vui lòng đăng nhập hoặc tạo tài khoản DriveRent để hoàn tất thủ tục thuê xe và lưu thông tin hợp đồng an toàn.</p>

        <div class="auth-actions-grid">
          <router-link to="/login" class="btn-auth-primary">
            <span>Đăng nhập ngay</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
          <router-link to="/register" class="btn-auth-secondary">
            <span>Đăng ký tài khoản</span>
          </router-link>
        </div>
        <button type="button" class="btn-close-subtle" @click="showAuthPrompt = false">Để sau</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { createToaster } from '@meforma/vue-toaster';

import baseRequest from '../../core/baseRequest';
import { resolveCarImage, handleCarImgError } from '../../core/carImageHelper';

// Modular Child Components matching Video Reference Sections
import HomeHero from './components/HomeHero.vue';
import BookingSearchPanel from './components/BookingSearchPanel.vue';
import FeaturedFleet from './components/FeaturedFleet.vue';
import RentalSteps from './components/RentalSteps.vue';
import HomeBento from './components/HomeBento.vue';

const toaster = createToaster({ position: 'top-right' });
const route = useRoute();

// State
const cars = ref([]);
const categories = ref([]);
const provinces = ref([
  { id: 'sg', name: 'TP. Hồ Chí Minh' },
  { id: 'hn', name: 'Hà Nội' },
  { id: 'dn', name: 'Đà Nẵng' },
  { id: 'nt', name: 'Nha Trang' },
  { id: 'hp', name: 'Hải Phòng' },
  { id: 'ct', name: 'Cần Thơ' }
]);
const loading = ref(false);
const loadingProvinces = ref(false);
const openFaqIndex = ref(0);

// Search State
const searchData = reactive({
  city: 'sg',
  address: '',
  startDate: new Date(),
  endDate: new Date(new Date().getTime() + 2 * 24 * 60 * 60 * 1000)
});

// Booking Modal State
const showBookingModal = ref(false);
const bookingTargetCar = ref(null);
const bookingLocation = ref('');
const bookingStartDate = ref(null);
const bookingEndDate = ref(null);
const bookingLoading = ref(false);
const bookingSuccessData = ref(null);
const showAuthPrompt = ref(false);

const faqs = [
  {
    question: 'Tôi cần chuẩn bị những giấy tờ gì khi nhận xe?',
    answer: 'Quý khách chỉ cần chuẩn bị Căn cước công dân gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B1/B2 còn hiệu lực để đối chiếu thông tin khi giao nhận xe.'
  },
  {
    question: 'Xe có được bàn giao đầy pin/nhiên liệu không?',
    answer: 'Tất cả các dòng xe tại DriveRent đều được kiểm tra kỹ thuật, vệ sinh khử khuẩn và sạc đầy tối thiểu 80% - 100% pin hoặc nhiên liệu trước khi bàn giao.'
  },
  {
    question: 'Hình thức đặt cọc và thanh toán như thế nào?',
    answer: 'Hệ thống hỗ trợ thanh toán linh hoạt bằng mã VietQR liên kết ngân hàng. Quý khách hoàn tất đặt cọc trực tuyến và biên lai được hệ thống xác nhận tự động.'
  },
  {
    question: 'Chính sách hoàn cọc sau khi trả xe được xử lý ra sao?',
    answer: 'Tiền cọc an ninh được hoàn trả lại tài khoản ngân hàng của quý khách trong vòng 24 giờ sau khi hai bên ký biên bản đối soát hoàn tất chuyến đi.'
  },
  {
    question: 'Nếu có sự cố phát sinh trên đường thì hỗ trợ thế nào?',
    answer: 'DriveRent cung cấp gói cứu hộ khẩn cấp 24/7 trên toàn quốc. Đội ngũ kỹ thuật viên luôn túc trực và xe thay thế sẽ được điều động nhanh chóng.'
  }
];

const toggleFaq = (idx) => {
  openFaqIndex.value = openFaqIndex.value === idx ? -1 : idx;
};

const formatVND = (val) => {
  return Number(val || 0).toLocaleString('vi-VN') + ' đ';
};

const formatDisplayDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const toLocalISO = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const offset = d.getTimezoneOffset() * 60000;
  const localDate = new Date(d.getTime() - offset);
  return localDate.toISOString().slice(0, 19).replace('T', ' ');
};

// Modal Calculations
const modalRentalDays = computed(() => {
  if (!bookingStartDate.value || !bookingEndDate.value) return 1;
  const start = new Date(bookingStartDate.value);
  const end = new Date(bookingEndDate.value);
  const diff = end - start;
  return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)));
});

const modalTotalCost = computed(() => {
  if (!bookingTargetCar.value) return 0;
  return modalRentalDays.value * Number(bookingTargetCar.value.price_per_day || 0);
});

// Scroll Triggers
const scrollToSearch = () => {
  nextTick(() => {
    const el = document.getElementById('booking-search-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

const onHeroCategoryChange = (cat) => {
  // Can scroll down to fleet showcase
  nextTick(() => {
    const el = document.getElementById('fleet-showcase-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

// Data Fetching
const fetchProvinces = async () => {
  loadingProvinces.value = true;
  try {
    const res = await axios.get('https://provinces.open-api.vn/api/p/');
    if (res.data && Array.isArray(res.data)) {
      provinces.value = res.data.map(p => ({
        id: p.code.toString(),
        name: p.name
      }));
    }
  } catch (err) {
    // Fallback provinces
    provinces.value = [
      { id: 'sg', name: 'TP. Hồ Chí Minh' },
      { id: 'hn', name: 'Hà Nội' },
      { id: 'dn', name: 'Đà Nẵng' },
      { id: 'nt', name: 'Nha Trang' },
      { id: 'hp', name: 'Hải Phòng' },
      { id: 'ct', name: 'Cần Thơ' }
    ];
  } finally {
    loadingProvinces.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const res = await baseRequest.get('client/categories');
    if (res.data && res.data.status && res.data.data) {
      categories.value = res.data.data;
    }
  } catch (err) {
    // Fallback standard categories
    categories.value = [
      { id: 1, name: 'SUV' },
      { id: 2, name: 'Sedan' },
      { id: 3, name: 'Hatchback' },
      { id: 4, name: 'Xe điện' },
      { id: 5, name: 'Xe sang' }
    ];
  }
};

const fetchCars = async (params = {}) => {
  loading.value = true;
  try {
    const res = await baseRequest.get('client/car/data', { params });
    if (res.data && res.data.status) {
      const data = res.data.data || [];
      cars.value = data.map(car => ({
        ...car,
        name: car.name || car.ten_xe || 'VinFast Electric',
        price_per_day: car.price_per_day || car.gia_thue || 1200000,
        cityDisplay: (() => {
          const c = (car.city || '').toLowerCase();
          if (c === 'sg') return 'TP. Hồ Chí Minh';
          if (c === 'hn') return 'Hà Nội';
          if (c === 'dn') return 'Đà Nẵng';
          if (c === 'nt') return 'Nha Trang';
          return car.city || 'Toàn quốc';
        })()
      }));
    }
  } catch (err) {
    console.error('Fetch cars error:', err);
  } finally {
    loading.value = false;
  }
};

// Search handling
const handleSearchSubmit = (payload) => {
  searchData.city = payload.city;
  searchData.address = payload.address;
  searchData.startDate = payload.startDate;
  searchData.endDate = payload.endDate;

  const cityName = provinces.value.find(p => p.id === payload.city)?.name || '';
  const params = {
    city: cityName || payload.city,
    start_date: toLocalISO(payload.startDate),
    end_date: toLocalISO(payload.endDate),
    search: ''
  };

  fetchCars(params);

  // Smooth scroll to Fleet section
  nextTick(() => {
    const el = document.getElementById('fleet-showcase-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

// Booking Actions
const openBookingModal = (car) => {
  bookingTargetCar.value = car;
  bookingLocation.value = searchData.address || `${car.cityDisplay || 'Showroom chính'} - Bàn giao tận nơi`;
  bookingStartDate.value = searchData.startDate || new Date();
  bookingEndDate.value = searchData.endDate || new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
  showBookingModal.value = true;
};

const closeBookingModal = () => {
  showBookingModal.value = false;
  bookingTargetCar.value = null;
};

const executeBooking = async () => {
  const token = localStorage.getItem('token_client');
  if (!token) {
    showBookingModal.value = false;
    showAuthPrompt.value = true;
    return;
  }

  if (!bookingStartDate.value || !bookingEndDate.value) {
    toaster.warning('Vui lòng chọn thời gian nhận và trả xe!');
    return;
  }

  const start = new Date(bookingStartDate.value);
  const end = new Date(bookingEndDate.value);
  if (end <= start) {
    toaster.warning('Thời gian trả xe phải sau thời gian nhận xe ít nhất 1 giờ!');
    return;
  }

  bookingLoading.value = true;
  try {
    const payload = {
      car_id: bookingTargetCar.value.id,
      start_date: toLocalISO(bookingStartDate.value),
      end_date: toLocalISO(bookingEndDate.value),
      dia_chi_nhan_xe: bookingLocation.value || 'Showroom DriveRent',
      dia_chi_tra_xe: bookingLocation.value || 'Showroom DriveRent',
      ly_do_thue: 'Đặt xe trực tuyến qua DriveRent Website'
    };

    const res = await baseRequest.post('user/bookings/dat-lich', payload);
    if (res.data && res.data.status) {
      toaster.success('Đặt xe thành công!');
      bookingSuccessData.value = {
        ...res.data.data,
        carName: bookingTargetCar.value.name,
        price: modalTotalCost.value,
        startDate: bookingStartDate.value,
        endDate: bookingEndDate.value,
        address: bookingLocation.value
      };

      // Notify ChatWidget
      window.dispatchEvent(new CustomEvent('show-booking-invoice', {
        detail: { booking: res.data.data }
      }));

      closeBookingModal();
    } else {
      toaster.error(res.data?.message || 'Đặt xe thất bại');
    }
  } catch (error) {
    console.error('Booking submission error:', error);
    if (error.response?.status === 409 || error.response?.data?.error_type === 'SCHEDULE_CONFLICT') {
      const conflictMsg = error.response?.data?.message || 'Xe vừa được khách hàng khác đặt trong khung giờ này. Quý khách vui lòng chọn khung giờ khác hoặc đổi xe phù hợp!';
      toaster.warning(conflictMsg, { duration: 6000 });
      fetchCars();
    } else {
      toaster.error(error.response?.data?.message || 'Có lỗi xảy ra khi gửi yêu cầu thuê xe!');
    }
  } finally {
    bookingLoading.value = false;
  }
};

onMounted(async () => {
  fetchCars();
  fetchCategories();
  fetchProvinces();

  // Deep linking check if car_id is in query
  if (route.query.car_id) {
    const target = cars.value.find(c => c.id == route.query.car_id);
    if (target) {
      openBookingModal(target);
    }
  }
});
</script>

<style scoped>
.home-page {
  position: relative;
  background-color: #0b1220;
  color: #f8fafc;
  min-height: 100vh;
  overflow-x: hidden;
}

/* App container helper */
.app-container {
  max-width: 1280px;
  margin: 0 auto;
  padding-left: 24px;
  padding-right: 24px;
}

/* FAQ Section */
.home-faq-section {
  position: relative;
  z-index: 10;
  padding: 40px 0 100px 0;
}

.faq-heading-row {
  text-align: center;
  max-width: 640px;
  margin: 0 auto 48px auto;
}

.eyebrow-text {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #10b981;
  margin-bottom: 12px;
}

.section-title {
  font-size: clamp(2rem, 3.5vw, 2.75rem);
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
  margin-bottom: 12px;
}

.section-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

.faq-list-grid {
  max-width: 840px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.faq-card {
  background: rgba(17, 26, 46, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: all 260ms cubic-bezier(0.4, 0, 0.2, 1);
}

.faq-card:hover {
  border-color: rgba(16, 185, 129, 0.3);
  background: rgba(17, 26, 46, 0.85);
}

.faq-card.is-open {
  border-color: rgba(16, 185, 129, 0.5);
  background: rgba(17, 26, 46, 0.95);
  box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.4);
}

.faq-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: none;
  border: none;
  color: #f8fafc;
  font-size: 1.05rem;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
  gap: 16px;
}

.faq-chevron {
  font-size: 0.85rem;
  color: #94a3b8;
  transition: transform 260ms ease;
}

.faq-card.is-open .faq-chevron {
  transform: rotate(180deg);
  color: #10b981;
}

.faq-body {
  padding: 0 24px 22px 24px;
  font-size: 0.95rem;
  line-height: 1.7;
  color: #94a3b8;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
  padding-top: 16px;
}

/* Modals General Backdrop */
.modal-backdrop-dark {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(4, 8, 16, 0.82);
  backdrop-filter: blur(14px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: modalFadeIn 220ms ease;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Booking Modal Card */
.booking-modal-card {
  width: 100%;
  max-width: 620px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 26px;
  box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.8);
  overflow: hidden;
  animation: modalScaleUp 280ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScaleUp {
  from { opacity: 0; transform: scale(0.94) translateY(12px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 28px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(17, 26, 46, 0.5);
}

.head-eyebrow {
  display: block;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #10b981;
  margin-bottom: 4px;
}

.head-title-wrap h3 {
  font-size: 1.35rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.btn-close-modal {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 180ms ease;
}

.btn-close-modal:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}

.modal-body-content {
  padding: 24px 28px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.modal-car-preview {
  position: relative;
  height: 160px;
  background: radial-gradient(circle at center, rgba(16, 185, 129, 0.12) 0%, transparent 70%), #111a2e;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 12px;
}

.modal-car-preview img {
  max-width: 85%;
  max-height: 120px;
  object-fit: contain;
  filter: drop-shadow(0 14px 20px rgba(0, 0, 0, 0.5));
}

.car-badge-info {
  position: absolute;
  bottom: 12px;
  left: 14px;
  right: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cat-pill {
  padding: 4px 12px;
  background: rgba(11, 18, 32, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
}

.rate-text {
  font-size: 0.9rem;
  font-weight: 700;
  color: #10b981;
}

.booking-config-fields {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.field-item label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #94a3b8;
  margin-bottom: 6px;
}

.field-item label i {
  color: #10b981;
}

.modal-input {
  width: 100%;
  padding: 12px 16px;
  background: rgba(17, 26, 46, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #f8fafc;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 180ms ease;
}

.modal-input:focus {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.fields-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.price-calculation-card {
  background: rgba(17, 26, 46, 0.6);
  border: 1px dashed rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  padding: 16px 20px;
}

.calc-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
  margin-bottom: 8px;
}

.calc-row strong {
  color: #f8fafc;
}

.calc-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 10px 0;
}

.calc-row.grand-total {
  font-size: 1.05rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 4px;
}

.price-total {
  font-size: 1.25rem;
  font-weight: 800;
  color: #10b981;
}

.note-text {
  font-size: 0.75rem;
  color: #64748b;
  display: block;
}

.modal-footer-actions {
  padding: 18px 28px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(17, 26, 46, 0.5);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 14px;
}

.btn-cancel-modal {
  padding: 12px 22px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  color: #94a3b8;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 180ms ease;
}

.btn-cancel-modal:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.btn-confirm-booking {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 28px;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  border: none;
  border-radius: 999px;
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4);
  transition: all 220ms ease;
}

.btn-confirm-booking:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px -4px rgba(16, 185, 129, 0.55);
}

.btn-confirm-booking:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Receipt Modal */
.receipt-modal-card {
  width: 100%;
  max-width: 520px;
  background: #0f172a;
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 26px;
  box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.8);
  padding: 32px 28px;
  text-align: center;
  animation: modalScaleUp 280ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.success-ring {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px auto;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.15);
  border: 2px solid #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  color: #10b981;
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.35);
}

.success-header h3 {
  font-size: 1.6rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 6px;
}

.success-header p {
  font-size: 0.92rem;
  color: #94a3b8;
  margin-bottom: 24px;
}

.receipt-details-box {
  background: rgba(17, 26, 46, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  padding: 18px 20px;
  text-align: left;
  margin-bottom: 18px;
}

.receipt-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
  margin-bottom: 8px;
}

.receipt-line strong {
  color: #f8fafc;
}

.receipt-code {
  color: #10b981 !important;
  font-family: monospace;
  font-size: 1.05rem;
}

.receipt-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 12px 0;
}

.receipt-line.total-line {
  font-size: 1.05rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0;
}

.total-emerald {
  font-size: 1.25rem;
  font-weight: 800;
  color: #10b981;
}

.receipt-notice-box {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  text-align: left;
  font-size: 0.825rem;
  color: #94a3b8;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.2);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 24px;
}

.receipt-notice-box i {
  color: #10b981;
  font-size: 1rem;
  margin-top: 2px;
}

.receipt-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

/* Auth Prompt Card */
.auth-dialog-card {
  width: 100%;
  max-width: 440px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  padding: 32px 28px;
  text-align: center;
  box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.85);
  animation: modalScaleUp 280ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.auth-dialog-icon {
  width: 60px;
  height: 60px;
  margin: 0 auto 16px auto;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #10b981;
}

.auth-dialog-card h3 {
  font-size: 1.4rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 8px;
}

.auth-dialog-card p {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 24px;
}

.auth-actions-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}

.btn-auth-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 13px 24px;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  border-radius: 999px;
  color: #ffffff;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4);
  transition: all 200ms ease;
}

.btn-auth-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px -4px rgba(16, 185, 129, 0.55);
}

.btn-auth-secondary {
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  color: #f8fafc;
  font-weight: 600;
  text-decoration: none;
  transition: all 180ms ease;
}

.btn-auth-secondary:hover {
  background: rgba(255, 255, 255, 0.12);
}

.btn-close-subtle {
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.85rem;
  cursor: pointer;
  transition: color 180ms ease;
}

.btn-close-subtle:hover {
  color: #94a3b8;
}

@media (max-width: 640px) {
  .fields-two-col {
    grid-template-columns: 1fr;
  }
}
</style>
