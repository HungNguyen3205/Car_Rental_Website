<template>
  <section class="hero-section" aria-label="Giới thiệu đội xe">
    <div class="hero-ambient-glow" aria-hidden="true"></div>

    <div class="hero-container app-container">
      <div class="hero-grid">
        <!-- Left Column: Title, Segmented Tabs, Specs -->
        <div class="hero-info" :class="{ 'hero-entered': isLoaded }">
          <div class="hero-eyebrow">
            <span class="pulse-indicator"></span>
            <span>Hệ Thống Thuê Xe Trực Tuyến Hàng Đầu</span>
          </div>

          <h1 class="hero-title">
            XE SANG CHO<br />
            <span class="hero-highlight">MỌI HÀNH TRÌNH.</span>
          </h1>

          <!-- Segmented Category Control -->
          <div class="class-selector-group">
            <span class="selector-label">CHỌN PHÂN KHÚC</span>
            <div class="segmented-pill-container" role="tablist" aria-label="Phân khúc xe">
              <button
                v-for="(cat, index) in categories"
                :key="cat.id"
                type="button"
                role="tab"
                :aria-selected="activeCategoryIndex === index"
                class="segment-btn"
                :class="{ 'is-active': activeCategoryIndex === index }"
                @click="selectCategory(index)"
              >
                {{ cat.name }}
              </button>
            </div>
          </div>

          <!-- Quick Vehicle Specs -->
          <Transition name="specs-fade" mode="out-in">
            <div :key="activeCar.id" class="hero-specs-row">
              <div class="spec-col">
                <span class="spec-label">{{ activeCar.spec1Label }}</span>
                <strong class="spec-value">{{ activeCar.spec1Value }}</strong>
              </div>
              <div class="spec-divider"></div>
              <div class="spec-col">
                <span class="spec-label">{{ activeCar.spec2Label }}</span>
                <strong class="spec-value">{{ activeCar.spec2Value }}</strong>
              </div>
              <div class="spec-divider"></div>
              <div class="spec-col">
                <span class="spec-label">Đơn giá từ</span>
                <strong class="spec-value price-text">{{ formatPrice(activeCar.price) }}</strong>
              </div>
            </div>
          </Transition>

          <!-- Quick Action Link -->
          <div class="hero-quick-actions">
            <button type="button" class="btn-hero-explore" @click="$emit('scroll-to-search')">
              <span>Đặt xe ngay</span>
              <i class="fa-solid fa-arrow-down"></i>
            </button>
            <router-link to="/fleet" class="btn-hero-fleet">
              <span>Xem toàn bộ xe</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>

        <!-- Right Column: Vehicle Visual Stage -->
        <div class="hero-visual" :class="{ 'hero-entered': isLoaded }">
          <!-- Floating Status Badge (Similar to video frame 3) -->
          <div class="floating-badge" aria-hidden="true">
            <div class="badge-dot"></div>
            <div class="badge-text">
              <strong>Sẵn sàng bàn giao</strong>
              <span>Giao xe tận nơi trong 15 phút</span>
            </div>
            <div class="badge-pointer"></div>
          </div>

          <!-- Car Stage with Vue Transition -->
          <div class="car-stage-wrapper">
            <Transition name="car-slide" mode="out-in">
              <div :key="activeCar.id" class="car-stage-item">
                <img
                  :src="activeCar.image"
                  :alt="activeCar.title"
                  class="hero-car-img"
                  loading="eager"
                  decoding="async"
                />
                <!-- Subtle ground shadow -->
                <div class="car-ground-shadow"></div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits(['scroll-to-search', 'category-change']);

const isLoaded = ref(false);
const activeCategoryIndex = ref(0);

const categories = [
  {
    id: 'suv',
    name: 'SUV',
    title: 'VinFast VF9 & SUV Hạng Sang',
    image: '/assets/images/cars/hero_suv.jpg',
    spec1Label: 'Động cơ',
    spec1Value: 'Điện 402 hp',
    spec2Label: 'Số chỗ ngồi',
    spec2Value: '7 chỗ Full-Size',
    price: 1300000
  },
  {
    id: 'sport',
    name: 'Sport',
    title: 'Coupe Thể Thao Cao Cấp',
    image: '/assets/images/cars/hero_sport.jpg',
    spec1Label: 'Tăng tốc 0-100',
    spec1Value: '3.6 giây',
    spec2Label: 'Công suất',
    spec2Value: '585 hp',
    price: 2500000
  },
  {
    id: 'sedan',
    name: 'Sedan',
    title: 'Executive Luxury Sedan',
    image: '/assets/images/cars/hero_sedan.jpg',
    spec1Label: 'Tăng tốc 0-100',
    spec1Value: '4.8 giây',
    spec2Label: 'Cấu hình',
    spec2Value: '5 chỗ hạng sang',
    price: 1500000
  },
  {
    id: 'electric',
    name: 'Electric',
    title: 'VinFast VF8 Thuần Điện',
    image: '/assets/images/cars/hero_electric.jpg',
    spec1Label: 'Quãng đường',
    spec1Value: '420 km / sạc',
    spec2Label: 'Pin sạc nhanh',
    spec2Value: '87.7 kWh',
    price: 1200000
  }
];

