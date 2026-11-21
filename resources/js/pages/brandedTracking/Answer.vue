<script setup>
import { usePage } from '@inertiajs/vue3'

import CuratedAnswer from '@/components/feature/tracking/curated/CuratedAnswer.vue'
import LegacyAnswer from '@/components/feature/tracking/curated/LegacyAnswer.vue'

const page = usePage()
</script>
<template>
  <CuratedAnswer v-if="page.props.sectionOrder" />
  <LegacyAnswer v-else />
</template>
