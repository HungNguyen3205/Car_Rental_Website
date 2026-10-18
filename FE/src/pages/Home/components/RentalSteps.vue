<template>
  <section id="process-steps-section" class="rental-steps-section app-container" aria-label="Quy trình thuê xe">
    <!-- Header Row -->
    <div class="steps-heading-row">
      <div class="heading-left">
        <span class="eyebrow-text">QUY TRÌNH TINH GỌN</span>
        <h2 class="section-title">3 Bước Đơn Giản Lăn Bánh</h2>
      </div>
      <div class="heading-right">
        <p class="section-desc">
          Không thủ tục rườm rà, không chờ đợi giấy tờ. Toàn bộ quy trình được số hóa minh bạch từ lúc chọn xe đến khi bàn giao.
        </p>
      </div>
    </div>

    <!-- Progress Track (Matching Video Frame 14) -->
    <div class="progress-track-wrapper" aria-hidden="true">
      <div class="progress-track-bg"></div>
      <div class="progress-track-fill" :style="{ width: progressWidth + '%' }"></div>
      <div class="progress-indicator-dot" :style="{ left: progressWidth + '%' }">
        <span class="dot-core"></span>
        <span class="dot-pulse"></span>
      </div>
    </div>

    <!-- 3 Step Cards Grid -->
    <div class="steps-cards-grid" @mouseenter="pauseAutoPlay" @mouseleave="resumeAutoPlay">
      <div
        v-for="(step, index) in steps"
        :key="step.number"
        class="step-card"
        :class="{ 'is-active': currentStepIndex === index }"
        @click="setStep(index)"
        role="button"
        tabindex="0"
        :aria-label="'Bước ' + step.number + ': ' + step.title"
        @keydown.enter="setStep(index)"
      >
        <div class="step-card-header">
          <span class="step-number-pill">{{ step.number }}</span>
          <div class="step-icon-circle">
            <i :class="step.icon"></i>
          </div>
        </div>

        <div class="step-card-content">
          <h3 class="step-card-title">{{ step.title }}</h3>
          <p class="step-card-desc">{{ step.desc }}</p>
        </div>

        <div class="step-active-indicator"></div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const currentStepIndex = ref(0);
let autoPlayTimer = null;

const steps = [
  {
    number: '01',
    icon: 'fa-solid fa-car',
    title: 'Chọn xe & Lịch trình',
    desc: 'Lựa chọn dòng xe phù hợp nhu cầu, chọn ngày giờ nhận/trả xe và điểm giao linh hoạt tại Hà Nội, TP.HCM hoặc Đà Nẵng.'
  },
  {
    number: '02',
    icon: 'fa-solid fa-file-shield',
    title: 'Xác nhận & Đặt cọc',
    desc: 'Kiểm tra biểu phí minh bạch, chuyển khoản an toàn qua mã VietQR và nhận thông báo xác nhận đơn đặt ngay trên ứng dụng.'
  },
  {
    number: '03',
    icon: 'fa-solid fa-key',
    title: 'Nhận xe & Khởi hành',
    desc: 'Nhận bàn giao xe tận nơi với biên bản điện tử chi tiết, kiểm tra số km, lượng pin/xăng và an tâm tận hưởng hành trình.'
  }
];

const progressWidth = computed(() => {
  // 0% -> 50% -> 100%
  if (currentStepIndex.value === 0) return 16;
  if (currentStepIndex.value === 1) return 50;
  return 84;
});

const setStep = (idx) => {
  currentStepIndex.value = idx;
};

const nextStep = () => {
  currentStepIndex.value = (currentStepIndex.value + 1) % steps.length;
};

const pauseAutoPlay = () => {
  if (autoPlayTimer) clearInterval(autoPlayTimer);
};

const resumeAutoPlay = () => {
  pauseAutoPlay();
  autoPlayTimer = setInterval(nextStep, 5000);
};

onMounted(() => {
  resumeAutoPlay();
});

onUnmounted(() => {
  pauseAutoPlay();
});
</script>

<style scoped>
.rental-steps-section {
  position: relative;
  z-index: 10;
  padding: 60px 0 80px 0;
}

/* Heading */
.steps-heading-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 32px;
  margin-bottom: 40px;
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

/* Progress Track Bar */
.progress-track-wrapper {
  position: relative;
  width: 100%;
  height: 20px;
  display: flex;
  align-items: center;
  margin-bottom: 30px;
}

.progress-track-bg {
  position: absolute;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
}

.progress-track-fill {
  position: absolute;
  left: 0;
  height: 2px;
  background: linear-gradient(90deg, #059669 0%, #10b981 100%);
  border-radius: 9999px;
  transition: width 400ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
}

.progress-indicator-dot {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: left 400ms cubic-bezier(0.4, 0, 0.2, 1);
}

.dot-core {
  width: 10px;
  height: 10px;
  background: #34d399;
  border-radius: 50%;
  box-shadow: 0 0 12px #34d399;
  z-index: 2;
}

.dot-pulse {
  position: absolute;
  width: 18px;
  height: 18px;
  background: rgba(52, 211, 153, 0.3);
  border-radius: 50%;
  animation: pulseDot 2s infinite ease-out;
}

@keyframes pulseDot {
  0% { transform: scale(0.8); opacity: 1; }
  100% { transform: scale(1.8); opacity: 0; }
}

/* Steps Cards Grid */
.steps-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.step-card {
  position: relative;
  background: rgba(18, 25, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  padding: 32px 28px;
  cursor: pointer;
  outline: none;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 220px;
}

.step-card:hover {
  background: rgba(18, 25, 42, 0.85);
  border-color: rgba(255, 255, 255, 0.18);
  transform: translateY(-4px);
}

.step-card.is-active {
  background: rgba(18, 25, 42, 0.95);
  border-color: rgba(16, 185, 129, 0.4);
  box-shadow: 0 16px 36px -8px rgba(5, 150, 105, 0.25);
  transform: translateY(-4px);
}

.step-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.step-number-pill {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  color: #64748b;
  transition: color 250ms ease;
}

.step-card.is-active .step-number-pill {
  color: #34d399;
}

.step-icon-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  font-size: 1rem;
  transition: all 250ms ease;
}

.step-card.is-active .step-icon-circle {
  background: var(--color-primary, #059669);
  color: #ffffff;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.step-card-title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 10px;
  letter-spacing: -0.01em;
}

.step-card-desc {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
}

.step-active-indicator {
  position: absolute;
  bottom: 0;
  left: 28px;
  right: 28px;
  height: 2px;
  background: transparent;
  border-radius: 2px;
  transition: background 300ms ease;
}

.step-card.is-active .step-active-indicator {
  background: var(--color-primary-glow, #10b981);
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
}

/* Responsive */
@media (max-width: 900px) {
  .steps-heading-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .steps-cards-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .progress-track-wrapper {
    display: none;
  }
}
</style>
