<script setup>
import {
  faArrowUpFromBracket,
  faCopy,
} from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { usePage } from '@inertiajs/vue3'
import { VueQueryDevtools } from '@tanstack/vue-query-devtools'
import { useShare } from '@vueuse/core'
import { useClipboard } from '@vueuse/core'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompanyTheme } from '@/composables/hooks/theme'

import Footer from '../footer/Footer.vue'

const {
  app: { name: appName },
  initialTrackingData,
  initialCompany,
} = usePage().props

useCompanyTheme()

defineProps({
  bannerFilePath: {
    type: String,
    required: false,
    default: null,
  },
  footerFilePath: {
    type: String,
    required: false,
    default: null,
  },
})

const bolNumber = computed(() => {
  return initialTrackingData?.bolNum
})

const proNumber = computed(() => {
  const proNumber = initialTrackingData?.carrierPro

  const statusWithProNumber = initialTrackingData?.allStatuses?.find(
    (status) => status.pro_number !== null,
  )

  return proNumber ?? statusWithProNumber?.pro_number
})

const sharePage = useShare({
  title: `${initialCompany?.name ?? appName} - Tracking - ${bolNumber.value}`,
  text: `Track your shipment ${bolNumber.value} - ${proNumber.value}`,
  url: location.href,
})

const startSharePage = () => {
  return sharePage.share().catch((error) => console.error(error))
}

const pageHrefClipboard = useClipboard()

const copyPageHref = () => pageHrefClipboard.copy(location.href)
</script>

<template>
  <div
    class="relative grid min-h-screen items-start bg-background/0"
    :class="{
      'grid-rows-[auto,1fr,auto]': bannerFilePath,
      'grid-rows-[1fr,auto]': !bannerFilePath,
    }"
  >
    <!-- <Navbar /> -->
    <div class="group relative mx-auto mb-16 h-[40vh] w-full max-w-4xl">
      <div
        v-if="initialCompany?.banner?.file_path"
        class="absolute left-0 top-0 h-full w-full transition-opacity duration-500 ease-in-out group-hover:opacity-100"
      >
        <div
          class="absolute right-6 top-[calc(40vh+0.75rem)] flex flex-row items-center justify-start space-x-2 self-center sm:col-start-3 sm:col-end-4 sm:row-start-1 sm:row-end-3 sm:mt-0 sm:justify-end"
        >
          <Button
            v-if="sharePage.isSupported"
            variant="default"
            size="sm"
            @click="startSharePage"
          >
            <FontAwesomeIcon :icon="faArrowUpFromBracket" fixed-width />
          </Button>

          <Button variant="default" size="sm" @click="copyPageHref">
            <FontAwesomeIcon class="mr-2" :icon="faCopy" fixed-width />

            <span>Copy Link</span>
          </Button>
        </div>

        <img
          :src="imageAssetUrl({ filePath: initialCompany?.banner?.file_path })"
          :alt="initialCompany?.banner?.name"
          class="h-full w-full object-cover md:rounded-b-lg"
        />
      </div>

      <div
        v-else
        class="absolute left-0 top-0 flex h-full w-full flex-row items-center justify-center bg-gradient-to-r from-muted to-accent-foreground md:rounded-b-lg"
      >
        <img
          v-if="!initialCompany"
          :src="imageAssetUrl({ filePath: `images/hero-image.jpg` })"
          alt="Flat World Global Solutions Hero Image"
          class="h-full w-full object-cover md:rounded-b-lg"
        />

        <div v-if="!initialCompany">
          <p
            class="absolute right-6 top-[calc(40vh-3.75rem)] w-40 transform text-right font-sans text-base font-light tracking-wide text-background/50 md:top-[calc(40vh-2.75rem)] md:w-auto md:text-lg"
          >
            Powered by Prologue Technology
          </p>
        </div>

        <div
          class="absolute right-6 top-[calc(40vh+0.75rem)] flex flex-row items-center justify-start space-x-2 self-center sm:col-start-3 sm:col-end-4 sm:row-start-1 sm:row-end-3 sm:mt-0 sm:justify-end"
        >
          <Button
            v-if="sharePage.isSupported"
            variant="default"
            size="sm"
            @click="startSharePage"
          >
            <FontAwesomeIcon :icon="faArrowUpFromBracket" fixed-width />
          </Button>

          <Button variant="default" size="sm" @click="copyPageHref">
            <FontAwesomeIcon class="mr-2" :icon="faCopy" fixed-width />

            <span>Copy Link</span>
          </Button>
        </div>
      </div>

      <div
        class="absolute left-6 top-[calc(40vh-6rem)] mb-4 flex flex-col items-stretch justify-start space-y-4 md:top-[calc(40vh-11rem)]"
      >
        <div
          class="relative flex aspect-square h-40 w-40 flex-row items-center justify-center overflow-hidden rounded-lg border border-border bg-card p-4 shadow-lg md:h-60 md:w-60"
        >
          <img
            :src="
              initialCompany?.logo?.file_path
                ? imageAssetUrl({ filePath: initialCompany?.logo?.file_path })
                : imageAssetUrl({ filePath: `images/FW_Logo_Full_Color.png` })
            "
            :alt="initialCompany?.logo?.name"
          />
        </div>
      </div>
    </div>

    <div
      class="mx-auto mb-8 mt-12 w-full max-w-4xl rounded-lg bg-background px-6 py-4"
    >
      <main class="w-full">
        <slot />
      </main>

      <div
        v-if="footerFilePath"
        class="mx-auto mt-8 w-full max-w-3xl overflow-hidden rounded-lg shadow-xl"
      >
        <img
          :src="imageAssetUrl({ filePath: footerFilePath })"
          class="w-full object-cover"
          alt=""
        />
      </div>
    </div>

    <Footer />
  </div>

  <VueQueryDevtools />
</template>
