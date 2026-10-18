<template>
  <div class="admin-page">
    <!-- Page Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-headset"></i></div>
        <div>
          <h1 class="page-title">Hỗ Trợ & <span>Yêu Cầu Tư Vấn</span></h1>
          <p class="page-subtitle">Phân công chuyên viên, theo dõi tiến độ xử lý và lưu trữ lịch sử phản hồi khách hàng.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchContacts" :disabled="loading" title="Tải lại danh sách">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
      </div>
    </header>

    <!-- KPI Summary Cards (6 Cards) -->
    <div class="kpi-grid">
      <!-- Tất cả -->
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '' && filterAssignee === '' }" 
        @click="resetKpiFilter"
      >
        <div class="kpi-icon-box total">
          <i class="fa-solid fa-inbox"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Tổng yêu cầu</span>
          <span class="kpi-value">{{ counts.all || contacts.length }}</span>
        </div>
      </div>

      <!-- Chờ xử lý -->
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '0' && filterAssignee === '' }" 
        @click="setKpiStatus('0')"
      >
        <div class="kpi-icon-box pending">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Chờ xử lý</span>
          <span class="kpi-value">{{ counts.pending }}</span>
        </div>
      </div>

      <!-- Đang xử lý -->
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '1' && filterAssignee === '' }" 
        @click="setKpiStatus('1')"
      >
        <div class="kpi-icon-box processing">
          <i class="fa-solid fa-spinner"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đang xử lý</span>
          <span class="kpi-value">{{ counts.in_progress }}</span>
        </div>
      </div>

      <!-- Đã giải quyết -->
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '2' && filterAssignee === '' }" 
        @click="setKpiStatus('2')"
      >
        <div class="kpi-icon-box resolved">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đã giải quyết</span>
          <span class="kpi-value">{{ counts.resolved }}</span>
        </div>
      </div>

      <!-- Chưa phân công -->
      <div 
        class="kpi-card" 
        :class="{ 'active': filterAssignee === 'unassigned' }" 
        @click="setKpiAssignee('unassigned')"
      >
        <div class="kpi-icon-box unassigned">
          <i class="fa-solid fa-user-xmark"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Chưa phân công</span>
          <span class="kpi-value">{{ counts.unassigned }}</span>
        </div>
      </div>

      <!-- Việc của tôi -->
      <div 
        class="kpi-card" 
        :class="{ 'active': isMyAssignedFilterActive }" 
        @click="setKpiMyAssigned"
      >
        <div class="kpi-icon-box my-tasks">
          <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Việc của tôi</span>
          <span class="kpi-value">{{ counts.my_assigned }}</span>
        </div>
      </div>
    </div>

    <!-- Main Content Table & Filters -->
    <div class="content-card card">
      <!-- Toolbar Filters -->
      <div class="table-tools">
        <div class="filter-row-top">
          <!-- Keyword Search -->
          <div class="search-input">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              placeholder="Tìm theo họ tên, email, SĐT, tiêu đề, mã xe..." 
              v-model="searchQuery" 
              @keyup.enter="applyFilters"
            />
            <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''; applyFilters()">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Quick Search Button -->
          <button class="btn-filter-search" @click="applyFilters" title="Tìm kiếm">
            <i class="fa-solid fa-filter"></i>
            <span>Lọc</span>
          </button>
        </div>

        <div class="filter-row-bottom">
          <!-- Filter Status -->
          <div class="filter-item">
            <label class="filter-label"><i class="fa-solid fa-list-check"></i> Trạng thái:</label>
            <select v-model="filterStatus" class="form-select" @change="applyFilters">
              <option value="">Tất cả trạng thái</option>
              <option value="0">Chờ xử lý (Mới)</option>
              <option value="1">Đang xử lý</option>
              <option value="2">Đã giải quyết</option>
            </select>
          </div>

          <!-- Filter Assignee -->
          <div class="filter-item">
            <label class="filter-label"><i class="fa-solid fa-user-tie"></i> Phụ trách:</label>
            <select v-model="filterAssignee" class="form-select" @change="applyFilters">
              <option value="">Tất cả người phụ trách</option>
              <option value="unassigned">Chưa phân công (Cần nhận việc)</option>
              <option v-if="currentAdminId" :value="currentAdminId">Việc của tôi</option>
              <optgroup label="Danh sách chuyên viên">
                <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                  {{ staff.ho_ten }} ({{ staff.role_label || 'Chuyên viên' }})
                </option>
              </optgroup>
            </select>
          </div>

          <!-- Filter Date Range -->
          <div class="filter-item date-range-group">
            <label class="filter-label"><i class="fa-regular fa-calendar"></i> Khoảng ngày:</label>
            <div class="date-inputs">
              <input 
                type="date" 
                v-model="filterFromDate" 
                class="form-input-date" 
                title="Từ ngày"
                @change="applyFilters"
              />
              <span class="date-sep">-</span>
              <input 
                type="date" 
                v-model="filterToDate" 
                class="form-input-date" 
                title="Đến ngày"
                @change="applyFilters"
              />
            </div>
          </div>

          <!-- Reset Filter Button -->
          <button 
            v-if="hasActiveFilters" 
            class="btn-reset-filters" 
            @click="resetAllFilters"
            title="Xóa tất cả bộ lọc"
          >
            <i class="fa-solid fa-rotate-left"></i>
            <span>Đặt lại</span>
          </button>
        </div>
      </div>

      <!-- Table View -->
      <div class="table-responsive">
        <table class="premium-table" v-if="contacts.length > 0">
          <thead>
            <tr>
              <th width="90">Mã yêu cầu</th>
              <th width="210">Khách hàng</th>
              <th width="190">Xe quan tâm</th>
              <th>Tiêu đề & Nội dung</th>
              <th width="130">Thời gian gửi</th>
              <th width="125">Trạng thái</th>
              <th width="195">Người phụ trách</th>
              <th class="text-center" width="100">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="c in contacts" 
              :key="c.id" 
              :class="{ 
                'unread-row': Number(c.trang_thai) === 0, 
                'unassigned-row': !c.assigned_to && Number(c.trang_thai) !== 2 
              }"
            >
              <!-- Request ID -->
              <td>
                <span class="id-tag">#REQ-{{ c.id }}</span>
              </td>

              <!-- Customer info -->
              <td>
                <div class="user-cell">
                  <div class="user-header">
                    <span class="name-bold">{{ c.ho_ten }}</span>
                    <span v-if="c.user_id || c.user" class="badge-account-tiny" title="Đã có tài khoản hệ thống">
                      <i class="fa-solid fa-circle-check"></i> Thành viên
                    </span>
                    <span v-else class="badge-guest-tiny" title="Khách vãng lai">
                      Vãng lai
                    </span>
                  </div>
                  <div class="contact-sub">
                    <span :title="c.email"><i class="fa-regular fa-envelope"></i> {{ c.email }}</span>
                    <span v-if="c.so_dien_thoai"><i class="fa-solid fa-phone"></i> {{ c.so_dien_thoai }}</span>
                  </div>
                </div>
              </td>

              <!-- Car of Interest -->
              <td>
                <div v-if="c.car" class="car-interest-cell">
                  <img 
                    :src="c.car.image || '/assets/images/default-car.png'" 
                    :alt="c.car.name" 
                    class="car-thumb"
                    @error="$event.target.src = '/assets/images/default-car.png'"
                  />
                  <div class="car-info">
                    <span class="car-name" :title="c.car.name">{{ c.car.name }}</span>
                    <span class="car-plate" v-if="c.car.license_plate">{{ c.car.license_plate }}</span>
                  </div>
                </div>
                <div v-else class="no-car-linked">
                  <i class="fa-regular fa-circle-question"></i>
                  <span>Chưa chỉ định xe</span>
                </div>
              </td>

              <!-- Content and Subject -->
              <td>
                <div class="content-cell">
                  <div class="subject-line">{{ c.tieu_de }}</div>
                  <div class="message-preview" :title="c.noi_dung">{{ c.noi_dung }}</div>
                  <!-- Latest Note snippet if available -->
                  <div v-if="c.ghi_chu_noi_bo" class="latest-note-badge" :title="c.ghi_chu_noi_bo">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>{{ c.ghi_chu_noi_bo }}</span>
                  </div>
                </div>
              </td>

              <!-- Timestamp -->
              <td>
                <div class="time-cell">
                  <span>{{ formatDate(c.created_at) }}</span>
                  <small>{{ formatTime(c.created_at) }}</small>
                </div>
              </td>

              <!-- Status -->
              <td>
                <span class="status-badge" :class="getStatusClass(c.trang_thai)">
                  <i class="fa-solid" :class="getStatusIcon(c.trang_thai)"></i>
                  {{ getStatusText(c.trang_thai) }}
                </span>
              </td>

              <!-- Assignee & Handler -->
              <td>
                <div class="assignee-cell">
                  <div v-if="c.assignee" class="assignee-box">
                    <div class="staff-avatar">
                      <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="staff-meta">
                      <span class="staff-name">{{ c.assignee.ho_ten }}</span>
                      <small class="staff-role">{{ getRoleLabel(c.assignee.role) }}</small>
                    </div>
                  </div>
                  <div v-else class="unassigned-action-box">
                    <span class="badge-unassigned">
                      <i class="fa-regular fa-circle-xmark"></i> Chưa giao
                    </span>
                    <button 
                      class="btn-quick-claim" 
                      @click="handleQuickClaim(c)" 
                      title="Nhận yêu cầu này về mình xử lý"
                    >
                      <i class="fa-solid fa-hand-holding-hand"></i>
                      <span>Nhận việc</span>
                    </button>
                  </div>

                  <!-- Last Updater or Completer Info -->
                  <div class="updater-sub" v-if="c.completer && Number(c.trang_thai) === 2">
                    <i class="fa-solid fa-check-double text-success"></i>
                    <span>Hoàn tất bởi: {{ c.completer.ho_ten }}</span>
                  </div>
                  <div class="updater-sub" v-else-if="c.updater && c.updater.id !== c.assigned_to">
                    <i class="fa-solid fa-pen-nib"></i>
                    <span>Sửa bởi: {{ c.updater.ho_ten }}</span>
                  </div>
                </div>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <div class="action-btn-group">
                  <button class="btn-action-view" @click="openDetail(c)" title="Xem chi tiết & Xử lý">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Xử lý</span>
                  </button>
                  <button class="btn-action-appt" @click.stop="createAppointmentFromContact(c)" title="Đặt lịch hẹn tư vấn/xem xe">
                    <i class="fa-solid fa-calendar-plus"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-clipboard-check"></i>
          <h4>Không tìm thấy yêu cầu tư vấn nào</h4>
          <p>Không có yêu cầu phù hợp với bộ lọc hiện tại. Thử thay đổi từ khóa hoặc điều kiện lọc.</p>
          <button class="btn-outline-sm" @click="resetAllFilters" v-if="hasActiveFilters">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc
          </button>
        </div>
      </div>

      <!-- Pagination Toolbar -->
      <div class="table-pagination-footer" v-if="contacts.length > 0">
        <div class="pagination-info">
          Hiển thị <strong>{{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalRecords) }}</strong> 
          trong tổng số <strong>{{ totalRecords }}</strong> yêu cầu
        </div>
        <div class="pagination-controls" v-if="lastPage > 1">
          <button 
            class="btn-page" 
            :disabled="currentPage <= 1 || loading" 
            @click="changePage(currentPage - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          
          <button 
            v-for="p in visiblePages" 
            :key="p" 
            class="btn-page" 
            :class="{ 'active': p === currentPage }" 
            @click="changePage(p)"
          >
            {{ p }}
          </button>

          <button 
            class="btn-page" 
            :disabled="currentPage >= lastPage || loading" 
            @click="changePage(currentPage + 1)"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Loading Overlay -->
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Contact Detail & Processing Modal -->
    <div v-if="selectedContact" class="modal-overlay" @click.self="closeDetail">
      <div class="modal-content card detail-modal animate-in">
        <!-- Modal Header -->
        <div class="modal-header">
          <div class="header-icon-badge" :class="getStatusClass(selectedContact.trang_thai)">
            <i class="fa-solid fa-headset"></i>
          </div>
          <div class="header-title-wrap">
            <div class="header-title-top">
              <h3>Yêu cầu tư vấn #REQ-{{ selectedContact.id }}</h3>
              <span class="status-badge-inline" :class="getStatusClass(selectedContact.trang_thai)">
                <i class="fa-solid" :class="getStatusIcon(selectedContact.trang_thai)"></i>
                {{ getStatusText(selectedContact.trang_thai) }}
              </span>
            </div>
            <p class="modal-sub">
              <span>Gửi lúc: {{ formatDateTime(selectedContact.created_at) }}</span>
              <span class="sub-dot">•</span>
              <span v-if="selectedContact.assignee">
                Người phụ trách: <strong>{{ selectedContact.assignee.ho_ten }}</strong>
              </span>
              <span v-else class="text-warning">
                <i class="fa-regular fa-circle-question"></i> Chưa có chuyên viên phụ trách
              </span>
            </p>
          </div>
          <button class="btn-close-modal" @click="closeDetail" title="Đóng modal">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <!-- 1. Customer & Car of Interest Cards -->
          <div class="cards-duo-row">
            <!-- Customer Information Card -->
            <div class="info-card customer-card">
              <div class="card-header-mini">
                <i class="fa-solid fa-user-circle"></i>
                <span>Thông tin khách hàng</span>
                <span v-if="selectedContact.user_id || selectedContact.user" class="badge-account-verified">
                  <i class="fa-solid fa-shield-check"></i> Đã đăng ký
                </span>
                <span v-else class="badge-account-guest">
                  Khách vãng lai
                </span>
              </div>
              <div class="card-content-mini">
                <div class="customer-name-large">{{ selectedContact.ho_ten }}</div>
                <div class="customer-contact-line">
                  <i class="fa-regular fa-envelope"></i>
                  <a :href="`mailto:${selectedContact.email}`" class="link-contact">{{ selectedContact.email }}</a>
                </div>
                <div class="customer-contact-line" v-if="selectedContact.so_dien_thoai">
                  <i class="fa-solid fa-phone"></i>
                  <a :href="`tel:${selectedContact.so_dien_thoai}`" class="link-contact">{{ selectedContact.so_dien_thoai }}</a>
                </div>
              </div>
            </div>

            <!-- Car of Interest Card -->
            <div class="info-card car-card">
              <div class="card-header-mini">
                <i class="fa-solid fa-car"></i>
                <span>Xe quan tâm tư vấn</span>
                <span v-if="selectedContact.car" class="badge-car-linked">
                  <i class="fa-solid fa-link"></i> Đã liên kết
                </span>
                <span v-else class="badge-car-unlinked">
                  Chưa gắn xe
                </span>
              </div>
              <div class="card-content-mini">
                <div v-if="selectedContact.car" class="car-linked-box">
                  <img 
                    :src="selectedContact.car.image || '/assets/images/default-car.png'" 
                    :alt="selectedContact.car.name" 
                    class="modal-car-thumb"
                    @error="$event.target.src = '/assets/images/default-car.png'"
                  />
                  <div class="modal-car-info">
                    <span class="modal-car-name">{{ selectedContact.car.name }}</span>
                    <span class="modal-car-plate">{{ selectedContact.car.license_plate || 'Chưa gắn biển' }}</span>
                    <span class="modal-car-rate" v-if="selectedContact.car.price_per_day">
                      {{ formatCurrency(selectedContact.car.price_per_day) }} / ngày
                    </span>
                  </div>
                </div>
                <!-- Car Selector dropdown -->
                <div class="car-selector-box">
                  <label class="car-select-label">Gắn xe theo yêu cầu:</label>
                  <select v-model="editForm.car_id" class="form-select-sm">
                    <option :value="null">-- Không chọn / Chưa xác định xe --</option>
                    <option v-for="car in carsList" :key="car.id" :value="car.id">
                      {{ car.name }} {{ car.license_plate ? '(' + car.license_plate + ')' : '' }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Message Content Banner -->
          <div class="message-full-box">
            <div class="subject-banner">
              <span class="sub-label">Tiêu đề:</span>
              <span class="sub-title">{{ selectedContact.tieu_de }}</span>
            </div>
            <div class="message-content">
              {{ selectedContact.noi_dung }}
            </div>
          </div>

          <!-- 3. Workflow Control Tabs -->
          <div class="modal-tabs">
            <button 
              class="tab-btn" 
              :class="{ 'active': activeModalTab === 'assignment' }" 
              @click="activeModalTab = 'assignment'"
            >
              <i class="fa-solid fa-user-gear"></i>
              <span>Phân công & Trạng thái</span>
            </button>
            <button 
              class="tab-btn" 
              :class="{ 'active': activeModalTab === 'timeline' }" 
              @click="activeModalTab = 'timeline'"
            >
              <i class="fa-solid fa-timeline"></i>
              <span>Lịch sử xử lý & Ghi chú ({{ (selectedContact.activities || []).length }})</span>
            </button>
          </div>

          <!-- Tab Content 1: Assignment & Status -->
          <div v-show="activeModalTab === 'assignment'" class="tab-pane">
            <!-- Assignment Section -->
            <div class="action-section-box">
              <div class="section-title-wrap">
                <h4 class="section-title"><i class="fa-solid fa-user-tie"></i> Phân công / Chuyển giao chuyên viên</h4>
                <button 
                  v-if="!selectedContact.assigned_to" 
                  class="btn-claim-banner" 
                  @click="handleModalClaim"
                  :disabled="assigning"
                >
                  <i class="fa-solid fa-hand-holding-hand"></i>
                  <span>Nhận việc ngay</span>
                </button>
              </div>

              <div class="assign-form-grid">
                <div class="form-group">
                  <label class="form-label">Chuyên viên phụ trách:</label>
                  <select v-model="assignForm.assigned_to" class="form-select">
                    <option value="">-- Chưa phân công --</option>
                    <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                      {{ staff.ho_ten }} - {{ staff.role_label || 'Chuyên viên' }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Ghi chú bàn giao / chỉ đạo:</label>
                  <input 
                    type="text" 
                    v-model="assignForm.note" 
                    placeholder="VD: Khách cần xe gấp sáng mai, hỗ trợ gọi ngay..." 
                    class="form-input"
                    maxlength="500"
                  />
                </div>
              </div>

              <div class="section-actions-right">
                <button 
                  class="btn-secondary-action" 
                  @click="handleAssignStaff" 
                  :disabled="assigning"
                >
                  <i v-if="assigning" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-user-check"></i>
                  <span>{{ assigning ? 'Đang cập nhật...' : 'Lưu phân công' }}</span>
                </button>
              </div>
            </div>

            <!-- Status & Processing Section -->
            <div class="action-section-box">
              <h4 class="section-title"><i class="fa-solid fa-list-check"></i> Cập nhật tiến độ xử lý</h4>

              <div class="status-options">
                <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 0 }">
                  <input type="radio" :value="0" v-model="editForm.trang_thai" />
                  <div class="radio-content">
                    <span class="dot pending"></span>
                    <div>
                      <strong>Chờ xử lý (Mới)</strong>
                      <p>Tiếp nhận ban đầu, chưa liên hệ khách</p>
                    </div>
                  </div>
                </label>

                <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 1 }">
                  <input type="radio" :value="1" v-model="editForm.trang_thai" />
                  <div class="radio-content">
                    <span class="dot processing"></span>
                    <div>
                      <strong>Đang xử lý</strong>
                      <p>Đã liên hệ, đang tư vấn hoặc chuẩn bị xe</p>
                    </div>
                  </div>
                </label>

                <label class="status-radio-card" :class="{ 'selected': editForm.trang_thai === 2 }">
                  <input type="radio" :value="2" v-model="editForm.trang_thai" />
                  <div class="radio-content">
                    <span class="dot resolved"></span>
                    <div>
                      <strong>Đã giải quyết</strong>
                      <p>Khách đã chốt thuê xe hoặc hoàn tất tư vấn</p>
                    </div>
                  </div>
                </label>
              </div>

              <!-- Internal Note on Status Change -->
              <div class="internal-note-group">
                <label for="adminStatusNote">
                  <i class="fa-solid fa-lock"></i> Ghi chú nội bộ cho lần cập nhật này:
                </label>
                <textarea 
                  id="adminStatusNote" 
                  rows="2" 
                  v-model="editForm.ghi_chu_noi_bo" 
                  placeholder="Ghi nhận nội dung trao đổi, thỏa thuận với khách (sẽ tự động thêm vào lịch sử xử lý)..."
                  maxlength="1000"
                ></textarea>
                <small class="char-count">{{ (editForm.ghi_chu_noi_bo || '').length }}/1000 ký tự</small>
              </div>

              <!-- Handler & Updater Audit Info Bar -->
              <div class="meta-audit-bar">
                <div class="audit-item" v-if="selectedContact.updater">
                  <i class="fa-solid fa-clock-rotate-left"></i>
                  Cập nhật gần nhất: <strong>{{ selectedContact.updater.ho_ten }}</strong> 
                  <span>({{ formatDateTime(selectedContact.updated_at) }})</span>
                </div>
                <div class="audit-item" v-if="selectedContact.completer && Number(selectedContact.trang_thai) === 2">
                  <i class="fa-solid fa-circle-check text-success"></i>
                  Hoàn tất bởi: <strong>{{ selectedContact.completer.ho_ten }}</strong> 
                  <span v-if="selectedContact.completed_at">({{ formatDateTime(selectedContact.completed_at) }})</span>
                </div>
              </div>

              <div class="section-actions-right">
                <button 
                  class="btn-primary" 
                  @click="handleSaveStatus" 
                  :disabled="saving"
                >
                  <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-floppy-disk"></i>
                  <span>{{ saving ? 'Đang lưu...' : 'Lưu cập nhật trạng thái' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Tab Content 2: History & Activities Timeline -->
          <div v-show="activeModalTab === 'timeline'" class="tab-pane">
            <!-- Add New Note Box -->
            <div class="add-note-box">
              <label class="form-label"><i class="fa-solid fa-pen"></i> Thêm ghi chú mới vào dòng thời gian:</label>
              <div class="add-note-input-row">
                <textarea 
                  v-model="newNoteContent" 
                  rows="2" 
                  placeholder="Nhập ghi chú diễn biến trao đổi, cuộc gọi chăm sóc, kết quả báo giá..."
                  class="form-textarea-note"
                  maxlength="1000"
                ></textarea>
                <button 
                  class="btn-add-note" 
                  @click="handleAddNote" 
                  :disabled="addingNote || !newNoteContent.trim()"
                >
                  <i v-if="addingNote" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-paper-plane"></i>
                  <span>{{ addingNote ? 'Đang gửi...' : 'Gửi ghi chú' }}</span>
                </button>
              </div>
            </div>

            <!-- Activities Timeline Stream -->
            <div class="timeline-container">
              <div 
                v-if="selectedContact.activities && selectedContact.activities.length > 0" 
                class="timeline-stream"
              >
                <div 
                  v-for="act in selectedContact.activities" 
                  :key="act.id" 
                  class="timeline-item"
                >
                  <div class="timeline-marker" :class="getActivityMarkerClass(act.type)">
                    <i class="fa-solid" :class="getActivityIcon(act.type)"></i>
                  </div>
                  <div class="timeline-card">
                    <div class="timeline-header">
                      <div class="timeline-author">
                        <span class="author-name">{{ act.admin_name || act.admin?.ho_ten || 'Hệ thống' }}</span>
                        <span class="activity-type-badge" :class="getActivityBadgeClass(act.type)">
                          {{ getActivityTypeText(act.type) }}
                        </span>
                      </div>
                      <span class="timeline-time">{{ formatDateTime(act.created_at) }}</span>
                    </div>
                    <div class="timeline-content">
                      {{ act.content }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty Timeline -->
              <div v-else class="empty-timeline-state">
                <i class="fa-regular fa-clock"></i>
                <p>Chưa có ghi chú hoặc diễn biến nào được ghi nhận cho yêu cầu này.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
          <div class="footer-left">
            <button class="btn-schedule-appt-modal" @click="createAppointmentFromContact(selectedContact)">
              <i class="fa-solid fa-calendar-plus"></i>
              <span>Đặt lịch hẹn tư vấn / xem xe</span>
            </button>
          </div>
          <div class="footer-right">
            <button class="btn-outline" @click="closeDetail" :disabled="saving || assigning">Đóng</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { getStoredUser } from '../../../core/authStorage';
import { createToaster } from "@meforma/vue-toaster";

const router = useRouter();
const toaster = createToaster({ position: "top-right" });

// State
const contacts = ref([]);
const staffList = ref([]);
const carsList = ref([]);
const loading = ref(true);
const saving = ref(false);
const assigning = ref(false);
const addingNote = ref(false);

// Current user
const currentUser = ref(getStoredUser());
const currentAdminId = computed(() => currentUser.value?.id || null);

// Filters & Pagination
const searchQuery = ref('');
const filterStatus = ref('');
const filterAssignee = ref('');
const filterFromDate = ref('');
const filterToDate = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const totalRecords = ref(0);

// KPI counts
const counts = ref({
  all: 0,
  pending: 0,
  in_progress: 0,
  resolved: 0,
  unassigned: 0,
  my_assigned: 0,
});

// Modal Selection
const selectedContact = ref(null);
const activeModalTab = ref('assignment'); // 'assignment' | 'timeline'
const newNoteContent = ref('');

const editForm = ref({
  trang_thai: 0,
  ghi_chu_noi_bo: '',
  car_id: null,
  expected_updated_at: null,
});

const assignForm = ref({
  assigned_to: '',
  note: '',
});

// Computed Filters Check
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' ||
    filterStatus.value !== '' ||
    filterAssignee.value !== '' ||
    filterFromDate.value !== '' ||
    filterToDate.value !== '';
});

const isMyAssignedFilterActive = computed(() => {
  return currentAdminId.value && String(filterAssignee.value) === String(currentAdminId.value);
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, currentPage.value - 2);
  const end = Math.min(lastPage.value, currentPage.value + 2);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Fetch contacts with filters & pagination
const fetchContacts = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
    };

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }
    if (filterStatus.value !== '') {
      params.trang_thai = filterStatus.value;
    }
    if (filterAssignee.value !== '') {
      params.assigned_to = filterAssignee.value;
    }
    if (filterFromDate.value) {
      params.from_date = filterFromDate.value;
    }
    if (filterToDate.value) {
      params.to_date = filterToDate.value;
    }

    const res = await baseRequest.get('admin/contacts', { params });
    if (res.data && res.data.status) {
      const respData = res.data.data;
      if (respData && Array.isArray(respData.data)) {
        contacts.value = respData.data;
        totalRecords.value = respData.total || respData.data.length;
        lastPage.value = respData.last_page || 1;
        currentPage.value = respData.current_page || 1;
      } else if (Array.isArray(respData)) {
        contacts.value = respData;
        totalRecords.value = respData.length;
        lastPage.value = 1;
        currentPage.value = 1;
      }

      if (res.data.counts) {
        counts.value = res.data.counts;
      }
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách liên hệ!");
  } finally {
    loading.value = false;
  }
};

