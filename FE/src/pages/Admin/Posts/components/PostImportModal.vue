<template>
  <div v-if="visible" class="modal-overlay" @click.self="onCloseModal">
    <div class="modal-content card post-import-modal animate-in">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="modal-icon-badge">
            <i class="fa-solid fa-file-excel"></i>
          </div>
          <div>
            <h3>Import Bài Viết & Lên Lịch Đăng Đa Kênh</h3>
            <p class="modal-sub">
              Nhập nội dung từ file Excel 4 cột, tự động ghép ảnh từ Google Drive và lên lịch đăng Website / Facebook Page.
            </p>
          </div>
        </div>
        <button class="btn-close" @click="onCloseModal"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <!-- Step Indicator -->
      <div class="import-stepper">
        <div class="step-item" :class="{ active: currentStep === 1, done: currentStep > 1 }">
          <div class="step-num"><i v-if="currentStep > 1" class="fa-solid fa-check"></i><span v-else>1</span></div>
          <span class="step-label">Tải tệp Excel</span>
        </div>
        <div class="step-line" :class="{ done: currentStep > 1 }"></div>

        <div class="step-item" :class="{ active: currentStep === 2, done: currentStep > 2 }">
          <div class="step-num"><i v-if="currentStep > 2" class="fa-solid fa-check"></i><span v-else>2</span></div>
          <span class="step-label">Ghép ảnh Drive</span>
        </div>
        <div class="step-line" :class="{ done: currentStep > 2 }"></div>

        <div class="step-item" :class="{ active: currentStep === 3, done: currentStep > 3 }">
          <div class="step-num"><i v-if="currentStep > 3" class="fa-solid fa-check"></i><span v-else>3</span></div>
          <span class="step-label">Xem trước & Sửa</span>
        </div>
        <div class="step-line" :class="{ done: currentStep > 3 }"></div>

        <div class="step-item" :class="{ active: currentStep === 4, done: currentStep > 4 }">
          <div class="step-num"><i v-if="currentStep > 4" class="fa-solid fa-check"></i><span v-else>4</span></div>
          <span class="step-label">Chọn kênh đăng</span>
        </div>
        <div class="step-line" :class="{ done: currentStep > 4 }"></div>

        <div class="step-item" :class="{ active: currentStep === 5, done: currentStep === 5 }">
          <div class="step-num"><span>5</span></div>
          <span class="step-label">Xác nhận & Snapshot</span>
        </div>
      </div>

      <div class="modal-body import-modal-body">
        <!-- ==================== BƯỚC 1: CHỌN FILE EXCEL & DRIVE FOLDER ==================== -->
        <div v-if="currentStep === 1" class="step-content">
          <div class="template-download-banner">
            <div class="banner-text">
              <i class="fa-solid fa-file-circle-check text-emerald"></i>
              <div>
                <strong>Định dạng file Excel chuẩn 4 cột:</strong>
                <code>content | tên ảnh | ngày đăng | giờ đăng</code> (Ví dụ: <em>25/10/2026 | 08:30</em>)
              </div>
            </div>
            <button type="button" class="btn-download-tpl" @click="downloadTemplate">
              <i class="fa-solid fa-download"></i> Tải file mẫu (.xlsx)
            </button>
          </div>

          <!-- Dropzone -->
          <div 
            class="file-dropzone" 
            :class="{ 'has-file': selectedFile, 'drag-over': isDragOver }"
            @dragover.prevent="isDragOver = true"
            @dragleave.prevent="isDragOver = false"
            @drop.prevent="onFileDrop"
          >
            <input 
              type="file" 
              ref="fileInputRef" 
              accept=".xlsx,.xls" 
              @change="onFileSelected" 
              hidden 
            />
            
            <div v-if="!selectedFile" class="dropzone-empty" @click="triggerFileInput">
              <i class="fa-solid fa-cloud-arrow-up drop-icon"></i>
              <h4>Kéo & Thả file Excel vào đây hoặc click để chọn</h4>
              <p>Hỗ trợ định dạng .xlsx, .xls (Tối đa 500 dòng, dung lượng tối đa 10MB)</p>
              <button type="button" class="btn-browse">Chọn file từ máy</button>
            </div>

            <div v-else class="dropzone-selected">
              <div class="file-info-badge">
                <i class="fa-solid fa-file-excel file-badge-icon"></i>
                <div class="file-text">
                  <span class="file-name">{{ selectedFile.name }}</span>
                  <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                </div>
              </div>
              <button type="button" class="btn-remove-file" @click="removeSelectedFile">
                <i class="fa-solid fa-trash-can"></i> Đổi file khác
              </button>
            </div>
          </div>

          <!-- Thư mục Drive -->
          <div class="form-group mt-3">
            <label>Google Drive Folder ID (Thư mục chứa ảnh bài viết) <span class="required">*</span></label>
            <div class="input-with-button">
              <input 
                type="text" 
                v-model="driveFolderId" 
                placeholder="VD: 1BxiMVs0XRA5nFMdKvBHK... hoặc dán link thư mục Google Drive" 
                class="form-input"
              />
              <button type="button" class="btn-secondary" @click="testDriveFolder" :disabled="testingDrive">
                <i class="fa-solid fa-plug" :class="{ 'fa-spin': testingDrive }"></i>
                <span>{{ testingDrive ? 'Đang kiểm tra...' : 'Kiểm tra Drive' }}</span>
              </button>
            </div>
            <small class="helper-text">
              Hệ thống sẽ tra cứu tên ảnh chính xác trong thư mục này. Hãy chia sẻ quyền <em>Viewer</em> cho email: 
              <code>{{ serviceAccountEmail || 'marketing-sa@...iam.gserviceaccount.com' }}</code>
            </small>
          </div>
        </div>

        <!-- ==================== BƯỚC 2: PHÂN TÍCH & GHÉP ẢNH DRIVE (LOADING) ==================== -->
        <div v-if="currentStep === 2" class="step-content step-analyzing">
          <div class="analyzing-card">
            <div class="spinner-radar">
              <i class="fa-solid fa-magnifying-glass fa-spin text-primary"></i>
            </div>
            <h3>Đang đọc tệp Excel & Đối chiếu ảnh Google Drive...</h3>
            <p>
              Hệ thống đang duyệt phân trang toàn bộ thư mục Drive, kiểm tra ngày giờ đăng tương lai và lập chỉ mục ảnh chính xác.
            </p>
          </div>
        </div>

        <!-- ==================== BƯỚC 3: BẢNG XEM TRƯỚC (PREVIEW & SỬA) ==================== -->
        <div v-if="currentStep === 3" class="step-content">
          <!-- Summary Bar -->
          <div class="preview-stats-bar">
            <div class="stat-pill total">
              <span>Tổng dòng:</span>
              <strong>{{ previewData?.total_rows || 0 }}</strong>
            </div>
            <div class="stat-pill valid">
              <i class="fa-solid fa-circle-check"></i>
              <span>Hợp lệ:</span>
              <strong>{{ previewData?.valid_rows_count || 0 }}</strong>
            </div>
            <div class="stat-pill error" v-if="previewData?.error_rows_count > 0">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>Có lỗi:</span>
              <strong>{{ previewData?.error_rows_count }}</strong>
            </div>
            <div class="stat-pill skipped" v-if="previewData?.skipped_empty_rows > 0">
              <span>Bỏ qua dòng trống:</span>
              <strong>{{ previewData?.skipped_empty_rows }}</strong>
            </div>

            <!-- Filter View -->
            <div class="preview-filters-right">
              <button 
                type="button" 
                class="btn-filter-toggle" 
                :class="{ active: previewFilter === 'all' }" 
                @click="previewFilter = 'all'"
              >
                Tất cả ({{ previewRows.length }})
              </button>
              <button 
                type="button" 
                class="btn-filter-toggle" 
                :class="{ active: previewFilter === 'valid' }" 
                @click="previewFilter = 'valid'"
              >
                Hợp lệ ({{ previewValidRows.length }})
              </button>
              <button 
                v-if="previewErrorRows.length > 0"
                type="button" 
                class="btn-filter-toggle text-danger" 
                :class="{ active: previewFilter === 'error' }" 
                @click="previewFilter = 'error'"
              >
                Chỉ lỗi ({{ previewErrorRows.length }})
              </button>

              <button 
                v-if="previewErrorRows.length > 0" 
                type="button" 
                class="btn-outline-danger btn-sm" 
                @click="uncheckAllErrors"
                title="Bỏ chọn các dòng lỗi để chỉ import các dòng hợp lệ"
              >
                <i class="fa-solid fa-filter-circle-xmark"></i> Bỏ chọn dòng lỗi
              </button>
            </div>
          </div>

          <!-- Preview Table -->
          <div class="preview-table-container">
            <table class="preview-table">
              <thead>
                <tr>
                  <th width="40" class="text-center">
                    <input 
                      type="checkbox" 
                      :checked="isAllSelected" 
                      @change="toggleSelectAll($event.target.checked)" 
                    />
                  </th>
                  <th width="60" class="text-center">Dòng</th>
                  <th width="120">Tên ảnh Drive</th>
                  <th width="140">Thời gian đăng</th>
                  <th width="240">Tiêu đề Website (Sửa được)</th>
                  <th>Nội dung bài viết (Caption)</th>
                  <th width="140" class="text-center">Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(row, idx) in displayedPreviewRows" 
                  :key="idx"
                  :class="{ 'row-error': row.status !== 'valid', 'row-unselected': !selectedRowNumbers.has(row.row_number) }"
                >
                  <td class="text-center">
                    <input 
                      type="checkbox" 
                      :checked="selectedRowNumbers.has(row.row_number)"
                      @change="toggleRowSelection(row.row_number)"
                    />
                  </td>

                  <td class="text-center">
                    <span class="row-num-badge">#{{ row.row_number }}</span>
                  </td>

                  <!-- Ảnh Drive -->
                  <td>
                    <div class="preview-img-cell">
                      <div class="preview-img-thumb" :class="{ 'thumb-err': !row.drive_match?.file }">
                        <img 
                          v-if="row.drive_match?.file?.thumbnail_link" 
                          :src="row.drive_match.file.thumbnail_link" 
                          alt="Thumb" 
                        />
                        <i v-else class="fa-regular fa-image"></i>
                      </div>
                      <div class="img-meta">
                        <span class="img-name" :title="row.image_name">{{ row.image_name }}</span>
                        <span v-if="row.drive_match?.status === 'matched'" class="match-tag text-emerald">
                          <i class="fa-solid fa-check"></i> Đã khớp
                        </span>
                        <span v-else-if="row.drive_match?.status === 'duplicate'" class="match-tag text-warning">
                          <i class="fa-solid fa-copy"></i> Trùng tên
                        </span>
                        <span v-else class="match-tag text-danger">
                          <i class="fa-solid fa-xmark"></i> Không thấy
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- Ngày giờ đăng -->
                  <td>
                    <div class="schedule-cell">
                      <i class="fa-regular fa-clock text-muted"></i>
                      <span class="schedule-text">{{ row.scheduled_display || '—' }}</span>
                    </div>
                  </td>

                  <!-- Tiêu đề đề xuất (Cho phép sửa trực tiếp) -->
                  <td>
                    <input 
                      type="text" 
                      v-model="row.title" 
                      class="form-input table-input" 
                      placeholder="Nhập tiêu đề website..."
                    />
                  </td>

                  <!-- Nội dung -->
                  <td>
                    <div class="content-cell">
                      <p class="content-snippet">{{ row.content_preview }}</p>
                      <button type="button" class="btn-read-more" @click="openContentModal(row)">
                        Xem toàn văn
                      </button>
                    </div>
                  </td>

                  <!-- Trạng thái & Lỗi -->
                  <td class="text-center">
                    <div v-if="row.status === 'valid'" class="badge-status-valid">
                      <i class="fa-solid fa-circle-check"></i> Hợp lệ
                    </div>
                    <div v-else class="badge-status-error" :title="row.errors.join(' | ')">
                      <i class="fa-solid fa-circle-exclamation"></i> Lỗi ({{ row.errors.length }})
                    </div>

                    <div v-if="row.errors.length > 0" class="row-error-list">
                      <small v-for="(err, eIdx) in row.errors" :key="eIdx" class="error-msg">
                        • {{ err }}
                      </small>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ==================== BƯỚC 4: CHỌN KÊNH XUẤT BẢN ==================== -->
        <div v-if="currentStep === 4" class="step-content">
          <div class="channel-selection-wrapper">
            <h3>Chọn kênh xuất bản cho đợt import này:</h3>
            <p class="sub-text">
              Hệ thống độc lập trạng thái từng kênh. Bạn có thể chọn đăng lên Website, Facebook Page hoặc Cả hai.
            </p>

            <div class="channels-grid">
              <!-- Channel 1: Website -->
              <div 
                class="channel-card" 
                :class="{ active: selectedChannel === 'website' }"
                @click="selectedChannel = 'website'"
              >
                <div class="channel-card-header">
                  <div class="channel-icon web"><i class="fa-solid fa-globe"></i></div>
                  <input type="radio" value="website" v-model="selectedChannel" />
                </div>
                <h4>Chỉ Website</h4>
                <p>Tạo bài viết tin tức & cẩm nang trên website Web_thue_xe đúng ngày giờ đã lên lịch.</p>
                <div class="channel-badge ready"><i class="fa-solid fa-check"></i> Sẵn sàng</div>
              </div>

              <!-- Channel 2: Facebook Page -->
              <div 
                class="channel-card" 
                :class="{ active: selectedChannel === 'facebook', disabled: !facebookStatus.is_ready }"
                @click="selectFacebookChannel('facebook')"
              >
                <div class="channel-card-header">
                  <div class="channel-icon fb"><i class="fa-brands fa-facebook"></i></div>
                  <input type="radio" value="facebook" v-model="selectedChannel" :disabled="!facebookStatus.is_ready" />
                </div>
                <h4>Chỉ Facebook Page</h4>
                <p>Đăng caption và ảnh snapshot trực tiếp lên Fanpage đã kết nối qua Meta Graph API.</p>
                <div v-if="facebookStatus.is_ready" class="channel-badge ready">
                  <i class="fa-solid fa-check"></i> {{ facebookStatus.page_name || 'Đã kết nối Page' }}
                </div>
                <div v-else class="channel-badge not-ready">
                  <i class="fa-solid fa-triangle-exclamation"></i> Chưa kết nối Fanpage
                </div>
              </div>

              <!-- Channel 3: Cả hai -->
              <div 
                class="channel-card" 
                :class="{ active: selectedChannel === 'both', disabled: !facebookStatus.is_ready }"
                @click="selectFacebookChannel('both')"
              >
                <div class="channel-card-header">
                  <div class="channel-icon both"><i class="fa-solid fa-share-nodes"></i></div>
                  <input type="radio" value="both" v-model="selectedChannel" :disabled="!facebookStatus.is_ready" />
                </div>
                <h4>Cả hai (Website & Facebook)</h4>
                <p>Đồng bộ xuất bản song song cả Website và Facebook Page. Trạng thái mỗi kênh được quản lý độc lập.</p>
                <div v-if="facebookStatus.is_ready" class="channel-badge ready">
                  <i class="fa-solid fa-check"></i> Sẵn sàng xuất bản đa kênh
                </div>
                <div v-else class="channel-badge not-ready">
                  <i class="fa-solid fa-triangle-exclamation"></i> Cần cấu hình Facebook trước
                </div>
              </div>
            </div>

            <!-- Cấu hình Chuyên mục Website -->
            <div v-if="selectedChannel === 'website' || selectedChannel === 'both'" class="form-group mt-4 category-picker-box">
              <label>Chuyên mục Website mặc định cho đợt import này:</label>
              <select v-model="defaultCategoryId" class="form-select">
                <option :value="null">-- Tự động theo bài viết --</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ==================== BƯỚC 5: TIẾN TRÌNH SNAPSHOT & HOÀN TẤT ==================== -->
        <div v-if="currentStep === 5" class="step-content">
          <div class="confirm-execution-box">
            <div v-if="executingImport" class="execution-progress">
              <div class="spinner-radar">
                <i class="fa-solid fa-cloud-arrow-down fa-spin text-emerald"></i>
              </div>
              <h3>Đang tải ảnh snapshot từ Google Drive & Lên lịch đăng bài...</h3>
              <p>Hệ thống đang tải toàn bộ ảnh gốc về lưu trữ an toàn trong máy chủ nội bộ để tránh phụ thuộc Google Drive.</p>
            </div>

            <div v-else-if="executionResult" class="execution-done">
              <div class="done-icon-wrapper">
                <i class="fa-solid fa-circle-check text-emerald"></i>
              </div>
              <h3>Lên Lịch Đăng Bài Thành Công!</h3>
              <p class="done-desc">
                Đã lập lịch thành công <strong>{{ executionResult.scheduled_count }}</strong> bài viết trong đợt 
                <strong>#BATCH-{{ executionResult.batch_id }}</strong>.
              </p>

              <div v-if="executionResult.failed_count > 0" class="failed-snapshot-warning">
                <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                <div>
                  <strong>Có {{ executionResult.failed_count }} ảnh không thể tải snapshot:</strong>
                  <ul>
                    <li v-for="(f, fIdx) in executionResult.failed_rows" :key="fIdx">
                      Dòng {{ f.row_number }} ({{ f.image_name }}): {{ f.error }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer flex-between">
        <div class="footer-left">
          <button 
            v-if="currentStep > 1 && currentStep < 5" 
            type="button" 
            class="btn-outline" 
            @click="goToPrevStep"
          >
            <i class="fa-solid fa-arrow-left"></i> Quay lại
          </button>
        </div>

        <div class="footer-right">
          <button 
            v-if="currentStep < 5" 
            type="button" 
            class="btn-outline" 
            @click="onCloseModal"
          >
            Hủy bỏ
          </button>

          <!-- Nút chuyển từ Bước 1 sang Bước 2/3 -->
          <button 
            v-if="currentStep === 1" 
            type="button" 
            class="btn-primary" 
            :disabled="!selectedFile || !driveFolderId"
            @click="runPreviewAnalysis"
          >
            <span>Tiếp tục: Phân tích & Ghép ảnh</span>
            <i class="fa-solid fa-arrow-right"></i>
          </button>

          <!-- Nút chuyển từ Bước 3 sang Bước 4 -->
          <button 
            v-if="currentStep === 3" 
            type="button" 
            class="btn-primary" 
            :disabled="selectedRowNumbers.size === 0"
            @click="goToStep4"
          >
            <span>Tiếp tục chọn kênh ({{ selectedRowNumbers.size }} dòng đã chọn)</span>
            <i class="fa-solid fa-arrow-right"></i>
          </button>

          <!-- Nút chuyển từ Bước 4 sang Xác nhận Lên Lịch (Bước 5) -->
          <button 
            v-if="currentStep === 4" 
            type="button" 
            class="btn-primary btn-emerald" 
            @click="executeConfirmImport"
          >
            <i class="fa-solid fa-calendar-check"></i>
            <span>Xác nhận & Tải ảnh snapshot ({{ selectedRowNumbers.size }} bài)</span>
          </button>

          <!-- Nút hoàn tất Bước 5 -->
          <button 
            v-if="currentStep === 5 && !executingImport" 
            type="button" 
            class="btn-primary btn-emerald" 
            @click="finishAndGoToSchedules"
          >
            <i class="fa-solid fa-calendar-days"></i>
            <span>Xem danh sách Lịch đăng bài</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Xem Toàn Văn Bài Viết -->
    <div v-if="contentModalRow" class="modal-overlay inner-modal" @click.self="contentModalRow = null">
      <div class="modal-content card inner-content-modal animate-in">
        <div class="modal-header">
          <div>
            <h3>Toàn văn dòng #{{ contentModalRow.row_number }}: {{ contentModalRow.title }}</h3>
            <p class="modal-sub">Ảnh: <code>{{ contentModalRow.image_name }}</code> • Ngày giờ: {{ contentModalRow.scheduled_display }}</p>
          </div>
          <button class="btn-close" @click="contentModalRow = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="full-content-view">
            {{ contentModalRow.content }}
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="contentModalRow = null">Đóng</button>
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
  categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'imported', 'view-schedules']);
