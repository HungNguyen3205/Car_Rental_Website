<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="forest-bg">
        <!-- Sunbeams overlay -->
        <div class="sunbeam-overlay"></div>
        <!-- Moving Car -->
        <img 
          src="/assets/images/hero.png" 
          class="hero-car-image" 
          alt="VinFast Fleet"
        />
      </div>
      
      <div class="hero-content app-container">
        <div class="hero-text animate-up">
          <span class="badge">#1 Nền tảng thuê xe điện</span>
          <h1>Kiến Tạo Hành Trình <br/><span>Xanh & Thông Minh</span></h1>
          <p>Trải nghiệm dòng xe điện VinFast hiện đại, bền bỉ và đẳng cấp ngay hôm nay.</p>
          <div class="hero-btns">
            <router-link to="/fleet" class="btn-primary">Khám phá ngay <i class="fa-solid fa-arrow-right"></i></router-link>
            <router-link to="/process" class="btn-outline">Xem quy trình</router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Search / Filter Component -->
    <section id="search-section" class="search-box-wrapper app-container">
      <div class="search-box card">
        <div class="search-field city-selector-wrapper" v-click-outside="() => showCityDropdown = false">
          <label><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> Tỉnh/Thành giao xe</label>
          <div class="custom-dropdown" @click.stop="toggleCityDropdown">
            <div class="selected-city" :class="{ 'placeholder': !searchData.city }">
              <span class="city-name">{{ loadingProvinces ? 'Đang tải...' : (provinces.find(c => c.id === searchData.city)?.name || 'Chọn khu vực...') }}</span>
              <i v-if="loadingProvinces" class="fa-solid fa-spinner fa-spin ml-auto"></i>
              <i v-else class="fa-solid fa-chevron-down ml-auto" :class="{ 'rotate': showCityDropdown }"></i>
            </div>
            <Transition name="fade-slide">
              <div v-if="showCityDropdown" class="dropdown-list scrollable-dropdown">
                <div class="dropdown-search" @click.stop>
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input 
                    type="text" 
                    v-model="provinceSearch" 
                    placeholder="Tìm kiếm tỉnh thành..." 
                    @click.stop
                  />
                </div>
                <div class="dropdown-scroll-area">
                  <div 
                    v-for="city in filteredProvinces" 
                    :key="city.id" 
                    class="dropdown-item"
                    @click.stop="selectCity(city.id)"
                    :class="{ 'active': searchData.city === city.id }"
                  >
                    <i class="fa-solid fa-location-dot"></i> {{ city.name }}
                  </div>
                  <div v-if="filteredProvinces.length === 0" class="no-results">
                    Không tìm thấy kết quả
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
        <div class="search-separator"></div>
        <div class="search-field flex-grow-1 relative">
          <label>Địa chỉ nhận xe chi tiết</label>
          <div class="input-with-icon premium-input-wrapper">
            <i class="fa-solid fa-map-location-dot input-icon-left"></i>
            <input 
              type="text" 
              v-model="searchData.address" 
              @input="onAddressInput"
              @focus="showPopular = true"
              @blur="setTimeout(() => showPopular = false, 200)"
              :placeholder="searchData.city ? 'Nhập địa chỉ, sân bay, tên đường...' : 'Vui lòng chọn thành phố trước'" 
              :disabled="!searchData.city"
              :class="{ 'disabled-field': !searchData.city }"
            />
            <div v-if="loadingSuggestions" class="loader-inline"></div>
          </div>
          <!-- Suggestions Dropdown (Glassmorphism 2.0) -->
          <Transition name="fade-slide">
            <div v-if="suggestions.length > 0 || (showPopular && !searchData.address)" class="suggestions-list">
              <div v-if="suggestions.length > 0">
                <div class="pop-title"><i class="fa-solid fa-magnifying-glass"></i> Kết quả tìm thấy</div>
                <li v-for="(item, index) in suggestions" :key="index" @click="selectAddress(item)" class="suggestion-item">
                  <div class="suggestion-icon"><i class="fa-solid fa-location-dot"></i></div>
                  <div class="suggestion-content">
                    <span class="main-text">{{ item.main_text }}</span>
                    <span class="sub-text">{{ item.sub_text }}</span>
                  </div>
                </li>
              </div>
              <div v-else-if="showPopular" class="popular-locations">
                <div class="pop-title"><i class="fa-solid fa-star"></i> Vị trí phổ biến</div>
                <li v-for="loc in filteredPopularLocations" :key="loc" @click="selectPopular(loc)" class="suggestion-item">
                  <div class="suggestion-icon"><i class="fa-solid fa-plane-departure" v-if="loc.includes('Sân bay')"></i><i class="fa-solid fa-train" v-else-if="loc.includes('Ga')"></i><i class="fa-solid fa-map-pin" v-else></i></div>
                  <div class="suggestion-content">
                    <span class="main-text">{{ loc }}</span>
                  </div>
                </li>
              </div>
            </div>
          </Transition>
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian lấy</label>
          <VueDatePicker 
            v-model="searchData.startDate" 
            :min-date="new Date()"
            placeholder="Chọn ngày lấy"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>
        <div class="search-separator"></div>
        <div class="search-field">
          <label>Thời gian trả</label>
          <VueDatePicker 
            v-model="searchData.endDate" 
            :min-date="searchData.startDate || new Date()"
            placeholder="Chọn ngày trả"
            auto-apply
            :teleport="true"
            format="dd/MM/yyyy HH:mm"
          />
        </div>
        <button class="btn-primary search-btn" @click="handleSearch">Tìm Kiếm</button>
      </div>
      <div v-if="dateError" class="error-msg">{{ dateError }}</div>
    </section>

    <!-- Car Listing Section -->
    <section id="results-section" v-if="hasSearched" class="fleet-section app-container" style="margin-top: 40px;">
      <div class="section-heading">
        <span class="sub-title">Đội ngũ xe điện VinFast</span>
        <h2>Chọn Xe Của Bạn</h2>
        <p>Những mẫu xe điện thông minh, dẫn đầu xu thế</p>
      </div>

      <div class="car-grid" v-if="!loading && cars.length > 0">
        <div class="car-card premium-card" v-for="car in cars" :key="car.id">
          <div class="car-img-container">
            <img :src="car.image" :alt="car.name" />
          </div>
          <div class="car-info">
            <div class="car-header">
              <h3 class="car-name">{{ car.name }}</h3>
              <span class="car-badge">{{ car.type }}</span>
            </div>
            
            <div class="car-specs-minimal">
              <div class="spec-item">
                <i class="fa-solid fa-battery-full"></i>
                <span>{{ car.battery || '75 kWh' }}</span>
              </div>
              <div class="spec-item">
                <i class="fa-solid fa-users"></i>
                <span>{{ car.seats || 5 }} Chỗ</span>
              </div>
            </div>
            
            <div class="car-footer">
              <div class="price-box">
                <span class="label">Giá từ</span>
                <span class="price">{{ (car.price_per_day / 1000).toLocaleString() }}k</span>
                <span class="unit">/ngày</span>
              </div>
              <button class="btn-vinfast" @click="toggleInlineBooking(car)">
                {{ expandingCarId === car.id ? 'ĐÓNG LẠI' : 'ĐẶT NGAY' }}
              </button>
            </div>

            <!-- Inline Booking Card -->
            <Transition name="fade-slide">
                <div v-if="expandingCarId === car.id" class="inline-booking-card animate-in">
                    <div class="inline-header">
                        <h4><i class="fa-solid fa-calendar-check"></i> Xác nhận lịch trình</h4>
                    </div>
                    <div class="inline-form">
                        <div class="form-group-minimal">
                            <label>Ngày trả xe (Thay đổi nếu cần)</label>
                            <VueDatePicker 
                                v-model="searchData.endDate" 
                                :min-date="searchData.startDate"
                                auto-apply
                                :teleport="true"
                                format="dd/MM/yyyy HH:mm"
                            />
                        </div>
                        <div class="inline-total mt-4">
                            <div class="total-row">
                                <span>Tổng cộng ({{ calculateTotal(car) }} ngày)</span>
                                <span class="total-price-inline">{{ (calculateTotal(car) * car.price_per_day).toLocaleString() }} đ</span>
                            </div>
                        </div>
                        <button 
                            class="btn-primary w-full mt-4" 
                            :disabled="!isLocationValid(car)"
                            @click="confirmBooking(car)"
                        >
                            Xác nhận & Gửi tới AI
                        </button>
                    </div>
                </div>
            </Transition>
          </div>
        </div>
      </div>
      
      <!-- Empty State (Glassmorphism inspired) -->
      <div v-else-if="!loading && cars.length === 0" class="empty-state-card glass-card animate-in">
          <div class="empty-layout">
              <div class="empty-visual">
                  <div class="empty-icon-wrap">
                      <i class="fa-solid fa-car-burst"></i>
                  </div>
                  <div class="empty-pulse"></div>
              </div>
              <div class="empty-text">
                  <h3>Chúng tôi chưa có xe tại {{ provinces.find(p => p.id === searchData.city)?.name || 'khu vực này' }}</h3>
                  <p>Hãy thử chọn một Thành phố lớn khác hoặc mở rộng tiêu chí tìm kiếm của bạn để khám phá những chiếc xe tuyệt vời từ VinFast.</p>
                  <div class="empty-actions">
                      <button class="btn-primary" @click="handleResetSearch">
                        <i class="fa-solid fa-rotate-left"></i> Đặt lại tìm kiếm
                      </button>
                  </div>
              </div>
          </div>
      </div>

      <div v-else class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải dữ liệu...</p>
      </div>
    </section>

    <!-- Rental Process Section -->
    <section id="process-section" class="process-section app-container" style="margin-top: 100px;">
      <div class="section-heading">
        <span class="sub-title">Quy trình đơn giản</span>
        <h2>Thuê xe chỉ với 4 bước</h2>
      </div>
      <div class="process-grid">
        <div class="process-item">
          <div class="icon-box blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
          </div>
          <h3>1. Chọn xe</h3>
          <p>Tìm kiếm và lựa chọn chiếc xe ưng ý từ bộ sưu tập đa dạng của chúng tôi.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box green">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <h3>2. Đặt lịch</h3>
          <p>Chọn thời gian thuê, địa điểm giao nhận xe và điền thông tin cá nhân.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box yellow">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          </div>
          <h3>3. Thanh toán</h3>
          <p>Xác nhận đơn và thực hiện thanh toán an toàn qua nhiều hình thức.</p>
        </div>
        <div class="process-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
        <div class="process-item">
          <div class="icon-box red">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
          </div>
          <h3>4. Nhận xe</h3>
          <p>Nhận xe tại địa điểm đã chọn và bắt đầu hành trình tuyệt vời của bạn.</p>
        </div>
      </div>
    </section>


  </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch, nextTick, computed, onUnmounted } from 'vue';