// Fetch active staff list
const fetchStaffList = async () => {
  try {
    const res = await baseRequest.get('admin/contacts/staff-list');
    if (res.data && res.data.status) {
      staffList.value = res.data.data || [];
    }
  } catch (err) {
    console.error("Lỗi khi tải danh sách nhân viên:", err);
  }
};

// Fetch cars list for linking
const fetchCarsList = async () => {
  try {
    const res = await baseRequest.get('admin/cars/data?per_page=100');
    if (res.data && res.data.status) {
      const data = res.data.data;
      carsList.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    }
  } catch (err) {
    console.error("Lỗi khi tải danh sách xe:", err);
  }
};

// Filter Handlers
const applyFilters = () => {
  currentPage.value = 1;
  fetchContacts();
};

const changePage = (page) => {
  if (page < 1 || page > lastPage.value) return;
  currentPage.value = page;
  fetchContacts();
};

const resetKpiFilter = () => {
  filterStatus.value = '';
  filterAssignee.value = '';
  currentPage.value = 1;
  fetchContacts();
};

const setKpiStatus = (st) => {
  filterStatus.value = st;
  filterAssignee.value = '';
  currentPage.value = 1;
  fetchContacts();
};

const setKpiAssignee = (assigneeVal) => {
  filterAssignee.value = assigneeVal;
  currentPage.value = 1;
  fetchContacts();
};

