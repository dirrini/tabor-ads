<template>
  <div class="locale-switcher" role="group" :aria-label="t('language.label')">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :class="{ active: locale === option.value }"
      :aria-pressed="locale === option.value"
      :disabled="saving"
      @click="select(option.value)"
    >{{ option.label }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { setAppLocale } from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const props = defineProps({ persist: { type: Boolean, default: false } })
const auth = useAuthStore()
const toast = useToastStore()
const { locale, t } = useI18n()
const saving = ref(false)
const options = [{ value: 'pt-BR', label: 'PT' }, { value: 'en', label: 'EN' }]

async function select(value) {
  if (value === locale.value || saving.value) return
  if (props.persist && auth.authenticated) {
    saving.value = true
    try {
      await auth.updateLocale(value)
    } catch (exception) {
      toast.error(exception.message)
    } finally {
      saving.value = false
    }
  } else {
    setAppLocale(value)
  }
}
</script>
