<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const error = ref('')
const loading = ref(true)

onMounted(async () => {
  // Get token and user from URL params (sent by backend after OAuth)
  const token = route.query.token as string
  const userJson = route.query.user as string

  if (token && userJson) {
    try {
      const user = JSON.parse(decodeURIComponent(userJson))
      await authStore.handleOAuthCallback(token, user)
      router.push('/dashboard')
    } catch (e) {
      error.value = 'Помилка обробки даних авторизації'
      loading.value = false
    }
  } else {
    // Check for error in query
    const errorMessage = route.query.error as string
    error.value = errorMessage || 'Помилка авторизації. Спробуйте ще раз.'
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <div v-if="loading" class="space-y-4">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto"></div>
        <p class="text-gray-600">Завершення авторизації...</p>
      </div>

      <div v-else class="space-y-4">
        <div class="text-red-500 text-5xl">⚠️</div>
        <p class="text-red-600">{{ error }}</p>
        <router-link
          to="/login"
          class="inline-block bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 transition-colors"
        >
          Повернутися до входу
        </router-link>
      </div>
    </div>
  </div>
</template>
