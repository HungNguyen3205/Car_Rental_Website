<template>
  <div v-if="visible" class="media-picker-backdrop" @click.self="handleClose">
    <div class="media-picker-modal card animate-in">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon-badge">
            <i class="fa-solid fa-photo-film"></i>
          </div>
          <div>
            <h3 class="modal-title">{{ title || 'Thư Viện Tài Nguyên Dùng Chung' }}</h3>
            <p class="modal-subtitle">
              {{ multiple ? 'Chọn một hoặc nhiều tệp để chèn vào nội dung' : 'Chọn một tệp phù hợp từ thư viện' }}
            </p>
          </div>
        </div>

        <button class="btn-close-modal" @click="handleClose" title="Đóng cửa sổ">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="picker-tabs">
        <button 
          type="button" 
          class="tab-btn" 
          :class="{ active: activeTab === 'library' }"
          @click="activeTab = 'library'"
        >
          <i class="fa-solid fa-grip"></i>
          <span>Thư viện có sẵn</span>
          <span v-if="totalFilesCount > 0" class="tab-count-badge">{{ totalFilesCount }}</span>
        </button>

        <button 
          type="button" 
          class="tab-btn" 
          :class="{ active: activeTab === 'upload' }"
          @click="activeTab = 'upload'"
        >
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <span>Tải tệp mới lên máy chủ</span>
        </button>
      </div>

      <!-- Tab 1: Library Browser -->
      <div v-if="activeTab === 'library'" class="picker-body library-view">
        <!-- Filter Toolbar -->
        <div class="toolbar-row">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Tìm theo tên file, ghi chú, alt text..."
              @input="onSearchInput"
            />
            <button v-if="searchQuery" class="clear-search" @click="clearSearch">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="filter-controls">
            <select v-model="filterCollection" class="picker-select" @change="fetchMedia(1)">
              <option value="all">Tất cả nhóm</option>
              <option value="cars">Xe & Ngoại/Nội thất</option>
              <option value="marketing">Marketing & Banner</option>
              <option value="general">Tài nguyên chung</option>
              <option value="receipts">Biên lai (Riêng tư)</option>
              <option value="handovers">Giao nhận (Riêng tư)</option>
            </select>

            <select v-model="filterVisibility" class="picker-select" @change="fetchMedia(1)">
              <option value="all">Mọi chế độ xem</option>
              <option value="public">Công khai (Public)</option>
              <option value="private">Nội bộ (Private)</option>
            </select>

            <button class="btn-refresh-picker" @click="fetchMedia(pagination.current_page)" :disabled="loading" title="Tải lại">
              <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
            </button>
          </div>
        </div>

        <!-- Grid Container -->
        <div class="media-grid-container" :class="{ 'is-loading': loading }">
          <div v-if="loading && items.length === 0" class="loading-state">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <p>Đang tải danh sách tài nguyên...</p>
          </div>

          <div v-else-if="items.length === 0" class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <h4>Không tìm thấy tệp nào phù hợp</h4>
            <p>Thử tìm kiếm với từ khóa khác hoặc chuyển sang tab "Tải tệp mới" để thêm ảnh vào hệ thống.</p>
          </div>

          <div v-else class="media-items-grid">
            <div 
              v-for="item in items" 
              :key="item.id" 
              class="media-card"
              :class="{ 
                selected: isSelected(item),
                'is-private': item.visibility === 'private'
              }"
              @click="toggleSelect(item)"
            >
              <!-- Selection Checkbox / Indicator -->
              <div class="select-indicator">
                <i v-if="isSelected(item)" class="fa-solid fa-check"></i>
              </div>

              <!-- Media Thumbnail or Icon -->
              <div class="card-thumb">
                <img 
                  v-if="item.is_image" 
                  :src="item.display_url" 
                  :alt="item.alt_text || item.original_name"
                  loading="lazy"
                  @error="onImageError($event)"
                />
                <div v-else class="file-icon-box">
                  <i class="fa-solid fa-file-pdf"></i>
                  <span class="file-ext">PDF</span>
                </div>

                <!-- Visibility Badge -->
                <span class="visibility-chip" :class="item.visibility">
                  <i :class="item.visibility === 'public' ? 'fa-solid fa-globe' : 'fa-solid fa-lock'"></i>
                  {{ item.visibility === 'public' ? 'Public' : 'Private' }}
                </span>
              </div>

              <!-- Media Info -->
              <div class="card-info">
                <div class="file-name" :title="item.original_name">{{ item.original_name }}</div>
                <div class="file-meta">
                  <span v-if="item.width && item.height">{{ item.width }}x{{ item.height }}</span>
                  <span class="meta-dot" v-if="item.width && item.height">•</span>
                  <span>{{ item.formatted_size }}</span>
                </div>
                <div v-if="item.in_use" class="in-use-pill" :title="'Đang dùng ở ' + item.references_count + ' vị trí'">
                  <i class="fa-solid fa-link"></i> Đang dùng ({{ item.references_count }})
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Controls -->
        <div v-if="pagination.total > 0" class="picker-pagination">
          <span class="pagination-summary">
            Trang {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }} tệp)
          </span>
          <div class="pagination-buttons">
            <button 
              type="button" 
              class="btn-page" 
              :disabled="pagination.current_page <= 1"
              @click="fetchMedia(pagination.current_page - 1)"
            >
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="page-current">{{ pagination.current_page }}</span>
            <button 
              type="button" 
              class="btn-page" 
              :disabled="pagination.current_page >= pagination.last_page"
              @click="fetchMedia(pagination.current_page + 1)"
            >
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab 2: Upload Files -->
      <div v-else class="picker-body upload-view">
        <div 
          class="upload-dropzone" 
          :class="{ 'drag-over': isDragging }"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          @click="triggerFileInput"
        >
          <input 
            type="file" 
            ref="fileInputRef" 
            :multiple="multiple" 
            accept="image/jpeg,image/png,image/webp,application/pdf"
            @change="handleFileInputChange" 
            hidden
          />
          <div class="dropzone-content">
            <div class="dropzone-icon">
              <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <h4>Kéo thả tệp vào đây hoặc bấm để duyệt từ máy tính</h4>
            <p class="dropzone-hint">
              Hỗ trợ JPG, PNG, WEBP (chuẩn hóa ảnh xe, banner) và PDF (tài liệu hợp đồng). Tối đa 10MB/tệp.
            </p>
          </div>
        </div>

        <!-- Upload Settings & Queue -->
        <div class="upload-options-card">
          <div class="options-row">
            <div class="option-field">
              <label>Phân loại nhóm tệp:</label>
              <select v-model="uploadCollection">
                <option value="cars">Xe & Đội xe (Cars)</option>
                <option value="marketing">Marketing & Banner</option>
                <option value="general">Tài nguyên chung (General)</option>
                <option value="receipts">Biên lai thanh toán (Private)</option>
                <option value="handovers">Biên bản giao nhận (Private)</option>
              </select>
            </div>

            <div class="option-field">
              <label>Chế độ hiển thị:</label>
              <select v-model="uploadVisibility" :disabled="uploadCollection === 'receipts' || uploadCollection === 'handovers'">
                <option value="public">Công khai trên Website (Public)</option>
                <option value="private">Nội bộ có phân quyền (Private)</option>
              </select>
            </div>
          </div>

          <!-- Pending Files Queue -->
          <div v-if="queuedFiles.length > 0" class="queued-files-box">
            <div class="queue-header">
              <span>Danh sách tệp chờ tải lên ({{ queuedFiles.length }} tệp)</span>
              <button type="button" class="btn-clear-queue" @click="queuedFiles = []">Xóa tất cả</button>
            </div>
            <div class="queue-list">
              <div v-for="(file, idx) in queuedFiles" :key="idx" class="queue-item">
                <div class="queue-item-name">
                  <i class="fa-solid fa-image"></i>
                  <span>{{ file.name }}</span>
                </div>
                <div class="queue-item-meta">
                  <span class="file-size">{{ formatBytes(file.size) }}</span>
                  <button type="button" class="btn-remove-file" @click="removeQueuedFile(idx)">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="upload-action-bar">
              <button 
                type="button" 
                class="btn-start-upload" 
                :disabled="isUploading"
                @click="startUploadQueue"
              >
                <i v-if="isUploading" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-cloud-arrow-up"></i>
                <span>{{ isUploading ? 'Đang tải lên hệ thống...' : `Tải lên ${queuedFiles.length} tệp` }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <div class="footer-left">
          <span v-if="selectedItems.length > 0" class="selection-count">
            Đã chọn <strong>{{ selectedItems.length }}</strong> tệp
          </span>
          <span v-else class="selection-empty">Chưa chọn tệp nào</span>
        </div>

        <div class="footer-actions">
          <button type="button" class="btn-cancel" @click="handleClose">Hủy bỏ</button>
          <button 
            type="button" 
            class="btn-confirm" 
            :disabled="selectedItems.length === 0"
            @click="handleConfirmSelect"
          >
            <i class="fa-solid fa-check"></i>
            <span>{{ multiple ? `Chèn ${selectedItems.length} ảnh đã chọn` : 'Sử dụng ảnh này' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  collection: {
    type: String,
    default: 'all',
  },
  title: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'select']);

// Tabs
const activeTab = ref('library');

// Library State
const loading = ref(false);
const items = ref([]);
const totalFilesCount = ref(0);
const searchQuery = ref('');
const filterCollection = ref(props.collection || 'all');
const filterVisibility = ref('all');
let searchTimer = null;

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 24,
  total: 0,
});

