<script setup>
import { Head, usePage } from '@inertiajs/vue3'

import AlienGhostSvg from '@/components/art/AlienGhostSvg.vue'
import DefaultLayout from '@/components/layout/page/DefaultLayout.vue'

const {
  app: { name: appName },
} = usePage().props

defineProps({
  trackingNumber: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <Head :title="`${appName} - Tracking - Shipment not found`" />

  <DefaultLayout :has-navbar="false">
    <section
      class="flex min-h-full flex-col items-center justify-center space-y-12"
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
  </DefaultLayout>
</template>
