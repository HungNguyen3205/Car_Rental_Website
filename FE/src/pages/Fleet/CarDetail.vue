<template>
  <div class="car-detail-page">
    <!-- Breadcrumb Header -->
    <div class="breadcrumb-bar app-container">
      <router-link to="/" class="bc-link"><i class="fa-solid fa-house"></i> Trang chủ</router-link>
      <span class="bc-sep">/</span>
      <router-link to="/fleet" class="bc-link">Đội ngũ xe</router-link>
      <span class="bc-sep">/</span>
      <span class="bc-current">{{ car?.name || 'Chi tiết xe' }}</span>
    </div>

    <!-- 1. LOADING STATE -->
    <div v-if="loading" class="state-container app-container text-center">
      <div class="loading-card card">
        <div class="spinner"></div>
        <h3>Đang tải thông số kỹ thuật xe...</h3>
        <p class="text-muted">Hệ thống đang đồng bộ dữ liệu phương tiện thời gian thực từ máy chủ.</p>
      </div>
    </div>

    <!-- 2. NOT FOUND STATE (404) -->
    <div v-else-if="notFound" class="state-container app-container text-center animate-in">
      <div class="state-card card error-border">
        <div class="state-icon warn-icon">
          <i class="fa-solid fa-car-tunnel"></i>
        </div>
        <h2>Không Tìm Thấy Phương Tiện</h2>
        <p>Mẫu xe bạn đang tìm kiếm không tồn tại, đã bị xóa hoặc đường dẫn không chính xác.</p>
        <div class="state-actions">
          <router-link to="/fleet" class="btn-primary">
            <i class="fa-solid fa-arrow-left"></i> Khám phá đội xe hiện có
          </router-link>
        </div>
      </div>
    </div>

    <!-- 3. API ERROR STATE WITH RETRY -->
    <div v-else-if="fetchError" class="state-container app-container text-center animate-in">
      <div class="state-card card error-border">
        <div class="state-icon err-icon">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2>Không Thể Tải Dữ Liệu Xe</h2>
        <p>{{ fetchError }}</p>
        <div class="state-actions">
          <button class="btn-primary" @click="fetchCarDetail">
            <i class="fa-solid fa-rotate-right"></i> Thử kết nối lại
          </button>
          <router-link to="/fleet" class="btn-outline">
            Quay lại danh sách
          </router-link>
        </div>
      </div>
    </div>

    <!-- 4. ACTIVE / STOPPED VEHICLE CONTENT -->
    <div v-else-if="car" class="detail-container app-container animate-in">
      
      <!-- STOPPED / INACTIVE WARNING BANNER -->
      <div v-if="isStoppedService" class="stopped-banner card animate-in">
        <div class="stopped-icon">
          <i class="fa-solid fa-ban"></i>
        </div>
        <div class="stopped-text">
          <h3>Phương Tiện Tạm Ngừng Phục Vụ</h3>
          <p>Mẫu xe này hiện đang tạm ngưng kinh doanh hoặc trong kế hoạch kiểm định/bảo trì dài hạn. Quý khách vui lòng tham khảo các dòng xe tương tự.</p>
        </div>
        <router-link to="/fleet" class="btn-outline-sm">
          Xem xe khác
        </router-link>
      </div>

      <div class="detail-layout">
        <!-- LEFT COLUMN: Image Gallery & Detailed Specs -->
        <div class="detail-main">
          
          <!-- Image Gallery Section -->
          <section class="gallery-card card">
            <div class="main-image-wrap">
              <img 
                :src="activeImage" 
                :alt="car.name" 
                class="main-car-img" 
                @error="handleImgFallback($event)" 
                @click="openLightbox(activeImage)"
              />
              <span class="category-tag">{{ car.category_name || car.type || 'Tiêu chuẩn' }}</span>
              <span 
                class="service-status-pill" 
                :class="isStoppedService ? 'pill-stopped' : (car.status === 'maintenance' ? 'pill-warn' : 'pill-ok')"
              >
                {{ isStoppedService ? 'Ngừng phục vụ' : (car.status === 'maintenance' ? 'Đang bảo trì' : 'Sẵn sàng giao') }}
              </span>
            </div>

            <!-- Thumbnail Carousel if multiple images exist -->
            <div v-if="allImages.length > 1" class="thumbnail-strip">
              <div 
                v-for="(img, idx) in allImages" 
                :key="idx" 
                class="thumb-item" 
                :class="{ 'active': activeImage === img }"
                @click="activeImage = img"
              >
                <img :src="img" :alt="car.name + ' thumb ' + idx" @error="handleImgFallback($event)" />
              </div>
            </div>
          </section>

          <!-- Headline & City Info -->
          <section class="headline-section">
            <div class="title-row">
              <div>
                <span class="brand-sub">Hệ thống phương tiện DriveRent</span>
                <h1 class="car-title">{{ car.name }}</h1>
              </div>
              <div class="location-chip">
                <i class="fa-solid fa-location-dot"></i>
                <span>{{ cityDisplay }}</span>
              </div>
            </div>
          </section>

          <!-- Specifications Bento Grid (Only real fields, no fake 5-seats or fake battery) -->
          <section class="specs-section card">
            <h3 class="section-heading"><i class="fa-solid fa-sliders"></i> Thông Số Kỹ Thuật Thực Tế</h3>
            <div class="specs-grid">
              
              <!-- 1. Fuel Type -->
              <div class="spec-cell">
                <div class="spec-icon">
                  <i :class="isElectric ? 'fa-solid fa-bolt' : 'fa-solid fa-gas-pump'"></i>
                </div>
                <div class="spec-info">
                  <span class="label">Nhiên liệu / Động cơ</span>
                  <strong>{{ fuelTypeDisplay }}</strong>
                </div>
              </div>

              <!-- 2. Seats (Only if set, otherwise "Đang cập nhật") -->
              <div class="spec-cell">
                <div class="spec-icon"><i class="fa-solid fa-users"></i></div>
                <div class="spec-info">
                  <span class="label">Sức chứa</span>
                  <strong>{{ car.seats ? car.seats + ' Chỗ ngồi' : 'Đang cập nhật' }}</strong>
                </div>
              </div>

              <!-- 3. Battery Capacity (Only show if electric and set, or show "Đang cập nhật" for electric. Never for gasoline/diesel) -->
              <div class="spec-cell" v-if="isElectric">
                <div class="spec-icon"><i class="fa-solid fa-battery-full"></i></div>
                <div class="spec-info">
                  <span class="label">Dung lượng Pin</span>
                  <strong>{{ car.battery_capacity || 'Đang cập nhật' }}</strong>
                </div>
              </div>

              <!-- 4. Range / Fuel Consumption -->
              <div class="spec-cell">
                <div class="spec-icon"><i class="fa-solid fa-gauge-high"></i></div>
                <div class="spec-info">
                  <span class="label">{{ isElectric ? 'Quãng đường 1 lần sạc' : 'Mức tiêu hao nhiên liệu' }}</span>
                  <strong>{{ car.range_per_charge || 'Chuẩn kỹ thuật' }}</strong>
                </div>
              </div>

              <!-- 5. Transmission -->
              <div class="spec-cell">
                <div class="spec-icon"><i class="fa-solid fa-gear"></i></div>
                <div class="spec-info">
                  <span class="label">Hộp số</span>
                  <strong>{{ car.transmission || 'Tự động' }}</strong>
                </div>
              </div>

              <!-- 6. Year of manufacture -->
              <div class="spec-cell" v-if="car.year_of_manufacture">
                <div class="spec-icon"><i class="fa-solid fa-calendar"></i></div>
                <div class="spec-info">
                  <span class="label">Năm sản xuất</span>
                  <strong>{{ car.year_of_manufacture }}</strong>
                </div>
              </div>

              <!-- 7. Current Odometer -->
              <div class="spec-cell" v-if="car.current_km">
                <div class="spec-icon"><i class="fa-solid fa-road"></i></div>
                <div class="spec-info">
                  <span class="label">Số ODO hiện tại</span>
                  <strong>{{ Number(car.current_km).toLocaleString('vi-VN') }} km</strong>
                </div>
              </div>

              <!-- 8. Insurance -->
              <div class="spec-cell">
                <div class="spec-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="spec-info">
                  <span class="label">Bảo hiểm xe</span>
                  <strong>Bảo hiểm TNDS & Thân vỏ</strong>
                </div>
              </div>
            </div>
          </section>

          <!-- Description if exists -->
          <section v-if="car.description" class="description-section card">
            <h3 class="section-heading"><i class="fa-solid fa-circle-info"></i> Mô Tả Chi Tiết Phương Tiện</h3>
            <div class="desc-content">
              <p>{{ car.description }}</p>
            </div>
          </section>

          <!-- Rental Conditions (Terms & Requirements) -->
          <section class="conditions-section card">
            <h3 class="section-heading"><i class="fa-solid fa-file-contract"></i> Điều Kiện Thuê Xe Thực Tế</h3>
            <ul class="condition-list">
              <li>
                <i class="fa-solid fa-id-card text-primary"></i>
                <div>
                  <strong>Giấy tờ yêu cầu:</strong> CCCD gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B2 trở lên còn hiệu lực.
                </div>
              </li>
              <li>
                <i class="fa-solid fa-vault text-primary"></i>
                <div>
                  <strong>Tiền cọc bảo đảm:</strong> Khách hàng đặt cọc bảo đảm khi nhận xe. Khoản cọc được hoàn lại minh bạch sau khi kiểm tra biên bản nhận trả xe.
                </div>
              </li>
              <li>
                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                <div>
                  <strong>Quy ước thời gian thuê:</strong> Tính theo ngày (24 giờ). Giao nhận xe theo biên bản kiểm tra ngoại quan và đồng hồ công-tơ-mét hai bên cùng xác nhận.
                </div>
              </li>
              <li>
                <i class="fa-solid fa-ban-smoking text-primary"></i>
                <div>
                  <strong>Quy tắc trên xe:</strong> Không hút thuốc lá, không chở chất cấm hoặc hàng hóa nặng mùi làm giảm chất lượng nội thất.
                </div>
              </li>
            </ul>
          </section>

          <!-- Transparent Extra Fees (Cấu hình minh bạch, không bịa giá) -->
          <section class="fees-section card">
            <h3 class="section-heading"><i class="fa-solid fa-receipt"></i> Biểu Phí Phụ Trội Cấu Hình Hệ Thống</h3>
            <p class="fees-sub">DriveRent áp dụng biểu phí minh bạch đã cấu hình sẵn trong quy trình bàn giao & nhận trả xe:</p>
            <div class="fees-table-wrap">
              <table class="fees-table">
                <thead>
                  <tr>
                    <th>Khoản phụ phí</th>
                    <th>Đơn giá quy định</th>
                    <th>Ghi chú áp dụng</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Phụ phí vượt quãng đường</strong></td>
                    <td class="fee-val">3.000 đ / km</td>
                    <td>Chỉ tính khi tổng quãng đường di chuyển vượt quá 350 km/ngày.</td>
                  </tr>
                  <tr>
                    <td><strong>Phụ phí trả xe trễ giờ</strong></td>
                    <td class="fee-val">100.000 đ / giờ</td>
                    <td>Tối đa không quá 600.000 đ/ngày nếu không thông báo trước.</td>
                  </tr>
                  <tr>
                    <td><strong>Phí vệ sinh nội thất</strong></td>
                    <td class="fee-val">100.000 đ - 300.000 đ</td>
                    <td>Chỉ áp dụng khi xe trả về bẩn nhiều bùn đất hoặc có mùi khói thuốc.</td>
                  </tr>
                  <tr>
                    <td><strong>Chênh lệch pin / nhiên liệu</strong></td>
                    <td class="fee-val">{{ isElectric ? '5.000 đ / % pin' : '15.000 đ / % xăng' }}</td>
                    <td>Bù trừ theo thực tế đối chiếu giữa biên bản giao và nhận trả.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <!-- RIGHT COLUMN: Sticky Booking & Live Server Availability Widget -->
        <aside class="detail-sidebar">
          <div class="booking-widget card animate-in">
            <div class="widget-header">
              <span class="widget-sub">Giá thuê niêm yết</span>
              <div class="price-hero">
                <span class="price-number">{{ Number(car.price_per_day).toLocaleString('vi-VN') }} đ</span>
                <span class="price-cycle">/ ngày</span>
              </div>
            </div>

            <!-- Booking Date Pickers -->
            <div class="widget-form">
              <div class="form-group">
                <label><i class="fa-solid fa-calendar-arrow-down"></i> Thời gian nhận xe</label>
                <input 
                  type="datetime-local" 
                  v-model="bookingForm.startDate" 
                  :min="minStartDate"
                  @change="handleDateChange"
                  :disabled="isStoppedService"
                />
              </div>

              <div class="form-group">
                <label><i class="fa-solid fa-calendar-arrow-up"></i> Thời gian trả xe</label>
                <input 
                  type="datetime-local" 
                  v-model="bookingForm.endDate" 
                  :min="bookingForm.startDate"
                  @change="handleDateChange"
                  :disabled="isStoppedService"
                />
              </div>

              <div class="form-group">
                <label><i class="fa-solid fa-location-dot"></i> Địa điểm nhận & trả xe</label>
                <input 
                  type="text" 
                  v-model="bookingForm.address" 
                  placeholder="Nhập địa chỉ giao xe hoặc Tại showroom..."
                  :disabled="isStoppedService"
                />
              </div>

              <!-- Quote & Availability Server Status -->
              <div class="availability-box">
                <div v-if="checkingAvailability" class="avail-loading">
                  <i class="fa-solid fa-spinner fa-spin"></i> Đang kiểm tra lịch trống & tính giá server...
                </div>
                <div v-else-if="availabilityData" class="avail-result" :class="availabilityData.is_available ? 'avail-yes' : 'avail-no'">
                  <div class="avail-header">
                    <i :class="availabilityData.is_available ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'"></i>
                    <span>{{ availabilityData.message }}</span>
                  </div>
                  
                  <!-- Server Price Breakdown -->
                  <div v-if="availabilityData.is_available" class="quote-summary">
                    <div class="quote-row">
                      <span>Thời gian thuê tính toán:</span>
                      <strong>{{ availabilityData.days }} ngày</strong>
                    </div>
                    <div class="quote-row">
                      <span>Đơn giá server:</span>
                      <span>{{ Number(availabilityData.price_per_day).toLocaleString('vi-VN') }} đ/ngày</span>
                    </div>
                    <div class="quote-divider"></div>
                    <div class="quote-row total-quote">
                      <span>Tổng tiền tạm tính:</span>
                      <strong class="total-val">{{ Number(availabilityData.total_price).toLocaleString('vi-VN') }} đ</strong>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notice on Server snapshot pricing -->
              <div class="price-disclaimer">
                <i class="fa-solid fa-shield-check"></i>
                <small>Giá thuê được bảo lưu chính xác theo thời điểm đặt xe (Snapshot server).</small>
              </div>

              <!-- Main Booking CTA Button -->
              <div class="cta-wrap">
                <button 
                  type="button" 
                  class="btn-primary w-full btn-large" 
                  :disabled="isStoppedService || bookingSubmitting || (availabilityData && !availabilityData.is_available)"
                  @click="handleBookingSubmit"
                >
                  <i v-if="bookingSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else-if="isStoppedService">Xe tạm ngừng phục vụ</span>
                  <span v-else-if="availabilityData && !availabilityData.is_available">Khung giờ đã có khách</span>
                  <span v-else>Đặt xe ngay</span>
                </button>
              </div>

              <div class="widget-perks">
                <div class="perk-item"><i class="fa-solid fa-check"></i> Giữ chỗ an toàn chống trùng lịch</div>
                <div class="perk-item"><i class="fa-solid fa-check"></i> Biên bản kiểm định lúc giao & nhận</div>
                <div class="perk-item"><i class="fa-solid fa-check"></i> Hỗ trợ kỹ thuật 24/7 toàn quốc</div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- Mobile Bottom CTA Bar (Ensures CTA is always accessible on mobile without being covered by chat widget) -->
    <div v-if="car && !loading && !notFound && !fetchError" class="mobile-cta-bar">
      <div class="mobile-cta-price">
        <span class="label">Giá thuê:</span>
        <strong class="val">{{ Number(car.price_per_day).toLocaleString('vi-VN') }} đ <small>/ ngày</small></strong>
      </div>
      <button 
        type="button" 
        class="btn-primary mobile-cta-btn" 
        :disabled="isStoppedService || bookingSubmitting"
        @click="scrollToBooking"
      >
        <span>{{ isStoppedService ? 'Ngừng phục vụ' : 'Đặt thuê xe' }}</span>
        <i class="fa-solid fa-arrow-up"></i>
      </button>
    </div>

    <!-- Booking Success Modal -->
    <div v-if="bookingSuccess" class="modal-overlay" @click.self="bookingSuccess = null">
      <div class="modal-content card success-modal animate-in">
        <div class="modal-header">
          <div class="success-icon-wrap">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div>
            <h3>Đặt Thuê Xe Thành Công!</h3>
            <p class="modal-sub">Mã đơn đặt xe: <strong class="order-code">{{ bookingSuccess.ma_dat_xe }}</strong></p>
          </div>
        </div>
        <div class="modal-body">
          <div class="success-details-card">
            <div class="detail-line">
              <span>Phương tiện:</span>
              <strong>{{ car.name }}</strong>
            </div>
            <div class="detail-line">
              <span>Thời gian nhận:</span>
              <strong>{{ formatDateTime(bookingSuccess.start_date) }}</strong>
            </div>
            <div class="detail-line">
              <span>Thời gian trả:</span>
              <strong>{{ formatDateTime(bookingSuccess.end_date) }}</strong>
            </div>
            <div class="detail-line">
              <span>Tổng tiền ghi nhận (Server):</span>
              <strong class="highlight-price">{{ Number(bookingSuccess.tong_tien).toLocaleString('vi-VN') }} đ</strong>
            </div>
            <div class="detail-line">
              <span>Trạng thái đơn:</span>
              <span class="badge yellow">Chờ xác nhận</span>
            </div>
          </div>
          <p class="success-note">
            Đơn của bạn đã được ghi nhận vào hệ thống và giữ lịch an toàn. Nhân viên chăm sóc khách hàng sẽ liên hệ sớm nhất để hướng dẫn bàn giao.
          </p>
        </div>
        <div class="modal-footer">
          <router-link :to="'/my-bookings/' + bookingSuccess.id" class="btn-primary">
            <i class="fa-solid fa-file-invoice"></i> Xem chi tiết chuyến đi
          </router-link>
          <button class="btn-outline" @click="bookingSuccess = null">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div v-if="lightboxImage" class="lightbox-overlay" @click.self="lightboxImage = null">
      <div class="lightbox-content animate-in">
        <button class="btn-close-lightbox" @click="lightboxImage = null">&times;</button>
        <img :src="lightboxImage" :alt="car?.name" class="lightbox-img" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { resolveCarImage, handleCarImgError } from '../../core/carImageHelper';

