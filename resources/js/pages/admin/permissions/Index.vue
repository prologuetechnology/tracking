<script setup>
import { Head } from '@inertiajs/vue3'

import PermissionsDataTable from '@/components/feature/permission/PermissionsDataTable.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <Head title="Permissions" />

  <AuthenticatedLayout title="Permissions">
    <PermissionsDataTable />
  </AuthenticatedLayout>
</template>
