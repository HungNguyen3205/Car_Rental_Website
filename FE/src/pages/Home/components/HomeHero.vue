<template>
  <section class="hero-section" aria-label="Khám phá phân khúc xe">
    <div class="hero-container app-container">
      <div class="hero-grid">
        <div class="hero-info">
          <p class="hero-eyebrow"><span></span> DRIVERENT / CHỌN HÀNH TRÌNH CỦA BẠN</p>
          <h1 class="hero-title">XE SANG CHO<br />MỌI HÀNH TRÌNH<span>.</span></h1>
          <p class="hero-description">Một lựa chọn phù hợp. Một hành trình đáng nhớ.</p>
          <div class="class-selector-group">
            <span class="selector-label">KHÁM PHÁ PHÂN KHÚC</span>
            <div ref="tabList" class="segmented-pill-container" role="group" aria-label="Chọn phân khúc xe">
              <span class="segment-indicator" :style="indicatorStyle" aria-hidden="true"></span>
              <button v-for="(category, index) in categories" :key="category.id"
                :ref="el => tabButtons[index] = el" type="button" class="segment-btn"
                :aria-pressed="requestedIndex === index" :class="{ 'is-active': requestedIndex === index }"
                @click="selectCategory(index)">{{ category.name }}</button>
            </div>
          </div>
          <div class="hero-specs-row" aria-live="polite" aria-atomic="true">
            <div><span class="spec-label">Phân khúc</span><strong>{{ displayedCategory.label }}</strong></div>
            <div><span class="spec-label">{{ matchingCars.length ? 'Xe trong danh sách' : 'Bộ sưu tập' }}</span>
              <strong>{{ matchingCars.length ? matchingCars.length + ' lựa chọn' : 'Khám phá đội xe' }}</strong></div>
            <div v-if="startingPrice"><span class="spec-label">Giá tham khảo từ</span><strong class="price-text">{{ formatPrice(startingPrice) }}<small>/ngày</small></strong></div>
          </div>
          <div class="hero-quick-actions">
            <button type="button" class="btn-hero-explore" @click="emit('scroll-to-search')">Tìm xe cho chuyến đi <span aria-hidden="true">↗</span></button>
            <router-link to="/fleet" class="btn-hero-fleet">Xem đội xe <span aria-hidden="true">→</span></router-link>
          </div>
        </div>
        <div class="hero-visual">
          <div class="visual-note"><span></span> Khám phá xe. Chọn hành trình.</div>
          <div class="car-stage-wrapper" :class="{ 'is-moving': phase !== 'idle' }" :aria-busy="preparing">
            <div class="stage-halo" aria-hidden="true"></div>
            <div class="stage-road" aria-hidden="true"></div>
            <div v-for="layer in layers" :key="layer.key" class="car-layer" :class="'car-' + layer.state">
              <div class="car-ground-shadow" aria-hidden="true"></div>
              <div class="car-suspension"><img :src="categories[layer.index].image" :alt="layer.state === 'out' ? '' : categories[layer.index].alt"
                class="hero-car-img" :aria-hidden="layer.state === 'out'" draggable="false" decoding="async" fetchpriority="high" /></div>
            </div>
          </div>
          <div class="stage-caption"><span>{{ String(displayedIndex + 1).padStart(2, '0') }} / 04</span><span>{{ displayedCategory.label }} · Hình ảnh minh họa</span><span class="caption-line" aria-hidden="true"></span></div>
          <p v-if="assetError" class="asset-error" role="status">Chưa tải được hình xe. <button type="button" @click="selectCategory(requestedIndex, true)">Thử lại</button></p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
