<script setup>
import { TabsList } from 'radix-vue'
import { reactiveOmit } from '@vueuse/core'

import { cn } from '@/lib/utils'

const props = defineProps({
  asChild: Boolean,
  as: String,
  loop: { type: Boolean, default: true },
  class: { type: null, required: false },
})

const delegatedProps = reactiveOmit(props, `class`)
</script>

<template>
  <TabsList
    v-bind="delegatedProps"
    :class="
      cn(
        'inline-flex items-center justify-center rounded-md bg-muted p-1 text-muted-foreground',
        props.class,
      )
    "
  >
    <slot />
  </TabsList>
</template>
