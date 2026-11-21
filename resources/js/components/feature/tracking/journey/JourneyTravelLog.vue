<script setup>
import dayjs from 'dayjs'
import {
  ArrowDown,
  CircleDot,
  PackageCheck,
  TriangleAlert,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps({
  events: { type: Array, required: true },
  headingClass: { type: String, required: true },
})

const root = ref(null)
const days = computed(() => {
  const groups = []
  props.events.forEach((event, index) => {
    const stamp = event.at ? dayjs(event.at) : null
    const date = stamp?.isValid() ? stamp : null
    const key = date?.format(`YYYY-MM-DD`) ?? `unknown`
    if (groups.at(-1)?.key !== key) groups.push({ key, date, scans: [] })
    groups.at(-1).scans.push({ event, index, date })
  })
  return groups
})
const earlierException = computed(() => {
  if (props.events[0]?.exception) return -1
  return props.events.findIndex((event, index) => index > 0 && event.exception)
})

const jumpToException = () => {
  const item = root.value?.querySelector(
    `[data-scan-index="` + earlierException.value + `"]`,
  )
  if (item) {
    item.focus({ preventScroll: true })
    item.scrollIntoView({ block: `center` })
  }
}

const scanIcon = (event) => {
  if (event.exception) return TriangleAlert
  if (/^delivered$/i.test(event.status)) return PackageCheck
  return CircleDot
}
</script>

<template>
  <section ref="root" class="min-w-0" aria-labelledby="travel-log-title">
    <div
      class="flex flex-wrap items-end justify-between gap-2 border-b border-border pb-4"
    >
      <div>
        <p
          class="mb-1 font-mono text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground"
        >
          Carrier activity
        </p>
        <h2
          id="travel-log-title"
          class="text-4xl font-normal leading-tight tracking-[-0.045em] sm:text-5xl"
          :class="headingClass"
        >
          Travel log
        </h2>
      </div>
      <span class="font-mono text-xs text-muted-foreground"
        >{{ events.length }}
        {{ events.length === 1 ? 'event' : 'events' }}</span
      >
    </div>
    <button
      v-if="earlierException >= 0"
      type="button"
      class="mt-4 inline-flex min-h-10 items-center gap-2 rounded border border-border bg-card px-3 py-2 text-sm font-semibold underline-offset-4 hover:underline focus-visible:outline-primary"
      @click="jumpToException"
    >
      <TriangleAlert class="size-4" aria-hidden="true" /> Earlier exception
      <ArrowDown class="size-3.5" aria-hidden="true" />
    </button>

    <p v-if="!days.length" class="py-6 text-sm text-muted-foreground">
      The carrier has not reported a tracking event yet.
    </p>
    <section
      v-for="day in days"
      :key="day.key"
      class="grid grid-cols-[2.65rem,minmax(0,1fr)] gap-2.5 pt-5 sm:grid-cols-[3.8rem,minmax(0,1fr)] sm:gap-4 sm:pt-7"
    >
      <h3 class="min-w-0 text-center">
        <time v-if="day.date" :datetime="day.key">
          <span class="sr-only">{{
            day.date.format('dddd, MMMM D, YYYY')
          }}</span>
          <span aria-hidden="true" class="flex flex-col">
            <span
              class="font-mono text-[0.65rem] uppercase tracking-wide text-muted-foreground"
              >{{ day.date.format('ddd') }}</span
            >
            <span
              class="text-3xl leading-tight tracking-tight sm:text-4xl"
              :class="headingClass"
              >{{ day.date.format('D') }}</span
            >
            <span
              class="font-mono text-[0.65rem] uppercase leading-tight text-muted-foreground"
              >{{ day.date.format('MMM YYYY') }}</span
            >
          </span>
        </time>
        <span
          v-else
          class="block max-w-full text-xs font-medium text-muted-foreground"
          >Date pending</span
        >
      </h3>

      <ol class="min-w-0 border-l border-border pl-3 sm:pl-5">
        <li
          v-for="scan in day.scans"
          :key="scan.index"
          :data-scan-index="scan.index"
          :tabindex="scan.event.exception ? -1 : undefined"
          class="relative min-w-0 break-words focus-visible:outline-primary"
        >
          <span
            aria-hidden="true"
            class="absolute -left-[1.035rem] top-4 size-2 rounded-full border sm:-left-[1.53rem]"
            :class="
              scan.index === 0 || scan.event.exception
                ? 'border-primary bg-primary'
                : 'border-muted-foreground bg-background'
            "
          />
          <div
            class="flex items-start gap-2.5 py-2.5 sm:gap-3"
            :class="
              scan.event.exception
                ? 'mb-1 rounded-md border border-destructive/35 bg-destructive/5 px-3'
                : scan.index === 0
                  ? 'mb-1 rounded-md border border-primary/25 bg-primary/5 px-3'
                  : 'border-b border-border'
            "
          >
            <span
              v-if="scan.index === 0 || scan.event.exception"
              class="mt-1 grid size-8 shrink-0 place-items-center rounded-full sm:size-10"
              :class="
                scan.event.exception
                  ? 'bg-destructive text-destructive-foreground'
                  : 'bg-primary text-primary-foreground'
              "
            >
              <component
                :is="scanIcon(scan.event)"
                class="size-[1.1rem]"
                aria-hidden="true"
              />
            </span>
            <div class="min-w-0">
              <p
                v-if="scan.index === 0 || scan.event.exception"
                class="mb-1 font-mono text-[0.625rem] font-semibold uppercase tracking-[0.1em] text-muted-foreground"
              >
                {{ scan.index === 0 ? 'Latest scan' : 'Earlier exception' }}
              </p>
              <p
                :class="
                  scan.index === 0
                    ? [
                        headingClass,
                        'text-2xl leading-tight tracking-tight sm:text-3xl',
                      ]
                    : 'text-sm font-semibold'
                "
              >
                {{ scan.event.status || 'Carrier update' }}
              </p>
              <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                <time
                  v-if="scan.date"
                  :datetime="scan.date.toISOString()"
                  class="tabular-nums"
                  >{{ scan.event.approximate ? 'Approx. ' : ''
                  }}{{ scan.date.format('h:mm A') }}</time
                >
                <span v-else>Time unavailable</span>
                <span v-if="scan.event.location">
                  · {{ scan.event.location }}</span
                >
              </p>
            </div>
          </div>
        </li>
      </ol>
    </section>
  </section>
</template>
