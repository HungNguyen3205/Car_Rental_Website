<template>
  <div class="callback-container">
    <div class="loader-box">
      <div class="loader"></div>
      <h2>Đang xác thực...</h2>
      <p>Vui lòng đợi trong giây lát</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { createToaster } from "@meforma/vue-toaster";

const route = useRoute();
const router = useRouter();
const toaster = createToaster({ position: "top-right" });

onMounted(() => {
  const token = route.query.token;
  const userData = route.query.user;

  if (token && userData) {
    try {
      const user = JSON.parse(decodeURIComponent(userData));
      localStorage.setItem('token_client', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      toaster.success("Đăng nhập thành công!");
      router.push('/');
    } catch (e) {
      toaster.error("Lỗi dữ liệu xác thực!");
      router.push('/auth');
    }
  } else {
    toaster.error("Xác thực thất bại!");
    router.push('/auth');
  }
});
</script>

<style scoped>
.callback-container {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
}

.loader-box {
  text-align: center;
}

.loader {
  width: 48px;
  height: 48px;
  border: 5px solid #f3f3f3;
  border-top: 5px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 24px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

h2 {
  font-family: var(--font-heading);
  color: var(--color-text-main);
  margin-bottom: 8px;
}

p {
  color: var(--color-text-muted);
}
</style>
