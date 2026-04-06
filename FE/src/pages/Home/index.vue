<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-content">
        <span class="badge">#1 Nền tảng thuê xe tự lái</span>
        <h1 class="hero-title">Khám Phá Hành Trình Của Bạn Với <span>Sự Thoải Mái</span></h1>
        <p class="hero-subtitle">Mạng lưới thuê xe lớn nhất với hơn 1000+ loại xe cho mọi nhu cầu. Đặt xe nhanh chóng, giá cả minh bạch, giao xe tận nơi.</p>
        <div class="hero-actions">
          <button class="btn-primary size-lg">Tìm Xe Ngay <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
          <button class="btn-outline size-lg bg-white" @click="scrollToProcess">Xem Quy Trình</button>
        </div>
      </div>
      <!-- Decorative background elements -->
      <div class="hero-bg-shape"></div>
    </section>

    <!-- Search / Filter Component -->
    <section class="search-box-wrapper app-container">
      <div class="search-box card">
        <div class="search-field">
          <label><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> Tỉnh/Thành giao xe</label>
          <select class="custom-select" v-model="searchData.city">
            <option value="">Chọn khu vực...</option>
            <option value="sg">TP. Hồ Chí Minh</option>
            <option value="hn">Hà Nội</option>
            <option value="dn">Đà Nẵng</option>
            <option value="dl">Đà Lạt</option>
            <option value="nt">Nha Trang</option>
          </select>
        </div>
        <div class="search-separator"></div>
        <div class="search-field flex-2 relative">
          <label>Địa chỉ nhận xe chi tiết</label>
          <div class="input-with-icon">
            <input 
              type="text" 
              v-model="searchData.address" 
              @input="onAddressInput"
              placeholder="Nhập địa chỉ, sân bay, tên đường..." 
            />
            <div v-if="loadingSuggestions" class="loader-inline"></div>
          </div>
          <!-- Suggestions Dropdown -->
          <ul v-if="suggestions.length > 0" class="suggestions-list">
            <li v-for="(item, index) in suggestions" :key="index" @click="selectAddress(item)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>{{ item.display_name }}</span>
            </li>
          </ul>
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian lấy</label>
          <VueDatePicker 
            v-model="searchData.startDate" 
            :min-date="new Date()"
            placeholder="Chọn ngày lấy"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian trả</label>
          <VueDatePicker 
            v-model="searchData.endDate" 
            :min-date="searchData.startDate || new Date()"
            placeholder="Chọn ngày trả"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>
        <button class="btn-primary search-btn" @click="handleSearch">Tìm Kiếm</button>
      </div>
      <div v-if="dateError" class="error-msg">{{ dateError }}</div>
    </section>

    <!-- Rental Process Section -->
    <section id="process-section" class="process-section app-container" style="margin-top: 100px;">
      <div class="section-heading">
        <span class="sub-title">Quy trình đơn giản</span>
        <h2>Thuê xe chỉ với 4 bước</h2>
      </div>
      <div class="process-grid">
        <div class="process-item">
          <div class="icon-box blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
          </div>
          <h3>1. Chọn xe</h3>
          <p>Tìm kiếm và lựa chọn chiếc xe ưng ý từ bộ sưu tập đa dạng của chúng tôi.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box green">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <h3>2. Đặt lịch</h3>
          <p>Chọn thời gian thuê, địa điểm giao nhận xe và điền thông tin cá nhân.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box yellow">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          </div>
          <h3>3. Thanh toán</h3>
          <p>Xác nhận đơn và thực hiện thanh toán an toàn qua nhiều hình thức.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box red">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
          </div>
          <h3>4. Nhận xe</h3>
          <p>Nhận xe tại địa điểm đã chọn và bắt đầu hành trình tuyệt vời của bạn.</p>
        </div>
      </div>
    </section>

    <!-- Car Listing Section -->
    <section class="fleet-section app-container">
      <div class="section-heading">
        <span class="sub-title">Đội ngũ xe của chúng tôi</span>
        <h2>Dòng Xe Nổi Bật</h2>
        <p>Lựa chọn chiếc xe lý tưởng cho chuyến đi tiếp theo của gia đình bạn</p>
      </div>

      <div class="car-grid" v-if="!loading">
        <div class="car-card card" v-for="car in cars" :key="car.id">
          <div class="car-badge" v-if="car.type === 'SUV'">Gia đình</div>
          <div class="car-img-container">
            <img :src="car.image" :alt="car.name" />
          </div>
          <div class="car-info">
            <span class="type">{{ car.type }}</span>
            <h3 class="car-name">{{ car.name }}</h3>
            <div class="car-specs">
              <span class="spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m12 16 4-4-4-4"/><path d="M8 12h8"/></svg> Tự động</span>
              <span class="spec"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> 5 chỗ</span>
            </div>
            
            <div class="car-footer">
              <div class="price-wrapper">
                <span class="price">{{ (car.price_per_day / 1000).toLocaleString() }}k</span>
                <span class="unit">/ngày</span>
              </div>
              <button class="btn-primary">Đặt ngay</button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải dữ liệu xe...</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from 'vue';
