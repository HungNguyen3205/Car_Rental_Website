<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <h1 class="page-title">Phân Quyền & <span>Nhật Ký Thao Tác</span></h1>
          <p class="page-subtitle">Thiết lập vai trò quản trị (Chủ hệ thống, Điều phối, Kế toán), ma trận quyền và truy vết kiểm toán.</p>
        </div>
      </div>
      <div class="header-actions">
        <button v-if="activeTab === 'accounts'" class="btn-primary" @click="openModal('create')">
          <i class="fa-solid fa-plus"></i> Thêm Admin mới
        </button>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="tabs-nav-wrapper">
      <div class="tabs-nav">
        <button 
          type="button" 
          class="tab-btn" 
          :class="{ active: activeTab === 'accounts' }" 
          @click="activeTab = 'accounts'"
        >
          <i class="fa-solid fa-user-shield"></i>
          <span>Tài khoản quản trị</span>
          <span class="tab-count">{{ admins.length }}</span>
        </button>
        <button 
          type="button" 
          class="tab-btn" 
          :class="{ active: activeTab === 'matrix' }" 
          @click="activeTab = 'matrix'"
        >
          <i class="fa-solid fa-table-cells"></i>
          <span>Ma trận phân quyền</span>
        </button>
        <button 
          type="button" 
          class="tab-btn" 
          :class="{ active: activeTab === 'audit' }" 
          @click="switchTab('audit')"
        >
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Nhật ký kiểm toán (Audit Logs)</span>
        </button>
      </div>
    </div>

    <!-- TAB 1: DANH SÁCH TÀI KHOẢN QUẢN TRỊ -->
    <div v-if="activeTab === 'accounts'" class="tab-content animate-in">
      <div class="content-card card">
        <div class="table-tools">
          <div class="search-input">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Tìm theo tên, email hoặc vai trò..." v-model="searchQuery" />
          </div>
          <div class="filter-group">
            <select v-model="filterRole" class="form-select">
              <option value="">Tất cả vai trò</option>
              <option value="owner">Chủ hệ thống</option>
              <option value="dispatcher">Điều phối viên</option>
              <option value="accountant">Kế toán</option>
            </select>
          </div>
        </div>

        <div class="table-responsive">
          <table class="premium-table" v-if="filteredAdmins.length > 0">
            <thead>
              <tr>
                <th>Quản trị viên</th>
                <th>Vai trò nghiệp vụ</th>
                <th>Thông tin liên hệ</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th class="text-center" width="140">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="admin in filteredAdmins" :key="admin.id">
                <td>
                  <div class="user-profile-cell">
                    <div class="avatar-circle" :class="admin.role || 'dispatcher'">
                      {{ admin.ho_ten ? admin.ho_ten.charAt(0).toUpperCase() : 'A' }}
                    </div>
                    <div class="info">
                      <span class="name">{{ admin.ho_ten }}</span>
                      <span class="id">#ADM-{{ admin.id }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="role-badge" :class="admin.role || 'dispatcher'">
                    <i :class="getRoleIcon(admin.role)"></i>
                    {{ getRoleName(admin.role) }}
                  </span>
                </td>
                <td>
                  <div class="contact-info">
                    <span><i class="fa-solid fa-envelope"></i> {{ admin.email }}</span>
                    <span><i class="fa-solid fa-phone"></i> {{ admin.so_dien_thoai || 'Chưa cập nhật' }}</span>
                  </div>
                </td>
                <td>{{ formatDate(admin.created_at) }}</td>
                <td>
                  <div class="form-switch-wrapper">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      :id="'switch-' + admin.id"
                      :checked="Number(admin.tinh_trang) === 1" 
                      :disabled="isLastActiveOwner(admin)"
                      @change="toggleStatus(admin)"
                    />
                    <label :for="'switch-' + admin.id" class="status-label" :class="Number(admin.tinh_trang) === 1 ? 'active' : 'inactive'">
                      {{ Number(admin.tinh_trang) === 1 ? 'Hoạt động' : 'Tạm khóa' }}
                    </label>
                  </div>
                </td>
                <td class="text-center">
                  <div class="action-buttons">
                    <button class="btn-icon" @click="openModal('update', admin)" title="Chỉnh sửa thông tin & quyền">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button 
                      class="btn-icon danger" 
                      :disabled="isLastActiveOwner(admin)"
                      @click="requestDelete(admin)" 
                      :title="isLastActiveOwner(admin) ? 'Không thể xóa chủ hệ thống cuối cùng' : 'Xóa tài khoản'"
                    >
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else-if="loading" class="loading-state-box">
            <div class="spinner"></div>
            <p>Đang tải danh sách tài khoản...</p>
          </div>

          <div v-else class="empty-state-box">
            <i class="fa-solid fa-user-xmark"></i>
            <h4>Không tìm thấy tài khoản quản trị nào</h4>
            <p>Thử tìm kiếm với từ khóa khác hoặc điều chỉnh bộ lọc vai trò.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: MA TRẬN PHÂN QUYỀN HỆ THỐNG -->
    <div v-if="activeTab === 'matrix'" class="tab-content animate-in">
      <div class="content-card card">
        <div class="matrix-header">
          <h3>Ma Trận Phân Quyền Vai Trò Nghiệp Vụ</h3>
          <p>Hệ thống thực thi phân quyền nghiêm ngặt tại Backend qua Middleware & Policy; ẩn nút ở Frontend chỉ phục vụ trải nghiệm người dùng.</p>
        </div>

        <div class="table-responsive">
          <table class="premium-table matrix-table">
            <thead>
              <tr>
                <th width="35%">Nhóm & Quyền Hạn Nghiệp Vụ</th>
                <th class="text-center" width="20%">
                  <div class="matrix-role-header owner">
                    <i class="fa-solid fa-crown"></i>
                    <span>Chủ hệ thống</span>
                  </div>
                </th>
                <th class="text-center" width="20%">
                  <div class="matrix-role-header dispatcher">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    <span>Điều phối viên</span>
                  </div>
                </th>
                <th class="text-center" width="20%">
                  <div class="matrix-role-header accountant">
                    <i class="fa-solid fa-calculator"></i>
                    <span>Kế toán</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <!-- Group: Đội xe & Thiết bị -->
              <tr class="group-row">
                <td colspan="4"><i class="fa-solid fa-car-side"></i> Đội xe & Thiết bị</td>
              </tr>
              <tr>
                <td>
                  <strong>Quản lý xe (cars.manage)</strong>
                  <p class="desc">Thêm mới, sửa thông số thật, quản lý bộ ảnh, bảo dưỡng và trạng thái xe</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>

              <!-- Group: Vận hành đơn hàng -->
              <tr class="group-row">
                <td colspan="4"><i class="fa-solid fa-calendar-check"></i> Vận hành đơn hàng</td>
              </tr>
              <tr>
                <td>
                  <strong>Xem đơn đặt xe (bookings.view)</strong>
                  <p class="desc">Xem danh sách đơn, chi tiết đơn, lịch xe và thông tin khách thuê</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
              </tr>
              <tr>
                <td>
                  <strong>Xử lý đơn đặt xe (bookings.manage)</strong>
                  <p class="desc">Tạo đơn hộ, duyệt lịch hẹn, hủy đơn có lưu lý do kiểm toán bắt buộc</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>
              <tr>
                <td>
                  <strong>Giao nhận xe (handovers.manage)</strong>
                  <p class="desc">Lập biên bản bàn giao và nhận trả xe, kiểm tra km, pin/nhiên liệu và lưu ảnh an toàn</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>

              <!-- Group: Tài chính & Thu chi -->
              <tr class="group-row">
                <td colspan="4"><i class="fa-solid fa-money-bill-transfer"></i> Tài chính & Thu chi</td>
              </tr>
              <tr>
                <td>
                  <strong>Xác nhận thu & hoàn tiền (payments.manage)</strong>
                  <p class="desc">Xác nhận thanh toán tiền thuê, ghi nhận cọc giữ chân, khấu trừ và hoàn trả cọc</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
              </tr>
              <tr>
                <td>
                  <strong>Báo cáo & Đối soát (reports.view)</strong>
                  <p class="desc">Xem thống kê doanh thu thuê, tỷ lệ lấp đầy xe và danh sách đơn cần đối soát hoàn tiền</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
              </tr>

              <!-- Group: Hệ thống & Bảo mật -->
              <tr class="group-row">
                <td colspan="4"><i class="fa-solid fa-shield-halved"></i> Hệ thống & Bảo mật</td>
              </tr>
              <tr>
                <td>
                  <strong>Quản trị nhân sự Admin (admins.manage)</strong>
                  <p class="desc">Tạo mới, sửa vai trò, khóa tài khoản hoặc nâng quyền khách hàng an toàn</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>
              <tr>
                <td>
                  <strong>Nhật ký thao tác (audit_logs.view)</strong>
                  <p class="desc">Tra cứu lịch sử kiểm toán, truy vết thay đổi dữ liệu nhạy cảm của các vai trò</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>
              <tr>
                <td>
                  <strong>Cấu hình hệ thống (settings.manage)</strong>
                  <p class="desc">Cài đặt quy tắc đặt cọc, tham số email, hạn mức cọc và chính sách hoàn phí</p>
                </td>
                <td class="text-center"><i class="fa-solid fa-circle-check perm-check"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
                <td class="text-center"><i class="fa-solid fa-circle-xmark perm-deny"></i></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: NHẬT KÝ THAO TÁC (AUDIT LOGS) -->
    <div v-if="activeTab === 'audit'" class="tab-content animate-in">
      <div class="content-card card">
        <!-- Audit Filters -->
        <div class="audit-filters-grid">
          <div class="form-group">
            <label>Tìm kiếm từ khóa</label>
            <div class="input-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" v-model="auditFilter.search" placeholder="Tên admin, mã đơn, xe..." @keyup.enter="fetchAuditLogs(1)" />
            </div>
          </div>
          <div class="form-group">
            <label>Hành động</label>
            <select v-model="auditFilter.action" @change="fetchAuditLogs(1)" class="form-select">
              <option value="">Tất cả hành động</option>
              <option value="admin.created">Tạo Admin mới</option>
              <option value="admin.updated">Cập nhật Admin</option>
              <option value="admin.status_updated">Đổi trạng thái Admin</option>
              <option value="admin.deleted">Xóa Admin</option>
              <option value="user.promoted">Nâng quyền Khách hàng</option>
              <option value="car.created">Thêm xe mới</option>
              <option value="car.updated">Cập nhật thông tin xe</option>
              <option value="car.status_changed">Đổi trạng thái xe</option>
              <option value="booking.status_updated">Cập nhật trạng thái đơn</option>
              <option value="payment.confirmed">Xác nhận thanh toán</option>
              <option value="booking.transaction_recorded">Ghi nhận giao dịch</option>
              <option value="booking.handover_created">Lập biên bản giao nhận</option>
              <option value="booking.deleted">Xóa đơn đặt xe</option>
            </select>
          </div>
          <div class="form-group">
            <label>Loại đối tượng thực hiện</label>
            <select v-model="auditFilter.actor_type" @change="fetchAuditLogs(1)" class="form-select">
              <option value="">Tất cả</option>
              <option value="admin">Quản trị viên</option>
              <option value="user">Khách hàng</option>
              <option value="system">Hệ thống</option>
            </select>
          </div>
          <div class="form-group">
            <label>Từ ngày</label>
            <input type="date" v-model="auditFilter.date_from" @change="fetchAuditLogs(1)" class="form-control" />
          </div>
          <div class="form-group">
            <label>Đến ngày</label>
            <input type="date" v-model="auditFilter.date_to" @change="fetchAuditLogs(1)" class="form-control" />
          </div>
          <div class="form-group filter-btn-col">
            <label>&nbsp;</label>
            <button class="btn-primary w-full" @click="fetchAuditLogs(1)">
              <i class="fa-solid fa-filter"></i> Lọc dữ liệu
            </button>
          </div>
        </div>

        <!-- Audit Table (Read-only) -->
        <div class="table-responsive mt-3">
          <table class="premium-table" v-if="auditLogs.length > 0">
            <thead>
              <tr>
                <th width="160">Thời điểm</th>
                <th>Người thực hiện</th>
                <th>Hành động</th>
                <th>Đối tượng tác động</th>
                <th>IP / Thiết bị</th>
                <th class="text-center" width="110">Chi tiết</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in auditLogs" :key="log.id">
                <td class="font-mono text-sm">{{ formatDateTime(log.created_at) }}</td>
                <td>
                  <div class="actor-cell">
                    <span class="actor-badge" :class="log.actor_type">{{ getActorTypeName(log.actor_type) }}</span>
                    <div>
                      <strong>{{ log.actor_name || 'System' }}</strong>
                      <span class="sub" v-if="log.actor_email">{{ log.actor_email }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="action-tag" :class="getActionClass(log.action)">
                    {{ formatActionLabel(log.action) }}
                  </span>
                </td>
                <td>
                  <div class="target-info">
                    <span class="target-type-badge">{{ log.target_type || 'N/A' }}</span>
                    <span class="target-name">{{ log.target_label || ('#' + log.target_id) }}</span>
                  </div>
                </td>
                <td class="font-mono text-xs text-muted">{{ log.ip_address || '127.0.0.1' }}</td>
                <td class="text-center">
                  <button 
                    v-if="log.new_values || log.old_values" 
                    class="btn-icon" 
                    @click="viewLogDetail(log)" 
                    title="Xem thay đổi chi tiết"
                  >
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <span v-else class="text-muted text-xs">—</span>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else-if="loadingAudit" class="loading-state-box">
            <div class="spinner"></div>
            <p>Đang tải dữ liệu kiểm toán...</p>
          </div>

          <div v-else class="empty-state-box">
            <i class="fa-solid fa-file-circle-exclamation"></i>
            <h4>Không có nhật ký nào phù hợp</h4>
            <p>Không có bản ghi thao tác nào khớp với các tiêu chí tìm kiếm hiện tại.</p>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper" v-if="auditPagination.total > 0">
          <span class="pagination-info">
            Hiển thị trang <strong>{{ auditPagination.current_page }}</strong> / <strong>{{ auditPagination.last_page }}</strong> (Tổng số <strong>{{ auditPagination.total }}</strong> sự kiện)
          </span>
          <div class="pagination-btns">
            <button 
              class="btn-outline btn-sm" 
              :disabled="auditPagination.current_page <= 1" 
              @click="fetchAuditLogs(auditPagination.current_page - 1)"
            >
              <i class="fa-solid fa-chevron-left"></i> Trước
            </button>
            <button 
              class="btn-outline btn-sm" 
              :disabled="auditPagination.current_page >= auditPagination.last_page" 
              @click="fetchAuditLogs(auditPagination.current_page + 1)"
            >
              Sau <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL THÊM / SỬA ADMIN -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>{{ modalType === 'create' ? 'Thêm mới Quản trị viên' : 'Cập nhật tài khoản Admin' }}</h3>
            <p class="modal-sub">Gán vai trò nghiệp vụ phù hợp để quản lý đặc quyền chặt chẽ</p>
          </div>
          <button class="btn-close" @click="showModal = false" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full">
              <label>Họ và tên <span class="required">*</span></label>
              <input type="text" v-model="currentAdmin.ho_ten" placeholder="Nhập họ tên đầy đủ..." />
            </div>
            <div class="form-group">
              <label>Email đăng nhập <span class="required">*</span></label>
              <input type="email" v-model="currentAdmin.email" placeholder="admin@domain.com" :disabled="modalType === 'update'" />
            </div>
            <div class="form-group">
              <label>Số điện thoại <span class="required">*</span></label>
              <input type="text" v-model="currentAdmin.so_dien_thoai" placeholder="09xx xxx xxx" />
            </div>
            <div class="form-group">
              <label>Vai trò nghiệp vụ <span class="required">*</span></label>
              <select v-model="currentAdmin.role" class="form-select">
                <option value="owner" :disabled="!isCurrentUserOwner">👑 Chủ hệ thống (Toàn quyền)</option>
                <option value="dispatcher">🚚 Điều phối viên (Quản lý xe & Đơn)</option>
                <option value="accountant">💳 Kế toán (Thu/hoàn cọc & Báo cáo)</option>
              </select>
              <span class="field-hint" v-if="!isCurrentUserOwner">
                Chỉ Chủ hệ thống mới có thể cấp quyền Chủ hệ thống.
              </span>
            </div>
            <div class="form-group">
              <label>Trạng thái</label>
              <select v-model="currentAdmin.tinh_trang" class="form-select" :disabled="modalType === 'update' && isLastActiveOwner(currentAdmin)">
                <option :value="1">Hoạt động bình thường</option>
                <option :value="0">Khóa tài khoản</option>
              </select>
              <span class="field-hint text-danger" v-if="modalType === 'update' && isLastActiveOwner(currentAdmin)">
                Không thể khóa Chủ hệ thống duy nhất đang hoạt động.
              </span>
            </div>
            <div class="form-group full">
              <label>Mật khẩu {{ modalType === 'update' ? '(Để trống nếu không đổi)' : '*' }}</label>
              <input type="password" v-model="currentAdmin.password" placeholder="Tối thiểu 6 ký tự..." />
            </div>
            <div class="form-group full">
              <label>Địa chỉ công tác / ghi chú</label>
              <input type="text" v-model="currentAdmin.dia_chi" placeholder="Nhập địa chỉ..." />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showModal = false">Hủy bỏ</button>
          <button class="btn-primary" @click="saveAdmin" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            {{ saving ? 'Đang lưu...' : (modalType === 'create' ? 'Xác nhận thêm' : 'Lưu thay đổi') }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL XEM CHI TIẾT AUDIT LOG DIFF -->
    <div v-if="selectedLog" class="modal-overlay" @click.self="selectedLog = null">
      <div class="modal-content card diff-modal animate-in">
        <div class="modal-header">
          <div>
            <h3>Chi Tiết Thay Đổi Kiểm Toán #{{ selectedLog.id }}</h3>
            <p class="modal-sub">Hành động: <strong>{{ selectedLog.action }}</strong> | Thời gian: {{ formatDateTime(selectedLog.created_at) }}</p>
          </div>
          <button class="btn-close" @click="selectedLog = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="diff-grid">
            <div class="diff-col" v-if="selectedLog.old_values">
              <h4><i class="fa-solid fa-clock-rotate-left"></i> Dữ liệu trước thay đổi</h4>
              <pre class="json-box">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
            </div>
            <div class="diff-col" v-if="selectedLog.new_values">
              <h4><i class="fa-solid fa-check-double"></i> Dữ liệu cập nhật mới</h4>
              <pre class="json-box new">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
            </div>
          </div>
          <p class="sanitized-note">
            <i class="fa-solid fa-shield-halved"></i> Các trường nhạy cảm (mật khẩu, token, session) đã được tự động loại trừ an toàn theo chính sách bảo mật hệ thống.
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="selectedLog = null">Đóng</button>
        </div>
      </div>
    </div>

    <!-- MODAL XÓA ADMIN -->
    <div v-if="adminToDelete" class="modal-overlay" @click.self="adminToDelete = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div>
            <h3>Xác nhận xóa tài khoản Admin</h3>
            <p class="modal-sub">Hành động này sẽ thu hồi quyền quản trị của tài khoản vĩnh viễn</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa tài khoản quản trị sau?</p>
          <div class="highlight-box">
            <strong>{{ adminToDelete.ho_ten }}</strong>
            <span>Email: {{ adminToDelete.email }} | Vai trò: {{ getRoleName(adminToDelete.role) }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="adminToDelete = null">Hủy</button>
          <button class="btn-danger" @click="executeDeleteAdmin" :disabled="deleting">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ deleting ? 'Đang xóa...' : 'Đồng ý xóa' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const activeTab = ref('accounts');
const admins = ref([]);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const searchQuery = ref('');
const filterRole = ref('');
const showModal = ref(false);
const modalType = ref('create');
const adminToDelete = ref(null);

const currentAdmin = ref({
    ho_ten: '',
    email: '',
    password: '',
    so_dien_thoai: '',
    dia_chi: '',
    role: 'dispatcher',
    tinh_trang: 1
});

// Audit log state
const auditLogs = ref([]);
const loadingAudit = ref(false);
const selectedLog = ref(null);
const auditPagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
});
const auditFilter = ref({
    search: '',
    action: '',
    actor_type: '',
    date_from: '',
    date_to: ''
});

// Current User Role
const currentUser = computed(() => {
    try {
        return JSON.parse(localStorage.getItem('user') || '{}');
    } catch (e) {
        return {};
    }
});

const isCurrentUserOwner = computed(() => {
    return currentUser.value.admin_role === 'owner' || currentUser.value.role === 'admin';
});

const switchTab = (tab) => {
    activeTab.value = tab;
    if (tab === 'audit' && auditLogs.value.length === 0) {
        fetchAuditLogs(1);
    }
};

const fetchAdmins = async () => {
    loading.value = true;
    try {
        const res = await baseRequest.get('admin/admins/data');
        if (res.data && res.data.status) {
            admins.value = res.data.data;
        } else {
            toaster.error(res.data?.message || "Không thể tải danh sách tài khoản Admin.");
        }
    } catch (err) {
        toaster.error("Không thể tải danh sách tài khoản Admin!");
    } finally {
        loading.value = false;
    }
};

const fetchAuditLogs = async (page = 1) => {
    loadingAudit.value = true;
    try {
        const params = {
            page,
            per_page: 15,
            search: auditFilter.value.search || undefined,
            action: auditFilter.value.action || undefined,
            actor_type: auditFilter.value.actor_type || undefined,
            date_from: auditFilter.value.date_from || undefined,
            date_to: auditFilter.value.date_to || undefined
        };
        const res = await baseRequest.get('admin/audit-logs', { params });
        if (res.data && res.data.status) {
            auditLogs.value = res.data.data;
            auditPagination.value = res.data.pagination;
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || "Không có quyền xem nhật ký thao tác!");
    } finally {
        loadingAudit.value = false;
    }
};

const filteredAdmins = computed(() => {
    return admins.value.filter(a => {
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery = !q || 
            (a.ho_ten && a.ho_ten.toLowerCase().includes(q)) || 
            (a.email && a.email.toLowerCase().includes(q));
        const matchesRole = !filterRole.value || a.role === filterRole.value;
        return matchesQuery && matchesRole;
    });
});

const isLastActiveOwner = (admin) => {
    if (admin.role !== 'owner' || Number(admin.tinh_trang) !== 1) {
        return false;
    }
    const activeOwners = admins.value.filter(a => a.role === 'owner' && Number(a.tinh_trang) === 1);
    return activeOwners.length <= 1;
};

const getRoleName = (role) => {
    switch (role) {
        case 'owner': return 'Chủ hệ thống';
        case 'dispatcher': return 'Điều phối viên';
        case 'accountant': return 'Kế toán';
        default: return 'Quản trị viên';
    }
};

const getRoleIcon = (role) => {
    switch (role) {
        case 'owner': return 'fa-solid fa-crown';
        case 'dispatcher': return 'fa-solid fa-truck-ramp-box';
        case 'accountant': return 'fa-solid fa-calculator';
        default: return 'fa-solid fa-user-shield';
    }
};

const getActorTypeName = (type) => {
    switch (type) {
        case 'admin': return 'Quản trị';
        case 'user': return 'Khách hàng';
        case 'system': return 'Hệ thống';
        default: return type;
    }
};

const formatActionLabel = (action) => {
    const map = {
        'admin.created': 'Thêm mới Admin',
        'admin.updated': 'Cập nhật Admin',
        'admin.status_updated': 'Đổi trạng thái Admin',
        'admin.deleted': 'Xóa tài khoản Admin',
        'user.promoted': 'Nâng quyền Khách hàng',
        'car.created': 'Thêm mới xe',
        'car.updated': 'Cập nhật thông tin xe',
        'car.status_changed': 'Đổi trạng thái xe',
        'car.deleted': 'Xóa xe',
        'booking.status_updated': 'Đổi trạng thái đơn',
        'payment.confirmed': 'Xác nhận thanh toán',
        'booking.transaction_recorded': 'Ghi nhận giao dịch',
        'booking.handover_created': 'Lập biên bản giao nhận',
        'booking.deleted': 'Xóa đơn đặt xe'
    };
    return map[action] || action;
};

const getActionClass = (action) => {
    if (action.includes('created') || action.includes('promoted') || action.includes('confirmed')) return 'success';
    if (action.includes('deleted')) return 'danger';
    if (action.includes('status')) return 'warning';
    return 'info';
};

const viewLogDetail = (log) => {
    selectedLog.value = log;
};

const openModal = (type, admin = null) => {
    modalType.value = type;
    if (type === 'create') {
        currentAdmin.value = { 
            ho_ten: '', 
            email: '', 
            password: '', 
            so_dien_thoai: '', 
            dia_chi: '', 
            role: 'dispatcher', 
            tinh_trang: 1 
        };
    } else {
        currentAdmin.value = { ...admin, password: '', role: admin.role || 'dispatcher' };
    }
    showModal.value = true;
};

const saveAdmin = async () => {
    if (!currentAdmin.value.ho_ten?.trim()) {
        toaster.error("Vui lòng nhập họ tên quản trị viên!");
        return;
    }
    if (!currentAdmin.value.email?.trim()) {
        toaster.error("Vui lòng nhập email quản trị viên!");
        return;
    }
    if (modalType.value === 'create' && (!currentAdmin.value.password || currentAdmin.value.password.length < 6)) {
        toaster.error("Mật khẩu tài khoản mới tối thiểu 6 ký tự!");
        return;
    }

    saving.value = true;
    try {
        const url = modalType.value === 'create' ? 'admin/admins/create' : 'admin/admins/update';
        const res = await baseRequest.post(url, currentAdmin.value);
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Lưu tài khoản Admin thành công!");
            showModal.value = false;
            await fetchAdmins();
        } else {
            toaster.error(res.data?.message || "Có lỗi xảy ra khi lưu!");
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || "Lỗi khi kết nối với máy chủ!");
    } finally {
        saving.value = false;
    }
};

