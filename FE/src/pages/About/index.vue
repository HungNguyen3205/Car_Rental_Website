<template>
  <div class="about-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <span class="badge mb-3">{{ aboutContent.badge }}</span>
        <h1 class="page-title">{{ aboutContent.title }}</h1>
        <p class="hero-desc">{{ aboutContent.hero_desc }}</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="about-grid">
        <div class="about-text">
          <h2>{{ aboutContent.mission_title }}</h2>
          <p>{{ aboutContent.mission_paragraph_1 }}</p>
          <p>{{ aboutContent.mission_paragraph_2 }}</p>
          
          <div class="commitments-grid">
            <div
              v-for="(item, cIdx) in (aboutContent.commitments || [])"
              :key="cIdx"
              class="commit-item card"
            >
              <div class="commit-icon"><i :class="item.icon"></i></div>
              <div class="commit-info">
                <h4>{{ item.title }}</h4>
                <p>{{ item.desc }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="about-image-wrapper">
          <div class="image-frame card">
            <img 
              :src="aboutContent.team_image || 'https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1200'" 
              alt="Đội ngũ DriveRentals" 
              class="about-img" 
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { useWebsiteContent } from '@/core/contentStore';

const aboutContent = useWebsiteContent('about_page');
</script>

<style scoped>
.about-page {
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

.page-title span {
  color: var(--color-primary);
}

.hero-desc {
  font-size: 1.15rem;
  color: var(--color-text-muted);
  max-width: 680px;
  margin: 0 auto;
  line-height: 1.6;
}

.content-section {
  margin-top: 60px;
}

.about-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 60px;
  align-items: center;
}

.about-text h2 {
  font-size: clamp(1.8rem, 3vw, 2.2rem);
  color: var(--color-text-main);
  margin-bottom: 20px;
  font-family: var(--font-heading);
  letter-spacing: -0.5px;
}

.about-text p {
  font-size: 1.05rem;
  color: var(--color-text-muted);
  line-height: 1.7;
  margin-bottom: 20px;
}

.commitments-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 32px;
}

.commit-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 18px 20px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: transform 0.2s, box-shadow 0.2s;
}

.commit-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary);
}

.commit-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.commit-info h4 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  margin-bottom: 4px;
}

.commit-info p {
  font-size: 0.9rem;
  color: var(--color-text-muted);
  margin-bottom: 0;
  line-height: 1.5;
}

.about-image-wrapper {
  position: relative;
}

.image-frame {
  padding: 12px;
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
}

.about-img {
  width: 100%;
  height: 440px;
  border-radius: var(--radius-md);
  object-fit: cover;
}

@media (max-width: 992px) {
  .about-grid {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  .about-img {
    height: 320px;
  }
}
</style>
