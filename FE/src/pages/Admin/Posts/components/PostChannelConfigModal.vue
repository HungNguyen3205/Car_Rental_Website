<template>
  <div v-if="visible" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content card channel-config-modal animate-in">
      <div class="modal-header">
        <div>
          <h3><i class="fa-solid fa-sliders text-primary"></i> Cấu Hình Kênh Kết Nối (Google Drive & Facebook)</h3>
          <p class="modal-sub">Quản lý kết nối Google Drive để lấy ảnh và kết nối Facebook Page để đăng bài tự động</p>
        </div>
        <button class="btn-close" @click="$emit('close')"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <!-- Tab Switcher -->
      <div class="config-tabs">
        <button 
          class="tab-btn" 
          :class="{ active: activeTab === 'drive' }" 
          @click="activeTab = 'drive'"
        >
          <i class="fa-brands fa-google-drive"></i>
          <span>1. Google Drive (Thư mục ảnh)</span>
        </button>
        <button 
          class="tab-btn" 
          :class="{ active: activeTab === 'facebook' }" 
          @click="activeTab = 'facebook'"
        >
          <i class="fa-brands fa-facebook"></i>
          <span>2. Facebook Page (Graph API)</span>
        </button>
      </div>

      <div class="modal-body">
        <!-- TAB 1: GOOGLE DRIVE -->
        <div v-if="activeTab === 'drive'" class="tab-content">
          <div class="instruction-box">
            <i class="fa-solid fa-circle-info text-primary"></i>
            <div>
              <strong>Nguyên tắc kết nối Google Drive:</strong>
              Hệ thống sử dụng Service Account chính thức với quyền chỉ đọc (Read-only). Thư mục Drive của bạn không cần công khai toàn cầu, chỉ cần chia sẻ quyền <em>"Viewer" (Người xem)</em> cho email của Service Account bên dưới.
            </div>
          </div>

          <div class="form-group mt-3" v-if="driveConfig.service_account_email">
            <label>Email Service Account (Cần cấp quyền Viewer vào thư mục Drive):</label>
            <div class="copy-input-group">
              <input type="text" :value="driveConfig.service_account_email" readonly class="form-input readonly-code" />
              <button type="button" class="btn-copy" @click="copyText(driveConfig.service_account_email)">
                <i class="fa-regular fa-copy"></i> Sao chép
              </button>
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Google Drive Folder ID <span class="required">*</span></label>
            <input 
              type="text" 
              v-model="driveForm.folder_id" 
              placeholder="VD: 1BxiMVs0XRA5nFMdKvBHK... hoặc dán toàn bộ link thư mục Drive" 
              class="form-input"
            />
            <small class="helper-text">
              Bạn có thể dán toàn bộ đường dẫn URL dạng <code>https://drive.google.com/drive/folders/1BxiMVs...</code>, hệ thống sẽ tự trích xuất ID.
            </small>
          </div>

          <div class="connection-status-box" :class="driveStatusClass">
            <div class="status-indicator">
              <i class="fa-solid" :class="driveStatusIcon"></i>
              <span>{{ driveStatusText }}</span>
            </div>
            <div v-if="driveConfig.last_synced_at" class="last-sync">
              Đồng bộ gần nhất: <strong>{{ formatDateTime(driveConfig.last_synced_at) }}</strong>
            </div>
          </div>

          <!-- Kết quả test quyền đọc -->
          <div v-if="driveTestResult" class="test-result-box" :class="{ success: driveTestResult.success, error: !driveTestResult.success }">
            <div class="result-title">
              <i class="fa-solid" :class="driveTestResult.success ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger'"></i>
              <strong>{{ driveTestResult.success ? 'Kiểm tra quyền đọc thành công!' : 'Kiểm tra thất bại' }}</strong>
            </div>
            <p class="result-msg">{{ driveTestResult.message }}</p>
            <div v-if="driveTestResult.data" class="result-meta">
              <span>Thư mục: <strong>{{ driveTestResult.data.folder_name }}</strong></span>
              <span>Folder ID: <code>{{ driveTestResult.data.folder_id }}</code></span>
            </div>
          </div>
        </div>

        <!-- TAB 2: FACEBOOK PAGE -->
        <div v-if="activeTab === 'facebook'" class="tab-content">
          <div class="instruction-box">
            <i class="fa-solid fa-shield-halved text-primary"></i>
            <div>
              <strong>Kết nối Facebook Page (Meta Graph API):</strong>
              Chỉ hỗ trợ đăng lên Facebook Page (Fanpage) mà bạn có quyền quản lý nội dung (`pages_manage_posts`). Không hỗ trợ trang cá nhân (Personal Profile). Token được lưu trữ an toàn phía máy chủ và không bao giờ xuất hiện trong nhật ký.
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Facebook Page ID <span class="required">*</span></label>
            <input 
              type="text" 
              v-model="facebookForm.page_id" 
              placeholder="VD: 1000100010001" 
              class="form-input"
            />
            <small class="helper-text">Mã định danh Fanpage của bạn trong phần Cài đặt Trang Facebook.</small>
          </div>

          <div class="form-group mt-3">
            <label>Facebook Page Access Token</label>
            <input 
              type="password" 
              v-model="facebookForm.page_access_token" 
              placeholder="Nhập Page Access Token dài hạn (Bỏ trống nếu không thay đổi)" 
              class="form-input"
            />
            <small class="helper-text">Token dài hạn cấp quyền <code>pages_manage_posts, pages_read_engagement</code>.</small>
          </div>

          <div class="form-group mt-3">
            <label>Tên hiển thị Page (Tùy chọn)</label>
            <input 
              type="text" 
              v-model="facebookForm.page_name" 
              placeholder="VD: Thuê Xe Điện VinFast Uy Tín" 
              class="form-input"
            />
          </div>

          <div class="connection-status-box" :class="facebookStatusClass">
            <div class="status-indicator">
              <i class="fa-solid" :class="facebookStatusIcon"></i>
              <span>{{ facebookStatusText }}</span>
            </div>
            <div v-if="facebookConfig.page_name" class="last-sync">
              Trang hiện tại: <strong>{{ facebookConfig.page_name }}</strong> (ID: {{ facebookConfig.page_id }})
            </div>
          </div>

          <!-- Kết quả test kết nối Facebook -->
          <div v-if="facebookTestResult" class="test-result-box" :class="{ success: facebookTestResult.success, error: !facebookTestResult.success }">
            <div class="result-title">
              <i class="fa-solid" :class="facebookTestResult.success ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger'"></i>
              <strong>{{ facebookTestResult.success ? 'Xác thực Facebook Page thành công!' : 'Xác thực thất bại' }}</strong>
            </div>
            <p class="result-msg">{{ facebookTestResult.message }}</p>
          </div>
        </div>
      </div>

      <div class="modal-footer flex-between">
        <div class="left-actions">
          <button 
            v-if="activeTab === 'drive'" 
            type="button" 
            class="btn-outline" 
            @click="testDriveConnection" 
            :disabled="testingDrive"
          >
            <i class="fa-solid fa-plug" :class="{ 'fa-spin': testingDrive }"></i>
            <span>{{ testingDrive ? 'Đang kiểm tra Drive...' : 'Kiểm tra quyền đọc' }}</span>
          </button>

          <button 
            v-if="activeTab === 'facebook'" 
            type="button" 
            class="btn-outline" 
            @click="testFacebookConnection" 
            :disabled="testingFacebook"
          >
            <i class="fa-solid fa-plug" :class="{ 'fa-spin': testingFacebook }"></i>
            <span>{{ testingFacebook ? 'Đang kiểm tra Facebook...' : 'Kiểm tra Token Fanpage' }}</span>
          </button>
        </div>

        <div class="right-actions">
          <button type="button" class="btn-outline" @click="$emit('close')">Đóng</button>
          <button 
            v-if="activeTab === 'drive'" 
            type="button" 
            class="btn-primary" 
            @click="saveDriveConfig" 
            :disabled="savingDrive"
          >
            <i class="fa-solid fa-floppy-disk"></i>
            <span>{{ savingDrive ? 'Đang lưu...' : 'Lưu cấu hình Drive' }}</span>
          </button>

          <button 
            v-if="activeTab === 'facebook'" 
            type="button" 
            class="btn-primary" 
            @click="saveFacebookConfig" 
            :disabled="savingFacebook"
          >
            <i class="fa-solid fa-floppy-disk"></i>
            <span>{{ savingFacebook ? 'Đang lưu...' : 'Lưu cấu hình Facebook' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import baseRequest from '../../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const props = defineProps({
  visible: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'updated']);
