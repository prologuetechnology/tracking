<script setup>
import { Check } from 'lucide-vue-next'
import { computed } from 'vue'

import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

const props = defineProps({
  layouts: { type: Array, required: true },
  error: { type: String, default: null },
})
const selected = defineModel({ type: String, required: true })
const current = computed(() =>
  props.layouts.find((item) => item.id === selected.value),
)
</script>

<template>
  <fieldset
    id="draft-layout"
    class="min-w-0 space-y-3"
    :aria-describedby="error ? 'layout-error' : 'layout-description'"
  >
    <legend class="text-sm font-semibold">Tracking layout</legend>
    <RadioGroup
      v-model="selected"
      name="draft-layout"
      aria-label="Tracking layout"
      class="grid-cols-2"
    >
      <RadioGroupItem
        v-for="layout in layouts"
        :key="layout.id"
        :dusk="'draft-layout-option-' + layout.id"
        :value="layout.id"
        :aria-invalid="!!error"
        choice
        class="flex-col items-stretch"
      >
        <span class="flex items-center justify-between" aria-hidden="true">
          <span class="layout-glyph" :data-layout="layout.id">
            <i v-for="block in 4" :key="block"></i>
          </span>
          <Check v-if="selected === layout.id" class="size-4 text-primary" />
        </span>
        <span class="text-sm font-semibold leading-tight">{{
          layout.name
        }}</span></RadioGroupItem
      ></RadioGroup
    >
    <p
      id="layout-description"
      class="min-h-8 text-sm leading-snug text-muted-foreground"
    >
      {{ current?.description }}
    </p>
    <p
      v-if="error"
      id="layout-error"
      role="alert"
      class="text-sm text-destructive"
    >
      {{ error }}
    </p>
  </fieldset>
</template>

<style scoped>
.layout-glyph {
  display: grid;
  width: 2.3rem;
  height: 1.65rem;
  padding: 0.2rem;
  gap: 0.12rem;
  grid-template: repeat(3, minmax(0, 1fr)) / repeat(2, minmax(0, 1fr));
  border: 1px solid hsl(var(--muted-foreground) / 0.45);
  border-radius: 0.24rem;
  background: hsl(var(--background));
}
.layout-glyph i {
  border-radius: 0.08rem;
  background: hsl(var(--muted-foreground) / 0.3);
}
.layout-glyph i:first-child {
  background: hsl(var(--foreground) / 0.72);
}
.layout-glyph[data-layout='classic.v1'] i:first-child,
.layout-glyph[data-layout='classic.v1'] i:nth-child(2),
.layout-glyph[data-layout='answer.v1'] i:nth-child(n + 3),
.layout-glyph[data-layout='record.v1'] i {
  grid-column: span 2;
}
.layout-glyph[data-layout='journey.v1'] i:first-child {
  grid-row: span 3;
}
.layout-glyph[data-layout='record.v1'] {
  grid-template-rows: repeat(4, minmax(0, 1fr));
}
</style>
