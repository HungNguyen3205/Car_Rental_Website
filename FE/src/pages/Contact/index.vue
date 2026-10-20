<template>
  <div class="contact-page" data-page="contact">
    <!-- 1. COMPACT EDITORIAL HERO & BREADCRUMB (Matching Home / About / Process) -->
    <section class="contact-hero-section" aria-label="Đầu trang liên hệ">
      <div class="app-container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <router-link to="/" class="bc-link">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
          </router-link>
          <span class="bc-sep" aria-hidden="true">/</span>
          <span class="bc-current" aria-current="page">Liên hệ & Tư vấn</span>
        </nav>

        <div class="hero-inner text-center">
          <div class="eyebrow-pill">
            <span class="glow-dot" aria-hidden="true"></span>
            <span>{{ pageBadge }}</span>
          </div>
          <h1 class="hero-title">
            {{ pageTitle }}<span class="title-dot">.</span>
          </h1>
          <p class="hero-desc">
            {{ pageHeroDesc }}
          </p>
        </div>
      </div>
    </section>

    <!-- 2. MAIN TWO-COLUMN WORKSPACE -->
    <section class="app-container content-section" aria-label="Nội dung liên hệ">
      <div class="contact-workspace-grid">
        
        <!-- LEFT COLUMN: Contact Channels & Support Resources -->
        <aside class="contact-info-column" aria-label="Thông tin và kênh liên hệ">
          <!-- Primary Contact Channels Card -->
          <div class="info-card card">
            <div class="card-top-header">
              <div class="icon-badge">
                <i class="fa-solid fa-headset"></i>
              </div>
              <div>
                <h2 class="card-title">{{ contactContent.sidebar_title || 'Kênh Liên Hệ Trực Tiếp' }}</h2>
                <p class="card-sub">{{ contactContent.sidebar_sub || 'Kết nối ngay với chúng tôi qua các kênh hỗ trợ chính thức:' }}</p>
              </div>
            </div>

            <div class="channel-list">
              <!-- Hotline -->
              <a 
                v-if="cleanHotline" 
                :href="`tel:${cleanHotline}`" 
                class="channel-box"
                aria-label="Gọi tổng đài hỗ trợ"
              >
                <div class="channel-icon-wrap phone">
                  <i class="fa-solid fa-phone"></i>
                </div>
                <div class="channel-body">
                  <span class="channel-label">Tổng đài chăm sóc khách hàng</span>
                  <span class="channel-value">{{ settings.hotline }}</span>
                  <span class="channel-hint">Giờ làm việc: 08:00 – 18:00 (Thứ 2 – Thứ 7)</span>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square channel-ext-icon" aria-hidden="true"></i>
              </a>

              <!-- Email -->
              <a 
                v-if="settings.support_email" 
                :href="`mailto:${settings.support_email}`" 
                class="channel-box"
                aria-label="Gửi email cho bộ phận hỗ trợ"
              >
                <div class="channel-icon-wrap email">
                  <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="channel-body">
                  <span class="channel-label">Hộp thư hỗ trợ dịch vụ</span>
                  <span class="channel-value">{{ settings.support_email }}</span>
                  <span class="channel-hint">Tiếp nhận và phản hồi trong ngày làm việc</span>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square channel-ext-icon" aria-hidden="true"></i>
              </a>

              <!-- Head Office Address -->
              <div v-if="settings.company_address" class="channel-box static-channel">
                <div class="channel-icon-wrap location">
                  <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="channel-body">
                  <span class="channel-label">Trụ sở & Điểm giao nhận chính</span>
                  <span class="channel-value text-normal">{{ settings.company_address }}</span>
                  <span class="channel-hint">Có chỗ đỗ ô tô và trạm sạc nhanh tại điểm</span>
                </div>
              </div>

              <!-- Operating Hours Summary -->
              <div class="channel-box static-channel">
                <div class="channel-icon-wrap hours">
                  <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="channel-body">
                  <span class="channel-label">Thời gian phục vụ khách hàng</span>
                  <span class="channel-value text-normal">08:00 – 18:00 (Thứ 2 – Thứ 7)</span>
                  <span class="channel-hint">Giao nhận xe ngoài giờ theo lịch hẹn trước</span>
                </div>
              </div>
            </div>

            <!-- Validated Social Links (Only displayed if valid URLs are present) -->
            <div v-if="hasValidatedSocials" class="social-channels-block">
              <span class="social-block-title">Kênh mạng xã hội chính thức:</span>
              <div class="social-buttons-row">
                <a 
                  v-if="validFacebookUrl" 
                  :href="validFacebookUrl" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="social-btn facebook"
                  aria-label="Fanpage Facebook DriveRent"
                >
                  <i class="fa-brands fa-facebook-f"></i>
                  <span>Facebook Fanpage</span>
                </a>
                <a 
                  v-if="validZaloUrl" 
                  :href="validZaloUrl" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="social-btn zalo"
                  aria-label="Kênh Zalo OA DriveRent"
                >
                  <i class="fa-solid fa-comment-dots"></i>
                  <span>Zalo Hỗ Trợ</span>
                </a>
              </div>
            </div>
          </div>

          <!-- AI Assistant DriveBot Card -->
          <div class="ai-assistant-card card">
            <div class="ai-card-glow" aria-hidden="true"></div>
            <div class="ai-card-header">
              <div class="ai-pill">
                <span class="pulse-dot"></span>
                <span>{{ contactContent.ai_badge || 'HỖ TRỢ TỰ ĐỘNG' }}</span>
              </div>
              <h3 class="ai-card-title">{{ contactContent.ai_title || 'Trợ Lý Ảo DriveBot' }}</h3>
              <p class="ai-card-desc">
                {{ contactContent.ai_desc || 'Tra cứu thông số pin, bảng giá niêm yết theo ngày hoặc hướng dẫn thủ tục cọc tự động chỉ trong vài giây.' }}
              </p>
            </div>
            <button 
              type="button" 
              class="btn-ai-launch" 
              @click="launchChatbot"
              aria-label="Mở khung trò chuyện với Trợ lý ảo DriveBot"
            >
              <i class="fa-solid fa-robot"></i>
              <span>Trò chuyện cùng DriveBot ngay</span>
            </button>
          </div>

          <!-- Quick Practical FAQ Accordion -->
          <div class="faq-summary-card card">
            <h3 class="faq-summary-title">
              <i class="fa-solid fa-circle-question text-emerald"></i>
              <span>Giải đáp nhanh trước khi gửi yêu cầu</span>
            </h3>
            <div class="faq-accordion-list">
              <details class="faq-item" open>
                <summary class="faq-question">
                  <span>Mức đặt cọc xe tự lái được tính như thế nào?</span>
                  <i class="fa-solid fa-chevron-down faq-arrow" aria-hidden="true"></i>
                </summary>
                <p class="faq-answer">
                  Mức cọc thông thường dao động từ 15 – 30 triệu đồng tùy dòng xe (VF3, VF5, VF8, VF9) hoặc tài sản thế chấp (xe máy chính chủ kèm cà vẹt). Khoản cọc được đối soát và hoàn trả minh bạch sau khi kết thúc hợp đồng thuê.
                </p>
              </details>

              <details class="faq-item">
                <summary class="faq-question">
                  <span>Bao lâu sau khi gửi form tôi sẽ nhận được phản hồi?</span>
                  <i class="fa-solid fa-chevron-down faq-arrow" aria-hidden="true"></i>
                </summary>
                <p class="faq-answer">
                  Chuyên viên chăm sóc khách hàng sẽ liên hệ lại qua điện thoại hoặc email trong vòng 2 – 4 giờ làm việc. Nếu cần xe gấp, bạn vui lòng gọi trực tiếp hotline.
                </p>
              </details>

              <details class="faq-item">
                <summary class="faq-question">
                  <span>Thuê xe cho doanh nghiệp hoặc dài hạn có hóa đơn VAT không?</span>
                  <i class="fa-solid fa-chevron-down faq-arrow" aria-hidden="true"></i>
                </summary>
                <p class="faq-answer">
                  Có đầy đủ hợp đồng pháp lý và xuất hóa đơn điện tử VAT hợp lệ theo quy định. Bạn chỉ cần chọn chủ đề "Hợp tác doanh nghiệp / Thuê dài hạn" để nhận báo giá chiết khấu riêng.
                </p>
              </details>
            </div>
          </div>
        </aside>

        <!-- RIGHT COLUMN: Consultation Request Form -->
        <main class="contact-form-column" aria-label="Biểu mẫu đăng ký tư vấn">
          <div class="form-container-card card">
            
            <!-- Form Card Header -->
            <div class="form-card-header">
              <div class="icon-badge emerald">
                <i class="fa-solid fa-paper-plane"></i>
              </div>
              <div>
                <h2 class="form-card-title">{{ contactContent.form_title || 'Đăng Ký Tư Vấn & Báo Giá' }}</h2>
                <p class="form-card-subtitle">{{ contactContent.form_subtitle || 'Điền thông tin bên dưới, chuyên viên DriveRent sẽ liên hệ xác nhận lịch khả dụng và tư vấn chi tiết.' }}</p>
              </div>
            </div>

            <!-- SUCCESS STATE (Only shown after server confirms save) -->
            <div v-if="submittedSuccess" class="submission-success-banner" role="status" aria-live="polite">
              <div class="success-icon-bubble">
                <i class="fa-solid fa-circle-check"></i>
              </div>
              <h3 class="success-title">Yêu cầu đã được tiếp nhận thành công!</h3>
              <p class="success-desc">{{ successMessage }}</p>

              <div class="ticket-badge-box">
                <div class="ticket-row">
                  <span class="t-label"><i class="fa-solid fa-ticket"></i> Mã yêu cầu:</span>
                  <strong class="t-val">#REQ-{{ lastCreatedId }}</strong>
                </div>
                <div class="ticket-row" v-if="submittedSummary.ho_ten">
                  <span class="t-label"><i class="fa-solid fa-user"></i> Khách hàng:</span>
                  <span class="t-val">{{ submittedSummary.ho_ten }}</span>
                </div>
                <div class="ticket-row" v-if="submittedSummary.contactMethod">
                  <span class="t-label"><i class="fa-solid fa-address-book"></i> Liên hệ qua:</span>
                  <span class="t-val">{{ submittedSummary.contactMethod }}</span>
                </div>
                <div class="ticket-row" v-if="submittedSummary.carName">
                  <span class="t-label"><i class="fa-solid fa-car"></i> Xe quan tâm:</span>
                  <span class="t-val highlight-emerald">{{ submittedSummary.carName }}</span>
                </div>
              </div>

              <div class="success-actions">
                <button type="button" class="btn-outline reset-form-btn" @click="resetForm">
                  <i class="fa-solid fa-rotate-left"></i>
                  <span>Gửi thêm yêu cầu khác</span>
                </button>
                <router-link to="/fleet" class="btn-primary view-fleet-btn">
                  <span>Xem danh sách đội xe</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </router-link>
              </div>
            </div>

            <!-- FORM STATE -->
            <form v-else @submit.prevent="handleSubmitContact" class="consultation-form" novalidate>
              
              <!-- Server-wide Error Banner -->
              <div v-if="errorMessage" class="server-error-banner" role="alert" aria-live="polite">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ errorMessage }}</span>
              </div>

              <!-- Honeypot anti-spam field (hidden from real users, trips bots) -->
              <div class="honeypot-field" aria-hidden="true">
                <label for="contact-website-hp">Website (không điền trường này nếu bạn là người dùng)</label>
                <input 
                  id="contact-website-hp" 
                  type="text" 
                  v-model="form.website_hp" 
                  tabindex="-1" 
                  autocomplete="off" 
                />
              </div>

              <!-- ROW 1: Họ tên & Email -->
              <div class="form-grid-row two-cols">
                <!-- Họ và tên -->
                <div class="form-field-group" :class="{ 'has-error': errors.ho_ten }">
                  <label for="contact-fullname" class="field-label">
                    <span>Họ và tên</span>
                    <span class="req-star" aria-hidden="true">*</span>
                  </label>
                  <div class="input-control-wrap">
                    <i class="fa-solid fa-user field-icon" aria-hidden="true"></i>
                    <input 
                      id="contact-fullname"
                      type="text" 
                      v-model="form.ho_ten" 
                      placeholder="Ví dụ: Nguyễn Văn An" 
                      maxlength="100"
                      :disabled="submitting"
                      required
                      :aria-invalid="!!errors.ho_ten"
                      :aria-describedby="errors.ho_ten ? 'error-ho-ten' : undefined"
                    />
                  </div>
                  <span v-if="errors.ho_ten" id="error-ho-ten" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.ho_ten[0] }}
                  </span>
                </div>

                <!-- Email -->
                <div class="form-field-group" :class="{ 'has-error': errors.email }">
                  <label for="contact-email" class="field-label">
                    <span>Email liên hệ</span>
                    <span class="req-star" aria-hidden="true">*</span>
                  </label>
                  <div class="input-control-wrap">
                    <i class="fa-solid fa-envelope field-icon" aria-hidden="true"></i>
                    <input 
                      id="contact-email"
                      type="email" 
                      v-model="form.email" 
                      placeholder="email@example.com" 
                      maxlength="150"
                      :disabled="submitting"
                      required
                      :aria-invalid="!!errors.email"
                      :aria-describedby="errors.email ? 'error-email' : undefined"
                    />
                  </div>
                  <span v-if="errors.email" id="error-email" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.email[0] }}
                  </span>
                </div>
              </div>

              <!-- ROW 2: Số điện thoại & Ngày dự kiến thuê -->
              <div class="form-grid-row two-cols">
                <!-- Số điện thoại (tùy chọn) -->
                <div class="form-field-group" :class="{ 'has-error': errors.so_dien_thoai }">
                  <label for="contact-phone" class="field-label">
                    <span>Số điện thoại</span>
                    <span class="opt-label">(tùy chọn - để nhận cuộc gọi tư vấn)</span>
                  </label>
                  <div class="input-control-wrap">
                    <i class="fa-solid fa-phone field-icon" aria-hidden="true"></i>
                    <input 
                      id="contact-phone"
                      type="tel" 
                      v-model="form.so_dien_thoai" 
                      placeholder="0912 345 678" 
                      maxlength="20"
                      :disabled="submitting"
                      :aria-invalid="!!errors.so_dien_thoai"
                      :aria-describedby="errors.so_dien_thoai ? 'error-phone' : undefined"
                    />
                  </div>
                  <span v-if="errors.so_dien_thoai" id="error-phone" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.so_dien_thoai[0] }}
                  </span>
                </div>

                <!-- Ngày dự kiến thuê (tùy chọn) -->
                <div class="form-field-group" :class="{ 'has-error': errors.rental_date }">
                  <label for="contact-rental-date" class="field-label">
                    <span>Ngày dự kiến nhận xe</span>
                    <span class="opt-label">(tùy chọn)</span>
                  </label>
                  <div class="input-control-wrap">
                    <i class="fa-solid fa-calendar-days field-icon" aria-hidden="true"></i>
                    <input 
                      id="contact-rental-date"
                      type="date" 
                      v-model="form.rental_date" 
                      :min="todayIsoDate"
                      :disabled="submitting"
                      :aria-invalid="!!errors.rental_date"
                      :aria-describedby="errors.rental_date ? 'error-rental-date' : undefined"
                    />
                  </div>
                  <span v-if="errors.rental_date" id="error-rental-date" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.rental_date[0] }}
                  </span>
                </div>
              </div>

              <!-- ROW 3: Chủ đề yêu cầu & Xe quan tâm -->
              <div class="form-grid-row two-cols">
                <!-- Chủ đề yêu cầu -->
                <div class="form-field-group" :class="{ 'has-error': errors.tieu_de }">
                  <label for="contact-subject-select" class="field-label">
                    <span>Chủ đề tư vấn</span>
                    <span class="req-star" aria-hidden="true">*</span>
                  </label>
                  <div class="select-control-wrap">
                    <i class="fa-solid fa-tag field-icon" aria-hidden="true"></i>
                    <select 
                      id="contact-subject-select"
                      v-model="selectedTopicOption"
                      @change="handleTopicSelectChange"
                      :disabled="submitting"
                      class="custom-select"
                    >
                      <option value="" disabled>-- Chọn chủ đề cần hỗ trợ --</option>
                      <option v-for="topic in presetTopics" :key="topic" :value="topic">
                        {{ topic }}
                      </option>
                      <option value="custom">Chủ đề khác (tự nhập)...</option>
                    </select>
                    <i class="fa-solid fa-chevron-down select-caret" aria-hidden="true"></i>
                  </div>

                  <!-- Input phụ khi chọn "Chủ đề khác" -->
                  <div v-if="selectedTopicOption === 'custom'" class="input-control-wrap mt-2">
                    <i class="fa-solid fa-pen field-icon" aria-hidden="true"></i>
                    <input 
                      type="text" 
                      v-model="customSubject" 
                      placeholder="Nhập tiêu đề yêu cầu của bạn..."
                      maxlength="200"
                      :disabled="submitting"
                      required
                    />
                  </div>
                  <span v-if="errors.tieu_de" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.tieu_de[0] }}
                  </span>
                </div>

                <!-- Xe quan tâm (Dữ liệu thật từ DB) -->
                <div class="form-field-group" :class="{ 'has-error': errors.car_id }">
                  <label for="contact-car-select" class="field-label">
                    <span>Dòng xe bạn quan tâm</span>
                    <span class="opt-label">(tùy chọn)</span>
                  </label>
                  <div class="select-control-wrap">
                    <i class="fa-solid fa-car field-icon" aria-hidden="true"></i>
                    <select 
                      id="contact-car-select"
                      v-model="form.car_id" 
                      :disabled="submitting || loadingCars"
                      class="custom-select"
                    >
                      <option :value="null">-- Chưa xác định / Tư vấn chung --</option>
                      <option v-for="car in availableCars" :key="car.id" :value="car.id">
                        {{ car.name }} {{ car.type ? `(${car.type})` : '' }} - từ {{ formatVnd(car.price_per_day) }}/ngày
                      </option>
                    </select>
                    <i v-if="loadingCars" class="fa-solid fa-spinner fa-spin select-caret" aria-hidden="true"></i>
                    <i v-else class="fa-solid fa-chevron-down select-caret" aria-hidden="true"></i>
                  </div>
                  <span v-if="errors.car_id" class="field-error-msg" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ errors.car_id[0] }}
                  </span>
                </div>
              </div>

              <!-- ROW 4: Nội dung chi tiết -->
              <div class="form-field-group" :class="{ 'has-error': errors.noi_dung }">
                <div class="label-with-counter">
                  <label for="contact-message" class="field-label">
                    <span>Nội dung chi tiết</span>
                    <span class="req-star" aria-hidden="true">*</span>
                  </label>
                  <span class="char-counter" :class="{ 'limit-warn': form.noi_dung.length > 2800 }">
                    {{ form.noi_dung.length }}/3000 ký tự
                  </span>
                </div>
                <textarea 
                  id="contact-message"
                  v-model="form.noi_dung" 
                  rows="4" 
                  placeholder="Vui lòng chia sẻ lộ trình dự kiến, địa điểm nhận xe, số lượng người đi hoặc bất kỳ yêu cầu đặc biệt nào để nhân viên chuẩn bị tốt nhất..."
                  maxlength="3000"
                  :disabled="submitting"
                  required
                  :aria-invalid="!!errors.noi_dung"
                  :aria-describedby="errors.noi_dung ? 'error-noi-dung' : undefined"
                ></textarea>
                <span v-if="errors.noi_dung" id="error-noi-dung" class="field-error-msg" role="alert">
                  <i class="fa-solid fa-circle-exclamation"></i> {{ errors.noi_dung[0] }}
                </span>
              </div>

              <!-- ROW 5: Policy Agreement Checkbox -->
              <div class="privacy-agreement-row">
                <label class="custom-checkbox-label">
                  <input 
                    type="checkbox" 
                    v-model="agreedPolicy" 
                    :disabled="submitting"
                    required
                  />
                  <span class="checkbox-box" aria-hidden="true">
                    <i class="fa-solid fa-check check-mark"></i>
                  </span>
                  <span class="checkbox-text">
                    Tôi đồng ý để DriveRent lưu trữ thông tin phục vụ mục đích liên hệ tư vấn theo 
                    <router-link to="/policies" target="_blank" class="policy-link">Chính sách bảo mật</router-link>.
                  </span>
                </label>
              </div>

              <!-- ROW 6: Submit Button & Reassurance Notes -->
              <div class="form-actions-bar">
                <button 
                  type="submit" 
                  class="btn-primary submit-action-btn" 
                  :disabled="submitting || !agreedPolicy"
                >
                  <i v-if="submitting" class="fa-solid fa-circle-notch fa-spin"></i>
                  <i v-else class="fa-solid fa-paper-plane"></i>
                  <span>{{ submitting ? 'Đang gửi yêu cầu...' : 'Gửi yêu cầu tư vấn' }}</span>
                </button>

                <div class="reassurance-pill">
                  <i class="fa-solid fa-shield-halved text-emerald"></i>
                  <span>Thông tin an toàn & không nhận cuộc gọi làm phiền</span>
                </div>
              </div>

            </form>
          </div>
        </main>
      </div>
    </section>

    <!-- 3. SHOWROOM & PICKUP OFFICE MAP SECTION (Validated embed or secure external map) -->
    <section class="app-container map-section" aria-label="Bản đồ trạm nhận xe">
      <div class="map-card card">
        <div class="map-card-header">
          <div class="map-header-left">
            <div class="map-pin-icon">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
              <h2 class="map-title">Văn Phòng Trụ Sở & Trạm Giao Nhận Xe</h2>
              <p class="map-address">{{ settings.company_address || 'Địa chỉ đang được cập nhật trong hệ thống' }}</p>
            </div>
          </div>

          <div class="map-header-actions">
            <!-- GPS Badge if available -->
            <span v-if="settings.map_coordinates" class="gps-badge">
              <i class="fa-solid fa-crosshairs"></i>
              <span>GPS: {{ settings.map_coordinates }}</span>
            </span>

            <!-- External Google Maps Link -->
            <a 
              :href="externalMapSearchUrl" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="btn-map-external"
              aria-label="Mở địa điểm trong Google Maps"
            >
              <i class="fa-solid fa-arrow-up-right-from-square"></i>
              <span>Mở bản đồ ngoài</span>
            </a>
          </div>
        </div>

        <!-- Safe Embedded Map or Fallback Card -->
        <div class="map-view-container">
          <iframe
            v-if="safeMapEmbedUrl"
            :src="safeMapEmbedUrl"
            width="100%"
            height="390"
            style="border: 0; display: block;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Bản đồ vị trí văn phòng DriveRent"
          ></iframe>

          <!-- Fallback when embed URL is missing or not configured -->
          <div v-else class="map-fallback-view">
            <div class="fallback-content text-center">
              <div class="fallback-icon">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <h3 class="fallback-title">{{ settings.company_address || 'Trụ sở chính DriveRent' }}</h3>
              <p class="fallback-desc">
                Bản đồ nhúng trực tiếp hiện chưa được cấu hình. Bạn có thể mở vị trí chính xác trên Google Maps để dẫn đường.
              </p>
              <a 
                :href="externalMapSearchUrl" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="btn-primary btn-open-external"
              >
                <i class="fa-solid fa-diamond-turn-right"></i>
                <span>Chỉ đường trên Google Maps</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { useSystemSettings } from '../../core/settingsStore';
