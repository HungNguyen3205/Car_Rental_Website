<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-car-rear"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Kho Xe</span></h1>
          <p class="page-subtitle">Theo dõi, điều chỉnh trạng thái vận hành, xuất bản nội dung và kiểm soát đội xe toàn hệ thống.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal()">
        <i class="fa-solid fa-plus"></i> Thêm xe mới
      </button>
    </header>

    <!-- Content Card -->
    <div class="content-card card">
      <!-- Tabs Bar with Counters -->
      <div class="tabs-bar">
        <button 
          class="tab-item" 
          :class="{ active: currentTab === 'all' }" 
          @click="switchTab('all')"
        >
          <i class="fa-solid fa-layer-group"></i>
          <span>Tất cả xe</span>
          <span class="tab-badge">{{ counts.all || 0 }}</span>
        </button>

        <button 
          class="tab-item" 
          :class="{ active: currentTab === 'published' }" 
          @click="switchTab('published')"
        >
          <i class="fa-solid fa-circle-check text-success"></i>
          <span>Đã đăng (Công khai)</span>
          <span class="tab-badge badge-success">{{ counts.published || 0 }}</span>
        </button>

        <button 
          class="tab-item" 
          :class="{ active: currentTab === 'draft' }" 
          @click="switchTab('draft')"
        >
          <i class="fa-solid fa-file-pen text-amber"></i>
          <span>Bản nháp</span>
          <span class="tab-badge badge-warning">{{ counts.draft || 0 }}</span>
        </button>

        <button 
          class="tab-item" 
          :class="{ active: currentTab === 'archived' }" 
          @click="switchTab('archived')"
        >
          <i class="fa-solid fa-box-archive text-muted"></i>
          <span>Lưu trữ</span>
          <span class="tab-badge badge-muted">{{ counts.archived || 0 }}</span>
        </button>

        <button 
          class="tab-item tab-trash" 
          :class="{ active: currentTab === 'trash' }" 
          @click="switchTab('trash')"
        >
          <i class="fa-solid fa-trash-can text-danger"></i>
          <span>Thùng rác</span>
          <span class="tab-badge badge-danger">{{ counts.trash || 0 }}</span>
        </button>
      </div>

      <!-- Search & Filters -->
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input 
            type="text" 
            placeholder="Tìm theo tên xe, mã #CAR-ID, loại xe..." 
            v-model="searchQuery" 
            @input="onSearchInput"
          />
          <button v-if="searchQuery" class="clear-search-btn" @click="clearSearch" title="Xóa tìm kiếm">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="filter-group">
          <!-- Filter Vận hành (chỉ dùng khi không ở thùng rác) -->
          <select v-if="currentTab !== 'trash'" v-model="filterStatus" class="form-select" @change="fetchCars(1)">
            <option value="">Tất cả vận hành</option>
            <option value="available">Sẵn sàng (Hoạt động)</option>
            <option value="maintenance">Bảo trì (Tạm ngưng)</option>
          </select>

          <!-- Filter Danh mục -->
          <select v-model="filterCategory" class="form-select" @change="fetchCars(1)">
            <option value="">Tất cả phân hạng</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>

          <!-- Filter Khu vực -->
          <select v-model="filterCity" class="form-select" @change="fetchCars(1)">
            <option value="">Tất cả khu vực</option>
            <option value="sg">TP. Hồ Chí Minh</option>
            <option value="hn">Hà Nội</option>
            <option value="dn">Đà Nẵng</option>
            <option value="nt">Nha Trang / Khánh Hòa</option>
            <option value="hp">Hải Phòng</option>
            <option value="ct">Cần Thơ</option>
            <option value="all">Toàn quốc</option>
          </select>

          <!-- Reset Filter Button -->
          <button 
            v-if="searchQuery || filterStatus || filterCategory || filterCity" 
            class="btn-reset-filters" 
            @click="resetFilters" 
            title="Đặt lại các bộ lọc"
          >
            <i class="fa-solid fa-rotate-left"></i> Đặt lại
          </button>
        </div>
      </div>

      <!-- Professional Table -->
      <div class="table-responsive">
        <table class="data-table" v-if="cars.length > 0">
          <thead>
            <tr>
              <th class="col-stt">STT</th>
              <th class="col-car-info">Thông tin xe</th>
              <th class="col-category">Phân hạng</th>
              <th class="col-price">Giá thuê / ngày</th>
              <th class="col-city">Khu vực</th>
              <th v-if="currentTab !== 'trash'" class="col-status">Vận hành</th>
              <th v-if="currentTab !== 'trash'" class="col-pub">Xuất bản</th>
              <th v-if="currentTab === 'trash'" class="col-deleted-at">Thời điểm xóa</th>
              <th class="col-actions text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(car, index) in cars" :key="car.id">
              <td class="col-stt text-center">
                <span class="stt-badge">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</span>
              </td>
              <td class="col-car-info">
                <div class="car-cell">
                  <img 
                    :src="resolveCarImage(car)" 
                    class="car-thumb" 
                    @error="handleCarImgError($event)"
                    alt="car"
                  />
                  <div class="car-details">
                    <span class="car-name" :title="car.name">{{ car.name }}</span>
                    <span class="car-id">Mã xe: #CAR-{{ car.id }}</span>
                  </div>
                </div>
              </td>
              <td class="col-category">
                <span class="tag-outline">{{ car.category_name || car.type || 'Tiêu chuẩn' }}</span>
              </td>
              <td class="col-price">
                <div class="price-box">
                  <span class="price-text">{{ formatCurrency(car.price_per_day) }}</span>
                  <span class="price-unit">/ ngày</span>
                </div>
              </td>
              <td class="col-city">
                <span class="city-badge">
                  <i class="fa-solid fa-location-dot"></i> {{ getCityLabel(car.city) }}
                </span>
              </td>

              <!-- Vận hành (Sẵn sàng / Bảo trì) -->
              <td v-if="currentTab !== 'trash'" class="col-status">
                <div class="status-cell">
                  <span 
                    class="status-badge" 
                    :class="car.status === 'available' ? 'status-ok' : 'status-warn'"
                  >
                    <i class="fa-solid" :class="car.status === 'available' ? 'fa-circle-check' : 'fa-screwdriver-wrench'"></i>
                    {{ car.status === 'available' ? 'Sẵn sàng' : 'Bảo trì' }}
                  </span>
                </div>
              </td>

              <!-- Trạng thái nội dung / xuất bản -->
              <td v-if="currentTab !== 'trash'" class="col-pub">
                <div class="publication-select-wrapper">
                  <select 
                    class="pub-select" 
                    :class="'pub-' + (car.publication_status || 'published')"
                    :value="car.publication_status || 'published'"
                    @change="changePublicationStatus(car, $event.target.value)"
                  >
                    <option value="published">Đã đăng</option>
                    <option value="draft">Bản nháp</option>
                    <option value="archived">Lưu trữ</option>
                  </select>
                </div>
              </td>

              <!-- Thời điểm xóa khi ở tab thùng rác -->
              <td v-if="currentTab === 'trash'" class="col-deleted-at">
                <div class="deleted-time-cell">
                  <i class="fa-regular fa-clock text-muted"></i>
                  <span>{{ formatDateTime(car.deleted_at) }}</span>
                </div>
              </td>

              <!-- Actions Cell -->
              <td class="col-actions actions-cell">
                <!-- Chế độ kho xe thông thường -->
                <template v-if="currentTab !== 'trash'">
                  <button 
                    class="btn-icon" 
                    :class="{ 'btn-disabled': car.status !== 'available' || car.publication_status !== 'published' }"
                    @click="onQuickBookingClick(car)" 
                    :title="car.status === 'available' && car.publication_status === 'published' ? 'Tạo đơn nhanh hộ khách' : 'Xe phải sẵn sàng và đã đăng mới có thể tạo đơn'"
                  >
                    <i class="fa-solid fa-calendar-plus"></i>
                  </button>
                  <button class="btn-icon" @click="openModal(car)" title="Chỉnh sửa thông số xe">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button 
                    class="btn-icon" 
                    @click="toggleStatus(car)" 
                    :title="car.status === 'available' ? 'Chuyển sang trạng thái bảo trì' : 'Chuyển sang trạng thái sẵn sàng'"
                  >
                    <i class="fa-solid fa-repeat"></i>
                  </button>
                  <button class="btn-icon text-danger" @click="requestDelete(car)" title="Chuyển vào thùng rác">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </template>

                <!-- Chế độ thùng rác -->
                <template v-else>
                  <button 
                    class="btn-action-restore" 
                    @click="requestRestore(car)" 
                    title="Khôi phục xe này vào kho xe hoạt động"
                  >
                    <i class="fa-solid fa-rotate-left"></i> Khôi phục
                  </button>
                  <button 
                    class="btn-icon text-danger" 
                    @click="requestForceDelete(car)" 
                    title="Xóa vĩnh viễn khỏi hệ thống (Chỉ nếu chưa từng phát sinh đơn)"
                  >
                    <i class="fa-solid fa-ban"></i>
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- API Error State with Retry -->
        <div v-else-if="fetchCarsError && !loading" class="empty-state-box error-box">
          <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 2.2rem; color: #dc2626; margin-bottom: 12px;"></i>
          <h4>Không thể tải kho xe</h4>
          <p>{{ fetchCarsError }}</p>
          <button class="btn-primary" @click="fetchCars(1)" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-rotate-right"></i> Thử lại
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid" :class="currentTab === 'trash' ? 'fa-trash-arrow-up' : 'fa-car-side'"></i>
          <h4 v-if="currentTab === 'trash'">Thùng rác trống</h4>
          <h4 v-else>{{ searchQuery.trim() || filterStatus || filterCategory || filterCity ? 'Không tìm thấy phương tiện phù hợp' : 'Chưa có phương tiện nào trong danh mục này' }}</h4>
          <p v-if="currentTab === 'trash'">Hiện tại không có phương tiện nào trong thùng rác.</p>
          <p v-else>{{ searchQuery.trim() || filterStatus || filterCategory || filterCity ? 'Thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc trạng thái xe.' : 'Bấm nút "Thêm xe mới" ở trên để đưa phương tiện vào hệ thống.' }}</p>
        </div>

        <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
        </div>
      </div>

      <!-- Server Pagination Bar -->
      <div class="pagination-footer" v-if="!loading && pagination.total > 0">
        <div class="pagination-info">
          Hiển thị từ <strong>{{ pagination.from || 0 }}</strong> đến <strong>{{ pagination.to || 0 }}</strong> trong tổng số <strong>{{ pagination.total }}</strong> xe
        </div>

        <div class="pagination-controls">
          <div class="per-page-selector">
            <span>Hiển thị:</span>
            <select v-model.number="perPage" @change="changePerPage" class="per-page-select">
              <option :value="10">10 / trang</option>
              <option :value="20">20 / trang</option>
              <option :value="50">50 / trang</option>
            </select>
          </div>

          <div class="page-nav">
            <button 
              class="page-btn nav-btn" 
              :disabled="pagination.current_page <= 1" 
              @click="goToPage(pagination.current_page - 1)"
              aria-label="Trang trước"
            >
              <i class="fa-solid fa-chevron-left"></i>
            </button>

            <button 
              v-for="(p, idx) in visiblePages" 
              :key="idx" 
              class="page-btn" 
              :class="{ active: p === pagination.current_page, dots: p === '...' }" 
              :disabled="p === '...'"
              @click="p !== '...' && goToPage(p)"
            >
              {{ p }}
            </button>

            <button 
              class="page-btn nav-btn" 
              :disabled="pagination.current_page >= pagination.last_page" 
              @click="goToPage(pagination.current_page + 1)"
              aria-label="Trang sau"
            >
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>{{ editingCar.id ? 'Cập nhật thông tin xe' : 'Thêm phương tiện mới' }}</h3>
            <p class="modal-sub">Điền đầy đủ thông tin kỹ thuật, tình trạng vận hành và trạng thái nội dung xuất bản</p>
          </div>
          <button class="btn-close" @click="showModal = false" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Tên xe <span class="required">*</span></label>
              <input type="text" v-model="editingCar.name" placeholder="VD: VinFast VF9 Plus" />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
            </div>

            <div class="form-group">
              <label>Giá thuê (VNĐ / Ngày) <span class="required">*</span></label>
              <input type="number" v-model.number="editingCar.price_per_day" min="100000" step="50000" />
              <small class="helper-text">Hiển thị: {{ formatCurrency(editingCar.price_per_day || 0) }}</small>
            </div>

            <div class="form-group">
              <label>Phân hạng xe <span class="required">*</span></label>
              <select v-model="editingCar.category_id" @change="onCategoryChange">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <!-- Tách riêng Vận hành và Trạng thái xuất bản -->
            <div class="form-group">
              <label>Trạng thái vận hành <span class="required">*</span></label>
              <select v-model="editingCar.status">
                <option value="available">Sẵn sàng vận hành (Khách có thể nhận xe)</option>
                <option value="maintenance">Đang bảo trì / Kỹ thuật (Tạm ngưng phục vụ)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Trạng thái xuất bản nội dung <span class="required">*</span></label>
              <select v-model="editingCar.publication_status">
                <option value="published">Đã xuất bản (Công khai hiển thị và nhận khách)</option>
                <option value="draft">Bản nháp (Đang cập nhật, ẩn khỏi trang khách)</option>
                <option value="archived">Lưu trữ (Tạm ngưng kinh doanh, ẩn khỏi trang khách)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Khu vực phục vụ <span class="required">*</span></label>
              <select v-model="editingCar.city">
                <option value="sg">TP. Hồ Chí Minh (sg)</option>
                <option value="hn">Hà Nội (hn)</option>
                <option value="dn">Đà Nẵng (dn)</option>
                <option value="all">Toàn quốc</option>
              </select>
            </div>

            <div class="form-group">
              <label>Dòng xe / Kiểu dáng</label>
              <input type="text" v-model="editingCar.type" placeholder="VD: SUV 7 chỗ, Sedan" />
            </div>

            <div class="form-group">
              <label>Loại nhiên liệu / Động cơ</label>
              <select v-model="editingCar.fuel_type">
                <option value="electric">Điện 100% (EV)</option>
                <option value="gasoline">Xăng</option>
                <option value="diesel">Dầu Diesel</option>
                <option value="hybrid">Động cơ Hybrid</option>
              </select>
            </div>

            <div class="form-group">
              <label>Số chỗ ngồi (để trống nếu chưa xác định)</label>
              <input type="number" v-model.number="editingCar.seats" placeholder="VD: 5 hoặc 7" min="2" max="50" />
            </div>

            <div class="form-group" v-if="editingCar.fuel_type === 'electric'">
              <label>Dung lượng Pin</label>
              <input type="text" v-model="editingCar.battery_capacity" placeholder="VD: 87.7 kWh, 123 kWh" />
            </div>

            <div class="form-group">
              <label>Tầm hoạt động / Tiêu hao</label>
              <input type="text" v-model="editingCar.range_per_charge" placeholder="VD: 438 km (NEDC) hoặc 6.5L/100km" />
            </div>

            <div class="form-group">
              <label>Hộp số</label>
              <input type="text" v-model="editingCar.transmission" placeholder="VD: Tự động, Tự động 1 cấp, Số sàn" />
            </div>

            <div class="form-group">
              <label>Năm sản xuất</label>
              <input type="number" v-model.number="editingCar.year_of_manufacture" placeholder="VD: 2024" min="2000" max="2030" />
            </div>

            <div class="form-group full">
              <label>Đường dẫn hình ảnh đại diện (URL) <span class="required">*</span></label>
              <input type="text" v-model="editingCar.image" placeholder="https://... hoặc /assets/images/vf9.png" />
              
              <!-- Image Preview Box -->
              <div class="img-preview-container">
                <span class="preview-label">Xem trước hình ảnh đại diện:</span>
                <div class="preview-box">
                  <img 
                    :src="editingCar.image || '/assets/images/vf9.png'" 
                    @error="$event.target.src = '/assets/images/vf9.png'" 
                    alt="Preview" 
                  />
                </div>
              </div>
            </div>

            <div class="form-group full">
              <label>Bộ ảnh chi tiết (Thư viện ảnh, nhập mỗi URL một dòng)</label>
              <textarea 
                v-model="galleryInput" 
                rows="3" 
                placeholder="/assets/images/cars/vf9.png&#10;/assets/images/cars/vf8.png"
                class="form-textarea"
              ></textarea>
              <small class="helper-text">Mỗi dòng là một đường dẫn ảnh chi tiết phục vụ trang chi tiết xe.</small>
            </div>

            <div class="form-group full">
              <label>Mô tả chi tiết phương tiện & điều kiện thuê</label>
              <textarea 
                v-model="editingCar.description" 
                rows="3" 
                placeholder="Mô tả ưu điểm nổi bật, trang bị tiện nghi, tính năng an toàn thực tế của xe..."
                class="form-textarea"
              ></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showModal = false">Hủy bỏ</button>
          <button class="btn-primary" @click="saveCar" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            {{ saving ? 'Đang lưu...' : (editingCar.id ? 'Lưu thay đổi' : 'Thêm xe ngay') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Soft Delete Confirmation Modal (Chuyển vào thùng rác) -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper warn-icon">
            <i class="fa-solid fa-trash-can"></i>
          </div>
          <div>
            <h3>Chuyển xe vào Thùng rác</h3>
            <p class="modal-sub">Xe sẽ tạm ẩn khỏi danh sách cho thuê và khách hàng</p>
          </div>
        </div>
        <div class="modal-body">
          <div class="delete-car-info">
            <p>Bạn có chắc chắn muốn đưa phương tiện này vào thùng rác?</p>
            <div class="highlight-box">
              <div class="hl-name">{{ carToDelete?.name }}</div>
              <div class="hl-meta">Mã định danh: <code>#CAR-{{ carToDelete?.id }}</code> | Phân hạng: {{ carToDelete?.category_name || carToDelete?.type || 'Tiêu chuẩn' }}</div>
            </div>
            <div class="integrity-notice">
              <i class="fa-solid fa-shield-halved text-success"></i>
              <div>
                <strong>Bảo toàn dữ liệu:</strong> Toàn bộ lịch sử đơn đặt xe, hợp đồng và hóa đơn phát sinh trước đây của xe này sẽ được giữ nguyên vẹn 100%. Bạn có thể khôi phục lại bất cứ lúc nào từ tab Thùng rác.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showDeleteModal = false">Hủy</button>
          <button class="btn-danger" @click="confirmDelete" :disabled="deleting">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ deleting ? 'Đang chuyển...' : 'Đưa vào thùng rác' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Restore Confirmation Modal (Khôi phục xe) -->
    <div v-if="showRestoreModal" class="modal-overlay" @click.self="showRestoreModal = false">
      <div class="modal-content card restore-modal animate-in">
        <div class="modal-header">
          <div class="restore-icon-wrapper">
            <i class="fa-solid fa-rotate-left"></i>
          </div>
          <div>
            <h3>Khôi phục phương tiện</h3>
            <p class="modal-sub">Đưa xe trở lại kho xe hoạt động</p>
          </div>
        </div>
        <div class="modal-body">
          <div class="delete-car-info">
            <p>Khôi phục phương tiện sau vào hệ thống quản lý?</p>
            <div class="highlight-box">
              <div class="hl-name">{{ carToRestore?.name }}</div>
              <div class="hl-meta">Mã định danh: <code>#CAR-{{ carToRestore?.id }}</code></div>
            </div>
            <p class="helper-text-box">
              Sau khi khôi phục, phương tiện sẽ xuất hiện trở lại trong danh sách xe theo trạng thái nội dung trước đó.
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showRestoreModal = false">Hủy</button>
          <button class="btn-primary" @click="confirmRestore" :disabled="restoring">
            <i v-if="restoring" class="fa-solid fa-spinner fa-spin"></i>
            {{ restoring ? 'Đang khôi phục...' : 'Đồng ý khôi phục' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Force Delete Confirmation Modal (Xóa vĩnh viễn) -->
    <div v-if="showForceDeleteModal" class="modal-overlay" @click.self="showForceDeleteModal = false">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper danger-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3 class="text-danger">Xóa Vĩnh Viễn Xe</h3>
            <p class="modal-sub">Hành động này không thể hoàn tác</p>
          </div>
        </div>
        <div class="modal-body">
          <div class="delete-car-info">
            <p>Bạn có chắc chắn muốn xóa vĩnh viễn phương tiện này khỏi cơ sở dữ liệu?</p>
            <div class="highlight-box highlight-danger">
              <div class="hl-name">{{ carToForceDelete?.name }}</div>
              <div class="hl-meta">Mã định danh: <code>#CAR-{{ carToForceDelete?.id }}</code></div>
            </div>
            <div class="integrity-alert">
              <i class="fa-solid fa-circle-exclamation"></i>
              <div>
                <strong>Quy tắc bảo vệ sổ sách:</strong> Hệ thống sẽ kiểm tra và từ chối xóa vĩnh viễn nếu phương tiện đã từng có lịch sử phát sinh bất kỳ đơn đặt xe hoặc giao dịch nào.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showForceDeleteModal = false">Hủy</button>
          <button class="btn-danger" @click="confirmForceDelete" :disabled="forceDeleting">
            <i v-if="forceDeleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ forceDeleting ? 'Đang xóa...' : 'Xóa vĩnh viễn' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Comprehensive Admin Booking Modal for Customer -->
    <div v-if="showBookingModal" class="modal-overlay" @click.self="showBookingModal = false">
      <div class="modal-content card animate-in admin-booking-modal">
        <div class="modal-header">
          <div>
            <h3><i class="fa-solid fa-calendar-plus text-primary"></i> Đặt Xe Hộ Khách Hàng</h3>
            <p class="modal-sub">Tạo đơn thuê xe trực tiếp với hồ sơ khách hàng minh bạch và tính tiền tự động từ server.</p>
          </div>
          <button class="btn-close" @click="showBookingModal = false" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div class="modal-body" v-if="selectedCar">
          <!-- Car Highlight Card -->
          <div class="selected-car-card">
            <img :src="selectedCar.image || '/assets/images/vf9.png'" :alt="selectedCar.name" class="car-thumb" />
            <div class="car-meta">
              <span class="badge blue">{{ selectedCar.type || 'Tiêu chuẩn' }}</span>
              <h4>{{ selectedCar.name }}</h4>
              <p class="car-rate">
                Đơn giá: <strong>{{ formatCurrency(selectedCar.price_per_day) }}</strong> / ngày
                <span class="city-tag"><i class="fa-solid fa-location-dot"></i> {{ getCityLabel(selectedCar.city) }}</span>
              </p>
            </div>
          </div>

          <!-- Customer Selection Section -->
          <div class="section-title">
            <i class="fa-solid fa-user-check"></i> Thông tin khách hàng thuê xe <span class="required">*</span>
          </div>

          <div class="customer-mode-switch">
            <button 
              type="button" 
              class="mode-btn" 
              :class="{ active: customerMode === 'existing' }" 
              @click="customerMode = 'existing'"
            >
              <i class="fa-solid fa-magnifying-glass"></i> Chọn khách hàng sẵn có
            </button>
            <button 
              type="button" 
              class="mode-btn" 
              :class="{ active: customerMode === 'new' }" 
              @click="customerMode = 'new'"
            >
              <i class="fa-solid fa-user-plus"></i> Tạo khách hàng mới
            </button>
          </div>

          <!-- Mode 1: Search Existing Customer -->
          <div v-if="customerMode === 'existing'" class="customer-search-box">
            <div v-if="!selectedCustomer" class="search-input-wrapper">
              <div class="input-with-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input 
                  type="text" 
                  v-model="customerSearchQuery" 
                  @input="onSearchCustomer" 
                  placeholder="Gõ tên, số điện thoại hoặc email để tìm khách..." 
                  class="form-control"
                />
                <span v-if="searchingCustomer" class="spinner-small"></span>
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="customerSearchResults.length > 0" class="search-results-dropdown">
                <div 
                  v-for="cust in customerSearchResults" 
                  :key="cust.id" 
                  class="customer-result-item"
                  @click="selectCustomer(cust)"
                >
                  <div class="cust-avatar"><i class="fa-solid fa-user"></i></div>
                  <div class="cust-info">
                    <div class="cust-name">{{ cust.ho_ten }}</div>
                    <div class="cust-contact">
                      <span><i class="fa-solid fa-phone"></i> {{ cust.so_dien_thoai || 'Chưa có SĐT' }}</span>
                      <span><i class="fa-solid fa-envelope"></i> {{ cust.email }}</span>
                    </div>
                  </div>
                  <button type="button" class="btn-select-customer">Chọn</button>
                </div>
              </div>

              <div v-else-if="customerSearchQuery.trim() && !searchingCustomer" class="no-result-text">
                Không tìm thấy khách hàng phù hợp. Hãy chuyển sang tab "Tạo khách hàng mới".
              </div>
            </div>

            <!-- Selected Customer Display Card -->
            <div v-else class="selected-customer-card">
              <div class="cust-badge-left">
                <div class="avatar-circle"><i class="fa-solid fa-check"></i></div>
                <div>
                  <div class="selected-cust-name">{{ selectedCustomer.ho_ten }}</div>
                  <div class="selected-cust-sub">
                    <span><i class="fa-solid fa-phone"></i> {{ selectedCustomer.so_dien_thoai }}</span> • 
                    <span><i class="fa-solid fa-envelope"></i> {{ selectedCustomer.email }}</span>
                  </div>
                </div>
              </div>
              <button type="button" class="btn-change-cust" @click="selectedCustomer = null">
                <i class="fa-solid fa-arrows-rotate"></i> Đổi khách
              </button>
            </div>
          </div>

          <!-- Mode 2: Quick Create New Customer -->
          <div v-else class="quick-create-customer-box">
            <div class="form-grid">
              <div class="form-group">
                <label>Họ và tên khách <span class="required">*</span></label>
                <input type="text" v-model="newCustomer.ho_ten" placeholder="VD: Nguyễn Văn A" />
                <span v-if="customerErrors.ho_ten" class="field-error">{{ customerErrors.ho_ten }}</span>
              </div>
              <div class="form-group">
                <label>Số điện thoại <span class="required">*</span></label>
                <input type="text" v-model="newCustomer.so_dien_thoai" placeholder="VD: 0987654321" />
                <span v-if="customerErrors.so_dien_thoai" class="field-error">{{ customerErrors.so_dien_thoai }}</span>
              </div>
              <div class="form-group">
                <label>Email khách hàng <span class="required">*</span></label>
                <input type="email" v-model="newCustomer.email" placeholder="VD: khachhang@gmail.com" />
                <span v-if="customerErrors.email" class="field-error">{{ customerErrors.email }}</span>
              </div>
              <div class="form-group">
                <label>Địa chỉ thường trú</label>
                <input type="text" v-model="newCustomer.dia_chi" placeholder="VD: 123 Nguyễn Huệ, Q1, TP.HCM" />
              </div>
            </div>
            <div class="quick-create-actions">
              <button type="button" class="btn-secondary" @click="saveAndSelectNewCustomer" :disabled="creatingCustomer">
                <i v-if="creatingCustomer" class="fa-solid fa-spinner fa-spin"></i>
                {{ creatingCustomer ? 'Đang tạo...' : 'Lưu & Chọn khách này' }}
              </button>
            </div>
          </div>

          <!-- Rental Schedule Section -->
          <div class="section-title mt-4">
            <i class="fa-solid fa-clock"></i> Thời gian & Địa điểm giao nhận
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>Thời gian nhận xe <span class="required">*</span></label>
              <input type="datetime-local" v-model="adminBookingForm.startDate" @change="calculateRentalSummary" />
            </div>
            <div class="form-group">
              <label>Thời gian trả xe <span class="required">*</span></label>
              <input type="datetime-local" v-model="adminBookingForm.endDate" @change="calculateRentalSummary" />
            </div>
            <div class="form-group">
              <label>Địa điểm nhận xe</label>
              <input type="text" v-model="adminBookingForm.dia_chi_nhan_xe" placeholder="Nhập địa chỉ giao xe tận nơi..." />
            </div>
            <div class="form-group">
              <label>Địa điểm trả xe</label>
              <input type="text" v-model="adminBookingForm.dia_chi_tra_xe" placeholder="Để trống nếu trùng địa điểm nhận..." />
            </div>
          </div>

          <!-- Purpose of Renting -->
          <div class="form-group full mt-2">
            <label>Mục đích thuê xe</label>
            <input type="text" v-model="adminBookingForm.ly_do_thue" placeholder="VD: Đi công tác tỉnh, du lịch gia đình..." />
          </div>

          <!-- Status & Payment Privileges for Admin -->
          <div class="section-title mt-4">
            <i class="fa-solid fa-sliders"></i> Trạng thái & Quyền hạn xử lý của Admin
          </div>

          <div class="privilege-grid">
            <div class="privilege-card">
              <label class="form-check-label">
                <input type="checkbox" v-model="adminBookingForm.autoApprove" />
                <span><strong>Duyệt đơn ngay lập tức</strong> (Chuyển sang trạng thái "Đã duyệt/Chờ giao xe")</span>
              </label>
            </div>
            <div class="privilege-card">
              <label class="form-check-label">
                <input type="checkbox" v-model="adminBookingForm.isPaid" />
                <span><strong>Xác nhận đã thanh toán</strong> (Khách đã chuyển khoản hoặc trả tiền mặt trực tiếp)</span>
              </label>
            </div>
          </div>

          <div class="form-group full" v-if="adminBookingForm.isPaid">
            <label>Ghi chú thanh toán / Mã giao dịch</label>
            <input type="text" v-model="adminBookingForm.paymentNote" placeholder="VD: Khách chuyển khoản VCB mã GD 987123" />
          </div>

          <!-- Price Calculation Box -->
          <div class="rental-price-summary-card">
            <div class="summary-row">
              <span>Đơn giá thuê mỗi ngày:</span>
              <strong>{{ formatCurrency(selectedCar.price_per_day) }}</strong>
            </div>
            <div class="summary-row">
              <span>Thời gian thuê tính toán:</span>
              <strong>{{ rentalSummary.days }} ngày</strong>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row total-row">
              <span>Tổng tiền thuê dự kiến:</span>
              <span class="total-price-tag">{{ formatCurrency(rentalSummary.totalPrice) }}</span>
            </div>
          </div>

          <div v-if="adminBookingError" class="alert-error-banner mt-3">
            <i class="fa-solid fa-circle-exclamation"></i> {{ adminBookingError }}
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="showBookingModal = false">Hủy</button>
          <button class="btn-primary" @click="submitAdminBooking" :disabled="submittingBooking">
            <i v-if="submittingBooking" class="fa-solid fa-spinner fa-spin"></i>
            {{ submittingBooking ? 'Đang tạo đơn...' : 'Xác nhận tạo đơn' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { resolveCarImage, handleCarImgError } from '../../../core/carImageHelper';

const router = useRouter();
const toaster = createToaster({ position: "top-right" });

// Cars data & categories
const cars = ref([]);
const categories = ref([]);
const loading = ref(true);
const fetchCarsError = ref(null);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const forceDeleting = ref(false);

// Tabs & Counters
const currentTab = ref('all'); // 'all' | 'published' | 'draft' | 'archived' | 'trash'
const counts = ref({
  all: 0,
  published: 0,
  draft: 0,
  archived: 0,
  trash: 0
});

// Server Search & Filters
const searchQuery = ref('');
const filterStatus = ref(''); // available | maintenance
const filterCategory = ref('');
const filterCity = ref('');
let searchDebounceTimer = null;

// Server Pagination
const perPage = ref(10);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0
});

// Edit/Add Modal
const showModal = ref(false);
const editingCar = ref({});
const formErrors = ref({});
const galleryInput = ref('');

// Soft Delete Confirmation Modal
const showDeleteModal = ref(false);
const carToDelete = ref(null);

// Restore Confirmation Modal
const showRestoreModal = ref(false);
const carToRestore = ref(null);

// Force Delete Confirmation Modal
const showForceDeleteModal = ref(false);
const carToForceDelete = ref(null);

// Admin Booking for Customer State
const showBookingModal = ref(false);
const selectedCar = ref(null);
const customerMode = ref('existing'); // 'existing' | 'new'
const customerSearchQuery = ref('');
const customerSearchResults = ref([]);
const searchingCustomer = ref(false);
const selectedCustomer = ref(null);
let customerSearchDebounceTimer = null;

const newCustomer = ref({
  ho_ten: '',
  so_dien_thoai: '',
  email: '',
  dia_chi: ''
});
const customerErrors = ref({});
const creatingCustomer = ref(false);

const getDefaultStartDate = () => {
  const d = new Date(Date.now() + 2 * 3600 * 1000); // 2h from now
  return d.toISOString().slice(0, 16);
};

const getDefaultEndDate = () => {
  const d = new Date(Date.now() + (2 + 24) * 3600 * 1000); // 26h from now (1 day)
  return d.toISOString().slice(0, 16);
};

const adminBookingForm = ref({
  startDate: getDefaultStartDate(),
  endDate: getDefaultEndDate(),
  dia_chi_nhan_xe: '',
  dia_chi_tra_xe: '',
  ly_do_thue: '',
  autoApprove: true,
  isPaid: false,
  paymentNote: ''
});

const rentalSummary = ref({
  days: 1,
  totalPrice: 0
});

const submittingBooking = ref(false);
const adminBookingError = ref('');

// Helper Labels
const getCityLabel = (code) => {
  if (!code) return 'Toàn quốc';
  const c = code.toLowerCase();
  if (c === 'sg' || c.includes('hồ chí minh') || c === '79') return 'TP. HCM';
  if (c === 'hn' || c.includes('hà nội') || c === '1') return 'Hà Nội';
  if (c === 'dn' || c.includes('đà nẵng') || c === '48') return 'Đà Nẵng';
  if (c === 'nt' || c.includes('nha trang') || c.includes('khánh hòa') || c === '56') return 'Nha Trang';
  if (c === 'hp' || c.includes('hải phòng') || c === '31') return 'Hải Phòng';
  if (c === 'ct' || c.includes('cần thơ') || c === '92') return 'Cần Thơ';
  return code.toUpperCase();
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

// Visible page numbers for pagination
const visiblePages = computed(() => {
  const total = pagination.value.last_page || 1;
  const current = pagination.value.current_page || 1;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  pages.push(1);
  if (current > 3) {
    pages.push('...');
  }
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  if (current < total - 2) {
    pages.push('...');
  }
  pages.push(total);
  return pages;
});

// Fetch Cars with Server-side Search, Filters, Tabs & Pagination
const fetchCars = async (page = 1) => {
  loading.value = true;
  fetchCarsError.value = null;
  try {
    const params = {
      page,
      per_page: perPage.value,
      tab: currentTab.value,
      search: searchQuery.value.trim() || undefined,
      status: filterStatus.value || undefined,
      category_id: filterCategory.value || undefined,
      city: filterCity.value || undefined,
    };

    const res = await baseRequest.get('admin/cars/data', { params });
    if (res.data && res.data.status && Array.isArray(res.data.data)) {
      cars.value = res.data.data;
      if (res.data.meta) {
        pagination.value = res.data.meta;
      } else {
        pagination.value = {
          current_page: 1,
          last_page: 1,
          per_page: cars.value.length,
          total: cars.value.length,
          from: 1,
          to: cars.value.length
        };
      }
      if (res.data.counts) {
        counts.value = res.data.counts;
      }
    } else {
      fetchCarsError.value = res.data?.message || "Không thể tải danh sách xe từ máy chủ.";
    }
  } catch (err) {
    fetchCarsError.value = "Lỗi kết nối máy chủ khi tải danh sách phương tiện.";
    toaster.error("Không thể tải danh sách xe từ máy chủ!");
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    fetchCars(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchCars(1);
};

const switchTab = (tab) => {
  currentTab.value = tab;
  fetchCars(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  filterStatus.value = '';
  filterCategory.value = '';
  filterCity.value = '';
  fetchCars(1);
};

const changePerPage = () => {
  fetchCars(1);
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return;
  fetchCars(page);
};

// Fetch Categories for Dropdown
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

const onCategoryChange = () => {
  const cat = categories.value.find(c => c.id === editingCar.value.category_id);
  if (cat && !editingCar.value.type) {
    editingCar.value.type = cat.name;
  }
};

// Modal / Editor Navigation
const openModal = (car = null) => {
  if (car && car.id) {
    router.push(`/admin/cars/edit/${car.id}`);
  } else {
    router.push('/admin/cars/create');
  }
};

// Save Car
const saveCar = async () => {
  formErrors.value = {};
  if (!editingCar.value.name?.trim()) {
    formErrors.value.name = "Vui lòng nhập tên xe.";
    return;
  }
  if (!editingCar.value.price_per_day || editingCar.value.price_per_day <= 0) {
    toaster.error("Giá thuê phải lớn hơn 0 VNĐ.");
    return;
  }

  // Parse galleryInput into array
  const lines = (galleryInput.value || '')
    .split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 0);
  editingCar.value.gallery = lines;

  saving.value = true;
  const url = editingCar.value.id ? 'admin/cars/update' : 'admin/cars/create';
  try {
    const res = await baseRequest.post(url, editingCar.value);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Lưu thông tin xe thành công!");
      showModal.value = false;
      await fetchCars(pagination.value.current_page);
    } else {
      toaster.error(res.data?.message || "Không thể lưu thông tin xe.");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi kết nối với máy chủ!");
  } finally {
    saving.value = false;
  }
};

// Toggle Operational Status
const toggleStatus = async (car) => {
  try {
    const res = await baseRequest.post('admin/cars/status', { id: car.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã đổi trạng thái vận hành xe!");
      car.status = (car.status === 'available' ? 'maintenance' : 'available');
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi hệ thống khi cập nhật trạng thái.");
  }
};

// Change Publication Status
const changePublicationStatus = async (car, newStatus) => {
  if (car.publication_status === newStatus) return;
  try {
    const res = await baseRequest.post('admin/cars/publication-status', {
      id: car.id,
      publication_status: newStatus
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã cập nhật trạng thái xuất bản!");
      car.publication_status = newStatus;
      car.is_active = (newStatus === 'published');
      await fetchCars(pagination.value.current_page);
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái xuất bản!");
      // Revert UI select value by fetching
      await fetchCars(pagination.value.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi cập nhật trạng thái xuất bản!");
    await fetchCars(pagination.value.current_page);
  }
};

// Quick Booking Click validation
const onQuickBookingClick = (car) => {
  if (car.status !== 'available') {
    toaster.warning("Xe đang trong trạng thái Bảo trì, không thể tạo đơn đặt xe!");
    return;
  }
  if (car.publication_status !== 'published') {
    toaster.warning("Xe chưa được Xuất bản công khai, không thể tạo đơn đặt xe!");
    return;
  }
  openBookingModal(car);
};

// Soft Delete (Chuyển thùng rác)
const requestDelete = (car) => {
  carToDelete.value = car;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (!carToDelete.value) return;
  deleting.value = true;
  try {
    const res = await baseRequest.post('admin/cars/delete', { id: carToDelete.value.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã chuyển xe vào thùng rác thành công!");
      showDeleteModal.value = false;
      carToDelete.value = null;
      await fetchCars(pagination.value.current_page);
    } else {
      toaster.error(res.data?.message || "Không thể chuyển phương tiện này vào thùng rác.");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi chuyển phương tiện vào thùng rác.");
  } finally {
    deleting.value = false;
  }
};

// Restore from Trash
const requestRestore = (car) => {
  carToRestore.value = car;
  showRestoreModal.value = true;
};

const confirmRestore = async () => {
  if (!carToRestore.value) return;
  restoring.value = true;
  try {
    const res = await baseRequest.post('admin/cars/restore', { id: carToRestore.value.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã khôi phục xe thành công!");
      showRestoreModal.value = false;
      carToRestore.value = null;
      await fetchCars(pagination.value.current_page);
    } else {
      toaster.error(res.data?.message || "Không thể khôi phục xe.");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi khôi phục xe.");
  } finally {
    restoring.value = false;
  }
};

// Force Delete
const requestForceDelete = (car) => {
  carToForceDelete.value = car;
  showForceDeleteModal.value = true;
};

const confirmForceDelete = async () => {
  if (!carToForceDelete.value) return;
  forceDeleting.value = true;
  try {
    const res = await baseRequest.post('admin/cars/force-delete', { id: carToForceDelete.value.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa vĩnh viễn xe khỏi hệ thống!");
      showForceDeleteModal.value = false;
      carToForceDelete.value = null;
      await fetchCars(pagination.value.current_page);
    } else {
      toaster.error(res.data?.message || "Không thể xóa vĩnh viễn xe.");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi xóa vĩnh viễn phương tiện.");
  } finally {
    forceDeleting.value = false;
  }
};

// Booking Modal Logic
const openBookingModal = (car) => {
  selectedCar.value = car;
  selectedCustomer.value = null;
  customerSearchQuery.value = '';
  customerSearchResults.value = [];
  customerMode.value = 'existing';
  customerErrors.value = {};
  adminBookingError.value = '';
  
  adminBookingForm.value.startDate = getDefaultStartDate();
  adminBookingForm.value.endDate = getDefaultEndDate();
  adminBookingForm.value.dia_chi_nhan_xe = '';
  adminBookingForm.value.dia_chi_tra_xe = '';
  adminBookingForm.value.ly_do_thue = '';
  adminBookingForm.value.autoApprove = true;
  adminBookingForm.value.isPaid = false;
  adminBookingForm.value.paymentNote = '';
  
  calculateRentalSummary();
  showBookingModal.value = true;
};

const onSearchCustomer = () => {
  if (customerSearchDebounceTimer) clearTimeout(customerSearchDebounceTimer);
  const q = customerSearchQuery.value.trim();
  if (!q) {
    customerSearchResults.value = [];
    return;
  }
  
  customerSearchDebounceTimer = setTimeout(async () => {
    searchingCustomer.value = true;
    try {
      const res = await baseRequest.get('admin/users/search', { params: { q, per_page: 10 } });
      if (res.data && res.data.status) {
        customerSearchResults.value = res.data.data;
      }
    } catch (err) {
      console.error("Error searching customers:", err);
    } finally {
      searchingCustomer.value = false;
    }
  }, 300);
};

const selectCustomer = (cust) => {
  selectedCustomer.value = cust;
  customerSearchResults.value = [];
  customerSearchQuery.value = '';
  adminBookingError.value = '';
};

const saveAndSelectNewCustomer = async () => {
  customerErrors.value = {};
  if (!newCustomer.value.ho_ten?.trim()) {
    customerErrors.value.ho_ten = "Vui lòng nhập họ và tên khách!";
    return;
  }
  if (!newCustomer.value.so_dien_thoai?.trim()) {
    customerErrors.value.so_dien_thoai = "Vui lòng nhập số điện thoại!";
    return;
  }
  if (!newCustomer.value.email?.trim()) {
    customerErrors.value.email = "Vui lòng nhập email khách hàng!";
    return;
  }

  creatingCustomer.value = true;
  try {
    const res = await baseRequest.post('admin/users/quick-create', newCustomer.value);
    if (res.data && res.data.status) {
      toaster.success("Tạo mới khách hàng thành công!");
      selectedCustomer.value = res.data.data;
      customerMode.value = 'existing';
      newCustomer.value = { ho_ten: '', so_dien_thoai: '', email: '', dia_chi: '' };
    } else {
      toaster.error(res.data?.message || "Không thể tạo khách hàng!");
    }
  } catch (err) {
    if (err.response?.data?.errors) {
      customerErrors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    }
    toaster.error(err.response?.data?.message || "Lỗi khi tạo hồ sơ khách hàng!");
  } finally {
    creatingCustomer.value = false;
  }
};

const calculateRentalSummary = () => {
  if (!selectedCar.value) return;
  const start = new Date(adminBookingForm.value.startDate);
  const end = new Date(adminBookingForm.value.endDate);
  
  if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
    rentalSummary.value = { days: 1, totalPrice: selectedCar.value.price_per_day };
    return;
  }
  
  const diffHours = (end - start) / (1000 * 3600);
  const days = Math.max(1, Math.ceil(diffHours / 24));
  rentalSummary.value = {
    days,
    totalPrice: days * (selectedCar.value.price_per_day || 0)
  };
};

const formatApiDate = (dt) => {
  if (!dt) return '';
  return dt.replace('T', ' ') + (dt.length === 16 ? ':00' : '');
};

const submitAdminBooking = async () => {
  adminBookingError.value = '';
  
  if (!selectedCustomer.value) {
    adminBookingError.value = "Vui lòng chọn hoặc tạo mới một khách hàng thuê xe!";
    toaster.warning(adminBookingError.value);
    return;
  }
  
  const start = new Date(adminBookingForm.value.startDate);
  const end = new Date(adminBookingForm.value.endDate);
  
  if (end <= start) {
    adminBookingError.value = "Thời gian trả xe phải sau thời gian nhận xe!";
    toaster.warning(adminBookingError.value);
    return;
  }

  submittingBooking.value = true;
  try {
    const payload = {
      customer_id: selectedCustomer.value.id,
      car_id: selectedCar.value.id,
      start_date: formatApiDate(adminBookingForm.value.startDate),
      end_date: formatApiDate(adminBookingForm.value.endDate),
      dia_chi_nhan_xe: adminBookingForm.value.dia_chi_nhan_xe || null,
      dia_chi_tra_xe: adminBookingForm.value.dia_chi_tra_xe || adminBookingForm.value.dia_chi_nhan_xe || null,
      ly_do_thue: adminBookingForm.value.ly_do_thue || null,
      tinh_trang: adminBookingForm.value.autoApprove ? 1 : 0,
      is_thanh_toan: adminBookingForm.value.isPaid ? 1 : 0,
      payment_note: adminBookingForm.value.paymentNote || null
    };

    const res = await baseRequest.post('admin/bookings/create-for-customer', payload);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Tạo đơn đặt xe hộ thành công!");
      showBookingModal.value = false;
      await fetchCars(pagination.value.current_page);
    } else {
      adminBookingError.value = res.data?.message || "Không thể tạo đơn đặt xe.";
      toaster.error(adminBookingError.value);
    }
  } catch (err) {
    if (err.response?.status === 409 || err.response?.data?.error_type === 'SCHEDULE_CONFLICT') {
      adminBookingError.value = err.response?.data?.message || "Xe đã có người đặt trong khoảng thời gian này!";
      toaster.warning(adminBookingError.value);
    } else {
      adminBookingError.value = err.response?.data?.message || "Lỗi hệ thống khi tạo đơn đặt xe!";
      toaster.error(adminBookingError.value);
    }
  } finally {
    submittingBooking.value = false;
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    showModal.value = false;
    showDeleteModal.value = false;
    showRestoreModal.value = false;
    showForceDeleteModal.value = false;
    showBookingModal.value = false;
  }
};

onMounted(() => {
  fetchCars(1);
  fetchCategories();
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.admin-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 52px;
  height: 52px;
  background: white;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: var(--color-primary);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.page-title span { color: var(--color-primary); }
.page-subtitle { color: var(--color-text-muted); font-size: 0.9rem; margin-top: 2px; }

.content-card {
  padding: 0;
  overflow: hidden;
  position: relative;
}

/* Tabs Bar */
.tabs-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px 20px 0;
  background: #F8FAFC;
  border-bottom: 1px solid var(--color-border);
  overflow-x: auto;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.tab-item:hover {
  color: var(--color-text-main);
  background: rgba(241, 245, 249, 0.8);
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}

.tab-item.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  background: white;
  border-radius: var(--radius-sm) var(--radius-sm) 0 0;
}

.tab-trash.active {
  color: #DC2626;
  border-bottom-color: #DC2626;
}

.tab-badge {
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  background: #E2E8F0;
  color: #475569;
}

.tab-item.active .tab-badge {
  background: #EFF6FF;
  color: var(--color-primary);
}

.badge-success { background: #DCFCE7 !important; color: #15803D !important; }
.badge-warning { background: #FEF3C7 !important; color: #B45309 !important; }
.badge-muted { background: #F1F5F9 !important; color: #64748B !important; }
.badge-danger { background: #FEE2E2 !important; color: #B91C1C !important; }

/* Tools */
.table-tools {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid var(--color-border);
  background: white;
  flex-wrap: wrap;
}

.search-input {
  position: relative;
  flex: 1;
  min-width: 260px;
  max-width: 400px;
}

.search-input i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
}

.search-input input {
  width: 100%;
  padding: 9px 36px 9px 38px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
  outline: none;
  font-size: 0.88rem;
  transition: all 0.2s;
}

.search-input input:focus {
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.clear-search-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.form-select {
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-main);
  outline: none;
}

.btn-reset-filters {
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  border: 1px dashed var(--color-border);
  background: var(--bg-surface-alt);
  color: var(--color-text-muted);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-reset-filters:hover {
  background: #E2E8F0;
  color: var(--color-text-main);
}

/* Table */
.data-table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
}

.data-table th {
  background: var(--bg-surface-alt);
  padding: 13px 18px;
  text-align: left;
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
  white-space: nowrap;
}

.data-table td {
  padding: 13px 18px;
  border-bottom: 1px solid var(--color-border);
  color: var(--color-text-main);
  vertical-align: middle;
}

/* Explicit Column Sizing & No-Wrap */
.col-stt {
  width: 56px;
  text-align: center;
  white-space: nowrap;
}

.col-car-info {
  min-width: 280px;
}

.col-category {
  width: 130px;
  min-width: 120px;
  white-space: nowrap;
}

.col-price {
  width: 160px;
  min-width: 150px;
  white-space: nowrap;
}

.col-city {
  width: 150px;
  min-width: 140px;
  white-space: nowrap;
}

.col-status {
  width: 135px;
  min-width: 130px;
  white-space: nowrap;
}

.col-pub {
  width: 135px;
  min-width: 130px;
  white-space: nowrap;
}

.col-deleted-at {
  width: 160px;
  min-width: 150px;
  white-space: nowrap;
}

.col-actions {
  width: 180px;
  min-width: 180px;
  text-align: center;
  white-space: nowrap;
}

.stt-badge {
  font-weight: 700;
  color: var(--color-text-muted);
  font-size: 0.84rem;
}

.car-cell {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 260px;
}

.car-thumb {
  width: 76px;
  height: 48px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.car-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.car-name {
  font-weight: 700;
  color: var(--color-text-main);
  font-size: 0.94rem;
  line-height: 1.35;
  white-space: nowrap;
}

.car-id {
  font-size: 0.74rem;
  color: var(--color-text-muted);
  font-family: var(--font-mono, monospace);
  font-weight: 600;
  white-space: nowrap;
  letter-spacing: 0.3px;
}

.tag-outline {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 9999px;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--color-text-main);
  white-space: nowrap;
}

.price-box {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
  white-space: nowrap;
}

.price-text {
  font-weight: 800;
  color: #059669;
  font-size: 0.95rem;
  white-space: nowrap;
  letter-spacing: -0.2px;
}

.price-unit {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  font-weight: 500;
  white-space: nowrap;
}

.city-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.82rem;
  color: var(--color-text-muted);
  white-space: nowrap;
}

.city-badge i { color: #EF4444; }

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 700;
  white-space: nowrap;
}

.status-ok { background: #DCFCE7; color: #15803D; }
.status-warn { background: #FEF3C7; color: #B45309; }

/* Publication Status Selector */
.pub-select {
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 700;
  outline: none;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s;
}

.pub-select.pub-published {
  background: #DCFCE7;
  color: #15803D;
  border-color: #86EFAC;
}

.pub-select.pub-draft {
  background: #FEF3C7;
  color: #B45309;
  border-color: #FCD34D;
}

.pub-select.pub-archived {
  background: #F1F5F9;
  color: #64748B;
  border-color: #CBD5E1;
}

.deleted-time-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: #64748B;
  white-space: nowrap;
}

/* Actions */
.actions-cell {
  text-align: center;
  white-space: nowrap;
}

.btn-icon {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  color: var(--color-text-muted);
  cursor: pointer;
  margin: 0 3px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: var(--bg-surface-alt);
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.btn-icon.text-danger:hover {
  background: #FEE2E2;
  color: #DC2626;
  border-color: #F87171;
}

.btn-disabled {
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}

.btn-action-restore {
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid #10B981;
  background: #ECFDF5;
  color: #047857;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  margin-right: 6px;
}

.btn-action-restore:hover {
  background: #10B981;
  color: white;
}

/* Server Pagination Bar */
.pagination-footer {
  padding: 14px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid var(--color-border);
  background: #F8FAFC;
  flex-wrap: wrap;
  gap: 12px;
}

.pagination-info {
  font-size: 0.84rem;
  color: var(--color-text-muted);
}

.pagination-info strong { color: var(--color-text-main); }

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 14px;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: var(--color-text-muted);
}

.per-page-select {
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-main);
  outline: none;
}

.page-nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

.page-btn {
  min-width: 32px;
  height: 32px;
  padding: 0 6px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  color: var(--color-text-main);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled):not(.dots) {
  background: var(--bg-surface-alt);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.page-btn.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-btn.dots {
  border: none;
  background: transparent;
  cursor: default;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--color-border);
}

.modal-content.delete-modal,
.modal-content.restore-modal {
  max-width: 520px;
}

.modal-content.admin-booking-modal {
  max-width: 760px;
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-surface-alt);
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 2px;
}

.modal-sub {
  font-size: 0.82rem;
  color: var(--color-text-muted);
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.2rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: var(--bg-surface-alt);
}

/* Delete / Restore Highlights */
.delete-icon-wrapper,
.restore-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.delete-icon-wrapper.warn-icon {
  background: #FEF3C7;
  color: #D97706;
}

.delete-icon-wrapper.danger-icon {
  background: #FEE2E2;
  color: #DC2626;
}

.restore-icon-wrapper {
  background: #DCFCE7;
  color: #16A34A;
}

.highlight-box {
  padding: 12px 16px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  margin: 14px 0;
}

.highlight-box.highlight-danger {
  background: #FEF2F2;
  border-color: #FECACA;
}

.hl-name {
  font-weight: 700;
  font-size: 1rem;
  color: var(--color-text-main);
}

.hl-meta {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin-top: 4px;
}

.integrity-notice {
  padding: 12px 14px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  color: #166534;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  line-height: 1.5;
}

.integrity-alert {
  padding: 12px 14px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  color: #991B1B;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  line-height: 1.5;
}

.helper-text-box {
  font-size: 0.84rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  margin-top: 8px;
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
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
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-text-main);
}

.form-group input,
.form-group select,
.form-textarea {
  padding: 9px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.88rem;
  color: var(--color-text-main);
  outline: none;
}

.form-group input:focus,
.form-group select:focus,
.form-textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.required { color: #DC2626; }
.field-error { font-size: 0.78rem; color: #DC2626; }
.helper-text { font-size: 0.78rem; color: var(--color-text-muted); }

/* Image Preview */
.img-preview-container {
  margin-top: 8px;
}

.preview-label {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  display: block;
  margin-bottom: 4px;
}

.preview-box {
  width: 140px;
  height: 80px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--bg-surface-alt);
}

.preview-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Empty State & Errors */
.empty-state-box {
  padding: 48px 20px;
  text-align: center;
  color: var(--color-text-muted);
}

.empty-state-box i {
  font-size: 3rem;
  margin-bottom: 12px;
  opacity: 0.4;
}

.empty-state-box h4 {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 6px;
}

.empty-state-box p {
  font-size: 0.88rem;
}

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 38px;
  height: 38px;
  border: 3px solid #E2E8F0;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Admin Booking Modal Elements */
.selected-car-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  margin-bottom: 18px;
}

.selected-car-card .car-thumb {
  width: 90px;
  height: 56px;
}

.car-meta h4 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin: 3px 0;
}

.car-rate {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.city-tag {
  margin-left: 8px;
  font-size: 0.78rem;
  color: #475569;
}

.section-title {
  font-size: 0.88rem;
  font-weight: 800;
  color: var(--color-text-main);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.customer-mode-switch {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.mode-btn {
  flex: 1;
  padding: 8px 14px;
  border: 1px solid var(--color-border);
  background: white;
  border-radius: var(--radius-sm);
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.mode-btn.active {
  background: #EFF6FF;
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.input-with-icon {
  position: relative;
}

.input-with-icon i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
}

.input-with-icon input {
  padding-left: 36px;
  width: 100%;
}

.search-results-dropdown {
  margin-top: 6px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: white;
  max-height: 180px;
  overflow-y: auto;
  box-shadow: var(--shadow-md);
}

.customer-result-item {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  transition: background 0.15s;
}

.customer-result-item:hover {
  background: var(--bg-surface-alt);
}

.cust-name { font-weight: 700; font-size: 0.88rem; color: var(--color-text-main); }
.cust-contact { font-size: 0.78rem; color: var(--color-text-muted); display: flex; gap: 12px; margin-top: 2px; }

.btn-select-customer {
  padding: 4px 10px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 0.76rem;
  font-weight: 700;
  cursor: pointer;
}

.selected-customer-card {
  padding: 12px 16px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: var(--radius-md);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cust-badge-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.cust-badge-left .avatar-circle {
  width: 36px;
  height: 36px;
  background: #10B981;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
}

.selected-cust-name { font-weight: 700; color: #065F46; font-size: 0.95rem; }
.selected-cust-sub { font-size: 0.8rem; color: #047857; margin-top: 2px; }

.btn-change-cust {
  padding: 6px 12px;
  background: white;
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #047857;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.no-result-text {
  padding: 12px 14px;
  font-size: 0.84rem;
  color: var(--color-text-muted);
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  margin-top: 6px;
}

.quick-create-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}

.privilege-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 20px;
}

.privilege-card {
  padding: 12px 14px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.privilege-card .form-check-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 0.84rem;
}

.rental-price-summary-card {
  padding: 14px 18px;
  background: #0F172A;
  color: #F8FAFC;
  border-radius: var(--radius-md);
  margin-top: 20px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.88rem;
  color: #94A3B8;
  margin-bottom: 6px;
}

.summary-row strong { color: #F8FAFC; }
.summary-divider {
  height: 1px;
  background: #334155;
  margin: 10px 0;
}

.summary-row.total-row {
  color: #F8FAFC;
  font-size: 0.95rem;
  margin-bottom: 0;
}

.total-price-tag {
  color: #F59E0B;
  font-size: 1.25rem;
  font-weight: 800;
}

.alert-error-banner {
  padding: 10px 14px;
  background: #FEF2F2;
  border: 1px solid #FCA5A5;
  color: #B91C1C;
  border-radius: var(--radius-sm);
  font-size: 0.86rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.mt-2 { margin-top: 8px; }
.mt-3 { margin-top: 12px; }
.mt-4 { margin-top: 16px; }

@media (max-width: 768px) {
  .header-main { gap: 12px; }
  .page-title { font-size: 1.3rem; }
  .table-tools { flex-direction: column; align-items: stretch; }
  .search-input { max-width: 100%; }
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: span 1; }
  .privilege-grid { grid-template-columns: 1fr; }
}
</style>
