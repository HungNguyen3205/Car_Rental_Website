<template>
  <div class="appointments-page">
    <!-- Header -->
    <header class="page-header">
      <div class="header-main">
        <div class="header-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
          <h1 class="page-title">Lịch Hẹn <span>Tư Vấn & Xem Xe</span></h1>
          <p class="page-subtitle">Quản lý lịch hẹn lái thử & tư vấn chuyên sâu, kiểm soát xung đột lịch chuyên viên và độc lập với lịch thuê xe.</p>
        </div>
      </div>
      <div class="header-actions">
        <!-- Chế độ xem: Danh sách / Lịch Ngày / Lịch Tuần -->
        <div class="view-switch-tabs">
          <button 
            class="switch-btn" 
            :class="{ active: activeView === 'list' }" 
            @click="switchView('list')"
            title="Xem dạng danh sách bảng"
          >
            <i class="fa-solid fa-list-ul"></i>
            <span>Danh sách</span>
          </button>
          <button 
            class="switch-btn" 
            :class="{ active: activeView === 'day' }" 
            @click="switchView('day')"
            title="Xem lịch theo ngày"
          >
            <i class="fa-solid fa-calendar-day"></i>
            <span>Lịch ngày</span>
          </button>
          <button 
            class="switch-btn" 
            :class="{ active: activeView === 'week' }" 
            @click="switchView('week')"
            title="Xem lịch theo tuần"
          >
            <i class="fa-solid fa-calendar-week"></i>
            <span>Lịch tuần</span>
          </button>
        </div>

        <button class="btn-refresh" @click="refreshData" :disabled="loading" title="Tải lại dữ liệu">
          <i class="fa-solid fa-rotate" :class="{ 'fa-spin': loading }"></i>
        </button>

        <button class="btn-primary" @click="openCreateModal()">
          <i class="fa-solid fa-calendar-plus"></i>
          <span>Đặt lịch hẹn</span>
        </button>
      </div>
    </header>

    <!-- KPI Summary Cards (6 Cards) -->
    <section class="kpi-grid">
      <div 
        class="kpi-card" 
        :class="{ active: filterStatus === 'all' && filterAssignedTo === 'all' }"
        @click="applyKpiFilter('all')"
      >
        <div class="kpi-icon-box total"><i class="fa-solid fa-calendar-days"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Tổng lịch hẹn</span>
          <span class="kpi-value">{{ counts.total || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ active: filterStatus === 'scheduled' }"
        @click="applyKpiFilter('scheduled')"
      >
        <div class="kpi-icon-box scheduled"><i class="fa-solid fa-clock"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Đã lên lịch</span>
          <span class="kpi-value">{{ counts.scheduled || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ active: filterStatus === 'completed' }"
        @click="applyKpiFilter('completed')"
      >
        <div class="kpi-icon-box completed"><i class="fa-solid fa-circle-check"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Đã hoàn tất</span>
          <span class="kpi-value">{{ counts.completed || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ active: filterStatus === 'cancelled' }"
        @click="applyKpiFilter('cancelled')"
      >
        <div class="kpi-icon-box cancelled"><i class="fa-solid fa-ban"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Đã hủy</span>
          <span class="kpi-value">{{ counts.cancelled || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card" 
        :class="{ active: filterStatus === 'no_show' }"
        @click="applyKpiFilter('no_show')"
      >
        <div class="kpi-icon-box no-show"><i class="fa-solid fa-user-slash"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Khách vắng mặt</span>
          <span class="kpi-value">{{ counts.no_show || 0 }}</span>
        </div>
      </div>

      <div 
        class="kpi-card highlight-card" 
        :class="{ active: isTodayFilterActive }"
        @click="filterTodayOnly"
      >
        <div class="kpi-icon-box today"><i class="fa-solid fa-bell"></i></div>
        <div class="kpi-info">
          <span class="kpi-label">Hôm nay</span>
          <span class="kpi-value">{{ counts.today || 0 }}</span>
        </div>
      </div>
    </section>

    <!-- ==================== VIEW 1: DANH SÁCH (LIST VIEW) ==================== -->
    <div v-if="activeView === 'list'" class="view-container">
      <!-- Toolbar bộ lọc tìm kiếm -->
      <div class="card filter-toolbar">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input 
            type="text" 
            v-model="searchKeyword" 
            placeholder="Tìm theo tên khách, SĐT, email, mã #APT-..." 
            @input="onSearchInput"
          />
          <button v-if="searchKeyword" class="clear-search-btn" @click="clearSearch">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="filter-controls">
          <!-- Trạng thái -->
          <div class="select-wrapper">
            <select v-model="filterStatus" @change="fetchAppointments">
              <option value="all">Tất cả trạng thái</option>
              <option value="scheduled">Đã lên lịch</option>
              <option value="completed">Đã hoàn tất</option>
              <option value="cancelled">Đã hủy</option>
              <option value="no_show">Khách vắng mặt</option>
            </select>
          </div>

          <!-- Chuyên viên phụ trách -->
          <div class="select-wrapper">
            <select v-model="filterAssignedTo" @change="fetchAppointments">
              <option value="all">Tất cả chuyên viên</option>
              <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                {{ staff.ho_ten }} ({{ staff.role_label }})
              </option>
            </select>
          </div>

          <!-- Hình thức hẹn -->
          <div class="select-wrapper">
            <select v-model="filterMeetingType" @change="fetchAppointments">
              <option value="all">Tất cả hình thức</option>
              <option value="viewing">Xem xe & Lái thử</option>
              <option value="consultation">Tư vấn tại Showroom</option>
              <option value="online">Tư vấn trực tuyến</option>
            </select>
          </div>

          <!-- Xe quan tâm -->
          <div class="select-wrapper">
            <select v-model="filterCarId" @change="fetchAppointments">
              <option value="all">Tất cả xe</option>
              <option v-for="c in carList" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.license_plate || 'Chưa biển' }})
              </option>
            </select>
          </div>

          <!-- Reset Filter -->
          <button class="btn-outline-sm" @click="resetAllFilters" title="Đặt lại bộ lọc">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            <span>Đặt lại</span>
          </button>
        </div>
      </div>

      <!-- Bảng danh sách lịch hẹn -->
      <div class="card table-card">
        <div v-if="loading" class="loading-state">
          <i class="fa-solid fa-spinner fa-spin loading-icon"></i>
          <span>Đang tải danh sách lịch hẹn...</span>
        </div>

        <div v-else-if="appointments.length === 0" class="empty-state">
          <i class="fa-solid fa-calendar-xmark empty-icon"></i>
          <h3>Không tìm thấy lịch hẹn nào</h3>
          <p>Hãy thử thay đổi điều kiện tìm kiếm hoặc tạo một cuộc hẹn tư vấn mới.</p>
          <button class="btn-primary-sm" @click="openCreateModal()">
            <i class="fa-solid fa-calendar-plus"></i> Đặt lịch hẹn mới
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Mã hẹn</th>
                <th>Khách hàng</th>
                <th>Hình thức & Địa điểm</th>
                <th>Xe quan tâm</th>
                <th>Chuyên viên phụ trách</th>
                <th>Thời gian hẹn</th>
                <th>Trạng thái</th>
                <th class="text-right">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="apt in appointments" :key="apt.id" :class="{ 'row-cancelled': apt.status === 'cancelled' }">
                <!-- Mã hẹn -->
                <td class="font-mono">
                  <span class="apt-code-badge" @click="openDetailModal(apt)">
                    {{ apt.formatted_id }}
                  </span>
                  <div v-if="apt.contact_id" class="sub-link-meta" title="Bắt nguồn từ yêu cầu tư vấn">
                    <i class="fa-solid fa-headset"></i> #REQ-{{ apt.contact_id }}
                  </div>
                </td>

                <!-- Khách hàng -->
                <td>
                  <div class="customer-cell">
                    <span class="customer-name" @click="openDetailModal(apt)">{{ apt.customer_name }}</span>
                    <span class="customer-phone"><i class="fa-solid fa-phone"></i> {{ apt.customer_phone }}</span>
                    <span v-if="apt.user_id" class="badge-cus" title="Khách đã có tài khoản trên hệ thống">
                      <i class="fa-solid fa-user-check"></i> #CUS-{{ apt.user_id }}
                    </span>
                  </div>
                </td>

                <!-- Hình thức & Địa điểm -->
                <td>
                  <div class="meeting-cell">
                    <span class="meeting-type-badge" :class="apt.meeting_type">
                      <i class="fa-solid" :class="getMeetingTypeIcon(apt.meeting_type)"></i>
                      {{ apt.meeting_type_label }}
                    </span>
                    <span class="location-text" :title="apt.location">
                      <i class="fa-solid fa-location-dot"></i> {{ apt.location }}
                    </span>
                  </div>
                </td>

                <!-- Xe quan tâm -->
                <td>
                  <div v-if="apt.car" class="car-cell">
                    <img :src="getCarImage(apt.car)" :alt="apt.car.name" class="car-mini-thumb" />
                    <div class="car-meta">
                      <span class="car-name">{{ apt.car.name }}</span>
                      <span class="car-plate">{{ apt.car.license_plate || apt.car.type }}</span>
                      <!-- Cảnh báo mềm độc lập với lịch thuê -->
                      <span v-if="apt.car_rental_conflict" class="badge-conflict-warning" title="Xe đang có khách thuê thực tế trong khung giờ này (Chỉ xem tĩnh)">
                        <i class="fa-solid fa-triangle-exclamation"></i> Đang bận thuê
                      </span>
                    </div>
                  </div>
                  <span v-else class="text-muted">Chưa chỉ định xe</span>
                </td>

                <!-- Chuyên viên phụ trách -->
                <td>
                  <div v-if="apt.assignee" class="staff-cell">
                    <div class="staff-avatar">{{ getInitials(apt.assignee.ho_ten) }}</div>
                    <div class="staff-info">
                      <span class="staff-name">{{ apt.assignee.ho_ten }}</span>
                      <span class="staff-role">{{ apt.assignee.role || 'Chuyên viên' }}</span>
                    </div>
                  </div>
                  <span v-else class="text-danger">Chưa phân công</span>
                </td>

                <!-- Thời gian hẹn -->
                <td>
                  <div class="time-cell">
                    <span class="date-str"><i class="fa-regular fa-calendar"></i> {{ formatDate(apt.start_time) }}</span>
                    <span class="hours-range"><i class="fa-regular fa-clock"></i> {{ formatTimeRange(apt.start_time, apt.end_time) }}</span>
                    <small class="duration-label">({{ apt.duration_minutes }} phút)</small>
                  </div>
                </td>

                <!-- Trạng thái -->
                <td>
                  <span class="status-badge" :class="apt.status_badge">
                    {{ apt.status_label }}
                  </span>
                </td>

                <!-- Hành động -->
                <td class="text-right">
                  <div class="action-buttons">
                    <button class="act-btn btn-view" @click="openDetailModal(apt)" title="Xem chi tiết">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                    
                    <button 
                      v-if="apt.status === 'scheduled'" 
                      class="act-btn btn-edit" 
                      @click="openEditModal(apt)" 
                      title="Sửa / Đổi lịch"
                    >
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button 
                      v-if="apt.status === 'scheduled'" 
                      class="act-btn btn-complete" 
                      @click="openCompleteModal(apt)" 
                      title="Chốt kết quả tư vấn"
                    >
                      <i class="fa-solid fa-clipboard-check"></i>
                    </button>

                    <button 
                      v-if="apt.status === 'scheduled'" 
                      class="act-btn btn-remind" 
                      @click="triggerRemind(apt)" 
                      title="Gửi nhắc hẹn nội bộ"
                    >
                      <i class="fa-solid fa-bell"></i>
                    </button>

                    <button 
                      v-if="apt.status === 'scheduled'" 
                      class="act-btn btn-cancel" 
                      @click="openCancelModal(apt)" 
                      title="Hủy lịch hẹn"
                    >
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Phân trang -->
        <div v-if="pagination.total > 0" class="pagination-footer">
          <span class="pagination-info">
            Hiển thị <strong>{{ pagination.from || 0 }}</strong> - <strong>{{ pagination.to || 0 }}</strong> trên tổng <strong>{{ pagination.total }}</strong> lịch hẹn
          </span>
          <div class="pagination-controls">
            <button 
              class="page-nav-btn" 
              :disabled="pagination.current_page <= 1" 
              @click="changePage(pagination.current_page - 1)"
            >
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="page-current">Trang {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button 
              class="page-nav-btn" 
              :disabled="pagination.current_page >= pagination.last_page" 
              @click="changePage(pagination.current_page + 1)"
            >
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== VIEW 2 & 3: LỊCH NGÀY / LỊCH TUẦN ==================== -->
    <div v-else class="calendar-view-container">
      <!-- Calendar Navigator Bar -->
      <div class="card calendar-toolbar">
        <div class="nav-controls">
          <button class="cal-nav-btn" @click="navigateCalendarPrev" title="Khoảng trước">
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          <button class="cal-today-btn" @click="jumpCalendarToday" :class="{ active: isCalendarAnchorToday }">
            Hôm nay
          </button>
          <button class="cal-nav-btn" @click="navigateCalendarNext" title="Khoảng tiếp theo">
            <i class="fa-solid fa-chevron-right"></i>
          </button>

          <div class="period-display">
            <i class="fa-regular fa-calendar-days"></i>
            <span class="period-label-text">{{ calendarPeriodLabel }}</span>
          </div>

          <div class="date-picker-wrap">
            <input 
              type="date" 
              class="cal-date-input" 
              :value="calendarAnchorDate" 
              @change="onCalendarDateChange"
            />
          </div>
        </div>

        <!-- Calendar Filter Controls -->
        <div class="cal-filter-controls">
          <div class="select-wrapper">
            <select v-model="filterAssignedTo" @change="fetchCalendarAppointments">
              <option value="all">Tất cả chuyên viên</option>
              <option v-for="staff in staffList" :key="staff.id" :value="staff.id">{{ staff.ho_ten }}</option>
            </select>
          </div>

          <div class="select-wrapper">
            <select v-model="filterStatus" @change="fetchCalendarAppointments">
              <option value="all">Tất cả trạng thái</option>
              <option value="scheduled">Đã lên lịch</option>
              <option value="completed">Đã hoàn tất</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Lịch tuần: 7 Cột Thứ Hai -> Chủ Nhật -->
      <div v-if="activeView === 'week'" class="card week-grid-card">
        <div v-if="loading" class="cal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải lịch tuần...</div>
        <div v-else class="week-columns-grid">
          <div 
            v-for="day in weekDays" 
            :key="day.dateStr" 
            class="week-day-col" 
            :class="{ 'is-today': day.isToday, 'is-weekend': day.isWeekend }"
          >
            <div class="day-col-header">
              <span class="day-name">{{ day.dayName }}</span>
              <span class="day-number" :class="{ 'highlight': day.isToday }">{{ day.dayNumber }}</span>
              <span class="day-count-badge" v-if="day.appointments.length > 0">{{ day.appointments.length }} hẹn</span>
            </div>

            <div class="day-events-list">
              <div v-if="day.appointments.length === 0" class="empty-day-slot">
                <span>Không có lịch</span>
              </div>
              <div 
                v-for="apt in day.appointments" 
                :key="apt.id" 
                class="appointment-card-chip" 
                :class="[apt.status, apt.meeting_type]"
                @click="openDetailModal(apt)"
              >
                <div class="chip-top">
                  <span class="chip-time">
                    <i class="fa-regular fa-clock"></i> {{ formatTimeRange(apt.start_time, apt.end_time) }}
                  </span>
                  <span class="chip-status-dot" :class="apt.status"></span>
                </div>
                <h4 class="chip-customer">{{ apt.customer_name }}</h4>
                <div class="chip-meta">
                  <span class="chip-staff"><i class="fa-solid fa-user-tie"></i> {{ apt.assignee?.ho_ten || 'Chưa gán' }}</span>
                  <span v-if="apt.car" class="chip-car"><i class="fa-solid fa-car-side"></i> {{ apt.car.name }}</span>
                </div>
                <div v-if="apt.car_rental_conflict" class="chip-warning">
                  <i class="fa-solid fa-triangle-exclamation"></i> Xe bận thuê
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lịch ngày: Danh sách dòng thời gian chi tiết trong ngày -->
      <div v-if="activeView === 'day'" class="card day-view-card">
        <div v-if="loading" class="cal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải lịch ngày...</div>
        <div v-else class="day-timeline-layout">
          <div class="day-meta-sidebar">
            <div class="big-date-badge">
              <span class="b-day">{{ currentDayDateNumber }}</span>
              <span class="b-month">{{ currentDayMonthYear }}</span>
              <span class="b-weekday">{{ currentDayWeekday }}</span>
            </div>
            <div class="day-stats-box">
              <div class="d-stat"><span class="d-val">{{ dayViewAppointments.length }}</span><span class="d-lbl">Tổng cuộc hẹn</span></div>
              <div class="d-stat"><span class="d-val text-primary">{{ dayScheduledCount }}</span><span class="d-lbl">Sắp diễn ra</span></div>
              <div class="d-stat"><span class="d-val text-success">{{ dayCompletedCount }}</span><span class="d-lbl">Đã hoàn thành</span></div>
            </div>
          </div>

          <div class="day-timeline-content">
            <div v-if="dayViewAppointments.length === 0" class="empty-timeline">
              <i class="fa-solid fa-mug-hot"></i>
              <h4>Không có lịch hẹn nào trong ngày này</h4>
              <p>Chuyên viên đang có thời gian rảnh hoặc chưa có khách đặt lịch tư vấn.</p>
              <button class="btn-primary-sm" @click="openCreateModal({ date: calendarAnchorDate })">
                <i class="fa-solid fa-plus"></i> Đặt lịch hẹn ngày này
              </button>
            </div>

            <div v-else class="timeline-items-stream">
              <div 
                v-for="apt in dayViewAppointments" 
                :key="apt.id" 
                class="timeline-appointment-item"
                :class="apt.status"
              >
                <div class="item-time-col">
                  <span class="t-start">{{ formatHourOnly(apt.start_time) }}</span>
                  <span class="t-end">{{ formatHourOnly(apt.end_time) }}</span>
                  <small class="t-dur">{{ apt.duration_minutes }}p</small>
                </div>
                
                <div class="item-body-card">
                  <div class="card-head">
                    <div class="head-left">
                      <span class="apt-code">{{ apt.formatted_id }}</span>
                      <span class="meeting-pill" :class="apt.meeting_type">{{ apt.meeting_type_label }}</span>
                      <span class="status-pill" :class="apt.status_badge">{{ apt.status_label }}</span>
                      <span v-if="apt.car_rental_conflict" class="conflict-pill" title="Xe có lịch thuê trong thời gian này">
                        <i class="fa-solid fa-triangle-exclamation"></i> Xe bận thuê
                      </span>
                    </div>
                    <div class="head-right">
                      <button class="btn-act-icon" @click="openDetailModal(apt)" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></button>
                      <button v-if="apt.status === 'scheduled'" class="btn-act-icon" @click="openEditModal(apt)" title="Đổi lịch"><i class="fa-solid fa-pen"></i></button>
                    </div>
                  </div>

                  <h3 class="customer-title">{{ apt.customer_name }}</h3>
                  <div class="meta-row">
                    <span><i class="fa-solid fa-phone"></i> {{ apt.customer_phone }}</span>
                    <span><i class="fa-solid fa-location-dot"></i> {{ apt.location }}</span>
                    <span><i class="fa-solid fa-user-tie"></i> Phụ trách: <strong>{{ apt.assignee?.ho_ten }}</strong></span>
                    <span v-if="apt.car"><i class="fa-solid fa-car-side"></i> Xe: <strong>{{ apt.car.name }}</strong> ({{ apt.car.license_plate }})</span>
                  </div>
                  <p v-if="apt.notes" class="notes-preview"><i class="fa-regular fa-note-sticky"></i> {{ apt.notes }}</p>
                  <p v-if="apt.result_summary" class="result-preview"><i class="fa-solid fa-clipboard-check"></i> Kết quả: {{ apt.result_summary }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== MODALS ==================== -->
    <!-- Modal 1: Tạo / Sửa Lịch Hẹn -->
    <div v-if="showEditorModal" class="modal-backdrop" @click.self="closeEditorModal">
      <div class="modal-box editor-modal card">
        <div class="modal-header">
          <div class="m-title-box">
            <div class="m-icon"><i class="fa-solid fa-calendar-plus"></i></div>
            <div>
              <h3 class="m-title">{{ isEditing ? 'Chỉnh Sửa / Đổi Lịch Hẹn' : 'Đặt Lịch Hẹn Tư Vấn & Xem Xe' }}</h3>
              <p class="m-sub">{{ isEditing ? 'Cập nhật lại thời gian, hình thức hoặc phân công chuyên viên khác.' : 'Lên lịch hẹn tư vấn, lái thử xe trực tiếp hoặc tư vấn showroom cho khách.' }}</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeEditorModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="submitAppointmentForm" class="modal-body-form">
          <!-- Thông báo lỗi xung đột nếu có -->
          <div v-if="conflictErrorMessage" class="conflict-alert-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
              <strong>Xung đột lịch chuyên viên!</strong>
              <p>{{ conflictErrorMessage }}</p>
            </div>
          </div>

          <div class="form-grid">
            <!-- Thông tin khách hàng -->
            <div class="form-section-title"><i class="fa-solid fa-user"></i> Thông tin khách hàng</div>
            
            <div class="form-group">
              <label>Họ và tên khách hàng <span class="required">*</span></label>
              <input type="text" v-model="form.customer_name" required placeholder="Nguyễn Văn A" />
            </div>

            <div class="form-group">
              <label>Số điện thoại liên hệ <span class="required">*</span></label>
              <input type="tel" v-model="form.customer_phone" required placeholder="0901234567" />
            </div>

            <div class="form-group">
              <label>Email khách hàng</label>
              <input type="email" v-model="form.customer_email" placeholder="khachhang@example.com" />
            </div>

            <div class="form-group">
              <label>Hình thức cuộc hẹn <span class="required">*</span></label>
              <select v-model="form.meeting_type" required>
                <option value="viewing">Xem xe thực tế & Lái thử</option>
                <option value="consultation">Tư vấn trực tiếp tại Showroom</option>
                <option value="online">Tư vấn trực tuyến qua Video / Zalo</option>
              </select>
            </div>

            <!-- Phân công & Xe quan tâm -->
            <div class="form-section-title"><i class="fa-solid fa-car"></i> Chuyên viên & Dòng xe</div>

            <div class="form-group">
              <label>Chuyên viên phụ trách <span class="required">*</span></label>
              <select v-model="form.assigned_to" required>
                <option value="" disabled>-- Chọn chuyên viên tư vấn --</option>
                <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                  {{ staff.ho_ten }} - {{ staff.role_label }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Xe quan tâm (Xem xe / Lái thử)</label>
              <select v-model="form.car_id">
                <option :value="null">-- Khách chưa chỉ định dòng xe --</option>
                <option v-for="c in carList" :key="c.id" :value="c.id">
                  {{ c.name }} - {{ c.license_plate || c.type }} ({{ formatCurrency(c.price_per_day) }}/ngày)
                </option>
              </select>
            </div>

            <div class="form-group full-width">
              <label>Địa điểm hẹn gặp <span class="required">*</span></label>
              <input 
                type="text" 
                v-model="form.location" 
                required 
                placeholder="Ví dụ: Showroom Tân Bình - 123 Hoàng Văn Thụ, hoặc Tại địa chỉ khách..." 
              />
            </div>

            <!-- Thời gian -->
            <div class="form-section-title"><i class="fa-solid fa-clock"></i> Khung giờ hẹn & Nhắc nhở</div>

            <div class="form-group">
              <label>Thời điểm bắt đầu <span class="required">*</span></label>
              <input type="datetime-local" v-model="form.start_time" required @change="onStartTimeChange" />
            </div>

            <div class="form-group">
              <label>Thời điểm kết thúc <span class="required">*</span></label>
              <input type="datetime-local" v-model="form.end_time" required />
            </div>

            <div class="form-group">
              <label>Cài đặt nhắc hẹn trước</label>
              <select v-model="form.reminder_minutes">
                <option :value="15">Trước 15 phút</option>
                <option :value="30">Trước 30 phút</option>
                <option :value="60">Trước 1 tiếng</option>
                <option :value="120">Trước 2 tiếng</option>
                <option :value="1440">Trước 1 ngày</option>
              </select>
            </div>

            <div class="form-group full-width">
              <label>Ghi chú chuẩn bị trước hẹn</label>
              <textarea 
                v-model="form.notes" 
                rows="2" 
                placeholder="Chuẩn bị xe sạch sẽ, sạc pin > 80%, hồ sơ trả góp hoặc hợp đồng mẫu..."
              ></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-outline" @click="closeEditorModal">Hủy bỏ</button>
            <button type="submit" class="btn-primary" :disabled="formSubmitting">
              <i class="fa-solid fa-spinner fa-spin" v-if="formSubmitting"></i>
              <span v-else>{{ isEditing ? 'Lưu thay đổi' : 'Xác nhận tạo lịch hẹn' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal 2: Chi tiết lịch hẹn -->
    <div v-if="showDetailModal && selectedAppointment" class="modal-backdrop" @click.self="closeDetailModal">
      <div class="modal-box detail-modal card">
        <div class="modal-header">
          <div class="m-title-box">
            <div class="apt-lg-badge">{{ selectedAppointment.formatted_id }}</div>
            <div>
              <h3 class="m-title">{{ selectedAppointment.customer_name }}</h3>
              <span class="status-badge" :class="selectedAppointment.status_badge">{{ selectedAppointment.status_label }}</span>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeDetailModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="detail-modal-body">
          <!-- Cảnh báo nếu xe bận thuê -->
          <div v-if="selectedAppointment.car_rental_conflict" class="conflict-notice-banner">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
              <strong>Lưu ý lịch xe thực tế:</strong>
              <p>Chiếc xe quan tâm này đang có khách thuê thực tế trong khung giờ này. Cuộc hẹn tư vấn tại showroom vẫn diễn ra bình thường, nhưng khách chỉ có thể xem xe mẫu tương đương hoặc tư vấn trên catalog.</p>
            </div>
          </div>

          <!-- Thông tin chi tiết grid -->
          <div class="detail-info-grid">
            <div class="info-card">
              <h4><i class="fa-solid fa-user"></i> Khách hàng</h4>
              <p><strong>{{ selectedAppointment.customer_name }}</strong></p>
              <p><i class="fa-solid fa-phone"></i> {{ selectedAppointment.customer_phone }}</p>
              <p v-if="selectedAppointment.customer_email"><i class="fa-solid fa-envelope"></i> {{ selectedAppointment.customer_email }}</p>
              <p v-if="selectedAppointment.user_id">
                <router-link :to="`/admin/users?search=${selectedAppointment.customer_phone}`" class="link-user">
                  <i class="fa-solid fa-address-card"></i> Xem Hồ sơ khách (#CUS-{{ selectedAppointment.user_id }})
                </router-link>
              </p>
            </div>

            <div class="info-card">
              <h4><i class="fa-solid fa-user-tie"></i> Chuyên viên</h4>
              <p><strong>{{ selectedAppointment.assignee?.ho_ten || 'Chưa phân công' }}</strong></p>
              <p><i class="fa-solid fa-briefcase"></i> {{ selectedAppointment.assignee?.role || 'Nhân viên tư vấn' }}</p>
              <p v-if="selectedAppointment.assignee?.email"><i class="fa-solid fa-envelope"></i> {{ selectedAppointment.assignee?.email }}</p>
            </div>

            <div class="info-card">
              <h4><i class="fa-solid fa-clock"></i> Thời gian</h4>
              <p><strong>{{ formatDate(selectedAppointment.start_time) }}</strong></p>
              <p><i class="fa-regular fa-clock"></i> {{ formatTimeRange(selectedAppointment.start_time, selectedAppointment.end_time) }}</p>
              <small class="text-muted">Thời lượng: {{ selectedAppointment.duration_minutes }} phút</small>
            </div>

            <div class="info-card">
              <h4><i class="fa-solid fa-location-dot"></i> Hình thức & Nơi hẹn</h4>
              <p><span class="meeting-pill" :class="selectedAppointment.meeting_type">{{ selectedAppointment.meeting_type_label }}</span></p>
              <p class="location-full">{{ selectedAppointment.location }}</p>
            </div>
          </div>

          <!-- Xe quan tâm -->
          <div v-if="selectedAppointment.car" class="car-interest-box">
            <img :src="getCarImage(selectedAppointment.car)" :alt="selectedAppointment.car.name" class="car-int-thumb" />
            <div class="car-int-meta">
              <h4>{{ selectedAppointment.car.name }}</h4>
              <p>Biển số: <strong>{{ selectedAppointment.car.license_plate || 'Đang cập nhật' }}</strong> | Phân khúc: {{ selectedAppointment.car.type }}</p>
              <p class="price-txt">Giá thuê niêm yết: {{ formatCurrency(selectedAppointment.car.price_per_day) }}/ngày</p>
            </div>
          </div>

          <!-- Yêu cầu tư vấn liên kết (nếu có) -->
          <div v-if="selectedAppointment.contact" class="contact-link-box">
            <h4><i class="fa-solid fa-headset"></i> Yêu cầu tư vấn liên kết (#REQ-{{ selectedAppointment.contact.id }})</h4>
            <p><strong>{{ selectedAppointment.contact.tieu_de }}</strong></p>
            <p class="text-muted">{{ selectedAppointment.contact.noi_dung }}</p>
          </div>

          <!-- Ghi chú chuẩn bị -->
          <div v-if="selectedAppointment.notes" class="notes-box">
            <h4><i class="fa-regular fa-note-sticky"></i> Ghi chú nội bộ</h4>
            <p>{{ selectedAppointment.notes }}</p>
          </div>

          <!-- Kết quả buổi hẹn -->
          <div v-if="selectedAppointment.result_summary" class="result-box">
            <h4><i class="fa-solid fa-clipboard-check"></i> Kết quả buổi hẹn</h4>
            <p>{{ selectedAppointment.result_summary }}</p>
          </div>

          <!-- Lý do hủy (nếu có) -->
          <div v-if="selectedAppointment.status === 'cancelled'" class="cancel-box">
            <h4><i class="fa-solid fa-ban"></i> Thông tin hủy cuộc hẹn</h4>
            <p>Lý do: <strong>{{ selectedAppointment.cancel_reason }}</strong></p>
            <small class="text-muted">Hủy lúc: {{ formatDateTime(selectedAppointment.cancelled_at) }}</small>
          </div>
        </div>

        <div class="modal-footer detail-footer">
          <div class="left-actions">
            <button 
              v-if="selectedAppointment.status === 'scheduled'" 
              class="btn-outline-sm btn-remind-inline" 
              @click="triggerRemind(selectedAppointment)"
            >
              <i class="fa-solid fa-bell"></i> Gửi nhắc hẹn nội bộ
            </button>
          </div>

          <div class="right-actions">
            <button 
              v-if="selectedAppointment.status === 'scheduled'" 
              class="btn-danger-sm" 
              @click="openCancelModal(selectedAppointment)"
            >
              <i class="fa-solid fa-xmark"></i> Hủy hẹn
            </button>
            <button 
              v-if="selectedAppointment.status === 'scheduled'" 
              class="btn-outline-sm" 
              @click="openEditModal(selectedAppointment)"
            >
              <i class="fa-solid fa-pen"></i> Đổi lịch
            </button>
            <button 
              v-if="selectedAppointment.status === 'scheduled'" 
              class="btn-success-sm" 
              @click="openCompleteModal(selectedAppointment)"
            >
              <i class="fa-solid fa-check"></i> Chốt kết quả
            </button>
            <button class="btn-outline" @click="closeDetailModal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal 3: Hủy Lịch Hẹn -->
    <div v-if="showCancelModal && selectedAppointment" class="modal-backdrop" @click.self="closeCancelModal">
      <div class="modal-box small-modal card">
        <div class="modal-header">
          <div class="m-title-box">
            <div class="m-icon icon-danger"><i class="fa-solid fa-ban"></i></div>
            <div>
              <h3 class="m-title">Hủy Lịch Hẹn {{ selectedAppointment.formatted_id }}</h3>
              <p class="m-sub">Khung giờ sẽ được giải phóng hoàn toàn cho chuyên viên phụ trách.</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeCancelModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="submitCancelForm">
          <div class="modal-body-pad">
            <div class="form-group">
              <label>Lý do hủy lịch hẹn <span class="required">*</span></label>
              <textarea 
                v-model="cancelReason" 
                rows="3" 
                required 
                placeholder="Ví dụ: Khách bận đột xuất dời lịch, không liên lạc được với khách, khách đổi ý không thuê nữa..."
              ></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-outline" @click="closeCancelModal">Bỏ qua</button>
            <button type="submit" class="btn-danger" :disabled="formSubmitting || !cancelReason.trim()">
              <i class="fa-solid fa-spinner fa-spin" v-if="formSubmitting"></i>
              <span v-else>Xác nhận hủy hẹn</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal 4: Chốt Kết Quả Lịch Hẹn -->
    <div v-if="showCompleteModal && selectedAppointment" class="modal-backdrop" @click.self="closeCompleteModal">
      <div class="modal-box small-modal card">
        <div class="modal-header">
          <div class="m-title-box">
            <div class="m-icon icon-success"><i class="fa-solid fa-clipboard-check"></i></div>
            <div>
              <h3 class="m-title">Ghi Nhận Kết Quả {{ selectedAppointment.formatted_id }}</h3>
              <p class="m-sub">Lưu trữ kết quả tư vấn để tiếp tục quy trình chăm sóc khách hàng.</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeCompleteModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form @submit.prevent="submitCompleteForm">
          <div class="modal-body-pad">
            <div class="form-group">
              <label>Trạng thái kết thúc <span class="required">*</span></label>
              <select v-model="completeStatus" required>
                <option value="completed">Đã hoàn tất (Đã gặp & tư vấn)</option>
                <option value="no_show">Khách vắng mặt (Khách không đến / Hủy sát giờ)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Tóm tắt kết quả tư vấn <span class="required">*</span></label>
              <textarea 
                v-model="resultSummary" 
                rows="4" 
                required 
                placeholder="Ví dụ: Khách hàng hài lòng với trải nghiệm lái thử VF9, dự kiến đặt cọc thuê 1 tuần vào cuối tháng; hoặc Khách muốn cân nhắc dòng xe khác có giá thấp hơn..."
              ></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-outline" @click="closeCompleteModal">Bỏ qua</button>
            <button type="submit" class="btn-success" :disabled="formSubmitting || !resultSummary.trim()">
              <i class="fa-solid fa-spinner fa-spin" v-if="formSubmitting"></i>
              <span v-else>Lưu kết quả tư vấn</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import baseRequest from '../../../core/baseRequest';

const route = useRoute();

// States
const activeView = ref('list'); // 'list', 'day', 'week'
const loading = ref(false);
const formSubmitting = ref(false);

const appointments = ref([]);
const calendarAppointments = ref([]);
const staffList = ref([]);
const carList = ref([]);

const counts = reactive({
  total: 0,
  scheduled: 0,
  completed: 0,
  cancelled: 0,
  no_show: 0,
  today: 0,
  my_assigned: 0,
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

// Filters
const searchKeyword = ref('');
const filterStatus = ref('all');
const filterAssignedTo = ref('all');
const filterCarId = ref('all');
const filterMeetingType = ref('all');
const filterFromDate = ref('');
const filterToDate = ref('');

// Calendar Navigation
const calendarAnchor = ref(new Date());

// Modals
const showEditorModal = ref(false);
const isEditing = ref(false);
const editingAppointmentId = ref(null);
const conflictErrorMessage = ref('');

const showDetailModal = ref(false);
const selectedAppointment = ref(null);

const showCancelModal = ref(false);
const cancelReason = ref('');

const showCompleteModal = ref(false);
const completeStatus = ref('completed');
const resultSummary = ref('');

// Form Data
const form = reactive({
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  assigned_to: '',
  car_id: null,
  user_id: null,
  contact_id: null,
  meeting_type: 'viewing',
  location: 'Showroom VinFast',
  start_time: '',
  end_time: '',
  reminder_minutes: 60,
  notes: '',
});

// Computed
const calendarAnchorDate = computed(() => {
  const d = calendarAnchor.value;
  return d.toISOString().split('T')[0];
});

const isCalendarAnchorToday = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return calendarAnchorDate.value === today;
});

const isTodayFilterActive = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return filterFromDate.value === today && filterToDate.value === today;
});

const calendarPeriodLabel = computed(() => {
  const d = calendarAnchor.value;
  if (activeView.value === 'day') {
    return `Ngày ${d.getDate()} tháng ${d.getMonth() + 1}, ${d.getFullYear()}`;
  }
  // Tuần: Thứ Hai đến Chủ Nhật
  const day = d.getDay();
  const diffToMonday = (day === 0 ? -6 : 1) - day;
  const monday = new Date(d);
  monday.setDate(d.getDate() + diffToMonday);
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);

  return `${monday.getDate()}/${monday.getMonth() + 1} - ${sunday.getDate()}/${sunday.getMonth() + 1}/${sunday.getFullYear()}`;
});

// Week Days Columns Data
const weekDays = computed(() => {
  const d = calendarAnchor.value;
  const day = d.getDay();
  const diffToMonday = (day === 0 ? -6 : 1) - day;
  const monday = new Date(d);
  monday.setDate(d.getDate() + diffToMonday);

  const days = [];
  const dayNames = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'];
  const todayIso = new Date().toISOString().split('T')[0];

  for (let i = 0; i < 7; i++) {
    const cur = new Date(monday);
    cur.setDate(monday.getDate() + i);
    const iso = cur.toISOString().split('T')[0];

    // Lọc sự kiện thuộc ngày này
    const dayEvents = calendarAppointments.value.filter(apt => {
      const aptDate = apt.start_time ? apt.start_time.split('T')[0].split(' ')[0] : '';
      return aptDate === iso;
    });

    days.push({
      dateStr: iso,
      dayName: dayNames[i],
      dayNumber: cur.getDate(),
      isToday: iso === todayIso,
      isWeekend: i >= 5,
      appointments: dayEvents,
    });
  }

  return days;
});

// Day View Computed Data
const dayViewAppointments = computed(() => {
  const iso = calendarAnchorDate.value;
  return calendarAppointments.value.filter(apt => {
    const aptDate = apt.start_time ? apt.start_time.split('T')[0].split(' ')[0] : '';
    return aptDate === iso;
  });
});

const dayScheduledCount = computed(() => dayViewAppointments.value.filter(a => a.status === 'scheduled').length);
const dayCompletedCount = computed(() => dayViewAppointments.value.filter(a => a.status === 'completed').length);

const currentDayDateNumber = computed(() => calendarAnchor.value.getDate());
const currentDayMonthYear = computed(() => `Thg ${calendarAnchor.value.getMonth() + 1}, ${calendarAnchor.value.getFullYear()}`);
const currentDayWeekday = computed(() => {
  const map = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  return map[calendarAnchor.value.getDay()];
});

// Methods: Data Fetching
const fetchAppointments = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: searchKeyword.value.trim() || undefined,
      status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
      assigned_to: filterAssignedTo.value !== 'all' ? filterAssignedTo.value : undefined,
      car_id: filterCarId.value !== 'all' ? filterCarId.value : undefined,
      meeting_type: filterMeetingType.value !== 'all' ? filterMeetingType.value : undefined,
      from_date: filterFromDate.value || undefined,
      to_date: filterToDate.value || undefined,
    };

    const res = await baseRequest.get('/api/admin/appointments', { params });
    if (res.data?.status === 1) {
      appointments.value = res.data.data.data || [];
      pagination.current_page = res.data.data.current_page;
      pagination.last_page = res.data.data.last_page;
      pagination.total = res.data.data.total;
      pagination.from = res.data.data.from;
      pagination.to = res.data.data.to;

      if (res.data.counts) {
        Object.assign(counts, res.data.counts);
      }
    }
  } catch (error) {
    console.error('Lỗi tải danh sách lịch hẹn:', error);
  } finally {
    loading.value = false;
  }
};

const fetchCalendarAppointments = async () => {
  loading.value = true;
  try {
    const params = {
      view_type: activeView.value === 'day' ? 'day' : 'week',
      date: calendarAnchorDate.value,
      assigned_to: filterAssignedTo.value !== 'all' ? filterAssignedTo.value : undefined,
      car_id: filterCarId.value !== 'all' ? filterCarId.value : undefined,
      status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
    };

    const res = await baseRequest.get('/api/admin/appointments/calendar', { params });
    if (res.data?.status === 1) {
      calendarAppointments.value = res.data.data || [];
    }
  } catch (error) {
    console.error('Lỗi tải lịch:', error);
  } finally {
    loading.value = false;
  }
};

const fetchStaffList = async () => {
  try {
    const res = await baseRequest.get('/api/admin/appointments/staff-list');
    if (res.data?.status === 1) {
      staffList.value = res.data.data || [];
    }
  } catch (error) {
    console.error('Lỗi tải danh sách nhân viên:', error);
  }
};

const fetchCarList = async () => {
  try {
    const res = await baseRequest.get('/api/cars');
    if (res.data) {
      carList.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
    }
  } catch (error) {
    console.error('Lỗi tải danh sách xe:', error);
  }
};

const refreshData = () => {
  if (activeView.value === 'list') {
    fetchAppointments();
  } else {
    fetchCalendarAppointments();
  }
};

const switchView = (view) => {
  activeView.value = view;
  if (view === 'list') {
    fetchAppointments();
  } else {
    fetchCalendarAppointments();
  }
};

// Calendar Navigation Methods
const navigateCalendarPrev = () => {
  const d = new Date(calendarAnchor.value);
  if (activeView.value === 'day') {
    d.setDate(d.getDate() - 1);
  } else {
    d.setDate(d.getDate() - 7);
  }
  calendarAnchor.value = d;
  fetchCalendarAppointments();
};

const navigateCalendarNext = () => {
  const d = new Date(calendarAnchor.value);
  if (activeView.value === 'day') {
    d.setDate(d.getDate() + 1);
  } else {
    d.setDate(d.getDate() + 7);
  }
  calendarAnchor.value = d;
  fetchCalendarAppointments();
};

const jumpCalendarToday = () => {
  calendarAnchor.value = new Date();
  fetchCalendarAppointments();
};

const onCalendarDateChange = (e) => {
  if (e.target.value) {
    calendarAnchor.value = new Date(e.target.value);
    fetchCalendarAppointments();
  }
};

// Search & Filter Handlers
let searchDebounceTimeout = null;
const onSearchInput = () => {
  clearTimeout(searchDebounceTimeout);
  searchDebounceTimeout = setTimeout(() => {
    pagination.current_page = 1;
    fetchAppointments();
  }, 350);
};

const clearSearch = () => {
  searchKeyword.value = '';
  pagination.current_page = 1;
  fetchAppointments();
};

const applyKpiFilter = (status) => {
  filterStatus.value = status;
  filterFromDate.value = '';
  filterToDate.value = '';
  pagination.current_page = 1;
  fetchAppointments();
};

const filterTodayOnly = () => {
  const today = new Date().toISOString().split('T')[0];
  filterFromDate.value = today;
  filterToDate.value = today;
  filterStatus.value = 'all';
  pagination.current_page = 1;
  fetchAppointments();
};

const resetAllFilters = () => {
  searchKeyword.value = '';
  filterStatus.value = 'all';
  filterAssignedTo.value = 'all';
  filterCarId.value = 'all';
  filterMeetingType.value = 'all';
  filterFromDate.value = '';
  filterToDate.value = '';
  pagination.current_page = 1;
  fetchAppointments();
};

const changePage = (page) => {
  pagination.current_page = page;
  fetchAppointments();
};

// Modal Operations
const openCreateModal = (prefill = {}) => {
  isEditing.value = false;
  editingAppointmentId.value = null;
  conflictErrorMessage.value = '';

  // Thiết lập thời gian mặc định: 09:00 - 10:00 ngày mai hoặc ngày được chỉ định
  const baseDate = prefill.date ? new Date(prefill.date) : new Date(Date.now() + 86400000);
  const yyyy = baseDate.getFullYear();
  const mm = String(baseDate.getMonth() + 1).padStart(2, '0');
  const dd = String(baseDate.getDate()).padStart(2, '0');
  const defaultDate = `${yyyy}-${mm}-${dd}`;

  Object.assign(form, {
    customer_name: prefill.customer_name || '',
    customer_phone: prefill.customer_phone || '',
    customer_email: prefill.customer_email || '',
    assigned_to: prefill.assigned_to || (staffList.value[0]?.id || ''),
    car_id: prefill.car_id || null,
    user_id: prefill.user_id || null,
    contact_id: prefill.contact_id || null,
    meeting_type: prefill.meeting_type || 'viewing',
    location: prefill.location || 'Showroom VinFast',
    start_time: `${defaultDate}T09:00`,
    end_time: `${defaultDate}T10:00`,
    reminder_minutes: 60,
    notes: prefill.notes || '',
  });

  showEditorModal.value = true;
};

const openEditModal = (apt) => {
  isEditing.value = true;
  editingAppointmentId.value = apt.id;
  conflictErrorMessage.value = '';

  const formatIsoInput = (dt) => {
    if (!dt) return '';
    const d = new Date(dt);
    const YYYY = d.getFullYear();
    const MM = String(d.getMonth() + 1).padStart(2, '0');
    const DD = String(d.getDate()).padStart(2, '0');
    const HH = String(d.getHours()).padStart(2, '0');
    const II = String(d.getMinutes()).padStart(2, '0');
    return `${YYYY}-${MM}-${DD}T${HH}:${II}`;
  };

  Object.assign(form, {
    customer_name: apt.customer_name,
    customer_phone: apt.customer_phone,
    customer_email: apt.customer_email || '',
    assigned_to: apt.assigned_to,
    car_id: apt.car_id,
    user_id: apt.user_id,
    contact_id: apt.contact_id,
    meeting_type: apt.meeting_type || 'viewing',
    location: apt.location,
    start_time: formatIsoInput(apt.start_time),
    end_time: formatIsoInput(apt.end_time),
    reminder_minutes: apt.reminder_minutes || 60,
    notes: apt.notes || '',
  });

  showEditorModal.value = true;
};

const closeEditorModal = () => {
  showEditorModal.value = false;
  conflictErrorMessage.value = '';
};

const onStartTimeChange = () => {
  if (form.start_time && !isEditing.value) {
    const start = new Date(form.start_time);
    const end = new Date(start.getTime() + 60 * 60 * 1000); // Mặc định 1 tiếng
    const YYYY = end.getFullYear();
    const MM = String(end.getMonth() + 1).padStart(2, '0');
    const DD = String(end.getDate()).padStart(2, '0');
    const HH = String(end.getHours()).padStart(2, '0');
    const II = String(end.getMinutes()).padStart(2, '0');
    form.end_time = `${YYYY}-${MM}-${DD}T${HH}:${II}`;
  }
};

const submitAppointmentForm = async () => {
  conflictErrorMessage.value = '';
  formSubmitting.value = true;

  try {
    const payload = {
      customer_name: form.customer_name.trim(),
      customer_phone: form.customer_phone.trim(),
      customer_email: form.customer_email ? form.customer_email.trim() : null,
      assigned_to: parseInt(form.assigned_to),
      car_id: form.car_id ? parseInt(form.car_id) : null,
      user_id: form.user_id ? parseInt(form.user_id) : null,
      contact_id: form.contact_id ? parseInt(form.contact_id) : null,
      meeting_type: form.meeting_type,
      location: form.location.trim(),
      start_time: form.start_time.replace('T', ' ') + ':00',
      end_time: form.end_time.replace('T', ' ') + ':00',
      reminder_minutes: parseInt(form.reminder_minutes) || 60,
      notes: form.notes ? form.notes.trim() : null,
    };

    let res;
    if (isEditing.value) {
      res = await baseRequest.put(`/api/admin/appointments/${editingAppointmentId.value}`, payload);
    } else {
      res = await baseRequest.post('/api/admin/appointments', payload);
    }

    if (res.data?.status === 1) {
      closeEditorModal();
      refreshData();
    } else {
      conflictErrorMessage.value = res.data?.message || 'Không thể lưu lịch hẹn!';
    }
  } catch (error) {
    if (error.response?.status === 422) {
      conflictErrorMessage.value = error.response.data?.message || 'Thời gian hoặc chuyên viên bị xung đột!';
    } else {
      conflictErrorMessage.value = error.response?.data?.message || 'Có lỗi xảy ra trong quá trình xử lý!';
    }
  } finally {
    formSubmitting.value = false;
  }
};

// Detail Modal
const openDetailModal = async (apt) => {
  selectedAppointment.value = apt;
  showDetailModal.value = true;
  try {
    const res = await baseRequest.get(`/api/admin/appointments/${apt.id}`);
    if (res.data?.status === 1) {
      selectedAppointment.value = res.data.data;
    }
  } catch (e) {
    console.error('Lỗi chi tiết lịch hẹn:', e);
  }
};

const closeDetailModal = () => {
  showDetailModal.value = false;
};

// Cancel Modal
const openCancelModal = (apt) => {
  selectedAppointment.value = apt;
  cancelReason.value = '';
  showCancelModal.value = true;
};

const closeCancelModal = () => {
  showCancelModal.value = false;
};

const submitCancelForm = async () => {
  if (!cancelReason.value.trim()) return;
  formSubmitting.value = true;
  try {
    const res = await baseRequest.post(`/api/admin/appointments/${selectedAppointment.value.id}/cancel`, {
      cancel_reason: cancelReason.value.trim(),
    });

    if (res.data?.status === 1) {
      closeCancelModal();
      closeDetailModal();
      refreshData();
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Không thể hủy lịch hẹn!');
  } finally {
    formSubmitting.value = false;
  }
};

// Complete Modal
const openCompleteModal = (apt) => {
  selectedAppointment.value = apt;
  completeStatus.value = 'completed';
  resultSummary.value = '';
  showCompleteModal.value = true;
};

const closeCompleteModal = () => {
  showCompleteModal.value = false;
};

const submitCompleteForm = async () => {
  if (!resultSummary.value.trim()) return;
  formSubmitting.value = true;
  try {
    const res = await baseRequest.post(`/api/admin/appointments/${selectedAppointment.value.id}/complete`, {
      status: completeStatus.value,
      result_summary: resultSummary.value.trim(),
    });

    if (res.data?.status === 1) {
      closeCompleteModal();
      closeDetailModal();
      refreshData();
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Không thể lưu kết quả tư vấn!');
  } finally {
    formSubmitting.value = false;
  }
};

// In-app Reminder Trigger
const triggerRemind = async (apt) => {
  try {
    const res = await baseRequest.post(`/api/admin/appointments/${apt.id}/remind`);
    if (res.data?.status === 1) {
      alert('Đã phát hành thông báo nhắc hẹn nội bộ thành công!');
      refreshData();
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Không thể kích hoạt nhắc hẹn!');
  }
};

// Helpers & Formatting
const formatDate = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const formatDateTime = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')} ${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const formatTimeRange = (start, end) => {
  if (!start || !end) return '';
  const s = new Date(start);
  const e = new Date(end);
  const sH = String(s.getHours()).padStart(2, '0');
  const sM = String(s.getMinutes()).padStart(2, '0');
  const eH = String(e.getHours()).padStart(2, '0');
  const eM = String(e.getMinutes()).padStart(2, '0');
  return `${sH}:${sM} - ${eH}:${eM}`;
};

const formatHourOnly = (dt) => {
  if (!dt) return '';
  const d = new Date(dt);
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '0 đ';
  return Number(amount).toLocaleString('vi-VN') + ' đ';
};

const getMeetingTypeIcon = (type) => {
  if (type === 'viewing') return 'fa-car-side';
  if (type === 'consultation') return 'fa-comments';
  return 'fa-video';
};

const getInitials = (name) => {
  if (!name) return 'A';
  const parts = name.trim().split(' ');
  return parts[parts.length - 1].charAt(0).toUpperCase();
};

const getCarImage = (car) => {
  if (!car) return '/assets/images/default-car.png';
  if (car.image) return car.image;
  if (Array.isArray(car.images) && car.images.length > 0) return car.images[0];
  return '/assets/images/default-car.png';
};

// Lifecycle & Route Prefill handling
onMounted(async () => {
  await Promise.all([fetchStaffList(), fetchCarList()]);
  await fetchAppointments();

  // Kiểm tra nếu được redirect từ Contacts hoặc Customer profile với query params
  if (route.query.create === '1' || route.query.contact_id || route.query.user_id) {
    openCreateModal({
      customer_name: route.query.customer_name || '',
      customer_phone: route.query.customer_phone || '',
      customer_email: route.query.customer_email || '',
      car_id: route.query.car_id ? parseInt(route.query.car_id) : null,
      user_id: route.query.user_id ? parseInt(route.query.user_id) : null,
      contact_id: route.query.contact_id ? parseInt(route.query.contact_id) : null,
      location: route.query.location || 'Showroom VinFast',
      notes: route.query.notes || '',
    });
  }
});
</script>

<style scoped>
.appointments-page {
  padding: 1.5rem;
  max-width: 1600px;
  margin: 0 auto;
}

/* Page Header */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(16, 185, 129, 0.2));
  border: 1px solid rgba(59, 130, 246, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #60a5fa;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
}

.page-title span {
  color: #60a5fa;
}

.page-subtitle {
  color: #94a3b8;
  font-size: 0.875rem;
  margin: 0.25rem 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* View Switch Tabs */
.view-switch-tabs {
  display: flex;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 4px;
}

.switch-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: #94a3b8;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.switch-btn:hover {
  color: #fff;
}

.switch-btn.active {
  background: rgba(59, 130, 246, 0.25);
  color: #60a5fa;
  font-weight: 600;
}

.btn-refresh {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-refresh:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  border: none;
  padding: 0 1.25rem;
  height: 38px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
  transform: translateY(-1px);
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.kpi-card {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  transition: all 0.25s ease;
}

.kpi-card:hover, .kpi-card.active {
  border-color: rgba(59, 130, 246, 0.5);
  background: rgba(30, 41, 59, 0.7);
  transform: translateY(-2px);
}

.kpi-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.kpi-icon-box.total { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.kpi-icon-box.scheduled { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
.kpi-icon-box.completed { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.kpi-icon-box.cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; }
.kpi-icon-box.no-show { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.kpi-icon-box.today { background: rgba(168, 85, 247, 0.15); color: #c084fc; }

.kpi-info {
  display: flex;
  flex-direction: column;
}

.kpi-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #fff;
  margin-top: 2px;
}

/* Filter Toolbar */
.filter-toolbar {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 280px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-size: 0.875rem;
}

.search-box input {
  width: 100%;
  padding: 9px 36px 9px 38px;
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.search-box input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.clear-search-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.select-wrapper select {
  padding: 8px 12px;
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.875rem;
  outline: none;
  cursor: pointer;
}

.btn-outline-sm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  color: #cbd5e1;
  font-size: 0.8125rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline-sm:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

/* Data Table */
.table-card {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th {
  padding: 12px 16px;
  background: rgba(30, 41, 59, 0.5);
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  color: #e2e8f0;
  font-size: 0.875rem;
  vertical-align: middle;
}

.data-table tr:hover {
  background: rgba(30, 41, 59, 0.35);
}

.row-cancelled {
  opacity: 0.65;
}

.apt-code-badge {
  display: inline-block;
  font-weight: 700;
  color: #60a5fa;
  cursor: pointer;
  padding: 3px 8px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 6px;
  font-size: 0.8125rem;
}

.apt-code-badge:hover {
  text-decoration: underline;
}

.sub-link-meta {
  font-size: 0.75rem;
  color: #a78bfa;
  margin-top: 4px;
}

.customer-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.customer-name {
  font-weight: 600;
  color: #fff;
  cursor: pointer;
}

.customer-name:hover {
  color: #60a5fa;
}

.customer-phone {
  font-size: 0.75rem;
  color: #94a3b8;
}

.badge-cus {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.6875rem;
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  padding: 1px 6px;
  border-radius: 4px;
  width: fit-content;
  margin-top: 2px;
}

.meeting-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meeting-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 4px;
  width: fit-content;
  font-weight: 500;
}

.meeting-type-badge.viewing { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.meeting-type-badge.consultation { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
.meeting-type-badge.online { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }

.location-text {
  font-size: 0.75rem;
  color: #94a3b8;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.car-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.car-mini-thumb {
  width: 48px;
  height: 32px;
  object-fit: cover;
  border-radius: 4px;
  background: #1e293b;
}

.car-meta {
  display: flex;
  flex-direction: column;
}

.car-name {
  font-weight: 600;
  color: #fff;
  font-size: 0.8125rem;
}

.car-plate {
  font-size: 0.6875rem;
  color: #94a3b8;
}

.badge-conflict-warning {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.6875rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
  padding: 1px 6px;
  border-radius: 4px;
  margin-top: 2px;
}

.staff-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.staff-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.staff-name {
  font-weight: 600;
  color: #e2e8f0;
  font-size: 0.8125rem;
  display: block;
}

.staff-role {
  font-size: 0.6875rem;
  color: #94a3b8;
}

.time-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.date-str {
  font-weight: 600;
  color: #fff;
  font-size: 0.8125rem;
}

.hours-range {
  font-size: 0.75rem;
  color: #94a3b8;
}

.duration-label {
  font-size: 0.6875rem;
  color: #64748b;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.primary { background: rgba(59, 130, 246, 0.18); color: #60a5fa; }
.status-badge.success { background: rgba(16, 185, 129, 0.18); color: #34d399; }
.status-badge.danger { background: rgba(239, 68, 68, 0.18); color: #f87171; }
.status-badge.warning { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }

/* Action Buttons */
.action-buttons {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.act-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(30, 41, 59, 0.7);
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.8125rem;
}

.act-btn:hover {
  transform: translateY(-1px);
}

.btn-view:hover { color: #60a5fa; background: rgba(59, 130, 246, 0.2); }
.btn-edit:hover { color: #fbbf24; background: rgba(245, 158, 11, 0.2); }
.btn-complete:hover { color: #34d399; background: rgba(16, 185, 129, 0.2); }
.btn-remind:hover { color: #c084fc; background: rgba(168, 85, 247, 0.2); }
.btn-cancel:hover { color: #f87171; background: rgba(239, 68, 68, 0.2); }

/* Pagination Footer */
.pagination-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 0.8125rem;
  color: #94a3b8;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-nav-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-nav-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Loading & Empty States */
.loading-state, .empty-state, .cal-loading {
  padding: 3rem;
  text-align: center;
  color: #94a3b8;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.loading-icon, .empty-icon {
  font-size: 2.5rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.btn-primary-sm, .btn-success-sm, .btn-danger-sm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  color: #fff;
}

.btn-primary-sm { background: #2563eb; }
.btn-success-sm { background: #10b981; }
.btn-danger-sm { background: #ef4444; }

/* Calendar Toolbar */
.calendar-toolbar {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.nav-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cal-nav-btn {
  width: 34px;
  height: 34px;
  border-radius: 6px;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #e2e8f0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cal-today-btn {
  padding: 6px 14px;
  border-radius: 6px;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  font-size: 0.8125rem;
  cursor: pointer;
}

.cal-today-btn.active {
  background: rgba(59, 130, 246, 0.25);
  color: #60a5fa;
  border-color: #3b82f6;
  font-weight: 600;
}

.period-display {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 8px;
  font-weight: 600;
  color: #fff;
  font-size: 0.9375rem;
}

.cal-date-input {
  padding: 6px 10px;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #e2e8f0;
  font-size: 0.8125rem;
  cursor: pointer;
}

.cal-filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Week Grid */
.week-grid-card {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1rem;
  overflow-x: auto;
}

.week-columns-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(180px, 1fr));
  gap: 12px;
}

.week-day-col {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  min-height: 480px;
  display: flex;
  flex-direction: column;
}

.week-day-col.is-today {
  border-color: rgba(59, 130, 246, 0.4);
  background: rgba(30, 41, 59, 0.6);
}

.day-col-header {
  padding: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.day-name {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}

.day-number {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
}

.day-number.highlight {
  color: #60a5fa;
  background: rgba(59, 130, 246, 0.2);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.day-count-badge {
  font-size: 0.6875rem;
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  padding: 1px 6px;
  border-radius: 10px;
}

.day-events-list {
  padding: 8px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.empty-day-slot {
  text-align: center;
  padding: 2rem 0.5rem;
  color: #64748b;
  font-size: 0.75rem;
}

.appointment-card-chip {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-left: 3px solid #3b82f6;
  border-radius: 6px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.appointment-card-chip:hover {
  transform: translateY(-2px);
  border-color: rgba(59, 130, 246, 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.appointment-card-chip.completed { border-left-color: #10b981; }
.appointment-card-chip.cancelled { border-left-color: #ef4444; opacity: 0.6; }
.appointment-card-chip.no_show { border-left-color: #f59e0b; }

.chip-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.6875rem;
  color: #94a3b8;
  margin-bottom: 4px;
}

.chip-status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #3b82f6;
}

.chip-status-dot.completed { background: #10b981; }
.chip-status-dot.cancelled { background: #ef4444; }
.chip-status-dot.no_show { background: #f59e0b; }

.chip-customer {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chip-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.6875rem;
  color: #94a3b8;
}

.chip-warning {
  margin-top: 4px;
  font-size: 0.6875rem;
  color: #fbbf24;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Day Timeline View */
.day-view-card {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.5rem;
}

.day-timeline-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 2rem;
}

.day-meta-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.big-date-badge {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
}

.b-day {
  font-size: 3.5rem;
  font-weight: 800;
  color: #60a5fa;
  line-height: 1;
  display: block;
}

.b-month {
  font-size: 1rem;
  color: #cbd5e1;
  display: block;
  margin-top: 4px;
}

.b-weekday {
  font-size: 0.875rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
}

.day-stats-box {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.d-stat {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.d-val { font-weight: 700; font-size: 1.125rem; color: #fff; }
.d-lbl { font-size: 0.8125rem; color: #94a3b8; }

.empty-timeline {
  text-align: center;
  padding: 4rem 1rem;
  color: #64748b;
}

.empty-timeline i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.timeline-items-stream {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.timeline-appointment-item {
  display: flex;
  gap: 1.25rem;
}

.item-time-col {
  width: 90px;
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-top: 4px;
}

.t-start { font-weight: 700; color: #fff; font-size: 1rem; }
.t-end { font-size: 0.8125rem; color: #94a3b8; }
.t-dur { font-size: 0.6875rem; color: #64748b; }

.item-body-card {
  flex: 1;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1rem 1.25rem;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.head-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.apt-code { font-weight: 700; color: #60a5fa; font-size: 0.8125rem; }
.meeting-pill { font-size: 0.6875rem; padding: 2px 6px; border-radius: 4px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.status-pill { font-size: 0.6875rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.conflict-pill { font-size: 0.6875rem; padding: 2px 6px; border-radius: 4px; background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

.customer-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 0.5rem;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  font-size: 0.8125rem;
  color: #94a3b8;
  margin-bottom: 0.5rem;
}

.meta-row span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-row strong { color: #e2e8f0; }

.notes-preview, .result-preview {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
  padding: 6px 10px;
  border-radius: 6px;
}

.notes-preview { background: rgba(255, 255, 255, 0.04); color: #cbd5e1; }
.result-preview { background: rgba(16, 185, 129, 0.1); color: #34d399; }

.btn-act-icon {
  width: 28px;
  height: 28px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.06);
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  margin-left: 4px;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-box {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.editor-modal { width: 720px; }
.detail-modal { width: 760px; }
.small-modal { width: 500px; }

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.m-title-box {
  display: flex;
  align-items: center;
  gap: 12px;
}

.m-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.m-icon.icon-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; }
.m-icon.icon-success { background: rgba(16, 185, 129, 0.15); color: #34d399; }

.m-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
}

.m-sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 2px 0 0;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
}

.modal-body-form, .detail-modal-body, .modal-body-pad {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-section-title {
  grid-column: 1 / -1;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #60a5fa;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding-bottom: 4px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.2);
  margin-top: 0.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 0.8125rem;
  color: #cbd5e1;
  font-weight: 500;
}

.required { color: #f87171; }

.form-group input, .form-group select, .form-group textarea {
  padding: 9px 12px;
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  outline: none;
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.conflict-alert-box {
  display: flex;
  gap: 12px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  border-radius: 8px;
  padding: 1rem;
  color: #fca5a5;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.conflict-alert-box i { font-size: 1.25rem; color: #f87171; margin-top: 2px; }

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-outline {
  padding: 8px 16px;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  color: #cbd5e1;
  font-weight: 600;
  cursor: pointer;
}

.btn-danger {
  padding: 8px 16px;
  background: #dc2626;
  border: none;
  border-radius: 8px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

.btn-success {
  padding: 8px 16px;
  background: #10b981;
  border: none;
  border-radius: 8px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

/* Detail Modal Specifics */
.conflict-notice-banner {
  display: flex;
  gap: 12px;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.4);
  border-radius: 8px;
  padding: 0.875rem 1rem;
  color: #fde68a;
  font-size: 0.8125rem;
  margin-bottom: 1.25rem;
}

.conflict-notice-banner i { font-size: 1.25rem; color: #fbbf24; }

.detail-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.info-card {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 1rem;
}

.info-card h4 {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  margin: 0 0 0.5rem;
}

.info-card p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
  color: #e2e8f0;
}

.car-interest-box {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.car-int-thumb {
  width: 96px;
  height: 64px;
  object-fit: cover;
  border-radius: 6px;
}

.car-int-meta h4 {
  margin: 0 0 4px;
  font-size: 1rem;
  color: #fff;
}

.car-int-meta p {
  margin: 2px 0;
  font-size: 0.8125rem;
  color: #94a3b8;
}

.price-txt {
  color: #34d399 !important;
  font-weight: 600;
}

.contact-link-box, .notes-box, .result-box, .cancel-box {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.contact-link-box h4, .notes-box h4, .result-box h4, .cancel-box h4 {
  margin: 0 0 0.5rem;
  font-size: 0.8125rem;
  color: #60a5fa;
}

.cancel-box h4 { color: #f87171; }
.result-box h4 { color: #34d399; }

.detail-footer {
  justify-content: space-between;
}

.right-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .day-timeline-layout {
    grid-template-columns: 1fr;
  }
  .week-columns-grid {
    grid-template-columns: repeat(7, 160px);
  }
}

@media (max-width: 768px) {
  .form-grid, .detail-info-grid {
    grid-template-columns: 1fr;
  }
  .editor-modal, .detail-modal {
    width: 95vw;
  }
}
</style>
