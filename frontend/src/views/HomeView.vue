<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/layout/AppHeader.vue'

const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
</script>

<template>
  <div class="home-page">
    <AppHeader />

    <main>
      <!-- Hero Section -->
      <section class="hero">
        <div class="hero-content">
          <h1 class="hero-title">
            <span class="hero-title-accent">Story</span>Verse
          </h1>
          <p class="hero-description">
            Платформа для творчого писання з AI. Пиши фанфіки, створюй оригінальні історії,
            грай у Adventure Mode з AI Dungeon Master.
          </p>

          <div class="hero-buttons">
            <template v-if="!isAuthenticated">
              <RouterLink to="/register" class="btn btn-primary">
                Почати безкоштовно
              </RouterLink>
              <RouterLink to="/login" class="btn btn-secondary">
                Увійти
              </RouterLink>
            </template>
            <template v-else>
              <RouterLink to="/dashboard" class="btn btn-primary">
                До моїх історій
              </RouterLink>
            </template>
          </div>
        </div>
      </section>

      <!-- Features Section -->
      <section class="features">
        <h2 class="section-title">Можливості платформи</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">📝</div>
            <h3 class="feature-title">Story Mode</h3>
            <p class="feature-description">
              Пиши фанфіки та оригінальні історії з AI-помічником, який знає канон всесвітів
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">🎮</div>
            <h3 class="feature-title">Adventure Mode</h3>
            <p class="feature-description">
              Інтерактивні пригоди з AI Dungeon Master. Створюй персонажа та досліджуй світи
            </p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3 class="feature-title">Collaborative</h3>
            <p class="feature-description">
              Пиши історії разом з друзями в реальному часі. AI може бути одним з авторів
            </p>
          </div>
        </div>
      </section>

      <!-- Universes Section -->
      <section class="universes">
        <h2 class="section-title">Популярні всесвіти</h2>
        <div class="universe-tags">
          <span class="tag tag-blue">🦸 Marvel</span>
          <span class="tag tag-purple">⚡ Harry Potter</span>
          <span class="tag tag-green">⚔️ Відьмак</span>
          <span class="tag tag-orange">🐉 Dungeons & Dragons</span>
          <span class="tag tag-red">🌟 Star Wars</span>
          <span class="tag tag-indigo">✨ Оригінальні світи</span>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
      <p class="footer-logo">📚 StoryVerse</p>
      <p class="footer-tagline">AI-Powered Creative Writing Platform</p>
      <p class="footer-author">Created by Oleksandr Prytuliak</p>
    </footer>
  </div>
</template>

<style scoped>
.home-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fff;
}

main {
  flex: 1;
}

/* Hero Section */
.hero {
  background: linear-gradient(to bottom, #eef2ff, #fff);
  padding: 80px 20px;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 24px;
}

.hero-title-accent {
  color: #4f46e5;
}

.hero-description {
  font-size: 1.25rem;
  color: #4b5563;
  margin-bottom: 40px;
  line-height: 1.7;
}

.hero-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  display: inline-block;
  padding: 16px 32px;
  border-radius: 12px;
  font-size: 1.125rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #4f46e5;
  color: #fff;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
}

.btn-primary:hover {
  background: #4338ca;
}

.btn-secondary {
  background: #fff;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
  background: #f9fafb;
}

/* Features Section */
.features {
  padding: 80px 20px;
  background: #fff;
}

.section-title {
  font-size: 2rem;
  font-weight: 700;
  color: #111827;
  text-align: center;
  margin-bottom: 48px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 32px;
  max-width: 1200px;
  margin: 0 auto;
}

.feature-card {
  background: #f3f4f6;
  padding: 40px 32px;
  border-radius: 16px;
  text-align: center;
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.feature-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 12px;
}

.feature-description {
  color: #4b5563;
  line-height: 1.6;
}

/* Universes Section */
.universes {
  padding: 80px 20px;
  background: #f3f4f6;
}

.universe-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  max-width: 800px;
  margin: 0 auto;
}

.tag {
  padding: 10px 20px;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.tag-blue { background: #3b82f6; }
.tag-purple { background: #8b5cf6; }
.tag-green { background: #22c55e; }
.tag-orange { background: #f97316; }
.tag-red { background: #ef4444; }
.tag-indigo { background: #6366f1; }

/* Footer */
.footer {
  background: #111827;
  color: #fff;
  padding: 48px 20px;
  text-align: center;
}

.footer-logo {
  font-size: 1.5rem;
  margin-bottom: 8px;
}

.footer-tagline {
  color: #9ca3af;
}

.footer-author {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 16px;
}

/* Responsive */
@media (max-width: 640px) {
  .hero {
    padding: 60px 20px;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-description {
    font-size: 1.125rem;
  }

  .hero-buttons {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
