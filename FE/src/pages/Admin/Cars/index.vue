<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-car-rear"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Kho Xe</span></h1>
          <p class="page-subtitle">Theo dõi, điều chỉnh trạng thái và thông tin xe trong toàn hệ thống.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal()">
        <i class="fa-solid fa-plus"></i> Thêm xe mới
      </button>
    </header>

    <!-- Content Card -->
    <div class="content-card card">
      <!-- Search & Filters -->
      <div class="table-tools">
        <div class="search-input">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Tìm theo tên xe, loại xe..." v-model="searchQuery" />
        </div>
        <div class="filter-group">
          <select v-model="filterStatus" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <option value="available">Sẵn sàng (Hoạt động)</option>
            <option value="maintenance">Bảo trì (Tạm ngưng)</option>
          </select>
        </div>
      </div>

      <!-- Professional Table -->
      <div class="table-responsive">
        <table class="data-table" v-if="filteredCars.length > 0">
          <thead>
            <tr>
              <th width="60">STT</th>
              <th>Thông tin xe</th>
              <th>Loại xe</th>
              <th>Giá thuê / ngày</th>
              <th>Khu vực</th>
              <th>Trạng thái</th>
              <th width="180" class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(car, index) in filteredCars" :key="car.id">
              <td><span class="stt-badge">{{ index + 1 }}</span></td>
              <td>
                <div class="car-cell">
                  <img 
                    :src="resolveCarImage(car)" 
                    class="car-thumb" 
                    @error="handleCarImgError($event)"
                    alt="car"
                  />
                  <div class="car-details">
                    <span class="car-name">{{ car.name }}</span>
                    <span class="car-id">Mã xe: #CAR-{{ car.id }}</span>
                  </div>
                </div>
              </td>
              <td><span class="tag-outline">{{ car.category_name || car.type || 'Tiêu chuẩn' }}</span></td>
              <td>
                <div class="price-box">
                  <span class="price-text">{{ formatCurrency(car.price_per_day) }}</span>
                  <small>/ ngày</small>
                </div>
              </td>
              <td>
                <span class="city-badge">
                  <i class="fa-solid fa-location-dot"></i> {{ getCityLabel(car.city) }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="car.status === 'available' ? 'status-ok' : 'status-warn'">
                  <i class="fa-solid" :class="car.status === 'available' ? 'fa-circle-check' : 'fa-wrench'"></i>
                  {{ car.status === 'available' ? 'Sẵn sàng' : 'Bảo trì' }}
                </span>
              </td>
              <td class="actions-cell">
                <button class="btn-icon" @click="openBookingModal(car)" title="Tạo đơn nhanh">
                  <i class="fa-solid fa-calendar-plus"></i>
                </button>
                <button class="btn-icon" @click="openModal(car)" title="Chỉnh sửa">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn-icon" @click="toggleStatus(car)" title="Đổi trạng thái bảo trì/sẵn sàng">
                  <i class="fa-solid fa-repeat"></i>
                </button>
                <button class="btn-icon text-danger" @click="requestDelete(car)" title="Xóa xe">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- API Error State with Retry -->
        <div v-else-if="fetchCarsError && !loading" class="empty-state-box error-box">
          <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 2.2rem; color: #dc2626; margin-bottom: 12px;"></i>
          <h4>Không thể tải kho xe</h4>
          <p>{{ fetchCarsError }}</p>
          <button class="btn-primary" @click="fetchCars" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-rotate-right"></i> Thử lại
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-car-side"></i>
          <h4>{{ searchQuery.trim() || filterStatus ? 'Không tìm thấy phương tiện phù hợp' : 'Chưa có phương tiện nào trong kho xe' }}</h4>
          <p>{{ searchQuery.trim() || filterStatus ? 'Thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc trạng thái xe.' : 'Bấm nút "Thêm xe mới" ở trên để đưa phương tiện vào hệ thống.' }}</p>
        </div>

        <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
          <div class="spinner"></div>
        </div>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>{{ editingCar.id ? 'Cập nhật thông tin xe' : 'Thêm phương tiện mới' }}</h3>
            <p class="modal-sub">Điền đầy đủ thông tin kỹ thuật và giá thuê theo quy định</p>
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

            <div class="form-group full">
              <label class="form-check-label">
                <input type="checkbox" v-model="editingCar.is_active" />
                <span><strong>Đang phục vụ khách hàng</strong> (Bỏ chọn nếu xe tạm ngừng kinh doanh/ngừng phục vụ)</span>
              </label>
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

    <!-- Delete Confirmation Modal (shows exact car name) -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-content card delete-modal animate-in">
        <div class="modal-header">
          <div class="delete-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <h3>Xác nhận xóa xe</h3>
            <p class="modal-sub">Hành động này sẽ xóa dữ liệu phương tiện vĩnh viễn khỏi danh sách</p>
          </div>
        </div>
        <div class="modal-body">
          <div class="delete-car-info">
            <p>Bạn có chắc chắn muốn xóa phương tiện sau?</p>
            <div class="highlight-box">
              <strong>{{ carToDelete?.name }}</strong>
              <span>Mã định danh: #CAR-{{ carToDelete?.id }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showDeleteModal = false">Hủy</button>
          <button class="btn-danger" @click="confirmDelete" :disabled="deleting">
            <i v-if="deleting" class="fa-solid fa-spinner fa-spin"></i>
            {{ deleting ? 'Đang xóa...' : 'Đồng ý xóa' }}
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
              <input type="text" v-model="adminBookingForm.dia_chi_tra_xe" placeholder="Mặc định tại địa điểm nhận hoặc chỉ định..." />
            </div>
            <div class="form-group full">
              <label>Ghi chú / Yêu cầu đặc biệt</label>
              <input type="text" v-model="adminBookingForm.ly_do_thue" placeholder="VD: Khách yêu cầu giao xe sạch sẽ, sạc đầy pin 100%..." />
            </div>
          </div>

          <!-- Status & Payment Privileges -->
          <div class="section-title mt-4">
            <i class="fa-solid fa-shield-halved"></i> Thiết lập trạng thái & Thu tiền (Đặc quyền Admin)
          </div>

          <div class="privilege-grid">
            <div class="privilege-card">
              <label class="form-check-label">
                <input type="checkbox" v-model="adminBookingForm.autoApprove" />
                <span><strong>Duyệt lịch hẹn ngay</strong> (Trạng thái: Đã xác nhận)</span>
              </label>
              <small class="helper-text">Nếu không chọn, đơn sẽ ở trạng thái Chờ xác nhận.</small>
            </div>

            <div class="privilege-card">
              <label class="form-check-label">
                <input type="checkbox" v-model="adminBookingForm.isPaid" />
                <span><strong>Đã thu tiền trực tiếp</strong> (Trạng thái: Đã thanh toán)</span>
              </label>
              <input 
                v-if="adminBookingForm.isPaid" 
                type="text" 
                v-model="adminBookingForm.paymentNote" 
                placeholder="Ghi chú thu tiền (VD: Tiền mặt tại quầy / Chuyển khoản QR)"
                class="form-control-sm mt-2"
              />
            </div>
          </div>

          <!-- Price Summary Card -->
          <div class="rental-price-summary-card">
            <div class="summary-row">
              <span>Thời gian thuê tính toán:</span>
              <strong>{{ rentalSummary.days }} ngày</strong>
            </div>
            <div class="summary-row">
              <span>Đơn giá niêm yết xe:</span>
              <span>{{ formatCurrency(selectedCar.price_per_day) }} / ngày</span>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row total-row">
              <span>Tổng tiền thuê xe (Snapshot server):</span>
              <strong class="total-price-tag">{{ formatCurrency(rentalSummary.totalPrice) }}</strong>
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
            {{ submittingBooking ? 'Đang tạo đơn...' : 'Xác Nhận Đặt Xe Hộ' }}
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
import { resolveCarImage, handleCarImgError } from '../../../core/carImageHelper';

const toaster = createToaster({ position: "top-right" });

const cars = ref([]);
const categories = ref([]);
const loading = ref(true);
const fetchCarsError = ref(null);
const saving = ref(false);
const deleting = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');

const showModal = ref(false);
const editingCar = ref({});
const formErrors = ref({});
const galleryInput = ref('');

// Delete Confirmation
const showDeleteModal = ref(false);
const carToDelete = ref(null);

// Admin Booking for Customer State
const showBookingModal = ref(false);
const selectedCar = ref(null);
const customerMode = ref('existing'); // 'existing' | 'new'
const customerSearchQuery = ref('');
const customerSearchResults = ref([]);
const searchingCustomer = ref(false);
const selectedCustomer = ref(null);
let searchDebounceTimer = null;

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

const getCityLabel = (code) => {
  if (!code) return 'Toàn quốc';
  const c = code.toLowerCase();
  if (c === 'sg' || c.includes('hồ chí minh')) return 'TP. HCM';
  if (c === 'hn' || c.includes('hà nội')) return 'Hà Nội';
  if (c === 'dn' || c.includes('đà nẵng')) return 'Đà Nẵng';
  return code.toUpperCase();
};

const filteredCars = computed(() => {
  return cars.value.filter(car => {
    const matchSearch = car.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        (car.type && car.type.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchStatus = filterStatus.value === '' || car.status === filterStatus.value;
    return matchSearch && matchStatus;
  });
});

const fetchCars = async () => {
  loading.value = true;
  fetchCarsError.value = null;
  try {
    const res = await baseRequest.get('admin/cars/data');
    if (res.data && res.data.status && Array.isArray(res.data.data)) {
      cars.value = res.data.data;
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

const openModal = (car = null) => {
  formErrors.value = {};
  if (car) {
    editingCar.value = { 
      ...car,
      fuel_type: car.fuel_type || 'electric',
      is_active: car.is_active !== undefined ? Boolean(car.is_active) : true
    };
    galleryInput.value = Array.isArray(car.gallery) 
      ? car.gallery.join('\n') 
      : (typeof car.gallery === 'string' ? car.gallery : '');
  } else {
    editingCar.value = {
      name: '',
      price_per_day: 1200000,
      city: 'sg',
      type: 'SUV Điện',
      fuel_type: 'electric',
      category_id: categories.value[0]?.id || 1,
      image: '/assets/images/vf9.png',
      seats: null,
      battery_capacity: '',
      range_per_charge: '',
      transmission: 'Tự động',
      year_of_manufacture: 2024,
      description: '',
      is_active: true
    };
    galleryInput.value = '';
  }
  showModal.value = true;
};

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
      await fetchCars();
    } else {
      toaster.error(res.data?.message || "Không thể lưu thông tin xe.");
    }
  } catch (err) {
    toaster.error("Lỗi khi kết nối với máy chủ!");
  } finally {
    saving.value = false;
  }
};

const toggleStatus = async (car) => {
  try {
    const res = await baseRequest.post('admin/cars/status', { id: car.id });
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã đổi trạng thái xe!");
      // Update locally to avoid jump
      car.status = (car.status === 'available' ? 'maintenance' : 'available');
    } else {
      toaster.error(res.data?.message || "Không thể cập nhật trạng thái!");
    }
  } catch (err) {
    toaster.error("Lỗi hệ thống khi cập nhật trạng thái.");
  }
};

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
      toaster.success(res.data.message || "Đã xóa xe khỏi hệ thống!");
      showDeleteModal.value = false;
      carToDelete.value = null;
      await fetchCars();
    } else {
      toaster.error(res.data?.message || "Không thể xóa phương tiện này.");
    }
  } catch (err) {
    toaster.error("Lỗi khi xóa phương tiện.");
  } finally {
    deleting.value = false;
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

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
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  const q = customerSearchQuery.value.trim();
  if (!q) {
    customerSearchResults.value = [];
    return;
  }
  
  searchDebounceTimer = setTimeout(async () => {
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
      await fetchCars();
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
    showBookingModal.value = false;
  }
};

onMounted(() => {
  fetchCars();
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

/* Tools */
.table-tools {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  border-bottom: 1px solid var(--color-border);
  background: white;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 380px;
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
  padding: 10px 14px 10px 40px;
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

.form-select {
  padding: 10px 16px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-main);
  outline: none;
}

/* Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: var(--bg-surface-alt);
  padding: 14px 20px;
  text-align: left;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}

.data-table td {
  padding: 14px 20px;
  border-bottom: 1px solid var(--color-border);
  color: var(--color-text-main);
  vertical-align: middle;
}

.stt-badge {
  font-weight: 700;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.car-cell {
  display: flex;
  align-items: center;
  gap: 14px;
}

.car-thumb {
  width: 76px;
  height: 48px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
}

.car-details { display: flex; flex-direction: column; }
.car-name { font-weight: 700; color: var(--color-text-main); font-size: 0.95rem; }
.car-id { font-size: 0.72rem; color: var(--color-text-muted); font-family: monospace; }

.price-box {
  display: flex;
  flex-direction: column;
}

.price-text { font-weight: 800; color: var(--color-primary); font-size: 0.95rem; }
.price-box small { font-size: 0.75rem; color: var(--color-text-muted); }

.tag-outline {
  display: inline-block;
  padding: 4px 10px;
  border: 1px solid var(--color-border-dark);
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 600;
  background: white;
}

.city-badge {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-muted);
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.city-badge i { color: var(--color-primary); font-size: 0.8rem; }

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 100px;
  font-size: 0.75rem;
  font-weight: 700;
}

.status-ok { background: #DCFCE7; color: #15803D; }
.status-warn { background: #FEF3C7; color: #B45309; }

.actions-cell {
  display: flex;
  gap: 6px;
  justify-content: center;
}

.btn-icon {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: white;
  color: var(--color-text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-icon:hover {
  background: var(--bg-surface-alt);
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.btn-icon.text-danger:hover {
  color: #DC2626;
  border-color: #DC2626;
  background: #FEF2F2;
}

/* Modal */
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
  max-height: 90vh;
  overflow-y: auto;
  background: white;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
}

.modal-header {
  padding: 24px;
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

.modal-sub {
  color: var(--color-text-muted);
  font-size: 0.85rem;
  margin-top: 4px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: var(--color-text-muted);
  cursor: pointer;
  padding: 4px;
}

.modal-body { padding: 24px; }

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group.full { grid-column: span 2; }

.form-group label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 6px;
}

.form-group input, .form-group select {
  width: 100%;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  outline: none;
  font-size: 0.9rem;
  font-family: inherit;
  transition: border-color 0.2s;
}

.form-group input:focus, .form-group select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.helper-text {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin-top: 4px;
  display: block;
}

.required { color: #DC2626; }
.field-error { color: #DC2626; font-size: 0.78rem; margin-top: 4px; display: block; }

.img-preview-container {
  margin-top: 12px;
  padding: 12px;
  background: var(--bg-surface-alt);
  border-radius: var(--radius-sm);
  border: 1px dashed var(--color-border);
}

.preview-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-text-muted);
  display: block;
  margin-bottom: 8px;
}

.preview-box {
  width: 100%;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: var(--radius-sm);
  overflow: hidden;
}

.preview-box img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.modal-footer {
  padding: 16px 24px;
  background: var(--bg-surface-alt);
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

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

.highlight-box strong { font-size: 1rem; color: var(--color-text-main); }
.highlight-box span { font-size: 0.78rem; color: var(--color-text-muted); margin-top: 2px; }

.btn-danger {
  background: #DC2626;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: var(--radius-sm);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: #B91C1C;
}

.selected-car-badge {
  background: rgba(37, 99, 235, 0.08);
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  color: var(--color-primary);
  font-size: 0.88rem;
  border: 1px solid rgba(37, 99, 235, 0.2);
}

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
  border: 3px solid rgba(37, 99, 235, 0.2);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.admin-booking-modal {
  max-width: 720px;
}

.selected-car-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 18px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  margin-bottom: 20px;
}

.selected-car-card .car-thumb {
  width: 80px;
  height: 52px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: white;
  border: 1px solid var(--color-border);
}

.selected-car-card .car-meta h4 {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 2px 0 4px 0;
  color: var(--color-text-main);
}

.selected-car-card .car-rate {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.selected-car-card .car-rate strong {
  color: var(--color-primary);
  font-size: 0.95rem;
}

.city-tag {
  font-size: 0.78rem;
  background: white;
  padding: 2px 8px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
}

.section-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-main);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.customer-mode-switch {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
}

.mode-btn {
  flex: 1;
  padding: 9px 14px;
  border: 1px solid var(--color-border);
  background: white;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.mode-btn.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}

.search-input-wrapper {
  position: relative;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-icon i {
  position: absolute;
  left: 14px;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.input-with-icon input {
  padding-left: 38px;
  width: 100%;
}

.spinner-small {
  position: absolute;
  right: 14px;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(37, 99, 235, 0.2);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  max-height: 220px;
  overflow-y: auto;
  z-index: 50;
}

.customer-result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  transition: background 0.15s;
}

.customer-result-item:last-child { border-bottom: none; }
.customer-result-item:hover { background: var(--bg-surface-alt); }

.cust-avatar {
  width: 34px;
  height: 34px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.88rem;
  flex-shrink: 0;
}

.cust-info { flex: 1; }
.cust-name { font-weight: 700; font-size: 0.9rem; color: var(--color-text-main); }
.cust-contact {
  font-size: 0.78rem;
  color: var(--color-text-muted);
  display: flex;
  gap: 12px;
  margin-top: 2px;
}

.btn-select-customer {
  padding: 5px 12px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  color: var(--color-primary);
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
}

.selected-customer-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: rgba(16, 185, 129, 0.06);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: var(--radius-md);
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
  font-size: 0.88rem;
}

.privilege-card .form-check-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
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

@media (max-width: 768px) {
  .header-main { gap: 12px; }
  .page-title { font-size: 1.3rem; }
  .table-tools { flex-direction: column; }
  .search-input { max-width: 100%; }
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: span 1; }
}
</style>
