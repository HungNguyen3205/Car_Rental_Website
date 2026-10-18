<template>
  <div class="admin-page">
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-sliders"></i></div>
        <div>
          <h1 class="page-title">Cấu Hình <span>Hệ Thống & Danh Mục</span></h1>
          <p class="page-subtitle">Quản lý tham số thương hiệu, tài khoản chuyển khoản VietQR, chính sách thuê và danh mục phương tiện.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-refresh" @click="handleRefreshAll" :disabled="loading">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới & Xóa cache</span>
        </button>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="tabs-nav">
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'general' }" 
        @click="activeTab = 'general'"
      >
        <i class="fa-solid fa-building"></i> Thương hiệu & Liên hệ
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'bank' }" 
        @click="activeTab = 'bank'"
      >
        <i class="fa-solid fa-qrcode"></i> Ngân hàng & VietQR
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'policy' }" 
        @click="activeTab = 'policy'"
      >
        <i class="fa-solid fa-file-contract"></i> Chính sách thuê xe
      </button>
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ 'active': activeTab === 'categories' }" 
        @click="activeTab = 'categories'"
      >
        <i class="fa-solid fa-tags"></i> Danh mục xe ({{ categories.length }})
      </button>
    </div>

    <!-- Tab 1: General & Contact -->
    <div v-if="activeTab === 'general'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <h3><i class="fa-solid fa-circle-info"></i> Thông Tin Thương Hiệu & Kênh Liên Hệ</h3>
          <p>Các thông số hiển thị công khai trên Header, Footer và trang Liên hệ.</p>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-grid two-cols">
            <div class="form-group">
              <label for="site_name">Tên thương hiệu / Nền tảng <span class="required">*</span></label>
              <input id="site_name" type="text" v-model="form.site_name" required placeholder="DriveRent" />
            </div>

            <div class="form-group">
              <label for="hotline">Hotline tổng đài tư vấn <span class="required">*</span></label>
              <input id="hotline" type="text" v-model="form.hotline" required placeholder="1900 1234" />
            </div>

            <div class="form-group">
              <label for="support_email">Email hỗ trợ khách hàng <span class="required">*</span></label>
              <input id="support_email" type="email" v-model="form.support_email" required placeholder="support@driverentals.com" />
            </div>

            <div class="form-group">
              <label for="company_address">Địa chỉ trụ sở chính <span class="required">*</span></label>
              <input id="company_address" type="text" v-model="form.company_address" required placeholder="Tòa nhà Bitexco, Q.1, TP. HCM" />
            </div>
          </div>

          <div class="form-group mt-3">
            <label for="service_locations_text">Các trạm & Địa điểm phục vụ (Mỗi địa điểm cách nhau bằng dấu phẩy)</label>
            <input 
              id="service_locations_text" 
              type="text" 
              v-model="locationsRaw" 
              placeholder="Hà Nội, TP. Hồ Chí Minh, Đà Nẵng" 
            />
            <small class="field-hint">Ví dụ: Hà Nội, TP. Hồ Chí Minh, Đà Nẵng, Nha Trang</small>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu thông tin thương hiệu' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tab 2: Bank & VietQR -->
    <div v-if="activeTab === 'bank'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <h3><i class="fa-solid fa-building-columns"></i> Tài Khoản Ngân Hàng & Thanh Toán VietQR</h3>
          <p>Cấu hình tài khoản ngân hàng thụ hưởng tiền cọc và tiền thuê xe qua mã VietQR.</p>
        </div>

        <!-- Audit & Snapshot Warning Notice -->
        <div class="alert-notice warning">
          <i class="fa-solid fa-shield-halved"></i>
          <div>
            <strong>Lưu ý nghiệp vụ kiểm toán (Audit Log):</strong>
            <p>Mọi thay đổi thông tin ngân hàng sẽ được ghi nhật ký kiểm toán với đầy đủ thông tin Admin và thời điểm thay đổi. Thay đổi này không sửa đổi ngầm snapshot lịch sử chuyển khoản của các đơn hàng hoặc giao dịch đã ghi nhận trước đó.</p>
          </div>
        </div>

        <div class="bank-config-grid">
          <form @submit.prevent="saveSettings" class="settings-form">
            <div class="form-grid two-cols">
              <div class="form-group">
                <label for="bank_name">Tên ngân hàng thụ hưởng <span class="required">*</span></label>
                <input id="bank_name" type="text" v-model="form.bank_name" required placeholder="MBBank (Ngân hàng TMCP Quân Đội)" />
              </div>

              <div class="form-group">
                <label for="bank_bin">Mã BIN VietQR ngân hàng <span class="required">*</span></label>
                <input id="bank_bin" type="text" v-model="form.bank_bin" required placeholder="970422" />
                <small class="field-hint">Mã BIN VietQR (MBBank: 970422, Vietcombank: 970436, v.v.)</small>
              </div>

              <div class="form-group">
                <label for="bank_account_number">Số tài khoản ngân hàng <span class="required">*</span></label>
                <input id="bank_account_number" type="text" v-model="form.bank_account_number" required placeholder="0971120038" />
              </div>

              <div class="form-group">
                <label for="bank_account_holder">Tên chủ tài khoản (In hoa không dấu) <span class="required">*</span></label>
                <input id="bank_account_holder" type="text" v-model="form.bank_account_holder" required placeholder="NAM HUNG RENTAL" />
              </div>
            </div>

            <div class="form-group mt-3">
              <label for="bank_transfer_instruction">Hướng dẫn chuyển khoản cho khách</label>
              <textarea 
                id="bank_transfer_instruction" 
                rows="3" 
                v-model="form.bank_transfer_instruction" 
                placeholder="Nhập hướng dẫn chuyển khoản hiển thị tại màn hình thanh toán..."
              ></textarea>
            </div>

            <div class="form-actions mt-4">
              <button type="submit" class="btn-primary" :disabled="saving">
                <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-floppy-disk"></i>
                <span>{{ saving ? 'Đang lưu...' : 'Lưu tài khoản ngân hàng' }}</span>
              </button>
            </div>
          </form>

          <!-- Live VietQR Preview Box -->
          <div class="qr-preview-box card">
            <h4 class="preview-title"><i class="fa-solid fa-eye"></i> Xem trước VietQR khách nhìn thấy</h4>
            <div class="qr-display">
              <img :src="previewQrUrl" alt="Mã VietQR xem trước" class="preview-qr-img" />
              <div class="qr-preview-meta">
                <div class="preview-row"><span>Ngân hàng:</span><strong>{{ form.bank_name || 'Chưa thiết lập' }}</strong></div>
                <div class="preview-row"><span>Số tài khoản:</span><strong class="highlight-text">{{ form.bank_account_number || '---' }}</strong></div>
                <div class="preview-row"><span>Chủ tài khoản:</span><span>{{ form.bank_account_holder || '---' }}</span></div>
                <div class="preview-row"><span>Cú pháp:</span><code>#HD-XXXXXX</code></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 3: Rental Policies -->
    <div v-if="activeTab === 'policy'" class="settings-tab-content">
      <div class="settings-card card">
        <div class="card-header-clean">
          <h3><i class="fa-solid fa-scale-balanced"></i> Quy Định & Chính Sách Thuê Phương Tiện</h3>
          <p>Điều khoản hiển thị tại trang chi tiết xe, quy trình thuê và chân trang.</p>
        </div>

        <form @submit.prevent="saveSettings" class="settings-form">
          <div class="form-group">
            <label for="deposit_policy">Chính sách đặt cọc bảo đảm</label>
            <textarea 
              id="deposit_policy" 
              rows="3" 
              v-model="form.rental_policy.deposit_policy" 
              placeholder="Quy định đặt cọc khi nhận xe và thời gian hoàn trả..."
            ></textarea>
          </div>

          <div class="form-group mt-3">
            <label for="document_requirements">Yêu cầu giấy tờ pháp lý</label>
            <textarea 
              id="document_requirements" 
              rows="3" 
              v-model="form.rental_policy.document_requirements" 
              placeholder="Giấy tờ yêu cầu (CCCD, Hộ chiếu, GPLX)..."
            ></textarea>
          </div>

          <div class="form-group mt-3">
            <label for="rental_time_rules">Quy ước tính thời gian thuê & Giao nhận</label>
            <textarea 
              id="rental_time_rules" 
              rows="3" 
              v-model="form.rental_policy.rental_time_rules" 
              placeholder="Quy ước tính ngày (24 giờ), phụ phí trả trễ..."
            ></textarea>
          </div>

          <div class="form-group mt-3">
            <label for="vehicle_rules">Quy tắc ứng xử trên xe</label>
            <textarea 
              id="vehicle_rules" 
              rows="3" 
              v-model="form.rental_policy.vehicle_rules" 
              placeholder="Không hút thuốc, không vận chuyển hàng cấm..."
            ></textarea>
          </div>

          <div class="form-actions mt-4">
            <button type="submit" class="btn-primary" :disabled="saving">
              <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-floppy-disk"></i>
              <span>{{ saving ? 'Đang lưu...' : 'Lưu chính sách thuê xe' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tab 4: Car Categories CRUD -->
    <div v-if="activeTab === 'categories'" class="settings-tab-content">
      <div class="content-card card">
        <div class="table-tools">
          <div class="tools-left">
            <h3><i class="fa-solid fa-tags"></i> Quản Lý Danh Mục Phương Tiện</h3>
            <p class="subtitle">Phân loại dòng xe theo phân khúc. Danh mục có xe liên kết sẽ được hệ thống bảo vệ, không thể xóa.</p>
          </div>
          <div class="tools-right" style="display: flex; gap: 10px; align-items: center;">
            <router-link to="/admin/categories" class="btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
              <i class="fa-solid fa-up-right-from-square"></i> Mở trang quản lý chuyên biệt
            </router-link>
            <button class="btn-primary" @click="openCategoryModal(null)">
              <i class="fa-solid fa-plus"></i> Thêm Danh Mục Mới
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="premium-table" v-if="categories.length > 0">
            <thead>
              <tr>
                <th width="90">Mã ID</th>
                <th>Tên danh mục</th>
                <th>Mô tả chi tiết</th>
                <th width="150" class="text-center">Số xe đang gán</th>
                <th width="130" class="text-center">Trạng thái</th>
                <th width="140">Ngày tạo</th>
                <th width="120" class="text-center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id" :class="{ 'row-inactive': !cat.is_active }">
                <td><span class="id-tag">#CAT-{{ cat.id }}</span></td>
                <td><strong class="cat-name">{{ cat.name }}</strong></td>
                <td><span class="cat-desc">{{ cat.description || 'Chưa có mô tả' }}</span></td>
                <td class="text-center">
                  <span class="count-badge" :class="{ 'has-cars': cat.cars_count > 0 }">
                    <i class="fa-solid fa-car"></i> {{ cat.cars_count }} xe
                  </span>
                </td>
                <td class="text-center">
                  <span class="status-badge" :style="cat.is_active ? 'color: #16A34A; background: #DCFCE7; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;' : 'color: #D97706; background: #FEF3C7; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;'">
                    {{ cat.is_active ? 'Đang dùng' : 'Ngừng dùng' }}
                  </span>
                </td>
                <td>{{ formatDate(cat.created_at) }}</td>
                <td class="text-center">
                  <div class="action-buttons-inline">
                    <button class="btn-icon-btn edit" @click="openCategoryModal(cat)" title="Chỉnh sửa">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button 
                      class="btn-icon-btn delete" 
                      @click="handleDeleteCategory(cat)" 
                      :title="cat.cars_count > 0 ? `Đang có ${cat.cars_count} xe liên kết (Không thể xóa)` : 'Xóa danh mục'"
                    >
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else-if="!loadingCategories" class="empty-state-box">
            <i class="fa-solid fa-tags"></i>
            <h4>Chưa có danh mục nào</h4>
            <p>Bấm nút "Thêm Danh Mục Mới" để tạo phân loại phương tiện đầu tiên.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal (Add / Edit) -->
    <div v-if="showCatModal" class="modal-overlay" @click.self="showCatModal = false">
      <div class="modal-content card category-modal animate-in">
        <div class="modal-header">
          <div class="modal-icon-badge">
            <i class="fa-solid fa-tags"></i>
          </div>
          <div>
            <h3>{{ editingCategory ? 'Chỉnh sửa Danh Mục' : 'Thêm Mới Danh Mục Xe' }}</h3>
            <p class="modal-sub">{{ editingCategory ? `Cập nhật thông tin #${editingCategory.id}` : 'Nhập tên danh mục không trùng lặp' }}</p>
          </div>
          <button class="btn-close-modal" @click="showCatModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="saveCategory" class="modal-body">
          <div class="form-group">
            <label for="cat_name">Tên danh mục xe <span class="required">*</span></label>
            <input 
              id="cat_name" 
              type="text" 
              v-model="catForm.name" 
              placeholder="Ví dụ: Sedan Hạng Sang, SUV 7 Chỗ..." 
              required 
            />
          </div>

          <div class="form-group mt-3">
            <label for="cat_desc">Mô tả đặc điểm danh mục</label>
            <textarea 
              id="cat_desc" 
              rows="3" 
              v-model="catForm.description" 
              placeholder="Mô tả phong cách, đối tượng phục vụ hoặc phân khúc..."
            ></textarea>
          </div>

          <div class="form-group mt-3">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" v-model="catForm.is_active" />
              <span>Kích hoạt sử dụng danh mục</span>
            </label>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn-outline" @click="showCatModal = false" :disabled="savingCategory">Hủy</button>
            <button type="submit" class="btn-primary" :disabled="savingCategory">
              <i v-if="savingCategory" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ savingCategory ? 'Đang lưu...' : (editingCategory ? 'Lưu thay đổi' : 'Thêm mới') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import { refreshSettings as clearClientCache } from '../../../core/settingsStore';

const toaster = createToaster({ position: "top-right" });

const activeTab = ref('general');
const loading = ref(false);
const saving = ref(false);

const form = reactive({
  site_name: '',
  site_logo: '',
  hotline: '',
  support_email: '',
  company_address: '',
  service_locations: [],
  bank_name: '',
  bank_account_number: '',
  bank_account_holder: '',
  bank_bin: '',
  bank_transfer_instruction: '',
  rental_policy: {
    deposit_policy: '',
    document_requirements: '',
    rental_time_rules: '',
    vehicle_rules: '',
  }
});

const locationsRaw = computed({
  get: () => Array.isArray(form.service_locations) ? form.service_locations.join(', ') : '',
  set: (val) => {
    form.service_locations = val.split(',').map(s => s.trim()).filter(Boolean);
  }
});

const previewQrUrl = computed(() => {
  const bin = form.bank_bin || '970422';
  const acc = form.bank_account_number || '0971120038';
  const name = encodeURIComponent(form.bank_account_holder || 'NAM HUNG RENTAL');
  return `https://api.vietqr.io/image/${bin}-${acc}-qr_only.png?accountName=${name}&amount=1500000&addInfo=HD12345`;
});

// Category State
const categories = ref([]);
const loadingCategories = ref(false);
const showCatModal = ref(false);
const editingCategory = ref(null);
const savingCategory = ref(false);
const catForm = reactive({
  name: '',
  description: '',
  is_active: true,
});

const fetchAllSettings = async () => {
  loading.value = true;
  try {
    const res = await baseRequest.get('admin/settings');
    if (res.data && res.data.status && res.data.data) {
      const data = res.data.data;
      if (data.site_name) form.site_name = data.site_name.value || '';
      if (data.site_logo) form.site_logo = data.site_logo.value || '';
      if (data.hotline) form.hotline = data.hotline.value || '';
      if (data.support_email) form.support_email = data.support_email.value || '';
      if (data.company_address) form.company_address = data.company_address.value || '';
      if (data.service_locations) form.service_locations = data.service_locations.value || [];
      if (data.bank_name) form.bank_name = data.bank_name.value || '';
      if (data.bank_account_number) form.bank_account_number = data.bank_account_number.value || '';
      if (data.bank_account_holder) form.bank_account_holder = data.bank_account_holder.value || '';
      if (data.bank_bin) form.bank_bin = data.bank_bin.value || '';
      if (data.bank_transfer_instruction) form.bank_transfer_instruction = data.bank_transfer_instruction.value || '';
      if (data.rental_policy && data.rental_policy.value) {
        Object.assign(form.rental_policy, data.rental_policy.value);
      }
    }
  } catch (err) {
    toaster.error("Không thể tải cấu hình hệ thống!");
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const payload = {
      site_name: form.site_name,
      site_logo: form.site_logo,
      hotline: form.hotline,
      support_email: form.support_email,
      company_address: form.company_address,
      service_locations: form.service_locations,
      bank_name: form.bank_name,
      bank_account_number: form.bank_account_number,
      bank_account_holder: form.bank_account_holder,
      bank_bin: form.bank_bin,
      bank_transfer_instruction: form.bank_transfer_instruction,
      rental_policy: form.rental_policy,
    };

    const res = await baseRequest.put('admin/settings', payload);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Lưu cấu hình hệ thống thành công!");
      await clearClientCache();
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Có lỗi xảy ra khi lưu cấu hình!";
    toaster.error(msg);
  } finally {
    saving.value = false;
  }
};

const handleRefreshAll = async () => {
  await clearClientCache();
  await fetchAllSettings();
  await fetchCategories();
  toaster.success("Đã làm mới dữ liệu và xóa bộ nhớ đệm cache!");
};

// Categories Management
const fetchCategories = async () => {
  loadingCategories.value = true;
  try {
    const res = await baseRequest.get('admin/car-categories');
    if (res.data && res.data.status) {
      categories.value = res.data.data;
    }
  } catch (err) {
    toaster.error("Không thể tải danh sách danh mục xe!");
  } finally {
    loadingCategories.value = false;
  }
};

const openCategoryModal = (cat) => {
  editingCategory.value = cat;
  if (cat) {
    catForm.name = cat.name;
    catForm.description = cat.description || '';
    catForm.is_active = cat.is_active !== undefined ? Boolean(cat.is_active) : true;
  } else {
    catForm.name = '';
    catForm.description = '';
    catForm.is_active = true;
  }
  showCatModal.value = true;
};

const saveCategory = async () => {
  savingCategory.value = true;
  try {
    if (editingCategory.value) {
      const res = await baseRequest.put(`admin/car-categories/${editingCategory.value.id}`, catForm);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Cập nhật danh mục thành công!");
        showCatModal.value = false;
        fetchCategories();
      }
    } else {
      const res = await baseRequest.post('admin/car-categories', catForm);
      if (res.data && res.data.status) {
        toaster.success(res.data.message || "Thêm mới danh mục thành công!");
        showCatModal.value = false;
        fetchCategories();
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Lỗi khi lưu danh mục!";
    toaster.error(msg);
  } finally {
    savingCategory.value = false;
  }
};

const handleDeleteCategory = async (cat) => {
  if (cat.cars_count > 0) {
    toaster.warning(`Không thể xóa danh mục "${cat.name}" vì đang có ${cat.cars_count} xe đang liên kết! Vui lòng chuyển hoặc xóa xe trước.`);
    return;
  }

  if (!confirm(`Bạn có chắc chắn muốn xóa danh mục "${cat.name}"?`)) {
    return;
  }

  try {
    const res = await baseRequest.delete(`admin/car-categories/${cat.id}`);
    if (res.data && res.data.status) {
      toaster.success(res.data.message || "Đã xóa danh mục thành công!");
      fetchCategories();
    }
  } catch (err) {
    const msg = err.response?.data?.message || "Không thể xóa danh mục!";
    toaster.error(msg);
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

onMounted(() => {
  fetchAllSettings();
  fetchCategories();
});
</script>

<style scoped>
.admin-page {
  padding-bottom: 40px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
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
  font-size: 0.9rem;
  color: var(--color-text-muted, #64748b);
  margin: 4px 0 0;
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
  background: var(--bg-surface-sub, #f8fafc);
  border-color: var(--color-primary, #0066ff);
  color: var(--color-primary, #0066ff);
}

/* Tabs */
.tabs-nav {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 12px;
  overflow-x: auto;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.tab-btn.active {
  background: #eff6ff;
  color: var(--color-primary, #0066ff);
  font-weight: 700;
}

/* Cards */
.settings-card, .content-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.card-header-clean {
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 14px;
}

.card-header-clean h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-header-clean p {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

/* Form Styles */
.settings-form {
  display: flex;
  flex-direction: column;
}

.form-grid {
  display: grid;
  gap: 16px;
}

.form-grid.two-cols {
  grid-template-columns: repeat(2, 1fr);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 0.84rem;
  font-weight: 700;
  color: #334155;
}

.required {
  color: #ef4444;
}

.form-group input, .form-group textarea {
  width: 100%;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 0.9rem;
  outline: none;
  font-family: inherit;
  transition: border-color 0.2s;
}

.form-group input:focus, .form-group textarea:focus {
  border-color: var(--color-primary, #0066ff);
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
}

.field-hint {
  font-size: 0.75rem;
  color: #94a3b8;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  padding: 10px 24px;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #0066ff);
  color: white;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: opacity 0.2s;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.92;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  padding: 10px 20px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
}

/* Bank Grid & QR Preview */
.bank-config-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}

.alert-notice {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 12px;
  margin-bottom: 20px;
}

.alert-notice.warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

.alert-notice i {
  font-size: 1.25rem;
  margin-top: 2px;
}

.alert-notice p {
  font-size: 0.82rem;
  margin: 4px 0 0;
  line-height: 1.45;
}

.qr-preview-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  text-align: center;
}

.preview-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.preview-qr-img {
  width: 200px;
  height: 200px;
  border-radius: 12px;
  background: white;
  padding: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  margin-bottom: 16px;
}

.qr-preview-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  text-align: left;
  font-size: 0.82rem;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.preview-row span {
  color: #64748b;
}

.highlight-text {
  color: var(--color-primary, #0066ff);
}

/* Category Table */
.table-tools {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 16px;
}

.tools-left h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px;
}

.tools-left .subtitle {
  font-size: 0.82rem;
  color: #64748b;
  margin: 0;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.premium-table th {
  padding: 12px 16px;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.id-tag {
  font-family: monospace;
  font-weight: 700;
  font-size: 0.82rem;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}

.cat-name {
  font-size: 0.92rem;
  color: #0f172a;
}

.cat-desc {
  font-size: 0.82rem;
  color: #64748b;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  background: #f1f5f9;
  color: #64748b;
}

.count-badge.has-cars {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.action-buttons-inline {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn-icon-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.btn-icon-btn.edit {
  color: #0284c7;
}

.btn-icon-btn.edit:hover {
  background: #e0f2fe;
  border-color: #bae6fd;
}

.btn-icon-btn.delete {
  color: #ef4444;
}

.btn-icon-btn.delete:hover {
  background: #fee2e2;
  border-color: #fecaca;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.category-modal {
  width: 100%;
  max-width: 500px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.modal-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #eff6ff;
  color: var(--color-primary, #0066ff);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.modal-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 2px 0 0;
}

.btn-close-modal {
  margin-left: auto;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #e2e8f0;
  color: #64748b;
  cursor: pointer;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

@media (max-width: 900px) {
  .bank-config-grid {
    grid-template-columns: 1fr;
  }
  .form-grid.two-cols {
    grid-template-columns: 1fr;
  }
}
</style>
