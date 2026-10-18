<template>
  <section class="home-bento-section app-container" aria-label="Đặc quyền dịch vụ">
    <div class="bento-heading-row">
      <span class="eyebrow-text">TIÊU CHUẨN ĐẲNG CẤP</span>
      <h2 class="section-title">Trải Nghiệm Khác Biệt Tại DriveRent</h2>
    </div>

    <div class="bento-grid">
      <!-- Big Left Card -->
      <div class="bento-card bento-card-large">
        <img
          src="/assets/images/bento_main.jpg"
          alt="Trải nghiệm xe điện hiện đại"
          class="bento-bg-img"
          loading="lazy"
        />
        <div class="bento-gradient-overlay"></div>

        <div class="bento-content-box">
          <span class="bento-pill">
            <i class="fa-solid fa-bolt"></i> 100% Động Cơ Xanh
          </span>
          <h3 class="bento-title-lg">Kiến Tạo Hành Trình Thông Minh & Đẳng Cấp</h3>
          <p class="bento-desc-lg">
            Khám phá cảm giác lái êm ái, tĩnh lặng tuyệt đối cùng công nghệ hỗ trợ lái tiên tiến. Mỗi chặng đường đều là một trải nghiệm thư thái trọn vẹn.
          </p>

          <div class="bento-features-row">
            <div class="mini-feat">
              <i class="fa-solid fa-circle-check"></i>
              <span>Pin sạc chuẩn hoá</span>
            </div>
            <div class="mini-feat">
              <i class="fa-solid fa-circle-check"></i>
              <span>Cứu hộ 24/7 toàn quốc</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: 2 Stacked Cards -->
      <div class="bento-right-stack">
        <!-- Top Card: Network -->
        <div class="bento-card bento-card-sm">
          <div class="bento-sm-header">
            <div class="bento-icon-box green">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <span class="bento-sub-tag">Mạng Lưới Phục Vụ</span>
          </div>

          <h3 class="bento-title-sm">Phủ Sóng 3 Miền Toàn Quốc</h3>
          <p class="bento-desc-sm">
            Mạng lưới bàn giao xe linh hoạt tại Hà Nội, Đà Nẵng, TP. Hồ Chí Minh và đón trả tận nơi tại các sân bay lớn: Nội Bài, Tân Sơn Nhất, Đà Nẵng.
          </p>
        </div>

        <!-- Bottom Card: Transparent Policy -->
        <div class="bento-card bento-card-sm">
          <div class="bento-sm-header">
            <div class="bento-icon-box blue">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span class="bento-sub-tag">Minh Bạch Tuyệt Đối</span>
          </div>

          <h3 class="bento-title-sm">Chính Sách Hoàn Cọc Trong 24H</h3>
          <p class="bento-desc-sm">
            Bảo đảm hoàn cọc an ninh trong 24 giờ sau khi đối soát biên bản trả xe. Không phụ phí ẩn, không phát sinh ngoài hợp đồng số.
          </p>

          <div class="bento-link-row">
            <router-link to="/fleet" class="bento-cta-link">
              <span>Khám phá ngay</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
</script>

<style scoped>
.home-bento-section {
  position: relative;
  z-index: 10;
  padding: 60px 0 100px 0;
}

.bento-heading-row {
  text-align: center;
  max-width: 640px;
  margin: 0 auto 48px auto;
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

/* Bento Grid */
.bento-grid {
  display: grid;
  grid-template-columns: 58% 42%;
  gap: 28px;
  align-items: stretch;
}

.bento-card {
  position: relative;
  background: rgba(18, 25, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 26px;
  overflow: hidden;
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1),
              border-color 300ms ease,
              box-shadow 300ms ease;
  box-shadow: 0 14px 36px -10px rgba(0, 0, 0, 0.4);
}

.bento-card:hover {
  transform: translateY(-4px);
  border-color: rgba(16, 185, 129, 0.3);
  box-shadow: 0 20px 48px -12px rgba(5, 150, 105, 0.22);
}

/* Big Card Left */
.bento-card-large {
  min-height: 480px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 40px;
}

.bento-bg-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 600ms cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1;
}

.bento-card-large:hover .bento-bg-img {
  transform: scale(1.04);
}

.bento-gradient-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(11, 18, 32, 0.1) 0%, rgba(11, 18, 32, 0.8) 60%, rgba(11, 18, 32, 0.98) 100%);
  z-index: 2;
}

.bento-content-box {
  position: relative;
  z-index: 3;
}

.bento-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background: rgba(5, 150, 105, 0.25);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 9999px;
  color: #34d399;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 16px;
  backdrop-filter: blur(8px);
}

.bento-title-lg {
  font-family: var(--font-heading);
  font-size: clamp(1.5rem, 2.4vw, 2.1rem);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.2;
  margin-bottom: 14px;
  letter-spacing: -0.02em;
}

.bento-desc-lg {
  font-size: 0.95rem;
  color: #cbd5e1;
  line-height: 1.6;
  margin-bottom: 24px;
  max-width: 520px;
}

.bento-features-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.mini-feat {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.88rem;
  color: #f1f5f9;
  font-weight: 600;
}

.mini-feat i {
  color: #34d399;
}

/* Right Stack */
.bento-right-stack {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.bento-card-sm {
  padding: 32px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  flex: 1;
}

.bento-sm-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
}

.bento-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.bento-icon-box.green {
  background: rgba(5, 150, 105, 0.2);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.bento-icon-box.blue {
  background: rgba(2, 132, 199, 0.2);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.bento-sub-tag {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #94a3b8;
}

.bento-title-sm {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 10px;
  letter-spacing: -0.01em;
}

.bento-desc-sm {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
}

.bento-link-row {
  margin-top: 18px;
}

.bento-cta-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-primary-glow, #10b981);
  transition: transform 200ms ease, color 200ms ease;
}

.bento-cta-link:hover {
  color: #34d399;
  transform: translateX(4px);
}

/* Responsive */
@media (max-width: 900px) {
  .bento-grid {
    grid-template-columns: 1fr;
  }

  .bento-card-large {
    min-height: 400px;
    padding: 28px;
  }
}
</style>