import baseRequest from '../../core/baseRequest';
import axios from 'axios';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

// Use a custom directive for clicking outside
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener("click", el.clickOutsideEvent);
  },
};

const CAR_IMAGE_MAP = {
    'xpander': '/assets/images/cars/xpander.png',
    'camry': '/assets/images/cars/camry.png',
    'audi a6': '/assets/images/cars/audi_a6.png',
    'mercedes': '/assets/images/cars/mercedes_c200.png',
    'santafe': '/assets/images/cars/santafe.png',
    'mazda cx-5': '/assets/images/cars/cx5.png',
    'vf8': '/assets/images/cars/vf8.png',
    'vf7': '/assets/images/cars/vf7.png',
    'vf9': '/assets/images/vf9.png',
    'vf 7': '/assets/images/cars/vf7.png',
    'vf 8': '/assets/images/cars/vf8.png',
    'vf 9': '/assets/images/vf9.png'
};

const cars = ref([]);
const hasSearched = ref(false);

const loading = ref(false);
const dateError = ref('');
const suggestions = ref([]);
const loadingSuggestions = ref(false);
const expandingCarId = ref(null); // Track inline booking state
const selectedCar = ref(null);
const bookingLoading = ref(false);
const showPopular = ref(false);
const POPULAR_LOCATIONS_MAP = {
    'Hà Nội': ['Sân bay Nội Bài', 'Hồ Hoàn Kiếm', 'Ga Hà Nội', 'Lăng Bác', 'Văn Miếu'],
    'Hồ Chí Minh': ['Sân bay Tân Sơn Nhất', 'Dinh Độc Lập', 'Ga Sài Gòn', 'Chợ Bến Thành', 'Phố đi bộ Nguyễn Huệ'],
    'Đà Nẵng': ['Sân bay Đà Nẵng', 'Ga Đà Nẵng', 'Cầu Rồng', 'Bán đảo Sơn Trà', 'Bà Nà Hills'],
    'Khánh Hòa': ['Sân bay Cam Ranh', 'Ga Nha Trang', 'Tháp Bà Ponagar', 'Vinpearl Nha Trang', 'Chợ Đầm'],
    'Cần Thơ': ['Sân bay Cần Thơ', 'Bến Ninh Kiều', 'Chợ nổi Cái Răng'],
    'Lâm Đồng': ['Sân bay Liên Khương', 'Hồ Xuân Hương', 'Chợ Đà Lạt', 'Thung lũng Tình Yêu']
};

