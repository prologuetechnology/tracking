<script setup>
import { Head } from '@inertiajs/vue3'

import RoleTable from '@/components/feature/role/RoleTable.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <Head title="Roles" />

  <AuthenticatedLayout title="Manage Roles">
    <RoleTable />
  </AuthenticatedLayout>
</template>
