<script setup>
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  disabled: Boolean,
  choices: { type: Array, required: true },
  emptyLabel: { type: String, default: null },
  error: { type: String, default: null },
})
const value = defineModel({ type: [String, Number], default: null })
</script>

<template>
  <div class="grid gap-2">
    <Label :for="id">
      {{ label }}
    </Label>
    <Select
      :disabled="disabled"
      :model-value="String(value ?? 'none')"
      @update:model-value="value = $event === 'none' ? null : $event"
    >
      <SelectTrigger
        :id="id"
        :aria-invalid="!!error"
        :aria-describedby="error ? id + '-error' : undefined"
      >
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-if="emptyLabel" value="none">
          {{ emptyLabel }}
        </SelectItem>
        <SelectItem
          v-for="choice in choices"
          :key="choice.id"
          :dusk="`${id}-option-${choice.id}`"
          :value="String(choice.id)"
        >
          {{ choice.name }}
        </SelectItem>
      </SelectContent>
    </Select>
    <p
      v-if="error"
      :id="id + '-error'"
      role="alert"
      class="text-sm text-destructive"
    >
      {{ error }}
    </p>
  </div>
</template>
