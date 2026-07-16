<template>
  <main class="auth-page">
    <RouterLink class="brand auth-brand" to="/"><BrandLogo /></RouterLink>
    <LocaleSwitcher class="auth-locale" />
    <section class="auth-card">
      <div>
        <span class="eyebrow"><i /> {{ t(registering ? 'auth.registerEyebrow' : 'auth.loginEyebrow') }}</span>
        <h1>{{ t(registering ? 'auth.registerTitle' : 'auth.loginTitle') }}</h1>
        <p>{{ t(registering ? 'auth.registerText' : 'auth.loginText') }}</p>
      </div>
      <a class="btn google-btn" :href="googleUrl">
        <svg class="google-mark" viewBox="0 0 18 18" aria-hidden="true">
          <path fill="#4285F4" d="M17.64 9.205c0-.638-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.258h2.909c1.702-1.567 2.684-3.875 2.684-6.614Z" />
          <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.181l-2.909-2.258c-.806.54-1.836.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z" />
          <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z" />
          <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z" />
        </svg>
        {{ t('auth.google') }}
      </a>
      <div class="divider"><span>{{ t('auth.divider') }}</span></div>
      <form @submit.prevent="submit">
        <label v-if="registering">{{ t('auth.name') }}<input v-model="form.name" required :placeholder="t('auth.namePlaceholder')"></label>
        <label v-if="registering">{{ t('auth.workspace') }}<input v-model="form.workspace_name" :placeholder="t('auth.workspacePlaceholder')"></label>
        <label>{{ t('common.email') }}<input v-model="form.email" required type="email" :placeholder="t('auth.emailPlaceholder')"></label>
        <label>{{ t('common.password') }}<input v-model="form.password" required type="password" :placeholder="t('auth.passwordPlaceholder')"></label>
        <label v-if="registering">{{ t('auth.confirm') }}<input v-model="form.password_confirmation" required type="password"></label>
        <button class="btn btn-primary full" :disabled="loading">{{ t(loading ? 'common.loading' : registering ? 'auth.create' : 'auth.login') }}</button>
      </form>
      <p class="auth-switch">
        {{ t(registering ? 'auth.haveAccount' : 'auth.noAccount') }}
        <RouterLink :to="registering ? '/login' : '/register'">{{ t(registering ? 'auth.login' : 'auth.createFree') }}</RouterLink>
      </p>
    </section>
  </main>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BrandLogo from '../components/BrandLogo.vue'
import LocaleSwitcher from '../components/LocaleSwitcher.vue'
import { API_URL } from '../lib/api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const { t, locale } = useI18n()
const registering = computed(() => route.path === '/register')
const googleUrl = computed(() => `${API_URL}/api/auth/google/redirect?locale=${encodeURIComponent(locale.value)}`)
const form = reactive({
  name: '',
  workspace_name: '',
  email: route.query.email || '',
  password: '',
  password_confirmation: '',
  invitation_token: route.query.invite || '',
})
const loading = ref(false)

async function submit() {
  loading.value = true
  try {
    const payload = { ...form, ...(registering.value ? { locale: locale.value } : {}) }
    if (registering.value) await auth.register(payload)
    else await auth.login(payload)
    toast.success(t(registering.value ? 'auth.registerSuccess' : 'auth.loginSuccess'))
    await router.push(route.query.redirect || '/app/dashboard')
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    loading.value = false
  }
}
</script>
