<script setup>
import { RotateCcw } from 'lucide-vue-next'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

import { advanced, contrast, tokenHex } from './reviewStyles'
const props = defineProps({
  modelValue: { type: Object, required: true },
  derived: { type: Object, required: true },
  readonly: Boolean,
})
const emit = defineEmits([`update:modelValue`])
const guided = [
  [`brand`, `Brand color`],
  [`accent`, `Accent`],
  [`paper`, `Page background`],
]
const patch = (part) =>
  emit(`update:modelValue`, { ...props.modelValue, ...part })
function color(key, value, custom = false) {
  const field = custom ? `overrides` : `colors`
  if (/^#[0-9a-f]{6}$/i.test(value))
    patch({
      [field]: { ...props.modelValue[field], [key]: value.toUpperCase() },
    })
}
const effective = computed(() =>
  Object.fromEntries(
    Object.entries(props.derived).map(([id, hsl]) => [
      id,
      props.modelValue.overrides[id] ?? tokenHex(hsl),
    ]),
  ),
)
function reset(key) {
  const values = { ...props.modelValue.overrides }
  delete values[key]
  patch({ overrides: values })
}
function pairing(key, other) {
  if (!other || !effective.value[key] || !effective.value[other]) return ``
  const ratio = contrast(effective.value[key], effective.value[other])
  return (
    ratio.toFixed(1) +
    `:1 · ` +
    (ratio >= (key === `ring` ? 3 : 4.5) ? `Good contrast` : `Low contrast`)
  )
}
function finish(event, key, custom) {
  const value = event.target.value.trim()
  if (!/^#[0-9a-f]{6}$/i.test(value)) {
    event.target.setCustomValidity(
      `Enter a six-digit hex color, such as #185E86.`,
    )
    event.target.reportValidity()
    return
  }
  event.target.setCustomValidity(``)
  color(key, value, custom)
}
</script>
<template>
  <fieldset class="inspector" :disabled="readonly">
    <legend>Brand controls</legend>
    <div v-for="[key, label] in guided" :key="key" class="color-row">
      <label :for="'hex-' + key">{{ label }}</label>
      <input
        type="color"
        :aria-label="'Pick ' + label"
        :value="modelValue.colors[key]"
        @input="color(key, $event.target.value)"
      />
      <Input
        :id="'hex-' + key"
        :model-value="modelValue.colors[key]"
        maxlength="7"
        @input="$event.target.setCustomValidity('')"
        @change="finish($event, key, false)"
      />
    </div>
    <div class="style-choice">
      <label for="headline">Headline style</label>
      <select
        id="headline"
        :value="modelValue.headline"
        @change="patch({ headline: $event.target.value })"
      >
        <option value="layout">Layout default</option>
        <option value="sans">Modern sans</option>
        <option value="serif">Editorial serif</option>
      </select>
    </div>
    <div class="style-choice">
      <label for="corners">Corners</label>
      <select
        id="corners"
        :value="modelValue.corners"
        @change="patch({ corners: $event.target.value })"
      >
        <option value="square">Square</option>
        <option value="soft">Soft</option>
        <option value="round">Rounded</option>
      </select>
    </div>
    <details>
      <summary>Advanced colors</summary>
      <div v-for="[key, label, other] in advanced" :key="key" class="override">
        <label :for="'override-' + key">{{ label }}</label>
        <div class="override-fields">
          <input
            type="color"
            :aria-label="'Pick ' + label"
            :value="effective[key] || '#000000'"
            @input="color(key, $event.target.value, true)"
          />
          <Input
            :id="'override-' + key"
            :model-value="effective[key]"
            maxlength="7"
            @input="$event.target.setCustomValidity('')"
            @change="finish($event, key, true)"
          />
          <Button
            type="button"
            size="icon"
            variant="ghost"
            :disabled="!modelValue.overrides[key]"
            :aria-label="'Reset ' + label + ' to derived value'"
            title="Reset to derived value"
            @click="reset(key)"
          >
            <RotateCcw class="size-4" />
          </Button>
        </div>
        <small v-if="other">{{ pairing(key, other) }}</small>
      </div>
    </details>
  </fieldset>
</template>
<style scoped>
.inspector {
  min-width: 0;
}
.inspector legend {
  font-weight: 700;
  margin-bottom: 10px;
}
.color-row,
.style-choice {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 9px 0;
}
.color-row label,
.style-choice label {
  margin-right: auto;
}
.color-row input:last-child,
.override-fields input[type='text'] {
  width: 94px;
  font: 12px monospace;
}
.inspector input[type='color'] {
  width: 32px;
  height: 32px;
  border: 1px solid var(--line);
  padding: 2px;
  border-radius: 6px;
  background: white;
  cursor: pointer;
}
.inspector select {
  max-width: 160px;
  border: 1px solid var(--line);
  border-radius: 7px;
  padding: 6px;
  background: white;
}
.inspector details {
  margin-top: 18px;
  border-top: 1px solid var(--line);
}
.inspector summary {
  padding: 12px 0;
  cursor: pointer;
  font-weight: 650;
}
.override {
  margin: 10px 0;
}
.override-fields {
  display: flex;
  align-items: center;
  gap: 8px;
}
.override small {
  font-size: 11px;
  color: var(--sub);
}
.inspector label {
  font-size: 13px;
}
</style>
