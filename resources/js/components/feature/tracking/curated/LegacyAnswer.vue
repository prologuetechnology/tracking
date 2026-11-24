<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import {
  ArrowDown,
  ArrowRight,
  Check,
  Copy,
  MapPin,
  RefreshCw,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import ShipmentDetailsAndTracking from '@/components/feature/tracking/ShipmentDetailsAndTracking.vue'
import Footer from '@/components/layout/footer/Footer.vue'
import { Button } from '@/components/ui/button'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompanyTheme } from '@/composables/hooks/theme'

const page = usePage()
useCompanyTheme()
const company = computed(() => page.props.initialCompany)
const shipment = computed(() => page.props.initialTrackingData)
const events = computed(() =>
  [...(shipment.value.allStatuses ?? [])]
    .reverse()
    .sort(
      (a, b) =>
        (Date.parse(b.status_date_time ?? b.timestamp) || 0) -
        (Date.parse(a.status_date_time ?? a.timestamp) || 0),
    ),
)
const latest = computed(() => events.value[0])
const status = computed(
  () =>
    latest.value?.current_status ||
    (shipment.value.actualDeliveryDate
      ? `Delivered`
      : `Awaiting carrier update`),
)
const delivery = computed(
  () =>
    shipment.value.actualDeliveryDate ?? shipment.value.estimatedDeliveryDate,
)
const deliveryLabel = computed(() =>
  shipment.value.actualDeliveryDate ? `Delivered on` : `Estimated delivery`,
)
const format = (date, pattern) =>
  date && dayjs(date).isValid() ? dayjs(date).format(pattern) : null
const eventDate = (event) =>
  format(event?.status_date_time ?? event?.timestamp, `MMM D, YYYY · h:mm A`)
const place = (location) =>
  [location?.city, location?.state].filter(Boolean).join(`, `)
const highlights = computed(() => {
  const timing = {
    label: deliveryLabel.value,
    value: format(delivery.value, `MMM D, YYYY`) ?? `Date pending`,
  }
  const event = {
    label: `Latest carrier event`,
    value: eventDate(latest.value) ?? `Time unavailable`,
    location: latest.value?.current_location,
  }
  return page.props.emphasis === `event` ? [event, timing] : [timing, event]
})
const reference = computed(
  () => shipment.value.carrierPro || shipment.value.bolNum,
)
const copied = ref(false)
const copy = async () => {
  try {
    await navigator.clipboard.writeText(location.href)
    copied.value = true
  } catch {
    window.prompt(`Copy tracking link`, location.href)
  }
}
const refresh = () => router.reload({ preserveScroll: true })
</script>

<template>
  <Head :title="[company.name, reference].filter(Boolean).join(' · ')" />
  <div class="flex min-h-dvh flex-col bg-background text-foreground">
    <header class="border-b border-border">
      <div
        class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-10"
      >
        <img
          v-if="company.logo"
          class="max-h-12 max-w-36 object-contain"
          :src="imageAssetUrl({ filePath: company.logo.file_path })"
          :alt="company.name"
        />
        <span v-else class="text-xl font-bold tracking-tight">{{
          company.name
        }}</span>
        <div class="flex gap-2">
          <Button
            type="button"
            aria-label="Refresh shipment"
            size="sm"
            variant="ghost"
            @click="refresh"
            ><RefreshCw class="mr-2 size-4" />Refresh</Button
          >
          <Button
            v-if="!page.props.isDraftPreview"
            size="sm"
            variant="outline"
            @click="copy"
            ><component :is="copied ? Check : Copy" class="mr-2 size-4" />{{
              copied ? 'Copied' : 'Copy link'
            }}</Button
          >
        </div>
      </div>
    </header>
    <main class="mx-auto w-full max-w-6xl px-5 pb-12 pt-6 sm:px-10">
      <p
        class="mb-5 flex flex-wrap items-baseline gap-2 text-sm text-muted-foreground"
      >
        Shipment <span aria-hidden="true">/</span>
        <strong class="break-all font-mono text-foreground">{{
          reference
        }}</strong>
      </p>
      <section
        class="overflow-hidden rounded-2xl border bg-card"
        :class="company.banner ? 'grid md:grid-cols-[1.15fr,1fr]' : ''"
      >
        <div class="min-w-0 px-6 py-9 sm:px-10 sm:py-12">
          <p
            class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground"
          >
            <span class="size-2 rounded-full bg-primary"></span>Shipment status
          </p>
          <h1
            class="break-words text-4xl font-bold leading-tight tracking-tight sm:text-6xl"
          >
            {{ status }}
          </h1>
          <div class="mt-7 grid gap-4 border-t pt-5 sm:grid-cols-2">
            <div v-for="(item, index) in highlights" :key="item.label">
              <p class="text-sm text-muted-foreground">{{ item.label }}</p>
              <p
                class="mt-1 font-semibold"
                :class="index === 0 ? 'text-xl tracking-tight' : ''"
              >
                {{ item.value }}
              </p>
              <p
                v-if="item.location"
                class="mt-1 flex items-center gap-1 text-sm"
              >
                <MapPin class="size-3.5" />{{ item.location }}
              </p>
            </div>
          </div>
        </div>
        <img
          v-if="company.banner"
          :src="imageAssetUrl({ filePath: company.banner.file_path })"
          alt=""
          class="h-44 w-full object-cover md:h-full md:max-h-[28rem]"
        />
      </section>
      <section
        class="my-6 grid gap-4 border-y py-5 sm:flex sm:items-center sm:gap-10"
        aria-label="Shipment route"
      >
        <div>
          <span
            class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
            >Origin</span
          >
          <p class="font-semibold">
            {{ place(shipment.originLocation) || 'Location pending' }}
          </p>
        </div>
        <ArrowRight
          class="hidden size-5 text-primary sm:block"
          aria-hidden="true"
        />
        <div>
          <span
            class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
            >Destination</span
          >
          <p class="font-semibold">
            {{ place(shipment.destinationLocation) || 'Location pending' }}
          </p>
        </div>
        <a
          href="#shipment-details"
          class="flex items-center gap-2 text-sm font-semibold underline underline-offset-4 sm:ml-auto"
          >Shipment details <ArrowDown class="size-4"
        /></a>
      </section>
      <section class="my-10" aria-labelledby="carrier-updates">
        <h2
          id="carrier-updates"
          class="mb-4 text-2xl font-semibold tracking-tight"
        >
          Carrier updates
        </h2>
        <ol class="divide-y border-y">
          <li
            v-for="(event, index) in events"
            :key="event.id ?? index"
            class="grid gap-1 py-4 sm:grid-cols-[1fr,12rem,15rem]"
          >
            <strong>{{ event.current_status }}</strong
            ><span class="text-sm">{{ event.current_location }}</span
            ><span class="text-sm text-muted-foreground">{{
              eventDate(event)
            }}</span>
          </li>
        </ol>
        <p v-if="!events.length" class="py-4 text-sm">
          The carrier has not reported a tracking event yet.
        </p>
      </section>
      <div id="shipment-details">
        <ShipmentDetailsAndTracking
          :tracking-data="shipment"
          :company="company"
          :shipment-documents="page.props.initialShipmentDocuments"
          :shipment-coordinates="page.props.initialShipmentCoordinates"
          :show-history="false"
        />
      </div>
    </main>
    <Footer />
  </div>
</template>
