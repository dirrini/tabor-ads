<template>
  <div class="app-shell">
    <aside class="sidebar">
      <RouterLink class="brand" to="/"><BrandLogo light /></RouterLink>
      <div ref="workspaceSwitcher" class="workspace-switcher">
        <button class="workspace-chip" :class="{ premium: auth.owner && auth.premium, open: workspaceMenuOpen }" type="button" :aria-expanded="workspaceMenuOpen" @click="workspaceMenuOpen = !workspaceMenuOpen">
          <span>{{ initials }}</span>
          <div><b>{{ auth.workspace?.name }}</b><small>{{ auth.owner ? auth.workspace?.plan : t('shell.member') }}</small></div>
          <i aria-hidden="true">{{ workspaceMenuOpen ? '▲' : '▼' }}</i>
        </button>
        <div v-if="workspaceMenuOpen" class="workspace-menu">
          <small>{{ t('workspaceSwitcher.title') }}</small>
          <button
            v-for="workspace in auth.workspaces"
            :key="workspace.id"
            type="button"
            :class="{ active: workspace.id === auth.workspace?.id }"
            :disabled="switchingWorkspace"
            @click="selectWorkspace(workspace.id)"
          >
            <span>{{ workspaceInitials(workspace.name) }}</span>
            <div><b>{{ workspace.name }}</b><small>{{ workspace.permissions.owner ? workspace.plan : t('shell.member') }}</small></div>
            <i v-if="workspace.id === auth.workspace?.id">✓</i>
          </button>
        </div>
      </div>
      <nav>
        <RouterLink to="/app/dashboard"><AppIcon name="dashboard" /> {{ t('shell.dashboard') }}</RouterLink>
        <RouterLink v-if="auth.canCreateCampaigns" to="/app/campaigns"><AppIcon name="campaigns" /> {{ t('shell.campaigns') }}</RouterLink>
        <RouterLink v-if="auth.owner" to="/app/team"><AppIcon name="team" /> {{ t('shell.team') }}</RouterLink>
        <RouterLink v-if="auth.owner" to="/app/billing"><AppIcon name="billing" /> {{ t('shell.billing') }}</RouterLink>
      </nav>
      <LocaleSwitcher persist class="app-locale" />
      <RouterLink class="mobile-profile-button" to="/app/profile" :title="t('shell.profile')" :aria-label="t('shell.profile')">
        <span>{{ userInitials }}</span>
      </RouterLink>
      <div class="sidebar-foot">
        <RouterLink class="user-mini" to="/app/profile"><span>{{ userInitials }}</span><div><b>{{ auth.user?.name }}</b><small>{{ auth.user?.email }}</small></div></RouterLink>
        <button :title="t('shell.logout')" :aria-label="t('shell.logout')" @click="logout"><AppIcon name="logout" /></button>
      </div>
    </aside>
    <main class="app-main"><RouterView :key="auth.workspace?.id" /></main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { createEcho } from '../lib/echo'
import LocaleSwitcher from '../components/LocaleSwitcher.vue'
import BrandLogo from '../components/BrandLogo.vue'
import AppIcon from '../components/AppIcon.vue'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const { t } = useI18n()
const initials = computed(() => auth.workspace?.name?.split(' ').map((part) => part[0]).slice(0, 2).join(''))
const userInitials = computed(() => auth.user?.name?.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase())
const workspaceSwitcher = ref(null)
const workspaceMenuOpen = ref(false)
const switchingWorkspace = ref(false)
let billingEcho = null

function setupBillingUpdates() {
  billingEcho?.disconnect()
  billingEcho = null
  if (!auth.workspace?.id || !auth.owner) return
  billingEcho = createEcho()
  billingEcho.private(`workspaces.${auth.workspace.id}.billing`)
    .listenToAll((event, payload) => {
      if (event === '.workspace.plan.updated') auth.applyWorkspacePlan(payload)
    })
}

const workspaceInitials = (name) => name?.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()

async function selectWorkspace(workspaceId) {
  if (workspaceId === auth.workspace?.id || switchingWorkspace.value) {
    workspaceMenuOpen.value = false
    return
  }
  switchingWorkspace.value = true
  try {
    const result = await auth.switchWorkspace(workspaceId)
    workspaceMenuOpen.value = false
    toast.success(result.message)
    await router.push('/app/dashboard')
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    switchingWorkspace.value = false
  }
}

function closeWorkspaceMenu(event) {
  if (!workspaceSwitcher.value?.contains(event.target)) workspaceMenuOpen.value = false
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

watch(() => auth.workspace?.id, setupBillingUpdates)
onMounted(() => {
  setupBillingUpdates()
  document.addEventListener('click', closeWorkspaceMenu)
})
onBeforeUnmount(() => {
  billingEcho?.disconnect()
  document.removeEventListener('click', closeWorkspaceMenu)
})
</script>
