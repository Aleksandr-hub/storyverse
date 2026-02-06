<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'

const reload = () => {
  window.location.reload()
}
</script>

<template>
  <div class="error-page">
    <AppHeader />

    <main class="main">
      <div class="error-content">
        <div class="error-code">500</div>
        <h1 class="error-title">Щось пішло не так</h1>
        <p class="error-text">
          На сервері сталася помилка. Ми вже працюємо над її виправленням.
          Спробуйте оновити сторінку або поверніться пізніше.
        </p>
        <div class="error-actions">
          <button @click="reload" class="btn-primary">
            Оновити сторінку
          </button>
          <RouterLink to="/" class="btn-secondary">
            На головну
          </RouterLink>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.error-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 64px);
  padding: 32px 20px;
}

.error-content {
  text-align: center;
  max-width: 480px;
}

.error-code {
  font-size: 8rem;
  font-weight: 800;
  color: #fecaca;
  line-height: 1;
  margin-bottom: 16px;
}

.error-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 12px;
}

.error-text {
  color: #6b7280;
  margin-bottom: 32px;
  line-height: 1.6;
}

.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary {
  display: inline-block;
  background: #4f46e5;
  color: #fff;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background: #4338ca;
}

.btn-secondary {
  display: inline-block;
  background: #fff;
  color: #374151;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f3f4f6;
}

@media (max-width: 480px) {
  .error-code {
    font-size: 6rem;
  }
}
</style>
