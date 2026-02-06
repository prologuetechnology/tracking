import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const toggleCompanyFeature = async (companyId, feature) => {
  const { data } = await axios.patch(
    route(`api.companies.features.toggle`, {
      company: companyId,
      feature: feature,
    }),
  )

  return data
}

const useToggleCompanyFeatureMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ companyId, feature }) =>
      toggleCompanyFeature(companyId, feature),

    ...config,
  })

export default useToggleCompanyFeatureMutation
