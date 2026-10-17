<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-bg-layer">
        <div class="ambient-glow"></div>
        <img 
          src="/assets/images/hero.png" 
          class="hero-car-image" 
          alt="VinFast Electric Fleet"
        />
      </div>
      
      <div class="hero-content app-container">
        <div class="hero-text animate-up">
          <div class="eyebrow-pill">
            <span class="pulse-dot"></span>
            #1 Nền Tảng Thuê Xe Điện Thông Minh
          </div>
          <h1>Kiến Tạo Hành Trình <br/><span>Xanh & Đẳng Cấp</span></h1>
          <p>Trải nghiệm hệ sinh thái xe điện VinFast thông minh, vận hành êm ái, tối ưu chi phí và bảo vệ môi trường.</p>
          <div class="hero-btns">
            <button type="button" class="btn-primary" @click="scrollSearch">
              <span>Tìm xe ngay</span>
              <i class="fa-solid fa-arrow-down"></i>
            </button>
            <router-link to="/fleet" class="btn-secondary">
              <span>Xem toàn bộ xe</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Search / Filter Component (Double-Bezel Architecture) -->
    <section id="search-section" class="search-box-wrapper app-container">
      <div class="search-box card">
        <!-- City Selector -->
        <div class="search-field city-selector-wrapper" v-click-outside="() => showCityDropdown = false">
          <label>
            <i class="fa-solid fa-map-pin field-icon"></i>
            Tỉnh / Thành phố
          </label>
          <div class="custom-dropdown" @click.stop="toggleCityDropdown">
            <div class="selected-city" :class="{ 'placeholder': !searchData.city }">
              <span class="city-name">
                {{ loadingProvinces ? 'Đang tải...' : (provinces.find(c => c.id === searchData.city)?.name || 'Chọn khu vực nhận xe...') }}
              </span>
              <i v-if="loadingProvinces" class="fa-solid fa-spinner fa-spin dropdown-chevron"></i>
              <i v-else class="fa-solid fa-chevron-down dropdown-chevron" :class="{ 'rotate': showCityDropdown }"></i>
            </div>
            
            <Transition name="fade-slide">
              <div v-if="showCityDropdown" class="dropdown-list scrollable-dropdown">
                <div class="dropdown-search" @click.stop>
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input 
                    type="text" 
                    v-model="provinceSearch" 
                    placeholder="Tìm nhanh tỉnh thành..." 
                    @click.stop
                  />
                </div>
                <div class="dropdown-scroll-area">
                  <div 
                    v-for="city in filteredProvinces" 
                    :key="city.id" 
                    class="dropdown-item"
                    @click.stop="selectCity(city.id)"
                    :class="{ 'active': searchData.city === city.id }"
                  >
                    <i class="fa-solid fa-location-dot"></i> {{ city.name }}
                  </div>
                  <div v-if="filteredProvinces.length === 0" class="no-results">
                    Không tìm thấy khu vực phù hợp
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div class="search-separator"></div>

        <!-- Detailed Address Input -->
        <div class="search-field flex-grow-1 relative">
          <label>
            <i class="fa-solid fa-location-crosshairs field-icon"></i>
            Địa chỉ nhận xe chi tiết
          </label>
          <div class="input-with-icon premium-input-wrapper">
            <input 
              type="text" 
              v-model="searchData.address" 
              @input="onAddressInput"
              @focus="showPopular = true"
              @blur="setTimeout(() => showPopular = false, 250)"
              :placeholder="searchData.city ? 'Ví dụ: Sân bay, khách sạn, số nhà...' : 'Vui lòng chọn Tỉnh/Thành phố trước'" 
              :disabled="!searchData.city"
              :class="{ 'disabled-field': !searchData.city }"
            />
            <div v-if="loadingSuggestions" class="loader-inline">
              <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
          </div>

          <!-- Suggestions Dropdown -->
          <Transition name="fade-slide">
            <div v-if="suggestions.length > 0 || (showPopular && !searchData.address && filteredPopularLocations.length > 0)" class="suggestions-list">
              <div v-if="suggestions.length > 0">
                <div class="pop-title"><i class="fa-solid fa-magnifying-glass"></i> Địa chỉ gợi ý</div>
                <li v-for="(item, index) in suggestions" :key="index" @click="selectAddress(item)" class="suggestion-item">
                  <div class="suggestion-icon"><i class="fa-solid fa-location-dot"></i></div>
                  <div class="suggestion-content">
                    <span class="main-text">{{ item.main_text }}</span>
                    <span class="sub-text">{{ item.sub_text }}</span>
                  </div>
                </li>
              </div>
              <div v-else-if="showPopular" class="popular-locations">
                <div class="pop-title"><i class="fa-solid fa-star"></i> Vị trí phổ biến</div>
                <li v-for="loc in filteredPopularLocations" :key="loc" @click="selectPopular(loc)" class="suggestion-item">
                  <div class="suggestion-icon">
                    <i class="fa-solid fa-plane-departure" v-if="loc.includes('Sân bay')"></i>
                    <i class="fa-solid fa-train" v-else-if="loc.includes('Ga')"></i>
                    <i class="fa-solid fa-map-pin" v-else></i>
                  </div>
                  <div class="suggestion-content">
                    <span class="main-text">{{ loc }}</span>
                  </div>
                </li>
              </div>
            </div>
          </Transition>
        </div>

        <div class="search-separator"></div>

        <!-- Pickup Date -->
        <div class="search-field date-field">
          <label>
            <i class="fa-solid fa-calendar-plus field-icon"></i>
            Thời gian lấy xe
          </label>
          <VueDatePicker 
            v-model="searchData.startDate" 
            :min-date="new Date()"
            placeholder="Chọn ngày giờ lấy"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>

        <div class="search-separator"></div>

        <!-- Return Date -->
        <div class="search-field date-field">
          <label>
            <i class="fa-solid fa-calendar-check field-icon"></i>
            Thời gian trả xe
          </label>
          <VueDatePicker 
            v-model="searchData.endDate" 
            :min-date="searchData.startDate || new Date()"
            placeholder="Chọn ngày giờ trả"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>

        <!-- Submit Button -->
        <button class="btn-primary search-btn" @click="handleSearch" :disabled="loading">
          <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
          <span v-else>Tìm Kiếm</span>
        </button>
      </div>
      
      <!-- Field Error Notice -->
      <div v-if="dateError" class="field-error-banner animate-in">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ dateError }}</span>
      </div>
    </section>

    <!-- Car Listing Results Section -->
    <section id="results-section" v-if="hasSearched" class="fleet-section app-container">
      <div class="section-heading">
        <span class="sub-title">Đội ngũ xe điện VinFast</span>
        <h2>Danh Sách Xe Phù Hợp</h2>
        <p>Những mẫu xe điện hiện đại, bền bỉ và sẵn sàng cho chuyến đi của bạn</p>
      </div>

      <!-- Cars Grid -->
      <div class="car-grid" v-if="!loading && cars.length > 0">
        <div class="car-card card-interactive card" v-for="car in cars" :key="car.id">
          <!-- Car Image -->
          <div class="car-img-container">
            <img :src="car.image" :alt="car.name" loading="lazy" />
            <span class="car-category-pill">{{ car.category_name || car.type || 'SUV' }}</span>
          </div>

          <!-- Car Details -->
          <div class="car-info">
            <div class="car-header">
              <h3 class="car-name">{{ car.name }}</h3>
              <span class="city-badge">
                <i class="fa-solid fa-location-dot"></i> {{ car.cityDisplay || car.city }}
              </span>
            </div>
            
            <div class="car-specs-minimal">
              <div class="spec-item" title="Dung lượng pin">
                <i class="fa-solid fa-battery-full"></i>
                <span>{{ car.battery || 'Pin EV chuẩn' }}</span>
              </div>
              <div class="spec-item" title="Số chỗ ngồi">
                <i class="fa-solid fa-users"></i>
                <span>{{ car.seats || 5 }} chỗ</span>
              </div>
              <div class="spec-item" title="Động cơ điện">
                <i class="fa-solid fa-bolt"></i>
                <span>Thuần điện 100%</span>
              </div>
            </div>
            
            <div class="car-footer">
              <div class="price-box">
                <span class="label">Đơn giá</span>
                <span class="price">{{ Number(car.price_per_day).toLocaleString('vi-VN') }}đ</span>
                <span class="unit">/ ngày</span>
              </div>
              <button 
                type="button" 
                class="btn-vinfast" 
                @click="toggleInlineBooking(car)"
              >
                {{ expandingCarId === car.id ? 'Thu gọn' : 'Đặt xe ngay' }}
              </button>
            </div>

            <!-- Inline Booking Card -->
            <Transition name="fade-slide">
              <div v-if="expandingCarId === car.id" class="inline-booking-card animate-in">
                <div class="inline-header">
                  <h4><i class="fa-solid fa-calendar-check"></i> Xác nhận lịch trình thuê</h4>
                </div>
                
                <div class="inline-summary">
                  <div class="sum-row">
                    <span>Điểm nhận/trả xe:</span>
                    <strong>{{ searchData.address || 'Tại showroom' }}</strong>
                  </div>
                  <div class="sum-row">
                    <span>Thời gian thuê:</span>
                    <strong>{{ formatDate(searchData.startDate) }} - {{ formatDate(searchData.endDate) }}</strong>
                  </div>
                  <div class="sum-row highlight">
                    <span>Tổng thời gian:</span>
                    <strong>{{ calculateTotal(car) }} ngày</strong>
                  </div>
                  <div class="sum-row total">
                    <span>Tổng tiền tạm tính:</span>
                    <span class="total-price-inline">{{ (calculateTotal(car) * car.price_per_day).toLocaleString('vi-VN') }} đ</span>
                  </div>
                  <small class="calc-note">* Giá tạm tính chưa bao gồm ưu đãi nếu có. Đơn sẽ được ghi nhận sau khi xác nhận.</small>
                </div>

                <button 
                  type="button"
                  class="btn-primary w-full mt-3" 
                  :disabled="bookingLoading"
                  @click="confirmBooking(car)"
                >
                  <i v-if="bookingLoading" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else>Xác nhận đặt đơn</span>
                </button>
              </div>
            </Transition>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="!loading && cars.length === 0" class="empty-state-card card animate-in">
        <div class="empty-layout">
          <div class="empty-icon-wrap">
            <i class="fa-solid fa-car-burst"></i>
          </div>
          <div class="empty-text">
            <h3>Chưa có xe khả dụng tại {{ provinces.find(p => p.id === searchData.city)?.name || 'khu vực này' }}</h3>
            <p>Hiện tại xe tại khu vực này đã được đặt kín hoặc đang bảo dưỡng. Quý khách vui lòng chọn thành phố khác hoặc liên hệ hotline để được hỗ trợ điều xe.</p>
            <div class="empty-actions">
              <button class="btn-primary" @click="handleResetSearch">
                <i class="fa-solid fa-rotate-left"></i> Đặt lại tìm kiếm
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách phương tiện...</p>
      </div>
    </section>

    <!-- Rental Process Section -->
    <section id="process-section" class="process-section app-container">
      <div class="section-heading text-center">
        <span class="sub-title">Quy trình tối giản</span>
        <h2>Thuê Xe Nhanh Chóng Với 4 Bước</h2>
        <p>Quy trình tinh gọn, số hóa hoàn toàn từ bước chọn xe đến khi lăn bánh</p>
      </div>

      <div class="process-grid">
        <div class="process-item card">
          <div class="step-num">01</div>
          <div class="icon-box green">
            <i class="fa-solid fa-car"></i>
          </div>
          <h3>1. Chọn mẫu xe</h3>
          <p>Dễ dàng chọn lựa mẫu xe điện VinFast phù hợp với số chỗ và nhu cầu hành trình của bạn.</p>
        </div>

        <div class="process-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="process-item card">
          <div class="step-num">02</div>
          <div class="icon-box blue">
            <i class="fa-solid fa-calendar-days"></i>
          </div>
          <h3>2. Chọn lịch & điểm nhận</h3>
          <p>Tùy chọn thời gian nhận xe và địa chỉ giao xe tận nơi một cách linh hoạt.</p>
        </div>

        <div class="process-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="process-item card">
          <div class="step-num">03</div>
          <div class="icon-box yellow">
            <i class="fa-solid fa-qrcode"></i>
          </div>
          <h3>3. Xác nhận & Chuyển khoản</h3>
          <p>Thực hiện thanh toán an toàn qua QR code và tải biên lai lên hệ thống.</p>
        </div>

        <div class="process-arrow"><i class="fa-solid fa-arrow-right"></i></div>

        <div class="process-item card">
          <div class="step-num">04</div>
          <div class="icon-box red">
            <i class="fa-solid fa-key"></i>
          </div>
          <h3>4. Nhận xe & Lên đường</h3>
          <p>Nhận bàn giao xe tại điểm hẹn và an tâm tận hưởng hành trình êm ái.</p>
        </div>
      </div>
    </section>

    <!-- FAQ Accordion Section -->
    <section class="faq-section app-container">
      <div class="section-heading text-center">
        <span class="sub-title">Câu hỏi thường gặp</span>
        <h2>Thắc Mắc Khi Thuê Xe Điện?</h2>
        <p>Những thông tin cần biết để hành trình của bạn thêm trọn vẹn</p>
      </div>

      <div class="faq-list">
        <div 
          v-for="(faq, index) in faqs" 
          :key="index" 
          class="faq-item card" 
          :class="{ 'open': openFaqIndex === index }"
        >
          <button class="faq-question" @click="toggleFaq(index)" :aria-expanded="openFaqIndex === index">
            <span>{{ faq.question }}</span>
            <i class="fa-solid fa-chevron-down faq-chevron"></i>
          </button>
          <div class="faq-answer" v-show="openFaqIndex === index">
            <p>{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Booking Success Modal (In-Page Feedback) -->
    <div v-if="bookingSuccessData" class="modal-overlay" @click.self="bookingSuccessData = null">
      <div class="modal-content card animate-in">
        <div class="success-modal-header">
          <div class="success-icon-badge">
            <i class="fa-solid fa-check"></i>
          </div>
          <h3>Đặt Xe Thành Công!</h3>
          <p>Yêu cầu thuê xe của bạn đã được tiếp nhận trên hệ thống.</p>
        </div>

        <div class="booking-receipt-box">
          <div class="receipt-row">
            <span>Mã đơn đặt:</span>
            <strong class="receipt-code">#{{ bookingSuccessData.ma_dat_xe }}</strong>
          </div>
          <div class="receipt-row">
            <span>Phương tiện:</span>
            <strong>{{ bookingSuccessData.carName }}</strong>
          </div>
          <div class="receipt-row">
            <span>Thời gian nhận:</span>
            <span>{{ formatDate(bookingSuccessData.startDate) }}</span>
          </div>
          <div class="receipt-row">
            <span>Thời gian trả:</span>
            <span>{{ formatDate(bookingSuccessData.endDate) }}</span>
          </div>
          <div class="receipt-row">
            <span>Địa chỉ nhận xe:</span>
            <span>{{ bookingSuccessData.address || 'Tại Showroom' }}</span>
          </div>
          <div class="receipt-divider"></div>
          <div class="receipt-row total">
            <span>Tổng số tiền:</span>
            <strong class="text-primary">{{ Number(bookingSuccessData.tong_tien || bookingSuccessData.price).toLocaleString('vi-VN') }} đ</strong>
          </div>
        </div>

        <div class="next-step-box">
          <i class="fa-solid fa-circle-info"></i>
          <span>Bạn có thể kiểm tra hóa đơn và tải biên lai thanh toán tại mục <strong>Chuyến đi của tôi</strong> hoặc liên hệ trợ lý AI 24/7.</span>
        </div>

        <div class="modal-footer flex-between">
          <button type="button" class="btn-secondary" @click="bookingSuccessData = null">
            Đóng
          </button>
          <router-link to="/my-bookings" class="btn-primary">
            <span>Xem chuyến đi</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Auth Prompt Dialog -->
    <div v-if="showAuthPrompt" class="modal-overlay" @click.self="showAuthPrompt = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <h3>Yêu Cầu Đăng Nhập</h3>
          <button type="button" class="btn-close" @click="showAuthPrompt = false">&times;</button>
        </div>
        <div class="modal-body">
          <p class="mb-4">Vui lòng đăng nhập hoặc tạo tài khoản để hoàn tất thủ tục thuê xe và lưu thông tin hợp đồng của bạn.</p>
          <div class="auth-buttons-grid">
            <router-link to="/login" class="btn-primary text-center">
              Đăng nhập tài khoản
            </router-link>
            <router-link to="/register" class="btn-secondary text-center">
              Đăng ký tài khoản mới
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch, nextTick, computed, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const route = useRoute();

// Custom directive for clicking outside
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener("click", el.clickOutsideEvent);
  },
};

