<template>
  <div class="car-editor-page">
    <!-- Top Sticky Save / Action Bar -->
    <div class="editor-sticky-bar">
      <div class="bar-left">
        <button type="button" class="btn-back" @click="handleBack" title="Quay lại danh sách xe">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="bar-title-box">
          <div class="bar-breadcrumbs">
            <router-link to="/admin/cars">Quản lý kho xe</router-link>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ isEditMode ? `Cập nhật #CAR-${carId}` : 'Thêm xe mới' }}</span>
          </div>
          <h2 class="bar-car-name">
            {{ form.name || 'Phương tiện chưa đặt tên' }}
            <span v-if="form.license_plate" class="plate-tag">{{ form.license_plate }}</span>
          </h2>
        </div>
      </div>

      <div class="bar-right">
        <!-- Dirty State Indicator -->
        <div class="dirty-indicator">
          <span v-if="saving" class="indicator-pill saving">
            <i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...
          </span>
          <span v-else-if="isDirty" class="indicator-pill dirty">
            <i class="fa-solid fa-circle-dot"></i> Có thay đổi chưa lưu
          </span>
          <span v-else class="indicator-pill synced">
            <i class="fa-solid fa-check"></i> Đã đồng bộ máy chủ
          </span>
        </div>

        <!-- Preview Button -->
        <button type="button" class="btn-secondary btn-preview" @click="showPreviewModal = true">
          <i class="fa-regular fa-eye"></i> Xem trước
        </button>

        <!-- Cancel Button -->
        <button type="button" class="btn-outline" @click="handleCancel" :disabled="saving">
          Hủy bỏ
        </button>

        <!-- Submit Button -->
        <button type="button" class="btn-primary btn-save" @click="saveCar" :disabled="saving">
          <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
          <i v-else class="fa-solid fa-floppy-disk"></i>
          {{ isEditMode ? 'Lưu thay đổi' : 'Tạo mới xe' }}
        </button>
      </div>
    </div>

    <!-- Main Content Container with TOC & Sections -->
    <div class="editor-container">
      <!-- Left Column: Table of Contents (TOC) -->
      <aside class="editor-toc card">
        <div class="toc-header">
          <i class="fa-solid fa-list-check"></i>
          <span>Mục lục hồ sơ</span>
        </div>
        <nav class="toc-nav">
          <a 
            v-for="item in tocItems" 
            :key="item.id" 
            :href="'#' + item.id" 
            class="toc-link"
            :class="{ active: activeSection === item.id }"
            @click.prevent="scrollToSection(item.id)"
          >
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
            <span v-if="hasSectionError(item.id)" class="error-dot" title="Có lỗi nhập liệu">!</span>
          </a>
        </nav>

        <!-- Form Completion Metric -->
        <div class="toc-metric">
          <div class="metric-info">
            <span>Độ hoàn thiện hồ sơ:</span>
            <strong>{{ completionPercentage }}%</strong>
          </div>
          <div class="metric-bar">
            <div class="metric-fill" :style="{ width: completionPercentage + '%' }"></div>
          </div>
          <p class="metric-note">Điền đầy đủ thông số giúp xe nổi bật và tăng tỷ lệ khách chốt thuê.</p>
        </div>
      </aside>

      <!-- Right Column: Sections -->
      <main class="editor-main">
        <!-- Global Error Banner if 422 errors exist -->
        <div v-if="hasErrors" class="alert-banner error-banner animate-in">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div>
            <strong>Thông tin chưa hợp lệ:</strong> Vui lòng kiểm tra lại các trường được đánh dấu đỏ bên dưới.
          </div>
        </div>

        <!-- Section 1: Thông tin cơ bản -->
        <section id="sec-basic" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-car-side"></i>
              <h3>1. Thông tin cơ bản</h3>
            </div>
            <p class="section-desc">Định danh phương tiện, phân loại xe và thông tin nhận diện xe vật lý.</p>
          </div>

          <div class="form-grid">
            <!-- Tên xe -->
            <div class="form-group full" :class="{ 'has-error': formErrors.name }">
              <label>Tên thương mại của xe <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                placeholder="VD: VinFast VF9 Plus AWD 2024" 
                @input="markDirty"
              />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
            </div>

            <!-- Biển số xe vật lý -->
            <div class="form-group" :class="{ 'has-error': formErrors.license_plate }">
              <label>
                Biển số xe vật lý 
                <span class="label-badge">Định danh vật lý</span>
              </label>
              <input 
                type="text" 
                v-model="form.license_plate" 
                placeholder="VD: 51K-888.88 hoặc 30H-999.99" 
                @input="markDirty"
              />
              <small class="helper-text">
                Mỗi bản ghi trong kho đại diện cho 1 xe vật lý cụ thể để đối soát biên bản giao nhận và bảo dưỡng.
              </small>
              <span v-if="formErrors.license_plate" class="field-error">{{ formErrors.license_plate }}</span>
            </div>

            <!-- Phân hạng xe -->
            <div class="form-group" :class="{ 'has-error': formErrors.category_id }">
              <label>Phân hạng danh mục <span class="required">*</span></label>
              <select v-model="form.category_id" @change="onCategoryChange">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <span v-if="formErrors.category_id" class="field-error">{{ formErrors.category_id }}</span>
            </div>

            <!-- Kiểu dáng / Dòng xe -->
            <div class="form-group">
              <label>Kiểu dáng / Dòng xe</label>
              <input 
                type="text" 
                v-model="form.type" 
                placeholder="VD: SUV 7 chỗ, Sedan C, Crossover" 
                @input="markDirty"
              />
            </div>

            <!-- Số km hiện tại -->
            <div class="form-group">
              <label>Số km tích lũy hiện tại (Odo)</label>
              <input 
                type="number" 
                v-model.number="form.current_km" 
                placeholder="VD: 15200" 
                min="0"
                @input="markDirty"
              />
              <small class="helper-text">Hệ thống dùng số km này để tự động điền vào biên bản giao xe ban đầu.</small>
            </div>

            <!-- Mô tả chi tiết -->
            <div class="form-group full">
              <label>Mô tả chi tiết phương tiện & ưu điểm nổi bật</label>
              <textarea 
                v-model="form.description" 
                rows="4" 
                placeholder="Mô tả các trang bị tiện nghi cao cấp, hệ thống an toàn ADAS, trải nghiệm êm ái, phù hợp công tác hay du lịch gia đình..."
                class="form-textarea"
                @input="markDirty"
              ></textarea>
            </div>
          </div>
        </section>

        <!-- Section 2: Giá & Địa điểm -->
        <section id="sec-pricing" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-coins"></i>
              <h3>2. Giá thuê & Địa điểm phục vụ</h3>
            </div>
            <p class="section-desc">Mức giá niêm yết theo ngày và phạm vi điều phối phương tiện.</p>
          </div>

          <div class="form-grid">
            <div class="form-group" :class="{ 'has-error': formErrors.price_per_day }">
              <label>Giá thuê niêm yết (VNĐ / Ngày) <span class="required">*</span></label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="form.price_per_day" 
                  min="50000" 
                  step="50000" 
                  @input="markDirty"
                />
                <span class="currency-tag">VNĐ</span>
              </div>
              <div class="price-formatted-preview">
                Hiển thị cho khách: <strong>{{ formatCurrency(form.price_per_day || 0) }} / ngày</strong>
              </div>
              <span v-if="formErrors.price_per_day" class="field-error">{{ formErrors.price_per_day }}</span>
            </div>

            <div class="form-group" :class="{ 'has-error': formErrors.city }">
              <label>Khu vực / Địa bàn phục vụ <span class="required">*</span></label>
              <select v-model="form.city" @change="markDirty">
                <option value="sg">TP. Hồ Chí Minh (sg)</option>
                <option value="hn">Hà Nội (hn)</option>
                <option value="dn">Đà Nẵng (dn)</option>
                <option value="all">Toàn quốc (all)</option>
              </select>
              <small class="helper-text">Khách hàng lọc theo thành phố này sẽ tìm thấy xe tương ứng.</small>
              <span v-if="formErrors.city" class="field-error">{{ formErrors.city }}</span>
            </div>
          </div>
        </section>

        <!-- Section 3: Thông số kỹ thuật -->
        <section id="sec-specs" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-sliders"></i>
              <h3>3. Thông số kỹ thuật</h3>
            </div>
            <p class="section-desc">
              Các thông số kỹ thuật thực tế của xe. Để trống nếu chưa xác định, không tự tạo thông số giả.
            </p>
          </div>

          <div class="form-grid">
            <!-- Nhiên liệu -->
            <div class="form-group">
              <label>Loại nhiên liệu / Động cơ <span class="required">*</span></label>
              <select v-model="form.fuel_type" @change="markDirty">
                <option value="electric">Điện 100% (EV)</option>
                <option value="gasoline">Xăng</option>
                <option value="diesel">Dầu Diesel</option>
                <option value="hybrid">Động cơ Hybrid</option>
              </select>
            </div>

            <!-- Chỗ ngồi -->
            <div class="form-group">
              <label>Số chỗ ngồi</label>
              <input 
                type="number" 
                v-model.number="form.seats" 
                placeholder="VD: 5 hoặc 7" 
                min="2" 
                max="50" 
                @input="markDirty"
              />
            </div>

            <!-- Hộp số -->
            <div class="form-group">
              <label>Hộp số</label>
              <input 
                type="text" 
                v-model="form.transmission" 
                placeholder="VD: Tự động, Tự động 1 cấp, Số sàn" 
                @input="markDirty"
              />
            </div>

            <!-- Năm sản xuất -->
            <div class="form-group">
              <label>Năm sản xuất / Đời xe</label>
              <input 
                type="number" 
                v-model.number="form.year_of_manufacture" 
                placeholder="VD: 2024" 
                min="2000" 
                max="2035" 
                @input="markDirty"
              />
            </div>

            <!-- Pin (Chỉ hiển thị cho xe điện hoặc hybrid) -->
            <div class="form-group" v-if="form.fuel_type === 'electric' || form.fuel_type === 'hybrid'">
              <label>Dung lượng Pin</label>
              <input 
                type="text" 
                v-model="form.battery_capacity" 
                placeholder="VD: 87.7 kWh, 123 kWh" 
                @input="markDirty"
              />
            </div>

            <!-- Tầm hoạt động / Tiêu hao -->
            <div class="form-group">
              <label>Tầm hoạt động / Mức tiêu thụ</label>
              <input 
                type="text" 
                v-model="form.range_per_charge" 
                placeholder="VD: 438 km (NEDC) hoặc 6.5L/100km" 
                @input="markDirty"
              />
            </div>
          </div>
        </section>

        <!-- Section 4: Ảnh đại diện & Thư viện ảnh -->
        <section id="sec-media" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-images"></i>
              <h3>4. Ảnh đại diện & Thư viện ảnh</h3>
            </div>
            <p class="section-desc">Tải ảnh chất lượng cao để hiển thị trên website và ứng dụng di động.</p>
          </div>

          <!-- Main Thumbnail -->
          <div class="media-box-main">
            <label class="media-title">Ảnh đại diện chính của xe (Thumbnail) <span class="required">*</span></label>
            <div class="thumb-upload-wrapper">
              <div class="thumb-preview-box">
                <img 
                  :src="resolveCarImage({ image: form.image, name: form.name })" 
                  alt="Car Thumbnail" 
                  class="thumb-img"
                  @error="handleCarImgError($event)"
                />
              </div>

              <div class="thumb-controls">
                <div class="upload-btn-row">
                  <label class="btn-secondary btn-file-upload" :class="{ disabled: uploadingMain }">
                    <i v-if="uploadingMain" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-cloud-arrow-up"></i>
                    <span>{{ uploadingMain ? 'Đang tải ảnh...' : 'Tải ảnh từ máy tính' }}</span>
                    <input 
                      type="file" 
                      accept="image/png,image/jpeg,image/webp" 
                      @change="onUploadMainImage" 
                      :disabled="uploadingMain" 
                      hidden
                    />
                  </label>
                  <small class="helper-text">Định dạng hỗ trợ: JPG, PNG, WEBP (Tối đa 5MB)</small>
                </div>

                <div class="url-input-group mt-2">
                  <label class="sub-label">Hoặc nhập đường dẫn URL ảnh trực tiếp:</label>
                  <input 
                    type="text" 
                    v-model="form.image" 
                    placeholder="https://... hoặc /assets/images/vf9.png" 
                    @input="markDirty"
                  />
                </div>
              </div>
            </div>
            <span v-if="formErrors.image" class="field-error mt-1">{{ formErrors.image }}</span>
          </div>

          <div class="divider"></div>

          <!-- Gallery List -->
          <div class="gallery-management-box">
            <div class="gallery-header-row">
              <div>
                <label class="media-title">Thư viện ảnh chi tiết (Gallery)</label>
                <p class="sub-label">Bộ ảnh ngoại thất, nội thất, khoang hành lý phục vụ trang chi tiết xe.</p>
              </div>

              <label class="btn-secondary btn-file-upload btn-sm" :class="{ disabled: uploadingGallery }">
                <i v-if="uploadingGallery" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-plus"></i>
                <span>{{ uploadingGallery ? 'Đang tải...' : 'Thêm ảnh từ máy' }}</span>
                <input 
                  type="file" 
                  accept="image/png,image/jpeg,image/webp" 
                  multiple 
                  @change="onUploadGalleryImages" 
                  :disabled="uploadingGallery" 
                  hidden
                />
              </label>
            </div>

            <!-- Quick Add URL input -->
            <div class="gallery-quick-add">
              <input 
                type="text" 
                v-model="quickGalleryUrl" 
                placeholder="Dán đường dẫn URL ảnh chi tiết và bấm Thêm..." 
                @keydown.enter.prevent="addQuickGalleryUrl"
              />
              <button type="button" class="btn-outline btn-sm" @click="addQuickGalleryUrl">
                <i class="fa-solid fa-link"></i> Thêm URL
              </button>
            </div>

            <!-- Gallery Grid -->
            <div v-if="galleryList.length > 0" class="gallery-grid">
              <div 
                v-for="(imgUrl, idx) in galleryList" 
                :key="idx" 
                class="gallery-item-card"
              >
                <img 
                  :src="imgUrl" 
                  alt="Gallery Item" 
                  class="gallery-item-img" 
                  @error="handleCarImgError($event)"
                />
                <div class="gallery-actions-overlay">
                  <span class="gallery-index-badge">#{{ idx + 1 }}</span>
                  <button 
                    type="button" 
                    class="btn-gallery-action delete" 
                    @click="removeGalleryImage(idx)" 
                    title="Xóa ảnh này"
                  >
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="empty-gallery-box">
              <i class="fa-regular fa-image"></i>
              <p>Chưa có ảnh chi tiết nào trong thư viện. Hãy tải ảnh lên hoặc thêm đường dẫn URL.</p>
            </div>
          </div>
        </section>

        <!-- Section 5: Điều kiện thuê xe -->
        <section id="sec-conditions" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-file-contract"></i>
              <h3>5. Điều kiện & Yêu cầu thuê xe</h3>
            </div>
            <p class="section-desc">Hợp đồng, giấy tờ bắt buộc và số tiền cọc bảo đảm đối với khách hàng.</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Hạng bằng lái xe tối thiểu</label>
              <select v-model="rentalConditions.requires_license" @change="markDirty">
                <option value="B1">Bằng B1 (Số tự động)</option>
                <option value="B2">Bằng B2 (Số sàn & tự động phổ thông)</option>
                <option value="C">Bằng C (Xe tải / Xe chuyên dụng)</option>
                <option value="D">Bằng D (Xe khách 16-30 chỗ)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Số tiền cọc thế chấp bảo đảm (VNĐ)</label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="rentalConditions.deposit_amount" 
                  min="0" 
                  step="1000000" 
                  @input="markDirty"
                />
                <span class="currency-tag">VNĐ</span>
              </div>
              <small class="helper-text">
                Hiển thị: <strong>{{ formatCurrency(rentalConditions.deposit_amount || 0) }}</strong>
              </small>
            </div>

            <div class="form-group">
              <label>Độ tuổi tối thiểu của người lái</label>
              <input 
                type="number" 
                v-model.number="rentalConditions.minimum_age" 
                min="18" 
                max="70" 
                placeholder="VD: 21 tuổi" 
                @input="markDirty"
              />
            </div>

            <div class="form-group">
              <label>Giới hạn phạm vi lưu thông</label>
              <input 
                type="text" 
                v-model="rentalConditions.travel_area" 
                placeholder="VD: Toàn quốc, hoặc Bán kính 300km từ điểm nhận" 
                @input="markDirty"
              />
            </div>

            <div class="form-group full">
              <label class="form-check-label">
                <input 
                  type="checkbox" 
                  v-model="rentalConditions.requires_id_card" 
                  @change="markDirty"
                />
                <span><strong>Bắt buộc CCCD gắn chip hoặc Hộ chiếu gốc</strong> khi nhận xe</span>
              </label>
            </div>
          </div>
        </section>

        <!-- Section 6: Phí bổ sung & Phụ thu -->
        <section id="sec-fees" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-receipt"></i>
              <h3>6. Phí bổ sung & Chính sách phụ thu</h3>
            </div>
            <p class="section-desc">Quy định minh bạch về giới hạn km và phí phụ trội khi trả xe.</p>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Giới hạn quãng đường mỗi ngày</label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="extraFeesInfo.daily_km_limit" 
                  min="50" 
                  step="50" 
                  @input="markDirty"
                />
                <span class="currency-tag">km / ngày</span>
              </div>
            </div>

            <div class="form-group">
              <label>Phí vượt quãng đường quy định</label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="extraFeesInfo.excess_km_rate" 
                  min="0" 
                  step="500" 
                  @input="markDirty"
                />
                <span class="currency-tag">VNĐ / km</span>
              </div>
            </div>

            <div class="form-group">
              <label>Phí quá giờ trả xe</label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="extraFeesInfo.late_fee_hourly" 
                  min="0" 
                  step="10000" 
                  @input="markDirty"
                />
                <span class="currency-tag">VNĐ / giờ</span>
              </div>
            </div>

            <div class="form-group">
              <label>Phí vệ sinh nếu bẩn / mùi thức ăn / khói thuốc</label>
              <div class="input-currency-wrapper">
                <input 
                  type="number" 
                  v-model.number="extraFeesInfo.cleaning_fee" 
                  min="0" 
                  step="50000" 
                  @input="markDirty"
                />
                <span class="currency-tag">VNĐ / lần</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Section 7: Hiển thị & Vận hành -->
        <section id="sec-publication" class="editor-section card">
          <div class="section-header">
            <div class="section-title">
              <i class="fa-solid fa-toggle-on"></i>
              <h3>7. Trạng thái vận hành & Xuất bản website</h3>
            </div>
            <p class="section-desc">Kiểm soát tính khả dụng thực tế của xe và trạng thái hiển thị công khai.</p>
          </div>

          <div class="form-grid">
            <!-- Vận hành kỹ thuật -->
            <div class="form-group">
              <label>Trạng thái vận hành kỹ thuật <span class="required">*</span></label>
              <select v-model="form.status" @change="markDirty">
                <option value="available">Sẵn sàng (Xe hoạt động bình thường, sẵn sàng giao)</option>
                <option value="maintenance">Bảo trì (Đang bảo dưỡng, kỹ thuật, tạm ngưng nhận khách)</option>
              </select>
            </div>

            <!-- Xuất bản nội dung -->
            <div class="form-group">
              <label>Trạng thái xuất bản nội dung <span class="required">*</span></label>
              <select v-model="form.publication_status" @change="onPublicationStatusChange">
                <option value="published">Đã xuất bản (Công khai hiển thị và nhận khách thuê)</option>
                <option value="draft">Bản nháp (Đang cập nhật, ẩn khỏi trang khách hàng)</option>
                <option value="archived">Lưu trữ (Ẩn khỏi website, ngừng kinh doanh)</option>
              </select>
            </div>

            <!-- Kích hoạt -->
            <div class="form-group full">
              <label class="form-check-label">
                <input 
                  type="checkbox" 
                  v-model="form.is_active" 
                  @change="markDirty"
                />
                <span><strong>Đang mở kinh doanh phục vụ khách</strong> (Bỏ chọn nếu muốn tạm ẩn khỏi catalog)</span>
              </label>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Live Preview Modal -->
    <div v-if="showPreviewModal" class="modal-overlay" @click.self="showPreviewModal = false">
      <div class="modal-content preview-modal card animate-in">
        <div class="modal-header">
          <div>
            <h3><i class="fa-regular fa-eye text-primary"></i> Xem trước Hồ sơ xe trên Website</h3>
            <p class="modal-sub">Mô phỏng chính xác giao diện trang chi tiết xe hiển thị cho khách hàng</p>
          </div>
          <button class="btn-close" @click="showPreviewModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body preview-modal-body">
          <div class="preview-layout">
            <!-- Left: Car Images Mockup -->
            <div class="preview-media-col">
              <div class="preview-main-img-box">
                <img 
                  :src="resolveCarImage({ image: form.image, name: form.name })" 
                  alt="Main Preview" 
                  class="preview-main-img" 
                  @error="handleCarImgError($event)"
                />
              </div>

              <!-- Gallery thumbs -->
              <div v-if="galleryList.length > 0" class="preview-thumbs-row">
                <img 
                  v-for="(img, idx) in galleryList" 
                  :key="idx" 
                  :src="img" 
                  alt="Thumb" 
                  class="preview-mini-thumb" 
                  @error="handleCarImgError($event)"
                />
              </div>
            </div>

            <!-- Right: Car Info Mockup -->
            <div class="preview-info-col">
              <div class="preview-badge-row">
                <span class="preview-cat-badge">{{ getSelectedCategoryName() }}</span>
                <span v-if="form.license_plate" class="preview-plate-badge">{{ form.license_plate }}</span>
                <span class="preview-pub-badge" :class="'pub-' + (form.publication_status || 'published')">
                  {{ form.publication_status === 'published' ? 'Đã xuất bản' : 'Bản nháp' }}
                </span>
              </div>

              <h2 class="preview-car-name">{{ form.name || 'VinFast VF9 Plus' }}</h2>
              <div class="preview-price-row">
                <span class="preview-price-val">{{ formatCurrency(form.price_per_day || 0) }}</span>
                <small>/ ngày</small>
                <span class="preview-city"><i class="fa-solid fa-location-dot"></i> {{ getCityLabel(form.city) }}</span>
              </div>

              <!-- Specs Grid -->
              <div class="preview-specs-grid">
                <div class="spec-pill">
                  <i class="fa-solid fa-users"></i>
                  <span>{{ form.seats ? `${form.seats} Chỗ ngồi` : 'Chưa định chỗ' }}</span>
                </div>
                <div class="spec-pill">
                  <i class="fa-solid fa-bolt text-success"></i>
                  <span>{{ getFuelTypeLabel(form.fuel_type) }}</span>
                </div>
                <div class="spec-pill" v-if="form.battery_capacity">
                  <i class="fa-solid fa-battery-three-quarters text-primary"></i>
                  <span>{{ form.battery_capacity }}</span>
                </div>
                <div class="spec-pill" v-if="form.range_per_charge">
                  <i class="fa-solid fa-road"></i>
                  <span>{{ form.range_per_charge }}</span>
                </div>
                <div class="spec-pill" v-if="form.transmission">
                  <i class="fa-solid fa-gear"></i>
                  <span>{{ form.transmission }}</span>
                </div>
              </div>

              <!-- Description -->
              <div class="preview-desc-box">
                <h4>Mô tả phương tiện</h4>
                <p>{{ form.description || 'Chưa có mô tả chi tiết phương tiện.' }}</p>
              </div>

              <!-- Conditions & Fees Brief -->
              <div class="preview-terms-box">
                <h4>Điều kiện & Phụ phí</h4>
                <ul>
                  <li>Tiền đặt cọc bảo đảm: <strong>{{ formatCurrency(rentalConditions.deposit_amount || 0) }}</strong></li>
                  <li>Bằng lái yêu cầu: <strong>Hạng {{ rentalConditions.requires_license || 'B2' }}</strong></li>
                  <li>Giới hạn quãng đường: <strong>{{ extraFeesInfo.daily_km_limit || 350 }} km / ngày</strong> (Vượt: {{ formatCurrency(extraFeesInfo.excess_km_rate || 3000) }}/km)</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-primary" @click="showPreviewModal = false">
            Đóng xem trước
          </button>
        </div>
      </div>
    </div>

    <!-- Concurrency Conflict Modal (409) -->
    <div v-if="showConflictModal" class="modal-overlay" @click.self="showConflictModal = false">
      <div class="modal-content card conflict-modal animate-in">
        <div class="modal-header">
          <div class="conflict-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3 class="text-danger">Xung Đột Phiên Làm Việc (Optimistic Lock)</h3>
            <p class="modal-sub">Dữ liệu xe này đã bị quản trị viên khác chỉnh sửa trước đó</p>
          </div>
        </div>

        <div class="modal-body">
          <p>Dữ liệu xe <strong>#CAR-{{ carId }}</strong> trên máy chủ đã thay đổi vào lúc <code>{{ formatDateTime(conflictServerUpdatedAt) }}</code>.</p>
          <div class="conflict-alert-box">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
              <strong>Dữ liệu của bạn được giữ nguyên vẹn:</strong> Hệ thống từ chối ghi đè âm thầm để tránh làm mất các thông tin bạn vừa nhập.
            </div>
          </div>
          <p class="mt-2 text-muted">Bạn có thể chọn tải lại thông tin mới nhất từ máy chủ, hoặc hủy bỏ.</p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="showConflictModal = false">
            Giữ dữ liệu hiện tại & Đóng
          </button>
          <button type="button" class="btn-danger" @click="reloadServerData">
            <i class="fa-solid fa-rotate"></i> Tải lại dữ liệu mới nhất từ máy chủ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted, reactive, watch } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { resolveCarImage, handleCarImgError } from '../../../core/carImageHelper';

