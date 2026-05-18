import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/pages/HomePage.vue'),
        meta: { title: 'E-SEVEN STORE' },
      },
      {
        path: 'products',
        name: 'shop',
        component: () => import('@/pages/ShopPage.vue'),
        meta: { title: 'Shop - E-SEVEN STORE' },
      },
      {
        path: 'category/:slug',
        name: 'category',
        component: () => import('@/pages/CategoryPage.vue'),
        meta: { title: 'Category - E-SEVEN STORE' },
      },
      {
        path: 'brand/:slug',
        name: 'brand',
        component: () => import('@/pages/CategoryPage.vue'),
        meta: { title: 'Brand - E-SEVEN STORE' },
      },
      {
        path: 'product/:slug',
        name: 'product',
        component: () => import('@/pages/ProductDetailPage.vue'),
        meta: { title: 'Product - E-SEVEN STORE' },
      },
      {
        path: 'search',
        name: 'search',
        component: () => import('@/pages/SearchResultsPage.vue'),
        meta: { title: 'Search - E-SEVEN STORE' },
      },
      {
        path: 'flash-sale',
        name: 'flash-sale',
        component: () => import('@/pages/FlashSalePage.vue'),
        meta: { title: 'Flash Sale - E-SEVEN STORE' },
      },
      {
        path: 'cart',
        name: 'cart',
        component: () => import('@/pages/CartPage.vue'),
        meta: { title: 'Cart - E-SEVEN STORE' },
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { title: 'Login - E-SEVEN STORE', guest: true },
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/pages/RegisterPage.vue'),
        meta: { title: 'Register - E-SEVEN STORE', guest: true },
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/ForgotPasswordPage.vue'),
        meta: { title: 'Forgot Password - E-SEVEN STORE', guest: true },
      },
      {
        path: 'account',
        component: () => import('@/layouts/AccountLayout.vue'),
        meta: { requiresAuth: true },
        children: [
          {
            path: '',
            name: 'account',
            component: () => import('@/pages/AccountDashboardPage.vue'),
            meta: { title: 'My Account - E-SEVEN STORE' },
          },
          {
            path: 'profile',
            name: 'profile',
            component: () => import('@/pages/ProfilePage.vue'),
            meta: { title: 'Profile - E-SEVEN STORE' },
          },
          {
            path: 'orders',
            name: 'orders',
            component: () => import('@/pages/OrdersListPage.vue'),
            meta: { title: 'Orders - E-SEVEN STORE' },
          },
          {
            path: 'orders/:orderNumber',
            name: 'order-detail',
            component: () => import('@/pages/OrderDetailPage.vue'),
            meta: { title: 'Order Detail - E-SEVEN STORE' },
          },
          {
            path: 'wishlist',
            name: 'wishlist',
            component: () => import('@/pages/WishlistPage.vue'),
            meta: { title: 'Wishlist - E-SEVEN STORE' },
          },
          {
            path: 'addresses',
            name: 'addresses',
            component: () => import('@/pages/AddressesPage.vue'),
            meta: { title: 'Addresses - E-SEVEN STORE' },
          },
          {
            path: 'notifications',
            name: 'notifications',
            component: () => import('@/pages/NotificationsPage.vue'),
            meta: { title: 'Notifications - E-SEVEN STORE' },
          },
          {
            path: 'loyalty-points',
            name: 'loyalty-points',
            component: () => import('@/pages/LoyaltyPointsPage.vue'),
            meta: { title: 'Loyalty Points - E-SEVEN STORE' },
          },
          {
            path: 'wallet',
            name: 'wallet',
            component: () => import('@/pages/WalletPage.vue'),
            meta: { title: 'Wallet - E-SEVEN STORE' },
          },
        ]
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: () => import('@/pages/ResetPasswordPage.vue'),
        meta: { title: 'Reset Password - E-SEVEN STORE', guest: true },
      },
      {
        path: 'blog',
        name: 'blog',
        component: () => import('@/pages/BlogListPage.vue'),
        meta: { title: 'Blog - E-SEVEN STORE' },
      },
      {
        path: 'blog/:slug',
        name: 'blog-post',
        component: () => import('@/pages/BlogPostPage.vue'),
        meta: { title: 'Blog - E-SEVEN STORE' },
      },
      {
        path: ':slug',
        name: 'cms-page',
        component: () => import('@/pages/CmsPage.vue'),
        meta: { title: 'E-SEVEN STORE' },
      },
    ],
  },
  // Checkout routes — outside DefaultLayout (no header/footer)
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('@/pages/CheckoutPage.vue'),
    meta: { title: 'Checkout - E-SEVEN STORE' },
  },
  {
    path: '/checkout/success/:orderNumber',
    name: 'order-success',
    component: () => import('@/pages/OrderSuccessPage.vue'),
    meta: { title: 'Order Success - E-SEVEN STORE' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'smooth' }
  },
})

// Dynamic page titles + auth guards
router.beforeEach((to, _from, next) => {
  const title = to.meta.title as string
  if (title) document.title = title

  // Auth guard
  if (to.meta.requiresAuth) {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }
  }

  // Guest guard (prevent authenticated users from accessing login/register)
  if (to.meta.guest) {
    const auth = useAuthStore()
    if (auth.isAuthenticated) {
      return next({ name: 'account' })
    }
  }

  next()
})

export default router
