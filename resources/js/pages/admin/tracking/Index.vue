<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowUpRight, Info, Search } from 'lucide-vue-next'
import { computed, nextTick, ref } from 'vue'

import ShipmentDetailsAndTracking from '@/components/feature/tracking/ShipmentDetailsAndTracking.vue'
import SignalScanInspector from '@/components/feature/tracking/SignalScanInspector.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
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
  <AuthenticatedLayout title="Shipment Lookup" wide>
    <Head title="Shipment Lookup" />
    <div class="space-y-5">
      <Card
        as="form"
        aria-label="Shipment Lookup"
        class="overflow-hidden"
        @submit.prevent="submit"
      >
        <div
          class="grid gap-5 p-5 sm:p-6 md:grid-cols-[minmax(9rem,.8fr)_minmax(13rem,1.5fr)_minmax(12rem,1fr)] md:gap-y-2"
        >
          <div
            class="grid content-start gap-2 md:row-span-2 md:grid-rows-subgrid"
          >
            <Label
              for="searchOption"
              class="flex min-h-7 items-center leading-5"
            >
              Reference type
            </Label>
            <Select v-model="method">
              <SelectTrigger
                id="searchOption"
                dusk="tracking-search-option-trigger"
              >
                <SelectValue />
              </SelectTrigger>
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
          <div
            class="grid min-w-0 content-start gap-2 md:row-span-2 md:grid-rows-subgrid"
          >
            <Label
              for="tracking-number-input"
              class="flex min-h-7 items-center leading-5"
            >
              Shipment reference
            </Label>
            <Input
              id="tracking-number-input"
              v-model="reference"
              dusk="tracking-number-input"
              autocomplete="off"
              maxlength="255"
              required
              placeholder="Enter shipment reference"
            />
          </div>
          <div
            class="grid min-w-0 content-start gap-2 md:row-span-2 md:grid-rows-subgrid"
          >
            <div class="flex min-h-7 items-center gap-2">
              <Label for="shipment-brand" class="leading-5">
                Brand identifier
                <span class="font-normal text-muted-foreground">
                  (optional)
                </span>
              </Label>
              <Popover>
                <PopoverTrigger as-child>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    aria-label="About brand identifiers"
                  >
                    <Info class="size-4" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent aria-label="About brand identifiers">
                  Add a brand identifier when multiple brands share a Pipeline
                  company ID. Use the brand from the customer’s tracking link.
                </PopoverContent>
              </Popover>
            </div>
            <Input
              id="shipment-brand"
              v-model="brand"
              autocomplete="off"
              maxlength="255"
              placeholder="e.g. jayco"
            />
          </div>
        </div>
        <div
          class="flex items-center justify-end gap-2 bg-muted/40 px-5 py-3 sm:px-6"
        >
          <Button
            type="button"
            variant="ghost"
            :disabled="isFetching || (!lookup && !reference && !brand)"
            @click="reset"
          >
            Clear
          </Button>
          <Button
            type="submit"
            class="gap-2"
            dusk="tracking-search-submit"
            :disabled="!reference.trim() || isFetching"
          >
            <Search class="size-4" aria-hidden="true" /> Look up shipment
          </Button>
        </div>
      </Card>
      <p
        v-if="lookup && isFetching && !data"
        class="text-muted-foreground"
        role="status"
      >
        Looking up shipment…
      </p>
      <Card
        v-if="lookup && isError"
        as="section"
        class="space-y-2 border-l-2 border-destructive bg-card p-4"
        role="alert"
      >
        <h2 class="text-lg font-semibold">
          {{ failedTitle }}
        </h2>
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
        >
          Retry lookup
        </Button>
      </Card>
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
            >
              Customer tracking page <ArrowUpRight class="size-4" />
            </a>
            <Link
              v-if="company?.uuid && userCan('company:show')"
              :href="
                route('admin.companies.show', {
                  company: company.uuid,
                  _query: query,
                })
              "
              class="text-primary underline-offset-4 hover:underline"
            >
              Company workspace
            </Link>
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
            >
              Create design draft
            </Link>
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
    </div>
  </AuthenticatedLayout>
</template>
