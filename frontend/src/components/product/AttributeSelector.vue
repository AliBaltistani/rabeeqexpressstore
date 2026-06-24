<template>
  <!-- ── Read-only mode: show label+value pills ── -->
  <div v-if="readonly" class="attr-sel attr-sel--readonly">
    <span
      v-for="group in attributes"
      :key="group.id"
      class="attr-sel__readonly-pill"
    >
      <span class="attr-sel__readonly-label">{{ group.name }}:</span>
      <span class="attr-sel__readonly-value">{{ getSelectedLabel(group) }}</span>
    </span>
  </div>

  <!-- ── Interactive mode: select dropdowns + optional update button ── -->
  <div v-else class="attr-sel">
    <div
      v-for="group in attributes"
      :key="group.id"
      class="attr-sel__group"
    >
      <label class="attr-sel__label">
        {{ group.name }}
        <span v-if="showRequired" class="attr-sel__req">*</span>
      </label>
      <select
        class="attr-sel__select"
        :disabled="disabled"
        :value="Number(localValues[group.id] ?? group.values?.[0]?.id ?? '')"
        @change="onSelect(group.id, ($event.target as HTMLSelectElement).value)"
      >
        <option
          v-for="val in group.values"
          :key="val.id"
          :value="val.id"
        >{{ val.value }}</option>
      </select>
    </div>

    <!-- Optional inline Update button (used by CartPage) -->
    <button
      v-if="showUpdate"
      class="attr-sel__update-btn"
      :disabled="disabled || updating"
      @click="$emit('update')"
    >
      <span v-if="updating" class="attr-sel__spinner"></span>
      <span v-else>{{ $t('cart.updateVariant') }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import type { ProductAttributeGroup } from '@/types'

const props = withDefaults(defineProps<{
  /** Attribute groups from the product / cart item */
  attributes: ProductAttributeGroup[]
  /** Currently selected values: { [groupId]: valueId } */
  modelValue: Record<number, number | string>
  /** Render as read-only badges instead of selects */
  readonly?: boolean
  /** Show the * required marker next to each label */
  showRequired?: boolean
  /** Show the inline "Update" button (CartPage style) */
  showUpdate?: boolean
  /** Disable all inputs (e.g. while an API call is in progress) */
  disabled?: boolean
  /** Show spinner on Update button */
  updating?: boolean
}>(), {
  readonly:     false,
  showRequired: false,
  showUpdate:   false,
  disabled:     false,
  updating:     false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', v: Record<number, number>): void
  (e: 'update'): void
}>()

/**
 * Always work with numbers internally.
 * This prevents the type-mismatch bug where option :value (number) ≠ v-model (string).
 */
const localValues = computed<Record<number, number>>(() => {
  const result: Record<number, number> = {}
  for (const group of props.attributes) {
    const raw = props.modelValue[group.id]
    if (raw !== undefined && raw !== '') {
      result[group.id] = Number(raw)
    } else {
      // Default to first option
      result[group.id] = Number(group.values?.[0]?.id ?? 0)
    }
  }
  return result
})

function onSelect(groupId: number, rawValue: string) {
  const next = { ...localValues.value, [groupId]: Number(rawValue) }
  emit('update:modelValue', next)
}

/** Return the display label for the selected value in a group (readonly mode). */
function getSelectedLabel(group: ProductAttributeGroup): string {
  const selectedId = Number(props.modelValue[group.id] ?? group.values?.[0]?.id)
  const found = group.values.find(v => v.id === selectedId)
  return found?.value ?? group.values?.[0]?.value ?? '-'
}

// On mount: if modelValue has no entry for a group, auto-emit defaults so parent stays in sync
watch(
  () => props.attributes,
  (attrs) => {
    if (props.readonly || !attrs?.length) return
    const current = props.modelValue
    const defaults: Record<number, number> = {}
    let hasNew = false
    for (const group of attrs) {
      if (current[group.id] === undefined || current[group.id] === '') {
        defaults[group.id] = Number(group.values?.[0]?.id ?? 0)
        hasNew = true
      }
    }
    if (hasNew) {
      emit('update:modelValue', { ...localValues.value, ...defaults })
    }
  },
  { immediate: true },
)
</script>

<style scoped>
/* ── Interactive ── */
.attr-sel {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.attr-sel__group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.attr-sel__label {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--store-text-primary, #111827);
  flex-shrink: 0;
  min-width: 48px;
}
.attr-sel__req {
  color: #ef4444;
  margin-inline-start: 1px;
}
.attr-sel__select {
  flex: 1;
  min-width: 0;
  padding: 0.4rem 0.625rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 7px;
  font-size: 0.8125rem;
  color: var(--store-text-primary, #111827);
  background: var(--bg-primary, #fff);
  outline: none;
  cursor: pointer;
  appearance: auto;
  transition: border-color 0.2s;
}
.attr-sel__select:focus { border-color: var(--color-primary, #858585); }
.attr-sel__select:disabled { opacity: 0.6; cursor: not-allowed; }

/* Update button (cart mode) */
.attr-sel__update-btn {
  align-self: flex-start;
  padding: 0.35rem 0.875rem;
  background: var(--color-primary, #858585);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  min-width: 60px;
  justify-content: center;
}
.attr-sel__update-btn:hover:not(:disabled) { opacity: 0.85; }
.attr-sel__update-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Spinner */
.attr-sel__spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: attr-spin 0.7s linear infinite;
}
@keyframes attr-spin { to { transform: rotate(360deg); } }

/* ── Read-only ── */
.attr-sel--readonly {
  flex-direction: row;
  flex-wrap: wrap;
  gap: 0.375rem;
}
.attr-sel__readonly-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.6875rem;
  background: #f3f4f6;
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
}
.attr-sel__readonly-label {
  font-weight: 600;
  color: #6b7280;
}
.attr-sel__readonly-value {
  font-weight: 600;
  color: var(--store-text-primary, #111827);
}
</style>
