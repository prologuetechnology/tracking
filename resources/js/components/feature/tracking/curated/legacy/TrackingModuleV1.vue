<script setup>
import {
  ArrowUpRight,
  ClipboardList,
  FileText,
  MapPin,
  Package,
  Route,
} from 'lucide-vue-next'
import { defineAsyncComponent } from 'vue'

import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

defineProps({
  section: { type: String, required: true },
  featured: { type: Boolean, default: false },
})
const {
  moduleNames,
  shipment,
  references,
  addresses,
  documents,
  coordinates,
  place,
} = useTrackingDesign()
const icons = {
  receiving: MapPin,
  references: ClipboardList,
  instructions: Package,
  map: Route,
  documents: FileText,
}
const TrackingMap = defineAsyncComponent(
  () => import(`@/components/feature/tracking/TrackingMap.vue`),
)
</script>
<template>
  <section
    :data-section="section"
    :data-featured="featured || undefined"
    class="min-w-0 break-words"
  >
    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
      <component :is="icons[section]" class="size-4 shrink-0 text-primary" />{{
        moduleNames[section]
      }}
    </h3>
    <div v-if="section === 'receiving'" class="grid gap-4">
      <address
        v-for="[label, a] in addresses"
        :key="label"
        class="grid gap-1 text-sm not-italic leading-snug"
      >
        <span class="text-xs text-muted-foreground">{{
          label === 'Destination' ? 'Deliver to' : 'Shipped from'
        }}</span>
        <strong v-if="a.name">{{ a.name }}</strong
        ><span v-if="a.address">{{ a.address }}</span
        ><span v-if="a.address2">{{ a.address2 }}</span>
        <span v-if="place(a) || a.zipCode">{{
          [place(a), a.zipCode].filter(Boolean).join(' ')
        }}</span>
      </address>
    </div>
    <dl v-else-if="section === 'references'" class="grid gap-2 text-sm">
      <div
        v-for="[name, value] in references"
        :key="name"
        class="flex flex-wrap items-baseline justify-between gap-x-3 border-b border-border/60 pb-1.5"
      >
        <dt class="text-muted-foreground">{{ name }}</dt>
        <dd class="min-w-0 break-all font-medium tabular-nums">{{ value }}</dd>
      </div>
    </dl>
    <p
      v-else-if="section === 'instructions'"
      class="whitespace-pre-line text-sm"
    >
      {{ shipment.specialInstructions }}
    </p>
    <ul v-else-if="section === 'documents'" class="grid gap-2 text-sm">
      <li v-for="doc in documents" :key="doc.url">
        <a
          :href="doc.url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 font-medium underline underline-offset-4"
          ><FileText class="size-4" />{{
            doc.name?.toUpperCase() || 'Shipment document'
          }}<ArrowUpRight class="size-3.5" /><span class="sr-only">
            (opens in a new tab)</span
          ></a
        >
      </li>
    </ul>
    <div v-else-if="section === 'map'" data-proof-map>
      <TrackingMap
        :key="JSON.stringify(coordinates)"
        :shipment-coordinates="coordinates"
      />
    </div>
  </section>
</template>