// Accurate Image Mapping
const CAR_IMAGE_MAP = {
    'xpander': '/assets/images/cars/xpander.png',
    'camry': '/assets/images/cars/camry.png',
    'audi a6': '/assets/images/cars/audi_a6.png',
    'mercedes': '/assets/images/cars/mercedes_c200.png',
    'santafe': '/assets/images/cars/santafe.png',
    'mazda cx-5': '/assets/images/cars/cx5.png',
    'vf8': '/assets/images/cars/vf8.png',
    'vf7': '/assets/images/cars/vf7.png',
    'vf9': '/assets/images/vf9.png',
    'vf 7': '/assets/images/cars/vf7.png',
    'vf 8': '/assets/images/cars/vf8.png',
    'vf 9': '/assets/images/vf9.png'
};

const cars = ref([]);
const hasSearched = ref(false);
const loading = ref(false);
const dateError = ref('');
const suggestions = ref([]);
const loadingSuggestions = ref(false);
const expandingCarId = ref(null);
const selectedCar = ref(null);
const bookingLoading = ref(false);
const showPopular = ref(false);
const bookingSuccessData = ref(null);
const showAuthPrompt = ref(false);

const POPULAR_LOCATIONS_MAP = {
    'Hà Nội': ['Sân bay Nội Bài', 'Hồ Hoàn Kiếm', 'Ga Hà Nội', 'Lăng Bác', 'Văn Miếu'],
    'Hồ Chí Minh': ['Sân bay Tân Sơn Nhất', 'Dinh Độc Lập', 'Ga Sài Gòn', 'Chợ Bến Thành', 'Phố đi bộ Nguyễn Huệ'],
    'Đà Nẵng': ['Sân bay Đà Nẵng', 'Ga Đà Nẵng', 'Cầu Rồng', 'Bán đảo Sơn Trà', 'Bà Nà Hills'],
    'Khánh Hòa': ['Sân bay Cam Ranh', 'Ga Nha Trang', 'Tháp Bà Ponagar', 'Vinpearl Nha Trang'],
    'Cần Thơ': ['Sân bay Cần Thơ', 'Bến Ninh Kiều', 'Chợ nổi Cái Răng'],
    'Lâm Đồng': ['Sân bay Liên Khương', 'Hồ Xuân Hương', 'Chợ Đà Lạt']
};

