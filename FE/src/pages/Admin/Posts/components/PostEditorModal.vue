<template>
  <div v-if="visible" class="editor-modal-overlay" @click.self="handleAttemptClose">
    <div class="editor-modal-container animate-in">
      <!-- 1. HEADER BAR -->
      <header class="editor-header">
        <div class="header-left">
          <div class="header-icon-badge">
            <i class="fa-solid fa-pen-nib"></i>
          </div>
          <div class="header-titles">
            <div class="title-with-badge">
              <h3 class="editor-title">
                {{ isEditing ? 'Chỉnh sửa bài viết #' + postId : 'Soạn bài viết mới' }}
              </h3>
              <!-- Status Pill -->
              <span class="status-pill" :class="form.status">
                <i :class="getStatusIcon(form.status)"></i>
                {{ getStatusLabel(form.status) }}
              </span>
              <!-- Pending revision warning pill if published post has unapproved revision -->
              <span v-if="hasPendingRevision" class="revision-pill">
                <i class="fa-solid fa-code-compare"></i> Có bản sửa đổi chờ duyệt
              </span>
            </div>
            
            <!-- Save State Indicator & Last Saved Time -->
            <div class="save-status-indicator">
              <span v-if="saveState === 'saved'" class="state-badge state-saved">
                <i class="fa-solid fa-circle-check"></i> Đã lưu tất cả
                <span v-if="lastSavedText" class="time-sub">({{ lastSavedText }})</span>
              </span>
              <span v-else-if="saveState === 'saving'" class="state-badge state-saving">
                <i class="fa-solid fa-spinner fa-spin"></i> Đang lưu nháp...
              </span>
              <span v-else-if="saveState === 'dirty'" class="state-badge state-dirty">
                <i class="fa-solid fa-circle-dot"></i> Có thay đổi chưa lưu
              </span>
              <span v-else-if="saveState === 'conflict'" class="state-badge state-conflict">
                <i class="fa-solid fa-triangle-exclamation"></i> Xung đột phiên làm việc!
              </span>
              <span v-else-if="saveState === 'error'" class="state-badge state-error">
                <i class="fa-solid fa-circle-exclamation"></i> Lỗi lưu bản nháp
              </span>
            </div>
          </div>
        </div>

        <!-- Header Actions: Mobile Tab Switcher, Preview, Close -->
        <div class="header-right">
          <!-- Mobile Tab Switcher (Visible on < 992px) -->
          <div class="mobile-tabs-bar">
            <button 
              type="button" 
              class="mob-tab-btn" 
              :class="{ 'active': activeMobileTab === 'content' }"
              @click="activeMobileTab = 'content'"
            >
              <i class="fa-solid fa-align-left"></i> Nội dung
            </button>
            <button 
              type="button" 
              class="mob-tab-btn" 
              :class="{ 'active': activeMobileTab === 'settings' }"
              @click="activeMobileTab = 'settings'"
            >
              <i class="fa-solid fa-sliders"></i> Cài đặt & SEO
            </button>
            <button 
              type="button" 
              class="mob-tab-btn" 
              :class="{ 'active': activeMobileTab === 'preview' }"
              @click="activeMobileTab = 'preview'"
            >
              <i class="fa-regular fa-eye"></i> Xem trước
            </button>
          </div>

          <!-- Private Preview Trigger Button (Desktop & Mobile) -->
          <button 
            type="button" 
            class="btn-header-preview" 
            @click="openPrivatePreview" 
            title="Xem trước riêng tư bài viết"
          >
            <i class="fa-regular fa-eye"></i>
            <span>Xem trước</span>
          </button>

          <!-- Close Modal Button -->
          <button type="button" class="btn-close-editor" @click="handleAttemptClose" title="Đóng">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </header>

      <!-- 2. WARNING BANNER FOR EDITING PUBLISHED POST -->
      <div v-if="isEditingPublishedWithoutPublishPerm" class="revision-warning-alert">
        <i class="fa-solid fa-shield-halved alert-ico"></i>
        <div class="alert-content">
          <strong>Chế độ bảo vệ bài đã xuất bản:</strong>
          <p>
            Bài viết này đang công khai. Vì bạn không có quyền xuất bản trực tiếp, toàn bộ nội dung sửa đổi sẽ được lưu vào 
            <em>bản duyệt sửa đổi</em>. Bạn đọc ngoài website sẽ vẫn xem nội dung đã được duyệt trước đó cho đến khi người có thẩm quyền phê duyệt.
          </p>
        </div>
      </div>

      <!-- 3. MAIN EDITOR WORKSPACE -->
      <div class="editor-workspace">
        <!-- LEFT COLUMN: CONTENT & EDITING -->
        <div 
          class="workspace-main" 
          :class="{ 'mob-hidden': activeMobileTab !== 'content' && activeMobileTab !== 'preview' }"
        >
          <!-- Title Input -->
          <div class="form-group" :class="{ 'has-error': fieldErrors.title }">
            <label class="form-label required">
              <span>Tiêu đề bài viết:</span>
              <span class="char-count">{{ form.title.length }}/255</span>
            </label>
            <input 
              type="text" 
              v-model="form.title" 
              class="form-input title-input" 
              placeholder="VD: Cẩm nang lái xe điện VinFast an toàn đường đèo và sương mù..."
              maxlength="255"
              @input="onTitleInput"
            />
            <div v-if="fieldErrors.title" class="field-error-text">
              <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.title }}
            </div>
          </div>

          <!-- Slug Field (Editable, auto-generated, collision safe) -->
          <div class="form-group" :class="{ 'has-error': fieldErrors.slug }">
            <div class="slug-label-row">
              <label class="form-label">
                <span>Đường dẫn tĩnh (Slug duy nhất):</span>
              </label>
              <button type="button" class="btn-regen-slug" @click="regenerateSlug" title="Tạo lại slug từ tiêu đề">
                <i class="fa-solid fa-rotate"></i> Tạo lại theo tiêu đề
              </button>
            </div>
            <div class="slug-wrapper">
              <span class="slug-prefix">/blog/</span>
              <input 
                type="text" 
                v-model="form.slug" 
                class="form-input slug-input" 
                placeholder="cam-nang-lai-xe-dien-vinfast"
                maxlength="255"
                @input="isCustomSlug = true; markDirty()"
              />
            </div>
            <div v-if="fieldErrors.slug" class="field-error-text">
              <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.slug }}
            </div>
          </div>

          <!-- Excerpt / Summary -->
          <div class="form-group" :class="{ 'has-error': fieldErrors.summary }">
            <label class="form-label">
              <span>Tóm tắt bài viết (Excerpt):</span>
              <span class="char-count">{{ (form.summary || '').length }}/1000</span>
            </label>
            <textarea 
              v-model="form.summary" 
              class="form-textarea summary-textarea" 
              rows="2" 
              placeholder="Tóm tắt ngắn gọn 1-2 câu hiển thị ở trang chủ, danh sách bài viết và thẻ mô tả mạng xã hội..."
              maxlength="1000"
              @input="markDirty"
            ></textarea>
            <div v-if="fieldErrors.summary" class="field-error-text">
              <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.summary }}
            </div>
          </div>

          <!-- Rich Content Editor with Formatting Toolbar -->
          <div class="form-group editor-block" :class="{ 'has-error': fieldErrors.content }">
            <!-- Content Header & View Mode Switcher -->
            <div class="content-header-bar">
              <label class="form-label required mb-0">Nội dung bài viết:</label>
              
              <!-- View mode switcher on desktop -->
              <div class="view-mode-tabs desktop-only">
                <button 
                  type="button" 
                  class="mode-btn" 
                  :class="{ 'active': desktopViewMode === 'edit' }"
                  @click="desktopViewMode = 'edit'"
                >
                  <i class="fa-solid fa-code"></i> Soạn thảo
                </button>
                <button 
                  type="button" 
                  class="mode-btn" 
                  :class="{ 'active': desktopViewMode === 'split' }"
                  @click="desktopViewMode = 'split'"
                >
                  <i class="fa-solid fa-columns"></i> Chia đôi màn hình
                </button>
                <button 
                  type="button" 
                  class="mode-btn" 
                  :class="{ 'active': desktopViewMode === 'preview' }"
                  @click="desktopViewMode = 'preview'"
                >
                  <i class="fa-regular fa-eye"></i> Xem trước
                </button>
              </div>
            </div>

            <!-- Formatting Toolbar -->
            <div class="rich-toolbar">
              <!-- Headings -->
              <div class="btn-group-fmt">
                <button type="button" class="t-btn" @click="insertHeading('h2')" title="Tiêu đề H2"><b>H2</b></button>
                <button type="button" class="t-btn" @click="insertHeading('h3')" title="Tiêu đề H3"><b>H3</b></button>
                <button type="button" class="t-btn" @click="insertHeading('h4')" title="Tiêu đề H4"><b>H4</b></button>
              </div>

              <!-- Inline Styles -->
              <div class="btn-group-fmt">
                <button type="button" class="t-btn" @click="wrapSelection('**', '**', 'chữ in đậm')" title="In đậm (Bold)">
                  <i class="fa-solid fa-bold"></i>
                </button>
                <button type="button" class="t-btn" @click="wrapSelection('*', '*', 'chữ in nghiêng')" title="In nghiêng (Italic)">
                  <i class="fa-solid fa-italic"></i>
                </button>
                <button type="button" class="t-btn" @click="wrapSelection('<u>', '</u>', 'văn bản gạch chân')" title="Gạch chân (Underline)">
                  <i class="fa-solid fa-underline"></i>
                </button>
                <button type="button" class="t-btn" @click="wrapSelection('~~', '~~', 'văn bản gạch ngang')" title="Gạch ngang (Strikethrough)">
                  <i class="fa-solid fa-strikethrough"></i>
                </button>
              </div>

              <!-- Lists & Blocks -->
              <div class="btn-group-fmt">
                <button type="button" class="t-btn" @click="insertBulletList" title="Danh sách chấm (Bullet list)">
                  <i class="fa-solid fa-list-ul"></i>
                </button>
                <button type="button" class="t-btn" @click="insertNumberedList" title="Danh sách số (Numbered list)">
                  <i class="fa-solid fa-list-ol"></i>
                </button>
                <button type="button" class="t-btn" @click="wrapSelection('> ', '', 'Đoạn trích dẫn ghi chú quan trọng')" title="Khối trích dẫn (Blockquote)">
                  <i class="fa-solid fa-quote-left"></i>
                </button>
                <button type="button" class="t-btn" @click="insertCodeBlock" title="Khối mã (Code block)">
                  <i class="fa-solid fa-code"></i>
                </button>
                <button type="button" class="t-btn" @click="insertHorizontalRule" title="Đường kẻ ngang (Divider)">
                  <i class="fa-solid fa-minus"></i>
                </button>
              </div>

              <!-- Media & Embeds -->
              <div class="btn-group-fmt">
                <button type="button" class="t-btn" @click="openLinkModal" title="Chèn liên kết">
                  <i class="fa-solid fa-link"></i>
                </button>
                <button type="button" class="t-btn" @click="openImageModal" title="Chèn hình ảnh từ thư viện">
                  <i class="fa-solid fa-image"></i>
                </button>
                <button type="button" class="t-btn" @click="openVideoModal" title="Chèn video YouTube / Vimeo an toàn">
                  <i class="fa-solid fa-video"></i>
                </button>
                <button type="button" class="t-btn" @click="insertTableTemplate" title="Chèn bảng dữ liệu">
                  <i class="fa-solid fa-table"></i>
                </button>
              </div>
            </div>

            <!-- Content Area / Split View / Preview Area -->
            <div class="editor-view-container" :class="'mode-' + desktopViewMode">
              <!-- Textarea Editor -->
              <div 
                v-show="desktopViewMode === 'edit' || desktopViewMode === 'split' || activeMobileTab === 'content'" 
                class="editor-pane"
              >
                <textarea 
                  ref="contentTextareaRef"
                  v-model="form.content" 
                  class="form-textarea content-textarea" 
                  rows="16" 
                  placeholder="Nhập nội dung bài viết chi tiết tại đây (hỗ trợ văn bản thuần, Markdown và HTML an toàn)..."
                  @input="markDirty"
                ></textarea>
              </div>

              <!-- Live Preview Pane -->
              <div 
                v-show="desktopViewMode === 'preview' || desktopViewMode === 'split' || activeMobileTab === 'preview'" 
                class="preview-pane"
              >
                <div class="preview-inner-box">
                  <div class="preview-badge-watermark">
                    <i class="fa-regular fa-eye"></i> Xem trước hiển thị trực tiếp
                  </div>
                  <div class="article-rendered-content" v-html="renderedContentHtml"></div>
                </div>
              </div>
            </div>

            <!-- Content Footer Metrics (Word count, Read time, Clear formatting) -->
            <div class="content-stats-footer">
              <div class="stats-left">
                <span><i class="fa-solid fa-file-word"></i> <strong>{{ wordCount }}</strong> từ</span>
                <span class="dot">•</span>
                <span><i class="fa-solid fa-font"></i> <strong>{{ charCount }}</strong> ký tự</span>
                <span class="dot">•</span>
                <span><i class="fa-regular fa-clock"></i> Khoảng <strong>{{ readingTimeMinutes }}</strong> phút đọc</span>
              </div>
              <div class="stats-right">
                <button type="button" class="btn-text-action" @click="clearFormatting" title="Xóa toàn bộ nội dung">
                  <i class="fa-regular fa-trash-can"></i> Xóa trắng
                </button>
              </div>
            </div>

            <div v-if="fieldErrors.content" class="field-error-text">
              <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.content }}
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: METADATA, FEATURED IMAGE & SEO SETTINGS -->
        <div 
          class="workspace-side" 
          :class="{ 'mob-hidden': activeMobileTab !== 'settings' }"
        >
          <!-- Card 1: Category & Featured Image & Alt Text -->
          <div class="side-card">
            <div class="side-card-header">
              <i class="fa-solid fa-sliders"></i>
              <h4>Phân Loại & Ảnh Bìa</h4>
            </div>

            <!-- Category Select -->
            <div class="form-group" :class="{ 'has-error': fieldErrors.category_id }">
              <label class="form-label required">Chuyên mục:</label>
              <select v-model="form.category_id" class="form-select" @change="markDirty">
                <option :value="null">-- Chọn chuyên mục --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
              <div v-if="fieldErrors.category_id" class="field-error-text">
                <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.category_id }}
              </div>
            </div>

            <!-- Featured Image URL with Media Picker Button -->
            <div class="form-group" :class="{ 'has-error': fieldErrors.featured_image }">
              <label class="form-label">Ảnh đại diện bài viết:</label>
              <div class="media-input-group">
                <input 
                  type="text" 
                  v-model="form.featured_image" 
                  class="form-input" 
                  placeholder="https://..."
                  @input="markDirty"
                />
                <button 
                  type="button" 
                  class="btn-pick-media" 
                  @click="openMediaLibraryForThumbnail"
                  title="Chọn ảnh từ Thư viện tài nguyên"
                >
                  <i class="fa-solid fa-photo-film"></i>
                  <span>Thư viện</span>
                </button>
              </div>
              <div v-if="fieldErrors.featured_image" class="field-error-text">
                <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.featured_image }}
              </div>
            </div>

            <!-- Image Alt Text (SEO & Accessibility) -->
            <div class="form-group" :class="{ 'has-error': fieldErrors.featured_image_alt }">
              <label class="form-label">
                <span>Alt Text SEO (Mô tả ảnh bìa):</span>
              </label>
              <input 
                type="text" 
                v-model="form.featured_image_alt" 
                class="form-input" 
                placeholder="VD: VinFast VF9 màu xanh chạy thử nghiệm trên đèo Hải Vân"
                maxlength="255"
                @input="markDirty"
              />
              <small class="form-hint">Mô tả nội dung hình ảnh phục vụ Google Tìm kiếm hình ảnh và người dùng khiếm thị.</small>
              <div v-if="fieldErrors.featured_image_alt" class="field-error-text">
                <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.featured_image_alt }}
              </div>
            </div>

            <!-- Image Thumbnail Preview -->
            <div v-if="form.featured_image" class="thumb-preview-box">
              <img 
                :src="form.featured_image" 
                :alt="form.featured_image_alt || 'Xem trước ảnh bìa'"
                @error="$event.target.src = '/assets/images/default-car.png'"
              />
              <button type="button" class="btn-remove-thumb" @click="form.featured_image = ''; form.featured_image_alt = ''; markDirty()">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <!-- Scheduled Publication Date (Only for publishers or review) -->
            <div class="form-group mt-3" v-if="permissions.can_publish">
              <label class="form-label">Thời điểm xuất bản (Lên lịch):</label>
              <input 
                type="datetime-local" 
                v-model="form.published_at" 
                class="form-input"
                @input="markDirty"
              />
              <small class="form-hint">Để trống để xuất bản ngay lập tức khi bấm Xuất bản.</small>
            </div>
          </div>

          <!-- Card 2: SEO Settings & Google SERP Preview -->
          <div class="side-card">
            <div class="side-card-header">
              <i class="fa-solid fa-magnifying-glass-chart"></i>
              <h4>Tối Ưu Hóa SEO (Google Snippet)</h4>
            </div>

            <!-- Meta Title -->
            <div class="form-group" :class="{ 'has-error': fieldErrors.meta_title }">
              <label class="form-label">
                <span>Tiêu đề SEO (Meta Title):</span>
                <span class="char-count" :class="{ 'text-warning': form.meta_title && form.meta_title.length > 60 }">
                  {{ (form.meta_title || '').length }}/60
                </span>
              </label>
              <input 
                type="text" 
                v-model="form.meta_title" 
                class="form-input" 
                placeholder="Tiêu đề hiển thị trên kết quả tìm kiếm Google..."
                maxlength="255"
                @input="markDirty"
              />
              <div v-if="fieldErrors.meta_title" class="field-error-text">
                <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.meta_title }}
              </div>
            </div>

            <!-- Meta Description -->
            <div class="form-group" :class="{ 'has-error': fieldErrors.meta_description }">
              <label class="form-label">
                <span>Mô tả SEO (Meta Description):</span>
                <span class="char-count" :class="{ 'text-warning': form.meta_description && form.meta_description.length > 160 }">
                  {{ (form.meta_description || '').length }}/160
                </span>
              </label>
              <textarea 
                v-model="form.meta_description" 
                class="form-textarea" 
                rows="3" 
                placeholder="Mô tả tóm tắt hấp dẫn thu hút người tìm kiếm nhấp vào bài viết..."
                maxlength="500"
                @input="markDirty"
              ></textarea>
              <div v-if="fieldErrors.meta_description" class="field-error-text">
                <i class="fa-solid fa-circle-exclamation"></i> {{ fieldErrors.meta_description }}
              </div>
            </div>

            <!-- Meta Keywords -->
            <div class="form-group">
              <label class="form-label">Từ khóa SEO (Keywords):</label>
              <input 
                type="text" 
                v-model="form.meta_keywords" 
                class="form-input" 
                placeholder="thuê xe vinfast, cẩm nang xe điện, lái xe an toàn"
                maxlength="255"
                @input="markDirty"
              />
            </div>

            <!-- Google SERP Simulation Preview -->
            <div class="google-serp-preview">
              <span class="serp-tag">Mô phỏng hiển thị trên Google:</span>
              <div class="serp-box">
                <div class="serp-url">
                  https://carrental.vn/blog/{{ form.slug || 'duong-dan-bai-viet' }}
                </div>
                <div class="serp-title">
                  {{ form.meta_title || form.title || 'Tiêu đề bài viết hiển thị trên Google' }}
                </div>
                <div class="serp-desc">
                  {{ form.meta_description || form.summary || 'Đoạn mô tả ngắn gọn tóm tắt nội dung bài viết hiển thị trên kết quả tìm kiếm Google nhằm thúc đẩy tỉ lệ nhấp chuột...' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. BOTTOM ACTION FOOTER -->
      <footer class="editor-footer">
        <div class="footer-left">
          <button type="button" class="btn-cancel" @click="handleAttemptClose" :disabled="submitting">
            Hủy & Đóng
          </button>
        </div>

        <div class="footer-right">
          <!-- 1. Lưu bản nháp (Save Draft) -->
          <button 
            type="button" 
            class="btn-save-draft" 
            @click="submitPost('save_draft')" 
            :disabled="submitting"
          >
            <i class="fa-solid fa-floppy-disk"></i>
            <span>{{ isEditing ? 'Lưu cập nhật' : 'Lưu bản nháp' }}</span>
          </button>

          <!-- 2. Gửi duyệt (Submit for Review) - for Authors -->
          <button 
            type="button" 
            class="btn-submit-review" 
            @click="submitPost('submit_review')" 
            :disabled="submitting"
          >
            <i class="fa-solid fa-paper-plane"></i>
            <span>Gửi duyệt</span>
          </button>

          <!-- 3. Xuất bản (Publish Now) - for Publishers -->
          <button 
            v-if="permissions.can_publish" 
            type="button" 
            class="btn-publish-now" 
            @click="submitPost('publish')" 
            :disabled="submitting"
          >
            <i class="fa-solid fa-check-double"></i>
            <span>{{ form.status === 'published' ? 'Cập nhật xuất bản' : 'Xuất bản ngay' }}</span>
          </button>
        </div>
      </footer>
    </div>

    <!-- ========================================================
         MODAL: MEDIA PICKER INTEGRATION (THƯ VIỆN TÀI NGUYÊN)
         ======================================================== -->
    <div v-if="showMediaPickerModal" class="sub-modal-overlay" @click.self="showMediaPickerModal = false">
      <div class="sub-modal-card media-picker-modal animate-in">
        <div class="sub-modal-header">
          <div class="sub-modal-icon"><i class="fa-solid fa-photo-film"></i></div>
          <div>
            <h4>Chọn Hình Ảnh Từ Thư Viện Tài Nguyên</h4>
            <p class="sub-text">Kho tài nguyên hình ảnh xe và truyền thông chuẩn hệ thống</p>
          </div>
          <button type="button" class="btn-close-sub" @click="showMediaPickerModal = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="sub-modal-body">
          <!-- Filter bar -->
          <div class="picker-filter-bar">
            <div class="picker-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input 
                type="text" 
                v-model="mediaSearchQuery" 
                placeholder="Tìm ảnh theo tên hoặc alt text..."
                @input="debouncedFetchMedia"
              />
            </div>
            <select v-model="mediaFilterCollection" class="picker-select" @change="fetchMediaAssets(1)">
              <option value="all">Tất cả nhóm</option>
              <option value="cars">Xe & Ngoại thất</option>
              <option value="marketing">Marketing & Banner</option>
              <option value="general">Tài nguyên chung</option>
            </select>
          </div>

          <!-- Loading state -->
          <div v-if="loadingMedia" class="picker-loading">
            <i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh sách tài nguyên...
          </div>

          <!-- Empty state -->
          <div v-else-if="mediaAssets.length === 0" class="picker-empty">
            <i class="fa-regular fa-image"></i>
            <p>Không tìm thấy hình ảnh phù hợp trong thư viện.</p>
          </div>

          <!-- Media Grid -->
          <div v-else class="picker-media-grid">
            <div 
              v-for="asset in mediaAssets" 
              :key="asset.id" 
              class="media-item-box"
              @click="handleSelectMediaAsset(asset)"
            >
              <img :src="asset.display_url" :alt="asset.alt_text || asset.original_name" />
              <div class="media-hover-overlay">
                <i class="fa-solid fa-check"></i>
                <span>Chọn ảnh này</span>
              </div>
              <div class="media-meta-tag">
                <span class="file-name">{{ asset.original_name }}</span>
                <span v-if="asset.alt_text" class="alt-flag">Alt: {{ asset.alt_text }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="sub-modal-footer">
          <button type="button" class="btn-cancel" @click="showMediaPickerModal = false">Đóng</button>
        </div>
      </div>
    </div>

    <!-- ========================================================
         MODAL: INSERT LINK
         ======================================================== -->
    <div v-if="showLinkModal" class="sub-modal-overlay" @click.self="showLinkModal = false">
      <div class="sub-modal-card mini-modal animate-in">
        <div class="sub-modal-header">
          <div class="sub-modal-icon"><i class="fa-solid fa-link"></i></div>
          <div>
            <h4>Chèn Liên Kết</h4>
            <p class="sub-text">Hỗ trợ giao thức an toàn http, https, mailto, tel</p>
          </div>
          <button type="button" class="btn-close-sub" @click="showLinkModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sub-modal-body">
          <div class="form-group">
            <label class="form-label required">Văn bản hiển thị:</label>
            <input type="text" v-model="linkForm.text" class="form-input" placeholder="VD: Bảng giá thuê xe điện VinFast" />
          </div>
          <div class="form-group mt-3">
            <label class="form-label required">Địa chỉ URL:</label>
            <input type="text" v-model="linkForm.url" class="form-input" placeholder="https://carrental.vn/bang-gia" />
          </div>
          <div class="checkbox-row mt-3">
            <label class="custom-checkbox">
              <input type="checkbox" v-model="linkForm.openInNewTab" />
              <span>Mở trong tab mới (target="_blank" kèm rel="noopener noreferrer")</span>
            </label>
          </div>
        </div>
        <div class="sub-modal-footer">
          <button type="button" class="btn-cancel" @click="showLinkModal = false">Hủy</button>
          <button type="button" class="btn-primary-confirm" @click="confirmInsertLink">Chèn liên kết</button>
        </div>
      </div>
    </div>

    <!-- ========================================================
         MODAL: INSERT VIDEO (YOUTUBE / VIMEO SANITIZED)
         ======================================================== -->
    <div v-if="showVideoModal" class="sub-modal-overlay" @click.self="showVideoModal = false">
      <div class="sub-modal-card mini-modal animate-in">
        <div class="sub-modal-header">
          <div class="sub-modal-icon"><i class="fa-solid fa-video"></i></div>
          <div>
            <h4>Chèn Video An Toàn</h4>
            <p class="sub-text">Hệ thống áp dụng Allowlist bảo vệ: chỉ cho phép YouTube & Vimeo</p>
          </div>
          <button type="button" class="btn-close-sub" @click="showVideoModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sub-modal-body">
          <div class="form-group">
            <label class="form-label required">Đường dẫn Video YouTube hoặc Vimeo:</label>
            <input 
              type="text" 
              v-model="videoForm.url" 
              class="form-input" 
              placeholder="VD: https://www.youtube.com/watch?v=dQw4w9WgXcQ hoặc https://vimeo.com/..." 
            />
            <small class="form-hint">Nhập link xem thông thường hoặc link embed. Hệ thống sẽ tự động chuyển đổi sang mã nhúng an toàn.</small>
          </div>
        </div>
        <div class="sub-modal-footer">
          <button type="button" class="btn-cancel" @click="showVideoModal = false">Hủy</button>
          <button type="button" class="btn-primary-confirm" @click="confirmInsertVideo">Chèn video</button>
        </div>
      </div>
    </div>

    <!-- ========================================================
         MODAL: UNSAVED CHANGES CONFIRMATION WARNING
         ======================================================== -->
    <div v-if="showUnsavedWarning" class="sub-modal-overlay" @click.self="showUnsavedWarning = false">
      <div class="sub-modal-card warning-dialog animate-in">
        <div class="warning-icon-badge">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h4 class="warning-title">Bạn Có Thay Đổi Chưa Lưu!</h4>
        <p class="warning-desc">
          Nội dung bài viết đã có chỉnh sửa nhưng chưa được lưu vào máy chủ. Nếu rời đi bây giờ, những thay đổi này có thể bị mất.
        </p>
        <div class="warning-actions">
          <button type="button" class="btn-cancel" @click="showUnsavedWarning = false">
            Tiếp tục chỉnh sửa
          </button>
          <button type="button" class="btn-danger-confirm" @click="forceCloseEditor">
            Bỏ qua & Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================
         MODAL: CONFLICT RESOLUTION (OPTIMISTIC LOCKING 409)
         ======================================================== -->
    <div v-if="conflictData" class="sub-modal-overlay" @click.self="conflictData = null">
      <div class="sub-modal-card conflict-dialog animate-in">
        <div class="conflict-icon-badge">
          <i class="fa-solid fa-code-compare"></i>
        </div>
        <h4 class="warning-title">Xung Đột Sửa Đổi Bài Viết!</h4>
        <p class="warning-desc">
          Bài viết này đã được một quản trị viên khác lưu thay đổi kể từ lần cuối bạn tải dữ liệu.
        </p>
        <div class="conflict-meta-box">
          <div><strong>Thời điểm lưu mới nhất trên máy chủ:</strong> {{ conflictData.server_updated_at }}</div>
        </div>
        <div class="conflict-actions">
          <button type="button" class="btn-secondary" @click="reloadLatestPostFromServer">
            <i class="fa-solid fa-rotate"></i> Tải lại bài mới nhất từ máy chủ
          </button>
          <button type="button" class="btn-warning-action" @click="forceOverwriteConflict">
            <i class="fa-solid fa-bolt"></i> Ghi đè có chủ đích (Force Overwrite)
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================
         MODAL: PRIVATE PREVIEW (XEM TRƯỚC RIÊNG TƯ CHUẨN THIẾT BỊ)
         ======================================================== -->
    <div v-if="showPrivatePreviewModal" class="preview-modal-overlay" @click.self="showPrivatePreviewModal = false">
      <div class="preview-modal-container animate-in">
        <div class="preview-modal-topbar">
          <div class="preview-title-wrap">
            <span class="preview-watermark-tag">
              <i class="fa-solid fa-shield-halved"></i> XEM TRƯỚC NỘI BỘ (CHƯA XUẤT BẢN)
            </span>
            <h4>{{ form.title || 'Bài viết chưa có tiêu đề' }}</h4>
          </div>

          <!-- Device viewport toggle -->
          <div class="viewport-toggle-group">
            <button 
              type="button" 
              class="vp-btn" 
              :class="{ 'active': previewViewport === 'desktop' }"
              @click="previewViewport = 'desktop'"
              title="Giao diện máy tính"
            >
              <i class="fa-solid fa-desktop"></i>
            </button>
            <button 
              type="button" 
              class="vp-btn" 
              :class="{ 'active': previewViewport === 'tablet' }"
              @click="previewViewport = 'tablet'"
              title="Giao diện máy tính bảng"
            >
              <i class="fa-solid fa-tablet-screen-button"></i>
            </button>
            <button 
              type="button" 
              class="vp-btn" 
              :class="{ 'active': previewViewport === 'mobile' }"
              @click="previewViewport = 'mobile'"
              title="Giao diện điện thoại"
            >
              <i class="fa-solid fa-mobile-screen"></i>
            </button>
          </div>

          <button type="button" class="btn-close-preview" @click="showPrivatePreviewModal = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="preview-viewport-wrap" :class="'vp-' + previewViewport">
          <div class="preview-viewport-content">
            <div class="preview-article-header">
              <span class="preview-category">{{ getCategoryName(form.category_id) }}</span>
              <h1 class="preview-h1">{{ form.title || 'Tiêu đề bài viết' }}</h1>
              
              <div class="preview-meta-row">
                <span><i class="fa-regular fa-user"></i> Quản trị viên</span>
                <span class="dot">•</span>
                <span><i class="fa-regular fa-clock"></i> {{ readingTimeMinutes }} phút đọc</span>
                <span class="dot">•</span>
                <span><i class="fa-regular fa-calendar"></i> {{ form.published_at || 'Bản nháp riêng tư' }}</span>
              </div>
            </div>

            <!-- Featured Image -->
            <div v-if="form.featured_image" class="preview-featured-img">
              <img :src="form.featured_image" :alt="form.featured_image_alt || form.title" />
              <div v-if="form.featured_image_alt" class="preview-img-caption">
                <i class="fa-solid fa-camera"></i> {{ form.featured_image_alt }}
              </div>
            </div>

            <!-- Excerpt -->
            <div v-if="form.summary" class="preview-summary-box">
              {{ form.summary }}
            </div>

            <!-- Main Content -->
            <div class="preview-body-html" v-html="renderedContentHtml"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import baseRequest from '../../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  postId: {
    type: Number,
    default: null,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  permissions: {
    type: Object,
    default: () => ({
      can_create: true,
      can_review: true,
      can_publish: true,
      can_delete: true,
    }),
  },
});

const emit = defineEmits(['update:visible', 'saved', 'close']);

// -------------------------------------------------------------
// Form Data State
// -------------------------------------------------------------
const isEditing = computed(() => !!props.postId);
const contentTextareaRef = ref(null);

const form = reactive({
  title: '',
  slug: '',
  summary: '',
  content: '',
  category_id: null,
  featured_image: '',
  featured_image_alt: '',
  published_at: '',
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  status: 'draft',
  revision_notes: '',
});

// UI & Layout States
const activeMobileTab = ref('content'); // 'content' | 'settings' | 'preview'
const desktopViewMode = ref('edit'); // 'edit' | 'split' | 'preview'
const isCustomSlug = ref(false);
const submitting = ref(false);
const fieldErrors = reactive({});
const hasPendingRevision = ref(false);

// Save State & Optimistic Locking
const isDirty = ref(false);
const saveState = ref('saved'); // 'saved' | 'dirty' | 'saving' | 'conflict' | 'error'
const lastSavedAt = ref(null);
const currentUpdatedAt = ref(null);
let latestSaveRequestId = 0;
let autosaveTimer = null;

// Modal States
const showUnsavedWarning = ref(false);
const conflictData = ref(null);
const showPrivatePreviewModal = ref(false);
const previewViewport = ref('desktop');

// Sub-modals for Formatting
const showMediaPickerModal = ref(false);
const mediaTarget = ref('featured'); // 'featured' | 'content'
const mediaAssets = ref([]);
const loadingMedia = ref(false);
const mediaSearchQuery = ref('');
const mediaFilterCollection = ref('all');
let mediaDebounceTimer = null;

const showLinkModal = ref(false);
const linkForm = reactive({ text: '', url: '', openInNewTab: true });

const showVideoModal = ref(false);
const videoForm = reactive({ url: '' });

// -------------------------------------------------------------
// Computed Metrics & Formatting
// -------------------------------------------------------------
const lastSavedText = computed(() => {
  if (!lastSavedAt.value) return '';
  const d = new Date(lastSavedAt.value);
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
});

const isEditingPublishedWithoutPublishPerm = computed(() => {
  return isEditing.value && form.status === 'published' && !props.permissions?.can_publish;
});

const wordCount = computed(() => {
  const text = (form.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  if (!text) return 0;
  return text.split(' ').filter(Boolean).length;
});

const charCount = computed(() => {
  return (form.content || '').length;
});

const readingTimeMinutes = computed(() => {
  return Math.max(1, Math.ceil(wordCount.value / 200));
});

// Simple client-side Markdown / HTML renderer for live preview
const renderedContentHtml = computed(() => {
  let raw = form.content || '';
  if (!raw.trim()) {
    return '<p class="text-muted"><em>Chưa có nội dung để hiển thị xem trước...</em></p>';
  }

  // Convert basic markdown elements if user types in markdown format
  let html = raw;
  // Headings
  html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
  html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
  html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
  // Bold & Italic
  html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
  html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');
  html = html.replace(/~~(.*?)~~/gim, '<del>$1</del>');
  // Blockquotes
  html = html.replace(/^\> (.*$)/gim, '<blockquote>$1</blockquote>');
  // Divider
  html = html.replace(/^---$/gim, '<hr />');

  return html;
});

const getCategoryName = (catId) => {
  if (!catId) return 'Chưa phân loại';
  const c = props.categories.find(item => item.id === catId);
  return c ? c.name : 'Chuyên mục';
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'published': return 'Đã xuất bản';
    case 'pending_review': return 'Chờ duyệt';
    case 'archived': return 'Lưu trữ';
    default: return 'Bản nháp';
  }
};

const getStatusIcon = (status) => {
  switch (status) {
    case 'published': return 'fa-solid fa-circle-check';
    case 'pending_review': return 'fa-solid fa-clock-rotate-left';
    case 'archived': return 'fa-solid fa-box-archive';
    default: return 'fa-solid fa-pen-ruler';
  }
};

// -------------------------------------------------------------
// Slug Generation Logic
// -------------------------------------------------------------
const toSlug = (str) => {
  if (!str) return '';
  return str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
};

const onTitleInput = () => {
  markDirty();
  if (!isCustomSlug.value) {
    form.slug = toSlug(form.title);
  }
};

const regenerateSlug = () => {
  form.slug = toSlug(form.title);
  isCustomSlug.value = false;
  markDirty();
};

const markDirty = () => {
  isDirty.value = true;
  saveState.value = 'dirty';
  triggerAutosaveDebounce();
};

// -------------------------------------------------------------
// Fetch Single Post for Editing
// -------------------------------------------------------------
const fetchPostDetails = async (id) => {
  try {
    const res = await baseRequest.get(`/admin/posts/${id}`);
    if (res.data && res.data.status === 1) {
      const p = res.data.data;
      form.title = p.title || '';
      form.slug = p.slug || '';
      form.summary = p.summary || '';
      form.content = p.content || '';
      form.category_id = p.category_id || null;
      form.featured_image = p.featured_image || '';
      form.featured_image_alt = p.featured_image_alt || '';
      form.published_at = p.published_at ? p.published_at.substring(0, 16) : '';
      form.meta_title = p.meta_title || '';
      form.meta_description = p.meta_description || '';
      form.meta_keywords = p.meta_keywords || '';
      form.status = p.status || 'draft';
      hasPendingRevision.value = !!p.has_pending_revision;

      currentUpdatedAt.value = p.updated_at;
      isCustomSlug.value = true;
      isDirty.value = false;
      saveState.value = 'saved';
      lastSavedAt.value = new Date(p.updated_at);
    }
  } catch (err) {
    console.error('Fetch post error:', err);
    toaster.error('Không thể tải thông tin bài viết.');
  }
};

// -------------------------------------------------------------
// Reset Form
// -------------------------------------------------------------
const resetForm = () => {
  form.title = '';
  form.slug = '';
  form.summary = '';
  form.content = '';
  form.category_id = null;
  form.featured_image = '';
  form.featured_image_alt = '';
  form.published_at = '';
  form.meta_title = '';
  form.meta_description = '';
  form.meta_keywords = '';
  form.status = 'draft';
  form.revision_notes = '';
  hasPendingRevision.value = false;
  currentUpdatedAt.value = null;
  isCustomSlug.value = false;
  isDirty.value = false;
  saveState.value = 'saved';
  lastSavedAt.value = null;
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
};

// Watch for visibility & postId changes
watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.postId) {
      fetchPostDetails(props.postId);
    } else {
      resetForm();
    }
  } else {
    clearTimeout(autosaveTimer);
  }
}, { immediate: true });

