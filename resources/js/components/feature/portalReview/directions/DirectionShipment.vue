<script setup>
import {
  ArrowRight,
  ArrowUpRight,
  Check,
  CornerDownLeft,
  Search,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Input } from '@/components/ui/input'

import { assetUrl } from '../reviewStyles'
const props = defineProps({
  shipment: { type: Object, required: true },
  direction: { type: String, required: true },
  canCompany: Boolean,
})
defineEmits([`company`])
const query = ref(props.shipment.carrierPro),
  submitted = ref(props.shipment.carrierPro)
const matches = computed(() =>
  [props.shipment.carrierPro, props.shipment.bolNum].some(
    (id) => id.toLowerCase() === submitted.value.toLowerCase(),
  ),
)
const scans = computed(() => [...props.shipment.allStatuses].reverse())
const focus = ref(scans.value[0])
const mode = ref(`all`)
const visible = computed(() =>
  mode.value === `all`
    ? scans.value
    : scans.value.filter((s) =>
        s.current_status.toLowerCase().includes(`exception`),
      ),
)
const date = (stamp) =>
  new Intl.DateTimeFormat(`en-US`, {
    month: `short`,
    day: `numeric`,
    timeZone: `UTC`,
  }).format(new Date(stamp.slice(0, 10) + `T12:00:00Z`))
const time = (stamp) => stamp.slice(11, 16)
const places = computed(() => [
  ...new Set(props.shipment.allStatuses.map((s) => s.current_location)),
])
function selectPlace(place) {
  mode.value = `all`
  focus.value = scans.value.find((scan) => scan.current_location === place)
}
function sample() {
  query.value = props.shipment.carrierPro
  submitted.value = query.value
}
function filter(value) {
  mode.value = value
  focus.value = visible.value[0]
}
</script>
<template>
  <div class="shipment-surface">
    <form class="dir-lookup" @submit.prevent="submitted = query.trim()">
      <label for="dir-reference">Shipment reference</label>
      <div class="lookup-controls">
        <Search :size="17" /><Input
          id="dir-reference"
          v-model="query"
          autocomplete="off"
          required
          placeholder="Carrier PRO or bill of lading"
        /><button type="submit" class="dir-action">
          Look up <CornerDownLeft :size="15" />
        </button>
      </div>
    </form>
    <div v-if="!matches" class="empty-result" role="status">
      <h2>Shipment not found</h2>
      <p>No sample shipment matches “{{ submitted }}”.</p>
      <button class="dir-link" @click="sample">
        Use {{ shipment.carrierPro }} <ArrowRight :size="16" />
      </button>
    </div>
    <div v-else class="shipment-layout">
      <section class="route-panel" aria-label="Shipment route">
        <div v-if="direction === 'manifest'" class="docket">
          <div class="docket-top">
            <span>Carrier waybill</span><span>{{ shipment.bolNum }}</span>
          </div>
          <div class="airport-pair">
            <div>
              <strong>{{ shipment.originLocation.state }}</strong
              ><span>{{ shipment.originLocation.city }}</span>
            </div>
            <ArrowRight aria-hidden="true" />
            <div>
              <strong>{{ shipment.destinationLocation.state }}</strong
              ><span>{{ shipment.destinationLocation.city }}</span>
            </div>
          </div>
          <div class="docket-bottom">
            <span
              ><Check :size="15" /> Delivered ·
              {{ date(shipment.actualDeliveryDate) }}</span
            ><span class="docket-weight"
              >{{ shipment.lineItems[0].pieces }} PCS /
              {{ shipment.totalWeight }} LB</span
            >
          </div>
        </div>
        <figure v-else-if="direction === 'fieldbook'" class="travel-photo">
          <img
            :src="assetUrl('jayco-overlook')"
            alt="Jayco motorhome at a canyon overlook"
          />
          <figcaption>
            <span>Jayco, Inc.</span><span>Company photography</span>
          </figcaption>
        </figure>
        <div v-else class="signal-route">
          <div class="schematic-header">
            <span>Route trace</span><span>{{ shipment.bolNum }}</span>
          </div>
          <div class="trace-path">
            <button
              v-for="place in places"
              :key="place"
              class="trace-stop"
              :aria-pressed="focus.current_location === place"
              :aria-label="'Inspect scans in ' + place"
              @click="selectPlace(place)"
            >
              <i aria-hidden="true" /><span>{{ place.split(',')[0] }}</span>
            </button>
          </div>
          <div class="schematic-footer">
            <span><Check :size="14" /> Delivered</span
            ><span>{{ places.length }} locations</span>
          </div>
        </div>
      </section>
      <section class="shipment-file">
        <div class="dir-label">
          {{ direction === 'fieldbook' ? 'Delivery record' : 'Shipment file' }}
        </div>
        <h2 v-if="direction === 'fieldbook'">
          {{ shipment.originLocation.city }} <em>to</em>
          {{ shipment.destinationLocation.city }}
        </h2>
        <h2 v-else>{{ shipment.carrierPro }}</h2>
        <p v-if="direction === 'fieldbook'" class="file-reference">
          {{ shipment.carrierPro }} <span>·</span> Delivered
          {{ date(shipment.actualDeliveryDate) }}
        </p>
        <dl class="file-specs">
          <div>
            <dt>Carrier</dt>
            <dd>{{ shipment.carrierName }}</dd>
          </div>
          <div>
            <dt>Bill of lading</dt>
            <dd>{{ shipment.bolNum }}</dd>
          </div>
          <div>
            <dt>Shipment</dt>
            <dd>
              {{ shipment.lineItems[0].pieces }} pieces ·
              {{ shipment.totalWeight }} lb
            </dd>
          </div>
        </dl>
        <button v-if="canCompany" class="dir-link" @click="$emit('company')">
          Jayco workspace <ArrowUpRight :size="15" />
        </button>
      </section>
      <section class="scan-section">
        <div class="section-heading">
          <h2>
            {{ direction === 'fieldbook' ? 'Carrier journal' : 'Scan history' }}
          </h2>
          <div class="scan-filters" aria-label="Filter scans">
            <button :aria-pressed="mode === 'all'" @click="filter('all')">
              All {{ scans.length }}</button
            ><button
              :aria-pressed="mode === 'exceptions'"
              @click="filter('exceptions')"
            >
              Exceptions
            </button>
          </div>
        </div>
        <div class="scan-cols" aria-hidden="true">
          <span>Local time</span><span>Carrier event</span><span>Location</span>
        </div>
        <button
          v-for="s in visible"
          :key="s.id"
          class="scan-row"
          :aria-pressed="focus.id === s.id"
          @click="focus = s"
        >
          <span class="scan-date"
            >{{ date(s.status_date_time) }}
            <small>{{ time(s.status_date_time) }}</small></span
          ><strong
            ><i
              :class="{
                exception: s.current_status === 'Delivery exception',
                done: s.current_status === 'Delivered',
              }"
            />{{ s.current_status }}</strong
          ><span class="scan-location">{{ s.current_location }}</span>
        </button>
      </section>
      <aside class="scan-detail" aria-label="Selected scan">
        <div class="dir-label">Selected scan</div>
        <h3>{{ focus.current_status }}</h3>
        <p>{{ focus.current_location }}</p>
        <time :datetime="focus.status_date_time"
          >{{ date(focus.status_date_time) }} ·
          {{ time(focus.status_date_time) }} local</time
        ><a
          class="dir-link"
          :href="route('admin.portal-review.classic')"
          target="_blank"
          rel="noopener"
          >Open Classic tracking page <ArrowUpRight :size="15"
        /></a>
      </aside>
    </div>
  </div>
</template>
