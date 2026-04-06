const FEATURE_BACKED_BOOLEAN_FIELDS = new Set([
  `enable_map`,
  `enable_documents`,
])

const normalizeFeatureList = (features) => {
  if (Array.isArray(features)) {
    return features
  }

  if (Array.isArray(features?.data)) {
    return features.data
  }

  return []
}

const normalizeFeaturesToCheck = (features) =>
  Array.isArray(features) ? features : [features]

const getCompanyFeatureSlugs = (company) =>
  normalizeFeatureList(company?.features).map((feature) => feature.slug)

const companyBooleanFeatureEnabled = (company, feature) =>
  FEATURE_BACKED_BOOLEAN_FIELDS.has(feature) && Boolean(company?.[feature])

const hasCompanyFeature = (company, features) => {
  const featuresToCheck = normalizeFeaturesToCheck(features)
  const companyFeatureSlugs = getCompanyFeatureSlugs(company)

  return featuresToCheck.some(
    (feature) =>
      companyFeatureSlugs.includes(feature) ||
      companyBooleanFeatureEnabled(company, feature),
  )
}

const doesNotHaveCompanyFeature = (company, features) => {
  const featuresToCheck = normalizeFeaturesToCheck(features)
  const companyFeatureSlugs = getCompanyFeatureSlugs(company)

  return featuresToCheck.every(
    (feature) =>
      !companyFeatureSlugs.includes(feature) &&
      !companyBooleanFeatureEnabled(company, feature),
  )
}

const useCompanyFeatures = ({ company = null } = {}) => {
  const resolveArgs = (companyOrFeatures, maybeFeatures) => {
    if (typeof maybeFeatures === `undefined`) {
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
