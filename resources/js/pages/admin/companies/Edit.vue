<script setup>
import { Head } from '@inertiajs/vue3'

import CompanyDesignWorkspace from '@/components/feature/company/CompanyDesignWorkspace.vue'
import CompanyForm from '@/components/feature/company/CompanyForm.vue'
import ToggleCompanyIsActive from '@/components/feature/company/ToggleCompanyIsActive.vue'
import CompanyApiTokenForm from '@/components/feature/companyApiToken/CompanyApiTokenForm.vue'
import CompanyFeatures from '@/components/feature/companyFeature/CompanyFeatures.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Card } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useCompanyQuery } from '@/composables/queries/company'

const props = defineProps({
  designWorkspace: { type: Object, required: true },
  shipmentLookup: { type: Object, default: null },
  companyInitialValues: {
    type: Object,
    required: true,
  },
  initialImageTypes: {
    type: Array,
    required: true,
  },
})

const { userCan } = useRolesAndPermissions()
const { data: company, isError } = useCompanyQuery({
  id: props.companyInitialValues.id,

  config: {
    initialData: props.companyInitialValues,
  },
})
</script>

<template>
  <Head :title="`${company?.name} - Manage Company`" />

  <AuthenticatedLayout :title="company?.name">
    <div v-if="company && !isError" class="mb-6">
      <CompanyDesignWorkspace
        :company="company"
        :lookup="shipmentLookup"
        :overview="designWorkspace"
      />
    </div>

    <template v-if="company && !isError && userCan('company:update')">
      <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-4">
        <Card
          class="flex min-w-0 items-center justify-between gap-4 p-5 lg:col-span-2"
        >
          <div class="w-full space-y-0.5">
            <Label class="text-base">Enabled</Label>

            <p class="text-sm text-muted-foreground">
              Enable company tracking portal.
            </p>
          </div>

          <div>
            <ToggleCompanyIsActive
              id="is_active"
              name="is_active"
              :company-id="company?.id"
              :value="Boolean(company?.is_active)"
            />
          </div>
        </Card>
      </div>

      <CompanyFeatures />

      <CompanyApiTokenForm :company="company" />
      <CompanyForm :company="company" />
    </template>
    <Card
      v-else-if="company && !isError"
      as="section"
      aria-label="Company details"
      class="p-5"
    >
      <h2 class="mb-3 font-semibold">Company details</h2>
      <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
        <dt class="text-muted-foreground">Tracking portal</dt>
        <dd>{{ company.is_active ? 'Enabled' : 'Disabled' }}</dd>
        <dt class="text-muted-foreground">Pipeline ID</dt>
        <dd>{{ company.pipeline_company_id }}</dd>
        <template v-if="company.email"
          ><dt class="text-muted-foreground">Email</dt>
          <dd class="break-all">{{ company.email }}</dd></template
        >
        <template v-if="company.phone"
          ><dt class="text-muted-foreground">Phone</dt>
          <dd>{{ company.phone }}</dd></template
        >
      </dl>
    </Card>
    <p v-else-if="isError" role="alert">Could not load company details.</p>
  </AuthenticatedLayout>
</template>
