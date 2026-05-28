import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface ShippingMethodOption {
  id: number
  slug: string
  name: string
  carrier_type: string
  price: { raw: number; formatted: string }
  estimated_delivery: string
}

export interface PaymentGateway {
  id: string
  name: string
  enabled: boolean
  fee: string | null
  description: string | null
}

export const useCheckoutStore = defineStore('checkout', () => {
  // ─── Step management ───
  const currentStep = ref(1)

  // ─── Shipping methods ───
  const shippingMethods = ref<ShippingMethodOption[]>([])
  const selectedShippingId = ref<number | null>(null)
  const shippingLoading = ref(false)

  const selectedShipping = computed(() =>
    shippingMethods.value.find(m => m.id === selectedShippingId.value) ?? null
  )

  // ─── Payment gateways ───
  const paymentGateways = ref<PaymentGateway[]>([])
  const selectedPaymentId = ref('')
  const paymentLoading = ref(false)

  // Stripe
  const stripeClientSecret = ref<string | null>(null)
  const stripePaymentIntentId = ref<string | null>(null)
  const stripeRequiresAction = ref(false)

  // ─── Order ───
  const orderNumber = ref('')
  const orderLoading = ref(false)
  const orderError = ref('')

  // ─── Actions ───
  function setShippingMethods(methods: ShippingMethodOption[]) {
    shippingMethods.value = methods
    if (methods.length > 0 && !selectedShippingId.value) {
      selectedShippingId.value = methods[0].id
    }
  }

  function setPaymentGateways(gateways: PaymentGateway[]) {
    paymentGateways.value = gateways
    if (gateways.length > 0 && !selectedPaymentId.value) {
      selectedPaymentId.value = gateways[0].id
    }
  }

  function setStripeResult(data: { client_secret?: string | null; payment_intent_id?: string | null; requires_action?: boolean }) {
    stripeClientSecret.value = data.client_secret ?? null
    stripePaymentIntentId.value = data.payment_intent_id ?? null
    stripeRequiresAction.value = data.requires_action ?? false
  }

  function reset() {
    currentStep.value = 1
    shippingMethods.value = []
    selectedShippingId.value = null
    paymentGateways.value = []
    selectedPaymentId.value = ''
    stripeClientSecret.value = null
    stripePaymentIntentId.value = null
    stripeRequiresAction.value = false
    orderNumber.value = ''
    orderLoading.value = false
    orderError.value = ''
  }

  return {
    currentStep,
    shippingMethods, selectedShippingId, selectedShipping, shippingLoading,
    paymentGateways, selectedPaymentId, paymentLoading,
    stripeClientSecret, stripePaymentIntentId, stripeRequiresAction,
    orderNumber, orderLoading, orderError,
    setShippingMethods, setPaymentGateways, setStripeResult, reset,
  }
})
