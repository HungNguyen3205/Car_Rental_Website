<template>
  <div class="admin-page">
    <!-- Page Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div>
          <h1 class="page-title">Nhật Ký <span>Hoạt Động & Kiểm Toán</span></h1>
          <p class="page-subtitle">Truy vết toàn diện lịch sử thao tác, thay đổi dữ liệu đơn hàng, xe, khách hàng, bài viết và cấu hình hệ thống.</p>
        </div>
      </div>
      <div class="header-actions">
        <span class="total-badge" v-if="!loading">
          <i class="fa-solid fa-database"></i> {{ pagination.total.toLocaleString('vi-VN') }} bản ghi
        </span>
        <button class="btn-refresh" @click="fetchLogs" :disabled="loading" title="Tải lại dữ liệu">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
          <span>Làm mới</span>
        </button>
      </div>
    </header>

    <!-- Quick Stats Bar -->
    <div class="quick-stats-bar">
      <div class="stat-pill" :class="{ 'active': filterActorType === '' }" @click="setActorFilter('')">
        <span class="stat-dot all"></span>
        <span class="stat-name">Tất cả tác vụ</span>
      </div>
      <div class="stat-pill" :class="{ 'active': filterActorType === 'admin' }" @click="setActorFilter('admin')">
        <span class="stat-dot admin"></span>
        <span class="stat-name">Quản trị viên (Admin)</span>
      </div>
      <div class="stat-pill" :class="{ 'active': filterActorType === 'user' }" @click="setActorFilter('user')">
        <span class="stat-dot user"></span>
        <span class="stat-name">Khách hàng (User)</span>
      </div>
      <div class="stat-pill" :class="{ 'active': filterActorType === 'system' }" @click="setActorFilter('system')">
        <span class="stat-dot system"></span>
        <span class="stat-name">Hệ thống (System)</span>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="filter-toolbar card">
      <div class="toolbar-grid">
        <!-- Search Keyword -->
        <div class="filter-field search-field">
          <label for="audit-search"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</label>
          <div class="search-input-wrap">
            <input 
              id="audit-search"
              type="text" 
              v-model="searchTerm" 
              placeholder="Tìm theo người thao tác, email, đối tượng, mã..."
              @input="onSearchInput"
            />
            <button v-if="searchTerm" class="btn-clear-search" @click="clearSearch" title="Xóa tìm kiếm">
              &times;
            </button>
          </div>
        </div>

        <!-- Filter Action -->
        <div class="filter-field">
          <label for="filter-action"><i class="fa-solid fa-bolt"></i> Hành động</label>
          <select id="filter-action" v-model="filterAction" @change="applyFilters">
            <option value="">-- Tất cả hành động --</option>
            <optgroup label="Đơn đặt xe & Bàn giao">
              <option value="booking.created">Tạo đơn đặt xe (booking.created)</option>
              <option value="booking.status_updated">Cập nhật trạng thái đơn (booking.status_updated)</option>
              <option value="booking.payment_confirmed">Xác nhận thanh toán (booking.payment_confirmed)</option>
              <option value="booking.cancelled">Hủy đơn đặt xe (booking.cancelled)</option>
              <option value="booking.handover_created">Tạo biên bản bàn giao (booking.handover_created)</option>
              <option value="booking.handover_completed">Hoàn tất bàn giao (booking.handover_completed)</option>
            </optgroup>
            <optgroup label="Phương tiện & Danh mục">
              <option value="car.created">Thêm xe mới (car.created)</option>
              <option value="car.updated">Sửa thông tin xe (car.updated)</option>
              <option value="car.publication_status_updated">Trạng thái xuất bản (car.publication_status_updated)</option>
              <option value="car.maintenance_toggled">Bảo dưỡng xe (car.maintenance_toggled)</option>
              <option value="car.soft_deleted">Xóa tạm xe (car.soft_deleted)</option>
              <option value="car.restored">Khôi phục xe (car.restored)</option>
              <option value="car_category.created">Tạo danh mục xe (car_category.created)</option>
              <option value="car_category.updated">Sửa danh mục xe (car_category.updated)</option>
              <option value="car_category.status_toggled">Kích hoạt danh mục (car_category.status_toggled)</option>
              <option value="car_category.deleted">Xóa danh mục xe (car_category.deleted)</option>
            </optgroup>
            <optgroup label="Khách hàng & CRM">
              <option value="customer.profile_updated">Sửa hồ sơ khách (customer.profile_updated)</option>
              <option value="customer.note_added">Ghi chú chăm sóc (customer.note_added)</option>
              <option value="customer.status_toggled">Khóa/mở khách (customer.status_toggled)</option>
              <option value="contact.status_updated">Xử lý liên hệ (contact.status_updated)</option>
              <option value="contact.assigned">Phân công liên hệ (contact.assigned)</option>
              <option value="appointment.created">Tạo lịch hẹn (appointment.created)</option>
              <option value="appointment.status_updated">Cập nhật lịch hẹn (appointment.status_updated)</option>
              <option value="appointment.cancelled">Hủy lịch hẹn (appointment.cancelled)</option>
            </optgroup>
            <optgroup label="Cấu hình & Nội dung">
              <option value="settings.bank_updated">Đổi ngân hàng VietQR (settings.bank_updated)</option>
              <option value="settings.updated">Đổi cấu hình hệ thống (settings.updated)</option>
              <option value="content.draft_saved">Lưu nháp CMS (content.draft_saved)</option>
              <option value="content.published">Xuất bản CMS (content.published)</option>
              <option value="post.created">Tạo bài viết (post.created)</option>
              <option value="post.updated">Sửa bài viết (post.updated)</option>
              <option value="post.published">Xuất bản bài viết (post.published)</option>
              <option value="media.uploaded">Tải lên Media (media.uploaded)</option>
            </optgroup>
          </select>
        </div>

        <!-- Filter Target Type -->
        <div class="filter-field">
          <label for="filter-target"><i class="fa-solid fa-shapes"></i> Loại đối tượng</label>
          <select id="filter-target" v-model="filterTargetType" @change="applyFilters">
            <option value="">-- Tất cả đối tượng --</option>
            <option value="Booking">Đơn đặt xe (Booking)</option>
            <option value="Car">Phương tiện xe (Car)</option>
            <option value="CarCategory">Danh mục xe (CarCategory)</option>
            <option value="User">Khách hàng (User)</option>
            <option value="Admin">Quản trị viên (Admin)</option>
            <option value="Contact">Liên hệ hỗ trợ (Contact)</option>
            <option value="Appointment">Lịch hẹn tư vấn (Appointment)</option>
            <option value="SystemSetting">Cấu hình hệ thống (SystemSetting)</option>
            <option value="Post">Bài viết (Post)</option>
            <option value="PostCategory">Chuyên mục bài viết (PostCategory)</option>
            <option value="WebsiteContent">Nội dung CMS (WebsiteContent)</option>
            <option value="MediaAsset">Thư viện media (MediaAsset)</option>
          </select>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-field date-range-field">
          <label><i class="fa-solid fa-calendar-days"></i> Khoảng thời gian</label>
          <div class="date-inputs">
            <input 
              type="date" 
              v-model="filterDateFrom" 
              @change="applyFilters" 
              placeholder="Từ ngày"
              title="Từ ngày"
            />
            <span class="date-sep">đến</span>
            <input 
              type="date" 
              v-model="filterDateTo" 
              @change="applyFilters" 
              placeholder="Đến ngày"
              title="Đến ngày"
            />
          </div>
        </div>
      </div>

      <!-- Date Presets & Reset Toolbar Footer -->
      <div class="toolbar-footer">
        <div class="preset-buttons">
          <span class="preset-label">Xem nhanh:</span>
          <button type="button" class="btn-preset" @click="setDatePreset('today')">Hôm nay</button>
          <button type="button" class="btn-preset" @click="setDatePreset('7days')">7 ngày qua</button>
          <button type="button" class="btn-preset" @click="setDatePreset('30days')">30 ngày qua</button>
          <button type="button" class="btn-preset" @click="setDatePreset('all')">Tất cả</button>
        </div>
        <div class="footer-actions">
          <span v-if="hasActiveFilters" class="active-filter-indicator">
            <i class="fa-solid fa-filter"></i> Đang lọc dữ liệu
          </span>
          <button v-if="hasActiveFilters" type="button" class="btn-reset" @click="resetFilters">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Error State Banner -->
    <div v-if="error" class="alert alert-danger my-4 p-3 d-flex align-items-center justify-content-between" style="border-radius: 8px;">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
        <span>{{ error }}</span>
      </div>
      <button class="btn btn-sm btn-outline-danger" @click="fetchLogs">Thử lại</button>
    </div>

    <!-- Logs Table Card -->
    <div class="table-card card mt-4">
      <div class="table-responsive">
        <table class="audit-table">
          <thead>
            <tr>
              <th style="width: 80px;">Mã Log</th>
              <th style="width: 170px;">Thời gian</th>
              <th style="width: 220px;">Người thao tác</th>
              <th>Hành động thực hiện</th>
              <th>Đối tượng tác động</th>
              <th style="width: 110px;" class="text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading Skeleton -->
            <tr v-if="loading" v-for="i in 5" :key="'skel-' + i" class="skeleton-row">
              <td colspan="6">
                <div class="skeleton-line"></div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="logs.length === 0">
              <td colspan="6" class="empty-cell">
                <div class="empty-state">
                  <div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                  <h4>Không tìm thấy nhật ký hoạt động nào</h4>
                  <p>Không có dữ liệu phù hợp với bộ lọc tìm kiếm hiện tại.</p>
                  <button v-if="hasActiveFilters" class="btn-reset-empty" @click="resetFilters">
                    <i class="fa-solid fa-rotate-left"></i> Đặt lại tất cả bộ lọc
                  </button>
                </div>
              </td>
            </tr>

            <!-- Log Rows -->
            <tr v-else v-for="log in logs" :key="log.id" :class="{ 'highlighted-row': selectedLog && selectedLog.id === log.id }">
              <!-- ID -->
              <td>
                <span class="log-id">#{{ log.id }}</span>
              </td>

              <!-- Timestamp -->
              <td>
                <div class="time-wrap">
                  <div class="time-main"><i class="fa-regular fa-clock"></i> {{ formatDateTime(log.created_at) }}</div>
                  <small class="time-ago">{{ formatRelativeTime(log.created_at) }}</small>
                </div>
              </td>

              <!-- Actor -->
              <td>
                <div class="actor-wrap">
                  <div class="actor-header">
                    <span class="actor-badge" :class="log.actor_type">
                      <i :class="getActorIcon(log.actor_type)"></i>
                      {{ getActorTypeLabel(log.actor_type) }}
                    </span>
                    <span v-if="log.ip_address" class="ip-tag" :title="'IP: ' + log.ip_address">
                      {{ log.ip_address }}
                    </span>
                  </div>
                  <div class="actor-name">{{ log.actor_name || 'Hệ thống' }}</div>
                  <small v-if="log.actor_email" class="actor-email">{{ log.actor_email }}</small>
                </div>
              </td>

              <!-- Action -->
              <td>
                <div class="action-cell">
                  <div class="action-friendly" :class="getActionTheme(log.action)">
                    <i :class="getActionIcon(log.action)"></i>
                    <span>{{ getActionLabel(log.action) }}</span>
                  </div>
                  <code class="action-raw-code" title="Mã hành động gốc">{{ log.action }}</code>
                </div>
              </td>

              <!-- Target -->
              <td>
                <div class="target-cell">
                  <div class="target-type-badge">
                    {{ getTargetTypeLabel(log.target_type) }}
                    <span v-if="log.target_id" class="target-id">#{{ log.target_id }}</span>
                  </div>
                  <div class="target-title">
                    <!-- Clickable Link if target is routeable -->
                    <router-link 
                      v-if="getTargetRoute(log)" 
                      :to="getTargetRoute(log)" 
                      class="target-link"
                      title="Chuyển đến chi tiết đối tượng"
                    >
                      <i class="fa-solid fa-arrow-up-right-from-square"></i>
                      <span>{{ log.target_label || ('Bản ghi #' + log.target_id) }}</span>
                    </router-link>
                    <span v-else class="target-label-text">
                      {{ log.target_label || (log.target_id ? '#' + log.target_id : 'Hệ thống') }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Actions (Details Drawer Trigger) -->
              <td class="text-center">
                <button 
                  type="button" 
                  class="btn-view-details" 
                  @click="openDetailDrawer(log)"
                  title="Xem biến động dữ liệu chi tiết"
                >
                  <i class="fa-solid fa-eye"></i>
                  <span>Chi tiết</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="!loading && logs.length > 0" class="pagination-footer">
        <div class="pagination-info">
          Hiển thị <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</strong> - 
          <strong>{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong> 
          trên tổng số <strong>{{ pagination.total.toLocaleString('vi-VN') }}</strong> bản ghi
        </div>

        <div class="pagination-controls">
          <div class="per-page-select">
            <span>Hiển thị:</span>
            <select v-model="perPage" @change="onPerPageChange">
              <option :value="15">15</option>
              <option :value="30">30</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="page-buttons">
            <button 
              type="button" 
              class="btn-page" 
              :disabled="pagination.current_page <= 1"
              @click="goToPage(pagination.current_page - 1)"
            >
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="page-current">Trang {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button 
              type="button" 
              class="btn-page" 
              :disabled="pagination.current_page >= pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
            >
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Slide-Over Drawer -->
    <transition name="drawer-fade">
      <div v-if="showDrawer && selectedLog" class="drawer-backdrop" @click.self="closeDetailDrawer">
        <div class="drawer-panel" role="dialog" aria-labelledby="drawer-title">
          <!-- Drawer Header -->
          <div class="drawer-header">
            <div class="drawer-title-group">
              <div class="drawer-badge">
                <i class="fa-solid fa-shield-halved"></i> Audit Log #{{ selectedLog.id }}
              </div>
              <h3 id="drawer-title" class="drawer-title">{{ getActionLabel(selectedLog.action) }}</h3>
              <p class="drawer-subtitle">
                <i class="fa-regular fa-clock"></i> {{ formatDateTime(selectedLog.created_at) }}
                <span class="sep">•</span>
                <code class="raw-code-pill">{{ selectedLog.action }}</code>
              </p>
            </div>
            <button type="button" class="btn-drawer-close" @click="closeDetailDrawer" aria-label="Đóng chi tiết">
              &times;
            </button>
          </div>

          <!-- Drawer Body -->
          <div class="drawer-body">
            <!-- Event Metadata Grid -->
            <div class="metadata-grid card p-3 mb-4">
              <div class="meta-item">
                <span class="meta-label">Người thao tác (Actor)</span>
                <div class="meta-val actor-meta">
                  <span class="actor-badge" :class="selectedLog.actor_type">
                    {{ getActorTypeLabel(selectedLog.actor_type) }}
                  </span>
                  <strong>{{ selectedLog.actor_name || 'Hệ thống' }}</strong>
                </div>
                <small v-if="selectedLog.actor_email" class="text-muted">{{ selectedLog.actor_email }}</small>
              </div>

              <div class="meta-item">
                <span class="meta-label">Đối tượng tác động (Target)</span>
                <div class="meta-val">
                  <span class="target-type-badge">{{ getTargetTypeLabel(selectedLog.target_type) }}</span>
                  <span class="ms-1" v-if="selectedLog.target_id">#{{ selectedLog.target_id }}</span>
                </div>
                <div class="target-link-wrap mt-1">
                  <router-link 
                    v-if="getTargetRoute(selectedLog)" 
                    :to="getTargetRoute(selectedLog)" 
                    class="btn-target-direct"
                    @click="closeDetailDrawer"
                  >
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>{{ selectedLog.target_label || 'Xem đối tượng' }}</span>
                  </router-link>
                  <span v-else class="text-muted small">
                    {{ selectedLog.target_label || 'Đối tượng không hỗ trợ liên kết trực tiếp' }}
                  </span>
                </div>
              </div>

              <div class="meta-item">
                <span class="meta-label">Địa chỉ IP</span>
                <div class="meta-val font-mono">{{ selectedLog.ip_address || '127.0.0.1' }}</div>
              </div>

              <div class="meta-item">
                <span class="meta-label">Thiết bị & Trình duyệt (User Agent)</span>
                <div class="meta-val text-wrap small text-muted font-mono" style="word-break: break-all;">
                  {{ selectedLog.user_agent || 'Không ghi nhận' }}
                </div>
              </div>
            </div>

            <!-- Changes Diff Section -->
            <div class="changes-section">
              <div class="changes-header">
                <h4><i class="fa-solid fa-code-compare"></i> Biến Động Dữ Liệu Chi Tiết</h4>
                <span class="changes-status-badge" :class="getPayloadTypeClass(selectedLog)">
                  {{ getPayloadTypeLabel(selectedLog) }}
                </span>
              </div>

              <!-- Case 1: Both Old & New Values Exist (Update / Modification) -->
              <div v-if="hasDiffChanges(selectedLog)" class="diff-card">
                <div class="diff-table-responsive">
                  <table class="diff-table">
                    <thead>
                      <tr>
                        <th style="width: 25%;">Trường dữ liệu</th>
                        <th style="width: 37.5%;">Giá trị ban đầu (Trước)</th>
                        <th style="width: 37.5%;">Giá trị mới (Sau)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="row in computeDiffRows(selectedLog)" 
                        :key="row.field"
                        :class="{ 'field-changed': row.isChanged }"
                      >
                        <td class="field-col">
                          <code>{{ row.field }}</code>
                          <span v-if="row.isChanged" class="change-indicator" title="Giá trị có thay đổi">
                            <i class="fa-solid fa-pen"></i>
                          </span>
                        </td>
                        <td class="val-col old-val">
                          <pre class="json-cell">{{ formatDiffValue(row.oldVal) }}</pre>
                        </td>
                        <td class="val-col new-val">
                          <pre class="json-cell">{{ formatDiffValue(row.newVal) }}</pre>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Case 2: Only New Values (Creation Event) -->
              <div v-else-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0" class="payload-card">
                <div class="payload-card-header text-success">
                  <i class="fa-solid fa-circle-plus"></i> Dữ liệu khởi tạo mới
                </div>
                <div class="payload-card-body">
                  <table class="diff-table">
                    <thead>
                      <tr>
                        <th style="width: 30%;">Trường</th>
                        <th>Giá trị thiết lập</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(val, key) in selectedLog.new_values" :key="key">
                        <td class="field-col"><code>{{ key }}</code></td>
                        <td class="val-col new-val"><pre class="json-cell">{{ formatDiffValue(val) }}</pre></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Case 3: Only Old Values (Deletion Event) -->
              <div v-else-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0" class="payload-card">
                <div class="payload-card-header text-danger">
                  <i class="fa-solid fa-circle-minus"></i> Dữ liệu bị xóa
                </div>
                <div class="payload-card-body">
                  <table class="diff-table">
                    <thead>
                      <tr>
                        <th style="width: 30%;">Trường</th>
                        <th>Giá trị trước khi xóa</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(val, key) in selectedLog.old_values" :key="key">
                        <td class="field-col"><code>{{ key }}</code></td>
                        <td class="val-col old-val"><pre class="json-cell">{{ formatDiffValue(val) }}</pre></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Case 4: No payload stored (Clear indication, no guessing) -->
              <div v-else class="no-payload-notice">
                <i class="fa-solid fa-circle-info"></i>
                <div class="notice-content">
                  <strong>Không có dữ liệu thay đổi chi tiết</strong>
                  <p>Hành động này được hệ thống ghi nhận dưới dạng sự kiện mốc (Event checkpoint) và không đính kèm payload so sánh trước/sau.</p>
                </div>
              </div>
            </div>

            <!-- Immutability Security Notice -->
            <div class="immutability-notice mt-4">
              <i class="fa-solid fa-lock"></i>
              <span>Nhật ký kiểm toán có tính bất biến (Immutable Audit Log). Dữ liệu này chỉ đọc và không thể chỉnh sửa hoặc xóa bởi bất kỳ người dùng nào. Mọi thông tin mật khẩu, token và khóa bảo mật đã được máy chủ lọc bỏ tự động.</span>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });
const route = useRoute();
const router = useRouter();

// State
const logs = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedLog = ref(null);
const showDrawer = ref(false);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});

