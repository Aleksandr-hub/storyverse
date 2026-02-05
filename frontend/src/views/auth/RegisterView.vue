<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
})

const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

const handleSubmit = async () => {
  errors.value = {}
  generalError.value = ''

  const result = await authStore.register(form)

  if (result.success) {
    router.push('/dashboard')
  } else {
    if (result.errors) {
      errors.value = result.errors
    }
    generalError.value = result.message || ''
  }
}

const loginWithOAuth = (provider: 'google' | 'facebook') => {
  authStore.loginWithOAuth(provider)
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <!-- Header -->
      <div class="auth-header">
        <h1 class="auth-logo">📚 StoryVerse</h1>
        <h2 class="auth-title">Створити акаунт</h2>
        <p class="auth-subtitle">Приєднуйся до спільноти творчих авторів</p>
      </div>

      <!-- OAuth Buttons -->
      <div class="oauth-buttons">
        <button @click="loginWithOAuth('google')" class="oauth-btn">
          <svg class="oauth-icon" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          Реєстрація через Google
        </button>

        <button @click="loginWithOAuth('facebook')" class="oauth-btn">
          <svg class="oauth-icon" fill="#1877F2" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
          Реєстрація через Facebook
        </button>
      </div>

      <!-- Divider -->
      <div class="divider">
        <span>або</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="auth-form">
        <!-- Error Alert -->
        <div v-if="generalError" class="error-alert">
          {{ generalError }}
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            class="form-input"
            :class="{ 'input-error': errors.email }"
            placeholder="your@email.com"
          />
          <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
        </div>

        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            required
            class="form-input"
            :class="{ 'input-error': errors.username }"
            placeholder="coolwriter"
          />
          <p v-if="errors.username" class="field-error">{{ errors.username[0] }}</p>
          <p v-else class="field-hint">Літери, цифри, дефіс та підкреслення</p>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Пароль</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            class="form-input"
            :class="{ 'input-error': errors.password }"
            placeholder="Мінімум 8 символів"
          />
          <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
        </div>

        <div class="form-group">
          <label for="password_confirmation" class="form-label">Підтвердження пароля</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            class="form-input"
            placeholder="Повторіть пароль"
          />
        </div>

        <button type="submit" :disabled="authStore.loading" class="submit-btn">
          <span v-if="authStore.loading">Завантаження...</span>
          <span v-else>Створити акаунт</span>
        </button>
      </form>

      <!-- Footer -->
      <p class="auth-footer">
        Вже є акаунт?
        <RouterLink to="/login" class="auth-link">Увійти</RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 40px 20px;
}

.auth-container {
  width: 100%;
  max-width: 400px;
}

.auth-header {
  text-align: center;
  margin-bottom: 32px;
}

.auth-logo {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 16px;
}

.auth-title {
  font-size: 1.25rem;
  color: #4b5563;
  font-weight: 400;
  margin-bottom: 8px;
}

.auth-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
}

/* OAuth */
.oauth-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.oauth-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: background 0.2s;
}

.oauth-btn:hover {
  background: #f9fafb;
}

.oauth-icon {
  width: 20px;
  height: 20px;
}

/* Divider */
.divider {
  position: relative;
  text-align: center;
  margin-bottom: 24px;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #d1d5db;
}

.divider span {
  position: relative;
  background: #f9fafb;
  padding: 0 16px;
  color: #6b7280;
  font-size: 0.875rem;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
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
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.2s;
  outline: none;
}

.form-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-input.input-error {
  border-color: #dc2626;
}

.field-error {
  color: #dc2626;
  font-size: 0.875rem;
}

.field-hint {
  color: #6b7280;
  font-size: 0.75rem;
}

.submit-btn {
  margin-top: 8px;
  padding: 14px 24px;
  background: #4f46e5;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.submit-btn:hover {
  background: #4338ca;
}

.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Footer */
.auth-footer {
  text-align: center;
  margin-top: 24px;
  color: #4b5563;
  font-size: 0.875rem;
}

.auth-link {
  color: #4f46e5;
  text-decoration: none;
  font-weight: 500;
}

.auth-link:hover {
  text-decoration: underline;
}
</style>
