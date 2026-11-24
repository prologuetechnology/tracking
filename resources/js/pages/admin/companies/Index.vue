<script setup>
import { Head } from '@inertiajs/vue3'

import CompaniesDataTable from '@/components/feature/company/CompaniesDataTable.vue'
import CompanyCreateButton from '@/components/feature/company/CompanyCreateButton.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
const { userCan } = useRolesAndPermissions()
</script>
<template>
  <AuthenticatedLayout
    title="Companies"
    :action="userCan('company:store') ? CompanyCreateButton : null"
  >
    <Head title="Companies" />
    <CompaniesDataTable />
  </AuthenticatedLayout>
</template>