// Filters
const searchTerm = ref('');
const filterAction = ref('');
const filterActorType = ref('');
const filterTargetType = ref('');
const filterDateFrom = ref('');
const filterDateTo = ref('');
const perPage = ref(15);

let searchDebounceTimer = null;
let activeRequestId = 0;

// Action Dictionary Mapping to Vietnamese friendly labels, icons and themes
const ACTION_MAP = {
  // Bookings & Handovers
  'booking.created': { label: 'Tạo đơn đặt xe mới', icon: 'fa-calendar-plus', theme: 'success' },
  'booking.status_updated': { label: 'Cập nhật trạng thái đơn', icon: 'fa-clock-rotate-left', theme: 'info' },
  'booking.payment_confirmed': { label: 'Xác nhận thanh toán tiền cọc/thuê', icon: 'fa-circle-check', theme: 'success' },
  'booking.cancelled': { label: 'Hủy đơn đặt xe', icon: 'fa-ban', theme: 'danger' },
  'booking.handover_created': { label: 'Tạo biên bản giao/nhận xe', icon: 'fa-clipboard-check', theme: 'info' },
  'booking.handover_completed': { label: 'Hoàn tất bàn giao xe', icon: 'fa-car-side', theme: 'success' },

  // Cars & Fleet
  'car.created': { label: 'Thêm phương tiện mới', icon: 'fa-car', theme: 'success' },
  'car.updated': { label: 'Cập nhật thông số kỹ thuật xe', icon: 'fa-pen-to-square', theme: 'info' },
  'car.publication_status_updated': { label: 'Thay đổi trạng thái xuất bản xe', icon: 'fa-eye', theme: 'warning' },
  'car.maintenance_toggled': { label: 'Bật/tắt bảo dưỡng phương tiện', icon: 'fa-wrench', theme: 'warning' },
  'car.soft_deleted': { label: 'Xóa tạm thời phương tiện', icon: 'fa-trash-arrow-up', theme: 'danger' },
  'car.restored': { label: 'Khôi phục phương tiện đã xóa', icon: 'fa-rotate-left', theme: 'success' },
  'car.force_deleted': { label: 'Xóa vĩnh viễn phương tiện', icon: 'fa-fire', theme: 'danger' },
  'car_category.created': { label: 'Tạo danh mục phương tiện', icon: 'fa-tags', theme: 'success' },
  'car_category.updated': { label: 'Cập nhật danh mục xe', icon: 'fa-pen-to-square', theme: 'info' },
  'car_category.status_toggled': { label: 'Bật/tắt trạng thái danh mục', icon: 'fa-toggle-on', theme: 'warning' },
  'car_category.deleted': { label: 'Xóa danh mục xe', icon: 'fa-trash', theme: 'danger' },

  // Customer CRM & Contacts
  'customer.profile_updated': { label: 'Cập nhật hồ sơ khách hàng', icon: 'fa-user-pen', theme: 'info' },
  'customer.note_added': { label: 'Thêm ghi chú chăm sóc khách', icon: 'fa-comment-medical', theme: 'info' },
  'customer.status_toggled': { label: 'Khóa / Mở khóa tài khoản khách', icon: 'fa-user-slash', theme: 'warning' },
  'contact.status_updated': { label: 'Cập nhật trạng thái hỗ trợ', icon: 'fa-headset', theme: 'info' },
  'contact.assigned': { label: 'Phân công nhân viên tiếp nhận', icon: 'fa-user-check', theme: 'info' },
  'contact.activity_added': { label: 'Ghi nhật ký tương tác liên hệ', icon: 'fa-comments', theme: 'info' },
  'appointment.created': { label: 'Tạo lịch hẹn tư vấn mới', icon: 'fa-calendar-check', theme: 'success' },
  'appointment.status_updated': { label: 'Cập nhật trạng thái lịch hẹn', icon: 'fa-arrows-rotate', theme: 'info' },
  'appointment.rescheduled': { label: 'Dời lịch hẹn tư vấn', icon: 'fa-calendar-days', theme: 'warning' },
  'appointment.cancelled': { label: 'Hủy lịch hẹn tư vấn', icon: 'fa-calendar-xmark', theme: 'danger' },
  'appointment.completed': { label: 'Hoàn thành buổi tư vấn xe', icon: 'fa-circle-check', theme: 'success' },

  // Settings & System
  'settings.bank_updated': { label: 'Thay đổi tài khoản ngân hàng VietQR', icon: 'fa-building-columns', theme: 'warning' },
  'settings.updated': { label: 'Cập nhật cấu hình hệ thống', icon: 'fa-sliders', theme: 'info' },

  // Media & CMS
  'media.uploaded': { label: 'Tải lên tài nguyên thư viện media', icon: 'fa-cloud-arrow-up', theme: 'success' },
  'media.updated': { label: 'Cập nhật thông tin tài nguyên', icon: 'fa-pen', theme: 'info' },
  'media.deleted': { label: 'Xóa tài nguyên khỏi thư viện', icon: 'fa-trash', theme: 'danger' },
  'content.draft_saved': { label: 'Lưu bản nháp nội dung CMS', icon: 'fa-file-lines', theme: 'info' },
  'content.published': { label: 'Xuất bản nội dung website CMS', icon: 'fa-globe', theme: 'success' },
  'content.rolled_back': { label: 'Khôi phục phiên bản nội dung CMS', icon: 'fa-clock-rotate-left', theme: 'warning' },

  // Posts
  'post.created': { label: 'Tạo bài viết mới', icon: 'fa-newspaper', theme: 'success' },
  'post.updated': { label: 'Chỉnh sửa nội dung bài viết', icon: 'fa-pen-to-square', theme: 'info' },
  'post.submitted_for_review': { label: 'Gửi duyệt bài viết', icon: 'fa-paper-plane', theme: 'info' },
  'post.approved': { label: 'Phê duyệt bài viết', icon: 'fa-check-double', theme: 'success' },
  'post.rejected': { label: 'Từ chối bài viết', icon: 'fa-xmark', theme: 'danger' },
  'post.published': { label: 'Xuất bản bài viết', icon: 'fa-circle-check', theme: 'success' },
  'post.archived': { label: 'Lưu trữ bài viết', icon: 'fa-box-archive', theme: 'warning' },
  'post.soft_deleted': { label: 'Xóa tạm bài viết', icon: 'fa-trash', theme: 'danger' },
  'post.restored': { label: 'Khôi phục bài viết', icon: 'fa-rotate-left', theme: 'success' },
  'post.force_deleted': { label: 'Xóa vĩnh viễn bài viết', icon: 'fa-fire', theme: 'danger' },
  'post_category.created': { label: 'Tạo chuyên mục bài viết', icon: 'fa-folder-plus', theme: 'success' },
  'post_category.updated': { label: 'Sửa chuyên mục bài viết', icon: 'fa-folder-open', theme: 'info' },
  'post_category.deleted': { label: 'Xóa chuyên mục bài viết', icon: 'fa-folder-minus', theme: 'danger' },
};

