<template>
  <div v-if="visible" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content card schedule-edit-modal animate-in">
      <div class="modal-header">
        <div>
          <h3><i class="fa-solid fa-clock-rotate-left text-primary"></i> Điều Chỉnh Lịch & Nội Dung Bài Viết</h3>
          <p class="modal-sub">Chỉnh sửa tiêu đề, nội dung hoặc dời lịch trước khi hệ thống tự động xuất bản</p>
        </div>
        <button class="btn-close" @click="$emit('close')"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="modal-body" v-if="form">
        <!-- Thông tin ảnh snapshot hiện tại -->
        <div class="snapshot-preview-strip">
          <div class="strip-img-box">
            <img :src="schedule?.snapshot_url" alt="Snapshot" class="strip-img" />
          </div>
          <div class="strip-info">
            <div class="strip-badge-row">
              <span class="badge-channel" :class="'channel-' + schedule?.channel">
                Kênh: {{ getChannelLabel(schedule?.channel) }}
              </span>
              <span class="badge-drive-file">
                <i class="fa-brands fa-google-drive"></i> {{ schedule?.drive_image_name }}
              </span>
            </div>
            <p class="strip-sub">Ảnh snapshot đã được lưu an toàn trên máy chủ nội bộ.</p>
          </div>
        </div>

        <div class="form-group mt-3">
          <label>Tiêu đề bài viết (Hiển thị trên Website) <span class="required">*</span></label>
          <input 
            type="text" 
            v-model="form.title" 
            placeholder="Nhập tiêu đề hấp dẫn cho bài viết..." 
            class="form-input"
          />
        </div>

        <div class="form-group mt-3">
          <label>Chuyên mục Website</label>
          <select v-model="form.category_id" class="form-select">
            <option :value="null">-- Mặc định theo đợt --</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>

        <div class="form-group mt-3">
          <label>Thời gian đăng bài (Giờ Việt Nam) <span class="required">*</span></label>
          <input 
            type="datetime-local" 
            v-model="form.scheduled_at" 
            class="form-input"
          />
          <small class="helper-text">Thời gian đăng mới bắt buộc phải ở trong tương lai.</small>
        </div>

        <div class="form-group mt-3">
          <label>Nội dung bài viết (Caption Facebook & Nội dung Web) <span class="required">*</span></label>
          <textarea 
            v-model="form.content" 
            rows="6" 
            class="form-textarea"
            placeholder="Nội dung bài viết giới thiệu phương tiện..."
          ></textarea>
        </div>
      </div>

      <div class="modal-footer flex-between">
        <button type="button" class="btn-outline" @click="$emit('close')">Hủy bỏ</button>
        <button type="button" class="btn-primary" @click="submitUpdate" :disabled="saving">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>{{ saving ? 'Đang lưu...' : 'Lưu thay đổi lịch' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import baseRequest from '../../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const props = defineProps({
  visible: { type: Boolean, default: false },
  schedule: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated']);
const toaster = createToaster({ position: "top-right" });

const saving = ref(false);
const form = reactive({
  title: '',
  content: '',
  scheduled_at: '',
  category_id: null,
});

watch(() => props.schedule, (sc) => {
  if (sc) {
    form.title = sc.title || sc.title_suggested || '';
    form.content = sc.content || '';
    form.category_id = sc.category_id || null;

    if (sc.scheduled_at) {
      const d = new Date(sc.scheduled_at);
      const tzOffset = d.getTimezoneOffset() * 60000;
      const localISOTime = (new Date(d.getTime() - tzOffset)).toISOString().slice(0, 16);
      form.scheduled_at = localISOTime;
    }
  }
}, { immediate: true });

const getChannelLabel = (ch) => {
  if (ch === 'website') return 'Website';
  if (ch === 'facebook') return 'Facebook Page';
  return 'Cả hai (Web & Facebook)';
};

const submitUpdate = async () => {
  if (!form.title?.trim()) {
    toaster.warning('Vui lòng nhập tiêu đề bài viết.');
    return;
  }
  if (!form.content?.trim()) {
    toaster.warning('Vui lòng nhập nội dung bài viết.');
    return;
  }
  if (!form.scheduled_at) {
    toaster.warning('Vui lòng chọn thời gian đăng hợp lệ.');
    return;
  }

  saving.value = true;
  try {
    const res = await baseRequest.put(`admin/post-schedules/${props.schedule.id}`, {
      title: form.title,
      content: form.content,
      scheduled_at: form.scheduled_at,
      category_id: form.category_id,
    });

    if (res.data?.status === 1) {
      toaster.success('Đã cập nhật lịch đăng thành công!');
      emit('updated');
      emit('close');
    } else {
      toaster.error(res.data?.message || 'Không thể cập nhật lịch đăng.');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi cập nhật lịch đăng.');
  } finally {
    saving.value = false;
  }
};
</script>

<style scoped>
.schedule-edit-modal {
  max-width: 680px;
  width: 95%;
}

.snapshot-preview-strip {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
  background: var(--bg-surface-alt);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
}

.strip-img-box {
  width: 80px;
  height: 52px;
  border-radius: 4px;
  overflow: hidden;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.strip-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.strip-badge-row {
  display: flex;
  gap: 8px;
  margin-bottom: 4px;
}

.badge-channel {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
}

.badge-channel.channel-website {
  background: #E0E7FF;
  color: #3730A3;
}

.badge-channel.channel-facebook {
  background: #DBEAFE;
  color: #1E40AF;
}

.badge-channel.channel-both {
  background: #D1FAE5;
  color: #065F46;
}

.badge-drive-file {
  font-size: 0.72rem;
  font-family: monospace;
  background: #F1F5F9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 4px;
}

.strip-sub {
  margin: 0;
  font-size: 0.78rem;
  color: var(--color-text-muted);
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}
</style>
