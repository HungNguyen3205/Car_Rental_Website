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
                    :src="car.image || '/assets/images/vf9.png'" 
                    class="car-thumb" 
                    @error="$event.target.src = '/assets/images/vf9.png'"
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

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-box">
          <i class="fa-solid fa-car-side"></i>
          <h4>Không tìm thấy phương tiện phù hợp</h4>
          <p>Thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc trạng thái xe.</p>
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

            <div class="form-group full">
              <label>Đường dẫn hình ảnh xe (URL) <span class="required">*</span></label>
              <input type="text" v-model="editingCar.image" placeholder="https://... hoặc /assets/images/vf9.png" />
              
              <!-- Image Preview Box -->
              <div class="img-preview-container">
                <span class="preview-label">Xem trước hình ảnh xe:</span>
                <div class="preview-box">
                  <img 
                    :src="editingCar.image || '/assets/images/vf9.png'" 
                    @error="$event.target.src = '/assets/images/vf9.png'" 
                    alt="Preview" 
                  />
                </div>
              </div>
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

    <!-- Booking Modal Added for Admin -->
    <div v-if="showBookingModal" class="modal-overlay" @click.self="showBookingModal = false">
      <div class="modal-content card animate-in">
        <div class="modal-header">
          <div>
            <h3>Tạo đơn đặt xe nhanh</h3>
            <p class="modal-sub">Đơn hàng sẽ được chuyển tới trợ lý AI để tạo lịch hẹn cho khách</p>
          </div>
          <button class="btn-close" @click="showBookingModal = false" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div class="modal-body" v-if="selectedCar">
            <div class="selected-car-badge mb-4">
              <i class="fa-solid fa-car"></i> Đang chọn xe: <strong>{{ selectedCar.name }}</strong> ({{ formatCurrency(selectedCar.price_per_day) }}/ngày)
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Thời điểm nhận xe <span class="required">*</span></label>
                    <input type="datetime-local" v-model="bookingData.startDate" />
                </div>
                <div class="form-group">
                    <label>Thời điểm trả xe <span class="required">*</span></label>
                    <input type="datetime-local" v-model="bookingData.endDate" />
                </div>
                <div class="form-group full">
                    <label>Địa điểm nhận xe <span class="required">*</span></label>
                    <input type="text" v-model="bookingData.address" placeholder="Nhập địa chỉ giao xe chi tiết..." />
                    <span v-if="bookingError" class="field-error">{{ bookingError }}</span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="showBookingModal = false">Hủy</button>
          <button class="btn-primary" @click="confirmBooking">Xác Nhận & Chuyển AI</button>
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

const cars = ref([]);
const categories = ref([]);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');

const showModal = ref(false);
const editingCar = ref({});
const formErrors = ref({});

// Delete Confirmation
const showDeleteModal = ref(false);
const carToDelete = ref(null);

// Booking Handoff Logic
const showBookingModal = ref(false);
const selectedCar = ref(null);
const bookingError = ref('');
const bookingData = ref({
    startDate: new Date().toISOString().slice(0, 16),
    endDate: new Date(new Date().getTime() + 86400000).toISOString().slice(0, 16),
    address: ''
});

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
  try {
    const res = await baseRequest.get('admin/cars/data');
    if (res.data && res.data.status) {
      cars.value = res.data.data;
    }
  } catch (err) {
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
    editingCar.value = { ...car };
  } else {
    editingCar.value = {
      name: '',
      price_per_day: 1200000,
      city: 'sg',
      type: 'SUV Điện',
      category_id: categories.value[0]?.id || 1,
      image: '/assets/images/vf9.png'
    };
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
    bookingError.value = '';
    showBookingModal.value = true;
};

const isLocationValid = (car) => {
    if (!bookingData.value.address) return false;
    if (!car.city || car.city === 'all') return true;
    const query = bookingData.value.address.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const city = car.city.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return query.includes(city);
};

const confirmBooking = () => {
    if (!bookingData.value.address.trim()) {
        bookingError.value = "Vui lòng nhập địa chỉ nhận xe.";
        return;
    }

    if (!isLocationValid(selectedCar.value)) {
        bookingError.value = `Địa chỉ phải thuộc khu vực ${getCityLabel(selectedCar.value.city)}!`;
        return;
    }
    
    window.dispatchEvent(new CustomEvent('trigger-ai-booking', {
        detail: {
            car: selectedCar.value,
            startDate: bookingData.value.startDate,
            endDate: bookingData.value.endDate,
            address: bookingData.value.address
        }
    }));
    
    toaster.success("Đã kích hoạt tạo đơn! Vui lòng theo dõi qua hộp chat AI.");
    showBookingModal.value = false;
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

@media (max-width: 768px) {
  .header-main { gap: 12px; }
  .page-title { font-size: 1.3rem; }
  .table-tools { flex-direction: column; }
  .search-input { max-width: 100%; }
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: span 1; }
}
</style>
