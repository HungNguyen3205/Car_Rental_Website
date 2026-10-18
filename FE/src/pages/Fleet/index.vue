<template>
  <div class="fleet-page">
    <!-- Cinematic Hero Section -->
    <section class="fleet-hero">
      <div class="hero-content app-container text-center animate-up">
        <span class="badge-pill">Bộ Sưu Tập Xe Điện 100%</span>
        <h1>Đội Ngũ Xe Điện <span>Hiện Đại</span></h1>
        <p>Khám phá hệ sinh thái xe điện thông minh hàng đầu với công nghệ an toàn, tiện nghi và thân thiện với môi trường.</p>
      </div>
    </section>

    <!-- Filters & Search Bar -->
    <section class="fleet-controls app-container">
      <div class="controls-card card">
        <div class="controls-top">
          <!-- Search input -->
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo tên xe (VF8, VF9, Camry...)"
            />
            <button v-if="searchQuery" class="clear-search" @click="searchQuery = ''" aria-label="Xóa tìm kiếm">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Sort dropdown -->
          <div class="sort-box">
            <i class="fa-solid fa-arrow-down-short-wide"></i>
            <select v-model="sortBy" class="sort-select">
              <option value="default">Phổ biến nhất</option>
              <option value="price-asc">Giá thuê: Thấp đến Cao</option>
              <option value="price-desc">Giá thuê: Cao đến Thấp</option>
            </select>
          </div>
        </div>

        <!-- Category Pills -->
        <div class="category-pills">
          <button 
            v-for="cat in availableCategories" 
            :key="cat" 
            class="cat-pill" 
            :class="{ 'active': selectedType === cat }"
            @click="selectedType = cat"
          >
            {{ cat }}
            <span class="pill-count">{{ getCategoryCount(cat) }}</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Product Showcase Grid -->
    <section class="fleet-showcase app-container">
      <div v-if="loading" class="showcase-loading">
        <div class="spinner"></div>
        <p>Đang chuẩn bị danh sách phương tiện...</p>
      </div>

      <!-- API Error State with Retry -->
      <div v-else-if="fetchError" class="empty-fleet card text-center animate-in" style="border: 1px dashed #fca5a5; background: #fff5f5;">
        <div class="empty-icon" style="color: #ef4444; background: #fee2e2;">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3>Không thể tải danh sách phương tiện</h3>
        <p>{{ fetchError }}</p>
        <button class="btn-primary" @click="fetchAllCars" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-rotate-right"></i> Thử lại
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredCars.length === 0" class="empty-fleet card text-center animate-in">
        <div class="empty-icon">
          <i class="fa-solid fa-car-tunnel"></i>
        </div>
        <h3>Không tìm thấy mẫu xe phù hợp</h3>
        <p>Không có xe nào khớp với tiêu chí tìm kiếm hoặc bộ lọc hiện tại của bạn.</p>
        <button class="btn-primary" @click="resetFilters">
          <i class="fa-solid fa-rotate-left"></i> Xóa tất cả bộ lọc
        </button>
      </div>

      <!-- Car Grid -->
      <div v-else class="showcase-grid">
        <div 
          class="fleet-car-card card card-interactive animate-in" 
          v-for="car in filteredCars" 
          :key="car.id"
        >
          <div class="car-img-box">
            <router-link :to="'/fleet/' + car.id" class="img-link">
              <img 
                :src="car.image" 
                :alt="car.name" 
                loading="lazy" 
                @error="handleImgError($event)"
              />
            </router-link>
            <span class="type-tag">{{ car.type }}</span>
            <span class="status-indicator" :class="car.status === 'available' ? 'available' : 'maintenance'">
              {{ car.status === 'available' ? 'Sẵn sàng' : 'Bảo trì' }}
            </span>
          </div>
          
          <div class="car-content">
            <div class="car-headline">
              <h3 class="car-name">
                <router-link :to="'/fleet/' + car.id" class="car-name-link">{{ car.name }}</router-link>
              </h3>
              <span class="city-label">
                <i class="fa-solid fa-location-dot"></i> {{ car.cityDisplay }}
              </span>
            </div>
            
            <div class="car-specs-grid">
              <div class="spec-col">
                <i :class="car.fuel_type === 'gasoline' || car.fuel_type === 'diesel' ? 'fa-solid fa-gas-pump' : 'fa-solid fa-bolt'"></i>
                <div>
                  <span class="label">Nhiên liệu</span>
                  <strong>{{ car.fuel_type === 'gasoline' ? 'Xăng' : (car.fuel_type === 'diesel' ? 'Dầu Diesel' : (car.fuel_type === 'hybrid' ? 'Hybrid' : 'Điện 100%')) }}</strong>
                </div>
              </div>
              <div class="spec-col">
                <i class="fa-solid fa-users"></i>
                <div>
                  <span class="label">Sức chứa</span>
                  <strong>{{ car.seats ? car.seats + ' Chỗ ngồi' : 'Đang cập nhật' }}</strong>
                </div>
              </div>
              <div class="spec-col">
                <i class="fa-solid fa-gauge"></i>
                <div>
                  <span class="label">Tầm hoạt động</span>
                  <strong>{{ car.range_per_charge || car.range || (car.fuel_type === 'gasoline' ? 'Chuẩn' : 'Đang cập nhật') }}</strong>
                </div>
              </div>
              <div class="spec-col">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                  <span class="label">Bảo hiểm</span>
                  <strong>Đã bao gồm</strong>
                </div>
              </div>
            </div>
            
            <div class="car-actions">
              <div class="price-info">
                <span class="price-val">{{ Number(car.price_per_day).toLocaleString('vi-VN') }}đ</span>
                <span class="price-sub">/ ngày</span>
              </div>
              
              <div class="btn-group">
                <router-link :to="'/fleet/' + car.id" class="btn-secondary-sm">
                  <i class="fa-solid fa-circle-info"></i> Chi tiết
                </router-link>
                <router-link 
                  :to="{ path: '/', query: { car_id: car.id, city: car.city } }" 
                  class="btn-primary-sm"
                >
                  <span>Thuê xe</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick View Modal -->
    <div v-if="selectedQuickView" class="modal-overlay" @click.self="selectedQuickView = null">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <h3>Thông Tin Phương Tiện</h3>
          <button type="button" class="btn-close" @click="selectedQuickView = null">&times;</button>
        </div>
        <div class="modal-body">
          <div class="modal-car-preview">
            <img :src="selectedQuickView.image" :alt="selectedQuickView.name" />
          </div>
          <div class="modal-car-details">
            <h2>{{ selectedQuickView.name }}</h2>
            <p class="modal-type">{{ selectedQuickView.type }} • Khu vực: {{ selectedQuickView.cityDisplay }}</p>
            
            <div class="specs-summary">
              <div class="spec-box">
                <i class="fa-solid fa-users"></i>
                <span>Số chỗ: {{ selectedQuickView.seats || 5 }} chỗ</span>
              </div>
              <div class="spec-box">
                <i class="fa-solid fa-bolt"></i>
                <span>Nhiên liệu: Điện 100%</span>
              </div>
              <div class="spec-box">
                <i class="fa-solid fa-circle-check"></i>
                <span>Trạng thái: {{ selectedQuickView.status === 'available' ? 'Sẵn sàng giao' : 'Đang bảo dưỡng' }}</span>
              </div>
            </div>

            <div class="modal-price-row flex-between">
              <div>
                <span class="label">Đơn giá thuê tiêu chuẩn</span>
                <h3>{{ Number(selectedQuickView.price_per_day).toLocaleString('vi-VN') }} đ <small>/ ngày</small></h3>
              </div>
              <div class="modal-actions" style="display: flex; gap: 8px;">
                <router-link 
                  :to="'/fleet/' + selectedQuickView.id"
                  class="btn-outline"
                  @click="selectedQuickView = null"
                  style="display: inline-flex; align-items: center; gap: 6px;"
                >
                  <i class="fa-solid fa-circle-info"></i> Xem chi tiết
                </router-link>
                <router-link 
                  :to="{ path: '/', query: { car_id: selectedQuickView.id, city: selectedQuickView.city } }"
                  class="btn-primary"
                  @click="selectedQuickView = null"
                >
                  Đặt xe ngay
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../core/baseRequest';
import { resolveCarImage, handleCarImgError } from '../../core/carImageHelper';

