<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-car-rear"></i></div>
        <div>
          <h1 class="page-title">Danh Sách <span>Phương Tiện</span></h1>
          <p class="page-subtitle">Quản lý và cập nhật thông tin đội xe của bạn một cách nhanh chóng.</p>
        </div>
      </div>
      <button class="btn-primary" @click="openModal()"><i class="fa-solid fa-plus"></i> Thêm xe mới</button>
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
            <option value="available">Sẵn sàng</option>
            <option value="maintenance">Bảo trì</option>
          </select>
        </div>
      </div>

      <!-- Professional Table -->
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th width="50">#</th>
              <th>Thông tin xe</th>
              <th>Loại xe</th>
              <th>Giá thuê</th>
              <th>Khu vực</th>
              <th>Trạng thái</th>
              <th width="150" class="text-center">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(car, index) in filteredCars" :key="car.id">
              <td>{{ index + 1 }}</td>
              <td>
                <div class="car-cell">
                  <img :src="car.image" class="car-thumb" />
                  <div class="car-details">
                    <span class="car-name">{{ car.name }}</span>
                    <span class="car-id">ID: #CAR-{{ car.id }}</span>
                  </div>
                </div>
              </td>
              <td><span class="tag-outline">{{ car.category_name || car.type }}</span></td>
              <td>
                <span class="price-text">{{ formatCurrency(car.price_per_day) }}</span>
                <small>/ngày</small>
              </td>
              <td><i class="fa-solid fa-location-dot"></i> {{ car.city?.toUpperCase() }}</td>
              <td>
                <span class="status-badge" :class="car.status === 'available' ? 'status-ok' : 'status-warn'">
                  {{ car.status === 'available' ? 'Sẵn sàng' : 'Bảo trì' }}
                </span>
              </td>
              <td class="actions-cell">
                <button class="btn-icon" @click="openBookingModal(car)" title="Đặt ngay"><i class="fa-solid fa-calendar-plus"></i></button>
                <button class="btn-icon" @click="openModal(car)" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="btn-icon" @click="toggleStatus(car)" title="Đổi trạng thái"><i class="fa-solid fa-shuffle"></i></button>
                <button class="btn-icon text-danger" @click="deleteCar(car.id)" title="Xóa"><i class="fa-solid fa-trash-can"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Loading Overlay -->
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- Edit/Add Modal (Simplified for demo, but functional) -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content card">
        <div class="modal-header">
          <h3>{{ editingCar.id ? 'Cập nhật thông tin xe' : 'Thêm xe mới' }}</h3>
          <button class="btn-close" @click="showModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group">
              <label>Tên xe</label>
              <input type="text" v-model="editingCar.name" placeholder="VD: Mercedes C300" />
            </div>
            <div class="form-group">
              <label>Giá thuê (VNĐ/Ngày)</label>
              <input type="number" v-model="editingCar.price_per_day" />
            </div>
            <div class="form-group">
              <label>Thành phố</label>
              <input type="text" v-model="editingCar.city" />
            </div>
            <div class="form-group">
              <label>Loại xe</label>
              <select v-model="editingCar.category_id">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div class="form-group full">
              <label>Link ảnh xe</label>
              <input type="text" v-model="editingCar.image" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showModal = false">Hủy</button>
          <button class="btn-primary" @click="saveCar">Lưu thay đổi</button>
        </div>
      </div>
    </div>

    <!-- Booking Modal Added for Admin -->
    <div v-if="showBookingModal" class="modal-overlay" @click.self="showBookingModal = false">
      <div class="modal-content card animate-in">
        <button class="btn-close" @click="showBookingModal = false">&times;</button>
        <div class="modal-header">
          <h3>Tạo đơn đặt xe nhanh</h3>
          <p>Nhập thông tin bên dưới để Trợ lý AI xác nhận</p>
        </div>
        
        <div class="modal-body" v-if="selectedCar">
            <div class="form-grid">
                <div class="form-group">
                    <label>Ngày lấy xe</label>
                    <input type="datetime-local" v-model="bookingData.startDate" />
                </div>
                <div class="form-group">
                    <label>Ngày trả xe</label>
                    <input type="datetime-local" v-model="bookingData.endDate" />
                </div>
                <div class="form-group full">
                    <label>Địa điểm nhận xe</label>
                    <input type="text" v-model="bookingData.address" placeholder="Nhập địa chỉ..." />
                </div>
            </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="showBookingModal = false">Hủy</button>
          <button class="btn-primary" @click="confirmBooking">Xác Nhận & Gửi AI</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../../core/baseRequest';

const cars = ref([]);
const categories = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const filterStatus = ref('');
const showModal = ref(false);
const editingCar = ref({});

