import { usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { computed, inject, provide } from 'vue'

import { useCompanyTheme } from '@/composables/hooks/theme'

const key = Symbol(`tracking design`)
export const moduleNames = {
  receiving: `Receiving address`,
  references: `Shipment references`,
  instructions: `Special instructions`,
  documents: `Shipment documents`,
  map: `Tracking map`,
}
const text = (value) =>
  value !== undefined && value !== null && String(value).trim() !== ``
const dateValue = (value) =>
  value && dayjs(value).isValid() ? dayjs(value) : null
const dateField = (actual, estimated, actualLabel, estimatedLabel) => {
  const confirmed = dateValue(actual)
  return {
    label: confirmed ? actualLabel : estimatedLabel,
    value:
      (confirmed ?? dateValue(estimated))?.format(`MMM D, YYYY`) ??
      `Not available`,
  }
}
export const place = (location) =>
  [location?.city, location?.state].filter(Boolean).join(`, `)
export const formatEventTime = (event) => {
  const value = dateValue(event?.at)
  if (!value) return `Time unavailable`
  const time = value.format(`MMM D, YYYY · h:mm A`)
  return event.approximate ? `Approx. ` + time : time
}

export function useTrackingDesign() {
  const inherited = inject(key, null)
  if (inherited) return inherited
  useCompanyTheme()
  const page = usePage()
  const company = computed(() => page.props.initialCompany ?? {})
  const shipment = computed(() => page.props.initialTrackingData ?? {})
  const events = computed(() =>
    (shipment.value.allStatuses ?? [])
      .map((raw, index) => {
        const scanned = dateValue(raw.status_date_time)
        const time = scanned ?? dateValue(raw.timestamp)
        return {
          id: String(raw.id ?? `scan`) + `-` + index,
          status: raw.current_status || `Carrier update`,
          location: raw.current_location ?? ``,
          at: time ? (scanned ? raw.status_date_time : raw.timestamp) : null,
          approximate: !scanned && !!time,
          exception: /\b(exception|delay(?:ed)?)\b/i.test(
            raw.current_status ?? ``,
          ),
          sortTime: time?.valueOf() ?? -Infinity,
          raw,
        }
      })
      .sort((a, b) => b.sortTime - a.sortTime),
  )
  const latest = computed(() => events.value[0])
  const status = computed(
    () =>
      latest.value?.status ??
      (shipment.value.actualDeliveryDate
        ? `Delivered`
        : `Awaiting carrier update`),
  )
  const pickup = computed(() =>
    dateField(
      shipment.value.actualPickupDate,
      shipment.value.estimatedPickupDate,
      `Shipped`,
      `Estimated pickup`,
    ),
  )
  const delivery = computed(() =>
    dateField(
      shipment.value.actualDeliveryDate,
      shipment.value.estimatedDeliveryDate,
      `Delivered on`,
      `Estimated delivery`,
    ),
  )
  const pro = computed(
    () =>
      shipment.value.carrierPro ||
      events.value.find((event) => text(event.raw.pro_number))?.raw.pro_number,
  )
  const reference = computed(() => pro.value || shipment.value.bolNum || ``)
  const features = computed(
    () =>
      new Set((company.value.features ?? []).map((feature) => feature.slug)),
  )
  const documents = computed(() =>
    (page.props.initialShipmentDocuments ?? []).filter((document) =>
      /^https?:\/\//i.test(document.url ?? ``),
    ),
  )
  const coordinates = computed(() => page.props.initialShipmentCoordinates?.[0])
  const references = computed(() => {
    const s = shipment.value
    const pieces = (s.lineItems ?? [])
      .map((item) => item.pieces)
      .filter((value) => text(value) && Number.isFinite(Number(value)))
    return [
      [`Bill of lading`, s.bolNum],
      [`Carrier PRO`, pro.value],
      [`Carrier`, s.carrierName],
      [
        `Pieces`,
        pieces.length ? pieces.reduce((sum, n) => sum + Number(n), 0) : null,
      ],
      [`Total weight`, text(s.totalWeight) ? s.totalWeight + ` lb` : null],
      [
        `Customer POs`,
        features.value.has(`customer_pos`) ? s.poNumbers?.join(`, `) : null,
      ],
    ].filter(([, value]) => text(value))
  })
  const addresses = computed(() =>
    [
      [`Destination`, shipment.value.destinationLocation],
      [`Origin`, shipment.value.originLocation],
    ].filter(
      ([, address]) =>
        address &&
        [`name`, `address`, `address2`, `city`, `state`, `zipCode`].some(
          (field) => text(address[field]),
        ),
    ),
  )
  const modules = computed(() => {
    const available = {
      receiving: addresses.value.length,
      references: references.value.length,
      instructions:
        features.value.has(`special_instructions`) &&
        text(shipment.value.specialInstructions),
      documents:
        features.value.has(`enable_documents`) && documents.value.length,
      map: features.value.has(`enable_map`) && coordinates.value,
    }
    return (page.props.sectionOrder ?? []).filter((name) => available[name])
  })
  const headlineClass = computed(
    () =>
      ({ serif: `font-serif`, sans: `font-sans` })[page.props.headlineStyle] ??
      ``,
  )
  const model = {
    company,
    shipment,
    events,
    latest,
    status,
    pickup,
    delivery,
    pro,
    reference,
    references,
    addresses,
    coordinates,
    documents,
    modules,
    moduleNames,
    headlineClass,
    formatEventTime,
    place,
  }
  provide(key, model)
  return model
}
