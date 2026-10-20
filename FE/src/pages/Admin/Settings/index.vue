<template>
  <div class="admin-page" :class="{ 'reduced-motion': form.animation_mode === 'reduced', 'no-motion': form.animation_mode === 'off' }">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-sliders"></i></div>
        <div>
          <h1 class="page-title">Cấu Hình <span>Hệ Thống Quản Trị</span></h1>
          <p class="page-subtitle">Quản lý tham số thương hiệu, thanh toán VietQR, vận hành xe, SEO, bản đồ, chuyển động và thông báo.</p>
        </div>
      </div>
      <div class="header-actions">
        <div v-if="isDirty" class="dirty-badge animate-pulse">
          <i class="fa-solid fa-pen-nib"></i> Có thay đổi chưa lưu
        </div>
        <button class="btn-refresh" @click="handleRefreshAll" :disabled="loading || saving">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới & Xóa cache</span>
        </button>
      </div>
    </header>

    <!-- Permission Warning if Admin lacks settings.manage -->
    <div v-if="!canManage" class="alert-notice warning mb-4">
      <i class="fa-solid fa-lock"></i>
      <div>
        <strong>Chế độ chỉ đọc (Read-only):</strong>
        <p>Tài khoản của bạn không có quyền <code>settings.manage</code>. Bạn chỉ có thể xem các thông số cấu hình mà không thể lưu thay đổi.</p>
      </div>
    </div>

    <!-- Unsaved Changes Floating Bar -->
    <transition name="fade">
      <div v-if="isDirty && canManage" class="unsaved-bar">
        <div class="unsaved-info">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>Bạn đang có các thay đổi chưa được lưu vào hệ thống!</span>
        </div>
        <div class="unsaved-actions">
          <button type="button" class="btn-revert" @click="revertChanges" :disabled="saving">
            <i class="fa-solid fa-rotate-left"></i> Hoàn tác
          </button>
          <button type="button" class="btn-save-quick" @click="saveSettings" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-floppy-disk"></i>
            <span>{{ saving ? 'Đang lưu...' : 'Lưu thay đổi ngay' }}</span>
          </button>
        </div>
      </div>
    </transition>

    <!-- Navigation Tabs (7 main tabs + 1 category tool tab) -->
    <div class="tabs-nav">
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'general' }" 
        @click="switchTab('general')"
      >
        <i class="fa-solid fa-building"></i> Thương hiệu & Liên hệ
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'bank' }" 
        @click="switchTab('bank')"
      >
        <i class="fa-solid fa-credit-card"></i> Thanh toán & VietQR
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'operations' }" 
        @click="switchTab('operations')"
      >
        <i class="fa-solid fa-gauge-high"></i> Vận hành & Chính sách
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'seo' }" 
        @click="switchTab('seo')"
      >
        <i class="fa-solid fa-magnifying-glass-chart"></i> Tối ưu SEO
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'map' }" 
        @click="switchTab('map')"
      >
        <i class="fa-solid fa-map-location-dot"></i> Bản đồ & Tọa độ
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'animation' }" 
        @click="switchTab('animation')"
      >
        <i class="fa-solid fa-wand-magic-sparkles"></i> Chuyển động
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'notifications' }" 
        @click="switchTab('notifications')"
      >
        <i class="fa-solid fa-bell"></i> Thông báo
      </button>
      <button 
        type="button" 
        class="tab-btn cat-tab" 
        :class="{ 'active': activeTab === 'categories' }" 
        @click="switchTab('categories')"
      >
        <i class="fa-solid fa-tags"></i> Danh mục xe ({{ categories.length }})
      </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: THƯƠNG HIỆU & LIÊN HỆ               -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'general'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-building"></i> Thông Tin Thương Hiệu & Kênh Liên Hệ</h3>
            <p>Các thông số hiển thị công khai trên Header, Footer, Hợp đồng và trang Liên hệ.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-grid two-cols">
            <div class="form-group">
              <label for="site_name">Tên thương hiệu / Nền tảng <span class="required">*</span></label>
              <input 
                id="site_name" 
                type="text" 
                v-model="form.site_name" 
                @input="clearError('site_name')"
                :disabled="!canManage"
                required 
                placeholder="DriveRent" 
              />
              <small v-if="fieldErrors.site_name" class="field-error-text">{{ fieldErrors.site_name }}</small>
            </div>

            <div class="form-group">
              <label for="site_logo">Đường dẫn Logo website (URL)</label>
              <input 
                id="site_logo" 
                type="text" 
                v-model="form.site_logo" 
                @input="clearError('site_logo')"
                :disabled="!canManage"
                placeholder="https://... hoặc /assets/images/logo.png" 
              />
              <small v-if="fieldErrors.site_logo" class="field-error-text">{{ fieldErrors.site_logo }}</small>
            </div>

            <div class="form-group">
              <label for="hotline">Hotline tổng đài tư vấn 24/7 <span class="required">*</span></label>
              <input 
                id="hotline" 
                type="text" 
                v-model="form.hotline" 
                @input="clearError('hotline')"
                :disabled="!canManage"
                required 
                placeholder="1900 1234" 
              />
              <small v-if="fieldErrors.hotline" class="field-error-text">{{ fieldErrors.hotline }}</small>
            </div>

            <div class="form-group">
              <label for="support_email">Email hỗ trợ khách hàng <span class="required">*</span></label>
              <input 
                id="support_email" 
                type="email" 
                v-model="form.support_email" 
                @input="clearError('support_email')"
                :disabled="!canManage"
                required 
                placeholder="support@driverentals.com" 
              />
              <small v-if="fieldErrors.support_email" class="field-error-text">{{ fieldErrors.support_email }}</small>
            </div>
          </div>

          <div class="form-group mt-3">
            <label for="company_address">Địa chỉ trụ sở chính <span class="required">*</span></label>
            <input 
              id="company_address" 
              type="text" 
              v-model="form.company_address" 
              @input="clearError('company_address')"
              :disabled="!canManage"
              required 
              placeholder="Tòa nhà Bitexco, Q.1, TP. HCM" 
            />
            <small v-if="fieldErrors.company_address" class="field-error-text">{{ fieldErrors.company_address }}</small>
          </div>

          <div class="form-group mt-3">
            <label for="service_locations_text">Các trạm & Địa điểm phục vụ (Mỗi địa điểm cách nhau bằng dấu phẩy)</label>
            <input 
              id="service_locations_text" 
              type="text" 
              v-model="locationsRaw" 
              @input="clearError('service_locations')"
              :disabled="!canManage"
              placeholder="Hà Nội, TP. Hồ Chí Minh, Đà Nẵng, Nha Trang" 
            />
            <small class="field-hint">Ví dụ: Hà Nội, TP. Hồ Chí Minh, Đà Nẵng</small>
            <small v-if="fieldErrors.service_locations" class="field-error-text">{{ fieldErrors.service_locations }}</small>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu thông tin thương hiệu' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: THANH TOÁN & VIETQR                 -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'bank'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-credit-card"></i> Tài Khoản Ngân Hàng & Thanh Toán VietQR</h3>
            <p>Cấu hình tài khoản ngân hàng thụ hưởng tiền cọc và tiền thuê xe qua chuẩn VietQR tự động.</p>
          </div>
        </div>

        <!-- Audit & Snapshot Warning Notice -->
        <div class="alert-notice info mb-4">
          <i class="fa-solid fa-shield-halved"></i>
          <div>
            <strong>Lưu ý nghiệp vụ kiểm toán & Tính bất biến của giao dịch:</strong>
            <p>Mọi thay đổi tài khoản ngân hàng sẽ được ghi nhận vào nhật ký kiểm toán (Audit Log) kèm Admin thực hiện. Hệ thống cam kết không sửa đổi ngầm snapshot dữ liệu lịch sử thanh toán của các đơn hàng hoặc giao dịch đã lập trước đây.</p>
          </div>
        </div>

        <div class="bank-config-grid">
          <form @submit.prevent="saveSettings" class="settings-form">
            <div class="form-grid two-cols">
              <div class="form-group">
                <label for="bank_name">Tên ngân hàng thụ hưởng <span class="required">*</span></label>
                <input 
                  id="bank_name" 
                  type="text" 
                  v-model="form.bank_name" 
                  @input="clearError('bank_name')"
                  :disabled="!canManage"
                  required 
                  placeholder="MBBank (Ngân hàng TMCP Quân Đội)" 
                />
                <small v-if="fieldErrors.bank_name" class="field-error-text">{{ fieldErrors.bank_name }}</small>
              </div>

              <div class="form-group">
                <label for="bank_bin">Mã BIN ngân hàng (VietQR) <span class="required">*</span></label>
                <input 
                  id="bank_bin" 
                  type="text" 
                  v-model="form.bank_bin" 
                  @input="clearError('bank_bin')"
                  :disabled="!canManage"
                  required 
                  placeholder="970422" 
                />
                <small class="field-hint">Mã BIN ngân hàng 6 chữ số (MB: 970422, VCB: 970436, TCB: 970407, v.v.)</small>
                <small v-if="fieldErrors.bank_bin" class="field-error-text">{{ fieldErrors.bank_bin }}</small>
              </div>

              <div class="form-group">
                <label for="bank_account_number">Số tài khoản ngân hàng <span class="required">*</span></label>
                <input 
                  id="bank_account_number" 
                  type="text" 
                  v-model="form.bank_account_number" 
                  @input="clearError('bank_account_number')"
                  :disabled="!canManage"
                  required 
                  placeholder="Nhập số tài khoản thụ hưởng" 
                />
                <small v-if="fieldErrors.bank_account_number" class="field-error-text">{{ fieldErrors.bank_account_number }}</small>
              </div>

              <div class="form-group">
                <label for="bank_account_holder">Tên chủ tài khoản (In hoa không dấu) <span class="required">*</span></label>
                <input 
                  id="bank_account_holder" 
                  type="text" 
                  v-model="form.bank_account_holder" 
                  @input="clearError('bank_account_holder')"
                  :disabled="!canManage"
                  required 
                  placeholder="CONG TY CP THUE XE DRIVERENT" 
                />
                <small v-if="fieldErrors.bank_account_holder" class="field-error-text">{{ fieldErrors.bank_account_holder }}</small>
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="bank_transfer_instruction">Hướng dẫn chuyển khoản cho khách hàng</label>
              <textarea 
                id="bank_transfer_instruction" 
                rows="3" 
                v-model="form.bank_transfer_instruction" 
                @input="clearError('bank_transfer_instruction')"
                :disabled="!canManage"
                placeholder="Ví dụ: Quý khách vui lòng quét mã VietQR hoặc chuyển khoản với nội dung là Mã đơn hàng để hệ thống đối soát tự động nhanh nhất."
              ></textarea>
              <small v-if="fieldErrors.bank_transfer_instruction" class="field-error-text">{{ fieldErrors.bank_transfer_instruction }}</small>
            </div>

            <div class="form-actions mt-4">
              <button type="submit" class="btn-primary" :disabled="saving || !canManage">
                <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-floppy-disk"></i>
                <span>{{ saving ? 'Đang lưu...' : 'Lưu tài khoản ngân hàng' }}</span>
              </button>
            </div>
          </form>

          <!-- Live VietQR Preview Box -->
          <div class="qr-preview-box card">
            <h4 class="preview-title">
              <i class="fa-solid fa-eye"></i> Xem trước VietQR khách nhìn thấy
            </h4>

            <!-- Case 1: Configured -->
            <div v-if="isBankConfiguredLocally" class="qr-display">
              <img :src="previewQrUrl" alt="Mã VietQR xem trước" class="preview-qr-img" />
              <div class="qr-preview-meta">
                <div class="preview-row"><span>Ngân hàng:</span><strong>{{ form.bank_name || 'Chưa đặt tên' }}</strong></div>
                <div class="preview-row"><span>Số tài khoản:</span><strong class="highlight-text">{{ form.bank_account_number }}</strong></div>
                <div class="preview-row"><span>Chủ tài khoản:</span><span>{{ form.bank_account_holder || '---' }}</span></div>
                <div class="preview-row"><span>Cú pháp mẫu:</span><code>#HD-XXXXXX</code></div>
                <div class="preview-row"><span>Số tiền mẫu:</span><span class="text-muted">Theo số tiền đơn thực tế</span></div>
              </div>
            </div>

            <!-- Case 2: Unconfigured -->
            <div v-else class="qr-unconfigured-state">
              <div class="empty-icon-wrap">
                <i class="fa-solid fa-qrcode"></i>
                <span class="unconfigured-cross"><i class="fa-solid fa-slash"></i></span>
              </div>
              <h5>Chưa cấu hình tài khoản</h5>
              <p>Hệ thống sẽ không hiển thị mã QR hay số tài khoản mẫu cho khách hàng nhằm tránh nhầm lẫn tiền bạc.</p>
              <span class="badge-unconfigured">Trạng thái: Chưa sẵn sàng nhận chuyển khoản</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: VẬN HÀNH & CHÍNH SÁCH               -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'operations'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-gauge-high"></i> Tham Số Vận Hành & Chính Sách Thuê Xe</h3>
            <p>Cấu hình các mốc thời gian kiểm định, tự động hủy giữ chỗ và các điều khoản pháp lý.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-grid two-cols mb-4">
            <div class="form-group highlight-box">
              <label for="rental_buffer_hours">
                <i class="fa-solid fa-clock-rotate-left"></i> Thời gian đệm vệ sinh & kiểm định (giờ) <span class="required">*</span>
              </label>
              <div class="input-unit-wrap">
                <input 
                  id="rental_buffer_hours" 
                  type="number" 
                  min="0" 
                  max="48" 
                  v-model.number="form.rental_buffer_hours" 
                  @input="clearError('rental_buffer_hours')"
                  :disabled="!canManage"
                  required 
                />
                <span class="unit-text">giờ</span>
              </div>
              <small class="field-hint">Khoảng cách đệm kỹ thuật tối thiểu giữa hai lượt khách thuê cùng một xe để trạm kiểm định pin và làm sạch nội thất (mặc định: 2 giờ).</small>
              <small v-if="fieldErrors.rental_buffer_hours" class="field-error-text">{{ fieldErrors.rental_buffer_hours }}</small>
            </div>

            <div class="form-group highlight-box">
              <label for="booking_auto_cancel_unpaid_hours">
                <i class="fa-solid fa-hourglass-end"></i> Thời hạn giữ chỗ chờ cọc trước khi tự hủy (giờ) <span class="required">*</span>
              </label>
              <div class="input-unit-wrap">
                <input 
                  id="booking_auto_cancel_unpaid_hours" 
                  type="number" 
                  min="1" 
                  max="168" 
                  v-model.number="form.booking_auto_cancel_unpaid_hours" 
                  @input="clearError('booking_auto_cancel_unpaid_hours')"
                  :disabled="!canManage"
                  required 
                />
                <span class="unit-text">giờ</span>
              </div>
              <small class="field-hint">Sau khoảng thời gian này nếu khách không thanh toán cọc hoặc gửi biên lai, hệ thống sẽ tự động hủy đơn để nhả xe cho khách khác (mặc định: 24 giờ).</small>
              <small v-if="fieldErrors.booking_auto_cancel_unpaid_hours" class="field-error-text">{{ fieldErrors.booking_auto_cancel_unpaid_hours }}</small>
            </div>
          </div>

          <h4 class="section-title mt-4 mb-3"><i class="fa-solid fa-file-contract"></i> Quy định & Điều khoản thuê phương tiện</h4>

          <div class="form-group mb-3">
            <label for="deposit_policy">Chính sách đặt cọc bảo đảm</label>
            <textarea 
              id="deposit_policy" 
              rows="3" 
              v-model="form.rental_policy.deposit_policy" 
              @input="clearError('rental_policy')"
              :disabled="!canManage"
              placeholder="Nhập quy định về cọc xe..."
            ></textarea>
          </div>

          <div class="form-group mb-3">
            <label for="document_requirements">Yêu cầu giấy tờ & điều kiện người lái</label>
            <textarea 
              id="document_requirements" 
              rows="3" 
              v-model="form.rental_policy.document_requirements" 
              @input="clearError('rental_policy')"
              :disabled="!canManage"
              placeholder="CCCD gắn chip + GPLX B2 trở lên còn hiệu lực..."
            ></textarea>
          </div>

          <div class="form-grid two-cols mb-3">
            <div class="form-group">
              <label for="rental_time_rules">Quy định tính thời gian thuê</label>
              <textarea 
                id="rental_time_rules" 
                rows="3" 
                v-model="form.rental_policy.rental_time_rules" 
                @input="clearError('rental_policy')"
                :disabled="!canManage"
                placeholder="Tính theo chu kỳ 24h từ lúc nhận xe..."
              ></textarea>
            </div>

            <div class="form-group">
              <label for="vehicle_rules">Quy tắc sử dụng phương tiện</label>
              <textarea 
                id="vehicle_rules" 
                rows="3" 
                v-model="form.rental_policy.vehicle_rules" 
                @input="clearError('rental_policy')"
                :disabled="!canManage"
                placeholder="Không hút thuốc lá, không vận chuyển hàng cấm..."
              ></textarea>
            </div>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu quy định vận hành' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: TỐI ƯU SEO                          -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'seo'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-magnifying-glass-chart"></i> Tối Ưu Hóa Công Cụ Tìm Kiếm (SEO)</h3>
            <p>Tùy chỉnh tiêu đề, mô tả thẻ meta và hình ảnh đại diện khi người dùng chia sẻ liên kết trên Zalo/Facebook.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-group mb-3">
            <label for="seo_meta_title_suffix">Hậu tố tiêu đề trang (Title Suffix)</label>
            <input 
              id="seo_meta_title_suffix" 
              type="text" 
              v-model="form.seo_meta_title_suffix" 
              @input="clearError('seo_meta_title_suffix')"
              :disabled="!canManage"
              placeholder=" - Dịch Vụ Thuê Xe Điện VinFast Uy Tín" 
            />
            <small class="field-hint">Chuỗi ký tự tự động gắn phía sau tiêu đề từng trang (Ví dụ: "Thuê Xe VinFast VF8{{ form.seo_meta_title_suffix || ' - DriveRent' }}").</small>
            <small v-if="fieldErrors.seo_meta_title_suffix" class="field-error-text">{{ fieldErrors.seo_meta_title_suffix }}</small>
          </div>

          <div class="form-group mb-3">
            <label for="seo_meta_description">Mô tả tìm kiếm mặc định (Meta Description)</label>
            <textarea 
              id="seo_meta_description" 
              rows="3" 
              v-model="form.seo_meta_description" 
              @input="clearError('seo_meta_description')"
              :disabled="!canManage"
              placeholder="DriveRentals - Dịch vụ cho thuê xe điện tự lái VinFast thông minh..."
            ></textarea>
            <div class="char-counter" :class="{ 'warning': (form.seo_meta_description || '').length > 160 }">
              {{ (form.seo_meta_description || '').length }} / 160 ký tự khuyến nghị
            </div>
            <small v-if="fieldErrors.seo_meta_description" class="field-error-text">{{ fieldErrors.seo_meta_description }}</small>
          </div>

          <div class="form-group mb-4">
            <label for="seo_og_image">Đường dẫn ảnh đại diện chia sẻ (OpenGraph Image URL)</label>
            <input 
              id="seo_og_image" 
              type="text" 
              v-model="form.seo_og_image" 
              @input="clearError('seo_og_image')"
              :disabled="!canManage"
              placeholder="https://... hoặc /assets/images/bento_main.jpg" 
            />
            <small v-if="fieldErrors.seo_og_image" class="field-error-text">{{ fieldErrors.seo_og_image }}</small>
          </div>

          <!-- Live Google Search Preview -->
          <div class="seo-preview-box">
            <h4 class="preview-title"><i class="fa-brands fa-google"></i> Xem trước hiển thị trên kết quả tìm kiếm Google</h4>
            <div class="google-snippet">
              <div class="snippet-site">
                <span class="site-icon"><i class="fa-solid fa-car"></i></span>
                <span class="site-url">https://driverentals.com</span>
              </div>
              <div class="snippet-title">Trang Chủ {{ form.seo_meta_title_suffix || ' - Dịch Vụ Thuê Xe Điện Uy Tín' }}</div>
              <div class="snippet-desc">{{ form.seo_meta_description || 'DriveRentals - Dịch vụ cho thuê xe điện tự lái thông minh, giá niêm yết minh bạch, giao xe tận nơi 24/7.' }}</div>
            </div>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu cấu hình SEO' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 5: BẢN ĐỒ & TỌA ĐỘ                     -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'map'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-map-location-dot"></i> Bản Đồ Trụ Sở & Trạm Giao Nhận</h3>
            <p>Cấu hình bản đồ nhúng bảo mật phục vụ khách hàng tìm đường đến văn phòng và trạm nhận xe.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-grid two-cols mb-3">
            <div class="form-group">
              <label for="map_provider">Nhà cung cấp bản đồ <span class="required">*</span></label>
              <select 
                id="map_provider" 
                v-model="form.map_provider" 
                @change="clearError('map_provider')"
                :disabled="!canManage"
              >
                <option value="google_maps">Google Maps (Khuyến nghị)</option>
                <option value="openstreetmap">OpenStreetMap (Nguồn mở)</option>
              </select>
              <small v-if="fieldErrors.map_provider" class="field-error-text">{{ fieldErrors.map_provider }}</small>
            </div>

            <div class="form-group">
              <label for="map_coordinates">Tọa độ GPS (Vĩ độ, Kinh độ)</label>
              <input 
                id="map_coordinates" 
                type="text" 
                v-model="form.map_coordinates" 
                @input="clearError('map_coordinates')"
                :disabled="!canManage"
                placeholder="10.771595, 106.704457" 
              />
              <small class="field-hint">Phục vụ mở ứng dụng dẫn đường trên điện thoại di động.</small>
              <small v-if="fieldErrors.map_coordinates" class="field-error-text">{{ fieldErrors.map_coordinates }}</small>
            </div>
          </div>

          <div class="form-group mb-3">
            <label for="map_embed_url">Đường dẫn URL nhúng bản đồ trực tiếp <span class="required">*</span></label>
            <input 
              id="map_embed_url" 
              type="text" 
              v-model="form.map_embed_url" 
              @input="handleMapUrlInput"
              :disabled="!canManage"
              required 
              placeholder="https://www.google.com/maps/embed?..." 
            />
            
            <!-- Detected <iframe> Helper Banner -->
            <div v-if="detectedIframeInMapUrl" class="iframe-detected-alert mt-2">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
              <span>Phát hiện bạn đã dán cả thẻ <code>&lt;iframe&gt;</code>. Hệ thống yêu cầu chỉ nhập URL nhúng an toàn.</span>
              <button type="button" class="btn-clean-url" @click="cleanMapIframeUrl">Tự động trích xuất URL nhúng</button>
            </div>

            <small class="field-hint">Vui lòng lấy URL trong thuộc tính <code>src</code> khi chọn Share -> Embed map trên Google Maps. Tuyệt đối không dán cả thẻ HTML.</small>
            <small v-if="fieldErrors.map_embed_url" class="field-error-text">{{ fieldErrors.map_embed_url }}</small>
          </div>

          <!-- Live Safe Map Preview Box -->
          <div class="map-preview-wrapper mt-4">
            <h4 class="preview-title"><i class="fa-solid fa-eye"></i> Xem trước bản đồ nhúng bảo mật</h4>
            <div v-if="isValidMapUrl" class="map-embed-frame">
              <iframe 
                :src="form.map_embed_url" 
                width="100%" 
                height="320" 
                style="border:0; border-radius: 8px;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="DriveRent Office Location Preview"
              ></iframe>
            </div>
            <div v-else class="map-invalid-notice">
              <i class="fa-solid fa-map-pin"></i>
              <p>Chưa có URL bản đồ hợp lệ. Vui lòng nhập đường dẫn URL bắt đầu bằng <code>https://www.google.com/maps/embed...</code> hoặc <code>https://www.openstreetmap.org/export/embed.html...</code> để xem trước.</p>
            </div>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu cấu hình bản đồ' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 6: CHUYỂN ĐỘNG & HIỆU ỨNG              -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'animation'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Mức Độ Chuyển Động & Hiệu Ứng Giao Diện</h3>
            <p>Điều chỉnh trải nghiệm chuyển cảnh theo chuẩn trợ năng (Accessibility) và hiệu năng thiết bị. Nghiêm cấm chèn mã code tùy ý.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-group mb-4">
            <label class="form-label-bold">Chế độ chuyển động (Animation Mode) <span class="required">*</span></label>
            <div class="radio-card-grid">
              <label class="radio-card" :class="{ 'selected': form.animation_mode === 'standard' }">
                <input 
                  type="radio" 
                  value="standard" 
                  v-model="form.animation_mode" 
                  @change="clearError('animation_mode')"
                  :disabled="!canManage"
                />
                <div class="radio-content">
                  <div class="radio-icon"><i class="fa-solid fa-film"></i></div>
                  <div class="radio-title">Tiêu chuẩn (Standard)</div>
                  <div class="radio-desc">Hiệu ứng mượt mà mặc định, đầy đủ hiệu ứng trượt, xoay và đổ bóng động.</div>
                </div>
              </label>

              <label class="radio-card" :class="{ 'selected': form.animation_mode === 'reduced' }">
                <input 
                  type="radio" 
                  value="reduced" 
                  v-model="form.animation_mode" 
                  @change="clearError('animation_mode')"
                  :disabled="!canManage"
                />
                <div class="radio-content">
                  <div class="radio-icon"><i class="fa-solid fa-feather"></i></div>
                  <div class="radio-title">Giảm thiểu (Reduced)</div>
                  <div class="radio-desc">Rút ngắn thời lượng, giảm chuyển động lặp vô hạn, thân thiện với người nhạy cảm thị giác.</div>
                </div>
              </label>

              <label class="radio-card" :class="{ 'selected': form.animation_mode === 'off' }">
                <input 
                  type="radio" 
                  value="off" 
                  v-model="form.animation_mode" 
                  @change="clearError('animation_mode')"
                  :disabled="!canManage"
                />
                <div class="radio-content">
                  <div class="radio-icon"><i class="fa-solid fa-ban"></i></div>
                  <div class="radio-title">Tắt toàn bộ (Off)</div>
                  <div class="radio-desc">Vô hiệu hóa chuyển động, ưu tiên tối đa hiệu năng và thời lượng pin trên thiết bị yếu.</div>
                </div>
              </label>
            </div>
            <small v-if="fieldErrors.animation_mode" class="field-error-text">{{ fieldErrors.animation_mode }}</small>
          </div>

          <div class="form-group mb-4">
            <label for="animation_duration_scale" class="form-label-bold">Hệ số điều chỉnh thời lượng hiệu ứng</label>
            <select 
              id="animation_duration_scale" 
              v-model="form.animation_duration_scale" 
              @change="clearError('animation_duration_scale')"
              :disabled="!canManage"
              style="max-width: 320px;"
            >
              <option value="0.5">0.5x — Nhanh & dứt khoát</option>
              <option value="1.0">1.0x — Tiêu chuẩn mặc định</option>
              <option value="1.5">1.5x — Thong thả, êm ái</option>
            </select>
            <small class="field-hint">Điều chỉnh tỷ lệ thời gian chạy của các hiệu ứng fade, modal transition và accordion.</small>
            <small v-if="fieldErrors.animation_duration_scale" class="field-error-text">{{ fieldErrors.animation_duration_scale }}</small>
          </div>

          <!-- Interactive Animation Playground Card -->
          <div class="animation-playground-box">
            <h4 class="preview-title"><i class="fa-solid fa-vial"></i> Thử nghiệm chuyển động trực tiếp theo cấu hình</h4>
            <div class="playground-demo">
              <div 
                class="demo-orb" 
                :class="{ 
                  'anim-standard': form.animation_mode === 'standard',
                  'anim-reduced': form.animation_mode === 'reduced',
                  'anim-off': form.animation_mode === 'off'
                }"
                :style="{ animationDuration: `${(1.6 * Number(form.animation_duration_scale || 1))}s` }"
              >
                <i class="fa-solid fa-bolt"></i>
              </div>
              <div class="demo-info">
                <strong>Chế độ đang chọn: {{ form.animation_mode.toUpperCase() }} (Tốc độ {{ form.animation_duration_scale }}x)</strong>
                <p v-if="form.animation_mode === 'standard'">Hiệu ứng breathing glow và trượt tương tác mượt mà.</p>
                <p v-else-if="form.animation_mode === 'reduced'">Chuyển động nhẹ nhàng được tiết giảm 50% biên độ dao động.</p>
                <p v-else>Toàn bộ animation được ngắt ngay lập tức, giữ vị trí cố định.</p>
              </div>
            </div>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu cài đặt chuyển động' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 7: THÔNG BÁO                           -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'notifications'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <div>
            <h3><i class="fa-solid fa-bell"></i> Cảnh Báo & Kênh Thông Báo Vận Hành</h3>
            <p>Cấu hình âm thanh cảnh báo trên ứng dụng và địa chỉ email tiếp nhận thông báo nội bộ.</p>
          </div>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="toggle-card mb-4">
            <div class="toggle-info">
              <div class="toggle-title">
                <i class="fa-solid fa-volume-high"></i> Âm báo thông báo trên hệ thống web
              </div>
              <div class="toggle-desc">Phát âm thanh thông báo nhẹ khi có đơn hàng mới hoặc tin nhắn hỗ trợ từ khách hàng.</div>
            </div>
            <label class="switch">
              <input 
                type="checkbox" 
                v-model="form.notification_sound_enabled" 
                :disabled="!canManage"
              />
              <span class="slider round"></span>
            </label>
          </div>

          <div class="toggle-card mb-4">
            <div class="toggle-info">
              <div class="toggle-title">
                <i class="fa-solid fa-envelope-open-text"></i> Gửi email thông báo đơn đặt xe & liên hệ mới
              </div>
              <div class="toggle-desc">Tự động kích hoạt thông báo qua email khi khách hàng tạo đơn đặt xe hoặc gửi biểu mẫu yêu cầu.</div>
            </div>
            <label class="switch">
              <input 
                type="checkbox" 
                v-model="form.notification_email_alerts" 
                :disabled="!canManage"
              />
              <span class="slider round"></span>
            </label>
          </div>

          <div class="form-group mb-4" :style="{ opacity: form.notification_email_alerts ? 1 : 0.6 }">
            <label for="notification_admin_recipient_email">
              <i class="fa-solid fa-shield-halved"></i> Email quản trị viên tiếp nhận cảnh báo (Nội bộ bí mật)
            </label>
            <input 
              id="notification_admin_recipient_email" 
              type="email" 
              v-model="form.notification_admin_recipient_email" 
              @input="clearError('notification_admin_recipient_email')"
              :disabled="!canManage || !form.notification_email_alerts"
              placeholder="admin@driverentals.com" 
            />
            <small class="field-hint">
              <i class="fa-solid fa-lock"></i> Địa chỉ này được bảo mật trong kho backend, tuyệt đối KHÔNG bao giờ trả về qua API công khai của người dùng.
            </small>
            <small v-if="fieldErrors.notification_admin_recipient_email" class="field-error-text">{{ fieldErrors.notification_admin_recipient_email }}</small>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving || !canManage">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu cấu hình thông báo' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 8: QUẢN LÝ DANH MỤC XE                 -->
    <!-- ========================================== -->
    <div v-if="activeTab === 'categories'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean flex-between">
          <div>
            <h3><i class="fa-solid fa-tags"></i> Phân Khúc & Danh Mục Phương Tiện</h3>
            <p>Quản lý các nhóm xe phục vụ bộ lọc tìm kiếm trên giao diện khách hàng.</p>
          </div>
          <button class="btn-primary" @click="openCategoryModal(null)" :disabled="!canManage">
            <i class="fa-solid fa-plus"></i> Thêm Danh Mục
          </button>
        </div>

        <div class="table-responsive mt-3">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width: 60px;">ID</th>
                <th>Tên danh mục</th>
                <th>Mô tả</th>
                <th style="width: 120px;" class="text-center">Số lượng xe</th>
                <th style="width: 120px;" class="text-center">Trạng thái</th>
                <th style="width: 120px;">Ngày tạo</th>
                <th style="width: 140px;" class="text-center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loadingCategories">
                <td colspan="7" class="text-center py-4">
                  <i class="fa-solid fa-spinner fa-spin"></i> Đang tải danh sách danh mục...
                </td>
              </tr>
              <tr v-else-if="categories.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">Chưa có danh mục xe nào.</td>
              </tr>
              <tr v-for="cat in categories" :key="cat.id">
                <td>#{{ cat.id }}</td>
                <td><strong class="cat-name">{{ cat.name }}</strong></td>
                <td><span class="text-muted">{{ cat.description || '---' }}</span></td>
                <td class="text-center">
                  <span class="badge badge-info">{{ cat.cars_count || 0 }} xe</span>
                </td>
                <td class="text-center">
                  <span class="badge" :class="cat.is_active ? 'badge-success' : 'badge-danger'">
                    {{ cat.is_active ? 'Hoạt động' : 'Tạm dừng' }}
                  </span>
                </td>
                <td>{{ formatDate(cat.created_at) }}</td>
                <td class="text-center action-cells">
                  <button class="btn-action edit" title="Sửa danh mục" @click="openCategoryModal(cat)" :disabled="!canManage">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button 
                    class="btn-action delete" 
                    title="Xóa danh mục" 
                    @click="handleDeleteCategory(cat)"
                    :disabled="cat.cars_count > 0 || !canManage"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Category Modal -->
    <div v-if="showCatModal" class="modal-overlay" @click.self="showCatModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ editingCategory ? 'Chỉnh Sửa Danh Mục Xe' : 'Thêm Mới Danh Mục Xe' }}</h3>
          <button class="btn-close" @click="showCatModal = false">&times;</button>
        </div>
        <form @submit.prevent="saveCategory" class="modal-form">
          <div class="form-group">
            <label for="cat-name">Tên phân khúc / danh mục <span class="required">*</span></label>
            <input id="cat-name" type="text" v-model="catForm.name" required placeholder="Ví dụ: Sedan điện, SUV 7 chỗ..." />
          </div>

          <div class="form-group mt-3">
            <label for="cat-desc">Mô tả tóm tắt</label>
            <textarea 
              id="cat-desc" 
              rows="3" 
              v-model="catForm.description" 
              placeholder="Mô tả phong cách, đối tượng phục vụ hoặc phân khúc..."
            ></textarea>
          </div>

          <div class="form-group mt-3">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" v-model="catForm.is_active" />
              <span>Kích hoạt sử dụng danh mục</span>
            </label>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn-outline" @click="showCatModal = false" :disabled="savingCategory">Hủy</button>
            <button type="submit" class="btn-primary" :disabled="savingCategory">
              <i v-if="savingCategory" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ savingCategory ? 'Đang lưu...' : (editingCategory ? 'Lưu thay đổi' : 'Thêm mới') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { refreshSettings as clearClientCache, applySettingsConsumers } from '../../../core/settingsStore';

