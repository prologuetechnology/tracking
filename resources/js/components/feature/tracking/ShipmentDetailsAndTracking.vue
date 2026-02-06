<script setup>
import {
  faArrowRight,
  faBoxesStacked,
  faCalendarArrowDown,
  faCalendarArrowUp,
  faCalendarCheck,
  faExclamationTriangle,
  faFileLines,
  faFileSignature,
  faMapLocationDot,
  faTruckContainer,
  faWeightScale,
} from '@fortawesome/pro-duotone-svg-icons'
import { faSync } from '@fortawesome/pro-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import dayjs from 'dayjs'
import { computed } from 'vue'

import AddressCard from '@/components/feature/tracking/AddressCard.vue'
import ShipmentDetail from '@/components/feature/tracking/ShipmentDetail.vue'
import ShipmentDocuments from '@/components/feature/tracking/ShipmentDocuments.vue'
import StatusStepper from '@/components/feature/tracking/StatusStepper.vue'
import TrackingMap from '@/components/feature/tracking/TrackingMap.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useCompanyFeatures } from '@/composables/helpers'
import { useTrackShipmentQuery } from '@/composables/queries/trackShipment'

const props = defineProps({
  trackingData: {
    type: Object,
    required: true,
  },
  company: {
    type: Object,
    required: true,
    default: null,
  },
  shipmentCoordinates: {
    type: Object,
    required: true,
    default: null,
  },
  shipmentDocuments: {
    type: Array,
    required: true,
  },
  useTrackShipmentQueryRefetch: {
    type: Function,
    required: false,
    default: () => null,
  },
  lastUpdated: {
    type: String,
    required: false,
    default: null,
  },
})

const { companyHasFeature } = useCompanyFeatures({
  company: props.company,
})

const { refetch, dataUpdatedAt, isRefetching } = useTrackShipmentQuery({
  trackingNumber: props.trackingData.bolNum,
  searchOption: `bol`,
  companyId: props.company?.pipeline_company_id ?? ``,
})

const bolNumber = computed(() => {
  return props.trackingData?.bolNum
})

const proNumber = computed(() => {
  const proNumber = props.trackingData?.carrierPro

  const statusWithProNumber = props.trackingData?.allStatuses?.find(
    (status) => status.pro_number !== null,
  )

  return proNumber ?? statusWithProNumber?.pro_number
})

const statuses = computed(() => {
  return props.trackingData?.allStatuses
})

const numberOfPieces = computed(() => {
  return props.trackingData?.lineItems?.reduce((previous, current) => {
    const currentPieces = Number(current.pieces) || 0

    return previous + currentPieces
  }, 0)
})
</script>