const filteredPopularLocations = computed(() => {
  const cityName = provinces.value.find(p => p.id === searchData.city)?.name || '';
  if (!cityName) return [];
  
  for (const cityKey in POPULAR_LOCATIONS_MAP) {
    if (cityName.includes(cityKey)) return POPULAR_LOCATIONS_MAP[cityKey];
  }
  return ['Trung tâm thành phố', 'Bưu điện thành phố'];
});

// New UI State
const showCityDropdown = ref(false);
const leafletLoaded = ref(false);

const scrollY = ref(0);
// Motion disabled as per user request
const carTranslateX = computed(() => 0); 

const provinceSearch = ref('');
const filteredProvinces = computed(() => {
  if (!provinceSearch.value) return provinces.value;
  const query = provinceSearch.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  return provinces.value.filter(p => {
    const name = p.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    return name.includes(query);
  });
});

const handleScroll = () => {
  scrollY.value = window.scrollY;
};

const provinces = ref([]);
const loadingProvinces = ref(false);

const fetchProvinces = async () => {
  loadingProvinces.value = true;
  try {
    const response = await axios.get('https://provinces.open-api.vn/api/p/');
    provinces.value = response.data.map(p => ({
      id: p.code.toString(),
      name: p.name
    }));
  } catch (error) {
    console.error("Error fetching provinces:", error);
    // Fallback if API fails
    provinces.value = [
      { id: 'Hồ Chí Minh', name: 'TP. Hồ Chí Minh' },
      { id: 'Hà Nội', name: 'Hà Nội' },
      { id: 'Đà Nẵng', name: 'Đà Nẵng' }
    ];
  } finally {
    loadingProvinces.value = false;
  }
};

