<script setup>
import { useQueryClient } from '@tanstack/vue-query'

import { Switch } from '@/components/ui/switch'
import { useToggleCompanyFeatureMutation } from '@/composables/mutations/company'

const props = defineProps({
  companyId: {
    type: Number,
    required: true,
  },
  id: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  value: {
    type: Boolean,
    required: true,
  },
})

const queryClient = useQueryClient()

const { mutate: toggleFeature, isPending } = useToggleCompanyFeatureMutation({
  config: {
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: [`companies`, props.companyId],
      })

      await queryClient.invalidateQueries({
        queryKey: [`companies`],
      })
    },
  },
})

const handleToggle = () => {
  toggleFeature({
    companyId: props.companyId,
    feature: props.id,
  })
}
</script>

<template>
  <Switch
    :id="id"
    :name="name"
    :checked="value"
    :disabled="isPending"
    @update:checked="handleToggle"
  />
</template>