const toaster = createToaster({ position: "top-right" });

const activeTab = ref('general');
const loading = ref(false);
const saving = ref(false);
const fieldErrors = reactive({});

// Authorization Check (settings.manage)
const canManage = computed(() => {
  try {
    const rawUser = localStorage.getItem('user');
    if (!rawUser) return true;
    const u = JSON.parse(rawUser);
    if (u.admin_role === 'owner') return true;
    if (Array.isArray(u.permissions)) {
      return u.permissions.includes('settings.manage');
    }
  } catch (e) {}
  return true;
});

// Settings Form State
const form = reactive({
  site_name: '',
  site_logo: '',
  hotline: '',
  support_email: '',
  company_address: '',
  service_locations: [],
  bank_name: '',
  bank_account_number: '',
  bank_account_holder: '',
  bank_bin: '',
  bank_transfer_instruction: '',
  rental_policy: {
    deposit_policy: '',
    document_requirements: '',
    rental_time_rules: '',
    vehicle_rules: '',
  },
  rental_buffer_hours: 2,
  booking_auto_cancel_unpaid_hours: 24,
  seo_meta_title_suffix: '',
  seo_meta_description: '',
  seo_og_image: '',
  map_provider: 'google_maps',
  map_embed_url: '',
  map_coordinates: '',
  animation_mode: 'standard',
  animation_duration_scale: '1.0',
  notification_sound_enabled: true,
  notification_email_alerts: true,
  notification_admin_recipient_email: '',
});

