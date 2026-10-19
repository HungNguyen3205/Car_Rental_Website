<template>
  <div class="blog-page">
    <!-- Hero Header -->
    <section class="blog-hero">
      <div class="app-container hero-container">
        <div class="hero-badge">
          <i class="fa-solid fa-sparkles"></i>
          <span>Tin Tức & Cẩm Nang Thuê Xe</span>
        </div>
        <h1 class="hero-title">Kinh Nghiệm & <span>Hành Trình Lái Xe</span></h1>
        <p class="hero-subtitle">
          Khám phá cẩm nang lái xe điện VinFast, kinh nghiệm du lịch tự lái xuyên Việt và các mẹo thuê xe tiết kiệm, an toàn.
        </p>

        <!-- Search Bar -->
        <div class="hero-search-box">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Tìm kiếm bài viết, cẩm nang, mẹo lái xe..."
            @keyup.enter="handleSearch"
          />
          <button v-if="searchQuery" class="btn-clear" @click="clearSearch" title="Xóa">
            <i class="fa-solid fa-xmark"></i>
          </button>
          <button class="btn-search" @click="handleSearch">
            <span>Tìm kiếm</span>
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- Main Content Section -->
    <section class="blog-content-section app-container">
      <!-- Category Filter Pills Bar -->
      <div class="categories-filter-bar" v-if="categories.length > 0">
        <button 
          class="cat-pill" 
          :class="{ 'active': selectedCategory === '' }" 
          @click="selectCategory('')"
        >
          <span>Tất cả</span>
          <span class="cat-count">{{ totalPostsCount }}</span>
        </button>

        <button 
          v-for="cat in categories" 
          :key="cat.id" 
          class="cat-pill" 
          :class="{ 'active': selectedCategory === cat.slug }" 
          @click="selectCategory(cat.slug)"
        >
          <span>{{ cat.name }}</span>
          <span class="cat-count" v-if="cat.posts_count">{{ cat.posts_count }}</span>
        </button>
      </div>

      <!-- Articles Grid -->
      <div v-if="loading" class="loading-state">
        <i class="fa-solid fa-spinner fa-spin"></i>
        <p>Đang tải danh sách bài viết...</p>
      </div>

      <div v-else-if="posts.length > 0" class="articles-grid">
        <article v-for="post in posts" :key="post.id" class="article-card card">
          <router-link :to="`/blog/${post.slug}`" class="card-thumb-wrap">
            <img 
              :src="post.featured_image || '/assets/images/default-car.png'" 
              :alt="post.title" 
              class="card-thumb"
              @error="$event.target.src = '/assets/images/default-car.png'"
            />
            <span v-if="post.category" class="card-cat-badge">{{ post.category.name }}</span>
          </router-link>

          <div class="card-body">
            <div class="card-meta">
              <span><i class="fa-regular fa-calendar"></i> {{ formatDate(post.published_at) }}</span>
              <span class="meta-dot">•</span>
              <span><i class="fa-regular fa-clock"></i> {{ post.reading_time || 1 }} phút đọc</span>
            </div>

            <h3 class="card-title">
              <router-link :to="`/blog/${post.slug}`">{{ post.title }}</router-link>
            </h3>

            <p class="card-summary">{{ post.summary || 'Nhấn để xem chi tiết bài viết cẩm nang và hướng dẫn...' }}</p>

            <div class="card-footer">
              <div class="author-info">
                <div class="author-avatar">{{ (post.author?.ho_ten || 'A').charAt(0).toUpperCase() }}</div>
                <span class="author-name">{{ post.author?.ho_ten || 'Ban Biên Tập' }}</span>
              </div>
              <router-link :to="`/blog/${post.slug}`" class="read-more-link">
                <span>Đọc tiếp</span>
                <i class="fa-solid fa-arrow-right-long"></i>
              </router-link>
            </div>
          </div>
        </article>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state card">
        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
        <h3>Chưa có bài viết nào phù hợp</h3>
        <p>Không tìm thấy bài viết nào theo từ khóa hoặc chuyên mục bạn chọn. Thử tìm kiếm với từ khóa khác.</p>
        <button class="btn-reset-filter" @click="resetFilters">
          <i class="fa-solid fa-rotate-left"></i> Xem tất cả bài viết
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="blog-pagination">
        <button 
          class="btn-page-nav" 
          :disabled="pagination.current_page <= 1" 
          @click="changePage(pagination.current_page - 1)"
        >
          <i class="fa-solid fa-chevron-left"></i> Trang trước
        </button>

        <div class="page-numbers">
          <button 
            v-for="p in visiblePages" 
            :key="p" 
            class="page-number-btn" 
            :class="{ 'active': p === pagination.current_page }" 
            @click="changePage(p)"
          >
            {{ p }}
          </button>
        </div>

        <button 
          class="btn-page-nav" 
          :disabled="pagination.current_page >= pagination.last_page" 
          @click="changePage(pagination.current_page + 1)"
        >
          Trang sau <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';

const route = useRoute();
const router = useRouter();