// Target Label Dictionary
const TARGET_MAP = {
  'Booking': 'Đơn đặt xe',
  'Car': 'Phương tiện xe',
  'CarCategory': 'Danh mục xe',
  'User': 'Khách hàng',
  'Admin': 'Quản trị viên',
  'Contact': 'Yêu cầu liên hệ',
  'Appointment': 'Lịch hẹn tư vấn',
  'SystemSetting': 'Cấu hình hệ thống',
  'Post': 'Bài viết tin tức',
  'PostCategory': 'Chuyên mục bài viết',
  'WebsiteContent': 'Nội dung CMS',
  'MediaAsset': 'Tài nguyên Media',
};

// Check if any filter is active
const hasActiveFilters = computed(() => {
  return Boolean(
    searchTerm.value ||
    filterAction.value ||
    filterActorType.value ||
    filterTargetType.value ||
    filterDateFrom.value ||
    filterDateTo.value
  );
});

// Fetch Audit Logs with debounce & stale response rejection
const fetchLogs = async () => {
  const currentReq = ++activeRequestId;
  loading.value = true;
  error.value = null;

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: perPage.value,
    };

    if (searchTerm.value.trim()) params.search = searchTerm.value.trim();
    if (filterAction.value) params.action = filterAction.value;
    if (filterActorType.value) params.actor_type = filterActorType.value;
    if (filterTargetType.value) params.target_type = filterTargetType.value;
    if (filterDateFrom.value) params.date_from = filterDateFrom.value;
    if (filterDateTo.value) params.date_to = filterDateTo.value;

    const res = await baseRequest.get('admin/audit-logs', { params });

    // Discard stale response if a newer request was dispatched
    if (currentReq !== activeRequestId) return;

    if (res.data && res.data.status && res.data.data) {
      logs.value = res.data.data;
      if (res.data.pagination) {
        pagination.value = res.data.pagination;
      }
    }
  } catch (err) {
    if (currentReq !== activeRequestId) return;
    const msg = err.response?.data?.message || 'Không thể tải nhật ký hoạt động từ máy chủ!';
    error.value = msg;
    if (err.response?.status === 403) {
      toaster.error("Bạn không có quyền truy cập nhật ký kiểm toán (audit_logs.view)!");
    } else {
      toaster.error(msg);
    }
  } finally {
    if (currentReq === activeRequestId) {
      loading.value = false;
    }
  }
};

