<script setup>
import { Check } from 'lucide-vue-next'

import DraftSelect from '@/components/feature/designDraft/DraftSelect.vue'

defineProps({
  classic: { type: Boolean, required: true },
  palettes: { type: Array, required: true },
  themes: { type: Array, required: true },
  errors: { type: Object, required: true },
})
// Vue's compiler requires string literals for named models.
/* eslint-disable quotes */
const palette = defineModel('palette', { type: String, required: true })
const theme = defineModel('theme', { type: [String, Number], default: null })
const headline = defineModel('headline', { type: String, required: true })
/* eslint-enable quotes */
</script>
<template>
  <div class="grid gap-4">
    <fieldset v-if="!classic" class="min-w-0 space-y-2">
      <legend class="text-sm font-medium">Color palette</legend>
      <div class="flex flex-wrap gap-2">
        <label
          v-for="item in palettes"
          :key="item.id"
          :dusk="'palette-' + item.id"
          class="cursor-pointer"
        >
          <input
            v-model="palette"
            class="peer sr-only"
            type="radio"
            name="palette"
            :value="item.id"
          />
          <span
            class="inline-flex min-h-9 items-center gap-2 rounded-md border px-2.5 text-xs font-semibold hover:bg-muted peer-checked:border-primary peer-checked:bg-primary/5 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ring"
          >
            <span
              aria-hidden="true"
              class="size-3.5 rounded-full border border-foreground/15"
              :style="{
                background:
                  item.color ||
                  'linear-gradient(90deg, currentColor 50%, transparent 50%)',
              }"
            />
            {{ item.name
            }}<Check
              v-if="palette === item.id"
              class="size-3.5"
              aria-hidden="true"
            />
          </span>
        </label>
      </div>
      <p v-if="errors.palette" role="alert" class="text-sm text-destructive">
        {{ errors.palette }}
      </p>
    </fieldset>
    <DraftSelect
      v-if="classic || palette === 'company'"
      id="draft-theme"
      v-model="theme"
      label="Library theme"
      empty-label="Default colors"
      :choices="themes"
      :error="errors.theme_id"
    />
    <DraftSelect
      v-if="!classic"
      id="draft-headline"
      v-model="headline"
      label="Headline style"
      :choices="[
        { id: 'layout', name: 'Layout default' },
        { id: 'sans', name: 'Modern sans' },
        { id: 'serif', name: 'Editorial serif' },
      ]"
      :error="errors.headline_style"
    />
  </div>
</template>
