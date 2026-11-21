<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { Check, CircleDot, MapPin, TriangleAlert } from 'lucide-vue-next'
import { computed } from 'vue'

import Footer from '@/components/layout/footer/Footer.vue'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

import TrackingBrandHeader from './TrackingBrandHeader.vue'
import TrackingModule from './TrackingModule.vue'

const page = usePage()
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
  formatEventTime,
  place,
} = useTrackingDesign()
const eventFirst = computed(() => page.props.emphasis === `event`)
const highlights = computed(() => {
  const scan = {
    label: `Latest carrier event`,
    value: latest.value ? formatEventTime(latest.value) : `Not available`,
    location: latest.value?.location,
  }
  return eventFirst.value
    ? [scan, delivery.value, pickup.value]
    : [delivery.value, pickup.value, scan]
})
const itinerary = computed(() =>
  [
    place(shipment.value.originLocation),
    place(shipment.value.destinationLocation),
  ]
    .filter(Boolean)
    .join(` → `),
)
</script>
<template>
  <div
    data-tracking-layout="answer"
    class="min-h-screen bg-background text-foreground"
  >
    <Head :title="[company.name, reference].filter(Boolean).join(' · ')" />
    <TrackingBrandHeader />
    <main class="mx-auto w-full max-w-6xl px-4 pb-12 pt-5 sm:px-8 sm:pt-8">
      <p
        class="mb-4 flex flex-wrap items-baseline gap-2 text-xs text-muted-foreground"
      >
        Shipment <span aria-hidden="true">/</span
        ><strong class="break-all font-mono text-foreground">{{
          reference || 'Tracking'
        }}</strong>
      </p>
      <section
        aria-labelledby="answer-status"
        class="overflow-hidden rounded-xl border bg-card text-card-foreground"
        :class="company.banner ? 'grid md:grid-cols-[1.15fr,1fr]' : ''"
      >
        <div class="min-w-0 p-6 sm:p-9">
          <p
            class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground"
          >
            <span class="size-2 rounded-full bg-primary"></span>Shipment status
          </p>
          <h1
            id="answer-status"
            :class="headlineClass"
            class="break-words text-4xl font-bold leading-tight tracking-tight sm:text-6xl"
          >
            {{ status }}
          </h1>
          <p
            v-if="latest?.exception"
            class="mt-3 inline-flex items-center gap-2 rounded border border-amber-700/30 bg-amber-50 px-2 py-1 text-sm font-medium text-amber-950"
          >
            <TriangleAlert class="size-4" />Carrier reported an exception
          </p>
          <dl class="mt-6 grid gap-4 border-t pt-4 sm:grid-cols-2">
            <div
              v-for="(item, index) in highlights"
              :key="item.label"
              :class="index === 2 ? 'sm:col-span-2' : ''"
            >
              <dt class="text-xs text-muted-foreground">{{ item.label }}</dt>
              <dd class="font-semibold" :class="index === 0 ? 'text-xl' : ''">
                {{ item.value }}
              </dd>
              <dd
                v-if="item.location"
                class="flex items-center gap-1 text-xs text-muted-foreground"
              >
                <MapPin class="size-3" />{{ item.location }}
              </dd>
            </div>
          </dl>
        </div>
        <figure v-if="company.banner" class="relative min-h-0">
          <img
            :src="imageAssetUrl({ filePath: company.banner.file_path })"
            alt=""
            class="h-40 w-full object-cover md:absolute md:inset-0 md:h-full"
          />
          <figcaption
            v-if="itinerary"
            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-5 pb-3 pt-8 text-sm text-white"
          >
            {{ itinerary }}
          </figcaption>
        </figure>
      </section>
      <div
        v-if="modules.length"
        class="my-5 grid gap-4 rounded-xl border bg-card p-5 sm:p-6"
      >
        <TrackingModule :section="modules[0]" featured />
        <details v-if="modules.length > 1" class="border-t pt-3">
          <summary class="cursor-pointer text-sm font-semibold text-primary">
            <span class="ml-1">More shipment details</span>
          </summary>
          <div class="mt-5 grid gap-6 sm:grid-cols-2">
            <TrackingModule
              v-for="section in modules.slice(1)"
              :key="section"
              :section="section"
            />
          </div>
        </details>
      </div>
      <section class="mt-7" aria-labelledby="answer-history">
        <header
          class="mb-3 flex flex-wrap items-baseline justify-between gap-2"
        >
          <h2 id="answer-history" class="text-2xl font-semibold tracking-tight">
            Carrier updates
          </h2>
          <span
            v-if="shipment.carrierName"
            class="text-sm text-muted-foreground"
            >{{ shipment.carrierName }}</span
          >
        </header>
        <ol
          v-if="events.length"
          class="divide-y rounded-xl border bg-card px-4 sm:px-6"
        >
          <li
            v-for="(event, index) in events"
            :key="event.id"
            class="grid grid-cols-[auto,1fr] items-start gap-x-3 py-4 sm:grid-cols-[auto,1fr,12rem,15rem]"
          >
            <component
              :is="
                event.exception
                  ? TriangleAlert
                  : index === 0
                    ? Check
                    : CircleDot
              "
              aria-hidden="true"
              class="mt-1 size-4"
              :class="event.exception ? 'text-amber-700' : 'text-primary'"
            />
            <strong class="break-words text-sm">{{ event.status }}</strong
            ><span
              class="col-start-2 text-sm text-muted-foreground sm:col-start-auto"
              >{{ event.location }}</span
            ><span
              class="col-start-2 text-sm text-muted-foreground sm:col-start-auto"
              >{{ formatEventTime(event) }}</span
            >
          </li>
        </ol>
        <p v-else class="border-y py-5 text-sm">
          The carrier has not reported a tracking event yet.
        </p>
      </section>
      <img
        v-if="company.footer"
        :src="imageAssetUrl({ filePath: company.footer.file_path })"
        alt=""
        class="mx-auto mt-8 max-w-full rounded"
      />
    </main>
    <Footer :sticky="false" />
  </div>
</template>
