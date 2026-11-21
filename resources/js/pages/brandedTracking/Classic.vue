<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import ShipmentDetailsAndTracking from '@/components/feature/tracking/ShipmentDetailsAndTracking.vue'
import TrackingLayout from '@/components/layout/page/TrackingLayout.vue'

const page = usePage()
const isRefreshing = ref(false)
const lastUpdated = page.props.proofCapture?.at
  ? Date.parse(page.props.proofCapture.at)
  : Date.now()

const proNumber = computed(() => {
  const shipment = page.props.initialTrackingData

  return (
    shipment.carrierPro ??
    shipment.allStatuses?.find((status) => status.pro_number !== null)
      ?.pro_number
  )
})

const refresh = () => {
  if (page.props.isStudioPreview) {
    window.parent.postMessage(
      { type: `tracking-studio-refresh` },
      window.origin,
    )
    return
  }
  router.visit(window.location.href, {
    replace: true,
    preserveScroll: true,
    onStart: () => (isRefreshing.value = true),
    onFinish: () => (isRefreshing.value = false),
  })
}
</script>

<template>
  <Head>
    <title>
      {{ page.props.initialCompany?.name ?? page.props.app.name }} -
      {{ proNumber }}
    </title>

    <meta
      name="description"
      content="Track and share shipment details with ease."
    />
  </Head>

  <TrackingLayout
    :banner-file-path="page.props.initialCompany?.banner?.file_path"
    :footer-file-path="page.props.initialCompany?.footer?.file_path"
  >
    <ShipmentDetailsAndTracking
      :tracking-data="page.props.initialTrackingData"
      :company="page.props.initialCompany"
      :shipment-coordinates="page.props.initialShipmentCoordinates"
      :shipment-documents="page.props.initialShipmentDocuments"
      :on-refresh="refresh"
      :is-refreshing="isRefreshing"
      :last-updated="lastUpdated"
    />
  </TrackingLayout>
</template>