// Sync state to URL Query
const syncQueryToUrl = () => {
  const query = {};
  if (searchTerm.value.trim()) query.search = searchTerm.value.trim();
  if (filterAction.value) query.action = filterAction.value;
  if (filterActorType.value) query.actor_type = filterActorType.value;
  if (filterTargetType.value) query.target_type = filterTargetType.value;
  if (filterDateFrom.value) query.date_from = filterDateFrom.value;
  if (filterDateTo.value) query.date_to = filterDateTo.value;
  if (pagination.value.current_page > 1) query.page = String(pagination.value.current_page);
  if (perPage.value !== 15) query.per_page = String(perPage.value);

  router.replace({ query }).catch(() => {});
};

// Initialize filters from URL Query
const initFiltersFromQuery = () => {
  const q = route.query;
  if (q.search) searchTerm.value = String(q.search);
  if (q.action) filterAction.value = String(q.action);
  if (q.actor_type) filterActorType.value = String(q.actor_type);
  if (q.target_type) filterTargetType.value = String(q.target_type);
  if (q.date_from) filterDateFrom.value = String(q.date_from);
  if (q.date_to) filterDateTo.value = String(q.date_to);
  if (q.page) pagination.value.current_page = parseInt(q.page, 10) || 1;
  if (q.per_page) perPage.value = parseInt(q.per_page, 10) || 15;
};

