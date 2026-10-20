<template>
  <div class="fleet-page">
    <!-- 1. COMPACT PAGE INTRO -->
    <section class="fleet-intro-section" aria-label="Giới thiệu đội xe">
      <div class="app-container">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Đường dẫn">
          <router-link to="/" class="bc-link">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
          </router-link>
          <span class="bc-sep" aria-hidden="true">/</span>
          <span class="bc-current" aria-current="page">Đội xe</span>
        </nav>

        <div class="intro-grid">
          <div class="intro-content">
            <div class="eyebrow-pill">
              <span class="glow-dot" aria-hidden="true"></span>
              <span>HỆ SINH THÁI XE ĐIỆN & SANG TRỌNG</span>
            </div>
            <h1 class="intro-title">
              Đội Xe Tuyển Chọn Cho Mọi Hành Trình<span class="title-dot">.</span>
            </h1>
            <p class="intro-description">
              Khám phá danh mục xe điện VinFast thông minh và các dòng xe sang tuyển chọn. 
              100% phương tiện được bảo dưỡng chính hãng, kiểm định 150 điểm kỹ thuật và sẵn sàng phục vụ theo lịch trình của bạn.
            </p>
          </div>

          <!-- Trust Badges Grid -->
          <div class="trust-badges-grid">
            <div class="trust-badge-card">
              <div class="badge-icon"><i class="fa-solid fa-shield-halved"></i></div>
              <div class="badge-text">
                <strong>Bảo dưỡng chính hãng</strong>
                <span>Kiểm định 150 điểm kỹ thuật</span>
              </div>
            </div>
            <div class="trust-badge-card">
              <div class="badge-icon"><i class="fa-solid fa-bolt"></i></div>
              <div class="badge-text">
                <strong>Pin & Xe sẵn sàng</strong>
                <span>Sạc đầy 100% trước khi giao</span>
              </div>
            </div>
            <div class="trust-badge-card">
              <div class="badge-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div class="badge-text">
                <strong>Giao nhận tận nơi</strong>
                <span>Tại sân bay, khách sạn & tư gia</span>
              </div>
            </div>
            <div class="trust-badge-card">
              <div class="badge-icon"><i class="fa-solid fa-receipt"></i></div>
              <div class="badge-text">
                <strong>Minh bạch chi phí</strong>
                <span>Không phụ phí ẩn, snapshot giá</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. SEARCH & FILTER HUB -->
    <section class="fleet-search-hub app-container" aria-label="Bộ lọc và tìm kiếm xe">
      <div class="search-panel-card">
        <!-- Main Search Bar -->
        <form class="primary-search-form" @submit.prevent="applySearch">
          <!-- Field 1: Location -->
          <div class="search-field-col">
            <label for="fleet-location-select" class="field-label">
              <i class="fa-solid fa-location-dot"></i>
              <span>Khu vực / Chi nhánh</span>
            </label>
            <div class="select-wrap">
              <select id="fleet-location-select" v-model="filters.city" class="hub-select" @change="onFilterChange">
                <option value="all">Tất cả khu vực (Toàn quốc)</option>
                <option value="sg">TP. Hồ Chí Minh</option>
                <option value="hn">Hà Nội</option>
                <option value="dn">Đà Nẵng</option>
                <option value="nt">Nha Trang</option>
                <option value="hp">Hải Phòng</option>
                <option value="ct">Cần Thơ</option>
              </select>
              <i class="fa-solid fa-chevron-down select-arrow" aria-hidden="true"></i>
            </div>
          </div>

          <!-- Field 2: Pickup Date -->
          <div class="search-field-col">
            <label for="fleet-start-date" class="field-label">
              <i class="fa-solid fa-calendar-plus"></i>
              <span>Thời gian nhận xe</span>
            </label>
            <input
              id="fleet-start-date"
              type="datetime-local"
              v-model="filters.startDate"
              :min="minPickupDate"
              class="hub-input"
              @change="onDateChange"
            />
          </div>

          <!-- Field 3: Return Date -->
          <div class="search-field-col">
            <label for="fleet-end-date" class="field-label">
              <i class="fa-solid fa-calendar-check"></i>
              <span>Thời gian trả xe</span>
            </label>
            <input
              id="fleet-end-date"
              type="datetime-local"
              v-model="filters.endDate"
              :min="filters.startDate || minPickupDate"
              class="hub-input"
              @change="onDateChange"
            />
          </div>

          <!-- Field 4: Search Text & Submit -->
          <div class="search-field-col search-text-col">
            <label for="fleet-search-text" class="field-label">
              <i class="fa-solid fa-magnifying-glass"></i>
              <span>Tìm kiếm theo tên xe</span>
            </label>
            <div class="input-with-action">
              <input
                id="fleet-search-text"
                type="text"
                v-model="filters.search"
                placeholder="VF8, VF9, Camry, SantaFe..."
                class="hub-input"
                @input="handleSearchDebounce"
              />
              <button
                v-if="filters.search"
                type="button"
                class="btn-clear-text"
                @click="clearSearchText"
                aria-label="Xóa từ khóa tìm kiếm"
              >
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="search-action-col">
            <button type="submit" class="btn-primary btn-submit-search" :disabled="loading">
              <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
              <template v-else>
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Tìm xe</span>
              </template>
            </button>
          </div>
        </form>

        <!-- Secondary Filters Row (Desktop) -->
        <div class="secondary-filters-bar">
          <!-- Body Type Selector (SUV / Sedan /...) -->
          <div class="filter-dropdown-group">
            <label class="filter-group-label">Thân xe:</label>
            <select v-model="filters.type" class="filter-mini-select" @change="onFilterChange">
              <option value="all">Tất cả thân xe</option>
              <option value="SUV">SUV Gầm cao</option>
              <option value="Sedan">Sedan 4 cửa</option>
              <option value="Luxury">Xe Sang</option>
              <option value="Hatchback">Hatchback</option>
            </select>
          </div>

          <!-- Fuel Type Selector (EV vs Gasoline - Not confused with body type!) -->
          <div class="filter-dropdown-group">
            <label class="filter-group-label">Nhiên liệu:</label>
            <select v-model="filters.fuelType" class="filter-mini-select" @change="onFilterChange">
              <option value="all">Tất cả nhiên liệu</option>
              <option value="electric">⚡ Điện 100% (EV)</option>
              <option value="gasoline">⛽ Xăng</option>
              <option value="diesel">Động cơ Diesel</option>
              <option value="hybrid">Động cơ Hybrid</option>
            </select>
          </div>

          <!-- Seats Selector -->
          <div class="filter-dropdown-group">
            <label class="filter-group-label">Số chỗ:</label>
            <select v-model="filters.seats" class="filter-mini-select" @change="onFilterChange">
              <option value="all">Tất cả số chỗ</option>
              <option value="4">4 - 5 chỗ</option>
              <option value="7">7 chỗ trở lên</option>
            </select>
          </div>

          <!-- Price Range Selector -->
          <div class="filter-dropdown-group">
            <label class="filter-group-label">Mức giá / ngày:</label>
            <select v-model="filters.priceRange" class="filter-mini-select" @change="onFilterChange">
              <option value="all">Tất cả mức giá</option>
              <option value="under_1_2m">Dưới 1.200.000đ</option>
              <option value="1_2m_to_2m">1.200.000đ - 2.000.000đ</option>
              <option value="above_2m">Trên 2.000.000đ</option>
            </select>
          </div>

          <!-- Sort Selector -->
          <div class="filter-dropdown-group sort-group">
            <label class="filter-group-label"><i class="fa-solid fa-arrow-down-short-wide"></i> Sắp xếp:</label>
            <select v-model="filters.sort" class="filter-mini-select" @change="onFilterChange">
              <option value="default">Phổ biến / Mới nhất</option>
              <option value="price_asc">Giá thuê: Thấp đến Cao</option>
              <option value="price_desc">Giá thuê: Cao đến Thấp</option>
              <option value="name_asc">Tên xe: A - Z</option>
            </select>
          </div>

          <!-- Mobile Filter Trigger Button -->
          <button
            type="button"
            class="btn-mobile-filter-trigger"
            @click="openMobileDrawer"
            aria-label="Mở bộ lọc nâng cao trên di động"
          >
            <i class="fa-solid fa-sliders"></i>
            <span>Bộ lọc</span>
            <span v-if="activeFilterCount > 0" class="filter-count-badge">{{ activeFilterCount }}</span>
          </button>
        </div>

        <!-- 3. ACTIVE FILTERS & STATUS CHIPS BAR -->
        <div v-if="activeFilterCount > 0 || isDateFilteringActive" class="active-chips-strip">
          <div class="chips-list">
            <!-- Availability Checking Badge -->
            <span v-if="isDateFilteringActive" class="chip-item chip-date-active">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <span>Kiểm tra lịch: {{ formatDateShort(filters.startDate) }} → {{ formatDateShort(filters.endDate) }}</span>
              <button type="button" @click="clearDateFilter" aria-label="Xóa khoảng ngày nhận trả">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- City Chip -->
            <span v-if="filters.city && filters.city !== 'all'" class="chip-item">
              <span>Khu vực: {{ getCityLabel(filters.city) }}</span>
              <button type="button" @click="removeFilter('city')" aria-label="Bỏ lọc khu vực">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- Body Type Chip -->
            <span v-if="filters.type && filters.type !== 'all'" class="chip-item">
              <span>Thân xe: {{ filters.type }}</span>
              <button type="button" @click="removeFilter('type')" aria-label="Bỏ lọc thân xe">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- Fuel Type Chip -->
            <span v-if="filters.fuelType && filters.fuelType !== 'all'" class="chip-item">
              <span>Nhiên liệu: {{ getFuelLabel(filters.fuelType) }}</span>
              <button type="button" @click="removeFilter('fuelType')" aria-label="Bỏ lọc nhiên liệu">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- Seats Chip -->
            <span v-if="filters.seats && filters.seats !== 'all'" class="chip-item">
              <span>Số chỗ: {{ filters.seats === '7' ? '7 chỗ trở lên' : '4 - 5 chỗ' }}</span>
              <button type="button" @click="removeFilter('seats')" aria-label="Bỏ lọc số chỗ">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- Price Range Chip -->
            <span v-if="filters.priceRange && filters.priceRange !== 'all'" class="chip-item">
              <span>Giá: {{ getPriceRangeLabel(filters.priceRange) }}</span>
              <button type="button" @click="removeFilter('priceRange')" aria-label="Bỏ lọc mức giá">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>

            <!-- Search Keyword Chip -->
            <span v-if="filters.search" class="chip-item">
              <span>Từ khóa: "{{ filters.search }}"</span>
              <button type="button" @click="clearSearchText" aria-label="Xóa từ khóa">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </span>
          </div>

          <!-- Reset All Button -->
          <button type="button" class="btn-reset-chips" @click="resetAllFilters">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Đặt lại bộ lọc</span>
          </button>
        </div>
      </div>
    </section>

    <!-- 4. RESULTS SECTION -->
    <section id="fleet-results-section" class="fleet-results-section app-container" aria-label="Danh sách phương tiện">
      <!-- Results Toolbar Bar -->
      <div class="results-toolbar-row">
        <div class="results-count-group">
          <h2 class="results-heading">Danh Sách Phương Tiện</h2>
          <span class="results-badge">
            <i class="fa-solid fa-car-side"></i>
            <span v-if="loading">Đang tìm kiếm...</span>
            <span v-else>{{ totalCars }} phương tiện khả dụng</span>
          </span>
        </div>

        <div class="results-options-group">
          <!-- Per Page Selector -->
          <div class="per-page-select-wrap">
            <label for="per-page-select" class="per-page-label">Hiển thị:</label>
            <select id="per-page-select" v-model="pagination.perPage" class="per-page-select" @change="changePerPage">
              <option :value="9">9 xe / trang</option>
              <option :value="12">12 xe / trang</option>
              <option :value="18">18 xe / trang</option>
            </select>
          </div>

          <!-- Refresh Button -->
          <button
            type="button"
            class="btn-refresh-list"
            :disabled="loading"
            @click="fetchCarsData"
            aria-label="Làm mới danh sách xe"
            title="Tải lại danh sách"
          >
            <i class="fa-solid fa-rotate-right" :class="{ 'fa-spin': loading }"></i>
          </button>
        </div>
      </div>

      <!-- STATE 1: SKELETON LOADING GRID -->
      <div v-if="loading" class="fleet-cards-grid" aria-busy="true">
        <div v-for="n in (pagination.perPage || 9)" :key="n" class="fleet-card skeleton-card">
          <div class="skeleton-header">
            <div class="skeleton-shimmer skeleton-title"></div>
            <div class="skeleton-shimmer skeleton-sub"></div>
          </div>
          <div class="skeleton-visual-stage">
            <div class="skeleton-shimmer skeleton-image"></div>
          </div>
          <div class="skeleton-footer">
            <div class="skeleton-shimmer skeleton-price"></div>
            <div class="skeleton-shimmer skeleton-badges"></div>
            <div class="skeleton-actions-row">
              <div class="skeleton-shimmer skeleton-btn"></div>
              <div class="skeleton-shimmer skeleton-btn"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- STATE 2: API ERROR STATE -->
      <div v-else-if="fetchError" class="state-notice-card state-error-card" role="alert">
        <div class="state-icon-wrap icon-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3>Không Thể Tải Danh Sách Phương Tiện</h3>
        <p>{{ fetchError }}</p>
        <div class="state-actions-wrap">
          <button type="button" class="btn-primary" @click="fetchCarsData">
            <i class="fa-solid fa-rotate-right"></i>
            <span>Thử kết nối lại</span>
          </button>
        </div>
      </div>

      <!-- STATE 3: EMPTY RESULTS STATE -->
      <div v-else-if="carsList.length === 0" class="state-notice-card state-empty-card">
        <div class="state-icon-wrap icon-empty">
          <i class="fa-solid fa-car-tunnel"></i>
        </div>
        <h3>Không Tìm Thấy Mẫu Xe Phù Hợp</h3>
        <p v-if="isDateFilteringActive">
          Hiện tại không có xe nào trống trong khung giờ từ <strong>{{ formatDateShort(filters.startDate) }}</strong> 
          đến <strong>{{ formatDateShort(filters.endDate) }}</strong> với bộ lọc đã chọn.
        </p>
        <p v-else>
          Không có phương tiện nào khớp với tiêu chí tìm kiếm hoặc bộ lọc hiện tại của bạn.
        </p>
        <div class="state-actions-wrap">
          <button v-if="isDateFilteringActive" type="button" class="btn-secondary" @click="clearDateFilter">
            <i class="fa-solid fa-calendar-xmark"></i>
            <span>Đổi hoặc xóa khoảng ngày</span>
          </button>
          <button type="button" class="btn-primary" @click="resetAllFilters">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Xóa tất cả bộ lọc</span>
          </button>
        </div>
      </div>

      <!-- STATE 4: CARS LIST GRID -->
      <div v-else class="fleet-cards-grid">
        <article
          v-for="car in carsList"
          :key="car.id"
          class="fleet-card card-interactive"
        >
          <!-- Card Top: Name, Category, Arrow Link -->
          <div class="card-header-row">
            <div class="car-title-group">
              <h3 class="car-card-name">
                <router-link :to="getCarDetailLink(car)">{{ car.name }}</router-link>
              </h3>
              <div class="car-meta-sub">
                <span class="car-category-pill">{{ car.category_name || car.type || 'SUV Hạng Sang' }}</span>
                <span class="city-location-pill">
                  <i class="fa-solid fa-location-dot"></i>
                  <span>{{ car.cityDisplay }}</span>
                </span>
              </div>
            </div>

            <router-link
              :to="getCarDetailLink(car)"
              class="card-arrow-link"
              :aria-label="'Xem chi tiết ' + car.name"
            >
              <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </router-link>
          </div>

          <!-- Card Center: Visual Stage with Unified 8:5 Aspect Ratio -->
          <div class="card-visual-stage">
            <!-- Availability / Status Tag -->
            <div class="stage-tag-badge">
              <span v-if="isDateFilteringActive" class="status-tag status-available">
                <i class="fa-solid fa-circle-check"></i> Trống lịch thuê
              </span>
              <span v-else-if="car.status === 'maintenance'" class="status-tag status-maintenance">
                <i class="fa-solid fa-wrench"></i> Đang bảo dưỡng
              </span>
              <span v-else class="status-tag status-ready">
                <i class="fa-solid fa-check"></i> Sẵn sàng bàn giao
              </span>
            </div>

            <router-link :to="getCarDetailLink(car)" class="car-img-anchor">
              <img
                :src="resolveCarImage(car)"
                :alt="car.name"
                class="card-car-img"
                loading="lazy"
                @error="handleCarImgError($event)"
              />
            </router-link>
            <div class="card-ground-shadow" aria-hidden="true"></div>
          </div>

          <!-- Card Bottom: Specs, Price, Actions -->
          <div class="card-footer-box">
            <!-- Real Specs Badges Row -->
            <div class="card-specs-row">
              <span class="mini-spec-badge" title="Số chỗ ngồi">
                <i class="fa-solid fa-users"></i>
                <span>{{ car.seats ? car.seats + ' chỗ' : '5 chỗ' }}</span>
              </span>

              <span class="mini-spec-badge" title="Loại nhiên liệu">
                <i :class="car.fuel_type === 'gasoline' ? 'fa-solid fa-gas-pump' : 'fa-solid fa-bolt'"></i>
                <span>{{ car.fuel_type === 'gasoline' ? 'Xăng' : (car.fuel_type === 'diesel' ? 'Dầu' : 'Điện 100%') }}</span>
              </span>

              <span class="mini-spec-badge" title="Tầm hoạt động / Động cơ">
                <i class="fa-solid fa-gauge-high"></i>
                <span>{{ car.range_per_charge || car.range || 'Chuẩn kỹ thuật' }}</span>
              </span>
            </div>

            <!-- Price Row -->
            <div class="card-price-row">
              <div class="price-block">
                <span class="price-label">Đơn giá niêm yết</span>
                <div class="price-value-wrap">
                  <strong class="price-amount">{{ formatPrice(car.price_per_day) }}</strong>
                  <span class="price-unit">/ ngày</span>
                </div>
              </div>
              <span class="price-note">* Chưa bao gồm VAT</span>
            </div>

            <!-- Actions Row -->
            <div class="card-actions-row">
              <router-link :to="getCarDetailLink(car)" class="btn-card-detail">
                <i class="fa-solid fa-circle-info"></i>
                <span>Chi tiết</span>
              </router-link>

              <button
                type="button"
                class="btn-card-rent"
                @click="openBookingModal(car)"
              >
                <span>Đặt xe</span>
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            </div>
          </div>
        </article>
      </div>

      <!-- 5. PAGINATION CONTROLS -->
      <nav
        v-if="pagination.lastPage > 1 && !loading"
        class="pagination-container"
        aria-label="Phân trang danh sách xe"
      >
        <button
          type="button"
          class="page-nav-btn"
          :disabled="pagination.currentPage <= 1"
          @click="goToPage(pagination.currentPage - 1)"
          aria-label="Trang trước"
        >
          <i class="fa-solid fa-chevron-left"></i>
          <span>Trước</span>
        </button>

        <div class="page-numbers-list">
          <button
            v-for="pageNum in visiblePageNumbers"
            :key="pageNum"
            type="button"
            class="page-number-btn"
            :class="{ 'is-active': pagination.currentPage === pageNum, 'is-ellipsis': pageNum === '...' }"
            :disabled="pageNum === '...'"
            @click="pageNum !== '...' && goToPage(pageNum)"
            :aria-current="pagination.currentPage === pageNum ? 'page' : undefined"
          >
            {{ pageNum }}
          </button>
        </div>

        <button
          type="button"
          class="page-nav-btn"
          :disabled="pagination.currentPage >= pagination.lastPage"
          @click="goToPage(pagination.currentPage + 1)"
          aria-label="Trang tiếp theo"
        >
          <span>Sau</span>
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </nav>
    </section>

    <!-- 6. SUPPORT CTA BANNER -->
    <section class="fleet-cta-banner app-container" aria-label="Hỗ trợ chọn xe">
      <div class="cta-banner-card">
        <div class="cta-ambient-glow" aria-hidden="true"></div>
        <div class="cta-banner-content">
          <div class="cta-text-group">
            <span class="cta-eyebrow">DỊCH VỤ KHÁCH HÀNG 24/7</span>
            <h2 class="cta-title">Cần Xe Gấp Hoặc Tư Vấn Tuyến Đường Riêng?</h2>
            <p class="cta-desc">
              Chuyên viên của DriveRent sẵn sàng hỗ trợ bạn chọn mẫu xe phù hợp nhất, 
              hướng dẫn trạm sạc trên lộ trình và giao nhận xe theo lịch hẹn chính xác.
            </p>
          </div>
          <div class="cta-actions-group">
            <a href="tel:19006868" class="btn-primary cta-btn">
              <i class="fa-solid fa-phone"></i>
              <span>Hotline 1900 6868</span>
            </a>
            <router-link to="/contact" class="btn-secondary cta-btn-sec">
              <i class="fa-solid fa-headset"></i>
              <span>Gửi yêu cầu tư vấn</span>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 7. MOBILE FILTER DRAWER -->
    <Teleport to="body">
      <div
        v-if="mobileDrawerOpen"
        class="mobile-drawer-backdrop"
        @click="closeMobileDrawer"
      >
        <div
          class="mobile-drawer-sheet"
          role="dialog"
          aria-modal="true"
          aria-label="Bộ lọc phương tiện"
          @click.stop
          tabindex="-1"
          ref="drawerSheetRef"
          @keydown.esc="closeMobileDrawer"
        >
          <div class="drawer-header">
            <div class="drawer-title-group">
              <i class="fa-solid fa-sliders text-primary"></i>
              <h3>Bộ Lọc Phương Tiện</h3>
            </div>
            <button
              type="button"
              class="btn-close-drawer"
              @click="closeMobileDrawer"
              aria-label="Đóng bộ lọc"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="drawer-body">
            <!-- Khu vực -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-location-dot"></i> Khu vực chi nhánh</label>
              <select v-model="filters.city" class="hub-select">
                <option value="all">Tất cả khu vực</option>
                <option value="sg">TP. Hồ Chí Minh</option>
                <option value="hn">Hà Nội</option>
                <option value="dn">Đà Nẵng</option>
                <option value="nt">Nha Trang</option>
                <option value="hp">Hải Phòng</option>
                <option value="ct">Cần Thơ</option>
              </select>
            </div>

            <!-- Thời gian -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-calendar-plus"></i> Thời gian nhận xe</label>
              <input type="datetime-local" v-model="filters.startDate" :min="minPickupDate" class="hub-input" />
            </div>

            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-calendar-check"></i> Thời gian trả xe</label>
              <input type="datetime-local" v-model="filters.endDate" :min="filters.startDate || minPickupDate" class="hub-input" />
            </div>

            <!-- Thân xe -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-car"></i> Thân xe / Phân khúc</label>
              <select v-model="filters.type" class="hub-select">
                <option value="all">Tất cả thân xe</option>
                <option value="SUV">SUV Gầm cao</option>
                <option value="Sedan">Sedan 4 cửa</option>
                <option value="Luxury">Xe Sang</option>
                <option value="Hatchback">Hatchback</option>
              </select>
            </div>

            <!-- Nhiên liệu -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-bolt"></i> Loại nhiên liệu</label>
              <select v-model="filters.fuelType" class="hub-select">
                <option value="all">Tất cả nhiên liệu</option>
                <option value="electric">⚡ Điện 100% (EV)</option>
                <option value="gasoline">⛽ Xăng</option>
                <option value="diesel">Diesel</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>

            <!-- Số chỗ -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-users"></i> Sức chứa chỗ ngồi</label>
              <select v-model="filters.seats" class="hub-select">
                <option value="all">Tất cả số chỗ</option>
                <option value="4">4 - 5 chỗ</option>
                <option value="7">7 chỗ trở lên</option>
              </select>
            </div>

            <!-- Mức giá -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-tag"></i> Mức giá / ngày</label>
              <select v-model="filters.priceRange" class="hub-select">
                <option value="all">Tất cả mức giá</option>
                <option value="under_1_2m">Dưới 1.200.000đ</option>
                <option value="1_2m_to_2m">1.200.000đ - 2.000.000đ</option>
                <option value="above_2m">Trên 2.000.000đ</option>
              </select>
            </div>

            <!-- Sắp xếp -->
            <div class="drawer-group">
              <label class="drawer-label"><i class="fa-solid fa-arrow-down-short-wide"></i> Sắp xếp kết quả</label>
              <select v-model="filters.sort" class="hub-select">
                <option value="default">Phổ biến / Mới nhất</option>
                <option value="price_asc">Giá thuê: Thấp đến Cao</option>
                <option value="price_desc">Giá thuê: Cao đến Thấp</option>
                <option value="name_asc">Tên xe: A - Z</option>
              </select>
            </div>
          </div>

          <div class="drawer-footer">
            <button type="button" class="btn-drawer-reset" @click="resetAllFilters">
              Đặt lại
            </button>
            <button type="button" class="btn-primary btn-drawer-apply" @click="applyMobileDrawer">
              Áp dụng bộ lọc ({{ totalCars }} xe)
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- 8. BOOKING CONFIRMATION MODAL -->
    <Teleport to="body">
      <div
        v-if="showBookingModal && targetCar"
        class="modal-backdrop-dark"
        @click.self="closeBookingModal"
      >
        <div class="booking-modal-card" role="dialog" aria-modal="true" aria-label="Xác nhận đặt lịch">
          <div class="modal-head">
            <div class="head-title-wrap">
              <span class="head-eyebrow">XÁC NHẬN ĐẶT THUÊ XE</span>
              <h3>{{ targetCar.name }}</h3>
            </div>
            <button type="button" class="btn-close-modal" @click="closeBookingModal" aria-label="Đóng">&times;</button>
          </div>

          <div class="modal-body-content">
            <!-- Visual Preview Mini -->
            <div class="modal-car-preview">
              <img :src="resolveCarImage(targetCar)" :alt="targetCar.name" @error="handleCarImgError($event)" />
              <div class="car-badge-info">
                <span class="cat-pill">{{ targetCar.category_name || targetCar.type || 'SUV Hạng Sang' }}</span>
                <strong class="rate-text">{{ formatPrice(targetCar.price_per_day) }}/ngày</strong>
              </div>
            </div>

            <!-- Form Configurations -->
            <div class="booking-config-fields">
              <div class="field-item">
                <label><i class="fa-solid fa-location-dot"></i> Điểm giao nhận xe:</label>
                <input
                  type="text"
                  v-model="bookingLocation"
                  placeholder="Nhập địa chỉ giao xe hoặc nhận tại showroom..."
                  class="modal-input"
                />
              </div>

              <div class="fields-two-col">
                <div class="field-item">
                  <label><i class="fa-solid fa-calendar-plus"></i> Thời gian nhận xe:</label>
                  <input
                    type="datetime-local"
                    v-model="bookingStartDate"
                    :min="minPickupDate"
                    class="modal-input"
                  />
                </div>
                <div class="field-item">
                  <label><i class="fa-solid fa-calendar-check"></i> Thời gian trả xe:</label>
                  <input
                    type="datetime-local"
                    v-model="bookingEndDate"
                    :min="bookingStartDate || minPickupDate"
                    class="modal-input"
                  />
                </div>
              </div>
            </div>

            <!-- Calculation Box -->
            <div class="price-calculation-card">
              <div class="calc-row">
                <span>Thời gian thuê tính toán:</span>
                <strong>{{ modalRentalDays }} ngày</strong>
              </div>
              <div class="calc-row">
                <span>Đơn giá niêm yết:</span>
                <span>{{ formatPrice(targetCar.price_per_day) }} / ngày</span>
              </div>
              <div class="calc-divider"></div>
              <div class="calc-row grand-total">
                <span>Tổng tiền tạm tính:</span>
                <span class="price-total">{{ formatPrice(modalTotalCost) }}</span>
              </div>
              <small class="note-text">* Chưa bao gồm phụ phí giao nhận ngoài phạm vi trung tâm hoặc thuế VAT.</small>
            </div>
          </div>

          <div class="modal-footer-actions">
            <button type="button" class="btn-cancel-modal" @click="closeBookingModal">Hủy bỏ</button>
            <button
              type="button"
              class="btn-confirm-booking"
              :disabled="bookingLoading"
              @click="executeBooking"
            >
              <i v-if="bookingLoading" class="fa-solid fa-spinner fa-spin"></i>
              <span v-else>Xác nhận đặt lịch ngay <i class="fa-solid fa-arrow-right"></i></span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { createToaster } from '@meforma/vue-toaster';