const setKpiMyAssigned = () => {
  if (currentAdminId.value) {
    filterAssignee.value = currentAdminId.value;
  } else {
    filterAssignee.value = 'my_assigned';
  }
  currentPage.value = 1;
  fetchContacts();
};

const resetAllFilters = () => {
  searchQuery.value = '';
  filterStatus.value = '';
  filterAssignee.value = '';
  filterFromDate.value = '';
  filterToDate.value = '';
  currentPage.value = 1;
  fetchContacts();
};

// Modal Operations
const openDetail = async (contact) => {
  selectedContact.value = { ...contact };
  activeModalTab.value = 'assignment';
  newNoteContent.value = '';

  editForm.value = {
    trang_thai: Number(contact.trang_thai),
    ghi_chu_noi_bo: '',
    car_id: contact.car_id || null,
    expected_updated_at: contact.updated_at,
  };

  assignForm.value = {
    assigned_to: contact.assigned_to || '',
    note: '',
  };

  // Fetch full details with activities
  try {
    const res = await baseRequest.get(`admin/contacts/${contact.id}`);
    if (res.data && res.data.status && res.data.data) {
      selectedContact.value = res.data.data;
      editForm.value.expected_updated_at = res.data.data.updated_at;
      assignForm.value.assigned_to = res.data.data.assigned_to || '';
      editForm.value.car_id = res.data.data.car_id || null;
    }
  } catch (err) {
    console.error("Lỗi khi tải chi tiết yêu cầu:", err);
  }
};

