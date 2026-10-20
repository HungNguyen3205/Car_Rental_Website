<template>
  <div class="about-page" data-page="about">
    <!-- 1. COMPACT EDITORIAL HERO & BREADCRUMB -->
    <section class="about-intro-section" aria-label="Giới thiệu thương hiệu">
      <div class="app-container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <router-link to="/" class="bc-link">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
          </router-link>
          <span class="bc-sep" aria-hidden="true">/</span>
          <span class="bc-current" aria-current="page">Về chúng tôi</span>
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

            <div class="intro-actions">
              <router-link to="/fleet" class="btn-primary">
                <span>Khám phá đội xe</span>
                <i class="fa-solid fa-arrow-right"></i>
              </router-link>
              <router-link to="/process" class="btn-outline">
                <i class="fa-solid fa-route"></i>
                <span>Xem quy trình thuê xe</span>
              </router-link>
            </div>
          </div>

          <!-- Hero Verified Meta Highlights -->
          <div class="intro-highlights-card">
            <div class="card-header-label">
              <i class="fa-solid fa-certificate text-emerald"></i>
              <span>TIÊU CHUẨN VẬN HÀNH XÁC NHẬN</span>
            </div>
            <div class="highlights-list">
              <div class="hl-item">
                <div class="hl-icon"><i class="fa-solid fa-car-on"></i></div>
                <div class="hl-text">
                  <strong>Phương tiện kiểm định kỹ thuật</strong>
                  <span>Bảo dưỡng định kỳ, vệ sinh và sạc pin tối thiểu 80% trước khi bàn giao.</span>
                </div>
              </div>
              <div class="hl-item">
                <div class="hl-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="hl-text">
                  <strong>Chi phí niêm yết minh bạch</strong>
                  <span>Giá thuê theo ngày đã gồm VAT, snapshot cố định từ server, không phụ phí ẩn.</span>
                </div>
              </div>
              <div class="hl-item">
                <div class="hl-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="hl-text">
                  <strong>Cam kết hoàn cọc trong 24 giờ</strong>
                  <span>Chuyển trả tiền cọc an ninh trực tiếp về tài khoản sau khi hoàn tất đối soát.</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. BRAND STORY & OPERATIONAL MISSION -->
    <section class="brand-story-section app-container" aria-label="Câu chuyện và thông điệp thương hiệu">
      <div class="story-grid">
        <div class="story-text-column">
          <span class="eyebrow-text">SỨ MỆNH & GIÁ TRỊ CỐT LÕI</span>
          <h2 class="section-title">{{ missionTitle }}</h2>
          
          <div class="story-paragraphs">
            <p class="lead-para">
              {{ missionParagraph1 }}
            </p>
            <p>
              {{ missionParagraph2 }}
            </p>
          </div>

          <!-- Editorial Quote -->
          <blockquote class="editorial-quote">
            <div class="quote-mark">“</div>
            <p>
              Việc thuê xe phải đơn giản và tin cậy như một chạm: thông tin minh bạch, phương tiện sẵn sàng và chi phí không bao giờ có phụ phí ẩn.
            </p>
            <cite>— Đội ngũ vận hành {{ settings.site_name || 'DriveRent' }}</cite>
          </blockquote>

          <!-- Verified Corporate Details -->
          <div class="corporate-info-strip">
            <div class="corp-item">
              <span class="corp-label">Trụ sở hoạt động</span>
              <span class="corp-val">{{ settings.company_address || 'Tòa nhà Bitexco, Q.1, TP. Hồ Chí Minh' }}</span>
            </div>
            <div class="corp-item">
              <span class="corp-label">Hotline hỗ trợ 24/7</span>
              <a :href="`tel:${cleanPhone(settings.hotline)}`" class="corp-val hotline-link">
                {{ settings.hotline || '1900 1234' }}
              </a>
            </div>
            <div class="corp-item">
              <span class="corp-label">Email hỗ trợ</span>
              <a :href="`mailto:${settings.support_email}`" class="corp-val">
                {{ settings.support_email || 'support@driverentals.com' }}
              </a>
            </div>
          </div>
        </div>

        <!-- Right Side: Cohesive Media Frame -->
        <div class="story-media-column">
          <div class="media-frame-card">
            <div class="media-glow-overlay" aria-hidden="true"></div>
            <img
              :src="heroMediaSrc"
              :alt="`Hệ thống xe điện thông minh ${settings.site_name || 'DriveRent'}`"
              class="story-main-img"
              loading="lazy"
              @error="handleMediaImgError"
            />
            <div class="media-caption-badge">
              <div class="caption-dot"></div>
              <span>Hệ sinh thái di chuyển xanh • Sẵn sàng lăn bánh</span>
            </div>
          </div>

          <!-- Operational Coverage Badge -->
          <div class="coverage-card">
            <div class="coverage-header">
              <i class="fa-solid fa-map-location-dot text-emerald"></i>
              <strong>Mạng Lưới Phục Vụ Thực Tế</strong>
            </div>
            <p class="coverage-desc">
              Bàn giao xe linh hoạt tại Showroom trung tâm và đón trả tận nơi tại các sân bay lớn:
            </p>
            <div class="locations-tag-list">
              <span v-for="loc in serviceLocations" :key="loc" class="loc-tag">
                <i class="fa-solid fa-location-dot"></i> {{ loc }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. HOW WE SUPPORT CLIENTS (3 OPERATIONAL PILLARS) -->
    <section class="how-we-work-section app-container" aria-label="Cách dịch vụ hỗ trợ khách hàng">
      <div class="section-head-center">
        <span class="eyebrow-text">QUY TRÌNH HỖ TRỢ</span>
        <h2 class="section-title">Cách Dịch Vụ Đồng Hành Cùng Bạn</h2>
        <p class="section-desc">
          Ứng dụng công nghệ để mọi thao tác từ tra cứu, giữ lịch đến nghiệm thu xe diễn ra chuẩn xác, bảo vệ quyền lợi hai bên.
        </p>
      </div>

      <div class="pillars-three-grid">
        <div class="pillar-box">
          <div class="pillar-step-num">01</div>
          <div class="pillar-icon-wrap">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
          </div>
          <h3 class="pillar-title">Tìm Kiếm & Khả Dụng Thật</h3>
          <p class="pillar-text">
            Phân loại rõ ràng thân xe (SUV, Sedan) và loại nhiên liệu (Điện 100%, Xăng, Hybrid). Chỉ hiển thị xe thực sự khả dụng theo khung giờ nhận/trả được chọn.
          </p>
          <div class="pillar-footer-note">
            <i class="fa-solid fa-check text-emerald"></i>
            <span>Không hiển thị xe ảo hoặc trùng lịch</span>
          </div>
        </div>

        <div class="pillar-box highlight-pillar">
          <div class="pillar-step-num text-glow">02</div>
          <div class="pillar-icon-wrap primary-glow">
            <i class="fa-solid fa-lock"></i>
          </div>
          <h3 class="pillar-title">Khóa Bi Quan & Snapshot Giá</h3>
          <p class="pillar-text">
            Cơ chế khóa lịch xe ngay khi khởi tạo đơn giúp ngăn ngừa xung đột đặt trùng. Biểu phí niêm yết được snapshot cố định từ máy chủ, không phụ thu bất ngờ.
          </p>
          <div class="pillar-footer-note">
            <i class="fa-solid fa-check text-emerald"></i>
            <span>Giữ lịch an toàn tuyệt đối</span>
          </div>
        </div>

        <div class="pillar-box">
          <div class="pillar-step-num">03</div>
          <div class="pillar-icon-wrap">
            <i class="fa-solid fa-file-signature"></i>
          </div>
          <h3 class="pillar-title">Biên Bản Điện Tử & Hoàn Cọc 24H</h3>
          <p class="pillar-text">
            Chụp ảnh ngoại thất, đối chiếu số ODO và lượng pin trực tiếp khi giao nhận. Biên bản số hóa ký 2 chiều và tiền cọc an ninh được hoàn trả trong vòng 24 giờ.
          </p>
          <div class="pillar-footer-note">
            <i class="fa-solid fa-check text-emerald"></i>
            <span>Quy trình nghiệm thu minh bạch</span>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. GROUNDED COMMITMENTS & VALUES -->
    <section class="commitments-section app-container" aria-label="Cam kết và giá trị cốt lõi">
      <div class="section-head-center">
        <span class="eyebrow-text">TIÊU CHUẨN DỊCH VỤ</span>
        <h2 class="section-title">Cam Kết Dựa Trên Cơ Sở Thực Tế</h2>
        <p class="section-desc">
          Chúng tôi chỉ công bố những tiêu chuẩn mà hệ thống và đội ngũ vận hành thực sự cam kết thực thi trong mỗi chuyến đi.
        </p>
      </div>

      <div class="commitments-cards-grid">
        <div
          v-for="(commit, idx) in verifiedCommitments"
          :key="idx"
          class="commitment-card"
        >
          <div class="commit-header">
            <div class="commit-icon-box">
              <i :class="commit.icon"></i>
            </div>
            <span class="commit-seq">CAM KẾT 0{{ idx + 1 }}</span>
          </div>
          <h3 class="commit-title">{{ commit.title }}</h3>
          <p class="commit-desc">{{ commit.desc }}</p>
          <div class="commit-badge-proof">
            <i class="fa-solid fa-circle-check text-emerald"></i>
            <span>Áp dụng trên 100% chuyến đi</span>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. REAL FLEET SHOWCASE -->
    <section class="fleet-showcase-section app-container" aria-label="Hình ảnh đội xe thực tế">
      <div class="showcase-header-row">
        <div class="header-left">
          <span class="eyebrow-text">PHƯƠNG TIỆN SẴN SÀNG</span>
          <h2 class="section-title">Đội Xe Tuyển Chọn Đang Vận Hành</h2>
          <p class="section-desc">
            Hình ảnh các dòng xe thực tế đang phục vụ khách hàng trên toàn quốc, kiểm định kỹ thuật nghiêm ngặt.
          </p>
        </div>
        <div class="header-right">
          <router-link to="/fleet" class="btn-primary">
            <span>Xem toàn bộ {{ totalFleetCount ? `${totalFleetCount} xe` : 'đội xe' }}</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>

      <div class="fleet-sample-grid">
        <div
          v-for="sample in sampleFleet"
          :key="sample.id"
          class="fleet-sample-card"
        >
          <div class="sample-image-stage">
            <img
              :src="sample.image"
              :alt="sample.name"
              loading="lazy"
              @error="handleCarFallback($event)"
            />
            <div class="ground-shadow" aria-hidden="true"></div>
            <span class="sample-tag">{{ sample.category }}</span>
          </div>
          <div class="sample-meta">
            <h4 class="sample-name">{{ sample.name }}</h4>
            <div class="sample-specs">
              <span><i class="fa-solid fa-users"></i> {{ sample.seats }} chỗ</span>
              <span><i class="fa-solid fa-gas-pump"></i> {{ sample.fuel }}</span>
              <span><i class="fa-solid fa-gear"></i> {{ sample.transmission }}</span>
            </div>
            <div class="sample-bottom">
              <span class="sample-price">{{ sample.priceText }}</span>
              <router-link :to="`/fleet/${sample.id}`" class="link-detail">
                <span>Chi tiết</span>
                <i class="fa-solid fa-arrow-right"></i>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 6. CALL TO ACTION & DIRECT CONTACT BANNER -->
    <section class="about-cta-section app-container" aria-label="Khám phá hành trình">
      <div class="cta-banner-card">
        <div class="cta-glow-decor" aria-hidden="true"></div>
        <div class="cta-inner-grid">
          <div class="cta-text-side">
            <div class="cta-badge">
              <i class="fa-solid fa-shield-halved"></i>
              <span>ĐỒNG HÀNH MINH BẠCH • AN TÂM LĂN BÁNH</span>
            </div>
            <h2 class="cta-heading">
              Sẵn Sàng Trải Nghiệm Cùng {{ settings.site_name || 'DriveRent' }}?
            </h2>
            <p class="cta-sub">
              Khám phá danh mục xe thực tế, chọn showroom gần bạn nhất và đặt xe nhanh chóng chỉ với vài thao tác.
            </p>
            <div class="cta-buttons">
              <router-link to="/fleet" class="btn-primary btn-lg">
                <span>Khám phá đội xe ngay</span>
                <i class="fa-solid fa-arrow-right"></i>
              </router-link>
              <router-link to="/contact" class="btn-outline btn-lg">
                <i class="fa-solid fa-headset"></i>
                <span>Liên hệ tư vấn viên</span>
              </router-link>
            </div>
          </div>

          <div class="cta-meta-side">
            <div class="direct-contact-box">
              <span class="box-label">HOTLINE TƯ VẤN & HỖ TRỢ</span>
              <a :href="`tel:${cleanPhone(settings.hotline)}`" class="hotline-link">
                <i class="fa-solid fa-phone-volume"></i>
                <span>{{ settings.hotline || '1900 1234' }}</span>
              </a>
              <p class="support-time">Hỗ trợ 24/7 kể cả ngày nghỉ và lễ Tết</p>
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
import { computed } from 'vue';
import { useWebsiteContent } from '@/core/contentStore';
import { useSystemSettings } from '@/core/settingsStore';

// Cài đặt thương hiệu và CMS phản ứng
const aboutContent = useWebsiteContent('about_page');
const { settings } = useSystemSettings();

// Chuẩn hóa tiêu đề và huy hiệu
const pageBadge = computed(() => {
  return aboutContent.badge || 'VỀ CHÚNG TÔI';
});

const pageTitle = computed(() => {
  return aboutContent.title || 'Hành Trình Di Chuyển Thông Minh & Bền Vững';
});

const pageHeroDesc = computed(() => {
  return aboutContent.hero_desc || 'DriveRent mang đến giải pháp thuê xe điện và xe sang tự lái tiện lợi, minh bạch về chi phí và số hóa toàn diện từ khâu chọn xe đến bàn giao thực tế.';
});

const missionTitle = computed(() => {
  const name = settings.site_name || 'DriveRent';
  if (aboutContent.mission_title) {
    return aboutContent.mission_title.replace(/DriveRentals/g, name);
  }
  return `Sứ Mệnh Và Giá Trị Của ${name}`;
});

const missionParagraph1 = computed(() => {
  const name = settings.site_name || 'DriveRent';
  if (aboutContent.mission_paragraph_1) {
    return aboutContent.mission_paragraph_1.replace(/DriveRentals/g, name);
  }
  return `${name} được định hình với mục tiêu đơn giản hóa quy trình thuê xe tại Việt Nam. Chúng tôi kết nối khách hàng với phương tiện thông minh, đáp ứng nhu cầu du lịch, công tác và di chuyển gia đình linh hoạt.`;
});

const missionParagraph2 = computed(() => {
  const name = settings.site_name || 'DriveRent';
  if (aboutContent.mission_paragraph_2) {
    return aboutContent.mission_paragraph_2.replace(/DriveRentals/g, name);
  }
  return 'Mỗi phương tiện trong hệ thống đều trải qua quy trình kiểm tra kỹ thuật định kỳ, khử khuẩn và sạc đầy trước khi bàn giao. Toàn bộ thông tin giá niêm yết, tình trạng xe và quy định đặt cọc đều được thể hiện rõ ràng trên hợp đồng số.';
});

// Làm sạch số điện thoại cho thẻ tel:
const cleanPhone = (phone) => {
  if (!phone) return '19001234';
  return String(phone).replace(/\s+/g, '');
};

// Ảnh đại diện thương hiệu (Ưu tiên ảnh bento/showroom nội bộ, tránh ảnh stock nhân viên giả)
const heroMediaSrc = computed(() => {
  if (aboutContent.team_image && !aboutContent.team_image.includes('pexels')) {
    return aboutContent.team_image;
  }
  return '/assets/images/bento_main.jpg';
});

const handleMediaImgError = (event) => {
  if (!event.target.dataset.hasFailed) {
    event.target.dataset.hasFailed = 'true';
    event.target.src = '/assets/images/hero.png';
  }
};

const handleCarFallback = (event) => {
  if (!event.target.dataset.hasFailed) {
    event.target.dataset.hasFailed = 'true';
    event.target.src = '/assets/images/cars/vf8.png';
  }
};

// Danh sách khu vực dịch vụ thực tế
const serviceLocations = computed(() => {
  if (Array.isArray(settings.service_locations) && settings.service_locations.length) {
    return settings.service_locations;
  }
  return ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng'];
});

// Cam kết đã xác nhận từ cấu hình CMS
const verifiedCommitments = computed(() => {
  if (aboutContent.commitments && Array.isArray(aboutContent.commitments) && aboutContent.commitments.length) {
    return aboutContent.commitments;
  }
  return [
    {
      icon: 'fa-solid fa-bolt',
      title: 'Năng Lượng Sạch & Hiện Đại',
      desc: 'Hệ sinh thái xe điện thông minh giúp tối ưu chi phí vận hành và mang lại trải nghiệm lái êm ái, tĩnh lặng.'
    },
    {
      icon: 'fa-solid fa-receipt',
      title: 'Minh Bạch Chi Phí & Snapshot',
      desc: 'Biểu phí niêm yết công khai đã gồm VAT, chốt giá tự động theo ngày và cam kết hoàn cọc an ninh trong 24 giờ.'
    },
    {
      icon: 'fa-solid fa-headset',
      title: 'Hỗ Trợ Đồng Hành 24/7',
      desc: 'Đội ngũ tư vấn viên và kỹ thuật viên luôn túc trực, sẵn sàng hỗ trợ khách hàng trong suốt thời gian thuê xe.'
    }
  ];
});

// Mẫu xe đại diện thực tế đang có trong đội xe (Không bịa đặt)
const sampleFleet = [
  {
    id: 1,
    name: 'VinFast VF8 Plus',
    category: 'SUV Điện Thông Minh',
    image: '/assets/images/cars/vf8.png',
    seats: 5,
    fuel: 'Điện 100% EV',
    transmission: 'Tự động',
    priceText: 'Từ 1.250.000đ/ngày'
  },
  {
    id: 2,
    name: 'VinFast VF7 Plus',
    category: 'C-SUV Thể Thao',
    image: '/assets/images/cars/vf7.png',
    seats: 5,
    fuel: 'Điện 100% EV',
    transmission: 'Tự động',
    priceText: 'Từ 1.100.000đ/ngày'
  },
  {
    id: 3,
    name: 'Toyota Camry 2.5Q',
    category: 'Sedan Hạng D',
    image: '/assets/images/cars/camry.png',
    seats: 5,
    fuel: 'Xăng',
    transmission: 'Tự động',
    priceText: 'Từ 1.300.000đ/ngày'
  }
];

const totalFleetCount = 9; // Số xe đang hoạt động thực tế trong CSDL
</script>

<style scoped>
/* ==========================================================================
   ABOUT PAGE — Emerald-Navy Design System (Synced with Home, Fleet, Process)
   ========================================================================== */

.about-page {
  background-color: var(--bg-dark, #0b1220);
  color: var(--color-text-on-dark, #f8fafc);
  min-height: 100vh;
  padding-bottom: 90px;
  overflow-x: hidden;
}

/* 1. COMPACT INTRO SECTION */
.about-intro-section {
  padding: 110px 0 50px;
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
  grid-template-columns: 1.25fr 1fr;
  gap: 40px;
  align-items: center;
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
  margin-bottom: 24px;
}

.intro-actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

/* Highlights Card */
.intro-highlights-card {
  background: rgba(18, 25, 42, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  padding: 28px;
  backdrop-filter: blur(14px);
}

.card-header-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #34d399;
  margin-bottom: 18px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.highlights-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.hl-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.hl-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(5, 150, 105, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #34d399;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  flex-shrink: 0;
}

.hl-text strong {
  display: block;
  font-size: 0.92rem;
  color: #ffffff;
  margin-bottom: 3px;
}

.hl-text span {
  font-size: 0.82rem;
  color: #94a3b8;
  line-height: 1.5;
  display: block;
}

/* ==========================================================================
   2. BRAND STORY SECTION
   ========================================================================== */
.brand-story-section {
  padding: 80px 0 60px;
}

.story-grid {
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 50px;
  align-items: flex-start;
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
  margin-bottom: 18px;
}

.story-paragraphs {
  display: flex;
  flex-direction: column;
  gap: 14px;
  font-size: 0.98rem;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 24px;
}

.lead-para {
  color: #cbd5e1;
  font-size: 1.05rem;
}

/* Editorial Quote */
.editorial-quote {
  position: relative;
  background: rgba(18, 25, 42, 0.6);
  border-left: 3px solid var(--color-primary, #059669);
  border-radius: 0 16px 16px 0;
  padding: 22px 26px 22px 28px;
  margin: 28px 0;
}

.quote-mark {
  position: absolute;
  top: 6px;
  left: 12px;
  font-size: 2.2rem;
  color: rgba(16, 185, 129, 0.2);
  font-family: Georgia, serif;
  line-height: 1;
  pointer-events: none;
}

.editorial-quote p {
  font-size: 0.96rem;
  font-style: italic;
  color: #f1f5f9;
  line-height: 1.6;
  margin-bottom: 8px;
  position: relative;
  z-index: 2;
}

.editorial-quote cite {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #34d399;
  font-style: normal;
}

/* Corporate Info Strip */
.corporate-info-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.corp-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.corp-label {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.corp-val {
  font-size: 0.92rem;
  font-weight: 600;
  color: #e2e8f0;
  text-decoration: none;
}

.hotline-link {
  color: #34d399;
  font-family: var(--font-heading);
  font-weight: 800;
  font-size: 1.15rem;
}

/* Media Column */
.story-media-column {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.media-frame-card {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.6);
  background: #0f172a;
}

.media-glow-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(11, 18, 32, 0) 60%, rgba(11, 18, 32, 0.85) 100%);
  z-index: 2;
  pointer-events: none;
}

.story-main-img {
  width: 100%;
  height: 380px;
  object-fit: cover;
  display: block;
  transition: transform 400ms ease;
}

.media-frame-card:hover .story-main-img {
  transform: scale(1.02);
}

.media-caption-badge {
  position: absolute;
  bottom: 16px;
  left: 16px;
  right: 16px;
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background: rgba(11, 18, 32, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  backdrop-filter: blur(8px);
  font-size: 0.78rem;
  color: #cbd5e1;
}

.caption-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #34d399;
  box-shadow: 0 0 8px #34d399;
  flex-shrink: 0;
}

/* Coverage Card */
.coverage-card {
  background: rgba(18, 25, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  padding: 20px;
}

.coverage-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  color: #ffffff;
  margin-bottom: 6px;
}

.coverage-desc {
  font-size: 0.82rem;
  color: #94a3b8;
  line-height: 1.5;
  margin-bottom: 12px;
}

.locations-tag-list {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.loc-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #34d399;
  background: rgba(5, 150, 105, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 4px 12px;
  border-radius: 9999px;
}

/* ==========================================================================
   3. HOW WE WORK (OPERATIONAL PILLARS)
   ========================================================================== */
.how-we-work-section {
  padding: 60px 0 80px;
}

.section-head-center {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 40px;
}

.section-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

.pillars-three-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.pillar-box {
  background: rgba(18, 25, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  padding: 32px 26px;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 250ms ease;
}

.pillar-box:hover {
  background: rgba(18, 25, 42, 0.85);
  border-color: rgba(255, 255, 255, 0.16);
  transform: translateY(-4px);
}

.highlight-pillar {
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 12px 32px -8px rgba(5, 150, 105, 0.2);
}

.pillar-step-num {
  font-family: var(--font-heading);
  font-size: 1.8rem;
  font-weight: 800;
  color: rgba(255, 255, 255, 0.15);
  margin-bottom: 12px;
  line-height: 1;
}

.text-glow {
  color: rgba(16, 185, 129, 0.5);
}

.pillar-icon-wrap {
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
  margin-bottom: 18px;
}

.primary-glow {
  background: rgba(5, 150, 105, 0.18);
  border-color: rgba(16, 185, 129, 0.4);
}

.pillar-title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 12px;
}

.pillar-text {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 20px;
}

.pillar-footer-note {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #cbd5e1;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.text-emerald {
  color: #34d399;
}

/* ==========================================================================
   4. COMMITMENTS SECTION
   ========================================================================== */
.commitments-section {
  padding: 60px 0 80px;
}

.commitments-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.commitment-card {
  background: rgba(18, 25, 42, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  padding: 30px 26px;
  display: flex;
  flex-direction: column;
  transition: all 250ms ease;
}

.commitment-card:hover {
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 14px 32px -10px rgba(0, 0, 0, 0.5);
  transform: translateY(-4px);
}

.commit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.commit-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(5, 150, 105, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #34d399;
}

.commit-seq {
  font-family: var(--font-heading);
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #64748b;
}

.commit-title {
  font-family: var(--font-heading);
  font-size: 1.2rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 10px;
}

.commit-desc {
  font-size: 0.9rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 20px;
}

.commit-badge-proof {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  color: #34d399;
  font-weight: 600;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

/* ==========================================================================
   5. REAL FLEET SHOWCASE
   ========================================================================== */
.fleet-showcase-section {
  padding: 60px 0 80px;
}

.showcase-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
  margin-bottom: 36px;
  flex-wrap: wrap;
}

.fleet-sample-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.fleet-sample-card {
  background: rgba(18, 25, 42, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 22px;
  overflow: hidden;
  transition: all 250ms ease;
}

.fleet-sample-card:hover {
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 14px 36px -10px rgba(5, 150, 105, 0.2);
  transform: translateY(-4px);
}

.sample-image-stage {
  position: relative;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at center, rgba(16, 185, 129, 0.08) 0%, rgba(11, 18, 32, 0) 70%);
  padding: 16px;
}

.sample-image-stage img {
  max-width: 90%;
  max-height: 130px;
  object-fit: contain;
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 12px 16px rgba(0, 0, 0, 0.5));
  transition: transform 300ms ease;
}

.fleet-sample-card:hover .sample-image-stage img {
  transform: scale(1.05);
}

.ground-shadow {
  position: absolute;
  bottom: 24px;
  width: 70%;
  height: 12px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0) 75%);
  border-radius: 50%;
  z-index: 1;
}

.sample-tag {
  position: absolute;
  top: 14px;
  left: 16px;
  font-size: 0.72rem;
  font-weight: 700;
  color: #34d399;
  background: rgba(5, 150, 105, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 3px 10px;
  border-radius: 9999px;
  z-index: 3;
}

.sample-meta {
  padding: 20px 22px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.sample-name {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 10px;
}

.sample-specs {
  display: flex;
  gap: 12px;
  font-size: 0.78rem;
  color: #94a3b8;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.sample-specs span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.sample-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.sample-price {
  font-family: var(--font-heading);
  font-size: 0.95rem;
  font-weight: 800;
  color: #34d399;
}

.link-detail {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #e2e8f0;
  text-decoration: none;
  transition: all 180ms ease;
}

.link-detail:hover {
  color: #34d399;
  transform: translateX(3px);
}

/* ==========================================================================
   6. CTA SECTION
   ========================================================================== */
.about-cta-section {
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
    gap: 30px;
  }

  .story-grid {
    grid-template-columns: 1fr;
    gap: 40px;
  }

  .pillars-three-grid,
  .commitments-cards-grid,
  .fleet-sample-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .cta-inner-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 768px) {
  .about-intro-section {
    padding: 90px 0 30px;
  }

  .intro-actions {
    flex-direction: column;
    width: 100%;
  }

  .intro-actions .btn-primary,
  .intro-actions .btn-outline {
    width: 100%;
    text-align: center;
    justify-content: center;
  }

  .story-main-img {
    height: 280px;
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
