<script setup>
import { Head } from '@inertiajs/vue3'

import LoginButton from '@/components/feature/auth/LoginButton.vue'
import StaffFooter from '@/components/layout/footer/StaffFooter.vue'
import DefaultLayout from '@/components/layout/page/DefaultLayout.vue'
</script>

<template>
  <Head title="Home" />

  <DefaultLayout :has-navbar="false">
    <div
      class="flex min-h-full flex-col items-center justify-center space-y-24"
    >
      <div>
        <h1 class="w-auto text-4xl font-bold text-zinc-700">
          Welcome to {{ $page.props.app.name }}
        </h1>

        <p class="mt-2 w-auto text-center text-zinc-500">
          Customized tracking for your shipments.
        </p>
      </div>

      <LoginButton provider="azure" size="lg" />
    </div>
    <template #footer><StaffFooter /></template>
  </DefaultLayout>
</template>