const toaster = createToaster({ position: "top-right" });

const currentStep = ref(1);

// Step 1: File & Folder
const selectedFile = ref(null);
const fileInputRef = ref(null);
const isDragOver = ref(false);
const driveFolderId = ref('');
const serviceAccountEmail = ref('');
const testingDrive = ref(false);

// Step 3: Preview Data
const previewData = ref(null);
const previewRows = ref([]);
const previewFilter = ref('all');
const selectedRowNumbers = ref(new Set());
const contentModalRow = ref(null);

// Step 4: Channels
const selectedChannel = ref('both');
const defaultCategoryId = ref(null);
const facebookStatus = reactive({
  is_ready: false,
  page_name: '',
  page_id: '',
});

// Step 5: Execution
const executingImport = ref(false);
const executionResult = ref(null);

// Helpers
const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const onFileSelected = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    selectedFile.value = file;
  }
};

const onFileDrop = (e) => {
  isDragOver.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'xlsx' || ext === 'xls') {
      selectedFile.value = file;
    } else {
      toaster.error('Vui lòng chỉ kéo thả tệp Excel (.xlsx hoặc .xls)!');
    }
  }
};

const removeSelectedFile = () => {
  selectedFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
};

const downloadTemplate = () => {
  window.open('/api/admin/post-schedules/template', '_blank');
};

