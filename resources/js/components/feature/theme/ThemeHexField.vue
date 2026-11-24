<script setup>
import { ref, watch } from 'vue'

import { Input } from '@/components/ui/input'

const props = defineProps({ modelValue: { type: String, default: null } })
const emit = defineEmits([`update:modelValue`])
const focused = ref(false)
const text = ref(props.modelValue ?? ``)

// A newly derived preview color may arrive while staff are typing an override.
watch(
  () => props.modelValue,
  (value) => {
    if (!focused.value) text.value = value ?? ``
  },
)

function finish(event) {
  focused.value = false
  if (event.target.validity.valid) text.value = props.modelValue ?? ``
}

function edit(value) {
  text.value = value
  if (
    /^#[0-9a-f]{6}$/i.test(value) &&
    value.toUpperCase() !== props.modelValue?.toUpperCase()
  ) {
    emit(`update:modelValue`, value.toUpperCase())
  }
}
</script>

<template>
  <Input
    type="text"
    :model-value="text"
    :disabled="modelValue == null"
    required
    pattern="#[0-9A-Fa-f]{6}"
    title="Enter a six-digit hex color, such as #185E86."
    maxlength="7"
    :spellcheck="false"
    @focus="focused = true"
    @blur="finish"
    @update:model-value="edit"
  />
</template>
