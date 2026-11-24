<script setup>
import { ArrowRight } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import {
  formatEventTime,
  place,
  trackingEvents,
} from '@/composables/hooks/tracking/useTrackingDesign'

const props = defineProps({ shipment: { type: Object, required: true } })
const scans = computed(() => trackingEvents(props.shipment.allStatuses))
const selectedId = ref(null)
const exceptionsOnly = ref(false)
const visible = computed(() =>
  exceptionsOnly.value
    ? scans.value.filter((scan) => scan.exception)
    : scans.value,
)
const selected = computed(
  () =>
    visible.value.find((scan) => scan.id === selectedId.value) ??
    visible.value[0],
)
const stops = computed(() => {
  const visits = []
  for (const scan of [...scans.value].reverse()) {
    const name = scan.location.trim()
    if (!name || !scan.at) continue
    let visit = visits.at(-1)
    if (!visit || visit.name.toLowerCase() !== name.toLowerCase()) {
      visit = { name, scans: [] }
      visits.push(visit)
    }
    visit.scans.push(scan.id)
  }
  return visits
})
function chooseStop(visit) {
  exceptionsOnly.value = false
  selectedId.value = visit.scans.at(-1)
}
function showFilter(exception) {
  exceptionsOnly.value = exception
  selectedId.value = null
}
</script>
<template>
  <div class="scan-console">
    <Card as="section" class="scan-list" aria-labelledby="signal-scans">
      <h2 id="signal-scans">Scan history</h2>
      <ToggleGroup
        type="single"
        :model-value="exceptionsOnly ? 'exceptions' : 'all'"
        size="sm"
        class="scan-filters"
        aria-label="Filter carrier scans"
        @update:model-value="$event && showFilter($event === 'exceptions')"
      >
        <ToggleGroupItem value="all">All {{ scans.length }}</ToggleGroupItem
        ><ToggleGroupItem value="exceptions"
          >Exceptions</ToggleGroupItem
        ></ToggleGroup
      >
      <ul v-if="visible.length" aria-label="Carrier scans">
        <li v-for="scan in visible" :key="scan.id">
          <Button
            variant="ghost"
            type="button"
            class="scan"
            :aria-pressed="selected?.id === scan.id"
            @click="selectedId = scan.id"
          >
            <strong>{{ scan.status }}</strong
            ><span>{{ scan.location || 'Location unavailable' }}</span
            ><time :datetime="scan.at || undefined">{{
              formatEventTime(scan)
            }}</time>
          </Button>
        </li>
      </ul>
      <p v-else class="empty">
        {{
          exceptionsOnly
            ? 'No exceptions reported.'
            : 'No carrier scans reported yet.'
        }}
      </p>
    </Card>
    <Card as="section" class="trace" aria-labelledby="signal-route">
      <h2 id="signal-route">Route trace</h2>
      <ol
        v-if="stops.length"
        aria-label="Reported stops in chronological order"
      >
        <li v-for="(stop, index) in stops" :key="stop.scans[0]">
          <Button
            variant="ghost"
            type="button"
            :aria-pressed="stop.scans.includes(selected?.id)"
            :aria-label="'Stop ' + (index + 1) + ', ' + stop.name"
            @click="chooseStop(stop)"
          >
            <i aria-hidden="true" /><span>{{ stop.name }}</span>
          </Button>
        </li>
      </ol>
      <p v-else class="empty">No timed scan locations reported.</p>
      <div class="trace-caption">
        <span>{{ place(shipment.originLocation) || 'Origin unavailable' }}</span
        ><ArrowRight aria-hidden="true" :size="14" /><span>{{
          place(shipment.destinationLocation) || 'Destination unavailable'
        }}</span>
      </div>
    </Card>
    <Card as="aside" class="scan-inspector" aria-live="polite">
      <h2>Selected scan</h2>
      <template v-if="selected"
        ><strong>{{ selected.status }}</strong>
        <p>{{ selected.location || 'Location unavailable' }}</p>
        <time :datetime="selected.at || undefined">{{
          formatEventTime(selected)
        }}</time></template
      >
      <p v-else>No scan selected.</p>
    </Card>
  </div>
