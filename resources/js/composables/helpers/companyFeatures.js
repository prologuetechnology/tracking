const hasCompanyFeature = (company, slug) => {
  return Boolean(company?.features?.some((feature) => feature.slug === slug))
}

export { hasCompanyFeature }