const toggleCityDropdown = () => {
  showCityDropdown.value = !showCityDropdown.value;
};

const selectCity = (cityId) => {
  searchData.city = cityId;
  showCityDropdown.value = false;
  // handleSearch(); // Removed automatic re-filter as per user request
};

let timeout = null;

const searchData = reactive({
  city: '',
  address: '',
  startDate: new Date(),
  endDate: new Date(new Date().getTime() + 24 * 60 * 60 * 1000)
});

const fetchCars = async (params = {}) => {
  loading.value = true;
  try {
    const response = await baseRequest.get('client/car/data', { params });
    if (response.data && response.data.status) {
      const backendCars = response.data.data;
      if (backendCars && backendCars.length > 0) {
        cars.value = backendCars.map(car => {
          // Smart Image Mapping with safety checks
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
            // Ensure essential fields exist
            name: car.ten_xe || car.name || 'VinFast Model',
            price_per_day: car.gia_thue || car.price_per_day || 1000000,
            image: premiumImage ? (premiumImage.startsWith('/') ? premiumImage : `/assets/images/${premiumImage}`) : '/assets/images/vf9.png',
            cityDisplay: (() => {
                const c = (car.city || '').toLowerCase();
                if (c === 'sg') return 'TP. Hồ Chí Minh';
                if (c === 'hn') return 'Hà Nội';
                if (c === 'dn') return 'Đà Nẵng';
                if (c === 'nt') return 'Nha Trang';
                return car.city;
            })()
          };
        });
      } else {
        cars.value = []; // Explicitly clear if no results found
      }
    }
  } catch (error) {
    console.error("Error fetching cars:", error);
  } finally {
    loading.value = false;
  }
};


onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  // fetchCars(); // Removed automatic fetch on mount as per user request
  fetchProvinces();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

watch(() => searchData.endDate, (newVal) => {
  if (newVal && searchData.startDate && newVal <= searchData.startDate) {
    dateError.value = 'Ngày trả phải sau ngày lấy xe ít nhất 1 giờ!';
  } else {
    dateError.value = '';
  }
});

// Automatic State Clearing (Xóa dấu vết)
watch(() => searchData.city, () => {
  searchData.address = '';
  dateError.value = '';
});

