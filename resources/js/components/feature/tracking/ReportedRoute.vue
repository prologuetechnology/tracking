<script setup>
import { computed } from 'vue'

const props = defineProps({ coordinates: { type: Object, required: true } })
const isCoordinate = (value, limit) =>
  value !== null &&
  value !== undefined &&
  String(value).trim() !== `` &&
  Number.isFinite(Number(value)) &&
  Math.abs(Number(value)) <= limit
const points = computed(() => {
  const locations = [
    ...(props.coordinates.allKnownLocations ?? []),
    props.coordinates.lastLocation,
  ]
    .map((location) => location?.coordinates)
    .filter(
      (location) =>
        location &&
        isCoordinate(location.lng, 180) &&
        isCoordinate(location.lat, 90),
    )
  if (!locations.length) return []
  let west = +locations[0].lng
  let east = west
  let north = +locations[0].lat
  let south = north
  for (const { lng, lat } of locations) {
    west = Math.min(west, lng)
    east = Math.max(east, lng)
    north = Math.max(north, lat)
    south = Math.min(south, lat)
  }
  const width = east - west
  const height = north - south
  const scale = Math.min(540 / (width || 0.00001), 230 / (height || 0.00001))
  return locations.map(({ lng, lat }) => [
    300 + (+lng - west - width / 2) * scale,
    135 + (north - +lat - height / 2) * scale,
  ])
})
</script>
<template>
  <figure
    class="grid h-full place-content-center rounded bg-muted p-4 text-primary"
  >
    <svg
      v-if="points.length"
      role="img"
      aria-label="Reported shipment locations in sequence"
      viewBox="0 0 600 270"
      class="h-auto max-h-72 w-full"
    >
      <polyline
        :points="points.map((p) => p.join(',')).join(' ')"
        fill="none"
        stroke="currentColor"
        stroke-width="4"
        stroke-linejoin="round"
      />
      <circle
        :cx="points[0][0]"
        :cy="points[0][1]"
        r="6"
        fill="currentColor"
        stroke="currentColor"
        stroke-width="1"
      />
      <circle
        :cx="points.at(-1)[0]"
        :cy="points.at(-1)[1]"
        r="8"
        fill="currentColor"
        stroke="white"
        stroke-width="3"
      />
    </svg>
    <figcaption class="text-center text-xs text-muted-foreground">
      {{
        points.length
          ? 'Reported locations · Route schematic'
          : 'No reported locations available'
      }}
    </figcaption>
  </figure>
</template>