const activeCar = computed(() => categories[activeCategoryIndex.value]);

let switchTimeout = null;
const selectCategory = (index) => {
  if (activeCategoryIndex.value === index) return;
  if (switchTimeout) clearTimeout(switchTimeout);
  switchTimeout = setTimeout(() => {
    activeCategoryIndex.value = index;
    emit('category-change', categories[index].id);
  }, 30);
};

const formatPrice = (val) => {
  return Number(val).toLocaleString('vi-VN') + 'đ/ngày';
};

// Preload all 4 hero images for flicker-free transitions
onMounted(() => {
  categories.forEach((cat) => {
    const img = new Image();
    img.src = cat.image;
  });
  // Trigger intro animation
  requestAnimationFrame(() => {
    setTimeout(() => {
      isLoaded.value = true;
    }, 60);
  });
});
</script>

<style scoped>
.hero-section {
  position: relative;
  background-color: var(--bg-dark, #0b1220);
  background-image: 
    radial-gradient(ellipse 65% 55% at 65% 35%, rgba(5, 150, 105, 0.12) 0%, rgba(11, 18, 32, 0) 70%),
    radial-gradient(circle at 20% 80%, rgba(2, 132, 199, 0.06) 0%, rgba(11, 18, 32, 0) 50%);
  padding: 130px 0 50px 0;
  overflow: hidden;
  color: #ffffff;
}

.hero-ambient-glow {
  position: absolute;
  top: 15%;
  right: 10%;
  width: 520px;
  height: 380px;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.14) 0%, rgba(5, 150, 105, 0.03) 60%, transparent 80%);
  filter: blur(80px);
  pointer-events: none;
  z-index: 1;
}

