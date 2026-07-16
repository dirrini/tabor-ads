import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'
import LandingView from './views/LandingView.vue'
import AuthView from './views/AuthView.vue'
import AppShell from './views/AppShell.vue'
import DashboardView from './views/DashboardView.vue'
import CampaignsView from './views/CampaignsView.vue'
import TeamView from './views/TeamView.vue'
import BillingView from './views/BillingView.vue'
import ProfileView from './views/ProfileView.vue'

const router = createRouter({ history: createWebHistory(), routes: [
  { path: '/', component: LandingView },
  { path: '/login', component: AuthView, meta: { guest: true } },
  { path: '/register', component: AuthView, meta: { guest: true } },
  { path: '/app', component: AppShell, meta: { auth: true }, children: [
    { path: '', redirect: '/app/dashboard' },
    { path: 'dashboard', component: DashboardView },
    { path: 'campaigns', component: CampaignsView },
    { path: 'team', component: TeamView },
    { path: 'billing', component: BillingView },
    { path: 'profile', component: ProfileView },
  ]},
] })

router.beforeEach(async (to) => {
  if (!to.meta.auth) return

  const auth = useAuthStore()
  await auth.load()
  if (to.meta.auth && !auth.authenticated) return { path: '/login', query: { redirect: to.fullPath } }
})

export default router
