<template>
  <div class="fleet-page">
    <!-- Cinematic Hero Section -->
    <section class="fleet-hero">
      <div class="hero-bg"></div>
      <div class="hero-overlay"></div>
      <div class="app-container hero-content animate-up">
        <div class="badge-premium">VinFast Premium Fleet</div>
        <h1>TUYỆT TÁC <span>CÔNG NGHỆ</span></h1>
        <p>Khám phá hệ sinh thái xe điện thông minh - Nơi thiết kế Ý hòa quyện cùng công nghệ tương lai.</p>
        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-val">100%</span>
                <span class="stat-label">Xe điện sạch</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-val">24/7</span>
                <span class="stat-label">Hỗ trợ kỹ thuật</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-val">5★</span>
                <span class="stat-label">Trải nghiệm an toàn</span>
            </div>
        </div>
      </div>
    </section>

    <!-- Interactive Filters -->
    <section class="fleet-controls app-container">
        <div class="controls-wrapper card glass">
            <div class="filter-side">
                <span class="label">Dòng xe:</span>
                <div class="vibe-pills">
                    <button 
                        v-for="type in carTypes" 
                        :key="type" 
                        class="pill" 
                        :class="{ 'active': selectedType === type }"
                        @click="selectedType = type"
                    >
                        {{ type }}
                    </button>
                </div>
            </div>
            <div class="sort-side">
                <div class="sort-box">
                    <i class="fa-solid fa-sort-amount-down"></i>
                    <select v-model="sortBy">
                        <option value="default">Phổ biến nhất</option>
                        <option value="price-asc">Giá thuê tăng dần</option>
                        <option value="price-desc">Giá thuê giảm dần</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Showcase Grid -->
    <section class="fleet-showcase app-container">
      <div v-if="loading" class="showcase-loading">
        <div class="pulse-loader"></div>
        <p>Đang chuẩn bị dàn xe cho bạn...</p>
      </div>

      <div v-else class="showcase-grid">
        <div class="product-card animate-in" v-for="(car, index) in filteredCars" :key="car.id" :style="{ 'animation-delay': (index * 0.1) + 's' }">
          <div class="product-img-box">
            <img :src="car.image" :alt="car.name" />
            <div class="product-tag">{{ car.type }}</div>
          </div>
          
          <div class="product-details">
            <div class="product-main">
              <h3 class="product-name">{{ car.name }}</h3>
              <div class="product-price">
                <span class="price-val">{{ (car.price_per_day / 1000).toLocaleString() }}K</span>
                <span class="price-unit">/ ngày</span>
              </div>
            </div>
            
            <div class="product-specs">
              <div class="spec">
                <i class="fa-solid fa-gauge-high"></i>
                <span>{{ car.battery || 'Pin 75kWh' }}</span>
              </div>
              <div class="spec">
                <i class="fa-solid fa-shield-virus"></i>
                <span>An toàn 5★</span>
              </div>
              <div class="spec">
                <i class="fa-solid fa-couch"></i>
                <span>{{ car.seats || 5 }} Chỗ ngồi</span>
              </div>
            </div>
            
            <div class="product-actions">
              <router-link to="/" class="btn-primary-vibe">THUÊ NGAY</router-link>
              <button class="btn-secondary-vibe"><i class="fa-solid fa-plus"></i></button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import baseRequest from '../../core/baseRequest';

const loading = ref(true);
const cars = ref([]);
const selectedType = ref('Tất cả');
const sortBy = ref('default');
const carTypes = ['Tất cả', 'Sedan', 'SUV', 'Hatchback'];

const CAR_IMAGE_MAP = {
    'xpander': '/assets/images/cars/xpander.png',
    'camry': '/assets/images/cars/camry.png',
    'vf8': '/assets/images/cars/vf8.png',
    'vf7': '/assets/images/cars/vf7.png',
    'vf9': '/assets/images/vf9.png',
    'vf 7': '/assets/images/cars/vf7.png',
    'vf 8': '/assets/images/cars/vf8.png',
    'vf 9': '/assets/images/vf9.png'
};

const fetchAllCars = async () => {
    loading.value = true;
    try {
        const response = await baseRequest.get('client/car/data');
        if (response.data && response.data.status) {
            cars.value = response.data.data.map(car => {
                const modelName = (car.ten_xe || car.name || '').toLowerCase();
                let premiumImage = car.hinh_anh;
                for (const [key, path] of Object.entries(CAR_IMAGE_MAP)) {
                    if (modelName.includes(key)) {
                        premiumImage = path;
                        break;
                    }
                }
                return {
                    ...car,
                    name: car.ten_xe || car.name || 'VinFast Model',
                    price_per_day: car.gia_thue || car.price_per_day || 1000000,
                    image: premiumImage ? (premiumImage.startsWith('/') ? premiumImage : `/assets/images/${premiumImage}`) : '/assets/images/vf9.png',
                    type: car.category_name || car.type || 'SUV'
                };
            });
        }
    } catch (error) {
        console.error("Error fetching fleet:", error);
    } finally {
        loading.value = false;
    }
};

