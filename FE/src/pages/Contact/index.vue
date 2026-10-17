<template>
  <div class="contact-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <span class="badge mb-3">Trung tâm hỗ trợ</span>
        <h1 class="page-title">Liên Hệ <span>DriveRentals</span></h1>
        <p class="hero-desc">Chúng tôi luôn sẵn sàng hỗ trợ bạn qua hệ thống Trợ lý ảo AI 24/7 và đường dây nóng chăm sóc khách hàng.</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="contact-grid">
        <!-- Contact Info -->
        <div class="contact-info">
          <h2>Kênh Hỗ Trợ Trực Tiếp</h2>
          <p class="subtitle">Mọi thắc mắc về lịch trình thuê xe, kiểm tra tình trạng đơn và hỗ trợ kỹ thuật được tiếp nhận qua các kênh sau:</p>
          
          <div class="info-list">
            <a href="tel:1900123456" class="info-item card">
              <div class="icon-box">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div>
                <h4>Hotline Chăm Sóc Khách Hàng</h4>
                <p class="highlight-contact">1900 1234 56</p>
                <small class="time-note">Hoạt động từ 07:00 – 22:00 hàng ngày</small>
              </div>
            </a>
            
            <a href="mailto:support@driverentals.com" class="info-item card">
              <div class="icon-box">
                <i class="fa-solid fa-envelope"></i>
              </div>
              <div>
                <h4>Hộp Thư Điện Tử</h4>
                <p class="highlight-contact">support@driverentals.com</p>
                <small class="time-note">Phản hồi giải quyết khiếu nại trong 24h</small>
              </div>
            </a>
            
            <div class="info-item card">
              <div class="icon-box">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div>
                <h4>Văn Phòng Điều Hành</h4>
                <p>Tòa nhà Bitexco, Số 2 Hải Triều, Q.1, TP. HCM</p>
                <small class="time-note">Tiếp nhận thủ tục giao nhận xe tại chỗ</small>
              </div>
            </div>
          </div>
        </div>

        <!-- AI Assistant Direct Card (Replacing fake email form) -->
        <div class="ai-support-card card">
          <div class="ai-header">
            <div class="ai-badge">
              <span class="pulse-dot"></span> Trực tuyến 24/7
            </div>
            <h3>Hỗ Trợ Tức Thì Qua Trợ Lý AI</h3>
            <p>Không cần chờ đợi phản hồi email. Bạn có thể tra cứu thông tin xe, hỏi đáp quy trình thuê và kiểm tra đơn hàng trực tiếp ngay bây giờ.</p>
          </div>

          <div class="ai-features">
            <div class="ai-feature-row">
              <i class="fa-solid fa-circle-check"></i>
              <span>Tra cứu thông số & giá thuê dòng xe VinFast mới nhất</span>
            </div>
            <div class="ai-feature-row">
              <i class="fa-solid fa-circle-check"></i>
              <span>Kiểm tra trạng thái đơn đặt xe & hướng dẫn thanh toán QR</span>
            </div>
            <div class="ai-feature-row">
              <i class="fa-solid fa-circle-check"></i>
              <span>Giải đáp chính sách giao nhận xe tận nhà</span>
            </div>
          </div>

          <div class="ai-action-box">
            <button class="btn-primary btn-chat-now" @click="launchChatbot">
              <i class="fa-solid fa-robot"></i> Mở Trợ Lý Ảo DriveBot Ngay
            </button>
            <p class="transparency-note">
              <i class="fa-solid fa-shield-halved"></i> Hệ thống tiếp nhận xử lý tự động, đảm bảo bảo mật thông tin cá nhân.
            </p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const launchChatbot = () => {
  // Dispatches event to common ChatWidget
  window.dispatchEvent(new CustomEvent('open-ai-chat'));
};
</script>

<style scoped>
.contact-page {
  padding-bottom: 80px;
}

.hero-header {
  background: linear-gradient(180deg, var(--bg-surface-alt) 0%, var(--bg-main) 100%);
  padding: 140px 20px 60px;
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
  max-width: 640px;
  margin: 0 auto;
  line-height: 1.6;
}

.content-section {
  margin-top: 60px;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 50px;
  align-items: start;
}

.contact-info h2 {
  font-size: clamp(1.6rem, 3vw, 2rem);
  color: var(--color-text-main);
  margin-bottom: 12px;
  font-family: var(--font-heading);
}

.subtitle {
  color: var(--color-text-muted);
  margin-bottom: 32px;
  line-height: 1.6;
  font-size: 1rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  text-decoration: none;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.info-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary);
}

.icon-box {
  width: 50px;
  height: 50px;
  background: rgba(37, 99, 235, 0.08);
  color: var(--color-primary);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.info-item h4 {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 4px;
  font-family: var(--font-heading);
}

.highlight-contact {
  color: var(--color-primary);
  font-size: 1.15rem;
  font-weight: 800;
  font-family: var(--font-heading);
  margin-bottom: 2px;
}

.time-note {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  display: block;
}

/* AI Support Card */
.ai-support-card {
  padding: 36px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-md);
}

.ai-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 12px;
  border-radius: 100px;
  background: #DCFCE7;
  color: #15803D;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 16px;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22C55E;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: 0.6; }
  100% { transform: scale(1); opacity: 1; }
}

.ai-header h3 {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  margin-bottom: 10px;
}

.ai-header p {
  color: var(--color-text-muted);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 24px;
}

.ai-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 20px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-md);
  margin-bottom: 28px;
}

.ai-feature-row {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.9rem;
  color: var(--color-text-main);
}

.ai-feature-row i {
  color: #10B981;
  font-size: 1rem;
  flex-shrink: 0;
}

.btn-chat-now {
  width: 100%;
  padding: 14px 20px;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.transparency-note {
  margin-top: 14px;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

@media (max-width: 992px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 40px;
  }
}
</style>