const posts = ref([]);
const categories = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const selectedCategory = ref('');

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const totalPostsCount = computed(() => {
  return categories.value.reduce((sum, c) => sum + (c.posts_count || 0), 0);
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

const fetchPosts = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 9,
    };
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }
    if (selectedCategory.value) {
      params.category = selectedCategory.value;
    }

    const res = await baseRequest.get('/posts', { params });
    if (res.data && res.data.status === 1) {
      posts.value = res.data.data.data || [];
      pagination.current_page = res.data.data.current_page || 1;
      pagination.last_page = res.data.data.last_page || 1;
      pagination.total = res.data.data.total || 0;
    }
  } catch (err) {
    console.error('Lỗi khi tải bài viết:', err);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const res = await baseRequest.get('/posts/categories');
    if (res.data && res.data.status === 1) {
      categories.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Lỗi khi tải chuyên mục:', err);
  }
};

const selectCategory = (slug) => {
  selectedCategory.value = slug;
  fetchPosts(1);
};

const handleSearch = () => {
  fetchPosts(1);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchPosts(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = '';
  fetchPosts(1);
};

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page) return;
  fetchPosts(page);
  window.scrollTo({ top: 300, behavior: 'smooth' });
};

const formatDate = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

onMounted(() => {
  if (route.query.category) {
    selectedCategory.value = route.query.category;
  }
  if (route.query.search) {
    searchQuery.value = route.query.search;
  }
  fetchCategories();
  fetchPosts(1);
});
</script>

<style scoped>
.blog-page {
  background: #f8fafc;
  min-height: 100vh;
  padding-bottom: 60px;
}

/* Hero Section */
.blog-hero {
  background: radial-gradient(circle at 50% 0%, rgba(2, 132, 199, 0.12) 0%, transparent 60%),
              linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  padding: 60px 0 40px;
  text-align: center;
  border-bottom: 1px solid #e2e8f0;
}

.hero-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 20px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #e0f2fe;
  color: #0369a1;
  padding: 6px 16px;
  border-radius: 30px;
  font-size: 0.84rem;
  font-weight: 700;
  margin-bottom: 16px;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin: 0 0 16px;
}

.hero-title span {
  color: #0284c7;
}

.hero-subtitle {
  font-size: 1.05rem;
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 32px;
}

.hero-search-box {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #cbd5e1;
  border-radius: 50px;
  padding: 6px 8px 6px 20px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06);
  transition: all 0.2s;
  max-width: 600px;
  margin: 0 auto;
}

.hero-search-box:focus-within {
  border-color: #0284c7;
  box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.2);
}

.search-icon {
  color: #94a3b8;
  font-size: 1.1rem;
}

.hero-search-box input {
  flex: 1;
  border: none;
  outline: none;
  padding: 10px 14px;
  font-size: 0.95rem;
  background: transparent;
}

.btn-clear {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 8px;
}

.btn-search {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #0284c7;
  color: white;
  border: none;
  border-radius: 40px;
  padding: 10px 22px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-search:hover {
  background: #0369a1;
}

/* Category Filter Bar */
.blog-content-section {
  max-width: 1200px;
  margin: 32px auto 0;
  padding: 0 20px;
}

.categories-filter-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 12px;
  margin-bottom: 28px;
}

.cat-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 30px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #475569;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.cat-pill:hover {
  border-color: #0284c7;
  color: #0284c7;
}

.cat-pill.active {
  background: #0284c7;
  color: white;
  border-color: #0284c7;
}

.cat-count {
  font-size: 0.72rem;
  background: rgba(0, 0, 0, 0.08);
  padding: 2px 7px;
  border-radius: 12px;
}

.cat-pill.active .cat-count {
  background: rgba(255, 255, 255, 0.25);
  color: white;
}

/* Articles Grid */
.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 28px;
}

@media (max-width: 768px) {
  .articles-grid {
    grid-template-columns: 1fr;
  }
}

.article-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.article-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.1);
  border-color: #bae6fd;
}

.card-thumb-wrap {
  position: relative;
  display: block;
  width: 100%;
  height: 200px;
  background: #f1f5f9;
  overflow: hidden;
}

.card-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.article-card:hover .card-thumb {
  transform: scale(1.05);
}

.card-cat-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(6px);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
}

.card-body {
  padding: 22px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  color: #64748b;
  margin-bottom: 10px;
}

.meta-dot { color: #cbd5e1; }

.card-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.4;
  margin: 0 0 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-title a {
  color: inherit;
  text-decoration: none;
  transition: color 0.2s;
}

.card-title a:hover {
  color: #0284c7;
}

.card-summary {
  font-size: 0.88rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 20px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #0369a1;
  font-size: 0.76rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
}

.read-more-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.84rem;
  font-weight: 700;
  color: #0284c7;
  text-decoration: none;
  transition: gap 0.2s;
}

.read-more-link:hover {
  gap: 10px;
}

/* Empty State */
.empty-state {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  text-align: center;
  padding: 60px 24px;
}

.empty-icon {
  font-size: 3rem;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: #0f172a;
  margin: 0 0 8px;
}

.empty-state p {
  color: #64748b;
  max-width: 480px;
  margin: 0 auto 20px;
}

.btn-reset-filter {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 30px;
  background: #0284c7;
  color: white;
  border: none;
  font-weight: 700;
  cursor: pointer;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 0;
  color: #0284c7;
  font-size: 1.5rem;
}

.loading-state p {
  font-size: 0.95rem;
  color: #64748b;
  margin-top: 12px;
}

/* Pagination */
.blog-pagination {
  margin-top: 48px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-page-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #334155;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-page-nav:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 6px;
}

.page-number-btn {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #334155;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
}

.page-number-btn.active {
  background: #0284c7;
  color: white;
  border-color: #0284c7;
}
</style>