const loading = ref(true);
const fetchError = ref(null);
const cars = ref([]);
const selectedType = ref('Tất cả');
const sortBy = ref('default');
const searchQuery = ref('');
const selectedQuickView = ref(null);

const handleImgError = (event) => {
  handleCarImgError(event);
};

const openQuickView = (car) => {
  selectedQuickView.value = car;
};

const fetchAllCars = async () => {
  loading.value = true;
  fetchError.value = null;
  try {
    const response = await baseRequest.get('client/car/data');
    if (response.data && response.data.status && Array.isArray(response.data.data)) {
      cars.value = response.data.data.map(car => {
        return {
          ...car,
          name: car.name || car.ten_xe || 'VinFast Model',
          price_per_day: car.price_per_day || car.gia_thue || 1000000,
          image: resolveCarImage(car),
          type: car.category_name || car.type || 'SUV',
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
      fetchError.value = response.data?.message || "Không thể tải danh sách phương tiện từ máy chủ.";
    }
  } catch (error) {
    console.error("Lỗi khi tải danh sách xe:", error);
    fetchError.value = "Không thể kết nối máy chủ để tải danh mục xe. Vui lòng thử lại.";
  } finally {
    loading.value = false;
  }
};

const availableCategories = computed(() => {
  const types = new Set();
  cars.value.forEach(c => {
    if (c.type) types.add(c.type);
  });
  return ['Tất cả', ...Array.from(types)];
});

const getCategoryCount = (cat) => {
  if (cat === 'Tất cả') return cars.value.length;
  return cars.value.filter(c => c.type === cat).length;
};

const filteredCars = computed(() => {
  let result = [...cars.value];

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    result = result.filter(c => c.name.toLowerCase().includes(q) || (c.cityDisplay && c.cityDisplay.toLowerCase().includes(q)));
  }

  // Type filter
  if (selectedType.value !== 'Tất cả') {
    result = result.filter(c => c.type === selectedType.value);
  }

  // Sorting
  if (sortBy.value === 'price-asc') {
    result.sort((a, b) => Number(a.price_per_day) - Number(b.price_per_day));
  } else if (sortBy.value === 'price-desc') {
    result.sort((a, b) => Number(b.price_per_day) - Number(a.price_per_day));
  }

  return result;
});

const resetFilters = () => {
  selectedType.value = 'Tất cả';
  sortBy.value = 'default';
  searchQuery.value = '';
};

onMounted(() => {
  fetchAllCars();
});
</script>

<style scoped>
.fleet-page {
  padding-bottom: 80px;
  min-height: 100vh;
}

.fleet-hero {
  padding: 140px 0 60px;
  background: linear-gradient(180deg, #ffffff 0%, var(--bg-main) 100%);
}

.badge-pill {
  display: inline-block;
  padding: 6px 16px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 16px;
}

.fleet-hero h1 {
  font-size: clamp(2.2rem, 4.5vw, 3.4rem);
  letter-spacing: -0.03em;
  margin-bottom: 16px;
}

.fleet-hero h1 span {
  color: var(--color-primary);
}

.fleet-hero p {
  max-width: 650px;
  margin: 0 auto;
  font-size: 1.1rem;
}

/* Controls */
.fleet-controls {
  margin-bottom: 40px;
}

.controls-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  background: #ffffff;
}

.controls-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-md);
  flex: 1;
  max-width: 480px;
  border: 1px solid transparent;
  transition: all var(--duration-fast);
}

