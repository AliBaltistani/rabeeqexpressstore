<template>
  <div class="home-page">
    <div class="container" style="padding: 4rem 1rem; text-align: center;">
      <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--store-text-primary);">
        E-SEVEN STORE
      </h1>
      <p style="font-size: 1.125rem; color: var(--color-primary); margin-bottom: 2rem;">
        Vue 3 Frontend — Project Setup Complete ✅
      </p>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; max-width: 800px; margin: 0 auto;">
        <div class="setup-card">
          <span class="setup-icon">🎨</span>
          <h3>Design System</h3>
          <p>CSS Variables, PingARLT Font, Salla Icons</p>
        </div>
        <div class="setup-card">
          <span class="setup-icon">🗂️</span>
          <h3>Router</h3>
          <p>All routes configured with lazy loading</p>
        </div>
        <div class="setup-card">
          <span class="setup-icon">📦</span>
          <h3>Pinia Stores</h3>
          <p>Settings, Auth, Cart, Wishlist</p>
        </div>
        <div class="setup-card">
          <span class="setup-icon">🔌</span>
          <h3>API Client</h3>
          <p>Axios with auth & language support</p>
        </div>
      </div>
      <p style="margin-top: 3rem; color: #9ca3af; font-size: 0.875rem;">
        Next: Sub-Phase 12.B — Header &amp; Footer Components
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
// HomePage — will be replaced with actual content in Sub-Phase 12.C
</script>

<style scoped>
.setup-card {
  background: var(--bg-secondary);
  border: 1px solid var(--product-border-color);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  text-align: center;
  transition: all 0.25s ease;
}
.setup-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}
.setup-icon {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.75rem;
}
.setup-card h3 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}
.setup-card p {
  font-size: 0.8125rem;
  color: var(--color-primary);
}
</style>
