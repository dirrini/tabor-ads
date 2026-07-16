import { defineStore } from 'pinia'

let timer = null

export const useToastStore = defineStore('toast', {
  state: () => ({ message: '', type: 'success' }),
  actions: {
    show(message, type = 'success', duration = 5500) {
      if (!message) return
      this.clear()
      this.message = String(message)
      this.type = type === 'error' ? 'error' : 'success'
      timer = setTimeout(() => this.clear(), duration)
    },
    success(message, duration) {
      this.show(message, 'success', duration)
    },
    error(message, duration) {
      this.show(message, 'error', duration)
    },
    clear() {
      if (timer) clearTimeout(timer)
      timer = null
      this.message = ''
    },
  },
})