// Snapshot to track unsaved dirty changes
const savedSnapshot = ref('');

const isDirty = computed(() => {
  if (!savedSnapshot.value) return false;
  return JSON.stringify(form) !== savedSnapshot.value;
});

const clearError = (field) => {
  if (fieldErrors[field]) {
    delete fieldErrors[field];
  }
};

const locationsRaw = computed({
  get: () => Array.isArray(form.service_locations) ? form.service_locations.join(', ') : '',
  set: (val) => {
    form.service_locations = val.split(',').map(s => s.trim()).filter(Boolean);
  }
});

// Local bank configuration check (strictly without fake fallbacks)
const isBankConfiguredLocally = computed(() => {
  return Boolean(
    form.bank_bin && 
    form.bank_bin.trim().length > 0 && 
    form.bank_account_number && 
    form.bank_account_number.trim().length > 0
  );
});

// Live QR code for preview
const previewQrUrl = computed(() => {
  if (!isBankConfiguredLocally.value) return '';
  const bin = form.bank_bin.trim();
  const acc = form.bank_account_number.trim();
  const name = encodeURIComponent(form.bank_account_holder ? form.bank_account_holder.trim() : '');
  // No fake amounts like amount=1500000; purely compact QR showing account info
  return `https://api.vietqr.io/image/${bin}-${acc}-compact2.png?accountName=${name}&addInfo=${encodeURIComponent('HD_MA_DON')}`;
});

