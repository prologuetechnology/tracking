<script setup>
import { onMounted, useTemplateRef } from 'vue'

const props = defineProps({
  shipmentCoordinates: {
    type: Object,
    required: true,
  },
})

const trackingMap = useTemplateRef(`trackingMap`)

onMounted(async () => {
  const { default: TrimbleMaps } = await import(`@trimblemaps/trimblemaps-js`)

  TrimbleMaps.APIKey = import.meta.env.VITE_TRIMBLE_API_KEY

  const lngLat = new TrimbleMaps.LngLat(
    props.shipmentCoordinates.lastLocation.coordinates.lng,
    props.shipmentCoordinates.lastLocation.coordinates.lat,
  )

  const myMap = new TrimbleMaps.Map({
    container: trackingMap.value,
    center: lngLat,
    interactive: false,
    boxZoom: false,
  })

  const simplifiedCoordinates =
    props.shipmentCoordinates?.allKnownLocations?.map((locations) => [
      locations.coordinates.lng,
      locations.coordinates.lat,
    ])

  const coordinates = simplifiedCoordinates.map(
    (simplifiedCoordinate) =>
      new TrimbleMaps.LngLat(simplifiedCoordinate[0], simplifiedCoordinate[1]),
  )

  const myRoute = new TrimbleMaps.Route({
    routeId: `myRoute`,
    stops: coordinates,
  })

  myMap.on(`load`, () => myRoute.addTo(myMap))
})
</script>

<template>
  <div
    id="trackingMap"
    ref="trackingMap"
    class="h-[25rem] w-full overflow-hidden rounded [&_canvas]:!rounded"
  />
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
