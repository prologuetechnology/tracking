import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const clearCompanyImageAsset = async (companyId, type) => {
  const { data } = await axios.patch(
    route(`api.companies.clearImageAsset`, companyId),
    {
      type,
    },
  )

  return data
}

const useCompanyClearImageAssetMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ companyId, type }) =>
      clearCompanyImageAsset(companyId, type),

    ...config,
  })

export default useCompanyClearImageAssetMutation
