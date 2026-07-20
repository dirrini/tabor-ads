<template>
  <div class="page profile-page">
    <header class="page-head">
      <div>
        <span class="overline">{{ t('profile.eyebrow') }}</span>
        <h1>{{ t('profile.title') }}</h1>
        <p>{{ t('profile.subtitle') }}</p>
      </div>
    </header>

    <div v-if="loading" class="panel profile-loading">{{ t('common.loading') }}</div>

    <template v-else-if="profile.user">
      <section class="profile-plan-grid" :class="{ 'member-profile-grid': !profile.workspace.permissions.owner }">
        <article v-if="profile.workspace.permissions.owner" class="profile-plan-card" :class="{ premium: profile.workspace.plan === 'premium' }">
          <div>
            <span class="plan-badge">{{ t('profile.currentPlan') }}</span>
            <h2>{{ planName }}</h2>
            <p>{{ planDescription }}</p>
          </div>
          <div class="profile-expiration">
            <small>{{ t('profile.expiresAt') }}</small>
            <strong>{{ expiration }}</strong>
            <span v-if="profile.subscription">{{ subscriptionCycle }} · {{ subscriptionStatus }}</span>
          </div>
          <RouterLink v-if="profile.workspace.role === 'owner'" class="btn btn-primary" to="/app/billing">
            {{ t(profile.workspace.plan === 'premium' ? 'profile.managePlan' : 'profile.viewPlans') }}
          </RouterLink>
        </article>

        <article class="panel workspace-summary">
          <div class="panel-head">
            <div>
              <small>{{ t('profile.workspace') }}</small>
              <h2>{{ profile.workspace.name }}</h2>
            </div>
            <span class="role-badge">{{ roleName }}</span>
          </div>
          <dl class="profile-details">
            <div><dt>{{ t('profile.identifier') }}</dt><dd>{{ profile.workspace.slug }}</dd></div>
            <div><dt>{{ t('profile.createdAt') }}</dt><dd>{{ formatDate(profile.workspace.created_at) }}</dd></div>
            <div><dt>{{ t('profile.memberSince') }}</dt><dd>{{ formatDate(profile.workspace.joined_at) }}</dd></div>
            <div v-if="profile.workspace.permissions.owner"><dt>{{ t('profile.realtime') }}</dt><dd>{{ t(profile.workspace.limits.realtime ? 'common.enabled' : 'common.disabled') }}</dd></div>
            <div v-else><dt>{{ t('profile.createCampaigns') }}</dt><dd>{{ t(profile.workspace.permissions.can_create_campaigns ? 'common.enabled' : 'common.disabled') }}</dd></div>
            <div v-if="!profile.workspace.permissions.owner"><dt>{{ t('profile.viewMetrics') }}</dt><dd>{{ t(profile.workspace.permissions.can_view_metrics ? 'common.enabled' : 'common.disabled') }}</dd></div>
          </dl>
        </article>
      </section>

      <section v-if="profile.workspace.permissions.owner" class="profile-usage-grid">
        <article><small>{{ t('profile.campaignUsage') }}</small><strong>{{ profile.workspace.usage.campaigns }} / {{ profile.workspace.limits.campaigns }}</strong><span>{{ t('profile.standardCampaigns') }}</span></article>
        <article><small>{{ t('profile.adsLimit') }}</small><strong>{{ profile.workspace.limits.ads_per_campaign }}</strong><span>{{ t('profile.perCampaign') }}</span></article>
        <article><small>{{ t('profile.memberUsage') }}</small><strong>{{ profile.workspace.usage.members }} / {{ profile.workspace.limits.members }}</strong><span>{{ t('profile.workspaceSeats') }}</span></article>
        <article><small>{{ t('profile.simulations') }}</small><strong>{{ profile.workspace.usage.simulation_campaigns }}</strong><span>{{ t('profile.outsideLimit') }}</span></article>
      </section>

      <section class="panel profile-workspaces">
        <div class="panel-head">
          <div>
            <small>{{ t('profile.workspaces') }}</small>
            <h2>{{ t('profile.manageWorkspaces') }}</h2>
            <p>{{ t('profile.workspacesText') }}</p>
          </div>
          <button class="btn btn-ghost" type="button" @click="showWorkspaceForm = !showWorkspaceForm">
            {{ t('profile.newWorkspace') }}
          </button>
        </div>

        <form v-if="showWorkspaceForm" class="new-workspace-form" @submit.prevent="createWorkspace">
          <label>
            {{ t('profile.workspaceName') }}
            <input v-model="workspaceName" maxlength="120" :placeholder="t('profile.workspaceNamePlaceholder')" required>
          </label>
          <button class="btn btn-primary" :disabled="creatingWorkspace">
            <span v-if="creatingWorkspace" class="button-spinner"></span>
            {{ t(creatingWorkspace ? 'profile.creatingWorkspace' : 'profile.createWorkspace') }}
          </button>
          <button class="btn workspace-form-cancel" type="button" :disabled="creatingWorkspace" @click="cancelWorkspaceCreation">
            {{ t('common.cancel') }}
          </button>
        </form>

        <div class="profile-workspace-list">
          <button
            v-for="workspace in auth.workspaces"
            :key="workspace.id"
            type="button"
            :class="{ active: workspace.id === auth.workspace?.id, premium: workspace.permissions.owner && workspace.plan === 'premium' }"
            :disabled="switchingWorkspace || workspace.id === auth.workspace?.id"
            @click="switchWorkspace(workspace.id)"
          >
            <span>{{ workspaceInitials(workspace.name) }}</span>
            <div>
              <b>{{ workspace.name }}</b>
              <small>{{ workspace.permissions.owner ? t(`profile.${workspace.plan}`) : t(`profile.roles.${workspace.role}`) }}</small>
            </div>
            <i>{{ workspace.id === auth.workspace?.id ? t('profile.currentWorkspace') : t('profile.switchWorkspace') }}</i>
          </button>
        </div>
      </section>

      <section class="profile-account-grid">
        <article class="panel account-card">
          <div class="panel-head">
            <div>
              <small>{{ t('profile.account') }}</small>
              <h2>{{ t('profile.personalInfo') }}</h2>
            </div>
            <span class="account-avatar">{{ initials }}</span>
          </div>
          <form class="profile-name-form" @submit.prevent="updateName">
            <label>
              {{ t('profile.name') }}
              <input v-model="accountName" maxlength="120" autocomplete="name" required>
            </label>
            <button class="btn btn-dark" :disabled="updatingName || accountName.trim() === profile.user.name">
              <span v-if="updatingName" class="button-spinner"></span>
              {{ t(updatingName ? 'profile.savingName' : 'profile.saveName') }}
            </button>
          </form>
          <dl class="profile-details account-details">
            <div><dt>{{ t('common.email') }}</dt><dd>{{ profile.user.email }} <span class="verified-badge">{{ t(profile.user.email_verified ? 'profile.verified' : 'profile.notVerified') }}</span></dd></div>
            <div><dt>{{ t('profile.accountSince') }}</dt><dd>{{ formatDate(profile.user.created_at) }}</dd></div>
            <div><dt>{{ t('profile.language') }}</dt><dd>{{ profile.user.locale === 'en' ? 'English' : 'Português (Brasil)' }}</dd></div>
            <div><dt>{{ t('profile.loginMethods') }}</dt><dd>{{ loginMethods }}</dd></div>
          </dl>
        </article>

        <form class="panel password-card" @submit.prevent="changePassword">
          <div>
            <small>{{ t('profile.security') }}</small>
            <h2>{{ t(profile.user.has_password ? 'profile.changePassword' : 'profile.createPassword') }}</h2>
            <p>{{ t(profile.user.has_password ? 'profile.changePasswordText' : 'profile.createPasswordText') }}</p>
          </div>
          <label v-if="profile.user.has_password">
            {{ t('profile.currentPassword') }}
            <input v-model="password.current_password" type="password" autocomplete="current-password" required>
          </label>
          <label>
            {{ t('profile.newPassword') }}
            <input v-model="password.password" type="password" autocomplete="new-password" minlength="8" required>
          </label>
          <label>
            {{ t('profile.confirmPassword') }}
            <input v-model="password.password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
          </label>
          <small class="password-hint">{{ t('profile.passwordHint') }}</small>
          <button class="btn btn-dark" :disabled="submitting">{{ t(submitting ? 'common.loading' : 'profile.savePassword') }}</button>
        </form>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const { t, locale } = useI18n()
