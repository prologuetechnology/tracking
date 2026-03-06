<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

defineProps({
  type: {
    type: String,
    required: true,
    default: `Origin`,
  },
  location: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Card class="group w-full shadow-lg">
    <CardHeader>
      <CardTitle class="flex flex-row items-end space-x-4">
        <span>{{ type }}</span>
      </CardTitle>
    </CardHeader>

    <CardContent>
      <section class="flex flex-col items-start justify-between space-y-4">
        <address class="flex flex-col text-sm not-italic">
          <span
            v-if="location?.name"
            class="mb-2 text-base font-semibold capitalize leading-none"
          >
            {{ location?.name }}
          </span>

          <span v-if="location?.address">{{ location?.address }}</span>

          <span v-if="location?.address2">
            {{ location?.address2 }}
          </span>

          <span>
            {{ location?.city }},
            {{ location?.state }}
            {{ location?.zipCode }}
          </span>
        </address>

        <!-- <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button
                v-if="googleMapsLink"
                variant="outline"
                size="sm"
                as="a"
                :href="googleMapsLink"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full bg-background text-primary hover:bg-primary hover:text-primary-foreground"
              >
                <FontAwesomeIcon
                  class="mr-2 text-xl"
                  :icon="faMapLocationDot"
                  fixed-width
                />

                <span>Open in Maps</span>
              </Button>
            </TooltipTrigger>

            <TooltipContent>
              <p>Open in Google Maps</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider> -->
      </section>
    </CardContent>
  </Card>
</template>
