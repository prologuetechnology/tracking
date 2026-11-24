<script setup>
import { CollapsibleRoot, useForwardPropsEmits } from 'radix-vue'

const props = defineProps({
  asChild: Boolean,
  as: String,
  open: { type: Boolean, default: undefined },
  defaultOpen: Boolean,
  disabled: Boolean,
})
const emits = defineEmits([`update:open`])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <CollapsibleRoot v-slot="{ open }" v-bind="forwarded">
    <slot :open="open" />
  </CollapsibleRoot>
</template>