const toaster = createToaster({ position: "top-right" });

const activeTab = ref('drive');

// Drive States
const driveConfig = reactive({
  folder_id: '',
  service_account_email: '',
  has_service_account: false,
  last_synced_at: null,
});
const driveForm = reactive({ folder_id: '' });
const testingDrive = ref(false);
const savingDrive = ref(false);
const driveTestResult = ref(null);

// Facebook States
const facebookConfig = reactive({
  page_id: '',
  page_name: '',
  is_ready: false,
});
const facebookForm = reactive({
  page_id: '',
  page_access_token: '',
  page_name: '',
});
const testingFacebook = ref(false);
const savingFacebook = ref(false);
const facebookTestResult = ref(null);

// Formats & Helpers
const formatDateTime = (val) => {
  if (!val) return 'Chưa đồng bộ';
  return new Date(val).toLocaleString('vi-VN');
};

const copyText = (text) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  toaster.success('Đã sao chép email Service Account vào clipboard!');
};

// Computed UI Statuses
const driveStatusClass = computed(() => {
  if (driveTestResult.value) {
    return driveTestResult.value.success ? 'status-ok' : 'status-err';
  }
  return driveConfig.folder_id ? 'status-warn' : 'status-none';
});

const driveStatusIcon = computed(() => {
  if (driveTestResult.value?.success) return 'fa-circle-check text-success';
  if (driveTestResult.value && !driveTestResult.value.success) return 'fa-circle-xmark text-danger';
  return 'fa-circle-question text-muted';
});

