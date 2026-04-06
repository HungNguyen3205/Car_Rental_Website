<template>
  <div class="models-page">
    <section class="hero-header">
      <div class="app-container text-center">
        <h1>Các Dòng Xe Bổ Sung</h1>
        <p>Đa dạng các chủng loại xe phục vụ mọi nhu cầu di chuyển của bạn</p>
      </div>
    </section>

    <section class="app-container content-section">
      <!-- Category 1: SUV -->
      <div class="category-block">
        <div class="category-header">
          <h2>Xe SUV & Gầm cao</h2>
          <p>Phù hợp cho những chuyến đi xa, dã ngoại cùng gia đình với không gian rộng rãi.</p>
        </div>
        <div class="model-grid">
          <div class="model-card">
            <img src="https://images.pexels.com/photos/210019/pexels-photo-210019.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Ford Everest" />
            <div class="model-info">
              <h3>Ford Everest 2026</h3>
              <p>7 Chỗ • Máy Dầu • Từ 1.500k/ngày</p>
            </div>
          </div>
          <div class="model-card">
            <img src="https://images.pexels.com/photos/116675/pexels-photo-116675.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Honda CR-V" />
            <div class="model-info">
              <h3>Honda CR-V</h3>
              <p>7 Chỗ • Xăng lai điện • Từ 1.200k/ngày</p>
            </div>
          </div>
          <div class="model-card">
            <img src="https://images.pexels.com/photos/707046/pexels-photo-707046.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Hyundai Santafe" />
            <div class="model-info">
              <h3>Hyundai Santafe</h3>
              <p>7 Chỗ • Tiện nghi cao cấp • Từ 1.400k/ngày</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Category 2: Sedan -->
      <div class="category-block">
        <div class="category-header">
          <h2>Xe Sedan 4-5 Chỗ</h2>
          <p>Lựa chọn lý tưởng cho việc di chuyển trong đô thị hoặc gặp gỡ đối tác.</p>
        </div>
        <div class="model-grid">
          <div class="model-card">
            <img src="https://images.pexels.com/photos/112460/pexels-photo-112460.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Toyota Camry" />
            <div class="model-info">
              <h3>Toyota Camry</h3>
              <p>5 Chỗ • Sang trọng • Từ 1.000k/ngày</p>
            </div>
          </div>
          <div class="model-card">
            <img src="https://images.pexels.com/photos/120049/pexels-photo-120049.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Mazda 3" />
            <div class="model-info">
              <h3>Mazda 3</h3>
              <p>5 Chỗ • Thể thao • Từ 700k/ngày</p>
            </div>
          </div>
          <div class="model-card">
            <img src="https://images.pexels.com/photos/909907/pexels-photo-909907.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Honda Civic" />
            <div class="model-info">
              <h3>Honda Civic</h3>
              <p>5 Chỗ • Nam tính • Từ 800k/ngày</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Category 3: Luxury -->
      <div class="category-block">
        <div class="category-header">
          <h2>Dòng Xe Hạng Sang (Luxury)</h2>
          <p>Mang lại đẳng cấp khác biệt cho những sự kiện quan trọng.</p>
        </div>
        <div class="model-grid">
          <div class="model-card">
            <img src="https://images.pexels.com/photos/3786091/pexels-photo-3786091.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Porsche" />
            <div class="model-info">
              <h3>Porsche Panamera</h3>
              <p>Siêu xe • Thể thao sang trọng • Từ 4.500k/ngày</p>
            </div>
          </div>
          <div class="model-card">
            <img src="https://images.pexels.com/photos/1009871/pexels-photo-1009871.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Mercedes S-Class" />
            <div class="model-info">
              <h3>Mercedes S-Class</h3>
              <p>Đẳng cấp doanh nhân • Từ 3.800k/ngày</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.models-page {
  padding-bottom: 80px;
}
.hero-header {
  background: linear-gradient(to right, #f1f5f9, #e2e8f0);
  color: #0f172a;
  padding: 140px 20px 80px;
}
.hero-header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 12px;
  color: #0f172a;
}
.hero-header p {
  font-size: 1.125rem;
  color: #475569;
}
.content-section {
  margin-top: 60px;
}

.category-block {
  margin-bottom: 60px;
}
.category-header {
  margin-bottom: 24px;
}
.category-header h2 {
  font-size: 1.8rem;
  color: #0f172a;
  margin-bottom: 8px;
}
.category-header p {
  color: #64748b;
  font-size: 1.05rem;
}

.model-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 32px;
}
.model-card {
  background: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: transform 0.3s, box-shadow 0.3s;
}
.model-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}
.model-card img {
  width: 100%;
  height: 220px;
  object-fit: cover;
}
.model-info {
  padding: 20px;
}
.model-info h3 {
  font-size: 1.25rem;
  color: #0f172a;
  margin-bottom: 8px;
}
.model-info p {
  color: #475569;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .hero-header h1 { font-size: 2rem; }
  .model-grid { grid-template-columns: 1fr; }
}
</style>
