<template>
  <div id="booking-search-section" class="search-panel-wrapper app-container">
    <div class="search-panel-card">
      <!-- Top Row: Mode Toggles -->
      <div class="panel-top-bar">
        <div class="mode-selector">
          <span class="mode-label">Hình thức:</span>
          <div class="mode-pills">
            <button
              type="button"
              class="mode-pill-btn"
              :class="{ 'is-active': driverMode === 'self_drive' }"
              @click="driverMode = 'self_drive'"
            >
              <i class="fa-solid fa-car"></i>
              <span>Thuê tự lái</span>
            </button>
            <button
              type="button"
              class="mode-pill-btn"
              :class="{ 'is-active': driverMode === 'with_driver' }"
              @click="driverMode = 'with_driver'"
            >
              <i class="fa-solid fa-user-tie"></i>
              <span>Có tài xế</span>
            </button>
          </div>
        </div>

        <div class="round-trip-toggle">
          <label class="toggle-label" for="roundTripSwitch">
            <span>Giao nhận tận nơi</span>
            <div class="switch-ui" :class="{ 'on': isDoorToDoor }" @click="isDoorToDoor = !isDoorToDoor">
              <div class="switch-handle"></div>
            </div>
          </label>
        </div>
      </div>

      <!-- Main Input Grid -->
      <div class="panel-inputs-grid">
        <!-- Field 1: Province / City -->
        <div class="input-block city-block" v-click-outside="() => showCityDropdown = false">
          <label class="field-title">
            <i class="fa-solid fa-map-pin field-icon"></i>
            <span>Tỉnh / Thành phố</span>
          </label>
          <div class="custom-select-box" @click.stop="toggleCityDropdown">
            <span class="selected-text" :class="{ 'is-placeholder': !selectedCity }">
              {{ selectedCityName || 'Chọn khu vực...' }}
            </span>
            <i class="fa-solid fa-chevron-down select-chevron" :class="{ 'is-open': showCityDropdown }"></i>

            <!-- City Dropdown -->
            <Transition name="dropdown-fade">
              <div v-if="showCityDropdown" class="city-dropdown-menu" @click.stop>
                <div class="dropdown-search-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input
                    type="text"
                    v-model="citySearchQuery"
                    placeholder="Tìm nhanh thành phố..."
                    @click.stop
                  />
                </div>
                <div class="dropdown-city-list">
                  <button
                    v-for="city in filteredProvinces"
                    :key="city.id"
                    type="button"
                    class="city-option-item"
                    :class="{ 'is-active': selectedCity === city.id }"
                    @click.stop="selectCity(city.id)"
                  >
                    <i class="fa-solid fa-location-dot"></i>
                    <span>{{ city.name }}</span>
                  </button>
                  <div v-if="filteredProvinces.length === 0" class="no-city-found">
                    Không tìm thấy khu vực
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Field 2: Detailed Address / Showroom -->
        <div class="input-block address-block">
          <label class="field-title">
            <i class="fa-solid fa-location-crosshairs field-icon"></i>
            <span>Điểm nhận chi tiết</span>
          </label>
          <div class="input-field-wrap">
            <input
              type="text"
              v-model="detailedAddress"
              :placeholder="selectedCity ? 'Sân bay, khách sạn hoặc địa chỉ...' : 'Chọn thành phố trước'"
              :disabled="!selectedCity"
              class="search-text-input"
            />
          </div>
        </div>

        <!-- Field 3: Pickup Date/Time -->
        <div class="input-block date-block">
          <label class="field-title">
            <i class="fa-solid fa-calendar-plus field-icon"></i>
            <span>Thời gian nhận</span>
          </label>
          <VueDatePicker
            v-model="startDate"
            :min-date="new Date()"
            placeholder="Chọn ngày giờ"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
            class="dark-datepicker"
            dark
          />
        </div>

        <!-- Field 4: Return Date/Time -->
        <div class="input-block date-block">
          <label class="field-title">
            <i class="fa-solid fa-calendar-check field-icon"></i>
            <span>Thời gian trả</span>
          </label>
          <VueDatePicker
            v-model="endDate"
            :min-date="startDate || new Date()"
            placeholder="Chọn ngày giờ"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
            class="dark-datepicker"
            dark
          />
        </div>

        <!-- Submit Button -->
        <div class="input-block action-block">
          <button
            type="button"
            class="btn-submit-search"
            :disabled="loading"
            @click="handleSubmit"
            title="Tìm kiếm phương tiện phù hợp"
          >
            <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
            <template v-else>
              <span>Tìm Xe</span>
              <i class="fa-solid fa-arrow-right"></i>
            </template>
          </button>
        </div>
      </div>

      <!-- Error Notice -->
      <div v-if="validationError" class="panel-error-alert" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>{{ validationError }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  provinces: {
    type: Array,
    default: () => [
      { id: 'hn', name: 'Hà Nội' },
      { id: 'sg', name: 'TP. Hồ Chí Minh' },
      { id: 'dn', name: 'Đà Nẵng' },
      { id: 'nt', name: 'Nha Trang' },
      { id: 'hp', name: 'Hải Phòng' },
      { id: 'ct', name: 'Cần Thơ' }
    ]
  }
});

