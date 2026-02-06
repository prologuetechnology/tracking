import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'

const getCompanyFeatures = async () => {
  const { data } = await axios.get(route(`api.companies.features.index`))

  return data
}

const useCompanyFeaturesQuery = ({ config = {} } = {}) =>
  useQuery({
    queryKey: [`companyFeatures`],
    queryFn: getCompanyFeatures,

    ...config,
  })

export default useCompanyFeaturesQuery
