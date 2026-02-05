<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { charactersApi, universesApi, uploadApi } from '@/services/api'
import AppHeader from '@/components/layout/AppHeader.vue'

interface Universe {
  id: string
  name: string
  slug: string
}

const route = useRoute()
const router = useRouter()

const isEditMode = computed(() => !!route.params.id)
const pageTitle = computed(() => isEditMode.value ? 'Редагувати персонажа' : 'Створити персонажа')

const universes = ref<Universe[]>([])
const loading = ref(false)
const loadingCharacter = ref(false)
const error = ref('')
const avatarUploading = ref(false)

const form = ref({
  name: '',
  description: '',
  avatar_url: '',
  universe_id: '',
  traits: {
    personality: '',
    appearance: '',
    skills: [] as string[],
  },
})

const newSkill = ref('')

const addSkill = () => {
  const skill = newSkill.value.trim()
  if (skill && !form.value.traits.skills.includes(skill)) {
    form.value.traits.skills.push(skill)
    newSkill.value = ''
  }
}

const removeSkill = (index: number) => {
  form.value.traits.skills.splice(index, 1)
}

const handleAvatarUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files || !input.files[0]) return

  const file = input.files[0]

  if (file.size > 5 * 1024 * 1024) {
    error.value = 'Файл занадто великий (макс. 5MB)'
    return
  }

  if (!file.type.startsWith('image/')) {
    error.value = 'Дозволені лише зображення'
    return
  }

  error.value = ''
  avatarUploading.value = true

  try {
    const res = await uploadApi.upload(file, 'avatar')
    form.value.avatar_url = res.data.url
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Помилка завантаження'
  } finally {
    avatarUploading.value = false
    input.value = ''
  }
}

const removeAvatar = () => {
  form.value.avatar_url = ''
}

const loadUniverses = async () => {
  try {
    const res = await universesApi.list()
    universes.value = res.data.data
  } catch (err) {
    console.error('Error loading universes:', err)
  }
}

