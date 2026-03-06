<script setup>
import { Head } from '@inertiajs/vue3'

import PermissionForm from '@/components/feature/permission/PermissionForm.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <Head title="Create Permission" />

  <AuthenticatedLayout title="Create Permission">
    <PermissionForm />
  </AuthenticatedLayout>
</template>
