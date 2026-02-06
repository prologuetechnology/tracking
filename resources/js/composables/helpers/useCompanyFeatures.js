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
  const resolveArgs = (companyOrFeatures, maybeFeatures) => {
    if (typeof maybeFeatures === 'undefined') {
      return {
        companyToCheck: company,
        featuresToCheck: companyOrFeatures,
      }
    }

    return {
      companyToCheck: companyOrFeatures,
      featuresToCheck: maybeFeatures,
    }
  }

  const companyHasFeature = (companyOrFeatures, maybeFeatures) => {
    const { companyToCheck, featuresToCheck } = resolveArgs(
      companyOrFeatures,
      maybeFeatures,
    )

    return hasCompanyFeature(companyToCheck, featuresToCheck)
  }

  const companyDoesNotHaveFeature = (companyOrFeatures, maybeFeatures) => {
    const { companyToCheck, featuresToCheck } = resolveArgs(
      companyOrFeatures,
      maybeFeatures,
    )

    return doesNotHaveCompanyFeature(companyToCheck, featuresToCheck)
  }

  return {
    company,
    companyHasFeature,
    companyDoesNotHaveFeature,
  }
}

export { doesNotHaveCompanyFeature, hasCompanyFeature, useCompanyFeatures }
export default useCompanyFeatures