const route = useRoute();
const router = useRouter();
const toaster = createToaster({ position: "top-right" });

// Core State
const car = ref(null);
const loading = ref(true);
const notFound = ref(false);
const fetchError = ref(null);
const activeImage = ref('');
const lightboxImage = ref(null);

// Booking Form State
const getInitialStartDate = () => {
  if (route.query.start_date) {
    try {
      const d = new Date(route.query.start_date);
      if (!isNaN(d.getTime())) return d.toISOString().slice(0, 16);
    } catch (e) {}
  }
  const d = new Date(Date.now() + 2 * 3600 * 1000); // 2h from now
  return d.toISOString().slice(0, 16);
};

const getInitialEndDate = () => {
  if (route.query.end_date) {
    try {
      const d = new Date(route.query.end_date);
      if (!isNaN(d.getTime())) return d.toISOString().slice(0, 16);
    } catch (e) {}
  }
  const d = new Date(Date.now() + 26 * 3600 * 1000); // 26h from now (1 day)
  return d.toISOString().slice(0, 16);
};

const minStartDate = computed(() => {
  return new Date().toISOString().slice(0, 16);
});

const getInitialAddress = () => {
  if (route.query.address) return String(route.query.address);
  if (route.query.city) {
    const c = String(route.query.city).toLowerCase();
    if (c === 'sg' || c.includes('hồ chí minh')) return 'Showroom TP. Hồ Chí Minh';
    if (c === 'hn' || c.includes('hà nội')) return 'Showroom Hà Nội';
    if (c === 'dn' || c.includes('đà nẵng')) return 'Showroom Đà Nẵng';
    if (c === 'nt' || c.includes('nha trang')) return 'Showroom Nha Trang';
  }
  return 'Tại showroom DriveRent';
};