import baseRequest from '../../core/baseRequest';
import { resolveCarImage, handleCarImgError } from '../../core/carImageHelper';

const route = useRoute();
const router = useRouter();
const toaster = createToaster({ position: 'top-right' });

// State
const loading = ref(true);
const fetchError = ref(null);
const rawCars = ref([]);
const totalCars = ref(0);
const mobileDrawerOpen = ref(false);
const drawerSheetRef = ref(null);
let lastFocusedElement = null;

// Search debounce
let searchDebounceTimer = null;
let activeRequestId = 0;

// Filters Model
const filters = reactive({
  city: 'all',
  startDate: '',
  endDate: '',
  search: '',
  type: 'all',
  fuelType: 'all',
  seats: 'all',
  priceRange: 'all',
  sort: 'default'
});

// Pagination State
const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 9,
  total: 0
});

// Booking Modal State
const showBookingModal = ref(false);
const targetCar = ref(null);
const bookingLocation = ref('');
const bookingStartDate = ref('');
const bookingEndDate = ref('');
const bookingLoading = ref(false);

// Formatters & Date Utilities
const minPickupDate = computed(() => {
  const d = new Date(Date.now() + 60 * 60 * 1000); // 1h from now
  return d.toISOString().slice(0, 16);
});

const isDateFilteringActive = computed(() => {
  return Boolean(filters.startDate && filters.endDate);
});

