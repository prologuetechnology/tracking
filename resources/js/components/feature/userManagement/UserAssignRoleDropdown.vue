<script setup lang="ts">
import { faUserShield } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { usePage } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useUpdateUserRoleMutation } from '@/composables/mutations/user'
import { useRolesQuery } from '@/composables/queries/roles'
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
  allRoles: {
    type: Array,
    required: false,
    default: () => [],
  },
})

const isCurrentUser = computed(() => {
  return props.user.id === currentUser.value.id
})

const { data: roles } = useRolesQuery({
  config: {
    initialData: props.allRoles,
  },
})

const queryClient = useQueryClient()

const { mutate: mutateUserRole, isPending: isUpdatingRole } =
  useUpdateUserRoleMutation({
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [`users`] })
    },
    onError: (error) => {
      console.error(`Failed to update role:`, error)
    },
  })

const handleRoleChange = async (role) => {
  try {
    await mutateUserRole({
      userId: props.user.id,
      formData: { role: role },
    })
  } catch (error) {
    console.error(`Failed to update role`, error)
  }
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        :dusk="`user-role-trigger-${user.id}`"
        variant="text"
        :disabled="isCurrentUser || isUpdatingRole"
        size="sm"
      >
        <FontAwesomeIcon class="mr-2" :icon="faUserShield" fixed-width />

        {{ user.roles[0]?.name }}
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Roles</DropdownMenuLabel>

      <DropdownMenuSeparator />

      <DropdownMenuItem
        v-for="role in roles"
        :key="role.id"
        @click="() => handleRoleChange(role)"
      >
        <span
          :dusk="`user-role-option-${role.name.toLowerCase().replace(/\s+/g, '-')}`"
        >
          {{ role.name }}
        </span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
