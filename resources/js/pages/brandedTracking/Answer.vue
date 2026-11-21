<script setup>
import { usePage } from '@inertiajs/vue3'

import CuratedAnswer from '@/components/feature/tracking/curated/CuratedAnswer.vue'
import CuratedAnswerV1 from '@/components/feature/tracking/curated/legacy/CuratedAnswerV1.vue'
import LegacyAnswer from '@/components/feature/tracking/curated/LegacyAnswer.vue'

const page = usePage()
</script>
<template>
  <CuratedAnswer v-if="page.props.presentationVersion === 2" />
  <CuratedAnswerV1 v-else-if="page.props.sectionOrder" />
  <LegacyAnswer v-else />
</template>
