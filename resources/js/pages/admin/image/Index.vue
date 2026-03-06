<script setup>
import { h } from 'vue'

import ImagesDataTable from '@/components/feature/image/ImagesDataTable.vue'
import ImageUploadButton from '@/components/feature/image/ImageUploadButton.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'

const props = defineProps({
  initialImages: {
    type: Array,
    required: true,
  },
  initialImageTypes: {
    type: Array,
    required: true,
  },
})

const imageUploadAction = () =>
  h(ImageUploadButton, {
    initialImageTypes: props.initialImageTypes,
  })
</script>

<template>
  <Head title="Manage Images" />

  <AuthenticatedLayout title="Manage Images" :action="imageUploadAction">
    <ImagesDataTable
      :initial-images="initialImages"
      :initial-image-types="initialImageTypes"
    />
  </AuthenticatedLayout>
</template>
