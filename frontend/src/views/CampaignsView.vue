<template>
  <div class="page">
    <header class="page-head">
      <div>
        <span class="overline">{{ t('campaigns.eyebrow') }}</span>
        <h1>{{ t('campaigns.title') }}</h1>
        <p>{{ t('campaigns.usage', { used: standardCount, limit: limits.campaigns || '—' }) }}</p>
      </div>
      <button class="btn btn-primary" @click="showCreate = !showCreate">{{ t('campaigns.new') }}</button>
    </header>

    <form v-if="showCreate" class="campaign-create panel" @submit.prevent="createCampaign">
      <label class="campaign-name-field">
        {{ t('campaigns.name') }}
        <input v-model="form.name" required :placeholder="t('campaigns.namePlaceholder')">
      </label>
      <label :class="['simulation-toggle', { active: form.simulation }]">
        <input v-model="form.simulation" type="checkbox">
        <span class="toggle-track"><i /></span>
        <span>
          <strong>{{ t('campaigns.simulationMode') }}</strong>
          <small>{{ t('campaigns.simulationUnlimited') }}</small>
        </span>
      </label>
      <button class="btn btn-dark" :disabled="creating">
        {{ t(creating ? 'common.loading' : 'campaigns.create') }}
      </button>
    </form>

    <section class="campaign-list">
      <article v-for="campaign in campaigns" :key="campaign.id" class="campaign-card">
        <div class="campaign-summary">
          <span :class="['campaign-avatar', campaign.kind]">
            {{ campaign.kind === 'simulation' ? 'SIM' : initials(campaign.name) }}
          </span>
          <div>
            <div class="campaign-title">
              <h2>{{ campaign.name }}</h2>
              <span>{{ campaign.kind === 'simulation' ? t('campaigns.simulation') : statusLabel(campaign.status) }}</span>
            </div>
            <p>{{ t('campaigns.ads', { count: campaign.ads_count, id: campaign.public_id.slice(0, 8) }) }}</p>
          </div>
          <button v-if="campaign.kind === 'standard'" class="icon-btn" @click="toggleAd(campaign.id)">
            {{ t('campaigns.addAd') }}
          </button>
          <span v-else class="simulation-free-badge">{{ t('campaigns.outsideLimit') }}</span>
        </div>

        <form v-if="activeCampaign === campaign.id" class="ad-form" @submit.prevent="createAd(campaign)">
          <input v-model="adForm.name" required :placeholder="t('campaigns.adName')">
          <input v-model="adForm.destination_url" type="url" :placeholder="t('campaigns.destination')">
          <button class="btn btn-dark">{{ t('campaigns.add') }}</button>
        </form>

        <div v-if="campaign.ads?.length" class="ads-table">
          <div v-for="ad in campaign.ads" :key="ad.id">
            <span><i />{{ ad.name }}</span>
            <code>{{ pixel(ad.tracking_key) }}</code>
            <button @click="copy(pixel(ad.tracking_key))">{{ t('campaigns.copyPixel') }}</button>
          </div>
        </div>
      </article>
      <div v-if="!campaigns.length" class="panel empty large">{{ t('campaigns.empty') }}</div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api, API_URL } from '../lib/api'
import { useToastStore } from '../stores/toast'

const { t, te } = useI18n()
const toast = useToastStore()
const campaigns = ref([])
const standardCount = ref(0)
const limits = reactive({})
const showCreate = ref(false)
const creating = ref(false)
const form = reactive({ name: '', simulation: false })
const activeCampaign = ref(null)
const adForm = reactive({ name: '', destination_url: '' })

const initials = (name) => name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()
const pixel = (key) => `<img src="${API_URL || location.origin}/t/${key}.gif" width="1" height="1" alt="">`
const statusLabel = (status) => te(`campaigns.${status}`) ? t(`campaigns.${status}`) : status

async function load() {
  const response = await api('/api/campaigns')
  campaigns.value = response.data
  standardCount.value = response.standard_count
  Object.assign(limits, response.limits)
}

async function createCampaign() {
  creating.value = true
  try {
    await api('/api/campaigns', {
      method: 'POST',
      body: JSON.stringify({ name: form.name, status: 'active', simulation: form.simulation }),
    })
    form.name = ''
    form.simulation = false
    showCreate.value = false
    await load()
    toast.success(t('campaigns.created'))
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    creating.value = false
  }
}

function toggleAd(id) {
  activeCampaign.value = activeCampaign.value === id ? null : id
}

async function createAd(campaign) {
  try {
    await api(`/api/campaigns/${campaign.id}/ads`, { method: 'POST', body: JSON.stringify(adForm) })
    adForm.name = ''
    adForm.destination_url = ''
    activeCampaign.value = null
    await load()
    toast.success(t('campaigns.adCreated'))
  } catch (exception) {
    toast.error(exception.message)
  }
}

async function copy(text) {
  try {
    await navigator.clipboard.writeText(text)
    toast.success(t('campaigns.pixelCopied'))
  } catch {
    toast.error(t('common.copyError'))
  }
}

onMounted(() => load().catch((exception) => toast.error(exception.message)))
</script>
