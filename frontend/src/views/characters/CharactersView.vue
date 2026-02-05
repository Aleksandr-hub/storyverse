<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { charactersApi, universesApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/layout/AppHeader.vue'

interface Character {
  id: string
  name: string
  description: string | null
  avatar_url: string | null
  is_canonical: boolean
  universe?: { id: string; name: string }
  creator?: { id: string; username: string }
}

interface Universe {
  id: string
  name: string
  slug: string
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const characters = ref<Character[]>([])
const universes = ref<Universe[]>([])
const loading = ref(true)
const error = ref('')
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

const filters = ref({
  universe: route.query.universe as string || '',
  search: route.query.search as string || '',
  canonical: route.query.canonical as string || '',
})

const isAuthenticated = computed(() => authStore.isAuthenticated)

const loadCharacters = async (page = 1) => {
  loading.value = true
  error.value = ''

  try {
    const params: any = { per_page: 20, page }
    if (filters.value.universe) params.universe = filters.value.universe
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.canonical) params.canonical = filters.value.canonical === 'true'

    const res = await charactersApi.list(params)
    characters.value = res.data.data
    pagination.value = {
      currentPage: res.data.meta.current_page,
      lastPage: res.data.meta.last_page,
      total: res.data.meta.total,
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Помилка завантаження персонажів'
  } finally {
    loading.value = false
  }
}

const loadUniverses = async () => {
  try {
    const res = await universesApi.list()
    universes.value = res.data.data
  } catch (err) {
    console.error('Error loading universes:', err)
  }
}

const applyFilters = () => {
  const query: any = {}
  if (filters.value.universe) query.universe = filters.value.universe
  if (filters.value.search) query.search = filters.value.search
  if (filters.value.canonical) query.canonical = filters.value.canonical

  router.push({ query })
  loadCharacters()
}

const clearFilters = () => {
  filters.value = { universe: '', search: '', canonical: '' }
  router.push({ query: {} })
  loadCharacters()
}

watch(() => route.query, () => {
  filters.value = {
    universe: route.query.universe as string || '',
    search: route.query.search as string || '',
    canonical: route.query.canonical as string || '',
  }
  loadCharacters()
}, { immediate: false })

onMounted(() => {
  loadCharacters()
  loadUniverses()
})
</script>

<template>
  <div class="characters-page">
    <AppHeader />

    <main class="main">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Персонажі</h1>
          <p class="page-subtitle">{{ pagination.total }} персонажів</p>
        </div>
        <RouterLink v-if="isAuthenticated" to="/characters/new" class="btn-create">
          + Створити персонажа
        </RouterLink>
      </div>

      <!-- Filters -->
      <div class="card filters-card">
        <div class="filters">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Пошук за іменем..."
            class="filter-input"
            @keyup.enter="applyFilters"
          />

          <select v-model="filters.universe" class="filter-select">
            <option value="">Усі всесвіти</option>
            <option v-for="u in universes" :key="u.id" :value="u.id">
              {{ u.name }}
            </option>
          </select>

          <select v-model="filters.canonical" class="filter-select">
            <option value="">Усі типи</option>
            <option value="true">Канонічні</option>
            <option value="false">Користувацькі</option>
          </select>

          <button @click="applyFilters" class="btn-filter">Застосувати</button>
          <button @click="clearFilters" class="btn-clear">Скинути</button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="card error-card">
        <p class="error-text">{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="characters.length === 0" class="card empty-state">
        <div class="empty-icon">👤</div>
        <h3 class="empty-title">Персонажів не знайдено</h3>
        <p class="empty-text">Спробуйте змінити фільтри або створіть свого персонажа</p>
        <RouterLink v-if="isAuthenticated" to="/characters/new" class="btn-primary">
          Створити персонажа
        </RouterLink>
      </div>

      <!-- Characters Grid -->
      <div v-else class="characters-grid">
        <RouterLink
          v-for="character in characters"
          :key="character.id"
          :to="`/characters/${character.id}`"
          class="character-card"
        >
          <div class="character-avatar">
            <img v-if="character.avatar_url" :src="character.avatar_url" :alt="character.name" />
            <span v-else>{{ character.name.charAt(0).toUpperCase() }}</span>
          </div>
          <div class="character-info">
            <h3 class="character-name">
              {{ character.name }}
              <span v-if="character.is_canonical" class="canonical-badge" title="Канонічний">✓</span>
            </h3>
            <p v-if="character.universe" class="character-universe">{{ character.universe.name }}</p>
            <p v-if="character.description" class="character-description">{{ character.description }}</p>
          </div>
        </RouterLink>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="pagination">
        <button
          @click="loadCharacters(pagination.currentPage - 1)"
          :disabled="pagination.currentPage <= 1"
          class="page-btn"
        >
          ←
        </button>
        <span class="page-info">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
        <button
          @click="loadCharacters(pagination.currentPage + 1)"
          :disabled="pagination.currentPage >= pagination.lastPage"
          class="page-btn"
        >
          →
        </button>
      </div>
    </main>
  </div>
</template>

<style scoped>
.characters-page {
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
  margin-bottom: 24px;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.page-subtitle {
  color: #6b7280;
}

.btn-create {
  padding: 10px 20px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-create:hover {
  background: #4338ca;
}

/* Filters */
.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 24px;
  margin-bottom: 24px;
}

.filters-card {
  padding: 16px;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.filter-input {
  flex: 1;
  min-width: 200px;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}

.filter-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #fff;
  cursor: pointer;
}

.btn-filter {
  padding: 8px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-filter:hover {
  background: #4338ca;
}

.btn-clear {
  padding: 8px 16px;
  color: #6b7280;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.btn-clear:hover {
  color: #111827;
}

/* Loading */
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

/* Error */
.error-card {
  text-align: center;
  padding: 48px;
}

.error-text {
  color: #dc2626;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 64px;
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

.btn-primary {
  display: inline-block;
  padding: 10px 24px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #4338ca;
}

/* Characters Grid */
.characters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.character-card {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  transition: all 0.2s;
}

.character-card:hover {
  border-color: #a5b4fc;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.character-avatar {
  width: 64px;
  height: 64px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
  color: #4f46e5;
  flex-shrink: 0;
  overflow: hidden;
}

.character-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.character-info {
  flex: 1;
  min-width: 0;
}

.character-name {
  font-weight: 600;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

.canonical-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  background: #dcfce7;
  color: #16a34a;
  border-radius: 50%;
  font-size: 0.625rem;
}

.character-universe {
  font-size: 0.75rem;
  color: #4f46e5;
  margin-bottom: 4px;
}

.character-description {
  font-size: 0.875rem;
  color: #6b7280;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 32px;
}

.page-btn {
  padding: 8px 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  border-color: #4f46e5;
  color: #4f46e5;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #6b7280;
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .filters {
    flex-direction: column;
  }

  .filter-input,
  .filter-select {
    width: 100%;
  }

  .characters-grid {
    grid-template-columns: 1fr;
  }
}
</style>
