import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'

const useTrackShipmentQuery = ({
  config = {},
  trackingNumber = ``,
  searchOption = ``,
  companyId = ``,
  brand = ``,
} = {}) =>
  useQuery({
    queryKey: [`trackShipment`, trackingNumber, searchOption, companyId, brand],
    queryFn: async ({ queryKey, signal }) => {
      const [, trackingNumber, searchOption, companyId, brand] = queryKey
      const { data } = await axios.post(
        route(`api.shipmentTracking`),
        {
          trackingNumber,
          searchOption,
          companyId,
          brand,
        },
        { signal },
      )
      return data
    },
    retry: false,
    ...config,
  })
export default useTrackShipmentQuery