// User Interactions
const onSearchInput = () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    pagination.value.current_page = 1;
    syncQueryToUrl();
    fetchLogs();
  }, 350);
};

const clearSearch = () => {
  searchTerm.value = '';
  pagination.value.current_page = 1;
  syncQueryToUrl();
  fetchLogs();
};

const applyFilters = () => {
  pagination.value.current_page = 1;
  syncQueryToUrl();
  fetchLogs();
};

const setActorFilter = (type) => {
  filterActorType.value = type;
  applyFilters();
};

const setDatePreset = (preset) => {
  const now = new Date();
  const formatYmd = (d) => d.toISOString().split('T')[0];

  if (preset === 'today') {
    const todayStr = formatYmd(now);
    filterDateFrom.value = todayStr;
    filterDateTo.value = todayStr;
  } else if (preset === '7days') {
    const past = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
    filterDateFrom.value = formatYmd(past);
    filterDateTo.value = formatYmd(now);
  } else if (preset === '30days') {
    const past = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    filterDateFrom.value = formatYmd(past);
    filterDateTo.value = formatYmd(now);
  } else if (preset === 'all') {
    filterDateFrom.value = '';
    filterDateTo.value = '';
  }

  applyFilters();
};

const resetFilters = () => {
  searchTerm.value = '';
  filterAction.value = '';
  filterActorType.value = '';
  filterTargetType.value = '';
  filterDateFrom.value = '';
  filterDateTo.value = '';
  pagination.value.current_page = 1;
  syncQueryToUrl();
  fetchLogs();
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  pagination.value.current_page = page;
  syncQueryToUrl();
  fetchLogs();
};

