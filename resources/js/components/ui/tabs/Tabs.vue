<script setup>
import { TabsRoot, useForwardPropsEmits } from 'radix-vue'

const props = defineProps({
  asChild: Boolean,
  as: String,
  modelValue: String,
  defaultValue: String,
  orientation: String,
  dir: String,
  activationMode: String,
})
const emits = defineEmits([`update:modelValue`])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <TabsRoot v-bind="forwarded">
    <slot />
  </TabsRoot>
</template>