const emit = defineEmits(['search']);

const driverMode = ref('self_drive');
const isDoorToDoor = ref(true);

const selectedCity = ref('sg');
// Province IDs can change from fallback slugs to API codes after loading.
watch(() => props.provinces, list => {
  if (!list.length || list.some(city => city.id === selectedCity.value)) return;
  selectedCity.value = (list.find(city => /Hồ Chí Minh/.test(city.name)) || list[0]).id;
}, { immediate: true });
const detailedAddress = ref('');
const citySearchQuery = ref('');
const showCityDropdown = ref(false);

const getDefaultStartDate = () => {
  const d = new Date();
  d.setHours(d.getHours() + 2, 0, 0, 0);
  return d;
};

const getDefaultEndDate = () => {
  const d = new Date();
  d.setDate(d.getDate() + 2);
  d.setHours(d.getHours() + 2, 0, 0, 0);
  return d;
};

const startDate = ref(getDefaultStartDate());
const endDate = ref(getDefaultEndDate());
const validationError = ref('');

const selectedCityName = computed(() => {
  const c = props.provinces.find(p => p.id === selectedCity.value);
  return c ? c.name : '';
});

const filteredProvinces = computed(() => {
  if (!citySearchQuery.value.trim()) return props.provinces;
  const q = citySearchQuery.value.toLowerCase();
  return props.provinces.filter(p => p.name.toLowerCase().includes(q));
});

const toggleCityDropdown = () => {
  showCityDropdown.value = !showCityDropdown.value;
};

const selectCity = (id) => {
  selectedCity.value = id;
  showCityDropdown.value = false;
  citySearchQuery.value = '';
  validationError.value = '';
};

const handleSubmit = () => {
  validationError.value = '';

  if (!selectedCity.value) {
    validationError.value = 'Vui lòng chọn Tỉnh / Thành phố nhận xe!';
    return;
  }

  if (!startDate.value || !endDate.value) {
    validationError.value = 'Vui lòng chọn thời gian nhận và trả xe!';
    return;
  }

  const start = new Date(startDate.value);
  const end = new Date(endDate.value);

  if (end <= start) {
    validationError.value = 'Thời gian trả xe phải sau thời gian nhận xe ít nhất 1 giờ!';
    return;
  }

  emit('search', {
    city: selectedCity.value,
    address: detailedAddress.value,
    startDate: startDate.value,
    endDate: endDate.value,
    driverMode: driverMode.value,
    isDoorToDoor: isDoorToDoor.value
  });
};

// Custom click outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};
</script>

<style scoped>
.search-panel-wrapper {
  position: relative;
  z-index: 20;
  margin-top: -36px;
  margin-bottom: 70px;
}

