<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ previewError: { type: String, default: null } })
</script>
<template>
  <Head title="Shipment unavailable" />
  <main class="mx-auto grid max-w-md gap-2 px-6 py-16 text-center">
    <h1 class="text-xl font-semibold">Shipment unavailable</h1>
    <p class="text-muted-foreground">
      {{
        previewError ||
        'Check the shipment number and lookup type for this company, then try again.'
      }}
    </p>
  </main>
</template>