const toggleStatus = async (admin) => {
    try {
        const res = await baseRequest.post('admin/admins/status', { id: admin.id });
        if (res.data && res.data.status) {
            toaster.success(res.data.message || "Đã cập nhật trạng thái hoạt động!");
            admin.tinh_trang = Number(admin.tinh_trang) === 1 ? 0 : 1;
        } else {
            toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
            await fetchAdmins();
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || "Lỗi hệ thống khi cập nhật trạng thái.");
        await fetchAdmins();
    }
};

const requestDelete = (admin) => {
    adminToDelete.value = admin;
};

const executeDeleteAdmin = async () => {
    if (!adminToDelete.value) return;
    const target = adminToDelete.value;
    deleting.value = true;
    try {
        const res = await baseRequest.post('admin/admins/delete', { id: target.id });
        if (res.data && res.data.status) {
            toaster.success(res.data.message || `Đã xóa tài khoản Admin ${target.ho_ten}!`);
            admins.value = admins.value.filter(a => a.id !== target.id);
            adminToDelete.value = null;
            await fetchAdmins();
        } else {
            toaster.error(res.data?.message || "Không thể xóa tài khoản này!");
        }
    } catch (err) {
        toaster.error(err.response?.data?.message || "Lỗi hệ thống khi xóa tài khoản.");
    } finally {
        deleting.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('vi-VN');
};

const formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.toLocaleDateString('vi-VN')} ${d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
};