const toaster = createToaster({ position: "top-right" });
const route = useRoute();
const router = useRouter();

// Mode detection
const carId = computed(() => route.params.id ? Number(route.params.id) : null);
const isEditMode = computed(() => Boolean(carId.value));

// Categories
const categories = ref([]);

// Form State
const form = reactive({
  id: null,
  name: '',
  license_plate: '',
  price_per_day: 1200000,
  current_km: 0,
  type: 'SUV Điện',
  city: 'sg',
  status: 'available',
  publication_status: 'published',
  image: '/assets/images/vf9.png',
  category_id: 1,
  fuel_type: 'electric',
  seats: 7,
  battery_capacity: '',
  range_per_charge: '',
  transmission: 'Tự động',
  year_of_manufacture: 2024,
  description: '',
  is_active: true,
  updated_at: null,
});

// JSON Objects
const galleryList = ref([]);
const rentalConditions = reactive({
  requires_id_card: true,
  requires_license: 'B2',
  deposit_amount: 15000000,
  minimum_age: 21,
  travel_area: 'Toàn quốc'
});

const extraFeesInfo = reactive({
  daily_km_limit: 350,
  excess_km_rate: 3000,
  late_fee_hourly: 100000,
  cleaning_fee: 200000
});

// UI & Upload States
const quickGalleryUrl = ref('');
const uploadingMain = ref(false);
const uploadingGallery = ref(false);
const saving = ref(false);
const formErrors = ref({});
const isDirty = ref(false);
const activeSection = ref('sec-basic');

