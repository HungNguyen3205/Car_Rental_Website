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
              <span class="label">Trạng thái đơn:</span>
              <span class="status-badge" :class="getRentalStatusClass(booking.tinh_trang)">
                {{ getRentalStatusText(booking.tinh_trang) }}
              </span>
            </div>
            <div class="summary-status-tag">
              <span class="label">Thanh toán:</span>
              <span class="status-badge" :class="getPaymentStatus(booking).class">
                <i class="fa-solid" :class="getPaymentStatus(booking).icon"></i>
                {{ getPaymentStatus(booking).text }}
              </span>
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
                <img 
                  :src="`https://api.vietqr.io/image/970436-123456789-V3N0a6.jpg?accountName=NAM%20HUNG%20RENTAL&amount=${booking.tong_tien}&addInfo=${booking.ma_dat_xe}`" 
                  alt="QR Code" 
                  class="invoice-qr"
                />
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import html2pdf from 'html2pdf.js';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const route = useRoute();
const router = useRouter();
const bookingId = route.params.id;

const booking = ref(null);
const user = ref(null);
const loading = ref(true);
const isExporting = ref(false);
const showFullBill = ref(false);

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

const fetchBookingDetail = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('user/bookings/data');
    if (res.data.status) {
      booking.value = res.data.data.find(b => b.id == bookingId);
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
  const texts = ['Chờ xác nhận', 'Đã xác nhận', 'Đã hủy'];
  return texts[status] || 'Đang xử lý';
};

const getRentalStatusClass = (status) => {
  if (status === 0) return 'pending';
  if (status === 1) return 'paid';
  return 'cancelled';
};

const getPaymentStatus = (b) => {
  if (!b) return { class: 'unpaid', icon: 'fa-credit-card', text: 'CHỜ THANH TOÁN' };
  
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
    text: 'CHỜ THANH TOÁN'
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
</style>
