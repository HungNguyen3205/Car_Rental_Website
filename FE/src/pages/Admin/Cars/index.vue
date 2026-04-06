<template>
  <div class="admin-page">
    <!-- Hero Section Simplified for Admin -->
    <section class="hero-section">
      <div class="hero-content">
        <span class="badge">Khu vực Ban Quản Trị</span>
        <h1 class="hero-title">Quản Lý Trạng Thái <span>Phương Tiện</span></h1>
        <p class="hero-subtitle">Theo dõi và cập nhật trạng thái các phương tiện cho thuê trong thời gian thực. Bạn có quyền được thay đổi thông tin hệ thống.</p>
      </div>
      <div class="hero-bg-shape"></div>
    </section>

    <!-- Search Box for Admin -->
    <section class="search-box-wrapper app-container">
      <div class="search-box card">
        <div class="search-field">
          <label>Tìm kiếm nhanh</label>
          <input type="text" placeholder="Nhập tên xe, biển số xe..." />
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Lọc trạng thái</label>
          <select class="custom-select">
            <option value="">Tất cả trạng thái</option>
            <option value="available">Sẵn sàng (Trống)</option>
            <option value="rented">Đang cho thuê</option>
          </select>
        </div>
        <button class="btn-primary search-btn">Tìm Kiếm</button>
      </div>
    </section>

    <!-- Car Listing Section with Admin Controls -->
    <section class="fleet-section app-container">
      <div class="section-heading" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="text-align: left;">
          <h2>Danh Sách Phương Tiện</h2>
          <p>Quản lý toàn bộ danh sách xe hiển thị cho khách hàng</p>
        </div>
        <button class="btn-primary">+ Thêm xe mới</button>
      </div>

      <div class="car-grid" v-if="!loading">
        <div class="car-card card" v-for="car in cars" :key="car.id">
          <!-- Status Badge -->
          <div class="car-badge" :class="(!car.status || car.status === 'available') ? 'bg-success' : 'bg-warning'">
            {{ (!car.status || car.status === 'available') ? 'Sẵn sàng' : 'Đang thuê' }}
          </div>
          
          <div class="car-img-container">
            <img :src="car.image" :alt="car.name" />
          </div>
          <div class="car-info">
            <span class="type">{{ car.type }} (ID-0{{ car.id }})</span>
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
            </div>

            <!-- Admin Actions -->
            <div class="admin-actions">
              <button class="btn-outline btn-sm">Chỉnh sửa</button>
              <button class="btn-primary btn-sm status-toggle" @click="toggleStatus(car)">
                Đổi trạng thái
              </button>
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
import baseRequest from '../../../core/baseRequest';

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

const toggleStatus = (car) => {
  // Toggle the specific car status locally for simulation
  if(!car.status || car.status === 'available') {
    car.status = 'rented';
  } else {
    car.status = 'available';
  }
  // This would ideally fire a PUT/POST request to backend
  alert(`Đổi thành công trạng thái xe ${car.name} thành: ${car.status === 'available' ? 'Sẵn sàng' : 'Đang thuê'}`);
};
</script>

<style scoped>
/* Hero Section (Matching Client but slightly shorter) */
.hero-section {
  position: relative;
  padding: 100px 24px 100px;
  background: linear-gradient(to right, #f8fafc, #cbd5e1);
  overflow: hidden;
  text-align: center;
}

.hero-bg-shape {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 800px;
  height: 800px;
  background: radial-gradient(circle, rgba(37,99,235,0.05) 0%, rgba(37,99,235,0) 70%);
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
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.2;
  color: #0f172a;
  margin-bottom: 16px;
  letter-spacing: -1px;
}

.hero-title span {
  color: var(--color-primary);
}

.hero-subtitle {
  font-size: 1.125rem;
  color: #475569;
  line-height: 1.6;
  max-width: 600px;
  margin-inline: auto;
}

/* Search Box (Admin Version) */
.search-box-wrapper {
  margin-top: -36px;
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

.search-field label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.search-field input, .custom-select {
  border: none;
  background: transparent;
  font-size: 0.95rem;
  color: var(--color-text-main);
  outline: none;
  font-family: inherit;
  font-weight: 500;
  width: 100%;
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
  padding: 60px 24px;
}

.section-heading h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.section-heading p {
  color: var(--color-text-muted);
}

.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 32px;
  margin-top: 32px;
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
  color: white;
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  z-index: 2;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.bg-success {
  background: #16a34a;
}

.bg-warning {
  background: #ea580c;
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
  margin-bottom: 16px;
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
  padding-bottom: 16px;
  border-bottom: 1px dashed var(--color-border);
  margin-bottom: 16px;
}

.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: 2px;
}

.price {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-primary);
}

.unit {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  font-weight: 500;
}

/* Admin Actions Buttons on Card */
.admin-actions {
  display: flex;
  gap: 10px;
  margin-top: auto;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 0.875rem;
  flex: 1;
  text-align: center;
  justify-content: center;
}

.status-toggle {
  background: #475569;
}

.status-toggle:hover {
  background: #334155;
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
  .section-heading { flex-direction: column; align-items: flex-start; gap: 16px; }
}
</style>