// Selection State
const selectedItems = ref([]);

// Upload State
const isDragging = ref(false);
const isUploading = ref(false);
const fileInputRef = ref(null);
const queuedFiles = ref([]);
const uploadCollection = ref(props.collection !== 'all' ? props.collection : 'cars');
const uploadVisibility = ref('public');

// Watch visibility to auto-fetch
watch(() => props.visible, (newVal) => {
  if (newVal) {
    selectedItems.value = [];
    activeTab.value = 'library';
    if (props.collection && props.collection !== 'all') {
      filterCollection.value = props.collection;
      uploadCollection.value = props.collection;
    }
    fetchMedia(1);
  }
});

watch(uploadCollection, (newCol) => {
  if (newCol === 'receipts' || newCol === 'handovers') {
    uploadVisibility.value = 'private';
  } else {
    uploadVisibility.value = 'public';
  }
});

// Fetch Media from API
const fetchMedia = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page: page,
      per_page: pagination.per_page,
    };
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }
    if (filterCollection.value !== 'all') {
      params.collection = filterCollection.value;
    }
    if (filterVisibility.value !== 'all') {
      params.visibility = filterVisibility.value;
    }

    const response = await baseRequest.get('/admin/media', { params });
    if (response.data && response.data.status === 1) {
      items.value = response.data.data || [];
      if (response.data.meta) {
        pagination.current_page = response.data.meta.current_page;
        pagination.last_page = response.data.meta.last_page;
        pagination.total = response.data.meta.total;
        totalFilesCount.value = response.data.meta.total;
      }
    }
  } catch (error) {
    console.error('Fetch media error:', error);
    toaster.error('Không thể tải thư viện tài nguyên');
  } finally {
    loading.value = false;
  }
};

