import { usePage } from '@inertiajs/vue3'

import { useCompanyQuery } from '@/composables/queries/company'

const normalizeFeaturesToCheck = (features) =>
  Array.isArray(features) ? features : [features]

const getCompanyFeatureSlugs = (company) =>
  (company?.features ?? []).map((feature) => feature.slug)

const hasCompanyFeature = (company, features) => {
  const featuresToCheck = normalizeFeaturesToCheck(features)
  const companyFeatureSlugs = getCompanyFeatureSlugs(company)

  return featuresToCheck.some((feature) =>
    companyFeatureSlugs.includes(feature),
  )
}

const doesNotHaveCompanyFeature = (company, features) => {
  const featuresToCheck = normalizeFeaturesToCheck(features)
  const companyFeatureSlugs = getCompanyFeatureSlugs(company)

  return featuresToCheck.every(
    (feature) => !companyFeatureSlugs.includes(feature),
  )
}

const useCompanyFeatures = ({ company = null } = {}) => {
  const companyHasFeature = (features) => hasCompanyFeature(company, features)

  const companyDoesNotHaveFeature = (features) =>
    doesNotHaveCompanyFeature(company, features)

  return {
    company,
    companyHasFeature,
    companyDoesNotHaveFeature,
  }
}

export default useCompanyFeatures
