<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import {
  ChevronDown,
  CircleCheck,
  FileText,
  ScanBarcode,
  TriangleAlert,
  Truck,
} from 'lucide-vue-next'
import { computed } from 'vue'

import TrackingModuleV1 from '@/components/feature/tracking/curated/legacy/TrackingModuleV1.vue'
import TrackingBrandHeader from '@/components/feature/tracking/curated/TrackingBrandHeader.vue'
import TrackingModule from '@/components/feature/tracking/curated/TrackingModule.vue'
import TrackingSectionList from '@/components/feature/tracking/curated/TrackingSectionList.vue'
import ScanRegister from '@/components/feature/tracking/record/ScanRegister.vue'
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
  pro: carrierPro,
  formatEventTime,
} = useTrackingDesign()
const statusIcon = computed(() => {
  if (latest.value?.exception) return TriangleAlert
  if (/^delivered\b/i.test(status.value)) return CircleCheck
  return Truck
})
const banner = computed(
  () =>
    company.value.banner?.file_path &&
    imageAssetUrl({ filePath: company.value.banner.file_path }),
)
</script>

<template>
  <div
    class="flex min-h-dvh flex-col border-t-4 border-primary bg-muted/40 text-foreground"
    data-tracking-layout="record"
  >
    <Head
      :title="
        [company.name, 'Shipment record', reference].filter(Boolean).join(' · ')
      "
    />
    <TrackingBrandHeader />
    <main
      class="mx-auto w-full max-w-6xl px-4 pb-12 pt-7 sm:px-8 lg:pb-16 lg:pt-9"
    >
      <div
        class="flex items-center gap-2 font-mono text-xs font-semibold uppercase tracking-widest text-muted-foreground"
      >
        <ScanBarcode aria-hidden="true" class="size-5" />Carrier tracking record
      </div>
      <div
        class="my-4 flex flex-wrap items-center gap-x-4 gap-y-2"
        data-design-field="palette"
      >
        <h1
          class="text-3xl font-semibold tracking-tight sm:text-5xl"
          :class="headlineClass"
        >
          Shipment record
        </h1>
        <span
          class="inline-flex max-w-full items-center gap-2 rounded-sm border px-2.5 py-1 text-sm font-semibold"
          :class="
            latest?.exception
              ? 'border-amber-300 bg-amber-50 text-amber-950'
              : refined && statusIcon === CircleCheck
                ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                : 'border-primary/25 bg-card text-card-foreground'
          "
        >
          <component
            :is="statusIcon"
            aria-hidden="true"
            class="size-4 shrink-0"
          /><span class="min-w-0 [overflow-wrap:anywhere]">{{ status }}</span>
        </span>
      </div>

      <div
        class="flex overflow-hidden rounded-md border bg-card text-card-foreground"
      >
        <dl
          class="grid min-w-0 flex-1 grid-cols-2 divide-x sm:grid-cols-[minmax(0,1.3fr),repeat(2,minmax(0,1fr))]"
        >
          <div
            class="col-span-2 min-w-0 border-b px-4 py-3 sm:col-span-1 sm:border-b-0 sm:px-5 sm:py-4"
            :class="{ 'relative pr-20 sm:pr-5': refined && banner }"
          >
            <dt class="record-caption">
              {{
                carrierPro
                  ? 'Carrier PRO'
                  : shipment.bolNum
                    ? 'Bill of lading'
                    : 'Tracking number'
              }}
            </dt>
            <dd
              class="mt-1 break-all font-mono text-sm font-semibold sm:text-base"
            >
              <img
                v-if="refined && banner"
                :src="banner"
                data-design-field="banner"
                alt=""
                class="absolute inset-y-0 right-0 h-full w-16 rounded-tr object-cover sm:hidden"
              />
              {{ carrierPro || shipment.bolNum || 'Unavailable' }}
            </dd>
          </div>
          <div
            v-for="item in [pickup, delivery]"
            :key="item.label"
            class="min-w-0 px-4 py-3 sm:px-5 sm:py-4"
          >
            <dt class="record-caption">{{ item.label }}</dt>
            <dd class="mt-1 break-words text-sm font-semibold sm:text-base">
              {{ item.value }}
            </dd>
          </div>
        </dl>
        <img
          v-if="banner"
          data-design-field="banner"
          :src="banner"
          alt=""
          class="hidden max-h-24 w-32 object-cover"
          :class="refined ? 'sm:block' : 'lg:block'"
        />
      </div>

      <div
        class="mt-5 grid items-start gap-5"
        :class="{
          'md:grid-cols-[minmax(14rem,0.8fr),minmax(0,2.1fr)]': modules.length,
        }"
      >
        <aside
          v-if="modules.length"
          :class="{ 'record-file': refined }"
          aria-labelledby="shipment-file"
          class="min-w-0 rounded-md border bg-card text-card-foreground"
        >
          <div class="flex items-center gap-2 border-b bg-muted/40 px-4 py-3">
            <FileText aria-hidden="true" class="size-4 text-primary" />
            <h2 id="shipment-file" class="font-semibold">Shipment file</h2>
          </div>
          <div class="p-4">
            <component
              :is="refined ? TrackingModule : TrackingModuleV1"
              :section="modules[0]"
              featured
            />
          </div>
          <details v-if="modules.length > 1" class="group border-t">
            <summary
              class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-sm px-4 py-3 text-sm font-semibold hover:bg-muted/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring [&::-webkit-details-marker]:hidden"
            >
              Other shipment details
              <ChevronDown
                aria-hidden="true"
                class="size-4 shrink-0 group-open:rotate-180"
              />
            </summary>
            <TrackingSectionList
              v-if="refined"
              :sections="modules.slice(1)"
              class="border-t px-4"
            />
            <div v-else class="divide-y border-t">
              <div
                v-for="section in modules.slice(1)"
                :key="section"
                class="min-w-0 p-4"
              >
                <TrackingModuleV1 :section="section" />
              </div>
            </div>
          </details>
        </aside>

        <ScanRegister
          :heading-class="refined ? headlineClass : ''"
          :events="events"
          :carrier="shipment.carrierName"
          :format-event-time="formatEventTime"
        />
      </div>
    </main>
    <Footer />
  </div>
</template>

<style scoped>
.record-caption {
  @apply font-mono text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground;
}

.record-file :deep(.module-references) {
  @apply grid-cols-2 md:grid-cols-1;
}
</style>