.hero-container {
  position: relative;
  z-index: 2;
  max-width: 1320px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero-grid {
  display: grid;
  grid-template-columns: 44% 56%;
  align-items: center;
  gap: 32px;
  min-height: 520px;
}

/* Left Column Styling */
.hero-info {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 700ms cubic-bezier(0.2, 0.8, 0.2, 1),
              transform 700ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.hero-info.hero-entered {
  opacity: 1;
  transform: translateY(0);
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 6px 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #d1fae5;
  letter-spacing: 0.02em;
  margin-bottom: 20px;
  backdrop-filter: blur(8px);
}

.pulse-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--color-primary-glow, #10b981);
  box-shadow: 0 0 10px var(--color-primary-glow, #10b981);
  animation: pulse 2s infinite ease-in-out;
}

@keyframes pulse {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { transform: scale(1.3); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.8; }
}

.hero-title {
  font-family: var(--font-heading);
  font-size: clamp(2.5rem, 4.4vw, 4rem);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -0.03em;
  color: #ffffff;
  margin-bottom: 28px;
  text-transform: uppercase;
}

.hero-highlight {
  color: #f8fafc;
  display: inline-block;
  background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Segmented Control */
.class-selector-group {
  margin-bottom: 28px;
}

.selector-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #94a3b8;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.segmented-pill-container {
  display: inline-flex;
  align-items: center;
  background: rgba(18, 26, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
  padding: 4px;
  gap: 4px;
  backdrop-filter: blur(12px);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.4);
}

.segment-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 8px 18px;
  border-radius: 9999px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 220ms cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  font-family: var(--font-heading);
}

.segment-btn:hover:not(.is-active) {
  color: #f1f5f9;
  background: rgba(255, 255, 255, 0.05);
}

.segment-btn.is-active {
  background: var(--color-primary, #059669);
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
}

/* Specs Row */
.hero-specs-row {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 32px;
  padding: 16px 20px;
  background: rgba(17, 26, 46, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  max-width: 480px;
}

.spec-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.spec-label {
  font-size: 0.78rem;
  color: #94a3b8;
  font-weight: 500;
}

.spec-value {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.01em;
}

.spec-value.price-text {
  color: #34d399;
}

.spec-divider {
  width: 1px;
  height: 32px;
  background: rgba(255, 255, 255, 0.1);
}

/* Actions */
.hero-quick-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn-hero-explore {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 26px;
  background: linear-gradient(135deg, var(--color-primary, #059669) 0%, #047857 100%);
  color: #ffffff;
  border: none;
  border-radius: 9999px;
  font-size: 0.95rem;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(5, 150, 105, 0.35);
  transition: all 240ms cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-hero-explore:hover {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
}

.btn-hero-fleet {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: #e2e8f0;
  border-radius: 9999px;
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 220ms ease;
}

.btn-hero-fleet:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.25);
  transform: translateY(-2px);
}

/* Right Column (Visual) */
.hero-visual {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transform: translateX(60px);
  transition: opacity 850ms cubic-bezier(0.2, 0.8, 0.2, 1),
              transform 850ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.hero-visual.hero-entered {
  opacity: 1;
  transform: translateX(0);
}

/* Floating Status Badge */
.floating-badge {
  position: absolute;
  top: 12px;
  right: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 18px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
  z-index: 10;
  animation: floatBadge 4s ease-in-out infinite;
}

@keyframes floatBadge {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.badge-dot {
  width: 9px;
  height: 9px;
  background: #34d399;
  border-radius: 50%;
  box-shadow: 0 0 10px #34d399;
}

.badge-text {
  display: flex;
  flex-direction: column;
}

.badge-text strong {
  font-size: 0.8rem;
  color: #ffffff;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.badge-text span {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Car Stage & Transition */
.car-stage-wrapper {
  position: relative;
  width: 100%;
  max-width: 720px;
  min-height: 420px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-stage-item {
  position: relative;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero-car-img {
  width: 100%;
  max-height: 440px;
  object-fit: contain;
  filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.7));
  transform: perspective(1000px) rotateY(-2deg);
  transition: transform 400ms ease;
  user-select: none;
}

.car-stage-item:hover .hero-car-img {
  transform: perspective(1000px) rotateY(0deg) scale(1.02);
}

.car-ground-shadow {
  width: 82%;
  height: 28px;
  margin-top: -16px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 75%);
  filter: blur(6px);
  pointer-events: none;
}

/* Animations */
.specs-fade-enter-active,
.specs-fade-leave-active {
  transition: opacity 220ms ease, transform 220ms ease;
}

.specs-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.specs-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.car-slide-enter-active,
.car-slide-leave-active {
  transition: opacity 450ms cubic-bezier(0.3, 0, 0.2, 1),
              transform 450ms cubic-bezier(0.3, 0, 0.2, 1);
}

.car-slide-enter-from {
  opacity: 0;
  transform: translateX(70px) scale(0.98);
}

.car-slide-leave-to {
  opacity: 0;
  transform: translateX(-40px) scale(0.98);
}

/* Responsive */
@media (max-width: 1080px) {
  .hero-grid {
    grid-template-columns: 1fr;
    gap: 40px;
    text-align: center;
  }

  .hero-eyebrow,
  .class-selector-group,
  .hero-specs-row,
  .hero-quick-actions {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-specs-row {
    justify-content: center;
  }

  .floating-badge {
    right: 50%;
    transform: translateX(50%);
    animation: floatBadgeMobile 4s ease-in-out infinite;
  }

  @keyframes floatBadgeMobile {
    0%, 100% { transform: translate(50%, 0); }
    50% { transform: translate(50%, -6px); }
  }

  .car-stage-wrapper {
    min-height: 340px;
  }
}

@media (max-width: 640px) {
  .hero-section {
    padding: 100px 0 30px 0;
  }

  .segmented-pill-container {
    width: 100%;
    justify-content: space-between;
  }

  .segment-btn {
    padding: 6px 12px;
    font-size: 0.8rem;
  }

  .hero-specs-row {
    gap: 12px;
    padding: 12px 14px;
  }

  .spec-value {
    font-size: 0.95rem;
  }

  .spec-divider {
    height: 24px;
  }

  .car-stage-wrapper {
    min-height: 240px;
  }

  .floating-badge {
    top: -24px;
    padding: 6px 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-info,
  .hero-visual,
  .car-slide-enter-active,
  .car-slide-leave-active,
  .specs-fade-enter-active,
  .specs-fade-leave-active {
    transition: none !important;
    animation: none !important;
    transform: none !important;
  }
}
</style>
