<script setup>
import dayjs from 'dayjs'
import { Check, ChevronDown, CircleDot, TriangleAlert } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps({
  events: { type: Array, required: true },
  carrier: { type: String, default: null },
  formatEventTime: { type: Function, required: true },
  headingClass: { type: String, default: `` },
})
const expanded = ref(null)
const scans = computed(() =>
  props.events.map((event) => ({
    event,
    time: event.at && dayjs(event.at).isValid() ? dayjs(event.at) : null,
    icon: event.exception
      ? TriangleAlert
      : /^delivered\b/i.test(event.status)
        ? Check
        : CircleDot,
  })),
)
const commonYear = computed(() => {
  const years = new Set(
    scans.value.filter((scan) => scan.time).map((scan) => scan.time.year()),
  )
  return years.size === 1 ? [...years][0] : null
})
const toggle = (id) => {
  expanded.value = expanded.value === id ? null : id
}
</script>

<template>
  <section
    aria-labelledby="scan-register"
    class="min-w-0 rounded-md border bg-card text-card-foreground"
  >
    <header class="flex items-baseline justify-between gap-4 p-4 sm:px-5">
      <h2
        id="scan-register"
        :class="headingClass"
        class="text-xl font-semibold tracking-tight sm:text-2xl"
      >
        Scan register
      </h2>
      <span class="text-right font-mono text-xs text-muted-foreground"
        >{{ events.length }}
        {{ events.length === 1 ? 'event' : 'events' }}</span
      >
    </header>

    <p v-if="!scans.length" class="border-t p-5 text-sm text-muted-foreground">
      The carrier has not reported a tracking event yet.
    </p>
    <table
      v-else
      class="w-full table-fixed border-separate border-spacing-0 text-left"
    >
      <caption class="sr-only">
        Carrier scans, most recent first. Select a carrier event to inspect its
        details.
      </caption>
      <colgroup>
        <col class="w-[25%] sm:w-[19%]" />
        <col />
        <col class="w-[29%]" />
      </colgroup>
      <thead class="bg-muted text-muted-foreground">
        <tr>
          <th scope="col" class="register-heading">
            Time<span v-if="commonYear"> · {{ commonYear }}</span>
          </th>
          <th scope="col" class="register-heading">Carrier event</th>
          <th scope="col" class="register-heading">Location</th>
        </tr>
      </thead>
      <tbody
        v-for="({ event, time, icon }, index) in scans"
        :key="event.id"
        class="align-top"
      >
        <tr
          class="relative hover:bg-muted/60"
          :class="
            event.exception
              ? 'bg-amber-50 text-amber-950'
              : index === 0
                ? 'bg-primary/5'
                : ''
          "
        >
          <td
            class="register-cell relative font-mono text-[0.6875rem] tabular-nums sm:text-xs"
            :class="{
              'border-l-[3px] border-l-primary':
                index === 0 && !event.exception,
              'border-l-[3px] border-l-amber-600': event.exception,
            }"
          >
            <time v-if="time" :datetime="time.toISOString()">
              <span class="block">{{
                time.format(commonYear ? 'D MMM' : 'D MMM YYYY')
              }}</span>
              <span class="block"
                ><span v-if="event.approximate">Approx. </span
                >{{ time.format('h:mm A') }}</span
              >
            </time>
            <span v-else>Unavailable</span>
          </td>
          <th scope="row" class="register-cell">
            <button
              type="button"
              class="flex min-w-0 items-start gap-1.5 text-left text-xs font-semibold leading-snug after:absolute after:inset-0 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:gap-2 sm:text-sm"
              :aria-label="
                [
                  event.status || 'Carrier update',
                  formatEventTime(event),
                  event.location,
                  index === 0 ? 'Latest scan' : null,
                ]
                  .filter(Boolean)
                  .join(' · ')
              "
              :aria-expanded="expanded === event.id"
              :aria-controls="'scan-details-' + index"
              @click="toggle(event.id)"
            >
              <component
                :is="icon"
                aria-hidden="true"
                class="mt-0.5 size-3.5 shrink-0"
                :class="event.exception ? 'text-amber-800' : 'text-primary'"
              />
              <span class="min-w-0 [overflow-wrap:anywhere]"
                >{{ event.status || 'Carrier update' }}
                <span
                  v-if="index === 0"
                  class="mt-1 block text-[0.625rem] font-medium uppercase tracking-wider"
                  >Latest scan</span
                >
              </span>
            </button>
          </th>
          <td class="register-cell text-xs sm:text-sm">
            <div class="flex items-baseline justify-between gap-1">
              <span class="min-w-0 [overflow-wrap:anywhere]">{{
                event.location || 'Unavailable'
              }}</span>
              <ChevronDown
                aria-hidden="true"
                class="size-3 shrink-0 self-center"
                :class="{ 'rotate-180': expanded === event.id }"
              />
            </div>
          </td>
        </tr>
        <tr v-show="expanded === event.id" :id="'scan-details-' + index">
          <td colspan="3" class="border-t border-dashed bg-muted/40 p-4">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="detail-label">
                  {{
                    event.approximate && time
                      ? 'Approximate scan time'
                      : 'Scan time'
                  }}
                </dt>
                <dd>{{ formatEventTime(event) || 'Unavailable' }}</dd>
              </div>
              <div v-if="carrier">
                <dt class="detail-label">Carrier</dt>
                <dd>{{ carrier }}</dd>
              </div>
              <div v-if="event.raw?.pro_number">
                <dt class="detail-label">Carrier PRO</dt>
                <dd class="break-all font-mono text-xs">
                  {{ event.raw.pro_number }}
                </dd>
              </div>
              <div v-if="event.exception">
                <dt class="detail-label">Exception</dt>
                <dd>
                  {{
                    index === 0
                      ? 'Current carrier exception'
                      : 'Earlier carrier exception'
                  }}
                </dd>
              </div>
            </dl>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.register-heading {
  @apply border-y px-2 py-2 font-mono text-[0.625rem] font-semibold uppercase tracking-wide sm:px-4 sm:text-[0.6875rem];
}
.register-cell {
  @apply border-b px-2 py-3 align-middle sm:px-4;
}
.detail-label {
  @apply mb-1 text-xs font-medium text-muted-foreground;
}
</style>
