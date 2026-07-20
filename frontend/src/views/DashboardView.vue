<template>
  <div class="page">
    <header class="page-head">
      <div>
        <span class="overline">{{ t('dashboard.eyebrow') }}</span>
        <h1>Dashboard</h1>
        <p>{{ t('dashboard.subtitle') }}</p>
      </div>
      <div class="head-actions">
        <span v-if="data.metrics_allowed" :class="['status-pill', data.realtime ? 'live' : 'static']">
          {{ t(data.realtime ? 'dashboard.realtime' : 'dashboard.snapshot') }}
        </span>
        <button class="btn btn-ghost" :disabled="loading" @click="load">
          {{ t(loading ? 'common.loading' : 'dashboard.refresh') }}
        </button>
      </div>
    </header>

    <section v-if="!data.metrics_allowed" class="restricted-dashboard">
      <div class="panel restricted-dashboard-copy">
        <span class="overline">{{ t('dashboard.workspaceOverview') }}</span>
        <h2>{{ t('dashboard.restrictedTitle') }}</h2>
        <p>{{ t('dashboard.restrictedText') }}</p>
      </div>
      <div class="metric-grid restricted-metrics">
        <article><small>{{ t('dashboard.active') }}</small><strong>{{ number(data.summary.active_campaigns) }}</strong><span>{{ t('dashboard.activeCampaignSummary') }}</span></article>
        <article><small>{{ t('dashboard.adsTotal') }}</small><strong>{{ number(data.summary.ads) }}</strong><span>{{ t('dashboard.adsSummary') }}</span></article>
      </div>
    </section>

    <template v-else>
    <section class="panel analytics-filters">
      <div class="filter-toolbar">
        <div>
          <small>{{ t('dashboard.filtersEyebrow') }}</small>
          <h2>{{ t('dashboard.filtersTitle') }}</h2>
          <p>{{ t('dashboard.filtersSubtitle') }}</p>
        </div>
        <div class="filter-actions">
          <label class="period-control">
            <span>{{ t('dashboard.period') }}</span>
            <select v-model.number="days" @change="load">
              <option v-for="period in periods" :key="period" :value="period">
                {{ t('dashboard.lastDays', { count: period }) }}
              </option>
            </select>
          </label>
          <button class="filter-reset" :disabled="!hasFilters" @click="resetFilters">
            {{ t('dashboard.resetFilters') }}
          </button>
        </div>
      </div>

      <div class="filter-groups">
        <div class="filter-block">
          <div class="filter-label">
            <strong>{{ t('dashboard.campaignFilter') }}</strong>
            <span>{{ campaignFilterHint }}</span>
          </div>
          <div class="filter-options">
            <label
              v-for="campaign in data.filters.campaigns"
              :key="campaign.id"
              :class="[
                'filter-option',
                { selected: selectedCampaignIds.includes(campaign.id), simulation: campaign.kind === 'simulation' },
              ]"
            >
              <input v-model="selectedCampaignIds" type="checkbox" :value="campaign.id" @change="campaignsChanged">
              <span>{{ campaign.name }}</span>
              <small v-if="campaign.kind === 'simulation'" class="filter-kind-badge">
                {{ t('campaigns.simulation') }}
              </small>
            </label>
            <span v-if="!data.filters.campaigns.length" class="filter-empty">{{ t('dashboard.noCampaignOptions') }}</span>
          </div>
        </div>

        <Transition name="filter-reveal">
          <div v-if="selectedCampaignIds.length" class="filter-block ad-filter-block">
            <div class="filter-label">
              <strong>{{ t('dashboard.adFilter') }}</strong>
              <span>{{ adFilterHint }}</span>
            </div>
            <div class="filter-options">
              <label
                v-for="ad in availableAds"
                :key="ad.id"
                :class="['filter-option', { selected: selectedAdIds.includes(ad.id) }]"
              >
                <input v-model="selectedAdIds" type="checkbox" :value="ad.id" @change="load">
                <span>{{ ad.name }}</span>
                <small>{{ campaignName(ad.campaign_id) }}</small>
              </label>
              <span v-if="!availableAds.length" class="filter-empty">{{ t('dashboard.noAdOptions') }}</span>
            </div>
          </div>
        </Transition>
      </div>
    </section>

    <section class="metric-grid analytics-metrics">
      <article>
        <small>{{ t('dashboard.total') }}</small>
        <strong>{{ number(data.total) }}</strong>
        <span>{{ selectedCampaignIds.length ? t('dashboard.filteredScope') : t('dashboard.allCampaigns') }}</span>
      </article>
      <article>
        <small>{{ t('dashboard.active') }}</small>
        <strong>{{ data.campaigns.length }}</strong>
        <span>{{ t('dashboard.available', { count: auth.workspace?.limits?.campaigns }) }}</span>
      </article>
      <article>
        <small>{{ t('dashboard.browser') }}</small>
        <strong>{{ topBrowser?.browser || '—' }}</strong>
        <span>{{ topBrowser ? t('dashboard.impressions', { count: number(topBrowser.total) }) : t('dashboard.noData') }}</span>
      </article>
      <article>
        <small>{{ t('dashboard.periodTrend') }}</small>
        <strong :class="['trend-number', data.trend_direction]">
          {{ trendArrow }} {{ percentage(data.trend_percentage) }}
        </strong>
        <span>{{ t('dashboard.comparedHalves') }}</span>
      </article>
    </section>

    <section class="panel trend-panel">
      <div class="panel-head trend-heading">
        <div>
          <small>{{ t('dashboard.timelineEyebrow') }}</small>
          <h2>{{ t('dashboard.timelineTitle') }}</h2>
        </div>
        <span :class="['trend-pill', data.trend_direction]">
          {{ t(`dashboard.trend.${data.trend_direction}`) }}
        </span>
      </div>
      <div class="trend-chart-wrap">
        <Line v-if="data.timeline.length" :data="lineData" :options="lineOptions" />
        <div v-else class="empty">{{ t('dashboard.noImpressions') }}</div>
      </div>
    </section>

    <section class="dashboard-grid">
      <article class="panel chart-panel">
        <div class="panel-head">
          <div>
            <small>{{ t('dashboard.performance') }}</small>
            <h2>{{ t('dashboard.byCampaign') }}</h2>
          </div>
        </div>
        <div class="chart-wrap">
          <Bar v-if="data.campaigns.length" :data="barData" :options="cartesianOptions" />
          <div v-else class="empty">{{ t('dashboard.createFirst') }}</div>
        </div>
      </article>
      <article class="panel">
        <div class="panel-head">
          <div>
            <small>{{ t('dashboard.audience') }}</small>
            <h2>{{ t('dashboard.browsers') }}</h2>
          </div>
        </div>
        <div class="donut-wrap">
          <Doughnut v-if="data.browsers.length" :data="donutData" :options="donutOptions" />
          <div v-else class="empty">{{ t('dashboard.noImpressions') }}</div>
        </div>
      </article>
    </section>

    <section class="panel simulation-panel">
      <div>
        <span class="sim-icon">⌁</span>
        <div>
          <small>{{ t('dashboard.safe') }} · {{ t('dashboard.simulationLive') }}</small>
          <h2>{{ t('dashboard.simulationTitle') }}</h2>
          <p>{{ t('dashboard.simulationText') }}</p>
        </div>
      </div>
      <div v-if="simulationCampaigns.length" class="simulation-switch-control">
        <div class="simulation-state-copy">
          <strong>{{ t(simulationActive ? 'dashboard.simulationOn' : 'dashboard.simulationOff') }}</strong>
          <span v-if="simulationActive">{{ t('dashboard.timeRemaining', { time: formattedSimulationTime }) }}</span>
          <span v-else>{{ t('dashboard.simulationLimit') }}</span>
        </div>
        <button
          :class="['simulation-master-switch', { active: simulationActive }]"
          role="switch"
          :aria-checked="simulationActive"
          :aria-label="t(simulationActive ? 'dashboard.turnSimulationOff' : 'dashboard.turnSimulationOn')"
          :disabled="startingSimulation"
          @click="toggleSimulation"
        >
          <span><i /></span>
          <b>{{ t(simulationActive ? 'dashboard.turnOff' : 'dashboard.turnOn') }}</b>
        </button>
      </div>
      <div v-else class="simulation-empty-action">
        <span>{{ t('dashboard.createSimulationFirst') }}</span>
      </div>
    </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bar, Doughnut, Line } from 'vue-chartjs'
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Filler,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  Tooltip,
} from 'chart.js'
import { api } from '../lib/api'
import { createEcho } from '../lib/echo'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

