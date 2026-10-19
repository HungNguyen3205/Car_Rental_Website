<template>
  <div class="blog-detail-page">
    <!-- Loading State -->
    <div v-if="loading" class="detail-loading app-container">
      <i class="fa-solid fa-spinner fa-spin"></i>
      <p>Đang tải nội dung bài viết...</p>
    </div>

    <!-- 404 / Error State -->
    <div v-else-if="notFound || !post" class="detail-not-found app-container card">
      <div class="nf-icon"><i class="fa-regular fa-face-frown"></i></div>
      <h2>Bài viết không tồn tại hoặc đã được gỡ xuống</h2>
      <p>Bài viết bạn đang tìm kiếm có thể đã bị xóa, lưu trữ hoặc chưa được xuất bản công khai.</p>
      <router-link to="/blog" class="btn-primary-return">
        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách bài viết
      </router-link>
    </div>

    <!-- Article Content -->
    <article v-else class="article-detail-wrap app-container">
      <!-- Breadcrumbs -->
      <nav class="detail-breadcrumbs" aria-label="Điều hướng">
        <router-link to="/">Trang chủ</router-link>
        <i class="fa-solid fa-chevron-right sep"></i>
        <router-link to="/blog">Tin tức & Cẩm nang</router-link>
        <template v-if="post.category">
          <i class="fa-solid fa-chevron-right sep"></i>
          <router-link :to="`/blog?category=${post.category.slug}`">{{ post.category.name }}</router-link>
        </template>
        <i class="fa-solid fa-chevron-right sep"></i>
        <span class="current-crumb">{{ post.title }}</span>
      </nav>

      <!-- Article Header -->
      <header class="article-header">
        <div class="header-badges">
          <router-link 
            v-if="post.category" 
            :to="`/blog?category=${post.category.slug}`" 
            class="cat-badge-link"
          >
            {{ post.category.name }}
          </router-link>
          <span class="reading-time-badge">
            <i class="fa-regular fa-clock"></i> {{ post.reading_time || 1 }} phút đọc
          </span>
        </div>

        <h1 class="article-title">{{ post.title }}</h1>

        <!-- Meta Line: Author, Date, Views -->
        <div class="article-meta-bar">
          <div class="author-meta">
            <div class="author-avatar">{{ (post.author?.ho_ten || 'A').charAt(0).toUpperCase() }}</div>
            <div class="author-text">
              <span class="author-label">Tác giả</span>
              <strong class="author-name">{{ post.author?.ho_ten || 'Ban Biên Tập' }}</strong>
            </div>
          </div>

          <div class="meta-stats">
            <div class="stat-item" :title="`Xuất bản: ${formatDateTime(post.published_at)}`">
              <i class="fa-regular fa-calendar-check"></i>
              <span>{{ formatDate(post.published_at) }}</span>
            </div>
            <div class="stat-item">
              <i class="fa-regular fa-eye"></i>
              <span>{{ post.views_count || 0 }} lượt xem</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Featured Image -->
      <div class="featured-image-container" v-if="post.featured_image">
        <img 
          :src="post.featured_image" 
          :alt="post.title" 
          class="featured-img"
          @error="$event.target.style.display = 'none'"
        />
      </div>

      <!-- Excerpt / Summary Banner -->
      <div class="article-lead-banner" v-if="post.summary">
        <p>{{ post.summary }}</p>
      </div>

      <!-- Main Body Content -->
      <div class="article-prose-content">
        <div class="content-body" v-html="renderedContent"></div>
      </div>

      <!-- Article Footer / Share Bar -->
      <footer class="article-footer-bar card">
        <div class="share-group">
          <span class="share-label"><i class="fa-solid fa-share-nodes"></i> Chia sẻ bài viết:</span>
          <button class="btn-share copy" @click="copyLink" title="Sao chép đường dẫn">
            <i class="fa-solid fa-link"></i>
            <span>{{ copied ? 'Đã sao chép!' : 'Sao chép link' }}</span>
          </button>
          <button class="btn-share facebook" @click="shareFacebook" title="Chia sẻ lên Facebook">
            <i class="fa-brands fa-facebook"></i> Facebook
          </button>
        </div>

        <router-link to="/blog" class="btn-back-blog">
          <i class="fa-solid fa-arrow-left"></i> Danh sách bài viết
        </router-link>
      </footer>

      <!-- CTA Rental Banner -->
      <div class="cta-rental-card card">
        <div class="cta-content">
          <div class="cta-badge"><i class="fa-solid fa-bolt"></i> Trải nghiệm VinFast</div>
          <h3>Sẵn Sàng Cho Chuyến Đi Tự Lái Của Bạn?</h3>
          <p>Thuê xe điện VinFast VF5, VF8, VF9 đời mới với thủ tục nhanh gọn chỉ trong 5 phút.</p>
          <router-link to="/fleet" class="btn-cta-explore">
            <span>Khám phá đội xe ngay</span>
            <i class="fa-solid fa-arrow-right"></i>
          </router-link>
        </div>
      </div>

      <!-- Related Articles Section -->
      <section class="related-section" v-if="relatedPosts.length > 0">
        <h3 class="section-title"><i class="fa-solid fa-bookmark"></i> Bài Viết Cùng Chuyên Mục</h3>
        <div class="related-grid">
          <router-link 
            v-for="rel in relatedPosts" 
            :key="rel.id" 
            :to="`/blog/${rel.slug}`" 
            class="related-card card"
          >
            <img 
              :src="rel.featured_image || '/assets/images/default-car.png'" 
              :alt="rel.title" 
              class="rel-thumb"
              @error="$event.target.src = '/assets/images/default-car.png'"
            />
            <div class="rel-info">
              <span class="rel-date">{{ formatDate(rel.published_at) }}</span>
              <h4 class="rel-title">{{ rel.title }}</h4>
            </div>
          </router-link>
        </div>
      </section>
    </article>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import baseRequest from '../../core/baseRequest';