// Modal States
const showPreviewModal = ref(false);
const showConflictModal = ref(false);
const conflictServerUpdatedAt = ref('');

// Table of Contents definition
const tocItems = [
  { id: 'sec-basic', label: '1. Thông tin cơ bản', icon: 'fa-solid fa-car-side' },
  { id: 'sec-pricing', label: '2. Giá & Địa điểm', icon: 'fa-solid fa-coins' },
  { id: 'sec-specs', label: '3. Thông số kỹ thuật', icon: 'fa-solid fa-sliders' },
  { id: 'sec-media', label: '4. Ảnh & Gallery', icon: 'fa-solid fa-images' },
  { id: 'sec-conditions', label: '5. Điều kiện thuê', icon: 'fa-solid fa-file-contract' },
  { id: 'sec-fees', label: '6. Phí bổ sung', icon: 'fa-solid fa-receipt' },
  { id: 'sec-publication', label: '7. Vận hành & Xuất bản', icon: 'fa-solid fa-toggle-on' }
];

// Helper Formats
const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleString('vi-VN', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
    day: '2-digit', month: '2-digit', year: 'numeric'
  });
};

const getCityLabel = (code) => {
  if (!code) return 'Toàn quốc';
  const c = code.toLowerCase();
  if (c === 'sg' || c.includes('hồ chí minh')) return 'TP. HCM';
  if (c === 'hn' || c.includes('hà nội')) return 'Hà Nội';
  if (c === 'dn' || c.includes('đà nẵng')) return 'Đà Nẵng';
  return code.toUpperCase();
};

