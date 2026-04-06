<template>
  <div class="about-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <h1>Về Chúng Tôi</h1>
        <p>Hành trình xây dựng nền tảng thuê xe tự lái số 1 Việt Nam</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="about-grid">
        <div class="about-text">
          <h2>Sứ mệnh của DriveRentals</h2>
          <p>
            Được thành lập vào năm 2026, DriveRentals ra đời với mong muốn mang lại sự tự do và tiện lợi tối đa cho mọi chuyến đi của bạn. Chúng tôi kết nối hàng ngàn chủ xe với người có nhu cầu thuê xe tự lái trên khắp cả nước, tạo ra một mạng lưới chia sẻ phương tiện an toàn, minh bạch và tiết kiệm.
          </p>
          <p>
            Chúng tôi tin rằng mỗi hành trình đều mang một ý nghĩa đặc biệt. Dù là chuyến công tác vội vã hay chuyến du lịch thong thả cùng gia đình, một chiếc xe chất lượng sẽ làm trải nghiệm của bạn trọn vẹn hơn.
          </p>
          
          <div class="stats-row">
            <div class="stat-box">
              <h3>1000+</h3>
              <span>Đối tác tin cậy</span>
            </div>
            <div class="stat-box">
              <h3>5000+</h3>
              <span>Chuyến đi an toàn</span>
            </div>
            <div class="stat-box">
              <h3>63</h3>
              <span>Tỉnh thành</span>
            </div>
          </div>
        </div>
        
        <div class="about-image">
          <img src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Team DriveRentals" class="rounded-img shadow" />
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
  background: linear-gradient(to right, #f1f5f9, #e2e8f0);
  color: #0f172a;
  padding: 140px 20px 80px;
}
.hero-header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 12px;
  color: #0f172a;
}
.hero-header p {
  font-size: 1.125rem;
  color: #475569;
}
.content-section {
  margin-top: 60px;
}
.about-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}
.about-text h2 {
  font-size: 2rem;
  color: #0f172a;
  margin-bottom: 24px;
}
.about-text p {
  font-size: 1.05rem;
  color: #475569;
  line-height: 1.7;
  margin-bottom: 20px;
}
.stats-row {
  display: flex;
  gap: 32px;
  margin-top: 40px;
}
.stat-box h3 {
  font-size: 2rem;
  color: var(--color-primary);
  font-weight: 800;
  margin-bottom: 4px;
}
.stat-box span {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 600;
}
.rounded-img {
  width: 100%;
  border-radius: 16px;
  object-fit: cover;
}
.shadow {
  box-shadow: var(--shadow-lg);
}

@media (max-width: 768px) {
  .about-grid { grid-template-columns: 1fr; }
}
</style>
