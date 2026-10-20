<template>
  <div class="admin-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-newspaper"></i></div>
        <div>
          <h1 class="page-title">Quản Lý <span>Bài Viết & Tin Tức</span></h1>
          <p class="page-subtitle">Soạn thảo, kiểm duyệt đa cấp, quản lý xuất bản cẩm nang thuê xe và tin tức VinFast.</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-outline-action" @click="openCategoryModal">
          <i class="fa-solid fa-folder-tree"></i>
          <span>Chuyên mục</span>
        </button>
        <button class="btn-outline-action" @click="downloadTemplate" title="Tải file mẫu Excel 4 cột chuẩn">
          <i class="fa-solid fa-file-excel text-emerald"></i>
          <span>Tải file mẫu Excel</span>
        </button>
        <button class="btn-outline-action" @click="showChannelConfigModal = true" title="Cấu hình Google Drive & Facebook Page">
          <i class="fa-solid fa-sliders"></i>
          <span>Cấu hình Kênh</span>
        </button>
        <button class="btn-primary btn-emerald" @click="showImportModal = true" title="Import Excel và ghép ảnh Google Drive">
          <i class="fa-solid fa-file-import"></i>
          <span>✦ Import Lịch Đăng</span>
        </button>
        <button class="btn-refresh" @click="onRefreshCurrentView" :disabled="loading || loadingSchedules || loadingBatches" title="Tải lại">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading || loadingSchedules || loadingBatches }"></i>
          <span>Làm mới</span>
        </button>
        <button v-if="permissions.can_create || permissions.can_publish" class="btn-primary" @click="openCreateModal">
          <i class="fa-solid fa-pen-nib"></i>
          <span>Viết bài mới</span>
        </button>
      </div>
    </header>

    <!-- Main View Switcher Tabs -->
    <div class="main-tabs-bar">
      <button 
        class="main-tab-btn" 
        :class="{ active: activeMainTab === 'articles' }"
        @click="switchMainTab('articles')"
      >
        <i class="fa-solid fa-newspaper"></i>
        <span>1. Quản lý Bài Viết (CMS Website)</span>
      </button>

      <button 
        class="main-tab-btn" 
        :class="{ active: activeMainTab === 'schedules' }"
        @click="switchMainTab('schedules')"
      >
        <i class="fa-solid fa-calendar-days text-primary"></i>
        <span>2. Lịch Đăng Bài Đa Kênh (Automation)</span>
        <span v-if="schedulesPagination.total > 0" class="tab-count-badge">{{ schedulesPagination.total }}</span>
      </button>

      <button 
        class="main-tab-btn" 
        :class="{ active: activeMainTab === 'batches' }"
        @click="switchMainTab('batches')"
      >
        <i class="fa-solid fa-boxes-stacked"></i>
        <span>3. Đợt Import Excel (Batches)</span>
      </button>
    </div>

    <!-- VIEW 1: QUẢN LÝ BÀI VIẾT (CMS) -->
    <div v-if="activeMainTab === 'articles'" class="articles-view-section">
    <!-- KPI Summary Grid -->
    <div class="kpi-grid">
      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'all' && !filterPendingRevision }" 
        @click="setStatusFilter('all')"
      >
        <div class="kpi-icon total"><i class="fa-solid fa-file-lines"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Tổng bài viết</span>
          <span class="kpi-val">{{ kpi.total || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'published' && !filterPendingRevision }" 
        @click="setStatusFilter('published')"
      >
        <div class="kpi-icon published"><i class="fa-solid fa-circle-check"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Đã xuất bản</span>
          <span class="kpi-val text-success">{{ kpi.published || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'pending_review' && !filterPendingRevision }" 
        @click="setStatusFilter('pending_review')"
      >
        <div class="kpi-icon pending"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Chờ duyệt</span>
          <span class="kpi-val text-warning">{{ kpi.pending_review || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'draft' && !filterPendingRevision }" 
        @click="setStatusFilter('draft')"
      >
        <div class="kpi-icon draft"><i class="fa-solid fa-pen-ruler"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Bản nháp</span>
          <span class="kpi-val text-secondary">{{ kpi.draft || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterPendingRevision === '1' }" 
        @click="togglePendingRevisionFilter"
      >
        <div class="kpi-icon revision"><i class="fa-solid fa-code-compare"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Bản sửa đổi chờ duyệt</span>
          <span class="kpi-val text-info">{{ kpi.pending_revisions || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'archived' && !filterPendingRevision }" 
        @click="setStatusFilter('archived')"
      >
        <div class="kpi-icon archived"><i class="fa-solid fa-box-archive"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Lưu trữ</span>
          <span class="kpi-val text-muted">{{ kpi.archived || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ 'active': filterStatus === 'trashed' && !filterPendingRevision }" 
        @click="setStatusFilter('trashed')"
      >
        <div class="kpi-icon trashed"><i class="fa-solid fa-trash-can"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Thùng rác</span>
          <span class="kpi-val text-danger">{{ kpi.trashed || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="content-card card">
      <!-- Toolbar Filters -->
      <div class="table-tools">
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Tìm theo tiêu đề, tóm tắt, slug, tác giả..."
            @input="onSearchInput"
          />
          <button v-if="searchQuery" class="btn-clear-search" @click="clearSearch">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="filters-row">
          <select v-model="filterStatus" class="form-select" @change="fetchPosts(1)">
            <option value="all">Tất cả trạng thái</option>
            <option value="published">Đã xuất bản</option>
            <option value="pending_review">Chờ kiểm duyệt</option>
            <option value="draft">Bản nháp</option>
            <option value="archived">Kho lưu trữ</option>
            <option value="trashed">Thùng rác (Đã xóa)</option>
          </select>

          <select v-model="filterCategory" class="form-select" @change="fetchPosts(1)">
            <option value="all">Tất cả chuyên mục</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>

          <select v-model="filterAuthor" class="form-select" @change="fetchPosts(1)">
            <option value="all">Tất cả tác giả</option>
            <option v-for="aut in authors" :key="aut.id" :value="aut.id">{{ aut.ho_ten }}</option>
          </select>

          <button v-if="hasActiveFilters" class="btn-reset" @click="resetFilters" title="Đặt lại bộ lọc">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Đặt lại</span>
          </button>
        </div>
      </div>

      <!-- Table of Posts -->
      <div class="table-responsive">
        <table class="premium-table" v-if="posts.length > 0">
          <thead>
            <tr>
              <th width="70">ID</th>
              <th width="320">Bài viết</th>
              <th width="150">Chuyên mục</th>
              <th width="140">Tác giả</th>
              <th width="140">Trạng thái</th>
              <th width="140">Xuất bản</th>
              <th width="90" class="text-center">Xem</th>
              <th width="160" class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in posts" :key="p.id" :class="{ 'row-trashed': p.deleted_at }">
              <!-- ID -->
              <td>
                <span class="id-tag">#{{ p.id }}</span>
              </td>

              <!-- Post Info (Image + Title + Reading time) -->
              <td>
                <div class="post-cell">
                  <img 
                    :src="p.featured_image || '/assets/images/default-car.png'" 
                    class="post-thumb"
                    alt="Ảnh đại diện"
                    @error="$event.target.src = '/assets/images/default-car.png'"
                  />
                  <div class="post-info">
                    <h4 class="post-title" :title="p.title">{{ p.title }}</h4>
                    <div class="post-meta-sub">
                      <span><i class="fa-regular fa-clock"></i> {{ p.reading_time || 1 }} phút đọc</span>
                      <span class="dot">•</span>
                      <span class="slug-tag">/{{ p.slug }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Category -->
              <td>
                <span v-if="p.category" class="category-pill">{{ p.category.name }}</span>
                <span v-else class="text-muted-dash">-</span>
              </td>

              <!-- Author -->
              <td>
                <div class="author-cell">
                  <div class="author-avatar">{{ (p.author?.ho_ten || 'A').charAt(0).toUpperCase() }}</div>
                  <span class="author-name">{{ p.author?.ho_ten || 'Hệ thống' }}</span>
                </div>
              </td>

              <!-- Status -->
              <td>
                <div class="status-cell">
                  <span class="status-badge" :class="getStatusClass(p)">
                    <i class="fa-solid" :class="getStatusIcon(p)"></i>
                    {{ p.status_label || getStatusText(p.status) }}
                  </span>
                  
                  <!-- Pending revision warning pill -->
                  <button 
                    v-if="p.has_pending_revision && !p.deleted_at" 
                    class="pill-revision-alert" 
                    @click="openRevisionModal(p)"
                    title="Có bản sửa đổi đang chờ duyệt"
                  >
                    <i class="fa-solid fa-code-compare"></i> Sửa đổi chờ duyệt
                  </button>
                </div>
              </td>

              <!-- Publication Date -->
              <td>
                <div class="date-cell">
                  <span v-if="p.published_at">{{ formatDateTime(p.published_at) }}</span>
                  <span v-else class="text-muted">Chưa công bố</span>
                </div>
              </td>

              <!-- Views -->
              <td class="text-center">
                <span class="views-badge"><i class="fa-regular fa-eye"></i> {{ p.views_count || 0 }}</span>
              </td>

              <!-- Actions -->
              <td class="text-center">
                <!-- If In Trash -->
                <div v-if="p.deleted_at" class="action-buttons-wrap">
                  <button class="btn-act restore" @click="restorePost(p)" title="Khôi phục">
                    <i class="fa-solid fa-rotate-left"></i>
                  </button>
                  <button class="btn-act force-delete" @click="confirmForceDelete(p)" title="Xóa vĩnh viễn">
                    <i class="fa-solid fa-ban"></i>
                  </button>
                </div>

                <!-- If Active / Normal -->
                <div v-else class="action-buttons-wrap">
                  <!-- View Live / Preview -->
                  <a 
                    v-if="p.status === 'published'" 
                    :href="`/blog/${p.slug}`" 
                    target="_blank" 
                    class="btn-act view" 
                    title="Xem trên trang người dùng"
                  >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                  </a>

                  <!-- Edit -->
                  <button 
                    class="btn-act edit" 
                    @click="openEditModal(p)" 
                    title="Chỉnh sửa bài viết"
                  >
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>

                  <!-- Review / Approve / Reject (If pending_review) -->
                  <button 
                    v-if="p.status === 'pending_review' && (permissions.can_review || permissions.can_publish)" 
                    class="btn-act review" 
                    @click="openReviewModal(p)" 
                    title="Duyệt bài viết này"
                  >
                    <i class="fa-solid fa-stamp"></i>
                  </button>

                  <!-- Quick Publish / Archive (Publisher only) -->
                  <button 
                    v-if="p.status !== 'published' && permissions.can_publish" 
                    class="btn-act publish" 
                    @click="quickPublish(p)" 
                    title="Xuất bản ngay"
                  >
                    <i class="fa-solid fa-upload"></i>
                  </button>

                  <button 
                    v-if="p.status === 'published' && permissions.can_publish" 
                    class="btn-act archive" 
                    @click="archivePost(p)" 
                    title="Chuyển vào lưu trữ"
                  >
                    <i class="fa-solid fa-box-archive"></i>
                  </button>

                  <!-- Delete to Trash -->
                  <button 
                    v-if="permissions.can_delete || permissions.can_publish" 
                    class="btn-act delete" 
                    @click="confirmDelete(p)" 
                    title="Xóa vào thùng rác"
                  >
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state">
          <i class="fa-regular fa-newspaper"></i>
          <h4>Không tìm thấy bài viết nào</h4>
          <p>Chưa có bài viết phù hợp với điều kiện lọc hoặc từ khóa tìm kiếm.</p>
          <button v-if="hasActiveFilters" class="btn-outline-sm" @click="resetFilters">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc
          </button>
        </div>
      </div>

      <!-- Pagination Footer -->
      <div class="table-pagination-footer" v-if="pagination.total > 0">
        <div class="pagination-info">
          Hiển thị <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }} - {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong> 
          trong tổng số <strong>{{ pagination.total }}</strong> bài viết
        </div>
        <div class="pagination-controls" v-if="pagination.last_page > 1">
          <button 
            class="btn-page" 
            :disabled="pagination.current_page <= 1 || loading" 
            @click="fetchPosts(pagination.current_page - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          
          <button 
            v-for="p in visiblePages" 
            :key="p" 
            class="btn-page" 
            :class="{ 'active': p === pagination.current_page }" 
            @click="fetchPosts(p)"
          >
            {{ p }}
          </button>

          <button 
            class="btn-page" 
            :disabled="pagination.current_page >= pagination.last_page || loading" 
            @click="fetchPosts(pagination.current_page + 1)"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>

      </div>
    </div> <!-- /articles-view-section -->

    <!-- ========================================================
         VIEW 2: LỊCH ĐĂNG BÀI ĐA KÊNH (AUTOMATION SCHEDULER)
         ======================================================== -->
    <div v-if="activeMainTab === 'schedules'" class="schedules-view-section">
      <!-- Schedules KPI Grid -->
      <div class="kpi-grid">
        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_website === 'all' && scheduleFilters.status_facebook === 'all' }" 
          @click="setScheduleFilter('all')"
        >
          <div class="kpi-icon total"><i class="fa-solid fa-calendar-days"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Tổng lịch đăng</span>
            <span class="kpi-val">{{ schedulesKpi.total || 0 }}</span>
          </div>
        </div>

        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_website === 'scheduled' || scheduleFilters.status_facebook === 'scheduled' }" 
          @click="setScheduleFilter('scheduled')"
        >
          <div class="kpi-icon pending"><i class="fa-solid fa-clock"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Chờ đến giờ đăng</span>
            <span class="kpi-val text-warning">{{ schedulesKpi.scheduled || 0 }}</span>
          </div>
        </div>

        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_website === 'published' }" 
          @click="setScheduleFilter('website_published')"
        >
          <div class="kpi-icon published"><i class="fa-solid fa-globe"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Đã đăng Website</span>
            <span class="kpi-val text-success">{{ schedulesKpi.website_published || 0 }}</span>
          </div>
        </div>

        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_facebook === 'published' }" 
          @click="setScheduleFilter('facebook_published')"
        >
          <div class="kpi-icon revision"><i class="fa-brands fa-facebook"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Đã đăng Fanpage</span>
            <span class="kpi-val text-info">{{ schedulesKpi.facebook_published || 0 }}</span>
          </div>
        </div>

        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_website === 'failed' || scheduleFilters.status_facebook === 'failed' }" 
          @click="setScheduleFilter('failed')"
        >
          <div class="kpi-icon trashed"><i class="fa-solid fa-circle-exclamation"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Lỗi xuất bản</span>
            <span class="kpi-val text-danger">{{ schedulesKpi.failed || 0 }}</span>
          </div>
        </div>

        <div 
          class="kpi-card" 
          :class="{ active: scheduleFilters.status_facebook === 'outcome_unknown' }" 
          @click="setScheduleFilter('outcome_unknown')"
        >
          <div class="kpi-icon archived"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div class="kpi-info">
            <span class="kpi-label">Chờ đối soát (Timeout)</span>
            <span class="kpi-val text-muted">{{ schedulesKpi.outcome_unknown || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Schedules Main Card -->
      <div class="content-card card">
        <div class="table-tools">
          <div class="search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input 
              type="text" 
              v-model="scheduleFilters.search" 
              placeholder="Tìm theo tiêu đề, nội dung, tên ảnh..."
              @input="onScheduleSearchInput"
            />
            <button v-if="scheduleFilters.search" class="btn-clear-search" @click="scheduleFilters.search = ''; fetchSchedules(1)">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="filters-row">
            <select v-model="scheduleFilters.channel" class="form-select" @change="fetchSchedules(1)">
              <option value="all">Tất cả kênh</option>
              <option value="website">Chỉ Website</option>
              <option value="facebook">Chỉ Facebook</option>
              <option value="both">Cả hai (Web & FB)</option>
            </select>

            <select v-model="scheduleFilters.status_website" class="form-select" @change="fetchSchedules(1)">
              <option value="all">Trạng thái Website: Tất cả</option>
              <option value="scheduled">Website: Chờ đăng</option>
              <option value="published">Website: Đã đăng</option>
              <option value="failed">Website: Lỗi</option>
            </select>

            <select v-model="scheduleFilters.status_facebook" class="form-select" @change="fetchSchedules(1)">
              <option value="all">Trạng thái FB: Tất cả</option>
              <option value="scheduled">FB: Chờ đăng</option>
              <option value="published">FB: Đã đăng</option>
              <option value="failed">FB: Lỗi</option>
              <option value="outcome_unknown">FB: Chờ đối soát</option>
            </select>

            <button v-if="scheduleFilters.batch_id" class="btn-clear-filters" @click="scheduleFilters.batch_id = ''; fetchSchedules(1)">
              <i class="fa-solid fa-xmark"></i> Bỏ lọc Batch #{{ scheduleFilters.batch_id }}
            </button>
          </div>
        </div>

        <!-- Schedules Table -->
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th width="100">Ảnh snapshot</th>
                <th width="240">Tiêu đề bài viết</th>
                <th>Nội dung (Caption)</th>
                <th width="150">Thời gian đăng</th>
                <th width="100">Kênh</th>
                <th width="150">Website</th>
                <th width="150">Facebook Page</th>
                <th width="130" class="text-center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="schedules.length === 0 && !loadingSchedules">
                <td colspan="8" class="text-center empty-cell">
                  <div class="empty-state">
                    <i class="fa-regular fa-calendar-xmark empty-icon"></i>
                    <p>Không có bài viết nào trong lịch đăng với bộ lọc hiện tại.</p>
                    <button class="btn-primary-sm mt-2 btn-emerald" @click="showImportModal = true">
                      <i class="fa-solid fa-file-import"></i> Import Excel ngay
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-for="sc in schedules" :key="sc.id">
                <!-- Snapshot Thumbnail -->
                <td>
                  <div class="schedule-thumb-box" @click="openImagePreview(sc.snapshot_url || sc.image_snapshot_path)">
                    <img :src="sc.snapshot_url" alt="Snapshot" class="schedule-thumb-img" />
                    <span class="thumb-drive-tag" :title="sc.drive_image_name">
                      <i class="fa-brands fa-google-drive"></i> {{ sc.drive_image_name }}
                    </span>
                  </div>
                </td>

                <!-- Tiêu đề & Batch -->
                <td>
                  <div class="schedule-title-cell">
                    <span class="schedule-title">{{ sc.title || sc.title_suggested }}</span>
                    <span v-if="sc.batch_id" class="batch-link-badge" @click="filterSchedulesByBatch(sc.batch_id)">
                      #BATCH-{{ sc.batch_id }}
                    </span>
                  </div>
                </td>

                <!-- Nội dung tóm tắt -->
                <td>
                  <div class="schedule-content-cell">
                    <p class="content-preview-line">{{ sc.content }}</p>
                    <button type="button" class="btn-read-more" @click="openFullContent(sc)">
                      Xem toàn văn
                    </button>
                  </div>
                </td>

                <!-- Thời gian đăng -->
                <td>
                  <div class="schedule-time-cell">
                    <i class="fa-regular fa-clock text-muted"></i>
                    <span class="time-main">{{ formatDateTime(sc.scheduled_at) }}</span>
                    <span v-if="isUpcoming(sc.scheduled_at)" class="time-countdown text-emerald">
                      {{ getTimeRemaining(sc.scheduled_at) }}
                    </span>
                    <span v-else class="time-countdown text-muted">Đã qua giờ</span>
                  </div>
                </td>

                <!-- Kênh -->
                <td>
                  <span class="badge-channel" :class="'channel-' + sc.channel">
                    {{ getChannelLabel(sc.channel) }}
                  </span>
                </td>

                <!-- Trạng thái Website -->
                <td>
                  <div class="status-cell-col">
                    <span class="badge-status-pill" :class="'status-' + sc.status_website">
                      <i class="fa-solid" :class="getStatusIcon(sc.status_website)"></i>
                      {{ getStatusLabel(sc.status_website) }}
                    </span>
                    <a v-if="sc.post?.slug" :href="'/blog/' + sc.post.slug" target="_blank" class="post-live-link">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem bài viết
                    </a>
                    <small v-if="sc.website_error" class="text-danger error-inline" :title="sc.website_error">
                      {{ sc.website_error }}
                    </small>
                  </div>
                </td>

                <!-- Trạng thái Facebook Page -->
                <td>
                  <div class="status-cell-col">
                    <span class="badge-status-pill" :class="'status-' + sc.status_facebook">
                      <i class="fa-solid" :class="getStatusIcon(sc.status_facebook)"></i>
                      {{ getStatusLabel(sc.status_facebook) }}
                    </span>
                    <a v-if="sc.facebook_post_url" :href="sc.facebook_post_url" target="_blank" class="post-live-link fb-link">
                      <i class="fa-brands fa-facebook"></i> Mở bài Page
                    </a>
                    <small v-if="sc.facebook_error" class="text-danger error-inline" :title="sc.facebook_error">
                      {{ sc.facebook_error }}
                    </small>
                  </div>
                </td>

                <!-- Thao tác -->
                <td class="text-center">
                  <div class="action-btns-group">
                    <button 
                      v-if="canEditSchedule(sc)" 
                      class="btn-act edit" 
                      @click="openEditSchedule(sc)" 
                      title="Chỉnh sửa nội dung & giờ đăng"
                    >
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button 
                      v-if="canPublishNow(sc)" 
                      class="btn-act publish" 
                      @click="handlePublishNow(sc)" 
                      title="Đăng ngay lập tức"
                    >
                      <i class="fa-solid fa-paper-plane"></i>
                    </button>

                    <button 
                      v-if="canRetrySchedule(sc)" 
                      class="btn-act retry" 
                      @click="handleRetrySchedule(sc)" 
                      title="Thử lại xuất bản"
                    >
                      <i class="fa-solid fa-rotate-right"></i>
                    </button>

                    <button 
                      v-if="canCancelSchedule(sc)" 
                      class="btn-act delete" 
                      @click="handleCancelSchedule(sc)" 
                      title="Hủy lịch đăng"
                    >
                      <i class="fa-solid fa-ban"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Schedules Pagination -->
        <div v-if="schedulesPagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Hiển thị trang <strong>{{ schedulesPagination.current_page }}</strong> / {{ schedulesPagination.last_page }} (Tổng {{ schedulesPagination.total }} lịch đăng)
          </span>
          <div class="pagination-btns">
            <button class="btn-page" :disabled="schedulesPagination.current_page <= 1" @click="fetchSchedules(schedulesPagination.current_page - 1)">
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button class="btn-page" :disabled="schedulesPagination.current_page >= schedulesPagination.last_page" @click="fetchSchedules(schedulesPagination.current_page + 1)">
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>

        <div v-if="loadingSchedules" class="loading-overlay">
          <i class="fa-solid fa-spinner fa-spin"></i>
        </div>
      </div>
    </div>

    <!-- ========================================================
         VIEW 3: ĐỢT IMPORT EXCEL (BATCHES)
         ======================================================== -->
    <div v-if="activeMainTab === 'batches'" class="batches-view-section">
      <div class="content-card card">
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th width="120">Mã đợt</th>
                <th>Tên tệp Excel</th>
                <th width="180">Thời gian xác nhận</th>
                <th width="120">Kênh</th>
                <th width="160" class="text-center">Số bài / Hợp lệ</th>
                <th width="150" class="text-center">Trạng thái</th>
                <th width="160" class="text-center">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="batches.length === 0 && !loadingBatches">
                <td colspan="7" class="text-center empty-cell">
                  <div class="empty-state">
                    <i class="fa-solid fa-boxes-stacked empty-icon"></i>
                    <p>Chưa có đợt import Excel nào.</p>
                  </div>
                </td>
              </tr>

              <tr v-for="b in batches" :key="b.id">
                <td><strong>#BATCH-{{ b.id }}</strong></td>
                <td>
                  <div class="batch-name-cell">
                    <i class="fa-solid fa-file-excel text-emerald"></i>
                    <span>{{ b.file_name }}</span>
                  </div>
                </td>
                <td>{{ formatDateTime(b.confirmed_at || b.created_at) }}</td>
                <td>
                  <span class="badge-channel" :class="'channel-' + b.channel">
                    {{ getChannelLabel(b.channel) }}
                  </span>
                </td>
                <td class="text-center">
                  <strong>{{ b.valid_rows || 0 }}</strong> / {{ b.total_rows || 0 }} bài
                </td>
                <td class="text-center">
                  <span class="badge-batch-status" :class="'bstatus-' + b.status">
                    {{ getBatchStatusLabel(b.status) }}
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn-primary-sm" @click="filterSchedulesByBatch(b.id)">
                    <i class="fa-solid fa-list-check"></i> Xem bài viết
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="loadingBatches" class="loading-overlay">
          <i class="fa-solid fa-spinner fa-spin"></i>
        </div>
      </div>
    </div>

    <!-- ==========================================
         MODAL 1: SOẠN THẢO / CHỈNH SỬA BÀI VIẾT
         ========================================== -->
    <!-- ==========================================
         MODAL 1: SOẠN THẢO / CHỈNH SỬA BÀI VIẾT
         ========================================== -->
    <PostEditorModal
      v-model:visible="showEditorModal"
      :post-id="editingPostId"
      :categories="categories"
      :permissions="permissions"
      @saved="onPostSaved"
      @close="showEditorModal = false"
    />

    <!-- ==========================================
         MODAL 2: QUẢN LÝ CHUYÊN MỤC BÀI VIẾT
         ========================================== -->
    <div v-if="showCategoryModal" class="modal-overlay" @click.self="showCategoryModal = false">
      <div class="modal-content card category-modal animate-in">
        <div class="modal-header">
          <div class="header-icon-badge">
            <i class="fa-solid fa-folder-tree"></i>
          </div>
          <div class="header-title-wrap">
            <h3>Quản Lý Chuyên Mục Bài Viết</h3>
            <p class="modal-sub">Thêm, sửa, phân cấp chuyên mục cẩm nang và tin tức</p>
          </div>
          <button class="btn-close-modal" @click="showCategoryModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
          <!-- Add Category Inline Form -->
          <div class="category-add-bar">
            <input 
              type="text" 
              v-model="newCategoryName" 
              class="form-input" 
              placeholder="Tên chuyên mục mới (VD: Cẩm nang du lịch)..."
              @keyup.enter="handleCreateCategory"
            />
            <input 
              type="text" 
              v-model="newCategoryDesc" 
              class="form-input" 
              placeholder="Mô tả ngắn..."
              @keyup.enter="handleCreateCategory"
            />
            <button class="btn-primary-sm" @click="handleCreateCategory" :disabled="creatingCategory || !newCategoryName.trim()">
              <i class="fa-solid fa-plus"></i> Thêm
            </button>
          </div>

          <!-- Category List Table -->
          <div class="cat-table-wrap">
            <table class="sub-table">
              <thead>
                <tr>
                  <th>Tên chuyên mục</th>
                  <th>Đường dẫn (Slug)</th>
                  <th class="text-center">Số bài viết</th>
                  <th class="text-center" width="80">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="cat in fullCategoriesList" :key="cat.id">
                  <td><strong>{{ cat.name }}</strong></td>
                  <td><code>/blog?category={{ cat.slug }}</code></td>
                  <td class="text-center"><span class="badge-count">{{ cat.posts_count || 0 }}</span></td>
                  <td class="text-center">
                    <button class="btn-act delete" @click="handleDeleteCategory(cat)" title="Xóa chuyên mục">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="showCategoryModal = false">Đóng</button>
        </div>
      </div>
    </div>

    <!-- ==========================================
         MODAL 3: DUYỆT / TỪ CHỐI BÀI VIẾT
         ========================================== -->
    <div v-if="showReviewModal && targetReviewPost" class="modal-overlay" @click.self="showReviewModal = false">
      <div class="modal-content card review-modal animate-in">
        <div class="modal-header">
          <div class="header-icon-badge warning"><i class="fa-solid fa-stamp"></i></div>
          <div class="header-title-wrap">
            <h3>Kiểm Duyệt Bài Viết #{{ targetReviewPost.id }}</h3>
            <p class="modal-sub">{{ targetReviewPost.title }}</p>
          </div>
          <button class="btn-close-modal" @click="showReviewModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <p>Tác giả: <strong>{{ targetReviewPost.author?.ho_ten || 'Tác giả' }}</strong></p>
          <p>Tóm tắt: <em>{{ targetReviewPost.summary || 'Chưa có tóm tắt' }}</em></p>
          
          <div class="form-group mt-3">
            <label class="form-label">Lý do từ chối / Góp ý biên tập (nếu yêu cầu sửa lại):</label>
            <textarea 
              v-model="rejectionReasonInput" 
              class="form-textarea" 
              rows="3" 
              placeholder="Nhập lý do cần tác giả chỉnh sửa lại..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-outline" @click="showReviewModal = false">Đóng</button>
          <div class="footer-actions-right">
            <button class="btn-danger-action" @click="submitReviewDecision('reject_review')" :disabled="reviewing">
              <i class="fa-solid fa-rotate-left"></i> Yêu cầu sửa lại
            </button>
            <button class="btn-primary" @click="submitReviewDecision('approve_review')" :disabled="reviewing">
              <i class="fa-solid fa-circle-check"></i> Duyệt bài viết
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         MODAL 4: SO SÁNH BẢN SỬA ĐỔI CHỜ DUYỆT
         ========================================== -->
    <div v-if="showRevisionModal && targetRevisionPost" class="modal-overlay" @click.self="showRevisionModal = false">
      <div class="modal-content card diff-modal animate-in">
        <div class="modal-header">
          <div class="header-icon-badge info"><i class="fa-solid fa-code-compare"></i></div>
          <div class="header-title-wrap">
            <h3>Xét Duyệt Bản Sửa Đổi Cho Bài Viết #{{ targetRevisionPost.id }}</h3>
            <p class="modal-sub">So sánh nội dung đang công khai vs bản đề xuất mới</p>
          </div>
          <button class="btn-close-modal" @click="showRevisionModal = false"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body diff-body">
          <div class="diff-meta-bar">
            <span>Người đề xuất sửa: <strong>{{ targetRevisionPost.pending_changes?.editor_name || 'Tác giả' }}</strong></span>
            <span>Ghi chú: <em>{{ targetRevisionPost.pending_changes?.revision_notes || 'Cập nhật nội dung' }}</em></span>
          </div>

          <div class="diff-columns">
            <div class="diff-col current">
              <div class="diff-col-head">
                <i class="fa-solid fa-globe"></i>
                <span>Nội dung đang công khai hiện tại</span>
              </div>
              <div class="diff-box">
                <h4>{{ targetRevisionPost.title }}</h4>
                <p class="diff-summary">{{ targetRevisionPost.summary }}</p>
                <div class="diff-content-text">{{ targetRevisionPost.content }}</div>
              </div>
            </div>

            <div class="diff-col pending">
              <div class="diff-col-head">
                <i class="fa-solid fa-pen-fancy"></i>
                <span>Bản sửa đổi đề xuất mới</span>
              </div>
              <div class="diff-box">
                <h4>{{ targetRevisionPost.pending_changes?.title }}</h4>
                <p class="diff-summary">{{ targetRevisionPost.pending_changes?.summary }}</p>
                <div class="diff-content-text">{{ targetRevisionPost.pending_changes?.content }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-outline" @click="showRevisionModal = false">Đóng</button>
          <div class="footer-actions-right">
            <button class="btn-danger-action" @click="submitRevisionDecision('reject_revision')" :disabled="reviewing">
              <i class="fa-solid fa-xmark"></i> Hủy bản sửa đổi
            </button>
            <button v-if="permissions.can_publish" class="btn-primary" @click="submitRevisionDecision('approve_revision')" :disabled="reviewing">
              <i class="fa-solid fa-check-double"></i> Duyệt & Cập nhật công khai
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         MODAL 5: IMPORT EXCEL LỊCH ĐĂNG & DRIVE
         ========================================== -->
    <PostImportModal
      :visible="showImportModal"
      :categories="categories"
      @close="showImportModal = false"
      @imported="onImportCompleted"
      @view-schedules="switchMainTab('schedules')"
    />

    <!-- ==========================================
         MODAL 6: CẤU HÌNH KÊNH (DRIVE & FACEBOOK)
         ========================================== -->
    <PostChannelConfigModal
      :visible="showChannelConfigModal"
      @close="showChannelConfigModal = false"
      @updated="onChannelConfigUpdated"
    />

    <!-- ==========================================
         MODAL 7: SỬA LỊCH ĐĂNG BÀI VIẾT
         ========================================== -->
    <PostScheduleEditModal
      :visible="showScheduleEditModal"
      :schedule="targetEditSchedule"
      :categories="categories"
      @close="showScheduleEditModal = false"
      @updated="fetchSchedules(schedulesPagination.current_page)"
    />

    <!-- Modal Xem Toàn Văn Bài Viết (Lịch Đăng) -->
    <div v-if="fullContentModalItem" class="modal-overlay inner-modal" @click.self="fullContentModalItem = null">
      <div class="modal-content card inner-content-modal animate-in">
        <div class="modal-header">
          <div>
            <h3>{{ fullContentModalItem.title || fullContentModalItem.title_suggested }}</h3>
            <p class="modal-sub">Ảnh Drive: <code>{{ fullContentModalItem.drive_image_name }}</code> • Thời gian đăng: {{ formatDateTime(fullContentModalItem.scheduled_at) }}</p>
          </div>
          <button class="btn-close" @click="fullContentModalItem = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
          <div class="full-content-view">
            {{ fullContentModalItem.content }}
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline" @click="fullContentModalItem = null">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Modal Xem Ảnh Snapshot To -->
    <div v-if="previewImageModalUrl" class="modal-overlay inner-modal" @click.self="previewImageModalUrl = null">
      <div class="modal-content card image-preview-modal animate-in">
        <div class="modal-header">
          <h3>Ảnh Snapshot Lưu Trữ Trên Máy Chủ</h3>
          <button class="btn-close" @click="previewImageModalUrl = null"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body text-center">
          <img :src="previewImageModalUrl" alt="Snapshot Preview" class="large-preview-img" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";
import PostEditorModal from './components/PostEditorModal.vue';
import PostImportModal from './components/PostImportModal.vue';
import PostChannelConfigModal from './components/PostChannelConfigModal.vue';
import PostScheduleEditModal from './components/PostScheduleEditModal.vue';

const toaster = createToaster({ position: "top-right" });

// Main Tabs State
const activeMainTab = ref('articles'); // 'articles' | 'schedules' | 'batches'

// Schedules Automation States
const schedules = ref([]);
const loadingSchedules = ref(false);
const schedulesPagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});
const schedulesKpi = reactive({
  total: 0,
  scheduled: 0,
  website_published: 0,
  facebook_published: 0,
  failed: 0,
  outcome_unknown: 0,
});
const scheduleFilters = reactive({
  search: '',
  channel: 'all',
  status_website: 'all',
  status_facebook: 'all',
  batch_id: '',
  date_from: '',
  date_to: '',
});

// Batches States
const batches = ref([]);
const loadingBatches = ref(false);
const batchesPagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

// Modal States
const showImportModal = ref(false);
const showChannelConfigModal = ref(false);
const showScheduleEditModal = ref(false);
const targetEditSchedule = ref(null);
const fullContentModalItem = ref(null);
const previewImageModalUrl = ref(null);

// States
const posts = ref([]);
const categories = ref([]);
const fullCategoriesList = ref([]);
const authors = ref([]);
const loading = ref(false);
const submitting = ref(false);
const reviewing = ref(false);

const kpi = reactive({
  total: 0,
  published: 0,
  pending_review: 0,
  draft: 0,
  archived: 0,
  trashed: 0,
  pending_revisions: 0,
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
});

const permissions = reactive({
  can_create: true,
  can_review: true,
  can_publish: true,
  can_delete: true,
});

// Filters
const searchQuery = ref('');
const filterStatus = ref('all');
const filterCategory = ref('all');
const filterAuthor = ref('all');
const filterPendingRevision = ref('');

// Modals State
const showEditorModal = ref(false);
const editingPostId = ref(null);

const showCategoryModal = ref(false);
const newCategoryName = ref('');
const newCategoryDesc = ref('');
const creatingCategory = ref(false);

const showReviewModal = ref(false);
const targetReviewPost = ref(null);
const rejectionReasonInput = ref('');

const showRevisionModal = ref(false);
const targetRevisionPost = ref(null);

// Computed Properties
const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' ||
    filterStatus.value !== 'all' ||
    filterCategory.value !== 'all' ||
    filterAuthor.value !== 'all' ||
    filterPendingRevision.value !== '';
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, pagination.current_page - 2);
  const end = Math.min(pagination.last_page, pagination.current_page + 2);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Methods
const fetchPosts = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: pagination.per_page,
    };
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (filterStatus.value !== 'all') params.status = filterStatus.value;
    if (filterCategory.value !== 'all') params.category_id = filterCategory.value;
    if (filterAuthor.value !== 'all') params.author_id = filterAuthor.value;
    if (filterPendingRevision.value !== '') params.has_pending_revision = filterPendingRevision.value;

    const res = await baseRequest.get('admin/posts', { params });
    if (res.data && res.data.status === 1) {
      posts.value = res.data.data.data || [];
      pagination.current_page = res.data.data.current_page || 1;
      pagination.last_page = res.data.data.last_page || 1;
      pagination.total = res.data.data.total || 0;

      if (res.data.kpi) {
        Object.assign(kpi, res.data.kpi);
      }
      if (res.data.meta) {
        categories.value = res.data.meta.categories || [];
        authors.value = res.data.meta.authors || [];
        if (res.data.meta.user_permissions) {
          Object.assign(permissions, res.data.meta.user_permissions);
        }
      }
    }
  } catch (err) {
    toaster.error('Không thể tải danh sách bài viết!');
  } finally {
    loading.value = false;
  }
};

