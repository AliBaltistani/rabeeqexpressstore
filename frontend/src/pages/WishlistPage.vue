<template>
  <div class="account-content-inner">
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
.account-content-inner {
  flex: 1;
}
.account-content-inner h2 {
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
