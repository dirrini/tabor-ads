<template>
  <main class="landing">
    <nav class="nav wrap"><RouterLink class="brand" to="/"><BrandLogo/></RouterLink><div class="nav-links"><a href="#recursos">{{ t('landing.nav.features') }}</a><a href="#planos">{{ t('landing.nav.plans') }}</a><RouterLink to="/privacy">{{ t('legal.privacy.link') }}</RouterLink><LocaleSwitcher/><RouterLink class="btn btn-ghost" to="/login">{{ t('landing.nav.login') }}</RouterLink></div></nav>
    <section class="hero wrap">
      <div class="hero-copy"><span class="eyebrow"><i></i> {{ t('landing.hero.eyebrow') }}</span><h1>{{ t('landing.hero.title') }}<br><em>{{ t('landing.hero.emphasis') }}</em></h1><p>{{ t('landing.hero.description') }}</p><div class="hero-actions"><RouterLink class="btn btn-primary" to="/register">{{ t('landing.hero.start') }} <span>→</span></RouterLink><a class="text-link" href="#como-funciona">{{ t('landing.hero.how') }}</a></div><div class="trust"><span>{{ t('landing.hero.trust1') }}</span><span>{{ t('landing.hero.trust2') }}</span><span>{{ t('landing.hero.trust3') }}</span></div></div>
      <div class="hero-visual"><div class="live-card"><div class="live-head"><div><small>{{ t('landing.hero.impressions') }}</small><strong>48.291</strong></div><span class="live-pill">{{ t('landing.hero.live') }}</span></div><svg class="spark" viewBox="0 0 560 180" preserveAspectRatio="none"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#59e6a7" stop-opacity=".34"/><stop offset="1" stop-color="#59e6a7" stop-opacity="0"/></linearGradient></defs><path class="area" d="M0,155 C55,150 62,130 110,137 S170,98 218,110 S270,56 320,82 S385,25 430,55 S500,32 560,12 L560,180 L0,180Z"/><path class="line" d="M0,155 C55,150 62,130 110,137 S170,98 218,110 S270,56 320,82 S385,25 430,55 S500,32 560,12"/></svg><div class="campaign-row"><span class="campaign-icon">SF</span><div><b>Summer Flash</b><small>{{ t('landing.hero.activeCampaign') }}</small></div><strong>+18,4%</strong></div></div><div class="float-card browser"><span>Chrome</span><b>62%</b><i><u></u></i></div><div class="float-card event"><i></i><span><b>{{ t('landing.hero.newImpression') }}</b><small>{{ t('landing.hero.now') }}</small></span></div></div>
    </section>
    <section id="recursos" class="section wrap"><div class="section-title"><span>{{ t('landing.features.eyebrow') }}</span><h2>{{ t('landing.features.title') }}</h2></div><div class="feature-grid"><article><span class="feature-number">01</span><h3>{{ t('landing.features.trackingTitle') }}</h3><p>{{ t('landing.features.trackingText') }}</p></article><article><span class="feature-number">02</span><h3>{{ t('landing.features.campaignsTitle') }}</h3><p>{{ t('landing.features.campaignsText') }}</p></article><article class="accent-card"><span class="feature-number">03</span><h3>{{ t('landing.features.realtimeTitle') }}</h3><p>{{ t('landing.features.realtimeText') }}</p><div class="pulse-demo"><i></i><span></span><span></span><span></span></div></article></div></section>
    <section id="como-funciona" class="how"><div class="wrap how-grid"><div><span class="eyebrow light">{{ t('landing.how.eyebrow') }}</span><h2 class="pre-line">{{ t('landing.how.title') }}</h2></div><ol><li v-for="step in steps" :key="step.number"><b>{{ step.number }}</b><span><strong>{{ t(step.title) }}</strong><small>{{ t(step.text) }}</small></span></li></ol></div></section>
    <section id="planos" class="section wrap pricing-section"><div class="section-title"><span>{{ t('landing.pricing.eyebrow') }}</span><h2>{{ t('landing.pricing.title') }}</h2></div><div class="pricing-grid">
      <article class="price-card"><small>FREE</small><h3>R$ 0 <span>/ {{ t('common.month') }}</span></h3><p>{{ t('landing.pricing.freeText') }}</p><ul><li>{{ t('landing.pricing.campaigns3') }}</li><li>{{ t('landing.pricing.oneAd') }}</li><li>{{ t('landing.pricing.static') }}</li><li>{{ t('landing.pricing.oneMember') }}</li></ul><RouterLink class="btn btn-dark" to="/register">{{ t('landing.pricing.create') }}</RouterLink></article>
      <article class="price-card"><small>{{ t('landing.pricing.monthly') }}</small><h3>R$ 1,90 <span>/ {{ t('common.month') }}</span></h3><p>{{ t('landing.pricing.monthlyText') }}</p><ul><li>{{ t('landing.pricing.campaigns20') }}</li><li>{{ t('landing.pricing.tenAds') }}</li><li>{{ t('landing.pricing.realtime') }}</li><li>{{ t('landing.pricing.fiveMembers') }}</li></ul><RouterLink class="btn btn-dark" :to="premiumRoute('monthly')">{{ t('landing.pricing.chooseMonthly') }}</RouterLink></article>
      <article class="price-card featured"><div class="popular">{{ t('landing.pricing.best') }}</div><small>{{ t('landing.pricing.annual') }}</small><h3>R$ 9,90 <span>/ {{ t('common.year') }}</span></h3><p>{{ t('landing.pricing.annualText') }}</p><ul><li>{{ t('landing.pricing.allPremium') }}</li><li>{{ t('landing.pricing.singlePayment') }}</li><li>{{ t('landing.pricing.pixCard') }}</li><li>{{ t('landing.pricing.twelveMonths') }}</li></ul><RouterLink class="btn btn-primary" :to="premiumRoute('annual')">{{ t('landing.pricing.chooseAnnual') }}</RouterLink></article>
    </div></section>
    <footer class="footer wrap">
      <BrandLogo/>
      <div class="footer-copy">
        <p>{{ t('landing.footer') }}</p>
        <nav class="footer-links" :aria-label="t('legal.footerNavigation')">
          <RouterLink to="/privacy">{{ t('legal.privacy.link') }}</RouterLink>
          <RouterLink to="/terms">{{ t('legal.terms.link') }}</RouterLink>
        </nav>
      </div>
    </footer>
  </main>
</template>
<script setup>
import { RouterLink } from 'vue-router'; import { useI18n } from 'vue-i18n'; import LocaleSwitcher from '../components/LocaleSwitcher.vue'; import BrandLogo from '../components/BrandLogo.vue'
const { t }=useI18n(); const steps=[{number:'01',title:'landing.how.step1Title',text:'landing.how.step1Text'},{number:'02',title:'landing.how.step2Title',text:'landing.how.step2Text'},{number:'03',title:'landing.how.step3Title',text:'landing.how.step3Text'}]
const premiumRoute=(plan)=>({path:'/register',query:{redirect:`/app/billing?plan=${plan}`}})
</script>
