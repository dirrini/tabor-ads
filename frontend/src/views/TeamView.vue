<template>
  <div class="page">
    <header class="page-head">
      <div>
        <span class="overline">{{ t('team.eyebrow') }}</span>
        <h1>{{ t('team.title') }}</h1>
        <p>{{ t('team.subtitle') }}</p>
      </div>
    </header>
    <section class="panel team-panel">
      <div class="panel-head">
        <div>
          <small>{{ t('team.members') }}</small>
          <h2>{{ t('team.slots', { count: workspace.members?.length || 0, limit: workspace.limits?.members || 1 }) }}</h2>
        </div>
      </div>
      <div v-for="member in workspace.members" :key="member.id" class="member">
        <span>{{ member.name[0] }}</span>
        <div><b>{{ member.name }}</b><small>{{ member.email }}</small></div>
      </div>
    </section>
    <form class="panel invite-panel" @submit.prevent="invite">
      <div>
        <small>{{ t('team.invite') }}</small>
        <h2>{{ t('team.bring') }}</h2>
        <p>{{ t('team.inviteText') }}</p>
      </div>
      <label>{{ t('common.email') }}<input v-model="email" type="email" required :placeholder="t('team.placeholder')"></label>
      <button class="btn btn-primary">{{ t('team.send') }}</button>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'
import { useToastStore } from '../stores/toast'

const { t } = useI18n()
const toast = useToastStore()
const workspace = reactive({})
const email = ref('')

async function load() {
  Object.assign(workspace, (await api('/api/workspace')).data)
}

async function invite() {
  try {
    await api('/api/workspace/invitations', { method: 'POST', body: JSON.stringify({ email: email.value }) })
    email.value = ''
    toast.success(t('team.invitationSent'))
  } catch (exception) {
    toast.error(exception.message)
  }
}

onMounted(() => load().catch((exception) => toast.error(exception.message)))
</script>
