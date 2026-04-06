<script setup>
import { faCircle } from '@fortawesome/pro-duotone-svg-icons'
import { faImageSlash } from '@fortawesome/pro-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link } from '@inertiajs/vue3'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from '@/components/ui/hover-card'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { imageAssetUrl } from '@/composables/hooks/disks'

const { userCan } = useRolesAndPermissions()

defineProps({
  company: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div class="flex flex-row items-center justify-start space-x-6">
    <div class="relative aspect-square w-16">
      <img
        v-if="company.logo?.file_path"
        :src="imageAssetUrl({ filePath: company.logo?.file_path })"
        class="absolute left-0 top-0 block h-full w-full scale-90 transform object-contain transition-all duration-300 group-hover:scale-95"
      />

      <div
        v-else
        class="absolute left-0 top-0 block h-full w-full flex-row items-center justify-center overflow-hidden rounded-lg bg-muted"
      >
        <FontAwesomeIcon
          class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 transform text-lg text-muted-foreground"
          :icon="faImageSlash"
          fixed-width
        />
      </div>
    </div>

    <div class="flex flex-row items-center justify-start space-x-1">
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger as-child>
            <FontAwesomeIcon
              class="text-xm mr-2"
              :icon="faCircle"
              fixed-width=""
              :class="{
                'text-lime-600': company.is_active,
                'text-red-600': !company.is_active,
              }"
            />
          </TooltipTrigger>

          <TooltipContent>
            <p>{{ company.is_active ? `Enabled` : `Disabled` }}</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <HoverCard :open-delay="300">
        <HoverCardTrigger as-child>
          <template v-if="userCan(`company:show`)">
            <Button variant="link" class="p-0 underline" as-child>
              <Link :href="route('admin.companies.show', company.uuid)">
                {{ company.name }}
              </Link>
            </Button>
          </template>
          <template v-else>
            {{ company.name }}
          </template>
        </HoverCardTrigger>
        <HoverCardContent class="w-[28rem]" align="start">
          <div class="flex flex-col items-start justify-start gap-4">
            <div class="flex w-full flex-row justify-between gap-1">
              <h4 class="text-xl font-semibold">{{ company.name }}</h4>
              <Badge v-if="company.pipeline_company_id" variant="secondary">
                Pipeline ID: {{ company.pipeline_company_id }}
              </Badge>
            </div>

            <div class="flex flex-col gap-4 text-sm">
              <p
                v-if="company.phone"
                class="flex flex-col items-start justify-start"
              >
                <span class="font-semibold">Phone: </span>

                <Button variant="link" class="h-auto p-0 underline" as-child>
                  <a
                    :href="`tel:+1${company.phone.replace(/\D/g, ``)}`"
                    target="_blank"
                  >
                    {{ company.phone }}
                  </a>
                </Button>
              </p>

              <p
                v-if="company.website"
                class="flex flex-col items-start justify-start"
              >
                <span class="font-semibold">Website: </span>

                <Button variant="link" class="h-auto p-0 underline" as-child>
                  <a :href="company.website" target="_blank">
                    {{ company.website }}
                  </a>
                </Button>
              </p>

              <p
                v-if="company.email"
                class="flex flex-col items-start justify-start"
              >
                <span class="font-semibold">Email: </span>

                <Button variant="link" class="h-auto p-0 underline" as-child>
                  <a :href="`mailto:${company.email}`" target="_blank">
                    {{ company.email }}
                  </a>
                </Button>
              </p>

              <p
                v-if="company.brand && company.requires_brand"
                class="flex flex-col items-start justify-start"
              >
                <span class="font-semibold">Brand: </span>

                <span>{{ company.brand }}</span>
              </p>
            </div>
          </div>
        </HoverCardContent>
      </HoverCard>
    </div>
  </div>
</template>
