<template>
  <section id="fleet-showcase-section" class="featured-fleet-section app-container" aria-label="Bộ sưu tập xe">
    <!-- Section Heading Row (Reference Video Section D) -->
    <div class="fleet-heading-row">
      <div class="heading-left">
        <span class="eyebrow-text">PHÂN KHÚC ĐA DẠNG</span>
        <h2 class="section-title">Bộ Sưu Tập Xe Tuyển Chọn</h2>
      </div>
      <div class="heading-right">
        <p class="section-desc">
          Mọi dòng xe đều được bảo dưỡng chính hãng, kiểm định 150 điểm kỹ thuật và vệ sinh khử khuẩn trước mỗi chuyến đi.
        </p>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="category-tabs-bar" role="tablist" aria-label="Lọc theo loại xe">
      <button
        type="button"
        role="tab"
        :aria-selected="selectedCategory === 'all'"
        class="cat-tab-btn"
        :class="{ 'is-active': selectedCategory === 'all' }"
        @click="selectedCategory = 'all'"
      >
        Tất cả
      </button>
      <button
        v-for="cat in availableCategories"
        :key="cat.id"
        type="button"
        role="tab"
        :aria-selected="selectedCategory === cat.name"
        class="cat-tab-btn"
        :class="{ 'is-active': selectedCategory === cat.name }"
        @click="selectedCategory = cat.name"
      >
        {{ cat.name }}
      </button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="fleet-cards-grid">
      <div v-for="n in 6" :key="n" class="fleet-card skeleton-card">
        <div class="skeleton-shimmer skeleton-header"></div>
        <div class="skeleton-shimmer skeleton-image"></div>
        <div class="skeleton-shimmer skeleton-footer"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredCars.length === 0" class="empty-fleet-box">
      <i class="fa-solid fa-car-tunnel empty-icon"></i>
      <h3>Chưa có xe khả dụng trong phân khúc này</h3>
      <p>Quý khách vui lòng chọn phân khúc khác hoặc liên hệ hotline để được hỗ trợ điều xe.</p>
      <button type="button" class="btn-reset-filter" @click="selectedCategory = 'all'">
        <span>Xem tất cả xe</span>
      </button>
    </div>

    <!-- Car Cards Grid -->
    <div v-else class="fleet-cards-grid">
      <article
        v-for="car in filteredCars"
        :key="car.id"
        class="fleet-card"
      >
        <!-- Card Top: Name & Quick Link -->
        <div class="card-header-row">
          <div class="car-title-group">
            <h3 class="car-card-name">
              <router-link :to="'/fleet/' + car.id">{{ car.name }}</router-link>
            </h3>
            <span class="car-category-sub">{{ car.category_name || car.type || 'SUV Hạng Sang' }}</span>
          </div>

          <router-link :to="'/fleet/' + car.id" class="card-arrow-link" :aria-label="'Xem chi tiết ' + car.name">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </router-link>
        </div>

        <!-- Center: Car Image Stage -->
        <div class="card-visual-stage">
          <router-link :to="'/fleet/' + car.id" class="car-img-anchor">
            <img
              :src="getCarImage(car)"
              :alt="car.name"
              class="card-car-img"
              loading="lazy"
              @error="handleImgError"
            />
          </router-link>
          <div class="card-ground-shadow"></div>
        </div>

        <!-- Card Footer: Specs, Price, Actions -->
        <div class="card-footer-box">
          <div class="card-info-row">
            <div class="price-block">
              <span class="price-label">Đơn giá từ</span>
              <div class="price-value-wrap">
                <strong class="price-amount">{{ formatPrice(car.price_per_day) }}</strong>
                <span class="price-unit">/ ngày</span>
              </div>
            </div>

            <div class="spec-badges-wrap">
              <span class="mini-spec-badge" title="Số chỗ ngồi">
                <i class="fa-solid fa-users"></i> {{ car.seats || 5 }} chỗ
              </span>
              <span class="mini-spec-badge" title="Nhiên liệu">
                <i :class="car.fuel_type === 'gasoline' ? 'fa-solid fa-gas-pump' : 'fa-solid fa-bolt'"></i>
                {{ car.fuel_type === 'gasoline' ? 'Xăng' : 'Điện' }}
              </span>
            </div>
          </div>

          <div class="card-actions-row">
            <router-link :to="'/fleet/' + car.id" class="btn-card-detail">
              <span>Chi tiết</span>
            </router-link>
            <button
              type="button"
              class="btn-card-rent"
              @click="$emit('select-car-for-booking', car)"
            >
              <span>Đặt xe</span>
              <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- Section Bottom CTA -->
    <div class="fleet-bottom-action">
      <router-link to="/fleet" class="btn-explore-all">
        <span>Xem toàn bộ đội xe</span>
        <i class="fa-solid fa-arrow-right"></i>
      </router-link>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue';
import { resolveCarImage, handleCarImgError } from '../../../core/carImageHelper';

const props = defineProps({
  cars: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['select-car-for-booking']);

const selectedCategory = ref('all');

const availableCategories = computed(() => {
  if (props.categories && props.categories.length > 0) {
    return props.categories;
  }
  return [
    { id: 1, name: 'SUV' },
    { id: 2, name: 'Sedan' },
    { id: 4, name: 'Xe điện' },
    { id: 5, name: 'Xe sang' }
  ];
});

const filteredCars = computed(() => {
  if (selectedCategory.value === 'all') {
    return props.cars.slice(0, 6);
  }
  const catObj = availableCategories.value.find(c => c.name === selectedCategory.value);
  const catName = selectedCategory.value.toLowerCase();

  return props.cars.filter(car => {
    if (catObj && car.category_id && car.category_id === catObj.id) return true;
    const cName = (car.category_name || car.type || '').toLowerCase();
    return cName.includes(catName);
  }).slice(0, 6);
});

const getCarImage = (car) => {
  return resolveCarImage(car);
};

const handleImgError = (e) => {
  handleCarImgError(e);
};

const formatPrice = (val) => {
  return Number(val || 0).toLocaleString('vi-VN') + 'đ';
};
</script>

<style scoped>
.featured-fleet-section {
  position: relative;
  z-index: 10;
  padding: 60px 0 80px 0;
}

/* Heading Row */
.fleet-heading-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 32px;
  margin-bottom: 32px;
}

