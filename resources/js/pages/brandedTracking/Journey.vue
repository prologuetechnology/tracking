<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import {
  ArrowRight,
  ChevronDown,
  CircleCheck,
  FileText,
  Route,
  TriangleAlert,
  Truck,
} from 'lucide-vue-next'
import { computed } from 'vue'

import TrackingModuleV1 from '@/components/feature/tracking/curated/legacy/TrackingModuleV1.vue'
import TrackingBrandHeader from '@/components/feature/tracking/curated/TrackingBrandHeader.vue'
import TrackingModule from '@/components/feature/tracking/curated/TrackingModule.vue'
import TrackingSectionList from '@/components/feature/tracking/curated/TrackingSectionList.vue'
import JourneyTravelLog from '@/components/feature/tracking/journey/JourneyTravelLog.vue'
import Footer from '@/components/layout/footer/Footer.vue'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

const page = usePage()
const refined = computed(() => page.props.presentationVersion === 2)
const {
  company,
  shipment,
  events,
  latest,
  status,
  pickup,
  delivery,
  modules,
  headlineClass,
  reference,
} = useTrackingDesign()

const cityName = (location) =>
  location?.city || location?.name || location?.state || ``
const origin = computed(() => cityName(shipment.value.originLocation))
const destination = computed(() => cityName(shipment.value.destinationLocation))
const originState = computed(() => shipment.value.originLocation?.state)
const destinationState = computed(
  () => shipment.value.destinationLocation?.state,
)
const titleClass = computed(() => headlineClass.value || `font-serif`)
const statusIcon = computed(() => {
  if (latest.value?.exception) return TriangleAlert
  if (
    refined.value
      ? /^delivered\b/i.test(status.value)
      : shipment.value.actualDeliveryDate || /^delivered$/i.test(status.value)
  )
    return CircleCheck
  return Truck
})
</script>

<template>
  <div
    class="journey-page flex min-h-dvh flex-col bg-background text-foreground"
    data-tracking-layout="journey"
  >
    <Head
      :title="
        [company.name, reference, 'Shipment journey']
          .filter(Boolean)
          .join(' · ')
      "
    />
    <TrackingBrandHeader />
    <main
      class="mx-auto grid w-full max-w-7xl flex-1 content-start gap-8 px-5 py-7 sm:px-9 sm:py-10 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)] lg:gap-16 lg:px-14 lg:py-12"
    >
      <section
        class="min-w-0"
        aria-labelledby="journey-title"
        data-design-field="palette"
      >
        <div
          class="flex flex-wrap items-center gap-2 text-sm font-semibold text-primary"
        >
          <Route class="size-4 shrink-0" aria-hidden="true" />
          <template v-if="originState && destinationState">
            <span>{{ originState }}</span
            ><ArrowRight class="size-3.5" aria-hidden="true" /><span
              class="sr-only"
              >to</span
            ><span>{{ destinationState }}</span>
          </template>
          <span v-else>Shipment route</span>
        </div>
        <h1
          id="journey-title"
          class="my-4 text-[clamp(2.5rem,8vw,4.9rem)] font-normal leading-[1.02] tracking-[-0.055em] [overflow-wrap:anywhere] lg:my-6"
          :class="titleClass"
        >
          <template v-if="origin && destination"
            >{{ origin }}<br class="hidden lg:block" /><span
              class="text-[0.73em] italic text-muted-foreground"
            >
              to </span
            >{{ destination }}</template
          >
          <template v-else-if="destination">To {{ destination }}</template>
          <template v-else-if="origin">From {{ origin }}</template>
          <template v-else>Shipment journey</template>
        </h1>
        <div
          class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm"
          :class="{
            'w-fit rounded border border-amber-300 bg-amber-50 px-2 py-1 text-amber-950':
              refined && latest?.exception,
          }"
        >
          <component
            :is="statusIcon"
            class="size-5 shrink-0"
            :class="{ 'text-primary': !refined || !latest?.exception }"
            aria-hidden="true"
          />
          <strong
            class="min-w-0 break-words text-base"
            :class="{ 'text-primary': !refined || !latest?.exception }"
            >{{ status }}</strong
          >
          <span
            v-if="latest?.location"
            class="min-w-0 break-words text-muted-foreground"
            >· {{ latest.location }}</span
          >
        </div>
        <dl class="grid grid-cols-2 gap-4 border-y border-border py-4">
          <div v-for="date in [pickup, delivery]" :key="date.label">
            <dt class="text-xs text-muted-foreground">{{ date.label }}</dt>
            <dd class="mt-1 text-base font-semibold">{{ date.value }}</dd>
          </div>
        </dl>

        <div
          v-if="company.banner || modules.length"
          class="mt-5 overflow-hidden rounded-md border border-border bg-card text-card-foreground"
        >
          <img
            v-if="company.banner"
            data-design-field="banner"
            :src="imageAssetUrl({ filePath: company.banner.file_path })"
            alt=""
            class="h-16 w-full object-cover sm:h-36"
          />
          <div v-if="modules.length" class="min-w-0 p-4 sm:p-5">
            <component
              :is="refined ? TrackingModule : TrackingModuleV1"
              :section="modules[0]"
              featured
            />
          </div>
        </div>

        <details
          v-if="modules.length > 1"
          class="group mt-4 border-y border-border"
        >
          <summary
            class="flex cursor-pointer list-none items-center gap-2 py-3 text-sm font-semibold focus-visible:outline-primary [&::-webkit-details-marker]:hidden"
          >
            <FileText class="size-4" aria-hidden="true" />
            Shipment details
            <ChevronDown
              class="ml-auto size-4 transition-transform group-open:rotate-180 motion-reduce:transition-none"
              aria-hidden="true"
            />
          </summary>
          <TrackingSectionList
            v-if="refined"
            :sections="modules.slice(1)"
            class="border-t"
          />
          <div v-else class="divide-y divide-border border-t border-border">
            <div
              v-for="section in modules.slice(1)"
              :key="section"
              class="min-w-0 py-4"
            >
              <TrackingModuleV1 :section="section" />
            </div>
          </div>
        </details>
      </section>

      <JourneyTravelLog :events="events" :heading-class="titleClass" />
    </main>
    <Footer />
  </div>
</template>

<style scoped>
.journey-page {
  background-color: color-mix(
    in srgb,
    hsl(var(--background)) 96%,
    hsl(var(--primary))
  );
}
</style>