const bookingForm = ref({
  startDate: getInitialStartDate(),
  endDate: getInitialEndDate(),
  address: getInitialAddress()
});

// Server Quote & Availability State
const checkingAvailability = ref(false);
const availabilityData = ref(null);
const bookingSubmitting = ref(false);
const bookingSuccess = ref(null);

// Image Fallback
const handleImgFallback = (e) => {
  handleCarImgError(e);
};

const openLightbox = (img) => {
  lightboxImage.value = img;
};

// Derived Properties
const isStoppedService = computed(() => {
  if (!car.value) return false;
  return car.value.is_active === false || car.value.status === 'stopped';
});

const isElectric = computed(() => {
  if (!car.value) return false;
  const ft = (car.value.fuel_type || '').toLowerCase();
  return ft === 'electric' || (!car.value.fuel_type && (car.value.name || '').toLowerCase().includes('vf'));
});

const fuelTypeDisplay = computed(() => {
  if (!car.value) return '';
  const ft = (car.value.fuel_type || '').toLowerCase();
  if (ft === 'gasoline') return 'Xăng';
  if (ft === 'diesel') return 'Dầu Diesel';
  if (ft === 'hybrid') return 'Động cơ Hybrid';
  return 'Điện 100% (EV)';
});

const cityDisplay = computed(() => {
  if (!car.value) return '';
  const c = (car.value.city || '').toLowerCase();
  if (c === 'sg' || c.includes('hồ chí minh')) return 'TP. Hồ Chí Minh';
  if (c === 'hn' || c.includes('hà nội')) return 'Hà Nội';
  if (c === 'dn' || c.includes('đà nẵng')) return 'Đà Nẵng';
  if (c === 'nt' || c.includes('nha trang')) return 'Nha Trang';
  return car.value.city || 'Toàn quốc';
});

