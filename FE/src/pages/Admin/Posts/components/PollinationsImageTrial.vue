<template>
  <section class="polli-trial" aria-label="Tạo ảnh xe thử bằng Pollinations">
    <button type="button" class="trial-toggle" :aria-expanded="expanded" @click="expanded = !expanded">✦ Tạo ảnh xe thử bằng Pollinations <span>{{ expanded ? '−' : '+' }}</span></button>
    <div v-if="expanded" class="trial-body">
      <p>Ảnh AI chỉ là minh họa, có thể sai mẫu xe, logo hoặc chi tiết. Không tự đăng Facebook hay thay ảnh xe thật.</p>
      <p v-if="config && (!config.enabled || !config.configured)" class="trial-warning">Chưa cấu hình máy chủ. Xem BE/docs/POLLINATIONS_TRIAL.md để bật và chạy worker.</p>
      <p v-else-if="config">Chế độ {{ config.mode }} · Tối đa {{ config.daily_limit }} lượt/người/ngày. {{ config.mode === 'classic' ? 'Classic có logo, giới hạn lượt và không bảo đảm thời gian.' : 'Dùng Pollen từ key đã cấu hình; không cam kết miễn phí.' }}</p>
      <label :for="promptId">Mô tả ảnh (gửi tới Pollinations)</label>
      <textarea :id="promptId" v-model="prompt" rows="5" maxlength="3000" :disabled="busy" placeholder="Mô tả xe, màu sơn và bối cảnh; không nhập dữ liệu riêng tư."></textarea>
      <div class="trial-actions">
        <button type="button" :disabled="busy" @click="suggestPrompt">Lấy gợi ý từ tiêu đề bài</button>
        <button type="button" class="trial-primary" :disabled="busy || prompt.trim().length < 20 || !config?.enabled || !config?.configured" @click="generate">{{ busy ? 'Đang chờ ảnh…' : 'Tạo 1 ảnh thử' }}</button>
      </div>
      <p class="trial-status" role="status" aria-live="polite">{{ statusText }}</p>
      <div v-if="previewUrl" class="trial-preview"><img :src="previewUrl" alt="Ảnh xe minh họa từ Pollinations, cần kiểm tra chi tiết" /><button type="button" class="trial-primary" :disabled="busy || applying" @click="apply">{{ applying ? 'Đang áp dụng…' : 'Dùng làm ảnh bìa (chưa xuất bản bài)' }}</button></div>
      <p v-if="error" class="trial-error" role="alert">{{ error }}</p>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, useId } from 'vue';
