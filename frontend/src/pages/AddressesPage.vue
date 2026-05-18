<template>
  <div class="account-content-inner">
      <div class="header-row">
        <h2>{{ $t('account.addresses') || 'Addresses' }}</h2>
        <button class="btn-primary" @click="openAddForm">{{ $t('address.addNew') || 'Add New Address' }}</button>
      </div>

      <div v-if="isLoading" class="loading-state">
        <p>{{ $t('common.loading') }}...</p>
      </div>
      
      <div v-else-if="addresses.length === 0" class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
        </svg>
        <h3>{{ $t('address.noAddresses') || 'No addresses saved yet' }}</h3>
        <p>{{ $t('address.noAddressesDesc') || 'Add an address for faster checkout.' }}</p>
      </div>

      <div v-else class="addresses-grid">
        <div v-for="addr in addresses" :key="addr.id" class="address-card" :class="{ 'is-default': addr.isDefault }">
          <div class="card-header">
            <h4>{{ addr.firstName }} {{ addr.lastName }} <span v-if="addr.isDefault" class="default-badge">{{ $t('address.default') || 'Default' }}</span></h4>
            <div class="actions">
              <button class="btn-icon" @click="openEditForm(addr)" title="Edit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              </button>
              <button class="btn-icon text-red" @click="confirmDelete(addr.id)" title="Delete">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              </button>
            </div>
          </div>
          <div class="card-body">
            <p>{{ addr.phone }}</p>
            <p>{{ addr.addressLine1 }}</p>
            <p v-if="addr.addressLine2">{{ addr.addressLine2 }}</p>
            <p>{{ addr.city }}, {{ addr.state }} {{ addr.postalCode }}</p>
            <p>{{ addr.country }}</p>
          </div>
        </div>
      </div>

      <!-- Address Modal -->
      <transition name="modal-fade">
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
          <div class="modal-content">
            <div class="modal-header">
              <h3>{{ isEditing ? ($t('address.edit') || 'Edit Address') : ($t('address.addNew') || 'Add New Address') }}</h3>
              <button class="close-btn" @click="showModal = false">×</button>
            </div>
            <form @submit.prevent="saveAddress" class="address-form">
              <div class="form-row">
                <div class="form-group">
                  <label>{{ $t('checkout.firstName') || 'First Name' }}</label>
                  <input type="text" v-model="formData.firstName" required />
                </div>
                <div class="form-group">
                  <label>{{ $t('checkout.lastName') || 'Last Name' }}</label>
                  <input type="text" v-model="formData.lastName" required />
                </div>
              </div>
              <div class="form-group">
                <label>{{ $t('checkout.phoneNumber') || 'Phone' }}</label>
                <input type="text" v-model="formData.phone" required />
              </div>
              <div class="form-group">
                <label>{{ $t('checkout.street') || 'Address Line 1' }}</label>
                <input type="text" v-model="formData.addressLine1" required />
              </div>
              <div class="form-group">
                <label>{{ $t('checkout.buildingDesc') || 'Address Line 2 (Optional)' }}</label>
                <input type="text" v-model="formData.addressLine2" />
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>{{ $t('checkout.city') || 'City' }}</label>
                  <input type="text" v-model="formData.city" required />
                </div>
                <div class="form-group">
                  <label>{{ $t('checkout.region') || 'State/Region' }}</label>
                  <input type="text" v-model="formData.state" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>{{ $t('checkout.postalCode') || 'Postal Code' }}</label>
                  <input type="text" v-model="formData.postalCode" />
                </div>
                <div class="form-group">
                  <label>{{ $t('checkout.country') || 'Country' }}</label>
                  <input type="text" v-model="formData.country" required />
                </div>
              </div>
              <div class="form-group checkbox-group">
                <input type="checkbox" id="is_default" v-model="formData.isDefault" />
                <label for="is_default">{{ $t('address.setAsDefault') || 'Set as default address' }}</label>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="showModal = false">{{ $t('common.close') || 'Cancel' }}</button>
                <button type="submit" class="btn-save" :disabled="isSaving">
                  {{ isSaving ? ($t('common.loading') || 'Saving...') : ($t('checkout.save') || 'Save') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { fetchAddresses, addAddress, updateAddress, deleteAddress } from '@/api/services'
import type { Address } from '@/types'

const auth = useAuthStore()
const router = useRouter()

const addresses = ref<Address[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const currentId = ref<number | null>(null)

const formData = ref<Partial<Address>>({
  firstName: '',
  lastName: '',
  phone: '',
  addressLine1: '',
  addressLine2: '',
  city: '',
  state: '',
  postalCode: '',
  country: 'Saudi Arabia',
  isDefault: false
})

async function loadAddresses() {
  if (!auth.isAuthenticated) return
  isLoading.value = true
  try {
    addresses.value = await fetchAddresses()
  } catch (error) {
    console.error('Failed to load addresses:', error)
  } finally {
    isLoading.value = false
  }
}

function openAddForm() {
  isEditing.value = false
  currentId.value = null
  formData.value = {
    firstName: auth.user?.name?.split(' ')[0] || '',
    lastName: auth.user?.name?.split(' ').slice(1).join(' ') || '',
    phone: auth.user?.phone || '',
    addressLine1: '',
    addressLine2: '',
    city: '',
    state: '',
    postalCode: '',
    country: 'Saudi Arabia',
    isDefault: addresses.value.length === 0
  }
  showModal.value = true
}

function openEditForm(addr: Address) {
  isEditing.value = true
  currentId.value = addr.id
  formData.value = { ...addr }
  showModal.value = true
}

async function saveAddress() {
  isSaving.value = true
  try {
    if (isEditing.value && currentId.value) {
      await updateAddress(currentId.value, formData.value)
    } else {
      await addAddress(formData.value)
    }
    showModal.value = false
    await loadAddresses()
  } catch (error) {
    console.error('Failed to save address:', error)
  } finally {
    isSaving.value = false
  }
}

async function confirmDelete(id: number) {
  if (confirm('Are you sure you want to delete this address?')) {
    try {
      await deleteAddress(id)
      await loadAddresses()
    } catch (error) {
      console.error('Failed to delete address:', error)
    }
  }
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  } else {
    loadAddresses()
  }
})
</script>

<style scoped>
.account-content-inner {
  flex: 1;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.header-row h2 {
  font-size: 1.5rem;
  color: var(--store-text-primary, #111827);
  margin: 0;
}

.btn-primary {
  padding: 0.625rem 1.25rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-primary:hover {
  opacity: 0.9;
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
}

.loading-state {
  text-align: center;
  padding: 4rem;
  color: #6b7280;
}

.addresses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.address-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.address-card.is-default {
  border-color: var(--color-primary, #858585);
  box-shadow: 0 0 0 1px var(--color-primary, #858585);
}
.address-card:hover {
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.card-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.default-badge {
  font-size: 0.6875rem;
  background: #fef3c7;
  color: #d97706;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-weight: 600;
}
.actions {
  display: flex;
  gap: 0.5rem;
}
.btn-icon {
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-icon:hover {
  background: #f3f4f6;
  color: #111827;
}
.btn-icon.text-red:hover {
  color: #ef4444;
  background: #fef2f2;
}

.card-body p {
  margin: 0 0 0.375rem;
  font-size: 0.875rem;
  color: #4b5563;
  line-height: 1.5;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #111827;
}
.close-btn {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
}
.address-form {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.form-row {
  display: flex;
  gap: 1rem;
}
.form-row .form-group {
  flex: 1;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}
.form-group input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9375rem;
  color: #111827;
}
.form-group input:focus {
  outline: none;
  border-color: var(--color-primary, #858585);
  box-shadow: 0 0 0 1px var(--color-primary, #858585);
}
.checkbox-group {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
}
.checkbox-group input {
  width: 1rem;
  height: 1rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}
.btn-cancel {
  padding: 0.625rem 1.25rem;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}
.btn-save {
  padding: 0.625rem 1.25rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}
.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
