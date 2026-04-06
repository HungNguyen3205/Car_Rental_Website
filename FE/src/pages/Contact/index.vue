<template>
  <div class="contact-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <h1>Liên Hệ Với DriveRentals</h1>
        <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="contact-grid">
        <!-- Contact Info -->
        <div class="contact-info">
          <h2>Thông Tin Liên Hệ</h2>
          <p class="subtitle">Đừng ngần ngại liên hệ với chúng tôi nếu bạn có bất kỳ thắc mắc nào về dịch vụ.</p>
          
          <div class="info-list">
            <div class="info-item">
              <div class="icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </div>
              <div>
                <h4>Hotline 24/7</h4>
                <p>1900 1234 56</p>
              </div>
            </div>
            
            <div class="info-item">
              <div class="icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              </div>
              <div>
                <h4>Email Box</h4>
                <p>support@driverentals.com</p>
              </div>
            </div>
            
            <div class="info-item">
              <div class="icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div>
                <h4>Trụ sở chính</h4>
                <p>Tòa nhà Bitexco, Số 2 Hải Triều, Q.1, TP. HCM</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form card">
          <h3>Gửi tin nhắn cho chúng tôi</h3>
          <form @submit.prevent="submitForm">
            <div class="form-group">
              <label>Họ và tên</label>
              <input type="text" placeholder="Nguyễn Văn A" required />
            </div>
            <div class="form-group">
              <label>Số điện thoại / Email</label>
              <input type="text" placeholder="SĐT hoặc Email của bạn" required />
            </div>
            <div class="form-group">
              <label>Lời nhắn</label>
              <textarea rows="4" placeholder="Nhập nội dung cần hỗ trợ..." required></textarea>
            </div>
            <button type="submit" class="btn-primary w-100 mt-2">Gửi Yêu Cầu</button>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const submitForm = () => {
  alert("Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.");
};
</script>

<style scoped>
.contact-page {
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
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
}
.contact-info h2 {
  font-size: 2rem;
  color: #0f172a;
  margin-bottom: 12px;
}
.subtitle {
  color: #64748b;
  margin-bottom: 40px;
  line-height: 1.6;
}
.info-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.info-item {
  display: flex;
  align-items: center;
  gap: 16px;
}
.icon-box {
  width: 48px;
  height: 48px;
  background: #eff6ff;
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.info-item h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
}
.info-item p {
  color: #475569;
  font-size: 0.95rem;
}
.contact-form {
  padding: 32px;
  background: white;
}
.contact-form h3 {
  font-size: 1.5rem;
  margin-bottom: 24px;
  color: #0f172a;
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
}
.form-group input, .form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus {
  border-color: var(--color-primary);
}
.w-100 {
  width: 100%;
  padding: 14px;
  font-size: 1rem;
}
.mt-2 {
  margin-top: 16px;
}

@media (max-width: 768px) {
  .contact-grid { grid-template-columns: 1fr; }
}
</style>