const allImages = computed(() => {
  if (!car.value) return [];
  const list = [];
  const mainImg = resolveCarImage(car.value);
  if (mainImg) list.push(mainImg);

  if (Array.isArray(car.value.gallery)) {
    car.value.gallery.forEach(img => {
      if (img && typeof img === 'string' && !list.includes(img)) {
        list.push(img);
      }
    });
  }
  return list.length > 0 ? list : [mainImg];
});

// Formatters
const formatDateTime = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const toLocalISO = (dateStr) => {
  const d = new Date(dateStr);
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 19).replace('T', ' ');
};

// Server Availability & Quote Calculation
const checkAvailability = async () => {
  if (!car.value || isStoppedService.value) return;
  if (!bookingForm.value.startDate || !bookingForm.value.endDate) return;

  const start = new Date(bookingForm.value.startDate);
  const end = new Date(bookingForm.value.endDate);
  if (end <= start) {
    availabilityData.value = {
      is_available: false,
      message: 'Thời gian trả xe phải diễn ra sau thời gian nhận xe!'
    };
    return;
  }

  checkingAvailability.value = true;
  try {
    const res = await baseRequest.post('client/car/check-availability', {
      car_id: car.value.id,
      start_date: toLocalISO(bookingForm.value.startDate),
      end_date: toLocalISO(bookingForm.value.endDate)
    });

    if (res.data && res.data.status) {
      availabilityData.value = res.data;
    } else {
      availabilityData.value = {
        is_available: false,
        message: res.data?.message || 'Không thể kiểm tra lịch trống lúc này.'
      };
    }
  } catch (err) {
    if (err.response?.status === 422) {
      availabilityData.value = {
        is_available: false,
        message: err.response?.data?.message || 'Thời gian thuê không hợp lệ.'
      };
    } else {
      availabilityData.value = null;
    }
  } finally {
    checkingAvailability.value = false;
  }
};

