<script setup>
import { faGhost } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { useImpersonateUserStartMutation } from '@/composables/mutations/user'
import { useUserQuery } from '@/composables/queries/user'

const { user: initialCurrentUser } = usePage().props.auth

const { data: currentUser } = useUserQuery({
  userId: initialCurrentUser.id,

  config: {
    initialData: initialCurrentUser,
  },
})

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
})

const isCurrentUser = computed(() => {
  return props.user.id === currentUser.value.id
})

const { mutate: impersonateUserStart } = useImpersonateUserStartMutation({
  config: {
    onSuccess: () => {
      router.visit(route(`home`), {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
})
</script>

<template>
  <Button
    :dusk="`impersonate-user-${user.id}`"
    :disabled="isCurrentUser"
    size="sm"
    variant="outline"
    @click="() => impersonateUserStart(user.id)"
  >
    <FontAwesomeIcon class="mr-2" :icon="faGhost" fixed-width />

    Impersonate
  </Button>
</template>
