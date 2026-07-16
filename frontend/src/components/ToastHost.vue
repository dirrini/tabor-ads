<template>
  <Transition name="toast">
    <div v-if="toast.message" :class="['app-toast', toast.type]" :role="toast.type === 'error' ? 'alert' : 'status'">
      <span class="app-toast-icon">{{ toast.type === 'error' ? '!' : '✓' }}</span>
      <span>{{ toast.message }}</span>
      <button type="button" :aria-label="t('common.close')" @click="toast.clear">×</button>
    </div>
  </Transition>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useToastStore } from '../stores/toast'

const toast = useToastStore()
const { t } = useI18n()
</script>