const route = useRoute();
const router = useRouter();

const post = ref(null);
const relatedPosts = ref([]);
const loading = ref(true);
const notFound = ref(false);
const copied = ref(false);

const fetchPostDetail = async () => {
  const slug = route.params.slug;
  if (!slug) return;

  loading.value = true;
  notFound.value = false;
  try {
    const res = await baseRequest.get(`/posts/${slug}`);
    if (res.data && res.data.status === 1) {
      post.value = res.data.data.post;
      relatedPosts.value = res.data.data.related_posts || [];
    } else {
      notFound.value = true;
    }
  } catch (err) {
    notFound.value = true;
  } finally {
    loading.value = false;
  }
};

// Formats content by converting newlines or basic markdown if needed
const renderedContent = computed(() => {
  if (!post.value || !post.value.content) return '';
  const raw = post.value.content;

  // If content contains standard HTML tags, return directly
  if (/<[a-z][\s\S]*>/i.test(raw)) {
    return raw;
  }

  // Otherwise convert Markdown-like paragraphs and line breaks
  return raw
    .split('\n\n')
    .map(p => {
      let trimmed = p.trim();
      if (trimmed.startsWith('### ')) {
        return `<h3>${trimmed.substring(4)}</h3>`;
      }
      if (trimmed.startsWith('## ')) {
        return `<h2>${trimmed.substring(3)}</h2>`;
      }
      if (trimmed.startsWith('> ')) {
        return `<blockquote>${trimmed.substring(2)}</blockquote>`;
      }
      return `<p>${trimmed.replace(/\n/g, '<br/>')}</p>`;
    })
    .join('');
});

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

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  } catch (e) {
    console.error('Không thể sao chép liên kết:', e);
  }
};

const shareFacebook = () => {
  const url = encodeURIComponent(window.location.href);
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
};