const driveStatusText = computed(() => {
  if (driveTestResult.value?.success) return 'Đã kết nối và xác minh quyền đọc thành công';
  if (driveTestResult.value && !driveTestResult.value.success) return 'Lỗi kết nối thư mục Google Drive';
  return driveConfig.folder_id ? 'Đã cấu hình Folder ID (Bấm "Kiểm tra quyền đọc" để xác minh)' : 'Chưa cấu hình Folder ID';
});

const facebookStatusClass = computed(() => {
  if (facebookTestResult.value) {
    return facebookTestResult.value.success ? 'status-ok' : 'status-err';
  }
  return facebookConfig.is_ready ? 'status-ok' : 'status-none';
});

const facebookStatusIcon = computed(() => {
  if (facebookTestResult.value?.success) return 'fa-circle-check text-success';
  if (facebookTestResult.value && !facebookTestResult.value.success) return 'fa-circle-xmark text-danger';
  return facebookConfig.is_ready ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-warning';
});

const facebookStatusText = computed(() => {
  if (facebookTestResult.value?.success) return 'Fanpage đã xác thực và sẵn sàng xuất bản';
  if (facebookTestResult.value && !facebookTestResult.value.success) return 'Không thể xác thực Fanpage';
  return facebookConfig.is_ready ? 'Fanpage đã được cấu hình sẵn sàng' : 'Chưa cấu hình Facebook Page';
});

// Fetch Configs
const fetchDriveConfig = async () => {
  try {
    const res = await baseRequest.get('admin/post-schedules/drive-config');
    if (res.data?.status && res.data.data) {
      Object.assign(driveConfig, res.data.data);
      driveForm.folder_id = driveConfig.folder_id || '';
    }
  } catch (err) {
    console.error('Fetch Drive Config Error:', err);
  }
};

const fetchFacebookConfig = async () => {
  try {
    const res = await baseRequest.get('admin/post-schedules/facebook-config');
    if (res.data?.status && res.data.data) {
      Object.assign(facebookConfig, res.data.data);
      facebookForm.page_id = facebookConfig.page_id || '';
      facebookForm.page_name = facebookConfig.page_name || '';
    }
  } catch (err) {
    console.error('Fetch Facebook Config Error:', err);
  }
};

// Actions: Drive
const testDriveConnection = async () => {
  testingDrive.value = true;
  driveTestResult.value = null;
  try {
    const res = await baseRequest.post('admin/post-schedules/drive-test', {
      folder_id: driveForm.folder_id,
    });
    driveTestResult.value = res.data.data || { success: res.data.status === 1, message: res.data.message };
    if (res.data.status === 1) {
      toaster.success(res.data.message || 'Kết nối Google Drive thành công!');
    } else {
      toaster.error(res.data.message || 'Lỗi truy cập thư mục Google Drive.');
    }
  } catch (err) {
    driveTestResult.value = {
      success: false,
      message: err.response?.data?.message || 'Không thể kiểm tra kết nối Google Drive.',
    };
    toaster.error(driveTestResult.value.message);
  } finally {
    testingDrive.value = false;
  }
};

