<template>
  <main class="landing legal-page">
    <nav class="nav wrap">
      <RouterLink class="brand" to="/" aria-label="Tabor Ads"><BrandLogo/></RouterLink>
      <div class="nav-links">
        <LocaleSwitcher/>
        <RouterLink class="btn btn-ghost" to="/login">{{ t('landing.nav.login') }}</RouterLink>
      </div>
    </nav>

    <article class="legal-content wrap">
      <RouterLink class="legal-back" to="/">← {{ t('legal.back') }}</RouterLink>
      <header class="legal-header">
        <span class="eyebrow"><i></i> {{ t(`legal.${document}.eyebrow`) }}</span>
        <h1>{{ t(`legal.${document}.title`) }}</h1>
        <p>{{ t(`legal.${document}.intro`) }}</p>
        <small>{{ t('legal.updatedAt') }}</small>
      </header>

      <section v-for="section in sections" :key="section" class="legal-section">
        <h2>{{ t(`legal.${document}.sections.${section}.title`) }}</h2>
        <p class="pre-line">{{ t(`legal.${document}.sections.${section}.text`) }}</p>
      </section>

      <section class="legal-contact">
        <h2>{{ t('legal.contactTitle') }}</h2>
        <p>{{ t('legal.contactText') }}</p>
        <a href="mailto:contato@dirrini.tech">contato@dirrini.tech</a>
      </section>
    </article>

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
import { computed, watchEffect } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BrandLogo from '../components/BrandLogo.vue'
import LocaleSwitcher from '../components/LocaleSwitcher.vue'

const props = defineProps({
  document: {
    type: String,
    required: true,
    validator: (value) => ['privacy', 'terms'].includes(value),
  },
})

const { t } = useI18n()
const sectionNames = {
  privacy: ['controller', 'data', 'google', 'purposes', 'sharing', 'retention', 'security', 'rights', 'changes'],
  terms: ['acceptance', 'service', 'account', 'tracking', 'plans', 'acceptableUse', 'intellectualProperty', 'availability', 'termination', 'law', 'changes'],
}
const sections = computed(() => sectionNames[props.document])

watchEffect((onCleanup) => {
  const previousTitle = document.title
  document.title = `${t(`legal.${props.document}.title`)} | Tabor Ads`
  onCleanup(() => { document.title = previousTitle })
})
</script>