const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const router = useRouter()
const loading = ref(true)
const submitting = ref(false)
const updatingName = ref(false)
const creatingWorkspace = ref(false)
const switchingWorkspace = ref(false)
const showWorkspaceForm = ref(route.query.newWorkspace === '1')
const workspaceName = ref('')
const accountName = ref('')
const profile = reactive({ user: null, workspace: null, subscription: null })
const password = reactive({ current_password: '', password: '', password_confirmation: '' })

const initials = computed(() => profile.user?.name?.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase())
const planName = computed(() => t(profile.workspace?.plan === 'premium' ? 'profile.premium' : 'profile.free'))
const planDescription = computed(() => t(profile.workspace?.plan === 'premium' ? 'profile.premiumDescription' : 'profile.freeDescription'))
const roleName = computed(() => t(`profile.roles.${profile.workspace?.role || 'member'}`))
const subscriptionCycle = computed(() => t(`profile.cycles.${profile.subscription?.provider_plan_id || 'premium'}`))
const subscriptionStatus = computed(() => t(`profile.statuses.${profile.subscription?.status || 'active'}`))
const expiration = computed(() => {
  if (profile.workspace?.plan !== 'premium') return t('profile.notApplicable')
  return profile.subscription?.current_period_end ? formatDate(profile.subscription.current_period_end) : t('profile.noExpiration')
})
const loginMethods = computed(() => {
  const methods = []
  if (profile.user?.has_password) methods.push(t('profile.emailPassword'))
  if (profile.user?.providers?.includes('google')) methods.push('Google')
  return methods.join(' · ') || t('profile.noLoginMethod')
})
const workspaceInitials = (name) => name?.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()

