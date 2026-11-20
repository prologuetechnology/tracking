<script setup>
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'

import ReportedRoute from './ReportedRoute.vue'

const props = defineProps({
  shipmentCoordinates: {
    type: Object,
    required: true,
  },
})

const trackingMap = useTemplateRef(`trackingMap`)
const fallback = ref(!import.meta.env.VITE_TRIMBLE_API_KEY)
const ready = ref(false)
let map
let timer
let disposed = false
const useFallback = () => {
  fallback.value = true
  clearTimeout(timer)
}
onBeforeUnmount(() => {
  disposed = true
  clearTimeout(timer)
  map?.remove()
})
onMounted(async () => {
  if (fallback.value) return
  timer = setTimeout(useFallback, 12000)
  try {
    const { default: TrimbleMaps } = await import(`@trimblemaps/trimblemaps-js`)
    if (disposed || fallback.value) return
    TrimbleMaps.APIKey = import.meta.env.VITE_TRIMBLE_API_KEY
    const center = props.shipmentCoordinates.lastLocation.coordinates
    map = new TrimbleMaps.Map({
      container: trackingMap.value,
      center: new TrimbleMaps.LngLat(center.lng, center.lat),
      interactive: false,
      boxZoom: false,
    })
    const stops = props.shipmentCoordinates.allKnownLocations.map(
      ({ coordinates }) =>
        new TrimbleMaps.LngLat(coordinates.lng, coordinates.lat),
    )
    map.on(`error`, useFallback)
    map.on(`load`, () => {
      if (stops.length > 1)
        new TrimbleMaps.Route({ routeId: `myRoute`, stops }).addTo(map)
      map.once(`idle`, () => {
        ready.value = true
        clearTimeout(timer)
      })
    })
  } catch {
    useFallback()
  }
})
</script>

<template>
  <div
    id="trackingMap"
    :data-map-ready="ready || fallback"
    class="h-[25rem] w-full overflow-hidden rounded [&_canvas]:!rounded"
  >
    <ReportedRoute v-if="fallback" :coordinates="shipmentCoordinates" />
    <div v-show="!fallback" ref="trackingMap" class="h-full w-full" />
  </div>
</template>

<style>
.trimblemaps-ctrl-bottom-right {
  display: none !important;
  visibility: hidden !important;
}

.trimblemaps-canvas-container {
  position: relative !important;
}

.trimblemaps-canvas {
  width: 100% !important;
}
</style>
