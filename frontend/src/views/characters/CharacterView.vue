<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { charactersApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AppHeader from '@/components/layout/AppHeader.vue'

interface Character {
  id: string
  name: string
  description: string | null
  avatar_url: string | null
  is_canonical: boolean
  traits: {
    personality?: string
    skills?: string[]
    appearance?: string
  } | null
  universe?: { id: string; name: string }
  creator?: { id: string; username: string }
  created_at: string
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const character = ref<Character | null>(null)
const loading = ref(true)
const error = ref('')

const currentUser = computed(() => authStore.user)
const isOwner = computed(() => character.value?.creator?.id === currentUser.value?.id)
const canEdit = computed(() => isOwner.value && !character.value?.is_canonical)

const loadCharacter = async () => {
  loading.value = true
  error.value = ''

  try {
    const res = await charactersApi.get(route.params.id as string)
    character.value = res.data.character
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } }
    error.value = e.response?.data?.message || 'Персонажа не знайдено'
  } finally {
    loading.value = false
  }
}

const deleteCharacter = async () => {
  if (!character.value) return
  if (!confirm('Ви впевнені, що хочете видалити цього персонажа?')) return

  try {
    await charactersApi.delete(character.value.id)
    router.push('/characters')
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } }
    alert(e.response?.data?.message || 'Помилка видалення')
  }
}

onMounted(loadCharacter)
</script>

<template>
  <div class="character-page">
    <AppHeader />

    <main class="main">
      <!-- Loading -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="card error-card">
        <div class="error-icon">😔</div>
        <h3 class="error-title">{{ error }}</h3>
        <RouterLink to="/characters" class="back-link">
          Повернутись до персонажів
        </RouterLink>
      </div>

      <!-- Character -->
      <template v-else-if="character">
        <div class="card profile-card">
          <div class="profile-header">
            <div class="avatar">
              <img v-if="character.avatar_url" :src="character.avatar_url" :alt="character.name" />
              <span v-else>{{ character.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="profile-info">
              <h1 class="character-name">
                {{ character.name }}
                <span v-if="character.is_canonical" class="canonical-badge">Канонічний</span>
              </h1>
              <p v-if="character.universe" class="universe">{{ character.universe.name }}</p>
              <p v-if="character.creator" class="creator">
                Створено:
                <RouterLink :to="`/users/${character.creator.id}`" class="creator-link">
                  {{ character.creator.username }}
                </RouterLink>
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="canEdit" class="actions">
            <RouterLink :to="`/characters/${character.id}/edit`" class="btn-edit">
              Редагувати
            </RouterLink>
            <button @click="deleteCharacter" class="btn-delete">
              Видалити
            </button>
          </div>
        </div>

        <!-- Description -->
        <div v-if="character.description" class="card">
          <h2 class="section-title">Опис</h2>
          <p class="description">{{ character.description }}</p>
        </div>

        <!-- Traits -->
        <div v-if="character.traits" class="card">
          <h2 class="section-title">Характеристики</h2>

          <div v-if="character.traits.personality" class="trait">
            <h3 class="trait-label">Особистість</h3>
            <p class="trait-value">{{ character.traits.personality }}</p>
          </div>

          <div v-if="character.traits.appearance" class="trait">
            <h3 class="trait-label">Зовнішність</h3>
            <p class="trait-value">{{ character.traits.appearance }}</p>
          </div>

          <div v-if="character.traits.skills && character.traits.skills.length > 0" class="trait">
            <h3 class="trait-label">Навички</h3>
            <div class="skills">
              <span v-for="skill in character.traits.skills" :key="skill" class="skill-tag">
                {{ skill }}
              </span>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<style scoped>
.character-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 20px;
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

.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 24px;
  margin-bottom: 24px;
}

/* Error State */
.error-card {
  text-align: center;
  padding: 48px;
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.error-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 8px;
}

.back-link {
  color: #4f46e5;
  font-weight: 500;
}

/* Profile */
.profile-header {
  display: flex;
  gap: 24px;
  margin-bottom: 20px;
}

.avatar {
  width: 120px;
  height: 120px;
  background: #eef2ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  font-weight: 600;
  color: #4f46e5;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-info {
  flex: 1;
}

.character-name {
  font-size: 1.75rem;
  font-weight: 700;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.canonical-badge {
  font-size: 0.75rem;
  padding: 4px 12px;
  background: #dcfce7;
  color: #16a34a;
  border-radius: 9999px;
  font-weight: 500;
}

.universe {
  color: #4f46e5;
  font-weight: 500;
  margin-bottom: 8px;
}

.creator {
  color: #6b7280;
  font-size: 0.875rem;
}

.creator-link {
  color: #4f46e5;
  font-weight: 500;
}

.creator-link:hover {
  text-decoration: underline;
}

/* Actions */
.actions {
  display: flex;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.btn-edit {
  padding: 10px 20px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-edit:hover {
  background: #4338ca;
}

.btn-delete {
  padding: 10px 20px;
  color: #dc2626;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-delete:hover {
  background: #fef2f2;
}

/* Section */
.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 16px;
}

.description {
  color: #4b5563;
  line-height: 1.7;
  white-space: pre-wrap;
}

/* Traits */
.trait {
  margin-bottom: 16px;
}

.trait:last-child {
  margin-bottom: 0;
}

.trait-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 4px;
}

.trait-value {
  color: #6b7280;
  line-height: 1.5;
}

.skills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.skill-tag {
  padding: 4px 12px;
  background: #eef2ff;
  color: #4338ca;
  border-radius: 9999px;
  font-size: 0.875rem;
}

@media (max-width: 640px) {
  .profile-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .character-name {
    justify-content: center;
    flex-wrap: wrap;
  }

  .actions {
    justify-content: center;
  }
}
</style>
