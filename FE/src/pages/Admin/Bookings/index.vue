<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Đơn Đặt Xe</span></h1>
          <p class="page-subtitle">Duyệt yêu cầu thuê xe, kiểm tra biên lai và xác nhận thanh toán minh bạch.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-outline export-btn" @click="handleExportCsv" :disabled="exporting" title="Xuất danh sách đơn đặt xe ra file CSV UTF-8">
          <i class="fa-solid fa-file-csv" :class="{ 'fa-spin': exporting }"></i>
          <span>{{ exporting ? 'Đang xuất...' : 'Xuất CSV' }}</span>
        </button>
        <router-link to="/admin/cars" class="btn-primary">
          <i class="fa-solid fa-calendar-plus"></i> Đặt xe hộ khách
        </router-link>
        <button class="btn-outline" @click="fetchBookings" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i> Làm mới dữ liệu
        </button>
      </div>
    </header>

    <!-- Summary Statistics -->
    <section class="summary-section">
      <div class="summary-card card">
        <div class="card-icon blue"><i class="fa-solid fa-list-check"></i></div>
        <div class="card-info">
          <span class="label">Tổng đơn nhận</span>
          <h3 class="value">{{ bookings.length }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="card-info">
          <span class="label">Chờ duyệt thuê</span>
          <h3 class="value">{{ pendingCount }}</h3>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon cyan"><i class="fa-solid fa-car-side"></i></div>
        <div class="card-info">
          <span class="label">Đang thuê (Lưu hành)</span>
          <h3 class="value">{{ inProgressCount }}</h3>
          <small class="sub-label">Đã bàn giao xe</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="card-info">
          <span class="label">Đã hoàn tất</span>
          <h3 class="value">{{ completedCount }}</h3>
          <small class="sub-label">Đóng đơn</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon purple"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="card-info">
          <span class="label">Doanh thu đã thu</span>
          <h3 class="value">{{ formatCurrency(realizedRevenue) }}</h3>
          <small class="sub-label">Hợp lệ (Đã đối soát)</small>
        </div>
      </div>
      <div class="summary-card card">
        <div class="card-icon cyan"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="card-info">
          <span class="label">Tiền cọc đang giữ</span>
          <h3 class="value">{{ formatCurrency(totalDepositHeld) }}</h3>
          <small class="sub-label">Ký quỹ (không tính DT)</small>
        </div>
      </div>
      <div 
        class="summary-card card pointer" 
        :class="{ 'card-discrepancy-active': filterPayment === 'refund_review' || filterPayment === 'missing_audit' }"
        @click="filterPayment = (filterPayment === 'refund_review' ? '' : 'refund_review')"
        title="Bấm để lọc các đơn đã hủy nhưng đã thu tiền hoặc cần đối soát"
      >
        <div class="card-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="card-info">
          <span class="label">Cần đối chiếu</span>
          <h3 class="value" :class="reconciliationSummary.total_discrepancies > 0 ? 'text-danger' : ''">
            {{ reconciliationSummary.total_discrepancies || 0 }}
          </h3>
          <small class="sub-label">Đơn hủy thu tiền / bill chờ duyệt</small>
        </div>
      </div>
    </section>

    <!-- Bookings Table Card -->
    <div class="content-card card">
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo mã đơn, khách hàng, xe..." v-model="searchQuery" />
        </div>
        <div class="filter-group">
          <div class="select-wrapper">
            <i class="fa-solid fa-filter"></i>
            <select v-model="filterStatus" class="form-select">
              <option value="">Tất cả trạng thái thuê</option>
              <option value="0">Chờ duyệt</option>
              <option value="1">Đã duyệt (Chờ giao)</option>
              <option value="3">Đang thuê (Đã giao xe)</option>
              <option value="4">Đã nhận trả xe</option>
              <option value="5">Hoàn tất đơn</option>
              <option value="2">Đã hủy</option>
            </select>
          </div>
          <div class="select-wrapper">
            <i class="fa-solid fa-credit-card"></i>
            <select v-model="filterPayment" class="form-select">
              <option value="">Tất cả thanh toán</option>
              <option value="1">Đã thanh toán đủ</option>
              <option value="2">Đã thanh toán 1 phần</option>
              <option value="has_deposit">Có tiền cọc đang giữ</option>
              <option value="verifying">Chờ đối soát biên lai</option>
              <option value="unpaid">Chưa thanh toán</option>
              <option value="refund_review">Đã thu tiền (Cần xử lý hoàn tiền)</option>
              <option value="missing_audit">Dữ liệu cũ thiếu kiểm toán</option>
            </select>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table" v-if="filteredBookings.length > 0">
          <thead>
            <tr>
              <th width="110">Mã đơn</th>
              <th>Khách hàng</th>
              <th>Phương tiện</th>
              <th width="140">Biên lai tải lên <small style="display:block;font-size:0.65rem;color:var(--color-text-muted);font-weight:normal;">(Chờ đối soát)</small></th>
              <th>Lịch trình thuê</th>
              <th>Tổng tiền</th>
              <th width="170">Trạng thái thanh toán</th>
              <th width="120">Trạng thái thuê</th>
              <th width="220" class="text-center">Thao tác xử lý</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td><span class="booking-id">#{{ booking.ma_dat_xe }}</span></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar">{{ booking.user_name ? booking.user_name.charAt(0).toUpperCase() : 'U' }}</div>
                  <div class="user-info">
                    <span class="name">{{ booking.user_name || 'Khách vãng lai' }}</span>
                    <span class="email"><i class="fa-solid fa-envelope"></i> {{ booking.user_email || 'Chưa có email' }}</span>
                    <span class="phone" v-if="booking.user_phone"><i class="fa-solid fa-phone"></i> {{ booking.user_phone }}</span>
                    <span v-if="booking.created_by_admin_name" class="badge-creator-admin" title="Đơn được nhân viên/admin đặt hộ">
                      <i class="fa-solid fa-user-shield"></i> Hộ bởi: {{ booking.created_by_admin_name }}
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <span class="car-title"><i class="fa-solid fa-car"></i> {{ booking.car_name }}</span>
              </td>
              <td>
                <div v-if="booking.bill_image" class="bill-thumb-container" @click="viewFullImage(booking.bill_image, booking)" title="Bấm để xem ảnh phóng to & đối soát">
                  <img :src="getBillUrl(booking.bill_image, booking)" alt="biên lai" class="bill-thumb" />
                  <div class="overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
                </div>
                <span v-else class="no-bill"><i class="fa-solid fa-circle-exclamation"></i> Chưa tải bill</span>
              </td>
              <td>
                <div class="date-cell">
                  <div><i class="fa-solid fa-calendar-day"></i> Nhận: {{ formatDate(booking.start_date) }}</div>
                  <div><i class="fa-solid fa-calendar-check"></i> Trả: {{ formatDate(booking.end_date) }}</div>
                </div>
              </td>
              <td class="price-cell">
                <span class="price">{{ formatCurrency(booking.tong_tien) }}</span>
                <div v-if="booking.financial_summary?.has_transactions" class="price-breakdown-sub">
                  <span v-if="booking.financial_summary.paid_rent_amount > 0" class="sub-paid text-success">
                    Đã thu: {{ formatCurrency(booking.financial_summary.paid_rent_amount) }}
                  </span>
                  <span v-if="booking.financial_summary.remaining_rent_amount > 0" class="sub-remaining text-warning">
                    Còn nợ: {{ formatCurrency(booking.financial_summary.remaining_rent_amount) }}
                  </span>
                </div>
              </td>
              <td>
                <div class="pay-cell-wrap">
                  <span class="pay-badge" :class="getPaymentStatus(booking).class">
                    <i class="fa-solid" :class="getPaymentStatus(booking).icon"></i>
                    {{ getPaymentStatus(booking).text }}
                  </span>
                  <span v-if="booking.financial_summary?.deposit_held_amount > 0" class="deposit-tag" title="Tiền cọc bảo đảm đang giữ">
                    <i class="fa-solid fa-shield-halved"></i> Cọc: {{ formatCurrency(booking.financial_summary.deposit_held_amount) }}
                  </span>
                  <span v-if="Number(booking.is_thanh_toan) === 1 && booking.confirmed_by_name" class="audit-note" title="Người duyệt thu">
                    <i class="fa-solid fa-user-check"></i> {{ booking.confirmed_by_name }}
                  </span>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="'status-' + booking.tinh_trang">
                  {{ getStatusText(booking.tinh_trang) }}
                </span>
              </td>
              <td class="actions-cell">
                <div class="action-btn-group">
                  <!-- Xem chi tiết -->
                  <button class="btn-action view" @click="openDetailModal(booking)" title="Xem chi tiết & lịch trình đơn">
                    <i class="fa-solid fa-eye"></i>
                  </button>

                  <!-- Sổ giao dịch & Tiền cọc -->
                  <button 
                    class="btn-action ledger" 
                    @click="openLedgerModal(booking)" 
                    title="Sổ giao dịch & Quản lý tiền cọc"
                  >
                    <i class="fa-solid fa-receipt"></i>
                    <span>Sổ quỹ</span>
                  </button>

                  <!-- 0 -> 1: Duyệt đơn -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 0" 
                    class="btn-action confirm" 
                    @click="updateRentalStatus(booking, 1)" 
                    title="Duyệt đơn thuê xe"
                  >
                    <i class="fa-solid fa-check"></i>
                    <span>Duyệt</span>
                  </button>

                  <!-- 1 -> 3: Bàn giao xe -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 1" 
                    class="btn-action deliver" 
                    @click="openHandoverModal(booking, 'delivery')" 
                    title="Lập biên bản bàn giao xe cho khách"
                  >
                    <i class="fa-solid fa-key"></i>
                    <span>Giao xe</span>
                  </button>

                  <!-- 3 -> 4: Nhận trả xe -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 3" 
                    class="btn-action return-car" 
                    @click="openHandoverModal(booking, 'return')" 
                    title="Lập biên bản nhận trả xe từ khách"
                  >
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    <span>Nhận trả</span>
                  </button>

                  <!-- Xem Biên Bản Bàn Giao / Nhận Trả -->
                  <button 
                    v-if="booking.has_delivery || booking.has_return || [3, 4, 5].includes(Number(booking.tinh_trang))" 
                    class="btn-action inspection" 
                    @click="openInspectionModal(booking)" 
                    title="Xem & In Biên bản Bàn giao / Nhận trả"
                  >
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Biên bản</span>
                  </button>

                  <!-- 4 -> 5: Hoàn tất đơn -->
                  <button 
                    v-if="Number(booking.tinh_trang) === 4" 
                    class="btn-action complete" 
                    @click="updateRentalStatus(booking, 5)" 
                    title="Hoàn tất và đóng đơn thuê xe"
                  >
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Hoàn tất</span>
                  </button>

                  <!-- Xác nhận đã thanh toán độc lập sau khi đối soát sao kê -->
                  <button 
                    v-if="Number(booking.is_thanh_toan) === 0 && Number(booking.tinh_trang) !== 2" 
                    class="btn-action pay" 
                    @click="openConfirmPaymentModal(booking)" 
                    title="Đối soát sao kê & Duyệt thu tiền"
                  >
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Thu tiền</span>
                  </button>

                  <!-- Hủy đơn thuê: Cho phép hủy khi đơn chưa hoàn tất hoặc chưa hủy (0, 1, 3) -->
                  <button 
                    v-if="[0, 1, 3].includes(Number(booking.tinh_trang))" 
                    class="btn-action cancel" 
                    @click="openCancelModal(booking)" 
                    :title="Number(booking.is_thanh_toan) === 1 ? 'Hủy đơn thuê (Đã thu tiền, cần xử lý hoàn tiền)' : 'Hủy đơn thuê này'"
                  >
                    <i class="fa-solid fa-ban"></i>
                    <span>Hủy</span>
                  </button>

                  <!-- Xóa đơn: Chặn xóa thay hủy -->
                  <button 
                    class="btn-action delete" 
                    @click="requestDeleteBooking(booking)" 
                    :title="Number(booking.tinh_trang) !== 2 ? 'Không thể xóa đơn đang hoạt động' : 'Xóa đơn đã hủy'"
                  >
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-folder-open"></i>
          <h4>Không tìm thấy đơn đặt xe</h4>
          <p>Không có đơn nào khớp với bộ lọc hoặc từ khóa tìm kiếm hiện tại.</p>
        </div>

        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
        </div>
      </div>
    </div>

    <!-- Booking Detail Modal -->
    <div v-if="selectedBooking" class="modal-overlay" @click.self="selectedBooking = null">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>Chi Tiết Đơn Đặt Xe #{{ selectedBooking.ma_dat_xe }}</h3>
            <p class="modal-sub">Ngày đặt: {{ formatDate(selectedBooking.created_at) }}</p>
          </div>
          <button class="btn-close" @click="selectedBooking = null" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-section">
              <h4>Thông tin khách hàng</h4>
              <p><strong>Họ tên:</strong> {{ selectedBooking.user_name || 'N/A' }}</p>
              <p><strong>Email:</strong> {{ selectedBooking.user_email || 'N/A' }}</p>
              <p><strong>Mã khách:</strong> #USR-{{ selectedBooking.user_id }}</p>
            </div>
            <div class="detail-section">
              <h4>Phương tiện & Chi phí</h4>
              <p><strong>Tên xe:</strong> {{ selectedBooking.car_name }}</p>
              <p><strong>Mã xe:</strong> #CAR-{{ selectedBooking.car_id }}</p>
              <p><strong>Tổng thanh toán:</strong> <span class="text-primary font-bold">{{ formatCurrency(selectedBooking.tong_tien) }}</span></p>
            </div>
            <div class="detail-section full">
              <h4>Lịch trình & Địa chỉ nhận xe</h4>
              <p><strong>Nhận xe:</strong> {{ formatDate(selectedBooking.start_date) }}</p>
              <p><strong>Trả xe:</strong> {{ formatDate(selectedBooking.end_date) }}</p>
              <p><strong>Địa điểm:</strong> {{ selectedBooking.dia_diem || 'Giao nhận tại showroom' }}</p>
            </div>
            <div class="detail-section full">
              <h4>Trạng thái đơn hàng & Thanh toán</h4>
              <div v-if="Number(selectedBooking.tinh_trang) === 2 && Number(selectedBooking.is_thanh_toan) === 1" class="financial-alert-box mb-3">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                  <strong>Cảnh báo tài chính: Đơn đã hủy nhưng đã thu tiền</strong>
                  <p>Hệ thống không tự động hoàn tiền. Kế toán cần đối soát sao kê ngân hàng và thực hiện hoàn tiền thủ công cho khách theo quy định.</p>
                </div>
              </div>
              <div class="status-summary-row">
                <div>
                  <span class="label-muted">Trạng thái thuê:</span>
                  <span class="status-badge" :class="'status-' + selectedBooking.tinh_trang">
                    {{ getStatusText(selectedBooking.tinh_trang) }}
                  </span>
                </div>
                <div>
                  <span class="label-muted">Trạng thái thanh toán:</span>
                  <span class="pay-badge" :class="getPaymentStatus(selectedBooking).class">
                    <i class="fa-solid" :class="getPaymentStatus(selectedBooking).icon"></i>
                    {{ getPaymentStatus(selectedBooking).text }}
                  </span>
                  <div v-if="Number(selectedBooking.is_thanh_toan) === 1" class="audit-detail mt-2">
                    <div v-if="selectedBooking.confirmed_by_name"><i class="fa-solid fa-user-check"></i> <strong>Duyệt bởi:</strong> {{ selectedBooking.confirmed_by_name }}</div>
                    <div v-if="selectedBooking.payment_confirmed_at"><i class="fa-solid fa-clock"></i> <strong>Thời điểm:</strong> {{ formatDate(selectedBooking.payment_confirmed_at) }}</div>
                    <div v-if="selectedBooking.payment_note" class="text-muted"><i class="fa-solid fa-note-sticky"></i> <em>{{ selectedBooking.payment_note }}</em></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vòng đời & Timeline kiểm toán -->
            <div class="detail-section full" v-if="selectedBooking.timeline && selectedBooking.timeline.length > 0">
              <h4><i class="fa-solid fa-timeline"></i> Tiến trình vòng đời đơn thuê</h4>
              <div class="admin-timeline">
                <div 
                  v-for="event in selectedBooking.timeline" 
                  :key="event.key" 
                  class="timeline-node" 
                  :class="{ 'active': event.completed, 'cancelled': event.is_cancel }"
                >
                  <div class="node-icon">
                    <i v-if="event.is_cancel" class="fa-solid fa-ban"></i>
                    <i v-else-if="event.completed" class="fa-solid fa-check"></i>
                    <i v-else class="fa-solid fa-circle"></i>
                  </div>
                  <div class="node-content">
                    <div class="node-header">
                      <strong class="node-title">{{ event.title }}</strong>
                      <span v-if="event.timestamp" class="node-time">{{ event.timestamp }}</span>
                    </div>
                    <p class="node-desc">{{ event.description }}</p>
                    <span v-if="event.operator" class="node-operator">
                      <i class="fa-solid fa-user-shield"></i> {{ event.operator }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Ghi chú kiểm toán / Lý do hủy nếu có -->
            <div class="detail-section full" v-if="selectedBooking.status_note || selectedBooking.cancel_reason">
              <h4><i class="fa-solid fa-clipboard-list"></i> Ghi chú kiểm toán & Lý do hủy</h4>
              <div v-if="selectedBooking.cancel_reason" class="financial-alert-box mb-2">
                <i class="fa-solid fa-ban"></i>
                <div>
                  <strong>Lý do hủy đơn:</strong>
                  <p>{{ selectedBooking.cancel_reason }}</p>
                  <small v-if="selectedBooking.cancelled_by_type" style="display:block;margin-top:4px;">
                    Hủy bởi: {{ selectedBooking.cancelled_by_type === 'admin' ? 'Quản trị viên' : 'Khách hàng' }}
                    <span v-if="selectedBooking.cancelled_at"> vào lúc {{ formatDate(selectedBooking.cancelled_at) }}</span>
                  </small>
                </div>
              </div>
              <div v-if="selectedBooking.status_note" class="note-audit-box">
                <strong><i class="fa-solid fa-clipboard-check"></i> Ghi chú bàn giao / nhận xe:</strong>
                <pre class="note-pre-text">{{ selectedBooking.status_note }}</pre>
              </div>
            </div>
            <div class="detail-section full" v-if="selectedBooking.bill_image">
              <h4>Ảnh chứng từ khách tải lên (Cần đối soát)</h4>
              <div class="receipt-audit-warning mini mb-2">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Ảnh biên lai là chứng từ tham khảo do khách tải lên. Kế toán/Admin cần đối soát số dư trên app ngân hàng trước khi xác nhận.</span>
              </div>
              <div class="bill-preview-large" @click="viewFullImage(selectedBooking.bill_image, selectedBooking)">
                <img :src="getBillUrl(selectedBooking.bill_image, selectedBooking)" alt="Biên lai" />
                <span class="click-hint"><i class="fa-solid fa-magnifying-glass-plus"></i> Bấm để phóng to biên lai & đối soát</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button 
            v-if="Number(selectedBooking.is_thanh_toan) === 0 && Number(selectedBooking.tinh_trang) !== 2" 
            class="btn-primary" 
            @click="openConfirmPaymentModal(selectedBooking)"
          >
            <i class="fa-solid fa-file-invoice-dollar"></i> Duyệt thu tiền đơn này
          </button>
          <button class="btn-outline" @click="selectedBooking = null">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Image Viewer Modal with Zoom & Operational Warning -->
    <div v-if="fullImageUrl" class="modal-overlay" @click.self="fullImageUrl = null">
        <div class="image-viewer-container" @click.stop>
            <div class="viewer-toolbar">
              <button class="tool-btn" @click="zoomLevel = Math.max(zoomLevel - 0.25, 0.5)" title="Thu nhỏ">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
              </button>
              <span class="zoom-value">{{ Math.round(zoomLevel * 100) }}%</span>
              <button class="tool-btn" @click="zoomLevel = Math.min(zoomLevel + 0.25, 3)" title="Phóng to">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
              </button>
              <button class="tool-btn" @click="zoomLevel = 1" title="Khôi phục kích thước chuẩn">
                <i class="fa-solid fa-compress"></i>
              </button>
              <button class="tool-btn close-tool" @click="fullImageUrl = null" title="Đóng">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>

            <!-- Operational Security Disclaimer -->
            <div class="viewer-disclaimer">
              <i class="fa-solid fa-shield-halved"></i>
              <span><strong>Cảnh báo nghiệp vụ:</strong> Ảnh biên lai là tài liệu do khách tải lên, KHÔNG được xem là bằng chứng chắc chắn tiền đã vào tài khoản. Kế toán bắt buộc phải đối soát số dư trên app ngân hàng thật trước khi bấm duyệt thu.</span>
            </div>

            <div class="image-scroll-box">
              <img 
                :src="getBillUrl(fullImageUrl, fullImageBooking)" 
                alt="Full Bill" 
                :style="{ transform: `scale(${zoomLevel})` }"
              />
            </div>
        </div>
    </div>

    <!-- Modal Duyệt Thu Tiền Thủ Công Sau Đối Soát -->
    <div v-if="bookingToConfirmPayment" class="modal-overlay" @click.self="bookingToConfirmPayment = null">
      <div class="modal-content card confirm-modal animate-in">
        <div class="modal-header">
          <div class="confirm-icon-wrapper">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div>
            <h3>Duyệt Thu Tiền Thủ Công</h3>
            <p class="modal-sub">Xác nhận thanh toán sau khi đã đối soát sao kê ngân hàng</p>
          </div>
          <button class="btn-close" @click="bookingToConfirmPayment = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="confirm-summary-box">
            <div class="summary-line">
              <span>Mã đơn đặt xe:</span>
              <strong>#{{ bookingToConfirmPayment.ma_dat_xe }}</strong>
            </div>
            <div class="summary-line">
              <span>Khách hàng:</span>
              <span>{{ bookingToConfirmPayment.user_name || 'Khách vãng lai' }}</span>
            </div>
            <div class="summary-line">
              <span>Phương tiện:</span>
              <span>{{ bookingToConfirmPayment.car_name }}</span>
            </div>
            <div class="summary-line highlight">
              <span>Số tiền thực thu:</span>
              <span class="price-val">{{ formatCurrency(bookingToConfirmPayment.tong_tien) }}</span>
            </div>
          </div>

          <div class="receipt-audit-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Lưu ý nghiệp vụ đối soát:</strong>
              <p>Chỉ bấm duyệt khi bạn đã nhìn thấy tiền thực tế trong tài khoản ngân hàng của doanh nghiệp. Hệ thống sẽ lưu vết định danh tài khoản Admin của bạn và thời điểm xác nhận này.</p>
            </div>
          </div>

          <div class="form-group mt-3">
            <label class="form-label font-bold text-xs">Ghi chú đối soát (tùy chọn):</label>
            <input 
              type="text" 
              class="form-control" 
              v-model="paymentConfirmNote" 
              placeholder="VD: Khớp biến động số dư Vietcombank lúc 15:20..." 
            />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="bookingToConfirmPayment = null">Hủy bỏ</button>
          <button class="btn-confirm-action" @click="executeConfirmPayment" :disabled="confirmingPayment">
            <i v-if="confirmingPayment" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check-double"></i>
            {{ confirmingPayment ? 'Đang duyệt...' : 'Xác nhận đã nhận đủ tiền' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="bookingToDelete" class="modal-overlay" @click.self="bookingToDelete = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3>Xác nhận xóa đơn</h3>
            <p class="modal-sub">Dữ liệu đơn hàng sẽ bị xóa vĩnh viễn khỏi cơ sở dữ liệu</p>
          </div>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa vĩnh viễn đơn thuê xe sau?</p>
          <div class="highlight-box">
            <strong>Mã đơn: #{{ bookingToDelete.ma_dat_xe }}</strong>
            <span>Khách hàng: {{ bookingToDelete.user_name || 'Khách vãng lai' }} | Xe: {{ bookingToDelete.car_name }}</span>
            <span>Tổng số tiền: {{ formatCurrency(bookingToDelete.tong_tien) }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="bookingToDelete = null">Hủy</button>
          <button class="btn-danger" @click="executeDeleteBooking" :disabled="deleting">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ deleting ? 'Đang xóa...' : 'Đồng ý xóa' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Chuyển Trạng Thái (Bàn giao xe / Nhận trả xe kèm ghi chú) -->
    <div v-if="transitionModalData" class="modal-overlay" @click.self="transitionModalData = null">
      <div class="modal-content card confirm-modal animate-in">
        <div class="modal-header">
          <div class="confirm-icon-wrapper" :style="{ background: transitionModalData.iconBg, color: transitionModalData.iconColor }">
            <i :class="transitionModalData.icon"></i>
          </div>
          <div>
            <h3>{{ transitionModalData.title }}</h3>
            <p class="modal-sub">Mã đơn đặt xe: #{{ transitionModalData.booking.ma_dat_xe }}</p>
          </div>
          <button class="btn-close" @click="transitionModalData = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="confirm-summary-box mb-3">
            <div class="summary-line">
              <span>Khách hàng:</span>
              <strong>{{ transitionModalData.booking.user_name || 'Khách vãng lai' }}</strong>
            </div>
            <div class="summary-line">
              <span>Phương tiện:</span>
              <span>{{ transitionModalData.booking.car_name }}</span>
            </div>
            <div class="summary-line">
              <span>Lịch trình:</span>
              <span>{{ formatDate(transitionModalData.booking.start_date) }} → {{ formatDate(transitionModalData.booking.end_date) }}</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label font-bold text-xs">{{ transitionModalData.noteLabel }}</label>
            <textarea 
              class="form-control" 
              rows="3" 
              v-model="transitionNote" 
              placeholder="Nhập ghi chú số km, mức xăng/pin, tình trạng trầy xước, phụ kiện..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="transitionModalData = null">Hủy bỏ</button>
          <button class="btn-primary" @click="executeTransition" :disabled="submittingTransition">
            <i v-if="submittingTransition" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            {{ submittingTransition ? 'Đang xử lý...' : transitionModalData.confirmText }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Hủy Đơn Thuê Xe (Bắt buộc lý do & cảnh báo tài chính) -->
    <div v-if="bookingToCancel" class="modal-overlay" @click.self="bookingToCancel = null">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper">
            <i class="fa-solid fa-ban"></i>
          </div>
          <div>
            <h3>Xác nhận Hủy Đơn Thuê Xe</h3>
            <p class="modal-sub">Mã đơn: #{{ bookingToCancel.ma_dat_xe }}</p>
          </div>
          <button class="btn-close" @click="bookingToCancel = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div v-if="Number(bookingToCancel.is_thanh_toan) === 1" class="financial-alert-box mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Lưu ý tài chính quan trọng:</strong>
              <p>Đơn đặt xe này ĐÃ ĐƯỢC THU TIỀN ({{ formatCurrency(bookingToCancel.tong_tien) }}). Thao tác hủy sẽ KHÔNG tự động hoàn tiền. Hệ thống bảo toàn lịch sử thanh toán và chuyển đơn sang danh sách chờ kế toán đối soát hoàn tiền.</p>
            </div>
          </div>

          <div class="confirm-summary-box mb-3">
            <div class="summary-line">
              <span>Khách hàng:</span>
              <strong>{{ bookingToCancel.user_name || 'Khách vãng lai' }}</strong>
            </div>
            <div class="summary-line">
              <span>Xe thuê:</span>
              <span>{{ bookingToCancel.car_name }}</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label font-bold text-xs">Lý do hủy đơn (bắt buộc): <span class="text-danger">*</span></label>
            <textarea 
              class="form-control" 
              rows="3" 
              v-model="cancelReasonInput" 
              placeholder="VD: Khách đổi lịch trình, xe gặp sự cố kỹ thuật trước giờ giao..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="bookingToCancel = null">Đóng</button>
          <button class="btn-danger" @click="executeCancelBooking" :disabled="submittingCancel || !cancelReasonInput.trim()">
            <i v-if="submittingCancel" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-ban"></i>
            {{ submittingCancel ? 'Đang hủy...' : 'Xác nhận hủy đơn' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Sổ Giao Dịch & Quản Lý Tiền Cọc (Deposit & Multi-payment Ledger) -->
    <div v-if="ledgerBooking" class="modal-overlay" @click.self="closeLedgerModal">
      <div class="modal-content card ledger-modal animate-in">
        <div class="modal-header">
          <div class="ledger-icon-wrapper">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div>
            <h3>Sổ Giao Dịch & Tiền Cọc Bảo Đảm</h3>
            <p class="modal-sub">Mã đơn: #{{ ledgerBooking.ma_dat_xe }} | Khách: {{ ledgerBooking.user_name || 'Khách vãng lai' }} | Xe: {{ ledgerBooking.car_name }}</p>
          </div>
          <button class="btn-close" @click="closeLedgerModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <div v-if="loadingLedger" class="text-center py-5">
            <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Đang tải sổ giao dịch...</p>
          </div>

          <div v-else-if="ledgerData">
            <!-- 4 Financial Balance Cards -->
            <div class="ledger-metric-grid mb-4">
              <div class="ledger-card rent-total">
                <div class="metric-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div class="metric-info">
                  <span class="metric-label">Tổng phí thuê xe</span>
                  <strong class="metric-val">{{ formatCurrency(ledgerData.total_rent_fee) }}</strong>
                  <small class="metric-sub">Hợp đồng thuê</small>
                </div>
              </div>

              <div class="ledger-card rent-paid">
                <div class="metric-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="metric-info">
                  <span class="metric-label">Tiền thuê đã thu</span>
                  <strong class="metric-val text-success">{{ formatCurrency(ledgerData.paid_rent_amount) }}</strong>
                  <small class="metric-sub">Thực thu phí thuê</small>
                </div>
              </div>

              <div class="ledger-card rent-remaining">
                <div class="metric-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="metric-info">
                  <span class="metric-label">Tiền thuê còn nợ</span>
                  <strong class="metric-val" :class="ledgerData.remaining_rent_amount > 0 ? 'text-warning' : 'text-muted'">
                    {{ formatCurrency(ledgerData.remaining_rent_amount) }}
                  </strong>
                  <small class="metric-sub">{{ ledgerData.remaining_rent_amount > 0 ? 'Còn phải thu' : 'Đã thanh toán đủ' }}</small>
                </div>
              </div>

              <div class="ledger-card deposit-held">
                <div class="metric-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="metric-info">
                  <span class="metric-label">Tiền cọc đang giữ</span>
                  <strong class="metric-val text-info">{{ formatCurrency(ledgerData.deposit_held_amount) }}</strong>
                  <small class="metric-sub">Ký quỹ (không tính DT)</small>
                </div>
              </div>
            </div>

            <!-- Deposit Breakdown Detail if any -->
            <div v-if="ledgerData.deposit_collected_amount > 0" class="deposit-breakdown-bar mb-4">
              <div class="breakdown-item">
                <span>Tổng cọc đã thu:</span>
                <strong>{{ formatCurrency(ledgerData.deposit_collected_amount) }}</strong>
              </div>
              <div class="breakdown-item">
                <span>Cọc đã hoàn trả:</span>
                <span class="text-secondary">{{ formatCurrency(ledgerData.deposit_refunded_amount) }}</span>
              </div>
              <div class="breakdown-item">
                <span>Cọc đã khấu trừ:</span>
                <span class="text-danger">{{ formatCurrency(ledgerData.deposit_deducted_amount) }}</span>
              </div>
            </div>

            <!-- Form Thêm Giao Dịch Mới -->
            <div class="new-tx-box mb-4">
              <div class="box-header" @click="showAddTxForm = !showAddTxForm">
                <div class="title-wrap">
                  <i class="fa-solid fa-circle-plus text-primary"></i>
                  <strong>Ghi nhận giao dịch tài chính mới</strong>
                </div>
                <button type="button" class="btn-toggle">
                  <i class="fa-solid" :class="showAddTxForm ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
              </div>

              <div v-if="showAddTxForm" class="box-body mt-3">
                <div class="form-row">
                  <div class="form-col">
                    <label class="form-label font-bold text-xs">Loại giao dịch: <span class="text-danger">*</span></label>
                    <select v-model="newTx.type" class="form-select">
                      <option value="rental_payment">Thu tiền thuê xe (Trả trước / theo đợt)</option>
                      <option value="rental_refund">Hoàn tiền thuê xe cho khách</option>
                      <option value="deposit_collection">Thu tiền cọc bảo đảm hoàn lại (không tính DT)</option>
                      <option value="deposit_refund">Hoàn trả tiền cọc cho khách</option>
                      <option value="deposit_deduction">Khấu trừ tiền cọc (Bồi thường / Phụ phí)</option>
                    </select>
                  </div>
                  <div class="form-col">
                    <label class="form-label font-bold text-xs">Số tiền (VND): <span class="text-danger">*</span></label>
                    <input 
                      type="number" 
                      v-model="newTx.amount" 
                      class="form-control" 
                      placeholder="VD: 1000000" 
                      min="1"
                    />
                  </div>
                  <div class="form-col">
                    <label class="form-label font-bold text-xs">Phương thức:</label>
                    <select v-model="newTx.method" class="form-select">
                      <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                      <option value="cash">Tiền mặt tại bãi xe / quầy</option>
                      <option value="credit_card">Thẻ tín dụng / Ghi nợ</option>
                      <option value="pos">Quẹt máy POS</option>
                      <option value="other">Phương thức khác</option>
                    </select>
                  </div>
                </div>

                <div class="form-row mt-3">
                  <div class="form-col">
                    <label class="form-label font-bold text-xs">Mã tham chiếu / Đối soát:</label>
                    <input 
                      type="text" 
                      v-model="newTx.reference_code" 
                      class="form-control" 
                      placeholder="VD: FT26040123, PT-0012, Sao kê..."
                    />
                  </div>
                  <div class="form-col">
                    <label class="form-label font-bold text-xs">Ghi chú giao dịch:</label>
                    <input 
                      type="text" 
                      v-model="newTx.note" 
                      class="form-control" 
                      placeholder="VD: Thu đợt 1, khách chuyển khoản lúc 10h..."
                    />
                  </div>
                </div>

                <div v-if="newTx.type === 'deposit_deduction'" class="form-group mt-3 deduction-reason-group">
                  <label class="form-label font-bold text-xs text-danger">
                    Lý do khấu trừ tiền cọc (bắt buộc): <span class="text-danger">*</span>
                  </label>
                  <textarea 
                    v-model="newTx.deduction_reason" 
                    rows="2" 
                    class="form-control" 
                    placeholder="VD: Bồi thường trầy xước cản trước 1.5 triệu, phụ phí rửa xe nội thất 200k..."
                  ></textarea>
                </div>

                <div class="text-end mt-3">
                  <button 
                    class="btn-primary" 
                    @click="submitNewTransaction" 
                    :disabled="submittingTx || !newTx.amount || newTx.amount <= 0 || (newTx.type === 'deposit_deduction' && !newTx.deduction_reason?.trim())"
                  >
                    <i v-if="submittingTx" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-check"></i>
                    {{ submittingTx ? 'Đang ghi nhận...' : 'Xác nhận ghi nhận giao dịch' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Bảng Lịch Sử Sổ Giao Dịch -->
            <div class="ledger-table-wrapper">
              <h4 class="ledger-table-title"><i class="fa-solid fa-list-check"></i> Sổ cái lịch sử giao dịch</h4>
              
              <div v-if="!ledgerData.transactions || ledgerData.transactions.length === 0" class="empty-tx-box">
                <i class="fa-solid fa-receipt"></i>
                <p>Chưa có giao dịch tài chính nào được ghi nhận cho đơn đặt xe này.</p>
                <small v-if="ledgerData.is_legacy" class="text-warning">
                  Đơn hàng lịch sử được đánh dấu đã thanh toán (cần đối soát sao kê nếu muốn bổ sung chi tiết).
                </small>
              </div>

              <table v-else class="ledger-table">
                <thead>
                  <tr>
                    <th width="140">Thời gian</th>
                    <th width="160">Loại giao dịch</th>
                    <th width="140" class="text-end">Số tiền</th>
                    <th width="120">Phương thức</th>
                    <th>Mã tham chiếu / Đối soát</th>
                    <th>Người ghi nhận</th>
                    <th>Ghi chú & Lý do</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in ledgerData.transactions" :key="tx.id">
                    <td><small>{{ formatDate(tx.transaction_time || tx.created_at) }}</small></td>
                    <td>
                      <span class="badge" :class="'badge-' + (tx.type_badge_color || 'secondary')">
                        {{ tx.type_label }}
                      </span>
                    </td>
                    <td class="text-end font-bold" :class="tx.is_outflow ? 'text-warning' : (tx.type === 'deposit_deduction' ? 'text-danger' : 'text-success')">
                      {{ tx.is_outflow ? '-' : '+' }}{{ formatCurrency(tx.amount) }}
                    </td>
                    <td><span class="method-tag">{{ formatMethod(tx.method) }}</span></td>
                    <td><code class="ref-code">{{ tx.reference_code || '-' }}</code></td>
                    <td><small class="admin-operator">{{ tx.created_by_admin?.ho_ten || 'Admin' }}</small></td>
                    <td>
                      <div class="note-content">
                        <span>{{ tx.note || '-' }}</span>
                        <div v-if="tx.deduction_reason" class="deduction-reason-badge">
                          <i class="fa-solid fa-triangle-exclamation"></i> Lý do: {{ tx.deduction_reason }}
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="closeLedgerModal">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Handover Modal: Bàn giao xe (Xuất bãi) / Nhận trả xe (Thu hồi) -->
    <div v-if="handoverModalData" class="modal-overlay" @click.self="closeHandoverModal">
      <div class="modal-content card handover-modal animate-in">
        <div class="modal-header">
          <div class="handover-header-info">
            <div class="handover-type-badge" :class="handoverModalData.type">
              <i :class="handoverModalData.type === 'delivery' ? 'fa-solid fa-key' : 'fa-solid fa-arrow-right-arrow-left'"></i>
              <span>{{ handoverModalData.type === 'delivery' ? 'BIÊN BẢN BÀN GIAO XE (XUẤT BÃI)' : 'BIÊN BẢN NHẬN TRẢ XE (THU HỒI)' }}</span>
            </div>
            <h3>{{ handoverModalData.booking.car_name }}</h3>
            <p class="modal-sub">
              Đơn đặt xe: <strong>#{{ handoverModalData.booking.ma_dat_xe }}</strong> | 
              Khách hàng: <strong>{{ handoverModalData.booking.user_name || 'Khách vãng lai' }}</strong> 
              <span v-if="handoverModalData.booking.user_phone">({{ handoverModalData.booking.user_phone }})</span>
            </p>
          </div>
          <button class="btn-close" @click="closeHandoverModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body handover-modal-body">
          <div v-if="loadingHandoverModal" class="text-center py-5">
            <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Đang tải dữ liệu kiểm tra xe...</p>
          </div>

          <div v-else class="handover-form-content">
            <!-- Alert & Baseline for Return Inspection -->
            <div v-if="handoverModalData.type === 'return' && handoverModalData.delivery" class="delivery-baseline-card mb-4">
              <div class="baseline-title">
                <i class="fa-solid fa-clock-rotate-left text-info"></i>
                <strong>Thông số đối chiếu lúc bàn giao ban đầu:</strong>
              </div>
              <div class="baseline-grid">
                <div class="b-item">
                  <span class="b-lbl">Km xuất phát:</span>
                  <strong class="b-val">{{ formatNumber(handoverModalData.delivery.current_km) }} km</strong>
                </div>
                <div class="b-item">
                  <span class="b-lbl">Mức pin/nhiên liệu giao:</span>
                  <strong class="b-val">{{ handoverModalData.delivery.fuel_or_battery_level }}%</strong>
                </div>
                <div class="b-item">
                  <span class="b-lbl">Thời gian giao thực tế:</span>
                  <span class="b-val">{{ formatDate(handoverModalData.delivery.actual_time) }}</span>
                </div>
                <div class="b-item">
                  <span class="b-lbl">Nhân viên giao:</span>
                  <span class="b-val text-primary">{{ handoverModalData.delivery.staff?.ho_ten || 'Admin' }}</span>
                </div>
              </div>

              <!-- Real-time dynamic comparison banner -->
              <div class="realtime-diff-bar mt-3">
                <div class="diff-chip" :class="diffKm >= 0 ? 'text-success' : 'text-danger font-bold'">
                  <i class="fa-solid fa-road"></i>
                  <span>Quãng đường: {{ diffKm >= 0 ? '+' + formatNumber(diffKm) + ' km' : formatNumber(diffKm) + ' km (CẢNH BÁO)' }}</span>
                </div>
                <div class="diff-chip" :class="diffFuel >= 0 ? 'text-success' : 'text-warning'">
                  <i :class="isCarElectric ? 'fa-solid fa-bolt' : 'fa-solid fa-gas-pump'"></i>
                  <span>{{ isCarElectric ? 'Pin' : 'Xăng/Dầu' }}: {{ diffFuel >= 0 ? '+' + diffFuel + '%' : diffFuel + '%' }}</span>
                </div>
                <div v-if="hoursLate > 0" class="diff-chip text-danger">
                  <i class="fa-solid fa-clock"></i>
                  <span>Trễ hạn: {{ hoursLate }} giờ</span>
                </div>
              </div>
            </div>

            <!-- Form Section 1: Thời gian & Kỹ thuật -->
            <div class="form-section-box mb-4">
              <div class="section-badge-title">
                <i class="fa-solid fa-gauge-high"></i>
                <span>1. Thời gian & Thông số vận hành thực tế</span>
              </div>

              <div class="form-row mt-3">
                <div class="form-col">
                  <label class="form-label font-bold text-xs">Thời gian thực tế: <span class="text-danger">*</span></label>
                  <div class="input-with-action">
                    <input type="datetime-local" v-model="handoverForm.actual_time" class="form-control" />
                    <button type="button" class="btn-sub-action" @click="setHandoverNow">Hiện tại</button>
                  </div>
                </div>

                <div class="form-col">
                  <label class="form-label font-bold text-xs">
                    Số KM đồng hồ hiện tại (Odometer): <span class="text-danger">*</span>
                  </label>
                  <div class="km-input-wrapper">
                    <input 
                      type="number" 
                      v-model.number="handoverForm.current_km" 
                      class="form-control" 
                      placeholder="VD: 15200" 
                      min="0"
                    />
                    <div class="quick-km-btns">
                      <button type="button" class="btn-quick-tag" @click="handoverForm.current_km += 10">+10</button>
                      <button type="button" class="btn-quick-tag" @click="handoverForm.current_km += 50">+50</button>
                      <button type="button" class="btn-quick-tag" @click="handoverForm.current_km += 100">+100</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Odometer lower warning & Exception adjustment workflow -->
              <div v-if="handoverModalData.type === 'return' && diffKm < 0" class="odometer-alert-card mt-3">
                <div class="alert-head">
                  <i class="fa-solid fa-triangle-exclamation"></i>
                  <strong>Cảnh báo bất thường: Số km nhận trả nhỏ hơn lúc giao xe!</strong>
                </div>
                <p class="alert-desc">
                  Theo quy định vận hành, số km nhận trả không được thấp hơn số km lúc giao ({{ formatNumber(handoverModalData.delivery?.current_km) }} km). Nếu có sự cố kỹ thuật (thay cụm đồng hồ, lỗi ghi nhận ban đầu), bạn bắt buộc phải kích hoạt quy trình điều chỉnh ngoại lệ và cung cấp lý do giải trình.
                </p>
                <div class="checkbox-box mt-2">
                  <label class="custom-checkbox-label">
                    <input type="checkbox" v-model="handoverForm.is_odometer_adjusted" />
                    <span class="font-bold text-danger">Kích hoạt quy trình điều chỉnh số km ngoại lệ</span>
                  </label>
                </div>
                <div v-if="handoverForm.is_odometer_adjusted" class="mt-2">
                  <label class="form-label font-bold text-xs text-danger">Lý do điều chỉnh (Bắt buộc giải trình kiểm toán): <span class="text-danger">*</span></label>
                  <textarea 
                    v-model="handoverForm.odometer_adjustment_reason" 
                    rows="2" 
                    class="form-control" 
                    placeholder="VD: Thay thế cụm đồng hồ công-tơ-mét chính hãng theo biên bản bảo hành..."
                  ></textarea>
                </div>
              </div>

              <!-- Fuel / Battery Slider -->
              <div class="fuel-battery-section mt-4">
                <div class="fuel-head">
                  <label class="form-label font-bold text-xs">
                    <i :class="isCarElectric ? 'fa-solid fa-bolt text-warning' : 'fa-solid fa-gas-pump text-primary'"></i>
                    {{ isCarElectric ? 'Mức pin xe điện (%)' : 'Mức nhiên liệu xăng/dầu (%)' }}: <span class="text-danger">*</span>
                  </label>
                  <span class="level-indicator" :class="handoverForm.fuel_or_battery_level >= 50 ? 'high' : (handoverForm.fuel_or_battery_level >= 20 ? 'med' : 'low')">
                    {{ handoverForm.fuel_or_battery_level }}%
                  </span>
                </div>
                <input 
                  type="range" 
                  v-model.number="handoverForm.fuel_or_battery_level" 
                  min="0" 
                  max="100" 
                  step="1" 
                  class="fuel-range-slider"
                />
                <div class="quick-level-chips mt-2">
                  <button type="button" class="btn-level-chip" @click="handoverForm.fuel_or_battery_level = 20">20%</button>
                  <button type="button" class="btn-level-chip" @click="handoverForm.fuel_or_battery_level = 50">50%</button>
                  <button type="button" class="btn-level-chip" @click="handoverForm.fuel_or_battery_level = 80">80%</button>
                  <button type="button" class="btn-level-chip" @click="handoverForm.fuel_or_battery_level = 100">100% (Đầy)</button>
                </div>
              </div>
            </div>

            <!-- Form Section 2: Checklist Phụ kiện & Trang bị (Touch friendly) -->
            <div class="form-section-box mb-4">
              <div class="section-badge-title">
                <i class="fa-solid fa-list-check"></i>
                <span>2. Danh mục phụ kiện & Trang bị bàn giao (Checklist)</span>
              </div>
              <p class="section-subtext">Chạm để đánh dấu trang bị đầy đủ và nguyên vẹn</p>

              <div class="checklist-touch-grid mt-3">
                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.smart_key }"
                  @click="handoverForm.checklist.smart_key = !handoverForm.checklist.smart_key"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.smart_key ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Chìa khóa thông minh</strong>
                    <small>Đủ 02 chìa / Remote hoạt động</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.vehicle_documents }"
                  @click="handoverForm.checklist.vehicle_documents = !handoverForm.checklist.vehicle_documents"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.vehicle_documents ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Giấy tờ phương tiện</strong>
                    <small>Đăng kiểm, Bảo hiểm TNDS, Giấy phép</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.spare_tire }"
                  @click="handoverForm.checklist.spare_tire = !handoverForm.checklist.spare_tire"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.spare_tire ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Lốp dự phòng</strong>
                    <small>Đủ áp suất / Gai lốp đạt chuẩn</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.jack_tool }"
                  @click="handoverForm.checklist.jack_tool = !handoverForm.checklist.jack_tool"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.jack_tool ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Bộ kích & Tay vặn lốp</strong>
                    <small>Đầy đủ trong cốp phụ</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.portable_charger }"
                  @click="handoverForm.checklist.portable_charger = !handoverForm.checklist.portable_charger"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.portable_charger ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Bộ sạc di động chính hãng</strong>
                    <small>{{ isCarElectric ? 'Bộ sạc EV AC chính hãng' : 'Tẩu sạc nhanh điện thoại' }}</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.dashcam_working }"
                  @click="handoverForm.checklist.dashcam_working = !handoverForm.checklist.dashcam_working"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.dashcam_working ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Camera hành trình</strong>
                    <small>Hoạt động tốt / Có thẻ nhớ</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.clean_interior }"
                  @click="handoverForm.checklist.clean_interior = !handoverForm.checklist.clean_interior"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.clean_interior ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Vệ sinh nội thất sạch</strong>
                    <small>Ghế da, sàn xe, khử mùi</small>
                  </div>
                </div>

                <div 
                  class="checklist-touch-item" 
                  :class="{ checked: handoverForm.checklist.clean_exterior }"
                  @click="handoverForm.checklist.clean_exterior = !handoverForm.checklist.clean_exterior"
                >
                  <div class="check-icon"><i class="fa-solid" :class="handoverForm.checklist.clean_exterior ? 'fa-square-check' : 'fa-square'"></i></div>
                  <div class="check-text">
                    <strong>Vỏ xe & Thân vỏ sạch</strong>
                    <small>Rửa vỏ ngoài / Đèn, kính nguyên vẹn</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Section 3: Tình trạng ngoại thất & nội thất -->
            <div class="form-section-box mb-4">
              <div class="section-badge-title">
                <i class="fa-solid fa-car-on"></i>
                <span>3. Đánh giá hiện trạng phương tiện</span>
              </div>

              <div class="form-row mt-3">
                <div class="form-col">
                  <label class="form-label font-bold text-xs">Hiện trạng thân vỏ / Ngoại thất:</label>
                  <textarea 
                    v-model="handoverForm.exterior_status" 
                    rows="2" 
                    class="form-control" 
                    placeholder="Mô tả vết trầy xước dăm, đèn, kính, gương..."
                  ></textarea>
                  <div class="quick-text-chips mt-1">
                    <button type="button" class="btn-text-chip" @click="handoverForm.exterior_status = 'Thân vỏ nguyên vẹn, không móp méo trầy xước.'">Nguyên vẹn</button>
                    <button type="button" class="btn-text-chip" @click="handoverForm.exterior_status = 'Xước dăm nhẹ cản trước/sau, không ảnh hưởng thẩm mỹ.'">Xước dăm nhẹ</button>
                  </div>
                </div>

                <div class="form-col">
                  <label class="form-label font-bold text-xs">Hiện trạng khoang lái / Nội thất:</label>
                  <textarea 
                    v-model="handoverForm.interior_status" 
                    rows="2" 
                    class="form-control" 
                    placeholder="Mô tả tình trạng ghế da, thảm sàn, trần nỉ..."
                  ></textarea>
                  <div class="quick-text-chips mt-1">
                    <button type="button" class="btn-text-chip" @click="handoverForm.interior_status = 'Nội thất sạch sẽ, không mùi, ghế da nguyên vẹn.'">Sạch sẽ</button>
                    <button type="button" class="btn-text-chip" @click="handoverForm.interior_status = 'Bụi bẩn thông thường sàn xe, cần rửa cơ bản.'">Bụi sàn xe</button>
                  </div>
                </div>
              </div>

              <div class="form-group mt-3">
                <label class="form-label font-bold text-xs">Ghi chú nhân viên thực hiện:</label>
                <input 
                  type="text" 
                  v-model="handoverForm.staff_notes" 
                  class="form-control" 
                  placeholder="Ghi chú thêm về khách hàng hoặc thỏa thuận đặc biệt..."
                />
              </div>
            </div>

            <!-- Form Section 4: Ảnh chụp hiện trạng (Bảo mật riêng tư) -->
            <div class="form-section-box mb-4">
              <div class="section-badge-title">
                <i class="fa-solid fa-camera"></i>
                <span>4. Ảnh chụp hiện trạng phương tiện (Lưu trữ riêng tư bảo mật)</span>
              </div>
              <p class="section-subtext">Chụp/tải ảnh 4 góc xe, đồng hồ km, mức pin, giấy tờ xe hoặc các vết trầy xước (tối đa 5MB/ảnh).</p>

              <div class="photo-upload-container mt-3">
                <label class="upload-dropzone">
                  <input 
                    type="file" 
                    multiple 
                    accept="image/jpeg,image/png,image/webp" 
                    class="sr-only" 
                    @change="handleHandoverPhotoUpload"
                  />
                  <div class="dropzone-inner">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x text-primary"></i>
                    <span>Chạm để chụp ảnh hoặc tải tệp từ thiết bị</span>
                    <small>Định dạng JPG, PNG, WEBP &bull; Tối đa 5MB &bull; Lưu trữ an toàn riêng tư</small>
                  </div>
                </label>

                <!-- Thumbnails preview list -->
                <div v-if="handoverForm.imagePreviews.length > 0" class="previews-grid mt-3">
                  <div 
                    v-for="(prev, pIdx) in handoverForm.imagePreviews" 
                    :key="pIdx" 
                    class="prev-thumb-card"
                  >
                    <img :src="prev" alt="preview" />
                    <button type="button" class="btn-del-thumb" @click="removeHandoverPhoto(pIdx)" title="Xóa ảnh">&times;</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Section 5: Phụ phí & Khấu trừ cọc (Chỉ hiển thị khi Nhận trả xe) -->
            <div v-if="handoverModalData.type === 'return'" class="form-section-box mb-4 surcharge-management-section">
              <div class="section-badge-title">
                <i class="fa-solid fa-file-invoice-dollar text-warning"></i>
                <span>5. Phụ phí phát sinh & Khấu trừ tiền cọc (Quy tắc hệ thống)</span>
              </div>
              <p class="section-subtext">Chỉ áp dụng các quy tắc thực tế đã cấu hình trong hệ thống, không tự bịa đơn giá.</p>

              <!-- Quick Surcharge Suggestion Buttons -->
              <div class="quick-surcharge-row mt-3">
                <button 
                  type="button" 
                  class="btn-sc-preset" 
                  @click="addPresetSurcharge('cleaning', 100000, 'Phụ phí vệ sinh xe tiêu chuẩn (bùn đất thân vỏ)')"
                >
                  <i class="fa-solid fa-soap"></i> Vệ sinh tiêu chuẩn (100k)
                </button>
                <button 
                  type="button" 
                  class="btn-sc-preset" 
                  @click="addPresetSurcharge('cleaning', 300000, 'Phụ phí dọn nội thất chuyên sâu (ố bẩn/ám mùi)')"
                >
                  <i class="fa-solid fa-broom"></i> Vệ sinh nặng (300k)
                </button>
                <button 
                  v-if="diffFuel < 0" 
                  type="button" 
                  class="btn-sc-preset highlight" 
                  @click="addSuggestedFuelSurcharge"
                >
                  <i class="fa-solid fa-gas-pump"></i> Bù hao hụt {{ isCarElectric ? 'pin' : 'nhiên liệu' }} ({{ formatCurrency(calcFuelSurchargeAmount) }})
                </button>
                <button 
                  v-if="hoursLate > 0" 
                  type="button" 
                  class="btn-sc-preset highlight" 
                  @click="addSuggestedLateSurcharge"
                >
                  <i class="fa-solid fa-clock"></i> Phụ phí trễ {{ hoursLate }}h ({{ formatCurrency(calcLateSurchargeAmount) }})
                </button>
                <button 
                  type="button" 
                  class="btn-sc-preset custom" 
                  @click="addCustomSurcharge"
                >
                  <i class="fa-solid fa-plus"></i> Thêm phụ phí khác
                </button>
              </div>

              <!-- Surcharges Table -->
              <div v-if="handoverForm.surcharges.length > 0" class="surcharges-table-box mt-3">
                <table class="sc-table">
                  <thead>
                    <tr>
                      <th width="150">Loại phụ phí</th>
                      <th width="160">Số tiền (VND)</th>
                      <th>Lý do & Quy tắc</th>
                      <th width="50"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(sc, sIdx) in handoverForm.surcharges" :key="sIdx">
                      <td>
                        <select v-model="sc.type" class="form-select form-select-sm">
                          <option value="late_return">Trả xe trễ hạn</option>
                          <option value="excess_km">Vượt giới hạn km</option>
                          <option value="cleaning">Vệ sinh xe</option>
                          <option value="fuel_diff">Hao hụt nhiên liệu/pin</option>
                          <option value="damage">Trầy xước / Bồi thường</option>
                        </select>
                      </td>
                      <td>
                        <input 
                          type="number" 
                          v-model.number="sc.amount" 
                          class="form-control form-control-sm text-end font-bold" 
                          min="0" 
                          step="1000"
                        />
                      </td>
                      <td>
                        <input 
                          type="text" 
                          v-model="sc.reason" 
                          class="form-control form-control-sm" 
                          placeholder="Nhập lý do chi tiết..."
                        />
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn-del-sc" @click="removeSurchargeItem(sIdx)">&times;</button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="2" class="text-end font-bold">TỔNG PHỤ PHÍ:</th>
                      <th colspan="2" class="text-danger font-bold text-base">{{ formatCurrency(totalSurchargesAmount) }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Deposit Deduction Option -->
              <div v-if="heldDepositAmount > 0 && totalSurchargesAmount > 0" class="deposit-deduction-card mt-3">
                <div class="checkbox-box">
                  <label class="custom-checkbox-label">
                    <input type="checkbox" v-model="handoverForm.deduct_surcharges_from_deposit" />
                    <strong>Khấu trừ phụ phí ({{ formatCurrency(Math.min(totalSurchargesAmount, heldDepositAmount)) }}) vào Tiền cọc đang giữ ({{ formatCurrency(heldDepositAmount) }})</strong>
                  </label>
                </div>
                <small class="text-muted block mt-1">
                  Khoản tiền cọc còn lại sau khi khấu trừ ({{ formatCurrency(Math.max(0, heldDepositAmount - totalSurchargesAmount)) }}) sẽ được bộ phận kế toán hoàn trả cho khách theo quy định.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer handover-modal-footer">
          <button class="btn-outline" @click="closeHandoverModal">Hủy bỏ</button>
          <button 
            class="btn-primary" 
            @click="submitHandoverRecord" 
            :disabled="submittingHandover || (handoverModalData?.type === 'return' && diffKm < 0 && (!handoverForm.is_odometer_adjusted || !handoverForm.odometer_adjustment_reason?.trim()))"
          >
            <i v-if="submittingHandover" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else :class="handoverModalData.type === 'delivery' ? 'fa-solid fa-key' : 'fa-solid fa-arrow-right-arrow-left'"></i>
            {{ submittingHandover ? 'Đang lưu biên bản...' : (handoverModalData.type === 'delivery' ? 'Xác nhận Bàn giao & Giao xe' : 'Xác nhận Nhận trả xe') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Xem & In Biên Bản Bàn Giao / Nhận Trả Xe -->
    <div v-if="inspectionBooking" class="modal-overlay" @click.self="closeInspectionModal">
      <div class="modal-content card inspection-modal animate-in">
        <div class="modal-header no-print">
          <div class="title-wrap">
            <i class="fa-solid fa-clipboard-check text-primary"></i>
            <div>
              <h3>Biên Bản Bàn Giao & Nhận Trả Phương Tiện</h3>
              <p class="modal-sub">Mã hồ sơ: #BBBG-{{ inspectionBooking.ma_dat_xe }}</p>
            </div>
          </div>
          <div class="header-action-group">
            <button class="btn-primary btn-sm" @click="printInspectionReport">
              <i class="fa-solid fa-print"></i> In biên bản
            </button>
            <button class="btn-close" @click="closeInspectionModal"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </div>

        <div class="modal-body inspection-body" id="inspection-printable-area">
          <div v-if="loadingInspection" class="text-center py-5">
            <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Đang tải biên bản bàn giao...</p>
          </div>

          <div v-else-if="inspectionData" class="inspection-document-sheet">
            <!-- Header Paper -->
            <div class="sheet-header">
              <div class="company-brand">
                <div class="brand-logo-mark">DriveRent</div>
                <div class="brand-sub">Hệ sinh thái Thuê Xe & Di Chuyển Thông Minh</div>
                <small class="company-meta">Trụ sở: Tòa nhà Green Mobility, TP. Hồ Chí Minh | Hotline: 1900 1234</small>
              </div>
              <div class="sheet-title-block">
                <h2>BIÊN BẢN BÀN GIAO & NHẬN TRẢ PHƯƠNG TIỆN</h2>
                <div class="doc-code">Số: #BBBG-{{ inspectionBooking.ma_dat_xe }}</div>
                <div class="doc-date">Ngày lập: {{ formatDate(new Date()) }}</div>
              </div>
            </div>

            <!-- Contract & Customer Info Box -->
            <div class="sheet-meta-grid">
              <div class="meta-item">
                <span class="m-lbl">Khách hàng:</span>
                <strong class="m-val">{{ inspectionBooking.user_name || 'Khách vãng lai' }}</strong>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Số điện thoại:</span>
                <span class="m-val">{{ inspectionBooking.user_phone || 'Chưa cập nhật' }}</span>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Phương tiện:</span>
                <strong class="m-val text-primary">{{ inspectionBooking.car_name }}</strong>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Lịch hẹn thuê:</span>
                <span class="m-val">{{ formatDate(inspectionBooking.start_date) }} → {{ formatDate(inspectionBooking.end_date) }}</span>
              </div>
            </div>

            <!-- Two-Column Side-by-Side Inspection Details: Delivery vs Return -->
            <div class="inspection-comparison-grid mt-4">
              <!-- Cột 1: Biên bản bàn giao lúc xuất bãi -->
              <div class="inspection-col delivery-col">
                <div class="col-header">
                  <i class="fa-solid fa-key"></i>
                  <h4>I. BIÊN BẢN BÀN GIAO (XUẤT BÃI)</h4>
                </div>

                <div v-if="inspectionData.delivery" class="col-content">
                  <div class="stat-row">
                    <span>Thời gian thực tế:</span>
                    <strong>{{ formatDate(inspectionData.delivery.actual_time) }}</strong>
                  </div>
                  <div class="stat-row">
                    <span>Nhân viên giao xe:</span>
                    <strong>{{ inspectionData.delivery.staff?.ho_ten || 'Admin' }}</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Số KM công-tơ-mét:</span>
                    <strong class="val-metric">{{ formatNumber(inspectionData.delivery.current_km) }} km</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Mức pin/nhiên liệu:</span>
                    <strong class="val-metric">{{ inspectionData.delivery.fuel_or_battery_level }}%</strong>
                  </div>

                  <div class="checklist-summary-box mt-3">
                    <strong class="box-lbl">Phụ kiện & Trang bị bàn giao:</strong>
                    <ul class="clean-check-list">
                      <li :class="inspectionData.delivery.checklist?.smart_key ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.smart_key ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Chìa khóa thông minh (02 chìa)
                      </li>
                      <li :class="inspectionData.delivery.checklist?.vehicle_documents ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.vehicle_documents ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Giấy đăng ký, đăng kiểm, bảo hiểm TNDS
                      </li>
                      <li :class="inspectionData.delivery.checklist?.spare_tire ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.spare_tire ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Lốp dự phòng
                      </li>
                      <li :class="inspectionData.delivery.checklist?.jack_tool ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.jack_tool ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Bộ kích nâng & dụng cụ thay lốp
                      </li>
                      <li :class="inspectionData.delivery.checklist?.portable_charger ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.portable_charger ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Bộ sạc di động chính hãng
                      </li>
                      <li :class="inspectionData.delivery.checklist?.dashcam_working ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.delivery.checklist?.dashcam_working ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Camera hành trình
                      </li>
                    </ul>
                  </div>

                  <div class="status-notes-box mt-3">
                    <div class="note-line"><strong>Ngoại thất:</strong> {{ inspectionData.delivery.exterior_status || 'Nguyên vẹn' }}</div>
                    <div class="note-line"><strong>Nội thất:</strong> {{ inspectionData.delivery.interior_status || 'Sạch sẽ' }}</div>
                    <div v-if="inspectionData.delivery.staff_notes" class="note-line"><strong>Ghi chú NV:</strong> {{ inspectionData.delivery.staff_notes }}</div>
                  </div>

                  <!-- Delivery Photos -->
                  <div v-if="inspectionData.delivery.images && inspectionData.delivery.images.length > 0" class="photos-strip mt-3">
                    <strong class="box-lbl">Ảnh hiện trạng bàn giao:</strong>
                    <div class="photos-grid">
                      <img 
                        v-for="(img, imgIdx) in inspectionData.delivery.images" 
                        :key="imgIdx" 
                        :src="getHandoverImageUrl(inspectionBooking.id, img)" 
                        alt="Ảnh bàn giao" 
                        class="inspection-img" 
                        @click="viewFullImage(getHandoverImageUrl(inspectionBooking.id, img), inspectionBooking)"
                      />
                    </div>
                  </div>
                </div>

                <div v-else class="empty-col-notice">
                  <i class="fa-solid fa-clock"></i>
                  <p>Chưa lập biên bản bàn giao xe.</p>
                </div>
              </div>

              <!-- Cột 2: Biên bản nhận trả lúc thu hồi xe -->
              <div class="inspection-col return-col">
                <div class="col-header">
                  <i class="fa-solid fa-arrow-right-arrow-left"></i>
                  <h4>II. BIÊN BẢN NHẬN TRẢ (THU HỒI)</h4>
                </div>

                <div v-if="inspectionData.return" class="col-content">
                  <div class="stat-row">
                    <span>Thời gian thực tế:</span>
                    <strong>{{ formatDate(inspectionData.return.actual_time) }}</strong>
                  </div>
                  <div class="stat-row">
                    <span>Nhân viên nhận xe:</span>
                    <strong>{{ inspectionData.return.staff?.ho_ten || 'Admin' }}</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Số KM công-tơ-mét:</span>
                    <strong class="val-metric">{{ formatNumber(inspectionData.return.current_km) }} km</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Mức pin/nhiên liệu:</span>
                    <strong class="val-metric">{{ inspectionData.return.fuel_or_battery_level }}%</strong>
                  </div>

                  <div class="checklist-summary-box mt-3">
                    <strong class="box-lbl">Phụ kiện & Trang bị hoàn trả:</strong>
                    <ul class="clean-check-list">
                      <li :class="inspectionData.return.checklist?.smart_key ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.smart_key ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Chìa khóa thông minh
                      </li>
                      <li :class="inspectionData.return.checklist?.vehicle_documents ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.vehicle_documents ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Giấy tờ xe đầy đủ
                      </li>
                      <li :class="inspectionData.return.checklist?.spare_tire ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.spare_tire ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Lốp dự phòng
                      </li>
                      <li :class="inspectionData.return.checklist?.jack_tool ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.jack_tool ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Bộ kích & dụng cụ
                      </li>
                      <li :class="inspectionData.return.checklist?.portable_charger ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.portable_charger ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Bộ sạc di động chính hãng
                      </li>
                      <li :class="inspectionData.return.checklist?.dashcam_working ? 'ok' : 'missing'">
                        <i class="fa-solid" :class="inspectionData.return.checklist?.dashcam_working ? 'fa-check text-success' : 'fa-xmark text-danger'"></i>
                        Camera hành trình
                      </li>
                    </ul>
                  </div>

                  <div class="status-notes-box mt-3">
                    <div class="note-line"><strong>Ngoại thất:</strong> {{ inspectionData.return.exterior_status || 'Bình thường' }}</div>
                    <div class="note-line"><strong>Nội thất:</strong> {{ inspectionData.return.interior_status || 'Bình thường' }}</div>
                    <div v-if="inspectionData.return.is_odometer_adjusted" class="note-line text-danger">
                      <strong>Điều chỉnh số km:</strong> {{ inspectionData.return.odometer_adjustment_reason }}
                    </div>
                  </div>

                  <!-- Return Photos -->
                  <div v-if="inspectionData.return.images && inspectionData.return.images.length > 0" class="photos-strip mt-3">
                    <strong class="box-lbl">Ảnh hiện trạng lúc trả xe:</strong>
                    <div class="photos-grid">
                      <img 
                        v-for="(img, imgIdx) in inspectionData.return.images" 
                        :key="imgIdx" 
                        :src="getHandoverImageUrl(inspectionBooking.id, img)" 
                        alt="Ảnh nhận trả" 
                        class="inspection-img" 
                        @click="viewFullImage(getHandoverImageUrl(inspectionBooking.id, img), inspectionBooking)"
                      />
                    </div>
                  </div>
                </div>

                <div v-else class="empty-col-notice">
                  <i class="fa-solid fa-clock-rotate-left"></i>
                  <p>Chưa lập biên bản nhận trả xe (Xe đang lưu hành hoặc chờ trả).</p>
                </div>
              </div>
            </div>

            <!-- Comparison Diff Summary & Surcharges -->
            <div v-if="inspectionData.comparison" class="diff-summary-card mt-4">
              <h4 class="diff-title"><i class="fa-solid fa-chart-simple"></i> III. ĐỐI CHIẾU THỰC TẾ & THANH TOÁN PHỤ PHÍ</h4>
              <div class="diff-metrics-grid">
                <div class="dm-item">
                  <span>Quãng đường di chuyển:</span>
                  <strong>{{ formatNumber(inspectionData.comparison.km_driven) }} km</strong>
                </div>
                <div class="dm-item">
                  <span>Chênh lệch pin/nhiên liệu:</span>
                  <strong :class="inspectionData.comparison.fuel_diff >= 0 ? 'text-success' : 'text-warning'">
                    {{ inspectionData.comparison.fuel_diff >= 0 ? '+' : '' }}{{ inspectionData.comparison.fuel_diff }}%
                  </strong>
                </div>
                <div class="dm-item">
                  <span>Thời gian trả xe:</span>
                  <strong :class="inspectionData.comparison.hours_late > 0 ? 'text-danger' : 'text-success'">
                    {{ inspectionData.comparison.hours_late > 0 ? 'Trễ ' + inspectionData.comparison.hours_late + ' giờ' : 'Đúng hạn' }}
                  </strong>
                </div>
              </div>

              <!-- Surcharges Table if any -->
              <div v-if="inspectionData.surcharges && inspectionData.surcharges.length > 0" class="surcharges-result-box mt-3">
                <table class="report-table">
                  <thead>
                    <tr>
                      <th>Hạng mục phụ phí</th>
                      <th class="text-end" width="160">Số tiền</th>
                      <th>Lý do & Quy tắc</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sc in inspectionData.surcharges" :key="sc.id">
                      <td><strong>{{ sc.type_label }}</strong></td>
                      <td class="text-end font-bold text-danger">{{ formatCurrency(sc.amount) }}</td>
                      <td>{{ sc.reason }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th class="text-end">TỔNG PHỤ PHÍ:</th>
                      <th class="text-end text-danger font-bold">{{ formatCurrency(inspectionData.surcharges.reduce((sum, s) => sum + Number(s.amount), 0)) }}</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Customer Electronic Confirmation Status (NO fake signature) -->
            <div class="confirmation-section-sheet mt-4">
              <div class="confirm-head">
                <i class="fa-solid fa-fingerprint text-primary"></i>
                <h4>IV. GHI NHẬN XÁC NHẬN CỦA CÁC BÊN</h4>
              </div>

              <div class="confirm-grid mt-3">
                <div class="confirm-box staff-box">
                  <span class="c-title">ĐẠI DIỆN HỆ THỐNG DRIVERENT</span>
                  <div class="c-status ok">
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>ĐÃ LẬP BIÊN BẢN QUA HỆ THỐNG NỘI BỘ</strong>
                    <small>Nhân viên: {{ (inspectionData.return?.staff?.ho_ten || inspectionData.delivery?.staff?.ho_ten || 'Kỹ thuật viên DriveRent') }}</small>
                  </div>
                </div>

                <div class="confirm-box customer-box">
                  <span class="c-title">ĐẠI DIỆN KHÁCH HÀNG THUÊ XE</span>
                  <div v-if="inspectionData.delivery?.customer_confirmed || inspectionData.return?.customer_confirmed" class="c-status ok">
                    <i class="fa-solid fa-user-check"></i>
                    <strong>ĐÃ XÁC NHẬN ĐIỆN TỬ QUA ỨNG DỤNG</strong>
                    <small>Tài khoản: {{ inspectionBooking.user_name }} ({{ inspectionBooking.user_email }})</small>
                    <small>Thời điểm xác nhận: {{ formatDate(inspectionData.delivery?.customer_confirmed_at || inspectionData.return?.customer_confirmed_at) }}</small>
                    <p v-if="inspectionData.delivery?.customer_notes || inspectionData.return?.customer_notes" class="cust-note-text">
                      "{{ inspectionData.delivery?.customer_notes || inspectionData.return?.customer_notes }}"
                    </p>
                  </div>
                  <div v-else class="c-status pending">
                    <i class="fa-solid fa-clock"></i>
                    <strong>CHỜ KHÁCH HÀNG XÁC NHẬN TRÊN ỨNG DỤNG</strong>
                    <small>Hệ thống không tự tạo chữ ký thay khách hàng. Khách hàng kiểm tra và xác nhận biên bản qua tài khoản cá nhân.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Legal Disclaimer Box -->
            <div class="legal-disclaimer-box mt-4">
              <div class="disclaimer-title">
                <i class="fa-solid fa-scale-balanced"></i>
                <strong>LƯU Ý PHÁP LÝ & GIÁ TRỊ VĂN BẢN</strong>
              </div>
              <p>
                Biên bản bàn giao/nhận trả này là tài liệu kiểm tra hiện trạng kỹ thuật và trang thiết bị thực tế phục vụ quy trình vận hành và quyết toán nội bộ giữa Công ty DriveRent và Khách hàng tại thời điểm giao/nhận xe. Biên bản ghi nhận khách quan số km, mức nhiên liệu/pin, phụ kiện và tình trạng thân vỏ; <strong>không có giá trị thay thế văn bản chứng thực của cơ quan nhà nước có thẩm quyền hoặc cơ quan công chứng</strong>.
              </p>
            </div>
          </div>
        </div>

        <div class="modal-footer no-print">
          <button class="btn-outline" @click="closeInspectionModal">Đóng</button>
          <button class="btn-primary" @click="printInspectionReport">
            <i class="fa-solid fa-print"></i> In Biên Bản
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const route = useRoute();

const bookings = ref([]);
const loading = ref(true);
const deleting = ref(false);
const exporting = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const filterPayment = ref('');

const handleExportCsv = async () => {
  exporting.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('keyword', searchQuery.value);
    if (filterStatus.value !== '') params.append('tinh_trang', filterStatus.value);
    if (filterPayment.value !== '') {
      if (['0', '1'].includes(String(filterPayment.value))) {
        params.append('is_thanh_toan', filterPayment.value);
      }
    }

    const filename = `danh_sach_don_dat_xe_${new Date().toISOString().slice(0, 10)}.csv`;
    await baseRequest.downloadFile(`admin/bookings/export?${params.toString()}`, filename);
    toaster.success("Xuất file CSV thành công!");
  } catch (err) {
    if (err.response?.status === 403) {
      toaster.error("Bạn không có quyền xuất dữ liệu đơn đặt xe!");
    } else {
      toaster.error("Không thể kết nối đến máy chủ để tải file!");
    }
  } finally {
    exporting.value = false;
  }
};

const selectedBooking = ref(null);
const fullImageUrl = ref(null);
const zoomLevel = ref(1);
const bookingToDelete = ref(null);
const bookingToConfirmPayment = ref(null);
const paymentConfirmNote = ref('');
const confirmingPayment = ref(false);

const bookingToCancel = ref(null);
const cancelReasonInput = ref('');
const submittingCancel = ref(false);

const transitionModalData = ref(null);
const transitionNote = ref('');
const submittingTransition = ref(false);

const fullImageBooking = ref(null);

const reconciliationSummary = ref({
  total_discrepancies: 0,
  requires_refund_count: 0,
  pending_receipt_count: 0,
  missing_audit_count: 0,
});

const getBillUrl = (path, booking = null) => {
  if (!path && !booking) return '';
  const token = localStorage.getItem('token_admin') || localStorage.getItem('token_client');
  if (booking && booking.receipt_url) {
    return token ? `${booking.receipt_url}?token=${encodeURIComponent(token)}` : booking.receipt_url;
  }
  if (booking && booking.id) {
    const url = `http://localhost:8000/api/bookings/${booking.id}/receipt`;
    return token ? `${url}?token=${encodeURIComponent(token)}` : url;
  }
  if (path && (path.startsWith('http://') || path.startsWith('https://'))) {
    return token ? `${path}?token=${encodeURIComponent(token)}` : path;
  }
  return '';
};

const viewFullImage = (url, booking = null) => {
  fullImageUrl.value = url;
  fullImageBooking.value = booking;
  zoomLevel.value = 1;
};

const openDetailModal = (booking) => {
  selectedBooking.value = booking;
};

// Trạng thái thanh toán: Độc lập hoàn toàn với trạng thái thuê
const getPaymentStatus = (booking) => {
  if (!booking) return { class: 'unpaid', icon: 'fa-circle-exclamation', text: 'Chưa thanh toán' };

  // Đơn đã hủy nhưng đã thu tiền: KHÔNG coi là đã hoàn tiền, hiển thị cần đối soát / xử lý tài chính
  if (Number(booking.tinh_trang) === 2 && Number(booking.is_thanh_toan) === 1) {
    return {
      class: 'refund-review',
      icon: 'fa-triangle-exclamation',
      text: 'Đã thu tiền (Cần xử lý hoàn tiền)',
      subtext: 'Đơn đã hủy - Cần đối soát'
    };
  }

  // Đã thanh toán một phần
  if (Number(booking.is_thanh_toan) === 2 || booking.financial_summary?.payment_status_code === 'partial') {
    return {
      class: 'partial',
      icon: 'fa-hourglass-half',
      text: 'Thanh toán 1 phần',
      subtext: `Đã thu: ${formatCurrency(booking.financial_summary?.paid_rent_amount || 0)}`
    };
  }
  
  if (Number(booking.is_thanh_toan) === 1) {
    return {
      class: 'paid',
      icon: 'fa-circle-check',
      text: 'Đã thanh toán đủ',
      subtext: booking.confirmed_by_name ? `Bởi: ${booking.confirmed_by_name}` : 'Đã đối soát'
    };
  }
  
  if (booking.bill_image) {
    return {
      class: 'verifying',
      icon: 'fa-clock-rotate-left',
      text: 'Chờ đối soát biên lai',
      subtext: 'Đã có biên lai'
    };
  }
  
  return {
    class: 'unpaid',
    icon: 'fa-circle-exclamation',
    text: 'Chưa thanh toán',
    subtext: 'Chưa có bill'
  };
};

const pendingCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 0).length);
const confirmedCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 1).length);
const inProgressCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 3).length);
const returnedCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 4).length);
const completedCount = computed(() => bookings.value.filter(b => Number(b.tinh_trang) === 5).length);

