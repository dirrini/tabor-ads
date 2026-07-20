<template>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <template v-if="name === 'dashboard'">
      <rect x="3" y="3" width="7" height="7" rx="1.5"/>
      <rect x="14" y="3" width="7" height="4" rx="1.5"/>
      <rect x="14" y="11" width="7" height="10" rx="1.5"/>
      <rect x="3" y="14" width="7" height="7" rx="1.5"/>
    </template>
    <template v-else-if="name === 'campaigns'">
      <path d="M4 13.5V10a2 2 0 0 1 2-2h3l7-3v13l-7-3H6a2 2 0 0 1-2-1.5Z"/>
      <path d="M9 15 10.5 20H7l-1.2-5"/>
      <path d="M19 8.5a5 5 0 0 1 0 6"/>
    </template>
    <template v-else-if="name === 'team'">
      <circle cx="9" cy="8" r="3"/>
      <path d="M3.5 19a5.5 5.5 0 0 1 11 0"/>
      <path d="M16 5.4a3 3 0 0 1 0 5.2"/>
      <path d="M17 14a5 5 0 0 1 3.5 5"/>
    </template>
    <template v-else-if="name === 'billing'">
      <rect x="3" y="5" width="18" height="14" rx="2.5"/>
      <path d="M3 9h18"/>
      <path d="M7 15h4"/>
    </template>
    <template v-else-if="name === 'logout'">
      <path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"/>
      <path d="M14 8l4 4-4 4"/>
      <path d="M9 12h9"/>
    </template>
  </svg>
</template>

<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
})
</script>
