<template>
  <main class="auth-page">
    <RouterLink class="brand auth-brand" to="/"><BrandLogo /></RouterLink>
    <LocaleSwitcher class="auth-locale" />
    <section class="auth-card verification-card">
      <div class="verification-icon">✉</div>
      <div>
        <span class="eyebrow"><i /> {{ t('verification.eyebrow') }}</span>
        <h1>{{ t('verification.title') }}</h1>
        <p>{{ t('verification.text', { email: auth.user?.email }) }}</p>
      </div>
      <button class="btn btn-primary full" :disabled="sending" @click="resend">
        {{ t(sending ? 'common.loading' : 'verification.resend') }}
      </button>
      <button class="verification-logout" @click="logout">{{ t('verification.useAnotherAccount') }}</button>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import BrandLogo from '../components/BrandLogo.vue'
import LocaleSwitcher from '../components/LocaleSwitcher.vue'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const { t } = useI18n()
const sending = ref(false)

async function resend() {
  sending.value = true
  try {
    const result = await api('/api/auth/email/verification-notification', { method: 'POST' })
    toast.success(result.message)
  } catch (error) {
    toast.error(error.message)
  } finally {
    sending.value = false
  }
}

async function logout() {
  await auth.logout()
  await router.push('/login')
}
</script>