import baseRequest from '../../core/baseRequest';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

const cars = ref([]);
const loading = ref(true);
const dateError = ref('');
const suggestions = ref([]);
const loadingSuggestions = ref(false);
let timeout = null;

const searchData = reactive({
  city: '',
  address: '',
  startDate: null,
  endDate: null
});

onMounted(async () => {
  try {
    const response = await baseRequest.get('cars');
    if (response.data && response.data.status) {
      cars.value = response.data.data;
    }
  } catch (error) {
    console.error("Error fetching cars:", error);
  } finally {
    loading.value = false;
  }
});

watch(() => searchData.endDate, (newVal) => {
  if (newVal && searchData.startDate && newVal <= searchData.startDate) {
    dateError.value = 'Ngày trả phải sau ngày lấy xe ít nhất 1 giờ!';
  } else {
    dateError.value = '';
  }
});

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
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=vn`);
      const data = await res.json();
      suggestions.value = data;
    } catch (err) {
      console.error("OSM Error:", err);
    } finally {
      loadingSuggestions.value = false;
    }
  }, 500);
};

const selectAddress = (item) => {
  searchData.address = item.display_name;
  suggestions.value = [];
};

const handleSearch = () => {
  if (!searchData.startDate || !searchData.endDate) {
    dateError.value = 'Vui lòng chọn đầy đủ thời gian!';
    return;
  }
  if (searchData.endDate <= searchData.startDate) {
    dateError.value = 'Ngày trả không hợp lệ!';
    return;
  }
  
  console.log("Searching with:", searchData);
  alert("Tính năng tìm kiếm đang được phát triển!");
};

const scrollToProcess = () => {
  const el = document.getElementById('process-section');
  if (el) el.scrollIntoView({ behavior: 'smooth' });
};
</script>

<style scoped>
/* Hero Section */
.hero-section {
  position: relative;
  padding: 140px 24px 120px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  overflow: hidden;
  text-align: center;
}

.hero-bg-shape {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 800px;
  height: 800px;
  background: radial-gradient(circle, rgba(37,99,235,0.08) 0%, rgba(37,99,235,0) 70%);
  border-radius: 50%;
  z-index: 0;
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 800px;
  margin: 0 auto;
}

.badge {
  display: inline-block;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  padding: 8px 16px;
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 700;
  margin-bottom: 32px;
  box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
}

.hero-title {
  font-size: 4rem;
  font-weight: 800;
  line-height: 1.1;
  color: #0f172a;
  margin-bottom: 24px;
  letter-spacing: -2px;
}

.hero-title span {
  color: var(--color-primary);
  position: relative;
}

.hero-title span::after {
  content: "";
  position: absolute;
  bottom: 8px;
  left: 0;
  width: 100%;
  height: 8px;
  background: rgba(37, 99, 235, 0.15);
  z-index: -1;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 48px;
  max-width: 650px;
  margin-inline: auto;
}

.hero-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.size-lg {
  padding: 16px 36px;
  font-size: 1.125rem;
  font-weight: 600;
}

.bg-white {
  background-color: white;
  border: 1px solid #e2e8f0;
  box-shadow: var(--shadow-sm);
  color: #334155;
}

/* Search Box */
.search-box-wrapper {
  margin-top: -60px;
  position: relative;
  z-index: 50; /* Tăng z-index để dropdown không bị đè */
}

.search-box {
  display: flex;
  padding: 16px;
  align-items: stretch;
  background: white;
  gap: 12px;
  border-radius: 20px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.search-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 8px 16px;
  transition: all 0.2s;
  border-radius: 12px;
  position: relative;
}

.search-field:hover:not(.flex-2) {
  background: #f8fafc;
}

.flex-2 {
  flex: 1.5;
}

.search-field label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.search-field label svg {
  color: var(--color-primary);
}

.custom-select, .search-field input {
  border: none;
  background: transparent;
  font-size: 1rem;
  color: #1e293b;
  outline: none;
  font-family: inherit;
  font-weight: 600;
  width: 100%;
}

.search-field input::placeholder {
  font-weight: 400;
  color: #94a3b8;
}

.search-separator {
  width: 1px;
  background: #f1f5f9;
  margin: 12px 0;
}

.search-btn {
  margin-left: 8px;
  padding: 0 40px;
  border-radius: 14px;
  font-weight: 700;
}

.error-msg {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 12px;
  font-weight: 600;
  text-align: center;
}

/* Address Suggestions UI */
.suggestions-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  margin-top: 8px;
  list-style: none;
  padding: 8px;
  z-index: 1000;
  max-height: 250px;
  overflow-y: auto;
  border: 1px solid #f1f5f9;
}

.suggestions-list li {
  padding: 10px 12px;
  font-size: 0.875rem;
  color: #475569;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  transition: background 0.2s;
}

.suggestions-list li:hover {
  background: #f1f5f9;
  color: var(--color-primary);
}

.suggestions-list li svg {
  margin-top: 3px;
  flex-shrink: 0;
  color: #94a3b8;
}

.loader-inline {
  width: 16px;
  height: 16px;
  border: 2px solid #f1f5f9;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  vertical-align: middle;
  margin-left: 5px;
}

/* Process Section */
.process-section {
  padding: 100px 24px;
  background: white;
}

.sub-title {
  display: block;
  color: var(--color-primary);
  font-weight: 700;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 12px;
}

.process-grid {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 60px;
  gap: 20px;
}

.process-item {
  flex: 1;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.icon-box {
  width: 80px;
  height: 80px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.process-item:hover .icon-box {
  transform: translateY(-10px) rotate(5deg);
}

.icon-box.blue { background: rgba(37, 99, 235, 0.1); color: var(--color-primary); }
.icon-box.green { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.icon-box.yellow { background: rgba(234, 179, 8, 0.1); color: #ca8a04; }
.icon-box.red { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

.process-item h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 12px;
}

.process-item p {
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.5;
}

.process-arrow {
  color: #cbd5e1;
}

/* Fleet Section */
.fleet-section {
  padding: 80px 24px 120px;
  background: #f8fafc;
}

.section-heading {
  text-align: center;
  margin-bottom: 64px;
}

.section-heading h2 {
  font-size: 3rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 16px;
  letter-spacing: -1.5px;
}

.section-heading p {
  color: #64748b;
  font-size: 1.125rem;
  max-width: 600px;
  margin: 0 auto;
}

.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 32px;
}

.car-card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  transition: transform 0.3s, box-shadow 0.3s;
  border: 1px solid #f1f5f9;
}

.car-card:hover {
  transform: translateY(-12px);
  box-shadow: 0 30px 40px -10px rgba(15, 23, 42, 0.08);
}

.car-badge {
  position: absolute;
  top: 20px;
  left: 20px;
  background: #0f172a;
  color: white;
  padding: 6px 14px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  z-index: 2;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.car-img-container {
  width: 100%;
  height: 220px;
  overflow: hidden;
  background: #f8fafc;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-img-container img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  transition: transform 0.5s ease;
}

.car-card:hover .car-img-container img {
  transform: scale(1.1);
}

.car-info {
  padding: 32px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.type {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: var(--color-primary);
  font-weight: 700;
  margin-bottom: 12px;
}

.car-name {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 20px;
}

.car-specs {
  display: flex;
  gap: 20px;
  margin-bottom: 32px;
}

.spec {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: #475569;
  font-weight: 500;
}

.spec svg {
  color: #94a3b8;
}

.car-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 24px;
  border-top: 1px solid #f1f5f9;
}

.price {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
}

.unit {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.loading-state {
  text-align: center;
  padding: 100px;
  color: #64748b;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f1f5f9;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  margin: 0 auto 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Datepicker Overrides - FIX OVERLAP */
:deep(.dp__theme_light) {
  --dp-primary-color: var(--color-primary);
  --dp-border-radius: 8px;
  --dp-button-height: 40px;
}
:deep(.dp__input) {
  border: none !important;
  font-weight: 600;
  font-size: 1rem;
  padding: 4px 0 4px 35px !important; /* Thêm padding-left 35px để tránh đè icon */
  font-family: inherit;
  background: transparent;
}
/* Căn chỉnh icon lịch của thư viện */
:deep(.dp__input_icon) {
  left: 0 !important;
  padding: 0 !important;
  color: #94a3b8;
  width: 20px;
}

@media (max-width: 1200px) {
  .process-arrow { display: none; }
  .process-grid { flex-wrap: wrap; justify-content: center; gap: 40px; }
  .process-item { flex: 0 0 200px; }
}

@media (max-width: 992px) {
  .search-box { flex-direction: column; align-items: stretch; padding: 24px; border-radius: 30px; }
  .search-separator { width: 100%; height: 1px; margin: 8px 0; }
  .search-btn { margin-top: 24px; margin-left: 0; padding: 18px; }
  .hero-title { font-size: 3.5rem; }
}

@media (max-width: 768px) {
  .hero-title { font-size: 2.75rem; }
  .hero-actions { flex-direction: column; align-items: stretch; }
  .process-item { flex: 0 0 100%; }
}
</style>
