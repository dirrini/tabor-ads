<template>
  <div class="page">
    <header class="page-head">
      <div><span class="overline">{{ t('billing.eyebrow') }}</span><h1>{{ t('billing.title') }}</h1><p>{{ t('billing.subtitle') }}</p></div>
    </header>

    <section class="current-plan">
      <div><span class="plan-badge">{{ auth.workspace?.plan }}</span><h2>{{ auth.premium ? 'Premium' : 'Free' }}</h2><p>{{ t(auth.premium ? 'billing.premiumText' : 'billing.freeText') }}</p></div>
    </section>

    <section v-if="!checkoutVisible" class="billing-cycle-grid">
      <article class="billing-cycle-card panel">
        <small>{{ t('billing.monthly') }}</small><h2>{{ money(planFor('monthly').amount) }} <span>{{ t('billing.perMonth') }}</span></h2><p>{{ t('billing.monthlyText') }}</p>
        <ul><li>{{ t('billing.pixOrCard') }}</li><li>{{ t('billing.cardOnce') }}</li><li>{{ t('billing.oneMonth') }}</li></ul>
        <button class="btn btn-dark full" @click="openCheckout('monthly')">{{ t(auth.premium ? 'billing.renewMonthly' : 'billing.chooseMonthly') }}</button>
      </article>
      <article class="billing-cycle-card panel featured">
        <span class="popular">{{ t('billing.best') }}</span><small>{{ t('billing.annual') }}</small><h2>{{ money(planFor('annual').amount) }} <span>{{ t('billing.perYear') }}</span></h2><p>{{ t('billing.annualText') }}</p>
        <ul><li>{{ t('billing.onePayment', { amount: money(planFor('annual').amount) }) }}</li><li>{{ t('billing.creditCard') }}</li><li>{{ t('billing.twelveMonths') }}</li></ul>
        <button class="btn btn-primary full" @click="openCheckout('annual')">{{ t(auth.premium ? 'billing.renewAnnual' : 'billing.chooseAnnual') }}</button>
      </article>
    </section>

    <section v-if="checkoutVisible" class="checkout-panel panel">
      <div class="checkout-copy">
        <span class="overline">{{ t('billing.secure') }}</span><h2>{{ t('billing.premiumCycle', { cycle: cycleName(selectedCycle) }) }}</h2><p>{{ t('billing.checkoutText') }}</p>
        <strong>{{ money(selectedPlan.amount) }} <small>/ {{ selectedCycle === 'annual' ? t('common.year') : t('common.month') }}</small></strong>
        <ul><li>{{ t('billing.upToCampaigns') }}</li><li>{{ t('billing.upToAds') }}</li><li>{{ t('billing.moreUsers') }}</li><li v-if="selectedCycle === 'annual'">{{ t('billing.annualPayment') }}</li></ul>
        <button type="button" class="checkout-close" @click="cancelCheckout">{{ t('common.cancel') }}</button>
      </div>
      <div class="checkout-form">
        <div class="checkout-method-tabs">
          <button type="button" :class="{ active: selectedMethod === 'card' }" @click="switchPaymentMethod('card')">{{ t('billing.card') }}</button>
          <button type="button" :class="{ active: selectedMethod === 'pix' }" @click="switchPaymentMethod('pix')">{{ t('billing.pix') }}</button>
        </div>
        <div v-if="brickLoading" class="checkout-loading">{{ t(selectedMethod === 'pix' ? 'billing.generatingPix' : 'billing.loadingPayment') }}</div>
        <div v-if="selectedMethod === 'card'" id="cardPaymentBrick_container"></div>
        <div v-else-if="pix.qrCode && !brickLoading" class="pix-result">
          <div><span class="overline">{{ t('billing.pixGenerated', { cycle: cycleName(pix.cycle).toUpperCase() }) }}</span><h2>{{ t('billing.scanPix') }}</h2><p>{{ t('billing.pixConfirmation') }}</p><button class="btn btn-primary" @click="copyPix">{{ t(copied ? 'billing.copied' : 'billing.copyPix') }}</button></div>
          <img v-if="pix.qrBase64" :src="`data:image/png;base64,${pix.qrBase64}`" alt="QR Code Pix">
        </div>
      </div>
    </section>

    <section class="limit-cards">
      <article><small>{{ t('billing.campaigns') }}</small><strong>{{ auth.workspace?.limits?.campaigns }}</strong><span>{{ t('billing.notArchived') }}</span></article>
      <article><small>{{ t('billing.adsPerCampaign') }}</small><strong>{{ auth.workspace?.limits?.ads_per_campaign }}</strong><span>{{ t('billing.creatives') }}</span></article>
      <article><small>{{ t('billing.members') }}</small><strong>{{ auth.workspace?.limits?.members }}</strong><span>{{ t('billing.perWorkspace') }}</span></article>
      <article><small>{{ t('billing.realtime') }}</small><strong>{{ auth.workspace?.limits?.realtime ? 'ON' : 'OFF' }}</strong><span>{{ t('billing.privateChannels') }}</span></article>
    </section>
    <div class="panel billing-note"><b>{{ t('billing.securePayment') }}</b><p>{{ t('billing.securityNote') }}</p></div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const PIX_STATUS_INTERVAL = 4000