// Doanh thu đơn hợp lệ đã thanh toán: Đồng bộ chuẩn hóa với RevenueCalculationService và Dashboard
const serverRevenueSummary = ref(null);

const realizedRevenue = computed(() => {
  if (serverRevenueSummary.value && typeof serverRevenueSummary.value.valid_paid_revenue === 'number') {
    return serverRevenueSummary.value.valid_paid_revenue;
  }
  return bookings.value
    .filter(b => Number(b.is_thanh_toan) === 1 && Number(b.tinh_trang) !== 2)
    .reduce((sum, b) => sum + Math.round(Number(b.tong_tien || 0)), 0);
});

const totalDepositHeld = computed(() => {
  if (serverRevenueSummary.value && typeof serverRevenueSummary.value.total_deposit_held === 'number') {
    return serverRevenueSummary.value.total_deposit_held;
  }
  return bookings.value.reduce((sum, b) => sum + Math.round(Number(b.financial_summary?.deposit_held_amount || 0)), 0);
});

const totalBookingValue = computed(() => {
  if (serverRevenueSummary.value && typeof serverRevenueSummary.value.total_booking_value === 'number') {
    return serverRevenueSummary.value.total_booking_value;
  }
  return bookings.value.reduce((sum, b) => sum + Math.round(Number(b.tong_tien || 0)), 0);
});

