import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const toggleCompanyEnableMap = async (companyId) => {
  const { data } = await axios.patch(
    route(`api.companies.features.toggle`, {
      company: companyId,
      feature: `enable_map`,
    }),
  )

  return data
}

const useToggleCompanyEnableMapMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ companyId }) => toggleCompanyEnableMap(companyId),

    ...config,
  })

export default useToggleCompanyEnableMapMutation
