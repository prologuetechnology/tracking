<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useClipboard } from '@vueuse/core'

import { Button } from '@/components/ui/button'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

defineProps({
  detail: {
    type: [Number, String],
    required: true,
  },
  label: {
    type: String,
    required: true,
    default: `ADD LABEL`,
  },
  icon: {
    type: [Object, Boolean],
    required: false,
    default: () => false,
  },
  size: {
    type: String,
    require: false,
    default: ``,
  },
  isCopyable: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const proNumberClipboard = useClipboard()
</script>

<template>
  <div
    class="grid grid-cols-[auto,1fr] grid-rows-2 gap-x-4 gap-y-1"
    :class="{
      'grid-cols[auto,1fr]': icon,
      'grid-cols-1': !icon,
    }"
  >
    <div v-if="icon" class="row-start-1 row-end-3 items-center self-center">
      <FontAwesomeIcon :icon="icon" class="text-xl text-primary" fixed-width />
    </div>

    <div class="row-start-1 row-end-2 self-end">
      <TooltipProvider v-if="isCopyable">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="link"
              class="group m-0 h-auto p-0 text-base font-semibold leading-none text-foreground underline"
              @click="proNumberClipboard.copy(detail)"
            >
              {{ detail }}
            </Button>
          </TooltipTrigger>

          <TooltipContent>
            <p>Click to copy</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <p v-else class="text-base font-semibold leading-none text-foreground">
        {{ detail }}
      </p>
    </div>

    <div class="row-start-2 row-end-3 self-start">
      <p class="text-xs font-semibold leading-none text-muted-foreground">
        {{ label }}
      </p>
    </div>
  </div>
</template>