.heading-left {
  max-width: 580px;
}

.eyebrow-text {
  display: inline-block;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--color-primary-glow, #10b981);
  margin-bottom: 8px;
  text-transform: uppercase;
}

.section-title {
  font-family: var(--font-heading);
  font-size: clamp(1.8rem, 3.2vw, 2.6rem);
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.heading-right {
  max-width: 440px;
}

.section-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

/* Tabs Bar */
.category-tabs-bar {
  display: inline-flex;
  background: rgba(18, 26, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
  padding: 4px;
  gap: 6px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.cat-tab-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 8px 20px;
  border-radius: 9999px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 200ms ease;
  user-select: none;
  font-family: var(--font-heading);
}

.cat-tab-btn:hover:not(.is-active) {
  color: #f1f5f9;
  background: rgba(255, 255, 255, 0.05);
}

.cat-tab-btn.is-active {
  background: var(--color-primary, #059669);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

/* Cards Grid */
.fleet-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 28px;
  margin-bottom: 50px;
}

.fleet-card {
  background: rgba(18, 25, 42, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 22px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1),
              border-color 300ms ease,
              box-shadow 300ms ease;
  box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.4);
}

.fleet-card:hover {
  transform: translateY(-6px);
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 20px 40px -10px rgba(5, 150, 105, 0.2);
}

/* Card Header */
.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.car-card-name {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 4px;
}

.car-card-name a {
  color: #ffffff;
  transition: color 180ms ease;
}

.car-card-name a:hover {
  color: var(--color-primary-glow, #10b981);
}

.car-category-sub {
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 500;
}

.card-arrow-link {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  font-size: 0.85rem;
  transition: all 200ms ease;
  flex-shrink: 0;
}

.fleet-card:hover .card-arrow-link {
  background: var(--color-primary, #059669);
  color: #ffffff;
  border-color: var(--color-primary, #059669);
  transform: rotate(45deg);
}

/* Card Visual Stage */
.card-visual-stage {
  position: relative;
  width: 100%;
  height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 16px 0;
}

.car-img-anchor {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-car-img {
  max-width: 90%;
  max-height: 180px;
  object-fit: contain;
  transition: transform 320ms cubic-bezier(0.4, 0, 0.2, 1);
  filter: drop-shadow(0 14px 20px rgba(0, 0, 0, 0.5));
}

.fleet-card:hover .card-car-img {
  transform: scale(1.05);
}

.card-ground-shadow {
  width: 75%;
  height: 18px;
  margin-top: -10px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.2) 60%, transparent 80%);
  filter: blur(5px);
  pointer-events: none;
}

/* Card Footer */
.card-footer-box {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.card-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.price-label {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
  font-weight: 500;
}

.price-value-wrap {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.price-amount {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  color: #34d399;
}

.price-unit {
  font-size: 0.8rem;
  color: #94a3b8;
}

.spec-badges-wrap {
  display: flex;
  gap: 8px;
}

.mini-spec-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #cbd5e1;
}

.card-actions-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.btn-card-detail {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 9px 0;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  color: #e2e8f0;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 200ms ease;
}

.btn-card-detail:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.25);
}

.btn-card-rent {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 0;
  background: linear-gradient(135deg, var(--color-primary, #059669) 0%, #047857 100%);
  border: none;
  border-radius: 12px;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
  transition: all 200ms ease;
}

.btn-card-rent:hover {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* Skeleton Loading */
.skeleton-card {
  min-height: 380px;
  gap: 20px;
}

.skeleton-shimmer {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.03) 25%, rgba(255, 255, 255, 0.08) 50%, rgba(255, 255, 255, 0.03) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 10px;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.skeleton-header { width: 60%; height: 28px; }
.skeleton-image { width: 100%; height: 180px; }
.skeleton-footer { width: 100%; height: 48px; }

/* Empty Box */
.empty-fleet-box {
  text-align: center;
  padding: 60px 20px;
  background: rgba(18, 25, 42, 0.5);
  border: 1px dashed rgba(255, 255, 255, 0.15);
  border-radius: 20px;
  color: #94a3b8;
}

.empty-icon {
  font-size: 3rem;
  color: #64748b;
  margin-bottom: 16px;
}

.empty-fleet-box h3 {
  font-size: 1.2rem;
  color: #ffffff;
  margin-bottom: 8px;
}

.empty-fleet-box p {
  font-size: 0.9rem;
  margin-bottom: 20px;
}

.btn-reset-filter {
  padding: 10px 24px;
  background: var(--color-primary, #059669);
  color: #ffffff;
  border: none;
  border-radius: 9999px;
  font-weight: 600;
  cursor: pointer;
}

/* Bottom Action */
.fleet-bottom-action {
  text-align: center;
  margin-top: 10px;
}

.btn-explore-all {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 32px;
  background: rgba(18, 26, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 9999px;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.95rem;
  font-family: var(--font-heading);
  transition: all 220ms ease;
}

.btn-explore-all:hover {
  background: var(--color-primary, #059669);
  border-color: var(--color-primary, #059669);
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
}

/* Responsive */
@media (max-width: 768px) {
  .fleet-heading-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .fleet-cards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