.search-box:focus-within {
  background: #ffffff;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.search-box i {
  color: var(--color-text-muted);
}

.search-box input {
  width: 100%;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.925rem;
}

.clear-search {
  color: var(--color-text-muted);
}

.sort-box {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-md);
}

.sort-box i {
  color: var(--color-primary);
}

.sort-select {
  border: none;
  background: transparent;
  font-weight: 600;
  font-size: 0.885rem;
  color: var(--color-text-main);
  outline: none;
  cursor: pointer;
}

.category-pills {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.cat-pill {
  padding: 8px 18px;
  border-radius: var(--radius-full);
  background: var(--bg-surface-sub);
  color: var(--color-text-muted);
  font-weight: 600;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all var(--duration-fast);
}

.pill-count {
  font-size: 0.75rem;
  background: rgba(0, 0, 0, 0.06);
  padding: 2px 7px;
  border-radius: 10px;
}

.cat-pill:hover {
  background: #e2e8f0;
  color: var(--color-text-main);
}

.cat-pill.active {
  background: var(--color-primary);
  color: #ffffff;
}

.cat-pill.active .pill-count {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* Showcase Grid */
.showcase-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 32px;
}

.fleet-car-card {
  padding: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.car-img-box {
  position: relative;
  background: linear-gradient(180deg, #f8fafc 0%, #eef2f6 100%);
  padding: 32px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 16 / 9;
}

.car-img-box img {
  max-height: 100%;
  object-fit: contain;
  transition: transform var(--duration-slow) var(--ease-spring);
}

.fleet-car-card:hover .car-img-box img {
  transform: scale(1.06);
}

.type-tag {
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

.status-indicator {
  position: absolute;
  top: 16px;
  right: 16px;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
}

.status-indicator.available {
  background: var(--color-success-bg);
  color: var(--color-success);
  border: 1px solid var(--color-success-border);
}

.status-indicator.maintenance {
  background: var(--color-warning-bg);
  color: var(--color-warning);
  border: 1px solid var(--color-warning-border);
}

.car-content {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.car-headline {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.car-name {
  font-size: 1.35rem;
  font-weight: 800;
}

.city-label {
  font-size: 0.8rem;
  color: var(--color-primary);
  font-weight: 600;
  background: var(--color-primary-light);
  padding: 4px 10px;
  border-radius: var(--radius-xs);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.car-specs-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  padding-bottom: 20px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--color-border-subtle);
}

.spec-col {
  display: flex;
  align-items: center;
  gap: 10px;
}

.spec-col i {
  color: var(--color-primary);
  font-size: 1rem;
}

.spec-col .label {
  display: block;
  font-size: 0.725rem;
  color: var(--color-text-muted);
}

.spec-col strong {
  font-size: 0.85rem;
  color: var(--color-text-main);
}

.car-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
}

.price-val {
  font-size: 1.35rem;
  font-weight: 800;
  font-family: var(--font-heading);
  color: var(--color-text-main);
}

.price-sub {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.btn-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary-sm {
  padding: 8px 14px;
  background: var(--bg-surface-sub);
  color: var(--color-text-main);
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.85rem;
}

.btn-secondary-sm:hover {
  background: #e2e8f0;
}

.btn-primary-sm {
  padding: 8px 18px;
  background: var(--grad-active);
  color: #ffffff !important;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: var(--shadow-soft);
}

.btn-primary-sm:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

/* Empty State */
.empty-fleet {
  padding: 60px 24px;
  max-width: 580px;
  margin: 40px auto;
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  margin: 0 auto 20px;
}

.empty-fleet h3 {
  font-size: 1.4rem;
  margin-bottom: 8px;
}

.empty-fleet p {
  margin-bottom: 24px;
}

/* Modal Quick View */
.modal-car-preview {
  background: var(--bg-surface-sub);
  border-radius: var(--radius-lg);
  padding: 24px;
  text-align: center;
  margin-bottom: 20px;
}

.modal-car-preview img {
  max-height: 200px;
  margin: 0 auto;
}

.modal-car-details h2 {
  font-size: 1.6rem;
  margin-bottom: 4px;
}

.modal-type {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin-bottom: 20px;
}

.specs-summary {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: var(--bg-surface-sub);
  padding: 16px;
  border-radius: var(--radius-md);
  margin-bottom: 24px;
}

.spec-box {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
}

.spec-box i {
  color: var(--color-primary);
  width: 20px;
}

.modal-price-row {
  border-top: 1px solid var(--color-border);
  padding-top: 20px;
}

.modal-price-row .label {
  display: block;
  font-size: 0.775rem;
  color: var(--color-text-muted);
}

.modal-price-row h3 {
  font-size: 1.4rem;
  color: var(--color-primary);
}

.modal-price-row small {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.showcase-loading {
  text-align: center;
  padding: 60px 0;
}

.showcase-loading .spinner {
  margin: 0 auto 16px;
}

@media (max-width: 768px) {
  .showcase-grid {
    grid-template-columns: 1fr;
  }
  .controls-top {
    flex-direction: column;
    align-items: stretch;
  }
  .search-box {
    max-width: 100%;
  }
}
</style>