watch(() => route.params.slug, () => {
  fetchPostDetail();
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

onMounted(() => {
  fetchPostDetail();
});
</script>

<style scoped>
.blog-detail-page {
  background: #f8fafc;
  min-height: 100vh;
  padding: 32px 0 60px;
}

.app-container {
  max-width: 860px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Breadcrumbs */
.detail-breadcrumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.detail-breadcrumbs a {
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
}

.detail-breadcrumbs a:hover {
  color: #0284c7;
}

.sep {
  font-size: 0.65rem;
  color: #cbd5e1;
}

.current-crumb {
  color: #0f172a;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 320px;
}

/* Article Header */
.article-header {
  margin-bottom: 28px;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.cat-badge-link {
  background: #0284c7;
  color: white;
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s;
}

.cat-badge-link:hover {
  background: #0369a1;
}

.reading-time-badge {
  font-size: 0.8rem;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.article-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.3;
  margin: 0 0 20px;
}

@media (max-width: 768px) {
  .article-title {
    font-size: 1.6rem;
  }
}

.article-meta-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 0;
  border-top: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
  gap: 14px;
}

.author-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.author-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #e0f2fe;
  color: #0284c7;
  font-size: 1.1rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-text {
  display: flex;
  flex-direction: column;
}

.author-label {
  font-size: 0.72rem;
  color: #94a3b8;
  text-transform: uppercase;
  font-weight: 600;
}

.author-name {
  font-size: 0.95rem;
  color: #0f172a;
}

.meta-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 0.84rem;
  color: #64748b;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Featured Image */
.featured-image-container {
  width: 100%;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 28px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
}

.featured-img {
  width: 100%;
  max-height: 480px;
  object-fit: cover;
  display: block;
}

/* Excerpt Banner */
.article-lead-banner {
  background: #f1f5f9;
  border-left: 4px solid #0284c7;
  padding: 16px 20px;
  border-radius: 0 12px 12px 0;
  margin-bottom: 32px;
}

.article-lead-banner p {
  font-size: 1.05rem;
  font-weight: 500;
  color: #334155;
  line-height: 1.6;
  margin: 0;
}

/* Prose Content */
.article-prose-content {
  font-size: 1.05rem;
  color: #334155;
  line-height: 1.8;
  margin-bottom: 40px;
}

.article-prose-content :deep(h2) {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 36px 0 16px;
}

.article-prose-content :deep(h3) {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin: 28px 0 14px;
}

.article-prose-content :deep(p) {
  margin-bottom: 20px;
}

.article-prose-content :deep(blockquote) {
  border-left: 4px solid #cbd5e1;
  padding: 10px 20px;
  margin: 24px 0;
  color: #475569;
  font-style: italic;
  background: #f8fafc;
  border-radius: 0 8px 8px 0;
}

.article-prose-content :deep(ul),
.article-prose-content :deep(ol) {
  padding-left: 24px;
  margin-bottom: 20px;
}

.article-prose-content :deep(li) {
  margin-bottom: 8px;
}

.article-prose-content :deep(img) {
  max-width: 100%;
  border-radius: 12px;
  margin: 20px 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* Footer & Share Bar */
.article-footer-bar {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.share-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.share-label {
  font-size: 0.86rem;
  font-weight: 700;
  color: #475569;
}

.btn-share {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-share.copy:hover {
  background: #f1f5f9;
  border-color: #0284c7;
  color: #0284c7;
}

.btn-share.facebook {
  background: #1877f2;
  color: white;
  border-color: #1877f2;
}

.btn-share.facebook:hover {
  background: #166fe5;
}

.btn-back-blog {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0284c7;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
}

.btn-back-blog:hover {
  text-decoration: underline;
}

/* CTA Rental Card */
.cta-rental-card {
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: white;
  border-radius: 16px;
  padding: 36px 32px;
  margin-bottom: 40px;
  box-shadow: 0 14px 30px -10px rgba(2, 132, 199, 0.4);
}

.cta-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 700;
  margin-bottom: 12px;
}

.cta-content h3 {
  font-size: 1.5rem;
  font-weight: 800;
  margin: 0 0 8px;
}

.cta-content p {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.9);
  margin: 0 0 20px;
}

.btn-cta-explore {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: white;
  color: #0284c7;
  padding: 10px 24px;
  border-radius: 30px;
  font-size: 0.92rem;
  font-weight: 800;
  text-decoration: none;
  transition: transform 0.2s;
}

.btn-cta-explore:hover {
  transform: translateY(-2px);
}

/* Related Section */
.related-section {
  margin-top: 40px;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

@media (max-width: 768px) {
  .related-grid {
    grid-template-columns: 1fr;
  }
}

.related-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s;
}

.related-card:hover {
  transform: translateY(-3px);
  border-color: #0284c7;
  box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.08);
}

.rel-thumb {
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.rel-info {
  padding: 14px;
}

.rel-date {
  font-size: 0.74rem;
  color: #94a3b8;
  display: block;
  margin-bottom: 4px;
}

.rel-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

/* Loading & 404 */
.detail-loading {
  text-align: center;
  padding: 80px 0;
  font-size: 2rem;
  color: #0284c7;
}

.detail-loading p {
  font-size: 1rem;
  color: #64748b;
  margin-top: 12px;
}

.detail-not-found {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  text-align: center;
  padding: 60px 24px;
  margin-top: 40px;
}

.nf-icon {
  font-size: 3rem;
  color: #f59e0b;
  margin-bottom: 16px;
}

.detail-not-found h2 {
  font-size: 1.4rem;
  color: #0f172a;
  margin: 0 0 10px;
}

.detail-not-found p {
  color: #64748b;
  margin: 0 auto 24px;
  max-width: 480px;
}

.btn-primary-return {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #0284c7;
  color: white;
  padding: 10px 24px;
  border-radius: 30px;
  font-weight: 700;
  text-decoration: none;
}
</style>
