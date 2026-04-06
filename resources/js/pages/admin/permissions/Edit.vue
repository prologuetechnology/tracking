<script setup>
// import { faEdit } from '@fortawesome/pro-duotone-svg-icons'

import PermissionForm from '@/components/feature/permission/PermissionForm.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { usePermissionQuery } from '@/composables/queries/permission'

const props = defineProps({
  initialPermission: {
    type: Object,
    required: true,
  },
})

const { data: permission, isError } = usePermissionQuery({
  id: props.initialPermission.id,

  config: {
    initialData: props.initialPermission,
  },
})
</script>

<template>
  <AuthenticatedLayout :title="'Permission Information'">
    <PermissionForm v-if="permission && !isError" :permission="permission" />
  </AuthenticatedLayout>
</template>
