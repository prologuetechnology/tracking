<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { PanelsTopLeft, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useCompaniesQuery } from '@/composables/queries/company'

import CompanyArtwork from './CompanyArtwork.vue'

const { userCan } = useRolesAndPermissions()
const query = ref(``)
const { data, isError } = useCompaniesQuery({
  config: { initialData: usePage().props.initialCompanies },
})
const companies = computed(() =>
  (data.value || []).filter((company) =>
    [company.name, company.brand, company.pipeline_company_id].some((value) =>
      String(value ?? ``)
        .toLowerCase()
        .includes(query.value.trim().toLowerCase()),
    ),
  ),
)
</script>
<template>
  <section aria-label="Company directory">
    <Label for="find-company" class="mb-2 block"> Find a company </Label>
    <div class="relative mb-5 max-w-md">
      <Search :size="16" class="absolute left-3 top-3 text-muted-foreground" />
      <Input
        id="find-company"
        v-model="query"
        type="search"
        class="bg-card pl-9"
        placeholder="Name, brand, or Pipeline ID"
      />
    </div>
    <p v-if="isError" role="alert">
      The company directory could not be loaded.
    </p>
    <ul
      v-else-if="companies.length"
      class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
    >
      <Card
        v-for="company in companies"
        :key="company.uuid"
        :dusk="'company-tile-' + company.uuid"
        as="li"
        class="flex min-w-0 flex-col overflow-hidden"
      >
        <CompanyArtwork :company="company" class="h-28 sm:h-32" />
        <div class="flex flex-1 flex-col gap-4 p-5">
          <div class="flex items-start justify-between gap-3">
            <h2
              :id="'company-' + company.uuid"
              class="break-words text-lg font-semibold leading-tight"
            >
              {{ company.name }}
            </h2>
            <Badge
              :variant="company.is_active ? 'secondary' : 'outline'"
              class="shrink-0"
            >
              {{ company.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </div>
          <dl
            class="mt-auto grid grid-cols-[auto_minmax(0,1fr)] items-baseline gap-x-4 gap-y-1.5 text-sm"
          >
            <dt class="text-muted-foreground">Pipeline ID</dt>
            <dd class="font-mono">
              {{ company.pipeline_company_id }}
            </dd>
            <template v-if="company.brand">
              <dt class="text-muted-foreground">Brand</dt>
              <dd class="break-words">
                {{ company.brand }}
              </dd>
            </template>
            <template v-else-if="company.website">
              <dt class="text-muted-foreground">Website</dt>
              <dd class="break-all">
                {{
                  company.website
                    .replace(/^https?:\/\//i, '')
                    .replace(/\/$/, '')
                }}
              </dd>
            </template>
          </dl>
        </div>
        <div
          class="mt-auto flex flex-wrap items-center justify-between gap-2 px-4 pb-4 pt-1"
        >
          <Button variant="ghost" size="sm" as-child>
            <Link
              :href="route('admin.companies.show', company.uuid)"
              :aria-describedby="'company-' + company.uuid"
            >
              View Company
            </Link>
          </Button>
          <Button
            v-if="userCan('company:show') && userCan('company:update')"
            variant="outline"
            size="sm"
            as-child
          >
            <Link
              :href="route('admin.drafts.index', company.uuid)"
              :aria-describedby="'company-' + company.uuid"
              class="gap-2"
            >
              <PanelsTopLeft class="size-4" />
              Design Studio
            </Link>
          </Button>
        </div>
      </Card>
    </ul>
    <p v-else class="py-8 text-sm text-muted-foreground">
      {{ query ? 'No matching companies.' : 'No companies available.' }}
    </p>
  </section>
</template>
