<template>
  <div class="admin-page content-cms-page">
    <!-- Top Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-layer-group"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Nội Dung Website (CMS)</span></h1>
          <p class="page-subtitle">
            Cấu hình giao diện chuẩn hóa: Hero, Quy trình, Bento đặc quyền, Giới thiệu, FAQ & Chính sách theo schema an toàn.
          </p>
        </div>
      </div>

      <div class="header-actions">
        <button class="btn-secondary" @click="openPreviewModal">
          <i class="fa-solid fa-eye"></i>
          <span>Xem trước (Preview)</span>
        </button>
        <button class="btn-secondary" @click="openVersionsModal">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Lịch sử phiên bản</span>
        </button>
        <button class="btn-outline-primary" :disabled="savingDraft || !isDirty" @click="handleSaveDraft">
          <i v-if="savingDraft" class="fa-solid fa-spinner fa-spin"></i>
          <i v-else class="fa-solid fa-floppy-disk"></i>
          <span>Lưu bản nháp</span>
        </button>
        <button class="btn-primary" :disabled="publishing" @click="handlePublish">
          <i v-if="publishing" class="fa-solid fa-spinner fa-spin"></i>
          <i v-else class="fa-solid fa-cloud-arrow-up"></i>
          <span>Xuất bản ngay</span>
        </button>
      </div>
    </header>

    <!-- Operational Isolation Warning Alert -->
    <div class="safety-banner card">
      <div class="banner-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div class="banner-text">
        <strong>Nguyên tắc bảo vệ hệ thống:</strong>
        Mọi bảng giá xe, thông số kỹ thuật và trạng thái đặt chỗ tiếp tục được nạp động từ cơ sở dữ liệu xe thực tế. CMS chỉ quản lý các khối văn bản, minh họa và đường dẫn CTA an toàn đã qua kiểm định.
      </div>
    </div>

    <!-- Section Navigation Tabs -->
    <div class="section-tabs-bar">
      <button
        v-for="tab in sectionTabs"
        :key="tab.key"
        type="button"
        class="section-tab-btn"
        :class="{ 'is-active': activeSectionKey === tab.key }"
        @click="switchSection(tab.key)"
      >
        <i :class="tab.icon"></i>
        <span class="tab-label">{{ tab.name }}</span>
        <span
          v-if="contentStatuses[tab.key]?.has_unpublished_changes"
          class="tab-dirty-indicator"
          title="Có bản nháp chưa xuất bản"
        ></span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loadingSection" class="content-loading card text-center">
      <i class="fa-solid fa-spinner fa-spin loading-icon"></i>
      <p>Đang tải dữ liệu khối nội dung...</p>
    </div>

    <!-- Main Editor Card -->
    <div v-else-if="currentRecord" class="editor-main-card card">
      <!-- Section Meta Status Bar -->
      <div class="section-meta-header">
        <div class="meta-left">
          <span class="section-pill">{{ currentRecord.section_key }}</span>
          <h2 class="section-title">{{ currentRecord.title }}</h2>
          <span class="version-badge">Phiên bản: v{{ currentRecord.version }}</span>
          <span
            class="status-badge"
            :class="currentRecord.has_unpublished_changes ? 'status-draft' : 'status-published'"
          >
            <i :class="currentRecord.has_unpublished_changes ? 'fa-solid fa-pen-nib' : 'fa-solid fa-circle-check'"></i>
            {{ currentRecord.has_unpublished_changes ? 'Bản nháp chưa xuất bản' : 'Đã xuất bản trực tuyến' }}
          </span>
        </div>

        <div class="meta-right">
          <small v-if="currentRecord.published_at">
            Xuất bản: {{ formatDate(currentRecord.published_at) }}
            <span v-if="currentRecord.publisher">bởi {{ currentRecord.publisher.ho_ten }}</span>
          </small>
        </div>
      </div>

      <!-- Error alert -->
      <div v-if="formError" class="error-banner animate-in">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ formError }}</span>
      </div>

      <!-- Specific Form for Section -->
      <form @submit.prevent="handleSaveDraft" class="cms-form-body">
        
        <!-- SECTION 1: HOME HERO -->
        <template v-if="activeSectionKey === 'home_hero'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Dòng tiêu đề phụ (Eyebrow) <span class="required">*</span></label>
              <input type="text" v-model="formData.eyebrow" class="cms-input" maxlength="120" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề chính (Title) <span class="required">*</span></label>
              <input type="text" v-model="formData.title" class="cms-input" maxlength="200" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả ngắn gọn (Description) <span class="required">*</span></label>
            <textarea v-model="formData.description" class="cms-textarea" rows="2" maxlength="500" required></textarea>
          </div>

          <div class="form-row two-cols">
            <div class="form-group">
              <label>Nút hành động chính (Primary CTA) <span class="required">*</span></label>
              <input type="text" v-model="formData.primary_cta_text" class="cms-input" maxlength="80" required />
            </div>
            <div class="form-group">
              <label>Hành động CTA chính</label>
              <select v-model="formData.primary_cta_action" class="cms-select">
                <option value="scroll_to_search">Cuộn tới bộ tìm xe (scroll_to_search)</option>
                <option value="/fleet">Chuyển hướng xem đội xe (/fleet)</option>
              </select>
            </div>
          </div>

          <div class="form-row two-cols">
            <div class="form-group">
              <label>Nút hành động phụ (Secondary CTA) <span class="required">*</span></label>
              <input type="text" v-model="formData.secondary_cta_text" class="cms-input" maxlength="80" required />
            </div>
            <div class="form-group">
              <label>Đường dẫn CTA phụ (URL) <span class="required">*</span></label>
              <div class="input-with-validation">
                <input
                  type="text"
                  v-model="formData.secondary_cta_url"
                  class="cms-input"
                  :class="{ 'is-invalid': !isUrlValid(formData.secondary_cta_url) }"
                  placeholder="/fleet hoặc https://..."
                  required
                />
                <span v-if="isUrlValid(formData.secondary_cta_url)" class="url-valid-pill">Hợp lệ</span>
                <span v-else class="url-invalid-pill">URL không an toàn</span>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Ghi chú góc ảnh (Visual note)</label>
            <input type="text" v-model="formData.visual_note" class="cms-input" maxlength="120" />
          </div>
        </template>

        <!-- SECTION 2: HOME RENTAL STEPS -->
        <template v-else-if="activeSectionKey === 'home_rental_steps'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Dòng tiêu đề phụ (Eyebrow) <span class="required">*</span></label>
              <input type="text" v-model="formData.eyebrow" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề chính (Title) <span class="required">*</span></label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả quy trình <span class="required">*</span></label>
            <textarea v-model="formData.description" class="cms-textarea" rows="2" required></textarea>
          </div>

          <h3 class="nested-section-title"><i class="fa-solid fa-list-check"></i> Danh sách 3 bước thực hiện</h3>
          <div class="steps-editor-grid">
            <div v-for="(step, sIdx) in (formData.steps || [])" :key="sIdx" class="step-editor-item card">
              <div class="step-item-header">
                <span class="step-badge">Bước {{ step.step_number || (sIdx + 1) }}</span>
              </div>
              <div class="form-group">
                <label>Mã số bước</label>
                <input type="text" v-model="step.step_number" class="cms-input" placeholder="01" required />
              </div>
              <div class="form-group">
                <label>Biểu tượng FontAwesome</label>
                <div class="icon-input-wrapper">
                  <i :class="step.icon" class="preview-icon"></i>
                  <input type="text" v-model="step.icon" class="cms-input" placeholder="fa-solid fa-car" required />
                </div>
              </div>
              <div class="form-group">
                <label>Tiêu đề bước</label>
                <input type="text" v-model="step.title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Nội dung chi tiết</label>
                <textarea v-model="step.desc" class="cms-textarea" rows="3" required></textarea>
              </div>
            </div>
          </div>
        </template>

        <!-- SECTION 3: HOME BENTO -->
        <template v-else-if="activeSectionKey === 'home_bento'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Dòng tiêu đề phụ (Eyebrow)</label>
              <input type="text" v-model="formData.eyebrow" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề Bento</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <!-- Featured Large Card -->
          <fieldset class="fieldset-card">
            <legend><i class="fa-solid fa-star"></i> Thẻ nổi bật lớn (Large Card)</legend>
            <div class="form-row two-cols">
              <div class="form-group">
                <label>Nhãn thẻ (Badge)</label>
                <input type="text" v-model="formData.featured_badge" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Tiêu đề thẻ</label>
                <input type="text" v-model="formData.featured_title" class="cms-input" required />
              </div>
            </div>

            <div class="form-group">
              <label>Mô tả trải nghiệm</label>
              <textarea v-model="formData.featured_desc" class="cms-textarea" rows="2" required></textarea>
            </div>

            <div class="form-group">
              <label>Ảnh nền thẻ nổi bật</label>
              <div class="image-picker-group">
                <input type="text" v-model="formData.featured_image" class="cms-input" required />
                <button type="button" class="btn-picker" @click="openMediaPicker('featured_image')">
                  <i class="fa-solid fa-images"></i> Chọn từ thư viện
                </button>
              </div>
              <div v-if="formData.featured_image" class="image-preview-box">
                <img :src="formData.featured_image" alt="Preview" />
              </div>
            </div>

            <div class="form-group">
              <label>Điểm nhấn tính năng (1 dòng mỗi điểm)</label>
              <div v-for="(feat, fIdx) in (formData.featured_features || [])" :key="fIdx" class="dynamic-item-row">
                <input type="text" v-model="formData.featured_features[fIdx]" class="cms-input" required />
                <button type="button" class="btn-remove-row" @click="removeFeaturedFeature(fIdx)" v-if="formData.featured_features.length > 1">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
              <button type="button" class="btn-add-item" @click="addFeaturedFeature" v-if="formData.featured_features?.length < 5">
                <i class="fa-solid fa-plus"></i> Thêm điểm nhấn
              </button>
            </div>
          </fieldset>

          <!-- Stacked Right Cards -->
          <div class="two-subcards-grid">
            <fieldset class="fieldset-card">
              <legend><i class="fa-solid fa-map-location-dot"></i> Thẻ mạng lưới (Network)</legend>
              <div class="form-group">
                <label>Nhãn thẻ</label>
                <input type="text" v-model="formData.card_network_badge" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" v-model="formData.card_network_title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea v-model="formData.card_network_desc" class="cms-textarea" rows="3" required></textarea>
              </div>
            </fieldset>

            <fieldset class="fieldset-card">
              <legend><i class="fa-solid fa-shield-halved"></i> Thẻ chính sách hoàn cọc</legend>
              <div class="form-group">
                <label>Nhãn thẻ</label>
                <input type="text" v-model="formData.card_policy_badge" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" v-model="formData.card_policy_title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea v-model="formData.card_policy_desc" class="cms-textarea" rows="3" required></textarea>
              </div>
              <div class="form-row two-cols">
                <div class="form-group">
                  <label>Chữ nút CTA</label>
                  <input type="text" v-model="formData.card_policy_cta_text" class="cms-input" required />
                </div>
                <div class="form-group">
                  <label>Đường dẫn CTA</label>
                  <input type="text" v-model="formData.card_policy_cta_url" class="cms-input" required />
                </div>
              </div>
            </fieldset>
          </div>
        </template>

        <!-- SECTION 4: HOME FAQ -->
        <template v-else-if="activeSectionKey === 'home_faq'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Dòng tiêu đề phụ (Eyebrow)</label>
              <input type="text" v-model="formData.eyebrow" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề chính</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả phần FAQ</label>
            <textarea v-model="formData.description" class="cms-textarea" rows="2" required></textarea>
          </div>

          <div class="faq-list-editor">
            <div class="editor-subhead">
              <h3><i class="fa-solid fa-circle-question"></i> Danh mục câu hỏi thường gặp</h3>
              <button type="button" class="btn-add-item" @click="addFaqItem">
                <i class="fa-solid fa-plus"></i> Thêm câu hỏi
              </button>
            </div>

            <div v-for="(faq, fIdx) in (formData.faqs || [])" :key="fIdx" class="faq-editor-card card">
              <div class="faq-editor-head">
                <span class="faq-num">#{{ fIdx + 1 }}</span>
                <button type="button" class="btn-remove-row" @click="removeFaqItem(fIdx)" v-if="formData.faqs.length > 1">
                  <i class="fa-solid fa-trash"></i> Xóa
                </button>
              </div>
              <div class="form-group">
                <label>Câu hỏi</label>
                <input type="text" v-model="faq.question" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Câu trả lời</label>
                <textarea v-model="faq.answer" class="cms-textarea" rows="3" required></textarea>
              </div>
            </div>
          </div>
        </template>

        <!-- SECTION 5: ABOUT PAGE -->
        <template v-else-if="activeSectionKey === 'about_page'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Huy hiệu trang (Badge)</label>
              <input type="text" v-model="formData.badge" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề trang (Title)</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả Hero</label>
            <textarea v-model="formData.hero_desc" class="cms-textarea" rows="2" required></textarea>
          </div>

          <fieldset class="fieldset-card">
            <legend><i class="fa-solid fa-bullseye"></i> Sứ mệnh & Tầm nhìn</legend>
            <div class="form-group">
              <label>Tiêu đề phần sứ mệnh</label>
              <input type="text" v-model="formData.mission_title" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Đoạn văn 1</label>
              <textarea v-model="formData.mission_paragraph_1" class="cms-textarea" rows="3" required></textarea>
            </div>
            <div class="form-group">
              <label>Đoạn văn 2</label>
              <textarea v-model="formData.mission_paragraph_2" class="cms-textarea" rows="3" required></textarea>
            </div>
          </fieldset>

          <fieldset class="fieldset-card">
            <legend><i class="fa-solid fa-handshake"></i> Cam kết dịch vụ (3 cam kết)</legend>
            <div class="commitments-editor-grid">
              <div v-for="(commit, cIdx) in (formData.commitments || [])" :key="cIdx" class="commit-editor-item card">
                <div class="form-group">
                  <label>Icon</label>
                  <div class="icon-input-wrapper">
                    <i :class="commit.icon" class="preview-icon"></i>
                    <input type="text" v-model="commit.icon" class="cms-input" required />
                  </div>
                </div>
                <div class="form-group">
                  <label>Tiêu đề</label>
                  <input type="text" v-model="commit.title" class="cms-input" required />
                </div>
                <div class="form-group">
                  <label>Mô tả ngắn</label>
                  <textarea v-model="commit.desc" class="cms-textarea" rows="2" required></textarea>
                </div>
              </div>
            </div>
          </fieldset>

          <div class="form-group">
            <label>Ảnh đội ngũ / Văn phòng</label>
            <div class="image-picker-group">
              <input type="text" v-model="formData.team_image" class="cms-input" required />
              <button type="button" class="btn-picker" @click="openMediaPicker('team_image')">
                <i class="fa-solid fa-images"></i> Chọn từ thư viện
              </button>
            </div>
            <div v-if="formData.team_image" class="image-preview-box">
              <img :src="formData.team_image" alt="Preview" />
            </div>
          </div>
        </template>

        <!-- SECTION 6: CONTACT PAGE -->
        <template v-else-if="activeSectionKey === 'contact_page'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Huy hiệu trang (Badge)</label>
              <input type="text" v-model="formData.badge" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề trang (Title)</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả Header</label>
            <textarea v-model="formData.hero_desc" class="cms-textarea" rows="2" required></textarea>
          </div>

          <div class="form-row two-cols">
            <div class="form-group">
              <label>Tiêu đề Form gửi yêu cầu</label>
              <input type="text" v-model="formData.form_title" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Phụ đề Form</label>
              <input type="text" v-model="formData.form_subtitle" class="cms-input" required />
            </div>
          </div>

          <div class="form-row two-cols">
            <div class="form-group">
              <label>Tiêu đề cột Kênh liên hệ</label>
              <input type="text" v-model="formData.sidebar_title" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Phụ đề cột Kênh liên hệ</label>
              <input type="text" v-model="formData.sidebar_sub" class="cms-input" required />
            </div>
          </div>

          <fieldset class="fieldset-card">
            <legend><i class="fa-solid fa-robot"></i> Khối Trợ Lý Ảo AI (DriveBot)</legend>
            <div class="form-row two-cols">
              <div class="form-group">
                <label>Huy hiệu trạng thái</label>
                <input type="text" v-model="formData.ai_badge" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Tiêu đề trợ lý AI</label>
                <input type="text" v-model="formData.ai_title" class="cms-input" required />
              </div>
            </div>
            <div class="form-group">
              <label>Mô tả trợ lý AI</label>
              <textarea v-model="formData.ai_desc" class="cms-textarea" rows="2" required></textarea>
            </div>
          </fieldset>
        </template>

        <!-- SECTION 7: PROCESS PAGE -->
        <template v-else-if="activeSectionKey === 'process_page'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Huy hiệu trang (Badge)</label>
              <input type="text" v-model="formData.badge" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề trang (Title)</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả quy trình</label>
            <textarea v-model="formData.hero_desc" class="cms-textarea" rows="2" required></textarea>
          </div>

          <h3 class="nested-section-title"><i class="fa-solid fa-timeline"></i> 4 Bước trong quy trình</h3>
          <div class="steps-editor-grid">
            <div v-for="(step, sIdx) in (formData.steps || [])" :key="sIdx" class="step-editor-item card">
              <div class="step-item-header">
                <span class="step-badge">Bước {{ step.step_number || (sIdx + 1) }}</span>
              </div>
              <div class="form-group">
                <label>Mã số bước</label>
                <input type="text" v-model="step.step_number" class="cms-input" placeholder="01" required />
              </div>
              <div class="form-group">
                <label>Biểu tượng FontAwesome</label>
                <div class="icon-input-wrapper">
                  <i :class="step.icon" class="preview-icon"></i>
                  <input type="text" v-model="step.icon" class="cms-input" required />
                </div>
              </div>
              <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" v-model="step.title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Mô tả chi tiết</label>
                <textarea v-model="step.desc" class="cms-textarea" rows="3" required></textarea>
              </div>
            </div>
          </div>

          <fieldset class="fieldset-card mt-4">
            <legend><i class="fa-solid fa-bullhorn"></i> Khối Call-To-Action Cuối Trang</legend>
            <div class="form-row two-cols">
              <div class="form-group">
                <label>Tiêu đề CTA</label>
                <input type="text" v-model="formData.cta_title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Mô tả CTA</label>
                <input type="text" v-model="formData.cta_desc" class="cms-input" required />
              </div>
            </div>
            <div class="form-row two-cols">
              <div class="form-group">
                <label>Nút chính (Text)</label>
                <input type="text" v-model="formData.primary_cta_text" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Nút chính (URL)</label>
                <input type="text" v-model="formData.primary_cta_url" class="cms-input" required />
              </div>
            </div>
            <div class="form-row two-cols">
              <div class="form-group">
                <label>Nút phụ (Text)</label>
                <input type="text" v-model="formData.secondary_cta_text" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Nút phụ (URL)</label>
                <input type="text" v-model="formData.secondary_cta_url" class="cms-input" required />
              </div>
            </div>
          </fieldset>
        </template>

        <!-- SECTION 8: POLICIES PAGE -->
        <template v-else-if="activeSectionKey === 'policies_page'">
          <div class="form-row two-cols">
            <div class="form-group">
              <label>Huy hiệu trang (Badge)</label>
              <input type="text" v-model="formData.badge" class="cms-input" required />
            </div>
            <div class="form-group">
              <label>Tiêu đề trang (Title)</label>
              <input type="text" v-model="formData.title" class="cms-input" required />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả mở đầu (Intro)</label>
            <textarea v-model="formData.intro" class="cms-textarea" rows="2" required></textarea>
          </div>

          <div class="faq-list-editor">
            <div class="editor-subhead">
              <h3><i class="fa-solid fa-scale-balanced"></i> Danh sách các điều khoản chính sách</h3>
              <button type="button" class="btn-add-item" @click="addPolicySection">
                <i class="fa-solid fa-plus"></i> Thêm điều khoản
              </button>
            </div>

            <div v-for="(sec, sIdx) in (formData.sections || [])" :key="sIdx" class="faq-editor-card card">
              <div class="faq-editor-head">
                <span class="faq-num">Điều khoản {{ sIdx + 1 }}</span>
                <button type="button" class="btn-remove-row" @click="removePolicySection(sIdx)" v-if="formData.sections.length > 1">
                  <i class="fa-solid fa-trash"></i> Xóa
                </button>
              </div>
              <div class="form-group">
                <label>Tiêu đề điều khoản</label>
                <input type="text" v-model="sec.title" class="cms-input" required />
              </div>
              <div class="form-group">
                <label>Nội dung điều khoản</label>
                <textarea v-model="sec.content" class="cms-textarea" rows="3" required></textarea>
              </div>
            </div>
          </div>
        </template>

        <!-- Bottom Action Bar -->
        <div class="editor-bottom-bar">
          <div class="dirty-status" v-if="isDirty">
            <span class="dirty-dot"></span>
            <span>Bạn có thay đổi chưa lưu bản nháp.</span>
          </div>
          <div class="ml-auto d-flex gap-3">
            <button type="button" class="btn-secondary" @click="resetToInitialDraft">
              <i class="fa-solid fa-rotate-left"></i> Phục hồi chưa lưu
            </button>
            <button type="submit" class="btn-outline-primary" :disabled="savingDraft">
              <i v-if="savingDraft" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              Lưu bản nháp
            </button>
            <button type="button" class="btn-primary" :disabled="publishing" @click="handlePublish">
              <i v-if="publishing" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-cloud-arrow-up"></i>
              Xuất bản công khai
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- MODAL 1: PRIVATE PREVIEW MODAL -->
    <div v-if="showPreviewModal" class="modal-backdrop-dark" @click.self="showPreviewModal = false">
      <div class="preview-modal-card card animate-in">
        <div class="preview-header">
          <div class="preview-title-box">
            <span class="badge-preview">Xem trước riêng tư (Admin Private Preview)</span>
            <h3>{{ currentRecord?.title }}</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="showPreviewModal = false">&times;</button>
        </div>

        <div class="preview-body-container">
          <div class="preview-disclaimer">
            <i class="fa-solid fa-eye"></i>
            <span>Dưới đây là nội dung bản nháp được dựng theo giao diện thực tế. Nội dung này hoàn toàn riêng tư và chưa hiển thị ra ngoài công chúng.</span>
          </div>

          <!-- Live Mock Preview Rendering based on section -->
          <div class="preview-mock-stage">
            <div v-if="activeSectionKey === 'home_hero'" class="hero-preview-box">
              <p class="preview-eyebrow">{{ formData.eyebrow }}</p>
              <h1 class="preview-hero-title">{{ formData.title }}</h1>
              <p class="preview-desc">{{ formData.description }}</p>
              <div class="preview-actions">
                <button type="button" class="btn-primary">{{ formData.primary_cta_text }} ↗</button>
                <span class="preview-link">{{ formData.secondary_cta_text }} ({{ formData.secondary_cta_url }}) →</span>
              </div>
              <small class="preview-note"><i class="fa-solid fa-circle-info"></i> {{ formData.visual_note }}</small>
            </div>

            <div v-else-if="activeSectionKey === 'home_rental_steps'" class="steps-preview-box">
              <h2>{{ formData.title }}</h2>
              <p class="preview-desc">{{ formData.description }}</p>
              <div class="preview-steps-grid">
                <div v-for="(st, idx) in (formData.steps || [])" :key="idx" class="preview-step-card">
                  <div class="st-num">{{ st.step_number }}</div>
                  <h4><i :class="st.icon"></i> {{ st.title }}</h4>
                  <p>{{ st.desc }}</p>
                </div>
              </div>
            </div>

            <div v-else-if="activeSectionKey === 'home_bento'" class="bento-preview-box">
              <h2>{{ formData.title }}</h2>
              <div class="preview-bento-grid">
                <div class="preview-bento-main">
                  <span class="badge mb-2">{{ formData.featured_badge }}</span>
                  <h3>{{ formData.featured_title }}</h3>
                  <p>{{ formData.featured_desc }}</p>
                  <ul>
                    <li v-for="(f, idx) in formData.featured_features" :key="idx"><i class="fa-solid fa-check"></i> {{ f }}</li>
                  </ul>
                </div>
                <div class="preview-bento-side">
                  <div class="side-card">
                    <h4>{{ formData.card_network_title }}</h4>
                    <p>{{ formData.card_network_desc }}</p>
                  </div>
                  <div class="side-card">
                    <h4>{{ formData.card_policy_title }}</h4>
                    <p>{{ formData.card_policy_desc }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="activeSectionKey === 'home_faq'" class="faq-preview-box">
              <h2>{{ formData.title }}</h2>
              <div class="preview-faq-list">
                <div v-for="(faq, idx) in (formData.faqs || [])" :key="idx" class="preview-faq-item">
                  <strong>Q: {{ faq.question }}</strong>
                  <p>A: {{ faq.answer }}</p>
                </div>
              </div>
            </div>

            <div v-else class="generic-preview-box">
              <h2>{{ formData.title }}</h2>
              <p class="preview-desc">{{ formData.hero_desc || formData.intro }}</p>
            </div>
          </div>
        </div>

        <div class="preview-footer">
          <button type="button" class="btn-secondary" @click="showPreviewModal = false">Đóng xem trước</button>
          <button type="button" class="btn-primary" @click="handlePublishFromPreview">
            <i class="fa-solid fa-cloud-arrow-up"></i> Xuất bản ngay từ bản xem trước
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 2: VERSION HISTORY MODAL -->
    <div v-if="showVersionsModal" class="modal-backdrop-dark" @click.self="showVersionsModal = false">
      <div class="versions-modal-card card animate-in">
        <div class="preview-header">
          <div class="preview-title-box">
            <span class="badge-versions">Lịch sử & Khôi phục</span>
            <h3>Phiên bản của khối [{{ currentRecord?.title }}]</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="showVersionsModal = false">&times;</button>
        </div>

        <div class="versions-body-container">
          <div v-if="loadingVersions" class="text-center py-4">
            <i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh sách phiên bản...
          </div>
          <div v-else-if="versionHistory.length === 0" class="text-center py-4 text-muted">
            Chưa có phiên bản lịch sử nào.
          </div>
          <div v-else class="version-timeline-list">
            <div
              v-for="ver in versionHistory"
              :key="ver.id"
              class="version-item"
              :class="{ 'is-current': ver.version === currentRecord.version }"
            >
              <div class="version-dot-col">
                <span class="version-dot"></span>
                <span class="version-line"></span>
              </div>
              <div class="version-content-box">
                <div class="version-row-head">
                  <div class="v-pill">v{{ ver.version }}</div>
                  <span v-if="ver.version === currentRecord.version" class="current-tag">Phiên bản hiện tại</span>
                  <span class="v-date">{{ formatDate(ver.created_at) }}</span>
                </div>
                <p class="v-note">{{ ver.note || 'Không có ghi chú' }}</p>
                <div class="v-meta">
                  <small v-if="ver.creator">Người thực hiện: {{ ver.creator.ho_ten }} ({{ ver.creator.email }})</small>
                </div>
                <div class="v-actions" v-if="ver.version !== currentRecord.version">
                  <button
                    type="button"
                    class="btn-restore"
                    :disabled="restoring"
                    @click="handleRestore(ver)"
                  >
                    <i class="fa-solid fa-rotate-left"></i> Khôi phục về v{{ ver.version }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 3: MEDIA PICKER MODAL -->
    <div v-if="showMediaPicker" class="modal-backdrop-dark" @click.self="showMediaPicker = false">
      <div class="media-picker-modal-card card animate-in">
        <div class="preview-header">
          <div class="preview-title-box">
            <span class="badge-picker">Thư viện phương tiện</span>
            <h3>Chọn ảnh minh họa</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="showMediaPicker = false">&times;</button>
        </div>

        <div class="media-picker-search">
          <input
            type="text"
            v-model="mediaSearch"
            placeholder="Tìm kiếm tài nguyên..."
            class="cms-input"
            @input="fetchMediaAssets"
          />
        </div>

        <div class="media-picker-grid">
          <div v-if="loadingMedia" class="col-span-full text-center py-4">
            <i class="fa-solid fa-spinner fa-spin"></i> Đang tải thư viện ảnh...
          </div>
          <div v-else-if="mediaList.length === 0" class="col-span-full text-center py-4 text-muted">
            Chưa có tài nguyên hình ảnh nào.
          </div>
          <div
            v-else
            v-for="asset in mediaList"
            :key="asset.id"
            class="media-select-card"
            @click="selectMediaAsset(asset)"
          >
            <img :src="asset.url || asset.file_path" :alt="asset.alt_text || asset.original_name" />
            <div class="media-name-tag">{{ asset.original_name }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import baseRequest from '@/core/baseRequest';
import { createToaster } from '@meforma/vue-toaster';
import { invalidateClientContentCache } from '@/core/contentStore';

const toaster = createToaster({ position: 'top-right' });

// 8 Blocks
const sectionTabs = [
  { key: 'home_hero', name: 'Hero Trang Chủ', icon: 'fa-solid fa-flag' },
  { key: 'home_rental_steps', name: '3 Bước Quy Trình', icon: 'fa-solid fa-list-ol' },
  { key: 'home_bento', name: 'Bento Đặc Quyền', icon: 'fa-solid fa-shapes' },
  { key: 'home_faq', name: 'FAQ Hỏi Đáp', icon: 'fa-solid fa-circle-question' },
  { key: 'about_page', name: 'Giới Thiệu (About)', icon: 'fa-solid fa-building' },
  { key: 'contact_page', name: 'Liên Hệ (Contact)', icon: 'fa-solid fa-address-book' },
  { key: 'process_page', name: 'Quy Trình 4 Bước', icon: 'fa-solid fa-timeline' },
  { key: 'policies_page', name: 'Chính Sách & Quy Định', icon: 'fa-solid fa-scale-balanced' },
];

const activeSectionKey = ref('home_hero');
const currentRecord = ref(null);
const loadingSection = ref(false);
const savingDraft = ref(false);
const publishing = ref(false);
const restoring = ref(false);
const formError = ref('');
const contentStatuses = reactive({});

// Reactive form state
const formData = reactive({});
let initialFormSnapshot = '';

// Modals
const showPreviewModal = ref(false);
const showVersionsModal = ref(false);
const showMediaPicker = ref(false);
const mediaTargetField = ref('');
const versionHistory = ref([]);
const loadingVersions = ref(false);
const mediaList = ref([]);
const loadingMedia = ref(false);
const mediaSearch = ref('');

// Computed dirty state
const isDirty = computed(() => {
  return JSON.stringify(formData) !== initialFormSnapshot;
});

// URL validator
function isUrlValid(url) {
  if (!url) return true;
  const trimmed = String(url).trim();
  if (/^(javascript|data|vbscript|file):/i.test(trimmed)) return false;
  if (trimmed.includes('<') || trimmed.includes('>')) return false;
  return /^(\/[a-zA-Z0-9_\-.~%!$&'()*+,;=:@\/?#]*|https?:\/\/[a-zA-Z0-9\-._~:?#\[\]@!$&'()*+,;=]+)$/i.test(trimmed);
}

// Fetch all sections overview
async function fetchAllStatuses() {
  try {
    const res = await baseRequest.get('/admin/content');
    if (res.data && res.data.status && res.data.data) {
      res.data.data.forEach(item => {
        contentStatuses[item.section_key] = {
          version: item.version,
          has_unpublished_changes: item.has_unpublished_changes,
          status: item.status,
        };
      });
    }
  } catch (err) {
    console.error('Fetch CMS statuses error:', err);
  }
}

// Fetch single section details
async function loadSection(sectionKey) {
  loadingSection.value = true;
  formError.value = '';
  try {
    const res = await baseRequest.get(`/admin/content/${sectionKey}`);
    if (res.data && res.data.status && res.data.data) {
      currentRecord.value = res.data.data;

      // Populate form data prioritizing draft_content over published_content
      const source = res.data.data.draft_content || res.data.data.published_content || {};
      // Reset formData object
      Object.keys(formData).forEach(k => delete formData[k]);
      Object.assign(formData, JSON.parse(JSON.stringify(source)));

      initialFormSnapshot = JSON.stringify(formData);
    }
  } catch (err) {
    toaster.error('Không thể tải chi tiết khối nội dung');
    console.error(err);
  } finally {
    loadingSection.value = false;
  }
}

// Switch section tab
async function switchSection(key) {
  if (isDirty.value) {
    if (!confirm('Bạn có thay đổi chưa lưu bản nháp. Chuyển tab sẽ mất các thay đổi chưa lưu, bạn có chắc chắn?')) {
      return;
    }
  }
  activeSectionKey.value = key;
  await loadSection(key);
}

// Save draft
async function handleSaveDraft() {
  savingDraft.value = true;
  formError.value = '';
  try {
    const res = await baseRequest.post(`/admin/content/${activeSectionKey.value}/draft`, {
      content: formData,
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || 'Đã lưu bản nháp thành công!');
      currentRecord.value = res.data.data;
      contentStatuses[activeSectionKey.value] = {
        version: res.data.data.version,
        has_unpublished_changes: true,
        status: res.data.data.status,
      };
      initialFormSnapshot = JSON.stringify(formData);
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Lỗi khi lưu bản nháp. Vui lòng kiểm tra lại.';
    formError.value = msg;
    toaster.error(msg);
  } finally {
    savingDraft.value = false;
  }
}

// Publish
async function handlePublish() {
  if (!confirm(`Xác nhận xuất bản khối [${currentRecord.value?.title}] ra website công khai? Nội dung cũ sẽ được lưu vào lịch sử phiên bản.`)) {
    return;
  }

  publishing.value = true;
  formError.value = '';
  try {
    const res = await baseRequest.post(`/admin/content/${activeSectionKey.value}/publish`, {
      content: formData,
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || 'Xuất bản thành công!');
      currentRecord.value = res.data.data;
      contentStatuses[activeSectionKey.value] = {
        version: res.data.data.version,
        has_unpublished_changes: false,
        status: 'published',
      };
      initialFormSnapshot = JSON.stringify(formData);

      // Invalidate frontend local cache
      await invalidateClientContentCache();
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Lỗi khi xuất bản nội dung.';
    formError.value = msg;
    toaster.error(msg);
  } finally {
    publishing.value = false;
  }
}

async function handlePublishFromPreview() {
  showPreviewModal.value = false;
  await handlePublish();
}

// Reset form
function resetToInitialDraft() {
  if (initialFormSnapshot) {
    Object.keys(formData).forEach(k => delete formData[k]);
    Object.assign(formData, JSON.parse(initialFormSnapshot));
    toaster.info('Đã hoàn tác về bản nháp gần nhất.');
  }
}

// Preview Modal
function openPreviewModal() {
  showPreviewModal.value = true;
}

// Versions Modal
async function openVersionsModal() {
  showVersionsModal.value = true;
  loadingVersions.value = true;
  try {
    const res = await baseRequest.get(`/admin/content/${activeSectionKey.value}/versions`);
    if (res.data && res.data.status && res.data.data) {
      versionHistory.value = res.data.data.versions || [];
    }
  } catch (err) {
    toaster.error('Không thể tải lịch sử phiên bản');
  } finally {
    loadingVersions.value = false;
  }
}

// Restore Version
async function handleRestore(targetVersion) {
  if (!confirm(`Khôi phục nội dung về phiên bản v${targetVersion.version}? Thao tác này sẽ tạo một phiên bản xuất bản mới từ dữ liệu lịch sử.`)) {
    return;
  }

  restoring.value = true;
  try {
    const res = await baseRequest.post(`/admin/content/${activeSectionKey.value}/restore/${targetVersion.id}`);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || 'Đã khôi phục phiên bản thành công!');
      showVersionsModal.value = false;
      await loadSection(activeSectionKey.value);
      await fetchAllStatuses();
      await invalidateClientContentCache();
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi khôi phục phiên bản');
  } finally {
    restoring.value = false;
  }
}

// Media Picker
async function openMediaPicker(targetField) {
  mediaTargetField.value = targetField;
  showMediaPicker.value = true;
  await fetchMediaAssets();
}

async function fetchMediaAssets() {
  loadingMedia.value = true;
  try {
    const params = { collection: 'all' };
    if (mediaSearch.value.trim()) params.search = mediaSearch.value.trim();
    const res = await baseRequest.get('/admin/media', { params });
    if (res.data && res.data.status) {
      mediaList.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Fetch media list error:', err);
  } finally {
    loadingMedia.value = false;
  }
}

function selectMediaAsset(asset) {
  const url = asset.url || asset.file_path || `/admin/media/${asset.id}/stream`;
  if (mediaTargetField.value) {
    formData[mediaTargetField.value] = url;
  }
  showMediaPicker.value = false;
  toaster.success('Đã chọn hình ảnh!');
}

// Array helpers for Bento & FAQ & Policies
function addFeaturedFeature() {
  if (!formData.featured_features) formData.featured_features = [];
  formData.featured_features.push('Điểm nhấn mới');
}
function removeFeaturedFeature(idx) {
  formData.featured_features.splice(idx, 1);
}

function addFaqItem() {
  if (!formData.faqs) formData.faqs = [];
  formData.faqs.push({ question: 'Câu hỏi mới?', answer: 'Nội dung trả lời giải đáp...' });
}
function removeFaqItem(idx) {
  formData.faqs.splice(idx, 1);
}

function addPolicySection() {
  if (!formData.sections) formData.sections = [];
  formData.sections.push({ title: 'Điều khoản bổ sung', content: 'Nội dung quy định chi tiết...' });
}
function removePolicySection(idx) {
  formData.sections.splice(idx, 1);
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

onMounted(async () => {
  await fetchAllStatuses();
  await loadSection(activeSectionKey.value);
});
</script>

<style scoped>
.content-cms-page {
  padding-bottom: 80px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 52px;
  height: 52px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  font-size: 1.5rem;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-main);
  margin: 0 0 4px 0;
}

.page-title span {
  color: var(--color-primary);
}

.page-subtitle {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.safety-banner {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: var(--radius-md);
  margin-bottom: 24px;
}

.banner-icon {
  font-size: 1.5rem;
  color: #10b981;
}

.banner-text {
  font-size: 0.88rem;
  color: var(--color-text-main);
  line-height: 1.5;
}

/* Tabs */
.section-tabs-bar {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 12px;
  margin-bottom: 24px;
}

.section-tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 18px;
  border-radius: var(--radius-md);
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  position: relative;
}

.section-tab-btn:hover {
  color: var(--color-text-main);
  border-color: rgba(37, 99, 235, 0.4);
}

.section-tab-btn.is-active {
  background: rgba(37, 99, 235, 0.12);
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.tab-dirty-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f59e0b;
}

/* Editor Main Card */
.editor-main-card {
  padding: 32px;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.section-meta-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 20px;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.meta-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.section-pill {
  font-family: monospace;
  font-size: 0.8rem;
  padding: 4px 10px;
  border-radius: 6px;
  background: var(--bg-surface-alt);
  color: var(--color-text-muted);
}

.section-title {
  font-size: 1.3rem;
  font-weight: 700;
  margin: 0;
  color: var(--color-text-main);
}

.version-badge {
  padding: 4px 10px;
  border-radius: 100px;
  font-size: 0.8rem;
  font-weight: 700;
  background: rgba(37, 99, 235, 0.15);
  color: var(--color-primary);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 100px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-draft {
  background: rgba(245, 158, 11, 0.12);
  color: #f59e0b;
}

.status-published {
  background: rgba(16, 185, 129, 0.12);
  color: #10b981;
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  border-radius: var(--radius-md);
  margin-bottom: 24px;
}

/* Forms */
.form-row {
  display: grid;
  gap: 20px;
  margin-bottom: 20px;
}

.form-row.two-cols {
  grid-template-columns: 1fr 1fr;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 8px;
  color: var(--color-text-main);
}

.required {
  color: #ef4444;
}

.cms-input,
.cms-select,
.cms-textarea {
  width: 100%;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  color: var(--color-text-main);
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.cms-input:focus,
.cms-select:focus,
.cms-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}

.input-with-validation {
  position: relative;
}

.url-valid-pill {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
  font-weight: 600;
}

.url-invalid-pill {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  font-weight: 600;
}

.fieldset-card {
  padding: 24px;
  border-radius: var(--radius-md);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  margin-bottom: 24px;
}

.fieldset-card legend {
  font-size: 1rem;
  font-weight: 700;
  padding: 0 10px;
  color: var(--color-primary);
}

.two-subcards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.nested-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 28px 0 16px 0;
  color: var(--color-text-main);
}

.steps-editor-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.step-editor-item {
  padding: 20px;
  border-radius: var(--radius-md);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
}

.step-item-header {
  margin-bottom: 12px;
}

.step-badge {
  font-size: 0.8rem;
  font-weight: 700;
  padding: 2px 8px;
  background: rgba(37, 99, 235, 0.15);
  color: var(--color-primary);
  border-radius: 4px;
}

.icon-input-wrapper {
  position: relative;
}

.preview-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-primary);
}

.icon-input-wrapper input {
  padding-left: 38px;
}

.image-picker-group {
  display: flex;
  gap: 10px;
}

.btn-picker {
  padding: 10px 16px;
  border-radius: var(--radius-md);
  background: rgba(37, 99, 235, 0.15);
  color: var(--color-primary);
  border: 1px solid rgba(37, 99, 235, 0.3);
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  white-space: nowrap;
}

.image-preview-box {
  margin-top: 10px;
  max-width: 240px;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.image-preview-box img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.dynamic-item-row {
  display: flex;
  gap: 10px;
  margin-bottom: 8px;
}

.btn-remove-row {
  padding: 0 14px;
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
  cursor: pointer;
}

.btn-add-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-md);
  background: var(--bg-surface);
  border: 1px dashed var(--color-primary);
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
}

.faq-list-editor {
  margin-top: 24px;
}

.editor-subhead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.faq-editor-card {
  padding: 20px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  margin-bottom: 16px;
}

.faq-editor-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.faq-num {
  font-weight: 700;
  color: var(--color-primary);
}

.commitments-editor-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.commit-editor-item {
  padding: 16px;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

/* Bottom Bar */
.editor-bottom-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 36px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
}

.dirty-status {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  color: #f59e0b;
  font-weight: 600;
}

.dirty-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #f59e0b;
}

/* Modals */
.modal-backdrop-dark {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.preview-modal-card {
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.badge-preview {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 3px 8px;
  background: rgba(37, 99, 235, 0.2);
  color: var(--color-primary);
  border-radius: 4px;
  display: inline-block;
  margin-bottom: 4px;
}

.badge-versions {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 3px 8px;
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
  border-radius: 4px;
  display: inline-block;
  margin-bottom: 4px;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 1.8rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.preview-body-container {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.preview-disclaimer {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.2);
  color: var(--color-text-main);
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  margin-bottom: 24px;
}

.preview-mock-stage {
  padding: 36px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-md);
  border: 1px dashed var(--color-border);
}

.preview-eyebrow {
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.preview-hero-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 12px;
}

.preview-desc {
  color: var(--color-text-muted);
  font-size: 1rem;
  line-height: 1.6;
}

.preview-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 20px 0;
}

.preview-link {
  color: var(--color-primary);
  font-weight: 600;
}

.preview-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
}

/* Versions Modal */
.versions-modal-card {
  width: 100%;
  max-width: 680px;
  max-height: 85vh;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.versions-body-container {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.version-timeline-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.version-item {
  display: flex;
  gap: 16px;
}

.version-dot-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.version-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--color-border);
  border: 3px solid var(--bg-surface);
  box-shadow: 0 0 0 2px var(--color-border);
}

.version-item.is-current .version-dot {
  background: #10b981;
  box-shadow: 0 0 0 2px #10b981;
}

.version-line {
  flex: 1;
  width: 2px;
  background: var(--color-border);
  margin-top: 4px;
}

.version-content-box {
  flex: 1;
  padding: 16px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.version-row-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
}

.v-pill {
  padding: 2px 8px;
  border-radius: 100px;
  font-weight: 700;
  font-size: 0.8rem;
  background: rgba(37, 99, 235, 0.15);
  color: var(--color-primary);
}

.current-tag {
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
  font-weight: 600;
}

.v-date {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-left: auto;
}

.v-note {
  font-size: 0.9rem;
  color: var(--color-text-main);
  margin: 0 0 8px 0;
}

.v-meta {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.v-actions {
  margin-top: 10px;
}

.btn-restore {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: var(--radius-md);
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-restore:hover {
  background: rgba(37, 99, 235, 0.1);
  border-color: var(--color-primary);
}

/* Media Picker Modal */
.media-picker-modal-card {
  width: 100%;
  max-width: 800px;
  max-height: 85vh;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.badge-picker {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 3px 8px;
  background: rgba(168, 85, 247, 0.2);
  color: #a855f7;
  border-radius: 4px;
  display: inline-block;
  margin-bottom: 4px;
}

.media-picker-search {
  padding: 16px 24px;
  border-bottom: 1px solid var(--color-border);
}

.media-picker-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.media-select-card {
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: transform 0.2s, border-color 0.2s;
  background: var(--bg-surface-alt);
}

.media-select-card:hover {
  transform: translateY(-2px);
  border-color: var(--color-primary);
}

.media-select-card img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}

.media-name-tag {
  padding: 6px 8px;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 992px) {
  .steps-editor-grid,
  .commitments-editor-grid {
    grid-template-columns: 1fr;
  }
  .form-row.two-cols,
  .two-subcards-grid {
    grid-template-columns: 1fr;
  }
  .media-picker-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
