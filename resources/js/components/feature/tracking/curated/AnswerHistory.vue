<script setup>
import dayjs from 'dayjs'
import { Check, CircleDot, MapPin, TriangleAlert } from 'lucide-vue-next'

defineProps({
  events: { type: Array, required: true },
  formatEventTime: { type: Function, required: true },
  headingClass: { type: String, default: `` },
})
const scanIcon = (event) =>
  event.exception
    ? TriangleAlert
    : /^delivered\b/i.test(event.status)
      ? Check
      : CircleDot
</script>
<template>
  <section class="mx-auto mt-6 max-w-4xl" aria-labelledby="answer-history">
    <header class="mb-3 flex items-baseline justify-between gap-2">
      <h2
        id="answer-history"
        :class="headingClass"
        class="text-xl font-semibold tracking-tight sm:text-2xl"
      >
        Tracking history
      </h2>
      <span class="font-mono text-xs text-muted-foreground"
        >{{ events.length }}
        {{ events.length === 1 ? 'event' : 'events' }}</span
      >
    </header>
    <ol v-if="events.length" class="answer-scans">
      <li
        v-for="(event, index) in events"
        :key="event.id"
        :data-scan-index="index"
        class="grid min-w-0 grid-cols-[1.5rem,minmax(0,1fr),minmax(4.8rem,auto)] items-center gap-x-2 px-2 py-3 sm:px-4"
        :class="
          event.exception
            ? 'rounded-lg bg-amber-50 text-amber-950'
            : index === 0
              ? 'rounded-lg bg-muted'
              : ''
        "
      >
        <span
          class="relative z-[1] grid size-5 place-items-center rounded-full border bg-background text-primary"
          :class="
            event.exception
              ? 'border-amber-700 bg-amber-100 text-amber-900'
              : index === 0
                ? 'border-primary bg-primary text-primary-foreground'
                : 'border-border'
          "
        >
          <component
            :is="scanIcon(event)"
            class="size-3.5"
            aria-hidden="true"
          />
        </span>
        <div class="min-w-0">
          <span
            v-if="index === 0 || event.exception"
            class="mb-0.5 block font-mono text-[0.625rem] font-semibold uppercase tracking-[0.13em]"
            >{{
              index === 0
                ? event.exception
                  ? 'Current exception'
                  : 'Latest scan'
                : 'Earlier exception'
            }}</span
          >
          <strong
            class="block text-sm leading-snug [overflow-wrap:anywhere] sm:text-base"
            >{{ event.status }}</strong
          >
          <span
            v-if="event.location"
            class="mt-0.5 flex min-w-0 items-center gap-1 text-xs"
            :class="{ 'text-muted-foreground': !event.exception }"
            ><MapPin aria-hidden="true" class="size-3 shrink-0" /><span
              class="[overflow-wrap:anywhere]"
              >{{ event.location }}</span
            ></span
          >
        </div>
        <span
          class="max-w-28 text-right text-xs tabular-nums leading-snug"
          :class="{ 'text-muted-foreground': !event.exception }"
        >
          <time
            v-if="event.at"
            :datetime="dayjs(event.at).toISOString()"
            :aria-label="formatEventTime(event)"
          >
            <span class="block">{{
              dayjs(event.at).format('MMM D, YYYY')
            }}</span>
            <span class="block whitespace-nowrap"
              >{{ event.approximate ? 'Approx. ' : ''
              }}{{ dayjs(event.at).format('h:mm A') }}</span
            >
          </time>
          <template v-else>{{ formatEventTime(event) }}</template>
        </span>
      </li>
    </ol>
    <p v-else class="border-t py-5 text-sm text-muted-foreground">
      The carrier has not reported a tracking event yet.
    </p>
  </section>
</template>
<style scoped>
.answer-scans li {
  position: relative;
}
.answer-scans li::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  inset-inline-start: calc(0.5rem + 0.6rem);
  border-inline-start: 1px solid hsl(var(--border));
}
.answer-scans li:first-child::before {
  top: 50%;
}
.answer-scans li:last-child::before {
  bottom: 50%;
}
@media (min-width: 640px) {
  .answer-scans li::before {
    inset-inline-start: calc(1rem + 0.6rem);
  }
}
</style>