import { useWebsiteContent } from '../../core/contentStore';

const route = useRoute();
const toaster = createToaster({ position: "top-right" });

// Load dynamic CMS content & System Settings
const contactContent = useWebsiteContent('contact_page');
const { settings, fetchSettings } = useSystemSettings();

const pageTitle = computed(() => {
  return contactContent.value?.title || 'Liên Hệ & Đăng Ký Tư Vấn Thuê Xe';
});

const pageBadge = computed(() => {
  return contactContent.value?.badge || 'TRUNG TÂM TƯ VẤN & HỖ TRỢ KHÁCH HÀNG';
});

const pageHeroDesc = computed(() => {
  const desc = contactContent.value?.hero_desc;
  if (!desc || typeof desc !== 'string') {
    return 'Đội ngũ chuyên viên DriveRent sẵn sàng lắng nghe, tư vấn lịch xe điện tối ưu và giải đáp minh bạch mọi thắc mắc của bạn.';
  }
  return desc.replace(/\s*24\/7\.?$/i, ' trong giờ làm việc.').replace(/24\/7/gi, 'nhanh chóng');
});

// Preset consultation topics
const presetTopics = [
  'Tư vấn chọn xe điện & báo giá theo ngày',
  'Hỏi thủ tục đặt cọc & giấy tờ nhận xe',
  'Thuê xe dài hạn / Hợp tác doanh nghiệp',
  'Hỗ trợ kỹ thuật & trạm sạc trên hành trình',
  'Góp ý chất lượng dịch vụ & khác',
];

