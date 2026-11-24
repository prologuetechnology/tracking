<script setup>
import { RotateCcw } from 'lucide-vue-next'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { SelectField } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

import { advanced, contrast, tokenHex } from './themeColorFields'
import ThemeHexField from './ThemeHexField.vue'
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
</script>
<template>
  <fieldset class="inspector" :disabled="readonly">
    <legend>Brand controls</legend>
    <div v-for="[key, label] in guided" :key="key" class="color-row">
      <Label :for="'hex-' + key">{{ label }}</Label>
      <Input
        type="color"
        :aria-label="'Pick ' + label"
        :model-value="modelValue.colors[key]"
        @update:model-value="color(key, $event)"
      />
      <ThemeHexField
        :id="'hex-' + key"
        :model-value="modelValue.colors[key]"
        @update:model-value="color(key, $event, false)"
      />
    </div>
    <SelectField
      id="headline"
      class="mt-4"
      label="Headline style"
      :disabled="readonly"
      :model-value="modelValue.headline"
      :choices="[
        { id: 'layout', name: 'Layout default' },
        { id: 'sans', name: 'Modern sans' },
        { id: 'serif', name: 'Editorial serif' },
      ]"
      @update:model-value="patch({ headline: $event })"
    />
    <SelectField
      id="corners"
      class="mt-4"
      label="Corners"
      :disabled="readonly"
      :model-value="modelValue.corners"
      :choices="[
        { id: 'square', name: 'Square' },
        { id: 'soft', name: 'Soft' },
        { id: 'round', name: 'Rounded' },
      ]"
      @update:model-value="patch({ corners: $event })"
    />
    <Collapsible class="mt-5 border-t pt-3">
      <CollapsibleTrigger as-child>
        <Button type="button" variant="outline" size="sm">
          Advanced colors
        </Button>
      </CollapsibleTrigger>
      <CollapsibleContent>
        <div
          v-for="[key, label, other] in advanced"
          :key="key"
          class="override"
        >
          <Label :for="'override-' + key">{{ label }}</Label>
          <div class="override-fields">
            <Input
              type="color"
              :aria-label="'Pick ' + label"
              :model-value="effective[key] || '#000000'"
              @update:model-value="color(key, $event, true)"
            />
            <ThemeHexField
              :id="'override-' + key"
              :model-value="effective[key]"
              @update:model-value="color(key, $event, true)"
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
      </CollapsibleContent>
    </Collapsible>
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
.color-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 9px 0;
}
.color-row label {
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
  border: 1px solid hsl(var(--border));
  padding: 2px;
  border-radius: 6px;
  background: hsl(var(--background));
  color: hsl(var(--foreground));
  cursor: pointer;
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
  color: hsl(var(--muted-foreground));
}
.inspector label {
  font-size: 13px;
}
</style>
