<script setup>
import {
  faBuildings,
  faMagnifyingGlassLocation,
} from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, usePage } from '@inertiajs/vue3'

import UserDropdown from '@/components/feature/user/navigation/UserDropdown.vue'
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu'
import { useRolesAndPermissions } from '@/composables/hooks/auth'

const { userCan } = useRolesAndPermissions()

const {
  auth: { user },
} = usePage().props
</script>

<template>
  <header
    class="sticky top-0 z-50 w-full border-b border-b-border/70 bg-background/70 backdrop-blur-lg"
  >
    <nav
      class="relative mx-auto flex h-16 w-full max-w-3xl flex-row items-center justify-center px-6 lg:px-0"
    >
      <NavigationMenu>
        <NavigationMenuList>
          <NavigationMenuItem v-if="userCan(`company:show`)">
            <NavigationMenuLink :class="navigationMenuTriggerStyle()" as-child>
              <Link :href="route(`admin.companies.index`)">
                <FontAwesomeIcon class="mr-2" :icon="faBuildings" fixed-width />

                Companies
              </Link>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink :class="navigationMenuTriggerStyle()" as-child>
              <Link :href="route(`admin.tracking.index`)">
                <FontAwesomeIcon
                  class="mr-2"
                  :icon="faMagnifyingGlassLocation"
                  fixed-width
                />

                Track
              </Link>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem
            v-if="userCan(`theme:show`) || userCan(`image:show`)"
          >
            <NavigationMenuTrigger> Manage </NavigationMenuTrigger>

            <NavigationMenuContent>
              <ul class="grid min-w-72 grid-cols-1 gap-3 p-4">
                <li v-if="userCan(`theme:show`)">
                  <NavigationMenuLink as-child>
                    <Link
                      :href="route(`admin.themes.index`)"
                      class="block select-none space-y-1 rounded-md p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                    >
                      <div class="text-sm font-medium leading-none">Themes</div>

                      <p
                        class="line-clamp-2 text-sm leading-snug text-muted-foreground"
                      >
                        Create and edit custom themes.
                      </p>
                    </Link>
                  </NavigationMenuLink>
                </li>

                <li v-if="userCan(`image:show`)">
                  <NavigationMenuLink as-child>
                    <Link
                      :href="route(`admin.image.index`)"
                      class="block select-none space-y-1 rounded-md p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                    >
                      <div class="text-sm font-medium leading-none">Images</div>

                      <p
                        class="line-clamp-2 text-sm leading-snug text-muted-foreground"
                      >
                        Manage image library.
                      </p>
                    </Link>
                  </NavigationMenuLink>
                </li>
              </ul>
            </NavigationMenuContent>
          </NavigationMenuItem>
        </NavigationMenuList>
      </NavigationMenu>

      <div class="ml-auto flex flex-row items-center justify-end space-x-4">
        <p
          class="whitespace-nowrap text-lg font-extrabold text-zinc-700 dark:text-zinc-200"
        >
          {{ $page.props.app.name }}
        </p>

        <UserDropdown v-if="user" />
      </div>
    </nav>
  </header>
</template>