// -------------------------------------------------------------
// Autosave with Out-of-Order Response Protection
// -------------------------------------------------------------
const triggerAutosaveDebounce = () => {
  clearTimeout(autosaveTimer);

  // QUY TẮC AN TOÀN: CHỈ AUTOSAVE BẢN NHÁP (DRAFT)
  // Tuyệt đối không autosave đè lên bài đang Published hoặc Pending Review
  if (form.status !== 'draft') {
    return;
  }

  if (!form.title.trim()) {
    return;
  }

  autosaveTimer = setTimeout(() => {
    executeAutosave();
  }, 20000); // 20 giây sau lần gõ cuối cùng
};

const executeAutosave = async () => {
  if (form.status !== 'draft' || !isDirty.value || !form.title.trim() || submitting.value) {
    return;
  }

  const thisRequestId = ++latestSaveRequestId;
  saveState.value = 'saving';

  try {
    const payload = buildPayload('save_draft');
    payload.expected_updated_at = currentUpdatedAt.value;

    let res;
    if (isEditing.value) {
      res = await baseRequest.put(`/admin/posts/${props.postId}`, payload);
    } else {
      res = await baseRequest.post('/admin/posts', payload);
    }

    // CHỐNG RESPONSE CŨ GHI ĐÈ BẢN MỚI
    if (thisRequestId < latestSaveRequestId) {
      console.warn('Ignored outdated autosave response', thisRequestId, latestSaveRequestId);
      return;
    }

    if (res.data && res.data.status === 1) {
      saveState.value = 'saved';
      lastSavedAt.value = new Date();
      isDirty.value = false;

      if (res.data.data?.updated_at) {
        currentUpdatedAt.value = res.data.data.updated_at;
      }

      // Nếu vừa tạo mới thành công ở bản nháp đầu tiên, emit để parent biết
      if (!isEditing.value && res.data.data?.id) {
        emit('saved', res.data.data);
      }
    }
  } catch (err) {
    if (thisRequestId === latestSaveRequestId) {
      if (err.response?.status === 409) {
        saveState.value = 'conflict';
        conflictData.value = err.response.data;
      } else {
        saveState.value = 'error';
      }
    }
  }
};

