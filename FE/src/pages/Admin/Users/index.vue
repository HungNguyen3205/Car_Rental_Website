<template>
  <div class="admin-page">
    <!-- Page Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-id-card-clip"></i></div>
        <div>
          <h1 class="page-title">Hồ Sơ & Quản Lý <span>Khách Hàng</span></h1>
          <p class="page-subtitle">Quản lý hồ sơ chuyên sâu, phân tích nhu cầu thuê xe, lịch sử đơn hàng và nhật ký chăm sóc khách hàng.</p>
        </div>
      </div>
      <div class="header-actions">
        <router-link to="/admin/permissions" class="btn-link-admin" title="Quản lý tài khoản và phân quyền quản trị viên">
          <i class="fa-solid fa-user-shield"></i>
          <span>Tài khoản Admin</span>
        </router-link>
        <button class="btn-refresh" @click="fetchCustomers" :disabled="loading" title="Tải lại danh sách">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
      </div>
    </header>

    <!-- Entity Navigation Tabs: Khách hàng vs Nhân viên -->
    <div class="entity-nav-tabs">
      <button class="entity-tab active">
        <i class="fa-solid fa-users"></i>
        <span>Hồ sơ Khách hàng (#CUS)</span>
        <span class="tab-badge">{{ counts.total }}</span>
      </button>
      <router-link to="/admin/permissions" class="entity-tab link-tab">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Tài khoản Nhân viên / Admin (#ADM)</span>
        <i class="fa-solid fa-arrow-up-right-from-square tab-ext-icon"></i>
      </router-link>
    </div>

    <!-- KPI Summary Cards (5 Cards) -->
    <div class="kpi-grid">
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '' && filterDemand === '' }" 
        @click="resetKpiFilter"
      >
        <div class="kpi-icon-box total">
          <i class="fa-solid fa-address-book"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Tổng khách hàng</span>
          <span class="kpi-value">{{ counts.total }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '1' }" 
        @click="setKpiStatus('1')"
      >
        <div class="kpi-icon-box active">
          <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đang hoạt động</span>
          <span class="kpi-value">{{ counts.active }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === '0' }" 
        @click="setKpiStatus('0')"
      >
        <div class="kpi-icon-box locked">
          <i class="fa-solid fa-user-lock"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đã khóa</span>
          <span class="kpi-value">{{ counts.locked }}</span>
        </div>
      </div>

      <div class="kpi-card static">
        <div class="kpi-icon-box bookings">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Đã từng thuê xe</span>
          <span class="kpi-value">{{ counts.with_bookings }}</span>
        </div>
      </div>

      <div class="kpi-card static">
        <div class="kpi-icon-box inquiries">
          <i class="fa-solid fa-headset"></i>
        </div>
        <div class="kpi-info">
          <span class="kpi-label">Có gửi yêu cầu tư vấn</span>
          <span class="kpi-value">{{ counts.with_contacts }}</span>
        </div>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card card">
      <!-- Toolbar Filters -->
      <div class="table-tools">
        <div class="filter-row-top">
          <!-- Keyword Search -->
          <div class="search-input">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              placeholder="Tìm theo tên, email, số điện thoại, địa chỉ..." 
              v-model="searchQuery" 
              @keyup.enter="applyFilters"
            />
            <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''; applyFilters()">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <button class="btn-filter-search" @click="applyFilters" title="Tìm kiếm">
            <i class="fa-solid fa-filter"></i>
            <span>Lọc</span>
          </button>
        </div>

        <div class="filter-row-bottom">
          <!-- Status Filter -->
          <div class="filter-item">
            <label class="filter-label"><i class="fa-solid fa-toggle-on"></i> Trạng thái:</label>
            <select v-model="filterStatus" class="form-select" @change="applyFilters">
              <option value="">Tất cả trạng thái</option>
              <option value="1">Đang hoạt động</option>
              <option value="0">Đã khóa</option>
            </select>
          </div>

          <!-- Demand Filter -->
          <div class="filter-item">
            <label class="filter-label"><i class="fa-solid fa-tags"></i> Nhu cầu thuê:</label>
            <select v-model="filterDemand" class="form-select" @change="applyFilters">
              <option value="">Tất cả nhu cầu</option>
              <option value="self_drive">Tự lái</option>
              <option value="with_driver">Có tài xế</option>
              <option value="monthly">Thuê dài hạn / Theo tháng</option>
              <option value="wedding">Xe cưới / Sự kiện</option>
              <option value="travel">Du lịch / Gia đình</option>
            </select>
          </div>

          <!-- Interested Car Filter -->
          <div class="filter-item">
            <label class="filter-label"><i class="fa-solid fa-car"></i> Xe quan tâm:</label>
            <select v-model="filterCarId" class="form-select" @change="applyFilters">
              <option value="">Tất cả mẫu xe</option>
              <option v-for="car in carsList" :key="car.id" :value="car.id">
                {{ car.name }} {{ car.license_plate ? '(' + car.license_plate + ')' : '' }}
              </option>
            </select>
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

      <!-- Customer List Table -->
      <div class="table-responsive">
        <table class="premium-table" v-if="customers.length > 0">
          <thead>
            <tr>
              <th width="110">Mã khách</th>
              <th width="240">Khách hàng</th>
              <th width="240">Nhu cầu & Ngân sách</th>
              <th width="150" class="text-center">Lịch sử tương tác</th>
              <th width="130">Ngày đăng ký</th>
              <th width="130">Trạng thái</th>
              <th class="text-center" width="160">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in customers" :key="u.id" :class="{ 'locked-row': !u.is_active }">
              <!-- Customer ID -->
              <td>
                <span class="id-tag-customer">#CUS-{{ u.id }}</span>
              </td>

              <!-- Customer Contact -->
              <td>
                <div class="user-profile-cell">
                  <div class="avatar-circle">
                    {{ u.ho_ten ? u.ho_ten.charAt(0).toUpperCase() : 'C' }}
                  </div>
                  <div class="info">
                    <span class="name">{{ u.ho_ten }}</span>
                    <div class="contact-sub">
                      <span><i class="fa-regular fa-envelope"></i> {{ u.email }}</span>
                      <span v-if="u.so_dien_thoai"><i class="fa-solid fa-phone"></i> {{ u.so_dien_thoai }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Demand & Preference -->
              <td>
                <div class="preference-cell">
                  <div class="demand-badge-wrap">
                    <span v-if="u.nhu_cau_thue" class="badge-demand" :class="u.nhu_cau_thue">
                      {{ getDemandLabel(u.nhu_cau_thue) }}
                    </span>
                    <span v-else class="text-muted-dash">Chưa khảo sát</span>

                    <span v-if="u.gplx_hang" class="badge-gplx" title="Hạng giấy phép lái xe">
                      GPLX: {{ u.gplx_hang }}
                    </span>
                  </div>

                  <!-- Budget -->
                  <div v-if="u.ngan_sach_du_kien" class="budget-line">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>{{ formatCurrency(u.ngan_sach_du_kien) }}</span>
                  </div>

                  <!-- Interested Car -->
                  <div v-if="u.car" class="car-pill" :title="u.car.name">
                    <i class="fa-solid fa-car"></i>
                    <span>{{ u.car.name }}</span>
                  </div>
                </div>
              </td>

              <!-- Interaction Counts -->
              <td class="text-center">
                <div class="interaction-counts">
                  <span class="count-pill bookings" :title="`${u.bookings_count || 0} đơn đặt xe`">
                    <i class="fa-solid fa-file-invoice"></i> {{ u.bookings_count || 0 }}
                  </span>
                  <span class="count-pill contacts" :title="`${u.contacts_count || 0} yêu cầu tư vấn`">
                    <i class="fa-solid fa-headset"></i> {{ u.contacts_count || 0 }}
                  </span>
                </div>
              </td>

              <!-- Register Date -->
              <td>
                <span class="date-cell">{{ formatDate(u.created_at) }}</span>
              </td>

              <!-- Status -->
              <td>
                <span class="status-indicator" :class="u.is_active ? 'active' : 'inactive'">
                  {{ u.is_active ? 'Đang hoạt động' : 'Đã khóa' }}
                </span>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <div class="action-buttons">
                  <button 
                    class="btn-action-profile" 
                    @click="openCustomerProfile(u)" 
                    title="Xem hồ sơ chuyên sâu & Chăm sóc"
                  >
                    <i class="fa-solid fa-id-card"></i>
                    <span>Hồ sơ</span>
                  </button>
                  <button 
                    class="btn-icon" 
                    @click="requestPromote(u)" 
                    title="Cấp quyền quản trị viên"
                  >
                    <i class="fa-solid fa-user-shield"></i>
                  </button>
                  <button 
                    class="btn-icon" 
                    :class="u.is_active ? 'danger' : 'success'" 
                    @click="toggleCustomerStatus(u)" 
                    :title="u.is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'"
                  >
                    <i class="fa-solid" :class="u.is_active ? 'fa-user-lock' : 'fa-user-check'"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-users-slash"></i>
          <h4>Không tìm thấy khách hàng nào</h4>
          <p>Không có hồ sơ khách hàng khớp với bộ lọc hoặc từ khóa tìm kiếm.</p>
          <button class="btn-outline-sm" @click="resetAllFilters" v-if="hasActiveFilters">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc
          </button>
        </div>
      </div>

      <!-- Pagination Toolbar -->
      <div class="table-pagination-footer" v-if="customers.length > 0">
        <div class="pagination-info">
          Hiển thị <strong>{{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalRecords) }}</strong> 
          trong tổng số <strong>{{ totalRecords }}</strong> khách hàng
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

    <!-- Comprehensive Customer Profile Modal / Drawer -->
    <div v-if="selectedCustomer" class="modal-overlay" @click.self="closeCustomerProfile">
      <div class="modal-content card profile-modal animate-in">
        <!-- Profile Modal Header -->
        <div class="profile-header">
          <div class="profile-avatar">
            {{ selectedCustomer.ho_ten ? selectedCustomer.ho_ten.charAt(0).toUpperCase() : 'C' }}
          </div>
          <div class="profile-title-box">
            <div class="title-top-row">
              <h3>{{ selectedCustomer.ho_ten }}</h3>
              <span class="id-tag-customer">#CUS-{{ selectedCustomer.id }}</span>
              <span class="status-indicator" :class="selectedCustomer.is_active ? 'active' : 'inactive'">
                {{ selectedCustomer.is_active ? 'Đang hoạt động' : 'Đã khóa' }}
              </span>
            </div>
            <p class="profile-sub">
              <span><i class="fa-regular fa-envelope"></i> {{ selectedCustomer.email }}</span>
              <span class="sub-dot">•</span>
              <span><i class="fa-solid fa-phone"></i> {{ selectedCustomer.so_dien_thoai || 'Chưa có SĐT' }}</span>
              <span class="sub-dot">•</span>
              <span>Tham gia: {{ formatDate(selectedCustomer.created_at) }}</span>
            </p>
          </div>

          <div class="header-right-actions">
            <button 
              class="btn-status-toggle" 
              :class="selectedCustomer.is_active ? 'btn-lock' : 'btn-unlock'"
              @click="toggleCustomerStatus(selectedCustomer)"
              title="Đổi trạng thái tài khoản"
            >
              <i class="fa-solid" :class="selectedCustomer.is_active ? 'fa-user-lock' : 'fa-user-check'"></i>
              <span>{{ selectedCustomer.is_active ? 'Khóa tài khoản' : 'Mở khóa' }}</span>
            </button>
            <button class="btn-close-modal" @click="closeCustomerProfile" title="Đóng modal">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <!-- Profile Body -->
        <div class="modal-body profile-body">
          <!-- Quick Statistics Row -->
          <div class="stats-mini-grid">
            <div class="stat-mini-card">
              <span class="stat-label">Tổng đơn thuê xe</span>
              <span class="stat-value">{{ customerStats.total_bookings || 0 }}</span>
            </div>
            <div class="stat-mini-card">
              <span class="stat-label">Đơn hoàn tất</span>
              <span class="stat-value text-success">{{ customerStats.completed_bookings || 0 }}</span>
            </div>
            <div class="stat-mini-card">
              <span class="stat-label">Đơn đang chạy / chờ</span>
              <span class="stat-value text-primary">{{ customerStats.active_bookings || 0 }}</span>
            </div>
            <div class="stat-mini-card">
              <span class="stat-label">Tổng chi tiêu</span>
              <span class="stat-value" v-if="customerStats.can_view_financial">
                {{ formatCurrency(customerStats.total_spent) }}
              </span>
              <span class="stat-value text-restricted" v-else title="Cần quyền xem tài chính hoặc Chủ hệ thống">
                <i class="fa-solid fa-lock"></i> [Bảo mật]
              </span>
            </div>
            <div class="stat-mini-card">
              <span class="stat-label">Lịch hẹn tư vấn</span>
              <span class="stat-value text-info">{{ customerStats.total_appointments || customerAppointments.length || 0 }}</span>
            </div>
          </div>

          <!-- Section: Customer Preferences & Contact Info Form -->
          <div class="profile-section-card">
            <div class="section-card-header">
              <i class="fa-solid fa-sliders"></i>
              <span>Thông tin hồ sơ & Nhu cầu thuê xe</span>
            </div>

            <div class="profile-form-grid">
              <!-- Left: Contact Details -->
              <div class="form-column">
                <div class="form-group">
                  <label class="form-label">Họ và tên khách hàng:</label>
                  <input type="text" v-model="profileForm.ho_ten" class="form-input" />
                </div>

                <div class="form-group-row">
                  <div class="form-group">
                    <label class="form-label">Số điện thoại:</label>
                    <input type="text" v-model="profileForm.so_dien_thoai" class="form-input" placeholder="090..." />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Hạng GPLX:</label>
                    <select v-model="profileForm.gplx_hang" class="form-select">
                      <option value="">-- Chưa cập nhật --</option>
                      <option value="B1">B1 (Tự động)</option>
                      <option value="B2">B2 (Cơ bản)</option>
                      <option value="C">C (Xe tải)</option>
                      <option value="D">D / E (Chở khách)</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Địa chỉ liên hệ:</label>
                  <input type="text" v-model="profileForm.dia_chi" class="form-input" placeholder="Số nhà, đường, phường/xã..." />
                </div>
              </div>

              <!-- Right: Demand & Budget -->
              <div class="form-column">
                <div class="form-group-row">
                  <div class="form-group">
                    <label class="form-label">Nhu cầu thuê chính:</label>
                    <select v-model="profileForm.nhu_cau_thue" class="form-select">
                      <option value="">-- Chưa xác định --</option>
                      <option value="self_drive">Thuê tự lái</option>
                      <option value="with_driver">Thuê có tài xế</option>
                      <option value="monthly">Thuê theo tháng / Dài hạn</option>
                      <option value="wedding">Xe cưới / Sự kiện</option>
                      <option value="travel">Du lịch / Gia đình</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Ngân sách dự kiến (VNĐ):</label>
                    <input 
                      type="number" 
                      v-model="profileForm.ngan_sach_du_kien" 
                      class="form-input" 
                      placeholder="VD: 2500000" 
                      min="0"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Mẫu xe quan tâm / yêu thích:</label>
                  <select v-model="profileForm.car_id" class="form-select">
                    <option :value="null">-- Chưa gắn mẫu xe cụ thể --</option>
                    <option v-for="car in carsList" :key="car.id" :value="car.id">
                      {{ car.name }} {{ car.license_plate ? '(' + car.license_plate + ')' : '' }}
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Ghi chú sở thích & Thói quen thuê xe:</label>
                  <textarea 
                    v-model="profileForm.ghi_chu_so_thich" 
                    rows="2" 
                    class="form-textarea"
                    placeholder="VD: Thích xe màu trắng, giao xe tại sân bay Nội Bài, cần hóa đơn VAT..."
                    maxlength="1000"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="section-card-footer">
              <span class="security-note">
                <i class="fa-solid fa-shield-halved"></i>
                Lưu ý: Không lưu số thẻ tín dụng hoặc tài liệu nhạy cảm vào ô ghi chú tự do.
              </span>
              <button class="btn-primary-sm" @click="saveProfile" :disabled="savingProfile">
                <i v-if="savingProfile" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-floppy-disk"></i>
                <span>{{ savingProfile ? 'Đang lưu...' : 'Lưu cập nhật hồ sơ' }}</span>
              </button>
            </div>
          </div>

          <!-- History Tabs Section -->
          <div class="profile-tabs-header">
            <button 
              class="profile-tab-btn" 
              :class="{ 'active': activeHistoryTab === 'bookings' }" 
              @click="activeHistoryTab = 'bookings'"
            >
              <i class="fa-solid fa-file-invoice"></i>
              <span>Lịch sử đơn thuê xe ({{ customerBookings.length }})</span>
            </button>

            <button 
              class="profile-tab-btn" 
              :class="{ 'active': activeHistoryTab === 'contacts' }" 
              @click="activeHistoryTab = 'contacts'"
            >
              <i class="fa-solid fa-headset"></i>
              <span>Yêu cầu tư vấn & Liên hệ ({{ customerContacts.length }})</span>
            </button>

            <button 
              class="profile-tab-btn" 
              :class="{ 'active': activeHistoryTab === 'notes' }" 
              @click="activeHistoryTab = 'notes'"
            >
              <i class="fa-solid fa-timeline"></i>
              <span>Nhật ký chăm sóc ({{ customerNotes.length }})</span>
            </button>

            <button 
              class="profile-tab-btn" 
              :class="{ 'active': activeHistoryTab === 'appointments' }" 
              @click="activeHistoryTab = 'appointments'"
            >
              <i class="fa-solid fa-calendar-check"></i>
              <span>Lịch hẹn tư vấn ({{ customerAppointments.length }})</span>
            </button>
          </div>

          <!-- Tab Content 1: Bookings History -->
          <div v-show="activeHistoryTab === 'bookings'" class="history-tab-pane">
            <div class="history-table-wrap" v-if="customerBookings.length > 0">
              <table class="sub-table">
                <thead>
                  <tr>
                    <th>Mã đơn</th>
                    <th>Xe thuê</th>
                    <th>Thời gian thuê</th>
                    <th>Trạng thái thuê</th>
                    <th>Thanh toán</th>
                    <th class="text-right">Tổng tiền</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="b in customerBookings" :key="b.id">
                    <td>
                      <span class="sub-id-tag">{{ b.ma_dat_xe }}</span>
                    </td>
                    <td>
                      <div class="sub-car-cell" v-if="b.car">
                        <img :src="b.car.image || '/assets/images/default-car.png'" class="sub-car-thumb" @error="$event.target.src = '/assets/images/default-car.png'" />
                        <div>
                          <strong>{{ b.car.name }}</strong>
                          <small v-if="b.car.license_plate">{{ b.car.license_plate }}</small>
                        </div>
                      </div>
                      <span v-else class="text-muted-dash">-</span>
                    </td>
                    <td>
                      <div class="sub-date-cell">
                        <span>{{ formatDate(b.start_date) }}</span>
                        <i class="fa-solid fa-arrow-right-long text-muted"></i>
                        <span>{{ formatDate(b.end_date) }}</span>
                      </div>
                    </td>
                    <td>
                      <span class="badge-status-sub" :class="getBookingStatusClass(b.tinh_trang)">
                        {{ getBookingStatusText(b.tinh_trang) }}
                      </span>
                    </td>
                    <td>
                      <span class="badge-payment-sub" :class="b.is_thanh_toan === 1 ? 'paid' : 'unpaid'">
                        {{ b.is_thanh_toan === 1 ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                      </span>
                    </td>
                    <td class="text-right">
                      <span class="booking-amount">{{ b.tong_tien_formatted }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="empty-sub-state">
              <i class="fa-regular fa-folder-open"></i>
              <p>Khách hàng chưa có đơn đặt xe nào trong hệ thống.</p>
            </div>
          </div>

          <!-- Tab Content 2: Contacts & Consultations History -->
          <div v-show="activeHistoryTab === 'contacts'" class="history-tab-pane">
            <div class="history-table-wrap" v-if="customerContacts.length > 0">
              <table class="sub-table">
                <thead>
                  <tr>
                    <th width="100">Mã yêu cầu</th>
                    <th>Tiêu đề & Nội dung</th>
                    <th width="140">Xe quan tâm</th>
                    <th width="140">Người phụ trách</th>
                    <th width="120">Trạng thái</th>
                    <th width="120">Liên kết</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in customerContacts" :key="c.id">
                    <td>
                      <span class="sub-id-tag">#REQ-{{ c.id }}</span>
                    </td>
                    <td>
                      <div class="sub-content-cell">
                        <strong class="sub-subject">{{ c.tieu_de }}</strong>
                        <p class="sub-message">{{ c.noi_dung }}</p>
                        <small class="sub-time">{{ formatDateTime(c.created_at) }}</small>
                      </div>
                    </td>
                    <td>
                      <span v-if="c.car" class="sub-car-name">{{ c.car.name }}</span>
                      <span v-else class="text-muted-dash">-</span>
                    </td>
                    <td>
                      <span v-if="c.assignee" class="sub-staff-name">
                        <i class="fa-solid fa-user-check"></i> {{ c.assignee.ho_ten }}
                      </span>
                      <span v-else class="text-muted-dash">Chưa phân công</span>
                    </td>
                    <td>
                      <span class="badge-contact-sub" :class="getContactStatusClass(c.trang_thai)">
                        {{ getContactStatusText(c.trang_thai) }}
                      </span>
                    </td>
                    <td>
                      <span v-if="c.is_linked" class="badge-linked">
                        <i class="fa-solid fa-link"></i> Đã gắn
                      </span>
                      <button 
                        v-else 
                        class="btn-link-action" 
                        @click="handleLinkContact(c)"
                        title="Liên kết yêu cầu này vào hồ sơ khách hàng"
                      >
                        <i class="fa-solid fa-link"></i> Gắn hồ sơ
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="empty-sub-state">
              <i class="fa-regular fa-comment-dots"></i>
              <p>Chưa có yêu cầu tư vấn hay liên hệ nào từ khách hàng này.</p>
            </div>
          </div>

          <!-- Tab Content 3: Customer Care Notes Timeline -->
          <div v-show="activeHistoryTab === 'notes'" class="history-tab-pane">
            <!-- Add Note Form -->
            <div class="add-care-note-box">
              <label class="form-label"><i class="fa-solid fa-pen-to-square"></i> Thêm ghi chú chăm sóc khách hàng:</label>
              <div class="add-note-row">
                <textarea 
                  v-model="newCareNote" 
                  rows="2" 
                  class="form-textarea"
                  placeholder="Ghi nhận nhật ký cuộc gọi, mong muốn thuê xe, phản hồi chất lượng..."
                  maxlength="1000"
                ></textarea>
                <button 
                  class="btn-primary-sm" 
                  @click="submitCareNote" 
                  :disabled="submittingNote || !newCareNote.trim()"
                >
                  <i v-if="submittingNote" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-paper-plane"></i>
                  <span>{{ submittingNote ? 'Đang gửi...' : 'Gửi ghi chú' }}</span>
                </button>
              </div>
            </div>

            <!-- Notes Timeline -->
            <div class="notes-timeline-stream" v-if="customerNotes.length > 0">
              <div v-for="note in customerNotes" :key="note.id" class="note-timeline-item">
                <div class="note-marker"><i class="fa-solid fa-note-sticky"></i></div>
                <div class="note-card">
                  <div class="note-card-header">
                    <span class="note-author">{{ note.admin_name || note.admin?.ho_ten || 'Quản trị viên' }}</span>
                    <span class="note-time">{{ formatDateTime(note.created_at) }}</span>
                  </div>
                  <div class="note-content">{{ note.content }}</div>
                </div>
              </div>
            </div>

            <div v-else class="empty-sub-state">
              <i class="fa-regular fa-clock"></i>
              <p>Chưa có ghi chú chăm sóc nào được ghi nhận cho khách hàng này.</p>
            </div>
          </div>

          <!-- Tab Content 4: Appointments History -->
          <div v-show="activeHistoryTab === 'appointments'" class="history-tab-pane">
            <div class="appointment-header-bar">
              <div class="appt-count-text">
                Tổng cộng <strong>{{ customerAppointments.length }}</strong> lịch hẹn đã ghi nhận
              </div>
              <button class="btn-primary-sm" @click="createAppointmentForCustomer(selectedCustomer)">
                <i class="fa-solid fa-calendar-plus"></i>
                <span>Đặt lịch hẹn mới</span>
              </button>
            </div>

            <div class="history-table-wrap" v-if="customerAppointments.length > 0">
              <table class="sub-table">
                <thead>
                  <tr>
                    <th width="100">Mã hẹn</th>
                    <th width="140">Hình thức</th>
                    <th>Thời gian</th>
                    <th width="160">Xe quan tâm</th>
                    <th width="140">Chuyên viên</th>
                    <th width="130">Trạng thái</th>
                    <th width="110" class="text-center">Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="apt in customerAppointments" :key="apt.id">
                    <td>
                      <span class="sub-id-tag">#APT-{{ apt.id }}</span>
                    </td>
                    <td>
                      <span class="badge-appt-type">{{ getAppointmentTypeText(apt.meeting_type) }}</span>
                    </td>
                    <td>
                      <div class="sub-date-cell">
                        <strong>{{ formatDateTime(apt.start_time) }}</strong>
                        <small class="text-muted">Đến: {{ formatDateTime(apt.end_time) }}</small>
                      </div>
                    </td>
                    <td>
                      <span v-if="apt.car" class="sub-car-name">
                        <i class="fa-solid fa-car-side"></i> {{ apt.car.name }}
                      </span>
                      <span v-else class="text-muted-dash">-</span>
                    </td>
                    <td>
                      <span v-if="apt.assignee" class="sub-staff-name">
                        <i class="fa-solid fa-user-check"></i> {{ apt.assignee.ho_ten }}
                      </span>
                      <span v-else class="text-muted-dash">Chưa gán</span>
                    </td>
                    <td>
                      <span class="badge-appt-status" :class="getAppointmentStatusClass(apt.status)">
                        {{ getAppointmentStatusText(apt.status) }}
                      </span>
                    </td>
                    <td class="text-center">
                      <router-link :to="`/admin/appointments?search=${apt.id}`" class="btn-link-action" title="Xem trên lịch">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="empty-sub-state">
              <i class="fa-regular fa-calendar-xmark"></i>
              <p>Khách hàng chưa có lịch hẹn tư vấn hoặc xem xe nào.</p>
              <button class="btn-primary-sm mt-2" @click="createAppointmentForCustomer(selectedCustomer)">
                <i class="fa-solid fa-calendar-plus"></i> Đặt lịch hẹn đầu tiên
              </button>
            </div>
          </div>
        </div>

        <!-- Profile Modal Footer -->
        <div class="modal-footer">
          <button class="btn-outline" @click="closeCustomerProfile">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Promote Modal (Giữ lại tính năng an toàn cấp quyền Admin) -->
    <div v-if="userToPromote" class="modal-overlay" @click.self="userToPromote = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper blue">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h3>Nâng cấp quyền Quản trị</h3>
            <p class="modal-sub">Cấp vai trò quản trị mà vẫn bảo toàn tài khoản và lịch sử đơn khách hàng</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Tài khoản khách hàng sau sẽ được cấp quyền truy cập hệ thống quản trị:</p>
          <div class="highlight-box blue-box">
            <strong>{{ userToPromote.ho_ten }}</strong>
            <span>Email: {{ userToPromote.email }} | Mã khách: #CUS-{{ userToPromote.id }}</span>
          </div>

          <div style="margin-top: 16px; text-align: left;">
            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px; color: var(--color-text-main);">
              Vai trò chỉ định:
            </label>
            <select v-model="promoteRole" class="form-select" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 0.9rem;">
              <option value="dispatcher">Điều phối viên (Quản lý xe, giao nhận & xem đơn)</option>
              <option value="accountant">Kế toán viên (Thu chi, đối soát & báo cáo)</option>
              <option value="owner">Chủ hệ thống (Toàn quyền quản trị)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="userToPromote = null">Hủy</button>
          <button class="btn-primary" @click="confirmPromote" :disabled="promoting">
            <i v-if="promoting" class="fa-solid fa-spinner fa-spin"></i>
            {{ promoting ? 'Đang xử lý...' : 'Xác nhận nâng cấp' }}
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

const router = useRouter();
const toaster = createToaster({ position: "top-right" });

// State
const customers = ref([]);
const carsList = ref([]);
const loading = ref(true);
const promoting = ref(false);
const savingProfile = ref(false);
const submittingNote = ref(false);

// Filters & Server Pagination
const searchQuery = ref('');
const filterStatus = ref('');
const filterDemand = ref('');
const filterCarId = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const totalRecords = ref(0);

// KPI counts
const counts = ref({
  total: 0,
  active: 0,
  locked: 0,
  with_bookings: 0,
  with_contacts: 0,
});

// Customer Profile Drawer/Modal
const selectedCustomer = ref(null);
const customerStats = ref({});
const customerBookings = ref([]);
const customerContacts = ref([]);
const customerNotes = ref([]);
const customerAppointments = ref([]);
const activeHistoryTab = ref('bookings'); // 'bookings' | 'contacts' | 'notes' | 'appointments'
const newCareNote = ref('');

const profileForm = ref({
  ho_ten: '',
  so_dien_thoai: '',
  dia_chi: '',
  nhu_cau_thue: '',
  ngan_sach_du_kien: null,
  car_id: null,
  ghi_chu_so_thich: '',
  gplx_hang: '',
});

// Promote state
const userToPromote = ref(null);
const promoteRole = ref('dispatcher');

// Computed Filters Check
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' ||
    filterStatus.value !== '' ||
    filterDemand.value !== '' ||
    filterCarId.value !== '';
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

// Fetch Customers List with server search & pagination
const fetchCustomers = async () => {
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
      params.status = filterStatus.value;
    }
    if (filterDemand.value !== '') {
      params.nhu_cau_thue = filterDemand.value;
    }
    if (filterCarId.value !== '') {
      params.car_id = filterCarId.value;
    }

    const res = await baseRequest.get('admin/customers', { params });
    if (res.data && res.data.status) {
      const respData = res.data.data;
      if (respData && Array.isArray(respData.data)) {
        customers.value = respData.data;
        totalRecords.value = respData.total || respData.data.length;
        lastPage.value = respData.last_page || 1;
        currentPage.value = respData.current_page || 1;
      } else if (Array.isArray(respData)) {
        customers.value = respData;
        totalRecords.value = respData.length;
        lastPage.value = 1;
      }

      if (res.data.counts) {
        counts.value = res.data.counts;
      }
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách khách hàng!");
  } finally {
    loading.value = false;
  }
};

// Fetch Active Cars for dropdown
const fetchCars = async () => {
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

// Filter Controls
const applyFilters = () => {
  currentPage.value = 1;
  fetchCustomers();
};

const changePage = (page) => {
  if (page < 1 || page > lastPage.value) return;
  currentPage.value = page;
  fetchCustomers();
};

const resetKpiFilter = () => {
  filterStatus.value = '';
  filterDemand.value = '';
  filterCarId.value = '';
  currentPage.value = 1;
  fetchCustomers();
};

const setKpiStatus = (st) => {
  filterStatus.value = st;
  filterDemand.value = '';
  currentPage.value = 1;
  fetchCustomers();
};

const resetAllFilters = () => {
  searchQuery.value = '';
  filterStatus.value = '';
  filterDemand.value = '';
  filterCarId.value = '';
  currentPage.value = 1;
  fetchCustomers();
};

// Open Customer Profile Modal
const openCustomerProfile = async (customer) => {
  selectedCustomer.value = { ...customer };
  activeHistoryTab.value = 'bookings';
  newCareNote.value = '';

  profileForm.value = {
    ho_ten: customer.ho_ten || '',
    so_dien_thoai: customer.so_dien_thoai || '',
    dia_chi: customer.dia_chi || '',
    nhu_cau_thue: customer.nhu_cau_thue || '',
    ngan_sach_du_kien: customer.ngan_sach_du_kien ? Number(customer.ngan_sach_du_kien) : null,
    car_id: customer.car_id || null,
    ghi_chu_so_thich: customer.ghi_chu_so_thich || '',
    gplx_hang: customer.gplx_hang || '',
  };

  try {
    const res = await baseRequest.get(`admin/customers/${customer.id}`);
    if (res.data && res.data.status && res.data.data) {
      const detail = res.data.data;
      selectedCustomer.value = detail.user;
      customerStats.value = detail.stats || {};
      customerBookings.value = detail.bookings || [];
      customerContacts.value = detail.contacts || [];
      customerNotes.value = detail.user?.notes || [];
      customerAppointments.value = detail.appointments || [];

      profileForm.value = {
        ho_ten: detail.user.ho_ten || '',
        so_dien_thoai: detail.user.so_dien_thoai || '',
        dia_chi: detail.user.dia_chi || '',
        nhu_cau_thue: detail.user.nhu_cau_thue || '',
        ngan_sach_du_kien: detail.user.ngan_sach_du_kien ? Number(detail.user.ngan_sach_du_kien) : null,
        car_id: detail.user.car_id || null,
        ghi_chu_so_thich: detail.user.ghi_chu_so_thich || '',
        gplx_hang: detail.user.gplx_hang || '',
      };
    }
  } catch (err) {
    toaster.error("Không thể tải chi tiết hồ sơ khách hàng!");
  }
};

const closeCustomerProfile = () => {
  selectedCustomer.value = null;
};

// Create appointment shortcut for this customer
const createAppointmentForCustomer = (customer) => {
  if (!customer) return;
  router.push({
    path: '/admin/appointments',
    query: {
      create: '1',
      user_id: customer.id,
      customer_name: customer.ho_ten || '',
      customer_phone: customer.so_dien_thoai || '',
      customer_email: customer.email || '',
      car_id: customer.car_id || '',
      notes: `Hẹn tư vấn/xem xe cho khách hàng #${customer.id} - ${customer.ho_ten}`
    }
  });
};

const getAppointmentStatusClass = (st) => {
  if (st === 'completed') return 'status-completed';
  if (st === 'cancelled') return 'status-cancelled';
  if (st === 'no_show') return 'status-no-show';
  return 'status-scheduled';
};

const getAppointmentStatusText = (st) => {
  if (st === 'completed') return 'Đã hoàn tất';
  if (st === 'cancelled') return 'Đã hủy';
  if (st === 'no_show') return 'Khách không đến';
  return 'Đã lên lịch';
};

const getAppointmentTypeText = (type) => {
  if (type === 'viewing') return 'Xem xe thực tế';
  if (type === 'consultation') return 'Tư vấn trực tiếp';
  return 'Tư vấn Online';
};

// Save Profile Updates via dedicated API
const saveProfile = async () => {
  if (!selectedCustomer.value) return;
  savingProfile.value = true;
  try {
    const res = await baseRequest.put(`admin/customers/${selectedCustomer.value.id}`, {
      ho_ten: profileForm.value.ho_ten,
      so_dien_thoai: profileForm.value.so_dien_thoai || null,
      dia_chi: profileForm.value.dia_chi || null,
      nhu_cau_thue: profileForm.value.nhu_cau_thue || null,
      ngan_sach_du_kien: profileForm.value.ngan_sach_du_kien || null,
      car_id: profileForm.value.car_id || null,
      ghi_chu_so_thich: profileForm.value.ghi_chu_so_thich || null,
      gplx_hang: profileForm.value.gplx_hang || null,
    });

    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Cập nhật hồ sơ khách hàng thành công!");
      fetchCustomers();
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Có lỗi xảy ra khi cập nhật hồ sơ!";
    toaster.error(msg);
  } finally {
    savingProfile.value = false;
  }
};

// Add Care Note to Customer Timeline
const submitCareNote = async () => {
  if (!selectedCustomer.value || !newCareNote.value.trim()) return;
  submittingNote.value = true;
  try {
    const res = await baseRequest.post(`admin/customers/${selectedCustomer.value.id}/notes`, {
      content: newCareNote.value.trim(),
    });

    if (res.data && res.data.status) {
      toaster.success("Đã thêm nhật ký chăm sóc khách hàng!");
      newCareNote.value = '';
      if (res.data.data) {
        customerNotes.value.unshift(res.data.data);
      }
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể thêm ghi chú!");
  } finally {
    submittingNote.value = false;
  }
};

// Toggle Customer Status (Lock / Unlock) - PRESERVES BOOKINGS
const toggleCustomerStatus = async (user) => {
  try {
    const res = await baseRequest.post(`admin/customers/${user.id}/status`);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã đổi trạng thái khách hàng thành công!");
      user.is_active = res.data.data?.is_active ?? (user.is_active ? 0 : 1);
      if (selectedCustomer.value && selectedCustomer.value.id === user.id) {
        selectedCustomer.value.is_active = user.is_active;
      }
      fetchCustomers();
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi hệ thống khi cập nhật trạng thái.");
  }
};

// Link Contact (Lead) to Customer
const handleLinkContact = async (contact) => {
  if (!selectedCustomer.value) return;
  try {
    const res = await baseRequest.post(`admin/customers/${selectedCustomer.value.id}/link-contact`, {
      contact_id: contact.id,
    });
    if (res.data && res.data.status) {
      toaster.success("Đã liên kết yêu cầu tư vấn vào hồ sơ khách!");
      contact.is_linked = true;
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể liên kết yêu cầu!");
  }
};

// Promote to Admin User
const requestPromote = (user) => {
  userToPromote.value = user;
  promoteRole.value = 'dispatcher';
};

const confirmPromote = async () => {
  if (!userToPromote.value) return;
  promoting.value = true;
  try {
    const res = await baseRequest.post('admin/users/promote', { 
      id: userToPromote.value.id,
      role: promoteRole.value,
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã cấp quyền Quản trị viên thành công!");
      userToPromote.value = null;
      await fetchCustomers();
    } else {
      toaster.error(res.data?.message || "Không thể cấp quyền quản trị!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi kết nối với máy chủ!");
  } finally {
    promoting.value = false;
  }
};

// Formatters
const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
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

const getDemandLabel = (code) => {
  switch (code) {
    case 'self_drive': return 'Tự lái';
    case 'with_driver': return 'Có tài xế';
    case 'monthly': return 'Thuê dài hạn';
    case 'wedding': return 'Xe cưới/Sự kiện';
    case 'travel': return 'Du lịch/Gia đình';
    default: return 'Khác';
  }
};

const getBookingStatusText = (st) => {
  switch (Number(st)) {
    case 0: return 'Chờ duyệt';
    case 1: return 'Đã duyệt';
    case 2: return 'Đã hủy';
    case 3: return 'Đang đi';
    case 4: return 'Đã trả xe';
    case 5: return 'Hoàn thành';
    default: return 'Khác';
  }
};

const getBookingStatusClass = (st) => {
  switch (Number(st)) {
    case 0: return 'b-pending';
    case 1: return 'b-confirmed';
    case 2: return 'b-cancelled';
    case 3: return 'b-progress';
    case 4: return 'b-returned';
    case 5: return 'b-completed';
    default: return 'b-muted';
  }
};

const getContactStatusText = (st) => {
  switch (Number(st)) {
    case 0: return 'Chờ xử lý';
    case 1: return 'Đang xử lý';
    case 2: return 'Đã giải quyết';
    default: return 'Khác';
  }
};

const getContactStatusClass = (st) => {
  switch (Number(st)) {
    case 0: return 'c-pending';
    case 1: return 'c-processing';
    case 2: return 'c-resolved';
    default: return 'c-muted';
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    if (selectedCustomer.value) closeCustomerProfile();
    if (userToPromote.value) userToPromote.value = null;
  }
};

onMounted(() => {
  fetchCustomers();
  fetchCars();
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
  gap: 20px;
  padding-bottom: 40px;
}

/* Header */
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
  width: 50px;
  height: 50px;
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
  font-size: 0.88rem;
  color: var(--color-text-muted, #64748b);
  margin-top: 2px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-link-admin {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #334155;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-link-admin:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
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

/* Entity Navigation Tabs */
.entity-nav-tabs {
  display: flex;
  gap: 12px;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 2px;
}

.entity-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px 10px 0 0;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -4px;
  text-decoration: none;
  transition: all 0.2s;
}

.entity-tab:hover {
  color: var(--color-primary, #0066ff);
}

.entity-tab.active {
  color: var(--color-primary, #0066ff);
  border-bottom-color: var(--color-primary, #0066ff);
  background: rgba(0, 102, 255, 0.04);
}

.tab-badge {
  background: #e2e8f0;
  color: #334155;
  font-size: 0.74rem;
  padding: 2px 7px;
  border-radius: 10px;
}

.entity-tab.active .tab-badge {
  background: var(--color-primary, #0066ff);
  color: white;
}

.tab-ext-icon {
  font-size: 0.72rem;
  color: #94a3b8;
}

/* KPI Summary Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
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

.kpi-card:hover:not(.static) {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(0,0,0,0.06);
  border-color: #bfdbfe;
}

.kpi-card.active {
  border-color: var(--color-primary, #0066ff);
  background: rgba(0, 102, 255, 0.03);
  box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.15);
}

.kpi-card.static {
  cursor: default;
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
.kpi-icon-box.active { background: #ecfdf5; color: #059669; }
.kpi-icon-box.locked { background: #fef2f2; color: #dc2626; }
.kpi-icon-box.bookings { background: #f5f3ff; color: #7c3aed; }
.kpi-icon-box.inquiries { background: #fffbeb; color: #d97706; }

.kpi-info {
  display: flex;
  flex-direction: column;
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
  padding: 0;
  position: relative;
  overflow: hidden;
  background: white;
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border, #e2e8f0);
}

.table-tools {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-bottom: 1px solid var(--color-border, #e2e8f0);
  background: #fafbfc;
}

.filter-row-top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 500px;
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
  background: white;
  transition: all 0.2s;
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
  background: white;
  font-size: 0.86rem;
  outline: none;
  cursor: pointer;
}

.form-select:focus {
  border-color: var(--color-primary, #0066ff);
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
  margin-left: auto;
  transition: all 0.2s;
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
}

.premium-table th {
  background: #f8fafc;
  padding: 12px 18px;
  text-align: left;
  font-size: 0.76rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table td {
  padding: 13px 18px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.locked-row {
  background: rgba(220, 38, 38, 0.02);
}

.id-tag-customer {
  display: inline-block;
  font-family: monospace;
  font-weight: 700;
  font-size: 0.8rem;
  color: #1e40af;
  background: #eff6ff;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid #bfdbfe;
}

.user-profile-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--grad-active, linear-gradient(135deg, #0066ff 0%, #00d2ff 100%));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.info .name {
  display: block;
  font-weight: 700;
  color: #0f172a;
  font-size: 0.88rem;
}

.contact-sub {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.74rem;
  color: #64748b;
  margin-top: 2px;
}

.contact-sub span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.preference-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.demand-badge-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
}

.badge-demand {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 700;
}

.badge-demand.self_drive { background: #e0f2fe; color: #0369a1; }
.badge-demand.with_driver { background: #ecfdf5; color: #047857; }
.badge-demand.monthly { background: #f3e8ff; color: #7e22ce; }
.badge-demand.wedding { background: #fdf2f8; color: #be185d; }
.badge-demand.travel { background: #fffbeb; color: #b45309; }

.badge-gplx {
  font-size: 0.68rem;
  font-weight: 700;
  color: #475569;
  background: #f1f5f9;
  padding: 1px 6px;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
}

.budget-line {
  font-size: 0.74rem;
  color: #059669;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
}

.car-pill {
  font-size: 0.74rem;
  color: #2563eb;
  background: #eff6ff;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  max-width: fit-content;
}

.interaction-counts {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.count-pill {
  font-size: 0.76rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.count-pill.bookings { background: #f5f3ff; color: #7c3aed; }
.count-pill.contacts { background: #eff6ff; color: #1d4ed8; }

.date-cell {
  font-size: 0.8rem;
  color: #475569;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 700;
}

.status-indicator::before {
  content: "";
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-indicator.active { color: #16a34a; }
.status-indicator.active::before { background: #22c55e; box-shadow: 0 0 6px #22c55e; }
.status-indicator.inactive { color: #dc2626; }
.status-indicator.inactive::before { background: #ef4444; }

.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
}

.btn-action-profile {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action-profile:hover {
  background: #dbeafe;
}

.btn-icon {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  transition: all 0.15s;
}

.btn-icon:hover {
  background: #f1f5f9;
  color: var(--color-primary, #0066ff);
  border-color: var(--color-primary, #0066ff);
}

.btn-icon.danger:hover {
  color: #dc2626;
  border-color: #dc2626;
  background: #fef2f2;
}

.btn-icon.success:hover {
  color: #16a34a;
  border-color: #16a34a;
  background: #ecfdf5;
}

/* Pagination Footer */
.table-pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
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

/* Comprehensive Customer Profile Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.profile-modal {
  width: 100%;
  max-width: 920px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.profile-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--grad-active, linear-gradient(135deg, #0066ff 0%, #00d2ff 100%));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 800;
  flex-shrink: 0;
}

.profile-title-box {
  flex: 1;
}

.title-top-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-top-row h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.profile-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 4px 0 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sub-dot { color: #cbd5e1; }

.header-right-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-status-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s;
}

.btn-status-toggle.btn-lock {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

.btn-status-toggle.btn-lock:hover {
  background: #fee2e2;
}

.btn-status-toggle.btn-unlock {
  background: #ecfdf5;
  color: #059669;
  border-color: #a7f3d0;
}

.btn-status-toggle.btn-unlock:hover {
  background: #d1fae5;
}

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

.profile-body {
  padding: 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* Stats Mini Grid */
.stats-mini-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.stat-mini-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-mini-card .stat-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
}

.stat-mini-card .stat-value {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

.text-success { color: #16a34a !important; }
.text-primary { color: #0284c7 !important; }
.text-restricted { color: #94a3b8 !important; font-size: 0.85rem !important; }

/* Profile Section Card (Preferences form) */
.profile-section-card {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
  overflow: hidden;
}

.section-card-header {
  padding: 12px 18px;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.82rem;
  font-weight: 800;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-form-grid {
  padding: 16px 18px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.form-group-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #475569;
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

.form-textarea {
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

.form-textarea:focus {
  border-color: var(--color-primary, #0066ff);
}

.section-card-footer {
  padding: 12px 18px;
  background: white;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.security-note {
  font-size: 0.74rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-primary-sm {
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-primary-sm:hover:not(:disabled) {
  background: #0052cc;
}

.btn-primary-sm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Profile Tabs Header */
.profile-tabs-header {
  display: flex;
  border-bottom: 2px solid #e2e8f0;
  gap: 8px;
}

.profile-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
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

.profile-tab-btn:hover {
  color: var(--color-primary, #0066ff);
}

.profile-tab-btn.active {
  color: var(--color-primary, #0066ff);
  border-bottom-color: var(--color-primary, #0066ff);
}

.history-tab-pane {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* Sub Table for Bookings & Contacts */
.history-table-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.sub-table {
  width: 100%;
  border-collapse: collapse;
}

.sub-table th {
  background: #f8fafc;
  padding: 10px 14px;
  text-align: left;
  font-size: 0.74rem;
  font-weight: 700;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.sub-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.82rem;
  vertical-align: middle;
}

.sub-id-tag {
  font-family: monospace;
  font-weight: 700;
  font-size: 0.78rem;
  color: #0f172a;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.sub-car-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sub-car-thumb {
  width: 38px;
  height: 26px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.sub-date-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
}

.badge-status-sub {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
}

.badge-status-sub.b-pending { background: #fffbeb; color: #b45309; }
.badge-status-sub.b-confirmed { background: #e0f2fe; color: #0369a1; }
.badge-status-sub.b-progress { background: #fef3c7; color: #d97706; }
.badge-status-sub.b-completed { background: #ecfdf5; color: #047857; }
.badge-status-sub.b-cancelled { background: #fef2f2; color: #dc2626; }
.badge-status-sub.b-returned { background: #f3e8ff; color: #7e22ce; }

.badge-payment-sub {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
}

.badge-payment-sub.paid { background: #ecfdf5; color: #047857; }
.badge-payment-sub.unpaid { background: #fef2f2; color: #dc2626; }

.booking-amount {
  font-weight: 800;
  color: #0f172a;
}

.sub-content-cell .sub-subject {
  display: block;
  font-size: 0.84rem;
  color: #0f172a;
}

.sub-content-cell .sub-message {
  font-size: 0.76rem;
  color: #64748b;
  margin: 2px 0 0;
  line-height: 1.3;
}

.sub-time {
  font-size: 0.7rem;
  color: #94a3b8;
}

.badge-linked {
  font-size: 0.72rem;
  font-weight: 700;
  color: #059669;
  background: #ecfdf5;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-link-action {
  font-size: 0.72rem;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 3px 8px;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-link-action:hover {
  background: #dbeafe;
}

/* Customer Appointments Tab */
.appointment-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.appt-count-text {
  font-size: 0.82rem;
  color: #475569;
}

.badge-appt-type {
  font-size: 0.72rem;
  font-weight: 700;
  color: #4338ca;
  background: #e0e7ff;
  padding: 2px 8px;
  border-radius: 12px;
}

.badge-appt-status {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
}

.badge-appt-status.status-scheduled { background: #eff6ff; color: #1d4ed8; }
.badge-appt-status.status-completed { background: #ecfdf5; color: #047857; }
.badge-appt-status.status-cancelled { background: #fef2f2; color: #b91c1c; }
.badge-appt-status.status-no-show { background: #fef3c7; color: #b45309; }

/* Care Notes Timeline */
.add-care-note-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
}

.add-note-row {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}

.notes-timeline-stream {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 320px;
  overflow-y: auto;
}

.note-timeline-item {
  display: flex;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 14px;
}

.note-marker {
  color: #0284c7;
  font-size: 1rem;
  margin-top: 2px;
}

.note-card {
  flex: 1;
}

.note-card-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.note-author {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
}

.note-time {
  font-size: 0.72rem;
  color: #94a3b8;
}

.note-content {
  font-size: 0.84rem;
  color: #334155;
  line-height: 1.5;
  white-space: pre-wrap;
}

.empty-sub-state {
  padding: 40px 20px;
  text-align: center;
  color: #94a3b8;
}

.empty-sub-state i {
  font-size: 2rem;
  margin-bottom: 8px;
}

/* Promote Modal */
.modal-content.delete-modal {
  max-width: 480px;
}

.delete-icon-wrapper.blue {
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary, #0066ff);
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.highlight-box.blue-box {
  margin-top: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid var(--color-primary, #0066ff);
  display: flex;
  flex-direction: column;
}

.highlight-box strong { font-size: 0.95rem; color: #0f172a; }
.highlight-box span { font-size: 0.8rem; color: #64748b; margin-top: 2px; }

.modal-footer {
  padding: 14px 24px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-outline {
  padding: 8px 18px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-weight: 600;
  font-size: 0.88rem;
  cursor: pointer;
}

.btn-primary {
  padding: 8px 22px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover:not(:disabled) { opacity: 0.9; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

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
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary, #0066ff);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 1024px) {
  .kpi-grid { grid-template-columns: repeat(3, 1fr); }
  .profile-form-grid { grid-template-columns: 1fr; }
  .stats-mini-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .form-group-row { grid-template-columns: 1fr; }
}
</style>
