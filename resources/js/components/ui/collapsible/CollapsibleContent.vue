<script setup>
import { CollapsibleContent } from 'radix-vue'

const props = defineProps({ asChild: Boolean, as: String, forceMount: Boolean })
</script>

<template>
  <CollapsibleContent
    v-bind="props"
    class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
  >
    <slot />
  </CollapsibleContent>
</template>
