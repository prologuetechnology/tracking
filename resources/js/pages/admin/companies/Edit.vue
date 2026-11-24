<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, PanelsTopLeft } from 'lucide-vue-next'

import CompanyArtwork from '@/components/feature/company/CompanyArtwork.vue'
import CompanyDesignWorkspace from '@/components/feature/company/CompanyDesignWorkspace.vue'
import CompanyForm from '@/components/feature/company/CompanyForm.vue'
import ToggleCompanyIsActive from '@/components/feature/company/ToggleCompanyIsActive.vue'
import CompanyApiTokenForm from '@/components/feature/companyApiToken/CompanyApiTokenForm.vue'
import CompanyFeatures from '@/components/feature/companyFeature/CompanyFeatures.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
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
  <AuthenticatedLayout>
    <Head :title="company?.name ?? 'Company'" />
    <div v-if="company && !isError">
      <Link
        :href="route('admin.companies.index')"
        class="mb-5 inline-flex items-center gap-2 text-sm text-muted-foreground hover:underline"
      >
        <ArrowLeft class="size-4" />
        Companies
      </Link>
      <header
        class="mb-7 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-5 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:gap-6"
      >
        <CompanyArtwork
          :company="company"
          class="size-16 rounded-md sm:h-28 sm:w-52"
        />
        <div class="grid min-w-0 gap-3">
          <div class="flex flex-wrap items-center gap-3">
            <h1 class="break-words">
              {{ company.name }}
            </h1>
            <Badge :variant="company.is_active ? 'secondary' : 'outline'">
              {{ company.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </div>
          <dl
            aria-label="Company details"
            class="flex flex-wrap gap-x-6 gap-y-2 text-sm"
          >
            <div>
              <dt class="text-xs text-muted-foreground">Pipeline ID</dt>
              <dd class="font-mono">
                {{ company.pipeline_company_id }}
              </dd>
            </div>
            <div v-if="company.brand">
              <dt class="text-xs text-muted-foreground">Brand</dt>
              <dd>
                {{ company.brand }}
              </dd>
            </div>
          </dl>
        </div>
        <Button
          v-if="userCan('company:update')"
          as-child
          class="col-span-2 w-fit gap-2 sm:col-span-1 sm:justify-self-end"
        >
          <Link
            :href="
              route('admin.drafts.index', {
                company: company.uuid,
                _query: shipmentLookup ?? {},
              })
            "
          >
            <PanelsTopLeft class="size-4" />
            Design Studio
          </Link>
        </Button>
      </header>
      <Tabs default-value="overview">
        <TabsList
          v-if="userCan('company:update')"
          aria-label="Company sections"
          class="company-sections"
        >
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="details"> Company Details </TabsTrigger>
          <TabsTrigger value="tracking"> Tracking Controls </TabsTrigger>
          <TabsTrigger value="pipeline"> Pipeline Authentication </TabsTrigger>
        </TabsList>

        <TabsContent
          value="overview"
          class="grid items-start gap-8 pt-6 sm:grid-cols-2 lg:max-w-4xl lg:gap-16"
        >
          <section aria-labelledby="company-contact">
            <h2 id="company-contact" class="mb-4 text-lg font-semibold">
              Contact
            </h2>
            <dl
              v-if="company.email || company.phone || company.website"
              class="grid gap-3 text-sm"
            >
              <div v-if="company.email">
                <dt class="text-xs text-muted-foreground">Email</dt>
                <dd class="break-all">
                  <a
                    :href="'mailto:' + company.email"
                    class="hover:underline"
                    >{{ company.email }}</a
                  >
                </dd>
              </div>
              <div v-if="company.phone">
                <dt class="text-xs text-muted-foreground">Phone</dt>
                <dd>
                  <a :href="'tel:' + company.phone" class="hover:underline">{{
                    company.phone
                  }}</a>
                </dd>
              </div>
              <div v-if="company.website">
                <dt class="text-xs text-muted-foreground">Website</dt>
                <dd class="break-all">
                  {{ company.website }}
                </dd>
              </div>
            </dl>
            <p v-else class="text-sm text-muted-foreground">
              No contact details on file.
            </p>
          </section>
          <CompanyDesignWorkspace
            :company="company"
            :lookup="shipmentLookup"
            :overview="designWorkspace"
          />
        </TabsContent>
        <template v-if="userCan('company:update')">
          <TabsContent
            value="details"
            force-mount
            class="pt-4 data-[state=inactive]:hidden"
          >
            <CompanyForm :company="company" />
          </TabsContent>
          <TabsContent value="tracking" class="max-w-3xl pt-4">
            <h2 class="text-xl font-semibold">Tracking Controls</h2>
            <div class="flex items-center justify-between gap-4 py-5">
              <div>
                <Label for="is_active" class="text-base">
                  Tracking portal
                </Label>
                <p class="text-sm text-muted-foreground">
                  Enable tracking for this company.
                </p>
              </div>
              <ToggleCompanyIsActive
                id="is_active"
                name="is_active"
                :company-id="company.id"
                :value="Boolean(company.is_active)"
              />
            </div>
            <CompanyFeatures />
          </TabsContent>
          <TabsContent
            value="pipeline"
            force-mount
            class="pt-4 data-[state=inactive]:hidden"
          >
            <CompanyApiTokenForm :company="company" />
          </TabsContent>
        </template>
      </Tabs>
    </div>
    <p v-else-if="isError" role="alert">Could not load company details.</p>
  </AuthenticatedLayout>
</template>
<style scoped>
.company-sections {
  width: 100%;
  height: auto;
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: 0.25rem;
  padding: 0;
  border-radius: 0;
  background: transparent;
}
.company-sections :deep([role='tab'][data-state='active']) {
  color: hsl(var(--accent-foreground));
  background: hsl(var(--accent));
  box-shadow: none;
}
</style>