// Quản lý Sổ giao dịch & Cọc
const ledgerBooking = ref(null);
const ledgerData = ref(null);
const loadingLedger = ref(false);
const submittingTx = ref(false);
const showAddTxForm = ref(false);

const newTx = ref({
  type: 'rental_payment',
  amount: null,
  method: 'bank_transfer',
  reference_code: '',
  note: '',
  deduction_reason: '',
});

const formatMethod = (method) => {
  const map = {
    bank_transfer: 'Chuyển khoản',
    cash: 'Tiền mặt',
    credit_card: 'Thẻ tín dụng',
    pos: 'Máy POS',
    other: 'Khác',
  };
  return map[method] || method || 'Chuyển khoản';
};

const openLedgerModal = async (booking) => {
  ledgerBooking.value = booking;
  loadingLedger.value = true;
  showAddTxForm.value = false;
  newTx.value = {
    type: 'rental_payment',
    amount: null,
    method: 'bank_transfer',
    reference_code: '',
    note: '',
    deduction_reason: '',
  };

  try {
    const res = await baseRequest.get(`admin/bookings/${booking.id}/transactions`);
    if (res.data.status && res.data.data) {
      ledgerData.value = res.data.data;
    } else {
      toaster.error(res.data.message || 'Không thể tải sổ giao dịch');
    }
  } catch (error) {
    console.error('Error fetching ledger:', error);
    toaster.error('Lỗi khi tải dữ liệu sổ giao dịch!');
  } finally {
    loadingLedger.value = false;
  }
};

