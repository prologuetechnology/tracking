<script setup>
import { ChevronDown } from 'lucide-vue-next'

import { moduleNames } from '@/composables/hooks/tracking/useTrackingDesign'

import TrackingModule from './TrackingModule.vue'

defineProps({ sections: { type: Array, required: true } })
</script>

<template>
  <div class="divide-y divide-border">
    <details
      v-for="section in sections"
      :key="section"
      :data-section="section"
      class="group/section"
    >
      <summary
        class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-sm py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden"
      >
        {{ moduleNames[section] }}
        <ChevronDown
          class="size-4 shrink-0 transition-transform group-open/section:rotate-180 motion-reduce:transition-none"
          aria-hidden="true"
        />
      </summary>
      <TrackingModule :section="section" class="pb-4" />
    </details>
  </div>
</template>