// -------------------------------------------------------------
// Submit Post (Manual Save / Review / Publish)
// -------------------------------------------------------------
const buildPayload = (action) => {
  return {
    title: form.title.trim(),
    slug: form.slug.trim(),
    summary: form.summary ? form.summary.trim() : null,
    content: form.content,
    featured_image: form.featured_image ? form.featured_image.trim() : null,
    featured_image_alt: form.featured_image_alt ? form.featured_image_alt.trim() : null,
    category_id: form.category_id,
    action: action,
    published_at: form.published_at || null,
    meta_title: form.meta_title ? form.meta_title.trim() : null,
    meta_description: form.meta_description ? form.meta_description.trim() : null,
    meta_keywords: form.meta_keywords ? form.meta_keywords.trim() : null,
    revision_notes: form.revision_notes || null,
  };
};

const validateClient = () => {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
  let isValid = true;

  if (!form.title.trim()) {
    fieldErrors.title = 'Vui lòng nhập tiêu đề bài viết.';
    isValid = false;
  }

  if (!form.content.trim()) {
    fieldErrors.content = 'Vui lòng nhập nội dung bài viết.';
    isValid = false;
  }

  if (!isValid) {
    toaster.warning('Vui lòng điền đầy đủ các thông tin bắt buộc.');
  }

  return isValid;
};

