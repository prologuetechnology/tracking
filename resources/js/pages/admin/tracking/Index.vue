<script setup>
import { faSquareArrowUpRight } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

import AlienGhostSvg from '@/components/art/AlienGhostSvg.vue'
import ShipmentDetailsAndTracking from '@/components/feature/tracking/ShipmentDetailsAndTracking.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useTrackShipmentQuery } from '@/composables/queries/trackShipment'

const {
  app: { appUrl },
} = usePage().props

const trackingNumber = ref(``)
const searchOption = ref(``)

const {
  data,
  refetch,
  dataUpdatedAt,
  isError,
  isLoading,
  isFetching,
  isRefetching,
} = useTrackShipmentQuery({
  config: {
    enabled: false,
  },

  trackingNumber: trackingNumber,
  searchOption: searchOption,
})

const resetForm = () => {
  trackingNumber.value = ``
  searchOption.value = ``
}

const submitForm = () => {
  refetch()
}
</script>

<template>
  <Head title="Tracking" />

  <AuthenticatedLayout>
    <Card class="group w-full shadow-lg">
      <CardHeader>
        <CardTitle class="flex flex-row items-end space-x-4">
          Track a Shipment
        </CardTitle>
      </CardHeader>

      <CardContent>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_25%]">
          <div class="w-full">
            <Label for="trackingNumber">Tracking Number</Label>

            <Input
              id="trackingNumber"
              v-model="trackingNumber"
              name="trackingNumber"
              type="text"
              :disabled="isLoading || isFetching"
              placeholder="Enter tracking number"
            />
          </div>

          <div class="w-full">
            <Label for="searchOption">Type</Label>

            <Select
              id="searchOption"
              v-model="searchOption"
              name="searchOption"
              :disabled="isLoading || isFetching"
            >
              <SelectTrigger>
                <SelectValue placeholder="Select a type" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="bol">Bill of Lading</SelectItem>
                <SelectItem value="carrierPro">Carrier PRO</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </section>

        <section class="mt-6 flex flex-row items-center justify-end space-x-2">
          <Button
            v-if="trackingNumber && searchOption"
            variant="ghost"
            size="sm"
            class="mr-auto"
            as-child
          >
            <a
              :href="`${appUrl}/trackShipment?searchOption=${searchOption}&trackingNumber=${trackingNumber}`"
              target="_blank"
              rel="noopener noreferrer"
            >
              <FontAwesomeIcon
                class="mr-1"
                :icon="faSquareArrowUpRight"
                fixed-width
              />

              <span>Track in Portal</span>
            </a>
          </Button>

          <Button
            variant="outline"
            size="sm"
            :disabled="isLoading || isFetching"
            @click="resetForm"
          >
            Reset
          </Button>

          <Button
            variant="default"
            size="sm"
            :disabled="isLoading || isFetching"
            @click="submitForm"
          >
            Submit
          </Button>
        </section>
      </CardContent>
    </Card>

    <div v-if="data?.trackingData && !isError" class="mt-8">
      <ShipmentDetailsAndTracking
        :tracking-data="data?.trackingData"
        :company="data?.company"
        :shipment-coordinates="data?.shipmentCoordinates"
        :shipment-documents="data?.shipmentDocuments"
        :on-refresh="refetch"
        :is-refreshing="isRefetching"
        :last-updated="dataUpdatedAt"
      />
    </div>

    <section
      v-if="isError && !data?.trackingData?.bolNum"
      class="mt-24 flex flex-col items-center justify-center space-y-12"
    >
      <h2 class="text-center text-3xl font-semibold text-primary">
        Shipment not found.
      </h2>

      <p
        class="mx-auto mt-4 w-2/3 text-center text-lg font-medium text-primary"
      >
        Oh no! We couldn&apos;t locate shipment
        <span class="font-bold">{{ trackingNumber }}</span> or tracking data for
        it.
      </p>

      <div class="mt-24 flex flex-row justify-center p-12">
        <AlienGhostSvg />
      </div>

      <p
        class="font-regular mx-auto mt-4 w-2/3 text-center text-base text-primary"
      >
        If you're sure this shipment exists and are experiencing issues getting
        tracking data for it, please
        <a href="mailto:help@prologuetechnology.com" class="underline">
          contact our support team</a
        >.
      </p>
    </section>
  </AuthenticatedLayout>
</template>