const getFuelTypeLabel = (code) => {
  switch (code) {
    case 'electric': return 'Điện (EV)';
    case 'gasoline': return 'Xăng';
    case 'diesel': return 'Dầu Diesel';
    case 'hybrid': return 'Hybrid';
    default: return code || 'Điện (EV)';
  }
};

const getSelectedCategoryName = () => {
  const cat = categories.value.find(c => c.id === form.category_id);
  return cat ? cat.name : 'Tiêu chuẩn';
};

// Form completion calculation
const completionPercentage = computed(() => {
  let filled = 0;
  let total = 8;
  if (form.name?.trim()) filled++;
  if (form.price_per_day > 0) filled++;
  if (form.category_id) filled++;
  if (form.image?.trim()) filled++;
  if (form.seats) filled++;
  if (form.description?.trim()) filled++;
  if (galleryList.value.length > 0) filled++;
  if (rentalConditions.deposit_amount > 0) filled++;
  return Math.round((filled / total) * 100);
});

const hasErrors = computed(() => Object.keys(formErrors.value).length > 0);

const hasSectionError = (secId) => {
  const errs = formErrors.value;
  if (secId === 'sec-basic') return Boolean(errs.name || errs.license_plate || errs.category_id);
  if (secId === 'sec-pricing') return Boolean(errs.price_per_day || errs.city);
  if (secId === 'sec-media') return Boolean(errs.image || errs.gallery);
  return false;
};

