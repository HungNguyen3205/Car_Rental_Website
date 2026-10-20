<template>
  <div class="booking-detail-page">
    <div class="app-container py-10">
      <!-- Header Actions -->
      <div class="detail-actions animate-in">
        <button @click="router.back()" class="btn-back" type="button">
          <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
        </button>
        <div class="right-actions">
          <button @click="exportToPDF" class="btn-export" :disabled="isExporting" type="button">
            <i v-if="!isExporting" class="fa-solid fa-file-pdf"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            {{ isExporting ? 'Đang tạo PDF...' : 'Xuất Hóa Đơn PDF' }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state card">
        <div class="spinner"></div>
        <p>Đang tải thông tin đơn đặt xe...</p>
      </div>

      <!-- Not Found -->
      <div v-else-if="!booking" class="empty-state card text-center">
        <i class="fa-solid fa-triangle-exclamation empty-icon"></i>
        <h3>Không tìm thấy đơn đặt xe</h3>
        <p>Đơn đặt xe không tồn tại hoặc bạn không có quyền truy cập.</p>
        <router-link to="/my-bookings" class="btn-primary mt-4">Quay về danh sách</router-link>
      </div>

      <!-- Invoice Wrapper -->
      <div v-else id="invoice-content" ref="invoiceRef" class="invoice-wrapper card animate-in delay-1">
        <!-- Invoice Header -->
        <div class="invoice-header">
          <div class="company-info">
            <div class="brand-logo">
              <div class="logo-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.4-.8l-4.2-3.15A2 2 0 0 0 16 8.35L14 8H9a2 2 0 0 0-2 2v3m-2-1H3M2 16h2m13-4v4m-5 4a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-10 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
                </svg>
              </div>
              <span class="logo-text">Drive<span>Rent</span></span>
            </div>
            <p class="company-sub">Hệ sinh thái thuê xe điện thông minh toàn quốc</p>
            <p class="company-contact">Hotline: 1900 1234 | Email: support@driverent.vn</p>
          </div>
          
          <div class="invoice-meta">
            <h2>HÓA ĐƠN THUÊ XE</h2>
            <div class="invoice-id">#{{ booking.ma_dat_xe }}</div>
            <div class="invoice-date">Ngày tạo: {{ formatDate(booking.created_at || new Date()) }}</div>
          </div>
        </div>

        <div class="invoice-divider"></div>

        <!-- Lifecycle Timeline Progress Tracker -->
        <div class="client-lifecycle-container mb-6">
          <div class="lifecycle-header-row">
            <h4><i class="fa-solid fa-route"></i> Tiến trình đơn thuê xe</h4>
            <button 
              v-if="booking.tinh_trang === 0" 
              class="btn-cancel-trip" 
              type="button"
              @click="cancelCurrentBooking"
            >
              <i class="fa-solid fa-ban"></i> Hủy yêu cầu đặt xe
            </button>
          </div>

          <!-- Banner đơn hủy -->
          <div v-if="booking.tinh_trang === 2" class="cancelled-trip-banner">
            <div class="cancel-icon"><i class="fa-solid fa-ban"></i></div>
            <div class="cancel-info">
              <strong>Đơn đặt xe đã hủy</strong>
              <p v-if="booking.cancel_reason">Lý do: {{ booking.cancel_reason }}</p>
              <small v-if="booking.cancelled_at">Thời điểm: {{ formatDate(booking.cancelled_at) }}</small>
            </div>
          </div>

          <!-- 5-Step Progress Tracker -->
          <div v-else class="stepper-horizontal">
            <div 
              class="step-item" 
              :class="{ 
                'completed': true, 
                'active': booking.tinh_trang === 0 
              }"
            >
              <div class="step-circle"><i class="fa-solid fa-calendar-plus"></i></div>
              <div class="step-label">Tạo đơn</div>
              <small class="step-time">{{ formatDate(booking.created_at) }}</small>
            </div>

            <div class="step-line" :class="{ 'active': booking.tinh_trang >= 1 }"></div>

            <div 
              class="step-item" 
              :class="{ 
                'completed': booking.tinh_trang >= 1, 
                'active': booking.tinh_trang === 1 
              }"
            >
              <div class="step-circle"><i class="fa-solid fa-calendar-check"></i></div>
              <div class="step-label">Đã duyệt</div>
              <small class="step-time">{{ booking.tinh_trang >= 1 ? 'Đã duyệt xe' : 'Chờ duyệt' }}</small>
            </div>

            <div class="step-line" :class="{ 'active': booking.tinh_trang >= 3 }"></div>

            <div 
              class="step-item" 
              :class="{ 
                'completed': booking.tinh_trang >= 3, 
                'active': booking.tinh_trang === 3 
              }"
            >
              <div class="step-circle"><i class="fa-solid fa-car-side"></i></div>
              <div class="step-label">Đang thuê</div>
              <small class="step-time">{{ booking.delivered_at ? formatDate(booking.delivered_at) : (booking.tinh_trang >= 3 ? 'Đang lưu hành' : 'Chờ giao xe') }}</small>
            </div>

            <div class="step-line" :class="{ 'active': booking.tinh_trang >= 4 }"></div>

            <div 
              class="step-item" 
              :class="{ 
                'completed': booking.tinh_trang >= 4, 
                'active': booking.tinh_trang === 4 
              }"
            >
              <div class="step-circle"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
              <div class="step-label">Đã trả xe</div>
              <small class="step-time">{{ booking.returned_at ? formatDate(booking.returned_at) : (booking.tinh_trang >= 4 ? 'Đã nhận trả' : 'Chờ trả xe') }}</small>
            </div>

            <div class="step-line" :class="{ 'active': booking.tinh_trang === 5 }"></div>

            <div 
              class="step-item" 
              :class="{ 
                'completed': booking.tinh_trang === 5, 
                'active': booking.tinh_trang === 5 
              }"
            >
              <div class="step-circle"><i class="fa-solid fa-circle-check"></i></div>
              <div class="step-label">Hoàn tất</div>
              <small class="step-time">{{ booking.completed_at ? formatDate(booking.completed_at) : (booking.tinh_trang === 5 ? 'Đóng đơn' : 'Kết thúc') }}</small>
            </div>
          </div>
        </div>

        <!-- Customer & Trip Info Grid -->
        <div class="invoice-grid">
          <div class="info-section">
            <h4><i class="fa-solid fa-user"></i> Thông tin khách hàng</h4>
            <div class="info-row">
              <span class="label">Họ và tên:</span>
              <span class="value">{{ user?.ho_ten || 'Khách hàng' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Email:</span>
              <span class="value">{{ user?.email }}</span>
            </div>
            <div class="info-row">
              <span class="label">Số điện thoại:</span>
              <span class="value">{{ booking.user_phone || user?.so_dien_thoai || 'Đang cập nhật' }}</span>
            </div>
          </div>

          <div class="info-section">
            <h4><i class="fa-solid fa-route"></i> Lịch trình di chuyển</h4>
            <div class="info-row">
              <span class="label">Thời gian nhận:</span>
              <span class="value">{{ formatDate(booking.start_date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Thời gian trả:</span>
              <span class="value">{{ formatDate(booking.end_date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Địa điểm nhận:</span>
              <span class="value">{{ booking.dia_chi_nhan_xe || 'Tại Showroom' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Địa điểm trả:</span>
              <span class="value">{{ booking.dia_chi_tra_xe || 'Tại Showroom' }}</span>
            </div>
          </div>
        </div>

        <!-- Car Details Table -->
        <div class="invoice-table">
          <table>
            <thead>
              <tr>
                <th>Phương tiện thuê</th>
                <th>Thời gian</th>
                <th>Đơn giá/ngày</th>
                <th class="text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="car-item">
                    <img :src="getCarImage(booking.car_name)" alt="car" class="pdf-car-img" @error="handleImgError($event)">
                    <div class="car-title">
                      <strong>{{ booking.car_name }}</strong>
                      <span>VinFast Electric Mobility</span>
                    </div>
                  </div>
                </td>
                <td>{{ rentalDays }} ngày</td>
                <td>{{ Number(pricePerDay).toLocaleString('vi-VN') }}đ</td>
                <td class="text-right font-bold">{{ Number(booking.tong_tien).toLocaleString('vi-VN') }}đ</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary & Status -->
        <div class="invoice-summary-area">
          <div class="status-summary-box">
            <div class="summary-status-tag">
              <span class="label">Trạng thái thuê:</span>
              <span class="status-badge" :class="getRentalStatusClass(booking.tinh_trang)">
                {{ getRentalStatusText(booking.tinh_trang) }}
              </span>
            </div>
            <div class="summary-status-tag">
              <span class="label">Trạng thái thanh toán:</span>
              <span class="status-badge" :class="getPaymentStatus(booking).class">
                <i class="fa-solid" :class="getPaymentStatus(booking).icon"></i>
                {{ getPaymentStatus(booking).text }}
              </span>
            </div>
          </div>

          <div v-if="Number(booking.tinh_trang) === 2 && Number(booking.is_thanh_toan) === 1" class="financial-alert-box mt-3 mb-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Đơn thuê đã hủy - Đã phát sinh giao dịch thu tiền</strong>
              <p>Đơn hàng đã được thanh toán trước đó. Bộ phận kế toán đang kiểm tra sao kê ngân hàng để đối soát và xử lý hoàn tiền theo quy định.</p>
            </div>
          </div>

          <div class="summary-details">
            <div class="summary-row">
              <span>Tạm tính thuê xe:</span>
              <span>{{ Number(booking.tong_tien).toLocaleString('vi-VN') }}đ</span>
            </div>
            <div class="summary-row">
              <span>Thuế GTGT & Bảo hiểm:</span>
              <span class="included-tag">Đã bao gồm</span>
            </div>
            <div class="summary-row total">
              <span>TỔNG THANH TOÁN:</span>
              <span class="total-price">{{ Number(booking.tong_tien).toLocaleString('vi-VN') }}đ</span>
            </div>
          </div>
        </div>

        <!-- Sổ Quỹ & Chi Tiết Tiền Cọc Bảo Đảm Minh Bạch -->
        <div class="client-ledger-section mb-6">
          <div class="section-title-wrap mb-3">
            <h4 class="font-bold text-lg"><i class="fa-solid fa-scale-balanced text-primary"></i> Minh Bạch Tài Chính & Tiền Cọc Bảo Đảm</h4>
            <p class="section-sub text-muted">DriveRent phân định độc lập giữa Chi phí thuê xe và Tiền cọc bảo đảm ký quỹ hoàn lại</p>
          </div>

          <!-- Explanatory note -->
          <div class="ledger-explainer-card mb-4">
            <div class="explainer-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="explainer-text">
              <strong>Chính sách Tiền Cọc Bảo Đảm Hoàn Lại:</strong>
              <p>Tiền cọc bảo đảm là khoản ký quỹ để bảo chứng an toàn phương tiện trong thời gian quý khách lưu hành. Khoản này <strong>KHÔNG</strong> tính vào doanh thu hay chi phí thuê xe, và sẽ được hoàn trả đầy đủ cho quý khách sau khi nhận trả và kiểm tra xe hoàn tất.</p>
            </div>
          </div>

          <!-- 2 Comparative Cards: Rental Fee vs Security Deposit -->
          <div class="financial-balance-cards-grid mb-6">
            <!-- Card 1: Rental Fee -->
            <div class="balance-card rent-card">
              <div class="card-head">
                <i class="fa-solid fa-receipt text-primary"></i>
                <h5>Chi Phí Thuê Xe</h5>
              </div>
              <div class="card-body">
                <div class="balance-line">
                  <span>Tổng phí hợp đồng:</span>
                  <strong>{{ formatCurrency(booking.financial_summary?.total_rent_fee ?? booking.tong_tien) }}</strong>
                </div>
                <div class="balance-line">
                  <span>Tiền thuê đã thanh toán:</span>
                  <strong class="text-success">{{ formatCurrency(booking.financial_summary?.paid_rent_amount ?? (booking.is_thanh_toan === 1 ? booking.tong_tien : 0)) }}</strong>
                </div>
                <div class="balance-line highlight">
                  <span>Tiền thuê còn phải trả:</span>
                  <strong :class="(booking.financial_summary?.remaining_rent_amount ?? (booking.is_thanh_toan === 1 ? 0 : booking.tong_tien)) > 0 ? 'text-warning' : 'text-muted'">
                    {{ formatCurrency(booking.financial_summary?.remaining_rent_amount ?? (booking.is_thanh_toan === 1 ? 0 : booking.tong_tien)) }}
                  </strong>
                </div>
                <div v-if="(booking.financial_summary?.rental_refunded_amount ?? 0) > 0" class="balance-line text-sm text-secondary">
                  <span>Tiền thuê đã hoàn trả lại:</span>
                  <span>{{ formatCurrency(booking.financial_summary.rental_refunded_amount) }}</span>
                </div>
              </div>
            </div>

            <!-- Card 2: Security Deposit -->
            <div class="balance-card deposit-card">
              <div class="card-head">
                <i class="fa-solid fa-vault text-info"></i>
                <h5>Tiền Cọc Bảo Đảm (Ký Quỹ)</h5>
              </div>
              <div class="card-body">
                <div class="balance-line">
                  <span>Tổng cọc đã nộp:</span>
                  <strong>{{ formatCurrency(booking.financial_summary?.deposit_collected_amount ?? 0) }}</strong>
                </div>
                <div class="balance-line">
                  <span>Cọc đã hoàn trả quý khách:</span>
                  <strong class="text-secondary">{{ formatCurrency(booking.financial_summary?.deposit_refunded_amount ?? 0) }}</strong>
                </div>
                <div v-if="(booking.financial_summary?.deposit_deducted_amount ?? 0) > 0" class="balance-line">
                  <span>Cọc đã khấu trừ (phụ phí/bồi thường):</span>
                  <strong class="text-danger">{{ formatCurrency(booking.financial_summary.deposit_deducted_amount) }}</strong>
                </div>
                <div class="balance-line highlight">
                  <span>Tiền cọc DriveRent đang giữ:</span>
                  <strong class="text-info">{{ formatCurrency(booking.financial_summary?.deposit_held_amount ?? 0) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Transaction History Table for Client -->
          <div class="client-transactions-history mb-6">
            <h5 class="history-title mb-3"><i class="fa-solid fa-list-check"></i> Sổ Lịch Sử Giao Dịch Đã Xác Nhận</h5>

            <div v-if="loadingTx" class="text-center py-4">
              <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
              <p class="mt-2 text-muted">Đang tải lịch sử giao dịch...</p>
            </div>

            <div v-else-if="!transactions || transactions.length === 0" class="empty-tx-client">
              <i class="fa-solid fa-file-invoice-dollar"></i>
              <p>Chưa có giao dịch chi tiết nào được ghi nhận cho đơn này.</p>
              <small v-if="Number(booking.is_thanh_toan) === 1" class="text-muted">
                (Đơn hàng lịch sử đã hoàn tất thanh toán trước khi kích hoạt sổ cái đối soát)
              </small>
            </div>

            <div v-else class="transactions-table-wrap">
              <table class="client-tx-table">
                <thead>
                  <tr>
                    <th>Thời gian</th>
                    <th>Loại giao dịch</th>
                    <th class="text-right">Số tiền</th>
                    <th>Phương thức</th>
                    <th>Mã tham chiếu</th>
                    <th>Ghi chú</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in transactions" :key="tx.id">
                    <td>{{ formatDate(tx.transaction_time || tx.created_at) }}</td>
                    <td>
                      <span class="badge" :class="'badge-' + (tx.type_badge_color || 'secondary')">
                        {{ tx.type_label }}
                      </span>
                    </td>
                    <td class="text-right font-bold" :class="tx.is_outflow ? 'text-warning' : (tx.type === 'deposit_deduction' ? 'text-danger' : 'text-success')">
                      {{ tx.is_outflow ? '-' : '+' }}{{ formatCurrency(tx.amount) }}
                    </td>
                    <td>{{ formatMethod(tx.method) }}</td>
                    <td><code class="tx-ref">{{ tx.reference_code || '-' }}</code></td>
                    <td>
                      <div>
                        <span>{{ tx.note || '-' }}</span>
                        <div v-if="tx.deduction_reason" class="client-deduction-badge">
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

        <!-- Handover & Inspection Section for Client -->
        <div v-if="handoverData && (handoverData.delivery || handoverData.return)" class="client-handover-section mb-6">
          <div class="section-title-wrap mb-3 handover-header-flex">
            <div>
              <h4 class="font-bold text-lg"><i class="fa-solid fa-clipboard-check text-primary"></i> Biên Bản Bàn Giao & Nhận Trả Xe</h4>
              <p class="section-sub text-muted">Hồ sơ kiểm tra hiện trạng kỹ thuật, số km, pin và phụ kiện đối chiếu minh bạch</p>
            </div>
            <button class="btn-outline btn-sm" type="button" @click="showInspectionModal = true">
              <i class="fa-solid fa-print"></i> Xem & In Biên bản chi tiết
            </button>
          </div>

          <!-- Comparison Diff Banner if return exists -->
          <div v-if="handoverData.return && handoverData.comparison" class="handover-diff-banner mb-4">
            <div class="banner-title"><i class="fa-solid fa-chart-line text-info"></i> Kết Quả Đối Chiếu Hành Trình Thuê</div>
            <div class="diff-chips-row">
              <div class="chip-item">
                <span class="c-lbl">Quãng đường đã chạy:</span>
                <strong class="c-val text-primary">+{{ Number(handoverData.comparison.km_driven || 0).toLocaleString('vi-VN') }} km</strong>
              </div>
              <div class="chip-item">
                <span class="c-lbl">Chênh lệch pin/nhiên liệu:</span>
                <strong class="c-val" :class="handoverData.comparison.fuel_diff >= 0 ? 'text-success' : 'text-warning'">
                  {{ handoverData.comparison.fuel_diff >= 0 ? '+' : '' }}{{ handoverData.comparison.fuel_diff }}%
                </strong>
              </div>
              <div class="chip-item">
                <span class="c-lbl">Thời gian trả:</span>
                <strong class="c-val" :class="handoverData.comparison.hours_late > 0 ? 'text-danger' : 'text-success'">
                  {{ handoverData.comparison.hours_late > 0 ? 'Trễ ' + handoverData.comparison.hours_late + ' giờ' : 'Đúng hạn' }}
                </strong>
              </div>
            </div>
          </div>

          <!-- Cards Grid: Delivery vs Return -->
          <div class="handover-cards-grid">
            <!-- Card 1: Lúc bàn giao xuất bãi -->
            <div v-if="handoverData.delivery" class="handover-stage-card delivery-card">
              <div class="stage-head">
                <div class="stage-badge delivery"><i class="fa-solid fa-key"></i> LÚC NHẬN XE (XUẤT BÃI)</div>
                <small class="stage-time">{{ formatDate(handoverData.delivery.actual_time) }}</small>
              </div>

              <div class="stage-stats-grid">
                <div class="s-stat">
                  <span class="s-lbl">Số KM đồng hồ:</span>
                  <strong class="s-val">{{ Number(handoverData.delivery.current_km || 0).toLocaleString('vi-VN') }} km</strong>
                </div>
                <div class="s-stat">
                  <span class="s-lbl">Mức pin / nhiên liệu:</span>
                  <strong class="s-val text-info">{{ handoverData.delivery.fuel_or_battery_level }}%</strong>
                </div>
                <div class="s-stat full">
                  <span class="s-lbl">Nhân viên bàn giao:</span>
                  <span class="s-val">{{ handoverData.delivery.staff?.ho_ten || 'Đại diện DriveRent' }}</span>
                </div>
              </div>

              <!-- Checklist items -->
              <div class="client-checklist-summary mt-3">
                <span class="chk-title">Trang bị bàn giao đầy đủ:</span>
                <div class="chk-badges">
                  <span v-if="handoverData.delivery.checklist?.smart_key" class="chk-tag ok"><i class="fa-solid fa-check"></i> Chìa khóa thông minh</span>
                  <span v-if="handoverData.delivery.checklist?.vehicle_documents" class="chk-tag ok"><i class="fa-solid fa-check"></i> Giấy tờ xe</span>
                  <span v-if="handoverData.delivery.checklist?.spare_tire" class="chk-tag ok"><i class="fa-solid fa-check"></i> Lốp dự phòng</span>
                  <span v-if="handoverData.delivery.checklist?.jack_tool" class="chk-tag ok"><i class="fa-solid fa-check"></i> Bộ dụng cụ kích xe</span>
                  <span v-if="handoverData.delivery.checklist?.portable_charger" class="chk-tag ok"><i class="fa-solid fa-check"></i> Bộ sạc di động</span>
                  <span v-if="handoverData.delivery.checklist?.dashcam_working" class="chk-tag ok"><i class="fa-solid fa-check"></i> Camera hành trình</span>
                </div>
              </div>

              <div class="client-status-notes mt-3">
                <p><strong>Ngoại thất:</strong> {{ handoverData.delivery.exterior_status || 'Nguyên vẹn không móp méo' }}</p>
                <p><strong>Nội thất:</strong> {{ handoverData.delivery.interior_status || 'Sạch sẽ' }}</p>
              </div>

              <!-- Photos -->
              <div v-if="handoverData.delivery.images && handoverData.delivery.images.length > 0" class="client-photos-row mt-3">
                <span class="chk-title">Ảnh chụp hiện trường lúc giao xe:</span>
                <div class="thumb-strip">
                  <img 
                    v-for="(img, iIdx) in handoverData.delivery.images" 
                    :key="iIdx" 
                    :src="getHandoverImageClientUrl(img)" 
                    alt="Ảnh giao xe" 
                    class="client-handover-thumb"
                    @click="viewHandoverImage(getHandoverImageClientUrl(img))"
                  />
                </div>
              </div>

              <!-- Customer Confirmation Block for Delivery -->
              <div class="customer-confirm-block mt-4">
                <div v-if="handoverData.delivery.customer_confirmed" class="confirm-badge confirmed">
                  <i class="fa-solid fa-circle-check"></i>
                  <div>
                    <strong>Quý khách đã xác nhận nhận xe</strong>
                    <small>Thời điểm: {{ formatDate(handoverData.delivery.customer_confirmed_at) }}</small>
                    <p v-if="handoverData.delivery.customer_notes" class="user-confirmed-note">"{{ handoverData.delivery.customer_notes }}"</p>
                  </div>
                </div>

                <div v-else class="confirm-action-box">
                  <div class="checkbox-box">
                    <label class="custom-checkbox-label">
                      <input type="checkbox" v-model="deliveryAgreeCheck" />
                      <span>Tôi đã kiểm tra thực tế, đối chiếu phụ kiện, số km và mức pin đúng với biên bản bàn giao.</span>
                    </label>
                  </div>
                  <input 
                    type="text" 
                    v-model="deliveryCustomerNote" 
                    class="form-control form-control-sm mt-2" 
                    placeholder="Ghi chú phản hồi của quý khách (tùy chọn)..."
                  />
                  <button 
                    type="button" 
                    class="btn-primary btn-sm w-full mt-2" 
                    :disabled="!deliveryAgreeCheck || confirmingDelivery"
                    @click="confirmHandover('delivery')"
                  >
                    <i v-if="confirmingDelivery" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-circle-check"></i>
                    {{ confirmingDelivery ? 'Đang xác nhận...' : 'Xác nhận đã nhận xe & hiện trạng' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Card 2: Lúc nhận trả thu hồi -->
            <div v-if="handoverData.return" class="handover-stage-card return-card">
              <div class="stage-head">
                <div class="stage-badge return"><i class="fa-solid fa-arrow-right-arrow-left"></i> LÚC TRẢ XE (THU HỒI)</div>
                <small class="stage-time">{{ formatDate(handoverData.return.actual_time) }}</small>
              </div>

              <div class="stage-stats-grid">
                <div class="s-stat">
                  <span class="s-lbl">Số KM đồng hồ:</span>
                  <strong class="s-val">{{ Number(handoverData.return.current_km || 0).toLocaleString('vi-VN') }} km</strong>
                </div>
                <div class="s-stat">
                  <span class="s-lbl">Mức pin / nhiên liệu:</span>
                  <strong class="s-val text-info">{{ handoverData.return.fuel_or_battery_level }}%</strong>
                </div>
                <div class="s-stat full">
                  <span class="s-lbl">Nhân viên nhận trả:</span>
                  <span class="s-val">{{ handoverData.return.staff?.ho_ten || 'Đại diện DriveRent' }}</span>
                </div>
              </div>

              <div class="client-checklist-summary mt-3">
                <span class="chk-title">Trang bị hoàn trả:</span>
                <div class="chk-badges">
                  <span v-if="handoverData.return.checklist?.smart_key" class="chk-tag ok"><i class="fa-solid fa-check"></i> Chìa khóa</span>
                  <span v-if="handoverData.return.checklist?.vehicle_documents" class="chk-tag ok"><i class="fa-solid fa-check"></i> Giấy tờ xe</span>
                  <span v-if="handoverData.return.checklist?.spare_tire" class="chk-tag ok"><i class="fa-solid fa-check"></i> Lốp dự phòng</span>
                  <span v-if="handoverData.return.checklist?.jack_tool" class="chk-tag ok"><i class="fa-solid fa-check"></i> Bộ kích xe</span>
                  <span v-if="handoverData.return.checklist?.portable_charger" class="chk-tag ok"><i class="fa-solid fa-check"></i> Bộ sạc di động</span>
                  <span v-if="handoverData.return.checklist?.dashcam_working" class="chk-tag ok"><i class="fa-solid fa-check"></i> Camera hành trình</span>
                </div>
              </div>

              <!-- Surcharges List if any -->
              <div v-if="handoverData.surcharges && handoverData.surcharges.length > 0" class="client-surcharges-box mt-3">
                <span class="chk-title text-danger">Phụ phí phát sinh đã quyết toán:</span>
                <ul class="surcharges-list">
                  <li v-for="sc in handoverData.surcharges" :key="sc.id">
                    <span>{{ sc.type_label }}: {{ sc.reason }}</span>
                    <strong class="text-danger">{{ formatCurrency(sc.amount) }}</strong>
                  </li>
                </ul>
              </div>

              <!-- Photos -->
              <div v-if="handoverData.return.images && handoverData.return.images.length > 0" class="client-photos-row mt-3">
                <span class="chk-title">Ảnh chụp hiện trường lúc trả xe:</span>
                <div class="thumb-strip">
                  <img 
                    v-for="(img, iIdx) in handoverData.return.images" 
                    :key="iIdx" 
                    :src="getHandoverImageClientUrl(img)" 
                    alt="Ảnh trả xe" 
                    class="client-handover-thumb"
                    @click="viewHandoverImage(getHandoverImageClientUrl(img))"
                  />
                </div>
              </div>

              <!-- Customer Confirmation Block for Return -->
              <div class="customer-confirm-block mt-4">
                <div v-if="handoverData.return.customer_confirmed" class="confirm-badge confirmed">
                  <i class="fa-solid fa-circle-check"></i>
                  <div>
                    <strong>Quý khách đã xác nhận hoàn tất trả xe</strong>
                    <small>Thời điểm: {{ formatDate(handoverData.return.customer_confirmed_at) }}</small>
                    <p v-if="handoverData.return.customer_notes" class="user-confirmed-note">"{{ handoverData.return.customer_notes }}"</p>
                  </div>
                </div>

                <div v-else class="confirm-action-box">
                  <div class="checkbox-box">
                    <label class="custom-checkbox-label">
                      <input type="checkbox" v-model="returnAgreeCheck" />
                      <span>Tôi đồng ý với hiện trạng xe và các khoản phụ phí quyết toán (nếu có) khi trả xe.</span>
                    </label>
                  </div>
                  <input 
                    type="text" 
                    v-model="returnCustomerNote" 
                    class="form-control form-control-sm mt-2" 
                    placeholder="Ghi chú phản hồi khi trả xe (tùy chọn)..."
                  />
                  <button 
                    type="button" 
                    class="btn-primary btn-sm w-full mt-2" 
                    :disabled="!returnAgreeCheck || confirmingReturn"
                    @click="confirmHandover('return')"
                  >
                    <i v-if="confirmingReturn" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-circle-check"></i>
                    {{ confirmingReturn ? 'Đang xác nhận...' : 'Xác nhận hoàn tất trả xe' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Receipt / Payment Verification Box -->
        <div class="receipt-verification-box">
          <div v-if="booking.bill_image" class="verified-bill-content">
            <h4><i class="fa-solid fa-file-invoice"></i> Ảnh biên lai chuyển khoản (Chứng từ tải lên)</h4>
            <div class="bill-preview-wrapper" @click="showFullBill = true">
              <img :src="getReceiptSecureUrl(booking)" alt="Biên lai thanh toán" class="receipt-thumbnail" />
              <div class="overlay"><i class="fa-solid fa-magnifying-glass-plus"></i> Nhấn để phóng to</div>
            </div>
            <div class="verification-notice" :class="Number(booking.is_thanh_toan) === 1 ? 'verified' : 'pending'">
              <i class="fa-solid" :class="Number(booking.is_thanh_toan) === 1 ? 'fa-circle-check' : 'fa-clock-rotate-left'"></i>
              <div>
                <strong>{{ Number(booking.is_thanh_toan) === 1 ? 'Đã xác nhận thanh toán' : 'Đang chờ kế toán đối soát giao dịch' }}</strong>
                <p>
                  {{ Number(booking.is_thanh_toan) === 1 
                    ? 'Bộ phận kế toán đã kiểm tra sao kê ngân hàng và xác nhận giao dịch thành công.' 
                    : 'Hệ thống đã nhận được ảnh biên lai do quý khách cung cấp. Lưu ý: Ảnh biên lai không thay thế cho việc xác nhận tiền đã vào tài khoản. Kế toán đang đối soát với sao kê ngân hàng (thời gian xử lý từ 5-15 phút trong giờ hành chính).' 
                  }}
                </p>
              </div>
            </div>
          </div>

          <div v-else-if="booking.is_thanh_toan === 0 && booking.tinh_trang !== 2" class="unpaid-action-box">
            <h4><i class="fa-solid fa-qrcode"></i> Thanh toán & Tải biên lai</h4>
            <p>Đơn hàng này hiện chưa được thanh toán hoặc chưa gửi biên lai. Quý khách vui lòng quét mã VietQR và gửi ảnh biên lai để hoàn tất.</p>
            
            <div class="qr-upload-grid">
              <div class="qr-col">
                <div v-if="settings.bank_bin && settings.bank_account_number" class="qr-active-box">
                  <img 
                    :src="`https://api.vietqr.io/image/${settings.bank_bin}-${settings.bank_account_number}-compact2.png?accountName=${encodeURIComponent(settings.bank_account_holder || '')}&amount=${booking.tong_tien}&addInfo=${booking.ma_dat_xe}`" 
                    alt="Mã VietQR Chuyển Khoản" 
                    class="invoice-qr"
                  />
                  <div class="qr-bank-caption mt-2 text-center" style="font-size: 13px;">
                    <div>{{ settings.bank_name || 'Ngân hàng thụ hưởng' }}</div>
                    <strong>STK: {{ settings.bank_account_number }}</strong>
                    <div v-if="settings.bank_account_holder" class="text-muted">CTK: {{ settings.bank_account_holder }}</div>
                  </div>
                </div>
                <div v-else class="qr-unconfigured-box alert alert-warning p-3 text-center" style="border-radius: 8px; border: 1px dashed #f59e0b; background: #fffbeb;">
                  <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #d97706; margin-bottom: 8px;"></i>
                  <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;">Chưa cấu hình tài khoản ngân hàng</div>
                  <p style="font-size: 12px; color: #b45309; margin: 0;">Quý khách vui lòng liên hệ tổng đài <strong>{{ settings.hotline || '1900 1234' }}</strong> để nhận số tài khoản thanh toán.</p>
                </div>
              </div>
              <div class="upload-col">
                <input 
                  type="file" 
                  ref="billInput" 
                  style="display: none" 
                  accept="image/jpeg,image/png,image/webp,image/jpg"
                  @change="handleBillChange"
                />
                
                <div class="file-drop-box" @click="$refs.billInput.click()">
                  <div v-if="!tempBillPreview">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Chọn ảnh biên lai (JPEG, PNG, WebP)</span>
                  </div>
                  <div v-else class="preview-mini">
                    <img :src="tempBillPreview" alt="Preview" />
                    <span>Nhấn để chọn lại</span>
                  </div>
                </div>

                <button 
                  type="button" 
                  class="btn-primary w-full mt-2" 
                  :disabled="!tempBillFile || isUploading"
                  @click="uploadBookingBill"
                >
                  <i v-if="isUploading" class="fa-solid fa-spinner fa-spin"></i>
                  <span v-else>Tải lên biên lai</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
          <div class="signature-section">
            <div class="signature-box">
              <p class="sign-title">Đại diện DriveRent</p>
              <div class="stamp-box">
                <span>DriveRent</span>
                <small>XÁC NHẬN HỆ THỐNG</small>
              </div>
              <p class="sign-name">Bộ phận Vận hành</p>
            </div>
          </div>
          <p class="thanks">Cảm ơn quý khách đã tin tưởng đồng hành cùng DriveRent trên mọi hành trình!</p>
        </div>
      </div>
    </div>

    <!-- Full Image Bill Modal -->
    <div v-if="showFullBill && booking?.bill_image" class="modal-overlay" @click="showFullBill = false">
      <div class="modal-content image-viewer" @click.stop>
        <button class="btn-close-abs" @click="showFullBill = false">&times;</button>
        <img :src="getReceiptSecureUrl(booking)" alt="Biên lai chi tiết" />
      </div>
    </div>

    <!-- Full Handover Photo Modal -->
    <div v-if="fullHandoverImageUrl" class="modal-overlay" @click="fullHandoverImageUrl = null">
      <div class="modal-content image-viewer" @click.stop>
        <button class="btn-close-abs" @click="fullHandoverImageUrl = null">&times;</button>
        <img :src="fullHandoverImageUrl" alt="Ảnh hiện trường bàn giao" />
      </div>
    </div>

    <!-- Printable Formal Inspection Document Modal for Client -->
    <div v-if="showInspectionModal && handoverData" class="modal-overlay" @click.self="showInspectionModal = false">
      <div class="modal-content card inspection-modal animate-in">
        <div class="modal-header no-print">
          <div class="title-wrap">
            <i class="fa-solid fa-clipboard-check text-primary"></i>
            <div>
              <h3>Biên Bản Bàn Giao & Nhận Trả Xe</h3>
              <p class="modal-sub">Mã hồ sơ: #BBBG-{{ booking.ma_dat_xe }}</p>
            </div>
          </div>
          <div class="header-action-group">
            <button class="btn-primary btn-sm" @click="printClientInspectionReport">
              <i class="fa-solid fa-print"></i> In biên bản
            </button>
            <button class="btn-close" @click="showInspectionModal = false">&times;</button>
          </div>
        </div>

        <div class="modal-body inspection-body" id="client-inspection-printable-area">
          <div class="inspection-document-sheet">
            <!-- Header Paper -->
            <div class="sheet-header">
              <div class="company-brand">
                <div class="brand-logo-mark">DriveRent</div>
                <div class="brand-sub">Hệ sinh thái Thuê Xe & Di Chuyển Thông Minh</div>
                <small class="company-meta">Trụ sở: Tòa nhà Green Mobility, TP. Hồ Chí Minh | Hotline: 1900 1234</small>
              </div>
              <div class="sheet-title-block">
                <h2>BIÊN BẢN BÀN GIAO & NHẬN TRẢ PHƯƠNG TIỆN</h2>
                <div class="doc-code">Số: #BBBG-{{ booking.ma_dat_xe }}</div>
                <div class="doc-date">Ngày lập: {{ formatDate(new Date()) }}</div>
              </div>
            </div>

            <!-- Meta Grid -->
            <div class="sheet-meta-grid">
              <div class="meta-item">
                <span class="m-lbl">Khách hàng thuê xe:</span>
                <strong class="m-val">{{ user?.ho_ten || 'Khách hàng' }}</strong>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Số điện thoại:</span>
                <span class="m-val">{{ booking.user_phone || user?.so_dien_thoai }}</span>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Phương tiện:</span>
                <strong class="m-val text-primary">{{ booking.car_name }}</strong>
              </div>
              <div class="meta-item">
                <span class="m-lbl">Thời gian hợp đồng:</span>
                <span class="m-val">{{ formatDate(booking.start_date) }} → {{ formatDate(booking.end_date) }}</span>
              </div>
            </div>

            <!-- Two-Column Side-by-Side Comparison -->
            <div class="inspection-comparison-grid mt-4">
              <!-- Cột Giao Xe -->
              <div class="inspection-col delivery-col">
                <div class="col-header">
                  <i class="fa-solid fa-key"></i>
                  <h4>I. BIÊN BẢN BÀN GIAO (XUẤT BÃI)</h4>
                </div>
                <div v-if="handoverData.delivery" class="col-content">
                  <div class="stat-row">
                    <span>Thời gian giao thực tế:</span>
                    <strong>{{ formatDate(handoverData.delivery.actual_time) }}</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Số KM công-tơ-mét:</span>
                    <strong class="val-metric">{{ Number(handoverData.delivery.current_km || 0).toLocaleString('vi-VN') }} km</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Mức pin/nhiên liệu:</span>
                    <strong class="val-metric">{{ handoverData.delivery.fuel_or_battery_level }}%</strong>
                  </div>
                  <div class="status-notes-box mt-3">
                    <p><strong>Ngoại thất:</strong> {{ handoverData.delivery.exterior_status || 'Nguyên vẹn' }}</p>
                    <p><strong>Nội thất:</strong> {{ handoverData.delivery.interior_status || 'Sạch sẽ' }}</p>
                  </div>
                </div>
                <div v-else class="empty-col-notice">
                  <p>Chưa có biên bản giao xe.</p>
                </div>
              </div>

              <!-- Cột Trả Xe -->
              <div class="inspection-col return-col">
                <div class="col-header">
                  <i class="fa-solid fa-arrow-right-arrow-left"></i>
                  <h4>II. BIÊN BẢN NHẬN TRẢ (THU HỒI)</h4>
                </div>
                <div v-if="handoverData.return" class="col-content">
                  <div class="stat-row">
                    <span>Thời gian trả thực tế:</span>
                    <strong>{{ formatDate(handoverData.return.actual_time) }}</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Số KM công-tơ-mét:</span>
                    <strong class="val-metric">{{ Number(handoverData.return.current_km || 0).toLocaleString('vi-VN') }} km</strong>
                  </div>
                  <div class="stat-row highlight">
                    <span>Mức pin/nhiên liệu:</span>
                    <strong class="val-metric">{{ handoverData.return.fuel_or_battery_level }}%</strong>
                  </div>
                  <div class="status-notes-box mt-3">
                    <p><strong>Ngoại thất:</strong> {{ handoverData.return.exterior_status || 'Bình thường' }}</p>
                    <p><strong>Nội thất:</strong> {{ handoverData.return.interior_status || 'Bình thường' }}</p>
                  </div>
                </div>
                <div v-else class="empty-col-notice">
                  <p>Chưa có biên bản nhận trả xe.</p>
                </div>
              </div>
            </div>

            <!-- Comparison Diff Summary -->
            <div v-if="handoverData.comparison" class="diff-summary-card mt-4">
              <h4 class="diff-title"><i class="fa-solid fa-chart-simple"></i> III. ĐỐI CHIẾU THỰC TẾ & THANH TOÁN</h4>
              <div class="diff-metrics-grid">
                <div class="dm-item">
                  <span>Quãng đường di chuyển:</span>
                  <strong>{{ Number(handoverData.comparison.km_driven || 0).toLocaleString('vi-VN') }} km</strong>
                </div>
                <div class="dm-item">
                  <span>Chênh lệch pin/nhiên liệu:</span>
                  <strong :class="handoverData.comparison.fuel_diff >= 0 ? 'text-success' : 'text-warning'">
                    {{ handoverData.comparison.fuel_diff >= 0 ? '+' : '' }}{{ handoverData.comparison.fuel_diff }}%
                  </strong>
                </div>
                <div class="dm-item">
                  <span>Thời gian trả xe:</span>
                  <strong :class="handoverData.comparison.hours_late > 0 ? 'text-danger' : 'text-success'">
                    {{ handoverData.comparison.hours_late > 0 ? 'Trễ ' + handoverData.comparison.hours_late + ' giờ' : 'Đúng hạn' }}
                  </strong>
                </div>
              </div>

              <!-- Surcharges if any -->
              <div v-if="handoverData.surcharges && handoverData.surcharges.length > 0" class="surcharges-result-box mt-3">
                <table class="report-table">
                  <thead>
                    <tr>
                      <th>Hạng mục phụ phí</th>
                      <th class="text-end" width="160">Số tiền</th>
                      <th>Lý do</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sc in handoverData.surcharges" :key="sc.id">
                      <td><strong>{{ sc.type_label }}</strong></td>
                      <td class="text-end font-bold text-danger">{{ formatCurrency(sc.amount) }}</td>
                      <td>{{ sc.reason }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th class="text-end">TỔNG PHỤ PHÍ:</th>
                      <th class="text-end text-danger font-bold">{{ formatCurrency(handoverData.surcharges.reduce((sum, s) => sum + Number(s.amount), 0)) }}</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Electronic Confirmation Status -->
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
                    <strong>ĐÃ LẬP BIÊN BẢN HỆ THỐNG</strong>
                    <small>{{ handoverData.delivery?.staff?.ho_ten || 'Đại diện DriveRent' }}</small>
                  </div>
                </div>

                <div class="confirm-box customer-box">
                  <span class="c-title">ĐẠI DIỆN KHÁCH HÀNG THUÊ XE</span>
                  <div v-if="handoverData.delivery?.customer_confirmed || handoverData.return?.customer_confirmed" class="c-status ok">
                    <i class="fa-solid fa-user-check"></i>
                    <strong>ĐÃ XÁC NHẬN ĐIỆN TỬ QUA ỨNG DỤNG</strong>
                    <small>Tài khoản: {{ user?.ho_ten }} ({{ user?.email }})</small>
                    <small>Thời điểm: {{ formatDate(handoverData.delivery?.customer_confirmed_at || handoverData.return?.customer_confirmed_at) }}</small>
                  </div>
                  <div v-else class="c-status pending">
                    <i class="fa-solid fa-clock"></i>
                    <strong>CHỜ XÁC NHẬN TRÊN ỨNG DỤNG</strong>
                    <small>Khách hàng trực tiếp xác nhận qua tài khoản cá nhân.</small>
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
          <button class="btn-outline" @click="showInspectionModal = false">Đóng</button>
          <button class="btn-primary" @click="printClientInspectionReport">
            <i class="fa-solid fa-print"></i> In Biên Bản
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import html2pdf from 'html2pdf.js';
import { createToaster } from "@meforma/vue-toaster";
import { useSystemSettings } from '../../../core/settingsStore';

const toaster = createToaster({ position: "top-right" });
const { settings, fetchSettings } = useSystemSettings();

const route = useRoute();
const router = useRouter();
const bookingId = route.params.id;

const booking = ref(null);
const user = ref(null);
const loading = ref(true);
const isExporting = ref(false);
const showFullBill = ref(false);

const handoverData = ref(null);
const loadingHandover = ref(false);
const confirmingDelivery = ref(false);
const confirmingReturn = ref(false);
const deliveryCustomerNote = ref('');
const deliveryAgreeCheck = ref(false);
const returnCustomerNote = ref('');
const returnAgreeCheck = ref(false);
const showInspectionModal = ref(false);
const fullHandoverImageUrl = ref(null);

const tempBillFile = ref(null);
const tempBillPreview = ref('');
const isUploading = ref(false);

const CAR_IMAGE_MAP = {
  'vf9': '/assets/images/vf9.png',
  'vf8': '/assets/images/cars/vf8.png',
  'vf7': '/assets/images/cars/vf7.png',
  'camry': '/assets/images/cars/camry.png',
  'xpander': '/assets/images/cars/xpander.png'
};

const handleImgError = (event) => {
  event.target.src = '/assets/images/vf9.png';
};

const getCarImage = (name) => {
  const n = (name || '').toLowerCase();
  for (const [key, path] of Object.entries(CAR_IMAGE_MAP)) {
    if (n.includes(key)) return path;
  }
  return '/assets/images/vf9.png';
};

const transactions = ref([]);
const loadingTx = ref(false);

const formatCurrency = (amount) => {
  if (amount === undefined || amount === null) return '0 ₫';
  return Number(amount).toLocaleString('vi-VN') + ' ₫';
};

const formatMethod = (method) => {
  const map = {
    'bank_transfer': 'Chuyển khoản',
    'cash': 'Tiền mặt',
    'credit_card': 'Thẻ tín dụng',
    'pos': 'Quẹt thẻ POS',
    'other': 'Khác'
  };
  return map[method] || method || 'Chuyển khoản';
};

const fetchTransactions = async (id) => {
  if (!id) return;
  loadingTx.value = true;
  try {
    const res = await baseRequest.get(`user/bookings/${id}/transactions`);
    if (res.data.status && res.data.data) {
      transactions.value = res.data.data.transactions || [];
      if (res.data.data.summary && booking.value) {
        booking.value.financial_summary = res.data.data.summary;
      }
    }
  } catch (err) {
    console.warn("Could not load transactions:", err);
  } finally {
    loadingTx.value = false;
  }
};

const fetchHandoverData = async (id) => {
  if (!id) return;
  loadingHandover.value = true;
  try {
    const res = await baseRequest.get(`user/bookings/${id}/handovers`);
    if (res.data.status && res.data.data) {
      handoverData.value = res.data.data;
    }
  } catch (err) {
    console.warn("Could not load handovers:", err);
  } finally {
    loadingHandover.value = false;
  }
};

const getHandoverImageClientUrl = (imagePath) => {
  if (!imagePath || !booking.value) return '';
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) return imagePath;
  const filename = imagePath.split('/').pop();
  const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
  const url = `http://localhost:8000/api/bookings/${booking.value.id}/handovers/images/${filename}`;
  return token ? `${url}?token=${encodeURIComponent(token)}` : url;
};

const viewHandoverImage = (url) => {
  fullHandoverImageUrl.value = url;
};

const printClientInspectionReport = () => {
  window.print();
};

const confirmHandover = async (type) => {
  if (!booking.value) return;
  const isDelivery = type === 'delivery';
  if (isDelivery && !deliveryAgreeCheck.value) {
    toaster.warning("Vui lòng tích chọn đồng ý với hiện trạng phương tiện trong biên bản!");
    return;
  }
  if (!isDelivery && !returnAgreeCheck.value) {
    toaster.warning("Vui lòng tích chọn xác nhận kiểm tra và quyết toán trả xe!");
    return;
  }

  if (isDelivery) confirmingDelivery.value = true;
  else confirmingReturn.value = true;

  try {
    const res = await baseRequest.post(`user/bookings/${booking.value.id}/handovers/confirm`, {
      type,
      customer_notes: isDelivery ? deliveryCustomerNote.value : returnCustomerNote.value,
    });
    if (res.data.status) {
      toaster.success(res.data.message || "Xác nhận biên bản thành công!");
      await fetchHandoverData(booking.value.id);
    } else {
      toaster.error(res.data.message || "Xác nhận thất bại!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi xác nhận biên bản!");
  } finally {
    if (isDelivery) confirmingDelivery.value = false;
    else confirmingReturn.value = false;
  }
};

const fetchBookingDetail = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('user/bookings/data');
    if (res.data.status) {
      booking.value = res.data.data.find(b => b.id == bookingId);
      if (booking.value) {
        await Promise.allSettled([
          fetchTransactions(booking.value.id),
          fetchHandoverData(booking.value.id)
        ]);
      }
    }
  } catch (error) {
    console.error("Error fetching booking detail:", error);
    toaster.error("Không thể tải thông tin đơn đặt xe!");
  } finally {
    loading.value = false;
  }
};

const rentalDays = computed(() => {
  if (!booking.value) return 0;
  const start = new Date(booking.value.start_date);
  const end = new Date(booking.value.end_date);
  const diffTime = Math.abs(end - start);
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
});

const pricePerDay = computed(() => {
  if (!booking.value || !rentalDays.value) return 0;
  return booking.value.tong_tien / rentalDays.value;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getRentalStatusText = (status) => {
  const texts = {
    0: 'Chờ duyệt',
    1: 'Đã duyệt',
    2: 'Đã hủy',
    3: 'Đang thuê',
    4: 'Đã trả xe',
    5: 'Hoàn tất'
  };
  return texts[Number(status)] || 'Đang xử lý';
};

const getRentalStatusClass = (status) => {
  const num = Number(status);
  if (num === 0) return 'pending';
  if (num === 1) return 'confirmed';
  if (num === 2) return 'cancelled';
  if (num === 3) return 'in-progress';
  if (num === 4) return 'returned';
  if (num === 5) return 'completed';
  return 'pending';
};

const cancelCurrentBooking = async () => {
  if (!booking.value) return;
  const reason = prompt(`Xác nhận hủy đơn đặt xe #${booking.value.ma_dat_xe}.\nVui lòng nhập lý do hủy đơn (tùy chọn):`, "Khách hàng đổi lịch trình");
  if (reason === null) return;

  try {
    const res = await baseRequest.post('user/bookings/huy-lich', {
      id: booking.value.id,
      cancel_reason: reason.trim() || 'Khách hàng hủy đơn qua chi tiết chuyến đi'
    });
    if (res.data.status) {
      toaster.success(res.data.message || "Hủy đơn đặt xe thành công!");
      await fetchBookingDetail();
    } else {
      toaster.error(res.data.message || "Không thể hủy đơn đặt xe!");
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Lỗi khi hủy đơn đặt xe!");
  }
};

const getPaymentStatus = (b) => {
  if (!b) return { class: 'unpaid', icon: 'fa-credit-card', text: 'CHƯA THANH TOÁN' };

  // Đơn đã hủy nhưng đã thu tiền
  if (Number(b.tinh_trang) === 2 && Number(b.is_thanh_toan) === 1) {
    return {
      class: 'refund-review',
      icon: 'fa-triangle-exclamation',
      text: 'ĐÃ THU TIỀN (CẦN HOÀN TIỀN)'
    };
  }

  // Đã thanh toán 1 phần
  if (Number(b.is_thanh_toan) === 2) {
    return {
      class: 'partial',
      icon: 'fa-chart-pie',
      text: 'ĐÃ THANH TOÁN 1 PHẦN'
    };
  }
  
  if (Number(b.is_thanh_toan) === 1) {
    return {
      class: 'paid',
      icon: 'fa-circle-check',
      text: 'ĐÃ XÁC NHẬN THANH TOÁN'
    };
  }
  
  if (b.bill_image) {
    return {
      class: 'verifying',
      icon: 'fa-clock-rotate-left',
      text: 'CHỜ ĐỐI SOÁT BIÊN LAI'
    };
  }
  
  return {
    class: 'unpaid',
    icon: 'fa-credit-card',
    text: 'CHƯA THANH TOÁN'
  };
};

const getReceiptSecureUrl = (b) => {
  if (!b) return '';
  const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
  const baseUrl = b.receipt_url || `http://localhost:8000/api/bookings/${b.id}/receipt`;
  return token ? `${baseUrl}?token=${encodeURIComponent(token)}` : baseUrl;
};

const handleBillChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toaster.warning("Kích thước ảnh không được vượt quá 5MB!");
    return;
  }

  tempBillFile.value = file;
  tempBillPreview.value = URL.createObjectURL(file);
};

const uploadBookingBill = async () => {
  if (!tempBillFile.value || !booking.value) return;

  isUploading.value = true;
  try {
    const formData = new FormData();
    formData.append('booking_id', booking.value.id);
    formData.append('bill_image', tempBillFile.value);

    const res = await baseRequest.post('user/bookings/upload-bill', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.status) {
      toaster.success(res.data.message || "Tải biên lai thành công!");
      tempBillFile.value = null;
      tempBillPreview.value = '';
      await fetchBookingDetail();
    } else {
      toaster.error(res.data.message || "Không thể tải biên lai!");
    }
  } catch (err) {
    toaster.error("Có lỗi xảy ra khi tải ảnh!");
  } finally {
    isUploading.value = false;
  }
};

const exportToPDF = () => {
  if (!booking.value) return;
  isExporting.value = true;
  const element = document.getElementById('invoice-content');
  const opt = {
    margin: [0.3, 0.3],
    filename: `DriveRent_Invoice_${booking.value.ma_dat_xe}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, letterRendering: true },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save().then(() => {
    isExporting.value = false;
    toaster.success("Xuất hóa đơn PDF thành công!");
  }).catch(() => {
    isExporting.value = false;
    toaster.error("Lỗi khi xuất PDF!");
  });
};

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    try {
      user.value = JSON.parse(storedUser);
    } catch (e) {
      user.value = null;
    }
  }
  fetchBookingDetail();
});
</script>

<style scoped>
.booking-detail-page {
  background: var(--bg-main);
  min-height: 100vh;
  padding: 120px 0 80px;
}

.detail-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.btn-back {
  background: #ffffff;
  border: 1px solid var(--color-border);
  padding: 10px 20px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--color-text-main);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: var(--shadow-xs);
}

.btn-back:hover {
  background: var(--bg-surface-sub);
}

.btn-export {
  background: var(--bg-dark);
  color: #ffffff;
  padding: 10px 22px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: var(--shadow-soft);
}

.btn-export:hover:not(:disabled) {
  background: var(--color-primary);
  transform: translateY(-1px);
}

/* Invoice Content */
.invoice-wrapper {
  background: #ffffff;
  padding: 48px;
  border-radius: var(--radius-xl);
  max-width: 960px;
  margin: 0 auto;
}

.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.logo-box {
  width: 36px;
  height: 36px;
  background: var(--grad-active);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.logo-text {
  font-family: var(--font-heading);
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-text-main);
}

.logo-text span {
  color: var(--color-primary);
}

.company-sub {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.company-contact {
  font-size: 0.825rem;
  color: var(--color-text-subtle);
  margin-top: 4px;
}

.invoice-meta {
  text-align: right;
}

.invoice-meta h2 {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-main);
  letter-spacing: -0.02em;
}

.invoice-id {
  font-family: var(--font-mono);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-primary);
  margin: 4px 0;
}

.invoice-date {
  font-size: 0.825rem;
  color: var(--color-text-muted);
}

.invoice-divider {
  height: 1px;
  background: var(--color-border);
  margin: 32px 0;
}

/* Info Grid */
.invoice-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 36px;
  margin-bottom: 36px;
}

.info-section h4 {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-section h4 i {
  color: var(--color-primary);
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed var(--color-border-subtle);
  font-size: 0.885rem;
}

.info-row .label {
  color: var(--color-text-muted);
}

.info-row .value {
  font-weight: 600;
  color: var(--color-text-main);
}

/* Table */
.invoice-table {
  margin-bottom: 32px;
}

.invoice-table table {
  width: 100%;
  border-collapse: collapse;
}

.invoice-table th {
  padding: 14px 16px;
  background: var(--bg-surface-sub);
  color: var(--color-text-muted);
  font-size: 0.775rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
}

.invoice-table td {
  padding: 18px 16px;
  border-bottom: 1px solid var(--color-border-subtle);
  vertical-align: middle;
  font-size: 0.9rem;
}

.car-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.pdf-car-img {
  width: 72px;
  height: 48px;
  object-fit: contain;
  background: var(--bg-surface-sub);
  border-radius: var(--radius-xs);
  padding: 4px;
}

.car-title strong {
  display: block;
  font-size: 0.95rem;
}

.car-title span {
  font-size: 0.775rem;
  color: var(--color-text-muted);
}

.text-right {
  text-align: right;
}

.font-bold {
  font-weight: 700;
}

/* Summary Area */
.invoice-summary-area {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 32px;
  align-items: flex-start;
  margin-bottom: 36px;
}

.status-summary-box {
  display: flex;
  gap: 16px;
  background: var(--bg-surface-sub);
  padding: 16px 20px;
  border-radius: var(--radius-md);
}

.summary-status-tag {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.summary-status-tag .label {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.included-tag {
  color: var(--color-success);
  font-weight: 600;
}

.summary-row.total {
  padding-top: 12px;
  border-top: 2px solid var(--color-border);
  font-size: 1.1rem;
  font-weight: 800;
}

.total-price {
  color: var(--color-primary);
  font-family: var(--font-heading);
  font-size: 1.35rem;
}

/* Receipt Verification */
.receipt-verification-box {
  background: var(--bg-surface-sub);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 24px;
  margin-bottom: 36px;
}

.verified-bill-content h4 {
  font-size: 0.95rem;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.bill-preview-wrapper {
  position: relative;
  display: inline-block;
  cursor: pointer;
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.receipt-thumbnail {
  max-height: 140px;
  display: block;
}

.bill-preview-wrapper .overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.8rem;
  opacity: 0;
  transition: opacity var(--duration-fast);
}

.bill-preview-wrapper:hover .overlay {
  opacity: 1;
}

.verification-notice {
  margin-top: 14px;
  padding: 14px 18px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.85rem;
  line-height: 1.5;
}

.verification-notice.pending {
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  color: #92400E;
}

.verification-notice.pending i {
  color: #D97706;
  font-size: 1.3rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.verification-notice.verified {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  color: #166534;
}

.verification-notice.verified i {
  color: #16A34A;
  font-size: 1.3rem;
  margin-top: 2px;
  flex-shrink: 0;
}

.verification-notice strong {
  display: block;
  font-size: 0.92rem;
  margin-bottom: 3px;
}

.verification-notice p {
  margin: 0;
  font-size: 0.82rem;
  opacity: 0.95;
}

/* Unpaid & Upload */
.unpaid-action-box h4 {
  font-size: 1rem;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.unpaid-action-box p {
  font-size: 0.85rem;
  margin-bottom: 16px;
}

.qr-upload-grid {
  display: grid;
  grid-template-columns: 160px 1fr;
  gap: 20px;
  align-items: center;
}

.invoice-qr {
  max-width: 150px;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-xs);
}

.file-drop-box {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: 16px;
  text-align: center;
  cursor: pointer;
  background: #ffffff;
}

.file-drop-box:hover {
  border-color: var(--color-primary);
}

.file-drop-box i {
  font-size: 1.5rem;
  color: var(--color-primary);
  display: block;
  margin-bottom: 6px;
}

.file-drop-box span {
  font-size: 0.825rem;
  color: var(--color-text-muted);
}

.preview-mini img {
  max-height: 80px;
  margin: 0 auto 6px;
}

/* Footer Signature */
.invoice-footer {
  border-top: 1px solid var(--color-border);
  padding-top: 32px;
  text-align: center;
}

.signature-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 24px;
}

.signature-box {
  text-align: center;
  width: 220px;
}

.sign-title {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin-bottom: 12px;
}

.stamp-box {
  border: 2px solid var(--color-primary);
  color: var(--color-primary);
  border-radius: var(--radius-sm);
  padding: 8px;
  display: flex;
  flex-direction: column;
  transform: rotate(-3deg);
  margin-bottom: 12px;
}

.stamp-box span {
  font-family: var(--font-heading);
  font-weight: 800;
  font-size: 1rem;
}

.stamp-box small {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.sign-name {
  font-weight: 700;
  font-size: 0.9rem;
}

.thanks {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.image-viewer {
  max-width: 90vw;
  max-height: 90vh;
  position: relative;
  background: transparent;
  box-shadow: none;
  border: none;
  text-align: center;
}

.image-viewer img {
  max-width: 100%;
  max-height: 85vh;
  object-fit: contain;
  border-radius: var(--radius-md);
}

.btn-close-abs {
  position: absolute;
  top: -40px;
  right: 0;
  color: white;
  font-size: 2rem;
  background: none;
  border: none;
  cursor: pointer;
}

.loading-state, .empty-state {
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 2.5rem;
  color: var(--color-warning);
  margin-bottom: 16px;
}

.mt-4 {
  margin-top: 16px;
}

.mt-2 {
  margin-top: 8px;
}

.w-full {
  width: 100%;
}

@media (max-width: 768px) {
  .invoice-wrapper {
    padding: 24px;
  }
  .invoice-header {
    flex-direction: column;
  }
  .invoice-meta {
    text-align: left;
  }
  .invoice-grid {
    grid-template-columns: 1fr;
  }
  .invoice-summary-area {
    grid-template-columns: 1fr;
  }
  .qr-upload-grid {
    grid-template-columns: 1fr;
  }
  .signature-section {
    justify-content: center;
  }
}

.financial-alert-box {
  background: #FFF1F2;
  border: 1px solid #FECDD3;
  border-radius: var(--radius-sm);
  padding: 14px 18px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: #9F1239;
  font-size: 0.88rem;
  line-height: 1.45;
}
.financial-alert-box i {
  font-size: 1.3rem;
  color: #E11D48;
  margin-top: 2px;
}
.financial-alert-box strong {
  display: block;
  font-size: 0.95rem;
  margin-bottom: 2px;
}

/* Client Lifecycle Stepper Styles */
.client-lifecycle-container {
  background: var(--bg-surface-sub);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 20px 24px;
}

.lifecycle-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.lifecycle-header-row h4 {
  font-size: 1rem;
  font-weight: 800;
  color: var(--color-text-main);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-cancel-trip {
  background: #FFF1F2;
  color: #E11D48;
  border: 1px solid #FECDD3;
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}
.btn-cancel-trip:hover {
  background: #FFE4E6;
  border-color: #FDA4AF;
}

.cancelled-trip-banner {
  background: #FFF1F2;
  border: 1px solid #FECDD3;
  border-radius: var(--radius-sm);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  color: #9F1239;
}
.cancel-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #FEE2E2;
  color: #DC2626;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}
.cancel-info strong {
  display: block;
  font-size: 1rem;
  margin-bottom: 2px;
}
.cancel-info p {
  margin: 0;
  font-size: 0.85rem;
}
.cancel-info small {
  display: block;
  color: #BE123C;
  font-size: 0.78rem;
  margin-top: 4px;
}

.stepper-horizontal {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  position: relative;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  z-index: 2;
  min-width: 80px;
}

.step-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--bg-surface);
  border: 2px solid #CBD5E1;
  color: #94A3B8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.3s ease;
  margin-bottom: 8px;
}

.step-item.completed .step-circle {
  background: #10B981;
  border-color: #10B981;
  color: white;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
}

.step-item.active .step-circle {
  background: #2563EB;
  border-color: #2563EB;
  color: white;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
}

.step-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-text-main);
  white-space: nowrap;
}

.step-time {
  font-size: 0.7rem;
  color: var(--color-text-muted);
  margin-top: 2px;
  white-space: nowrap;
}

.step-line {
  flex: 1;
  height: 3px;
  background: #E2E8F0;
  margin: 0 6px;
  margin-top: -24px;
  transition: all 0.3s ease;
  z-index: 1;
}

.step-line.active {
  background: #10B981;
}

/* Client Ledger & Deposit Management Styling */
.client-ledger-section {
  background: var(--bg-surface-sub);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 24px;
}

.section-title-wrap h4 {
  font-size: 1.1rem;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-sub {
  font-size: 0.825rem;
  margin: 0;
}

.ledger-explainer-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: var(--radius-md);
  padding: 14px 18px;
}

.explainer-icon {
  width: 36px;
  height: 36px;
  background: #DCFCE7;
  color: #16A34A;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.explainer-text strong {
  display: block;
  font-size: 0.88rem;
  color: #166534;
  margin-bottom: 2px;
}

.explainer-text p {
  margin: 0;
  font-size: 0.82rem;
  color: #15803D;
  line-height: 1.45;
}

.financial-balance-cards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.balance-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 18px 20px;
  box-shadow: var(--shadow-xs);
}

.balance-card .card-head {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  border-bottom: 1px solid var(--color-border-subtle);
  padding-bottom: 10px;
  margin-bottom: 12px;
}

.balance-card .card-head h5 {
  margin: 0;
  font-size: 0.95rem;
}

.balance-card .card-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.balance-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.balance-line strong {
  font-size: 0.92rem;
  color: var(--color-text-main);
}

.balance-line.highlight {
  border-top: 1px dashed var(--color-border);
  padding-top: 8px;
  margin-top: 4px;
}

.balance-line.highlight strong {
  font-size: 1.05rem;
  font-weight: 800;
}

.client-transactions-history {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 18px 20px;
}

.history-title {
  font-size: 0.92rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-main);
}

.transactions-table-wrap {
  overflow-x: auto;
}

.client-tx-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.825rem;
}

.client-tx-table th {
  background: var(--bg-surface-sub);
  padding: 10px 12px;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-align: left;
  border-bottom: 2px solid var(--color-border);
}

.client-tx-table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--color-border-subtle);
  vertical-align: middle;
}

.tx-ref {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  background: var(--bg-surface-sub);
  padding: 2px 6px;
  border-radius: var(--radius-xs);
  color: #475569;
}

.client-deduction-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #FEE2E2;
  color: #DC2626;
  padding: 2px 6px;
  border-radius: var(--radius-xs);
  font-size: 0.72rem;
  margin-top: 3px;
}

.empty-tx-client {
  text-align: center;
  padding: 32px 16px;
  color: var(--color-text-muted);
}

.empty-tx-client i {
  font-size: 2rem;
  opacity: 0.35;
  margin-bottom: 8px;
  display: block;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: var(--radius-xs);
  font-size: 0.72rem;
  font-weight: 700;
}
.badge-success { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
.badge-warning { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
.badge-info { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
.badge-secondary { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
.badge-danger { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }

.status-badge.partial {
  background: #FEF3C7;
  color: #B45309;
  border: 1px solid #FCD34D;
}

.text-info { color: #0284C7 !important; }

@media (max-width: 768px) {
  .financial-balance-cards-grid {
    grid-template-columns: 1fr;
  }
}

/* ============================================================
   CLIENT HANDOVER & INSPECTION SECTION STYLES
   ============================================================ */
.handover-header-flex {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.handover-diff-banner {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: var(--radius-md);
  padding: 14px 18px;
}
.handover-diff-banner .banner-title {
  font-size: 0.88rem;
  font-weight: 800;
  color: #166534;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.diff-chips-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
.diff-chips-row .chip-item {
  background: white;
  border: 1px solid #DCFCE7;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
}
.diff-chips-row .c-lbl { font-size: 0.78rem; color: #475569; }

.handover-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
}

.handover-stage-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 18px;
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
}
.handover-stage-card.delivery-card { border-top: 4px solid #0284C7; }
.handover-stage-card.return-card { border-top: 4px solid #7C3AED; }

.stage-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.stage-badge {
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.stage-badge.delivery { background: #E0F2FE; color: #0369A1; }
.stage-badge.return { background: #EDE9FE; color: #6D28D9; }
.stage-time { font-size: 0.75rem; color: var(--color-text-muted); }

.stage-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  background: #F8FAFC;
  padding: 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}
.s-stat { display: flex; flex-direction: column; gap: 2px; }
.s-stat.full { grid-column: span 2; }
.s-stat .s-lbl { font-size: 0.72rem; color: var(--color-text-muted); }
.s-stat .s-val { font-size: 0.95rem; font-weight: 700; color: var(--color-text-main); }

.client-checklist-summary .chk-title, .client-photos-row .chk-title {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 6px;
}
.chk-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.chk-tag {
  font-size: 0.72rem;
  padding: 3px 8px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.chk-tag.ok {
  background: #F0FDF4;
  color: #16A34A;
  border: 1px solid #BBF7D0;
}

.client-status-notes {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  line-height: 1.4;
}
.client-status-notes p { margin: 2px 0; }

.thumb-strip {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.client-handover-thumb {
  width: 70px;
  height: 55px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #CBD5E1;
  cursor: pointer;
  transition: transform 0.15s;
}
.client-handover-thumb:hover { transform: scale(1.05); }

.customer-confirm-block {
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px dashed var(--color-border);
}
.confirm-badge.confirmed {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  color: #166534;
}
.confirm-badge.confirmed i { font-size: 1.25rem; color: #16A34A; margin-top: 2px; }
.confirm-badge.confirmed strong { display: block; font-size: 0.82rem; }
.confirm-badge.confirmed small { font-size: 0.72rem; color: #475569; }
.user-confirmed-note {
  margin-top: 4px;
  font-style: italic;
  font-size: 0.75rem;
  color: #065F46;
}

.confirm-action-box {
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  padding: 12px;
  border-radius: var(--radius-sm);
}
.confirm-action-box .custom-checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 0.78rem;
  color: #92400E;
  cursor: pointer;
  line-height: 1.35;
}
.confirm-action-box .custom-checkbox-label input {
  margin-top: 2px;
}

.client-surcharges-box {
  background: #FEF2F2;
  border: 1px solid #FECACA;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
}
.surcharges-list {
  list-style: none;
  padding: 0;
  margin: 4px 0 0;
  font-size: 0.78rem;
}
.surcharges-list li {
  display: flex;
  justify-content: space-between;
  padding: 3px 0;
  border-bottom: 1px dashed #FCA5A5;
}

/* Printable Inspection Modal for Client */
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
}
.sheet-header {
  display: flex;
  justify-content: space-between;
  border-bottom: 2px solid #0F172A;
  padding-bottom: 16px;
  margin-bottom: 16px;
}
.sheet-title-block { text-align: right; }
.sheet-title-block h2 { font-size: 1.1rem; font-weight: 800; margin: 0; }
.sheet-title-block .doc-code { font-size: 0.85rem; font-weight: 700; color: #0284C7; }
.sheet-title-block .doc-date { font-size: 0.75rem; color: #64748B; }

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
  font-size: 0.88rem;
  font-weight: 800;
  border-bottom: 2px solid #E2E8F0;
  padding-bottom: 8px;
  margin-bottom: 10px;
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
.stat-row .val-metric { font-size: 0.92rem; color: #0284C7; }

.status-notes-box {
  background: white;
  border: 1px solid #E2E8F0;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 0.78rem;
}
.status-notes-box p { margin: 2px 0; }

.diff-summary-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px;
}
.diff-title {
  font-size: 0.88rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.diff-metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
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
.report-table th { background: #F1F5F9; font-size: 0.75rem; text-align: left; }

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
.confirm-box .c-title { display: block; font-size: 0.72rem; font-weight: 700; color: #64748B; margin-bottom: 4px; }
.confirm-box .c-status { display: flex; flex-direction: column; gap: 2px; }
.confirm-box .c-status.ok { color: #16A34A; }
.confirm-box .c-status.pending { color: #D97706; }
.confirm-box .c-status strong { font-size: 0.82rem; }
.confirm-box .c-status small { font-size: 0.72rem; color: #64748B; }

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
  #client-inspection-printable-area, #client-inspection-printable-area * {
    visibility: visible;
  }
  #client-inspection-printable-area {
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
