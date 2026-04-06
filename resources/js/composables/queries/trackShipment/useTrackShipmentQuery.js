import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'

const trackShipment = async (formData) => {
  const { data } = await axios.post(route(`api.shipmentTracking`), formData)

  return data
}

const useTrackShipmentQuery = ({
  config = {},
  trackingNumber = ``,
  searchOption = ``,
  companyId = ``,
} = {}) =>
  useQuery({
    queryKey: [`trackShipment`, trackingNumber, searchOption, companyId],
    queryFn: () =>
      trackShipment({
        trackingNumber: trackingNumber?.value ?? trackingNumber,
        searchOption: searchOption?.value ?? searchOption,
        companyId,
      }),

    retry: false,
    ...config,
  })

export default useTrackShipmentQuery
