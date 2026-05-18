<template>
  <div class="account-content-inner">
    <div class="header-row">
      <h2>{{ $t('account.notifications') || 'Notifications' }}</h2>
      <button class="btn-primary" @click="markAllRead" :disabled="!hasUnread || isUpdating">
        {{ $t('notifications.markAllRead') || 'Mark all as read' }}
      </button>
    </div>

    <div v-if="isLoading" class="loading-state">
      <p>{{ $t('common.loading') }}...</p>
    </div>

    <div v-else-if="notifications.length === 0" class="empty-state">
      <p>No notifications yet.</p>
    </div>

    <div v-else class="notifications-list">
      <div 
        v-for="notification in notifications" 
        :key="notification.id" 
        class="notification-card" 
        :class="{ unread: !notification.read_at }"
        @click="markAsRead(notification.id, notification.read_at)"
      >
        <div class="notification-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <div class="notification-body">
          <h4>{{ notification.data?.title || 'Notification' }}</h4>
          <p>{{ notification.data?.message || '' }}</p>
          <span class="time">{{ new Date(notification.created_at).toLocaleString() }}</span>
        </div>
        <div class="notification-status">
          <div v-if="!notification.read_at" class="unread-dot"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { fetchNotifications, markNotificationRead, markAllNotificationsRead } from '@/api/services'

const notifications = ref<any[]>([])
const isLoading = ref(true)
const isUpdating = ref(false)

const hasUnread = computed(() => {
  return notifications.value.some(n => !n.read_at)
})

async function loadNotifications() {
  isLoading.value = true
  try {
    const res = await fetchNotifications()
    notifications.value = res.data || res || []
  } catch (error) {
    console.error('Failed to load notifications', error)
  } finally {
    isLoading.value = false
  }
}

async function markAsRead(id: number, readAt: string | null) {
  if (readAt) return
  try {
    await markNotificationRead(id)
    const n = notifications.value.find(item => item.id === id)
    if (n) {
      n.read_at = new Date().toISOString()
    }
  } catch (error) {
    console.error(error)
  }
}

async function markAllRead() {
  if (!hasUnread.value) return
  isUpdating.value = true
  try {
    await markAllNotificationsRead()
    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString()
    })
  } catch (error) {
    console.error(error)
  } finally {
    isUpdating.value = false
  }
}

onMounted(() => {
  loadNotifications()
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.header-row h2 {
  font-size: 1.5rem;
  color: #111827;
  margin: 0;
}
.btn-primary {
  padding: 0.5rem 1rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.2s;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.notification-card {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: box-shadow 0.2s, background-color 0.2s;
  cursor: pointer;
}
.notification-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.notification-card.unread {
  background-color: #f8fafc;
  border-left: 4px solid #3b82f6;
}

.notification-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 50%;
  flex-shrink: 0;
}
.notification-body {
  flex: 1;
}
.notification-body h4 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #111827;
}
.notification-body p {
  margin: 0 0 0.5rem;
  color: #4b5563;
  font-size: 0.9375rem;
}
.time {
  font-size: 0.8125rem;
  color: #9ca3af;
}

.notification-status {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
}
.unread-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #3b82f6;
}
.empty-state, .loading-state {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}
</style>