// Dirty tracker
const markDirty = () => {
  isDirty.value = true;
};

// Scroll to Section
const scrollToSection = (secId) => {
  activeSection.value = secId;
  const el = document.getElementById(secId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

// Handle category change
const onCategoryChange = () => {
  markDirty();
  const cat = categories.value.find(c => c.id === form.category_id);
  if (cat && (!form.type || form.type === 'Sedan' || form.type === 'SUV Điện')) {
    form.type = cat.name;
  }
};

const onPublicationStatusChange = () => {
  markDirty();
  form.is_active = (form.publication_status === 'published');
};

// Quick add gallery URL
const addQuickGalleryUrl = () => {
  const url = quickGalleryUrl.value.trim();
  if (!url) return;
  galleryList.value.push(url);
  quickGalleryUrl.value = '';
  markDirty();
};

const removeGalleryImage = (index) => {
  galleryList.value.splice(index, 1);
  markDirty();
};

// Upload Main Image via real API
const onUploadMainImage = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toaster.error("Kích thước tệp không được vượt quá 5MB!");
    return;
  }

  const formData = new FormData();
  formData.append('image', file);

  uploadingMain.value = true;
  try {
    const res = await baseRequest.post('admin/cars/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (res.data && res.data.status && res.data.url) {
      form.image = res.data.url;
      markDirty();
      toaster.success("Tải ảnh đại diện thành công!");
    } else {
      toaster.error(res.data?.message || "Không thể lưu tệp ảnh lên máy chủ.");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi tải ảnh lên máy chủ!");
  } finally {
    uploadingMain.value = false;
    e.target.value = '';
  }
};

// Upload Gallery Images via real API
const onUploadGalleryImages = async (e) => {
  const files = e.target.files;
  if (!files || files.length === 0) return;

  uploadingGallery.value = true;
  let successCount = 0;

  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    if (file.size > 5 * 1024 * 1024) {
      toaster.warning(`Tệp ${file.name} vượt quá 5MB nên bị bỏ qua.`);
      continue;
    }

    const formData = new FormData();
    formData.append('image', file);

    try {
      const res = await baseRequest.post('admin/cars/upload-image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      if (res.data && res.data.status && res.data.url) {
        galleryList.value.push(res.data.url);
        successCount++;
      }
    } catch (err) {
      console.error("Gallery upload error:", err);
    }
  }

  uploadingGallery.value = false;
  e.target.value = '';
  if (successCount > 0) {
    markDirty();
    toaster.success(`Đã tải lên thành công ${successCount} ảnh vào thư viện!`);
  }
};

