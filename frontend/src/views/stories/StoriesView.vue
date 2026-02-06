<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useStoriesStore } from '@/stores/stories'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/layout/AppHeader.vue'

const route = useRoute()
const router = useRouter()
const storiesStore = useStoriesStore()
const authStore = useAuthStore()

const stories = computed(() => storiesStore.stories)
const universes = computed(() => storiesStore.universes)
const popularTags = computed(() => storiesStore.popularTags)
const loading = computed(() => storiesStore.loading)
const pagination = computed(() => storiesStore.pagination)
const isAuthenticated = computed(() => authStore.isAuthenticated)

const search = ref((route.query.search as string) || '')
const selectedUniverse = ref((route.query.universe as string) || '')
const selectedTag = ref((route.query.tag as string) || '')

const loadStories = async () => {
  await storiesStore.fetchStories({
    search: search.value || undefined,
    universe: selectedUniverse.value || undefined,
    tag: selectedTag.value || undefined,
    page: Number(route.query.page) || 1,
  })
}

const handleSearch = () => {
  router.push({
    query: { ...route.query, search: search.value || undefined, page: undefined },
  })
}

const selectUniverse = (id: string) => {
  router.push({
    query: { ...route.query, universe: id || undefined, page: undefined },
  })
}

const selectTag = (id: string) => {
  router.push({
    query: { ...route.query, tag: id || undefined, page: undefined },
  })
}

const goToPage = (page: number) => {
  router.push({
    query: { ...route.query, page: page > 1 ? page : undefined },
  })
}

watch(() => route.query, () => {
  search.value = (route.query.search as string) || ''
  selectedUniverse.value = (route.query.universe as string) || ''
  selectedTag.value = (route.query.tag as string) || ''
  loadStories()
}, { immediate: false })

onMounted(async () => {
  await Promise.all([
    loadStories(),
    storiesStore.fetchUniverses({ official: true }),
    storiesStore.fetchPopularTags(),
  ])
})
</script>

<template>
  <div class="stories-page">
    <AppHeader />

    <main class="main">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Історії</h1>
          <p class="page-subtitle">Відкрий для себе захоплюючі історії від спільноти авторів</p>
        </div>
        <RouterLink v-if="isAuthenticated" to="/stories/new" class="btn-primary">
          ✍️ Написати історію
        </RouterLink>
      </div>

      <div class="content">
        <!-- Sidebar Filters -->
        <aside class="sidebar">
          <!-- Search -->
          <div class="card">
            <label class="filter-label">Пошук</label>
            <div class="search-box">
              <input
                v-model="search"
                type="text"
                placeholder="Назва або опис..."
                class="search-input"
                @keyup.enter="handleSearch"
              />
              <button @click="handleSearch" class="search-btn">🔍</button>
            </div>
          </div>

          <!-- Universes -->
          <div class="card">
            <h3 class="filter-label">Всесвіти</h3>
            <div class="filter-list">
              <button
                @click="selectUniverse('')"
                :class="['filter-item', { active: !selectedUniverse }]"
              >
                Всі всесвіти
              </button>
              <button
                v-for="universe in universes"
                :key="universe.id"
                @click="selectUniverse(universe.id)"
                :class="['filter-item', { active: selectedUniverse === universe.id }]"
              >
                {{ universe.name }}
                <span class="filter-count">({{ universe.stories_count }})</span>
              </button>
            </div>
          </div>

          <!-- Popular Tags -->
          <div class="card">
            <h3 class="filter-label">Популярні теги</h3>
            <div class="tags-list">
              <button
                v-for="tag in popularTags.slice(0, 10)"
                :key="tag.id"
                @click="selectTag(selectedTag === tag.id ? '' : tag.id)"
                :class="['tag', { active: selectedTag === tag.id }]"
              >
                {{ tag.name }}
              </button>
            </div>
          </div>
        </aside>

        <!-- Stories Grid -->
        <div class="stories-content">
          <!-- Loading State -->
          <div v-if="loading" class="loading">
            <div class="spinner"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="stories.length === 0" class="empty-state">
            <div class="empty-icon">📭</div>
            <h3 class="empty-title">Нічого не знайдено</h3>
            <p class="empty-text">Спробуй змінити фільтри або напиши першу історію!</p>
            <RouterLink v-if="isAuthenticated" to="/stories/new" class="btn-primary">
              ✍️ Написати історію
            </RouterLink>
          </div>

          <!-- Stories -->
          <div v-else class="stories-grid">
            <RouterLink
              v-for="story in stories"
              :key="story.id"
              :to="`/stories/${story.id}`"
              class="story-card"
            >
              <div class="story-header">
                <h3 class="story-title">{{ story.title }}</h3>
                <span v-if="story.rating !== '0+'" class="rating-badge">{{ story.rating }}</span>
              </div>

              <p v-if="story.description" class="story-description">{{ story.description }}</p>

              <div v-if="story.tags && story.tags.length > 0" class="story-tags">
                <span v-for="tag in story.tags.slice(0, 3)" :key="tag.id" class="story-tag">
                  {{ tag.name }}
                </span>
                <span v-if="story.tags.length > 3" class="story-tag-more">
                  +{{ story.tags.length - 3 }}
                </span>
              </div>

              <div class="story-footer">
                <RouterLink
                  v-if="story.author"
                  :to="`/users/${story.author.id}`"
                  class="story-author"
                  @click.stop
                >
                  <div class="author-avatar">{{ story.author.username?.charAt(0).toUpperCase() }}</div>
                  <span>{{ story.author.username }}</span>
                </RouterLink>
                <div v-else class="story-author">
                  <div class="author-avatar">?</div>
                  <span>Невідомий</span>
                </div>
                <div class="story-stats">
                  <span>{{ story.chapters_count || 0 }} глав</span>
                  <span>{{ story.view_count }} 👁</span>
                </div>
              </div>
            </RouterLink>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.lastPage > 1" class="pagination">
            <button
              v-for="page in pagination.lastPage"
              :key="page"
              @click="goToPage(page)"
              :class="['page-btn', { active: pagination.currentPage === page }]"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.stories-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 20px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
  gap: 16px;
  flex-wrap: wrap;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
}