const testDriveFolder = async () => {
  if (!driveFolderId.value) {
    toaster.warning('Vui lòng nhập Drive Folder ID trước.');
    return;
  }
  testingDrive.value = true;
  try {
    const res = await baseRequest.post('admin/post-schedules/drive-test', {
      folder_id: driveFolderId.value,
    });
    if (res.data?.status === 1) {
      toaster.success(res.data.message || 'Thư mục Google Drive hợp lệ và sẵn sàng!');
    } else {
      toaster.error(res.data?.message || 'Không thể truy cập thư mục Drive.');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi kiểm tra thư mục Drive.');
  } finally {
    testingDrive.value = false;
  }
};

// Step Transitions
const runPreviewAnalysis = async () => {
  if (!selectedFile.value) {
    toaster.warning('Vui lòng chọn file Excel.');
    return;
  }
  if (!driveFolderId.value) {
    toaster.warning('Vui lòng nhập Google Drive Folder ID.');
    return;
  }

  currentStep.value = 2; // Show analyzing spinner

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('drive_folder_id', driveFolderId.value);
  formData.append('channel', selectedChannel.value);

  try {
    const res = await baseRequest.post('admin/post-schedules/preview-import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.status === 1 && res.data.data) {
      previewData.value = res.data.data;
      previewRows.value = res.data.data.rows || [];

      // Mặc định chọn tất cả các dòng hợp lệ
      selectedRowNumbers.value = new Set(
        previewRows.value.filter(r => r.status === 'valid').map(r => r.row_number)
      );

      currentStep.value = 3;
      toaster.success(`Phân tích thành công ${previewRows.value.length} dòng dữ liệu!`);
    } else {
      currentStep.value = 1;
      toaster.error(res.data?.message || 'Không thể phân tích dữ liệu Excel.');
    }
  } catch (err) {
    currentStep.value = 1;
    toaster.error(err.response?.data?.message || 'Có lỗi xảy ra khi phân tích file Excel.');
  }
};

// Computed Rows for Table
const previewValidRows = computed(() => previewRows.value.filter(r => r.status === 'valid'));
const previewErrorRows = computed(() => previewRows.value.filter(r => r.status !== 'valid'));

const displayedPreviewRows = computed(() => {
  if (previewFilter.value === 'valid') return previewValidRows.value;
  if (previewFilter.value === 'error') return previewErrorRows.value;
  return previewRows.value;
});

const isAllSelected = computed(() => {
  return displayedPreviewRows.value.length > 0 &&
    displayedPreviewRows.value.every(r => selectedRowNumbers.value.has(r.row_number));
});

const toggleSelectAll = (checked) => {
  const newSet = new Set(selectedRowNumbers.value);
  displayedPreviewRows.value.forEach(r => {
    if (checked) {
      newSet.add(r.row_number);
    } else {
      newSet.delete(r.row_number);
    }
  });
  selectedRowNumbers.value = newSet;
};

const toggleRowSelection = (rowNum) => {
  const newSet = new Set(selectedRowNumbers.value);
  if (newSet.has(rowNum)) {
    newSet.delete(rowNum);
  } else {
    newSet.add(rowNum);
  }
  selectedRowNumbers.value = newSet;
};

const uncheckAllErrors = () => {
  const newSet = new Set(selectedRowNumbers.value);
  previewErrorRows.value.forEach(r => newSet.delete(r.row_number));
  selectedRowNumbers.value = newSet;
  toaster.info(`Đã bỏ chọn ${previewErrorRows.value.length} dòng có lỗi.`);
};

const openContentModal = (row) => {
  contentModalRow.value = row;
};

// Step 4
const goToStep4 = () => {
  if (selectedRowNumbers.value.size === 0) {
    toaster.warning('Vui lòng chọn ít nhất 1 bài viết hợp lệ để tiếp tục.');
    return;
  }
  currentStep.value = 4;
};

const selectFacebookChannel = (ch) => {
  if (!facebookStatus.is_ready) {
    toaster.warning('Facebook Page chưa được kết nối hợp lệ. Vui lòng cấu hình Page Token trước.');
    return;
  }
  selectedChannel.value = ch;
};

const goToPrevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

// Step 5: Execute Import
const executeConfirmImport = async () => {
  const rowsToSchedule = previewRows.value.filter(r => selectedRowNumbers.value.has(r.row_number));

  if (rowsToSchedule.length === 0) {
    toaster.warning('Không có bài viết nào được chọn để lên lịch.');
    return;
  }

  // Chặn nếu có dòng lỗi mà người dùng vẫn cố chọn
  const hasErrorRowsSelected = rowsToSchedule.some(r => r.status !== 'valid');
  if (hasErrorRowsSelected) {
    if (!confirm('Một số dòng được chọn đang có lỗi hoặc thiếu ảnh Drive. Hệ thống sẽ bỏ qua các dòng lỗi này. Bạn có muốn tiếp tục?')) {
      return;
    }
  }

  currentStep.value = 5;
  executingImport.value = true;
  executionResult.value = null;

  const validRowsPayload = rowsToSchedule
    .filter(r => r.drive_match?.file?.id)
    .map(r => ({
      row_number: r.row_number,
      content: r.content,
      title: r.title || r.title_suggested,
      title_suggested: r.title_suggested,
      image_name: r.image_name,
      drive_file_id: r.drive_match.file.id,
      scheduled_at: r.scheduled_at,
      fingerprint: r.fingerprint,
    }));

  try {
    const res = await baseRequest.post('admin/post-schedules/confirm-import', {
      file_name: selectedFile.value?.name || 'import.xlsx',
      drive_folder_id: driveFolderId.value,
      channel: selectedChannel.value,
      default_category_id: defaultCategoryId.value,
      rows: validRowsPayload,
    });

    if (res.data?.status === 1) {
      executionResult.value = res.data.data;
      toaster.success(res.data.message || 'Lên lịch đăng bài thành công!');
      emit('imported');
    } else {
      toaster.error(res.data?.message || 'Lỗi khi lên lịch đăng bài.');
      currentStep.value = 4;
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Có lỗi xảy ra khi xác nhận import.');
    currentStep.value = 4;
  } finally {
    executingImport.value = false;
  }
};

const finishAndGoToSchedules = () => {
  emit('view-schedules');
  emit('close');
};

const onCloseModal = () => {
  if (executingImport.value) {
    toaster.warning('Đang trong quá trình tải snapshot ảnh, vui lòng không đóng cửa sổ.');
    return;
  }
  emit('close');
};

// Load Initial Settings
onMounted(async () => {
  try {
    const [driveRes, fbRes] = await Promise.all([
      baseRequest.get('admin/post-schedules/drive-config'),
      baseRequest.get('admin/post-schedules/facebook-config'),
    ]);

    if (driveRes.data?.data) {
      driveFolderId.value = driveRes.data.data.folder_id || '';
      serviceAccountEmail.value = driveRes.data.data.service_account_email || '';
    }

    if (fbRes.data?.data) {
      Object.assign(facebookStatus, fbRes.data.data);
      if (!facebookStatus.is_ready) {
        selectedChannel.value = 'website'; // Fallback nếu chưa kết nối FB
      }
    }
  } catch (err) {
    console.error('PostImportModal init error:', err);
  }
});
</script>

<style scoped>
.post-import-modal {
  max-width: 1100px;
  width: 95%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md);
  background: #ECFDF5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

/* Stepper */
.import-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 28px;
  background: var(--bg-surface-alt);
  border-bottom: 1px solid var(--color-border);
}

.step-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-muted);
  font-size: 0.82rem;
  font-weight: 600;
}