ChartJS.register(ArcElement, BarElement, CategoryScale, Filler, LineElement, LinearScale, PointElement, Tooltip, Legend)

const auth = useAuthStore()
const toast = useToastStore()
const { t, locale } = useI18n()
const periods = [7, 14, 30, 90]
const days = ref(30)
const selectedCampaignIds = ref([])
const selectedAdIds = ref([])
const loading = ref(false)
const simulationActive = ref(false)
const simulationSecondsRemaining = ref(180)
const startingSimulation = ref(false)
const simulationTickRunning = ref(false)
const data = reactive({
  metrics_allowed: auth.canViewMetrics,
  summary: { active_campaigns: 0, ads: 0 },
  campaigns: [],
  browsers: [],
  timeline: [],
  total: 0,
  trend_percentage: 0,
  trend_direction: 'stable',
  filters: { campaigns: [], ads: [], days: 30 },
  realtime: false,
})

let echo = null
let requestSequence = 0
let simulationTimer = null
let simulationToken = ''

const localeCode = computed(() => locale.value === 'en' ? 'en-US' : 'pt-BR')
const topBrowser = computed(() => [...data.browsers].sort((a, b) => b.total - a.total)[0])
const availableAds = computed(() => data.filters.ads.filter((ad) => selectedCampaignIds.value.includes(ad.campaign_id)))
const simulationCampaigns = computed(() => data.filters.campaigns.filter((campaign) => campaign.kind === 'simulation'))
const formattedSimulationTime = computed(() => {
  const minutes = Math.floor(simulationSecondsRemaining.value / 60)
  const seconds = String(simulationSecondsRemaining.value % 60).padStart(2, '0')
  return `${minutes}:${seconds}`
})
const hasFilters = computed(() => selectedCampaignIds.value.length > 0 || selectedAdIds.value.length > 0 || days.value !== 30)
const campaignFilterHint = computed(() => selectedCampaignIds.value.length
  ? t('dashboard.selectedCount', { count: selectedCampaignIds.value.length })
  : t('dashboard.allSelectedHint'))
