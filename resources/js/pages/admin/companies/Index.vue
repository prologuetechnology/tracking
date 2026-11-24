<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import CompaniesDataTable from '@/components/feature/company/CompaniesDataTable.vue'
import CompanyCreateButton from '@/components/feature/company/CompanyCreateButton.vue'
import StudioNavigation from '@/components/layout/navigation/StudioNavigation.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
const page = usePage()
const { userCan } = useRolesAndPermissions()
const studio = computed(
  () =>
    userCan(`company:show`) &&
    userCan(`company:update`) &&
    new URL(page.url, location.origin).searchParams.get(`workspace`) ===
      `studio`,
)
</script>
<template>
  <AuthenticatedLayout
    :title="studio ? 'Choose a company for design studio' : 'Companies'"
    :action="!studio && userCan('company:store') ? CompanyCreateButton : null"
  >
    <Head :title="studio ? 'Choose a company' : 'Companies'" />
    <StudioNavigation v-if="studio" />
    <CompaniesDataTable :studio="studio" />
  </AuthenticatedLayout>
</template>
