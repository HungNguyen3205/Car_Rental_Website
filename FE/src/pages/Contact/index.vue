<template>
  <div class="contact-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <span class="badge mb-3">{{ contactContent.badge }}</span>
        <h1 class="page-title">{{ contactContent.title }}</h1>
        <p class="hero-desc">{{ contactContent.hero_desc }}</p>
      </div>
    </section>

    <section class="app-container content-section">
      <div class="contact-grid">
        <!-- Contact Form Card -->
        <div class="contact-form-card card">
          <div class="form-header">
            <div class="icon-circle">
              <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
              <h3>{{ contactContent.form_title }}</h3>
              <p class="subtitle">{{ contactContent.form_subtitle }}</p>
            </div>
          </div>

          <!-- Success State -->
          <div v-if="submittedSuccess" class="success-banner animate-in">
            <div class="success-icon">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4>Yêu cầu đã được tiếp nhận thành công!</h4>
            <p>{{ successMessage }}</p>
            <div class="submitted-info">
              <span class="info-tag"><i class="fa-solid fa-ticket"></i> Mã yêu cầu: #REQ-{{ lastCreatedId }}</span>
              <span class="info-tag"><i class="fa-solid fa-clock"></i> Thời gian: Vừa xong</span>
            </div>
            <button class="btn-outline send-another-btn" @click="resetForm">
              <i class="fa-solid fa-rotate-left"></i> Gửi yêu cầu khác
            </button>
          </div>

          <!-- Form Content -->
          <form v-else @submit.prevent="handleSubmitContact" class="contact-form">
            <!-- Rate limit / Error Alert -->
            <div v-if="errorMessage" class="error-alert animate-in">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>{{ errorMessage }}</span>
            </div>

            <div class="form-row two-cols">
              <div class="form-group">
                <label for="contact-name">Họ và tên <span class="required">*</span></label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-user input-icon"></i>
                  <input 
                    id="contact-name"
                    type="text" 
                    v-model="form.ho_ten" 
                    placeholder="Nguyễn Văn A" 
                    :disabled="submitting"
                    required
                  />
                </div>
                <small v-if="errors.ho_ten" class="error-text">{{ errors.ho_ten[0] }}</small>
              </div>

              <div class="form-group">
                <label for="contact-email">Email liên hệ <span class="required">*</span></label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-envelope input-icon"></i>
                  <input 
                    id="contact-email"
                    type="email" 
                    v-model="form.email" 
                    placeholder="email@example.com" 
                    :disabled="submitting"
                    required
                  />
                </div>
                <small v-if="errors.email" class="error-text">{{ errors.email[0] }}</small>
              </div>
            </div>

            <div class="form-row two-cols">
              <div class="form-group">
                <label for="contact-phone">Số điện thoại <span class="optional">(tùy chọn)</span></label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-phone input-icon"></i>
                  <input 
                    id="contact-phone"
                    type="tel" 
                    v-model="form.so_dien_thoai" 
                    placeholder="0912 345 678" 
                    :disabled="submitting"
                  />
                </div>
                <small v-if="errors.so_dien_thoai" class="error-text">{{ errors.so_dien_thoai[0] }}</small>
              </div>

              <div class="form-group">
                <label for="contact-subject">Tiêu đề yêu cầu <span class="required">*</span></label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-heading input-icon"></i>
                  <input 
                    id="contact-subject"
                    type="text" 
                    v-model="form.tieu_de" 
                    placeholder="Tư vấn lịch xe, chính sách cọc..." 
                    :disabled="submitting"
                    required
                  />
                </div>
                <small v-if="errors.tieu_de" class="error-text">{{ errors.tieu_de[0] }}</small>
              </div>
            </div>

            <div class="form-group">
              <label for="contact-message">Nội dung chi tiết <span class="required">*</span></label>
              <textarea 
                id="contact-message"
                v-model="form.noi_dung" 
                rows="5" 
                placeholder="Vui lòng mô tả chi tiết thắc mắc, phản ánh hoặc yêu cầu thuê xe của bạn..."
                :disabled="submitting"
                required
              ></textarea>
              <div class="char-count">
                <small v-if="errors.noi_dung" class="error-text">{{ errors.noi_dung[0] }}</small>
                <span class="count-text">{{ form.noi_dung.length }}/3000 ký tự</span>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary submit-btn" :disabled="submitting">
                <i v-if="submitting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-paper-plane"></i>
                <span>{{ submitting ? 'Đang gửi yêu cầu...' : 'Gửi yêu cầu hỗ trợ' }}</span>
              </button>
              <span class="security-note">
                <i class="fa-solid fa-shield-halved"></i> Thông tin được mã hóa và bảo mật tuyệt đối.
              </span>
            </div>
          </form>
        </div>

        <!-- Sidebar Direct Channels & AI Assistant -->
        <div class="contact-sidebar">
          <div class="direct-channels card">
            <h3 class="sidebar-title">{{ contactContent.sidebar_title }}</h3>
            <p class="sidebar-sub">{{ contactContent.sidebar_sub }}</p>

            <div class="channel-list">
              <a :href="`tel:${settings.hotline ? settings.hotline.replace(/\s+/g, '') : '19001234'}`" class="channel-item">
                <div class="channel-icon phone">
                  <i class="fa-solid fa-phone"></i>
                </div>
                <div class="channel-meta">
                  <span class="meta-label">Tổng đài CSKH 24/7</span>
                  <span class="meta-val">{{ settings.hotline || '1900 1234' }}</span>
                  <small>Hỗ trợ giải đáp thắc mắc & tư vấn lộ trình</small>
                </div>
              </a>

              <a :href="`mailto:${settings.support_email || 'support@driverentals.com'}`" class="channel-item">
                <div class="channel-icon mail">
                  <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="channel-meta">
                  <span class="meta-label">Email hỗ trợ khách hàng</span>
                  <span class="meta-val">{{ settings.support_email || 'support@driverentals.com' }}</span>
                  <small>Phản hồi trong tối đa 24 giờ</small>
                </div>
              </a>

              <div class="channel-item">
                <div class="channel-icon location">
                  <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="channel-meta">
                  <span class="meta-label">Văn phòng chính</span>
                  <span class="meta-val">{{ settings.company_address || 'Tòa nhà Bitexco, Q.1, TP. HCM' }}</span>
                  <small>08:00 – 18:00 (Thứ 2 – Thứ 7)</small>
                </div>
              </div>
            </div>
          </div>

          <!-- AI Assistant Direct Card -->
          <div class="ai-support-card card">
            <div class="ai-header">
              <div class="ai-badge">
                <span class="pulse-dot"></span> {{ contactContent.ai_badge }}
              </div>
              <h4>{{ contactContent.ai_title }}</h4>
              <p>{{ contactContent.ai_desc }}</p>
            </div>

            <div class="ai-action-box">
              <button type="button" class="btn-primary btn-chat-now" @click="launchChatbot">
                <i class="fa-solid fa-robot"></i> Mở Trợ Lý Ảo DriveBot Ngay
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { useSystemSettings } from '../../core/settingsStore';
import { useWebsiteContent } from '../../core/contentStore';