const closeLedgerModal = () => {
  ledgerBooking.value = null;
  ledgerData.value = null;
  showAddTxForm.value = false;
};

const submitNewTransaction = async () => {
  if (!ledgerBooking.value || !newTx.value.amount || newTx.value.amount <= 0) {
    toaster.error('Vui lòng nhập số tiền hợp lệ (> 0 VND)!');
    return;
  }

  if (newTx.value.type === 'deposit_deduction' && !newTx.value.deduction_reason?.trim()) {
    toaster.error('Vui lòng nhập lý do khấu trừ tiền cọc!');
    return;
  }

  submittingTx.value = true;
  try {
    const payload = {
      type: newTx.value.type,
      amount: Math.round(Number(newTx.value.amount)),
      method: newTx.value.method,
      reference_code: newTx.value.reference_code,
      note: newTx.value.note,
      deduction_reason: newTx.value.deduction_reason,
      idempotency_key: 'TX_' + ledgerBooking.value.id + '_' + Date.now(),
    };

    const res = await baseRequest.post(`admin/bookings/${ledgerBooking.value.id}/transactions`, payload);
    if (res.data.status) {
      toaster.success(res.data.message || 'Ghi nhận giao dịch thành công!');
      ledgerData.value = res.data.summary;
      
      // Cập nhật lại booking trong danh sách bảng
      const idx = bookings.value.findIndex(b => b.id === ledgerBooking.value.id);
      if (idx !== -1) {
        bookings.value[idx].financial_summary = res.data.summary;
        if (res.data.summary.remaining_rent_amount === 0 && res.data.summary.paid_rent_amount > 0) {
          bookings.value[idx].is_thanh_toan = 1;
        } else if (res.data.summary.paid_rent_amount > 0) {
          bookings.value[idx].is_thanh_toan = 2;
        } else {
          bookings.value[idx].is_thanh_toan = 0;
        }
      }

      // Reset form
      newTx.value.amount = null;
      newTx.value.reference_code = '';
      newTx.value.note = '';
      newTx.value.deduction_reason = '';
      showAddTxForm.value = false;
    } else {
      toaster.error(res.data.message || 'Ghi nhận giao dịch thất bại!');
    }
  } catch (error) {
    console.error('Error submitting transaction:', error);
    const msg = error.response?.data?.message || 'Có lỗi xảy ra khi ghi nhận giao dịch!';
    toaster.error(msg);
  } finally {
    submittingTx.value = false;
  }
};