const PIX_STATUS_TIMEOUT = 15 * 60 * 1000
const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const { t, locale } = useI18n()
const defaults = { monthly: { amount: 1.90 }, annual: { amount: 9.90 } }
const checkoutVisible = ref(false)
const brickLoading = ref(false)
const copied = ref(false)
const config = ref(null)
const selectedCycle = ref('monthly')
const selectedMethod = ref('card')
const pix = reactive({ paymentId: '', qrCode: '', qrBase64: '', cycle: '' })

let brickController = null
let mercadoPago = null
let checkoutGeneration = 0
let isResetting = false
let paymentStatusTimer = null
let paymentStatusDeadline = 0
let pixCompletionRunning = false

const planFor = (cycle) => config.value?.plans?.[cycle] || defaults[cycle]
const selectedPlan = computed(() => planFor(selectedCycle.value))
const money = (amount) => new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'pt-BR', { style: 'currency', currency: config.value?.currency || 'BRL' }).format(amount)
const cycleName = (cycle) => t(cycle === 'annual' ? 'billing.cycleAnnual' : 'billing.cycleMonthly')

function clearToast() { toast.clear() }
function showToast(message, type = 'success') { toast.show(message, type) }
function resetPix() { Object.assign(pix, { paymentId: '', qrCode: '', qrBase64: '', cycle: '' }); copied.value = false }
function clearPaymentStatusMonitoring() { if (paymentStatusTimer) clearTimeout(paymentStatusTimer); paymentStatusTimer = null; paymentStatusDeadline = 0 }

function loadSdk() {
  if (window.MercadoPago) return Promise.resolve()
  return new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-mercadopago-sdk]')
    if (existing) {
      existing.addEventListener('load', resolve, { once: true })
      existing.addEventListener('error', reject, { once: true })
      return
    }
    const script = document.createElement('script')
    script.src = 'https://sdk.mercadopago.com/js/v2'
    script.dataset.mercadopagoSdk = 'true'
    script.onload = resolve
    script.onerror = () => reject(new Error(t('billing.sdkError')))
    document.head.appendChild(script)
  })
}

async function loadConfiguration() {
  if (!config.value) config.value = await api('/api/billing/configuration')
  return config.value
}

async function unmountBrick() {
  const controller = brickController
  brickController = null
  if (!controller) return
  try { await Promise.resolve(controller.unmount()) } catch {}
}

async function hideCheckout() {
  clearPaymentStatusMonitoring()
  isResetting = true
  checkoutGeneration++
  await unmountBrick()
  checkoutVisible.value = false
  brickLoading.value = false
  isResetting = false
}

async function cancelCheckout() {
  clearToast()
  await hideCheckout()
  selectedCycle.value = 'monthly'
  selectedMethod.value = 'card'
  mercadoPago = null
  resetPix()
}

function startPaymentStatusMonitoring(paymentId, generation) {
  clearPaymentStatusMonitoring()
  if (!paymentId) return
  paymentStatusDeadline = Date.now() + PIX_STATUS_TIMEOUT
  paymentStatusTimer = setTimeout(() => checkPaymentStatus(paymentId, generation), 3000)
}

function schedulePaymentStatusCheck(paymentId, generation) {
  if (Date.now() >= paymentStatusDeadline) {
    paymentStatusTimer = null
    showToast(t('billing.confirmationDelayed'), 'error')
    return
  }
  paymentStatusTimer = setTimeout(() => checkPaymentStatus(paymentId, generation), PIX_STATUS_INTERVAL)
}

async function checkPaymentStatus(paymentId, generation) {
  paymentStatusTimer = null
  if (generation !== checkoutGeneration || !checkoutVisible.value || selectedMethod.value !== 'pix' || pix.paymentId !== paymentId) return

  try {
    const result = await api(`/api/billing/payments/${encodeURIComponent(paymentId)}/status`)
    if (result.status === 'active') {
      await completePixPayment(result)
      return
    }
    if (result.status === 'canceled') {
      await failPixPayment()
      return
    }
  } catch {
    // A transient status error should not replace the QR Code with an error state.
  }

  schedulePaymentStatusCheck(paymentId, generation)
}

async function completePixPayment(update) {
  const paymentId = String(update.payment_id || '')
  if (pixCompletionRunning || !paymentId || paymentId !== pix.paymentId) return
  pixCompletionRunning = true
  const cycle = pix.cycle
  const wasPremium = auth.premium
  await hideCheckout()
  resetPix()
  auth.applyWorkspacePlan({ ...update, workspace_id: auth.workspace?.id })
  if (!wasPremium || update.status === 'active') showToast(t('billing.paymentApproved', { cycle: cycleName(cycle).toLowerCase() }))
  pixCompletionRunning = false
}

async function failPixPayment() {
  if (pixCompletionRunning) return
  pixCompletionRunning = true
  await hideCheckout()
  resetPix()
  showToast(t('billing.pixCanceled'), 'error')
  pixCompletionRunning = false
}

