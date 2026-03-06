<script setup>
import { Head } from '@inertiajs/vue3'

import AddDomainDialog from '@/components/feature/allowedDomains/AddDomainDialog.vue'
import DomainsDataTable from '@/components/feature/allowedDomains/DomainsDataTable.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout title="Allowed Domains" :action="AddDomainDialog">
    <DomainsDataTable />
  </AuthenticatedLayout>
</template>