const selectedTopicOption = ref('');
const customSubject = ref('');
const agreedPolicy = ref(true);

const submitting = ref(false);
const submittedSuccess = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const lastCreatedId = ref(null);
const errors = ref({});

// Summary saved after success
const submittedSummary = reactive({
  ho_ten: '',
  contactMethod: '',
  carName: '',
});

// Cars data from server
const availableCars = ref([]);
const loadingCars = ref(false);

// Form model
const form = reactive({
  ho_ten: '',
  email: '',
  so_dien_thoai: '',
  tieu_de: '',
  car_id: null,
  rental_date: '',
  noi_dung: '',
  website_hp: '', // Honeypot field
});

// Today's date in YYYY-MM-DD for datepicker min attribute
const todayIsoDate = computed(() => {
  const now = new Date();
  return now.toISOString().split('T')[0];
});

// Format hotline for tel: link (strips spaces and non-digits/+)
const cleanHotline = computed(() => {
  if (!settings.hotline) return '';
  return String(settings.hotline).replace(/[^0-9+]/g, '');
});

// Validate social URLs (strictly require https:// or http://)
const validFacebookUrl = computed(() => {
  const url = settings.social_facebook;
  if (!url || typeof url !== 'string') return '';
  const trimmed = url.trim();
  if (trimmed.startsWith('https://facebook.com') || trimmed.startsWith('https://www.facebook.com')) {
    return trimmed;
  }
  return '';
});

