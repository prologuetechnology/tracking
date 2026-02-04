const hasCompanyFeature = (company, slug) => {
  console.log(company)
  console.log(slug)
  return Boolean(company?.features?.some((feature) => feature.slug === slug))
}

export { hasCompanyFeature }