const onPerPageChange = () => {
  pagination.value.current_page = 1;
  syncQueryToUrl();
  fetchLogs();
};

// Details Drawer
const openDetailDrawer = (log) => {
  selectedLog.value = log;
  showDrawer.value = true;
};

const closeDetailDrawer = () => {
  showDrawer.value = false;
};

// Helpers for presentation
const getActionLabel = (action) => {
  if (!action) return 'Thao tác không xác định';
  if (ACTION_MAP[action]) return ACTION_MAP[action].label;
  return action.replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getActionIcon = (action) => {
  if (ACTION_MAP[action]) return 'fa-solid ' + ACTION_MAP[action].icon;
  return 'fa-solid fa-clock-rotate-left';
};

const getActionTheme = (action) => {
  if (ACTION_MAP[action]) return 'theme-' + ACTION_MAP[action].theme;
  return 'theme-info';
};

const getActorTypeLabel = (actorType) => {
  switch (actorType) {
    case 'admin': return 'Quản trị viên';
    case 'user': return 'Khách hàng';
    case 'system': return 'Hệ thống';
    default: return actorType || 'Hệ thống';
  }
};

const getActorIcon = (actorType) => {
  switch (actorType) {
    case 'admin': return 'fa-solid fa-user-shield';
    case 'user': return 'fa-solid fa-user';
    case 'system': return 'fa-solid fa-server';
    default: return 'fa-solid fa-circle-user';
  }
};

const getTargetTypeLabel = (targetType) => {
  if (!targetType) return 'Hệ thống';
  return TARGET_MAP[targetType] || targetType;
};

// Smart link resolution for existing entities
const getTargetRoute = (log) => {
  if (!log || !log.target_type || !log.target_id) return null;
  const id = log.target_id;
  switch (log.target_type) {
    case 'Booking':
      return { path: '/admin/bookings', query: { search: id } };
    case 'Car':
      return { path: `/admin/cars/edit/${id}` };
    case 'User':
      return { path: '/admin/users', query: { search: id } };
    case 'Contact':
      return { path: '/admin/contacts', query: { search: id } };
    case 'Appointment':
      return { path: '/admin/appointments', query: { search: id } };
    case 'Post':
      return { path: '/admin/posts', query: { search: id } };
    case 'SystemSetting':
      return { path: '/admin/settings' };
    default:
      return null;
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '---';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleTimeString('vi-VN', { hour12: false }) + ' ' + d.toLocaleDateString('vi-VN');
};

const formatRelativeTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  const diffSec = Math.floor((now - d) / 1000);
  if (diffSec < 60) return 'Vừa xong';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)} phút trước`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} giờ trước`;
  return `${Math.floor(diffSec / 86400)} ngày trước`;
};

