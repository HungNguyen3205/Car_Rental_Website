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
          <button class="btn-outline size-lg bg-white">Xem Quy Trình</button>
        </div>
      </div>
      <!-- Decorative background elements -->
      <div class="hero-bg-shape"></div>
    </section>

    <!-- Search / Filter Component -->
    <section class="search-box-wrapper app-container">
      <div class="search-box card">
        <div class="search-field">
          <label>Tỉnh/Thành giao xe</label>
          <select class="custom-select">
            <option value="">Chọn khu vực...</option>
            <option value="sg">TP. Hồ Chí Minh</option>
            <option value="hn">Hà Nội</option>
            <option value="dn">Đà Nẵng</option>
            <option value="dl">Đà Lạt</option>
            <option value="nt">Nha Trang</option>
          </select>
        </div>
        <div class="search-separator"></div>
        <div class="search-field flex-2">
          <label>Địa chỉ nhận xe chi tiết</label>
          <input type="text" placeholder="Nhập địa chỉ, sân bay, tên đường..." />
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian lấy</label>
          <input type="datetime-local" class="custom-date" />
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian trả</label>
          <input type="datetime-local" class="custom-date" />
        </div>
        <button class="btn-primary search-btn">Tìm Kiếm</button>
      </div>
    </section>

    <!-- Car Listing Section -->
    <section class="fleet-section app-container">
      <div class="section-heading">
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
                <span class="price">{{ (car.price / 1000).toLocaleString() }}k</span>
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
import { ref, onMounted } from 'vue';
import baseRequest from '../../core/baseRequest';

const cars = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await baseRequest.get('cars');
    if (response.data) {
      cars.value = response.data;
    }
  } catch (error) {
    console.error("Error fetching cars:", error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* Hero Section */
.hero-section {
  position: relative;
  padding: 140px 24px 120px;
  background: linear-gradient(to right, #f1f5f9, #e2e8f0);
  overflow: hidden;
  text-align: center;
}

.hero-bg-shape {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 800px;
  height: 800px;
  background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, rgba(37,99,235,0) 70%);
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
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 24px;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1.2;
  color: #0f172a;
  margin-bottom: 24px;
  letter-spacing: -1px;
}

.hero-title span {
  color: var(--color-primary);
}

.hero-subtitle {
  font-size: 1.125rem;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 40px;
  max-width: 600px;
  margin-inline: auto;
}

.hero-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.size-lg {
  padding: 14px 28px;
  font-size: 1.125rem;
}

.bg-white {
  background-color: white;
  border-color: transparent;
  box-shadow: var(--shadow-sm);
  color: #334155;
}
.bg-white:hover {
  background-color: #f8f9fa;
  color: #0f172a;
}

/* Search Box */
.search-box-wrapper {
  margin-top: -50px;
  position: relative;
  z-index: 10;
}

.search-box {
  display: flex;
  padding: 12px;
  align-items: stretch;
  background: white;
  gap: 12px;
}

.search-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 8px 12px;
}

.flex-2 {
  flex: 1.5;
}

.search-field label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.search-field input, .custom-select, .custom-date {
  border: none;
  background: transparent;
  font-size: 0.95rem;
  color: var(--color-text-main);
  outline: none;
  font-family: inherit;
  font-weight: 500;
  width: 100%;
}

.search-field input::placeholder {
  font-weight: 400;
  color: #94a3b8;
}

.search-separator {
  width: 1px;
  background: var(--color-border);
  margin: 8px 0;
}

.search-btn {
  margin-left: 8px;
  padding: 0 32px;
}

/* Fleet Section */
.fleet-section {
  padding: 80px 24px;
}

.section-heading {
  text-align: center;
  margin-bottom: 48px;
}

.section-heading h2 {
  font-size: 2.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.section-heading p {
  color: var(--color-text-muted);
  font-size: 1.125rem;
}

.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 32px;
}

.car-card {
  position: relative;
  display: flex;
  flex-direction: column;
}

.car-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  background: var(--color-secondary);
  color: white;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  z-index: 2;
}

.car-img-container {
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: #f1f5f9;
}

.car-img-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.car-card:hover .car-img-container img {
  transform: scale(1.05);
}

.car-info {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.type {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--color-text-muted);
  font-weight: 600;
  margin-bottom: 8px;
}

.car-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 16px;
}

.car-specs {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
}

.spec {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.875rem;
  color: #475569;
}

.spec svg {
  color: var(--color-primary);
}

.car-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: 2px;
}

.price {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-primary);
}

.unit {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  font-weight: 500;
}

.loading-state {
  text-align: center;
  padding: 60px;
  color: var(--color-text-muted);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  margin: 0 auto 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 992px) {
  .search-box { flex-direction: column; align-items: stretch; padding: 24px; }
  .search-separator { width: 100%; height: 1px; margin: 8px 0; }
  .search-btn { margin-top: 16px; margin-left: 0; padding: 14px; }
}
@media (max-width: 768px) {
  .hero-title { font-size: 2.5rem; }
  .hero-actions { flex-direction: column; }
}
</style>