const loadCharacter = async () => {
  if (!route.params.id) return

  loadingCharacter.value = true
  try {
    const res = await charactersApi.get(route.params.id as string)
    const character = res.data.character

    form.value = {
      name: character.name,
      description: character.description || '',
      avatar_url: character.avatar_url || '',
      universe_id: character.universe?.id || '',
      traits: {
        personality: character.traits?.personality || '',
        appearance: character.traits?.appearance || '',
        skills: character.traits?.skills || [],
      },
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Персонажа не знайдено'
  } finally {
    loadingCharacter.value = false
  }
}

const submit = async () => {
  if (!form.value.name.trim()) {
    error.value = 'Введіть ім\'я персонажа'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const data: any = {
      name: form.value.name.trim(),
      description: form.value.description.trim() || undefined,
      avatar_url: form.value.avatar_url || undefined,
      universe_id: form.value.universe_id || undefined,
    }

    // Add traits if any are filled
    if (form.value.traits.personality || form.value.traits.appearance || form.value.traits.skills.length > 0) {
      data.traits = {}
      if (form.value.traits.personality) data.traits.personality = form.value.traits.personality
      if (form.value.traits.appearance) data.traits.appearance = form.value.traits.appearance
      if (form.value.traits.skills.length > 0) data.traits.skills = form.value.traits.skills
    }

    if (isEditMode.value) {
      await charactersApi.update(route.params.id as string, data)
      router.push(`/characters/${route.params.id}`)
    } else {
      const res = await charactersApi.create(data)
      router.push(`/characters/${res.data.character.id}`)
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Помилка збереження'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUniverses()
  if (isEditMode.value) {
    loadCharacter()
  }
})
</script>

<template>
  <div class="create-page">
    <AppHeader />

    <main class="main">
      <div class="card">
        <h1 class="page-title">{{ pageTitle }}</h1>

        <!-- Loading character for edit -->
        <div v-if="loadingCharacter" class="loading">
          <div class="spinner"></div>
        </div>

        <form v-else @submit.prevent="submit" class="form">
          <!-- Avatar Upload -->
          <div class="form-group">
            <label class="form-label">Аватар</label>
            <div class="avatar-upload">
              <label v-if="!form.avatar_url" class="avatar-dropzone" :class="{ uploading: avatarUploading }">
                <input
                  type="file"
                  accept="image/*"
                  @change="handleAvatarUpload"
                  :disabled="avatarUploading"
                  class="avatar-input"
                />
                <span v-if="avatarUploading">Завантаження...</span>
                <span v-else class="avatar-text">
                  <span class="avatar-icon">📷</span>
                  Натисніть для завантаження
                </span>
              </label>
              <div v-else class="avatar-preview">
                <img :src="form.avatar_url" alt="Avatar" />
                <button type="button" @click="removeAvatar" class="avatar-remove">✕</button>
              </div>
            </div>
          </div>

          <!-- Name -->
          <div class="form-group">
            <label class="form-label">Ім'я *</label>
            <input
              v-model="form.name"
              type="text"
              class="form-input"
              placeholder="Введіть ім'я персонажа"
              required
            />
          </div>

          <!-- Universe -->
          <div class="form-group">
            <label class="form-label">Всесвіт</label>
            <select v-model="form.universe_id" class="form-input">
              <option value="">Оригінальний</option>
              <option v-for="u in universes" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label">Опис</label>
            <textarea
              v-model="form.description"
              class="form-input form-textarea"
              rows="4"
              placeholder="Розкажіть про цього персонажа..."
            ></textarea>
          </div>

          <!-- Traits Section -->
          <div class="traits-section">
            <h2 class="traits-title">Характеристики</h2>

            <!-- Personality -->
            <div class="form-group">
              <label class="form-label">Особистість</label>
              <input
                v-model="form.traits.personality"
                type="text"
                class="form-input"
                placeholder="Наприклад: хоробрий, допитливий, саркастичний"
              />
            </div>

            <!-- Appearance -->
            <div class="form-group">
              <label class="form-label">Зовнішність</label>
              <textarea
                v-model="form.traits.appearance"
                class="form-input form-textarea"
                rows="2"
                placeholder="Опишіть зовнішність персонажа"
              ></textarea>
            </div>

            <!-- Skills -->
            <div class="form-group">
              <label class="form-label">Навички</label>
              <div class="skills-input">
                <input
                  v-model="newSkill"
                  type="text"
                  class="form-input"
                  placeholder="Введіть навичку"
                  @keyup.enter.prevent="addSkill"
                />
                <button type="button" @click="addSkill" class="btn-add-skill">+</button>
              </div>
              <div v-if="form.traits.skills.length > 0" class="skills-list">
                <span v-for="(skill, index) in form.traits.skills" :key="skill" class="skill-tag">
                  {{ skill }}
                  <button type="button" @click="removeSkill(index)" class="skill-remove">×</button>
                </span>
              </div>
            </div>
          </div>

          <!-- Error -->
          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <!-- Submit -->
          <div class="form-actions">
            <button type="button" @click="router.back()" class="btn-cancel">
              Скасувати
            </button>
            <button type="submit" :disabled="loading" class="btn-submit">
              {{ loading ? 'Збереження...' : (isEditMode ? 'Зберегти' : 'Створити') }}
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<style scoped>
.create-page {
  min-height: 100vh;
  background: #f9fafb;
}

.main {
  max-width: 640px;
  margin: 0 auto;
  padding: 32px 20px;
}

.card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  padding: 32px;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 24px;
}

.loading {
  display: flex;
  justify-content: center;
  padding: 48px;
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

.form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  transition: all 0.2s;
}

.form-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
  resize: none;
}

/* Avatar Upload */
.avatar-upload {
  width: 120px;
}

.avatar-dropzone {
  width: 120px;
  height: 120px;
  border: 2px dashed #d1d5db;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.avatar-dropzone:hover {
  border-color: #4f46e5;
  background: #f9fafb;
}

.avatar-dropzone.uploading {
  opacity: 0.5;
  cursor: not-allowed;
}

.avatar-input {
  display: none;
}

.avatar-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  color: #6b7280;
  text-align: center;
  padding: 8px;
}

.avatar-icon {
  font-size: 1.25rem;
}

.avatar-preview {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
}

.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  border-radius: 50%;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.avatar-remove:hover {
  background: #dc2626;
}

/* Traits Section */
.traits-section {
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.traits-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 16px;
}

/* Skills */
.skills-input {
  display: flex;
  gap: 8px;
}

.btn-add-skill {
  padding: 10px 16px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-add-skill:hover {
  background: #4338ca;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.skill-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  background: #eef2ff;
  color: #4338ca;
  border-radius: 9999px;
  font-size: 0.875rem;
}

.skill-remove {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6366f1;
  font-size: 1rem;
  transition: color 0.2s;
}

.skill-remove:hover {
  color: #dc2626;
}

/* Error */
.error-message {
  padding: 12px 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-size: 0.875rem;
}

/* Actions */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 10px 20px;
  color: #6b7280;
  font-weight: 500;
  transition: color 0.2s;
}

.btn-cancel:hover {
  color: #111827;
}

.btn-submit {
  padding: 10px 24px;
  background: #4f46e5;
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background: #4338ca;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