function formatDate(value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'pt-BR', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value))
}

async function loadProfile() {
  loading.value = true
  try {
    Object.assign(profile, (await api('/api/profile')).data)
    accountName.value = profile.user.name
  } catch (error) {
    toast.error(error.message)
  } finally {
    loading.value = false
  }
}

async function changePassword() {
  submitting.value = true
  try {
    const result = await api('/api/profile/password', { method: 'PUT', body: JSON.stringify(password) })
    profile.user.has_password = true
    Object.assign(password, { current_password: '', password: '', password_confirmation: '' })
    toast.success(result.message)
  } catch (error) {
    toast.error(error.message)
  } finally {
    submitting.value = false
  }
}

async function updateName() {
  if (updatingName.value) return
  updatingName.value = true
  try {
    const result = await auth.updateName(accountName.value.trim())
    profile.user.name = result.user.name
    accountName.value = result.user.name
    toast.success(result.message)
  } catch (error) {
    toast.error(error.message)
  } finally {
    updatingName.value = false
  }
}

function cancelWorkspaceCreation() {
  showWorkspaceForm.value = false
  workspaceName.value = ''
  if (route.query.newWorkspace) router.replace('/app/profile')
}

async function createWorkspace() {
  if (creatingWorkspace.value) return
  creatingWorkspace.value = true
  try {
    const result = await auth.createWorkspace(workspaceName.value.trim())
    toast.success(result.message)
    await router.replace('/app/profile')
  } catch (error) {
    toast.error(error.message)
  } finally {
    creatingWorkspace.value = false
  }
}

async function switchWorkspace(workspaceId) {
  switchingWorkspace.value = true
  try {
    const result = await auth.switchWorkspace(workspaceId)
    toast.success(result.message)
  } catch (error) {
    toast.error(error.message)
  } finally {
    switchingWorkspace.value = false
  }
}

onMounted(loadProfile)
</script>
