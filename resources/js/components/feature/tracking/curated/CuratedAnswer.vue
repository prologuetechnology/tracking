<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import {
  ChevronRight,
  FileText,
  MapPin,
  Route,
  TriangleAlert,
} from 'lucide-vue-next'
import { computed } from 'vue'

import Footer from '@/components/layout/footer/Footer.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useTrackingDesign } from '@/composables/hooks/tracking/useTrackingDesign'

import AnswerHistory from './AnswerHistory.vue'
import TrackingBrandHeader from './TrackingBrandHeader.vue'
import TrackingModule from './TrackingModule.vue'
import TrackingSectionList from './TrackingSectionList.vue'

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
    class="flex min-h-dvh flex-col bg-background text-foreground"
  >
    <Head :title="[company.name, reference].filter(Boolean).join(' · ')" />
    <TrackingBrandHeader />
    <main class="mx-auto w-full max-w-6xl px-4 pb-10 pt-4 sm:px-8 sm:pt-6">
      <p
        class="mb-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
      >
        Shipment <ChevronRight class="size-3" aria-hidden="true" /><strong
          class="break-all font-mono font-medium text-foreground"
          >{{ reference || 'Tracking' }}</strong
        >
      </p>
      <section
        aria-labelledby="answer-status"
        class="overflow-hidden rounded-xl bg-brand text-brand-foreground"
        :class="{ 'grid md:grid-cols-[1.15fr,1fr]': company.banner }"
      >
        <div
          data-design-field="palette"
          class="min-w-0 px-5 py-6 sm:px-9 sm:py-8"
        >
          <p
            class="mb-3 flex items-center gap-2 font-mono text-[0.69rem] font-semibold uppercase tracking-[0.15em]"
          >
            <TriangleAlert
              v-if="latest?.exception"
              aria-hidden="true"
              class="size-4"
            />
            <span
              v-else
              aria-hidden="true"
              class="ring-current/10 size-2 rounded-full bg-current ring-4"
            ></span
            >Shipment status
          </p>
          <h1
            id="answer-status"
            :class="headlineClass"
            class="break-words text-[clamp(2.5rem,6vw,5.5rem)] font-bold leading-[1.02] tracking-[-0.055em] [overflow-wrap:anywhere]"
          >
            {{ status }}
          </h1>
          <p
            v-if="latest"
            class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1"
            :class="eventFirst ? 'text-lg font-semibold' : 'text-sm'"
          >
            <template v-if="latest.location"
              ><MapPin class="size-4 shrink-0" aria-hidden="true" /><span
                class="min-w-0 [overflow-wrap:anywhere]"
                >{{ latest.location }}</span
              ><span aria-hidden="true">·</span></template
            >
            <span class="sr-only">Latest carrier event</span
            >{{ formatEventTime(latest) }}
          </p>
          <dl
            class="border-current/25 mt-5 grid max-w-xl grid-cols-2 gap-x-3 border-t pt-3.5"
          >
            <div v-for="item in [pickup, delivery]" :key="item.label">
              <dt class="text-xs font-medium">{{ item.label }}</dt>
              <dd
                class="mt-0.5 font-semibold tabular-nums"
                :class="
                  item === delivery && !eventFirst ? 'text-lg' : 'text-base'
                "
              >
                {{ item.value }}
              </dd>
            </div>
          </dl>
        </div>
        <figure
          v-if="company.banner"
          data-design-field="banner"
          class="relative h-24 sm:h-32 md:h-auto"
        >
          <img
            :src="imageAssetUrl({ filePath: company.banner.file_path })"
            alt=""
            class="absolute inset-0 h-full w-full object-cover"
          />
          <figcaption
            v-if="itinerary"
            class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-gradient-to-t from-black/85 to-transparent px-4 pb-2 pt-7 text-xs font-medium text-white"
          >
            <Route class="size-3.5" aria-hidden="true" />{{ itinerary }}
          </figcaption>
        </figure>
      </section>
      <section
        v-if="modules.length"
        aria-label="Shipment information"
        class="mt-3 flex flex-col items-start gap-3 rounded-xl border bg-card px-4 py-3.5 text-card-foreground sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-6"
      >
        <TrackingModule
          :section="modules[0]"
          class="w-full sm:flex-1"
          featured
          brief
        />
        <Dialog>
          <DialogTrigger as-child>
            <Button variant="outline" size="sm" class="shrink-0">
              <FileText class="mr-2 size-4" aria-hidden="true" />
              <span>Shipment details</span>
            </Button>
          </DialogTrigger>
          <DialogContent
            class="max-h-[85dvh] max-w-[min(36rem,calc(100vw-2rem))] overflow-y-auto rounded-xl"
          >
            <DialogHeader>
              <DialogTitle>Shipment details</DialogTitle>
              <DialogDescription
                class="break-all font-mono"
                :class="{ 'sr-only': !reference }"
              >
                {{ reference || 'Shipment information' }}
              </DialogDescription>
            </DialogHeader>
            <TrackingModule :section="modules[0]" featured />
            <TrackingSectionList
              v-if="modules.length > 1"
              :sections="modules.slice(1)"
              class="border-t"
            />
          </DialogContent>
        </Dialog>
      </section>
      <AnswerHistory
        :events="events"
        :format-event-time="formatEventTime"
        :heading-class="headlineClass"
      />
    </main>
    <Footer />
  </div>
</template>