// Fetch initial data
const fetchCategories = async () => {
  try {
    const res = await baseRequest.get('client/categories');
    if (res.data && res.data.status) {
      categories.value = res.data.data;
    }
  } catch (err) {
    // Silently continue
  }
};

const fetchCarDetail = async () => {
  if (!carId.value) return;
  try {
    const res = await baseRequest.get(`admin/cars/detail/${carId.value}`);
    if (res.data && res.data.status && res.data.data) {
      const c = res.data.data;
      form.id = c.id;
      form.name = c.name || '';
      form.license_plate = c.license_plate || '';
      form.price_per_day = Number(c.price_per_day) || 1200000;
      form.current_km = Number(c.current_km) || 0;
      form.type = c.type || '';
      form.city = c.city || 'sg';
      form.status = c.status || 'available';
      form.publication_status = c.publication_status || 'published';
      form.image = c.image || '/assets/images/vf9.png';
      form.category_id = c.category_id || 1;
      form.fuel_type = c.fuel_type || 'electric';
      form.seats = c.seats ?? null;
      form.battery_capacity = c.battery_capacity || '';
      form.range_per_charge = c.range_per_charge || '';
      form.transmission = c.transmission || '';
      form.year_of_manufacture = c.year_of_manufacture ?? null;
      form.description = c.description || '';
      form.is_active = c.is_active !== undefined ? Boolean(c.is_active) : true;
      form.updated_at = c.updated_at || null;

      // Gallery
      if (Array.isArray(c.gallery)) {
        galleryList.value = [...c.gallery];
      } else if (typeof c.gallery === 'string') {
        try {
          galleryList.value = JSON.parse(c.gallery);
        } catch {
          galleryList.value = c.gallery.split('\n').filter(Boolean);
        }
      }

      // Rental conditions
      if (c.rental_conditions && typeof c.rental_conditions === 'object') {
        Object.assign(rentalConditions, c.rental_conditions);
      }

      // Extra fees info
      if (c.extra_fees_info && typeof c.extra_fees_info === 'object') {
        Object.assign(extraFeesInfo, c.extra_fees_info);
      }

      // Reset dirty flag after load
      isDirty.value = false;
    } else {
      toaster.error("Không tìm thấy thông tin xe cần sửa.");
      router.push('/admin/cars');
    }
  } catch (err) {
    toaster.error("Lỗi khi tải thông tin chi tiết xe từ máy chủ!");
    router.push('/admin/cars');
  }
};

