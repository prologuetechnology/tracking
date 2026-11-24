<script setup>
import { TabsContent } from 'radix-vue'
import { reactiveOmit } from '@vueuse/core'

import { cn } from '@/lib/utils'

const props = defineProps({
  asChild: Boolean,
  as: String,
  value: { type: String, required: true },
  forceMount: Boolean,
  class: { type: null, required: false },
})

const delegatedProps = reactiveOmit(props, `class`)
</script>

<template>
  <TabsContent
    :class="
      cn(
        'mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        props.class,
      )
    "
    v-bind="delegatedProps"
  >
    <slot />
  </TabsContent>
</template>
