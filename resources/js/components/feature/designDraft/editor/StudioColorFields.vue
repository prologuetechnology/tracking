<script setup>
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const colors = defineModel({ type: Object, required: true })
defineProps({ errors: { type: Object, required: true } })
const fields = [
  { key: `brand`, label: `Brand color` },
  { key: `accent`, label: `Accent color` },
  { key: `paper`, label: `Page background` },
]
const setColor = (key, value) => {
  colors.value = { ...colors.value, [key]: value.toUpperCase() }
}
</script>
<template>
  <fieldset class="grid gap-3">
    <legend class="sr-only">Custom draft colors</legend>
    <div
      v-for="field in fields"
      :key="field.key"
      class="grid grid-cols-[1fr,auto,7rem] items-center gap-2"
    >
      <Label :for="'draft-color-' + field.key">{{ field.label }}</Label>
      <Input
        type="color"
        :aria-label="'Choose ' + field.label.toLowerCase()"
        class="size-9 cursor-pointer rounded border bg-card p-1"
        :model-value="
          /^#[\da-f]{6}$/i.test(colors[field.key])
            ? colors[field.key]
            : '#ffffff'
        "
        @update:model-value="setColor(field.key, $event)"
      />
      <Input
        :id="'draft-color-' + field.key"
        :model-value="colors[field.key]"
        maxlength="7"
        spellcheck="false"
        :aria-invalid="!!errors['custom_colors.' + field.key]"
        :aria-describedby="
          errors['custom_colors.' + field.key]
            ? field.key + '-color-error'
            : undefined
        "
        class="font-mono"
        @update:model-value="setColor(field.key, $event)"
      />
      <p
        v-if="errors['custom_colors.' + field.key]"
        :id="field.key + '-color-error'"
        role="alert"
        class="col-span-3 text-sm text-destructive"
      >
        Enter a six-digit hex color, such as #185E86.
      </p>
    </div>
    <p class="text-xs text-muted-foreground">
      Text and link colors adjust for contrast. These colors belong to this
      draft.
    </p>
  </fieldset>
</template>