const props = defineProps({ cars: { type: Array, default: () => [] } });
const emit = defineEmits(['scroll-to-search', 'category-change']);
const categories = [
  { id: 'suv', name: 'SUV', label: 'SUV', image: '/assets/images/cars/hero_suv_transparent.png', alt: 'Xe SUV góc nhìn ba phần tư' },
  { id: 'sport', name: 'Sport', label: 'Thể thao', image: '/assets/images/cars/hero_sport_transparent.png', alt: 'Xe thể thao góc nhìn ba phần tư' },
  { id: 'sedan', name: 'Sedan', label: 'Sedan', image: '/assets/images/cars/hero_sedan_transparent.png', alt: 'Xe sedan góc nhìn ba phần tư' },
  { id: 'electric', name: 'Electric', label: 'Xe điện', image: '/assets/images/cars/hero_electric_transparent.png', alt: 'Xe điện góc nhìn ba phần tư' }
];
const requestedIndex = ref(0);
const displayedIndex = ref(0);
const displayedCategory = computed(() => categories[displayedIndex.value]);
const phase = ref('idle');
const preparing = ref(false);
const assetError = ref(false);
const layers = ref([{ index: 0, key: 0, state: 'still' }]);
const tabList = ref(null);
const tabButtons = [];
const indicatorStyle = ref({ opacity: 0 });
let generation = 0, serial = 0, finishTimer, resizeObserver, motionQuery, disposed = false;
const decoded = new Map();
const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').toLowerCase();
const matchingCars = computed(() => props.cars.filter(car => {
  const type = normalize(car.category_name || car.type);
  const id = displayedCategory.value.id;
  return id === 'electric' ? /electric|xe dien/.test(type) : id === 'sport' ? /sport|the thao/.test(type) : type.includes(id);
}));
const startingPrice = computed(() => {
  const prices = matchingCars.value.map(car => Number(car.price_per_day ?? car.gia_thue)).filter(price => Number.isFinite(price) && price > 0);
  return prices.length ? Math.min(...prices) : null;
});
const formatPrice = price => new Intl.NumberFormat('vi-VN').format(price) + 'đ';
const updateIndicator = () => {
  const button = tabButtons[requestedIndex.value];
  if (!button || !tabList.value) return;
  indicatorStyle.value = { width: button.offsetWidth + 'px', transform: `translateX(${button.offsetLeft}px)`, opacity: 1 };
};
const loadImage = index => {
  if (decoded.has(index)) return decoded.get(index);
  const promise = new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = async () => { try { await img.decode(); } catch {} resolve(); };
    img.onerror = () => reject(new Error('Vehicle asset unavailable'));
    img.src = categories[index].image;
  });
  decoded.set(index, promise);
  promise.catch(() => decoded.delete(index));
  return promise;
};
const selectCategory = async (index, retry = false) => {
  if (!retry && requestedIndex.value === index && !assetError.value) return;
  const ticket = ++generation;
  requestedIndex.value = index;
  nextTick(updateIndicator);
  preparing.value = true;
  assetError.value = false;
  try { await loadImage(index); }
  catch { if (ticket === generation && !disposed) { preparing.value = false; assetError.value = true; } return; }
  if (disposed || ticket !== generation) return;
  preparing.value = false;
  clearTimeout(finishTimer);
  const previous = displayedIndex.value;
  displayedIndex.value = index;
  emit('category-change', categories[index].id);
  if (motionQuery?.matches || previous === index) {
    layers.value = [{ index, key: ++serial, state: 'still' }];
    phase.value = 'idle';
    return;
  }
  layers.value = [{ index: previous, key: ++serial, state: 'out' }, { index, key: ++serial, state: 'in' }];
  phase.value = 'moving';
  finishTimer = setTimeout(() => {
    if (disposed || displayedIndex.value !== index) return;
    layers.value = [{ index, key: ++serial, state: 'still' }];
    phase.value = 'idle';
  }, 940);
};
const handleMotionChange = () => {
  if (!motionQuery.matches) return;
  clearTimeout(finishTimer);
  layers.value = [{ index: displayedIndex.value, key: ++serial, state: 'still' }];
  phase.value = 'idle';
};
onMounted(() => {
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  motionQuery.addEventListener('change', handleMotionChange);
  resizeObserver = new ResizeObserver(updateIndicator);
  if (tabList.value) resizeObserver.observe(tabList.value);
  nextTick(updateIndicator);
  document.fonts?.ready.then(() => { if (!disposed) updateIndicator(); });
  categories.forEach((_, index) => loadImage(index).catch(() => { if (index === displayedIndex.value && !disposed) assetError.value = true; }));
});
onUnmounted(() => {
  disposed = true; ++generation; clearTimeout(finishTimer);
  resizeObserver?.disconnect(); motionQuery?.removeEventListener('change', handleMotionChange);
});
</script>

