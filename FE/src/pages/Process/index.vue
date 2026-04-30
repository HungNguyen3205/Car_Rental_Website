<template>
  <div class="process-page">
    <!-- Hero Header -->
    <section class="process-hero">
      <div class="app-container text-center animate-up">
        <span class="badge">Trải nghiệm đơn giản</span>
        <h1>Quy Trình <span>Thuê Xe</span></h1>
        <p>Bắt đầu hành trình của bạn chỉ trong vài phút với quy trình tối giản và hiện đại.</p>
      </div>
    </section>

    <!-- Visual Process Timeline -->
    <section class="timeline-section app-container">
      <div class="timeline-container">
        <!-- Step 1 -->
        <div class="timeline-item animate-left">
          <div class="step-number">01</div>
          <div class="step-content">
            <div class="step-icon">
              <i class="fa-solid fa-car-side"></i>
            </div>
            <h3>Chọn chiếc xe ưng ý</h3>
            <p>Khám phá đội xe VinFast đa dạng từ SUV sang trọng đến Sedan hiện đại. Xem chi tiết thông số và chọn mẫu xe phù hợp với nhu cầu của bạn.</p>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="timeline-item reverse animate-right">
          <div class="step-number">02</div>
          <div class="step-content">
            <div class="step-icon">
              <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h3>Đặt lịch & Địa điểm</h3>
            <p>Chọn thời gian bắt đầu và kết thúc. Nhập địa chỉ bạn muốn nhận xe, chúng tôi sẽ giao xe tận nơi một cách nhanh chóng và chính xác.</p>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="timeline-item animate-left">
          <div class="step-number">03</div>
          <div class="step-content">
            <div class="step-icon">
              <i class="fa-solid fa-credit-card"></i>
            </div>
            <h3>Thanh toán an toàn</h3>
            <p>Hệ thống hỗ trợ nhiều phương thức thanh toán linh hoạt. Sau khi chuyển khoản, AI sẽ tự động xác nhận đơn hàng cho bạn trong giây lát.</p>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="timeline-item reverse animate-right">
          <div class="step-number">04</div>
          <div class="step-content">
            <div class="step-icon">
              <i class="fa-solid fa-key"></i>
            </div>
            <h3>Nhận xe & Lên đường</h3>
            <p>Kiểm tra xe và ký xác nhận nhanh chóng qua ứng dụng. Giờ đây bạn đã sẵn sàng để tận hưởng hành trình xanh cùng DriveRent!</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to action -->
    <section class="process-cta app-container text-center">
      <div class="cta-card glass">
        <h2>Sẵn sàng để bắt đầu?</h2>
        <p>Hàng ngàn chuyến đi tuyệt vời đang chờ đón bạn.</p>
        <router-link to="/fleet" class="btn-primary">Đặt xe ngay hôm nay <i class="fa-solid fa-bolt"></i></router-link>
      </div>
    </section>
  </div>
</template>

<style scoped>
.process-page {
  padding-top: 100px;
  background: #f8fafc;
  overflow-x: hidden;
}

.process-hero {
  padding: 100px 0 60px;
  background: white;
}

.process-hero h1 {
  font-size: 4rem;
  font-weight: 800;
  margin: 20px 0;
  color: #0f172a;
}

.process-hero h1 span {
  color: var(--color-primary);
}

.process-hero p {
  font-size: 1.25rem;
  color: var(--color-text-muted);
  max-width: 600px;
  margin: 0 auto;
}

/* Timeline Design */
.timeline-section {
  padding: 80px 0;
  position: relative;
}

.timeline-container {
  position: relative;
  max-width: 1000px;
  margin: 0 auto;
}

.timeline-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  width: 2px;
  height: 100%;
  background: linear-gradient(to bottom, transparent, #e2e8f0 10%, #e2e8f0 90%, transparent);
  transform: translateX(-50%);
}

.timeline-item {
  display: flex;
  justify-content: flex-start;
  width: 100%;
  margin-bottom: 80px;
  position: relative;
}

.timeline-item.reverse {
  justify-content: flex-end;
}

.step-number {
  position: absolute;
  left: 50%;
  top: 0;
  transform: translateX(-50%);
  width: 50px;
  height: 50px;
  background: white;
  border: 4px solid var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: var(--color-primary);
  z-index: 5;
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
}

.step-content {
  width: 45%;
  background: white;
  padding: 40px;
  border-radius: 24px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  transition: all 0.4s;
}

.step-content:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}

.step-icon {
  width: 60px;
  height: 60px;
  background: var(--bg-main);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--color-primary);
  margin-bottom: 24px;
}

.step-content h3 {
  font-size: 1.5rem;
  font-weight: 800;
  margin-bottom: 16px;
  color: #0f172a;
}

.step-content p {
  color: var(--color-text-muted);
  line-height: 1.6;
}

.process-cta {
  padding-bottom: 100px;
}

.cta-card {
  padding: 60px;
  border-radius: 30px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
  color: white;
}

.cta-card h2 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 16px;
}

.cta-card p {
  font-size: 1.1rem;
  opacity: 0.8;
  margin-bottom: 32px;
}

@media (max-width: 768px) {
  .timeline-container::before { left: 30px; }
  .step-number { left: 30px; transform: none; }
  .timeline-item, .timeline-item.reverse { justify-content: flex-start; }
  .step-content { width: calc(100% - 70px); margin-left: 70px; }
  .process-hero h1 { font-size: 2.5rem; }
}
</style>