const submitPost = async (action, forceOverwrite = false) => {
  if (!validateClient()) return;

  clearTimeout(autosaveTimer);
  submitting.value = true;
  saveState.value = 'saving';
  const thisRequestId = ++latestSaveRequestId;

  try {
    const payload = buildPayload(action);
    if (isEditing.value) {
      payload.expected_updated_at = currentUpdatedAt.value;
      if (forceOverwrite) {
        payload.force_overwrite = true;
      }
    }

    let res;
    if (isEditing.value) {
      res = await baseRequest.put(`/admin/posts/${props.postId}`, payload);
    } else {
      res = await baseRequest.post('/admin/posts', payload);
    }

    if (thisRequestId < latestSaveRequestId) {
      return;
    }

    if (res.data && res.data.status === 1) {
      saveState.value = 'saved';
      lastSavedAt.value = new Date();
      isDirty.value = false;
      conflictData.value = null;

      toaster.success(res.data.message || 'Thao tác bài viết thành công!');
      emit('saved', res.data.data);
      emit('update:visible', false);
      emit('close');
    } else {
      toaster.error(res.data.message || 'Có lỗi xảy ra khi lưu bài viết.');
      saveState.value = 'error';
    }
  } catch (err) {
    if (err.response?.status === 409) {
      // 409 Conflict: Optimistic Locking
      saveState.value = 'conflict';
      conflictData.value = err.response.data;
      toaster.error(err.response.data.message || 'Xung đột phiên làm việc!');
    } else if (err.response?.status === 422) {
      // Validation error
      saveState.value = 'dirty';
      const errors = err.response.data.errors || {};
      Object.keys(errors).forEach(key => {
        fieldErrors[key] = errors[key][0];
      });
      toaster.error('Dữ liệu không hợp lệ. Vui lòng kiểm tra các trường báo đỏ.');
    } else if (err.response?.status === 403) {
      saveState.value = 'dirty';
      toaster.error(err.response.data.message || 'Bạn không có quyền thực hiện thao tác này!');
    } else {
      saveState.value = 'error';
      toaster.error('Không thể kết nối đến máy chủ. Vui lòng thử lại.');
    }
  } finally {
    submitting.value = false;
  }
};