const filteredBookings = computed(() => {
  return bookings.value.filter(b => {
    const query = searchQuery.value.toLowerCase().trim();
    const matchSearch = !query || 
      (b.ma_dat_xe && b.ma_dat_xe.toLowerCase().includes(query)) || 
      (b.user_name && b.user_name.toLowerCase().includes(query)) ||
      (b.car_name && b.car_name.toLowerCase().includes(query));

    const matchStatus = filterStatus.value === '' || Number(b.tinh_trang) === Number(filterStatus.value);
    
    let matchPayment = true;
    if (filterPayment.value === '1') {
      matchPayment = Number(b.is_thanh_toan) === 1 && Number(b.tinh_trang) !== 2;
    } else if (filterPayment.value === '2') {
      matchPayment = Number(b.is_thanh_toan) === 2 || (b.financial_summary?.paid_rent_amount > 0 && b.financial_summary?.remaining_rent_amount > 0);
    } else if (filterPayment.value === 'has_deposit') {
      matchPayment = Number(b.financial_summary?.deposit_held_amount || 0) > 0;
    } else if (filterPayment.value === 'verifying') {
      matchPayment = Number(b.is_thanh_toan) === 0 && Boolean(b.bill_image);
    } else if (filterPayment.value === 'unpaid') {
      matchPayment = Number(b.is_thanh_toan) === 0 && !b.bill_image;
    } else if (filterPayment.value === 'refund_review') {
      matchPayment = Number(b.is_thanh_toan) === 1 && Number(b.tinh_trang) === 2;
    } else if (filterPayment.value === 'missing_audit') {
      matchPayment = Number(b.is_thanh_toan) === 1 && (!b.confirmed_by_name || !b.payment_confirmed_at);
    }

    return matchSearch && matchStatus && matchPayment;
  });
});