.search-panel-card {
  background: rgba(18, 25, 42, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 26px;
  padding: 22px 28px;
  box-shadow: 0 24px 50px -10px rgba(0, 0, 0, 0.55),
              0 0 0 1px rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}

/* Top Bar */
.panel-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 18px;
  margin-bottom: 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  flex-wrap: wrap;
  gap: 16px;
}

.mode-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.mode-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #94a3b8;
}

.mode-pills {
  display: inline-flex;
  background: rgba(11, 18, 32, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  padding: 3px;
  gap: 4px;
}

.mode-pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 6px 14px;
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 180ms ease;
}

.mode-pill-btn:hover:not(.is-active) {
  color: #f1f5f9;
}

.mode-pill-btn.is-active {
  background: var(--color-primary, #059669);
  color: #ffffff;
}

/* Switch */
.round-trip-toggle .toggle-label {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 600;
  color: #94a3b8;
  user-select: none;
}

.switch-ui {
  width: 42px;
  height: 22px;
  background: rgba(255, 255, 255, 0.14);
  border-radius: 9999px;
  position: relative;
  transition: background 200ms ease;
}

.switch-ui.on {
  background: var(--color-primary, #059669);
}

.switch-handle {
  width: 18px;
  height: 18px;
  background: #ffffff;
  border-radius: 50%;
  position: absolute;
  top: 2px;
  left: 2px;
  transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.switch-ui.on .switch-handle {
  transform: translateX(20px);
}

/* Main Inputs Grid */
.panel-inputs-grid {
  display: grid;
  grid-template-columns: 1.1fr 1.3fr 1fr 1fr auto;
  gap: 16px;
  align-items: flex-end;
}

.input-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
  position: relative;
}

.field-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.01em;
}

.field-icon {
  color: var(--color-primary-glow, #10b981);
  font-size: 0.8rem;
}

/* Custom Select */
.custom-select-box {
  height: 48px;
  background: rgba(11, 18, 32, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 14px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 200ms ease;
  position: relative;
}

.custom-select-box:hover {
  border-color: rgba(255, 255, 255, 0.25);
  background: rgba(11, 18, 32, 0.9);
}

.selected-text {
  font-size: 0.9rem;
  font-weight: 600;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.selected-text.is-placeholder {
  color: #64748b;
  font-weight: 400;
}

.select-chevron {
  color: #94a3b8;
  font-size: 0.75rem;
  transition: transform 200ms ease;
}

.select-chevron.is-open {
  transform: rotate(180deg);
}

/* Dropdown Menu */
.city-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 240px;
  background: #111a2e;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.55);
  padding: 8px;
  z-index: 100;
  backdrop-filter: blur(20px);
}

.dropdown-search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  margin-bottom: 6px;
}

.dropdown-search-box i {
  color: #94a3b8;
  font-size: 0.8rem;
}

.dropdown-search-box input {
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-size: 0.82rem;
  width: 100%;
}

.dropdown-city-list {
  max-height: 200px;
  overflow-y: auto;
}

.city-option-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: #cbd5e1;
  font-size: 0.85rem;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: all 150ms ease;
}

.city-option-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.city-option-item.is-active {
  background: var(--color-primary, #059669);
  color: #ffffff;
  font-weight: 600;
}

.no-city-found {
  padding: 12px;
  font-size: 0.8rem;
  color: #94a3b8;
  text-align: center;
}

/* Address Input */
.search-text-input {
  width: 100%;
  height: 48px;
  background: rgba(11, 18, 32, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 14px;
  padding: 0 14px;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 500;
  outline: none;
  transition: all 200ms ease;
}

.search-text-input:focus {
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
  background: rgba(11, 18, 32, 0.95);
}

.search-text-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Submit Button */
.btn-submit-search {
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 0 28px;
  background: linear-gradient(135deg, var(--color-primary, #059669) 0%, #047857 100%);
  color: #ffffff;
  border: none;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 4px 16px rgba(5, 150, 105, 0.35);
  transition: all 240ms cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-submit-search:hover:not(:disabled) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
}

.btn-submit-search:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Error Alert */
.panel-error-alert {
  margin-top: 14px;
  padding: 10px 16px;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 12px;
  color: #fca5a5;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* VueDatePicker Dark Theme Customization */
.dark-datepicker {
  width: 100%;
}

.dark-datepicker :deep(.dp__input_wrap) {
  height: 48px;
}

.dark-datepicker :deep(.dp__input) {
  height: 48px !important;
  background: rgba(11, 18, 32, 0.75) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  border-radius: 14px !important;
  padding: 0 36px 0 38px !important;
  color: #ffffff !important;
  font-size: 0.88rem !important;
  font-weight: 500 !important;
  font-family: inherit !important;
  outline: none !important;
  transition: all 200ms ease !important;
  box-shadow: none !important;
}

.dark-datepicker :deep(.dp__input::placeholder) {
  color: #64748b !important;
  font-weight: 400 !important;
}

.dark-datepicker :deep(.dp__input:hover) {
  border-color: rgba(255, 255, 255, 0.25) !important;
  background: rgba(11, 18, 32, 0.9) !important;
}

.dark-datepicker :deep(.dp__input:focus) {
  border-color: var(--color-primary, #059669) !important;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2) !important;
  background: rgba(11, 18, 32, 0.95) !important;
}

.dark-datepicker :deep(.dp__input_icon) {
  color: var(--color-primary-glow, #10b981) !important;
  padding-left: 12px !important;
  font-size: 0.92rem !important;
}

.dark-datepicker :deep(.dp__clear_icon) {
  color: #94a3b8 !important;
  padding-right: 12px !important;
}

.dark-datepicker :deep(.dp__clear_icon:hover) {
  color: #ffffff !important;
}

/* Transitions */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

/* Responsive */
@media (max-width: 1120px) {
  .panel-inputs-grid {
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }

  .action-block {
    grid-column: span 2;
  }

  .btn-submit-search {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .search-panel-card {
    padding: 16px;
    border-radius: 20px;
  }

  .panel-inputs-grid {
    grid-template-columns: 1fr;
  }

  .action-block {
    grid-column: span 1;
  }
}
</style>

<style>
/* Teleported VueDatePicker Dark Theme Popup Menu */
.dp__menu.dp__theme_dark {
  background: #111a2e !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  border-radius: 18px !important;
  box-shadow: 0 20px 48px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
  backdrop-filter: blur(24px) !important;
  font-family: var(--font-body, inherit) !important;
  padding: 8px !important;
}

.dp__theme_dark .dp__calendar_header_item {
  color: #94a3b8 !important;
  font-weight: 600 !important;
  font-size: 0.8rem !important;
}

.dp__theme_dark .dp__cell_inner {
  border-radius: 8px !important;
  transition: all 150ms ease !important;
  font-size: 0.85rem !important;
  color: #e2e8f0 !important;
}

.dp__theme_dark .dp__cell_inner:hover {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #ffffff !important;
}

.dp__theme_dark .dp__active_date {
  background: var(--color-primary, #059669) !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  box-shadow: 0 2px 10px rgba(5, 150, 105, 0.4) !important;
}

.dp__theme_dark .dp__today {
  border: 1px solid var(--color-primary-glow, #10b981) !important;
}

.dp__theme_dark .dp__overlay_cell_active {
  background: var(--color-primary, #059669) !important;
  color: #ffffff !important;
  border-radius: 8px !important;
}

.dp__theme_dark .dp__action_buttons .dp__action_select {
  background: var(--color-primary, #059669) !important;
  color: #ffffff !important;
  border-radius: 8px !important;
  font-weight: 600 !important;
}

.dp__theme_dark .dp__action_buttons .dp__action_cancel {
  color: #94a3b8 !important;
  border-radius: 8px !important;
}

.dp__theme_dark .dp__arrow_top,
.dp__theme_dark .dp__arrow_bottom {
  background: #111a2e !important;
  border-color: rgba(255, 255, 255, 0.14) !important;
}
</style>