const handleDateChange = () => {
  checkAvailability();
};

// Fetch Car Detail
const fetchCarDetail = async () => {
  const carId = route.params.id;
  if (!carId) {
    notFound.value = true;
    loading.value = false;
    return;
  }

  loading.value = true;
  notFound.value = false;
  fetchError.value = null;

  try {
    const res = await baseRequest.get(`client/car/detail/${carId}`);
    if (res.data && res.data.status && res.data.data) {
      car.value = res.data.data;
      activeImage.value = resolveCarImage(car.value);

      // Metadata title using real car data (no fake reviews)
      document.title = `${car.value.name} - Thông số kỹ thuật & Đặt thuê xe | DriveRent`;

      // Check initial availability
      await checkAvailability();
    } else {
      notFound.value = true;
    }
  } catch (err) {
    if (err.response?.status === 404) {
      notFound.value = true;
    } else {
      fetchError.value = err.response?.data?.message || 'Không thể kết nối máy chủ để tải thông tin xe. Vui lòng thử lại sau.';
    }
  } finally {
    loading.value = false;
  }
};

// Submit Booking using Shared Endpoint
const handleBookingSubmit = async () => {
  const token = localStorage.getItem('token_client');
  if (!token) {
    toaster.warning("Vui lòng đăng nhập để tiến hành đặt xe!");
    router.push(`/login?redirect=${encodeURIComponent(route.fullPath)}`);
    return;
  }

  if (isStoppedService.value) {
    toaster.error("Phương tiện này hiện đang tạm ngừng kinh doanh.");
    return;
  }

  const start = new Date(bookingForm.value.startDate);
  const end = new Date(bookingForm.value.endDate);
  if (end <= start) {
    toaster.warning("Thời gian trả xe phải diễn ra sau thời gian nhận xe!");
    return;
  }

  bookingSubmitting.value = true;
  try {
    const res = await baseRequest.post('user/bookings/dat-lich', {
      car_id: car.value.id,
      start_date: toLocalISO(bookingForm.value.startDate),
      end_date: toLocalISO(bookingForm.value.endDate),
      dia_chi_nhan_xe: bookingForm.value.address || 'Tại showroom DriveRent',
      dia_chi_tra_xe: bookingForm.value.address || 'Tại showroom DriveRent',
      ly_do_thue: `Đặt xe ${car.value.name} qua trang chi tiết`
    });

    if (res.data && res.data.status) {
      toaster.success("Đặt xe thành công!");
      bookingSuccess.value = res.data.data;

      // Check if price changed from estimate
      if (availabilityData.value && Number(res.data.data.tong_tien) !== Number(availabilityData.value.total_price)) {
        toaster.info(`Đơn giá đã được cập nhật chính xác theo snapshot máy chủ: ${Number(res.data.data.tong_tien).toLocaleString('vi-VN')} đ`);
      }
    } else {
      toaster.error(res.data?.message || "Đặt xe thất bại. Vui lòng thử lại!");
    }
  } catch (err) {
    if (err.response?.status === 409 || err.response?.data?.error_type === 'SCHEDULE_CONFLICT') {
      const msg = err.response?.data?.message || "Xe vừa được khách hàng khác giữ chỗ trong khung giờ này. Vui lòng chọn khung giờ khác!";
      toaster.warning(msg, { duration: 6000 });
      // Refresh availability
      await checkAvailability();
    } else {
      toaster.error(err.response?.data?.message || "Có lỗi xảy ra khi tạo đơn đặt xe.");
    }
  } finally {
    bookingSubmitting.value = false;
  }
};