const onSearchInput = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    fetchMedia(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchMedia(1);
};

// Selection Helpers
const isSelected = (item) => {
  return selectedItems.value.some(s => s.id === item.id);
};

const toggleSelect = (item) => {
  if (props.multiple) {
    const idx = selectedItems.value.findIndex(s => s.id === item.id);
    if (idx >= 0) {
      selectedItems.value.splice(idx, 1);
    } else {
      selectedItems.value.push(item);
    }
  } else {
    if (isSelected(item)) {
      selectedItems.value = [];
    } else {
      selectedItems.value = [item];
    }
  }
};

const handleConfirmSelect = () => {
  if (selectedItems.value.length === 0) return;

  if (props.multiple) {
    emit('select', selectedItems.value);
  } else {
    emit('select', selectedItems.value[0]);
  }
  handleClose();
};

const handleClose = () => {
  selectedItems.value = [];
  emit('close');
};

// Upload Handlers
const triggerFileInput = () => {
  if (fileInputRef.value) {
    fileInputRef.value.click();
  }
};

const handleFileInputChange = (e) => {
  const files = Array.from(e.target.files || []);
  addFilesToQueue(files);
  e.target.value = '';
};

const handleDrop = (e) => {
  isDragging.value = false;
  const files = Array.from(e.dataTransfer.files || []);
  addFilesToQueue(files);
};