const showCityDropdown = ref(false);
const provinceSearch = ref('');
const provinces = ref([]);
const loadingProvinces = ref(false);

const openFaqIndex = ref(0);
const faqs = [
  {
    question: "Tôi cần chuẩn bị những giấy tờ gì khi nhận xe điện?",
    answer: "Quý khách chỉ cần chuẩn bị Căn cước công dân gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B1/B2 còn hiệu lực để đối chiếu thông tin khi giao nhận xe."
  },
  {
    question: "Xe điện có được sạc đầy pin khi giao không?",
    answer: "Tất cả các dòng xe DriveRent đều được sạc đầy tối thiểu 80% - 100% pin trước khi giao tới khách hàng, đảm bảo quãng đường di chuyển thoải mái."
  },
  {
    question: "Tôi có thể sạc pin xe điện VinFast ở đâu?",
    answer: "Quý khách có thể sạc tại hàng ngàn trạm sạc công cộng của VinFast trên toàn quốc (tại các trạm dừng chân, cây xăng, TTTM Vincom) hoặc sạc tại nhà qua bộ sạc di động kèm theo xe."
  },
  {
    question: "Chính sách hủy lịch thuê xe như thế nào?",
    answer: "Quý khách có thể hủy lịch miễn phí trước 24 giờ so với thời điểm bắt đầu thuê. Sau khoảng thời gian này, vui lòng liên hệ bộ phận hỗ trợ để được giải quyết theo quy định."
  },
  {
    question: "Hình thức thanh toán và đặt cọc như thế nào?",
    answer: "Hệ thống hỗ trợ chuyển khoản ngân hàng qua mã VietQR tiện lợi. Quý khách chuyển khoản thanh toán và tải biên lai lên hệ thống để hoàn tất đơn hàng."
  }
];

