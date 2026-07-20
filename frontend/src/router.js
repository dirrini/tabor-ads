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
import VerifyEmailView from './views/VerifyEmailView.vue'
import InvitationView from './views/InvitationView.vue'
import LegalView from './views/LegalView.vue'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
  routes: [
  { path: '/', component: LandingView },
  { path: '/privacy', name: 'privacy', component: LegalView, props: { document: 'privacy' } },
  { path: '/terms', name: 'terms', component: LegalView, props: { document: 'terms' } },
  { path: '/login', component: AuthView, meta: { guest: true } },
  { path: '/register', component: AuthView, meta: { guest: true } },
  { path: '/verify-email', name: 'verify-email', component: VerifyEmailView, meta: { auth: true, allowUnverified: true } },
  { path: '/invite/:token', name: 'invitation', component: InvitationView },
  { path: '/app', component: AppShell, meta: { auth: true }, children: [
    { path: '', redirect: '/app/dashboard' },
    { path: 'dashboard', component: DashboardView },
    { path: 'campaigns', component: CampaignsView, meta: { campaignPermission: true } },
    { path: 'team', component: TeamView, meta: { owner: true } },
    { path: 'billing', component: BillingView, meta: { owner: true } },
    { path: 'profile', component: ProfileView },
  ]},
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.auth) return

  const auth = useAuthStore()
  await auth.load()
  if (to.meta.auth && !auth.authenticated) return { path: '/login', query: { redirect: to.fullPath } }
  if (!auth.verified && !to.meta.allowUnverified) return { name: 'verify-email' }
  if (auth.verified && to.name === 'verify-email') return { path: '/app/dashboard' }
  if (to.meta.owner && !auth.owner) return { path: '/app/dashboard' }
  if (to.meta.campaignPermission && !auth.canCreateCampaigns) return { path: '/app/dashboard' }
})

export default router
