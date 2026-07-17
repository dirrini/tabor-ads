import { defineStore } from 'pinia'
import { api } from '../lib/api'
import { setAppLocale } from '../i18n'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, workspace: null, loaded: false, lastBillingUpdate: null }),
  getters: { authenticated: (state) => Boolean(state.user), premium: (state) => state.workspace?.plan === 'premium' },
  actions: {
    async load() {
      if (this.loaded) return
      try { Object.assign(this, await api('/api/auth/me')); if (this.user?.locale) setAppLocale(this.user.locale) } catch { this.user = null; this.workspace = null }
      this.loaded = true
    },
    async refresh() {
      this.loaded = false
      await this.load()
    },
    applyWorkspacePlan(update) {
      if (!this.workspace || Number(update.workspace_id || this.workspace.id) !== Number(this.workspace.id)) return
      this.workspace = {
        ...this.workspace,
        plan: update.plan || this.workspace.plan,
        limits: update.limits || this.workspace.limits,
      }
      this.lastBillingUpdate = {
        payment_id: String(update.payment_id || ''),
        status: update.status,
        plan: update.plan,
        limits: update.limits,
        received_at: Date.now(),
      }
    },
    async login(payload) { Object.assign(this, await api('/api/auth/login', { method: 'POST', body: JSON.stringify(payload) })); if (this.user?.locale) setAppLocale(this.user.locale); this.loaded = true },
    async register(payload) { Object.assign(this, await api('/api/auth/register', { method: 'POST', body: JSON.stringify(payload) })); if (this.user?.locale) setAppLocale(this.user.locale); this.loaded = true },
    async updateLocale(locale) {
      const previous = this.user?.locale
      setAppLocale(locale)
      try {
        const result = await api('/api/auth/preferences', { method: 'PATCH', body: JSON.stringify({ locale }) })
        this.user = { ...this.user, ...result.user }
      } catch (error) {
        if (previous) setAppLocale(previous)
        throw error
      }
    },
    async logout() { await api('/api/auth/logout', { method: 'POST' }); this.$reset(); this.loaded = true },
  },
})