const addFilesToQueue = (files) => {
  const validFiles = files.filter(f => {
    if (f.size > 10 * 1024 * 1024) {
      toaster.warning(`Tệp ${f.name} vượt quá 10MB và bị bỏ qua.`);
      return false;
    }
    return true;
  });

  if (props.multiple) {
    queuedFiles.value.push(...validFiles);
  } else {
    queuedFiles.value = validFiles.slice(0, 1);
  }
};

const removeQueuedFile = (idx) => {
  queuedFiles.value.splice(idx, 1);
};

const startUploadQueue = async () => {
  if (queuedFiles.value.length === 0) return;

  isUploading.value = true;
  const formData = new FormData();
  queuedFiles.value.forEach(f => {
    formData.append('files[]', f);
  });
  formData.append('collection', uploadCollection.value);
  formData.append('visibility', uploadVisibility.value);

  try {
    const response = await baseRequest.post('/admin/media', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (response.data && response.data.status === 1) {
      toaster.success(response.data.message || 'Tải lên thành công!');
      const newAssets = response.data.data || [];
      queuedFiles.value = [];
      activeTab.value = 'library';
      await fetchMedia(1);

      // Auto-select newly uploaded items
      if (newAssets.length > 0) {
        if (props.multiple) {
          selectedItems.value = [...newAssets];
        } else {
          selectedItems.value = [newAssets[0]];
        }
      }
    } else {
      toaster.error(response.data.message || 'Tải tệp thất bại');
    }
  } catch (error) {
    const msg = error.response?.data?.message || 'Có lỗi xảy ra khi tải tệp lên máy chủ.';
    toaster.error(msg);
  } finally {
    isUploading.value = false;
  }
};

const onImageError = (e) => {
  e.target.src = '/assets/images/placeholder-car.png';
};

const formatBytes = (bytes) => {
  if (!bytes) return '0 B';
  if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(1) + ' MB';
  }
  return (bytes / 1024).toFixed(0) + ' KB';
};

onMounted(() => {
  if (props.visible) {
    fetchMedia(1);
  }
});
</script>