<template>
  <div
    v-if="dataUpdatedAt"
    class="mb-4 flex items-center justify-between gap-x-2 text-xs text-muted-foreground"
  >
    <p>
      <strong>Last Updated:</strong>

      {{ dayjs(dataUpdatedAt).format('MMMM D, YYYY h:mm A') }}
    </p>

    <div>
      <Button
        variant="ghost"
        :disabled="isRefetching"
        size="xs"
        @click="refetch"
      >
        <FontAwesomeIcon
          class="mr-2"
          :icon="faSync"
          fixed-width
          :spin="isRefetching"
        />

        <span>Refresh</span>
      </Button>
    </div>
  </div>

  <div class="flex flex-col gap-12">
    <!-- Shipment Info -->
    <Card class="w-full shadow-lg">
      <CardHeader>
        <CardTitle>Shipment Details</CardTitle>
      </CardHeader>

      <CardContent>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-[1fr,auto]">
          <div
            class="grid grid-flow-col grid-cols-1 grid-rows-5 gap-x-8 gap-y-4 md:grid-cols-2 md:grid-rows-3"
          >
            <ShipmentDetail
              v-if="proNumber"
              :detail="proNumber"
              label="Tracking Number"
              :icon="faMapLocationDot"
              is-copyable
            />

            <ShipmentDetail
              v-if="bolNumber"
              :detail="bolNumber"
              label="Bill of Lading"
              :icon="faFileSignature"
              is-copyable
            />

            <ShipmentDetail
              :detail="trackingData?.carrierName"
              label="Carrier"
              :icon="faTruckContainer"
            />

            <ShipmentDetail
              v-if="numberOfPieces"
              :detail="numberOfPieces"
              :label="`Piece${numberOfPieces > 1 ? `s` : ``}`"
              :icon="faBoxesStacked"
            />

            <ShipmentDetail
              :detail="`${trackingData?.totalWeight} lb${
                Number(trackingData?.totalWeight) || 0 >= 1 ? `s` : ``
              }`"
              label="Total Weight"
              :icon="faWeightScale"
            />

            <ShipmentDetail
              v-if="
                companyHasFeature(`customer_pos`) &&
                trackingData?.poNumbers?.length
              "
              :detail="`${
                trackingData?.poNumbers?.length
                  ? trackingData?.poNumbers.join(`, `)
                  : `N/A`
              }`"
              label="Customer POs"
              :icon="faFileLines"
            />
          </div>

          <div
            class="flex min-w-56 flex-col gap-4 border-t border-t-border pt-4 md:border-l md:border-t-0 md:border-l-border md:pl-4 md:pt-0"
          >
            <ShipmentDetail
              v-if="trackingData?.actualDeliveryDate"
              :detail="
                dayjs(trackingData?.actualDeliveryDate).format('MMMM D, YYYY')
              "
              :icon="faCalendarCheck"
              label="Actual Delivery Date"
            />

            <ShipmentDetail
              v-if="
                !trackingData?.actualDeliveryDate &&
                trackingData?.estimatedDeliveryDate
              "
              :detail="
                dayjs(trackingData?.estimatedDeliveryDate).format(
                  'MMMM D, YYYY',
                )
              "
              :icon="faCalendarArrowDown"
              label="Estimated Delivery Date"
            />

            <ShipmentDetail
              v-if="trackingData?.actualPickupDate"
              :detail="
                dayjs(trackingData?.actualPickupDate).format('MMMM D, YYYY')
              "
              :icon="faCalendarCheck"
              label="Actual Pickup Date"
            />

            <ShipmentDetail
              v-if="
                !trackingData?.actualPickupDate &&
                trackingData?.estimatedPickupDate
              "
              :detail="
                dayjs(trackingData?.estimatedPickupDate).format('MMMM D, YYYY')
              "
              :icon="faCalendarArrowUp"
              label="Estimated Pickup Date"
            />
          </div>
        </section>
      </CardContent>
    </Card>

    <!-- Special Instructions -->
    <Card
      v-if="
        companyHasFeature(`special_instructions`) &&
        trackingData.specialInstructions
      "
      class="w-full shadow-lg"
    >
      <CardHeader>
        <CardTitle>Special Instructions</CardTitle>
      </CardHeader>

      <CardContent>
        <p>{{ trackingData.specialInstructions }}</p>
      </CardContent>
    </Card>

    <!-- Address Cards -->
    <section
      class="flex flex-col items-stretch justify-between space-x-0 space-y-8 md:flex-row md:space-x-8 md:space-y-0"
    >
      <AddressCard :location="trackingData?.originLocation" type="Origin" />

      <FontAwesomeIcon
        class="rotate-90 transform self-center md:rotate-0"
        :icon="faArrowRight"
        fixed-width
      />

      <AddressCard
        :location="trackingData?.destinationLocation"
        type="Destination"
      />
    </section>

    <!-- Shipment Documents -->
    <section
      v-if="
        shipmentDocuments?.length >= 1 &&
        companyHasFeature(`enable_documents`)
      "
    >
      <ShipmentDocuments
        :documents="shipmentDocuments"
        :bol-number="bolNumber"
      />
    </section>

    <!-- Shipment Tracking Map -->
    <Card
      v-if="shipmentCoordinates && companyHasFeature(`enable_map`)"
      class="w-full shadow-lg"
    >
      <CardHeader>
        <CardTitle>Tracking Map</CardTitle>
      </CardHeader>

      <CardContent>
        <TrackingMap :shipment-coordinates="shipmentCoordinates[0]" />
      </CardContent>
    </Card>

    <!-- Status History -->
    <section>
      <h3 class="mb-6 text-2xl font-semibold">Status History</h3>

      <StatusStepper v-if="statuses" :statuses="statuses" />

      <div v-else class="mt-24 flex flex-col items-center justify-center">
        <FontAwesomeIcon
          class="text-2xl text-amber-300"
          :icon="faExclamationTriangle"
          fixed-width
        />

        <p class="mt-2 text-center text-sm text-muted-foreground">
          No status history found...yet.
        </p>
      </div>
    </section>
  </div>
</template>