.page-subtitle {
  color: #6b7280;
  margin-top: 4px;
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

.content {
  display: flex;
  gap: 32px;
}

/* Sidebar */
.sidebar {
  width: 256px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 16px;
}

.filter-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 12px;
}

.search-box {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 8px 40px 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #4f46e5;
}

.search-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-btn:hover {
  color: #4f46e5;
}

.filter-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-item {
  text-align: left;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #374151;
  transition: all 0.2s;
}

.filter-item:hover {
  background: #f3f4f6;
}

.filter-item.active {
  background: #eef2ff;
  color: #4338ca;
}

.filter-count {
  color: #9ca3af;
  font-size: 0.75rem;
  margin-left: 4px;
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.tag:hover {
  background: #eef2ff;
}

.tag.active {
  background: #eef2ff;
  color: #4338ca;
  border-color: #a5b4fc;
}

/* Stories Content */
.stories-content {
  flex: 1;
  min-width: 0;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 80px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 48px;
  text-align: center;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 8px;
}

.empty-text {
  color: #6b7280;
  margin-bottom: 24px;
}

.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
}

.story-card {
  display: block;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 24px;
  transition: all 0.2s;
}

.story-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.story-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;
}

.story-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.rating-badge {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 9999px;
  background: #fee2e2;
  color: #b91c1c;
  font-weight: 500;
  white-space: nowrap;
}

.story-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 16px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.story-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.story-tag {
  padding: 4px 8px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 0.75rem;
  border-radius: 9999px;
}

.story-tag-more {
  padding: 4px 8px;
  color: #9ca3af;
  font-size: 0.75rem;
}

.story-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #6b7280;
}

.story-author {
  display: flex;
  align-items: center;
  gap: 8px;
  transition: color 0.2s;
}

a.story-author:hover {
  color: #4f46e5;
}

.author-avatar {
  width: 24px;
  height: 24px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 500;
  color: #4f46e5;
}

.story-stats {
  display: flex;
  gap: 16px;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 32px;
}

.page-btn {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff;
  font-weight: 500;
  color: #374151;
  transition: all 0.2s;
}

.page-btn:hover {
  background: #f3f4f6;
}

.page-btn.active {
  background: #4f46e5;
  color: #fff;
  border-color: #4f46e5;
}

@media (max-width: 768px) {
  .content {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
  }

  .stories-grid {
    grid-template-columns: 1fr;
  }
}
</style>