const toggleFaq = (index) => {
  openFaqIndex.value = openFaqIndex.value === index ? -1 : index;
};

const filteredProvinces = computed(() => {
  if (!provinceSearch.value) return provinces.value;
  const query = provinceSearch.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  return provinces.value.filter(p => {
    const name = p.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return name.includes(query);
  });
});

const filteredPopularLocations = computed(() => {
  const cityName = provinces.value.find(p => p.id === searchData.city)?.name || '';
  if (!cityName) return [];
  
  for (const cityKey in POPULAR_LOCATIONS_MAP) {
    if (cityName.includes(cityKey)) return POPULAR_LOCATIONS_MAP[cityKey];
  }
  return ['Trung tâm thành phố', 'Sân bay địa phương', 'Ga tàu trung tâm'];
});

const searchData = reactive({
  city: '',
  address: '',
  startDate: new Date(),
  endDate: new Date(new Date().getTime() + 24 * 60 * 60 * 1000)
});

const fetchProvinces = async () => {
  loadingProvinces.value = true;
  try {
    const response = await axios.get('https://provinces.open-api.vn/api/p/');
    provinces.value = response.data.map(p => ({
      id: p.code.toString(),
      name: p.name
    }));
  } catch (error) {
    console.error("Error fetching provinces:", error);
    provinces.value = [
      { id: 'Hồ Chí Minh', name: 'TP. Hồ Chí Minh' },
      { id: 'Hà Nội', name: 'Hà Nội' },
      { id: 'Đà Nẵng', name: 'Đà Nẵng' }
    ];
  } finally {
    loadingProvinces.value = false;
  }
};

const toggleCityDropdown = () => {
  showCityDropdown.value = !showCityDropdown.value;
};

const selectCity = (cityId) => {
  searchData.city = cityId;
  showCityDropdown.value = false;
};

let timeout = null;