// Save Car logic with 422 mapping and 409 concurrency handling
const saveCar = async () => {
  formErrors.value = {};

  // Client validation
  if (!form.name?.trim()) {
    formErrors.value.name = "Vui lòng nhập tên thương mại của xe.";
    scrollToSection('sec-basic');
    return;
  }
  if (!form.price_per_day || form.price_per_day <= 0) {
    formErrors.value.price_per_day = "Giá thuê mỗi ngày phải lớn hơn 0 VNĐ.";
    scrollToSection('sec-pricing');
    return;
  }

  saving.value = true;
  const payload = {
    ...form,
    gallery: galleryList.value,
    rental_conditions: { ...rentalConditions },
    extra_fees_info: { ...extraFeesInfo },
  };

  const url = isEditMode.value ? 'admin/cars/update' : 'admin/cars/create';

  try {
    const res = await baseRequest.post(url, payload);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || (isEditMode.value ? "Cập nhật xe thành công!" : "Tạo mới xe thành công!"));
      isDirty.value = false;
      if (!isEditMode.value && res.data.data?.id) {
        // Redirect to edit mode for the newly created car
        router.push(`/admin/cars/edit/${res.data.data.id}`);
      } else if (res.data.data?.updated_at) {
        form.updated_at = res.data.data.updated_at;
      }
    } else {
      toaster.error(res.data?.message || "Không thể lưu thông tin xe.");
    }
  } catch (err) {
    // 409 Concurrency Conflict
    if (err.response?.status === 409 && err.response?.data?.error_type === 'CONCURRENCY_CONFLICT') {
      conflictServerUpdatedAt.value = err.response.data.current_updated_at || '';
      showConflictModal.value = true;
      return;
    }

    // 422 Validation Error
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const errs = err.response.data.errors;
      formErrors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
      toaster.error("Dữ liệu nhập vào chưa hợp lệ. Vui lòng kiểm tra lại!");
      
      // Auto-scroll to first error section
      if (formErrors.value.name || formErrors.value.category_id) scrollToSection('sec-basic');
      else if (formErrors.value.price_per_day || formErrors.value.city) scrollToSection('sec-pricing');
      else if (formErrors.value.image) scrollToSection('sec-media');
      return;
    }

    toaster.error(err.response?.data?.message || "Có lỗi xảy ra khi lưu dữ liệu lên máy chủ.");
  } finally {
    saving.value = false;
  }
};

// Reload Server Data on conflict
const reloadServerData = async () => {
  showConflictModal.value = false;
  await fetchCarDetail();
  toaster.info("Đã tải lại dữ liệu mới nhất từ máy chủ.");
};

// Navigation guard
const handleBack = () => {
  if (isDirty.value) {
    if (!confirm("Bạn có những thay đổi chưa được lưu. Rời trang sẽ mất dữ liệu này. Bạn có chắc chắn muốn rời đi?")) {
      return;
    }
  }
  router.push('/admin/cars');
};

const handleCancel = () => {
  handleBack();
};

onBeforeRouteLeave((to, from, next) => {
  if (isDirty.value && !saving.value) {
    const answer = window.confirm("Bạn có thay đổi chưa lưu. Bạn có chắc chắn muốn rời khỏi trang biên tập xe?");
    if (answer) {
      next();
    } else {
      next(false);
    }
  } else {
    next();
  }
});

const handleBeforeUnload = (e) => {
  if (isDirty.value) {
    e.preventDefault();
    e.returnValue = '';
  }
};

onMounted(async () => {
  await fetchCategories();
  if (isEditMode.value) {
    await fetchCarDetail();
  }
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<style scoped>
.car-editor-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 80px;
}

/* Sticky Action Bar */
.editor-sticky-bar {
  position: sticky;
  top: 0;
  z-index: 90;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  padding: 14px 24px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  flex-wrap: wrap;
  gap: 14px;
}

.bar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.btn-back {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-back:hover {
  background: white;
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.bar-breadcrumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.bar-breadcrumbs a {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 600;
}

.bar-car-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main);
  margin-top: 2px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.plate-tag {
  display: inline-block;
  padding: 2px 8px;
  border-radius: var(--radius-xs);
  background: #FEF3C7;
  color: #92400E;
  border: 1px solid #FCD34D;
  font-size: 0.8rem;
  font-family: monospace;
  font-weight: 700;
}

.bar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dirty-indicator {
  display: flex;
  align-items: center;
}

.indicator-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 700;
}

.indicator-pill.synced { background: #ECFDF5; color: #047857; }
.indicator-pill.dirty { background: #FEF3C7; color: #B45309; }
.indicator-pill.saving { background: #EFF6FF; color: var(--color-primary); }

.btn-preview {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-save {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  font-weight: 700;
}

/* Two Column Layout */
.editor-container {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 24px;
  align-items: start;
}

/* Left TOC */
.editor-toc {
  position: sticky;
  top: 90px;
  padding: 18px;
}

.toc-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  color: var(--color-text-main);
  font-size: 0.95rem;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--color-border);
}

.toc-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toc-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  color: var(--color-text-muted);
  text-decoration: none;
  font-size: 0.84rem;
  font-weight: 600;
  transition: all 0.2s;
}

.toc-link i {
  width: 20px;
  color: var(--color-text-muted);
}

.toc-link:hover {
  background: var(--bg-surface-alt);
  color: var(--color-text-main);
}

.toc-link.active {
  background: #ECFDF5;
  color: var(--color-primary);
  font-weight: 700;
}

.toc-link.active i {
  color: var(--color-primary);
}

.error-dot {
  width: 18px;
  height: 18px;
  background: #DC2626;
  color: white;
  border-radius: 50%;
  font-size: 0.72rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
}

.toc-metric {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.metric-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-bottom: 6px;
}

.metric-bar {
  height: 6px;
  background: #E2E8F0;
  border-radius: 9999px;
  overflow: hidden;
}

.metric-fill {
  height: 100%;
  background: var(--color-primary);
  transition: width 0.3s ease;
}

.metric-note {
  font-size: 0.74rem;
  color: var(--color-text-muted);
  margin-top: 8px;
  line-height: 1.4;
}

/* Right Main Sections */
.editor-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.editor-section {
  padding: 24px;
  scroll-margin-top: 90px;
}

.section-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--color-text-main);
}

