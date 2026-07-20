<template>
  <div class="app-shell">
    <aside class="sidebar">
      <RouterLink class="brand" to="/"><BrandLogo light /></RouterLink>
      <RouterLink class="workspace-chip" :class="{ premium: auth.owner && auth.premium }" to="/app/profile">
        <span>{{ initials }}</span>
        <div><b>{{ auth.workspace?.name }}</b><small>{{ auth.owner ? auth.workspace?.plan : t('shell.member') }}</small></div>
      </RouterLink>
      <nav>
        <RouterLink to="/app/dashboard"><span>⌁</span> {{ t('shell.dashboard') }}</RouterLink>
        <RouterLink v-if="auth.canCreateCampaigns" to="/app/campaigns"><span>◫</span> {{ t('shell.campaigns') }}</RouterLink>
        <RouterLink v-if="auth.owner" to="/app/team"><span>♙</span> {{ t('shell.team') }}</RouterLink>
        <RouterLink v-if="auth.owner" to="/app/billing"><span>◇</span> {{ t('shell.billing') }}</RouterLink>
        <RouterLink class="profile-nav-link" to="/app/profile"><span>◉</span> {{ t('shell.profile') }}</RouterLink>
      </nav>
      <LocaleSwitcher persist class="app-locale" />
      <div class="sidebar-foot">
        <div class="user-mini"><span>{{ auth.user?.name?.[0] }}</span><div><b>{{ auth.user?.name }}</b><small>{{ auth.user?.email }}</small></div></div>
        <button :title="t('shell.logout')" @click="logout">↗</button>
      </div>
    </aside>
    <main class="app-main"><RouterView /></main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { createEcho } from '../lib/echo'
import LocaleSwitcher from '../components/LocaleSwitcher.vue'
import BrandLogo from '../components/BrandLogo.vue'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const { t } = useI18n()
const initials = computed(() => auth.workspace?.name?.split(' ').map((part) => part[0]).slice(0, 2).join(''))
let billingEcho = null

function setupBillingUpdates() {
  if (!auth.workspace?.id || !auth.owner) return
  billingEcho = createEcho()
  billingEcho.private(`workspaces.${auth.workspace.id}.billing`)
    .listenToAll((event, payload) => {
      if (event === '.workspace.plan.updated') auth.applyWorkspacePlan(payload)
    })
}

async function logout() {
  try {
    await auth.logout()
    toast.success(t('shell.logoutSuccess'))
    await router.push('/')
  } catch (exception) {
    toast.error(exception.message)
  }
}

onMounted(setupBillingUpdates)
onBeforeUnmount(() => billingEcho?.disconnect())
</script>