async function submitPayment(formData, cycle, generation = null) {
  try {
    const result = await api('/api/billing/payment', { method: 'POST', body: JSON.stringify({ ...formData, billing_cycle: cycle }) })
    if (generation !== null && generation !== checkoutGeneration) return
    if (result.payment_method_id === 'pix') {
      if (!result.qr_code) throw new Error(t('billing.pixUnavailable'))
      Object.assign(pix, { paymentId: String(result.payment_id), qrCode: result.qr_code, qrBase64: result.qr_code_base64 || '', cycle })
      startPaymentStatusMonitoring(pix.paymentId, checkoutGeneration)
      showToast(t('billing.pixSuccess'))
    } else if (result.status === 'approved') {
      await hideCheckout()
      showToast(t('billing.paymentApproved', { cycle: cycleName(cycle).toLowerCase() }))
      await auth.refresh()
    } else if (result.status === 'rejected') throw new Error(t('billing.rejected'))
    else showToast(t('billing.awaiting'))
  } catch (error) {
    if (generation === null || generation === checkoutGeneration) showToast(error.message, 'error')
    throw error
  }
}

async function renderPaymentMethod(generation) {
  isResetting = true
  await unmountBrick()
  isResetting = false
  if (generation !== checkoutGeneration || !checkoutVisible.value || !mercadoPago || selectedMethod.value !== 'card') return
  brickLoading.value = true
  await nextTick()
  const plan = selectedPlan.value
  const cycle = selectedCycle.value
  const callbacks = {
    onReady: () => { if (generation === checkoutGeneration) brickLoading.value = false },
    onError: () => { if (!isResetting && generation === checkoutGeneration) showToast(t('billing.optionError'), 'error'); brickLoading.value = false },
    onSubmit: (formData) => submitPayment(formData, cycle),
  }
  brickController = await mercadoPago.bricks().create('cardPayment', 'cardPaymentBrick_container', {
    initialization: { amount: plan.amount, payer: { email: config.value.payer.email } },
    customization: { paymentMethods: { minInstallments: 1, maxInstallments: 1 } },
    callbacks,
  })
}

async function createPixPayment(generation) {
  await unmountBrick()
  if (generation !== checkoutGeneration || !checkoutVisible.value || selectedMethod.value !== 'pix') return
  if (pix.qrCode) {
    brickLoading.value = false
    startPaymentStatusMonitoring(pix.paymentId, generation)
    return
  }
  brickLoading.value = true
  try { await submitPayment({ payment_method_id: 'pix', payer: { email: config.value.payer.email } }, selectedCycle.value, generation) } catch {}
  finally { if (generation === checkoutGeneration && checkoutVisible.value) brickLoading.value = false }
}

async function switchPaymentMethod(method) {
  if (!['card', 'pix'].includes(method) || method === selectedMethod.value) return
  selectedMethod.value = method
  if (method === 'card') clearPaymentStatusMonitoring()
  try {
    if (method === 'pix') await createPixPayment(checkoutGeneration)
    else await renderPaymentMethod(checkoutGeneration)
  } catch { showToast(t('billing.optionError'), 'error') }
}

async function openCheckout(cycle) {
  if (!['monthly', 'annual'].includes(cycle)) cycle = 'monthly'
  await unmountBrick()
  clearToast()
  resetPix()
  selectedCycle.value = cycle
  selectedMethod.value = 'card'
  checkoutVisible.value = true
  brickLoading.value = true
  const generation = ++checkoutGeneration
  try {
    const billingConfig = await loadConfiguration()
    if (generation !== checkoutGeneration || !checkoutVisible.value) return
    if (!billingConfig.configured) throw new Error(t('billing.configure'))
    await nextTick()
    await loadSdk()
    if (generation !== checkoutGeneration || !checkoutVisible.value) return
    mercadoPago = new window.MercadoPago(billingConfig.public_key, { locale: locale.value === 'en' ? 'en-US' : 'pt-BR' })
    await renderPaymentMethod(generation)
  } catch (error) {
    if (generation !== checkoutGeneration) return
    await hideCheckout()
    showToast(error.message, 'error')
  }
}

async function copyPix() {
  try { await navigator.clipboard.writeText(pix.qrCode); copied.value = true; showToast(t('billing.pixCopied')) }
  catch { showToast(t('common.copyError'), 'error') }
}

watch(() => auth.lastBillingUpdate, (update) => {
  if (!update?.payment_id || update.payment_id !== pix.paymentId) return
  if (update.status === 'active') completePixPayment(update)
  else if (update.status === 'canceled') failPixPayment()
})

onMounted(async () => {
  try {
    await loadConfiguration()
    if (['monthly', 'annual'].includes(route.query.plan)) await openCheckout(route.query.plan)
  } catch (error) { showToast(error.message, 'error') }
})

onBeforeUnmount(() => {
  checkoutGeneration++
  clearPaymentStatusMonitoring()
  unmountBrick()
})
</script>
