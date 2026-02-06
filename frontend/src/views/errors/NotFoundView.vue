<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'
</script>

<template>
  <div class="error-page">
    <AppHeader />

    <main class="main">
      <div class="error-content">
        <div class="error-code">404</div>
        <h1 class="error-title">Сторінку не знайдено</h1>
        <p class="error-text">
          Схоже, ця сторінка загубилась у просторах всесвіту.
          Можливо, вона була переміщена або видалена.
        </p>
        <div class="error-actions">
          <RouterLink to="/" class="btn-primary">
            На головну
          </RouterLink>
          <RouterLink to="/stories" class="btn-secondary">
            До історій
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
  color: #e5e7eb;
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