.section-title i {
  color: var(--color-primary);
  font-size: 1.15rem;
}

.section-title h3 {
  font-size: 1.15rem;
  font-weight: 800;
}

.section-desc {
  font-size: 0.84rem;
  color: var(--color-text-muted);
  margin-top: 4px;
}

/* Forms */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group.full {
  grid-column: span 2;
}

.form-group label {
  font-size: 0.84rem;
  font-weight: 700;
  color: var(--color-text-main);
  display: flex;
  align-items: center;
  gap: 8px;
}

.label-badge {
  padding: 2px 6px;
  background: #EFF6FF;
  color: var(--color-primary);
  border-radius: var(--radius-xs);
  font-size: 0.7rem;
  font-weight: 700;
}

.form-group input,
.form-group select,
.form-textarea {
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.88rem;
  color: var(--color-text-main);
  outline: none;
  transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.has-error input,
.has-error select,
.has-error textarea {
  border-color: #DC2626 !important;
  background: #FEF2F2 !important;
}

.required { color: #DC2626; }
.field-error { font-size: 0.78rem; color: #DC2626; font-weight: 600; }
.helper-text { font-size: 0.78rem; color: var(--color-text-muted); line-height: 1.4; }

.input-currency-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-currency-wrapper input {
  width: 100%;
  padding-right: 60px;
}

.currency-tag {
  position: absolute;
  right: 14px;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-text-muted);
  pointer-events: none;
}

.price-formatted-preview {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin-top: 4px;
}

.price-formatted-preview strong {
  color: var(--color-primary);
  font-size: 0.95rem;
}

/* Media Box */
.thumb-upload-wrapper {
  display: flex;
  gap: 20px;
  align-items: flex-start;
  margin-top: 8px;
}

.thumb-preview-box {
  width: 180px;
  height: 110px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
  flex-shrink: 0;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumb-controls {
  flex: 1;
}

.btn-file-upload {
  padding: 8px 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.btn-file-upload.disabled {
  opacity: 0.5;
  pointer-events: none;
}

.divider {
  height: 1px;
  background: var(--color-border);
  margin: 24px 0;
}

/* Gallery Management */
.gallery-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.gallery-quick-add {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.gallery-quick-add input {
  flex: 1;
  padding: 8px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  font-size: 0.84rem;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 14px;
}

.gallery-item-card {
  position: relative;
  height: 90px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
}

.gallery-item-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gallery-actions-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 6px;
  opacity: 0;
  transition: opacity 0.2s;
}

.gallery-item-card:hover .gallery-actions-overlay {
  opacity: 1;
}

.gallery-index-badge {
  background: rgba(255, 255, 255, 0.9);
  color: var(--color-text-main);
  padding: 2px 6px;
  border-radius: var(--radius-xs);
  font-size: 0.7rem;
  font-weight: 700;
}

.btn-gallery-action.delete {
  background: #DC2626;
  color: white;
  border: none;
  width: 26px;
  height: 26px;
  border-radius: var(--radius-xs);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.empty-gallery-box {
  padding: 28px;
  text-align: center;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border: 1px dashed var(--color-border);
  color: var(--color-text-muted);
}

.empty-gallery-box i {
  font-size: 2rem;
  margin-bottom: 8px;
  opacity: 0.5;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content.preview-modal {
  max-width: 960px;
  width: 100%;
  max-height: 90vh;
}

.modal-content.conflict-modal {
  max-width: 520px;
}

.modal-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-surface-alt);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.modal-footer {
  padding: 14px 24px;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: var(--bg-surface-alt);
}

/* Preview Modal Interior */
.preview-layout {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 24px;
}

.preview-main-img-box {
  height: 240px;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
}

.preview-main-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-thumbs-row {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  overflow-x: auto;
}

.preview-mini-thumb {
  width: 70px;
  height: 46px;
  border-radius: var(--radius-xs);
  object-fit: cover;
  border: 1px solid var(--color-border);
}

.preview-badge-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.preview-cat-badge {
  padding: 3px 8px;
  background: #ECFDF5;
  color: var(--color-primary);
  border-radius: 9999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.preview-plate-badge {
  padding: 3px 8px;
  background: #FEF3C7;
  color: #92400E;
  border-radius: var(--radius-xs);
  font-size: 0.74rem;
  font-family: monospace;
  font-weight: 700;
}

.preview-pub-badge {
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.preview-car-name {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.preview-price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin: 10px 0 16px;
}

.preview-price-val {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-primary);
}

.preview-city {
  margin-left: 12px;
  font-size: 0.82rem;
  color: var(--color-text-muted);
}

.preview-specs-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.spec-pill {
  padding: 6px 12px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.preview-desc-box,
.preview-terms-box {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid var(--color-border);
}

.preview-desc-box h4,
.preview-terms-box h4 {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 6px;
}

.preview-desc-box p {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.preview-terms-box ul {
  padding-left: 16px;
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin: 0;
}

.preview-terms-box li {
  margin-bottom: 4px;
}

/* Conflict Modal Interior */
.conflict-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #FEE2E2;
  color: #DC2626;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.conflict-alert-box {
  padding: 12px 14px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  color: #991B1B;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 12px;
  line-height: 1.5;
}

@media (max-width: 900px) {
  .editor-container {
    grid-template-columns: 1fr;
  }
  .editor-toc {
    display: none;
  }
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group.full {
    grid-column: span 1;
  }
  .preview-layout {
    grid-template-columns: 1fr;
  }
}
</style>