onMounted(() => {
    fetchAdmins();
});
</script>

<style scoped>
.admin-page { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; }
.header-main { display: flex; align-items: center; gap: 16px; }
.header-icon { 
  width: 52px; height: 52px; 
  background: white; border-radius: var(--radius-md); 
  display: flex; align-items: center; justify-content: center; 
  font-size: 1.4rem; color: var(--color-primary); 
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.page-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 2px; }
.page-title span { color: var(--color-primary); }
.page-subtitle { color: var(--color-text-muted); font-size: 0.9rem; }

/* Tabs Navigation */
.tabs-nav-wrapper {
  background: #ffffff;
  padding: 6px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.tabs-nav {
  display: flex;
  gap: 8px;
}

.tab-btn {
  padding: 10px 18px;
  border-radius: var(--radius-md);
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all var(--duration-fast);
}

.tab-btn:hover {
  background: var(--bg-surface-sub);
  color: var(--color-text-main);
}

.tab-btn.active {
  background: var(--color-primary);
  color: #ffffff;
}

.tab-count {
  font-size: 0.75rem;
  padding: 2px 7px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.1);
}

.tab-btn.active .tab-count {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* Card & Table */
.content-card {
  padding: 24px;
  background: #ffffff;
}

.table-tools {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 360px;
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
  padding: 9px 12px 9px 38px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  font-size: 0.885rem;
}

/* Role Badges */
.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: var(--radius-full);
  font-size: 0.775rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.role-badge.owner {
  background: rgba(147, 51, 234, 0.12);
  color: #7e22ce;
  border: 1px solid rgba(147, 51, 234, 0.25);
}

.role-badge.dispatcher {
  background: rgba(2, 132, 199, 0.12);
  color: #0369a1;
  border: 1px solid rgba(2, 132, 199, 0.25);
}

.role-badge.accountant {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
  border: 1px solid rgba(16, 185, 129, 0.25);
}

.avatar-circle.owner { background: #7e22ce; color: #ffffff; }
.avatar-circle.dispatcher { background: #0284c7; color: #ffffff; }
.avatar-circle.accountant { background: #059669; color: #ffffff; }

.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; }
.user-profile-cell .info { display: flex; flex-direction: column; }
.user-profile-cell .name { font-weight: 700; color: var(--color-text-main); font-size: 0.9rem; }
.user-profile-cell .id { font-size: 0.75rem; color: var(--color-text-muted); }

.contact-info { display: flex; flex-direction: column; gap: 3px; font-size: 0.825rem; color: var(--color-text-muted); }
.contact-info span { display: flex; align-items: center; gap: 6px; }

/* Switch */
.form-switch-wrapper { display: flex; align-items: center; gap: 8px; }
.form-check-input { width: 36px; height: 18px; cursor: pointer; accent-color: var(--color-primary); }
.status-label { font-size: 0.8rem; font-weight: 600; }
.status-label.active { color: #059669; }
.status-label.inactive { color: #dc2626; }

/* Actions */
.action-buttons { display: flex; gap: 6px; justify-content: center; }
.btn-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); border: 1px solid var(--color-border); background: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--color-text-muted); transition: all var(--duration-fast); }
.btn-icon:hover:not(:disabled) { background: #f1f5f9; color: var(--color-primary); }
.btn-icon.danger:hover:not(:disabled) { background: rgba(239, 68, 68, 0.1); color: #dc2626; border-color: rgba(239, 68, 68, 0.2); }
.btn-icon:disabled { opacity: 0.35; cursor: not-allowed; }

/* Matrix Table */
.matrix-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border); }
.matrix-header h3 { font-size: 1.25rem; font-weight: 800; margin-bottom: 4px; }
.matrix-header p { font-size: 0.885rem; color: var(--color-text-muted); }

.matrix-table th { background: #f8fafc; }
.matrix-role-header { display: flex; flex-direction: column; align-items: center; gap: 4px; font-weight: 700; font-size: 0.85rem; }
.matrix-role-header.owner i { color: #7e22ce; font-size: 1.1rem; }
.matrix-role-header.dispatcher i { color: #0284c7; font-size: 1.1rem; }
.matrix-role-header.accountant i { color: #059669; font-size: 1.1rem; }

.matrix-table .group-row td {
  background: var(--bg-surface-sub);
  font-weight: 800;
  font-size: 0.85rem;
  color: var(--color-text-main);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 10px 16px;
}

.matrix-table .desc { font-size: 0.775rem; color: var(--color-text-muted); margin-top: 2px; }
.perm-check { color: #059669; font-size: 1.2rem; }
.perm-deny { color: #cbd5e1; font-size: 1.2rem; }

/* Audit Table */
.audit-filters-grid {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1.2fr 1fr 1fr 1fr;
  gap: 12px;
  align-items: flex-end;
}

.actor-cell { display: flex; align-items: center; gap: 8px; }
.actor-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
  text-transform: uppercase;
}
.actor-badge.admin { background: #e0e7ff; color: #4338ca; }
.actor-badge.user { background: #ecfdf5; color: #047857; }
.actor-badge.system { background: #f1f5f9; color: #475569; }

.action-tag {
  display: inline-block;
  padding: 3px 8px;
  border-radius: var(--radius-sm);
  font-size: 0.775rem;
  font-weight: 600;
}
.action-tag.success { background: #ecfdf5; color: #047857; }
.action-tag.warning { background: #fffbeb; color: #b45309; }
.action-tag.danger { background: #fef2f2; color: #b91c1c; }
.action-tag.info { background: #f0f9ff; color: #0369a1; }

.target-info { display: flex; align-items: center; gap: 6px; }
.target-type-badge { font-size: 0.7rem; padding: 2px 5px; background: var(--bg-surface-sub); border-radius: 4px; color: var(--color-text-muted); }
.target-name { font-weight: 600; font-size: 0.85rem; }

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}
.pagination-info { font-size: 0.85rem; color: var(--color-text-muted); }
.pagination-btns { display: flex; gap: 8px; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 999; }
.modal-content { width: 100%; max-width: 580px; background: #ffffff; padding: 28px; border-radius: var(--radius-xl); max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid var(--color-border); padding-bottom: 14px; }
.modal-header h3 { font-size: 1.25rem; font-weight: 800; }
.modal-sub { font-size: 0.825rem; color: var(--color-text-muted); }
.btn-close { border: none; background: transparent; font-size: 1.1rem; color: var(--color-text-muted); cursor: pointer; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 4px; color: var(--color-text-main); }
.form-group input, .form-group select { width: 100%; padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border); font-size: 0.875rem; }
.required { color: #dc2626; }
.field-hint { font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px; display: block; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }

/* Diff Modal */
.diff-modal { max-width: 760px; }
.diff-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.diff-col h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; color: var(--color-text-main); display: flex; align-items: center; gap: 6px; }
.json-box { background: #0f172a; color: #f8fafc; padding: 12px; border-radius: var(--radius-md); font-size: 0.75rem; font-family: monospace; max-height: 260px; overflow-y: auto; }
.json-box.new { background: #064e3b; color: #ecfdf5; }
.sanitized-note { font-size: 0.775rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 6px; }

/* Delete Modal */
.delete-modal { max-width: 440px; text-align: center; }
.delete-icon-wrapper { width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 14px; }
.highlight-box { background: var(--bg-surface-sub); padding: 12px; border-radius: var(--radius-md); margin-top: 12px; display: flex; flex-direction: column; gap: 4px; }

.btn-danger { background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
.btn-danger:hover { background: #b91c1c; }

@media (max-width: 900px) {
  .audit-filters-grid { grid-template-columns: 1fr; }
  .diff-grid { grid-template-columns: 1fr; }
}
</style>