// Payload Diff Computation
const hasDiffChanges = (log) => {
  if (!log) return false;
  const hasOld = log.old_values && typeof log.old_values === 'object' && Object.keys(log.old_values).length > 0;
  const hasNew = log.new_values && typeof log.new_values === 'object' && Object.keys(log.new_values).length > 0;
  return hasOld && hasNew;
};

const computeDiffRows = (log) => {
  if (!log) return [];
  const oldVals = log.old_values || {};
  const newVals = log.new_values || {};

  const allKeys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]));

  return allKeys.map(key => {
    const oldV = oldVals[key];
    const newV = newVals[key];
    const isChanged = JSON.stringify(oldV) !== JSON.stringify(newV);
    return {
      field: key,
      oldVal: oldV,
      newVal: newV,
      isChanged,
    };
  });
};

const formatDiffValue = (val) => {
  if (val === undefined) return '(không tồn tại)';
  if (val === null) return 'null';
  if (typeof val === 'object') {
    return JSON.stringify(val, null, 2);
  }
  return String(val);
};

const getPayloadTypeClass = (log) => {
  if (hasDiffChanges(log)) return 'badge-update';
  if (log?.new_values && Object.keys(log.new_values).length > 0) return 'badge-create';
  if (log?.old_values && Object.keys(log.old_values).length > 0) return 'badge-delete';
  return 'badge-checkpoint';
};

const getPayloadTypeLabel = (log) => {
  if (hasDiffChanges(log)) return 'Biến động cập nhật (Update Diff)';
  if (log?.new_values && Object.keys(log.new_values).length > 0) return 'Bản ghi tạo mới (Created Payload)';
  if (log?.old_values && Object.keys(log.old_values).length > 0) return 'Bản ghi xóa bỏ (Deleted Payload)';
  return 'Sự kiện mốc (Event Log)';
};

onMounted(() => {
  initFiltersFromQuery();
  fetchLogs();
});
</script>