.step-num {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 1px solid var(--color-border);
  background: var(--bg-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.76rem;
}

.step-item.active {
  color: var(--color-primary);
}

.step-item.active .step-num {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.step-item.done {
  color: #059669;
}

.step-item.done .step-num {
  background: #ECFDF5;
  color: #059669;
  border-color: #A7F3D0;
}

.step-line {
  flex: 1;
  height: 2px;
  background: var(--color-border);
  margin: 0 12px;
}

.step-line.done {
  background: #059669;
}

/* Step 1 */
.template-download-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: var(--radius-sm);
  margin-bottom: 16px;
}

.banner-text {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.84rem;
  color: #065F46;
}

.text-emerald {
  color: #059669 !important;
}

.btn-download-tpl {
  padding: 6px 14px;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.file-dropzone {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: 36px 20px;
  text-align: center;
  background: var(--bg-surface-alt);
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-dropzone:hover, .file-dropzone.drag-over {
  border-color: var(--color-primary);
  background: rgba(30, 58, 138, 0.03);
}

.drop-icon {
  font-size: 2.5rem;
  color: var(--color-primary);
  margin-bottom: 10px;
}

.dropzone-empty h4 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 6px;
}

.dropzone-empty p {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin-bottom: 14px;
}

.btn-browse {
  padding: 8px 18px;
  background: var(--color-primary);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.84rem;
  cursor: pointer;
}

.dropzone-selected {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  background: var(--bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
}

.file-info-badge {
  display: flex;
  align-items: center;
  gap: 12px;
}

.file-badge-icon {
  font-size: 1.8rem;
  color: #059669;
}

.file-text {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.file-name {
  font-weight: 700;
  font-size: 0.9rem;
}

.file-size {
  font-size: 0.78rem;
  color: var(--color-text-muted);
}

.btn-remove-file {
  padding: 6px 12px;
  background: transparent;
  border: 1px solid #FECACA;
  color: #DC2626;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  cursor: pointer;
}

.input-with-button {
  display: flex;
  gap: 8px;
}

/* Step 2: Analyzing */
.step-analyzing {
  padding: 50px 20px;
  text-align: center;
}

.spinner-radar {
  font-size: 3rem;
  margin-bottom: 16px;
}

.analyzing-card h3 {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.analyzing-card p {
  color: var(--color-text-muted);
  max-width: 500px;
  margin: 0 auto;
  font-size: 0.86rem;
}

/* Step 3: Preview Table */
.preview-stats-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.stat-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-weight: 600;
}

.stat-pill.total {
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
}

.stat-pill.valid {
  background: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

.stat-pill.error {
  background: #FEF2F2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

.stat-pill.skipped {
  background: #F1F5F9;
  color: #475569;
}

.preview-filters-right {
  margin-left: auto;
  display: flex;
  gap: 8px;
}

.btn-filter-toggle {
  padding: 5px 10px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-filter-toggle.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.btn-outline-danger {
  padding: 5px 10px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  color: #DC2626;
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}

.preview-table-container {
  overflow-x: auto;
  max-height: 480px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
}

.preview-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.preview-table th {
  background: var(--bg-surface-alt);
  padding: 10px 12px;
  border-bottom: 2px solid var(--color-border);
  font-weight: 700;
  position: sticky;
  top: 0;
  z-index: 2;
}

.preview-table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.row-error {
  background: #FFFDFD;
}

.row-unselected {
  opacity: 0.55;
}

.row-num-badge {
  font-weight: 700;
  color: var(--color-text-muted);
}

.preview-img-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.preview-img-thumb {
  width: 44px;
  height: 30px;
  border-radius: 4px;
  background: #000;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  flex-shrink: 0;
}

.preview-img-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-img-thumb.thumb-err {
  background: #FEE2E2;
  color: #DC2626;
}

.img-meta {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.img-name {
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 110px;
}

.match-tag {
  font-size: 0.7rem;
  font-weight: 700;
}

.schedule-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.table-input {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 10px;
}

.content-snippet {
  margin: 0;
  font-size: 0.78rem;
  line-height: 1.4;
  color: var(--color-text-main);
}

.btn-read-more {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.74rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-top: 2px;
  text-decoration: underline;
}

.badge-status-valid {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #065F46;
  font-weight: 700;
  background: #ECFDF5;
  padding: 3px 8px;
  border-radius: 4px;
}

.badge-status-error {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #DC2626;
  font-weight: 700;
  background: #FEE2E2;
  padding: 3px 8px;
  border-radius: 4px;
  cursor: help;
}

.row-error-list {
  text-align: left;
  margin-top: 4px;
  display: flex;
  flex-direction: column;
}

.error-msg {
  color: #DC2626;
  font-size: 0.7rem;
  line-height: 1.3;
}

/* Step 4: Channels */
.channel-selection-wrapper {
  padding: 10px 0;
}

.channel-selection-wrapper h3 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.sub-text {
  font-size: 0.84rem;
  color: var(--color-text-muted);
  margin-bottom: 20px;
}

.channels-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.channel-card {
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--bg-surface);
  position: relative;
}

.channel-card:hover:not(.disabled) {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.channel-card.active {
  border-color: var(--color-primary);
  background: rgba(30, 58, 138, 0.04);
}

.channel-card.disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: var(--bg-surface-alt);
}

.channel-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.channel-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.channel-icon.web {
  background: #EEF2FF;
  color: #4F46E5;
}

.channel-icon.fb {
  background: #EFF6FF;
  color: #2563EB;
}

.channel-icon.both {
  background: #ECFDF5;
  color: #059669;
}

.channel-card h4 {
  font-size: 0.96rem;
  font-weight: 700;
  margin-bottom: 6px;
}

.channel-card p {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  margin-bottom: 14px;
}

.channel-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.channel-badge.ready {
  background: #ECFDF5;
  color: #065F46;
}

.channel-badge.not-ready {
  background: #FEF2F2;
  color: #DC2626;
}

.category-picker-box {
  background: var(--bg-surface-alt);
  padding: 14px 18px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

/* Step 5 */
.confirm-execution-box {
  padding: 40px 20px;
  text-align: center;
}

.done-icon-wrapper {
  font-size: 3.5rem;
  margin-bottom: 14px;
}

.done-desc {
  font-size: 1rem;
  color: var(--color-text-main);
  margin-bottom: 20px;
}

.failed-snapshot-warning {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 18px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  border-radius: var(--radius-sm);
  text-align: left;
  max-width: 600px;
  margin: 0 auto;
  font-size: 0.84rem;
  color: #92400E;
}

.failed-snapshot-warning ul {
  padding-left: 18px;
  margin-top: 6px;
}

.btn-emerald {
  background: #059669 !important;
  border-color: #047857 !important;
  color: #fff !important;
}

.btn-emerald:hover {
  background: #047857 !important;
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.footer-left, .footer-right {
  display: flex;
  gap: 10px;
}

/* Inner content modal */
.inner-modal {
  z-index: 1100;
}

.inner-content-modal {
  max-width: 600px;
}

.full-content-view {
  white-space: pre-wrap;
  line-height: 1.6;
  font-size: 0.88rem;
  background: var(--bg-surface-alt);
  padding: 16px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}
</style>