const validZaloUrl = computed(() => {
  const url = settings.social_zalo;
  if (!url || typeof url !== 'string') return '';
  const trimmed = url.trim();
  if (trimmed.startsWith('https://zalo.me/') || trimmed.startsWith('https://oa.zalo.me/')) {
    return trimmed;
  }
  return '';
});

const hasValidatedSocials = computed(() => {
  return !!validFacebookUrl.value || !!validZaloUrl.value;
});

// Safe map embed URL verification (prevent arbitrary iframe injection)
const safeMapEmbedUrl = computed(() => {
  const url = settings.map_embed_url;
  if (!url || typeof url !== 'string') return '';
  const trimmed = url.trim();
  if (
    trimmed.startsWith('https://www.google.com/maps/embed') ||
    trimmed.startsWith('https://maps.google.com/') ||
    trimmed.startsWith('https://www.openstreetmap.org/export/embed.html')
  ) {
    return trimmed;
  }
  return '';
});

// External Google Maps fallback search URL
const externalMapSearchUrl = computed(() => {
  const query = settings.map_coordinates || settings.company_address || 'Bitexco Financial Tower Ho Chi Minh';
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
});

// Format VND currency
const formatVnd = (amount) => {
  if (!amount || isNaN(amount)) return '0đ';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
};