</template>
<style scoped>
.scan-console {
  display: grid;
  grid-template-columns: minmax(220px, 0.85fr) minmax(0, 2fr);
  grid-template-rows: auto 1fr;
  gap: 12px;
}
.scan-console section,
.scan-console aside {
  min-width: 0;
  padding: clamp(16px, 1.55vw, 24px);
}
.scan-list {
  grid-column: 1;
  grid-row: 1/3;
}
.scan-list ul {
  max-height: 410px;
  overflow-y: auto;
  margin-inline: calc(-1 * clamp(16px, 1.55vw, 24px));
}
.scan-console h2 {
  font-weight: 700;
  font-size: 18px;
  line-height: 1.3;
  letter-spacing: -0.02em;
}
.scan-filters,
.trace-caption {
  display: flex;
  align-items: center;
  gap: 5px;
}
.scan-filters {
  margin: 8px 0 12px;
}
.scan-filters {
  width: fit-content;
  padding: 3px;
  border-radius: var(--radius);
  background: hsl(var(--muted));
}
.scan {
  display: block;
  width: 100%;
  height: auto;
  min-height: 3.5rem;
  white-space: normal;
  border-radius: 0;
  text-align: left;
  padding: 10px clamp(16px, 1.55vw, 24px);
  border-top: 1px solid hsl(var(--border));
}
.scan strong,
.scan span,
.scan time {
  display: block;
}
.scan span,
.scan time,
.scan-inspector time,
.empty {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
}
.scan time,
.scan-inspector time,
.trace-caption {
  font-family: ui-monospace, monospace;
}
.scan[aria-pressed='true'] {
  background: hsl(var(--accent));
  box-shadow: inset 3px 0 hsl(var(--primary));
}
.trace {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 14px;
  background-image: radial-gradient(hsl(var(--border)) 1px, transparent 1px);
  background-size: 13px 13px;
}
.trace ol {
  display: flex;
  overflow-x: auto;
  padding-block: 6px 12px;
}
.trace li {
  flex: 1;
  min-width: 100px;
  position: relative;
}
.trace li:not(:last-child):before {
  content: '';
  position: absolute;
  left: 50%;
  top: 14px;
  width: 100%;
  height: 2px;
  background: hsl(var(--primary));
}
.trace button {
  height: auto;
  white-space: normal;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 9px;
  width: 100%;
  padding: 8px 2px 2px;
  font:
    12px ui-monospace,
    monospace;
}
.trace button i {
  background: hsl(var(--card));
  border: 2px solid hsl(var(--primary));
  border-radius: 50%;
  height: 14px;
  width: 14px;
}
.trace button[aria-pressed='true'] i {
  background: hsl(var(--signal-marker, var(--primary)));
  outline: 1px solid hsl(var(--primary));
  outline-offset: 3px;
}
.trace button span,
.trace h2,
.trace-caption {
  background: hsl(var(--card));
  width: fit-content;
}
.trace-caption {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
}
.scan-inspector strong {
  display: block;
  font-size: clamp(22px, 3vw, 36px);
  line-height: 1.2;
  color: hsl(var(--primary));
  margin: 7px 0;
}
.scan-inspector h2,
.trace h2 {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font:
    650 12px ui-monospace,
    monospace;
}
.empty {
  margin-block: 12px;
}
.scan-console button:focus-visible {
  outline: 2px solid hsl(var(--ring));
  outline-offset: -2px;
}
@media (max-width: 720px) {
  .scan-console {
    grid-template-columns: minmax(0, 1fr);
    grid-template-rows: auto;
  }
  .scan-list {
    grid-column: 1;
    grid-row: 2;
  }
  .trace {
    grid-row: 1;
  }
  .scan-list ul {
    max-height: 280px;
  }
  .trace li {
    min-width: 82px;
  }
}
</style>