const fetchBookings = async () => {
  loading.value = true;
  try {
    const [resBookings, resRecon] = await Promise.allSettled([
      baseRequest.get('admin/bookings/data'),
      baseRequest.get('admin/bookings/reconciliation')
    ]);

    if (resBookings.status === 'fulfilled' && resBookings.value.data && resBookings.value.data.status && Array.isArray(resBookings.value.data.data)) {
      bookings.value = resBookings.value.data.data;
      if (resBookings.value.data.revenue_summary) {
        serverRevenueSummary.value = resBookings.value.data.revenue_summary;
      }
    }

    if (resRecon.status === 'fulfilled' && resRecon.value.data && resRecon.value.data.status) {
      reconciliationSummary.value = resRecon.value.data.summary || reconciliationSummary.value;
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách đơn đặt xe!");
  } finally {
    loading.value = false;
  }
};

// Cập nhật trạng thái thuê xe qua BookingLifecycleService tập trung (TUYỆT ĐỐI KHÔNG GỬI is_thanh_toan)
const updateRentalStatus = async (booking, status, params = {}) => {
  try {
    const payload = {
      id: booking.id,
      tinh_trang: status,
      ...params
    };
    const res = await baseRequest.post('admin/bookings/status', payload);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Cập nhật trạng thái đơn thuê thành công!");
      booking.tinh_trang = status;
      if (res.data.requires_refund) {
        toaster.warning("Đơn hàng này đã thu tiền trước đó! Vui lòng chuyển bộ phận kế toán đối soát xử lý hoàn tiền theo quy định.");
      }
      await fetchBookings();
      if (selectedBooking.value && selectedBooking.value.id === booking.id) {
        selectedBooking.value = res.data.data || booking;
      }
      return true;
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái đơn thuê.");
      return false;
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Lỗi khi cập nhật trạng thái đơn thuê.";
    toaster.error(msg);
    return false;
  }
};

// Mở modal bàn giao / nhận trả xe kèm ghi chú
const openTransitionModal = (booking, targetStatus, title, noteLabel, icon, iconBg, iconColor, confirmText) => {
  transitionModalData.value = {
    booking,
    targetStatus,
    title,
    noteLabel,
    icon,
    iconBg,
    iconColor,
    confirmText
  };
  transitionNote.value = '';
};

// Thực thi bàn giao / nhận trả xe
const executeTransition = async () => {
  if (!transitionModalData.value) return;
  submittingTransition.value = true;
  try {
    const ok = await updateRentalStatus(
      transitionModalData.value.booking, 
      transitionModalData.value.targetStatus, 
      { status_note: transitionNote.value }
    );
    if (ok) {
      transitionModalData.value = null;
    }
  } finally {
    submittingTransition.value = false;
  }
};

// Mở modal hủy đơn đặt xe
const openCancelModal = (booking) => {
  bookingToCancel.value = booking;
  cancelReasonInput.value = '';
};

// Thực thi hủy đơn đặt xe với lý do bắt buộc
const executeCancelBooking = async () => {
  if (!bookingToCancel.value) return;
  if (!cancelReasonInput.value.trim()) {
    toaster.warning("Vui lòng cung cấp lý do hủy đơn đặt xe!");
    return;
  }

  submittingCancel.value = true;
  try {
    const ok = await updateRentalStatus(
      bookingToCancel.value, 
      2, 
      { cancel_reason: cancelReasonInput.value.trim() }
    );
    if (ok) {
      bookingToCancel.value = null;
    }
  } finally {
    submittingCancel.value = false;
  }
};

// Mở modal xác nhận duyệt thu sau khi đối soát
const openConfirmPaymentModal = (booking) => {
  bookingToConfirmPayment.value = booking;
  paymentConfirmNote.value = 'Đã đối soát biến động số dư ngân hàng';
};

// Xác nhận thanh toán độc lập qua endpoint có phân quyền và lưu vết kiểm toán
const executeConfirmPayment = async () => {
  if (!bookingToConfirmPayment.value) return;
  confirmingPayment.value = true;
  try {
    const res = await baseRequest.post('admin/bookings/confirm-payment', {
      id: bookingToConfirmPayment.value.id,
      payment_note: paymentConfirmNote.value
    });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xác nhận thanh toán thành công!");
      bookingToConfirmPayment.value.is_thanh_toan = 1;
      bookingToConfirmPayment.value.payment_confirmed_at = new Date().toISOString();
      if (res.data.data?.confirmed_by_name) {
        bookingToConfirmPayment.value.confirmed_by_name = res.data.data.confirmed_by_name;
      }
      bookingToConfirmPayment.value = null;
      if (selectedBooking.value) {
        selectedBooking.value.is_thanh_toan = 1;
      }
      await fetchBookings();
    } else {
      toaster.error(res.data?.message || "Không thể xác nhận thanh toán.");
    }
  } catch (err) {
    toaster.error("Lỗi khi xác nhận thanh toán.");
  } finally {
    confirmingPayment.value = false;
  }
};

const requestDeleteBooking = (booking) => {
  if (Number(booking.tinh_trang) !== 2) {
    toaster.error("Không thể xóa đơn đặt xe đang hoạt động để thay thế cho việc hủy đơn! Vui lòng bấm nút 'Hủy' để lưu vết kiểm toán và lý do theo quy định.");
    return;
  }

  if (Number(booking.is_thanh_toan) === 1) {
    toaster.error("Không thể xóa đơn đặt xe đã có phát sinh thanh toán tài chính! Đơn cần được lưu trữ vĩnh viễn để phục vụ đối soát kế toán.");
    return;
  }

  bookingToDelete.value = booking;
};

const executeDeleteBooking = async () => {
  if (!bookingToDelete.value) return;
  
  if (Number(bookingToDelete.value.tinh_trang) !== 2) {
    toaster.error("Không thể xóa đơn đặt xe đang hoạt động để thay thế cho việc hủy đơn! Vui lòng thực hiện thao tác Hủy đơn.");
    bookingToDelete.value = null;
    return;
  }

  if (Number(bookingToDelete.value.is_thanh_toan) === 1) {
    toaster.error("Không thể xóa đơn đặt xe đã có phát sinh thanh toán tài chính!");
    bookingToDelete.value = null;
    return;
  }

  deleting.value = true;
  try {
    const res = await baseRequest.post('admin/bookings/delete', { id: bookingToDelete.value.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa đơn đặt xe!");
      bookingToDelete.value = null;
      await fetchBookings();
    } else {
      toaster.error(res.data?.message || "Không thể xóa đơn đặt xe.");
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Lỗi hệ thống khi xóa đơn.";
    toaster.error(msg);
  } finally {
    deleting.value = false;
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const getStatusText = (status) => {
  const texts = {
    0: 'Chờ duyệt',
    1: 'Đã duyệt',
    2: 'Đã hủy',
    3: 'Đang thuê',
    4: 'Đã nhận trả',
    5: 'Hoàn tất'
  };
  return texts[Number(status)] || 'Khác';
};

// ==========================================
// QUẢN LÝ BIÊN BẢN BÀN GIAO & NHẬN TRẢ XE
// ==========================================
const handoverModalData = ref(null);
const loadingHandoverModal = ref(false);
const submittingHandover = ref(false);

const handoverForm = ref({
  actual_time: '',
  current_km: 0,
  fuel_or_battery_level: 100,
  exterior_status: '',
  interior_status: '',
  checklist: {
    smart_key: true,
    vehicle_documents: true,
    spare_tire: true,
    jack_tool: true,
    portable_charger: true,
    dashcam_working: true,
    clean_interior: true,
    clean_exterior: true,
  },
  staff_notes: '',
  images: [],
  imagePreviews: [],
  is_odometer_adjusted: false,
  odometer_adjustment_reason: '',
  deduct_surcharges_from_deposit: false,
  surcharges: [],
});

const inspectionBooking = ref(null);
const inspectionData = ref(null);
const loadingInspection = ref(false);

const getCurrentDateTimeLocal = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const setHandoverNow = () => {
  handoverForm.value.actual_time = getCurrentDateTimeLocal();
};

const isCarElectric = computed(() => {
  if (!handoverModalData.value?.booking) return false;
  const fuelType = handoverModalData.value.booking.car_fuel_type || '';
  const carName = (handoverModalData.value.booking.car_name || '').toLowerCase();
  return fuelType === 'electric' || carName.includes('vf') || carName.includes('electric');
});

const diffKm = computed(() => {
  if (handoverModalData.value?.type !== 'return') return 0;
  const current = Number(handoverForm.value.current_km || 0);
  const baseline = Number(handoverModalData.value?.delivery?.current_km || handoverModalData.value?.booking?.car_current_km || 0);
  return current - baseline;
});

const diffFuel = computed(() => {
  if (handoverModalData.value?.type !== 'return') return 0;
  const current = Number(handoverForm.value.fuel_or_battery_level || 0);
  const baseline = Number(handoverModalData.value?.delivery?.fuel_or_battery_level || 100);
  return current - baseline;
});

const hoursLate = computed(() => {
  if (handoverModalData.value?.type !== 'return' || !handoverModalData.value?.booking?.end_date) return 0;
  const act = new Date(handoverForm.value.actual_time || new Date());
  const scheduled = new Date(handoverModalData.value.booking.end_date);
  const diffHours = (act - scheduled) / (1000 * 3600);
  return diffHours > 0.5 ? Math.round(diffHours * 10) / 10 : 0;
});

const calcLateSurchargeAmount = computed(() => {
  const hours = hoursLate.value;
  if (hours <= 0) return 0;
  const days = Math.floor(hours / 24);
  const remainingHours = hours % 24;
  return Math.round((days * 600000) + Math.min(600000, remainingHours * 100000));
});

const calcFuelSurchargeAmount = computed(() => {
  const drop = Math.abs(Math.min(0, diffFuel.value));
  if (drop <= 0) return 0;
  const unitRate = isCarElectric.value ? 5000 : 15000;
  return Math.round(drop * unitRate);
});

const heldDepositAmount = computed(() => {
  return Number(handoverModalData.value?.booking?.financial_summary?.deposit_held_amount || 0);
});

const totalSurchargesAmount = computed(() => {
  return (handoverForm.value.surcharges || []).reduce((sum, s) => sum + (Math.round(Number(s.amount)) || 0), 0);
});

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('vi-VN');
};

const getHandoverImageUrl = (bookingId, imagePath) => {
  if (!imagePath) return '';
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) return imagePath;
  const filename = imagePath.split('/').pop();
  const token = localStorage.getItem('token_admin') || localStorage.getItem('token_client');
  const url = `http://localhost:8000/api/bookings/${bookingId}/handovers/images/${filename}`;
  return token ? `${url}?token=${encodeURIComponent(token)}` : url;
};

const openHandoverModal = async (booking, type) => {
  handoverModalData.value = {
    booking,
    type,
    delivery: null,
    comparison: null,
  };

  handoverForm.value = {
    actual_time: getCurrentDateTimeLocal(),
    current_km: Number(booking.car_current_km || 0),
    fuel_or_battery_level: 100,
    exterior_status: 'Thân vỏ nguyên vẹn, không móp méo trầy xước.',
    interior_status: 'Nội thất sạch sẽ, các nút bấm hoạt động tốt.',
    checklist: {
      smart_key: true,
      vehicle_documents: true,
      spare_tire: true,
      jack_tool: true,
      portable_charger: true,
      dashcam_working: true,
      clean_interior: true,
      clean_exterior: true,
    },
    staff_notes: '',
    images: [],
    imagePreviews: [],
    is_odometer_adjusted: false,
    odometer_adjustment_reason: '',
    deduct_surcharges_from_deposit: false,
    surcharges: [],
  };

  if (type === 'return') {
    loadingHandoverModal.value = true;
    try {
      const res = await baseRequest.get(`admin/bookings/${booking.id}/handovers`);
      if (res.data.status && res.data.data) {
        handoverModalData.value.delivery = res.data.data.delivery;
        handoverModalData.value.comparison = res.data.data.comparison;

        if (res.data.data.delivery?.current_km) {
          handoverForm.value.current_km = Number(res.data.data.delivery.current_km);
        }

        if (res.data.data.comparison?.suggested_surcharges?.length > 0) {
          handoverForm.value.surcharges = res.data.data.comparison.suggested_surcharges.map(s => ({
            type: s.type,
            amount: s.amount,
            reason: s.reason,
          }));
        }

        if (Number(booking.financial_summary?.deposit_held_amount || 0) > 0) {
          handoverForm.value.deduct_surcharges_from_deposit = true;
        }
      }
    } catch (err) {
      console.warn("Could not load delivery baseline:", err);
    } finally {
      loadingHandoverModal.value = false;
    }
  }
};

const closeHandoverModal = () => {
  handoverModalData.value = null;
};

const handleHandoverPhotoUpload = (e) => {
  const files = Array.from(e.target.files || []);
  for (const file of files) {
    if (file.size > 5 * 1024 * 1024) {
      toaster.error(`Tệp ${file.name} vượt quá dung lượng tối đa 5MB!`);
      continue;
    }
    handoverForm.value.images.push(file);
    handoverForm.value.imagePreviews.push(URL.createObjectURL(file));
  }
};

const removeHandoverPhoto = (idx) => {
  handoverForm.value.images.splice(idx, 1);
  handoverForm.value.imagePreviews.splice(idx, 1);
};

const addPresetSurcharge = (type, amount, reason) => {
  handoverForm.value.surcharges.push({ type, amount, reason });
};

const addSuggestedFuelSurcharge = () => {
  const amt = calcFuelSurchargeAmount.value;
  if (amt <= 0) return;
  const reason = `Hao hụt ${Math.abs(diffFuel.value)}% ${isCarElectric.value ? 'pin điện' : 'nhiên liệu'} so với lúc nhận xe`;
  handoverForm.value.surcharges.push({
    type: 'fuel_diff',
    amount: amt,
    reason,
  });
};

const addSuggestedLateSurcharge = () => {
  const amt = calcLateSurchargeAmount.value;
  if (amt <= 0) return;
  const reason = `Trả xe trễ ${hoursLate.value} giờ so với lịch hẹn hợp đồng`;
  handoverForm.value.surcharges.push({
    type: 'late_return',
    amount: amt,
    reason,
  });
};

const addCustomSurcharge = () => {
  handoverForm.value.surcharges.push({
    type: 'damage',
    amount: 200000,
    reason: 'Phụ phí phát sinh',
  });
};

const removeSurchargeItem = (idx) => {
  handoverForm.value.surcharges.splice(idx, 1);
};

const submitHandoverRecord = async () => {
  if (!handoverModalData.value) return;

  if (handoverModalData.value.type === 'return') {
    if (diffKm.value < 0 && (!handoverForm.value.is_odometer_adjusted || !handoverForm.value.odometer_adjustment_reason?.trim())) {
      toaster.error("Số km nhận trả nhỏ hơn lúc giao! Bạn phải kích hoạt điều chỉnh số km ngoại lệ và nhập lý do giải trình.");
      return;
    }
  }

  submittingHandover.value = true;
  try {
    const formData = new FormData();
    formData.append('type', handoverModalData.value.type);
    formData.append('actual_time', handoverForm.value.actual_time || getCurrentDateTimeLocal());
    formData.append('current_km', Math.round(Number(handoverForm.value.current_km || 0)));
    formData.append('fuel_or_battery_level', Math.round(Number(handoverForm.value.fuel_or_battery_level || 100)));
    formData.append('exterior_status', handoverForm.value.exterior_status || '');
    formData.append('interior_status', handoverForm.value.interior_status || '');
    formData.append('staff_notes', handoverForm.value.staff_notes || '');

    Object.keys(handoverForm.value.checklist).forEach(key => {
      formData.append(`checklist[${key}]`, handoverForm.value.checklist[key] ? '1' : '0');
    });

    handoverForm.value.images.forEach(f => {
      formData.append('images[]', f);
    });

    if (handoverModalData.value.type === 'return') {
      formData.append('is_odometer_adjusted', handoverForm.value.is_odometer_adjusted ? '1' : '0');
      if (handoverForm.value.is_odometer_adjusted) {
        formData.append('odometer_adjustment_reason', handoverForm.value.odometer_adjustment_reason || '');
      }
      formData.append('deduct_surcharges_from_deposit', handoverForm.value.deduct_surcharges_from_deposit ? '1' : '0');

      (handoverForm.value.surcharges || []).forEach((sc, idx) => {
        formData.append(`surcharges[${idx}][type]`, sc.type);
        formData.append(`surcharges[${idx}][amount]`, Math.round(Number(sc.amount || 0)));
        formData.append(`surcharges[${idx}][reason]`, sc.reason || '');
      });
    }

    const res = await baseRequest.post(`admin/bookings/${handoverModalData.value.booking.id}/handovers`, formData);
    if (res.data.status) {
      toaster.success(res.data.message || "Lập biên bản thành công!");
      closeHandoverModal();
      await fetchBookings();
    } else {
      toaster.error(res.data.message || "Lập biên bản thất bại!");
    }
  } catch (err) {
    console.error("Error submitting handover:", err);
    toaster.error(err.response?.data?.message || "Có lỗi xảy ra khi lưu biên bản!");
  } finally {
    submittingHandover.value = false;
  }
};

const openInspectionModal = async (booking) => {
  inspectionBooking.value = booking;
  loadingInspection.value = true;
  inspectionData.value = null;

  try {
    const res = await baseRequest.get(`admin/bookings/${booking.id}/handovers`);
    if (res.data.status && res.data.data) {
      inspectionData.value = res.data.data;
    } else {
      toaster.error(res.data.message || "Không thể tải dữ liệu biên bản bàn giao!");
    }
  } catch (err) {
    console.error("Error fetching inspection report:", err);
    toaster.error("Lỗi khi tải dữ liệu biên bản bàn giao!");
  } finally {
    loadingInspection.value = false;
  }
};

const closeInspectionModal = () => {
  inspectionBooking.value = null;
  inspectionData.value = null;
};

const printInspectionReport = () => {
  window.print();
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    selectedBooking.value = null;
    fullImageUrl.value = null;
    bookingToDelete.value = null;
    bookingToCancel.value = null;
    transitionModalData.value = null;
    bookingToConfirmPayment.value = null;
    handoverModalData.value = null;
    inspectionBooking.value = null;
  }
};

onMounted(() => {
  if (route.query.search) {
    searchQuery.value = String(route.query.search);
  }
  if (route.query.status !== undefined && route.query.status !== '') {
    filterStatus.value = String(route.query.status);
  }
  if (route.query.payment !== undefined && route.query.payment !== '') {
    filterPayment.value = String(route.query.payment);
  }
  fetchBookings();
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

.header-main { display: flex; align-items: center; gap: 16px; }
.header-icon {
  width: 52px; height: 52px;
  background: white; border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #8B5CF6;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}

.page-title { font-size: 1.6rem; font-weight: 800; color: var(--color-text-main); font-family: var(--font-heading); }
.page-title span { color: #8B5CF6; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.9rem; margin-top: 2px; }

/* Summary Grid */
.summary-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: white;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.card-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
}

.card-icon.blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.card-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.card-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.card-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.card-info { display: flex; flex-direction: column; }
.card-info .label { font-size: 0.75rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; }
.card-info .value { font-size: 1.3rem; font-weight: 800; color: var(--color-text-main); margin-top: 2px; font-family: var(--font-heading); }
.card-info .sub-label { font-size: 0.7rem; color: #10B981; font-weight: 600; margin-top: 2px; }

/* Content Table */
.content-card { padding: 0; overflow: hidden; position: relative; }
.table-tools { 
  padding: 18px 24px; 
  display: flex; 
  justify-content: space-between; 
  gap: 16px; 
  border-bottom: 1px solid var(--color-border);
  background: white;
}

.search-input { position: relative; flex: 1; max-width: 360px; }
.search-input i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); }
.search-input input { 
  width: 100%; 
  padding: 10px 14px 10px 40px; 
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  background: var(--bg-surface-alt); 
  outline: none; 
  font-size: 0.88rem;
  transition: all 0.2s; 
}
.search-input input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12); }

.filter-group { display: flex; gap: 12px; }
.select-wrapper { position: relative; display: flex; align-items: center; }
.select-wrapper i { position: absolute; left: 12px; color: var(--color-text-muted); font-size: 0.8rem; pointer-events: none; }

.form-select {
  padding: 9px 16px 9px 34px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-main);
  outline: none;
  cursor: pointer;
}