import baseRequest from '../../../../core/baseRequest';
const props = defineProps({ title: { type: String, default: '' } });
const emit = defineEmits(['selected']);
const promptId = useId();
const expanded = ref(false), prompt = ref(''), config = ref(null), busy = ref(false), applying = ref(false);
const statusText = ref(''), error = ref(''), previewUrl = ref('');
let id = null, timer = null, generation = 0, alive = true, startedAt = 0;
const endpoint = 'admin/post-image-trials';
const revoke = () => { if (previewUrl.value) URL.revokeObjectURL(previewUrl.value); previewUrl.value = ''; };
const suggestPrompt = () => {
  prompt.value = `Premium automotive editorial illustration for a car rental article titled: ${props.title.slice(0, 220) || 'Weekend road trip'}. A single complete car in front three-quarter view, realistic proportions and wheels, tasteful coastal road setting, natural daylight, deep navy and emerald visual accents, clean composition with empty space for a headline. No people, no faces, no text, no logos. This is a conceptual illustration, not a real rental listing photo.`;
};
const message = e => e.response?.data?.message || 'Không kết nối được máy chủ. Kiểm tra cấu hình và worker.';
const poll = async ticket => {
  if (!alive || ticket !== generation) return;
  if (Date.now() - startedAt > 15 * 60 * 1000) { busy.value = false; statusText.value = ''; error.value = 'Chờ quá lâu. Kiểm tra worker trước khi tạo thêm ảnh.'; return; }
  try {
    const res = await baseRequest.get(`${endpoint}/${id}`);
    if (!alive || ticket !== generation) return;
    const item = res.data.data;
    if (item.status === 'failed') { busy.value = false; statusText.value = ''; error.value = item.message || 'Tạo ảnh thất bại.'; return; }
    if (item.status === 'ready') {
      const image = await baseRequest.get(`${endpoint}/${id}/image`, { responseType: 'blob' });
      if (!alive || ticket !== generation) return;
      revoke(); previewUrl.value = URL.createObjectURL(image.data);
      busy.value = false; statusText.value = 'Ảnh đã tạo. Kiểm tra hình dáng xe, bánh, logo và chi tiết trước khi dùng.';
      return;
    }
    statusText.value = item.status === 'queued' ? 'Đang trong hàng đợi. Cần worker marketing-images hoạt động.' : 'Pollinations đang tạo ảnh. Thời gian phụ thuộc dịch vụ.';
    timer = setTimeout(() => poll(ticket), 3000);
  } catch (e) { if (alive && ticket === generation) { busy.value = false; error.value = message(e); statusText.value = ''; } }
};
const generate = async () => {
  const ticket = ++generation;
  clearTimeout(timer); revoke(); error.value = ''; busy.value = true; statusText.value = 'Đang tạo yêu cầu…';
  try {
    const res = await baseRequest.post(endpoint, { prompt: prompt.value.trim() });
    if (!alive || ticket !== generation) return;
    id = res.data.data.id; startedAt = Date.now(); poll(ticket);
  } catch (e) { if (alive && ticket === generation) { busy.value = false; statusText.value = ''; error.value = message(e); } }
};
const apply = async () => {
  if (!id || busy.value || applying.value) return;
  applying.value = true; error.value = '';
  try {
    const res = await baseRequest.post(`${endpoint}/${id}/approve`, {});
    if (!alive) return;
    emit('selected', res.data.data);
    statusText.value = 'Đã đưa ảnh vào form. Lưu bài để ghi nhận; bài chưa tự xuất bản.';
  } catch (e) { if (alive) error.value = message(e); }
  finally { if (alive) applying.value = false; }
};
onMounted(async () => {
  suggestPrompt();
  try { const res = await baseRequest.get(`${endpoint}/config`); if (alive) config.value = res.data.data; }
  catch (e) { if (alive) error.value = message(e); }
});
onBeforeUnmount(() => { alive = false; ++generation; clearTimeout(timer); revoke(); });
</script>

<style scoped>
.polli-trial{border:1px solid var(--color-primary-border,#a7f3d0);border-radius:12px;margin:16px 0;background:var(--bg-surface,#fff)}
.trial-toggle{padding:13px 14px;width:100%;display:flex;align-items:center;justify-content:space-between;color:var(--color-primary,#059669);font-weight:700;text-align:left;font-size:.85rem}
.trial-body{padding:0 14px 14px}.trial-body p{font-size:.76rem;line-height:1.6;margin:8px 0;color:var(--color-text-muted,#667085)}
.trial-body label{display:block;font-size:.8rem;font-weight:600;margin:12px 0 6px}
.trial-body textarea{width:100%;resize:vertical;border:1px solid var(--color-border,#e4e7ec);border-radius:8px;padding:10px;font:inherit;font-size:.8rem;min-height:110px;color:var(--color-text-main,#101828);background:var(--bg-surface,#fff)}
.trial-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:9px}.trial-body button{border-radius:8px;padding:9px 11px;border:1px solid var(--color-border,#e4e7ec);font-size:.76rem}
.trial-body .trial-primary{background:var(--color-primary,#059669);color:white;border-color:transparent}.trial-body button:disabled{opacity:.5;cursor:not-allowed}
.trial-body .trial-error{color:#b42318}.trial-body .trial-warning{color:#92400e}.trial-preview img{width:100%;object-fit:contain;border-radius:8px;margin-bottom:8px}.trial-status{min-height:18px}
</style>
