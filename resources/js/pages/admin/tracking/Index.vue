<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowUpRight, Search, SlidersHorizontal } from 'lucide-vue-next'
import { computed, nextTick, ref } from 'vue'

import ShipmentDetailsAndTracking from '@/components/feature/tracking/ShipmentDetailsAndTracking.vue'
import SignalScanInspector from '@/components/feature/tracking/SignalScanInspector.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useTrackShipmentQuery } from '@/composables/queries/trackShipment'

const { userCan } = useRolesAndPermissions()
const reference = ref(``)
const method = ref(`carrierPro`)
const brand = ref(``)
const showBrand = ref(false)
const lookup = ref(null)
const { data, refetch, dataUpdatedAt, isError, isFetching, error } =
  useTrackShipmentQuery({
    config: { enabled: false },
    trackingNumber: computed(() => lookup.value?.trackingNumber ?? ``),
    searchOption: computed(() => lookup.value?.searchOption ?? ``),
    brand: computed(() => lookup.value?.brand ?? ``),
  })
const company = computed(() => data.value?.company)
const query = computed(() =>
  lookup.value
    ? {
        trackingNumber: lookup.value.trackingNumber,
        searchOption: lookup.value.searchOption,
      }
    : {},
)
const publicLink = computed(() =>
  route(`trackShipment.index`, {
    ...query.value,
    companyId: company.value?.pipeline_company_id,
    brand: company.value?.brand || lookup.value?.brand || undefined,
  }),
)
const statusCode = computed(() => error.value?.response?.status)
const failedTitle = computed(() =>
  statusCode.value === 404
    ? `Shipment not found.`
    : `Shipment lookup unavailable`,
)
function reset() {
  reference.value = ``
  brand.value = ``
  showBrand.value = false
  lookup.value = null
}
async function submit() {
  if (!reference.value.trim() || isFetching.value) return
  lookup.value = {
    trackingNumber: reference.value.trim(),
    searchOption: method.value,
    brand: brand.value.trim(),
  }
  await nextTick()
  await refetch()
}
</script>
<template>
  <AuthenticatedLayout wide>
    <Head title="Shipment console" />
    <div class="space-y-5">
      <header>
        <h1
          class="text-3xl font-semibold leading-tight tracking-tight sm:text-4xl"
        >
          Shipment console
        </h1>
      </header>
      <form class="border-y border-border py-4" @submit.prevent="submit">
        <div class="flex flex-wrap items-end gap-3">
          <div class="w-40 max-w-full space-y-1.5">
            <Label for="searchOption">Reference type</Label>
            <Select v-model="method">
              <SelectTrigger
                id="searchOption"
                dusk="tracking-search-option-trigger"
                ><SelectValue
              /></SelectTrigger>
              <SelectContent>
                <SelectItem value="bol" dusk="tracking-search-option-bol">
                  Bill of lading
                </SelectItem>
                <SelectItem
                  value="carrierPro"
                  dusk="tracking-search-option-carrier-pro"
                >
                  Carrier PRO
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="min-w-40 max-w-xl flex-1 space-y-1.5">
            <Label for="tracking-number-input">Shipment reference</Label
            ><Input
              id="tracking-number-input"
              v-model="reference"
              dusk="tracking-number-input"
              autocomplete="off"
              maxlength="255"
              required
              placeholder="Enter shipment reference"
            />
          </div>
          <Button
            type="submit"
            dusk="tracking-search-submit"
            :disabled="!reference.trim() || isFetching"
            ><Search class="mr-2 size-4" /> Look up</Button
          >
          <Button
            type="button"
            variant="outline"
            aria-controls="lookup-brand"
            :aria-expanded="showBrand"
            @click="showBrand = !showBrand"
            ><SlidersHorizontal class="mr-2 size-4" />Brand</Button
          >
          <Button
            v-if="lookup"
            type="button"
            variant="ghost"
            :disabled="isFetching"
            @click="reset"
          >
            Clear
          </Button>
        </div>
        <div
          v-if="showBrand"
          id="lookup-brand"
          class="mt-3 max-w-sm space-y-1.5"
        >
          <Label for="shipment-brand">Brand identifier</Label
          ><Input
            id="shipment-brand"
            v-model="brand"
            autocomplete="off"
            maxlength="255"
            placeholder="Brand from the customer’s tracking link"
          />
        </div>
      </form>
      <p
        v-if="lookup && isFetching && !data"
        class="text-muted-foreground"
        role="status"
      >
        Looking up shipment…
      </p>
      <section
        v-if="lookup && isError"
        class="space-y-2 border-l-2 border-destructive bg-card p-4"
        role="alert"
      >
        <h2 class="text-lg font-semibold">{{ failedTitle }}</h2>
        <p v-if="statusCode === 404">
          Check {{ lookup.trackingNumber }}, its reference type, and the brand
          if this company uses branded links.
        </p>
        <p v-else>
          The shipment service could not complete the request. Try the lookup
          again.
        </p>
        <Button
          type="button"
          variant="outline"
          :disabled="isFetching"
          @click="refetch()"
          >Retry lookup</Button
        >
      </section>
      <template v-if="lookup && data?.trackingData">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div class="min-w-0">
            <p v-if="company?.name" class="text-sm text-muted-foreground">
              {{ company.name }}
            </p>
            <h2 class="break-all font-mono text-xl font-semibold">
              {{ lookup.trackingNumber }}
            </h2>
          </div>
          <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
            <a
              :href="publicLink"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-primary underline-offset-4 hover:underline"
              >Customer tracking page <ArrowUpRight class="size-4"
            /></a>
            <Link
              v-if="company?.uuid && userCan('company:show')"
              :href="
                route('admin.companies.show', {
                  company: company.uuid,
                  _query: query,
                })
              "
              class="text-primary underline-offset-4 hover:underline"
              >Company workspace</Link
            >
            <Link
              v-if="
                company?.uuid &&
                userCan('company:show') &&
                userCan('company:update')
              "
              :href="
                route('admin.drafts.create', {
                  company: company.uuid,
                  _query: query,
                })
              "
              class="text-primary underline-offset-4 hover:underline"
              >Create design draft</Link
            >
          </div>
        </div>
        <SignalScanInspector
          :key="JSON.stringify(lookup)"
          :shipment="data.trackingData"
        />
        <ShipmentDetailsAndTracking
          :key="company?.uuid || 'unbranded'"
          :tracking-data="data.trackingData"
          :company="company ?? {}"
          :shipment-coordinates="data.shipmentCoordinates"
          :shipment-documents="data.shipmentDocuments"
          :show-history="false"
          :on-refresh="refetch"
          :is-refreshing="isFetching"
          :last-updated="dataUpdatedAt"
        />
      </template>
      <p v-if="!lookup" class="text-sm text-muted-foreground">
        Look up a shipment to see its route, carrier scans, and tracking
        documents.
      </p>
    </div>
  </AuthenticatedLayout>
</template>