const fetchCategoriesList = async () => {
  try {
    const res = await baseRequest.get('admin/posts/categories');
    if (res.data && res.data.status === 1) {
      fullCategoriesList.value = res.data.data || [];
    }
  } catch (e) {
    console.error('Lỗi khi tải chuyên mục:', e);
  }
};

let searchDebounce = null;
const onSearchInput = () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    fetchPosts(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchPosts(1);
};

const setStatusFilter = (st) => {
  filterStatus.value = st;
  filterPendingRevision.value = '';
  fetchPosts(1);
};

const togglePendingRevisionFilter = () => {
  if (filterPendingRevision.value === '1') {
    filterPendingRevision.value = '';
  } else {
    filterPendingRevision.value = '1';
    filterStatus.value = 'all';
  }
  fetchPosts(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  filterStatus.value = 'all';
  filterCategory.value = 'all';
  filterAuthor.value = 'all';
  filterPendingRevision.value = '';
  fetchPosts(1);
};

// Editor Methods
const openCreateModal = () => {
  editingPostId.value = null;
  showEditorModal.value = true;
};

const openEditModal = (post) => {
  editingPostId.value = post.id;
  showEditorModal.value = true;
};

const onPostSaved = () => {
  fetchPosts(pagination.current_page);
};


// Category Methods
const openCategoryModal = () => {
  showCategoryModal.value = true;
  fetchCategoriesList();
};

const handleCreateCategory = async () => {
  if (!newCategoryName.value.trim()) return;
  creatingCategory.value = true;
  try {
    const res = await baseRequest.post('admin/posts/categories', {
      name: newCategoryName.value.trim(),
      description: newCategoryDesc.value.trim() || null,
    });
    if (res.data && res.data.status === 1) {
      toaster.success('Thêm chuyên mục thành công!');
      newCategoryName.value = '';
      newCategoryDesc.value = '';
      fetchCategoriesList();
      fetchPosts(1);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể tạo chuyên mục!');
  } finally {
    creatingCategory.value = false;
  }
};

const handleDeleteCategory = async (cat) => {
  if (!confirm(`Bạn có chắc chắn muốn xóa chuyên mục "${cat.name}"?`)) return;
  try {
    const res = await baseRequest.delete(`admin/posts/categories/${cat.id}`);
    if (res.data && res.data.status === 1) {
      toaster.success('Đã xóa chuyên mục!');
      fetchCategoriesList();
      fetchPosts(1);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xóa chuyên mục!');
  }
};

// Review Modal
const openReviewModal = (post) => {
  targetReviewPost.value = post;
  rejectionReasonInput.value = '';
  showReviewModal.value = true;
};

const submitReviewDecision = async (action) => {
  reviewing.value = true;
  try {
    const res = await baseRequest.post(`admin/posts/${targetReviewPost.value.id}/review`, {
      action,
      rejection_reason: rejectionReasonInput.value.trim() || undefined,
    });
    if (res.data && res.data.status === 1) {
      toaster.success(res.data.message || 'Đã cập nhật trạng thái duyệt!');
      showReviewModal.value = false;
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xử lý kiểm duyệt!');
  } finally {
    reviewing.value = false;
  }
};

// Revision Modal
const openRevisionModal = (post) => {
  targetRevisionPost.value = post;
  showRevisionModal.value = true;
};

const submitRevisionDecision = async (action) => {
  reviewing.value = true;
  try {
    const res = await baseRequest.post(`admin/posts/${targetRevisionPost.value.id}/review`, {
      action,
    });
    if (res.data && res.data.status === 1) {
      toaster.success(res.data.message || 'Đã xử lý bản sửa đổi!');
      showRevisionModal.value = false;
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xử lý bản sửa đổi!');
  } finally {
    reviewing.value = false;
  }
};

// Publish / Archive / Trash
const quickPublish = async (post) => {
  if (!confirm(`Xuất bản ngay bài viết "${post.title}"?`)) return;
  try {
    const res = await baseRequest.post(`admin/posts/${post.id}/publish`);
    if (res.data && res.data.status === 1) {
      toaster.success('Đã xuất bản bài viết thành công!');
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xuất bản!');
  }
};

const archivePost = async (post) => {
  if (!confirm(`Chuyển bài viết "${post.title}" vào lưu trữ?`)) return;
  try {
    const res = await baseRequest.post(`admin/posts/${post.id}/archive`);
    if (res.data && res.data.status === 1) {
      toaster.success('Đã chuyển bài viết vào kho lưu trữ!');
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể lưu trữ!');
  }
};

const confirmDelete = async (post) => {
  if (!confirm(`Đưa bài viết "${post.title}" vào thùng rác?`)) return;
  try {
    const res = await baseRequest.delete(`admin/posts/${post.id}`);
    if (res.data && res.data.status === 1) {
      toaster.success('Đã đưa bài viết vào thùng rác!');
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xóa!');
  }
};

const restorePost = async (post) => {
  try {
    const res = await baseRequest.post(`admin/posts/${post.id}/restore`);
    if (res.data && res.data.status === 1) {
      toaster.success('Khôi phục bài viết thành công!');
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể khôi phục!');
  }
};

const confirmForceDelete = async (post) => {
  if (!confirm(`CẢNH BÁO: Bạn có chắc chắn muốn XÓA VĨNH VIỄN bài viết "${post.title}"? Thao tác này không thể hoàn tác.`)) return;
  try {
    const res = await baseRequest.delete(`admin/posts/${post.id}/force`);
    if (res.data && res.data.status === 1) {
      toaster.success('Đã xóa vĩnh viễn bài viết!');
      fetchPosts(pagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể xóa vĩnh viễn!');
  }
};

// Formatting helpers
const formatDateTime = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')} ${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const getStatusClass = (post) => {
  if (post.deleted_at) return 'status-trashed';
  switch (post.status) {
    case 'published': return 'status-published';
    case 'pending_review': return 'status-pending';
    case 'draft': return 'status-draft';
    case 'archived': return 'status-archived';
    default: return '';
  }
};

const getStatusIcon = (post) => {
  if (typeof post === 'string') {
    switch (post) {
      case 'published': return 'fa-circle-check';
      case 'scheduled': return 'fa-clock';
      case 'publishing': return 'fa-spinner fa-spin';
      case 'failed': return 'fa-circle-exclamation';
      case 'outcome_unknown': return 'fa-triangle-exclamation';
      case 'cancelled': return 'fa-ban';
      default: return 'fa-minus';
    }
  }
  if (post?.deleted_at) return 'fa-trash-can';
  switch (post?.status) {
    case 'published': return 'fa-circle-check';
    case 'pending_review': return 'fa-clock';
    case 'draft': return 'fa-file-lines';
    case 'archived': return 'fa-box-archive';
    default: return 'fa-circle-question';
  }
};

const getStatusText = (st) => {
  switch (st) {
    case 'published': return 'Đã xuất bản';
    case 'pending_review': return 'Chờ duyệt';
    case 'draft': return 'Bản nháp';
    case 'archived': return 'Lưu trữ';
    default: return 'Không xác định';
  }
};

// ==========================================
// SCHEDULES & MULTI-CHANNEL AUTOMATION LOGIC
// ==========================================
const switchMainTab = (tab) => {
  activeMainTab.value = tab;
  if (tab === 'schedules') {
    fetchSchedules(1);
  } else if (tab === 'batches') {
    fetchBatches(1);
  } else {
    fetchPosts(pagination.current_page);
  }
};

const onRefreshCurrentView = () => {
  if (activeMainTab.value === 'articles') {
    fetchPosts(pagination.current_page);
  } else if (activeMainTab.value === 'schedules') {
    fetchSchedules(schedulesPagination.current_page);
  } else {
    fetchBatches(batchesPagination.current_page);
  }
};

const downloadTemplate = () => {
  window.open('/api/admin/post-schedules/template', '_blank');
};

let scheduleSearchTimeout = null;
const onScheduleSearchInput = () => {
  clearTimeout(scheduleSearchTimeout);
  scheduleSearchTimeout = setTimeout(() => {
    fetchSchedules(1);
  }, 400);
};

const setScheduleFilter = (type) => {
  if (type === 'all') {
    scheduleFilters.status_website = 'all';
    scheduleFilters.status_facebook = 'all';
  } else if (type === 'scheduled') {
    scheduleFilters.status_website = 'scheduled';
    scheduleFilters.status_facebook = 'all';
  } else if (type === 'website_published') {
    scheduleFilters.status_website = 'published';
    scheduleFilters.status_facebook = 'all';
  } else if (type === 'facebook_published') {
    scheduleFilters.status_website = 'all';
    scheduleFilters.status_facebook = 'published';
  } else if (type === 'failed') {
    scheduleFilters.status_website = 'failed';
    scheduleFilters.status_facebook = 'all';
  } else if (type === 'outcome_unknown') {
    scheduleFilters.status_website = 'all';
    scheduleFilters.status_facebook = 'outcome_unknown';
  }
  fetchSchedules(1);
};

const filterSchedulesByBatch = (batchId) => {
  scheduleFilters.batch_id = batchId;
  activeMainTab.value = 'schedules';
  fetchSchedules(1);
};

const fetchSchedules = async (page = 1) => {
  loadingSchedules.value = true;
  try {
    const params = {
      page,
      per_page: schedulesPagination.per_page,
    };
    if (scheduleFilters.search.trim()) params.search = scheduleFilters.search.trim();
    if (scheduleFilters.channel !== 'all') params.channel = scheduleFilters.channel;
    if (scheduleFilters.status_website !== 'all') params.status_website = scheduleFilters.status_website;
    if (scheduleFilters.status_facebook !== 'all') params.status_facebook = scheduleFilters.status_facebook;
    if (scheduleFilters.batch_id) params.batch_id = scheduleFilters.batch_id;
    if (scheduleFilters.date_from) params.date_from = scheduleFilters.date_from;
    if (scheduleFilters.date_to) params.date_to = scheduleFilters.date_to;

    const res = await baseRequest.get('admin/post-schedules', { params });
    if (res.data?.status === 1 && res.data.data) {
      schedules.value = res.data.data.data || [];
      schedulesPagination.current_page = res.data.data.current_page || 1;
      schedulesPagination.last_page = res.data.data.last_page || 1;
      schedulesPagination.total = res.data.data.total || 0;

      calculateSchedulesKpi();
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể tải danh sách lịch đăng bài.');
  } finally {
    loadingSchedules.value = false;
  }
};

const calculateSchedulesKpi = () => {
  schedulesKpi.total = schedulesPagination.total;
  let scheduled = 0, webPub = 0, fbPub = 0, failed = 0, unknown = 0;
  schedules.value.forEach(s => {
    if (s.status_website === 'scheduled' || s.status_facebook === 'scheduled') scheduled++;
    if (s.status_website === 'published') webPub++;
    if (s.status_facebook === 'published') fbPub++;
    if (s.status_website === 'failed' || s.status_facebook === 'failed') failed++;
    if (s.status_facebook === 'outcome_unknown') unknown++;
  });
  schedulesKpi.scheduled = scheduled;
  schedulesKpi.website_published = webPub;
  schedulesKpi.facebook_published = fbPub;
  schedulesKpi.failed = failed;
  schedulesKpi.outcome_unknown = unknown;
};

const fetchBatches = async (page = 1) => {
  loadingBatches.value = true;
  try {
    const res = await baseRequest.get('admin/post-schedules/batches', {
      params: { page, per_page: batchesPagination.per_page }
    });
    if (res.data?.status === 1 && res.data.data) {
      batches.value = res.data.data.data || [];
      batchesPagination.current_page = res.data.data.current_page || 1;
      batchesPagination.last_page = res.data.data.last_page || 1;
      batchesPagination.total = res.data.data.total || 0;
    }
  } catch (err) {
    toaster.error('Không thể tải danh sách đợt import.');
  } finally {
    loadingBatches.value = false;
  }
};

const isUpcoming = (scheduledAt) => {
  if (!scheduledAt) return false;
  return new Date(scheduledAt) > new Date();
};

const getTimeRemaining = (scheduledAt) => {
  if (!scheduledAt) return '';
  const diffMs = new Date(scheduledAt) - new Date();
  if (diffMs <= 0) return 'Đến giờ';
  const hours = Math.floor(diffMs / (1000 * 60 * 60));
  const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
  if (hours > 24) {
    const days = Math.floor(hours / 24);
    return `Còn ${days} ngày nữa`;
  }
  return `Còn ${hours}h ${mins}p`;
};

const getChannelLabel = (ch) => {
  if (ch === 'website') return 'Website';
  if (ch === 'facebook') return 'Facebook';
  return 'Cả hai (Web & FB)';
};

const getStatusLabel = (st) => {
  switch (st) {
    case 'scheduled': return 'Chờ đến lịch';
    case 'publishing': return 'Đang đăng...';
    case 'published': return 'Đã xuất bản';
    case 'failed': return 'Lỗi';
    case 'outcome_unknown': return 'Cần đối soát';
    case 'cancelled': return 'Đã hủy';
    case 'not_applicable': return 'Không áp dụng';
    default: return st;
  }
};

const getBatchStatusLabel = (st) => {
  switch (st) {
    case 'draft': return 'Bản nháp';
    case 'scheduled': return 'Đã lên lịch';
    case 'completed': return 'Đã hoàn tất';
    case 'partially_failed': return 'Có lỗi';
    case 'cancelled': return 'Đã hủy';
    default: return st;
  }
};

const canEditSchedule = (sc) => {
  return sc.status_website !== 'published' && sc.status_facebook !== 'published';
};

const canPublishNow = (sc) => {
  return sc.status_website === 'scheduled' || sc.status_facebook === 'scheduled' ||
         sc.status_website === 'failed' || sc.status_facebook === 'failed';
};

const canRetrySchedule = (sc) => {
  return sc.status_website === 'failed' || sc.status_facebook === 'failed' || sc.status_facebook === 'outcome_unknown';
};

const canCancelSchedule = (sc) => {
  return sc.status_website !== 'published' && sc.status_facebook !== 'published' &&
         sc.status_website !== 'cancelled' && sc.status_facebook !== 'cancelled';
};

const openEditSchedule = (sc) => {
  targetEditSchedule.value = sc;
  showScheduleEditModal.value = true;
};

const handlePublishNow = async (sc) => {
  if (!confirm(`Bạn có chắc muốn đăng bài viết "${sc.title || sc.title_suggested}" ngay lập tức?`)) return;
  try {
    const res = await baseRequest.post(`admin/post-schedules/${sc.id}/publish-now`);
    if (res.data?.status === 1) {
      toaster.success('Đã xuất bản bài viết thành công!');
      fetchSchedules(schedulesPagination.current_page);
    } else {
      toaster.error(res.data?.message || 'Xuất bản thất bại.');
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Lỗi khi đăng bài.');
  }
};

const handleRetrySchedule = async (sc) => {
  try {
    const res = await baseRequest.post(`admin/post-schedules/${sc.id}/retry`);
    if (res.data?.status === 1) {
      toaster.success('Đã đưa bài viết trở lại hàng đợi xuất bản.');
      fetchSchedules(schedulesPagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể thử lại.');
  }
};

const handleCancelSchedule = async (sc) => {
  if (!confirm(`Hủy lịch đăng của bài viết này?`)) return;
  try {
    const res = await baseRequest.post(`admin/post-schedules/${sc.id}/cancel`);
    if (res.data?.status === 1) {
      toaster.success('Đã hủy lịch đăng thành công.');
      fetchSchedules(schedulesPagination.current_page);
    }
  } catch (err) {
    toaster.error(err.response?.data?.message || 'Không thể hủy lịch.');
  }
};

const openFullContent = (sc) => {
  fullContentModalItem.value = sc;
};

const openImagePreview = (url) => {
  if (url) previewImageModalUrl.value = url;
};

const onImportCompleted = () => {
  fetchSchedules(1);
  fetchBatches(1);
};

const onChannelConfigUpdated = () => {
  fetchSchedules(schedulesPagination.current_page);
};

onMounted(() => {
  fetchPosts(1);
  // Prefetch count for schedules tab
  fetchSchedules(1);
});
</script>

<style scoped>
.admin-page {
  padding: 24px;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.page-title span {
  color: #0284c7;
}

.page-subtitle {
  font-size: 0.85rem;
  color: #64748b;
  margin: 4px 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-outline-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline-action:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover {
  background: #f8fafc;
  color: #0f172a;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: white;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
  transition: all 0.2s;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35);
}

/* KPI Summary Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}

.kpi-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.kpi-card:hover {
  border-color: #0284c7;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.kpi-card.active {
  border-color: #0284c7;
  background: #f0f9ff;
  box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
}

.kpi-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.kpi-icon.total { background: #f1f5f9; color: #475569; }
.kpi-icon.published { background: #ecfdf5; color: #059669; }
.kpi-icon.pending { background: #fffbeb; color: #d97706; }
.kpi-icon.draft { background: #f8fafc; color: #64748b; }
.kpi-icon.revision { background: #ede9fe; color: #7c3aed; }
.kpi-icon.archived { background: #f3f4f6; color: #6b7280; }
.kpi-icon.trashed { background: #fef2f2; color: #dc2626; }

.kpi-info {
  display: flex;
  flex-direction: column;
}

.kpi-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.kpi-val {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.text-success { color: #059669; }
.text-warning { color: #d97706; }
.text-secondary { color: #64748b; }
.text-info { color: #7c3aed; }
.text-danger { color: #dc2626; }

/* Content Card */
.content-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  position: relative;
}

/* Toolbar */
.table-tools {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  background: #fafafa;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 280px;
}

.search-wrap i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 0.88rem;
}

.search-wrap input {
  width: 100%;
  padding: 9px 36px 9px 38px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.86rem;
  outline: none;
  background: white;
  transition: all 0.2s;
}

.search-wrap input:focus {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

.btn-clear-search {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}

.filters-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.form-select {
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.84rem;
  background: white;
  color: #334155;
  outline: none;
}

.form-select:focus {
  border-color: #0284c7;
}

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-reset:hover {
  background: #f1f5f9;
  color: #0f172a;
}

/* Premium Table */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.premium-table th {
  padding: 12px 16px;
  font-size: 0.74rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.86rem;
  vertical-align: middle;
}

.premium-table tr:hover {
  background: #f8fafc;
}

.row-trashed {
  background: #fff5f5;
  opacity: 0.85;
}

.id-tag {
  font-family: monospace;
  font-weight: 700;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.post-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.post-thumb {
  width: 64px;
  height: 44px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #f1f5f9;
  flex-shrink: 0;
}

.post-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.post-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 280px;
}

.post-meta-sub {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.74rem;
  color: #64748b;
}

.dot { color: #cbd5e1; }

.slug-tag {
  color: #0284c7;
  max-width: 140px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.category-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #e0f2fe;
  color: #0369a1;
}

.author-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #334155;
  font-size: 0.76rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #334155;
}

.status-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.74rem;
  font-weight: 700;
  width: fit-content;
}

.status-published { background: #ecfdf5; color: #047857; }
.status-pending { background: #fffbeb; color: #b45309; }
.status-draft { background: #f1f5f9; color: #475569; }
.status-archived { background: #f3f4f6; color: #6b7280; }
.status-trashed { background: #fef2f2; color: #dc2626; }

.pill-revision-alert {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.68rem;
  font-weight: 700;
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #ddd6fe;
  cursor: pointer;
  transition: all 0.2s;
  width: fit-content;
}

.pill-revision-alert:hover {
  background: #ddd6fe;
}

.date-cell {
  font-size: 0.78rem;
  color: #475569;
}

.views-badge {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.action-buttons-wrap {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.btn-act {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-act.view { color: #0284c7; }
.btn-act.view:hover { background: #e0f2fe; border-color: #bae6fd; }

.btn-act.edit { color: #3b82f6; }
.btn-act.edit:hover { background: #eff6ff; border-color: #bfdbfe; }

.btn-act.review { color: #d97706; }
.btn-act.review:hover { background: #fef3c7; border-color: #fde68a; }

.btn-act.publish { color: #10b981; }
.btn-act.publish:hover { background: #ecfdf5; border-color: #a7f3d0; }

.btn-act.archive { color: #6b7280; }
.btn-act.archive:hover { background: #f3f4f6; border-color: #e5e7eb; }

.btn-act.delete { color: #ef4444; }
.btn-act.delete:hover { background: #fef2f2; border-color: #fecaca; }

.btn-act.restore { color: #059669; }
.btn-act.restore:hover { background: #ecfdf5; border-color: #a7f3d0; }

.btn-act.force-delete { color: #b91c1c; }
.btn-act.force-delete:hover { background: #fee2e2; border-color: #fca5a5; }

/* Empty state */
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #64748b;
}

.empty-state i {
  font-size: 2.5rem;
  color: #cbd5e1;
  margin-bottom: 12px;
}

.empty-state h4 {
  font-size: 1.1rem;
  color: #1e293b;
  margin: 0 0 6px;
}

/* Pagination Footer */
.table-pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  background: #fafafa;
}

.pagination-info {
  font-size: 0.82rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-page {
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #334155;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-page.active {
  background: #0284c7;
  color: white;
  border-color: #0284c7;
}

.btn-page:hover:not(:disabled):not(.active) {
  background: #f1f5f9;
}

/* Loading Overlay */
.loading-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #0284c7;
  z-index: 10;
}

/* ==========================================
   MODAL STYLES
   ========================================== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.editor-modal {
  max-width: 1100px;
  max-height: 90vh;
}

.category-modal {
  max-width: 700px;
}

.review-modal {
  max-width: 550px;
}

.diff-modal {
  max-width: 1000px;
  max-height: 85vh;
}

.modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #e0f2fe;
  color: #0284c7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.header-icon-badge.warning { background: #fef3c7; color: #d97706; }
.header-icon-badge.info { background: #ede9fe; color: #7c3aed; }

.header-title-wrap {
  flex: 1;
}

.header-title-wrap h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin: 2px 0 0;
}

.btn-close-modal {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
}

.btn-close-modal:hover { color: #0f172a; }

.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
}

.modal-footer {
  padding: 14px 24px;
  border-top: 1px solid #e2e8f0;
  background: #fafafa;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-actions-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Editor Form */
.revision-warning-banner {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.banner-icon {
  font-size: 1.4rem;
  color: #d97706;
}

.banner-content strong {
  display: block;
  font-size: 0.86rem;
  color: #92400e;
}

.banner-content p {
  font-size: 0.8rem;
  color: #b45309;
  margin: 4px 0 0;
}

.form-grid-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 20px;
}

@media (max-width: 900px) {
  .form-grid-layout {
    grid-template-columns: 1fr;
  }
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 6px;
}

.form-label.required::after {
  content: " *";
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.86rem;
  outline: none;
  background: white;
}

.form-input:focus {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

.title-input {
  font-size: 1rem;
  font-weight: 700;
}

.slug-input-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  background: #f8fafc;
}

.slug-prefix {
  padding: 0 10px;
  font-size: 0.82rem;
  color: #64748b;
  font-family: monospace;
}

.slug-field {
  border: none;
  border-left: 1px solid #e2e8f0;
  border-radius: 0;
  background: white;
}

.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.86rem;
  outline: none;
  font-family: inherit;
  resize: vertical;
}

.form-textarea:focus {
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

.content-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.format-buttons {
  display: flex;
  gap: 4px;
}

.btn-fmt {
  padding: 4px 8px;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  background: white;
  font-size: 0.78rem;
  cursor: pointer;
  color: #475569;
}

.btn-fmt:hover {
  background: #f1f5f9;
}

.side-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 16px;
}

.side-card-title {
  font-size: 0.88rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.image-input-wrap {
  display: flex;
  gap: 6px;
}

.btn-browse-media {
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: white;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.featured-image-preview {
  margin-top: 10px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  max-height: 160px;
}

.featured-image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.form-hint {
  display: block;
  font-size: 0.74rem;
  color: #64748b;
  margin-top: 4px;
}

.btn-outline {
  padding: 8px 18px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-weight: 600;
  font-size: 0.86rem;
  cursor: pointer;
}

.btn-secondary {
  padding: 8px 18px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #f1f5f9;
  color: #334155;
  font-weight: 600;
  font-size: 0.86rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-warning-action {
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  background: #f59e0b;
  color: white;
  font-weight: 700;
  font-size: 0.86rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-danger-action {
  padding: 8px 18px;
  border-radius: 8px;
  border: none;
  background: #ef4444;
  color: white;
  font-weight: 700;
  font-size: 0.86rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Category Add Bar */
.category-add-bar {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 16px;
}

.btn-primary-sm {
  padding: 8px 14px;
  border-radius: 8px;
  border: none;
  background: #0284c7;
  color: white;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.cat-table-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.sub-table {
  width: 100%;
  border-collapse: collapse;
}

.sub-table th {
  background: #f8fafc;
  padding: 10px 14px;
  font-size: 0.74rem;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.sub-table td {
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.82rem;
}

.badge-count {
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.74rem;
}

/* Diff Modal */
.diff-meta-bar {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  border-radius: 8px;
  padding: 8px 14px;
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
  color: #5b21b6;
  margin-bottom: 16px;
}

.diff-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.diff-col {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.diff-col.current .diff-col-head {
  background: #f1f5f9;
  color: #334155;
}

.diff-col.pending .diff-col-head {
  background: #fef3c7;
  color: #92400e;
}

.diff-col-head {
  padding: 10px 14px;
  font-size: 0.84rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.diff-box {
  padding: 14px;
  max-height: 400px;
  overflow-y: auto;
}

.diff-box h4 {
  margin: 0 0 8px;
  font-size: 1rem;
  color: #0f172a;
}

.diff-summary {
  font-size: 0.84rem;
  color: #64748b;
  font-style: italic;
  margin-bottom: 12px;
}

.diff-content-text {
  font-size: 0.84rem;
  color: #334155;
  white-space: pre-wrap;
  line-height: 1.5;
}

/* ========================================================
   MAIN TABS BAR & AUTOMATION SCHEDULER DESIGN SYSTEM
   ======================================================== */
.main-tabs-bar {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  background: var(--bg-surface-alt);
  padding: 6px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.main-tab-btn {
  padding: 10px 18px;
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--color-text-muted);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.main-tab-btn:hover {
  color: var(--color-text-main);
  background: rgba(0, 0, 0, 0.03);
}

.main-tab-btn.active {
  background: var(--bg-surface);
  color: var(--color-primary);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tab-count-badge {
  background: var(--color-primary);
  color: #fff;
  font-size: 0.72rem;
  padding: 2px 7px;
  border-radius: 10px;
  font-weight: 700;
}

.btn-emerald {
  background: #059669 !important;
  color: #fff !important;
  border-color: #047857 !important;
}

.btn-emerald:hover {
  background: #047857 !important;
}

.text-emerald {
  color: #059669 !important;
}

/* Schedule Table Cells */
.schedule-thumb-box {
  position: relative;
  width: 76px;
  height: 50px;
  border-radius: 4px;
  overflow: hidden;
  background: #0f172a;
  cursor: pointer;
}

.schedule-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s ease;
}

.schedule-thumb-box:hover .schedule-thumb-img {
  transform: scale(1.08);
}

.thumb-drive-tag {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(15, 23, 42, 0.75);
  color: #fff;
  font-size: 0.65rem;
  padding: 1px 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
}

.schedule-title-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.schedule-title {
  font-weight: 700;
  font-size: 0.88rem;
  color: var(--color-text-main);
  line-height: 1.35;
}

.batch-link-badge {
  align-self: flex-start;
  font-size: 0.7rem;
  font-family: monospace;
  background: #EEF2FF;
  color: #4F46E5;
  padding: 2px 6px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.batch-link-badge:hover {
  background: #E0E7FF;
}

.schedule-content-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.content-preview-line {
  margin: 0;
  font-size: 0.82rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-read-more {
  background: none;
  border: none;
  color: var(--color-primary);
  font-size: 0.74rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  text-align: left;
  text-decoration: underline;
}

.schedule-time-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.time-main {
  font-size: 0.84rem;
  font-weight: 700;
}

.time-countdown {
  font-size: 0.72rem;
  font-weight: 600;
}

/* Badges */
.badge-channel {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 4px;
  display: inline-block;
  white-space: nowrap;
}

.badge-channel.channel-website {
  background: #EEF2FF;
  color: #4338CA;
}

.badge-channel.channel-facebook {
  background: #EFF6FF;
  color: #1D4ED8;
}

.badge-channel.channel-both {
  background: #ECFDF5;
  color: #065F46;
}

.status-cell-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-start;
}

.badge-status-pill {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}

.badge-status-pill.status-scheduled {
  background: #FFFBEB;
  color: #B45309;
}

.badge-status-pill.status-publishing {
  background: #EFF6FF;
  color: #1D4ED8;
}

.badge-status-pill.status-published {
  background: #ECFDF5;
  color: #065F46;
}

.badge-status-pill.status-failed {
  background: #FEF2F2;
  color: #B91C1C;
}

.badge-status-pill.status-outcome_unknown {
  background: #FFF7ED;
  color: #C2410C;
}

.badge-status-pill.status-cancelled {
  background: #F1F5F9;
  color: #64748B;
}

.badge-status-pill.status-not_applicable {
  background: #F8FAFC;
  color: #94A3B8;
}

.post-live-link {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.post-live-link:hover {
  text-decoration: underline;
}

.post-live-link.fb-link {
  color: #1877F2;
}

.error-inline {
  font-size: 0.7rem;
  line-height: 1.25;
  max-width: 140px;
  display: block;
}

.action-btns-group {
  display: flex;
  gap: 6px;
  justify-content: center;
}

.btn-act.publish {
  background: #ECFDF5;
  color: #059669;
}

.btn-act.publish:hover {
  background: #059669;
  color: #fff;
}

.btn-act.retry {
  background: #EFF6FF;
  color: #2563EB;
}

.btn-act.retry:hover {
  background: #2563EB;
  color: #fff;
}

.btn-clear-filters {
  padding: 6px 12px;
  background: #FEF2F2;
  border: 1px solid #FECACA;
  color: #DC2626;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

/* Batch Table Styles */
.batch-name-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.badge-batch-status {
  font-size: 0.74rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 4px;
}

.badge-batch-status.bstatus-scheduled {
  background: #FFFBEB;
  color: #B45309;
}

.badge-batch-status.bstatus-completed {
  background: #ECFDF5;
  color: #065F46;
}

.badge-batch-status.bstatus-partially_failed {
  background: #FEF2F2;
  color: #B91C1C;
}

.badge-batch-status.bstatus-draft {
  background: #F1F5F9;
  color: #64748B;
}

.badge-batch-status.bstatus-cancelled {
  background: #F1F5F9;
  color: #94A3B8;
}

.image-preview-modal {
  max-width: 800px;
}

.large-preview-img {
  max-width: 100%;
  max-height: 70vh;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
