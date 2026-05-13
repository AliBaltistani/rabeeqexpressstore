import { computed } from 'vue'
import { useSettingsStore } from '@/stores/settingsStore'

export function useCurrency() {
  const settings = useSettingsStore()

  const currentCurrency = computed(() => settings.currentCurrency)

  /**
   * Format a raw price amount using the current currency settings.
   * If the API already returns formatted prices, prefer using those directly.
   * This is for cases where you have a raw number (e.g., from local calculation).
   */
  function format(amount: number): string {
    const cur = currentCurrency.value
    if (!cur) return `${amount.toFixed(2)} SAR`
    const rate = cur.exchangeRate || 1
    const converted = amount * rate
    const decimals = cur.decimalPlaces ?? 2
    return `${converted.toFixed(decimals)} ${cur.code}`
  }

  /**
   * Convert a raw SAR amount to current currency (raw number, no formatting).
   */
  function convert(amount: number): number {
    const cur = currentCurrency.value
    if (!cur) return amount
    return amount * (cur.exchangeRate || 1)
  }

  /**
   * Display a formatted price from the API response PriceValue.
   * The API already returns both raw and formatted; this just picks formatted.
   */
  function display(priceValue: { raw: number; formatted: string } | null | undefined): string {
    if (!priceValue) return ''
    return priceValue.formatted
  }

  return { format, convert, display, currentCurrency }
}