const adFilterHint = computed(() => selectedAdIds.value.length
  ? t('dashboard.selectedCount', { count: selectedAdIds.value.length })
  : t('dashboard.allAdsHint'))
const trendArrow = computed(() => ({ growth: '↗', decline: '↘', stable: '→' })[data.trend_direction] || '→')

const number = (value) => new Intl.NumberFormat(localeCode.value).format(value || 0)
const percentage = (value) => new Intl.NumberFormat(localeCode.value, { maximumFractionDigits: 1, signDisplay: 'never' }).format(Math.abs(value || 0)) + '%'
const campaignName = (campaignId) => data.filters.campaigns.find((campaign) => campaign.id === campaignId)?.name || ''
const dateLabel = (date) => new Intl.DateTimeFormat(localeCode.value, { day: '2-digit', month: 'short' }).format(new Date(`${date}T00:00:00`))

const barData = computed(() => ({
  labels: data.campaigns.map((item) => item.name),
  datasets: [{ label: t('dashboard.total'), data: data.campaigns.map((item) => item.total), backgroundColor: '#183f35', borderRadius: 7, maxBarThickness: 46 }],
}))
const donutData = computed(() => ({
  labels: data.browsers.map((item) => item.browser),
  datasets: [{ data: data.browsers.map((item) => item.total), backgroundColor: ['#59e6a7', '#183f35', '#ffb05a', '#8da29b', '#d8e4df'], borderWidth: 0 }],
}))
const lineData = computed(() => ({
  labels: data.timeline.map((item) => dateLabel(item.date)),
  datasets: [{
    label: t('dashboard.dailyImpressions'),
    data: data.timeline.map((item) => item.total),
    borderColor: '#28b879',
    backgroundColor: 'rgba(89, 230, 167, .16)',
    borderWidth: 3,
    pointRadius: data.timeline.length <= 14 ? 3 : 0,
    pointHoverRadius: 5,
    pointBackgroundColor: '#183f35',
    tension: .38,
    fill: true,
  }],
}))

const cartesianOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#edf1ef' }, border: { display: false } },
    x: { grid: { display: false }, border: { display: false } },
  },
}))
const lineOptions = computed(() => ({
  ...cartesianOptions.value,
  interaction: { mode: 'index', intersect: false },
  scales: {
    ...cartesianOptions.value.scales,
    x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 9, maxRotation: 0 } },
  },
}))
const donutOptions = { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } }

function analyticsPath() {
  const params = new URLSearchParams({ days: String(days.value) })
  selectedCampaignIds.value.forEach((id) => params.append('campaign_ids[]', id))
  selectedAdIds.value.forEach((id) => params.append('ad_ids[]', id))
  return `/api/analytics?${params}`
}

async function load() {
  const sequence = ++requestSequence
  loading.value = true
  try {
    const response = await api(analyticsPath())
    if (sequence !== requestSequence) return
    Object.assign(data, response.data)
    setupRealtime()
  } catch (exception) {
    if (sequence === requestSequence) toast.error(exception.message)
  } finally {
    if (sequence === requestSequence) loading.value = false
  }
}

