<script setup>
import { Head } from '@inertiajs/vue3'
import { h } from 'vue'

import ImagesDataTable from '@/components/feature/image/ImagesDataTable.vue'
import ImageUploadButton from '@/components/feature/image/ImageUploadButton.vue'
import SharedAssetsNavigation from '@/components/layout/navigation/SharedAssetsNavigation.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

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

const { userCan } = useRolesAndPermissions()
const imageUploadAction = () =>
  h(ImageUploadButton, {
    initialImageTypes: props.initialImageTypes,
  })
</script>

<template>
  <AuthenticatedLayout
    title="Shared Assets"
    :action="userCan('image:store') ? imageUploadAction : null"
  >
    <Head title="Shared Assets" />
    <SharedAssetsNavigation />
    <ImagesDataTable
      :initial-images="initialImages"
      :initial-image-types="initialImageTypes"
    />
  </AuthenticatedLayout>
</template>
