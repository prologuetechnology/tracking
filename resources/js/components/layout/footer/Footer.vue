<script setup>
import { faCopyright } from '@fortawesome/pro-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import dayjs from 'dayjs'

import { Badge } from '@/components/ui/badge'

defineProps({ sticky: { type: Boolean, default: true } })
</script>

<template>
  <footer
    :class="{ 'sticky bottom-0 z-50': sticky }"
    class="flex w-full flex-row items-center justify-center space-x-2 border-t border-t-border/70 bg-background/70 py-4 shadow-[0_-12px_25px_-12px_rgb(0_0_0_/_0.17)] backdrop-blur-lg"
  >
    <p class="text-center text-sm">
      <a
        href="https://prologuetechnology.com/"
        target="_blank"
        rel="noopener noreferrer"
        class="font-semibold underline"
      >
        {{ $page.props.app.name }}
      </a>

      <FontAwesomeIcon
        class="mx-[0.125rem] text-xs"
        :icon="faCopyright"
        fixed-width
      />

      {{ dayjs().year() }}
    </p>

    <div
      v-if="$page.props.app.env !== `production`"
      class="flex flex-row items-center justify-center"
    >
      <Badge variant="destructive" class="capitalize">
        <span>{{ $page.props.app.env }}</span>
      </Badge>
    </div>
  </footer>
</template>