// Map Embed URL Safety & Verification
const detectedIframeInMapUrl = computed(() => {
  const url = (form.map_embed_url || '').trim();
  return url.toLowerCase().includes('<iframe') || url.toLowerCase().includes('src=');
});

const cleanMapIframeUrl = () => {
  const raw = form.map_embed_url || '';
  const match = raw.match(/src=["'](https:\/\/[^"']+)["']/i);
  if (match && match[1]) {
    form.map_embed_url = match[1];
    clearError('map_embed_url');
    toaster.success('Đã tự động trích xuất URL nhúng hợp lệ!');
  } else {
    toaster.warning('Không tìm thấy đường dẫn https:// hợp lệ trong thẻ iframe.');
  }
};

const handleMapUrlInput = () => {
  clearError('map_embed_url');
};

const isValidMapUrl = computed(() => {
  const url = (form.map_embed_url || '').trim();
  if (!url || !url.startsWith('https://')) return false;
  if (url.includes('<') || url.includes('>')) return false;
  return (
    url.startsWith('https://www.google.com/maps/embed') ||
    url.startsWith('https://maps.google.com/') ||
    url.startsWith('https://www.openstreetmap.org/export/embed.html')
  );
});

// Tab switching with dirty protection
const switchTab = (newTab) => {
  if (isDirty.value && activeTab.value !== newTab) {
    const confirmSwitch = window.confirm('Bạn có các thay đổi chưa được lưu tại tab này. Bạn có chắc chắn muốn chuyển tab?');
    if (!confirmSwitch) return;
  }
  activeTab.value = newTab;
};

