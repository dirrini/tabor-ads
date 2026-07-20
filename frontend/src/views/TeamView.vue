<template>
  <div class="page">
    <header class="page-head">
      <div><span class="overline">{{ t('team.eyebrow') }}</span><h1>{{ t('team.title') }}</h1><p>{{ t('team.subtitle') }}</p></div>
    </header>

    <section class="panel team-panel permissions-team-panel">
      <div class="panel-head">
        <div><small>{{ t('team.members') }}</small><h2>{{ t('team.slots', { count: occupiedSlots, limit: workspace.limits?.members || 1 }) }}</h2></div>
      </div>
      <div class="team-permission-head"><span>{{ t('team.person') }}</span></div>
      <div v-for="member in workspace.members" :key="member.id" class="member permission-member">
        <div class="member-identity"><span>{{ member.name[0] }}</span><div><b>{{ member.name }}</b><small>{{ member.email }} · {{ t(`profile.roles.${member.role}`) }}</small></div></div>
        <label class="permission-check"><input v-model="member.can_create_campaigns" type="checkbox" :disabled="member.role === 'owner' || saving === member.id" @change="updatePermissions(member)"><span>{{ t('team.createCampaigns') }}</span></label>
        <label class="permission-check"><input v-model="member.can_view_metrics" type="checkbox" :disabled="member.role === 'owner' || saving === member.id" @change="updatePermissions(member)"><span>{{ t('team.viewMetrics') }}</span></label>
        <button v-if="member.role !== 'owner'" class="member-remove" type="button" :disabled="removing === `member-${member.id}`" @click="removeMember(member)">{{ t(removing === `member-${member.id}` ? 'team.removing' : 'team.remove') }}</button>
      </div>
      <div v-for="invitation in workspace.pending_invitations" :key="`invitation-${invitation.id}`" class="member permission-member pending-member">
        <div class="member-identity"><span>{{ (invitation.name || invitation.email)[0].toUpperCase() }}</span><div><b>{{ invitation.name || invitation.email }}</b><small><span v-if="invitation.name">{{ invitation.email }} · </span><span class="pending-badge">{{ t('team.pendingConfirmation') }}</span> · {{ t('team.expiresAt', { date: formatDate(invitation.expires_at) }) }}</small></div></div>
        <label class="permission-check"><input :checked="invitation.can_create_campaigns" type="checkbox" disabled><span>{{ t('team.createCampaigns') }}</span></label>
        <label class="permission-check"><input :checked="invitation.can_view_metrics" type="checkbox" disabled><span>{{ t('team.viewMetrics') }}</span></label>
        <button class="member-remove" type="button" :disabled="removing === `invitation-${invitation.id}`" @click="cancelInvitation(invitation)">{{ t(removing === `invitation-${invitation.id}` ? 'team.removing' : 'team.cancelInvitation') }}</button>
      </div>
    </section>

    <form class="panel invite-panel permission-invite-panel" @submit.prevent="invite">
      <div><small>{{ t('team.invite') }}</small><h2>{{ t('team.bring') }}</h2><p>{{ t('team.inviteText') }}</p></div>
      <label>{{ t('auth.name') }}<input v-model="inviteForm.name" type="text" maxlength="120" :placeholder="t('team.namePlaceholder')" :disabled="inviting"></label>
      <label>{{ t('common.email') }}<input v-model="inviteForm.email" type="email" required :placeholder="t('team.placeholder')" :disabled="inviting"></label>
      <div class="invite-permissions">
        <label class="permission-check"><input v-model="inviteForm.can_create_campaigns" type="checkbox" :disabled="inviting"><span>{{ t('team.createCampaigns') }}</span></label>
        <label class="permission-check"><input v-model="inviteForm.can_view_metrics" type="checkbox" :disabled="inviting"><span>{{ t('team.viewMetrics') }}</span></label>
      </div>
      <button class="btn btn-primary" :disabled="inviting" :aria-busy="inviting"><span v-if="inviting" class="button-spinner" aria-hidden="true"></span>{{ t(inviting ? 'team.sending' : 'team.send') }}</button>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'
import { useToastStore } from '../stores/toast'

const { t, locale } = useI18n()
const toast = useToastStore()
const workspace = reactive({})
const inviteForm = reactive({ name: '', email: '', can_create_campaigns: true, can_view_metrics: true })
const inviting = ref(false)
const saving = ref(null)
const removing = ref(null)
const occupiedSlots = computed(() => (workspace.members?.length || 0) + (workspace.pending_invitations?.length || 0))

function formatDate(value) {
  return new Intl.DateTimeFormat(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value))
}

async function load() {
  Object.assign(workspace, (await api('/api/workspace')).data)
}

async function invite() {
  inviting.value = true
  try {
    const response = await api('/api/workspace/invitations', { method: 'POST', body: JSON.stringify({ ...inviteForm, locale: locale.value }) })
    workspace.pending_invitations ||= []
    const existingIndex = workspace.pending_invitations.findIndex((item) => item.id === response.data.id)
    if (existingIndex >= 0) workspace.pending_invitations.splice(existingIndex, 1, response.data)
    else workspace.pending_invitations.unshift(response.data)
    inviteForm.name = ''
    inviteForm.email = ''
    toast.success(t('team.invitationSent'))
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    inviting.value = false
  }
}

async function removeMember(member) {
  if (!window.confirm(t('team.removeConfirm', { name: member.name }))) return
  removing.value = `member-${member.id}`
  try {
    await api(`/api/workspace/members/${member.id}`, { method: 'DELETE' })
    workspace.members = workspace.members.filter((item) => item.id !== member.id)
    toast.success(t('team.memberRemoved'))
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    removing.value = null
  }
}

async function cancelInvitation(invitation) {
  if (!window.confirm(t('team.cancelConfirm', { name: invitation.name || invitation.email }))) return
  removing.value = `invitation-${invitation.id}`
  try {
    await api(`/api/workspace/invitations/${invitation.id}`, { method: 'DELETE' })
    workspace.pending_invitations = workspace.pending_invitations.filter((item) => item.id !== invitation.id)
    toast.success(t('team.invitationCanceled'))
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    removing.value = null
  }
}

async function updatePermissions(member) {
  saving.value = member.id
  try {
    await api(`/api/workspace/members/${member.id}/permissions`, {
      method: 'PATCH',
      body: JSON.stringify({ can_create_campaigns: member.can_create_campaigns, can_view_metrics: member.can_view_metrics }),
    })
    toast.success(t('team.permissionsUpdated'))
  } catch (exception) {
    toast.error(exception.message)
    await load()
  } finally {
    saving.value = null
  }
}

onMounted(() => load().catch((exception) => toast.error(exception.message)))
</script>
