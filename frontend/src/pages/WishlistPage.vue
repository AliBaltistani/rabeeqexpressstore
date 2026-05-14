<template>
  <div class="account-page container">
    <aside class="account-sidebar">
      <div class="user-info">
        <div class="avatar">{{ userInitials }}</div>
        <h3>{{ auth.user?.name || 'Guest User' }}</h3>
        <p v-if="auth.isAuthenticated">{{ auth.user?.email || '' }}</p>
        <p v-else>{{ $t('account.notLoggedIn') || 'Sign in to sync wishlist' }}</p>
      </div>
      <nav class="account-nav">
        <router-link v-if="auth.isAuthenticated" to="/account">{{ $t('account.dashboard') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/orders">{{ $t('account.orders') }}</router-link>
        <router-link v-if="auth.isAuthenticated" to="/account/profile">{{ $t('account.profile') }}</router-link>
        <router-link to="/account/wishlist" class="active">{{ $t('common.wishlist') }}</router-link>
        <button v-if="auth.isAuthenticated" @click="logout" class="logout-btn">{{ $t('auth.logout') }}</button>
      </nav>
    </aside>

    <main class="account-content">
      <h2>{{ $t('common.wishlist') }} <span class="count-badge">({{ wishlist.count }})</span></h2>
      
      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>
      
      <div v-else-if="products.length === 0" class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <h3>{{ $t('wishlist.empty') || 'Your wishlist is empty' }}</h3>
        <p>{{ $t('wishlist.emptyDesc') || 'Explore our shop and add your favorite items!' }}</p>
        <router-link to="/products" class="btn-shop">{{ $t('common.shopNow') }}</router-link>
      </div>
      
      <div v-else class="products-grid">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
        />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useWishlistStore } from '@/stores/wishlistStore'
import { fetchWishlist, fetchProducts } from '@/api/services'
import ProductCard from '@/components/home/ProductCard.vue'

const auth = useAuthStore()
const wishlist = useWishlistStore()
const router = useRouter()

const products = ref<any[]>([])
const isLoading = ref(true)

const userInitials = computed(() => {
  const name = auth.user?.name || 'G'
  return name.substring(0, 2).toUpperCase()
})

async function logout() {
  await auth.logout()
  router.push('/login')
}

async function loadWishlistProducts() {
  isLoading.value = true
  try {
    // Fetch products based on the local wishlist store's items array.
    // This ensures that the products shown exactly match the items counted in the header,
    // including items that might have been optimistically added or carried over from guest mode.
    const ids = wishlist.items
    if (ids.length > 0) {
      const res = await fetchProducts({ ids: ids.join(','), perPage: 100 })
      products.value = res.data || []
    } else {
      products.value = []
    }
  } catch (error) {
    console.error('Failed to load wishlist products:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for changes in wishlist count to reload products (if an item is removed)
watch(() => wishlist.count, (newCount, oldCount) => {
  if (newCount < oldCount) {
    // Optimistically remove from local array
    products.value = products.value.filter(p => wishlist.items.includes(p.id))
  } else if (newCount > oldCount) {
    // If added, reload from API
    loadWishlistProducts()
  }
})

onMounted(() => {
  loadWishlistProducts()
})
</script>

<style scoped>
.account-page {
  padding: 4rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
@media (min-width: 768px) {
  .account-page {
    flex-direction: row;
  }
}
.account-sidebar {
  width: 100%;
  background: #fff;
  border-radius: 12px;
  padding: 2rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
@media (min-width: 768px) {
  .account-sidebar {
    width: 280px;
    flex-shrink: 0;
  }
}
.user-info {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #f3f4f6;
}
.avatar {
  width: 64px;
  height: 64px;
  background: var(--color-primary, #858585);
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin: 0 auto 1rem;
}
.user-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
  color: var(--store-text-primary, #111827);
}
.user-info p {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}
.account-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.account-nav a, .logout-btn {
  display: block;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  color: #4b5563;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
  transition: all 0.2s;
  text-align: left;
  border: none;
  background: transparent;
  cursor: pointer;
}
html[dir="rtl"] .account-nav a, html[dir="rtl"] .logout-btn {
  text-align: right;
}
.account-nav a:hover, .account-nav a.active {
  background: #f9fafb;
  color: var(--color-primary, #858585);
}
.logout-btn {
  color: #ef4444;
}
.logout-btn:hover {
  background: #fef2f2;
}

.account-content {
  flex: 1;
}
.account-content h2 {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  color: var(--store-text-primary, #111827);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.count-badge {
  font-size: 1rem;
  color: #9ca3af;
  font-weight: 500;
}
.empty-state {
  background: #fff;
  padding: 4rem 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.empty-state svg {
  margin-bottom: 1rem;
}
.empty-state h3 {
  font-size: 1.25rem;
  color: #111827;
  margin: 0 0 0.5rem;
}
.empty-state p {
  color: #6b7280;
  margin-bottom: 2rem;
}
.btn-shop {
  display: inline-block;
  padding: 0.75rem 2rem;
  background: var(--color-primary, #858585);
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: opacity 0.2s;
}
.btn-shop:hover {
  opacity: 0.9;
}
.loading-state {
  text-align: center;
  padding: 4rem;
  color: #6b7280;
}
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
}
</style>
