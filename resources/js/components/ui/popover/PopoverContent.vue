<script setup>
import { PopoverContent, PopoverPortal } from 'radix-vue'
defineOptions({ inheritAttrs: false })
</script>
<template>
  <PopoverPortal>
    <PopoverContent
      v-bind="$attrs"
      :side-offset="6"
      :collision-padding="12"
      class="z-50 w-72 max-w-[calc(100vw-1.5rem)] rounded-md border bg-popover p-3 text-sm leading-relaxed text-popover-foreground shadow-md focus:outline-none"
    >
      <slot />
    </PopoverContent>
  </PopoverPortal>
</template>