// Revert unsaved edits back to last saved snapshot
const revertChanges = () => {
  if (savedSnapshot.value) {
    const original = JSON.parse(savedSnapshot.value);
    Object.assign(form, original);
    Object.keys(fieldErrors).forEach(k => delete fieldErrors[k]);
    toaster.info('Đã khôi phục các thay đổi về giá trị đã lưu gần nhất.');
  }
};

// Category Management State
const categories = ref([]);
const loadingCategories = ref(false);
const showCatModal = ref(false);
const editingCategory = ref(null);
const savingCategory = ref(false);
const catForm = reactive({
  name: '',
  description: '',
  is_active: true,
});

// Load all system settings
const fetchAllSettings = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/settings');
    if (res.data && res.data.status && res.data.data) {
      const data = res.data.data;
      if (data.site_name) form.site_name = data.site_name.value ?? '';
      if (data.site_logo) form.site_logo = data.site_logo.value ?? '';
      if (data.hotline) form.hotline = data.hotline.value ?? '';
      if (data.support_email) form.support_email = data.support_email.value ?? '';
      if (data.company_address) form.company_address = data.company_address.value ?? '';
      if (data.service_locations) form.service_locations = data.service_locations.value ?? [];
      if (data.bank_name) form.bank_name = data.bank_name.value ?? '';
      if (data.bank_account_number) form.bank_account_number = data.bank_account_number.value ?? '';
      if (data.bank_account_holder) form.bank_account_holder = data.bank_account_holder.value ?? '';
      if (data.bank_bin) form.bank_bin = data.bank_bin.value ?? '';
      if (data.bank_transfer_instruction) form.bank_transfer_instruction = data.bank_transfer_instruction.value ?? '';
      if (data.rental_policy && data.rental_policy.value) {
        Object.assign(form.rental_policy, data.rental_policy.value);
      }
      if (data.rental_buffer_hours !== undefined) form.rental_buffer_hours = Number(data.rental_buffer_hours.value ?? 2);
      if (data.booking_auto_cancel_unpaid_hours !== undefined) form.booking_auto_cancel_unpaid_hours = Number(data.booking_auto_cancel_unpaid_hours.value ?? 24);
      if (data.seo_meta_title_suffix) form.seo_meta_title_suffix = data.seo_meta_title_suffix.value ?? '';
      if (data.seo_meta_description) form.seo_meta_description = data.seo_meta_description.value ?? '';
      if (data.seo_og_image) form.seo_og_image = data.seo_og_image.value ?? '';
      if (data.map_provider) form.map_provider = data.map_provider.value ?? 'google_maps';
      if (data.map_embed_url) form.map_embed_url = data.map_embed_url.value ?? '';
      if (data.map_coordinates) form.map_coordinates = data.map_coordinates.value ?? '';
      if (data.animation_mode) form.animation_mode = data.animation_mode.value ?? 'standard';
      if (data.animation_duration_scale) form.animation_duration_scale = String(data.animation_duration_scale.value ?? '1.0');
      if (data.notification_sound_enabled !== undefined) form.notification_sound_enabled = Boolean(data.notification_sound_enabled.value);
      if (data.notification_email_alerts !== undefined) form.notification_email_alerts = Boolean(data.notification_email_alerts.value);
      if (data.notification_admin_recipient_email) form.notification_admin_recipient_email = data.notification_admin_recipient_email.value ?? '';

      savedSnapshot.value = JSON.stringify(form);
      applySettingsConsumers(form);
    }
  } catch (err) {
    toaster.error("Không thể tải cấu hình hệ thống từ máy chủ!");
  } finally {
    loading.value = false;
  }
};

