<template>
  <div class="process-page">
    <!-- Hero Header -->
    <section class="process-hero">
      <div class="app-container text-center">
        <span class="badge mb-3">{{ processContent.badge }}</span>
        <h1 class="page-title">{{ processContent.title }}</h1>
        <p class="hero-desc">{{ processContent.hero_desc }}</p>
      </div>
    </section>

    <!-- Visual Process Timeline -->
    <section class="timeline-section app-container">
      <div class="timeline-container">
        <div
          v-for="(step, idx) in displaySteps"
          :key="step.step_number || idx"
          class="timeline-item"
          :class="{ reverse: idx % 2 === 1 }"
        >
          <div class="step-number">{{ step.step_number }}</div>
          <div class="step-content card">
            <div class="step-icon">
              <i :class="step.icon"></i>
            </div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to action -->
    <section class="process-cta app-container text-center">
      <div class="cta-card card">
        <h2>{{ processContent.cta_title }}</h2>
        <p>{{ processContent.cta_desc }}</p>
        <div class="cta-actions">
          <router-link :to="processContent.primary_cta_url || '/fleet'" class="btn-primary btn-lg">
            {{ processContent.primary_cta_text || 'Khám phá đội xe ngay' }} <i class="fa-solid fa-arrow-right"></i>
          </router-link>
          <router-link :to="processContent.secondary_cta_url || '/'" class="btn-outline btn-lg">
            {{ processContent.secondary_cta_text || 'Tìm xe theo ngày' }} <i class="fa-solid fa-magnifying-glass"></i>
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useWebsiteContent } from '@/core/contentStore';

const processContent = useWebsiteContent('process_page');
const displaySteps = computed(() => {
  return processContent.steps || [];
});
</script>

<style scoped>
.process-page {
  padding-bottom: 80px;
}

.process-hero {
  padding: 140px 20px 60px;
  background: linear-gradient(180deg, var(--bg-surface-alt) 0%, var(--bg-main) 100%);
  border-bottom: 1px solid var(--color-border);
}

.badge {
  display: inline-block;
  padding: 6px 16px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  border-radius: 100px;
  font-size: 0.85rem;
  font-weight: 700;
  font-family: var(--font-heading);
}

.page-title {
  font-size: clamp(2rem, 4vw, 3.2rem);
  font-weight: 800;
  margin-bottom: 16px;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  letter-spacing: -0.5px;
}

.page-title span {
  color: var(--color-primary);
}

.hero-desc {
  font-size: 1.15rem;
  color: var(--color-text-muted);
  max-width: 640px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Timeline Design */
.timeline-section {
  padding: 70px 20px;
  position: relative;
}

.timeline-container {
  position: relative;
  max-width: 960px;
  margin: 0 auto;
}

.timeline-container::before {
  content: '';
  position: absolute;
  top: 20px;
  bottom: 20px;
  left: 50%;
  width: 2px;
  background: var(--color-border);
  transform: translateX(-50%);
}

.timeline-item {
  display: flex;
  justify-content: flex-start;
  width: 100%;
  margin-bottom: 60px;
  position: relative;
}

.timeline-item.reverse {
  justify-content: flex-end;
}

.step-number {
  position: absolute;
  left: 50%;
  top: 10px;
  transform: translateX(-50%);
  width: 48px;
  height: 48px;
  background: white;
  border: 3px solid var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: var(--color-primary);
  font-family: var(--font-heading);
  z-index: 5;
  box-shadow: 0 0 16px rgba(37, 99, 235, 0.2);
}

.step-content {
  width: 44%;
  background: white;
  padding: 32px 28px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.step-content:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--color-primary);
}

.step-icon {
  width: 52px;
  height: 52px;
  background: rgba(37, 99, 235, 0.08);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: var(--color-primary);
  margin-bottom: 20px;
}

.step-content h3 {
  font-size: 1.3rem;
  font-weight: 800;
  margin-bottom: 12px;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.step-content p {
  color: var(--color-text-muted);
  line-height: 1.6;
  font-size: 0.95rem;
}

.process-cta {
  margin-top: 20px;
}

.cta-card {
  padding: 60px 30px;
  border-radius: var(--radius-xl);
  background: #0B1220;
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: var(--shadow-lg);
}

.cta-card h2 {
  font-size: clamp(1.8rem, 3.5vw, 2.4rem);
  font-weight: 800;
  margin-bottom: 14px;
  font-family: var(--font-heading);
}

.cta-card p {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 32px;
  max-width: 540px;
  margin-left: auto;
  margin-right: auto;
}

.cta-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-lg {
  padding: 14px 28px;
  font-size: 1rem;
}

.cta-card .btn-outline {
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

.cta-card .btn-outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: white;
}

@media (max-width: 768px) {
  .timeline-container::before { left: 24px; }
  .step-number { left: 24px; transform: none; width: 42px; height: 42px; font-size: 0.9rem; }
  .timeline-item, .timeline-item.reverse { justify-content: flex-start; }
  .step-content { width: calc(100% - 60px); margin-left: 60px; padding: 24px 20px; }
}
</style>