const contactContent = useWebsiteContent('contact_page');

const toaster = createToaster({ position: "top-right" });
const { settings, fetchSettings } = useSystemSettings();

const submitting = ref(false);
const submittedSuccess = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const lastCreatedId = ref(null);
const errors = ref({});

const form = reactive({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  tieu_de: '',
  noi_dung: '',
});

onMounted(() => {
  fetchSettings();
  // Pre-fill user information if logged in
  const userData = localStorage.getItem('user');
  if (userData) {
    try {
      const parsed = JSON.parse(userData);
      if (parsed) {
        if (parsed.ho_ten) form.ho_ten = parsed.ho_ten;
        if (parsed.email) form.email = parsed.email;
        if (parsed.so_dien_thoai) form.so_dien_thoai = parsed.so_dien_thoai;
      }
    } catch (e) {
      // Ignore JSON error
    }
  }
});

const handleSubmitContact = async () => {
  submitting.value = true;
  errorMessage.value = '';
  errors.value = {};

  try {
    const res = await baseRequest.post('contact', {
      ho_ten: form.ho_ten,
      email: form.email,
      so_dien_thoai: form.so_dien_thoai || null,
      tieu_de: form.tieu_de,
      noi_dung: form.noi_dung,
    });

    if (res.data && res.data.status) {
      submittedSuccess.value = true;
      successMessage.value = res.data.message || 'Yêu cầu của bạn đã được gửi thành công.';
      lastCreatedId.value = res.data.data?.id || 'MỚI';
      toaster.success('Đã gửi yêu cầu liên hệ thành công!');
    } else {
      errorMessage.value = res.data?.message || 'Có lỗi xảy ra khi gửi yêu cầu.';
      toaster.error(errorMessage.value);
    }
  } catch (err) {
    if (err.response?.status === 429) {
      errorMessage.value = err.response.data?.message || 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng chờ 1 phút trước khi thử lại.';
    } else if (err.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors;
      errorMessage.value = err.response.data.message || 'Vui lòng kiểm tra lại thông tin biểu mẫu.';
    } else {
      errorMessage.value = err.response?.data?.message || 'Lỗi hệ thống khi gửi yêu cầu liên hệ.';
    }
    toaster.error(errorMessage.value);
  } finally {
    submitting.value = false;
  }
};

