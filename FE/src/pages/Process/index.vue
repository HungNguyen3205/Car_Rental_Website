<template>
  <div class="process-page" data-page="process">
    <!-- 1. COMPACT PAGE INTRO -->
    <section class="process-intro-section" aria-label="Giới thiệu quy trình thuê xe">
      <div class="app-container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <router-link to="/" class="bc-link">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
          </router-link>
          <span class="bc-sep" aria-hidden="true">/</span>
          <span class="bc-current" aria-current="page">Quy trình</span>
        </nav>

        <div class="intro-grid">
          <div class="intro-content">
            <div class="eyebrow-pill">
              <span class="glow-dot" aria-hidden="true"></span>
              <span>{{ pageBadge }}</span>
            </div>
            <h1 class="intro-title">
              {{ pageTitle }}<span class="title-dot">.</span>
            </h1>
            <p class="intro-description">
              {{ pageHeroDesc }}
            </p>
          </div>

          <!-- Quick Navigation Bar -->
          <div class="quick-nav-card">
            <div class="quick-nav-header">
              <i class="fa-solid fa-compass"></i>
              <span>Nội dung chính</span>
            </div>
            <div class="quick-nav-links">
              <a href="#quy-trinh-5-buoc" class="quick-nav-btn">
                <span class="nav-num">01</span>
                <span class="nav-text">5 Giai đoạn thực tế</span>
                <i class="fa-solid fa-arrow-down-long"></i>
              </a>
              <a href="#checklist-giao-nhan" class="quick-nav-btn">
                <span class="nav-num">02</span>
                <span class="nav-text">Checklist nhận & trả xe</span>
                <i class="fa-solid fa-arrow-down-long"></i>
              </a>
              <a href="#chinh-sach-doi-huy" class="quick-nav-btn">
                <span class="nav-num">03</span>
                <span class="nav-text">Quy định đổi lịch & hủy đơn</span>
                <i class="fa-solid fa-arrow-down-long"></i>
              </a>
              <a href="#faq-thue-xe" class="quick-nav-btn">
                <span class="nav-num">04</span>
                <span class="nav-text">Câu hỏi thường gặp</span>
                <i class="fa-solid fa-arrow-down-long"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Trust Badges Strip -->
        <div class="intro-meta-strip">
          <div class="meta-item">
            <div class="meta-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="meta-info">
              <strong>Hợp đồng & biên bản điện tử</strong>
              <span>Ký xác nhận minh bạch 2 chiều</span>
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-icon"><i class="fa-solid fa-bolt"></i></div>
            <div class="meta-info">
              <strong>Pin & xe sẵn sàng</strong>
              <span>Sạc tối thiểu 80% - 100% khi nhận</span>
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-icon"><i class="fa-solid fa-qrcode"></i></div>
            <div class="meta-info">
              <strong>Thanh toán VietQR chuẩn</strong>
              <span>Tự động khớp mã đơn & đối soát</span>
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="meta-info">
              <strong>Hoàn cọc trong 24h</strong>
              <span>Sau khi hai bên hoàn tất biên bản</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. THE 5-STEP CORE PROCESS TIMELINE -->
    <section id="quy-trinh-5-buoc" class="process-main-section app-container" aria-label="5 Giai đoạn thuê xe thực tế">
      <div class="section-head-center">
        <span class="eyebrow-text">TIẾN TRÌNH VẬN HÀNH THỰC TẾ</span>
        <h2 class="section-title">5 Giai Đoạn Thuê Xe Chi Tiết</h2>
        <p class="section-desc">
          Mỗi bước được thiết kế để khách hàng nắm rõ việc cần làm, giấy tờ cần chuẩn bị và trạng thái đơn hàng tương ứng trên hệ thống.
        </p>
      </div>

      <!-- Step Navigation Selector Tabs (Desktop / Tablet) -->
      <div class="stepper-tab-selector" role="tablist" aria-label="Chọn bước quy trình">
        <button
          v-for="(st, idx) in processSteps"
          :key="st.id"
          type="button"
          class="step-tab-btn"
          :class="{ 'is-active': activeStepIndex === idx }"
          role="tab"
          :aria-selected="activeStepIndex === idx"
          :aria-controls="`step-panel-${st.id}`"
          @click="selectStep(idx)"
        >
          <span class="tab-num">{{ st.number }}</span>
          <span class="tab-title">{{ st.shortTitle }}</span>
          <span class="tab-indicator" aria-hidden="true"></span>
        </button>
      </div>

      <!-- Steps Timeline Container -->
      <div class="timeline-wrapper">
        <article
          v-for="(step, index) in processSteps"
          :key="step.id"
          :id="`step-panel-${step.id}`"
          class="step-detail-card"
          :class="{
            'is-focused': activeStepIndex === index,
            'is-expanded': expandedSteps.includes(step.id)
          }"
        >
          <!-- Card Header & Badge -->
          <div class="card-top-row">
            <div class="step-badge-group">
              <span class="step-num-pill">BƯỚC {{ step.number }}</span>
              <span class="status-chip" :class="step.statusClass">
                <i class="fa-solid fa-circle-dot"></i>
                Trạng thái: {{ step.systemStatus }}
              </span>
            </div>
            <button
              type="button"
              class="btn-toggle-expand"
              :aria-expanded="expandedSteps.includes(step.id)"
              :aria-label="expandedSteps.includes(step.id) ? 'Thu gọn nội dung bước' : 'Mở rộng chi tiết bước'"
              @click="toggleStepExpand(step.id)"
            >
              <span>{{ expandedSteps.includes(step.id) ? 'Thu gọn' : 'Xem chi tiết' }}</span>
              <i class="fa-solid fa-chevron-down" :class="{ 'rotate-180': expandedSteps.includes(step.id) }"></i>
            </button>
          </div>

          <!-- Main Title & Summary -->
          <div class="step-heading-block">
            <div class="step-icon-box">
              <i :class="step.icon"></i>
            </div>
            <div class="step-title-text">
              <h3 class="step-name">{{ step.title }}</h3>
              <p class="step-summary">{{ step.summary }}</p>
            </div>
          </div>

          <!-- 4 Pillars Grid (Khách làm gì - Chuẩn bị gì - Kết quả - Hướng dẫn) -->
          <div class="step-pillars-grid">
            <!-- Pillar 1: Khách cần làm gì -->
            <div class="pillar-card">
              <div class="pillar-label">
                <i class="fa-solid fa-user-check text-emerald"></i>
                <strong>Khách cần làm gì</strong>
              </div>
              <ul class="pillar-list">
                <li v-for="(act, aIdx) in step.actions" :key="aIdx">
                  <i class="fa-solid fa-check"></i>
                  <span>{{ act }}</span>
                </li>
              </ul>
            </div>

            <!-- Pillar 2: Cần chuẩn bị gì -->
            <div class="pillar-card">
              <div class="pillar-label">
                <i class="fa-solid fa-id-card-clip text-cyan"></i>
                <strong>Cần chuẩn bị gì</strong>
              </div>
              <ul class="pillar-list">
                <li v-for="(prep, pIdx) in step.preparations" :key="pIdx">
                  <i class="fa-solid fa-arrow-right"></i>
                  <span>{{ prep }}</span>
                </li>
              </ul>
            </div>

            <!-- Pillar 3: Trạng thái & Kết quả tiếp theo -->
            <div class="pillar-card highlight-pillar">
              <div class="pillar-label">
                <i class="fa-solid fa-code-branch text-glow"></i>
                <strong>Kết quả / Tiến trình</strong>
              </div>
              <div class="pillar-result-content">
                <div class="result-badge-wrap">
                  <span class="result-badge">{{ step.resultHeadline }}</span>
                </div>
                <p class="result-desc">{{ step.resultDetails }}</p>
              </div>
            </div>
          </div>

          <!-- Extra Expandable Notes & Practical Tips -->
          <div v-show="expandedSteps.includes(step.id)" class="step-expanded-notes">
            <div class="note-box">
              <div class="note-icon"><i class="fa-solid fa-lightbulb"></i></div>
              <div class="note-body">
                <strong>Lưu ý nghiệp vụ thực tế:</strong>
                <p>{{ step.practicalTip }}</p>
              </div>
            </div>
          </div>

          <!-- Step Action Footer -->
          <div class="step-card-footer">
            <div class="footer-hint">
              <i class="fa-solid fa-circle-info"></i>
              <span>{{ step.footerHint }}</span>
            </div>
            <div class="footer-cta-wrap">
              <router-link
                v-if="step.primaryCta"
                :to="step.primaryCta.url"
                class="btn-primary btn-sm"
              >
                <span>{{ step.primaryCta.label }}</span>
                <i class="fa-solid fa-arrow-right"></i>
              </router-link>
              <a
                v-else-if="step.phoneCta"
                :href="`tel:${cleanPhone(settings.hotline)}`"
                class="btn-outline btn-sm"
              >
                <i class="fa-solid fa-phone"></i>
                <span>Hotline: {{ settings.hotline || '1900 1234' }}</span>
              </a>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- 3. INTERACTIVE TWO-WAY CHECKLIST -->
    <section id="checklist-giao-nhan" class="checklist-section app-container" aria-label="Checklist nhận và trả xe">
      <div class="checklist-card-wrap">
        <div class="checklist-header-row">
          <div class="header-left">
            <span class="eyebrow-text">AN TÂM 100% KHI LĂN BÁNH</span>
            <h2 class="section-title">Checklist Giao Nhận Hai Chiều</h2>
            <p class="section-desc">
              Thực hiện đối chiếu các mục dưới đây cùng nhân viên bàn giao để đảm bảo quyền lợi tối đa cho cả hai bên.
            </p>
          </div>
          <div class="checklist-tabs">
            <button
              type="button"
              class="chk-tab-btn"
              :class="{ 'is-active': activeChecklistTab === 'pickup' }"
              @click="activeChecklistTab = 'pickup'"
            >
              <i class="fa-solid fa-key"></i>
              <span>Trước khi nhận xe</span>
              <span class="chk-counter">{{ checkedPickupCount }}/{{ pickupChecklist.length }}</span>
            </button>
            <button
              type="button"
              class="chk-tab-btn"
              :class="{ 'is-active': activeChecklistTab === 'return' }"
              @click="activeChecklistTab = 'return'"
            >
              <i class="fa-solid fa-arrow-right-arrow-left"></i>
              <span>Trước khi trả xe</span>
              <span class="chk-counter">{{ checkedReturnCount }}/{{ returnChecklist.length }}</span>
            </button>
          </div>
        </div>

        <!-- Checklist Tab 1: Nhận xe -->
        <div v-show="activeChecklistTab === 'pickup'" class="checklist-items-grid">
          <label
            v-for="item in pickupChecklist"
            :key="item.id"
            class="checklist-item-card"
            :class="{ 'is-checked': checkedItems[item.id] }"
          >
            <input
              type="checkbox"
              v-model="checkedItems[item.id]"
              class="sr-only"
            />
            <div class="check-box-custom">
              <i class="fa-solid fa-check"></i>
            </div>
            <div class="check-text">
              <strong>{{ item.title }}</strong>
              <p>{{ item.desc }}</p>
            </div>
            <span class="check-tag">{{ item.tag }}</span>
          </label>
        </div>

        <!-- Checklist Tab 2: Trả xe -->
        <div v-show="activeChecklistTab === 'return'" class="checklist-items-grid">
          <label
            v-for="item in returnChecklist"
            :key="item.id"
            class="checklist-item-card"
            :class="{ 'is-checked': checkedItems[item.id] }"
          >
            <input
              type="checkbox"
              v-model="checkedItems[item.id]"
              class="sr-only"
            />
            <div class="check-box-custom">
              <i class="fa-solid fa-check"></i>
            </div>
            <div class="check-text">
              <strong>{{ item.title }}</strong>
              <p>{{ item.desc }}</p>
            </div>
            <span class="check-tag return-tag">{{ item.tag }}</span>
          </label>
        </div>

        <!-- Checklist Footer Summary -->
        <div class="checklist-footer">
          <div class="chk-note">
            <i class="fa-solid fa-shield-halved text-emerald"></i>
            <span>
              Mọi ghi chú về tình trạng vết xước, số ODO và lượng pin đều được số hóa trực tiếp trên 
              <strong>Biên bản bàn giao điện tử</strong> gửi về email của quý khách.
            </span>
          </div>
          <button
            type="button"
            class="btn-reset-checklist"
            @click="resetChecklist"
          >
            <i class="fa-solid fa-rotate-left"></i>
            <span>Làm mới danh sách</span>
          </button>
        </div>
      </div>
    </section>

    <!-- 4. CHANGE & CANCELLATION RULES -->
    <section id="chinh-sach-doi-huy" class="policy-summary-section app-container" aria-label="Quy định đổi lịch và hủy đơn">
      <div class="section-head-center">
        <span class="eyebrow-text">CHÍNH SÁCH MINH BẠCH</span>
        <h2 class="section-title">Quy Định Đổi Lịch & Hủy Đơn</h2>
        <p class="section-desc">
          DriveRent xây dựng cơ chế quản lý lịch xe rõ ràng, hỗ trợ tối đa cho khách hàng khi có thay đổi kế hoạch di chuyển.
        </p>
      </div>

      <div class="rules-three-grid">
        <!-- Rule 1: Đổi lịch thuê -->
        <div class="rule-card">
          <div class="rule-icon-box">
            <i class="fa-solid fa-calendar-days"></i>
          </div>
          <h3 class="rule-title">Đổi Lịch Di Chuyển</h3>
          <p class="rule-desc">
            Áp dụng khi quý khách cần dời ngày nhận/trả xe. Hệ thống sẽ kiểm tra tính khả dụng của xe trong khung giờ mới bằng thuật toán khóa bi quan chống trùng lịch.
          </p>
          <ul class="rule-bullets">
            <li>Thông báo trước ít nhất <strong>{{ settings.rental_buffer_hours || 2 }} giờ</strong> so với giờ nhận xe ban đầu.</li>
            <li>Nếu đổi sang khung giờ xe còn trống, hệ thống cập nhật lại tổng tiền theo biểu phí snapshot.</li>
            <li>Trường hợp xe đã có khách khác giữ chỗ, nhân viên sẽ tư vấn đổi sang xe tương đương.</li>
          </ul>
        </div>

        <!-- Rule 2: Hủy đơn khi Chờ duyệt -->
        <div class="rule-card highlight-border">
          <div class="rule-icon-box primary-glow">
            <i class="fa-solid fa-ban"></i>
          </div>
          <h3 class="rule-title">Tự Hủy Khi "Chờ Duyệt"</h3>
          <p class="rule-desc">
            Khi đơn hàng đang ở trạng thái <strong>Chờ duyệt (Pending)</strong> và chưa thanh toán, quý khách được toàn quyền tự hủy trực tiếp mà không mất bất kỳ chi phí nào.
          </p>
          <ul class="rule-bullets">
            <li>Thực hiện ngay trên trang <strong>Chuyến đi của tôi</strong> chỉ với 1 click.</li>
            <li>Hệ thống ngay lập tức giải phóng lịch xe cho các khách hàng khác thuê.</li>
            <li>Đơn không chuyển khoản sẽ tự động hết hạn và hủy sau <strong>{{ settings.booking_auto_cancel_unpaid_hours || 24 }} giờ</strong>.</li>
          </ul>
        </div>

        <!-- Rule 3: Hủy đơn sau khi Đã duyệt & Hoàn tiền -->
        <div class="rule-card">
          <div class="rule-icon-box">
            <i class="fa-solid fa-hand-holding-dollar"></i>
          </div>
          <h3 class="rule-title">Hủy Đơn Đã Xác Nhận</h3>
          <p class="rule-desc">
            Khi đơn hàng đã được đối soát thanh toán và chuyển trạng thái <strong>Đã duyệt (Confirmed)</strong>, việc hủy đơn cần thông qua bộ phận Chăm sóc khách hàng.
          </p>
          <ul class="rule-bullets">
            <li>Liên hệ hotline <strong>{{ settings.hotline || '1900 1234' }}</strong> để được chuyên viên xử lý hồ sơ.</li>
            <li>Hoàn trả tiền cọc hoặc thanh toán theo quy định bảo lưu lịch trình.</li>
            <li>Tiền hoàn được chuyển trả về đúng tài khoản ngân hàng của người đặt xe trong vòng 24h làm việc.</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- 5. FAQ ACCORDION SECTION -->
    <section id="faq-thue-xe" class="faq-accordion-section app-container" aria-label="Câu hỏi thường gặp về quy trình thuê xe">
      <div class="section-head-center">
        <span class="eyebrow-text">{{ faqContent.eyebrow || 'GIẢI ĐÁP NHANH' }}</span>
        <h2 class="section-title">{{ faqContent.title || 'Câu Hỏi Thường Gặp Về Quy Trình' }}</h2>
        <p class="section-desc">
          {{ faqContent.description || 'Những thắc mắc phổ biến nhất của khách hàng về hồ sơ, quy trình thanh toán và thủ tục nhận trả xe.' }}
        </p>
      </div>

      <div class="faq-list-grid">
        <div
          v-for="(faq, fIndex) in displayFaqs"
          :key="fIndex"
          class="faq-card"
          :class="{ 'is-open': openFaqIndex === fIndex }"
        >
          <button
            type="button"
            class="faq-trigger"
            :aria-expanded="openFaqIndex === fIndex"
            @click="toggleFaq(fIndex)"
          >
            <span class="faq-q-text">{{ faq.question }}</span>
            <div class="faq-icon-pill">
              <i class="fa-solid fa-chevron-down faq-chevron"></i>
            </div>
          </button>
          <div v-show="openFaqIndex === fIndex" class="faq-body">
            <p>{{ faq.answer }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 6. SUPPORT & CTA BANNER -->
    <section class="process-cta-section app-container" aria-label="Bắt đầu hành trình">
      <div class="cta-banner-card">
        <div class="cta-glow-decor" aria-hidden="true"></div>
        <div class="cta-inner-grid">
          <div class="cta-text-side">
            <div class="cta-badge">
              <i class="fa-solid fa-car-side"></i>
              <span>HÀNH TRÌNH TIỆN NGHI ĐANG CHỜ BẠN</span>
            </div>
            <h2 class="cta-heading">
              {{ processContent.cta_title || 'Sẵn Sàng Khởi Hành Cùng DriveRent?' }}
            </h2>
            <p class="cta-sub">
              {{ processContent.cta_desc || 'Lựa chọn mẫu xe ưng ý, kiểm tra biểu phí snapshot minh bạch và bắt đầu chuyến đi an tâm cùng dịch vụ số hóa chuẩn mực.' }}
            </p>
            <div class="cta-buttons">
              <router-link to="/fleet" class="btn-primary btn-lg">
                <span>Khám phá đội xe ngay</span>
                <i class="fa-solid fa-arrow-right"></i>
              </router-link>
              <router-link to="/contact" class="btn-outline btn-lg">
                <i class="fa-solid fa-headset"></i>
                <span>Tư vấn trực tiếp 24/7</span>
              </router-link>
            </div>
          </div>

          <div class="cta-meta-side">
            <div class="direct-contact-box">
              <span class="box-label">HỖ TRỢ KHẨN CẤP TOÀN QUỐC</span>
              <a :href="`tel:${cleanPhone(settings.hotline)}`" class="hotline-link">
                <i class="fa-solid fa-phone-volume"></i>
                <span>{{ settings.hotline || '1900 1234' }}</span>
              </a>
              <p class="support-time">Phục vụ 24/7 kể cả ngày lễ và Tết</p>
              <div class="location-chips">
                <span v-for="loc in serviceLocations" :key="loc" class="loc-chip">
                  <i class="fa-solid fa-location-dot"></i> {{ loc }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useWebsiteContent } from '@/core/contentStore';
import { useSystemSettings } from '@/core/settingsStore';

// Cài đặt và CMS phản ứng
const processContent = useWebsiteContent('process_page');
const faqContent = useWebsiteContent('home_faq');
const { settings } = useSystemSettings();

// Tiêu đề và mô tả chuẩn hóa 5 giai đoạn nghiệp vụ thật
const pageTitle = computed(() => {
  if (processContent.title && !processContent.title.includes('4 Bước')) {
    return processContent.title;
  }
  return 'Quy Trình Thuê Xe Thông Minh';
});

const pageBadge = computed(() => {
  if (processContent.badge && processContent.badge !== 'Quy trình đơn giản') {
    return processContent.badge;
  }
  return 'MINH BẠCH • TINH GỌN • SỐ HÓA TOÀN TRÌNH';
});

const pageHeroDesc = computed(() => {
  return processContent.hero_desc || 'Trải nghiệm thuê xe nhanh chóng, minh bạch từng bước từ lúc chọn xe, giữ lịch, đối soát thanh toán đến bàn giao và hoàn cọc an ninh sau hành trình.';
});

// Quản lý active step và mở rộng chi tiết
const activeStepIndex = ref(0);
const expandedSteps = ref([1]); // Mặc định mở bước 1

const selectStep = (index) => {
  activeStepIndex.value = index;
  const targetId = processSteps.value[index]?.id;
  if (targetId && !expandedSteps.value.includes(targetId)) {
    expandedSteps.value.push(targetId);
  }
};

const toggleStepExpand = (stepId) => {
  const idx = expandedSteps.value.indexOf(stepId);
  if (idx > -1) {
    expandedSteps.value.splice(idx, 1);
  } else {
    expandedSteps.value.push(stepId);
  }
};

// Làm sạch số điện thoại cho thẻ tel:
const cleanPhone = (phone) => {
  if (!phone) return '19001234';
  return String(phone).replace(/\s+/g, '');
};

// Danh sách khu vực dịch vụ từ cấu hình
const serviceLocations = computed(() => {
  if (Array.isArray(settings.service_locations) && settings.service_locations.length) {
    return settings.service_locations;
  }
  return ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Nha Trang'];
});

// 5 Giai đoạn thực tế của hệ thống
const processSteps = computed(() => {
  const docReq = settings.rental_policy?.document_requirements || 'CCCD gắn chip (hoặc Hộ chiếu còn hạn) + GPLX B1/B2 trở lên còn hiệu lực';
  const depPol = settings.rental_policy?.deposit_policy || 'Đặt cọc bảo đảm khi nhận xe. Hoàn lại toàn bộ sau 24h đối soát trả xe';
  const bufferH = settings.rental_buffer_hours || 2;
  const cancelH = settings.booking_auto_cancel_unpaid_hours || 24;

  return [
    {
      id: 1,
      number: '01',
      shortTitle: 'Chọn xe & Lịch',
      title: 'Chọn Dòng Xe Phù Hợp & Thiết Lập Lịch Trình',
      summary: 'Duyệt danh mục xe thực tế, chọn showroom hoặc địa chỉ nhận xe, ngày giờ bắt đầu và kết thúc chuyến đi.',
      icon: 'fa-solid fa-car-side',
      systemStatus: 'Tìm kiếm & Báo giá',
      statusClass: 'status-blue',
      actions: [
        'Chọn dòng xe (SUV 7 chỗ, Sedan, Hatchback hoặc xe điện thông minh) theo nhu cầu.',
        'Chọn địa điểm nhận xe: Tại Showroom trung tâm hoặc giao tận nơi tại sân bay / tư gia.',
        `Chọn thời gian nhận & trả xe (tối thiểu đặt trước ${bufferH} tiếng để chuẩn bị xe chu đáo).`
      ],
      preparations: [
        'Xác định số lượng hành khách và hành lý dự kiến.',
        'Kế hoạch lịch trình di chuyển giữa các tỉnh thành hoặc nội đô.',
        'Lựa chọn gói thuê tự lái hoặc yêu cầu thêm tài xế nếu cần.'
      ],
      resultHeadline: 'Báo Giá Snapshot Tự Động',
      resultDetails: 'Hệ thống tự động tính số ngày thuê (24h/ngày), hiển thị tổng tiền đã gồm VAT và kiểm tra lịch trống nửa mở [nhận, trả) để đảm bảo không bị trùng xe.',
      practicalTip: 'Đặt xe sớm vào các dịp cuối tuần hoặc kỳ nghỉ lễ giúp bạn giữ được mẫu xe yêu thích với mức giá ưu đãi nhất.',
      footerHint: 'Biểu phí niêm yết rõ ràng, không phát sinh phụ phí ẩn.',
      primaryCta: {
        label: 'Khám phá đội xe',
        url: '/fleet'
      }
    },
    {
      id: 2,
      number: '02',
      shortTitle: 'Gửi yêu cầu & Giữ chỗ',
      title: 'Tạo Yêu Cầu Thuê Xe & Khóa Bi Quan Giữ Lịch',
      summary: 'Đăng nhập tài khoản, xác nhận thông tin người thuê và tạo đơn đặt xe với mã định danh duy nhất.',
      icon: 'fa-solid fa-file-circle-check',
      systemStatus: 'Chờ duyệt (Pending - Giữ lịch)',
      statusClass: 'status-amber',
      actions: [
        'Đăng nhập tài khoản cá nhân (hoặc đăng ký nhanh bằng số điện thoại / email).',
        'Xác nhận địa chỉ giao nhận chi tiết và ghi chú lý do thuê xe (du lịch, công tác).',
        'Bấm xác nhận đặt xe để hệ thống khởi tạo hợp đồng đặt chỗ điện tử.'
      ],
      preparations: [
        'Thông tin cá nhân chính xác: Họ tên, số điện thoại liên lạc thường xuyên, email.',
        'Ghi chú khung giờ giao nhận mong muốn nếu có yêu cầu đặc biệt.'
      ],
      resultHeadline: 'Khóa Bi Quan (Pessimistic Lock) Thành Công',
      resultDetails: 'Đơn được tạo với mã duy nhất (VD: HDTX000123), trạng thái "Chờ duyệt". Xe được giữ lịch ngay lập tức trên máy chủ để không ai có thể đặt trùng thời gian của bạn.',
      practicalTip: 'Quý khách hoàn toàn có thể tự hủy đơn trực tiếp trên trang cá nhân khi đơn đang ở trạng thái "Chờ duyệt" mà không mất phí.',
      footerHint: 'Mã đơn được cấp ngay tức thì trên hệ thống.',
      primaryCta: {
        label: 'Xem chuyến đi của tôi',
        url: '/my-bookings'
      }
    },
    {
      id: 3,
      number: '03',
      shortTitle: 'Xác nhận & Thanh toán',
      title: 'Thanh Toán VietQR & Đối Soát Đơn Hàng',
      summary: 'Quét mã VietQR tự động kèm mã đơn, tải ảnh biên lai giao dịch và nhận duyệt từ ban quản trị.',
      icon: 'fa-solid fa-qrcode',
      systemStatus: 'Đã duyệt / Xác nhận (Confirmed)',
      statusClass: 'status-emerald',
      actions: [
        'Mở chi tiết đơn hàng trên trang "Chuyến đi của tôi" để xem mã VietQR thanh toán.',
        'Quét mã bằng ứng dụng ngân hàng (nội dung chuyển khoản tự động khớp đúng mã đơn HDTX).',
        'Tải ảnh chụp biên lai (bill) giao dịch lên hệ thống để chuyên viên đối soát.'
      ],
      preparations: [
        'Ứng dụng ngân hàng hoặc ví điện tử hỗ trợ quét VietQR liên ngân hàng.',
        'Ảnh chụp màn hình biên lai chuyển khoản rõ số tham chiếu giao dịch.'
      ],
      resultHeadline: 'Đã Đối Soát & Xác Nhận Đơn Hàng',
      resultDetails: `Bộ phận kế toán đối soát giao dịch và chuyển trạng thái đơn sang "Đã duyệt / Xác nhận". Xe được chuẩn bị sẵn sàng. Đơn chưa thanh toán sẽ tự động hủy sau ${cancelH} giờ để nhả lịch.`,
      practicalTip: 'Chuyển khoản đúng cú pháp mã đơn giúp hệ thống và kế toán đối soát nhanh chóng trong vòng 5-15 phút.',
      footerHint: 'Thanh toán bảo mật qua chuẩn VietQR Napas 247.',
      primaryCta: {
        label: 'Kiểm tra trạng thái đơn',
        url: '/my-bookings'
      }
    },
    {
      id: 4,
      number: '04',
      shortTitle: 'Kiểm tra & Nhận xe',
      title: 'Đối Chiếu Giấy Tờ, Kiểm Tra Thực Tế & Nhận Xe',
      summary: 'Gặp chuyên viên bàn giao, kiểm tra tình trạng ngoại thất, lượng pin/xăng, ký biên bản điện tử và nhận chìa khóa.',
      icon: 'fa-solid fa-key',
      systemStatus: 'Đang thuê (In Progress)',
      statusClass: 'status-purple',
      actions: [
        'Có mặt tại Showroom đã chọn hoặc điểm giao hẹn trước theo đúng giờ đã đăng ký.',
        'Cùng chuyên viên kiểm tra ngoại thất (chụp ảnh/quay video vết xước nếu có), số km ODO và pin/nhiên liệu.',
        'Ký xác nhận biên bản bàn giao điện tử và nhận bàn giao chìa khóa thông minh.'
      ],
      preparations: [
        `Giấy tờ gốc bắt buộc: ${docReq}.`,
        `Khoản cọc an ninh (thế chấp bảo đảm): ${depPol}.`
      ],
      resultHeadline: 'Bàn Giao Xe & Bắt Đầu Hành Trình',
      resultDetails: 'Hệ thống cập nhật mốc thời gian bàn giao (delivered_at), đơn chuyển sang trạng thái "Đang thuê / Đang lưu hành". Quý khách an tâm tận hưởng hành trình với hỗ trợ 24/7.',
      practicalTip: 'DriveRent cam kết pin xe luôn đạt tối thiểu từ 80% đến 100% khi bàn giao, xe đã được khử khuẩn và vệ sinh sạch sẽ.',
      footerHint: 'Hỗ trợ kỹ thuật và cứu hộ toàn quốc trong suốt chuyến đi.',
      phoneCta: true
    },
    {
      id: 5,
      number: '05',
      shortTitle: 'Trả xe & Hoàn tất',
      title: 'Bàn Giao Lại Xe, Đối Soát & Hoàn Cọc An Ninh',
      summary: 'Trả xe đúng hẹn, cùng chuyên viên nghiệm thu biên bản kết thúc và nhận hoàn cọc an ninh trong vòng 24 giờ.',
      icon: 'fa-solid fa-circle-check',
      systemStatus: 'Hoàn tất đơn (Completed)',
      statusClass: 'status-green',
      actions: [
        'Đưa xe về điểm hẹn đúng giờ, kiểm tra lại toàn bộ tư trang cá nhân trên xe.',
        'Cùng chuyên viên đối chiếu mức pin/xăng, tình trạng vệ sinh khoang xe theo quy tắc.',
        'Ký biên bản trả xe và cung cấp số tài khoản ngân hàng để nhận hoàn tiền cọc an ninh.'
      ],
      preparations: [
        'Bàn giao đầy đủ chìa khóa thông minh, phụ kiện sạc và giấy tờ lưu hành xe.',
        'Thanh toán phụ phí nếu có (phí sạc/xăng chênh lệch nếu không trả theo mức ban đầu, quá giờ).'
      ],
      resultHeadline: 'Biên Bản Trả Xe & Hoàn Cọc Trong 24H',
      resultDetails: 'Đơn chuyển sang trạng thái "Đã nhận trả xe" (returned_at). Sau khi bộ phận kiểm soát đối soát không có vi phạm giao thông, tiền cọc được hoàn trả 100% trong vòng 24 giờ và đơn chuyển "Hoàn tất".',
      practicalTip: 'Quy tắc xe nghiêm cấm hút thuốc lá và chở hàng gây mùi. Giữ xe sạch sẽ giúp bạn không phải trả thêm phí rửa dọn nội thất.',
      footerHint: 'Tiền cọc an ninh hoàn trả tự động về tài khoản ngân hàng của bạn.',
      primaryCta: {
        label: 'Đặt chuyến đi tiếp theo',
        url: '/fleet'
      }
    }
  ];
});

// Checklist tương tác 2 chiều
const activeChecklistTab = ref('pickup');
const checkedItems = ref({
  p1: true,
  p2: true,
  p3: false,
  p4: false,
  p5: false,
  r1: false,
  r2: false,
  r3: false,
  r4: false,
  r5: false
});

const pickupChecklist = [
  {
    id: 'p1',
    title: 'Xuất trình CCCD & GPLX B1/B2 còn hạn',
    desc: 'Bản gốc đối chiếu thông tin người lái chính xác theo hợp đồng.',
    tag: 'Giấy tờ bắt buộc'
  },
  {
    id: 'p2',
    title: 'Đặt cọc an ninh bảo đảm phương tiện',
    desc: 'Ký quỹ cọc an ninh theo chính sách niêm yết, cam kết hoàn trả sau 24h đối soát.',
    tag: 'Tài chính minh bạch'
  },
  {
    id: 'p3',
    title: 'Kiểm tra ngoại thất & quay video quanh xe',
    desc: 'Ghi nhận mọi vết xước nhỏ có sẵn trước khi rời điểm bàn giao.',
    tag: 'Nghiệm thu thực tế'
  },
  {
    id: 'p4',
    title: 'Kiểm tra mức pin sạc/xăng & số ODO ban đầu',
    desc: 'Đảm bảo pin đạt tối thiểu 80% - 100%, đồng hồ số km được ghi vào biên bản.',
    tag: 'Tiêu chuẩn xe'
  },
  {
    id: 'p5',
    title: 'Ký biên bản bàn giao điện tử 2 bên',
    desc: 'Biên bản có chữ ký xác nhận số hóa được gửi trực tiếp về email khách hàng.',
    tag: 'Pháp lý an toàn'
  }
];

const returnChecklist = [
  {
    id: 'r1',
    title: 'Kiểm tra kỹ toàn bộ tư trang cá nhân',
    desc: 'Kiểm tra hộc để đồ, cốp trước, cốp sau và các ngăn túi ghế.',
    tag: 'Tư trang cá nhân'
  },
  {
    id: 'r2',
    title: 'Kiểm tra mức pin/nhiên liệu khi hoàn trả',
    desc: 'Đưa mức sạc/nhiên liệu tương đương lúc nhận xe để tránh phát sinh phụ phí sạc bù.',
    tag: 'Nhiên liệu / Pin'
  },
  {
    id: 'r3',
    title: 'Đảm bảo khoang nội thất sạch sẽ, không mùi',
    desc: 'Tuân thủ quy định không hút thuốc lá và không mang hải sản / chất gây mùi vào xe.',
    tag: 'Vệ sinh xe'
  },
  {
    id: 'r4',
    title: 'Bàn giao chìa khóa & phụ kiện đi kèm',
    desc: 'Hoàn trả chìa khóa thông minh, bộ sạc di động và giấy tờ xe nguyên vẹn.',
    tag: 'Bàn giao thiết bị'
  },
  {
    id: 'r5',
    title: 'Ký biên bản trả xe & nhận thông báo hoàn cọc',
    desc: 'Hai bên chốt số km thực tế và xác nhận thời gian hoàn cọc an ninh trong 24 giờ.',
    tag: 'Hoàn tất chuyến đi'
  }
];

const checkedPickupCount = computed(() => {
  return pickupChecklist.filter(item => checkedItems.value[item.id]).length;
});

const checkedReturnCount = computed(() => {
  return returnChecklist.filter(item => checkedItems.value[item.id]).length;
});

const resetChecklist = () => {
  for (const key in checkedItems.value) {
    checkedItems.value[key] = false;
  }
};

// FAQ Accordion
const openFaqIndex = ref(0);

const toggleFaq = (index) => {
  openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const displayFaqs = computed(() => {
  if (faqContent.faqs && Array.isArray(faqContent.faqs) && faqContent.faqs.length) {
    return faqContent.faqs;
  }
  return [
    {
      question: 'Tôi cần chuẩn bị những giấy tờ gì khi đến nhận xe?',
      answer: 'Quý khách chỉ cần chuẩn bị bản gốc Căn cước công dân gắn chip (hoặc Hộ chiếu còn hạn) và Giấy phép lái xe hạng B1/B2 trở lên còn hiệu lực để chuyên viên đối chiếu thông tin khi giao nhận xe.'
    },
    {
      question: 'Thời gian thuê xe được tính như thế nào?',
      answer: 'Thời gian thuê được tính theo block ngày (24 giờ kể từ thời điểm nhận xe). Để đội ngũ chuẩn bị xe tốt nhất, quý khách vui lòng đặt xe trước tối thiểu 2 giờ.'
    },
    {
      question: 'Chính sách đặt cọc an ninh và hoàn tiền được xử lý ra sao?',
      answer: 'Khoản cọc an ninh bảo đảm phương tiện được ký quỹ khi nhận xe. Sau khi quý khách trả xe và hai bên ký biên bản nghiệm thu, DriveRent sẽ hoàn trả 100% tiền cọc về tài khoản ngân hàng của quý khách trong vòng 24 giờ làm việc.'
    },
    {
      question: 'Tôi có thể đổi lịch hoặc tự hủy đơn đặt xe được không?',
      answer: 'Có. Khi đơn hàng ở trạng thái "Chờ duyệt", quý khách hoàn toàn có thể tự hủy đơn trực tiếp trên trang cá nhân chỉ với 1 click. Nếu đơn đã được duyệt hoặc cần đổi ngày, vui lòng liên hệ hotline để nhân viên hỗ trợ kiểm tra tính khả dụng của xe.'
    },
    {
      question: 'Nếu phát sinh sự cố kỹ thuật trên đường thì được hỗ trợ thế nào?',
      answer: 'DriveRent cung cấp hotline hỗ trợ kỹ thuật và cứu hộ 24/7 trên phạm vi toàn quốc. Mọi sự cố khẩn cấp sẽ được đội ngũ kỹ thuật viên điều phối xử lý hoặc cung cấp xe thay thế nhanh chóng.'
    }
  ];
});
</script>

<style scoped>
/* ==========================================================================
   PROCESS PAGE — Emerald-Navy Design System (Synced with Home & Fleet)
   ========================================================================== */

.process-page {
  background-color: var(--bg-dark, #0b1220);
  color: var(--color-text-on-dark, #f8fafc);
  min-height: 100vh;
  padding-bottom: 90px;
  overflow-x: hidden;
}

/* 1. COMPACT INTRO SECTION */
.process-intro-section {
  padding: 110px 0 40px;
  background: radial-gradient(circle at 50% 0%, rgba(5, 150, 105, 0.12) 0%, rgba(11, 18, 32, 0) 70%),
              linear-gradient(180deg, #070c16 0%, #0b1220 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
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
  color: #34d399;
}

.bc-sep {
  color: #475569;
}

.bc-current {
  color: #e2e8f0;
  font-weight: 600;
}

.intro-grid {
  display: grid;
  grid-template-columns: 1.35fr 1fr;
  gap: 36px;
  align-items: center;
  margin-bottom: 36px;
}

.eyebrow-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background: rgba(5, 150, 105, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-primary-glow, #10b981);
  letter-spacing: 0.08em;
  margin-bottom: 14px;
}

.glow-dot {
  width: 8px;
  height: 8px;
  background-color: #34d399;
  border-radius: 50%;
  box-shadow: 0 0 10px #34d399;
  animation: pulseDot 2s infinite ease-out;
}

@keyframes pulseDot {
  0% { transform: scale(0.8); opacity: 1; }
  100% { transform: scale(1.8); opacity: 0; }
}

.intro-title {
  font-family: var(--font-heading);
  font-size: clamp(2rem, 3.8vw, 3rem);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.15;
  letter-spacing: -0.02em;
  margin-bottom: 14px;
}

.title-dot {
  color: var(--color-primary-glow, #10b981);
}

.intro-description {
  font-size: 1.02rem;
  color: #94a3b8;
  line-height: 1.65;
  max-width: 620px;
}

/* Quick Nav Card */
.quick-nav-card {
  background: rgba(18, 25, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 22px 24px;
  backdrop-filter: blur(14px);
}

.quick-nav-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #34d399;
  margin-bottom: 14px;
}

.quick-nav-links {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.quick-nav-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 10px 12px;
  text-decoration: none;
  color: #e2e8f0;
  font-size: 0.88rem;
  font-weight: 600;
  transition: all 200ms ease;
}

.quick-nav-btn:hover {
  background: rgba(5, 150, 105, 0.15);
  border-color: rgba(16, 185, 129, 0.35);
  color: #ffffff;
  transform: translateY(-2px);
}

.nav-num {
  font-family: var(--font-heading);
  font-size: 0.8rem;
  font-weight: 800;
  color: #34d399;
}

.nav-text {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quick-nav-btn i {
  font-size: 0.75rem;
  color: #64748b;
  transition: transform 200ms ease, color 200ms ease;
}

.quick-nav-btn:hover i {
  color: #34d399;
  transform: translateY(2px);
}

/* Intro Meta Strip */
.intro-meta-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  padding-top: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: rgba(18, 25, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
}

.meta-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(5, 150, 105, 0.12);
  color: #34d399;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  flex-shrink: 0;
}

.meta-info {
  display: flex;
  flex-direction: column;
}

.meta-info strong {
  font-size: 0.88rem;
  color: #f1f5f9;
  font-weight: 700;
}

.meta-info span {
  font-size: 0.78rem;
  color: #94a3b8;
}

/* ==========================================================================
   2. CORE 5-STEP TIMELINE SECTION
   ========================================================================== */
.process-main-section {
  padding: 80px 0 60px;
  scroll-margin-top: 90px;
}

.section-head-center {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 40px;
}

.eyebrow-text {
  display: inline-block;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--color-primary-glow, #10b981);
  text-transform: uppercase;
  margin-bottom: 8px;
}

.section-title {
  font-family: var(--font-heading);
  font-size: clamp(1.8rem, 3.2vw, 2.5rem);
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
  line-height: 1.2;
  margin-bottom: 12px;
}

.section-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

/* Stepper Tabs Bar */
.stepper-tab-selector {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  margin-bottom: 40px;
  background: rgba(18, 25, 42, 0.6);
  padding: 8px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.step-tab-btn {
  background: transparent;
  border: none;
  border-radius: 10px;
  padding: 12px 14px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  position: relative;
  transition: all 200ms ease;
  outline: none;
}

.step-tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
}

.step-tab-btn.is-active {
  background: rgba(5, 150, 105, 0.18);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.tab-num {
  font-family: var(--font-heading);
  font-size: 0.82rem;
  font-weight: 800;
  color: #64748b;
  transition: color 200ms ease;
}

.step-tab-btn.is-active .tab-num {
  color: #34d399;
}

.tab-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #cbd5e1;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.step-tab-btn.is-active .tab-title {
  color: #ffffff;
}

/* Timeline Cards */
.timeline-wrapper {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.step-detail-card {
  background: rgba(18, 25, 42, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  padding: 32px;
  backdrop-filter: blur(14px);
  transition: border-color 250ms ease, box-shadow 250ms ease, transform 250ms ease;
  scroll-margin-top: 100px;
}

.step-detail-card:hover {
  border-color: rgba(255, 255, 255, 0.15);
  box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.5);
}

.step-detail-card.is-focused {
  border-color: rgba(16, 185, 129, 0.45);
  box-shadow: 0 16px 40px -12px rgba(5, 150, 105, 0.25);
}

.card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
}

.step-badge-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.step-num-pill {
  font-family: var(--font-heading);
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  color: #34d399;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 9999px;
}

.status-blue {
  background: rgba(2, 132, 199, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.status-amber {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-emerald {
  background: rgba(5, 150, 105, 0.18);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.3);
}

.status-purple {
  background: rgba(139, 92, 246, 0.15);
  color: #c084fc;
  border: 1px solid rgba(192, 132, 252, 0.3);
}

.status-green {
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.4);
}

.btn-toggle-expand {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 6px 14px;
  font-size: 0.82rem;
  color: #cbd5e1;
  cursor: pointer;
  transition: all 200ms ease;
}

.btn-toggle-expand:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.rotate-180 {
  transform: rotate(180deg);
}

/* Step Heading Block */
.step-heading-block {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
}

.step-icon-box {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.2) 0%, rgba(16, 185, 129, 0.05) 100%);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: #34d399;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.2);
}

.step-name {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.01em;
  margin-bottom: 6px;
}

.step-summary {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

/* Pillars Grid */
.step-pillars-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.15fr;
  gap: 20px;
  margin-bottom: 20px;
}

.pillar-card {
  background: rgba(11, 18, 32, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 20px;
}

.highlight-pillar {
  background: linear-gradient(145deg, rgba(18, 25, 42, 0.8) 0%, rgba(5, 150, 105, 0.08) 100%);
  border-color: rgba(16, 185, 129, 0.2);
}

.pillar-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.88rem;
  color: #f1f5f9;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.text-emerald { color: #34d399; }
.text-cyan { color: #38bdf8; }
.text-glow { color: #10b981; }

.pillar-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.pillar-list li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.88rem;
  color: #cbd5e1;
  line-height: 1.5;
}

.pillar-list li i {
  color: #34d399;
  font-size: 0.75rem;
  margin-top: 4px;
  flex-shrink: 0;
}

.pillar-result-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.result-badge {
  display: inline-block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #34d399;
  background: rgba(5, 150, 105, 0.15);
  padding: 4px 10px;
  border-radius: 6px;
}

.result-desc {
  font-size: 0.88rem;
  color: #cbd5e1;
  line-height: 1.6;
}

/* Expanded Notes */
.step-expanded-notes {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.note-box {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: rgba(245, 158, 11, 0.08);
  border: 1px solid rgba(245, 158, 11, 0.2);
  border-radius: 12px;
  padding: 14px 18px;
}

.note-icon {
  color: #fbbf24;
  font-size: 1.1rem;
  margin-top: 2px;
}

.note-body strong {
  display: block;
  font-size: 0.88rem;
  color: #fef3c7;
  margin-bottom: 4px;
}

.note-body p {
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.55;
  margin: 0;
}

/* Step Card Footer */
.step-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  flex-wrap: wrap;
  gap: 16px;
}

.footer-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.84rem;
  color: #94a3b8;
}

.footer-hint i {
  color: #34d399;
}

.footer-cta-wrap {
  display: flex;
  gap: 12px;
}

.btn-sm {
  padding: 8px 18px;
  font-size: 0.88rem;
}

/* ==========================================================================
   3. CHECKLIST SECTION
   ========================================================================== */
.checklist-section {
  padding: 60px 0 80px;
  scroll-margin-top: 90px;
}

.checklist-card-wrap {
  background: rgba(18, 25, 42, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 28px;
  padding: 36px;
  backdrop-filter: blur(14px);
}

.checklist-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 28px;
  margin-bottom: 32px;
  flex-wrap: wrap;
}

.checklist-tabs {
  display: flex;
  gap: 10px;
  background: rgba(11, 18, 32, 0.7);
  padding: 6px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.chk-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: transparent;
  border: none;
  border-radius: 10px;
  color: #94a3b8;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 200ms ease;
  outline: none;
}

.chk-tab-btn:hover {
  color: #ffffff;
}

.chk-tab-btn.is-active {
  background: rgba(5, 150, 105, 0.2);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.35);
}

.chk-counter {
  font-size: 0.75rem;
  background: rgba(255, 255, 255, 0.1);
  padding: 2px 8px;
  border-radius: 9999px;
  color: #cbd5e1;
}

.chk-tab-btn.is-active .chk-counter {
  background: #34d399;
  color: #0b1220;
  font-weight: 800;
}

/* Checklist Items Grid */
.checklist-items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.checklist-item-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px 20px;
  background: rgba(11, 18, 32, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  cursor: pointer;
  transition: all 200ms ease;
  position: relative;
}

.checklist-item-card:hover {
  background: rgba(11, 18, 32, 0.85);
  border-color: rgba(255, 255, 255, 0.14);
}

.checklist-item-card.is-checked {
  background: rgba(5, 150, 105, 0.12);
  border-color: rgba(16, 185, 129, 0.4);
}

.check-box-custom {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  border: 2px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  color: transparent;
  transition: all 180ms ease;
  flex-shrink: 0;
  margin-top: 2px;
}

.checklist-item-card.is-checked .check-box-custom {
  background: #34d399;
  border-color: #34d399;
  color: #0b1220;
}

.check-text {
  flex: 1;
}

.check-text strong {
  display: block;
  font-size: 0.95rem;
  color: #ffffff;
  margin-bottom: 4px;
}

.check-text p {
  font-size: 0.82rem;
  color: #94a3b8;
  line-height: 1.5;
  margin: 0;
}

.check-tag {
  font-size: 0.7rem;
  font-weight: 700;
  color: #34d399;
  background: rgba(5, 150, 105, 0.15);
  padding: 3px 8px;
  border-radius: 6px;
  align-self: flex-start;
  white-space: nowrap;
}

.return-tag {
  color: #38bdf8;
  background: rgba(2, 132, 199, 0.15);
}

/* Checklist Footer */
.checklist-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  flex-wrap: wrap;
  gap: 16px;
}

.chk-note {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.88rem;
  color: #cbd5e1;
}

.btn-reset-checklist {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 8px 16px;
  color: #94a3b8;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 200ms ease;
}

.btn-reset-checklist:hover {
  color: #ffffff;
  border-color: #ffffff;
}

/* ==========================================================================
   4. CHANGE & CANCELLATION SECTION
   ========================================================================== */
.policy-summary-section {
  padding: 60px 0 80px;
  scroll-margin-top: 90px;
}

.rules-three-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.rule-card {
  background: rgba(18, 25, 42, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  padding: 32px 26px;
  display: flex;
  flex-direction: column;
}

.rule-card.highlight-border {
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 12px 32px -8px rgba(5, 150, 105, 0.2);
}

.rule-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #34d399;
  margin-bottom: 20px;
}

.primary-glow {
  background: rgba(5, 150, 105, 0.18);
  border-color: rgba(16, 185, 129, 0.4);
}

.rule-title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 12px;
}

.rule-desc {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 18px;
}

.rule-bullets {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: auto;
}

.rule-bullets li {
  position: relative;
  padding-left: 18px;
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.5;
}

.rule-bullets li::before {
  content: '•';
  position: absolute;
  left: 4px;
  top: 0;
  color: #34d399;
  font-size: 1.1rem;
}

/* ==========================================================================
   5. FAQ ACCORDION SECTION
   ========================================================================== */
.faq-accordion-section {
  padding: 60px 0 80px;
  scroll-margin-top: 90px;
}

.faq-list-grid {
  max-width: 860px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.faq-card {
  background: rgba(18, 25, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  overflow: hidden;
  transition: all 200ms ease;
}

.faq-card.is-open {
  background: rgba(18, 25, 42, 0.9);
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 10px 24px -6px rgba(0, 0, 0, 0.4);
}

.faq-trigger {
  width: 100%;
  padding: 20px 24px;
  background: transparent;
  border: none;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  text-align: left;
  cursor: pointer;
  color: #ffffff;
  outline: none;
}

.faq-q-text {
  font-family: var(--font-heading);
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.4;
}

.faq-icon-pill {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 250ms ease, background 250ms ease;
}

.faq-chevron {
  font-size: 0.8rem;
  color: #94a3b8;
  transition: transform 250ms ease;
}

.faq-card.is-open .faq-icon-pill {
  background: var(--color-primary, #059669);
}

.faq-card.is-open .faq-chevron {
  color: #ffffff;
  transform: rotate(180deg);
}

.faq-body {
  padding: 0 24px 22px;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.faq-body p {
  font-size: 0.92rem;
  color: #94a3b8;
  line-height: 1.65;
  margin-top: 14px;
}

/* ==========================================================================
   6. CTA SECTION
   ========================================================================== */
.process-cta-section {
  padding: 40px 0 60px;
}

.cta-banner-card {
  position: relative;
  background: linear-gradient(135deg, #0e1726 0%, #070c16 100%);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 28px;
  padding: 56px 48px;
  overflow: hidden;
  box-shadow: 0 20px 48px -12px rgba(0, 0, 0, 0.6);
}

.cta-glow-decor {
  position: absolute;
  top: -40px;
  right: -40px;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, rgba(5, 150, 105, 0) 70%);
  border-radius: 50%;
  pointer-events: none;
}

.cta-inner-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}

.cta-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #34d399;
  margin-bottom: 12px;
}

.cta-heading {
  font-family: var(--font-heading);
  font-size: clamp(1.8rem, 3.2vw, 2.4rem);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.2;
  margin-bottom: 14px;
}

.cta-sub {
  font-size: 1rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 28px;
  max-width: 560px;
}

.cta-buttons {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-lg {
  padding: 13px 26px;
  font-size: 0.98rem;
}

.direct-contact-box {
  background: rgba(18, 25, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 28px 24px;
  text-align: center;
}

.box-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #94a3b8;
  margin-bottom: 12px;
}

.hotline-link {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-heading);
  font-size: 1.7rem;
  font-weight: 800;
  color: #34d399;
  text-decoration: none;
  transition: transform 200ms ease;
  margin-bottom: 8px;
}

.hotline-link:hover {
  transform: scale(1.04);
}

.support-time {
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 16px;
}

.location-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.loc-chip {
  font-size: 0.75rem;
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.05);
  padding: 4px 10px;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

/* ==========================================================================
   RESPONSIVE & BREAKPOINTS
   ========================================================================== */
@media (max-width: 1024px) {
  .intro-grid {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .intro-meta-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .step-pillars-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .rules-three-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .cta-inner-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .stepper-tab-selector {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .process-intro-section {
    padding: 90px 0 30px;
  }

  .stepper-tab-selector {
    display: none; /* Hide tab selector on mobile in favor of direct stacked cards */
  }

  .intro-meta-strip {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .quick-nav-links {
    grid-template-columns: 1fr;
  }

  .step-detail-card {
    padding: 22px 18px;
    border-radius: 18px;
  }

  .card-top-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .step-heading-block {
    gap: 14px;
  }

  .step-icon-box {
    width: 44px;
    height: 44px;
    font-size: 1.1rem;
  }

  .step-name {
    font-size: 1.15rem;
  }

  .checklist-card-wrap {
    padding: 24px 18px;
    border-radius: 20px;
  }

  .checklist-header-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 18px;
  }

  .checklist-tabs {
    width: 100%;
    flex-direction: column;
  }

  .chk-tab-btn {
    justify-content: space-between;
    width: 100%;
  }

  .checklist-items-grid {
    grid-template-columns: 1fr;
  }

  .cta-banner-card {
    padding: 36px 20px;
    border-radius: 20px;
  }

  .cta-buttons {
    flex-direction: column;
    width: 100%;
  }

  .cta-buttons .btn-primary,
  .cta-buttons .btn-outline {
    width: 100%;
    text-align: center;
    justify-content: center;
  }
}

@media (max-width: 420px) {
  .intro-title {
    font-size: 1.8rem;
  }

  .hotline-link {
    font-size: 1.4rem;
  }

  .step-badge-group {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
}

/* Reduced Motion Mode Support */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}

[data-animation-mode="reduced"] *,
[data-animation-mode="off"] * {
  animation-duration: 0.01ms !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0.01ms !important;
}
</style>
