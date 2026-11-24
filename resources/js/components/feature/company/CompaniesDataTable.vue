<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowUpRight, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { Input } from '@/components/ui/input'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompaniesQuery } from '@/composables/queries/company'

defineProps({ studio: Boolean })
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
    <label
      for="find-company"
      class="mb-1.5 block text-sm font-medium text-foreground"
      >Find a company</label
    >
    <div class="relative mb-5 max-w-md">
      <Search
        :size="16"
        class="absolute left-3 top-3 text-muted-foreground"
      /><Input
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
    <div v-else class="divide-y rounded border bg-card">
      <Link
        v-for="company in companies"
        :key="company.uuid"
        :href="
          route(
            studio ? 'admin.drafts.index' : 'admin.companies.show',
            company.uuid,
          )
        "
        class="signal-company-row"
      >
        <span class="signal-company-logo"
          ><img
            v-if="company.logo?.file_path"
            :src="imageAssetUrl({ filePath: company.logo.file_path })"
            alt=""
          /><span v-else>{{ company.name.slice(0, 1) }}</span></span
        >
        <span
          ><strong>{{ company.name }}</strong
          ><small
            >{{ company.brand || 'Company' }} ·
            {{ company.pipeline_company_id }}</small
          ></span
        >
        <span class="company-state">{{
          company.is_active ? 'Active' : 'Inactive'
        }}</span
        ><ArrowUpRight :size="17" aria-hidden="true" />
      </Link>
      <p v-if="!companies.length" class="p-5 text-sm text-muted-foreground">
        {{ query ? 'No matching companies.' : 'No companies available.' }}
      </p>
    </div>
  </section>
</template>