// Save Settings Form
const saveSettings = async () => {
  if (!canManage.value) {
    toaster.error("Bạn không có quyền cập nhật cấu hình hệ thống (settings.manage)!");
    return;
  }

  // Frontend map validation check
  if (form.map_embed_url && form.map_embed_url.trim()) {
    if (form.map_embed_url.includes('<iframe') || form.map_embed_url.includes('<script')) {
      fieldErrors.map_embed_url = 'Vui lòng chỉ nhập URL nhúng trực tiếp (https://...), không chèn thẻ <iframe> tùy ý.';
      activeTab.value = 'map';
      toaster.error(fieldErrors.map_embed_url);
      return;
    }
  }

  saving.value = true;
  Object.keys(fieldErrors).forEach(k => delete fieldErrors[k]);

  try {
    const payload = {
      site_name: form.site_name,
      site_logo: form.site_logo,
      hotline: form.hotline,
      support_email: form.support_email,
      company_address: form.company_address,
      service_locations: form.service_locations,
      bank_name: form.bank_name,
      bank_account_number: form.bank_account_number,
      bank_account_holder: form.bank_account_holder,
      bank_bin: form.bank_bin,
      bank_transfer_instruction: form.bank_transfer_instruction,
      rental_policy: form.rental_policy,
      rental_buffer_hours: Number(form.rental_buffer_hours),
      booking_auto_cancel_unpaid_hours: Number(form.booking_auto_cancel_unpaid_hours),
      seo_meta_title_suffix: form.seo_meta_title_suffix,
      seo_meta_description: form.seo_meta_description,
      seo_og_image: form.seo_og_image,
      map_provider: form.map_provider,
      map_embed_url: form.map_embed_url,
      map_coordinates: form.map_coordinates,
      animation_mode: form.animation_mode,
      animation_duration_scale: form.animation_duration_scale,
      notification_sound_enabled: form.notification_sound_enabled,
      notification_email_alerts: form.notification_email_alerts,
      notification_admin_recipient_email: form.notification_admin_recipient_email,
    };

    const res = await baseRequest.put('admin/settings', payload);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Cập nhật cấu hình thành công!");
      await clearClientCache();
      applySettingsConsumers(form);
      savedSnapshot.value = JSON.stringify(form);
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      const errs = err.response.data.errors;
      Object.keys(errs).forEach(key => {
        fieldErrors[key] = Array.isArray(errs[key]) ? errs[key][0] : errs[key];
      });
      toaster.error("Vui lòng kiểm tra lại các trường dữ liệu bị lỗi!");
    } else if (err.response?.status === 403) {
      toaster.error("Quyền bị từ chối: Tài khoản không có quyền settings.manage!");
    } else {
      toaster.error(err.response?.data?.message || "Lỗi khi lưu cấu hình!");
    }
  } finally {
    saving.value = false;
  }
};