<style scoped>
.hero-section{position:relative;background:var(--bg-dark);color:#f8fafc;padding:138px 0 68px;isolation:isolate}
.hero-section::before{content:'';position:absolute;inset:0;z-index:-1;background:radial-gradient(ellipse at 72% 60%,rgba(5,150,105,.1),transparent 52%);pointer-events:none}
.hero-container{max-width:1380px}
.hero-grid{display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,1.3fr);gap:22px;align-items:center;min-height:440px}
.hero-info{position:relative;z-index:2;min-width:0;animation:info-reveal .7s both}
.hero-eyebrow{display:flex;align-items:center;gap:9px;font-size:.64rem;letter-spacing:.14em;color:#a6bbb7;margin-bottom:25px}
.hero-eyebrow span,.visual-note span{width:6px;height:6px;border-radius:50%;background:var(--color-primary-glow);flex-shrink:0}
.hero-title{font-family:var(--font-heading);font-size:clamp(2.6rem,4.4vw,4.6rem);line-height:1.04;font-weight:800;letter-spacing:-.055em;color:#f8fafc;margin:0 0 18px}
.hero-title>span{color:var(--color-primary-glow)}
.hero-description{font-size:.85rem;color:#93a5ae;max-width:350px;margin-bottom:27px}
.selector-label{display:block;font-size:.61rem;letter-spacing:.13em;color:#95a4af;margin-bottom:9px}
.segmented-pill-container{position:relative;display:inline-flex;padding:4px;background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.09);border-radius:40px;max-width:100%}
.segment-indicator{position:absolute;top:4px;bottom:4px;left:0;border-radius:40px;background:var(--color-primary);transition:transform .32s cubic-bezier(.22,1,.36,1),width .32s,opacity .2s;pointer-events:none}
.segment-btn{position:relative;background:transparent;border:0;color:#9babb5;font-size:.79rem;font-weight:600;padding:9px 19px;border-radius:40px;transition:color .2s}
.segment-btn.is-active{color:white}.segment-btn:hover{color:white}
.hero-specs-row{display:flex;gap:25px;margin-top:25px;min-height:56px;flex-wrap:wrap}
.hero-specs-row>div{display:flex;flex-direction:column;gap:5px}
.spec-label{color:#8fa0aa;font-size:.64rem}.hero-specs-row strong{font-family:var(--font-heading);font-size:1rem;font-weight:600;color:#e9f0f1}
.hero-specs-row .price-text{color:#6ee7b7}.price-text small{font-size:.63rem;font-weight:400;margin-left:3px}
.hero-quick-actions{display:flex;align-items:center;gap:22px;margin-top:24px;flex-wrap:wrap}
.btn-hero-explore{display:flex;align-items:center;gap:22px;border-radius:40px;background:var(--color-primary);color:#fff;font-size:.8rem;font-weight:600;padding:12px 19px;transition:background .2s,transform .2s}
.btn-hero-explore:hover{background:var(--color-primary-hover);transform:translateY(-2px)}
.btn-hero-explore>span{font-size:1.1rem}.btn-hero-fleet{font-size:.77rem;color:#bdcbc9}.btn-hero-fleet span{margin-left:10px}
.hero-visual{position:relative;min-width:0;align-self:stretch;display:flex;flex-direction:column;justify-content:center}
.visual-note{display:flex;align-items:center;gap:9px;align-self:flex-end;font-size:.65rem;color:#a3b3b7;border:1px solid rgba(255,255,255,.08);border-radius:9px;padding:10px 13px;margin-bottom:8px}
.car-stage-wrapper{position:relative;width:100%;aspect-ratio:1.7;isolation:isolate;overflow:clip;overflow-clip-margin:18px}
.stage-halo{position:absolute;inset:17% 5% 2%;background:radial-gradient(ellipse,rgba(16,185,129,.07),transparent 65%);pointer-events:none}
.stage-road{position:absolute;left:7%;right:2%;bottom:11%;height:1px;background:linear-gradient(90deg,transparent,rgba(167,243,208,.15),transparent)}
.car-layer{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;transform-origin:55% 78%}
.car-suspension{width:100%;position:relative;z-index:1;transform-origin:60% 78%}
.hero-car-img{width:100%;height:auto;max-height:390px;object-fit:contain;user-select:none;filter:drop-shadow(0 18px 12px rgba(0,0,0,.22))}
.car-ground-shadow{position:absolute;bottom:12%;left:12%;width:79%;height:9%;border-radius:50%;background:radial-gradient(ellipse,rgba(0,0,0,.8),transparent 70%);filter:blur(7px);transform-origin:center}
.car-in{animation:vehicle-arrive .94s cubic-bezier(.16,1,.3,1) both;z-index:2}
.car-out{animation:vehicle-depart .46s cubic-bezier(.55,0,.85,.36) both;z-index:1}
.car-in .car-suspension{animation:body-settle .94s ease-out both}
.car-in .car-ground-shadow{animation:shadow-settle .94s ease-out both}
.stage-caption{display:flex;gap:16px;align-items:center;font-size:.58rem;letter-spacing:.07em;color:#849790;margin:0 5% 0 12%;min-height:20px}
.stage-caption>span:first-child{color:#b5c5c0;white-space:nowrap}.caption-line{height:1px;background:rgba(255,255,255,.1);flex:1}
.asset-error{font-size:.75rem;color:#fcd34d;text-align:center}.asset-error button{color:inherit;text-decoration:underline}
@keyframes vehicle-arrive{0%{transform:translate3d(105%,8%,0);opacity:0}9%{opacity:1}65%{transform:translate3d(-1.6%,0,0)}82%{transform:translate3d(.5%,0,0)}100%{transform:translate3d(0,0,0);opacity:1}}
@keyframes vehicle-depart{0%{transform:translate3d(0,0,0);opacity:1}35%{opacity:1}100%{transform:translate3d(-110%,2%,0);opacity:0}}
@keyframes body-settle{0%,45%{transform:translateY(-3px) rotate(-.45deg)}68%{transform:translateY(2px) rotate(.22deg)}84%{transform:translateY(-1px) rotate(-.1deg)}100%{transform:none}}
@keyframes shadow-settle{0%{opacity:.35;transform:scaleX(.88)}70%{opacity:.9;transform:scaleX(1.02)}100%{opacity:1;transform:scaleX(1)}}
@keyframes info-reveal{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@media(min-width:1600px){.hero-grid{min-height:480px}}
@media(max-width:1000px){.hero-grid{grid-template-columns:minmax(0,1fr) minmax(0,1.12fr);gap:14px}.hero-title{font-size:clamp(2.4rem,4.5vw,3.5rem)}.hero-eyebrow{font-size:.55rem}.segment-btn{padding:9px 14px}.hero-specs-row{gap:17px}.visual-note{font-size:.58rem}.hero-section{padding-top:120px}}
@media(max-width:700px){.hero-section{padding:108px 0 46px}.hero-grid{grid-template-columns:minmax(0,1fr);gap:15px}.hero-title{font-size:clamp(2.4rem,8.8vw,3.6rem)}.hero-description{margin-bottom:22px}.hero-eyebrow{margin-bottom:18px}.hero-visual{margin-top:4px}.visual-note{display:none}.car-stage-wrapper{aspect-ratio:1.9}.hero-specs-row{margin-top:21px;min-height:50px}.hero-quick-actions{margin-top:17px}.stage-caption{margin-top:0}.hero-car-img{max-height:280px}.segment-btn{padding:9px 18px}.hero-specs-row strong{font-size:.9rem}}
@media(prefers-reduced-motion:reduce){.hero-info,.car-in,.car-out,.car-suspension,.car-ground-shadow{animation:none!important}.car-out{display:none}.segment-indicator{transition:none}}
</style>