const saveDriveConfig = async () => {
  savingDrive.value = true;
  try {
    const res = await baseRequest.post('admin/post-schedules/drive-config', {
      folder_id: driveForm.folder_id,
    });
    if (res.data?.status === 1) {
      toaster.success('Đã lưu cấu hình Google Drive thành công!');
      await fetchDriveConfig();
      emit('updated');
    } else {
      toaster.error(res.data?.message || 'Không thể lưu cấu hình.');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi lưu cấu hình.');
  } finally {
    savingDrive.value = false;
  }
};

// Actions: Facebook
const testFacebookConnection = async () => {
  testingFacebook.value = true;
  facebookTestResult.value = null;
  try {
    const res = await baseRequest.post('admin/post-schedules/facebook-test');
    facebookTestResult.value = res.data.data || { success: res.data.status === 1, message: res.data.message };
    if (res.data.status === 1) {
      toaster.success(res.data.message || 'Xác thực Fanpage thành công!');
    } else {
      toaster.error(res.data.message || 'Xác thực Fanpage thất bại.');
    }
  } catch (err) {
    facebookTestResult.value = {
      success: false,
      message: err.response?.data?.message || 'Lỗi kiểm tra Facebook Page.',
    };
    toaster.error(facebookTestResult.value.message);
  } finally {
    testingFacebook.value = false;
  }
};

const saveFacebookConfig = async () => {
  if (!facebookForm.page_id?.trim()) {
    toaster.warning('Vui lòng nhập Facebook Page ID.');
    return;
  }
  savingFacebook.value = true;
  try {
    const res = await baseRequest.post('admin/post-schedules/facebook-config', {
      page_id: facebookForm.page_id,
      page_access_token: facebookForm.page_access_token,
      page_name: facebookForm.page_name,
    });
    if (res.data?.status === 1) {
      toaster.success('Đã lưu cấu hình Facebook Page thành công!');
      facebookForm.page_access_token = '';
      await fetchFacebookConfig();
      emit('updated');
    } else {
      toaster.error(res.data?.message || 'Không thể lưu cấu hình.');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi lưu cấu hình.');
  } finally {
    savingFacebook.value = false;
  }
};

onMounted(() => {
  fetchDriveConfig();
  fetchFacebookConfig();
});
</script>

<style scoped>
.channel-config-modal {
  max-width: 720px;
  width: 95%;
}

.config-tabs {
  display: flex;
  border-bottom: 1px solid var(--color-border);
  padding: 0 16px;
  background: var(--bg-surface-alt);
}

.tab-btn {
  padding: 12px 18px;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  font-size: 0.86rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: var(--color-text-main);
}

.tab-btn.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.tab-content {
  padding-top: 6px;
}

.instruction-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  background: rgba(30, 58, 138, 0.05);
  border: 1px solid rgba(30, 58, 138, 0.15);
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  line-height: 1.5;
  color: var(--color-text-main);
}

.copy-input-group {
  display: flex;
  gap: 8px;
}

.readonly-code {
  font-family: monospace;
  background: var(--bg-surface-alt);
}

.btn-copy {
  padding: 8px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--bg-surface);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-copy:hover {
  background: var(--color-border-light);
}

.connection-status-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  margin-top: 14px;
  font-size: 0.82rem;
}

.connection-status-box.status-ok {
  background: #ECFDF5;
  border: 1px solid #A7F3D0;
  color: #065F46;
}

.connection-status-box.status-err {
  background: #FEF2F2;
  border: 1px solid #FECACA;
  color: #991B1B;
}

.connection-status-box.status-warn {
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  color: #92400E;
}

.connection-status-box.status-none {
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  color: var(--color-text-muted);
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.test-result-box {
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
}

.test-result-box.success {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
}

.test-result-box.error {
  background: #FEF2F2;
  border: 1px solid #FECACA;
}

.result-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.result-msg {
  margin: 0;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.result-meta {
  display: flex;
  gap: 16px;
  margin-top: 8px;
  font-size: 0.78rem;
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.left-actions, .right-actions {
  display: flex;
  gap: 10px;
}
</style>
