<script setup>
import { Head } from '@inertiajs/vue3'

import UserManagementTable from '@/components/feature/userManagement/UserManagementTable.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <Head title="Admin | Users" />

  <AuthenticatedLayout title="Users">
    <UserManagementTable />
  </AuthenticatedLayout>
</template>
