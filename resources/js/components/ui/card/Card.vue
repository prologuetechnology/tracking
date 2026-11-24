<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
  as: { type: String, default: `div` },
  class: { type: null, required: false },
})
</script>

<template>
  <component
    :is="as"
    data-slot="card"
    :class="
      cn(
        'rounded-lg border bg-card text-card-foreground shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </component>
</template>