// Handle topic change
const handleTopicSelectChange = () => {
  if (selectedTopicOption.value === 'custom') {
    form.tieu_de = customSubject.value;
  } else {
    form.tieu_de = selectedTopicOption.value;
  }
};

// Fetch real cars list from backend
const fetchCarsList = async () => {
  loadingCars.value = true;
  try {
    const res = await baseRequest.get('client/car/data');
    if (res.data && res.data.status && Array.isArray(res.data.data)) {
      availableCars.value = res.data.data.map(car => ({
        id: car.id,
        name: car.name,
        type: car.type || car.category_name,
        price_per_day: car.price_per_day,
        image: car.image,
      }));
    }
  } catch (e) {
    // Graceful fallback: form can still be submitted without picking a car
    availableCars.value = [];
  } finally {
    loadingCars.value = false;
  }
};

onMounted(async () => {
  fetchSettings();
  await fetchCarsList();

  // 1. Pre-fill user data from logged in session if available
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
      // Ignore JSON parse error
    }
  }

  // 2. Check query params (e.g. ?car_id=1&subject=...)
  if (route.query.car_id) {
    const parsedCarId = Number(route.query.car_id);
    if (!isNaN(parsedCarId)) {
      form.car_id = parsedCarId;
    }
  }

  if (route.query.subject) {
    const subj = String(route.query.subject).trim();
    if (presetTopics.includes(subj)) {
      selectedTopicOption.value = subj;
      form.tieu_de = subj;
    } else {
      selectedTopicOption.value = 'custom';
      customSubject.value = subj;
      form.tieu_de = subj;
    }
  } else {
    // Default topic
    selectedTopicOption.value = presetTopics[0];
    form.tieu_de = presetTopics[0];
  }
});

