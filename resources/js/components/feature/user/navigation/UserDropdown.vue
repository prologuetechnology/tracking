<script setup>
import {
  faBars,
  faGlobePointer,
  faShieldKeyhole,
  faSignOut,
  faStop,
  faUserGear,
} from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, router, usePage } from '@inertiajs/vue3'

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
  useRolesAndPermissions,
} from '@/composables/hooks/auth'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'

const { user } = usePage().props.auth

const { logout } = useLogout()

const { userIs } = useRolesAndPermissions()

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
    <DropdownMenuTrigger>
      <Button
        dusk="user-menu-trigger"
        class="flex flex-row items-center justify-center space-x-1"
        variant="outline"
        size="xs"
      >
        <FontAwesomeIcon class="text-sm" :icon="faBars" fixed-width />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent class="w-72" align="end">
      <DropdownMenuGroup>
        <DropdownMenuLabel class="flex flex-col">
          <span class="text-foreground">
            {{ user.first_name ?? `` }} {{ user.last_name ?? `` }}
          </span>

          <span class="text-xs font-medium text-foreground/70">
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

      <DropdownMenuSeparator v-if="userIs(`Super Admin`)" />

      <DropdownMenuGroup v-if="userIs(`Super Admin`)">
        <DropdownMenuLabel>Administration</DropdownMenuLabel>

        <DropdownMenuItem>
          <Link :href="route(`admin.allowed-domains.index`)" class="w-full">
            <span dusk="nav-allowed-domains-link" class="hidden" />
            <FontAwesomeIcon class="mr-2" :icon="faGlobePointer" fixed-width />
            <span>Allowed Domains</span>
          </Link>
        </DropdownMenuItem>

        <DropdownMenuItem>
          <Link :href="route(`admin.users.index`)" class="w-full">
            <span dusk="nav-users-link" class="hidden" />
            <FontAwesomeIcon class="mr-2" :icon="faUserGear" fixed-width />
            <span>Users</span>
          </Link>
        </DropdownMenuItem>

        <DropdownMenuItem>
          <Link :href="route(`admin.role.index`)" class="w-full">
            <span dusk="nav-roles-link" class="hidden" />
            <FontAwesomeIcon class="mr-2" :icon="faShieldKeyhole" fixed-width />
            <span>Roles</span>
          </Link>
        </DropdownMenuItem>
      </DropdownMenuGroup>

      <DropdownMenuSeparator v-if="isCurrentlyImpersonating" />

      <DropdownMenuItem
        v-if="isCurrentlyImpersonating"
        dusk="stop-impersonation"
        @click="mutateImpersonateUserStop"
      >
        <FontAwesomeIcon class="mr-2" :icon="faStop" fixed-width />

        <span>Stop Impersonation</span>
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuItem dusk="sign-out" @click="logout">
        <FontAwesomeIcon class="mr-2" :icon="faSignOut" fixed-width />

        <span>Sign out</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
