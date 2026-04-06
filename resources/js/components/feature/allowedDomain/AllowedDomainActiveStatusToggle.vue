<script setup>
import { useQueryClient } from '@tanstack/vue-query'

import { Switch } from '@/components/ui/switch'
import { useAllowedDomainToggleActiveStatusMutation } from '@/composables/mutations/allowedDomain'

const props = defineProps({
  allowedDomain: {
    type: Object,
    required: true,
  },
})

const queryClient = useQueryClient()

const { mutate: toggleAllowedDomainActiveStatus, isPending } =
  useAllowedDomainToggleActiveStatusMutation({
    config: {
      onSuccess: async () => {
        await queryClient.invalidateQueries({
          queryKey: [`allowedDomains`],
        })
      },
    },
  })

const handleToggle = () => {
  toggleAllowedDomainActiveStatus({
    allowedDomainId: props.allowedDomain.id,
  })
}
</script>

<template>
  <Switch
    :dusk="`allowed-domain-toggle-${props.allowedDomain.id}`"
    :checked="allowedDomain.is_active"
    :disabled="isPending"
    @update:checked="handleToggle"
  />
</template>
