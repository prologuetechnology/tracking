<script setup>
import { CollapsibleTrigger } from 'radix-vue'

const props = defineProps({ asChild: Boolean, as: String })
</script>

<template>
  <CollapsibleTrigger v-bind="props">
    <slot />
  </CollapsibleTrigger>
</template>