const activeFilterCount = computed(() => {
  let count = 0;
  if (filters.city && filters.city !== 'all') count++;
  if (filters.startDate && filters.endDate) count++;
  if (filters.search && filters.search.trim()) count++;
  if (filters.type && filters.type !== 'all') count++;
  if (filters.fuelType && filters.fuelType !== 'all') count++;
  if (filters.seats && filters.seats !== 'all') count++;
  if (filters.priceRange && filters.priceRange !== 'all') count++;
  if (filters.sort && filters.sort !== 'default') count++;
  return count;
});

const formatPrice = (val) => {
  const num = Number(val || 0);
  if (num <= 0) return 'Liên hệ';
  return num.toLocaleString('vi-VN') + 'đ';
};

const formatDateShort = (isoStr) => {
  if (!isoStr) return '';
  try {
    const d = new Date(isoStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month} ${hours}:${mins}`;
  } catch (e) {
    return isoStr;
  }
};

const toServerISO = (val) => {
  if (!val) return '';
  try {
    const d = new Date(val);
    if (isNaN(d.getTime())) return '';
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
  } catch {
    return '';
  }
};

const getCityLabel = (val) => {
  const map = {
    all: 'Toàn quốc',
    sg: 'TP. Hồ Chí Minh',
    hn: 'Hà Nội',
    dn: 'Đà Nẵng',
    nt: 'Nha Trang',
    hp: 'Hải Phòng',
    ct: 'Cần Thơ'
  };
  return map[val] || val;
};

const getFuelLabel = (val) => {
  const map = {
    electric: 'Điện 100% (EV)',
    gasoline: 'Xăng',
    diesel: 'Diesel',
    hybrid: 'Hybrid'
  };
  return map[val] || val;
};

const getPriceRangeLabel = (val) => {
  const map = {
    under_1_2m: 'Dưới 1.2tr',
    '1_2m_to_2m': '1.2tr - 2tr',
    above_2m: 'Trên 2tr'
  };
  return map[val] || val;
};

// Cars list computed with cityDisplay formatting
const carsList = computed(() => {
  return rawCars.value.map(car => {
    return {
      ...car,
      name: car.name || car.ten_xe || 'VinFast Model',
      price_per_day: car.price_per_day || car.gia_thue || 0,
      type: car.type || car.category_name || 'SUV',
      category_name: car.category_name || 'Tiêu chuẩn',
      cityDisplay: (() => {
        const c = (car.city || '').toLowerCase();
        if (c === 'sg' || c === '79' || c.includes('hồ chí minh')) return 'TP. Hồ Chí Minh';
        if (c === 'hn' || c === '1' || c.includes('hà nội')) return 'Hà Nội';
        if (c === 'dn' || c === '48' || c.includes('đà nẵng')) return 'Đà Nẵng';
        if (c === 'nt' || c === '56' || c.includes('nha trang') || c.includes('khánh hòa')) return 'Nha Trang';
        if (c === 'hp' || c === '31' || c.includes('hải phòng')) return 'Hải Phòng';
        if (c === 'ct' || c === '92' || c.includes('cần thơ')) return 'Cần Thơ';
        return car.city || 'Toàn quốc';
      })()
    };
  });
});

// Page numbers generator with ellipsis
const visiblePageNumbers = computed(() => {
  const total = pagination.lastPage;
  const current = pagination.currentPage;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [];
  pages.push(1);
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

// URL Query Sync
const syncFiltersToUrl = () => {
  const query = {};
  if (filters.city && filters.city !== 'all') query.city = filters.city;
  if (filters.startDate) query.start_date = filters.startDate;
  if (filters.endDate) query.end_date = filters.endDate;
  if (filters.search && filters.search.trim()) query.search = filters.search.trim();
  if (filters.type && filters.type !== 'all') query.type = filters.type;
  if (filters.fuelType && filters.fuelType !== 'all') query.fuel_type = filters.fuelType;
  if (filters.seats && filters.seats !== 'all') query.seats = filters.seats;
  if (filters.priceRange && filters.priceRange !== 'all') query.price_range = filters.priceRange;
  if (filters.sort && filters.sort !== 'default') query.sort = filters.sort;
  if (pagination.currentPage > 1) query.page = String(pagination.currentPage);
  if (pagination.perPage !== 9) query.per_page = String(pagination.perPage);

  router.replace({ query }).catch(() => {});
};

const parseFiltersFromUrl = () => {
  const q = route.query;
  if (q.city) filters.city = q.city;
  if (q.start_date) filters.startDate = q.start_date;
  if (q.end_date) filters.endDate = q.end_date;
  if (q.search) filters.search = q.search;
  if (q.type) filters.type = q.type;
  if (q.fuel_type) filters.fuelType = q.fuel_type;
  if (q.seats) filters.seats = q.seats;
  if (q.price_range) filters.priceRange = q.price_range;
  if (q.sort) filters.sort = q.sort;
  if (q.page) pagination.currentPage = Math.max(1, parseInt(q.page) || 1);
  if (q.per_page) pagination.perPage = Math.max(1, parseInt(q.per_page) || 9);
};

// API Fetching
const fetchCarsData = async () => {
  const requestId = ++activeRequestId;
  loading.value = true;
  fetchError.value = null;

  try {
    const params = {
      page: pagination.currentPage,
      per_page: pagination.perPage
    };

    if (filters.city && filters.city !== 'all') params.city = filters.city;
    if (filters.type && filters.type !== 'all') params.type = filters.type;
    if (filters.fuelType && filters.fuelType !== 'all') params.fuel_type = filters.fuelType;
    if (filters.seats && filters.seats !== 'all') params.seats = filters.seats;
    if (filters.search && filters.search.trim()) params.search = filters.search.trim();
    if (filters.sort && filters.sort !== 'default') params.sort = filters.sort;

    if (filters.priceRange === 'under_1_2m') {
      params.price_max = 1200000;
    } else if (filters.priceRange === '1_2m_to_2m') {
      params.price_min = 1200000;
      params.price_max = 2000000;
    } else if (filters.priceRange === 'above_2m') {
      params.price_min = 2000000;
    }

    if (filters.startDate && filters.endDate) {
      params.start_date = toServerISO(filters.startDate);
      params.end_date = toServerISO(filters.endDate);
    }

    const res = await baseRequest.get('client/car/data', { params });

    // Discard stale responses
    if (requestId !== activeRequestId) return;

    if (res.data && res.data.status) {
      if (Array.isArray(res.data.data)) {
        rawCars.value = res.data.data;
        if (res.data.pagination) {
          pagination.currentPage = res.data.pagination.current_page;
          pagination.lastPage = res.data.pagination.last_page;
          pagination.total = res.data.pagination.total;
          totalCars.value = res.data.pagination.total;
        } else {
          totalCars.value = res.data.data.length;
          pagination.lastPage = 1;
        }
      } else {
        rawCars.value = [];
        totalCars.value = 0;
      }
    } else {
      fetchError.value = res.data?.message || 'Không thể tải danh sách xe từ máy chủ.';
    }
  } catch (err) {
    if (requestId !== activeRequestId) return;
    console.error('Fetch cars error:', err);
    fetchError.value = 'Không thể kết nối máy chủ để tải danh sách xe. Vui lòng thử lại sau.';
  } finally {
    if (requestId === activeRequestId) {
      loading.value = false;
    }
  }
};

// Filter event handlers
const onFilterChange = () => {
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const onDateChange = () => {
  if (filters.startDate && filters.endDate) {
    const s = new Date(filters.startDate);
    const e = new Date(filters.endDate);
    if (e <= s) {
      toaster.warning('Thời gian trả xe phải sau thời gian nhận xe!');
      return;
    }
  }
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const handleSearchDebounce = () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1;
    syncFiltersToUrl();
    fetchCarsData();
  }, 350);
};

const clearSearchText = () => {
  filters.search = '';
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const removeFilter = (key) => {
  if (key === 'city') filters.city = 'all';
  else if (key === 'type') filters.type = 'all';
  else if (key === 'fuelType') filters.fuelType = 'all';
  else if (key === 'seats') filters.seats = 'all';
  else if (key === 'priceRange') filters.priceRange = 'all';
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const clearDateFilter = () => {
  filters.startDate = '';
  filters.endDate = '';
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const resetAllFilters = () => {
  filters.city = 'all';
  filters.startDate = '';
  filters.endDate = '';
  filters.search = '';
  filters.type = 'all';
  filters.fuelType = 'all';
  filters.seats = 'all';
  filters.priceRange = 'all';
  filters.sort = 'default';
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
  if (mobileDrawerOpen.value) {
    closeMobileDrawer();
  }
};

const applySearch = () => {
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const changePerPage = () => {
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.lastPage || page === pagination.currentPage) return;
  pagination.currentPage = page;
  syncFiltersToUrl();
  fetchCarsData();

  nextTick(() => {
    const el = document.getElementById('fleet-results-section');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
};

// Detail Link Generator preserving queries
const getCarDetailLink = (car) => {
  const query = {};
  if (filters.startDate) query.start_date = filters.startDate;
  if (filters.endDate) query.end_date = filters.endDate;
  if (car.city) query.city = car.city;
  return {
    path: `/fleet/${car.id}`,
    query
  };
};

// Mobile Drawer Handlers
const openMobileDrawer = () => {
  lastFocusedElement = document.activeElement;
  mobileDrawerOpen.value = true;
  document.body.style.overflow = 'hidden';
  nextTick(() => {
    if (drawerSheetRef.value) {
      drawerSheetRef.value.focus();
    }
  });
};

const closeMobileDrawer = () => {
  mobileDrawerOpen.value = false;
  document.body.style.overflow = '';
  if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
    lastFocusedElement.focus();
  }
};

const applyMobileDrawer = () => {
  closeMobileDrawer();
  pagination.currentPage = 1;
  syncFiltersToUrl();
  fetchCarsData();
};

// Booking Modal Actions
const openBookingModal = (car) => {
  targetCar.value = car;
  bookingLocation.value = `${car.cityDisplay || 'Showroom chính'} - Bàn giao tận nơi`;
  
  if (filters.startDate && filters.endDate) {
    bookingStartDate.value = filters.startDate;
    bookingEndDate.value = filters.endDate;
  } else {
    const start = new Date(Date.now() + 2 * 3600 * 1000);
    const end = new Date(Date.now() + 26 * 3600 * 1000);
    bookingStartDate.value = start.toISOString().slice(0, 16);
    bookingEndDate.value = end.toISOString().slice(0, 16);
  }

  showBookingModal.value = true;
};

const closeBookingModal = () => {
  showBookingModal.value = false;
  targetCar.value = null;
};

const modalRentalDays = computed(() => {
  if (!bookingStartDate.value || !bookingEndDate.value) return 1;
  const start = new Date(bookingStartDate.value).getTime();
  const end = new Date(bookingEndDate.value).getTime();
  const diffHours = (end - start) / (1000 * 60 * 60);
  if (diffHours <= 0) return 1;
  return Math.max(1, Math.ceil(diffHours / 24));
});

const modalTotalCost = computed(() => {
  if (!targetCar.value) return 0;
  const price = Number(targetCar.value.price_per_day || 0);
  return price * modalRentalDays.value;
});

const executeBooking = async () => {
  const token = localStorage.getItem('token_client');
  if (!token) {
    closeBookingModal();
    toaster.info('Vui lòng đăng nhập để tiến hành đặt lịch thuê xe!');
    const redirectUrl = encodeURIComponent(`/fleet/${targetCar.value.id}?start_date=${bookingStartDate.value}&end_date=${bookingEndDate.value}`);
    router.push(`/login?redirect=${redirectUrl}`);
    return;
  }

  if (!bookingStartDate.value || !bookingEndDate.value) {
    toaster.warning('Vui lòng chọn thời gian nhận và trả xe!');
    return;
  }

  const start = new Date(bookingStartDate.value);
  const end = new Date(bookingEndDate.value);
  if (end <= start) {
    toaster.warning('Thời gian trả xe phải sau thời gian nhận xe!');
    return;
  }

  bookingLoading.value = true;
  try {
    const payload = {
      car_id: targetCar.value.id,
      start_date: toServerISO(bookingStartDate.value),
      end_date: toServerISO(bookingEndDate.value),
      dia_chi_nhan_xe: bookingLocation.value || 'Showroom DriveRent',
      dia_chi_tra_xe: bookingLocation.value || 'Showroom DriveRent',
      ly_do_thue: 'Đặt xe trực tuyến từ trang Đội xe DriveRent'
    };

    const res = await baseRequest.post('user/bookings/dat-lich', payload);
    if (res.data && res.data.status) {
      toaster.success('Đặt xe thành công! Chuyên viên DriveRent sẽ liên hệ bạn ngay.');
      closeBookingModal();
      router.push('/my-bookings');
    } else {
      toaster.error(res.data?.message || 'Không thể tạo đơn đặt xe lúc này.');
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Có lỗi xảy ra khi tạo đơn đặt xe. Vui lòng kiểm tra lại.';
    toaster.error(msg);
  } finally {
    bookingLoading.value = false;
  }
};

// Watch for browser history navigation (back/forward)
watch(() => route.query, (newQuery, oldQuery) => {
  if (JSON.stringify(newQuery) !== JSON.stringify(oldQuery)) {
    parseFiltersFromUrl();
    fetchCarsData();
  }
});

onMounted(() => {
  parseFiltersFromUrl();
  fetchCarsData();
});

onUnmounted(() => {
  clearTimeout(searchDebounceTimer);
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* ==========================================================================
   FLEET PAGE — EMERALD NAVY DESIGN SYSTEM
   ========================================================================== */
.fleet-page {
  background: var(--bg-dark, #0b1220);
  color: #f8fafc;
  min-height: 100vh;
  padding-bottom: 90px;
  position: relative;
  isolation: isolate;
  overflow-x: clip;
}

/* Ambient emerald glow background */
.fleet-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 680px;
  background: radial-gradient(ellipse at 50% -10%, rgba(5, 150, 105, 0.18), transparent 65%);
  pointer-events: none;
  z-index: -1;
}

/* ==========================================================================
   1. COMPACT PAGE INTRO
   ========================================================================== */
.fleet-intro-section {
  padding: 124px 0 36px 0;
  position: relative;
}

.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.82rem;
  color: #94a3b8;
  margin-bottom: 22px;
}

.bc-link {
  color: #94a3b8;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: color 180ms ease;
}

.bc-link:hover {
  color: var(--color-primary-glow, #10b981);
}

.bc-sep {
  color: rgba(255, 255, 255, 0.2);
}

.bc-current {
  color: #f1f5f9;
  font-weight: 600;
}

.intro-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  gap: 40px;
  align-items: center;
}

.eyebrow-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 14px;
  background: rgba(5, 150, 105, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--color-primary-glow, #10b981);
  margin-bottom: 14px;
  text-transform: uppercase;
}

.glow-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-primary-glow, #10b981);
  box-shadow: 0 0 8px #10b981;
}

.intro-title {
  font-family: var(--font-heading);
  font-size: clamp(2rem, 3.8vw, 3.2rem);
  font-weight: 800;
  color: #f8fafc;
  line-height: 1.12;
  letter-spacing: -0.03em;
  margin: 0 0 14px 0;
}

.title-dot {
  color: var(--color-primary-glow, #10b981);
}

.intro-description {
  font-size: 0.98rem;
  color: #94a3b8;
  line-height: 1.65;
  max-width: 640px;
}

.trust-badges-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.trust-badge-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(18, 25, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  backdrop-filter: blur(12px);
  transition: transform 200ms ease, border-color 200ms ease;
}

.trust-badge-card:hover {
  transform: translateY(-2px);
  border-color: rgba(16, 185, 129, 0.3);
}

.badge-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(5, 150, 105, 0.15);
  color: var(--color-primary-glow, #10b981);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.badge-text {
  display: flex;
  flex-direction: column;
}

.badge-text strong {
  font-size: 0.82rem;
  font-weight: 700;
  color: #f1f5f9;
}

.badge-text span {
  font-size: 0.72rem;
  color: #8fa0aa;
}

/* ==========================================================================
   2. SEARCH & FILTER HUB
   ========================================================================== */
.fleet-search-hub {
  margin-bottom: 36px;
  position: relative;
  z-index: 20;
}

.search-panel-card {
  background: rgba(18, 26, 44, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 16px 40px -12px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(16px);
}

.primary-search-form {
  display: grid;
  grid-template-columns: 1.1fr 1fr 1fr 1.3fr auto;
  gap: 16px;
  align-items: flex-end;
}

.search-field-col {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #94a3b8;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.field-label i {
  color: var(--color-primary-glow, #10b981);
}

.select-wrap {
  position: relative;
  width: 100%;
}

.hub-select, .hub-input {
  width: 100%;
  padding: 12px 14px;
  background: rgba(11, 18, 32, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  color: #f1f5f9;
  font-size: 0.88rem;
  font-family: var(--font-body);
  outline: none;
  transition: all 180ms ease;
  color-scheme: dark;
}

.hub-select {
  appearance: none;
  cursor: pointer;
  padding-right: 32px;
}

.select-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.75rem;
  pointer-events: none;
}

.hub-select:focus, .hub-input:focus {
  border-color: var(--color-primary-glow, #10b981);
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.input-with-action {
  position: relative;
  width: 100%;
}

.btn-clear-text {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  font-size: 0.85rem;
}

.btn-clear-text:hover {
  color: #ffffff;
}

.btn-submit-search {
  padding: 12px 24px;
  height: 46px;
  font-size: 0.9rem;
}

/* Secondary Filters Bar */
.secondary-filters-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  flex-wrap: wrap;
}

.filter-dropdown-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-group-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #8fa0aa;
  white-space: nowrap;
}

.filter-mini-select {
  background: rgba(11, 18, 32, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  color: #e2e8f0;
  padding: 6px 14px;
  font-size: 0.82rem;
  font-family: var(--font-body);
  outline: none;
  cursor: pointer;
  transition: all 180ms ease;
}

.filter-mini-select:focus, .filter-mini-select:hover {
  border-color: rgba(16, 185, 129, 0.4);
  color: #ffffff;
}

.sort-group {
  margin-left: auto;
}

.btn-mobile-filter-trigger {
  display: none;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(5, 150, 105, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 9999px;
  color: #10b981;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
}

.filter-count-badge {
  background: var(--color-primary-glow, #10b981);
  color: #0b1220;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 9999px;
}

/* Active Chips Strip */
.active-chips-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  flex-wrap: wrap;
}

.chips-list {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.chip-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
  font-size: 0.78rem;
  color: #cbd5e1;
}

.chip-item button {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
}

.chip-item button:hover {
  color: #f87171;
}

.chip-date-active {
  background: rgba(5, 150, 105, 0.2);
  border-color: rgba(16, 185, 129, 0.4);
  color: #6ee7b7;
  font-weight: 600;
}

.chip-date-active i {
  color: #10b981;
}

.btn-reset-chips {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 180ms ease;
  white-space: nowrap;
}

.btn-reset-chips:hover {
  color: #f87171;
  background: rgba(239, 68, 68, 0.08);
}

/* ==========================================================================
   3. RESULTS SECTION
   ========================================================================== */
.fleet-results-section {
  position: relative;
  z-index: 10;
  margin-bottom: 60px;
}

.results-toolbar-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.results-count-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.results-heading {
  font-family: var(--font-heading);
  font-size: 1.45rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.02em;
}

.results-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #a7f3d0;
}

.results-options-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.per-page-select-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
}

.per-page-label {
  font-size: 0.78rem;
  color: #8fa0aa;
}

.per-page-select {
  background: rgba(11, 18, 32, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #e2e8f0;
  padding: 5px 8px;
  font-size: 0.8rem;
  outline: none;
  cursor: pointer;
}

.btn-refresh-list {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 180ms ease;
}

.btn-refresh-list:hover:not(:disabled) {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.12);
}

/* ==========================================================================
   4. CAR CARDS GRID & CARD DESIGN
   ========================================================================== */
.fleet-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 28px;
}

.fleet-card {
  background: rgba(18, 25, 42, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 22px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.4);
  transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1),
              border-color 300ms ease,
              box-shadow 300ms ease;
  position: relative;
}

.fleet-card:hover {
  transform: translateY(-6px);
  border-color: rgba(16, 185, 129, 0.35);
  box-shadow: 0 20px 40px -10px rgba(5, 150, 105, 0.22);
}

/* Card Header */
.card-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.car-title-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.car-card-name {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.car-card-name a {
  color: #ffffff;
  transition: color 180ms ease;
}

.car-card-name a:hover {
  color: var(--color-primary-glow, #10b981);
}

.car-meta-sub {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.car-category-pill {
  font-size: 0.72rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.05);
  padding: 2px 8px;
  border-radius: 6px;
}

.city-location-pill {
  font-size: 0.72rem;
  color: #6ee7b7;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.card-arrow-link {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  font-size: 0.85rem;
  transition: all 200ms ease;
  flex-shrink: 0;
}

.fleet-card:hover .card-arrow-link {
  background: var(--color-primary, #059669);
  color: #ffffff;
  border-color: var(--color-primary, #059669);
  transform: rotate(45deg);
}

/* Card Visual Stage (8:5 aspect ratio) */
.card-visual-stage {
  position: relative;
  width: 100%;
  height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 14px 0;
}

.stage-tag-badge {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 5;
}

.status-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 9999px;
  letter-spacing: 0.02em;
}

.status-available {
  background: rgba(5, 150, 105, 0.2);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #6ee7b7;
}

.status-ready {
  background: rgba(2, 132, 199, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.3);
  color: #7dd3fc;
}

.status-maintenance {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(248, 113, 113, 0.3);
  color: #fca5a5;
}

.car-img-anchor {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-car-img {
  max-width: 90%;
  max-height: 180px;
  object-fit: contain;
  transition: transform 320ms cubic-bezier(0.4, 0, 0.2, 1);
  filter: drop-shadow(0 14px 20px rgba(0, 0, 0, 0.5));
}

.fleet-card:hover .card-car-img {
  transform: scale(1.05);
}

.card-ground-shadow {
  width: 75%;
  height: 18px;
  margin-top: -10px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.2) 60%, transparent 80%);
  filter: blur(5px);
  pointer-events: none;
}

/* Card Footer */
.card-footer-box {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.card-specs-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.mini-spec-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #cbd5e1;
}

.mini-spec-badge i {
  color: var(--color-primary-glow, #10b981);
  font-size: 0.75rem;
}

.card-price-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.price-label {
  display: block;
  font-size: 0.7rem;
  color: #8fa0aa;
  font-weight: 500;
  margin-bottom: 2px;
}

.price-value-wrap {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.price-amount {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: #34d399;
}

.price-unit {
  font-size: 0.8rem;
  color: #94a3b8;
}

.price-note {
  font-size: 0.68rem;
  color: #64748b;
}

.card-actions-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.btn-card-detail {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 0;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  color: #e2e8f0;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 200ms ease;
}

.btn-card-detail:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.25);
}

.btn-card-rent {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 0;
  background: linear-gradient(135deg, var(--color-primary, #059669) 0%, #047857 100%);
  border: none;
  border-radius: 12px;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
  transition: all 200ms ease;
}

.btn-card-rent:hover {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* ==========================================================================
   5. PAGINATION CONTROLS
   ========================================================================== */
.pagination-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 48px;
}

.page-nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: rgba(18, 25, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #cbd5e1;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 180ms ease;
}

.page-nav-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.page-nav-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-numbers-list {
  display: flex;
  align-items: center;
  gap: 6px;
}

.page-number-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(18, 25, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #94a3b8;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 180ms ease;
}

.page-number-btn:hover:not(.is-active):not(.is-ellipsis) {
  background: rgba(255, 255, 255, 0.08);
  color: #f1f5f9;
}

.page-number-btn.is-active {
  background: var(--color-primary, #059669);
  color: #ffffff;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.page-number-btn.is-ellipsis {
  cursor: default;
  border: none;
  background: transparent;
}

/* ==========================================================================
   6. STATES (SKELETON, EMPTY, ERROR)
   ========================================================================== */
.skeleton-card {
  pointer-events: none;
}

.skeleton-shimmer {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.04) 25%, rgba(255, 255, 255, 0.09) 50%, rgba(255, 255, 255, 0.04) 75%);
  background-size: 200% 100%;
  animation: skeleton-wave 1.6s infinite;
  border-radius: 8px;
}

@keyframes skeleton-wave {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.skeleton-title {
  width: 70%;
  height: 22px;
  margin-bottom: 6px;
}

.skeleton-sub {
  width: 40%;
  height: 14px;
}

.skeleton-visual-stage {
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.skeleton-image {
  width: 80%;
  height: 110px;
  border-radius: 14px;
}

.skeleton-price {
  width: 45%;
  height: 24px;
  margin-bottom: 10px;
}

.skeleton-badges {
  width: 65%;
  height: 16px;
  margin-bottom: 12px;
}

.skeleton-actions-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.skeleton-btn {
  height: 38px;
  border-radius: 12px;
}

/* State Notice Card */
.state-notice-card {
  background: rgba(18, 25, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 24px;
  padding: 50px 24px;
  text-align: center;
  max-width: 600px;
  margin: 40px auto;
  box-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.5);
}

.state-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 18px;
  font-size: 1.6rem;
}

.icon-empty {
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
}

.icon-error {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

.state-notice-card h3 {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 10px;
}

.state-notice-card p {
  font-size: 0.92rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 22px;
}

.state-actions-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* ==========================================================================
   7. SUPPORT CTA BANNER
   ========================================================================== */
.fleet-cta-banner {
  margin-top: 60px;
}

.cta-banner-card {
  background: linear-gradient(135deg, rgba(11, 18, 32, 0.95) 0%, rgba(18, 26, 44, 0.95) 100%);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 28px;
  padding: 44px 48px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 48px -12px rgba(5, 150, 105, 0.15);
}

.cta-ambient-glow {
  position: absolute;
  top: -50%;
  right: -10%;
  width: 480px;
  height: 480px;
  background: radial-gradient(circle, rgba(5, 150, 105, 0.2), transparent 70%);
  pointer-events: none;
}

.cta-banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 36px;
  position: relative;
  z-index: 2;
}

.cta-text-group {
  max-width: 640px;
}

.cta-eyebrow {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--color-primary-glow, #10b981);
  display: inline-block;
  margin-bottom: 8px;
}

.cta-title {
  font-family: var(--font-heading);
  font-size: clamp(1.6rem, 2.8vw, 2.3rem);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.2;
  margin-bottom: 12px;
}

.cta-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
}

.cta-actions-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex-shrink: 0;
}

.cta-btn {
  padding: 14px 28px;
  font-size: 0.95rem;
}

.cta-btn-sec {
  padding: 12px 24px;
  font-size: 0.88rem;
}

/* ==========================================================================
   8. MOBILE FILTER DRAWER
   ========================================================================== */
.mobile-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
}

.mobile-drawer-sheet {
  width: 100%;
  max-width: 400px;
  height: 100%;
  background: #0f172a;
  border-left: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  flex-direction: column;
  animation: drawer-slide-in 260ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes drawer-slide-in {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.drawer-header {
  padding: 20px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.drawer-title-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.drawer-title-group h3 {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.btn-close-drawer {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.drawer-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.drawer-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.drawer-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 6px;
}

.drawer-label i {
  color: var(--color-primary-glow, #10b981);
}

.drawer-footer {
  padding: 16px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 12px;
}

.btn-drawer-reset {
  padding: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #cbd5e1;
  font-weight: 600;
  cursor: pointer;
}

.btn-drawer-apply {
  padding: 12px;
  font-size: 0.88rem;
}

/* ==========================================================================
   9. BOOKING CONFIRMATION MODAL (MATCH HOME)
   ========================================================================== */
.modal-backdrop-dark {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.78);
  backdrop-filter: blur(8px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.booking-modal-card {
  width: 100%;
  max-width: 560px;
  background: #111a2e;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  padding: 28px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
  animation: modal-pop 260ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes modal-pop {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 16px;
}

.head-eyebrow {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: var(--color-primary-glow, #10b981);
  display: block;
  margin-bottom: 4px;
}

.head-title-wrap h3 {
  font-family: var(--font-heading);
  font-size: 1.4rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.btn-close-modal {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: none;
  color: #cbd5e1;
  font-size: 1.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  line-height: 1;
}

.modal-car-preview {
  position: relative;
  background: rgba(0, 0, 0, 0.35);
  border-radius: 16px;
  padding: 16px;
  text-align: center;
  margin-bottom: 20px;
}

.modal-car-preview img {
  max-height: 130px;
  margin: 0 auto;
  object-fit: contain;
  filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.4));
}

.car-badge-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.cat-pill {
  font-size: 0.75rem;
  background: rgba(255, 255, 255, 0.08);
  padding: 3px 10px;
  border-radius: 9999px;
  color: #e2e8f0;
}

.rate-text {
  color: #34d399;
  font-size: 0.95rem;
}

.booking-config-fields {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 20px;
}

.field-item label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #94a3b8;
  margin-bottom: 6px;
}

.field-item label i {
  color: var(--color-primary-glow, #10b981);
}

.modal-input {
  width: 100%;
  padding: 11px 14px;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  color: #ffffff;
  font-size: 0.88rem;
  outline: none;
  color-scheme: dark;
}

.modal-input:focus {
  border-color: var(--color-primary-glow, #10b981);
}

.fields-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.price-calculation-card {
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 24px;
}

.calc-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #94a3b8;
  margin-bottom: 8px;
}

.calc-row strong, .calc-row span:last-child {
  color: #e2e8f0;
}

.calc-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 10px 0;
}

.grand-total {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff !important;
  margin-bottom: 4px;
}

.price-total {
  color: #34d399 !important;
  font-size: 1.15rem;
}

.note-text {
  display: block;
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 6px;
}

.modal-footer-actions {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 12px;
}

.btn-cancel-modal {
  padding: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #cbd5e1;
  font-weight: 600;
  cursor: pointer;
}

.btn-confirm-booking {
  padding: 12px;
  background: var(--grad-active);
  border: none;
  border-radius: 12px;
  color: #ffffff;
  font-weight: 700;
  font-family: var(--font-heading);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 16px rgba(5, 150, 105, 0.35);
}

.btn-confirm-booking:hover:not(:disabled) {
  filter: brightness(1.08);
}

.btn-confirm-booking:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ==========================================================================
   RESPONSIVE BREAKPOINTS
   ========================================================================== */
@media (max-width: 1200px) {
  .primary-search-form {
    grid-template-columns: 1fr 1fr 1fr;
  }
  .search-text-col {
    grid-column: span 2;
  }
  .search-action-col {
    grid-column: span 1;
  }
}

@media (max-width: 960px) {
  .intro-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }
  .trust-badges-grid {
    grid-template-columns: 1fr 1fr;
  }
  .primary-search-form {
    grid-template-columns: 1fr 1fr;
  }
  .search-text-col {
    grid-column: span 2;
  }
  .search-action-col {
    grid-column: span 2;
  }
  .btn-submit-search {
    width: 100%;
  }
  .secondary-filters-bar {
    display: none;
  }
  .btn-mobile-filter-trigger {
    display: inline-flex;
    margin-top: 14px;
    width: 100%;
    justify-content: center;
  }
  .cta-banner-content {
    flex-direction: column;
    align-items: flex-start;
  }
  .cta-actions-group {
    width: 100%;
    flex-direction: row;
  }
  .cta-actions-group a {
    flex: 1;
    text-align: center;
  }
}

@media (max-width: 640px) {
  .fleet-intro-section {
    padding-top: 108px;
  }
  .trust-badges-grid {
    grid-template-columns: 1fr;
  }
  .primary-search-form {
    grid-template-columns: 1fr;
  }
  .search-text-col, .search-action-col {
    grid-column: span 1;
  }
  .fields-two-col {
    grid-template-columns: 1fr;
  }
  .modal-footer-actions {
    grid-template-columns: 1fr;
  }
  .cta-actions-group {
    flex-direction: column;
  }
  .cta-banner-card {
    padding: 30px 20px;
  }
  .results-toolbar-row {
    flex-direction: column;
    align-items: flex-start;
  }
  .results-options-group {
    width: 100%;
    justify-content: space-between;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .fleet-card, .card-car-img, .btn-submit-search, .btn-card-rent, .card-arrow-link {
    transition: none !important;
  }
  .fleet-card:hover {
    transform: none !important;
  }
  .skeleton-shimmer {
    animation: none !important;
  }
}
</style>