function campaignsChanged() {
  const validAdIds = new Set(availableAds.value.map((ad) => ad.id))
  selectedAdIds.value = selectedAdIds.value.filter((id) => validAdIds.has(id))
  load()
}

function resetFilters() {
  selectedCampaignIds.value = []
  selectedAdIds.value = []
  days.value = 30
  load()
}

function setupRealtime() {
  if (echo || !data.metrics_allowed) return
  const realtimeCampaigns = data.filters.campaigns.filter((campaign) => data.realtime || campaign.kind === 'simulation')
  if (!realtimeCampaigns.length) return

  echo = createEcho()
  realtimeCampaigns.forEach((campaign) => {
    echo.private(`workspaces.${auth.workspace.id}.campaigns.${campaign.id}`)
      .listenToAll((event, payload) => {
        if (event === '.impression.recorded') applyRealtimeEvent(payload)
      })
  })
}

function applyRealtimeEvent(event, receivedFromChannel = true) {
  const payload = typeof event === 'string' ? JSON.parse(event) : (event?.data || event)
  const campaignId = Number(payload.campaign_id)
  const adId = Number(payload.ad_id)

  if (receivedFromChannel && simulationActive.value) {
    const campaignKind = data.filters.campaigns.find((campaign) => campaign.id === campaignId)?.kind
    if (campaignKind === 'simulation') return
  }

  if (selectedCampaignIds.value.length && !selectedCampaignIds.value.includes(campaignId)) return
  if (selectedAdIds.value.length && !selectedAdIds.value.includes(adId)) return

  const amount = Number(payload.count || 1)
  const campaign = data.campaigns.find((item) => item.id === campaignId)
  if (!campaign) return

  campaign.total = Number(campaign.total) + amount
  data.total = Number(data.total) + amount

  const browser = data.browsers.find((item) => item.browser === payload.browser)
  if (browser) browser.total = Number(browser.total) + amount
  else data.browsers.push({ browser: payload.browser, total: amount })

  const day = data.timeline.find((item) => item.date === payload.date)
  if (day) day.total = Number(day.total) + amount
  recalculateTrend()
}

function recalculateTrend() {
  const period = data.timeline.length
  const half = Math.floor(period / 2)
  const previous = data.timeline.slice(period - (half * 2), period - half).reduce((sum, item) => sum + Number(item.total), 0)
  const current = data.timeline.slice(period - half).reduce((sum, item) => sum + Number(item.total), 0)
  const trend = previous === 0 ? (current > 0 ? 100 : 0) : Math.round((((current - previous) / previous) * 100) * 10) / 10
  data.trend_percentage = trend
  data.trend_direction = trend > 0 ? 'growth' : trend < 0 ? 'decline' : 'stable'
}

async function toggleSimulation() {
  if (simulationActive.value) await stopSimulation()
  else await startSimulation()
}

async function startSimulation() {
  startingSimulation.value = true
  try {
    const session = await api('/api/simulation/start', { method: 'POST', body: JSON.stringify({}) })
    simulationToken = session.token
    simulationSecondsRemaining.value = session.max_seconds
    simulationActive.value = true
    await runSimulationTick()
    simulationTimer = setInterval(async () => {
      simulationSecondsRemaining.value -= 1
      if (simulationSecondsRemaining.value <= 0) await stopSimulation(true)
      else await runSimulationTick()
    }, 1000)
  } catch (exception) {
    toast.error(exception.message)
  } finally {
    startingSimulation.value = false
  }
}

async function runSimulationTick() {
  if (!simulationActive.value || simulationTickRunning.value) return
  simulationTickRunning.value = true
  try {
    const result = await api('/api/simulation/tick', {
      method: 'POST',
      body: JSON.stringify({ token: simulationToken }),
    })
    result.events.forEach((event) => applyRealtimeEvent(event, false))
  } catch (exception) {
    toast.error(exception.message)
    await stopSimulation()
  } finally {
    simulationTickRunning.value = false
  }
}

async function stopSimulation(expired = false) {
  const token = simulationToken
  clearInterval(simulationTimer)
  simulationTimer = null
  simulationToken = ''
  simulationActive.value = false
  simulationSecondsRemaining.value = 180

  if (token) {
    try {
      await api('/api/simulation/stop', { method: 'POST', body: JSON.stringify({ token }) })
    } catch (exception) {
      if (!expired) toast.error(exception.message)
    }
  }
  await load()
}

onMounted(load)
onBeforeUnmount(() => {
  clearInterval(simulationTimer)
  if (simulationToken) {
    api('/api/simulation/stop', { method: 'POST', body: JSON.stringify({ token: simulationToken }) }).catch(() => {})
  }
  echo?.disconnect()
})
</script>