// Submit consultation request
const handleSubmitContact = async () => {
  // Sync subject
  if (selectedTopicOption.value === 'custom') {
    form.tieu_de = customSubject.value.trim();
  } else {
    form.tieu_de = selectedTopicOption.value;
  }

  // Frontend quick validation
  errors.value = {};
  errorMessage.value = '';

  const clientErrors = {};
  if (!form.ho_ten || !form.ho_ten.trim()) {
    clientErrors.ho_ten = ['Vui lòng nhập họ và tên của bạn!'];
  }
  if (!form.email || !form.email.trim()) {
    clientErrors.email = ['Vui lòng cung cấp địa chỉ email liên hệ!'];
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
    clientErrors.email = ['Địa chỉ email không đúng định dạng hợp lệ!'];
  }

  if (form.so_dien_thoai && form.so_dien_thoai.trim()) {
    const cleanPhone = form.so_dien_thoai.trim();
    if (!/^[0-9+\s\-().]{8,20}$/.test(cleanPhone)) {
      clientErrors.so_dien_thoai = ['Số điện thoại chứa ký tự không hợp lệ!'];
    }
  }

  if (!form.tieu_de || form.tieu_de.length < 3) {
    clientErrors.tieu_de = ['Vui lòng chọn hoặc nhập tiêu đề yêu cầu (ít nhất 3 ký tự)!'];
  }

  if (!form.noi_dung || form.noi_dung.trim().length < 5) {
    clientErrors.noi_dung = ['Vui lòng nhập nội dung chi tiết (ít nhất 5 ký tự)!'];
  }

  if (Object.keys(clientErrors).length > 0) {
    errors.value = clientErrors;
    errorMessage.value = 'Vui lòng kiểm tra và hoàn thiện các mục còn thiếu bên dưới.';
    toaster.warning(errorMessage.value);
    return;
  }

  submitting.value = true;

  try {
    const payload = {
      ho_ten: form.ho_ten.trim(),
      email: form.email.trim(),
      so_dien_thoai: form.so_dien_thoai ? form.so_dien_thoai.trim() : null,
      tieu_de: form.tieu_de.trim(),
      car_id: form.car_id || null,
      rental_date: form.rental_date || null,
      noi_dung: form.noi_dung.trim(),
      source: 'Trang Liên hệ',
      website_hp: form.website_hp || null,
    };

    const res = await baseRequest.post('contact', payload);

    if (res.data && res.data.status) {
      submittedSuccess.value = true;
      successMessage.value = res.data.message || 'Yêu cầu tư vấn của bạn đã được tiếp nhận thành công.';
      lastCreatedId.value = res.data.data?.id || 'MỚI';

      // Store summary for display
      submittedSummary.ho_ten = form.ho_ten;
      submittedSummary.contactMethod = form.so_dien_thoai ? `${form.email} • ${form.so_dien_thoai}` : form.email;
      
      const foundCar = availableCars.value.find(c => c.id === form.car_id);
      submittedSummary.carName = foundCar ? foundCar.name : (form.car_id ? `Mã xe #${form.car_id}` : '');

      toaster.success('Đã gửi yêu cầu tư vấn thành công!');
    } else {
      errorMessage.value = res.data?.message || 'Có lỗi xảy ra khi gửi yêu cầu.';
      toaster.error(errorMessage.value);
    }
  } catch (err) {
    if (err.response?.status === 429) {
      errorMessage.value = err.response.data?.message || 'Bạn đã gửi quá nhiều yêu cầu liên tiếp. Vui lòng chờ 1 phút trước khi thử lại!';
    } else if (err.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors;
      errorMessage.value = err.response.data.message || 'Thông tin chưa hợp lệ. Vui lòng kiểm tra lại biểu mẫu.';
    } else {
      errorMessage.value = err.response?.data?.message || 'Lỗi kết nối khi gửi yêu cầu. Thông tin của bạn vẫn được giữ nguyên, vui lòng thử lại.';
    }
    toaster.error(errorMessage.value);
  } finally {
    submitting.value = false;
  }
};

// Reset form for another submission
const resetForm = () => {
  submittedSuccess.value = false;
  errorMessage.value = '';
  errors.value = {};
  form.noi_dung = '';
  form.car_id = null;
  form.rental_date = '';
  selectedTopicOption.value = presetTopics[0];
  customSubject.value = '';
  form.tieu_de = presetTopics[0];
};

// Launch DriveBot Chatbot
const launchChatbot = () => {
  window.dispatchEvent(new CustomEvent('open-ai-chat'));
};
</script>

