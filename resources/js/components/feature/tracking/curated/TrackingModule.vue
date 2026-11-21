<script setup>
import {
  ArrowUpRight,
  ClipboardList,
  FileText,
  MapPin,
  Package,
  Route,
} from 'lucide-vue-next'

import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

import TrackingRouteSummary from './TrackingRouteSummary.vue'

defineProps({
  section: { type: String, required: true },
  featured: { type: Boolean, default: false },
  brief: { type: Boolean, default: false },
})
const { moduleNames, shipment, references, addresses, documents, place } =
  useTrackingDesign()
const documentLabels = { bol: `Bill of lading`, pod: `Proof of delivery` }
const icons = {
  receiving: MapPin,
  references: ClipboardList,
  instructions: Package,
  map: Route,
  documents: FileText,
}
</script>
<template>
  <section
    :data-section="section"
    :data-featured="featured || undefined"
    class="min-w-0 break-words"
  >
    <h3
      v-if="featured"
      class="mb-2 flex items-center gap-2 text-sm font-semibold text-primary"
    >
      <component :is="icons[section]" class="size-4 shrink-0 text-primary" />{{
        moduleNames[section]
      }}
    </h3>
    <div
      v-if="section === 'receiving'"
      class="module-address grid gap-3"
      :class="{ 'brief-address sm:grid-cols-2': brief }"
    >
      <address
        v-for="[label, a] in addresses"
        :key="label"
        class="min-w-0 text-sm not-italic leading-snug"
      >
        <span
          v-if="label === 'Origin' || !brief"
          class="block text-xs text-muted-foreground"
          >{{ label === 'Destination' ? 'Deliver to' : 'Shipped from' }}</span
        >
        <strong v-if="a.name" class="block">{{ a.name }}</strong>
        <span class="block text-muted-foreground">{{
          [
            a.address,
            a.address2,
            [place(a), a.zipCode].filter(Boolean).join(' '),
          ]
            .filter(Boolean)
            .join(' · ')
        }}</span>
      </address>
    </div>
    <dl
      v-else-if="section === 'references'"
      class="module-references grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2"
    >
      <div v-for="[name, value] in references" :key="name" class="min-w-0">
        <dt class="text-xs text-muted-foreground">{{ name }}</dt>
        <dd
          class="min-w-0 break-all font-mono text-xs font-medium tabular-nums"
        >
          {{ value }}
        </dd>
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
            documentLabels[doc.name?.toLowerCase()] ||
            doc.name ||
            'Shipment document'
          }}<ArrowUpRight class="size-3.5" /><span class="sr-only">
            (opens in a new tab)</span
          ></a
        >
      </li>
    </ul>
    <TrackingRouteSummary v-else-if="section === 'map'" />
  </section>
</template>

<style scoped>
@media (max-width: 639px) {
  .brief-address address + address {
    display: none;
  }
}
</style>