const scrollToBooking = () => {
  const widget = document.querySelector('.booking-widget');
  if (widget) {
    widget.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

watch(() => route.params.id, () => {
  fetchCarDetail();
});

onMounted(() => {
  fetchCarDetail();
});
</script>

<style scoped>
.car-detail-page {
  padding-bottom: 80px;
  background: var(--bg-main, #f8fafc);
  min-height: 100vh;
}

/* Breadcrumb */
.breadcrumb-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  font-size: 0.9rem;
  color: var(--color-muted, #64748b);
}

.bc-link {
  color: var(--color-muted, #64748b);
  text-decoration: none;
  transition: color var(--duration-fast, 0.2s);
}

.bc-link:hover {
  color: var(--color-primary, #0052cc);
}

.bc-sep {
  opacity: 0.5;
}

.bc-current {
  color: var(--color-text, #0f172a);
  font-weight: 600;
}

/* State Views */
.state-container {
  padding: 60px 20px;
  max-width: 680px;
  margin: 0 auto;
}

.state-card {
  padding: 48px 32px;
  border-radius: var(--radius-xl, 16px);
  background: #ffffff;
}

.state-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto 20px;
}

.warn-icon {
  background: #fef3c7;
  color: #d97706;
}

.err-icon {
  background: #fee2e2;
  color: #dc2626;
}

.state-card h2 {
  font-size: 1.5rem;
  margin-bottom: 12px;
  color: var(--color-text, #0f172a);
}

.state-card p {
  color: var(--color-muted, #64748b);
  margin-bottom: 24px;
}

.state-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

/* Stopped Banner */
.stopped-banner {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  margin-bottom: 24px;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: var(--radius-lg, 12px);
}

.stopped-icon {
  font-size: 1.8rem;
  color: #e11d48;
}

.stopped-text h3 {
  font-size: 1.05rem;
  color: #9f1239;
  margin-bottom: 4px;
}

.stopped-text p {
  font-size: 0.9rem;
  color: #be123c;
  margin: 0;
}

/* Detail Layout Grid */
.detail-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 32px;
  align-items: start;
}

/* Gallery Section */
.gallery-card {
  padding: 16px;
  border-radius: var(--radius-xl, 16px);
  background: #ffffff;
  margin-bottom: 24px;
}

.main-image-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: var(--radius-lg, 12px);
  overflow: hidden;
  background: #f1f5f9;
  cursor: zoom-in;
}

.main-car-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--duration-normal, 0.3s) ease;
}

.main-car-img:hover {
  transform: scale(1.02);
}

.category-tag {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(8px);
  color: #ffffff;
  padding: 6px 14px;
  border-radius: var(--radius-full, 9999px);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.service-status-pill {
  position: absolute;
  top: 14px;
  right: 14px;
  padding: 6px 14px;
  border-radius: var(--radius-full, 9999px);
  font-size: 0.8rem;
  font-weight: 700;
}

.pill-ok {
  background: rgba(34, 197, 94, 0.9);
  color: #ffffff;
}

.pill-warn {
  background: rgba(245, 158, 11, 0.9);
  color: #ffffff;
}

.pill-stopped {
  background: rgba(239, 68, 68, 0.9);
  color: #ffffff;
}

.thumbnail-strip {
  display: flex;
  gap: 12px;
  margin-top: 14px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.thumb-item {
  flex: 0 0 90px;
  height: 60px;
  border-radius: var(--radius-md, 8px);
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all var(--duration-fast, 0.2s);
}

.thumb-item.active {
  border-color: var(--color-primary, #0052cc);
  box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2);
}

.thumb-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Headline */
.headline-section {
  margin-bottom: 24px;
}

.title-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
}

.brand-sub {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-primary, #0052cc);
  font-weight: 700;
}

.car-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-text, #0f172a);
  margin-top: 4px;
}

.location-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: var(--radius-full, 9999px);
  background: #ffffff;
  border: 1px solid var(--color-border, #e2e8f0);
  font-size: 0.9rem;
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
}

.location-chip i {
  color: #ef4444;
}

/* Specs Grid */
.specs-section, .description-section, .conditions-section, .fees-section {
  padding: 24px;
  border-radius: var(--radius-xl, 16px);
  background: #ffffff;
  margin-bottom: 24px;
}

.section-heading {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text, #0f172a);
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-heading i {
  color: var(--color-primary, #0052cc);
}

.specs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 16px;
}

.spec-cell {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: var(--radius-lg, 12px);
}

.spec-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(0, 82, 204, 0.08);
  color: var(--color-primary, #0052cc);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.spec-info {
  display: flex;
  flex-direction: column;
}

.spec-info .label {
  font-size: 0.78rem;
  color: var(--color-muted, #64748b);
  margin-bottom: 2px;
}

.spec-info strong {
  font-size: 0.95rem;
  color: var(--color-text, #0f172a);
}

/* Description */
.desc-content p {
  line-height: 1.7;
  color: #334155;
  font-size: 0.95rem;
  margin: 0;
}

/* Conditions List */
.condition-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.condition-list li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #334155;
}

.condition-list li i {
  margin-top: 3px;
  font-size: 1.1rem;
}

/* Fees Table */
.fees-sub {
  font-size: 0.9rem;
  color: var(--color-muted, #64748b);
  margin-bottom: 14px;
}

.fees-table-wrap {
  overflow-x: auto;
}

.fees-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.fees-table th, .fees-table td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
}

.fees-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
}

.fee-val {
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
}

/* Sticky Booking Widget */
.detail-sidebar {
  position: sticky;
  top: 96px;
  z-index: 20;
}

.booking-widget {
  padding: 24px;
  border-radius: var(--radius-xl, 16px);
  background: #ffffff;
  border: 1px solid var(--color-border, #e2e8f0);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

.widget-header {
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 16px;
  margin-bottom: 20px;
}

.widget-sub {
  font-size: 0.8rem;
  color: var(--color-muted, #64748b);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.price-hero {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-top: 4px;
}

.price-number {
  font-size: 1.85rem;
  font-weight: 800;
  color: var(--color-primary, #0052cc);
}

.price-cycle {
  font-size: 0.95rem;
  color: var(--color-muted, #64748b);
}

.widget-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.widget-form .form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.widget-form label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
}

.widget-form label i {
  color: var(--color-primary, #0052cc);
}

.widget-form input {
  padding: 10px 12px;
  border: 1px solid var(--color-border, #cbd5e1);
  border-radius: var(--radius-md, 8px);
  font-size: 0.9rem;
}

.availability-box {
  background: #f8fafc;
  border-radius: var(--radius-lg, 12px);
  padding: 12px 14px;
  font-size: 0.88rem;
}

.avail-loading {
  color: var(--color-muted, #64748b);
  display: flex;
  align-items: center;
  gap: 8px;
}

.avail-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.avail-yes .avail-header {
  color: #16a34a;
}

.avail-no .avail-header {
  color: #dc2626;
}

.quote-summary {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #e2e8f0;
}

.quote-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 4px;
  color: #475569;
}

.quote-divider {
  margin: 6px 0;
  border-bottom: 1px solid #e2e8f0;
}

.total-quote {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.total-val {
  color: var(--color-primary, #0052cc);
}

.price-disclaimer {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--color-muted, #64748b);
  font-size: 0.78rem;
}

.btn-large {
  padding: 14px 20px;
  font-size: 1.05rem;
  font-weight: 700;
}

.widget-perks {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 0.82rem;
  color: #475569;
}

.perk-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.perk-item i {
  color: #16a34a;
  font-size: 0.75rem;
}

/* Mobile Bottom CTA Bar */
.mobile-cta-bar {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #ffffff;
  padding: 12px 18px;
  border-top: 1px solid var(--color-border, #e2e8f0);
  box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.08);
  z-index: 800; /* Stays below ChatWidget (999) */
  justify-content: space-between;
  align-items: center;
}

.mobile-cta-price .label {
  font-size: 0.75rem;
  color: var(--color-muted, #64748b);
  display: block;
}

.mobile-cta-price .val {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--color-primary, #0052cc);
}

.mobile-cta-btn {
  padding: 10px 20px;
  font-size: 0.95rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  /* Margin on right to stay clear of floating chat widget on phones */
  margin-right: 68px;
}

/* Success Modal */
.success-modal {
  max-width: 500px;
}

.success-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.order-code {
  color: var(--color-primary, #0052cc);
  font-size: 1.05rem;
}

.success-details-card {
  background: #f8fafc;
  border-radius: var(--radius-lg, 12px);
  padding: 16px;
  margin: 16px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.detail-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.highlight-price {
  color: #0052cc;
  font-size: 1.05rem;
}

.success-note {
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.5;
}

/* Lightbox */
.lightbox-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.lightbox-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
}

.lightbox-img {
  max-width: 100%;
  max-height: 85vh;
  border-radius: var(--radius-lg, 12px);
  object-fit: contain;
}

.btn-close-lightbox {
  position: absolute;
  top: -40px;
  right: 0;
  background: none;
  border: none;
  color: #ffffff;
  font-size: 2.2rem;
  cursor: pointer;
}

/* Responsive Breakpoints */
@media (max-width: 992px) {
  .detail-layout {
    grid-template-columns: 1fr;
  }

  .detail-sidebar {
    position: static;
  }

  .mobile-cta-bar {
    display: flex;
  }

  .car-detail-page {
    padding-bottom: 120px;
  }
}

@media (max-width: 640px) {
  .title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .car-title {
    font-size: 1.6rem;
  }

  .specs-grid {
    grid-template-columns: 1fr;
  }
}
</style>