<style scoped>
.contact-page {
  min-height: 100vh;
  background-color: var(--bg-main, #f5f7fa);
  /* Bottom padding ensures the floating ChatWidget does not obscure submit actions on mobile */
  padding-bottom: 120px;
  overflow-x: hidden;
}

/* 1. HERO & BREADCRUMB SECTION */
.contact-hero-section {
  padding: 110px 0 50px;
  background: radial-gradient(circle at 50% 0%, rgba(5, 150, 105, 0.14) 0%, rgba(11, 18, 32, 0) 70%),
              linear-gradient(180deg, #070c16 0%, #0b1220 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  color: white;
}

.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.88rem;
  margin-bottom: 24px;
}

.bc-link {
  color: #94a3b8;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  transition: color 200ms ease;
}

.bc-link:hover {
  color: var(--color-primary-glow, #10b981);
}

.bc-sep {
  color: #475569;
}

.bc-current {
  color: #e2e8f0;
  font-weight: 600;
}

.hero-inner {
  max-width: 780px;
  margin: 0 auto;
}

.eyebrow-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  background: rgba(5, 150, 105, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-primary-glow, #10b981);
  letter-spacing: 0.08em;
  margin-bottom: 16px;
  text-transform: uppercase;
}

.glow-dot {
  width: 8px;
  height: 8px;
  background-color: #34d399;
  border-radius: 50%;
  box-shadow: 0 0 10px #34d399;
  animation: pulseGlow 2s infinite ease-out;
}

@keyframes pulseGlow {
  0% { transform: scale(0.8); opacity: 1; }
  100% { transform: scale(1.8); opacity: 0; }
}

.hero-title {
  font-family: var(--font-heading);
  font-size: clamp(2rem, 4vw, 2.85rem);
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 16px;
  letter-spacing: -0.02em;
  line-height: 1.25;
}

.title-dot {
  color: var(--color-primary-glow, #10b981);
}

.hero-desc {
  font-size: 1.05rem;
  color: #94a3b8;
  line-height: 1.65;
  margin: 0 auto;
}

/* 2. TWO-COLUMN WORKSPACE */
.content-section {
  margin-top: 40px;
}

.contact-workspace-grid {
  display: grid;
  grid-template-columns: 1fr 1.35fr;
  gap: 32px;
  align-items: start;
}

/* LEFT COLUMN CARDS */
.contact-info-column {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.info-card,
.faq-summary-card,
.form-container-card,
.map-card {
  background: #ffffff;
  border: 1px solid var(--color-border, #e4e7ec);
  border-radius: var(--radius-xl, 28px);
  padding: 32px;
  box-shadow: var(--shadow-soft, 0 4px 20px -2px rgba(16, 24, 40, 0.06));
}

.card-top-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.icon-badge {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(5, 150, 105, 0.1);
  color: var(--color-primary, #059669);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.card-title {
  font-family: var(--font-heading);
  font-size: 1.22rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
  margin-bottom: 4px;
}

.card-sub {
  font-size: 0.88rem;
  color: var(--color-text-muted, #667085);
  margin: 0;
  line-height: 1.5;
}

.channel-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.channel-box {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  border-radius: 16px;
  background: var(--bg-surface-subtle, #f8fafc);
  border: 1px solid var(--color-border-subtle, #f1f5f9);
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
}

.channel-box:hover:not(.static-channel) {
  background: #ffffff;
  border-color: var(--color-primary, #059669);
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(5, 150, 105, 0.1);
}

.channel-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.channel-icon-wrap.phone {
  background: #eff6ff;
  color: #2563eb;
}

.channel-icon-wrap.email {
  background: #ecfdf5;
  color: #059669;
}

.channel-icon-wrap.location {
  background: #faf5ff;
  color: #9333ea;
}

.channel-icon-wrap.hours {
  background: #fffbeb;
  color: #d97706;
}

.channel-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.channel-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted, #667085);
}

.channel-value {
  font-family: var(--font-heading);
  font-size: 1.02rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
}

.channel-value.text-normal {
  font-family: var(--font-body);
  font-size: 0.92rem;
  font-weight: 600;
  line-height: 1.45;
}

.channel-hint {
  font-size: 0.78rem;
  color: var(--color-text-subtle, #98a2b3);
}

.channel-ext-icon {
  font-size: 0.82rem;
  color: var(--color-text-subtle, #98a2b3);
  margin-top: 4px;
}

/* Social Buttons Row */
.social-channels-block {
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px dashed var(--color-border, #e4e7ec);
}

.social-block-title {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-text-muted, #667085);
  margin-bottom: 10px;
}

.social-buttons-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.social-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
}

.social-btn.facebook {
  background: #1877f2;
  color: white;
}

.social-btn.facebook:hover {
  background: #0d65d9;
}

.social-btn.zalo {
  background: #0068ff;
  color: white;
}

.social-btn.zalo:hover {
  background: #0054cc;
}

/* AI Assistant Card */
.ai-assistant-card {
  padding: 28px;
  background: linear-gradient(135deg, #0b1220 0%, #162036 100%);
  color: white;
  border-radius: var(--radius-xl, 28px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.ai-card-glow {
  position: absolute;
  top: -40px;
  right: -40px;
  width: 150px;
  height: 150px;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.3) 0%, transparent 70%);
  pointer-events: none;
}

.ai-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  border-radius: 9999px;
  background: rgba(16, 185, 129, 0.18);
  color: #34d399;
  font-size: 0.75rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  animation: pulseGlow 1.5s infinite;
}

.ai-card-title {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  margin-bottom: 8px;
  color: #ffffff;
}

.ai-card-desc {
  font-size: 0.88rem;
  color: #94a3b8;
  line-height: 1.55;
  margin-bottom: 20px;
}

.btn-ai-launch {
  width: 100%;
  padding: 12px 18px;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 0.92rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
}

.btn-ai-launch:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
}

/* FAQ Summary Card */
.faq-summary-title {
  font-family: var(--font-heading);
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
}

.faq-accordion-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.faq-item {
  border: 1px solid var(--color-border-subtle, #f1f5f9);
  border-radius: 12px;
  padding: 12px 16px;
  background: var(--bg-surface-subtle, #f8fafc);
  transition: all 0.2s ease;
}

.faq-item[open] {
  background: #ffffff;
  border-color: var(--color-border, #e4e7ec);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.faq-question {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-text-main, #101828);
  list-style: none;
  user-select: none;
}

.faq-question::-webkit-details-marker {
  display: none;
}

.faq-arrow {
  font-size: 0.78rem;
  color: var(--color-text-muted, #667085);
  transition: transform 0.2s ease;
}

.faq-item[open] .faq-arrow {
  transform: rotate(180deg);
  color: var(--color-primary, #059669);
}

.faq-answer {
  margin: 10px 0 2px;
  font-size: 0.84rem;
  line-height: 1.55;
  color: var(--color-text-muted, #667085);
}

/* RIGHT COLUMN: CONSULTATION FORM */
.form-card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 26px;
}

.icon-badge.emerald {
  background: rgba(5, 150, 105, 0.12);
  color: #059669;
}

.form-card-title {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
  margin-bottom: 4px;
}

.form-card-subtitle {
  font-size: 0.88rem;
  color: var(--color-text-muted, #667085);
  margin: 0;
  line-height: 1.5;
}

/* Form Layout */
.consultation-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-grid-row.two-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.form-field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-label {
  font-family: var(--font-heading);
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-main, #101828);
  display: flex;
  align-items: center;
  gap: 4px;
}

.req-star {
  color: #ef4444;
}

.opt-label {
  font-family: var(--font-body);
  font-size: 0.76rem;
  font-weight: 400;
  color: var(--color-text-muted, #667085);
}

.label-with-counter {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.char-counter {
  font-size: 0.75rem;
  color: var(--color-text-subtle, #98a2b3);
}

.char-counter.limit-warn {
  color: #d97706;
  font-weight: 700;
}

/* Input wrappers */
.input-control-wrap,
.select-control-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  font-size: 0.95rem;
  pointer-events: none;
}

.select-caret {
  position: absolute;
  right: 14px;
  color: #94a3b8;
  font-size: 0.85rem;
  pointer-events: none;
}

.input-control-wrap input,
.select-control-wrap select,
textarea {
  width: 100%;
  padding: 12px 14px 12px 40px;
  border: 1px solid var(--color-border, #e4e7ec);
  border-radius: 12px;
  font-family: var(--font-body);
  font-size: 0.92rem;
  color: var(--color-text-main, #101828);
  background: #ffffff;
  transition: all 0.2s ease;
}

.select-control-wrap select {
  appearance: none;
  cursor: pointer;
  padding-right: 36px;
}

textarea {
  padding: 12px 14px;
  min-height: 110px;
  resize: vertical;
  line-height: 1.5;
}

.input-control-wrap input:focus,
.select-control-wrap select:focus,
textarea:focus {
  outline: none;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

/* Has error state */
.form-field-group.has-error input,
.form-field-group.has-error select,
.form-field-group.has-error textarea {
  border-color: #ef4444;
  background-color: #fef2f2;
}

.field-error-msg {
  font-size: 0.78rem;
  color: #ef4444;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

/* Honeypot field (hidden from accessibility & screen) */
.honeypot-field {
  position: absolute;
  left: -9999px;
  opacity: 0;
  pointer-events: none;
}

/* Policy agreement */
.privacy-agreement-row {
  margin-top: 4px;
}

.custom-checkbox-label {
  display: inline-flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.custom-checkbox-label input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.checkbox-box {
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--color-border, #e4e7ec);
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  margin-top: 2px;
  flex-shrink: 0;
  transition: all 0.15s ease;
}

.check-mark {
  font-size: 0.65rem;
  color: white;
  display: none;
}

.custom-checkbox-label input:checked + .checkbox-box {
  background: var(--color-primary, #059669);
  border-color: var(--color-primary, #059669);
}

.custom-checkbox-label input:checked + .checkbox-box .check-mark {
  display: block;
}

.checkbox-text {
  font-size: 0.82rem;
  color: var(--color-text-muted, #667085);
  line-height: 1.45;
}

.policy-link {
  color: var(--color-primary, #059669);
  text-decoration: underline;
  font-weight: 600;
}

/* Form action bar */
.form-actions-bar {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;
}

.submit-action-btn {
  padding: 14px 28px;
  font-size: 1rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-radius: 12px;
  cursor: pointer;
}

.submit-action-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.reassurance-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.8rem;
  color: var(--color-text-muted, #667085);
}

/* Server Error Banner */
.server-error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  color: #dc2626;
  font-size: 0.88rem;
  font-weight: 600;
}

/* Success Banner */
.submission-success-banner {
  text-align: center;
  padding: 36px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.success-icon-bubble {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: #ecfdf5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);
}

.success-title {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
}

.success-desc {
  font-size: 0.94rem;
  color: var(--color-text-muted, #667085);
  max-width: 500px;
  line-height: 1.6;
}

.ticket-badge-box {
  width: 100%;
  max-width: 460px;
  background: var(--bg-surface-subtle, #f8fafc);
  border: 1px solid var(--color-border, #e4e7ec);
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  text-align: left;
}

.ticket-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.86rem;
}

.t-label {
  color: var(--color-text-muted, #667085);
  display: flex;
  align-items: center;
  gap: 6px;
}

.t-val {
  color: var(--color-text-main, #101828);
  font-weight: 700;
}

.t-val.highlight-emerald {
  color: var(--color-primary, #059669);
}

.success-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  justify-content: center;
  margin-top: 12px;
}

.reset-form-btn,
.view-fleet-btn {
  padding: 11px 20px;
  font-size: 0.92rem;
  font-weight: 700;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* 3. MAP SECTION */
.map-section {
  margin-top: 50px;
}

.map-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
}

.map-header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.map-pin-icon {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: rgba(5, 150, 105, 0.1);
  color: var(--color-primary, #059669);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.map-title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
  margin-bottom: 2px;
}

.map-address {
  font-size: 0.9rem;
  color: var(--color-text-muted, #667085);
  margin: 0;
}

.map-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.gps-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 9999px;
  background: var(--bg-surface-subtle, #f8fafc);
  border: 1px solid var(--color-border, #e4e7ec);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-muted, #667085);
}

.btn-map-external {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 10px;
  background: var(--bg-surface-subtle, #f8fafc);
  border: 1px solid var(--color-border, #e4e7ec);
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-main, #101828);
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-map-external:hover {
  background: #ffffff;
  border-color: var(--color-primary, #059669);
  color: var(--color-primary, #059669);
}

.map-view-container {
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--color-border, #e4e7ec);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.map-fallback-view {
  background: #f8fafc;
  padding: 60px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fallback-content {
  max-width: 480px;
}

.fallback-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #ecfdf5;
  color: var(--color-primary, #059669);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 14px;
}

.fallback-title {
  font-family: var(--font-heading);
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--color-text-main, #101828);
  margin-bottom: 6px;
}

.fallback-desc {
  font-size: 0.88rem;
  color: var(--color-text-muted, #667085);
  line-height: 1.55;
  margin-bottom: 20px;
}

.btn-open-external {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 0.9rem;
  font-weight: 700;
  border-radius: 10px;
}

/* RESPONSIVE BREAKPOINTS (390px, 768px, 992px, 1440px) */
@media (max-width: 992px) {
  .contact-workspace-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .form-grid-row.two-cols {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  /* On mobile / tablet, prioritize the form right below hero if user needs immediate inquiry */
  .contact-form-column {
    order: 1;
  }

  .contact-info-column {
    order: 2;
  }

  .map-card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .map-header-actions {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 576px) {
  .contact-hero-section {
    padding: 90px 0 40px;
  }

  .info-card,
  .faq-summary-card,
  .form-container-card,
  .map-card {
    padding: 22px 18px;
    border-radius: 20px;
  }

  .channel-box {
    padding: 12px;
  }

  .channel-icon-wrap {
    width: 36px;
    height: 36px;
    font-size: 0.95rem;
  }

  .channel-value {
    font-size: 0.92rem;
  }

  .submit-action-btn {
    width: 100%;
  }

  .success-actions {
    flex-direction: column;
    width: 100%;
  }

  .reset-form-btn,
  .view-fleet-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
