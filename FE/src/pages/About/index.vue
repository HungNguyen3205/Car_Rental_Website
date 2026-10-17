<template>
  <div class="about-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <span class="badge mb-3">Về chúng tôi</span>
        <h1 class="page-title">Hành Trình Chuyển Đổi <span>Di Chuyển Xanh</span></h1>
        <p class="hero-desc">Tiên phong mang đến dịch vụ thuê xe điện tự lái thông minh, an toàn và thân thiện với môi trường tại Việt Nam.</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="about-grid">
        <div class="about-text">
          <h2>Sứ mệnh của DriveRentals</h2>
          <p>
            Được thành lập với tầm nhìn phát triển giao thông bền vững, DriveRentals kết nối khách hàng với dòng xe điện cao cấp, mang lại sự tự do và tiện nghi tối đa cho mọi chuyến đi.
          </p>
          <p>
            Chúng tôi tin rằng mỗi chuyến đi không chỉ là hành trình di chuyển giữa hai điểm đến, mà là trải nghiệm công nghệ tinh tế, êm ái và không phát thải. Đội xe được bảo dưỡng định kỳ với tiêu chuẩn khắt khe, cam kết an tâm trọn vẹn.
          </p>
          
          <div class="commitments-grid">
            <div class="commit-item card">
              <div class="commit-icon"><i class="fa-solid fa-leaf"></i></div>
              <div class="commit-info">
                <h4>100% Động Cơ Điện</h4>
                <p>Giảm thiểu phát thải CO2, vận hành êm ái và mượt mà.</p>
              </div>
            </div>
            <div class="commit-item card">
              <div class="commit-icon"><i class="fa-solid fa-shield-halved"></i></div>
              <div class="commit-info">
                <h4>Bảo Hiểm Toàn Diện</h4>
                <p>Mỗi chuyến đi đều được bảo vệ với gói bảo hiểm chuẩn quốc tế.</p>
              </div>
            </div>
            <div class="commit-item card">
              <div class="commit-icon"><i class="fa-solid fa-headset"></i></div>
              <div class="commit-info">
                <h4>Hỗ Trợ Thông Minh 24/7</h4>
                <p>Trợ lý ảo AI và đội ngũ cứu hộ kỹ thuật sẵn sàng hỗ trợ mọi lúc.</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="about-image-wrapper">
          <div class="image-frame card">
            <img 
              src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1200" 
              alt="Đội ngũ DriveRentals" 
              class="about-img" 
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

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
