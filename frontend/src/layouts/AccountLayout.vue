<template>
  <div class="account-layout">
    <!-- Header Banner -->
    <div class="account-banner">
      <div class="banner-pattern"></div>
    </div>
    
    <!-- User Meta & Nav Container -->
    <div class="account-header container">
      <div class="user-profile-section">
        <div class="user-avatar">
          <span class="user-initials">{{ userInitials }}</span>
        </div>
        <h2 class="user-name">{{ auth.user?.name || 'User' }}</h2>
      </div>

      <nav class="account-tabs">
        <router-link to="/account/notifications" class="tab-link" exact-active-class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          <span class="hidden-mobile">{{ $t('account.notifications') }}</span>
        </router-link>
        <router-link to="/account/orders" class="tab-link" :class="{ 'active': route.path === '/account/orders' && !route.query.status }">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span class="hidden-mobile">{{ $t('account.orders') }}</span>
        </router-link>
        <!-- Pending Payments -->
        <a
          href="#"
          class="tab-link"
          :class="{ 'active': route.path === '/account/orders' && route.query.status === 'pending' }"
          @click.prevent="router.push({ path: '/account/orders', query: { status: 'pending' } })"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span class="hidden-mobile">{{ $t('account.pendingPayments') }}</span>
        </a>
        <router-link to="/account/wishlist" class="tab-link" exact-active-class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span class="hidden-mobile">{{ $t('account.wishlist') }}</span>
        </router-link>
        <router-link to="/account/wallet" class="tab-link" exact-active-class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/><path d="M17 12h5v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4h5"/></svg>
          <span class="hidden-mobile">{{ $t('account.myWallet') }}</span>
        </router-link>
        <router-link to="/account/loyalty-points" class="tab-link" exact-active-class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
          <span class="hidden-mobile">{{ $t('account.loyaltyPoints') }}</span>
        </router-link>
        <!-- Account dashboard linked here -->
        <router-link to="/account" class="tab-link" exact-active-class="active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/></svg>
          <span class="hidden-mobile">{{ $t('account.myAccount') }}</span>
        </router-link>
      </nav>
    </div>

    <!-- Active Child Page render area -->
    <main class="account-body container">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const userInitials = computed(() => {
  const name = auth.user?.name || 'U'
  return name.substring(0, 2).toUpperCase()
})
</script>

<style scoped>
.account-layout {
  min-height: 80vh;
  background-color: #f8fafc;
  padding-bottom: 4rem;
}

.account-banner {
  width: 100%;
  height: 250px;
  background-color: #e5e7eb;
  position: relative;
  overflow: hidden;
}

.banner-pattern {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  opacity: 0.2;
  background-image: repeating-linear-gradient(45deg, #111827 25%, transparent 25%, transparent 75%, #111827 75%, #111827), repeating-linear-gradient(45deg, #111827 25%, #e5e7eb 25%, #e5e7eb 75%, #111827 75%, #111827);
  background-position: 0 0, 20px 20px;
  background-size: 40px 40px;
}

.account-header {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  background: transparent;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.user-profile-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: -60px; /* Pull up to overlap banner */
  z-index: 10;
  margin-bottom: 2rem;
}

.user-avatar {
  width: 120px;
  height: 120px;
  background-color: #3b82f6; /* Accent color */
  border: 4px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  margin-bottom: 1rem;
}

.user-initials {
  color: white;
  font-size: 2.5rem;
  font-weight: bold;
  letter-spacing: 2px;
}

.user-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.account-tabs {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  width: 100%;
}

.tab-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4b5563;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.tab-link:hover {
  background-color: #f3f4f6;
  color: #111827;
}

.tab-link.active {
  color: #1a2234;
  background-color: white;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.account-body {
  padding-top: 3rem;
}

@media (max-width: 768px) {
  .account-tabs {
    gap: 0.5rem;
  }
  .hidden-mobile {
    display: none;
  }
  .tab-link {
    padding: 0.75rem;
  }
  .account-banner {
    height: 180px;
  }
}
</style>
