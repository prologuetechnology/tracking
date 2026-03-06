<script setup>
import { Head } from '@inertiajs/vue3'

import RolePermissionTable from '@/components/feature/role/RolePermissionTable.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'

defineProps({
  initialRole: {
    type: Object,
    required: true,
  },
  initialPermissions: {
    type: Array,
    required: true,
  },
})
</script>

<template>
  <Head :title="`Role - ${initialRole.name}`" />

  <AuthenticatedLayout :title="initialRole.name">
    <RolePermissionTable
      :role="initialRole"
      :permissions="initialRole.permissions"
      :all-permissions="initialPermissions"
    />
  </AuthenticatedLayout>
</template>
