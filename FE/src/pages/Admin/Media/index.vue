<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-photo-film"></i></div>
        <div>
          <h1 class="page-title">Thư Viện <span>Tài Nguyên Đa Phương Tiện</span></h1>
          <p class="page-subtitle">Quản lý tập trung hình ảnh đội xe, biểu ngữ marketing, tài liệu và biên lai thanh toán an toàn.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchMedia(1)" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
        <button class="btn-primary" @click="showUploadModal = true">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Tải Lên Tệp Mới</span>
        </button>
      </div>
    </header>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-folder-tree"></i></div>
        <div class="stat-info">
          <span class="stat-label">Tổng số tài nguyên</span>
          <strong class="stat-value">{{ stats.total_files }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-hard-drive"></i></div>
        <div class="stat-info">
          <span class="stat-label">Dung lượng máy chủ</span>
          <strong class="stat-value">{{ stats.total_size_human }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-globe"></i></div>
        <div class="stat-info">
          <span class="stat-label">Ảnh công khai (Public)</span>
          <strong class="stat-value">{{ stats.total_public }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="stat-info">
          <span class="stat-label">Hồ sơ riêng tư (Private)</span>
          <strong class="stat-value">{{ stats.total_private }}</strong>
        </div>
      </div>
    </div>

    <!-- Security & Isolation Banner -->
    <div class="guideline-banner card">
      <div class="banner-icon"><i class="fa-solid fa-shield-virus"></i></div>
      <div class="banner-text">
        <strong>Chính sách phân quyền & an toàn tài nguyên:</strong>
        <p>
          Hệ thống tách biệt tuyệt đối giữa:
          <span class="pill-chip"><strong>Tài nguyên công khai:</strong> Ảnh xe, banner hiển thị tốc độ cao trên Website.</span>
          <span class="pill-chip"><strong>Chứng từ riêng tư:</strong> Biên lai sao kê ngân hàng và ảnh hiện trạng giao nhận xe được lưu trữ tại phân vùng bảo mật, chỉ truy xuất qua API có chứng thực quyền, không mở public URL.</span>
          <span class="pill-chip"><strong>Bảo vệ toàn vẹn:</strong> Hệ thống chặn xóa tài nguyên đang được xe hoặc đơn hàng tham chiếu.</span>
        </p>
      </div>
    </div>

    <!-- Content Card -->
    <div class="content-card card">
      <!-- Toolbar -->
      <div class="table-tools">
        <div class="tools-left">
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo tên tệp, ghi chú, alt text..."
              @input="onSearchInput"
            />
            <button v-if="searchQuery" class="clear-btn" @click="clearSearch">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Filter Collection -->
          <select v-model="filterCollection" class="form-select" @change="fetchMedia(1)">
            <option value="all">Tất cả nhóm</option>
            <option value="cars">Xe & Ngoại/Nội thất</option>
            <option value="marketing">Marketing & Banner</option>
            <option value="receipts">Biên lai thanh toán</option>
            <option value="handovers">Biên bản giao nhận</option>
            <option value="general">Tài nguyên chung</option>
          </select>

          <!-- Filter Visibility -->
          <select v-model="filterVisibility" class="form-select" @change="fetchMedia(1)">
            <option value="all">Tất cả chế độ xem</option>
            <option value="public">Công khai (Public)</option>
            <option value="private">Riêng tư (Private)</option>
          </select>

          <!-- Filter Mime -->
          <select v-model="filterMime" class="form-select" @change="fetchMedia(1)">
            <option value="all">Tất cả định dạng</option>
            <option value="image">Hình ảnh (JPG, PNG, WEBP)</option>
            <option value="document">Tài liệu (PDF)</option>
          </select>

          <button 
            v-if="searchQuery || filterCollection !== 'all' || filterVisibility !== 'all' || filterMime !== 'all'" 
            class="btn-reset-filters" 
            @click="resetFilters"
            title="Đặt lại bộ lọc"
          >
            <i class="fa-solid fa-filter-circle-xmark"></i>
          </button>
        </div>

        <div class="tools-right">
          <!-- View Mode Toggle -->
          <div class="view-mode-toggle">
            <button 
              type="button" 
              class="btn-toggle-view" 
              :class="{ active: viewMode === 'grid' }"
              @click="viewMode = 'grid'"
              title="Xem dạng lưới"
            >
              <i class="fa-solid fa-border-all"></i>
            </button>
            <button 
              type="button" 
              class="btn-toggle-view" 
              :class="{ active: viewMode === 'table' }"
              @click="viewMode = 'table'"
              title="Xem dạng bảng"
            >
              <i class="fa-solid fa-list"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="state-container">
        <i class="fa-solid fa-spinner fa-spin state-icon"></i>
        <p>Đang tải danh sách tài nguyên đa phương tiện...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="items.length === 0" class="state-container empty">
        <i class="fa-regular fa-folder-open state-icon"></i>
        <h3>Không tìm thấy tệp tài nguyên nào</h3>
        <p>Không có kết quả khớp với tiêu chí tìm kiếm hoặc thư viện chưa có tệp nào.</p>
        <button class="btn-primary mt-3" @click="showUploadModal = true">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Tải Lên Tệp Đầu Tiên</span>
        </button>
      </div>

      <!-- View 1: Grid Mode -->
      <div v-else-if="viewMode === 'grid'" class="media-grid-view">
        <div 
          v-for="item in items" 
          :key="item.id" 
          class="asset-card"
          :class="{ 'is-in-use': item.in_use }"
        >
          <!-- Thumbnail Container -->
          <div class="asset-thumb-box" @click="openDetailModal(item)">
            <img 
              v-if="item.is_image" 
              :src="item.display_url" 
              :alt="item.alt_text || item.original_name"
              loading="lazy"
              @error="onImageError($event)"
            />
            <div v-else class="pdf-placeholder">
              <i class="fa-solid fa-file-pdf"></i>
              <span>PDF DOC</span>
            </div>

            <!-- Badges Overlay -->
            <div class="badge-overlay">
              <span class="vis-badge" :class="item.visibility">
                <i :class="item.visibility === 'public' ? 'fa-solid fa-globe' : 'fa-solid fa-lock'"></i>
                {{ item.visibility === 'public' ? 'Public' : 'Private' }}
              </span>
              <span class="col-badge">{{ formatCollection(item.collection) }}</span>
            </div>
          </div>

          <!-- Details & Actions -->
          <div class="asset-details">
            <h4 class="asset-title" :title="item.original_name" @click="openDetailModal(item)">
              {{ item.original_name }}
            </h4>

            <div class="asset-meta-row">
              <span v-if="item.width && item.height">{{ item.width }}x{{ item.height }}</span>
              <span v-if="item.width && item.height">•</span>
              <span>{{ item.formatted_size }}</span>
            </div>

            <div class="asset-usage-row">
              <span v-if="item.in_use" class="in-use-tag" @click="openDetailModal(item)">
                <i class="fa-solid fa-link"></i> Đang dùng ({{ item.references_count }})
              </span>
              <span v-else class="unused-tag">
                <i class="fa-regular fa-circle-check"></i> Chưa sử dụng
              </span>
            </div>

            <!-- Action Buttons -->
            <div class="asset-actions">
              <button class="btn-action-sm" @click="openDetailModal(item)" title="Xem chi tiết & nơi sử dụng">
                <i class="fa-regular fa-eye"></i>
              </button>
              <button class="btn-action-sm" @click="copyAssetUrl(item)" title="Sao chép đường dẫn">
                <i class="fa-regular fa-copy"></i>
              </button>
              <button class="btn-action-sm" @click="openEditModal(item)" title="Sửa thông tin">
                <i class="fa-regular fa-pen-to-square"></i>
              </button>
              <button class="btn-action-sm delete" @click="confirmDelete(item)" title="Xóa tài nguyên">
                <i class="fa-regular fa-trash-can"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View 2: Table Mode -->
      <div v-else class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 70px;">Ảnh</th>
              <th>Tên tệp gốc</th>
              <th>Phân loại</th>
              <th>Chế độ</th>
              <th>Kích thước</th>
              <th>Độ phân giải</th>
              <th>Nơi sử dụng</th>
              <th>Ngày tải lên</th>
              <th style="width: 140px; text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>
                <div class="table-thumb" @click="openDetailModal(item)">
                  <img 
                    v-if="item.is_image" 
                    :src="item.display_url" 
                    :alt="item.original_name"
                    @error="onImageError($event)"
                  />
                  <i v-else class="fa-solid fa-file-pdf pdf-table-icon"></i>
                </div>
              </td>
              <td>
                <strong class="file-name-table" @click="openDetailModal(item)">{{ item.original_name }}</strong>
                <small class="file-sub-table">{{ item.filename }}</small>
              </td>
              <td>
                <span class="table-chip">{{ formatCollection(item.collection) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="item.visibility">
                  <i :class="item.visibility === 'public' ? 'fa-solid fa-globe' : 'fa-solid fa-lock'"></i>
                  {{ item.visibility === 'public' ? 'Public' : 'Private' }}
                </span>
              </td>
              <td>{{ item.formatted_size }}</td>
              <td>{{ item.width && item.height ? `${item.width} x ${item.height}` : 'N/A' }}</td>
              <td>
                <span v-if="item.in_use" class="in-use-tag clickable" @click="openDetailModal(item)">
                  <i class="fa-solid fa-link"></i> {{ item.references_count }} vị trí
                </span>
                <span v-else class="unused-tag">Chưa dùng</span>
              </td>
              <td>{{ formatDate(item.created_at) }}</td>
              <td style="text-align: right;">
                <div class="table-action-btns">
                  <button class="btn-action-sm" @click="openDetailModal(item)" title="Xem chi tiết">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                  <button class="btn-action-sm" @click="copyAssetUrl(item)" title="Sao chép URL">
                    <i class="fa-regular fa-copy"></i>
                  </button>
                  <button class="btn-action-sm" @click="openEditModal(item)" title="Sửa">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <button class="btn-action-sm delete" @click="confirmDelete(item)" title="Xóa">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="table-pagination">
        <div class="pagination-info">
          Trang <strong>{{ pagination.current_page }}</strong> / {{ pagination.last_page }} (Tổng cộng {{ pagination.total }} tệp)
        </div>
        <div class="pagination-controls">
          <button 
            class="page-nav-btn" 
            :disabled="pagination.current_page <= 1"
            @click="fetchMedia(pagination.current_page - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i> Trước
          </button>
          <button 
            v-for="p in paginationPages" 
            :key="p" 
            class="page-num-btn"
            :class="{ active: p === pagination.current_page }"
            @click="fetchMedia(p)"
          >
            {{ p }}
          </button>
          <button 
            class="page-nav-btn" 
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchMedia(pagination.current_page + 1)"
          >
            Sau <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal 1: Upload Drawer / Modal -->
    <div v-if="showUploadModal" class="modal-overlay" @click.self="showUploadModal = false">
      <div class="modal-content card upload-modal animate-in">
        <div class="modal-header">
          <div class="modal-icon-badge">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </div>
          <div>
            <h3>Tải Lên Tệp Đa Phương Tiện</h3>
            <p class="modal-sub">Hỗ trợ tải lên nhiều tệp cùng lúc với kiểm tra định dạng và mã độc an toàn</p>
          </div>
          <button class="btn-close-modal" @click="showUploadModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <div 
            class="upload-dropzone"
            :class="{ 'drag-over': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
          >
            <input 
              type="file" 
              ref="pageFileInputRef" 
              multiple 
              accept="image/jpeg,image/png,image/webp,application/pdf"
              @change="handleFileInputChange" 
              hidden
            />
            <div class="dropzone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <h4>Kéo thả tệp vào đây hoặc bấm để chọn tệp</h4>
            <p class="dropzone-hint">Chấp nhận JPG, PNG, WEBP, PDF. Tối đa 10MB/tệp.</p>
          </div>

          <!-- Configuration Fields -->
          <div class="form-grid mt-3">
            <div class="form-group">
              <label>Phân loại nhóm tệp <span class="required">*</span></label>
              <select v-model="uploadForm.collection">
                <option value="cars">Xe & Ngoại/Nội thất (Cars)</option>
                <option value="marketing">Marketing & Banner</option>
                <option value="general">Tài nguyên chung (General)</option>
                <option value="receipts">Biên lai thanh toán (Private)</option>
                <option value="handovers">Biên bản giao nhận (Private)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Chế độ hiển thị <span class="required">*</span></label>
              <select v-model="uploadForm.visibility" :disabled="uploadForm.collection === 'receipts' || uploadForm.collection === 'handovers'">
                <option value="public">Công khai trên Website (Public)</option>
                <option value="private">Riêng tư có phân quyền (Private)</option>
              </select>
            </div>
          </div>

          <!-- File Queue List -->
          <div v-if="queuedFiles.length > 0" class="queued-section mt-3">
            <div class="queue-title-row">
              <strong>Tệp đã chọn để tải lên ({{ queuedFiles.length }}):</strong>
              <button type="button" class="btn-clear-text" @click="queuedFiles = []">Xóa tất cả</button>
            </div>
            <div class="queue-files-scroll">
              <div v-for="(file, idx) in queuedFiles" :key="idx" class="queue-file-card">
                <div class="file-icon-mini"><i class="fa-solid fa-file"></i></div>
                <div class="file-name-mini" :title="file.name">{{ file.name }}</div>
                <span class="file-size-mini">{{ formatBytes(file.size) }}</span>
                <button type="button" class="btn-remove-queue" @click="removeQueuedFile(idx)">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="showUploadModal = false" :disabled="isUploading">Hủy</button>
          <button 
            type="button" 
            class="btn-primary" 
            :disabled="queuedFiles.length === 0 || isUploading"
            @click="submitUpload"
          >
            <i v-if="isUploading" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-cloud-arrow-up"></i>
            <span>{{ isUploading ? 'Đang tải lên hệ thống...' : `Tải lên ${queuedFiles.length} tệp` }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal 2: Detail & Reference Inspector -->
    <div v-if="detailAsset" class="modal-overlay" @click.self="detailAsset = null">
      <div class="modal-content card detail-modal animate-in">
        <div class="modal-header">
          <div class="modal-icon-badge">
            <i class="fa-solid fa-circle-info"></i>
          </div>
          <div>
            <h3>Chi Tiết Tài Nguyên & Vị Trí Sử Dụng</h3>
            <p class="modal-sub">ID: #{{ detailAsset.id }} — {{ detailAsset.original_name }}</p>
          </div>
          <button class="btn-close-modal" @click="detailAsset = null"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body detail-body-layout">
          <!-- Left: Big Preview -->
          <div class="detail-preview-panel">
            <div class="big-preview-box">
              <img 
                v-if="detailAsset.is_image" 
                :src="detailAsset.display_url" 
                :alt="detailAsset.alt_text || detailAsset.original_name"
                @error="onImageError($event)"
              />
              <div v-else class="big-pdf-box">
                <i class="fa-solid fa-file-pdf"></i>
                <p>Tài liệu PDF định dạng bảo mật</p>
                <a :href="detailAsset.display_url" target="_blank" class="btn-secondary btn-sm mt-2">
                  <i class="fa-solid fa-arrow-up-right-from-square"></i> Mở xem tệp PDF
                </a>
              </div>
            </div>

            <div class="quick-links-panel mt-3">
              <label>Đường dẫn truy xuất:</label>
              <div class="url-copy-box">
                <input type="text" readonly :value="detailAsset.display_url" />
                <button type="button" @click="copyText(detailAsset.display_url)" title="Sao chép">
                  <i class="fa-regular fa-copy"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Right: Metadata & References -->
          <div class="detail-info-panel">
            <h4 class="section-heading"><i class="fa-solid fa-database"></i> Thông số kỹ thuật tệp</h4>
            <div class="meta-table">
              <div class="meta-row">
                <span class="meta-key">Tên lưu trên máy chủ:</span>
                <span class="meta-val code">{{ detailAsset.filename }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-key">Định dạng MIME thực tế:</span>
                <span class="meta-val"><span class="mime-pill">{{ detailAsset.mime_type }}</span></span>
              </div>
              <div class="meta-row">
                <span class="meta-key">Kích thước tệp:</span>
                <span class="meta-val">{{ detailAsset.formatted_size }} ({{ detailAsset.size }} bytes)</span>
              </div>
              <div class="meta-row" v-if="detailAsset.width && detailAsset.height">
                <span class="meta-key">Kích thước hiển thị:</span>
                <span class="meta-val">{{ detailAsset.width }} x {{ detailAsset.height }} px</span>
              </div>
              <div class="meta-row">
                <span class="meta-key">Chế độ hiển thị:</span>
                <span class="meta-val">
                  <span class="status-badge" :class="detailAsset.visibility">
                    {{ detailAsset.visibility === 'public' ? 'Công khai (Public)' : 'Riêng tư (Private)' }}
                  </span>
                </span>
              </div>
              <div class="meta-row">
                <span class="meta-key">Phân loại nhóm:</span>
                <span class="meta-val font-semibold">{{ formatCollection(detailAsset.collection) }}</span>
              </div>
              <div class="meta-row" v-if="detailAsset.alt_text">
                <span class="meta-key">Alt Text SEO:</span>
                <span class="meta-val">{{ detailAsset.alt_text }}</span>
              </div>
            </div>

            <!-- Reference Tracker -->
            <div class="references-tracker mt-4">
              <h4 class="section-heading">
                <i class="fa-solid fa-link"></i> 
                Các nơi đang sử dụng tài nguyên này 
                <span class="ref-counter-badge">{{ detailRefs.total_count }}</span>
              </h4>

              <div v-if="loadingRefs" class="p-3 text-center text-muted">
                <i class="fa-solid fa-spinner fa-spin"></i> Đang tra cứu liên kết tham chiếu...
              </div>

              <div v-else-if="detailRefs.total_count === 0" class="no-refs-box">
                <i class="fa-regular fa-circle-check"></i>
                <p>Tài nguyên này hiện chưa được gán vào xe hoặc đơn hàng nào. Có thể xóa an toàn khi cần.</p>
              </div>

              <div v-else class="refs-list">
                <!-- Cars Featured -->
                <div v-if="detailRefs.cars_featured && detailRefs.cars_featured.length > 0" class="ref-group">
                  <div class="ref-group-title">
                    <i class="fa-solid fa-car"></i> Ảnh đại diện của các xe:
                  </div>
                  <div class="ref-items">
                    <router-link 
                      v-for="car in detailRefs.cars_featured" 
                      :key="'feat-' + car.id" 
                      :to="'/admin/cars/edit/' + car.id"
                      class="ref-car-pill"
                    >
                      <span>{{ car.name }}</span>
                      <strong v-if="car.license_plate">({{ car.license_plate }})</strong>
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </router-link>
                  </div>
                </div>

                <!-- Cars Gallery -->
                <div v-if="detailRefs.cars_gallery && detailRefs.cars_gallery.length > 0" class="ref-group mt-2">
                  <div class="ref-group-title">
                    <i class="fa-solid fa-images"></i> Nằm trong thư viện ảnh xe:
                  </div>
                  <div class="ref-items">
                    <router-link 
                      v-for="car in detailRefs.cars_gallery" 
                      :key="'gal-' + car.id" 
                      :to="'/admin/cars/edit/' + car.id"
                      class="ref-car-pill"
                    >
                      <span>{{ car.name }}</span>
                      <strong v-if="car.license_plate">({{ car.license_plate }})</strong>
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </router-link>
                  </div>
                </div>

                <!-- Bookings -->
                <div v-if="detailRefs.bookings && detailRefs.bookings.length > 0" class="ref-group mt-2">
                  <div class="ref-group-title">
                    <i class="fa-solid fa-receipt"></i> Đơn thuê xe (Biên lai chứng từ):
                  </div>
                  <div class="ref-items">
                    <router-link 
                      v-for="b in detailRefs.bookings" 
                      :key="'b-' + b.id" 
                      to="/admin/bookings"
                      class="ref-booking-pill"
                    >
                      <span>Mã đơn: #{{ b.ma_dat_xe || b.id }}</span>
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="detailAsset = null">Đóng</button>
          <button type="button" class="btn-secondary" @click="openEditModal(detailAsset)">
            <i class="fa-regular fa-pen-to-square"></i> Sửa thông tin
          </button>
        </div>
      </div>
    </div>

    <!-- Modal 3: Edit Metadata -->
    <div v-if="editingAsset" class="modal-overlay" @click.self="editingAsset = null">
      <div class="modal-content card edit-modal animate-in">
        <div class="modal-header">
          <div class="modal-icon-badge">
            <i class="fa-regular fa-pen-to-square"></i>
          </div>
          <div>
            <h3>Cập Nhật Thông Tin Tài Nguyên</h3>
            <p class="modal-sub">Chỉnh sửa mô tả, SEO Alt text và nhóm phân loại</p>
          </div>
          <button class="btn-close-modal" @click="editingAsset = null"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="saveEditMetadata" class="modal-body">
          <div class="form-group">
            <label>Tên tệp gốc:</label>
            <input type="text" :value="editingAsset.original_name" disabled class="input-disabled" />
          </div>

          <div class="form-group mt-3">
            <label>Alt Text SEO (Mô tả ảnh cho công cụ tìm kiếm):</label>
            <input 
              type="text" 
              v-model="editForm.alt_text" 
              placeholder="VD: VinFast VF9 màu xanh dương ngoại thất trước" 
            />
          </div>

          <div class="form-group mt-3">
            <label>Phân loại nhóm tài nguyên:</label>
            <select v-model="editForm.collection">
              <option value="cars">Xe & Ngoại/Nội thất (Cars)</option>
              <option value="marketing">Marketing & Banner</option>
              <option value="general">Tài nguyên chung (General)</option>
              <option value="receipts">Biên lai thanh toán (Private)</option>
              <option value="handovers">Biên bản giao nhận (Private)</option>
            </select>
          </div>

          <div class="form-group mt-3">
            <label>Ghi chú / Mô tả nội bộ:</label>
            <textarea 
              rows="3" 
              v-model="editForm.description" 
              placeholder="Ghi chú nguồn ảnh, bộ phận sử dụng hoặc thời gian chụp..."
            ></textarea>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn-outline" @click="editingAsset = null" :disabled="saving">Hủy</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu thông tin' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal 4: In-Use Deletion Warning Modal -->
    <div v-if="inUseWarningData" class="modal-overlay" @click.self="inUseWarningData = null">
      <div class="modal-content card warning-modal animate-in">
        <div class="modal-header warning-header">
          <div class="modal-icon-badge warning-badge">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3>Không Thể Xóa Tài Nguyên Đang Sử Dụng</h3>
            <p class="modal-sub">Ràng buộc bảo vệ dữ liệu toàn vẹn hệ thống</p>
          </div>
          <button class="btn-close-modal" @click="inUseWarningData = null"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <div class="alert-box-warning">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
              <strong>Tài nguyên '{{ inUseWarningData.asset?.original_name }}' đang được tham chiếu bởi các bản ghi sau:</strong>
              <p class="mt-1">{{ inUseWarningData.message }}</p>
            </div>
          </div>

          <div class="rules-box mt-3">
            <h5>Quy tắc bảo vệ tài nguyên:</h5>
            <ul>
              <li>Không được phép xóa trực tiếp ảnh khi đang làm ảnh đại diện hoặc gallery của phương tiện trong kho xe.</li>
              <li>Để xóa tệp này, vui lòng truy cập vào trình chỉnh sửa của các xe liệt kê bên trên và thay thế hoặc gỡ bỏ ảnh trước.</li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-primary" @click="inUseWarningData = null">Đã hiểu & Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

// States
const loading = ref(false);
const saving = ref(false);
const items = ref([]);
const viewMode = ref('grid'); // 'grid' | 'table'

// Filters & Search
const searchQuery = ref('');
const filterCollection = ref('all');
const filterVisibility = ref('all');
const filterMime = ref('all');
let searchTimer = null;

// Stats
const stats = reactive({
  total_files: 0,
  total_public: 0,
  total_private: 0,
  total_images: 0,
  total_documents: 0,
  total_size_human: '0 B',
});

// Pagination
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 24,
  total: 0,
});

// Modals State
const showUploadModal = ref(false);
const isDragging = ref(false);
const isUploading = ref(false);
const pageFileInputRef = ref(null);
const queuedFiles = ref([]);
const uploadForm = reactive({
  collection: 'cars',
  visibility: 'public',
});

const detailAsset = ref(null);
const detailRefs = ref({ total_count: 0 });
const loadingRefs = ref(false);

const editingAsset = ref(null);
const editForm = reactive({
  alt_text: '',
  description: '',
  collection: 'cars',
});

const inUseWarningData = ref(null);

// Pagination Pages
const paginationPages = computed(() => {
  const pages = [];
  const max = pagination.last_page;
  const cur = pagination.current_page;
  const start = Math.max(1, cur - 2);
  const end = Math.min(max, cur + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Fetch Media List
const fetchMedia = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page: page,
      per_page: pagination.per_page,
    };
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }
    if (filterCollection.value !== 'all') {
      params.collection = filterCollection.value;
    }
    if (filterVisibility.value !== 'all') {
      params.visibility = filterVisibility.value;
    }
    if (filterMime.value !== 'all') {
      params.mime_type = filterMime.value;
    }

    const response = await baseRequest.get('/admin/media', { params });
    if (response.data && response.data.status === 1) {
      items.value = response.data.data || [];
      if (response.data.meta) {
        pagination.current_page = response.data.meta.current_page;
        pagination.last_page = response.data.meta.last_page;
        pagination.total = response.data.meta.total;
      }
      if (response.data.stats) {
        Object.assign(stats, response.data.stats);
      }
    }
  } catch (error) {
    console.error('Fetch media error:', error);
    toaster.error('Không thể tải danh sách tài nguyên.');
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    fetchMedia(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchMedia(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  filterCollection.value = 'all';
  filterVisibility.value = 'all';
  filterMime.value = 'all';
  fetchMedia(1);
};

// Upload
const triggerFileInput = () => {
  if (pageFileInputRef.value) {
    pageFileInputRef.value.click();
  }
};

const handleFileInputChange = (e) => {
  const files = Array.from(e.target.files || []);
  addFilesToQueue(files);
  e.target.value = '';
};

const handleDrop = (e) => {
  isDragging.value = false;
  const files = Array.from(e.dataTransfer.files || []);
  addFilesToQueue(files);
};

const addFilesToQueue = (files) => {
  const validFiles = files.filter(f => {
    if (f.size > 10 * 1024 * 1024) {
      toaster.warning(`Tệp ${f.name} vượt quá 10MB và bị bỏ qua.`);
      return false;
    }
    return true;
  });
  queuedFiles.value.push(...validFiles);
};

const removeQueuedFile = (idx) => {
  queuedFiles.value.splice(idx, 1);
};

const submitUpload = async () => {
  if (queuedFiles.value.length === 0) return;

  isUploading.value = true;
  const formData = new FormData();
  queuedFiles.value.forEach(f => {
    formData.append('files[]', f);
  });
  formData.append('collection', uploadForm.collection);
  formData.append('visibility', uploadForm.visibility);

  try {
    const response = await baseRequest.post('/admin/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (response.data && response.data.status === 1) {
      toaster.success(response.data.message || 'Tải lên thành công!');
      queuedFiles.value = [];
      showUploadModal.value = false;
      fetchMedia(1);
    } else {
      toaster.error(response.data.message || 'Tải tệp thất bại');
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Có lỗi xảy ra khi tải tệp lên máy chủ.';
    toaster.error(msg);
  } finally {
    isUploading.value = false;
  }
};

// Detail & References
const openDetailModal = async (item) => {
  detailAsset.value = item;
  detailRefs.value = { total_count: 0 };
  loadingRefs.value = true;

  try {
    const response = await baseRequest.get(`/admin/media/${item.id}/references`);
    if (response.data && response.data.status === 1) {
      detailRefs.value = response.data.data.references || { total_count: 0 };
    }
  } catch (error) {
    console.error('Fetch references error:', error);
  } finally {
    loadingRefs.value = false;
  }
};

// Edit Metadata
const openEditModal = (item) => {
  editingAsset.value = item;
  editForm.alt_text = item.alt_text || '';
  editForm.description = item.description || '';
  editForm.collection = item.collection || 'cars';
};

const saveEditMetadata = async () => {
  if (!editingAsset.value) return;

  saving.value = true;
  try {
    const response = await baseRequest.put(`/admin/media/${editingAsset.value.id}`, editForm);
    if (response.data && response.data.status === 1) {
      toaster.success('Cập nhật thông tin thành công!');
      Object.assign(editingAsset.value, response.data.data);
      editingAsset.value = null;
      fetchMedia(pagination.current_page);
    }
  } catch (error) {
    toaster.error('Không thể cập nhật thông tin tài nguyên.');
  } finally {
    saving.value = false;
  }
};

// Delete Asset
const confirmDelete = async (item) => {
  if (!confirm(`Bạn có chắc chắn muốn xóa tệp "${item.original_name}" không?`)) {
    return;
  }

  try {
    const response = await baseRequest.delete(`/admin/media/${item.id}`);
    if (response.data && response.data.status === 1) {
      toaster.success(response.data.message || 'Đã xóa tài nguyên thành công.');
      fetchMedia(pagination.current_page);
    }
  } catch (error) {
    if (error.response?.status === 422 && error.response?.data?.error_code === 'ASSET_IN_USE') {
      inUseWarningData.value = {
        asset: item,
        message: error.response.data.message,
        references: error.response.data.references,
      };
    } else {
      toaster.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa tài nguyên.');
    }
  }
};

// Copy URL
const copyAssetUrl = (item) => {
  const urlToCopy = item.url ? (window.location.origin + item.url) : item.display_url;
  copyText(urlToCopy);
};

const copyText = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    toaster.success('Đã sao chép đường dẫn vào bộ nhớ tạm!');
  }).catch(() => {
    toaster.warning('Không thể sao chép tự động, vui lòng chọn và copy thủ công.');
  });
};

const formatCollection = (col) => {
  const map = {
    cars: 'Xe & Đội xe',
    marketing: 'Marketing & Banner',
    receipts: 'Biên lai chuyển khoản',
    handovers: 'Giao nhận xe',
    general: 'Tài nguyên chung',
  };
  return map[col] || col;
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatBytes = (bytes) => {
  if (!bytes) return '0 B';
  if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(1) + ' MB';
  }
  return (bytes / 1024).toFixed(0) + ' KB';
};

const onImageError = (e) => {
  e.target.src = '/assets/images/placeholder-car.png';
};

onMounted(() => {
  fetchMedia(1);
});
</script>

<style scoped>
.admin-page {
  padding: 1.5rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.header-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.page-title span {
  color: #2563eb;
}

.page-subtitle {
  font-size: 0.9rem;
  color: #64748b;
  margin: 0.25rem 0 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-refresh {
  padding: 0.65rem 1rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-refresh:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-primary {
  padding: 0.65rem 1.25rem;
  background: #2563eb;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  padding: 0.6rem 1.25rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-outline:hover {
  background: #f1f5f9;
}

.btn-secondary {
  padding: 0.6rem 1.25rem;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #dbeafe;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
}

.stat-icon.primary { background: #eff6ff; color: #2563eb; }
.stat-icon.info { background: #f0fdf4; color: #16a34a; }
.stat-icon.success { background: #ecfdf5; color: #059669; }
.stat-icon.warning { background: #fffbeb; color: #d97706; }

.stat-label {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
}

.stat-value {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
}

/* Guideline Banner */
.guideline-banner {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-left: 4px solid #2563eb;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  display: flex;
  gap: 1rem;
}

.banner-icon {
  font-size: 1.4rem;
  color: #2563eb;
  padding-top: 0.1rem;
}

.banner-text strong {
  color: #1e293b;
  font-size: 0.95rem;
}

.banner-text p {
  margin: 0.35rem 0 0 0;
  font-size: 0.88rem;
  color: #475569;
  line-height: 1.6;
}

.pill-chip {
  display: inline-block;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 0.15rem 0.55rem;
  border-radius: 6px;
  margin: 0.15rem 0.25rem 0.15rem 0;
  font-size: 0.82rem;
}

/* Main Content Card */
.content-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
}

.table-tools {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  gap: 1rem;
}

.tools-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.search-input-wrapper {
  position: relative;
  width: 320px;
}

.search-input-wrapper i {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input-wrapper input {
  width: 100%;
  padding: 0.55rem 2rem 0.55rem 2.4rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
}

.search-input-wrapper input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.clear-btn {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}

.form-select {
  padding: 0.55rem 0.9rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.88rem;
  background: #ffffff;
  outline: none;
  cursor: pointer;
}

.btn-reset-filters {
  padding: 0.55rem 0.85rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 8px;
  color: #64748b;
  cursor: pointer;
}

.btn-reset-filters:hover {
  color: #ef4444;
  border-color: #fca5a5;
}

.view-mode-toggle {
  display: flex;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
}

.btn-toggle-view {
  background: #ffffff;
  border: none;
  padding: 0.55rem 0.85rem;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-toggle-view.active {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 700;
}

/* States */
.state-container {
  padding: 4rem 2rem;
  text-align: center;
  color: #64748b;
}

.state-icon {
  font-size: 2.5rem;
  color: #2563eb;
  margin-bottom: 1rem;
}

.state-container.empty .state-icon {
  color: #cbd5e1;
}

/* Grid View */
.media-grid-view {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
}

.asset-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.asset-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
  border-color: #93c5fd;
}

.asset-card.is-in-use {
  border-color: #bfdbfe;
}

.asset-thumb-box {
  width: 100%;
  aspect-ratio: 16 / 10;
  background: #f1f5f9;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.asset-thumb-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.asset-card:hover .asset-thumb-box img {
  transform: scale(1.05);
}

.pdf-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  color: #dc2626;
  font-size: 2.2rem;
}

.pdf-placeholder span {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
}

.badge-overlay {
  position: absolute;
  inset: 0;
  padding: 0.5rem;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  pointer-events: none;
}

.vis-badge {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  text-transform: uppercase;
  color: #ffffff;
}

.vis-badge.public { background: rgba(16, 185, 129, 0.95); }
.vis-badge.private { background: rgba(245, 158, 11, 0.95); }

.col-badge {
  background: rgba(15, 23, 42, 0.75);
  color: #ffffff;
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.asset-details {
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
}

.asset-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
}

.asset-title:hover {
  color: #2563eb;
}

.asset-meta-row {
  font-size: 0.75rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.asset-usage-row {
  margin-top: 0.25rem;
}

.in-use-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: #0369a1;
  background: #e0f2fe;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
}

.unused-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: #15803d;
  background: #f0fdf4;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.asset-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
}

.btn-action-sm {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-action-sm:hover {
  background: #f8fafc;
  color: #2563eb;
  border-color: #bfdbfe;
}

.btn-action-sm.delete:hover {
  background: #fef2f2;
  color: #ef4444;
  border-color: #fca5a5;
}

/* Table View */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  font-size: 0.82rem;
  font-weight: 700;
  color: #475569;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 0.85rem 1rem;
  font-size: 0.88rem;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table-thumb {
  width: 50px;
  height: 38px;
  border-radius: 6px;
  overflow: hidden;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.table-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pdf-table-icon {
  color: #dc2626;
  font-size: 1.25rem;
}

.file-name-table {
  display: block;
  font-weight: 700;
  color: #1e293b;
  cursor: pointer;
}

.file-name-table:hover {
  color: #2563eb;
}

.file-sub-table {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
}

.table-chip {
  background: #f1f5f9;
  color: #475569;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 700;
}

.status-badge.public { background: #ecfdf5; color: #059669; }
.status-badge.private { background: #fffbeb; color: #d97706; }

.table-action-btns {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.35rem;
}

/* Pagination */
.table-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 0.88rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.page-nav-btn {
  padding: 0.45rem 0.85rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 6px;
  color: #334155;
  font-size: 0.85rem;
  cursor: pointer;
}

.page-nav-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-num-btn {
  width: 32px;
  height: 32px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-num-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  font-weight: 700;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(5px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 650px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-content.upload-modal {
  max-width: 720px;
}

.modal-content.detail-modal {
  max-width: 900px;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.modal-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.15rem;
  color: #0f172a;
}

.modal-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.82rem;
  color: #64748b;
}

.btn-close-modal {
  margin-left: auto;
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.4rem;
}

.btn-close-modal:hover {
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: 75vh;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Upload Dropzone */
.upload-dropzone {
  border: 2px dashed #94a3b8;
  border-radius: 12px;
  background: #f8fafc;
  padding: 2.25rem 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-dropzone:hover, .upload-dropzone.drag-over {
  border-color: #2563eb;
  background: #eff6ff;
}

.dropzone-icon {
  font-size: 2.5rem;
  color: #2563eb;
  margin-bottom: 0.5rem;
}

.dropzone-hint {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0.35rem 0 0 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.35rem;
}

.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 0.55rem 0.85rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.88rem;
  outline: none;
}

.input-disabled {
  background: #f1f5f9;
  color: #64748b;
  cursor: not-allowed;
}

/* Queue in upload */
.queue-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.88rem;
  margin-bottom: 0.5rem;
}

.btn-clear-text {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 0.8rem;
  cursor: pointer;
}

.queue-files-scroll {
  max-height: 160px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.queue-file-card {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.45rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
}

.file-name-mini {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #1e293b;
}

.file-size-mini {
  font-size: 0.75rem;
  color: #64748b;
}

.btn-remove-queue {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}

.btn-remove-queue:hover {
  color: #ef4444;
}

/* Detail Modal Layout */
.detail-body-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.big-preview-box {
  width: 100%;
  aspect-ratio: 16 / 11;
  background: #0f172a;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.big-preview-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.big-pdf-box {
  color: #ffffff;
  text-align: center;
  font-size: 3rem;
}

.big-pdf-box p {
  font-size: 0.9rem;
  margin: 0.5rem 0 0 0;
  color: #94a3b8;
}

.url-copy-box {
  display: flex;
  gap: 0.35rem;
  margin-top: 0.25rem;
}

.url-copy-box input {
  flex: 1;
  padding: 0.45rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.8rem;
  background: #f8fafc;
}

.url-copy-box button {
  padding: 0.45rem 0.75rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 6px;
  cursor: pointer;
}

.section-heading {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.75rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.meta-table {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  padding-bottom: 0.4rem;
  border-bottom: 1px dashed #e2e8f0;
}

.meta-key {
  color: #64748b;
}

.meta-val {
  font-weight: 600;
  color: #1e293b;
}

.meta-val.code {
  font-family: monospace;
  font-size: 0.8rem;
  background: #f1f5f9;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.mime-pill {
  background: #eff6ff;
  color: #2563eb;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
  font-size: 0.78rem;
}

.ref-counter-badge {
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  margin-left: auto;
}

.no-refs-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
  padding: 0.75rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.85rem;
}

.refs-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 180px;
  overflow-y: auto;
}

.ref-group-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 0.35rem;
}

.ref-items {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.ref-car-pill, .ref-booking-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.6rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #1e293b;
  text-decoration: none;
  transition: all 0.2s;
}

.ref-car-pill:hover, .ref-booking-pill:hover {
  background: #eff6ff;
  border-color: #2563eb;
  color: #2563eb;
}

/* Warning Modal */
.warning-header {
  border-bottom-color: #fef3c7;
  background: #fffbeb;
}

.warning-badge {
  background: #fef3c7;
  color: #d97706;
}

.alert-box-warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  padding: 1rem;
  border-radius: 10px;
  display: flex;
  gap: 0.75rem;
  font-size: 0.9rem;
}

.rules-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  font-size: 0.85rem;
  color: #475569;
}

.rules-box h5 {
  margin: 0 0 0.5rem 0;
  color: #1e293b;
  font-weight: 700;
}

.rules-box ul {
  margin: 0;
  padding-left: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

@keyframes animateIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-in {
  animation: animateIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .detail-body-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .admin-page {
    padding: 1rem;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .table-tools {
    flex-direction: column;
    align-items: stretch;
  }
  .tools-left {
    flex-direction: column;
  }
  .search-input-wrapper {
    width: 100%;
  }
}
</style>