const onAddressInput = (e) => {
  const query = e.target.value;
  if (query.length < 3) {
    suggestions.value = [];
    return;
  }

  clearTimeout(timeout);
    timeout = setTimeout(async () => {
      loadingSuggestions.value = true;
      try {
        const currentCar = cars.value.find(c => c.id === expandingCarId.value);
        const cityName = currentCar?.cityDisplay || currentCar?.city || provinces.value.find(p => p.id === searchData.city)?.name || '';
        
        // Using Nominatim with Vietnamese support
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + (cityName ? ' ' + cityName : ''))}&addressdetails=1&limit=5&countrycodes=vn&accept-language=vi`);
        const data = await res.json();
        
        if (data && data.length > 0) {
            suggestions.value = data.map(item => {
                const addr = item.address;
                // Try to construct a nice 2-line display
                const mainName = addr.amenity || addr.building || addr.road || addr.suburb || item.display_name.split(',')[0];
                const subParts = [];
                if (addr.suburb) subParts.push(addr.suburb);
                if (addr.city || addr.town) subParts.push(addr.city || addr.town);
                if (addr.state) subParts.push(addr.state);
                
                return {
                    display_name: item.display_name,
                    main_text: mainName,
                    sub_text: subParts.length > 0 ? subParts.join(', ') : item.display_name.split(',').slice(1, 3).join(', ')
                };
            });
        } else {
            suggestions.value = [];
        }
      } catch (err) {
      console.error("Map API Error:", err);
    } finally {
      loadingSuggestions.value = false;
    }
  }, 500);
};

const selectAddress = (item) => {
  searchData.address = item.display_name;
  suggestions.value = [];
  showPopular.value = false;
};


const selectPopular = (loc) => {
  searchData.address = loc;
  showPopular.value = false;
  // For popular, we just set address without map since we don't have coords easily
};

const toLocalISO = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const offset = d.getTimezoneOffset() * 60000;
  const localDate = new Date(d.getTime() - offset);
  return localDate.toISOString().slice(0, 19).replace('T', ' ');
};

const scrollSearch = () => {
  nextTick(() => {
    const el = document.getElementById('search-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

const scrollResults = () => {
  nextTick(() => {
    const el = document.getElementById('results-section');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  });
};

const handleSearch = () => {
  if (!searchData.startDate || !searchData.endDate) {
    dateError.value = 'Vui lòng chọn đầy đủ thời gian!';
    return;
  }
  if (searchData.endDate <= searchData.startDate) {
    dateError.value = 'Ngày trả không hợp lệ!';
    return;
  }

  const cityName = provinces.value.find(p => p.id === searchData.city)?.name || '';
  
  // Validation: Ensure address matches city
  if (!isLocationValid({ city: cityName }, searchData.address)) {
      dateError.value = `Địa chỉ không thuộc ${cityName}!`;
      return;
  }

  const params = {
    city: cityName, // Send name to BE for LIKE query
    start_date: toLocalISO(searchData.startDate), 
    end_date: toLocalISO(searchData.endDate),
    search: '' 
  };
  
  hasSearched.value = true;
  fetchCars(params);
  scrollResults();
};

const handleResetSearch = () => {
  searchData.city = '';
  searchData.address = '';
  searchData.startDate = new Date();
  searchData.endDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
  hasSearched.value = false;
  cars.value = [];
};

// Supporting validation helper (overloaded or shared logic)
const isLocationValid = (carOrCity, addressInput = null) => {
    const addr = addressInput || searchData.address;
    if (!addr) return false;
    
    // Support both Car objects (car.cityDisplay) and search results (searchData.city name)
    const targetCity = carOrCity.cityDisplay || carOrCity.city || carOrCity.name || '';
    if (!targetCity) return true; 

    // Function to strip common VT prefixes and handle synonyms
    const cleanStr = (str) => {
        return str.toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/^(thanh pho|tinh|huyen|quan|phuong|xa)\s+/g, "")
            .trim();
    };

    const query = cleanStr(addr);
    const city = cleanStr(targetCity);
    
    // 1. Direct match
    if (query.includes(city) || city.includes(query)) return true;

    // 2. Normalize and check synonyms
    const normalize = (s) => {
        if (s.includes("ho chi minh") || s === "sg" || s.includes("sai gon")) return "hcm";
        if (s.includes("ha noi") || s === "hn") return "hn";
        if (s.includes("da nang") || s === "dn") return "dn";
        if (s.includes("nha trang") || s === "nt" || s.includes("khanh hoa")) return "khanhhoa";
        if (s.includes("da lat") || s.includes("lam dong")) return "lamdong";
        return s;
    };

    const normQuery = normalize(query);
    const normCity = normalize(city);
    
    if (normQuery.includes(normCity) || normCity.includes(normQuery)) return true;

    // 3. Deep Alias Mapping for major provinces
    const cityAliases = {
        'khanhhoa': ['nha trang', 'cam ranh', 'dien khanh', 'van ninh'],
        'lamdong': ['da lat', 'bao loc', 'duc trong', 'di linh'],
        'hcm': ['sai gon', 'quan 1', 'thu duc', 'go vap', 'binh chanh', 'tan son nhat'],
        'dn': ['ngu hanh son', 'son tra', 'lien chieu', 'thanh khe'],
        'hn': ['hoan kiem', 'ba dinh', 'tay ho', 'dong da', 'cau giay', 'noi bai']
    };

    const aliases = cityAliases[normCity] || [];
    return aliases.some(alias => query.includes(alias));
};

const toggleInlineBooking = (car) => {
  // Always allowed to toggle to see details
  if (expandingCarId.value === car.id) {
    expandingCarId.value = null;
    selectedCar.value = null;
  } else {
    expandingCarId.value = car.id;
    selectedCar.value = car;
  }
};

const calculateTotal = (car = null) => {
  const targetCar = car || selectedCar.value;
  if (!targetCar) return 0;
  const start = new Date(searchData.startDate);
  const end = new Date(searchData.endDate);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) || 1;
  return diffDays;
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('vi-VN');
};

const confirmBooking = async (car) => {
    const token = localStorage.getItem('token_client');
    if (!token) {
        alert("Vui lòng đăng nhập để thực hiện đặt xe!");
        return;
    }

    if (!isLocationValid(car)) {
        alert(`Vui lòng nhập địa chỉ thuộc thành phố ${car.cityDisplay || car.city}!`);
        return;
    }
    
    bookingLoading.value = true;
    try {
        const res = await baseRequest.post('user/bookings/dat-lich', {
            car_id: car.id,
            start_date: toLocalISO(searchData.startDate),
            end_date: toLocalISO(searchData.endDate),
            dia_chi_nhan_xe: searchData.address || 'Tại showroom',
            dia_chi_tra_xe: searchData.address || 'Tại showroom',
            ly_do_thue: 'Đặt xe qua website'
        });

        if (res.data.status) {
            alert("Đặt xe thành công! Vui lòng kiểm tra hóa đơn và thanh toán trong khung chat.");
            // Success! Trigger chatbot to show invoice
            window.dispatchEvent(new CustomEvent('show-booking-invoice', {
                detail: { booking: res.data.data }
            }));
            expandingCarId.value = null;
        } else {
            alert(res.data.message);
        }
    } catch (error) {
        console.error("Booking failed", error);
        const errorMsg = error.response?.data?.message || "Có lỗi xảy ra khi đặt xe. Vui lòng thử lại!";
        alert(errorMsg);
    } finally {
        bookingLoading.value = false;
    }
};

const scrollToProcess = () => {
  const el = document.getElementById('process-section');
  if (el) el.scrollIntoView({ behavior: 'smooth' });
};
</script>

<style scoped>
/* Hero Section Redesign - Static Full Screen */
.hero-section {
    position: relative;
    height: 100vh;
    min-height: 800px;
    background: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
    overflow: hidden;
    display: flex;
    align-items: center;
}

.forest-bg {
    position: absolute;
    top: 0;
    right: 0;
    width: 65%;
    height: 100%;
    z-index: 1;
    mask-image: linear-gradient(to right, transparent, black 30%);
    -webkit-mask-image: linear-gradient(to right, transparent, black 30%);
}

.hero-car-image {
    position: absolute;
    bottom: 0;
    right: 0%;
    width: 100%;
    max-width: 1200px;
    filter: drop-shadow(0 40px 100px rgba(0, 0, 0, 0.3));
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    width: 100%;
}

.hero-text {
    max-width: 650px;
}

.hero-text h1 {
  font-size: 4.2rem;
  line-height: 1.1;
  margin: 20px 0;
  color: #0f172a;
}

.badge {
    display: inline-flex;
    padding: 8px 16px;
    background: rgba(59, 130, 246, 0.1);
    color: var(--color-primary);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 24px;
}

.hero-text h1 {
    font-size: 4.5rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -3px;
    color: var(--color-text-main);
    margin-bottom: 24px;
}

.hero-text h1 span {
    color: var(--color-primary);
    background: linear-gradient(120deg, var(--color-primary), var(--color-primary-glow));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-text p {
    font-size: 1.25rem;
    color: var(--color-text-muted);
    font-weight: 500;
    margin-bottom: 40px;
}

.hero-btns {
    display: flex;
    gap: 20px;
}

/* Search Box Perfection */
.search-box-wrapper {
    position: relative;
    z-index: 10;
    margin-top: -64px;
}

.search-box {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(24px);
    border-radius: 32px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.search-field {
    flex: 1;
}

.flex-2 {
    flex: 2;
}

.search-field {
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: relative;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-field:hover {
    background: rgba(0, 0, 0, 0.02);
}

.premium-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    background: rgba(0, 0, 0, 0.03);
    border-radius: 12px;
    border: 1.5px solid transparent;
    transition: all 0.3s;
}

.premium-input-wrapper:focus-within {
    background: white;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.input-icon-left {
    color: var(--color-primary);
    font-size: 1.1rem;
}

.input-with-icon input {
    width: 100%;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    font-weight: 700;
    font-size: 1rem;
    color: var(--color-text-main);
    padding: 12px 0;
}

.search-separator {
    width: 1px;
    height: 48px;
    background: rgba(0, 0, 0, 0.06);
    align-self: center;
}

.search-btn {
    height: 64px;
    padding: 0 44px;
    border-radius: 24px;
    background: var(--grad-active);
    color: white;
    font-weight: 800;
    font-family: var(--font-heading);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
    transition: all 0.3s;
    margin-left: 8px;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(59, 130, 246, 0.45);
}

.error-msg {
  position: absolute;
  bottom: -30px;
  left: 50%;
  transform: translateX(-50%);
  color: #ef4444;
  font-size: 0.85rem;
  font-weight: 700;
}

.suggestions-list {
    position: absolute;
    top: calc(100% + 12px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    padding: 12px;
    z-index: 1001;
    border: 1px solid var(--color-border);
    max-height: 400px;
    overflow-y: auto;
}

.suggestion-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 4px;
}

.suggestion-item:hover {
    background: var(--bg-main);
    transform: translateX(4px);
}

.suggestion-icon {
    width: 36px;
    height: 36px;
    background: #eff6ff;
    color: var(--color-primary);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.suggestion-content {
    display: flex;
    flex-direction: column;
}

.suggestion-content .main-text {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--color-text-main);
}

.suggestion-content .sub-text {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    font-weight: 500;
}

.pop-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin: 12px 16px 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-btn {
    height: 64px;
    padding: 0 40px;
    border-radius: 24px;
    background: var(--grad-active);
    color: white;
    font-weight: 800;
    font-family: var(--font-heading);
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
}

.suggestions-list li svg {
  margin-top: 3px;
  flex-shrink: 0;
  color: #94a3b8;
}

.loader-inline {
  width: 16px;
  height: 16px;
  border: 2px solid #f1f5f9;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  vertical-align: middle;
  margin-left: 5px;
}

/* Dropdown Perfection */
.dropdown-list {
  position: absolute;
  top: calc(100% + 12px);
  left: 0;
  width: 320px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid var(--color-border);
  z-index: 1100;
  overflow: hidden;
  padding: 8px;
}

.dropdown-search {
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 8px;
}

.dropdown-search i {
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.dropdown-search input {
  border: none;
  outline: none;
  width: 100%;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text-main);
  background: transparent;
}

.dropdown-scroll-area {
  max-height: 320px;
  overflow-y: auto;
  padding-right: 4px;
}

/* Custom Scrollbar */
.dropdown-scroll-area::-webkit-scrollbar {
  width: 6px;
}
.dropdown-scroll-area::-webkit-scrollbar-track {
  background: transparent;
}
.dropdown-scroll-area::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dropdown-scroll-area::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-text-main);
  transition: all 0.2s;
  cursor: pointer;
}

.dropdown-item:hover {
  background: var(--bg-main);
  color: var(--color-primary);
}

.dropdown-item.active {
  background: var(--bg-active);
  color: var(--color-primary);
}

.no-results {
  padding: 20px;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 0.9rem;
  font-weight: 600;
}

/* Process Section */
.process-section {
  padding: 100px 24px;
  background: white;
}

.sub-title {
  display: block;
  color: var(--color-primary);
  font-weight: 700;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 12px;
}

.process-grid {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 60px;
  gap: 20px;
}

.process-item {
  flex: 1;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.icon-box {
  width: 80px;
  height: 80px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.process-item:hover .icon-box {
  transform: translateY(-10px) rotate(5deg);
}

.icon-box.blue { background: rgba(37, 99, 235, 0.1); color: var(--color-primary); }
.icon-box.green { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.icon-box.yellow { background: rgba(234, 179, 8, 0.1); color: #ca8a04; }
.icon-box.red { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

.process-item h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 12px;
}

.process-item p {
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.5;
}

.process-arrow {
  color: #cbd5e1;
}

/* Section Headings */
.section-heading { text-align: center; margin-bottom: 60px; }
.section-heading h2 { font-size: 2.8rem; font-weight: 800; color: var(--color-text-main); letter-spacing: -1.5px; margin-bottom: 12px; }
.section-heading p { color: var(--color-text-muted); font-size: 1.1rem; }

/* Fleet Grid */
.fleet-section { padding: 100px 0; background: var(--bg-main); }
.car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 32px; padding: 0 20px; }

.premium-card {
  background: white;
  border-radius: 20px;
  border: 1px solid var(--color-border);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.premium-card:hover {
  transform: translateY(-10px);
  box-shadow: var(--shadow-premium);
  border-color: var(--color-primary);
}

.car-img-container {
    height: 220px;
    padding: 0px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 50% 120%, rgba(59, 130, 246, 0.08), transparent 70%);
    position: relative;
    overflow: hidden;
}

.car-img-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 10%;
    right: 10%;
    height: 20px;
    background: radial-gradient(ellipse at center, rgba(0,0,0,0.1) 0%, transparent 70%);
    filter: blur(5px);
    z-index: 1;
}

.car-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
}

.premium-card:hover .car-img-container img {
  transform: scale(1.15);
}

/* Inline Booking Card Styles */
.inline-booking-card {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px dashed #e2e8f0;
    animation: slideDown 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.inline-header h4 {
    font-size: 1rem;
    font-weight: 800;
    color: var(--color-primary);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group-minimal label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    margin-bottom: 8px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
}

.total-row span:first-child {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-text-muted);
}

.total-price-inline {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--color-primary);
}

.validation-hint {
    display: block;
    margin-top: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #ef4444;
}

.location-input-wrapper {
    position: relative;
}

.car-info { padding: 28px; }

.car-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.car-name { font-size: 1.4rem; font-weight: 800; color: var(--color-text-main); }
.car-badge { font-size: 0.7rem; font-weight: 800; color: #1e40af; background: #dbeafe; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }

.car-specs-minimal {
  display: flex;
  gap: 20px;
  margin-bottom: 24px;
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-muted);
  font-size: 0.9rem;
  font-weight: 600;
}

.spec-item i { color: var(--color-primary); }

.car-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
}

.price-box {
  display: flex;
  flex-direction: column;
}

.price-box .label { font-size: 0.7rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; }
.price-box .price { font-size: 1.4rem; font-weight: 800; color: var(--color-text-main); margin: 2px 0; }
.price-box .unit { font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); }

.btn-vinfast {
  background: var(--grad-active);
  color: white;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 0.85rem;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
}

.btn-vinfast:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
}

.modal-content {
  background: white;
  width: 100%;
  max-width: 550px;
  border-radius: 24px;
  padding: 40px;
  position: relative;
}

.animate-in {
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalScale {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.close-btn {
  position: absolute;
  top: 24px;
  right: 24px;
  background: #f1f5f9;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-header {
  margin-bottom: 32px;
}

.modal-header h2 {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
}

.modal-header p {
  color: #64748b;
}

.car-preview {
  display: flex;
  gap: 20px;
  background: #f8fafc;
  padding: 20px;
  border-radius: 16px;
  margin-bottom: 24px;
  align-items: center;
}

.car-preview img {
  width: 120px;
  height: 70px;
  object-fit: contain;
}

.car-meta h3 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.type-tag {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--color-primary);
  font-weight: 700;
  background: rgba(37, 99, 235, 0.1);
  padding: 4px 10px;
  border-radius: 6px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 40px;
}

.info-item label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.info-item p {
  font-weight: 600;
  color: #1e293b;
}

.info-item.total {
  grid-column: span 2;
  background: #f1f5f9;
  padding: 20px;
  border-radius: 16px;
  text-align: center;
}

.total-price {
  font-size: 1.5rem !important;
  color: var(--color-primary) !important;
  font-weight: 800 !important;
}

.modal-input {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    margin-top: 5px;
    font-weight: 600;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}

.modal-input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.location-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    margin-top: 5px;
}

.input-icon-inside {
    position: absolute;
    left: 16px;
    color: var(--color-primary);
    font-size: 1.1rem;
    z-index: 2;
}

.modal-input.with-icon {
    padding-left: 45px;
    margin-top: 0;
}

.loader-inline-input {
    position: absolute;
    right: 16px;
    width: 18px;
    height: 18px;
    border: 3px solid #f1f5f9;
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.suggestions-list-inline {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    padding: 8px;
    z-index: 1000;
    border: 1px solid #e2e8f0;
    max-height: 280px;
    overflow-y: auto;
}

.validation-hint {
    position: absolute;
    bottom: -22px;
    left: 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: #ef4444;
    display: flex;
    align-items: center;
    gap: 5px;
}

.modal-actions {
  display: flex;
  gap: 16px;
}

.modal-actions button {
  flex: 1;
  padding: 16px;
  font-weight: 700;
}

/* Datepicker Overrides - FIX OVERLAP */
:deep(.dp__theme_light) {
  --dp-primary-color: var(--color-primary);
  --dp-border-radius: 8px;
  --dp-button-height: 40px;
}
:deep(.dp__input) {
  border: none !important;
  font-weight: 600;
  font-size: 1rem;
  padding: 4px 0 4px 35px !important; /* Thêm padding-left 35px để tránh đè icon */
  font-family: inherit;
  background: transparent;
}
/* Căn chỉnh icon lịch của thư viện */
:deep(.dp__input_icon) {
  left: 0 !important;
  padding: 0 !important;
  color: #94a3b8;
  width: 20px;
}

@media (max-width: 1200px) {
  .process-arrow { display: none; }
  .process-grid { flex-wrap: wrap; justify-content: center; gap: 40px; }
  .process-item { flex: 0 0 200px; }
}

@media (max-width: 992px) {
  .search-box { flex-direction: column; align-items: stretch; padding: 24px; border-radius: 30px; }
  .search-separator { width: 100%; height: 1px; margin: 8px 0; }
  .search-btn { margin-top: 24px; margin-left: 0; padding: 18px; }
  .hero-title { font-size: 3.5rem; }
}

@media (max-width: 768px) {
  .hero-title { font-size: 2.75rem; }
  .hero-actions { flex-direction: column; align-items: stretch; }
  .process-item { flex: 0 0 100%; }
}
</style>