const filteredCars = computed(() => {
    let result = [...cars.value];
    if (selectedType.value !== 'Tất cả') {
        result = result.filter(car => car.type === selectedType.value);
    }
    if (sortBy.value === 'price-asc') result.sort((a, b) => a.price_per_day - b.price_per_day);
    else if (sortBy.value === 'price-desc') result.sort((a, b) => b.price_per_day - a.price_per_day);
    return result;
});

onMounted(fetchAllCars);
</script>

<style scoped>
.fleet-page {
    padding-top: 80px;
    background: #fdfdfd;
    min-height: 100vh;
}

/* Hero Section */
.fleet-hero {
    position: relative;
    padding: 120px 0 100px;
    background: #0f172a;
    color: white;
    text-align: center;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('/assets/images/cars/vf9.png') center/70% no-repeat;
    opacity: 0.15;
    filter: blur(10px);
}

.hero-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at center, transparent 0%, #0f172a 80%);
}

.hero-content { position: relative; z-index: 1; }

.badge-premium {
    display: inline-block;
    padding: 6px 20px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 24px;
    background: rgba(255,255,255,0.05);
}

.hero-content h1 {
    font-size: 4.5rem;
    font-weight: 900;
    letter-spacing: -2px;
    margin-bottom: 16px;
}

.hero-content h1 span { color: var(--color-primary); }

.hero-content p {
    font-size: 1.3rem;
    opacity: 0.7;
    max-width: 700px;
    margin: 0 auto 48px;
}

.hero-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 40px;
}

.stat-val { display: block; font-size: 1.8rem; font-weight: 900; color: var(--color-primary); }
.stat-label { font-size: 0.8rem; opacity: 0.5; font-weight: 700; text-transform: uppercase; }
.stat-divider { width: 1px; height: 40px; background: rgba(255,255,255,0.1); }

/* Controls */
.fleet-controls {
    margin-top: -40px;
    position: relative;
    z-index: 2;
}

.controls-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 40px;
    background: white !important;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.05);
}

.filter-side { display: flex; align-items: center; gap: 20px; }
.filter-side .label { font-weight: 800; color: #64748b; font-size: 0.9rem; }

.vibe-pills { display: flex; gap: 10px; }
.pill {
    padding: 8px 24px;
    border: 1px solid #f1f5f9;
    background: #f8fafc;
    border-radius: 100px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    transition: 0.3s;
}

.pill.active { background: #0f172a; color: white; border-color: #0f172a; }

.sort-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f1f5f9;
    padding: 8px 16px;
    border-radius: 12px;
}

.sort-box select { background: transparent; border: none; outline: none; font-weight: 700; color: #1e293b; }

/* Showcase Grid */
.fleet-showcase { padding: 80px 0 120px; }

.showcase-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 40px;
}

.product-card {
    background: white;
    border-radius: 32px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid #f1f5f9;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 30px 60px rgba(0,0,0,0.08);
}

.product-img-box {
    position: relative;
    padding: 40px;
    background: #f8fafc;
    height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-img-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 15px 30px rgba(0,0,0,0.1));
}

.product-tag {
    position: absolute;
    top: 20px; left: 20px;
    padding: 4px 12px;
    background: white;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--color-primary);
    text-transform: uppercase;
}

.product-details { padding: 30px; }

.product-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.product-name { font-size: 1.6rem; font-weight: 800; color: #0f172a; }

.product-price { display: flex; flex-direction: column; align-items: flex-end; }
.price-val { font-size: 1.4rem; font-weight: 900; color: #0f172a; }
.price-unit { font-size: 0.75rem; color: #94a3b8; font-weight: 700; }

.product-specs {
    display: flex;
    justify-content: space-between;
    padding: 16px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 24px;
}

.spec { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.spec i { font-size: 1.1rem; color: #94a3b8; }
.spec span { font-size: 0.75rem; font-weight: 700; color: #64748b; }

.product-actions { display: flex; gap: 12px; }

.btn-primary-vibe {
    flex: 1;
    padding: 14px;
    background: #0f172a;
    color: white;
    text-align: center;
    border-radius: 16px;
    font-weight: 800;
    font-size: 0.9rem;
    transition: 0.3s;
}

.btn-primary-vibe:hover { background: var(--color-primary); transform: scale(1.02); }

.btn-secondary-vibe {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #0f172a;
    cursor: pointer;
    transition: 0.3s;
}

.btn-secondary-vibe:hover { background: #f8fafc; border-color: #0f172a; }

@media (max-width: 1024px) {
    .hero-content h1 { font-size: 3rem; }
    .controls-wrapper { flex-direction: column; gap: 20px; }
}
</style>