// Refresh All & Clear Cache
const handleRefreshAll = async () => {
  loading.value = true;
  try {
    await clearClientCache();
    await fetchAllSettings();
    await fetchCategories();
    toaster.success("Đã làm mới dữ liệu và xóa bộ nhớ đệm thành công!");
  } catch (err) {
    toaster.error("Lỗi khi làm mới dữ liệu!");
  } finally {
    loading.value = false;
  }
};

// Categories Management
const fetchCategories = async () => {
  loadingCategories.value = true;
  try {
    const res = await baseRequest.get('admin/car-categories');
    if (res.data && res.data.status) {
      categories.value = res.data.data;
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách danh mục xe!");
  } finally {
    loadingCategories.value = false;
  }
};

const openCategoryModal = (cat) => {
  editingCategory.value = cat;
  if (cat) {
    catForm.name = cat.name;
    catForm.description = cat.description || '';
    catForm.is_active = cat.is_active !== undefined ? Boolean(cat.is_active) : true;
  } else {
    catForm.name = '';
    catForm.description = '';
    catForm.is_active = true;
  }
  showCatModal.value = true;
};

const saveCategory = async () => {
  savingCategory.value = true;
  try {
    if (editingCategory.value) {
      const res = await baseRequest.put(`admin/car-categories/${editingCategory.value.id}`, catForm);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Cập nhật danh mục thành công!");
        showCatModal.value = false;
        fetchCategories();
      }
    } else {
      const res = await baseRequest.post('admin/car-categories', catForm);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Thêm mới danh mục thành công!");
        showCatModal.value = false;
        fetchCategories();
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Lỗi khi lưu danh mục!";
    toaster.error(msg);
  } finally {
    savingCategory.value = false;
  }
};

const handleDeleteCategory = async (cat) => {
  if (cat.cars_count > 0) {
    toaster.warning(`Không thể xóa danh mục "${cat.name}" vì đang có ${cat.cars_count} xe liên kết!`);
    return;
  }
  if (!confirm(`Bạn có chắc chắn muốn xóa danh mục "${cat.name}"?`)) return;

  try {
    const res = await baseRequest.delete(`admin/car-categories/${cat.id}`);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa danh mục thành công!");
      fetchCategories();
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể xóa danh mục!");
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

// Window unload alert if dirty
const handleBeforeUnload = (e) => {
  if (isDirty.value) {
    e.preventDefault();
    e.returnValue = '';
  }
};

onMounted(() => {
  fetchAllSettings();
  fetchCategories();
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<style scoped>
.admin-page {
  padding-bottom: 50px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--color-primary-light, #ecfdf5);
  color: var(--color-primary, #059669);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: #101828;
  margin: 0;
}

.page-title span {
  color: var(--color-primary, #059669);
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dirty-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .6; }
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #334155;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* Floating Unsaved Changes Bar */
.unsaved-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e293b;
  color: #f8fafc;
  padding: 12px 20px;
  border-radius: 10px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
  margin-bottom: 20px;
  border-left: 4px solid #f59e0b;
}

.unsaved-info {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 500;
}

.unsaved-info i {
  color: #f59e0b;
  font-size: 16px;
}

.unsaved-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-revert {
  background: transparent;
  border: 1px solid #475569;
  color: #cbd5e1;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-revert:hover {
  background: #334155;
  color: #ffffff;
}

.btn-save-quick {
  background: var(--color-primary, #059669);
  border: none;
  color: #ffffff;
  padding: 7px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-save-quick:hover:not(:disabled) {
  background: #047857;
}

/* Navigation Tabs */
.tabs-nav {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid #e2e8f0;
  margin-bottom: 24px;
  overflow-x: auto;
  padding-bottom: 2px;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  border-radius: 6px 6px 0 0;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: #0f172a;
  background: #f8fafc;
}

.tab-btn.active {
  color: var(--color-primary, #059669);
  border-bottom-color: var(--color-primary, #059669);
  background: #f0fdf4;
}

.tab-btn.cat-tab {
  margin-left: auto;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-bottom: 3px solid transparent;
}

.tab-btn.cat-tab.active {
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
  border-bottom-color: #2563eb;
}

/* Settings Card & Form Layout */
.settings-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.card-header-clean {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.card-header-clean.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header-clean h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.card-header-clean p {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.form-grid.two-cols {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.form-label-bold {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  display: block;
}

.form-group .required {
  color: #ef4444;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  background: #ffffff;
  color: #1e293b;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.field-hint {
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

.field-error-text {
  font-size: 12px;
  color: #dc2626;
  font-weight: 500;
}

.input-unit-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-unit-wrap .unit-text {
  position: absolute;
  right: 14px;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  pointer-events: none;
}

.highlight-box {
  background: #f8fafc;
  padding: 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

/* Alert Notices */
.alert-notice {
  display: flex;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 20px;
}

.alert-notice.warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

.alert-notice.info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}

.alert-notice i {
  font-size: 18px;
  margin-top: 2px;
}

.alert-notice p {
  margin: 4px 0 0 0;
}

/* Bank Grid & QR Preview */
.bank-config-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 24px;
  align-items: start;
}

.qr-preview-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.preview-title {
  font-size: 14px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.qr-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.preview-qr-img {
  width: 190px;
  height: 190px;
  background: #ffffff;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.qr-preview-meta {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 13px;
  background: #ffffff;
  padding: 12px 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-row span:first-child {
  color: #64748b;
}

.highlight-text {
  color: var(--color-primary, #059669);
  font-family: monospace;
  font-size: 14px;
}

/* Unconfigured QR Empty State */
.qr-unconfigured-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px 16px;
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
}

.empty-icon-wrap {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin-bottom: 12px;
}

.unconfigured-cross {
  position: absolute;
  color: #ef4444;
  font-size: 24px;
}

.qr-unconfigured-state h5 {
  font-size: 15px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 6px 0;
}

.qr-unconfigured-state p {
  font-size: 12px;
  color: #64748b;
  margin: 0 0 14px 0;
  line-height: 1.4;
}

.badge-unconfigured {
  background: #fee2e2;
  color: #b91c1c;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 12px;
}

/* SEO Snippet Preview */
.seo-preview-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px;
  margin-top: 16px;
}

.google-snippet {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 14px 18px;
  border-radius: 8px;
}

.snippet-site {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #202124;
  margin-bottom: 4px;
}

.site-icon {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #f1f3f4;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
}

.snippet-title {
  font-size: 18px;
  font-weight: 500;
  color: #1a0dab;
  line-height: 1.3;
  margin-bottom: 4px;
}

.snippet-desc {
  font-size: 13px;
  color: #4d5156;
  line-height: 1.4;
}

.char-counter {
  font-size: 12px;
  color: #64748b;
  text-align: right;
  margin-top: 2px;
}

.char-counter.warning {
  color: #b45309;
}

/* Map Settings */
.iframe-detected-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
}

.btn-clean-url {
  background: #059669;
  color: #ffffff;
  border: none;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  margin-left: auto;
}

.map-preview-wrapper {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 10px;
}

.map-invalid-notice {
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  padding: 24px;
  border-radius: 8px;
  text-align: center;
  color: #64748b;
}

.map-invalid-notice i {
  font-size: 28px;
  color: #94a3b8;
  margin-bottom: 8px;
}

.map-invalid-notice p {
  margin: 0;
  font-size: 13px;
}

/* Animation Radio Cards */
.radio-card-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-top: 8px;
}

.radio-card {
  display: flex;
  gap: 12px;
  padding: 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  cursor: pointer;
  transition: all 0.2s ease;
}

.radio-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.radio-card.selected {
  border-color: var(--color-primary, #059669);
  background: #f0fdf4;
}

.radio-card input {
  margin-top: 4px;
}

.radio-icon {
  font-size: 20px;
  color: var(--color-primary, #059669);
  margin-bottom: 6px;
}

.radio-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 4px;
}

.radio-desc {
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

.animation-playground-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 10px;
}

.playground-demo {
  display: flex;
  align-items: center;
  gap: 20px;
  background: #ffffff;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.demo-orb {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
}

.demo-orb.anim-standard {
  animation: orbGlow 1.6s ease-in-out infinite;
}

.demo-orb.anim-reduced {
  animation: orbSubtle 2.5s ease-in-out infinite;
}

.demo-orb.anim-off {
  animation: none !important;
}

@keyframes orbGlow {
  0%, 100% { transform: scale(1); filter: brightness(1); }
  50% { transform: scale(1.12); filter: brightness(1.2); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.6); }
}

@keyframes orbSubtle {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.demo-info strong {
  font-size: 14px;
  color: #1e293b;
}

.demo-info p {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
}

/* Notification Toggles */
.toggle-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.toggle-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 8px;
}

.toggle-desc {
  font-size: 13px;
  color: #64748b;
  margin-top: 4px;
}

/* Switch styling */
.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .3s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .3s;
}

input:checked + .slider {
  background-color: var(--color-primary, #059669);
}

input:checked + .slider:before {
  transform: translateX(22px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

/* Buttons */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--color-primary, #059669);
  color: #ffffff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background: #047857;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
}

/* Category Table */
.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 12px 16px;
  border-bottom: 2px solid #e2e8f0;
  text-align: left;
}

.admin-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
  color: #334155;
}

.cat-name {
  color: #0f172a;
  font-weight: 600;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.badge-info {
  background: #e0f2fe;
  color: #0369a1;
}

.badge-success {
  background: #dcfce7;
  color: #15803d;
}

.badge-danger {
  background: #fee2e2;
  color: #b91c1c;
}

.action-cells {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn-action {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action.edit:hover:not(:disabled) {
  background: #f0fdf4;
  color: var(--color-primary, #059669);
  border-color: #bbf7d0;
}

.btn-action.delete:hover:not(:disabled) {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

.btn-action:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: #ffffff;
  width: 100%;
  max-width: 500px;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

/* Responsive */
@media (max-width: 1024px) {
  .bank-config-grid {
    grid-template-columns: 1fr;
  }
  .radio-card-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .form-grid.two-cols {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .unsaved-bar {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  .unsaved-actions {
    justify-content: flex-end;
  }
}
</style>
