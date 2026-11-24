<script setup>
import { faSignOut, faStop } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router, usePage } from '@inertiajs/vue3'

import StaffAppearanceMenu from '@/components/feature/user/navigation/StaffAppearanceMenu.vue'
import Button from '@/components/ui/button/Button.vue'
import DropdownMenu from '@/components/ui/dropdown-menu/DropdownMenu.vue'
import DropdownMenuContent from '@/components/ui/dropdown-menu/DropdownMenuContent.vue'
import DropdownMenuGroup from '@/components/ui/dropdown-menu/DropdownMenuGroup.vue'
import DropdownMenuItem from '@/components/ui/dropdown-menu/DropdownMenuItem.vue'
import DropdownMenuLabel from '@/components/ui/dropdown-menu/DropdownMenuLabel.vue'
import DropdownMenuSeparator from '@/components/ui/dropdown-menu/DropdownMenuSeparator.vue'
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/DropdownMenuTrigger.vue'
import {
  useIsCurrentlyImpersonating,
  useLogout,
} from '@/composables/hooks/auth'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'

const { user } = usePage().props.auth

const initials =
  [user.first_name, user.last_name]
    .filter(Boolean)
    .map((part) => part[0])
    .join(``) ||
  user.email?.[0] ||
  `U`
const { logout } = useLogout()

const { isCurrentlyImpersonating } = useIsCurrentlyImpersonating()

const { mutate: mutateImpersonateUserStop } = useImpersonateUserStopMutation({
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
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        dusk="user-menu-trigger"
        aria-label="Staff account"
        class="size-9 rounded-full p-0 font-mono text-xs uppercase"
        variant="secondary"
      >
        <span aria-hidden="true">
          {{ initials }}
        </span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent
      class="w-72 max-w-[calc(100vw-9.5rem)] sm:max-w-none"
      align="end"
    >
      <DropdownMenuGroup>
        <DropdownMenuLabel class="flex flex-col">
          <span class="text-foreground">
            {{ user.first_name ?? `` }} {{ user.last_name ?? `` }}
          </span>
          <span class="break-all text-xs font-medium text-foreground/70">
            {{ user.email }}
          </span>
          <span
            v-if="user.roles[0]?.name"
            class="mt-2 text-xs font-semibold text-foreground"
          >
            {{ user.roles[0]?.name }}
          </span>
        </DropdownMenuLabel>
      </DropdownMenuGroup>
      <DropdownMenuSeparator />
      <StaffAppearanceMenu />
      <DropdownMenuSeparator v-if="isCurrentlyImpersonating" />
      <DropdownMenuItem
        v-if="isCurrentlyImpersonating"
        dusk="stop-impersonation"
        @click="mutateImpersonateUserStop"
      >
        <FontAwesomeIcon class="mr-2" :icon="faStop" fixed-width />
        <span> Stop Impersonation </span>
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem dusk="sign-out" @click="logout">
        <FontAwesomeIcon class="mr-2" :icon="faSignOut" fixed-width />
        <span> Sign Out </span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