const resetForm = () => {
  submittedSuccess.value = false;
  errorMessage.value = '';
  errors.value = {};
  form.tieu_de = '';
  form.noi_dung = '';
};

const launchChatbot = () => {
  window.dispatchEvent(new CustomEvent('open-ai-chat'));
};
</script>

<style scoped>
.contact-page {
  padding-bottom: 90px;
}

.hero-header {
  background: linear-gradient(180deg, var(--bg-surface-alt) 0%, var(--bg-main) 100%);
  padding: 130px 20px 50px;
  border-bottom: 1px solid var(--color-border);
}

.badge {
  display: inline-block;
  padding: 6px 18px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  border-radius: 100px;
  font-size: 0.85rem;
  font-weight: 700;
  font-family: var(--font-heading);
}

.page-title {
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 800;
  margin-bottom: 14px;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  letter-spacing: -0.5px;
}

.page-title span {
  color: var(--color-primary);
}

.hero-desc {
  font-size: 1.1rem;
  color: var(--color-text-muted);
  max-width: 680px;
  margin: 0 auto;
  line-height: 1.6;
}

.content-section {
  margin-top: 50px;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 40px;
  align-items: start;
}

/* Contact Form Card */
.contact-form-card {
  padding: 36px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-md);
}

.form-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
}

.icon-circle {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.form-header h3 {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  margin-bottom: 4px;
}

.form-header .subtitle {
  font-size: 0.88rem;
  color: var(--color-text-muted);
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row.two-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group label {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.required {
  color: #EF4444;
}

.optional {
  color: var(--color-text-muted);
  font-weight: 400;
  font-size: 0.78rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 14px 12px 40px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 0.92rem;
  color: var(--color-text-main);
  background: white;
  transition: all 0.2s ease;
}

.input-wrapper input:focus,
textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

textarea {
  width: 100%;
  padding: 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 0.92rem;
  color: var(--color-text-main);
  background: white;
  resize: vertical;
  min-height: 120px;
  line-height: 1.5;
  font-family: inherit;
  transition: all 0.2s ease;
}

.char-count {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.count-text {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin-left: auto;
}

.error-text {
  font-size: 0.8rem;
  color: #EF4444;
  font-weight: 600;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  border-radius: var(--radius-md);
  color: #DC2626;
  font-size: 0.88rem;
  font-weight: 600;
}

.form-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 6px;
}

.submit-btn {
  padding: 14px 28px;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-radius: var(--radius-md);
}

.security-note {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

/* Success Banner */
.success-banner {
  text-align: center;
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.success-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #DCFCE7;
  color: #16A34A;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.success-banner h4 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.success-banner p {
  color: var(--color-text-muted);
  font-size: 0.95rem;
  max-width: 480px;
  line-height: 1.6;
}

.submitted-info {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.info-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: var(--bg-surface-alt);
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-primary);
}

.send-another-btn {
  margin-top: 10px;
  padding: 10px 20px;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* Contact Sidebar */
.contact-sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.direct-channels {
  padding: 30px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
}

.sidebar-title {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
  margin-bottom: 6px;
}

.sidebar-sub {
  font-size: 0.88rem;
  color: var(--color-text-muted);
  margin-bottom: 24px;
}

.channel-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.channel-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  text-decoration: none;
  transition: all 0.2s ease;
}

.channel-item:hover {
  transform: translateY(-2px);
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.channel-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}

.channel-icon.phone {
  background: #EFF6FF;
  color: #2563EB;
}

.channel-icon.mail {
  background: #F0FDF4;
  color: #16A34A;
}

.channel-icon.location {
  background: #FAF5FF;
  color: #9333EA;
}

.channel-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.meta-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.meta-val {
  font-size: 1rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.channel-meta small {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

/* AI Assistant Card */
.ai-support-card {
  padding: 28px;
  background: linear-gradient(135deg, #0B1220 0%, #1E293B 100%);
  color: white;
  border-radius: var(--radius-xl);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: var(--shadow-md);
}

.ai-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  border-radius: 100px;
  background: rgba(34, 197, 94, 0.2);
  color: #4ADE80;
  font-size: 0.78rem;
  font-weight: 700;
  margin-bottom: 14px;
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

.ai-header h4 {
  font-size: 1.18rem;
  font-weight: 800;
  margin-bottom: 8px;
  color: white;
  font-family: var(--font-heading);
}

.ai-header p {
  font-size: 0.88rem;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.5;
  margin-bottom: 20px;
}

.btn-chat-now {
  width: 100%;
  padding: 12px 18px;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #2563EB;
}

.btn-chat-now:hover {
  background: #1D4ED8;
}

.animate-in {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 992px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  
  .form-row.two-cols {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