// -------------------------------------------------------------
// Conflict Resolution Actions
// -------------------------------------------------------------
const reloadLatestPostFromServer = async () => {
  conflictData.value = null;
  if (props.postId) {
    await fetchPostDetails(props.postId);
    toaster.info('Đã tải lại nội dung mới nhất từ máy chủ.');
  }
};

const forceOverwriteConflict = async () => {
  await submitPost('save_draft', true);
};

// -------------------------------------------------------------
// Close Handling & Unsaved Changes Warning
// -------------------------------------------------------------
const handleAttemptClose = () => {
  if (isDirty.value) {
    showUnsavedWarning.value = true;
  } else {
    forceCloseEditor();
  }
};

const forceCloseEditor = () => {
  showUnsavedWarning.value = false;
  emit('update:visible', false);
  emit('close');
};

const handleBeforeUnload = (e) => {
  if (isDirty.value) {
    e.preventDefault();
    e.returnValue = 'Bạn có thay đổi chưa lưu. Bạn có chắc muốn thoát?';
  }
};

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  clearTimeout(autosaveTimer);
  window.removeEventListener('beforeunload', handleBeforeUnload);
});

// -------------------------------------------------------------
// Media Library Integration
// -------------------------------------------------------------
const openMediaLibraryForThumbnail = () => {
  mediaTarget.value = 'featured';
  showMediaPickerModal.value = true;
  fetchMediaAssets(1);
};

