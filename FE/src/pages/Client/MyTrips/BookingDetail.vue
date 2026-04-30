<template>
  <div class="booking-detail-page">
    <div class="app-container py-10">
      <!-- Header Actions -->
      <div class="detail-actions animate-in">
        <button @click="router.back()" class="btn-back">
          <i class="fa-solid fa-arrow-left"></i> Quay lại
        </button>
        <div class="right-actions">
          <button @click="exportToPDF" class="btn-export" :disabled="isExporting">
            <i v-if="!isExporting" class="fa-solid fa-file-pdf"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            {{ isExporting ? 'Đang tạo PDF...' : 'Xuất Hóa Đơn PDF' }}
          </button>
        </div>
      </div>

      <!-- Invoice Content to be Exported -->
      <div id="invoice-content" ref="invoiceRef" class="invoice-wrapper card glass animate-in delay-1">
        <!-- Invoice Header -->
        <div class="invoice-header">
          <div class="company-info">
            <div class="logo">
              <i class="fa-solid fa-car-side"></i>
              <span>DriveRent</span>
            </div>
            <p>123 Đường VinFast, Quận Bình Thạnh, TP. HCM</p>
            <p>Hotline: 1900 1234 | Email: support@driverent.vn</p>
          </div>
          <div class="invoice-title">
            <h2>HÓA ĐƠN THUÊ XE</h2>
            <div class="invoice-id">#{{ booking?.ma_dat_xe }}</div>
            <div class="invoice-date">Ngày tạo: {{ formatDate(new Date()) }}</div>
          </div>
        </div>

        <div class="invoice-divider"></div>

        <!-- Customer & Trip Info -->
        <div class="invoice-grid">
          <div class="info-section">
            <h4>Thông tin khách hàng</h4>
            <div class="info-row">
              <span class="label">Họ tên:</span>
              <span class="value">{{ user?.ho_ten }}</span>
            </div>
            <div class="info-row">
              <span class="label">Email:</span>
              <span class="value">{{ user?.email }}</span>
            </div>
            <div class="info-row">
              <span class="label">Số điện thoại:</span>
              <span class="value">{{ booking?.user_phone || user?.so_dien_thoai || 'Chưa cập nhật' }}</span>
            </div>
          </div>

          <div class="info-section">
            <h4>Thông tin chuyến đi</h4>
            <div class="info-row">
              <span class="label">Ngày nhận xe:</span>
              <span class="value">{{ formatDate(booking?.start_date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Ngày trả xe:</span>
              <span class="value">{{ formatDate(booking?.end_date) }}</span>
            </div>
            <div class="info-row">
              <span class="label">Nơi nhận xe:</span>
              <span class="value">{{ booking?.dia_chi_nhan_xe || 'Tại Showroom' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Nơi trả xe:</span>
              <span class="value">{{ booking?.dia_chi_tra_xe || 'Tại Showroom' }}</span>
            </div>
          </div>
        </div>

        <!-- Car Details Table -->
        <div class="invoice-table">
          <table>
            <thead>
              <tr>
                <th>Thông tin xe</th>
                <th>Thời gian thuê</th>
                <th>Đơn giá/Ngày</th>
                <th class="text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="car-item">
                    <img :src="getCarImage(booking?.car_name)" alt="car" class="pdf-car-img">
                    <div class="car-name">
                      <strong>{{ booking?.car_name }}</strong>
                      <span>VinFast Electric Smart Car</span>
                    </div>
                  </div>
                </td>
                <td>{{ rentalDays }} ngày</td>
                <td>{{ Number(pricePerDay).toLocaleString() }}đ</td>
                <td class="text-right">{{ Number(booking?.tong_tien).toLocaleString() }}đ</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="invoice-summary">
          <div class="summary-details">
            <div class="summary-row">
              <span>Tạm tính:</span>
              <span>{{ Number(booking?.tong_tien).toLocaleString() }}đ</span>
            </div>
            <div class="summary-row">
              <span>Thuế GTGT (10%):</span>
              <span>Đã bao gồm</span>
            </div>
            <div class="summary-row total">
              <span>TỔNG THANH TOÁN:</span>
              <span class="total-price">{{ Number(booking?.tong_tien).toLocaleString() }}đ</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
          <div class="payment-status" :class="booking?.is_thanh_toan ? 'paid' : 'unpaid'">
            Trạng thái: {{ booking?.is_thanh_toan ? 'ĐÃ THANH TOÁN' : 'CHƯA THANH TOÁN' }}
          </div>
          <div class="signature-section">
            <div class="signature-box">
              <p>Đại diện DriveRent</p>
              <div class="stamp">DriveRent Official</div>
              <p>Nguyễn Văn Quản Lý</p>
            </div>
          </div>
          <p class="thanks">Cảm ơn quý khách đã tin tưởng và sử dụng dịch vụ của DriveRent!</p>
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

const route = useRoute();
const router = useRouter();
const bookingId = route.params.id;
const booking = ref(null);
const user = ref(JSON.parse(localStorage.getItem('user')));
const isExporting = ref(false);

const fetchBookingDetail = async () => {
  try {
    const res = await baseRequest.get('user/bookings/data');
    if (res.data.status) {
      // Find the specific booking from the list
      booking.value = res.data.data.find(b => b.id == bookingId);
    }
  } catch (error) {
    console.error("Error fetching booking detail:", error);
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
  if (!booking.value) return 0;
  return booking.value.tong_tien / rentalDays.value;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const getCarImage = (name) => {
  const n = (name || '').toLowerCase();
  if (n.includes('vf9')) return '/assets/images/cars/vf9.png';
  if (n.includes('vf8')) return '/assets/images/cars/vf8.png';
  if (n.includes('vf7')) return '/assets/images/cars/vf7.png';
  return '/assets/images/cars/camry.png';
};

const exportToPDF = () => {
  isExporting.value = true;
  const element = document.getElementById('invoice-content');
  const opt = {
    margin:       [0.5, 0.5],
    filename:     `DriveRent_Invoice_${booking.value.ma_dat_xe}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save().then(() => {
    isExporting.value = false;
  });
};

onMounted(() => {
  fetchBookingDetail();
});
</script>

<style scoped>
.booking-detail-page {
  background: #f8fafc;
  min-height: 100vh;
  padding-top: 100px;
  font-family: 'Inter', sans-serif;
}

.detail-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.btn-back {
  background: white;
  border: 1.5px solid #e2e8f0;
  padding: 10px 20px;
  border-radius: 12px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
}

.btn-back:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.btn-export {
  background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 10px 20px rgba(185, 28, 28, 0.2);
  transition: all 0.3s;
}

.btn-export:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(185, 28, 28, 0.3);
}

.btn-export:disabled { opacity: 0.7; cursor: not-allowed; }

.invoice-wrapper {
  background: white !important;
  border-radius: 24px;
  padding: 60px;
  max-width: 900px;
  margin: 0 auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
}

.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 12px;
}

.logo i { color: #2563eb; }

.company-info p {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 4px;
}

.invoice-title {
  text-align: right;
}

.invoice-title h2 {
  font-size: 2rem;
  font-weight: 900;
  color: #0f172a;
  margin-bottom: 8px;
  letter-spacing: -1px;
}

.invoice-id {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2563eb;
}

.invoice-date {
  color: #64748b;
  font-size: 0.9rem;
}

.invoice-divider {
  height: 2px;
  background: #f1f5f9;
  margin: 40px 0;
}

.invoice-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  margin-bottom: 60px;
}

.info-section h4 {
  font-size: 0.85rem;
  font-weight: 800;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  margin-bottom: 12px;
  font-size: 0.95rem;
}

.info-row .label {
  width: 130px;
  color: #64748b;
  font-weight: 600;
}

.info-row .value {
  color: #0f172a;
  font-weight: 700;
}

.invoice-table {
  margin-bottom: 40px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  text-align: left;
  padding: 16px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 800;
  text-transform: uppercase;
}

td {
  padding: 24px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.car-item {
  display: flex;
  align-items: center;
  gap: 20px;
}

.pdf-car-img {
  width: 100px;
  height: 60px;
  object-fit: contain;
}

.car-name strong {
  display: block;
  font-size: 1.1rem;
  color: #0f172a;
}

.car-name span {
  font-size: 0.85rem;
  color: #64748b;
}

.text-right { text-align: right; }

.invoice-summary {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 60px;
}

.summary-details {
  width: 300px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  color: #64748b;
  font-weight: 600;
}

.summary-row.total {
  margin-top: 12px;
  padding-top: 24px;
  border-top: 2px solid #0f172a;
  color: #0f172a;
  font-weight: 900;
}

.total-price {
  font-size: 1.8rem;
  color: #2563eb;
}

.invoice-footer {
  text-align: center;
}

.payment-status {
  display: inline-block;
  padding: 8px 24px;
  border-radius: 12px;
  font-weight: 900;
  margin-bottom: 40px;
  font-size: 1rem;
}

.payment-status.paid { background: #f0fdf4; color: #16a34a; border: 2px solid #16a34a; }
.payment-status.unpaid { background: #fff1f2; color: #e11d48; border: 2px solid #e11d48; }

.signature-section {
  display: flex;
  justify-content: center;
  margin-bottom: 40px;
}

.signature-box {
  width: 250px;
  text-align: center;
}

.stamp {
  width: 150px;
  height: 150px;
  border: 4px solid #ef4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 20px auto;
  color: #ef4444;
  font-weight: 900;
  font-size: 1rem;
  text-transform: uppercase;
  transform: rotate(-15deg);
  opacity: 0.6;
}

.thanks {
  color: #94a3b8;
  font-style: italic;
  font-size: 0.95rem;
}

@media (max-width: 768px) {
  .invoice-wrapper { padding: 30px; }
  .invoice-header, .invoice-grid { flex-direction: column; gap: 30px; }
  .invoice-title { text-align: left; }
  .invoice-grid { grid-template-columns: 1fr; }
  .summary-details { width: 100%; }
}

.animate-in { animation: fadeInUp 0.6s ease-out; }
.delay-1 { animation-delay: 0.2s; animation-fill-mode: both; }

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