<style scoped>
.media-picker-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(6px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.media-picker-modal {
  width: 100%;
  max-width: 1100px;
  height: 88vh;
  max-height: 850px;
  background: #ffffff;
  border-radius: 18px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.modal-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-subtitle {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0.15rem 0 0 0;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
}

.btn-close-modal:hover {
  color: #0f172a;
  background: #e2e8f0;
}

/* Tabs */
.picker-tabs {
  display: flex;
  border-bottom: 1px solid #e2e8f0;
  background: #ffffff;
  padding: 0 1.75rem;
  gap: 0.5rem;
}

.tab-btn {
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  padding: 0.9rem 1.25rem;
  font-weight: 600;
  font-size: 0.95rem;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #2563eb;
}

.tab-btn.active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.tab-count-badge {
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

/* Body */
.picker-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f8fafc;
}

/* Toolbar */
.toolbar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.75rem;
  gap: 1rem;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
}

.search-box {
  flex: 1;
  max-width: 450px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 0.9rem;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 0.55rem 2rem 0.55rem 2.4rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.2s;
}

.search-box input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.clear-search {
  position: absolute;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.picker-select {
  padding: 0.55rem 1rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.88rem;
  background: #ffffff;
  color: #334155;
  outline: none;
  cursor: pointer;
}

.btn-refresh-picker {
  padding: 0.55rem 0.85rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 8px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh-picker:hover {
  background: #f1f5f9;
  color: #0f172a;
}

/* Grid */
.media-grid-container {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 1.75rem;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  color: #64748b;
  text-align: center;
}

.loading-state i {
  font-size: 2.2rem;
  color: #2563eb;
  margin-bottom: 1rem;
}

.empty-state i {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.media-items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.25rem;
}

.media-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  position: relative;
  display: flex;
  flex-direction: column;
}

.media-card:hover {
  transform: translateY(-2px);
  border-color: #93c5fd;
  box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.08);
}

.media-card.selected {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

.select-indicator {
  position: absolute;
  top: 8px;
  left: 8px;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.9);
  border: 1.5px solid #cbd5e1;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.media-card.selected .select-indicator {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  font-size: 0.8rem;
}

.card-thumb {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 10;
  background: #f1f5f9;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.media-card:hover .card-thumb img {
  transform: scale(1.05);
}

.file-icon-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  color: #dc2626;
  font-size: 2rem;
}

.file-ext {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
}

.visibility-chip {
  position: absolute;
  bottom: 6px;
  right: 6px;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.visibility-chip.public {
  background: rgba(16, 185, 129, 0.9);
  color: #ffffff;
}

.visibility-chip.private {
  background: rgba(245, 158, 11, 0.95);
  color: #ffffff;
}

.card-info {
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.file-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-meta {
  font-size: 0.75rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.meta-dot {
  font-weight: bold;
}

.in-use-pill {
  margin-top: 0.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.7rem;
  font-weight: 600;
  color: #0369a1;
  background: #e0f2fe;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  width: fit-content;
}

/* Pagination */
.picker-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.75rem;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.pagination-summary {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-page {
  padding: 0.4rem 0.7rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 6px;
  color: #334155;
  cursor: pointer;
}

.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-current {
  font-weight: 700;
  font-size: 0.9rem;
  padding: 0 0.5rem;
  color: #2563eb;
}

/* Upload View */
.upload-view {
  padding: 1.75rem;
  overflow-y: auto;
  gap: 1.5rem;
}

.upload-dropzone {
  border: 2px dashed #94a3b8;
  border-radius: 14px;
  background: #ffffff;
  padding: 2.5rem 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-dropzone:hover, .upload-dropzone.drag-over {
  border-color: #2563eb;
  background: #eff6ff;
}

.dropzone-icon {
  font-size: 3rem;
  color: #2563eb;
  margin-bottom: 0.75rem;
}

.dropzone-content h4 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.35rem 0;
}

.dropzone-hint {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

.upload-options-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
}

.options-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.option-field label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.35rem;
}

.option-field select {
  width: 100%;
  padding: 0.55rem 0.85rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
}

.queued-files-box {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.queue-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.88rem;
  font-weight: 600;
  color: #1e293b;
}

.btn-clear-queue {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 0.8rem;
  cursor: pointer;
}

.queue-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 180px;
  overflow-y: auto;
}

.queue-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 0.85rem;
}

.queue-item-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #334155;
}

.queue-item-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-remove-file {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}

.btn-remove-file:hover {
  color: #ef4444;
}

.upload-action-bar {
  margin-top: 1.25rem;
  display: flex;
  justify-content: flex-end;
}

.btn-start-upload {
  background: #2563eb;
  color: #ffffff;
  border: none;
  padding: 0.65rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-start-upload:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-start-upload:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.75rem;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.selection-count {
  font-size: 0.9rem;
  color: #2563eb;
}

.selection-empty {
  font-size: 0.85rem;
  color: #94a3b8;
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-cancel {
  padding: 0.6rem 1.25rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #f1f5f9;
}

.btn-confirm {
  padding: 0.6rem 1.5rem;
  background: #2563eb;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-confirm:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes animateIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-in {
  animation: animateIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