const onAddressInput = (e) => {
  const query = e.target.value;
  if (query.length < 3) {
    suggestions.value = [];
    return;
  }

  clearTimeout(timeout);
  timeout = setTimeout(async () => {
    loadingSuggestions.value = true;
    try {
      const cityName = provinces.value.find(p => p.id === searchData.city)?.name || '';
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + (cityName ? ' ' + cityName : ''))}&addressdetails=1&limit=5&countrycodes=vn&accept-language=vi`);
      const data = await res.json();
      
      if (data && data.length > 0) {
        suggestions.value = data.map(item => {
          const addr = item.address;
          const mainName = addr.amenity || addr.building || addr.road || addr.suburb || item.display_name.split(',')[0];
          const subParts = [];
          if (addr.suburb) subParts.push(addr.suburb);
          if (addr.city || addr.town) subParts.push(addr.city || addr.town);
          if (addr.state) subParts.push(addr.state);
          
          return {
            display_name: item.display_name,
            main_text: mainName,
            sub_text: subParts.length > 0 ? subParts.join(', ') : item.display_name.split(',').slice(1, 3).join(', ')
          };
        });
      } else {
        suggestions.value = [];
      }
    } catch (err) {
      console.error("Map API Error:", err);
    } finally {
      loadingSuggestions.value = false;
    }
  }, 450);
};

const selectAddress = (item) => {
  searchData.address = item.display_name;
  suggestions.value = [];
  showPopular.value = false;
};

const selectPopular = (loc) => {
  searchData.address = loc;
  showPopular.value = false;
};

const toLocalISO = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const offset = d.getTimezoneOffset() * 60000;
  const localDate = new Date(d.getTime() - offset);
  return localDate.toISOString().slice(0, 19).replace('T', ' ');
};

const scrollSearch = () => {
  nextTick(() => {
    const el = document.getElementById('search-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

const scrollResults = () => {
  nextTick(() => {
    const el = document.getElementById('results-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

const fetchCars = async (params = {}) => {
  loading.value = true;
  try {
    const response = await baseRequest.get('client/car/data', { params });
    if (response.data && response.data.status) {
      const backendCars = response.data.data;
      if (backendCars && backendCars.length > 0) {
        cars.value = backendCars.map(car => {
          const modelName = (car.ten_xe || car.name || '').toLowerCase();
          let premiumImage = car.hinh_anh;
          
          for (const [key, path] of Object.entries(CAR_IMAGE_MAP)) {
            if (modelName.includes(key)) {
              premiumImage = path;
              break;
            }
          }

          return {
            ...car,
            name: car.ten_xe || car.name || 'VinFast Electric',
            price_per_day: car.gia_thue || car.price_per_day || 1000000,
            image: premiumImage ? (premiumImage.startsWith('/') ? premiumImage : `/assets/images/${premiumImage}`) : '/assets/images/vf9.png',
            cityDisplay: (() => {
              const c = (car.city || '').toLowerCase();
              if (c === 'sg') return 'TP. Hồ Chí Minh';
              if (c === 'hn') return 'Hà Nội';
              if (c === 'dn') return 'Đà Nẵng';
              if (c === 'nt') return 'Nha Trang';
              return car.city || 'Toàn quốc';
            })()
          };
        });
      } else {
        cars.value = [];
      }
    }
  } catch (error) {
    console.error("Error fetching cars:", error);
    toaster.error("Không thể tải danh sách xe từ máy chủ!");
  } finally {
    loading.value = false;
  }
};

const isLocationValid = (carOrCity, addressInput = null) => {
  const addr = addressInput || searchData.address;
  if (!addr) return false;
  
  const targetCity = carOrCity.cityDisplay || carOrCity.city || carOrCity.name || '';
  if (!targetCity) return true;

  const cleanStr = (str) => {
    return str.toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/^(thanh pho|tinh|huyen|quan|phuong|xa)\s+/g, "")
      .trim();
  };

  const query = cleanStr(addr);
  const city = cleanStr(targetCity);
  
  if (query.includes(city) || city.includes(query)) return true;

  const normalize = (s) => {
    if (s.includes("ho chi minh") || s === "sg" || s.includes("sai gon")) return "hcm";
    if (s.includes("ha noi") || s === "hn") return "hn";
    if (s.includes("da nang") || s === "dn") return "dn";
    if (s.includes("nha trang") || s === "nt" || s.includes("khanh hoa")) return "khanhhoa";
    if (s.includes("da lat") || s.includes("lam dong")) return "lamdong";
    return s;
  };

  const normQuery = normalize(query);
  const normCity = normalize(city);
  
  if (normQuery.includes(normCity) || normCity.includes(normQuery)) return true;

  const cityAliases = {
    'khanhhoa': ['nha trang', 'cam ranh', 'dien khanh'],
    'lamdong': ['da lat', 'bao loc', 'duc trong'],
    'hcm': ['sai gon', 'quan 1', 'thu duc', 'go vap', 'binh thanh', 'tan son nhat'],
    'dn': ['ngu hanh son', 'son tra', 'hai chau', 'thanh khe'],
    'hn': ['hoan kiem', 'ba dinh', 'tay ho', 'dong da', 'cau giay', 'noi bai']
  };

  const aliases = cityAliases[normCity] || [];
  return aliases.some(alias => query.includes(alias));
};

const handleSearch = () => {
  if (!searchData.startDate || !searchData.endDate) {
    dateError.value = 'Vui lòng chọn thời gian nhận và trả xe!';
    return;
  }
  if (searchData.endDate <= searchData.startDate) {
    dateError.value = 'Thời gian trả xe phải sau thời gian lấy xe ít nhất 1 giờ!';
    return;
  }

  const cityName = provinces.value.find(p => p.id === searchData.city)?.name || '';
  
  if (searchData.address && cityName && !isLocationValid({ city: cityName }, searchData.address)) {
    dateError.value = `Địa chỉ chi tiết cần nằm trong khu vực ${cityName}!`;
    return;
  }

  dateError.value = '';

  const params = {
    city: cityName,
    start_date: toLocalISO(searchData.startDate),
    end_date: toLocalISO(searchData.endDate),
    search: ''
  };
  
  hasSearched.value = true;
  fetchCars(params);
  scrollResults();
};

const handleResetSearch = () => {
  searchData.city = '';
  searchData.address = '';
  searchData.startDate = new Date();
  searchData.endDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
  hasSearched.value = false;
  cars.value = [];
  dateError.value = '';
};

const toggleInlineBooking = (car) => {
  if (expandingCarId.value === car.id) {
    expandingCarId.value = null;
    selectedCar.value = null;
  } else {
    expandingCarId.value = car.id;
    selectedCar.value = car;
  }
};

const calculateTotal = (car = null) => {
  const targetCar = car || selectedCar.value;
  if (!targetCar) return 0;
  const start = new Date(searchData.startDate);
  const end = new Date(searchData.endDate);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
  return diffDays;
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const confirmBooking = async (car) => {
  const token = localStorage.getItem('token_client');
  if (!token) {
    showAuthPrompt.value = true;
    return;
  }

  if (searchData.address && !isLocationValid(car)) {
    toaster.warning(`Vui lòng nhập địa chỉ thuộc khu vực ${car.cityDisplay || car.city}!`);
    return;
  }
  
  bookingLoading.value = true;
  try {
    const res = await baseRequest.post('user/bookings/dat-lich', {
      car_id: car.id,
      start_date: toLocalISO(searchData.startDate),
      end_date: toLocalISO(searchData.endDate),
      dia_chi_nhan_xe: searchData.address || 'Tại showroom',
      dia_chi_tra_xe: searchData.address || 'Tại showroom',
      ly_do_thue: 'Đặt xe qua website DriveRent'
    });

    if (res.data.status) {
      toaster.success("Đặt xe thành công!");
      bookingSuccessData.value = {
        ...res.data.data,
        carName: car.name,
        price: calculateTotal(car) * car.price_per_day,
        startDate: searchData.startDate,
        endDate: searchData.endDate,
        address: searchData.address
      };
      
      // Notify ChatWidget if user wants to view invoice there
      window.dispatchEvent(new CustomEvent('show-booking-invoice', {
        detail: { booking: res.data.data }
      }));

      expandingCarId.value = null;
    } else {
      toaster.error(res.data.message || "Đặt xe thất bại");
    }
  } catch (error) {
    console.error("Booking error:", error);
    if (error.response?.status === 409 || error.response?.data?.error_type === 'SCHEDULE_CONFLICT') {
      const conflictMsg = error.response?.data?.message || "Xe vừa được khách hàng khác đặt trong khung giờ này. Vui lòng chọn khung giờ khác hoặc đổi xe phù hợp!";
      toaster.warning(conflictMsg, { duration: 6000 });
      // Tải lại danh sách xe để cập nhật tình trạng mới nhất
      fetchCars();
    } else {
      const errorMsg = error.response?.data?.message || "Có lỗi xảy ra khi gửi yêu cầu đặt xe!";
      toaster.error(errorMsg);
    }
  } finally {
    bookingLoading.value = false;
  }
};

watch(() => searchData.endDate, (newVal) => {
  if (newVal && searchData.startDate && newVal <= searchData.startDate) {
    dateError.value = 'Thời gian trả xe phải sau thời gian lấy xe ít nhất 1 giờ!';
  } else {
    dateError.value = '';
  }
});

watch(() => searchData.city, () => {
  searchData.address = '';
  dateError.value = '';
});

onMounted(async () => {
  await fetchProvinces();

  const carId = route.query.car_id;
  const cityQuery = route.query.city;

  if (carId && cityQuery) {
    let matchedProvince = null;
    const cleanCity = cityQuery.toLowerCase();
    
    if (cleanCity === 'sg') {
      matchedProvince = provinces.value.find(p => p.name.includes('Hồ Chí Minh') || p.id === 'Hồ Chí Minh');
    } else if (cleanCity === 'hn') {
      matchedProvince = provinces.value.find(p => p.name.includes('Hà Nội') || p.id === 'Hà Nội');
    } else if (cleanCity === 'dn') {
      matchedProvince = provinces.value.find(p => p.name.includes('Đà Nẵng') || p.id === 'Đà Nẵng');
    } else {
      matchedProvince = provinces.value.find(p => p.name.toLowerCase().includes(cleanCity));
    }

    if (matchedProvince) {
      searchData.city = matchedProvince.id;
      const cityName = matchedProvince.name;
      if (cityName.includes('Hồ Chí Minh')) searchData.address = 'Quận 1, TP. Hồ Chí Minh';
      else if (cityName.includes('Hà Nội')) searchData.address = 'Hoàn Kiếm, Hà Nội';
      else if (cityName.includes('Đà Nẵng')) searchData.address = 'Hải Châu, Đà Nẵng';
      else searchData.address = cityName;

      handleSearch();
    }
  }
});
</script>

<style scoped>
/* Hero Section */
.hero-section {
  position: relative;
  min-height: 85vh;
  display: flex;
  align-items: center;
  padding: 120px 0 60px;
  background: radial-gradient(circle at 75% 40%, rgba(5, 150, 105, 0.08), transparent 50%),
              linear-gradient(180deg, #ffffff 0%, var(--bg-main) 100%);
  overflow: hidden;
}

.hero-bg-layer {
  position: absolute;
  top: 0;
  right: 0;
  width: 60%;
  height: 100%;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.hero-car-image {
  max-width: 1100px;
  width: 100%;
  object-fit: contain;
  filter: drop-shadow(0 25px 40px rgba(11, 18, 32, 0.12));
  transform: translateX(5%);
}

.hero-content {
  position: relative;
  z-index: 2;
}

.hero-text {
  max-width: 620px;
}

.eyebrow-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: 0.825rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  margin-bottom: 20px;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: var(--color-primary);
  border-radius: 50%;
  animation: pulseGlow 1.8s infinite;
}

.hero-text h1 {
  font-size: clamp(2.4rem, 5vw, 3.8rem);
  line-height: 1.15;
  margin-bottom: 20px;
  color: var(--color-text-main);
  letter-spacing: -0.03em;
}

.hero-text h1 span {
  color: var(--color-primary);
  background: var(--grad-active);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-text p {
  font-size: 1.125rem;
  color: var(--color-text-muted);
  line-height: 1.6;
  margin-bottom: 32px;
}

.hero-btns {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

/* Search Box Container */
.search-box-wrapper {
  margin-top: -36px;
  position: relative;
  z-index: 10;
}

.search-box {
  display: flex;
  align-items: center;
  background: #ffffff;
  padding: 12px 16px;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-md);
  border: 1px solid var(--color-border);
  gap: 12px;
}

.search-separator {
  width: 1px;
  height: 48px;
  background: var(--color-border);
}

.search-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 6px 12px;
}

.search-field label {
  font-size: 0.775rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
}

.field-icon {
  color: var(--color-primary);
  font-size: 0.85rem;
}

/* Custom Dropdown */
.city-selector-wrapper {
  min-width: 200px;
}

.custom-dropdown {
  position: relative;
  cursor: pointer;
}

.selected-city {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.925rem;
  border: 1px solid transparent;
  transition: all var(--duration-fast);
}

.selected-city:hover {
  background: #e2e8f0;
}

.selected-city.placeholder {
  color: var(--color-text-muted);
}

.dropdown-chevron {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  transition: transform var(--duration-fast);
}

.dropdown-chevron.rotate {
  transform: rotate(180deg);
}

.dropdown-list {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 280px;
  background: #ffffff;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-premium);
  border: 1px solid var(--color-border);
  z-index: var(--z-dropdown);
  padding: 10px;
}

.dropdown-search {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-sm);
  margin-bottom: 8px;
}

.dropdown-search input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.875rem;
  width: 100%;
}

.dropdown-scroll-area {
  max-height: 240px;
  overflow-y: auto;
}

.dropdown-item {
  padding: 10px 12px;
  border-radius: var(--radius-xs);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background var(--duration-fast);
  display: flex;
  align-items: center;
  gap: 8px;
}

.dropdown-item:hover, .dropdown-item.active {
  background: var(--color-primary-light);
  color: var(--color-primary);
  font-weight: 600;
}

.no-results {
  padding: 16px;
  text-align: center;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

/* Detailed Address */
.flex-grow-1 {
  flex: 1;
}

.premium-input-wrapper {
  position: relative;
}

.premium-input-wrapper input {
  width: 100%;
  padding: 8px 12px;
  background: var(--bg-surface-sub);
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  font-weight: 500;
  font-size: 0.925rem;
  outline: none;
  transition: all var(--duration-fast);
}

.premium-input-wrapper input:focus {
  background: #ffffff;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.disabled-field {
  cursor: not-allowed;
  opacity: 0.7;
}

.loader-inline {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-primary);
}

/* Suggestions Dropdown */
.suggestions-list {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: #ffffff;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-premium);
  border: 1px solid var(--color-border);
  z-index: var(--z-dropdown);
  padding: 12px;
  list-style: none;
  max-height: 280px;
  overflow-y: auto;
}

.pop-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--color-text-muted);
  padding: 4px 8px 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.suggestion-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background var(--duration-fast);
}

.suggestion-item:hover {
  background: var(--bg-surface-sub);
}

.suggestion-icon {
  color: var(--color-primary);
  margin-top: 2px;
}

.suggestion-content {
  display: flex;
  flex-direction: column;
}

.main-text {
  font-weight: 600;
  font-size: 0.885rem;
  color: var(--color-text-main);
}

.sub-text {
  font-size: 0.785rem;
  color: var(--color-text-muted);
}

/* Date Field */
.date-field {
  min-width: 170px;
}

.search-btn {
  padding: 14px 28px;
  font-size: 1rem;
  border-radius: var(--radius-lg);
}

/* Field Error Banner */
.field-error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 20px;
  background: var(--color-danger-bg);
  color: var(--color-danger);
  border: 1px solid var(--color-danger-border);
  border-radius: var(--radius-md);
  margin-top: 14px;
  font-size: 0.885rem;
  font-weight: 600;
}

/* Section Headings */
.section-heading {
  margin-bottom: 48px;
}

.section-heading.text-center {
  text-align: center;
}

.sub-title {
  display: inline-block;
  font-size: 0.825rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-primary);
  margin-bottom: 8px;
}

.section-heading h2 {
  font-size: clamp(1.8rem, 3.5vw, 2.6rem);
  letter-spacing: -0.02em;
  margin-bottom: 12px;
}

.section-heading p {
  font-size: 1.05rem;
  max-width: 600px;
  margin: 0 auto;
}

/* Car Listing Cards */
.fleet-section {
  padding-top: 80px;
}

.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 32px;
}

.car-card {
  padding: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.car-img-container {
  position: relative;
  background: linear-gradient(180deg, #f8fafc 0%, #eef2f6 100%);
  padding: 32px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 16 / 9;
}

.car-img-container img {
  max-height: 100%;
  object-fit: contain;
  transition: transform var(--duration-slow) var(--ease-spring);
}

.car-card:hover .car-img-container img {
  transform: scale(1.05);
}

.car-category-pill {
  position: absolute;
  top: 16px;
  left: 16px;
  background: #ffffff;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-main);
  box-shadow: var(--shadow-xs);
}

.car-info {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.car-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.car-name {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.city-badge {
  font-size: 0.775rem;
  font-weight: 600;
  color: var(--color-primary);
  background: var(--color-primary-light);
  padding: 4px 10px;
  border-radius: var(--radius-xs);
  display: flex;
  align-items: center;
  gap: 4px;
}

.car-specs-minimal {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border-subtle);
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--color-text-muted);
  font-weight: 500;
}

.spec-item i {
  color: var(--color-primary);
}

.car-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.price-box {
  display: flex;
  flex-direction: column;
}

.price-box .label {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.price-box .price {
  font-size: 1.35rem;
  font-weight: 800;
  font-family: var(--font-heading);
  color: var(--color-text-main);
}

.price-box .unit {
  font-size: 0.775rem;
  color: var(--color-text-muted);
}

.btn-vinfast {
  background: var(--bg-dark);
  color: #ffffff;
  padding: 10px 22px;
  border-radius: var(--radius-md);
  font-weight: 700;
  font-size: 0.885rem;
  transition: all var(--duration-fast);
}

.btn-vinfast:hover {
  background: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-glow);
}

/* Inline Booking Drawer */
.inline-booking-card {
  margin-top: 20px;
  padding: 20px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.inline-header h4 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-primary);
}

.inline-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 0.885rem;
}

.sum-row {
  display: flex;
  justify-content: space-between;
}

.sum-row.highlight {
  color: var(--color-primary);
  font-weight: 700;
}

.sum-row.total {
  padding-top: 10px;
  border-top: 1px dashed var(--color-border);
  font-size: 1rem;
  font-weight: 800;
}

.total-price-inline {
  color: var(--color-primary);
}

.calc-note {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-top: 4px;
}

/* Empty State */
.empty-state-card {
  padding: 48px 32px;
  text-align: center;
  background: #ffffff;
  max-width: 680px;
  margin: 0 auto;
}

.empty-icon-wrap {
  width: 72px;
  height: 72px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto 24px;
}

.empty-text h3 {
  font-size: 1.4rem;
  margin-bottom: 12px;
}

.empty-text p {
  color: var(--color-text-muted);
  line-height: 1.6;
  margin-bottom: 24px;
}

.loading-state {
  text-align: center;
  padding: 60px 0;
}

.loading-state .spinner {
  margin: 0 auto 16px;
}

/* Process Section */
.process-section {
  padding: 100px 0;
}

.process-grid {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 16px;
}

.process-item {
  flex: 1;
  padding: 32px 24px;
  position: relative;
  display: flex;
  flex-direction: column;
  background: #ffffff;
}

.step-num {
  position: absolute;
  top: 20px;
  right: 20px;
  font-family: var(--font-heading);
  font-weight: 800;
  font-size: 1.5rem;
  color: #e2e8f0;
}

.process-item .icon-box {
  width: 52px;
  height: 52px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  margin-bottom: 20px;
}

.icon-box.green { background: #ecfdf5; color: #059669; }
.icon-box.blue { background: #eff6ff; color: #2563eb; }
.icon-box.yellow { background: #fffbeb; color: #d97706; }
.icon-box.red { background: #fef2f2; color: #dc2626; }

.process-item h3 {
  font-size: 1.15rem;
  font-weight: 800;
  margin-bottom: 10px;
}

.process-item p {
  font-size: 0.9rem;
  line-height: 1.6;
}

.process-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  font-size: 1.25rem;
}

/* FAQ Section */
.faq-section {
  padding: 60px 0 100px;
}

.faq-list {
  max-width: 820px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.faq-item {
  padding: 0;
  overflow: hidden;
  transition: all var(--duration-fast);
}

.faq-question {
  width: 100%;
  padding: 22px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  text-align: left;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-main);
  background: transparent;
}

.faq-chevron {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  transition: transform var(--duration-fast);
}

.faq-item.open .faq-chevron {
  transform: rotate(180deg);
  color: var(--color-primary);
}

.faq-answer {
  padding: 0 28px 24px;
  border-top: 1px solid var(--color-border-subtle);
  animation: fadeIn 200ms ease;
}

.faq-answer p {
  padding-top: 16px;
  color: var(--color-text-muted);
  line-height: 1.65;
}

/* Booking Success Modal */
.success-modal-header {
  text-align: center;
  padding: 32px 28px 16px;
}

.success-icon-badge {
  width: 68px;
  height: 68px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto 16px;
  animation: pulseGlow 2s infinite;
}

.success-modal-header h3 {
  font-size: 1.6rem;
  font-weight: 800;
  margin-bottom: 6px;
}

.booking-receipt-box {
  background: var(--bg-surface-sub);
  border-radius: var(--radius-lg);
  padding: 20px;
  margin: 0 28px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  font-size: 0.925rem;
}

.receipt-row {
  display: flex;
  justify-content: space-between;
}

.receipt-code {
  color: var(--color-primary);
  font-size: 1.05rem;
}

.receipt-divider {
  height: 1px;
  background: var(--color-border);
  margin: 6px 0;
}

.receipt-row.total {
  font-size: 1.05rem;
}

.text-primary {
  color: var(--color-primary);
}

.next-step-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 20px;
  background: #f0fdf4;
  border-left: 4px solid var(--color-primary);
  border-radius: var(--radius-sm);
  margin: 0 28px 24px;
  font-size: 0.85rem;
  color: #166534;
}

.next-step-box i {
  font-size: 1rem;
  margin-top: 2px;
}

.auth-buttons-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 20px;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-3 {
  margin-top: 12px;
}

.w-full {
  width: 100%;
}

@media (max-width: 1024px) {
  .hero-bg-layer {
    opacity: 0.35;
    width: 100%;
  }
  .search-box {
    flex-direction: column;
    align-items: stretch;
    padding: 24px;
  }
  .search-separator {
    width: 100%;
    height: 1px;
  }
  .process-grid {
    flex-direction: column;
  }
  .process-arrow {
    transform: rotate(90deg);
    padding: 12px 0;
  }
}
</style>
