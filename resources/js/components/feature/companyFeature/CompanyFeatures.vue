<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Label } from '@/components/ui/label'
import { useCompanyQuery } from '@/composables/queries/company'
import { useCompanyFeaturesQuery } from '@/composables/queries/companyFeature'

import ToggleCompanyFeature from '../company/ToggleCompanyFeature.vue'

const { companyInitialValues, companyFeaturesInitialValues } = usePage().props

const { data: company } = useCompanyQuery({
  id: companyInitialValues.id,
  config: {
    initialData: companyInitialValues,
  },
})

const { data: companyFeatures } = useCompanyFeaturesQuery({
  config: {
    initialData: companyFeaturesInitialValues,
  },
})

const normalizeFeatures = (features) => {
  if (Array.isArray(features)) {
    return features
  }

  if (Array.isArray(features?.data)) {
    return features.data
  }

  return []
}

const computedFeatures = computed(() => {
  const allFeatures = normalizeFeatures(companyFeatures.value)
  const enabledFeatureSlugs = new Set(
    normalizeFeatures(company.value?.features).map((feature) => feature.slug),
  )

  return allFeatures.map((feature) => ({
    ...feature,
    enabled: enabledFeatureSlugs.has(feature.slug),
  }))
})
</script>

<template>
  <section
    v-if="computedFeatures.length >= 1"
    class="grid grid-cols-1 gap-4 md:grid-cols-2"
  >
    <div
      v-for="companyFeature in computedFeatures"
      :key="companyFeature.slug"
      class="flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4"
    >
      <div class="w-full space-y-0.5">
        <Label class="text-base">{{ companyFeature.name }}</Label>

        <p class="text-sm text-muted-foreground">
          {{ companyFeature.description }}
        </p>
      </div>

      <div>
        <ToggleCompanyFeature
          :id="companyFeature.slug"
          :company-id="company.id"
          :name="companyFeature.slug"
          :value="companyFeature.enabled"
        />
      </div>
    </div>
  </section>
</template>
