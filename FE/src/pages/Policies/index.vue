<template>
  <div class="policies-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <span class="badge mb-3">{{ policiesContent.badge || 'Điều khoản & Quy định' }}</span>
        <h1 class="page-title">{{ policiesContent.title || 'Chính Sách & Quy Định Thuê Xe' }}</h1>
        <p class="hero-desc">{{ policiesContent.intro || 'Tổng quan các điều khoản và quyền lợi minh bạch khi trải nghiệm dịch vụ thuê xe tại DriveRent.' }}</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="policies-grid">
        <div
          v-for="(section, idx) in (policiesContent.sections || [])"
          :key="idx"
          class="policy-card card"
        >
          <div class="policy-card-header">
            <span class="policy-index-pill">{{ String(idx + 1).padStart(2, '0') }}</span>
            <h3 class="policy-title">{{ section.title }}</h3>
          </div>
          <div class="policy-card-body">
            <p>{{ section.content }}</p>
          </div>
        </div>
      </div>

      <div class="policy-support-note card text-center mt-5">
        <div class="support-icon"><i class="fa-solid fa-headset"></i></div>
        <h3>Cần Hỗ Trợ Giải Đáp Chi Tiết Hơn?</h3>
        <p>Đội ngũ chuyên viên pháp lý và chăm sóc khách hàng của DriveRent luôn sẵn sàng tư vấn 24/7.</p>
        <div class="support-actions mt-3">
          <router-link to="/contact" class="btn-primary">
            <span>Liên hệ tư vấn viên</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { useWebsiteContent } from '@/core/contentStore';

const policiesContent = useWebsiteContent('policies_page');
</script>

<style scoped>
.policies-page {
  padding-bottom: 80px;
}

.hero-header {
  background: linear-gradient(180deg, var(--bg-surface-alt) 0%, var(--bg-main) 100%);
  padding: 140px 20px 70px;
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
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 800;
  margin-bottom: 16px;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  letter-spacing: -0.5px;
}

.hero-desc {
  font-size: 1.15rem;
  color: var(--color-text-muted);
  max-width: 680px;
  margin: 0 auto;
  line-height: 1.6;
}

.content-section {
  padding-top: 60px;
}

.policies-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.policy-card {
  padding: 32px;
  border-radius: var(--radius-lg);
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  transition: transform 0.2s, box-shadow 0.2s;
}

.policy-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.policy-card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.policy-index-pill {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}

.policy-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin: 0;
}

.policy-card-body p {
  font-size: 0.95rem;
  line-height: 1.7;
  color: var(--color-text-muted);
  margin: 0;
}

.policy-support-note {
  max-width: 680px;
  margin: 60px auto 0 auto;
  padding: 40px;
  border-radius: var(--radius-lg);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
}

.support-icon {
  font-size: 2.4rem;
  color: var(--color-primary);
  margin-bottom: 16px;
}

.policy-support-note h3 {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--color-text-main);
}

.policy-support-note p {
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

@media (max-width: 768px) {
  .policies-grid {
    grid-template-columns: 1fr;
  }
}
</style>