const openImageModal = () => {
  mediaTarget.value = 'content';
  showMediaPickerModal.value = true;
  fetchMediaAssets(1);
};

const debouncedFetchMedia = () => {
  clearTimeout(mediaDebounceTimer);
  mediaDebounceTimer = setTimeout(() => {
    fetchMediaAssets(1);
  }, 350);
};

const fetchMediaAssets = async (page = 1) => {
  loadingMedia.value = true;
  try {
    const params = {
      page: page,
      per_page: 20,
      mime_type: 'image',
    };
    if (mediaSearchQuery.value.trim()) {
      params.search = mediaSearchQuery.value.trim();
    }
    if (mediaFilterCollection.value !== 'all') {
      params.collection = mediaFilterCollection.value;
    }

    const res = await baseRequest.get('/admin/media', { params });
    if (res.data && res.data.status === 1) {
      mediaAssets.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Fetch media error:', err);
  } finally {
    loadingMedia.value = false;
  }
};

const handleSelectMediaAsset = (asset) => {
  if (mediaTarget.value === 'featured') {
    form.featured_image = asset.display_url;
    if (asset.alt_text) {
      form.featured_image_alt = asset.alt_text;
    }
    markDirty();
    showMediaPickerModal.value = false;
    toaster.success('Đã chọn ảnh đại diện từ thư viện!');
  } else {
    // Insert into content at cursor
    const alt = asset.alt_text || asset.original_name || 'Hình ảnh';
    const imageTag = `\n<figure><img src="${asset.display_url}" alt="${alt}" loading="lazy" /><figcaption>${alt}</figcaption></figure>\n`;
    insertTextAtCursor(imageTag);
    showMediaPickerModal.value = false;
    toaster.success('Đã chèn ảnh vào nội dung bài viết!');
  }
};

// -------------------------------------------------------------
// Textarea Formatting Helpers
// -------------------------------------------------------------
const insertTextAtCursor = (textToInsert) => {
  const textarea = contentTextareaRef.value;
  if (!textarea) {
    form.content += textToInsert;
    markDirty();
    return;
  }

  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const val = form.content;

  form.content = val.substring(0, start) + textToInsert + val.substring(end);
  markDirty();

  setTimeout(() => {
    textarea.focus();
    textarea.setSelectionRange(start + textToInsert.length, start + textToInsert.length);
  }, 50);
};

const wrapSelection = (before, after, defaultText = '') => {
  const textarea = contentTextareaRef.value;
  if (!textarea) return;

  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const val = form.content;
  const selected = val.substring(start, end) || defaultText;

  const replacement = before + selected + after;
  form.content = val.substring(0, start) + replacement + val.substring(end);
  markDirty();

  setTimeout(() => {
    textarea.focus();
    textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
  }, 50);
};

const insertHeading = (level) => {
  const prefix = level === 'h2' ? '## ' : (level === 'h3' ? '### ' : '#### ');
  wrapSelection(`\n${prefix}`, '\n', 'Tiêu đề đề mục');
};

const insertBulletList = () => {
  wrapSelection('\n- ', '\n', 'Mục danh sách 1\n- Mục danh sách 2');
};

const insertNumberedList = () => {
  wrapSelection('\n1. ', '\n', 'Mục số 1\n2. Mục số 2');
};

const insertCodeBlock = () => {
  wrapSelection('\n```\n', '\n```\n', '// Khối mã lệnh hoặc trích dẫn kỹ thuật');
};

const insertHorizontalRule = () => {
  insertTextAtCursor('\n---\n');
};

const insertTableTemplate = () => {
  const tableTemplate = `\n| Tiêu chí | Thông số kỹ thuật | Ghi chú |\n| :--- | :--- | :--- |\n| Quãng đường / 1 lần sạc | 450 km (Chuẩn WLTP) | Tối ưu đường trường |\n| Thời gian sạc nhanh | 10% - 70% trong 24 phút | Trụ sạc siêu nhanh 250kW |\n| Trang bị an toàn | 11 Túi khí + ADAS Level 2 | Chuẩn 5 sao ASEAN NCAP |\n\n`;
  insertTextAtCursor(tableTemplate);
};

const clearFormatting = () => {
  if (confirm('Bạn có chắc chắn muốn xóa toàn bộ nội dung bài viết không?')) {
    form.content = '';
    markDirty();
  }
};

// -------------------------------------------------------------
// Link Modal
// -------------------------------------------------------------
const openLinkModal = () => {
  const textarea = contentTextareaRef.value;
  const selected = textarea ? form.content.substring(textarea.selectionStart, textarea.selectionEnd) : '';
  linkForm.text = selected || '';
  linkForm.url = '';
  linkForm.openInNewTab = true;
  showLinkModal.value = true;
};

const confirmInsertLink = () => {
  if (!linkForm.url.trim()) {
    toaster.warning('Vui lòng nhập địa chỉ liên kết URL.');
    return;
  }
  const text = linkForm.text.trim() || linkForm.url.trim();
  const targetAttr = linkForm.openInNewTab ? ' target="_blank" rel="noopener noreferrer"' : '';
  const linkTag = `<a href="${linkForm.url.trim()}"${targetAttr}>${text}</a>`;
  insertTextAtCursor(linkTag);
  showLinkModal.value = false;
};

// -------------------------------------------------------------
// Video Embed Modal (YouTube / Vimeo Whitelist Converter)
// -------------------------------------------------------------
const openVideoModal = () => {
  videoForm.url = '';
  showVideoModal.value = true;
};

const confirmInsertVideo = () => {
  const url = videoForm.url.trim();
  if (!url) {
    toaster.warning('Vui lòng nhập URL video.');
    return;
  }

  let embedUrl = '';
  // Check YouTube
  const ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
  if (ytMatch && ytMatch[1]) {
    embedUrl = `https://www.youtube.com/embed/${ytMatch[1]}`;
  }

  // Check Vimeo
  const vimeoMatch = url.match(/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/);
  if (vimeoMatch && vimeoMatch[1]) {
    embedUrl = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
  }

  if (!embedUrl) {
    toaster.error('Chỉ hỗ trợ video từ YouTube hoặc Vimeo theo chính sách bảo mật Allowlist!');
    return;
  }

  const iframeHtml = `\n<div class="video-responsive-embed">\n  <iframe src="${embedUrl}" width="100%" height="450" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>\n</div>\n`;
  insertTextAtCursor(iframeHtml);
  showVideoModal.value = false;
  toaster.success('Đã chèn video an toàn vào bài viết!');
};

// -------------------------------------------------------------
// Private Preview Trigger
// -------------------------------------------------------------
const openPrivatePreview = () => {
  showPrivatePreviewModal.value = true;
};
</script>

<style scoped>
/* Fullscreen Backdrop & Container */
.editor-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
}

