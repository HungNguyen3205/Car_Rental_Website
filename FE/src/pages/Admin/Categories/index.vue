<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-tags"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Danh Mục Phương Tiện</span></h1>
          <p class="page-subtitle">Phân loại phân khúc và hạng xe phục vụ đặt xe, phân quyền quản lý và bảo vệ toàn vẹn dữ liệu phương tiện.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="fetchCategories(1)" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
        <button class="btn-primary" @click="openModal(null)">
          <i class="fa-solid fa-plus"></i>
          <span>Thêm Danh Mục Mới</span>
        </button>
      </div>
    </header>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-layer-group"></i></div>
        <div class="stat-info">
          <span class="stat-label">Tổng số danh mục</span>
          <strong class="stat-value">{{ stats.total }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
          <span class="stat-label">Đang hoạt động</span>
          <strong class="stat-value">{{ stats.active_count }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-pause"></i></div>
        <div class="stat-info">
          <span class="stat-label">Ngừng sử dụng</span>
          <strong class="stat-value">{{ stats.inactive_count }}</strong>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-car"></i></div>
        <div class="stat-info">
          <span class="stat-label">Tổng xe đang phân bổ</span>
          <strong class="stat-value">{{ stats.total_cars_assigned }}</strong>
        </div>
      </div>
    </div>

    <!-- Explanatory Guideline Banner -->
    <div class="guideline-banner card">
      <div class="banner-icon"><i class="fa-solid fa-circle-info"></i></div>
      <div class="banner-text">
        <strong>Chuẩn hóa hệ thống phân loại phương tiện:</strong>
        <p>
          Hệ thống tách bạch rõ 3 tiêu chí:
          <span class="pill-chip"><strong>Hạng xe / Phân khúc:</strong> Quản lý tại trang này (Xe phổ thông, Xe sang, Gia đình...)</span>
          <span class="pill-chip"><strong>Kiểu dáng thân xe:</strong> Thuộc tính cố định (SUV, Sedan, MPV, Hatchback...)</span>
          <span class="pill-chip"><strong>Loại nhiên liệu:</strong> Xe điện (EV), Xăng, Diesel, Hybrid (giúp xe điện như VF8 vẫn được định danh chính xác là SUV mà không bị loại trừ).</span>
        </p>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card card">
      <!-- Toolbar: Search & Filter -->
      <div class="table-tools">
        <div class="tools-left">
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo tên danh mục hoặc mô tả..."
              @input="onSearchInput"
            />
            <button v-if="searchQuery" class="clear-btn" @click="clearSearch" aria-label="Xóa từ khóa">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <select v-model="filterStatus" class="form-select filter-status" @change="fetchCategories(1)">
            <option value="all">Tất cả trạng thái</option>
            <option value="active">Đang sử dụng</option>
            <option value="inactive">Ngừng sử dụng</option>
          </select>

          <button 
            v-if="searchQuery || filterStatus !== 'all'" 
            class="btn-reset-filters" 
            @click="resetFilters"
            title="Đặt lại bộ lọc"
          >
            <i class="fa-solid fa-filter-circle-xmark"></i>
            <span>Xóa lọc</span>
          </button>
        </div>

        <div class="tools-right">
          <span class="results-count">Hiển thị <strong>{{ categories.length }}</strong> / {{ pagination.total }} danh mục</span>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="table-responsive">
        <table class="premium-table" v-if="categories.length > 0">
          <thead>
            <tr>
              <th width="100">Mã ID</th>
              <th>Tên phân hạng / Danh mục</th>
              <th>Mô tả đặc điểm</th>
              <th width="160" class="text-center">Số xe đang gán</th>
              <th width="150" class="text-center">Trạng thái</th>
              <th width="140">Ngày tạo</th>
              <th width="140" class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in categories" :key="cat.id" :class="{ 'row-inactive': !cat.is_active }">
              <td>
                <span class="id-tag">#CAT-{{ cat.id }}</span>
              </td>
              <td>
                <div class="cat-title-cell">
                  <strong class="cat-name">{{ cat.name }}</strong>
                  <span v-if="!cat.is_active" class="mini-inactive-badge">Đã ngừng dùng</span>
                </div>
              </td>
              <td>
                <span class="cat-desc">{{ cat.description || 'Chưa có mô tả đặc điểm phân khúc này.' }}</span>
              </td>
              <td class="text-center">
                <span 
                  class="count-badge" 
                  :class="{ 'has-cars': cat.cars_count > 0 }"
                  :title="cat.cars_count > 0 ? `Đang có ${cat.cars_count} xe liên kết trong kho` : 'Chưa có xe nào gán vào danh mục này'"
                >
                  <i class="fa-solid fa-car"></i> 
                  <strong>{{ cat.cars_count || 0 }}</strong> xe
                </span>
              </td>
              <td class="text-center">
                <button 
                  class="status-toggle-btn" 
                  :class="cat.is_active ? 'active' : 'inactive'"
                  @click="handleToggleStatus(cat)"
                  :title="cat.is_active ? 'Bấm để ngừng sử dụng danh mục này' : 'Bấm để kích hoạt lại danh mục này'"
                >
                  <i :class="cat.is_active ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-pause'"></i>
                  <span>{{ cat.is_active ? 'Đang dùng' : 'Ngừng dùng' }}</span>
                </button>
              </td>
              <td>{{ formatDate(cat.created_at) }}</td>
              <td class="text-center">
                <div class="action-buttons-inline">
                  <button 
                    class="btn-icon-btn edit" 
                    @click="openModal(cat)" 
                    title="Chỉnh sửa thông tin"
                  >
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button 
                    class="btn-icon-btn delete" 
                    @click="handleDeleteClick(cat)" 
                    :class="{ 'disabled-warning': cat.cars_count > 0 }"
                    :title="cat.cars_count > 0 ? `Không thể xóa (Đang có ${cat.cars_count} xe liên kết). Bấm để xem hướng dẫn.` : 'Xóa vĩnh viễn danh mục này'"
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
          <i class="fa-solid fa-tags"></i>
          <h4>Không tìm thấy danh mục phù hợp</h4>
          <p v-if="searchQuery || filterStatus !== 'all'">Thử điều chỉnh từ khóa tìm kiếm hoặc bỏ chọn bộ lọc trạng thái.</p>
          <p v-else>Chưa có danh mục xe nào. Hãy tạo phân hạng xe đầu tiên để phân loại đội xe.</p>
          <button v-if="searchQuery || filterStatus !== 'all'" class="btn-outline mt-3" @click="resetFilters">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc
          </button>
          <button v-else class="btn-primary mt-3" @click="openModal(null)">
            <i class="fa-solid fa-plus"></i> Tạo danh mục đầu tiên
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-footer" v-if="pagination.last_page > 1">
        <div class="pagination-info">
          Trang <strong>{{ pagination.current_page }}</strong> / {{ pagination.last_page }} (Tổng cộng {{ pagination.total }} danh mục)
        </div>
        <div class="pagination-controls">
          <button 
            class="page-nav-btn" 
            :disabled="pagination.current_page <= 1"
            @click="fetchCategories(pagination.current_page - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i> Trước
          </button>
          <button 
            v-for="p in paginationPages" 
            :key="p" 
            class="page-num-btn"
            :class="{ active: p === pagination.current_page }"
            @click="fetchCategories(p)"
          >
            {{ p }}
          </button>
          <button 
            class="page-nav-btn" 
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchCategories(pagination.current_page + 1)"
          >
            Sau <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal 1: Add / Edit Category -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content card category-modal animate-in">
        <div class="modal-header">
          <div class="modal-icon-badge">
            <i class="fa-solid fa-tags"></i>
          </div>
          <div>
            <h3>{{ editingCategory ? 'Chỉnh Sửa Danh Mục' : 'Thêm Mới Danh Mục Xe' }}</h3>
            <p class="modal-sub">{{ editingCategory ? `Cập nhật thông tin phân hạng #${editingCategory.id}` : 'Thiết lập tên và mô tả phân khúc không trùng lặp' }}</p>
          </div>
          <button class="btn-close-modal" @click="closeModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="saveCategory" class="modal-body">
          <div class="form-group" :class="{ 'has-error': formErrors.name }">
            <label for="modal_cat_name">Tên phân hạng / Danh mục xe <span class="required">*</span></label>
            <input 
              id="modal_cat_name" 
              type="text" 
              v-model="catForm.name" 
              placeholder="VD: Xe Gia Đình Tiện Nghi, Xe Sang Thương Gia..." 
              required 
            />
            <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
          </div>

          <div class="form-group mt-3" :class="{ 'has-error': formErrors.description }">
            <label for="modal_cat_desc">Mô tả đặc điểm & tiêu chuẩn phân khúc</label>
            <textarea 
              id="modal_cat_desc" 
              rows="3" 
              v-model="catForm.description" 
              placeholder="Mô tả nhóm đối tượng khách hàng, tiện ích nổi bật hoặc phong cách..."
            ></textarea>
            <span v-if="formErrors.description" class="field-error">{{ formErrors.description }}</span>
          </div>

          <div class="form-group mt-3">
            <label class="checkbox-label">
              <input type="checkbox" v-model="catForm.is_active" />
              <span><strong>Kích hoạt sử dụng:</strong> Cho phép hiển thị danh mục này trên bộ lọc khách hàng và trang biên tập xe.</span>
            </label>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn-outline" @click="closeModal" :disabled="saving">Hủy</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ saving ? 'Đang lưu...' : (editingCategory ? 'Lưu thay đổi' : 'Thêm mới danh mục') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal 2: In-Use Protection & Deactivate Suggestion Notice -->
    <div v-if="inUseWarningCat" class="modal-overlay" @click.self="inUseWarningCat = null">
      <div class="modal-content card in-use-modal animate-in">
        <div class="modal-header warning-header">
          <div class="modal-icon-badge warning">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h3>Bảo Vệ Toàn Vẹn Dữ Liệu</h3>
            <p class="modal-sub">Không thể xóa danh mục đang có phương tiện liên kết</p>
          </div>
          <button class="btn-close-modal" @click="inUseWarningCat = null"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body warning-body">
          <div class="warning-callout">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Danh mục "{{ inUseWarningCat.name }}" đang liên kết với {{ inUseWarningCat.cars_count }} phương tiện trong kho xe.</strong>
              <p>Việc xóa trực tiếp danh mục sẽ làm mất phân hạng xe và ảnh hưởng đến tính toàn vẹn của các đơn đặt xe đã phát sinh.</p>
            </div>
          </div>

          <div class="suggestion-card">
            <h4><i class="fa-solid fa-lightbulb"></i> Giải pháp an toàn khuyến nghị:</h4>
            <ul>
              <li><strong>Ngừng sử dụng:</strong> Chuyển danh mục sang trạng thái "Ngừng sử dụng" để ẩn khỏi bộ lọc khách thuê mới, trong khi các xe hiện có vẫn hoạt động bình thường.</li>
              <li><strong>Chuyển danh mục:</strong> Vào trang <em>Quản lý xe</em> để chuyển các xe sang danh mục khác trước khi thực hiện xóa.</li>
            </ul>
          </div>

          <div class="modal-footer mt-4 flex-between">
            <button type="button" class="btn-outline" @click="inUseWarningCat = null">Đóng</button>
            <button 
              type="button" 
              class="btn-warning-action" 
              @click="deactivateFromWarning(inUseWarningCat)"
              :disabled="saving"
            >
              <i class="fa-solid fa-pause"></i>
              <span>Chuyển sang "Ngừng sử dụng" ngay</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

// States
const loading = ref(false);
const saving = ref(false);
const categories = ref([]);
const searchQuery = ref('');
const filterStatus = ref('all');
let searchDebounce = null;

const stats = reactive({
  total: 0,
  active_count: 0,
  inactive_count: 0,
  total_cars_assigned: 0,
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

// Modal states
const showModal = ref(false);
const editingCategory = ref(null);
const inUseWarningCat = ref(null);
const formErrors = reactive({
  name: '',
  description: '',
});

const catForm = reactive({
  name: '',
  description: '',
  is_active: true,
});

// Computed pages for pagination
const paginationPages = computed(() => {
  const pages = [];
  const max = pagination.last_page;
  const cur = pagination.current_page;
  const start = Math.max(1, cur - 2);
  const end = Math.min(max, cur + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Fetch categories from API
const fetchCategories = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page: page,
      per_page: pagination.per_page,
      search: searchQuery.value.trim() || undefined,
      status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
      paginate: true,
    };

    const res = await baseRequest.get('admin/car-categories', { params });
    if (res.data && res.data.status) {
      categories.value = res.data.data || [];
      if (res.data.meta) {
        pagination.current_page = res.data.meta.current_page;
        pagination.last_page = res.data.meta.last_page;
        pagination.per_page = res.data.meta.per_page;
        pagination.total = res.data.meta.total;
      } else {
        pagination.total = categories.value.length;
      }

      if (res.data.stats) {
        Object.assign(stats, res.data.stats);
      }
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách danh mục xe!");
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    fetchCategories(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchCategories(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  filterStatus.value = 'all';
  fetchCategories(1);
};

// Modal handlers
const openModal = (cat = null) => {
  editingCategory.value = cat;
  formErrors.name = '';
  formErrors.description = '';

  if (cat) {
    catForm.name = cat.name;
    catForm.description = cat.description || '';
    catForm.is_active = cat.is_active !== undefined ? Boolean(cat.is_active) : true;
  } else {
    catForm.name = '';
    catForm.description = '';
    catForm.is_active = true;
  }

  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingCategory.value = null;
};

// Save Category (Create / Update)
const saveCategory = async () => {
  formErrors.name = '';
  formErrors.description = '';
  saving.value = true;

  try {
    const payload = {
      name: catForm.name.trim(),
      description: catForm.description.trim(),
      is_active: catForm.is_active,
    };

    if (editingCategory.value) {
      const res = await baseRequest.put(`admin/car-categories/${editingCategory.value.id}`, payload);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Cập nhật danh mục thành công!");
        closeModal();
        fetchCategories(pagination.current_page);
      }
    } else {
      const res = await baseRequest.post('admin/car-categories', payload);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Thêm mới danh mục xe thành công!");
        closeModal();
        fetchCategories(1);
      }
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const errors = err.response.data.errors;
      if (errors.name) formErrors.name = errors.name[0];
      if (errors.description) formErrors.description = errors.description[0];
      toaster.error(formErrors.name || "Dữ liệu nhập chưa hợp lệ!");
    } else {
      toaster.error(err.response?.data?.message || "Có lỗi xảy ra khi lưu danh mục!");
    }
  } finally {
    saving.value = false;
  }
};

// Toggle Active / Deactivate
const handleToggleStatus = async (cat) => {
  try {
    const res = await baseRequest.patch(`admin/car-categories/${cat.id}/status`);
    if (res.data && res.data.status) {
      cat.is_active = !cat.is_active;
      toaster.success(res.data.message || "Cập nhật trạng thái thành công!");
      // Cập nhật thống kê nhanh
      if (cat.is_active) {
        stats.active_count++;
        stats.inactive_count = Math.max(0, stats.inactive_count - 1);
      } else {
        stats.inactive_count++;
        stats.active_count = Math.max(0, stats.active_count - 1);
      }
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể cập nhật trạng thái danh mục!");
  }
};

// Delete Handler with Protection Warning
const handleDeleteClick = (cat) => {
  if (cat.cars_count > 0) {
    inUseWarningCat.value = cat;
    return;
  }

  if (!confirm(`Bạn có chắc chắn muốn xóa vĩnh viễn danh mục "${cat.name}"? Thao tác này không thể hoàn tác.`)) {
    return;
  }

  executeDelete(cat);
};

const executeDelete = async (cat) => {
  try {
    const res = await baseRequest.delete(`admin/car-categories/${cat.id}`);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa danh mục xe thành công!");
      fetchCategories(pagination.current_page);
    }
  } catch (err) {
    if (err.response?.status === 422) {
      inUseWarningCat.value = cat;
    } else {
      toaster.error(err.response?.data?.message || "Lỗi khi xóa danh mục!");
    }
  }
};

const deactivateFromWarning = async (cat) => {
  if (!cat) return;
  saving.value = true;
  try {
    const res = await baseRequest.put(`admin/car-categories/${cat.id}`, {
      name: cat.name,
      description: cat.description,
      is_active: false,
    });
    if (res.data && res.data.status) {
      toaster.success(`Đã chuyển danh mục "${cat.name}" sang Ngừng sử dụng thành công!`);
      inUseWarningCat.value = null;
      fetchCategories(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || "Không thể đổi trạng thái danh mục!");
  } finally {
    saving.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

onMounted(() => {
  fetchCategories(1);
});
</script>

<style scoped>
.admin-page {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #10B981, #059669);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text, #1E293B);
  margin: 0;
}

.page-title span {
  color: #10B981;
}

.page-subtitle {
  font-size: 13px;
  color: var(--color-text-muted, #64748B);
  margin: 4px 0 0 0;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--color-bg-card, #FFFFFF);
  border: 1px solid var(--color-border, #E2E8F0);
  border-radius: 10px;
  color: var(--color-text, #334155);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  border-color: #10B981;
  color: #10B981;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #10B981, #059669);
  border: none;
  border-radius: 10px;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
}

.btn-outline {
  padding: 9px 16px;
  background: transparent;
  border: 1px solid var(--color-border, #CBD5E1);
  border-radius: 9px;
  color: var(--color-text, #475569);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline:hover {
  background: var(--color-bg-hover, #F1F5F9);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: var(--color-bg-card, #FFFFFF);
  border: 1px solid var(--color-border, #E2E8F0);
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.stat-icon.primary { background: #ECFDF5; color: #10B981; }
.stat-icon.success { background: #F0FDF4; color: #16A34A; }
.stat-icon.warning { background: #FFFBEB; color: #D97706; }
.stat-icon.info { background: #EFF6FF; color: #2563EB; }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 12px;
  color: var(--color-text-muted, #64748B);
  font-weight: 500;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text, #1E293B);
  line-height: 1.2;
}

/* Guideline Banner */
.guideline-banner {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 18px;
  border-radius: 12px;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  margin-bottom: 20px;
}

.banner-icon {
  font-size: 18px;
  color: #3B82F6;
  margin-top: 2px;
}

.banner-text {
  font-size: 13px;
  line-height: 1.5;
  color: #334155;
}

.banner-text p {
  margin: 4px 0 0 0;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.pill-chip {
  display: inline-block;
  background: #FFFFFF;
  border: 1px solid #E2E8F0;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 12px;
}

/* Content Card */
.content-card {
  background: var(--color-bg-card, #FFFFFF);
  border: 1px solid var(--color-border, #E2E8F0);
  border-radius: 16px;
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
  overflow: hidden;
}

/* Table Tools */
.table-tools {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border, #E2E8F0);
  flex-wrap: wrap;
  gap: 12px;
}

.tools-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input-wrapper i {
  position: absolute;
  left: 12px;
  color: var(--color-text-muted, #94A3B8);
  font-size: 14px;
}

.search-input-wrapper input {
  padding: 8px 32px 8px 34px;
  border: 1px solid var(--color-border, #CBD5E1);
  border-radius: 9px;
  font-size: 13px;
  width: 280px;
  background: var(--color-bg, #FFFFFF);
  color: var(--color-text, #1E293B);
}

.search-input-wrapper input:focus {
  outline: none;
  border-color: #10B981;
}

.clear-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
}

.form-select {
  padding: 8px 14px;
  border: 1px solid var(--color-border, #CBD5E1);
  border-radius: 9px;
  font-size: 13px;
  background: var(--color-bg, #FFFFFF);
  color: var(--color-text, #1E293B);
  cursor: pointer;
}

.btn-reset-filters {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #F1F5F9;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
  cursor: pointer;
}

.results-count {
  font-size: 13px;
  color: var(--color-text-muted, #64748B);
}

/* Premium Table */
.table-responsive {
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.premium-table th {
  padding: 12px 18px;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-muted, #64748B);
  border-bottom: 1px solid var(--color-border, #E2E8F0);
  background: var(--color-bg-subtle, #F8FAFC);
  white-space: nowrap;
}

.premium-table td {
  padding: 14px 18px;
  border-bottom: 1px solid var(--color-border, #E2E8F0);
  color: var(--color-text, #1E293B);
  vertical-align: middle;
}

.premium-table tr:hover td {
  background: var(--color-bg-hover, #F8FAFC);
}

.row-inactive td {
  opacity: 0.75;
  background: #FFFDF7;
}

.id-tag {
  font-family: monospace;
  font-size: 11px;
  background: #F1F5F9;
  color: #475569;
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
}

.cat-title-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cat-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text, #0F172A);
}

.mini-inactive-badge {
  font-size: 10px;
  background: #FEF3C7;
  color: #D97706;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 600;
}

.cat-desc {
  color: var(--color-text-muted, #64748B);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  background: #F1F5F9;
  color: #475569;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.count-badge.has-cars {
  background: #ECFDF5;
  color: #059669;
  border: 1px solid #A7F3D0;
}

/* Status toggle button */
.status-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.status-toggle-btn.active {
  background: #F0FDF4;
  color: #16A34A;
  border-color: #BBF7D0;
}

.status-toggle-btn.active:hover {
  background: #DCFCE7;
}

.status-toggle-btn.inactive {
  background: #FEF3C7;
  color: #D97706;
  border-color: #FDE68A;
}

.status-toggle-btn.inactive:hover {
  background: #FDE68A;
}

/* Action buttons */
.action-buttons-inline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-icon-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--color-border, #E2E8F0);
  background: var(--color-bg, #FFFFFF);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text, #475569);
  cursor: pointer;
  transition: all 0.15s;
}

.btn-icon-btn.edit:hover {
  border-color: #10B981;
  color: #10B981;
  background: #ECFDF5;
}

.btn-icon-btn.delete:hover {
  border-color: #EF4444;
  color: #EF4444;
  background: #FEF2F2;
}

.btn-icon-btn.disabled-warning {
  border-color: #FCD34D;
  color: #D97706;
  background: #FFFBEB;
}

/* Pagination */
.pagination-footer {
  padding: 14px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid var(--color-border, #E2E8F0);
  flex-wrap: wrap;
  gap: 12px;
}

.pagination-info {
  font-size: 13px;
  color: var(--color-text-muted, #64748B);
}

.pagination-controls {
  display: flex;
  gap: 6px;
  align-items: center;
}

.page-nav-btn, .page-num-btn {
  padding: 6px 12px;
  border: 1px solid var(--color-border, #CBD5E1);
  background: var(--color-bg, #FFFFFF);
  border-radius: 7px;
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text, #334155);
  cursor: pointer;
}

.page-num-btn.active {
  background: #10B981;
  color: #FFFFFF;
  border-color: #10B981;
  font-weight: 700;
}

.page-nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Empty State */
.empty-state-box {
  padding: 48px 24px;
  text-align: center;
  color: var(--color-text-muted, #64748B);
}

.empty-state-box i {
  font-size: 40px;
  color: #CBD5E1;
  margin-bottom: 12px;
}

.empty-state-box h4 {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text, #1E293B);
  margin: 0 0 6px 0;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.category-modal {
  width: 100%;
  max-width: 500px;
  background: var(--color-bg-card, #FFFFFF);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.in-use-modal {
  width: 100%;
  max-width: 540px;
  background: var(--color-bg-card, #FFFFFF);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
  position: relative;
}

.modal-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #ECFDF5;
  color: #10B981;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.modal-icon-badge.warning {
  background: #FEF2F2;
  color: #EF4444;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
  color: var(--color-text, #0F172A);
}

.modal-sub {
  font-size: 12px;
  color: var(--color-text-muted, #64748B);
  margin: 2px 0 0 0;
}

.btn-close-modal {
  position: absolute;
  top: 0;
  right: 0;
  background: none;
  border: none;
  font-size: 18px;
  color: #94A3B8;
  cursor: pointer;
}

.modal-body {
  display: flex;
  flex-direction: column;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text, #334155);
  margin-bottom: 6px;
}

.form-group input[type="text"],
.form-group textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--color-border, #CBD5E1);
  border-radius: 9px;
  font-size: 13px;
  background: var(--color-bg, #FFFFFF);
  color: var(--color-text, #1E293B);
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #10B981;
}

.form-group.has-error input,
.form-group.has-error textarea {
  border-color: #EF4444;
}

.field-error {
  display: block;
  font-size: 12px;
  color: #EF4444;
  margin-top: 4px;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  cursor: pointer;
  color: #334155;
  user-select: none;
}

.checkbox-label input {
  margin-top: 3px;
  cursor: pointer;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* In-use warning modal details */
.warning-callout {
  display: flex;
  gap: 12px;
  padding: 14px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  border-radius: 10px;
  color: #991B1B;
  font-size: 13px;
  margin-bottom: 16px;
}

.warning-callout i {
  font-size: 20px;
  color: #EF4444;
  margin-top: 2px;
}

.warning-callout p {
  margin: 4px 0 0 0;
  color: #B91C1C;
}

.suggestion-card {
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  padding: 14px 16px;
  font-size: 13px;
  color: #334155;
}

.suggestion-card h4 {
  margin: 0 0 8px 0;
  font-size: 13px;
  font-weight: 600;
  color: #0F172A;
  display: flex;
  align-items: center;
  gap: 6px;
}

.suggestion-card ul {
  margin: 0;
  padding-left: 18px;
  line-height: 1.5;
}

.btn-warning-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #D97706;
  border: none;
  border-radius: 9px;
  color: #FFFFFF;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-warning-action:hover:not(:disabled) {
  background: #B45309;
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.required {
  color: #EF4444;
}

.animate-in {
  animation: fadeInModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInModal {
  from { opacity: 0; transform: scale(0.96) translateY(8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