// Booking Handoff Logic
const showBookingModal = ref(false);
const selectedCar = ref(null);
const bookingData = ref({
    startDate: new Date().toISOString().slice(0, 16),
    endDate: new Date(new Date().getTime() + 86400000).toISOString().slice(0, 16),
    address: ''
});

const filteredCars = computed(() => {
  return cars.value.filter(car => {
    const matchSearch = car.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchStatus = filterStatus.value === '' || car.status === filterStatus.value;
    return matchSearch && matchStatus;
  });
});

const fetchCars = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/cars/data');
    if (res.data.status) cars.value = res.data.data;
  } catch (err) { console.error(err); }
  finally { loading.value = false; }
};

const fetchCategories = async () => {
  try {
    const res = await baseRequest.get('client/categories');
    if (res.data.status) categories.value = res.data.data;
  } catch (err) { console.error(err); }
};

const openModal = (car = null) => {
  if (car) {
    editingCar.value = { ...car };
  } else {
    editingCar.value = {
      name: '',
      price_per_day: 500000,
      city: 'sg',
      category_id: categories.value[0]?.id || 1,
      image: 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=800'
    };
  }
  showModal.value = true;
};

const saveCar = async () => {
  const url = editingCar.value.id ? 'admin/cars/update' : 'admin/cars/create';
  try {
    const res = await baseRequest.post(url, editingCar.value);
    if (res.data.status) {
      alert(res.data.message);
      showModal.value = false;
      fetchCars();
    }
  } catch (err) { alert("Có lỗi xảy ra"); }
};

const toggleStatus = async (car) => {
  try {
    const res = await baseRequest.post('admin/cars/status', { id: car.id });
    if (res.data.status) {
      alert(res.data.message);
      fetchCars();
    }
  } catch (err) { alert("Lỗi hệ thống"); }
};

const deleteCar = async (id) => {
  if (!confirm("Bạn có chắc chắn muốn xóa xe này?")) return;
  try {
    const res = await baseRequest.post('admin/cars/delete', { id });
    if (res.data.status) {
      alert(res.data.message);
      fetchCars();
    }
  } catch (err) { alert("Lỗi hệ thống"); }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const openBookingModal = (car) => {
    selectedCar.value = car;
    showBookingModal.value = true;
};

const isLocationValid = (car) => {
    if (!bookingData.value.address) return false;
    if (!car.city) return true;
    const query = bookingData.value.address.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const city = car.city.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return query.includes(city);
};

const confirmBooking = () => {
    if (!isLocationValid(selectedCar.value)) {
        alert(`Vui lòng nhập địa chỉ thuộc thành phố ${selectedCar.value.city}!`);
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
    
    showBookingModal.value = false;
};

onMounted(() => {
  fetchCars();
  fetchCategories();
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
  margin-bottom: 32px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon {
  width: 60px;
  height: 60px;
  background: white;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
}

.page-title span { color: var(--color-primary); }
.page-subtitle { color: #64748b; margin-top: 4px; }

.content-card {
  padding: 0;
  overflow: hidden;
}

/* Tools */
.table-tools {
  padding: 24px;
  display: flex;
  justify-content: space-between;
  gap: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.search-input {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-input i {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input input {
  width: 100%;
  padding: 12px 16px 12px 48px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  outline: none;
  transition: all 0.2s;
}

.search-input input:focus {
  border-color: var(--color-primary);
  background: white;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

/* Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f8fafc;
  padding: 16px 24px;
  text-align: left;
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-weight: 500;
}

.car-cell {
  display: flex;
  align-items: center;
  gap: 16px;
}

.car-thumb {
  width: 80px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
  background: #f1f5f9;
}

.car-details { display: flex; flex-direction: column; }
.car-name { font-weight: 700; color: #0f172a; }
.car-id { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }

.price-text { font-weight: 800; color: var(--color-primary); }

.tag-outline {
  padding: 4px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
}

.status-ok { background: #dcfce7; color: #166534; }
.status-warn { background: #fee2e2; color: #991b1b; }

.actions-cell {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f8fafc;
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.btn-icon.text-danger:hover {
  color: #ef4444;
  border-color: #ef4444;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  width: 100%;
  max-width: 600px;
  padding: 0;
}

.modal-header {
  padding: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #f1f5f9;
}

.modal-body { padding: 24px; }

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group.full { grid-column: span 2; }

.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: 8px;
}

.form-group input, .form-group select {
  width: 100%;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  outline: none;
}

.modal-footer {
  padding: 20px 24px;
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

@media (max-width: 768px) {
  .header-main { gap: 12px; }
  .page-title { font-size: 1.25rem; }
  .table-tools { flex-direction: column; }
}
</style>