<style scoped>
.admin-page {
  padding-bottom: 50px;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--color-primary-light, #ecfdf5);
  color: var(--color-primary, #059669);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: #101828;
  margin: 0;
}

.page-title span {
  color: var(--color-primary, #059669);
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.total-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f1f5f9;
  color: #334155;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #334155;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* Quick Stats Bar */
.quick-stats-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  overflow-x: auto;
}

.stat-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 8px 16px;
  border-radius: 24px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.stat-pill:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.stat-pill.active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}

.stat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.stat-dot.all { background: #94a3b8; }
.stat-dot.admin { background: #2563eb; }
.stat-dot.user { background: #8b5cf6; }
.stat-dot.system { background: #10b981; }

/* Filter Toolbar */
.filter-toolbar {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.toolbar-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
  gap: 16px;
  align-items: end;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-field label {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 6px;
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input-wrap input {
  width: 100%;
  padding: 9px 34px 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  background: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input-wrap input:focus,
.filter-field select:focus,
.date-inputs input:focus {
  outline: none;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.btn-clear-search {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  font-size: 18px;
  color: #94a3b8;
  cursor: pointer;
}

.filter-field select {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  background: #ffffff;
}

.date-inputs {
  display: flex;
  align-items: center;
  gap: 6px;
}

.date-inputs input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  background: #ffffff;
}

.date-sep {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
}

.toolbar-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 12px;
}

.preset-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.preset-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.btn-preset {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-preset:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.active-filter-indicator {
  font-size: 12px;
  color: #059669;
  font-weight: 600;
}

.btn-reset {
  background: transparent;
  border: 1px dashed #cbd5e1;
  color: #64748b;
  padding: 5px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-reset:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

/* Table Card */
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
}

.audit-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 12px 16px;
  border-bottom: 2px solid #e2e8f0;
  text-align: left;
}

.audit-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
  color: #334155;
  vertical-align: middle;
}

.audit-table tbody tr:hover {
  background: #f8fafc;
}

.highlighted-row {
  background: #f0fdf4 !important;
}

.log-id {
  font-family: monospace;
  font-weight: 700;
  color: #64748b;
  font-size: 12px;
}

/* Time Cell */
.time-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.time-main {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.time-main i {
  color: #94a3b8;
  font-size: 12px;
}

.time-ago {
  font-size: 11px;
  color: #64748b;
}

/* Actor Cell */
.actor-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.actor-header {
  display: flex;
  align-items: center;
  gap: 6px;
}

.actor-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
}

.actor-badge.admin {
  background: #dbeafe;
  color: #1d4ed8;
}

.actor-badge.user {
  background: #f3e8ff;
  color: #7e22ce;
}

.actor-badge.system {
  background: #e2e8f0;
  color: #334155;
}

.ip-tag {
  font-family: monospace;
  font-size: 11px;
  color: #94a3b8;
  background: #f8fafc;
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.actor-name {
  font-weight: 700;
  color: #0f172a;
}

.actor-email {
  font-size: 11px;
  color: #64748b;
}

/* Action Cell */
.action-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.action-friendly {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: 13px;
}

.action-friendly.theme-success { color: #15803d; }
.action-friendly.theme-info { color: #0369a1; }
.action-friendly.theme-warning { color: #b45309; }
.action-friendly.theme-danger { color: #b91c1c; }

.action-raw-code {
  font-family: monospace;
  font-size: 11px;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  width: fit-content;
}

/* Target Cell */
.target-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.target-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  width: fit-content;
}

.target-id {
  color: #059669;
  font-weight: 700;
}

.target-title {
  font-size: 13px;
  font-weight: 600;
}

.target-link {
  color: #0284c7;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.target-link:hover {
  text-decoration: underline;
  color: #0369a1;
}

.target-label-text {
  color: #1e293b;
}

/* Details Action Button */
.btn-view-details {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f0fdf4;
  color: var(--color-primary, #059669);
  border: 1px solid #bbf7d0;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view-details:hover {
  background: #059669;
  color: #ffffff;
  border-color: #059669;
}

/* Pagination Footer */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #ffffff;
  border-top: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 14px;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 16px;
}

.per-page-select {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #64748b;
}

.per-page-select select {
  padding: 4px 8px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  background: #ffffff;
}

.page-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-page {
  width: 32px;
  height: 32px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #334155;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-current {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

/* Skeleton & Empty States */
.skeleton-line {
  height: 24px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.empty-cell {
  padding: 48px 16px !important;
  text-align: center;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin-bottom: 16px;
}

.empty-state h4 {
  font-size: 16px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 6px 0;
}

.empty-state p {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 16px 0;
}

.btn-reset-empty {
  background: #0f172a;
  color: #ffffff;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Details Slide-Over Drawer */
.drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  justify-content: flex-end;
}

.drawer-panel {
  width: 100%;
  max-width: 680px;
  background: #ffffff;
  height: 100vh;
  box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideDrawer 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideDrawer {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.drawer-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: var(--color-primary, #059669);
  background: #ecfdf5;
  padding: 4px 10px;
  border-radius: 12px;
  margin-bottom: 8px;
}

.drawer-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
}

.drawer-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.raw-code-pill {
  background: #e2e8f0;
  color: #334155;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
}

.btn-drawer-close {
  background: transparent;
  border: none;
  font-size: 28px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.btn-drawer-close:hover {
  color: #0f172a;
}

.drawer-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

/* Drawer Metadata Grid */
.metadata-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
}

.meta-val {
  font-size: 13px;
  color: #0f172a;
}

.actor-meta {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-target-direct {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  color: #0284c7;
  text-decoration: none;
}

.btn-target-direct:hover {
  text-decoration: underline;
}

/* Changes Section */
.changes-section {
  margin-top: 20px;
}

.changes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.changes-header h4 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.changes-status-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
}

.badge-update { background: #e0f2fe; color: #0369a1; }
.badge-create { background: #dcfce7; color: #15803d; }
.badge-delete { background: #fee2e2; color: #b91c1c; }
.badge-checkpoint { background: #f1f5f9; color: #475569; }

/* Diff Table */
.diff-card, .payload-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.payload-card-header {
  background: #f8fafc;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.diff-table {
  width: 100%;
  border-collapse: collapse;
}

.diff-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 10px 14px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}

.diff-table td {
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 12px;
  vertical-align: top;
}

.field-col {
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
}

.change-indicator {
  color: #f59e0b;
  font-size: 10px;
}

.field-changed {
  background: #fffbeb;
}

.val-col.old-val {
  background: rgba(254, 242, 242, 0.5);
  color: #991b1b;
}

.val-col.new-val {
  background: rgba(240, 253, 244, 0.5);
  color: #166534;
}

.json-cell {
  margin: 0;
  font-family: monospace;
  font-size: 12px;
  white-space: pre-wrap;
  word-break: break-all;
}

.no-payload-notice {
  display: flex;
  gap: 12px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  padding: 18px;
  border-radius: 8px;
  color: #64748b;
  align-items: flex-start;
}

.no-payload-notice i {
  font-size: 20px;
  color: #94a3b8;
  margin-top: 2px;
}

.notice-content strong {
  color: #334155;
  display: block;
  font-size: 14px;
  margin-bottom: 4px;
}

.notice-content p {
  margin: 0;
  font-size: 12px;
  line-height: 1.4;
}

.immutability-notice {
  display: flex;
  gap: 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.4;
  align-items: flex-start;
}

.immutability-notice i {
  margin-top: 2px;
  font-size: 14px;
}

/* Drawer Transition */
.drawer-fade-enter-active, .drawer-fade-leave-active {
  transition: opacity 0.3s;
}

.drawer-fade-enter-from, .drawer-fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .toolbar-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .toolbar-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .metadata-grid {
    grid-template-columns: 1fr;
  }
  .drawer-panel {
    max-width: 100%;
  }
}
</style>