.form-select:focus { border-color: #8b5cf6; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { 
  background: var(--bg-surface-alt); 
  padding: 14px 18px; 
  text-align: left; 
  font-size: 0.75rem; 
  font-weight: 700; 
  color: var(--color-text-muted); 
  text-transform: uppercase; 
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}
.data-table td { padding: 14px 18px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }

.booking-id { font-family: monospace; font-weight: 700; color: var(--color-primary); background: rgba(37, 99, 235, 0.08); padding: 4px 8px; border-radius: 4px; font-size: 0.82rem; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; color: #475569; }
.user-info .name { display: block; font-weight: 700; color: var(--color-text-main); font-size: 0.88rem; }
.user-info .email { display: block; font-size: 0.72rem; color: var(--color-text-muted); }
.user-info .phone { display: block; font-size: 0.72rem; color: var(--color-text-muted); margin-top: 1px; }
.badge-creator-admin {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.68rem;
  font-weight: 700;
  color: #0369A1;
  background: #E0F2FE;
  padding: 1px 6px;
  border-radius: 4px;
  margin-top: 3px;
}

.car-title { font-weight: 700; color: var(--color-text-main); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; }
.car-title i { color: var(--color-primary); }

.date-cell { font-size: 0.78rem; color: var(--color-text-muted); line-height: 1.5; }
.date-cell i { width: 14px; color: var(--color-primary); }

.price-cell .price { font-weight: 800; color: var(--color-text-main); font-size: 0.92rem; }

.pay-badge { 
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem; 
  font-weight: 700; 
  padding: 4px 10px; 
  border-radius: 100px; 
}
.pay-badge.paid { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
.pay-badge.partial { background: #FEF3C7; color: #B45309; border: 1px solid #FCD34D; }
.pay-badge.verifying { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
.pay-badge.unpaid { background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }
.pay-badge.refund-review { background: #FFF1F2; color: #BE123C; border: 1px solid #FECDD3; font-weight: 800; }

.price-breakdown-sub {
  display: flex;
  flex-direction: column;
  font-size: 0.72rem;
  margin-top: 3px;
  line-height: 1.3;
}
.deposit-tag {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: #E0F2FE;
  color: #0369A1;
  border: 1px solid #BAE6FD;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 0.68rem;
  font-weight: 700;
  margin-top: 2px;
}

.financial-alert-box {
  background: #FFF1F2;
  border: 1px solid #FECDD3;
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: #9F1239;
  font-size: 0.82rem;
  line-height: 1.4;
}
.financial-alert-box i {
  font-size: 1.2rem;
  color: #E11D48;
  margin-top: 2px;
}
.financial-alert-box strong {
  display: block;
  font-size: 0.88rem;
  margin-bottom: 2px;
}

.summary-card.pointer { cursor: pointer; transition: transform var(--transition-fast), border-color var(--transition-fast); }
.summary-card.pointer:hover { transform: translateY(-2px); border-color: #F43F5E; }
.card-discrepancy-active { border-color: #E11D48 !important; box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.2); }
.text-danger { color: #E11D48 !important; }

.pay-cell-wrap {
  display: flex;
  flex-direction: column;
  gap: 3px;
  align-items: flex-start;
}

.audit-note {
  font-size: 0.68rem;
  color: #15803D;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 3px;
}

.card-icon.cyan { background: rgba(6, 182, 212, 0.1); color: #0891b2; }

.status-badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; }
.status-0 { background: #FEF3C7; color: #B45309; }
.status-1 { background: #E0E7FF; color: #4338CA; border: 1px solid #C7D2FE; }
.status-2 { background: #FEE2E2; color: #B91C1C; }
.status-3 { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
.status-4 { background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; }
.status-5 { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }

.actions-cell { text-align: center; }
.action-btn-group { display: inline-flex; align-items: center; gap: 6px; }

.btn-action { 
  height: 32px; 
  padding: 0 10px;
  border-radius: var(--radius-sm); 
  border: 1px solid var(--color-border); 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  gap: 5px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer; 
  transition: all 0.15s; 
  background: white;
  color: var(--color-text-main);
}

.btn-action.view:hover { background: var(--bg-surface-alt); color: var(--color-primary); border-color: var(--color-primary); }
.btn-action.confirm { background: #10B981; color: white; border-color: #10B981; }
.btn-action.confirm:hover { background: #059669; }
.btn-action.deliver { background: #0284C7; color: white; border-color: #0284C7; }
.btn-action.deliver:hover { background: #0369A1; }
.btn-action.return-car { background: #7C3AED; color: white; border-color: #7C3AED; }
.btn-action.return-car:hover { background: #6D28D9; }
.btn-action.complete { background: #10B981; color: white; border-color: #10B981; }
.btn-action.complete:hover { background: #059669; }
.btn-action.pay { background: #8B5CF6; color: white; border-color: #8B5CF6; }
.btn-action.pay:hover { background: #7C3AED; }
.btn-action.ledger { color: #0284C7; border-color: #BAE6FD; background: #F0F9FF; }
.btn-action.ledger:hover { background: #0284C7; color: white; border-color: #0284C7; }
.btn-action.cancel { color: #B45309; }
.btn-action.cancel:hover { background: #FEF3C7; border-color: #F59E0B; }
.btn-action.delete { color: #DC2626; }
.btn-action.delete:hover { background: #FEF2F2; border-color: #DC2626; }

/* Timeline styles in Admin Detail */
.admin-timeline {
  display: flex;
  flex-direction: column;
  gap: 16px;
  position: relative;
  padding-left: 28px;
  margin-top: 12px;
}
.admin-timeline::before {
  content: '';
  position: absolute;
  top: 10px;
  bottom: 10px;
  left: 11px;
  width: 2px;
  background: var(--color-border);
}
.timeline-node {
  position: relative;
  display: flex;
  gap: 12px;
}
.node-icon {
  position: absolute;
  left: -28px;
  top: 2px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #E2E8F0;
  color: #64748B;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  z-index: 1;
}
.timeline-node.active .node-icon {
  background: #10B981;
  color: white;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}
.timeline-node.cancelled .node-icon {
  background: #EF4444;
  color: white;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}
.node-content {
  flex: 1;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  border: 1px solid var(--color-border);
}
.node-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.node-title {
  font-size: 0.85rem;
  color: var(--color-text-main);
}
.node-time {
  font-size: 0.72rem;
  color: var(--color-text-muted);
  font-weight: 600;
}
.node-desc {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin: 0;
}
.node-operator {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.7rem;
  color: #0284C7;
  font-weight: 700;
  margin-top: 4px;
}
.note-audit-box {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-size: 0.8rem;
}
.note-pre-text {
  font-family: inherit;
  font-size: 0.78rem;
  color: #334155;
  white-space: pre-wrap;
  margin: 6px 0 0 0;
}

/* Bill Style */
.bill-thumb-container {
  position: relative;
  width: 52px;
  height: 52px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid var(--color-border);
}

.bill-thumb { width: 100%; height: 100%; object-fit: cover; }
.bill-thumb-container .overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s;
  font-size: 0.85rem;
}
.bill-thumb-container:hover .overlay { opacity: 1; }

.no-bill {
  font-size: 0.72rem;
  color: var(--color-text-muted);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(11, 18, 32, 0.65);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  width: 100%;
  max-width: 600px;
  padding: 0;
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-main);
  font-family: var(--font-heading);
}

.modal-sub { color: var(--color-text-muted); font-size: 0.82rem; margin-top: 2px; }

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
}

.modal-body { padding: 24px; }

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-section {
  padding: 14px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.detail-section.full { grid-column: span 2; }

.detail-section h4 {
  font-size: 0.82rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--color-text-muted);
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.detail-section p {
  font-size: 0.88rem;
  color: var(--color-text-main);
  margin-bottom: 4px;
}

.status-summary-row {
  display: flex;
  gap: 24px;
  align-items: center;
  margin-top: 4px;
}

.label-muted {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-right: 8px;
}

.bill-preview-large {
  margin-top: 8px;
  text-align: center;
  cursor: pointer;
  border-radius: var(--radius-sm);
  overflow: hidden;
  border: 1px dashed var(--color-border);
  padding: 10px;
  background: white;
}

.bill-preview-large img {
  max-width: 100%;
  max-height: 240px;
  object-fit: contain;
  border-radius: 6px;
}

.click-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--color-primary);
  margin-top: 6px;
  font-weight: 600;
}

.modal-footer {
  padding: 14px 24px;
  background: var(--bg-surface-alt);
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Image Viewer with Zoom */
.image-viewer-container {
  display: flex;
  flex-direction: column;
  max-width: 90vw;
  max-height: 90vh;
  background: rgba(11, 18, 32, 0.95);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
}

.viewer-toolbar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tool-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.tool-btn:hover { background: rgba(255, 255, 255, 0.3); }
.tool-btn.close-tool { margin-left: 20px; background: rgba(239, 68, 68, 0.4); }
.tool-btn.close-tool:hover { background: #EF4444; }

.zoom-value { color: white; font-size: 0.85rem; font-weight: 700; min-width: 50px; text-align: center; }

.image-scroll-box {
  padding: 20px;
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  max-height: calc(90vh - 60px);
}

.image-scroll-box img {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
  transition: transform 0.2s ease-out;
  border-radius: 8px;
}

/* Operational Disclaimer & Audit Warning */
.viewer-disclaimer {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #FFFBEB;
  border-bottom: 1px solid #FDE68A;
  padding: 12px 20px;
  font-size: 0.82rem;
  color: #92400E;
  line-height: 1.45;
}

.viewer-disclaimer i {
  font-size: 1.25rem;
  color: #D97706;
  flex-shrink: 0;
}

.receipt-audit-warning {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  font-size: 0.82rem;
  color: #92400E;
  line-height: 1.45;
}

.receipt-audit-warning.mini {
  padding: 8px 12px;
  font-size: 0.78rem;
}

.receipt-audit-warning i {
  color: #D97706;
  font-size: 1.1rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.audit-detail {
  font-size: 0.76rem;
  color: var(--color-text-muted);
  line-height: 1.5;
}

/* Confirm Payment Modal */
.confirm-modal { max-width: 520px; }

.confirm-icon-wrapper {
  width: 44px;
  height: 44px;
  background: rgba(139, 92, 246, 0.12);
  color: #8B5CF6;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.confirm-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.confirm-summary-box {
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.86rem;
  color: var(--color-text-muted);
}

.summary-line strong { color: var(--color-text-main); font-family: monospace; }
.summary-line.highlight {
  border-top: 1px dashed var(--color-border);
  padding-top: 8px;
  margin-top: 4px;
  font-size: 0.92rem;
}

.summary-line.highlight .price-val {
  font-size: 1.1rem;
  font-weight: 800;
  color: #10B981;
}

.btn-confirm-action {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 9px 20px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
  transition: all 0.2s;
}

.btn-confirm-action:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
}

.btn-confirm-action:disabled { opacity: 0.6; cursor: not-allowed; }

/* Delete Modal */
.delete-modal { max-width: 480px; }
.delete-icon-wrapper {
  width: 44px;
  height: 44px;
  background: #FEE2E2;
  color: #DC2626;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.delete-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.highlight-box {
  margin-top: 12px;
  padding: 12px 16px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border-left: 4px solid #DC2626;
  display: flex;
  flex-direction: column;
}

.highlight-box strong { font-size: 0.95rem; color: var(--color-text-main); }
.highlight-box span { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px; }

.btn-danger {
  background: #DC2626;
  color: white;
  border: none;
  padding: 9px 18px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover { background: #B91C1C; }

.empty-state-box {
  padding: 48px 24px;
  text-align: center;
  color: var(--color-text-muted);
}

.empty-state-box i { font-size: 2.4rem; opacity: 0.4; margin-bottom: 12px; }
.empty-state-box h4 { font-weight: 700; color: var(--color-text-main); margin-bottom: 4px; }

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(139, 92, 246, 0.2);
  border-top-color: #8B5CF6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Ledger Modal & Financial Components */
.ledger-modal {
  max-width: 960px;
  width: 95%;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}

.ledger-modal .modal-body {
  overflow-y: auto;
  max-height: calc(92vh - 140px);
  padding: 20px;
}

.ledger-icon-wrapper {
  width: 44px;
  height: 44px;
  background: #E0F2FE;
  color: #0284C7;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  margin-right: 12px;
  flex-shrink: 0;
}

.ledger-metric-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.ledger-card {
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.ledger-card .metric-icon {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.ledger-card.rent-total .metric-icon { background: rgba(37, 99, 235, 0.1); color: #2563EB; }
.ledger-card.rent-paid .metric-icon { background: rgba(16, 185, 129, 0.1); color: #10B981; }
.ledger-card.rent-remaining .metric-icon { background: rgba(245, 158, 11, 0.1); color: #D97706; }
.ledger-card.deposit-held .metric-icon { background: rgba(6, 182, 212, 0.1); color: #0891B2; }

.ledger-card .metric-info { display: flex; flex-direction: column; min-width: 0; }
.ledger-card .metric-label { font-size: 0.7rem; color: var(--color-text-muted); font-weight: 600; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ledger-card .metric-val { font-size: 1.05rem; font-weight: 800; color: var(--color-text-main); margin-top: 2px; }
.ledger-card .metric-sub { font-size: 0.68rem; color: var(--color-text-muted); }

.deposit-breakdown-bar {
  display: flex;
  gap: 20px;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: var(--radius-sm);
  padding: 10px 16px;
  font-size: 0.8rem;
}
.deposit-breakdown-bar .breakdown-item { display: flex; align-items: center; gap: 6px; }

.new-tx-box {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: var(--radius-sm);
  padding: 14px 16px;
}
.new-tx-box .box-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  user-select: none;
}
.new-tx-box .title-wrap { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: var(--color-text-main); }
.new-tx-box .btn-toggle { background: transparent; border: none; color: var(--color-text-muted); cursor: pointer; }
.form-row { display: flex; gap: 12px; }
.form-col { flex: 1; }

.ledger-table-wrapper { margin-top: 16px; }
.ledger-table-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; color: var(--color-text-main); display: flex; align-items: center; gap: 8px; }
.ledger-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}
.ledger-table th {
  background: var(--bg-surface-alt);
  padding: 8px 12px;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-align: left;
  border-bottom: 2px solid var(--color-border);
}
.ledger-table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}
.ledger-table .ref-code {
  font-family: monospace;
  font-size: 0.75rem;
  background: #F1F5F9;
  padding: 2px 6px;
  border-radius: 4px;
  color: #475569;
}
.method-tag {
  font-size: 0.72rem;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  padding: 2px 8px;
  border-radius: 4px;
  color: #475569;
}
.admin-operator {
  font-size: 0.72rem;
  color: #0284C7;
  font-weight: 600;
}
.deduction-reason-badge {
  margin-top: 4px;
  font-size: 0.72rem;
  background: #FEF2F2;
  color: #DC2626;
  border: 1px solid #FECACA;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.empty-tx-box {
  padding: 32px 16px;
  text-align: center;
  background: #F8FAFC;
  border-radius: var(--radius-sm);
  color: var(--color-text-muted);
}
.empty-tx-box i { font-size: 1.8rem; opacity: 0.4; margin-bottom: 8px; }

/* Badges */
.badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; }
.badge-success { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
.badge-warning { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
.badge-info { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
.badge-secondary { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
.badge-danger { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }

@media (max-width: 992px) {
  .ledger-metric-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 992px) {
  .summary-section { grid-template-columns: 1fr 1fr; }
  .table-tools { flex-direction: column; }
  .search-input { max-width: 100%; }
}

@media (max-width: 640px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-section.full { grid-column: span 1; }
}

/* ============================================================
   HANDOVER & INSPECTION MODAL STYLES (Mobile First)
   ============================================================ */
.btn-action.inspection {
  background: #F0FDF4;
  color: #16A34A;
  border-color: #BBF7D0;
}
.btn-action.inspection:hover {
  background: #DCFCE7;
}

.handover-modal {
  max-width: 820px;
  width: 95%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.handover-modal-body {
  overflow-y: auto;
  padding: 20px;
}
.handover-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}
.handover-type-badge.delivery {
  background: #E0F2FE;
  color: #0369A1;
}
.handover-type-badge.return {
  background: #EDE9FE;
  color: #6D28D9;
}

.delivery-baseline-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px;
}
.baseline-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  margin-bottom: 8px;
}
.baseline-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 10px;
  font-size: 0.82rem;
}
.b-item { display: flex; flex-direction: column; gap: 2px; }
.b-lbl { font-size: 0.72rem; color: #64748B; }
.b-val { font-size: 0.88rem; }

.realtime-diff-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  background: white;
  border: 1px solid #E2E8F0;
  padding: 8px 12px;
  border-radius: 8px;
}
.diff-chip {
  font-size: 0.82rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-section-box {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 16px;
}
.section-badge-title {
  font-weight: 700;
  font-size: 0.88rem;
  color: #1E293B;
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-subtext {
  font-size: 0.78rem;
  color: #64748B;
  margin-top: 2px;
}

.km-input-wrapper {
  display: flex;
  gap: 8px;
  align-items: center;
}
.quick-km-btns { display: flex; gap: 4px; }
.btn-quick-tag {
  background: white;
  border: 1px solid #CBD5E1;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}
.btn-quick-tag:hover { background: #F1F5F9; border-color: #94A3B8; }

.odometer-alert-card {
  background: #FEF2F2;
  border: 1px solid #FECACA;
  border-radius: 8px;
  padding: 12px;
}
.odometer-alert-card .alert-head {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #DC2626;
  font-size: 0.85rem;
  margin-bottom: 4px;
}
.odometer-alert-card .alert-desc {
  font-size: 0.78rem;
  color: #991B1B;
  line-height: 1.4;
}

.fuel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}
.level-indicator {
  font-size: 1.1rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 6px;
}
.level-indicator.high { background: #DCFCE7; color: #15803D; }
.level-indicator.med { background: #FEF3C7; color: #B45309; }
.level-indicator.low { background: #FEE2E2; color: #DC2626; }

.fuel-range-slider {
  width: 100%;
  accent-color: #0284C7;
  cursor: pointer;
}
.quick-level-chips { display: flex; gap: 8px; }
.btn-level-chip {
  background: white;
  border: 1px solid #CBD5E1;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-level-chip:hover { background: #F1F5F9; }

.checklist-touch-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}
.checklist-touch-item {
  min-height: 54px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: white;
  border: 2px solid #E2E8F0;
  border-radius: 10px;
  cursor: pointer;
  user-select: none;
  transition: all 0.15s ease-in-out;
}
.checklist-touch-item:hover {
  border-color: #CBD5E1;
  transform: translateY(-1px);
}
.checklist-touch-item.checked {
  border-color: #10B981;
  background: #F0FDF4;
}
.checklist-touch-item .check-icon {
  font-size: 1.25rem;
  color: #94A3B8;
}
.checklist-touch-item.checked .check-icon {
  color: #10B981;
}
.checklist-touch-item .check-text strong {
  display: block;
  font-size: 0.82rem;
  color: #1E293B;
}
.checklist-touch-item .check-text small {
  font-size: 0.7rem;
  color: #64748B;
}

.quick-text-chips { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-text-chip {
  background: white;
  border: 1px solid #CBD5E1;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.72rem;
  color: #475569;
  cursor: pointer;
}
.btn-text-chip:hover { background: #F1F5F9; color: #1E293B; }

.photo-upload-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.upload-dropzone {
  border: 2px dashed #93C5FD;
  background: #EFF6FF;
  border-radius: 10px;
  padding: 16px;
  text-align: center;
  cursor: pointer;
  display: block;
  transition: background 0.2s;
}
.upload-dropzone:hover { background: #DBEAFE; }
.dropzone-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.dropzone-inner span {
  font-weight: 700;
  font-size: 0.85rem;
  color: #1E40AF;
}
.dropzone-inner small {
  font-size: 0.72rem;
  color: #64748B;
}

.previews-grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.prev-thumb-card {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #CBD5E1;
}
.prev-thumb-card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.btn-del-thumb {
  position: absolute;
  top: 2px;
  right: 2px;
  background: rgba(0,0,0,0.6);
  color: white;
  border: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.quick-surcharge-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.btn-sc-preset {
  background: white;
  border: 1px solid #CBD5E1;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.btn-sc-preset:hover { background: #F8FAFC; border-color: #94A3B8; }
.btn-sc-preset.highlight {
  border-color: #F59E0B;
  background: #FEF3C7;
  color: #B45309;
}
.btn-sc-preset.custom {
  border-color: #93C5FD;
  color: #0284C7;
}

.surcharges-table-box {
  background: white;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  overflow: hidden;
}
.sc-table {
  width: 100%;
  font-size: 0.8rem;
  border-collapse: collapse;
}
.sc-table th, .sc-table td {
  padding: 8px 10px;
  border-bottom: 1px solid #E2E8F0;
}
.sc-table th {
  background: #F8FAFC;
  font-size: 0.75rem;
  color: #64748B;
  text-align: left;
}
.btn-del-sc {
  background: none;
  border: none;
  color: #EF4444;
  font-size: 1.2rem;
  cursor: pointer;
}

.deposit-deduction-card {
  background: #EFF6FF;
  border: 1px solid #BFDBFE;
  padding: 12px;
  border-radius: 8px;
}

/* Inspection View Modal */
.inspection-modal {
  max-width: 900px;
  width: 95%;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}
.inspection-body {
  overflow-y: auto;
  padding: 24px;
  background: white;
}
.inspection-document-sheet {
  background: white;
  color: #0F172A;
  font-family: inherit;
}
.sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 2px solid #0F172A;
  padding-bottom: 16px;
  margin-bottom: 16px;
}
.company-brand .brand-logo-mark {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0284C7;
  letter-spacing: -0.5px;
}
.company-brand .brand-sub {
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
}
.company-brand .company-meta {
  font-size: 0.72rem;
  color: #94A3B8;
  display: block;
  margin-top: 2px;
}
.sheet-title-block {
  text-align: right;
}
.sheet-title-block h2 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0F172A;
  margin: 0;
}
.sheet-title-block .doc-code {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0284C7;
  margin-top: 2px;
}
.sheet-title-block .doc-date {
  font-size: 0.75rem;
  color: #64748B;
}

.sheet-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 0.82rem;
}
.meta-item { display: flex; flex-direction: column; gap: 2px; }
.meta-item .m-lbl { font-size: 0.72rem; color: #64748B; }

.inspection-comparison-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.inspection-col {
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px;
  background: #FAFAFA;
}
.inspection-col .col-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  font-weight: 800;
  color: #1E293B;
  border-bottom: 2px solid #E2E8F0;
  padding-bottom: 8px;
  margin-bottom: 12px;
}
.stat-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
  padding: 4px 0;
  border-bottom: 1px dashed #E2E8F0;
}
.stat-row.highlight {
  background: white;
  padding: 6px 8px;
  border-radius: 6px;
  margin: 4px 0;
  border: 1px solid #E2E8F0;
}
.stat-row .val-metric {
  font-size: 0.92rem;
  color: #0284C7;
}

.checklist-summary-box .box-lbl, .photos-strip .box-lbl {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 6px;
}
.clean-check-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.78rem;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.clean-check-list li {
  display: flex;
  align-items: center;
  gap: 6px;
}
.status-notes-box {
  background: white;
  border: 1px solid #E2E8F0;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 0.78rem;
}
.photos-grid {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.inspection-img {
  width: 75px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #CBD5E1;
  cursor: pointer;
  transition: transform 0.15s;
}
.inspection-img:hover {
  transform: scale(1.05);
}

.diff-summary-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px;
}
.diff-title {
  font-size: 0.88rem;
  font-weight: 800;
  color: #1E293B;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
.diff-metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
  font-size: 0.85rem;
}
.diff-metrics-grid .dm-item {
  display: flex;
  justify-content: space-between;
  background: white;
  border: 1px solid #E2E8F0;
  padding: 8px 12px;
  border-radius: 6px;
}
.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  background: white;
  border: 1px solid #E2E8F0;
  border-radius: 6px;
  overflow: hidden;
}
.report-table th, .report-table td {
  padding: 8px 12px;
  border-bottom: 1px solid #E2E8F0;
}
.report-table th {
  background: #F1F5F9;
  font-size: 0.75rem;
  color: #475569;
  text-align: left;
}

.confirmation-section-sheet {
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px;
  background: #F8FAFC;
}
.confirm-head {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.88rem;
  font-weight: 800;
  color: #1E293B;
}
.confirm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.confirm-box {
  background: white;
  border: 1px solid #E2E8F0;
  padding: 12px;
  border-radius: 8px;
}
.confirm-box .c-title {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748B;
  margin-bottom: 6px;
}
.confirm-box .c-status {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.confirm-box .c-status.ok { color: #16A34A; }
.confirm-box .c-status.pending { color: #D97706; }
.confirm-box .c-status strong { font-size: 0.82rem; }
.confirm-box .c-status small { font-size: 0.72rem; color: #64748B; }
.cust-note-text {
  margin-top: 4px;
  font-style: italic;
  font-size: 0.75rem;
  color: #1E293B;
  background: #F8FAFC;
  padding: 4px 8px;
  border-radius: 4px;
  border-left: 3px solid #0284C7;
}

.legal-disclaimer-box {
  background: #FFFBEB;
  border: 1px solid #FCD34D;
  border-radius: 8px;
  padding: 12px 14px;
  font-size: 0.75rem;
  color: #92400E;
  line-height: 1.45;
}
.legal-disclaimer-box .disclaimer-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  margin-bottom: 4px;
}

@media print {
  body * {
    visibility: hidden;
  }
  #inspection-printable-area, #inspection-printable-area * {
    visibility: visible;
  }
  #inspection-printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10mm;
    background: white !important;
    color: black !important;
  }
  .no-print {
    display: none !important;
  }
}

@media (max-width: 768px) {
  .inspection-comparison-grid, .confirm-grid {
    grid-template-columns: 1fr;
  }
  .sheet-header {
    flex-direction: column;
    gap: 10px;
  }
  .sheet-title-block {
    text-align: left;
  }
}
</style>
