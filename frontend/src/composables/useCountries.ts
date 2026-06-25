/**
 * useCountries — singleton composable
 *
 * Fetches the active-countries list exactly ONCE, regardless of how many
 * components consume it. All PhoneInput instances share the same reactive
 * `countries` and `loading` state, eliminating duplicate API calls and
 * race-condition sync issues.
 */

import { ref, readonly } from 'vue'
import { fetchActiveCountries } from '@/api/services'

const DEFAULT_COUNTRIES = [
    { code: 'SA', name: 'Saudi Arabia', phone_code: '+966', flag_url: null },
    { code: 'AE', name: 'UAE', phone_code: '+971', flag_url: null },
    { code: 'KW', name: 'Kuwait', phone_code: '+965', flag_url: null },
    { code: 'QA', name: 'Qatar', phone_code: '+974', flag_url: null },
    { code: 'BH', name: 'Bahrain', phone_code: '+973', flag_url: null },
    { code: 'OM', name: 'Oman', phone_code: '+968', flag_url: null },
    { code: 'EG', name: 'Egypt', phone_code: '+20', flag_url: null },
    { code: 'JO', name: 'Jordan', phone_code: '+962', flag_url: null },
    { code: 'LB', name: 'Lebanon', phone_code: '+961', flag_url: null },
    { code: 'IQ', name: 'Iraq', phone_code: '+964', flag_url: null },
    { code: 'YE', name: 'Yemen', phone_code: '+967', flag_url: null },
    { code: 'MA', name: 'Morocco', phone_code: '+212', flag_url: null },
    { code: 'PK', name: 'Pakistan', phone_code: '+92', flag_url: null },
    { code: 'IN', name: 'India', phone_code: '+91', flag_url: null },
    { code: 'US', name: 'United States', phone_code: '+1', flag_url: null },
    { code: 'GB', name: 'United Kingdom', phone_code: '+44', flag_url: null },
]

// Module-level singletons — shared across ALL component instances
const countries = ref<any[]>([])
const loading = ref(false)
let fetched = false
let fetchPromise: Promise<void> | null = null

async function ensureLoaded(): Promise<void> {
    if (fetched) return
    if (fetchPromise) return fetchPromise

    fetchPromise = (async () => {
        loading.value = true
        try {
            const result = await fetchActiveCountries()
            countries.value = result?.length ? result : DEFAULT_COUNTRIES
        } catch {
            countries.value = DEFAULT_COUNTRIES
        } finally {
            loading.value = false
            fetched = true
            fetchPromise = null
        }
    })()

    return fetchPromise
}

export function useCountries() {
    // Kick off the load (no-op if already running/done)
    ensureLoaded()

    return {
        countries: readonly(countries) as typeof countries,
        loading: readonly(loading) as typeof loading,
        /** Find a country by its dialling code, e.g. '+966' */
        findByCode: (code: string) =>
            countries.value.find((c: any) => c.phone_code === code) ?? null,
        /** Find a country by its ISO-2 code, e.g. 'SA' */
        findByIso: (iso: string) =>
            countries.value.find((c: any) => c.code === iso?.toUpperCase()) ?? null,
        /** Reload (force-refresh), useful after admin changes */
        reload: () => { fetched = false; fetchPromise = null; ensureLoaded() },
    }
}