.editor-modal-container {
  width: 100%;
  max-width: 1400px;
  height: 92vh;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

/* 1. Header */
.editor-header {
  padding: 16px 24px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-shrink: 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.editor-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-pill.published { background: #dcfce7; color: #15803d; }
.status-pill.pending_review { background: #fef3c7; color: #b45309; }
.status-pill.draft { background: #f1f5f9; color: #475569; }
.status-pill.archived { background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; }

.revision-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #e0f2fe;
  color: #0284c7;
}

.save-status-indicator {
  margin-top: 4px;
}

.state-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 500;
}

.state-saved { color: #16a34a; }
.state-saved .time-sub { color: #94a3b8; font-size: 0.72rem; }
.state-saving { color: #0284c7; }
.state-dirty { color: #d97706; }
.state-conflict { color: #dc2626; font-weight: 600; }
.state-error { color: #dc2626; }

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.mobile-tabs-bar {
  display: none;
}

.btn-header-preview {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: #f8fafc;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-header-preview:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

.btn-close-editor {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 16px;
}

.btn-close-editor:hover {
  background: #fee2e2;
  color: #ef4444;
}

/* 2. Warning alert */
.revision-warning-alert {
  padding: 10px 24px;
  background: #fffbeb;
  border-bottom: 1px solid #fef3c7;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.85rem;
}

.revision-warning-alert .alert-ico {
  color: #d97706;
  font-size: 18px;
  margin-top: 2px;
}

.revision-warning-alert p {
  margin: 2px 0 0;
  color: #78350f;
}

/* 3. Main Workspace */
.editor-workspace {
  flex: 1;
  display: flex;
  overflow: hidden;
  background: #f8fafc;
}

.workspace-main {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  border-right: 1px solid #e2e8f0;
}

.workspace-side {
  width: 380px;
  flex-shrink: 0;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  background: #ffffff;
}

/* Form inputs & groups */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.form-label.required::after {
  content: "*";
  color: #ef4444;
  margin-left: 4px;
}

.char-count {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: normal;
}

.form-input, .form-textarea, .form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #ffffff;
  color: #0f172a;
  font-size: 0.9rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-input:focus, .form-textarea:focus, .form-select:focus {
  outline: none;
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

.title-input {
  font-size: 1.15rem;
  font-weight: 600;
  padding: 12px 16px;
}

.has-error .form-input,
.has-error .form-textarea,
.has-error .form-select {
  border-color: #ef4444 !important;
  background: #fff5f5;
}

.field-error-text {
  font-size: 0.78rem;
  color: #ef4444;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

.slug-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.btn-regen-slug {
  border: none;
  background: none;
  color: #0284c7;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-regen-slug:hover { text-decoration: underline; }

.slug-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f1f5f9;
  overflow: hidden;
}

.slug-prefix {
  padding: 0 12px;
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 500;
  user-select: none;
}

.slug-input {
  border: none !important;
  border-radius: 0 !important;
  background: #ffffff !important;
}

/* Rich Toolbar */
.content-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.view-mode-tabs {
  display: flex;
  background: #e2e8f0;
  padding: 3px;
  border-radius: 8px;
  gap: 2px;
}

.mode-btn {
  border: none;
  background: none;
  padding: 5px 12px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.mode-btn.active {
  background: #ffffff;
  color: #0284c7;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.rich-toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 10px 10px 0 0;
  overflow-x: auto;
  white-space: nowrap;
}

.btn-group-fmt {
  display: flex;
  align-items: center;
  gap: 3px;
  border-right: 1px solid #cbd5e1;
  padding-right: 8px;
}

.btn-group-fmt:last-child {
  border-right: none;
  padding-right: 0;
}

.t-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid transparent;
  background: #ffffff;
  color: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.15s;
}

.t-btn:hover {
  background: #e0f2fe;
  color: #0284c7;
  border-color: #bae6fd;
}

/* Editor View Container */
.editor-view-container {
  display: flex;
  border: 1px solid #cbd5e1;
  border-top: none;
  border-radius: 0 0 10px 10px;
  background: #ffffff;
  min-height: 350px;
  overflow: hidden;
}

.editor-view-container.mode-split .editor-pane,
.editor-view-container.mode-split .preview-pane {
  width: 50%;
}

.editor-view-container.mode-split .editor-pane {
  border-right: 1px solid #e2e8f0;
}

.editor-pane {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.content-textarea {
  width: 100%;
  height: 100%;
  border: none !important;
  border-radius: 0 !important;
  padding: 16px;
  font-size: 0.95rem;
  line-height: 1.6;
  font-family: inherit;
  resize: vertical;
}

.preview-pane {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  background: #fafafa;
}

.preview-inner-box {
  background: #ffffff;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  min-height: 100%;
}

.preview-badge-watermark {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  color: #0284c7;
  background: #e0f2fe;
  padding: 3px 8px;
  border-radius: 4px;
  margin-bottom: 12px;
}

/* Content Stats Footer */
.content-stats-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 10px 10px;
  font-size: 0.78rem;
  color: #64748b;
}

.stats-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stats-left .dot { color: #cbd5e1; }

.btn-text-action {
  border: none;
  background: none;
  color: #94a3b8;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-text-action:hover { color: #ef4444; }

/* Side Cards */
.side-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.side-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #0f172a;
}

.side-card-header i { color: #0284c7; }
.side-card-header h4 { margin: 0; font-size: 0.95rem; font-weight: 700; }

.media-input-group {
  display: flex;
  gap: 8px;
}

.btn-pick-media {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 14px;
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.btn-pick-media:hover {
  background: #dcfce7;
  border-color: #86efac;
}

.form-hint {
  font-size: 0.72rem;
  color: #94a3b8;
  line-height: 1.4;
}

.thumb-preview-box {
  position: relative;
  width: 100%;
  height: 140px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.thumb-preview-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-remove-thumb {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: rgba(0,0,0,0.6);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.btn-remove-thumb:hover { background: #ef4444; }

/* Google SERP Preview Box */
.google-serp-preview {
  margin-top: 6px;
}

.serp-tag {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.serp-box {
  margin-top: 6px;
  padding: 12px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-family: Arial, sans-serif;
}

.serp-url {
  font-size: 0.75rem;
  color: #202124;
  margin-bottom: 2px;
  word-break: break-all;
}

.serp-title {
  font-size: 0.95rem;
  color: #1a0dab;
  font-weight: 400;
  line-height: 1.3;
  margin-bottom: 4px;
}

.serp-desc {
  font-size: 0.8rem;
  color: #4d5156;
  line-height: 1.4;
}

/* 4. Bottom Footer Actions */
.editor-footer {
  padding: 14px 24px;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-shrink: 0;
}

.footer-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-cancel {
  padding: 10px 18px;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel:hover { background: #e2e8f0; }

.btn-save-draft {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-save-draft:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-submit-review {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
  transition: all 0.2s;
}

.btn-submit-review:hover {
  background: linear-gradient(135deg, #d97706, #b45309);
  transform: translateY(-1px);
}

.btn-publish-now {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: linear-gradient(135deg, #0284c7, #0369a1);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
  transition: all 0.2s;
}

.btn-publish-now:hover {
  background: linear-gradient(135deg, #0369a1, #075985);
  transform: translateY(-1px);
}

/* Sub-modals (Media Picker, Link, Video, Conflict) */
.sub-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.sub-modal-card {
  width: 100%;
  max-width: 500px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sub-modal-card.media-picker-modal {
  max-width: 900px;
  height: 80vh;
}

.sub-modal-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.sub-modal-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #e0f2fe;
  color: #0284c7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.sub-modal-header h4 { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a; }
.sub-modal-header .sub-text { margin: 2px 0 0; font-size: 0.78rem; color: #64748b; }

.btn-close-sub {
  margin-left: auto;
  border: none;
  background: none;
  color: #94a3b8;
  font-size: 16px;
  cursor: pointer;
}

.btn-close-sub:hover { color: #0f172a; }

.sub-modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.sub-modal-footer {
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.btn-primary-confirm {
  padding: 8px 18px;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* Media Picker Grid */
.picker-filter-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.picker-search {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0 12px;
}

.picker-search input {
  border: none;
  background: none;
  width: 100%;
  padding: 8px 0;
  font-size: 0.85rem;
  outline: none;
}

.picker-select {
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
}

.picker-media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}

.media-item-box {
  position: relative;
  aspect-ratio: 16 / 10;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  cursor: pointer;
}

.media-item-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s;
}

.media-item-box:hover img { transform: scale(1.05); }

.media-hover-overlay {
  position: absolute;
  inset: 0;
  background: rgba(2, 132, 199, 0.8);
  color: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  opacity: 0;
  transition: opacity 0.2s;
}

.media-item-box:hover .media-hover-overlay { opacity: 1; }

.media-meta-tag {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(15, 23, 42, 0.7);
  padding: 4px 8px;
  color: #ffffff;
  font-size: 0.7rem;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.picker-loading, .picker-empty {
  padding: 40px;
  text-align: center;
  color: #64748b;
}

/* Warnings & Conflict Dialogs */
.warning-dialog, .conflict-dialog {
  text-align: center;
  padding: 24px;
  max-width: 440px;
}

.warning-icon-badge {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: #fef3c7;
  color: #d97706;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
}

.conflict-icon-badge {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
}

.warning-title {
  margin: 0 0 8px;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.warning-desc {
  margin: 0 0 20px;
  font-size: 0.88rem;
  color: #64748b;
  line-height: 1.5;
}

.warning-actions, .conflict-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.btn-danger-confirm {
  padding: 10px 18px;
  background: #ef4444;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

/* Private Preview Modal */
.preview-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10001;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
}

.preview-modal-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.preview-modal-topbar {
  padding: 12px 24px;
  background: #0f172a;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.preview-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.preview-watermark-tag {
  background: #ef4444;
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 4px;
  letter-spacing: 0.5px;
}

.preview-title-wrap h4 {
  margin: 0;
  font-size: 0.95rem;
  max-width: 450px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.viewport-toggle-group {
  display: flex;
  background: #1e293b;
  padding: 3px;
  border-radius: 8px;
  gap: 2px;
}

.vp-btn {
  border: none;
  background: none;
  color: #94a3b8;
  width: 36px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vp-btn.active { background: #0284c7; color: #ffffff; }

.btn-close-preview {
  border: none;
  background: none;
  color: #94a3b8;
  font-size: 20px;
  cursor: pointer;
}

.btn-close-preview:hover { color: #ffffff; }

.preview-viewport-wrap {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  justify-content: center;
}

.preview-viewport-content {
  background: #ffffff;
  border-radius: 12px;
  padding: 36px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  transition: width 0.3s;
}

.vp-desktop .preview-viewport-content { width: 1000px; }
.vp-tablet .preview-viewport-content { width: 720px; }
.vp-mobile .preview-viewport-content { width: 380px; padding: 20px; }

.preview-category {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #0284c7;
  letter-spacing: 0.5px;
}

.preview-h1 {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  margin: 10px 0 14px;
  line-height: 1.3;
}

.preview-meta-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 24px;
}

.preview-featured-img {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 24px;
}

.preview-featured-img img {
  width: 100%;
  max-height: 420px;
  object-fit: cover;
}

.preview-img-caption {
  font-size: 0.78rem;
  color: #64748b;
  font-style: italic;
  margin-top: 6px;
}

.preview-summary-box {
  background: #f8fafc;
  border-left: 4px solid #0284c7;
  padding: 14px 18px;
  border-radius: 0 8px 8px 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 24px;
  line-height: 1.6;
}

.preview-body-html {
  font-size: 1rem;
  line-height: 1.8;
  color: #1e293b;
}

/* ========================================================
   RESPONSIVE (MOBILE & TABLET <= 991px)
   ======================================================== */
@media (max-width: 991px) {
  .editor-modal-container {
    height: 100vh;
    border-radius: 0;
    max-width: 100%;
  }

  .editor-header {
    flex-direction: column;
    align-items: stretch;
    padding: 12px 16px;
    gap: 12px;
  }

  .header-right {
    justify-content: space-between;
    width: 100%;
  }

  .mobile-tabs-bar {
    display: flex;
    background: #e2e8f0;
    padding: 3px;
    border-radius: 8px;
    gap: 3px;
  }

  .mob-tab-btn {
    border: none;
    background: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
  }

  .mob-tab-btn.active {
    background: #ffffff;
    color: #0284c7;
  }

  .desktop-only { display: none !important; }

  .editor-workspace {
    flex-direction: column;
  }

  .workspace-main {
    border-right: none;
    padding: 16px;
  }

  .workspace-side {
    width: 100%;
    border-top: 1px solid #e2e8f0;
    padding: 16px;
  }

  .mob-hidden {
    display: none !important;
  }

  .editor-footer {
    padding: 10px 16px;
    flex-wrap: wrap;
  }

  .footer-right {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
