<script setup>
import { Head } from '@inertiajs/vue3'

import CompanyForm from '@/components/feature/company/CompanyForm.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <AuthenticatedLayout title="Create Company">
    <Head title="Create Company" />
    <CompanyForm />
  </AuthenticatedLayout>
</template>
