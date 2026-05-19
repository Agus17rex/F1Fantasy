import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // ─── Auth ────────────────────────────────────────────────────────────────
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guest: true },
  },

  // ─── App (con layout) ────────────────────────────────────────────────────
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
      },
      // Carreras
      {
        path: 'carreras',
        name: 'Carreras',
        component: () => import('@/views/race/RacesView.vue'),
      },
      {
        path: 'carreras/:id',
        name: 'DetalleCarrera',
        component: () => import('@/views/race/RaceDetailView.vue'),
      },
      // Pilotos y escuderías
      {
        path: 'pilotos',
        name: 'Pilotos',
        component: () => import('@/views/race/DriversView.vue'),
      },
      {
        path: 'escuderias',
        name: 'Escuderias',
        component: () => import('@/views/race/ConstructorsView.vue'),
      },
      {
        path: 'coches',
        name: 'Coches',
        component: () => import('@/views/race/CochesView.vue'),
      },
      {
        path: 'ranking-fantasy',
        name: 'RankingFantasy',
        component: () => import('@/views/race/FantasyRankingView.vue'),
      },
      {
        path: 'reglas',
        name: 'Reglas',
        component: () => import('@/views/race/ReglasView.vue'),
      },
      // Perfil de usuario
      {
        path: 'perfil',
        name: 'Perfil',
        component: () => import('@/views/auth/PerfilView.vue'),
      },
      // Ligas (el detalle de liga incluye clasificación, mercado y equipo en tabs)
      {
        path: 'ligas',
        name: 'Ligas',
        component: () => import('@/views/leagues/LeaguesView.vue'),
      },
      {
        path: 'ligas/:id',
        name: 'DetalleLiga',
        component: () => import('@/views/leagues/LeagueDetailView.vue'),
      },
    ],
  },

  // ─── Admin (con layout admin) ─────────────────────────────────────────────
  {
    path: '/admin',
    component: () => import('@/components/layout/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'AdminPanel',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
      },
      {
        path: 'carreras',
        name: 'AdminCarreras',
        component: () => import('@/views/admin/AdminRacesView.vue'),
      },
      {
        path: 'pilotos',
        name: 'AdminPilotos',
        component: () => import('@/views/admin/AdminDriversView.vue'),
      },
      {
        path: 'puntuacion',
        name: 'AdminPuntuacion',
        component: () => import('@/views/admin/AdminScoringView.vue'),
      },
      {
        path: 'resultados',
        name: 'AdminResultados',
        component: () => import('@/views/admin/AdminResultadosView.vue'),
      },
    ],
  },

  // ─── 404 ─────────────────────────────────────────────────────────────────
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

// Navigation Guards
router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Si hay token pero no hay usuario (recarga de página), lo recuperamos antes de navegar
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'Login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'Dashboard' }
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return { name: 'Dashboard' }
  }
})

export default router
