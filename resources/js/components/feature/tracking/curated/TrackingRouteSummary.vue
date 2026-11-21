<script setup>
import { ChevronDown } from 'lucide-vue-next'
import { computed, defineAsyncComponent, ref } from 'vue'

import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

const TrackingMap = defineAsyncComponent(
  () => import(`@/components/feature/tracking/TrackingMap.vue`),
)
const { events, coordinates } = useTrackingDesign()
const open = ref(false)
const stops = computed(() => {
  const itinerary = []
  for (const event of [...events.value].reverse()) {
    const place = typeof event.location === `string` && event.location.trim()
    if (event.at && place && itinerary.at(-1) !== place) itinerary.push(place)
  }
  return itinerary
})
</script>

<template>
  <div class="grid min-w-0 gap-2">
    <ol
      v-if="stops.length"
      class="route-stops"
      aria-label="Reported scan locations, oldest first"
    >
      <li
        v-for="(stop, index) in stops"
        :key="index"
        class="min-w-0 text-xs font-medium"
      >
        <span class="stop-marker" aria-hidden="true"></span>
        <span class="block pt-1.5 [overflow-wrap:anywhere]">{{ stop }}</span>
      </li>
    </ol>
    <details class="group/map" @toggle="open = $event.currentTarget.open">
      <summary
        class="inline-flex min-h-9 cursor-pointer list-none items-center gap-1.5 rounded-sm text-xs font-semibold text-primary underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden"
      >
        <ChevronDown
          class="size-3.5 transition-transform group-open/map:rotate-180 motion-reduce:transition-none"
          aria-hidden="true"
        />
        {{ open ? 'Hide tracking map' : 'View tracking map' }}
      </summary>
      <div v-if="open" data-proof-map>
        <TrackingMap
          :key="JSON.stringify(coordinates)"
          :shipment-coordinates="coordinates"
        />
      </div>
    </details>
  </div>
</template>

<style scoped>
.route-stops {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(3.45rem, 100%), 1fr));
  row-gap: 0.75rem;
  margin: 0.35rem 0 0;
}
.route-stops li {
  position: relative;
  padding-inline-end: 0.5rem;
}
.route-stops li:not(:last-child)::before {
  content: '';
  position: absolute;
  inset: 0.24rem 0 auto;
  border-block-start: 1px solid hsl(var(--border));
}
.stop-marker {
  position: relative;
  display: block;
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 100%;
  background: hsl(var(--primary));
  outline: 2px solid hsl(var(--card));
}
</style>
