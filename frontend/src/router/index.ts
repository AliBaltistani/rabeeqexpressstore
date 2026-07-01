import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSettingsStore } from '@/stores/settingsStore'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/pages/HomePage.vue'),
        meta: { title: 'Raqeeb Express Store ' },
      },
      {
        path: 'products',
        name: 'shop',
        component: () => import('@/pages/ShopPage.vue'),
        meta: { title: 'Shop - Raqeeb Express Store ' },
      },
      {
        path: 'category/:slug',
        name: 'category',
        component: () => import('@/pages/CategoryPage.vue'),
        meta: { title: 'Category - Raqeeb Express Store ' },
      },
      {
        path: 'brand/:slug',
        name: 'brand',
        component: () => import('@/pages/CategoryPage.vue'),
        meta: { title: 'Brand - Raqeeb Express Store ' },
      },
      {
        path: 'product/:slug',
        name: 'product',
        component: () => import('@/pages/ProductDetailPage.vue'),
        meta: { title: 'Product - Raqeeb Express Store ' },
      },
      {
        path: 'search',
        name: 'search',
        component: () => import('@/pages/SearchResultsPage.vue'),
        meta: { title: 'Search - Raqeeb Express Store ' },
      },
      {
        path: 'flash-sale',
        name: 'flash-sale',
        component: () => import('@/pages/FlashSalePage.vue'),
        meta: { title: 'Flash Sale - Raqeeb Express Store ' },
      },
      {
        path: 'cart',
        name: 'cart',
        component: () => import('@/pages/CartPage.vue'),
        meta: { title: 'Cart - Raqeeb Express Store ' },
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { title: 'Login - Raqeeb Express Store ', guest: true },
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/pages/RegisterPage.vue'),
        meta: { title: 'Register - Raqeeb Express Store ', guest: true },
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/ForgotPasswordPage.vue'),
        meta: { title: 'Forgot Password - Raqeeb Express Store ', guest: true },
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
            meta: { title: 'My Account - Raqeeb Express Store ' },
          },
          {
            path: 'profile',
            name: 'profile',
            component: () => import('@/pages/ProfilePage.vue'),
            meta: { title: 'Profile - Raqeeb Express Store ' },
          },
          {
            path: 'orders',
            name: 'orders',
            component: () => import('@/pages/OrdersListPage.vue'),
            meta: { title: 'Orders - Raqeeb Express Store ' },
          },
          {
            path: 'orders/:orderNumber',
            name: 'order-detail',
            component: () => import('@/pages/OrderDetailPage.vue'),
            meta: { title: 'Order Detail - Raqeeb Express Store ' },
          },
          {
            path: 'wishlist',
            name: 'wishlist',
            component: () => import('@/pages/WishlistPage.vue'),
            meta: { title: 'Wishlist - Raqeeb Express Store ' },
          },
          {
            path: 'addresses',
            name: 'addresses',
            component: () => import('@/pages/AddressesPage.vue'),
            meta: { title: 'Addresses - Raqeeb Express Store ' },
          },
          {
            path: 'notifications',
            name: 'notifications',
            component: () => import('@/pages/NotificationsPage.vue'),
            meta: { title: 'Notifications - Raqeeb Express Store ' },
          },
          {
            path: 'loyalty-points',
            name: 'loyalty-points',
            component: () => import('@/pages/LoyaltyPointsPage.vue'),
            meta: { title: 'Loyalty Points - Raqeeb Express Store ' },
          },
          {
            path: 'wallet',
            name: 'wallet',
            component: () => import('@/pages/WalletPage.vue'),
            meta: { title: 'Wallet - Raqeeb Express Store ' },
          },
        ]
      },
      {
        path: 'reset-password',
        name: 'reset-password',
        component: () => import('@/pages/ResetPasswordPage.vue'),
        meta: { title: 'Reset Password - Raqeeb Express Store ', guest: true },
      },
      {
        path: 'blog',
        name: 'blog',
        component: () => import('@/pages/BlogListPage.vue'),
        meta: { title: 'Blog - Raqeeb Express Store ' },
      },
      {
        path: 'blog/:slug',
        name: 'blog-post',
        component: () => import('@/pages/BlogPostPage.vue'),
        meta: { title: 'Blog - Raqeeb Express Store ' },
      },
      {
        path: ':slug',
        name: 'cms-page',
        component: () => import('@/pages/CmsPage.vue'),
        meta: { title: 'Raqeeb Express Store ' },
      },
    ],
  },
  // Checkout routes — outside DefaultLayout (no header/footer)
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('@/pages/CheckoutPage.vue'),
    meta: { title: 'Checkout - Raqeeb Express Store ' },
  },
  {
    path: '/checkout/success/:orderNumber',
    name: 'order-success',
    component: () => import('@/pages/OrderSuccessPage.vue'),
    meta: { title: 'Order Success - Raqeeb Express Store ' },
  },
  {
    // BNPL (Tamara / Tabby) redirect return page
    // Gateway → Laravel callback → redirects here with ?gateway=tamara&status=success&order=ORD-XX
    path: '/checkout/return',
    name: 'checkout-return',
    component: () => import('@/pages/CheckoutReturn.vue'),
    meta: { title: 'Payment Return - Raqeeb Express Store ' },
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
  // Set dynamic page title using store name from settings
  const title = to.meta.title as string
  if (title) {
    try {
      const settings = useSettingsStore()
      const storeName = settings.storeSettings.storeName || 'Raqeeb Express Store '
      document.title = title.replace(/Raqeeb Express Store /gi, storeName)
    } catch {
      document.title = title
    }
  }

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