const closeDetail = () => {
  selectedContact.value = null;
};

// Navigate to Appointments Calendar with contact prefilled
const createAppointmentFromContact = (contact) => {
  if (!contact) return;
  router.push({
    path: '/admin/appointments',
    query: {
      create: '1',
      contact_id: contact.id,
      customer_name: contact.ho_ten || '',
      customer_phone: contact.so_dien_thoai || '',
      customer_email: contact.email || '',
      car_id: contact.car_id || (contact.car ? contact.car.id : ''),
      notes: `Hẹn tư vấn từ yêu cầu #${contact.id}: ${contact.tieu_de || ''}`
    }
  });
};

// Quick Claim from Table Row
const handleQuickClaim = async (contact) => {
  try {
    const res = await baseRequest.post(`admin/contacts/${contact.id}/assign`, {
      claim_action: true,
      expected_updated_at: contact.updated_at,
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Bạn đã nhận xử lý yêu cầu này!");
      fetchContacts();
    }
  } catch (err) {
    if (err.response?.status === 409) {
      toaster.warning(err.response.data.message || "Yêu cầu đã được nhân viên khác nhận xử lý trước!");
      fetchContacts();
    } else {
      toaster.error(err.response?.data?.message || "Không thể nhận yêu cầu!");
    }
  }
};

// Claim from inside modal
const handleModalClaim = async () => {
  if (!selectedContact.value) return;
  assigning.value = true;
  try {
    const res = await baseRequest.post(`admin/contacts/${selectedContact.value.id}/assign`, {
      claim_action: true,
      expected_updated_at: selectedContact.value.updated_at,
    });

    if (res.data && res.data.status) {
      toaster.success("Bạn đã nhận xử lý yêu cầu tư vấn này!");
      selectedContact.value = res.data.data;
      assignForm.value.assigned_to = res.data.data.assigned_to || '';
      editForm.value.expected_updated_at = res.data.data.updated_at;
      fetchContacts();
    }
  } catch (err) {
    if (err.response?.status === 409) {
      toaster.warning(err.response.data.message || "Yêu cầu vừa được nhân viên khác nhận!");
      refreshModalDetail();
    } else {
      toaster.error(err.response?.data?.message || "Có lỗi xảy ra khi nhận việc!");
    }
  } finally {
    assigning.value = false;
  }
};

// Assign / Reassign Staff
const handleAssignStaff = async () => {
  if (!selectedContact.value) return;
  assigning.value = true;
  try {
    const res = await baseRequest.post(`admin/contacts/${selectedContact.value.id}/assign`, {
      assigned_to: assignForm.value.assigned_to || null,
      note: assignForm.value.note || undefined,
      expected_updated_at: selectedContact.value.updated_at,
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Phân công chuyên viên thành công!");
      selectedContact.value = res.data.data;
      assignForm.value.note = '';
      editForm.value.expected_updated_at = res.data.data.updated_at;
      fetchContacts();
    }
  } catch (err) {
    if (err.response?.status === 409) {
      toaster.warning(err.response.data.message || "Yêu cầu vừa được người khác cập nhật!");
      refreshModalDetail();
    } else {
      toaster.error(err.response?.data?.message || "Không thể phân công chuyên viên!");
    }
  } finally {
    assigning.value = false;
  }
};

// Save Status & Note
const handleSaveStatus = async () => {
  if (!selectedContact.value) return;
  saving.value = true;
  try {
    const res = await baseRequest.put(`admin/contacts/${selectedContact.value.id}/status`, {
      trang_thai: editForm.value.trang_thai,
      ghi_chu_noi_bo: editForm.value.ghi_chu_noi_bo || undefined,
      car_id: editForm.value.car_id || null,
      expected_updated_at: selectedContact.value.updated_at,
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Cập nhật tiến độ xử lý thành công!");
      selectedContact.value = res.data.data;
      editForm.value.ghi_chu_noi_bo = '';
      editForm.value.expected_updated_at = res.data.data.updated_at;
      fetchContacts();
    }
  } catch (err) {
    if (err.response?.status === 409) {
      toaster.warning(err.response.data.message || "Dữ liệu vừa được người khác cập nhật. Hệ thống đã làm mới!");
      refreshModalDetail();
    } else {
      toaster.error(err.response?.data?.message || "Có lỗi khi lưu tiến độ!");
    }
  } finally {
    saving.value = false;
  }
};

// Add Note to Timeline
const handleAddNote = async () => {
  if (!selectedContact.value || !newNoteContent.value.trim()) return;
  addingNote.value = true;
  try {
    const res = await baseRequest.post(`admin/contacts/${selectedContact.value.id}/notes`, {
      content: newNoteContent.value.trim(),
    });

    if (res.data && res.data.status) {
      toaster.success("Đã ghi nhận ghi chú nội bộ!");
      newNoteContent.value = '';
      if (res.data.data) {
        if (!selectedContact.value.activities) {
          selectedContact.value.activities = [];
        }
        selectedContact.value.activities.unshift(res.data.data);
      }
      fetchContacts();
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể thêm ghi chú!");
  } finally {
    addingNote.value = false;
  }
};

// Refresh Modal Contact
const refreshModalDetail = async () => {
  if (!selectedContact.value) return;
  try {
    const res = await baseRequest.get(`admin/contacts/${selectedContact.value.id}`);
    if (res.data && res.data.status && res.data.data) {
      selectedContact.value = res.data.data;
      editForm.value.expected_updated_at = res.data.data.updated_at;
      assignForm.value.assigned_to = res.data.data.assigned_to || '';
      editForm.value.car_id = res.data.data.car_id || null;
      fetchContacts();
    }
  } catch (err) {
    console.error("Lỗi làm mới modal:", err);
  }
};

// Helper UI Formatters
const getStatusText = (st) => {
  switch (Number(st)) {
    case 0: return 'Chờ xử lý';
    case 1: return 'Đang xử lý';
    case 2: return 'Đã giải quyết';
    default: return 'Không xác định';
  }
};

const getStatusClass = (st) => {
  switch (Number(st)) {
    case 0: return 'badge-pending';
    case 1: return 'badge-processing';
    case 2: return 'badge-resolved';
    default: return 'badge-muted';
  }
};

const getStatusIcon = (st) => {
  switch (Number(st)) {
    case 0: return 'fa-clock';
    case 1: return 'fa-spinner';
    case 2: return 'fa-check';
    default: return 'fa-question';
  }
};

const getRoleLabel = (role) => {
  switch (role) {
    case 'owner': return 'Chủ hệ thống';
    case 'dispatcher': return 'Điều phối viên';
    case 'staff': return 'Chuyên viên';
    default: return 'Nhân viên';
  }
};

const getActivityTypeText = (type) => {
  switch (type) {
    case 'note': return 'Ghi chú';
    case 'status_change': return 'Đổi trạng thái';
    case 'assigned': return 'Phân công';
    case 'reassigned': return 'Chuyển giao';
    default: return 'Hoạt động';
  }
};

const getActivityBadgeClass = (type) => {
  switch (type) {
    case 'note': return 'act-badge-note';
    case 'status_change': return 'act-badge-status';
    case 'assigned': return 'act-badge-assign';
    case 'reassigned': return 'act-badge-reassign';
    default: return 'act-badge-default';
  }
};

const getActivityIcon = (type) => {
  switch (type) {
    case 'note': return 'fa-note-sticky';
    case 'status_change': return 'fa-arrows-rotate';
    case 'assigned': return 'fa-user-plus';
    case 'reassigned': return 'fa-arrow-right-arrow-left';
    default: return 'fa-circle-dot';
  }
};

const getActivityMarkerClass = (type) => {
  switch (type) {
    case 'note': return 'marker-note';
    case 'status_change': return 'marker-status';
    case 'assigned': return 'marker-assign';
    case 'reassigned': return 'marker-reassign';
    default: return 'marker-default';
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

const formatTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

const formatDateTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatCurrency = (amount) => {
  if (!amount) return '0 đ';
  return Number(amount).toLocaleString('vi-VN') + ' đ';
};

onMounted(() => {
  fetchContacts();
  fetchStaffList();
  fetchCarsList();
});
</script>

<style scoped>
.admin-page {
  padding-bottom: 40px;
}

/* Header */
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
  background: var(--grad-active, linear-gradient(135deg, #0066ff 0%, #00d2ff 100%));
  color: white;
  border-radius: var(--radius-md, 12px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 12px rgba(0, 102, 255, 0.25);
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-main, #0f172a);
  margin: 0;
}

.page-title span {
  color: var(--color-primary, #0066ff);
}

.page-subtitle {
  font-size: 0.9rem;
  color: var(--color-text-muted, #64748b);
  margin: 4px 0 0;
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-sm, 8px);
  border: 1px solid var(--color-border, #e2e8f0);
  background: white;
  color: var(--color-text-main, #0f172a);
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: var(--color-primary, #0066ff);
  color: var(--color-primary, #0066ff);
}

/* KPI Summary Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 14px;
  margin-bottom: 24px;
}

.kpi-card {
  background: #ffffff;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 16px);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(0,0,0,0.06);
  border-color: #bfdbfe;
}

.kpi-card.active {
  border-color: var(--color-primary, #0066ff);
  background: rgba(0, 102, 255, 0.03);
  box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.15);
}

.kpi-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}

.kpi-icon-box.total { background: #eff6ff; color: #2563eb; }
.kpi-icon-box.pending { background: #fffbeb; color: #d97706; }
.kpi-icon-box.processing { background: #e0f2fe; color: #0284c7; }
.kpi-icon-box.resolved { background: #ecfdf5; color: #059669; }
.kpi-icon-box.unassigned { background: #fef2f2; color: #dc2626; }
.kpi-icon-box.my-tasks { background: #f5f3ff; color: #7c3aed; }

.kpi-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.kpi-label {
  font-size: 0.74rem;
  font-weight: 600;
  color: var(--color-text-muted, #64748b);
  white-space: nowrap;
}

.kpi-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-text-main, #0f172a);
  line-height: 1.2;
}

/* Content Card & Tools */
.content-card {
  background: white;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  position: relative;
  overflow: hidden;
}

.table-tools {
  padding: 18px 24px;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
  background: #fafbfc;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.filter-row-top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 520px;
}

.search-input i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.9rem;
}

.search-input input {
  width: 100%;
  padding: 9px 34px 9px 38px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
  background: white;
}

.search-input input:focus {
  border-color: var(--color-primary, #0066ff);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
}

.btn-clear-search {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
}

.btn-filter-search {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-filter-search:hover {
  background: #0052cc;
}

.filter-row-bottom {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-select {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.86rem;
  outline: none;
  background: white;
  cursor: pointer;
}

.form-select:focus {
  border-color: var(--color-primary, #0066ff);
}

.date-inputs {
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-input-date {
  padding: 7px 10px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.84rem;
  outline: none;
  background: white;
}

.date-sep {
  color: #94a3b8;
  font-weight: bold;
}

.btn-reset-filters {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-left: auto;
}

.btn-reset-filters:hover {
  background: #f1f5f9;
  color: #0f172a;
}

/* Premium Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.premium-table th {
  padding: 13px 18px;
  font-size: 0.76rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.unread-row {
  background: rgba(245, 158, 11, 0.02);
}

.unassigned-row {
  border-left: 3px solid #f59e0b;
}

.id-tag {
  display: inline-block;
  font-family: monospace;
  font-weight: 700;
  font-size: 0.8rem;
  color: #0f172a;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}

/* User Cell */
.user-cell .user-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 3px;
}

.user-cell .name-bold {
  font-weight: 700;
  font-size: 0.88rem;
  color: #0f172a;
}

.badge-account-tiny {
  font-size: 0.68rem;
  font-weight: 700;
  color: #059669;
  background: #ecfdf5;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.badge-guest-tiny {
  font-size: 0.68rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.contact-sub {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.76rem;
  color: #64748b;
}

.contact-sub span {
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Car of Interest Cell */
.car-interest-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.car-thumb {
  width: 44px;
  height: 32px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.car-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.car-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 140px;
}

.car-plate {
  font-size: 0.72rem;
  font-family: monospace;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  padding: 1px 4px;
  border-radius: 4px;
  width: fit-content;
}

.no-car-linked {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  color: #94a3b8;
}

/* Content Cell */
.content-cell {
  max-width: 300px;
}

.subject-line {
  font-weight: 700;
  font-size: 0.86rem;
  color: #0f172a;
  margin-bottom: 2px;
}

.message-preview {
  font-size: 0.78rem;
  color: #64748b;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.latest-note-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 4px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 0.72rem;
  color: #0369a1;
  max-width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Time Cell */
.time-cell {
  display: flex;
  flex-direction: column;
  font-size: 0.82rem;
  color: #334155;
}

.time-cell small {
  color: #94a3b8;
  font-size: 0.72rem;
}

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.badge-pending {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}

.badge-processing {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.badge-resolved {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

/* Assignee Cell */
.assignee-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.assignee-box {
  display: flex;
  align-items: center;
  gap: 8px;
}

.staff-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.staff-meta {
  display: flex;
  flex-direction: column;
}

.staff-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.staff-role {
  font-size: 0.7rem;
  color: #64748b;
}

.unassigned-action-box {
  display: flex;
  align-items: center;
  gap: 8px;
}

.badge-unassigned {
  font-size: 0.74rem;
  font-weight: 600;
  color: #dc2626;
  background: #fef2f2;
  padding: 3px 6px;
  border-radius: 6px;
  border: 1px solid #fecaca;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-quick-claim {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-quick-claim:hover {
  background: #dbeafe;
}

.updater-sub {
  font-size: 0.7rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 4px;
}

.text-success { color: #10b981; }

.action-btn-group {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.btn-action-view {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: var(--color-primary, #0066ff);
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action-view:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.btn-action-appt {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #c7d2fe;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action-appt:hover {
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
  transform: translateY(-1px);
}

/* Pagination Footer */
.table-pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 24px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.pagination-info {
  font-size: 0.82rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-page {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #334155;
  font-size: 0.82rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.btn-page.active {
  background: var(--color-primary, #0066ff);
  color: white;
  border-color: var(--color-primary, #0066ff);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Overlay & Card */
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

.detail-modal {
  width: 100%;
  max-width: 820px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.header-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.header-title-wrap {
  flex: 1;
}

.header-title-top {
  display: flex;
  align-items: center;
  gap: 10px;
}

.header-title-top h3 {
  font-size: 1.2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.status-badge-inline {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.modal-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 4px 0 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sub-dot { color: #cbd5e1; }

.btn-close-modal {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #e2e8f0;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.btn-close-modal:hover {
  background: #cbd5e1;
  color: #0f172a;
}

.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* Customer & Car Cards Row */
.cards-duo-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.info-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  background: #f8fafc;
}

.card-header-mini {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 10px;
}

.badge-account-verified {
  margin-left: auto;
  font-size: 0.68rem;
  color: #059669;
  background: #ecfdf5;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid #a7f3d0;
}

.badge-account-guest {
  margin-left: auto;
  font-size: 0.68rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.badge-car-linked {
  margin-left: auto;
  font-size: 0.68rem;
  color: #2563eb;
  background: #eff6ff;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid #bfdbfe;
}

.badge-car-unlinked {
  margin-left: auto;
  font-size: 0.68rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.customer-name-large {
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}

.customer-contact-line {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.82rem;
  color: #475569;
  margin-bottom: 3px;
}

.link-contact {
  color: var(--color-primary, #0066ff);
  text-decoration: none;
  font-weight: 600;
}

.link-contact:hover {
  text-decoration: underline;
}

.car-linked-box {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
  background: white;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.modal-car-thumb {
  width: 52px;
  height: 38px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.modal-car-info {
  display: flex;
  flex-direction: column;
}

.modal-car-name {
  font-size: 0.85rem;
  font-weight: 800;
  color: #0f172a;
}

.modal-car-plate {
  font-size: 0.74rem;
  font-family: monospace;
  font-weight: 700;
  color: #2563eb;
}

.modal-car-rate {
  font-size: 0.72rem;
  color: #059669;
  font-weight: 700;
}

.car-selector-box {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.car-select-label {
  font-size: 0.74rem;
  font-weight: 600;
  color: #64748b;
}

.form-select-sm {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  font-size: 0.82rem;
  background: white;
  outline: none;
}

/* Full Message Box */
.message-full-box {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.subject-banner {
  background: #f1f5f9;
  padding: 8px 16px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  gap: 8px;
  align-items: center;
}

.sub-label {
  font-size: 0.76rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.sub-title {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f172a;
}

.message-content {
  padding: 14px 16px;
  font-size: 0.88rem;
  line-height: 1.6;
  color: #334155;
  white-space: pre-wrap;
  background: white;
}

/* Tabs */
.modal-tabs {
  display: flex;
  border-bottom: 2px solid #e2e8f0;
  gap: 8px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: var(--color-primary, #0066ff);
}

.tab-btn.active {
  color: var(--color-primary, #0066ff);
  border-bottom-color: var(--color-primary, #0066ff);
}

.tab-pane {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Action Section Box */
.action-section-box {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  background: #f8fafc;
}

.section-title-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title-wrap .section-title {
  margin-bottom: 0;
}

.btn-claim-banner {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-claim-banner:hover:not(:disabled) {
  background: #dbeafe;
}

.assign-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-input {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.86rem;
  outline: none;
  background: white;
}

.form-input:focus {
  border-color: var(--color-primary, #0066ff);
}

.section-actions-right {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-secondary-action {
  padding: 7px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #1e293b;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-secondary-action:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}

/* Status Radio Cards */
.status-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 14px;
}

.status-radio-card {
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
  background: white;
}

.status-radio-card input {
  position: absolute;
  opacity: 0;
}

.status-radio-card.selected {
  border-color: var(--color-primary, #0066ff);
  background: #eff6ff;
}

.radio-content {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.radio-content strong {
  display: block;
  font-size: 0.82rem;
  color: #0f172a;
  margin-bottom: 2px;
}

.radio-content p {
  font-size: 0.7rem;
  color: #64748b;
  margin: 0;
  line-height: 1.3;
}

.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  margin-top: 4px;
  flex-shrink: 0;
}

.dot.pending { background: #f59e0b; }
.dot.processing { background: #0284c7; }
.dot.resolved { background: #10b981; }

.internal-note-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 12px;
}

.internal-note-group label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
}

.internal-note-group textarea {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.86rem;
  outline: none;
  font-family: inherit;
  resize: vertical;
  background: white;
}

.internal-note-group textarea:focus {
  border-color: var(--color-primary, #0066ff);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
}

.char-count {
  font-size: 0.7rem;
  color: #94a3b8;
  text-align: right;
}

.meta-audit-bar {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px 12px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  margin-bottom: 12px;
  font-size: 0.76rem;
  color: #64748b;
}

.audit-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Timeline Tab & Stream */
.add-note-box {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
  background: #f8fafc;
}

.add-note-input-row {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}

.form-textarea-note {
  flex: 1;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.86rem;
  outline: none;
  font-family: inherit;
  resize: none;
  background: white;
}

.form-textarea-note:focus {
  border-color: var(--color-primary, #0066ff);
}

.btn-add-note {
  padding: 0 18px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-add-note:hover:not(:disabled) {
  background: #0052cc;
}

.btn-add-note:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.timeline-container {
  max-height: 380px;
  overflow-y: auto;
  padding-right: 4px;
}

.timeline-stream {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-left: 20px;
}

.timeline-stream::before {
  content: '';
  position: absolute;
  left: 9px;
  top: 10px;
  bottom: 10px;
  width: 2px;
  background: #e2e8f0;
}

.timeline-item {
  position: relative;
  display: flex;
  gap: 12px;
}

.timeline-marker {
  position: absolute;
  left: -20px;
  top: 6px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  color: white;
  z-index: 1;
}

.marker-note { background: #0284c7; }
.marker-status { background: #8b5cf6; }
.marker-assign { background: #f59e0b; }
.marker-reassign { background: #14b8a6; }
.marker-default { background: #64748b; }

.timeline-card {
  flex: 1;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 14px;
  margin-left: 10px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.timeline-author {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
}

.activity-type-badge {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
}

.act-badge-note { background: #e0f2fe; color: #0369a1; }
.act-badge-status { background: #f3e8ff; color: #7e22ce; }
.act-badge-assign { background: #fef3c7; color: #b45309; }
.act-badge-reassign { background: #ccfbf1; color: #0f766e; }
.act-badge-default { background: #f1f5f9; color: #475569; }

.timeline-time {
  font-size: 0.72rem;
  color: #94a3b8;
}

.timeline-content {
  font-size: 0.82rem;
  color: #334155;
  line-height: 1.5;
  white-space: pre-wrap;
}

.empty-timeline-state {
  text-align: center;
  padding: 40px 20px;
  color: #94a3b8;
}

.empty-timeline-state i {
  font-size: 2rem;
  margin-bottom: 8px;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 24px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.footer-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-schedule-appt-modal {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
  transition: all 0.2s;
}

.btn-schedule-appt-modal:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
}

.btn-outline {
  padding: 8px 20px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
}

.btn-primary {
  padding: 8px 20px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-weight: 700;
  font-size: 0.86rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline-sm {
  margin-top: 12px;
  padding: 6px 14px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-size: 0.8rem;
  cursor: pointer;
}

/* Empty & Loading */
.empty-state-box {
  padding: 60px 20px;
  text-align: center;
  color: #94a3b8;
}

.empty-state-box i {
  font-size: 2.8rem;
  color: #cbd5e1;
  margin-bottom: 12px;
}

.empty-state-box h4 {
  font-size: 1.1rem;
  color: #475569;
  margin-bottom: 6px;
}

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary, #0066ff);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  .cards-duo-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .assign-form-grid {
    grid-template-columns: 1fr;
  }
  .status-options {
    grid-template-columns: 1fr;
  }
}
</style>
