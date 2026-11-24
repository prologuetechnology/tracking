<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { Menu } from 'lucide-vue-next'
import { ref } from 'vue'

import UserDropdown from '@/components/feature/user/navigation/UserDropdown.vue'
import StaffFooter from '@/components/layout/footer/StaffFooter.vue'
import SignalAccessNavigation from '@/components/layout/navigation/SignalAccessNavigation.vue'
import SignalNavigation from '@/components/layout/navigation/SignalNavigation.vue'
import StaffAppearanceSelect from '@/components/layout/navigation/StaffAppearanceSelect.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Toaster } from '@/components/ui/toast'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'

defineProps({
  company: { type: Object, default: null },
  contentId: { type: String, default: `signal-workspace` },
})
const page = usePage()
const open = ref(false)
const { userIs } = useRolesAndPermissions()
const { mutate: stop } = useImpersonateUserStopMutation({
  config: {
    onSuccess: () => router.visit(route(`home`), { preserveState: false }),
  },
})
</script>
<template>
  <div class="signal-shell">
    <Toaster />
    <a
      :href="'#' + contentId"
      class="sr-only rounded bg-card px-4 py-2 text-foreground focus:not-sr-only"
      >Skip to workspace</a
    >
    <header class="signal-mast">
      <Link
        :href="route('admin.tracking.index')"
        class="signal-wordmark"
        aria-label="Prologue shipment lookup"
        ><span class="signal-mark" aria-hidden="true"><i /><i /><i /></span
        ><span>Prologue<small>Control room</small></span></Link
      >
      <div class="signal-desktop"><SignalNavigation :company="company" /></div>
      <div class="signal-account">
        <div class="signal-desktop"><StaffAppearanceSelect /></div>
        <Dialog v-model:open="open"
          ><DialogTrigger as-child
            ><Button
              size="sm"
              variant="outline"
              class="signal-mobile"
              aria-label="Open staff navigation"
              ><Menu :size="16" /> Sections</Button
            ></DialogTrigger
          >
          <DialogContent class="max-h-[90dvh] max-w-sm overflow-y-auto"
            ><DialogTitle>Staff navigation</DialogTitle
            ><DialogDescription class="sr-only"
              >Choose a workspace or appearance.</DialogDescription
            ><SignalNavigation
              :company="company"
              @navigate="
                open = false
              " /><StaffAppearanceSelect /></DialogContent
        ></Dialog>
        <UserDropdown />
      </div>
    </header>
    <div v-if="page.props.is_impersonating" class="signal-acting" role="status">
      Acting as {{ page.props.auth.user.email }}
      <Button
        type="button"
        variant="outline"
        size="sm"
        aria-label="Stop staff impersonation"
        @click="stop()"
        >Stop impersonation</Button
      >
    </div>
    <main :id="contentId" class="signal-main" tabindex="-1">
      <SignalAccessNavigation
        v-if="
          userIs(`Super Admin`) &&
          /^admin\/(users|role|permissions|allowedDomains)\//.test(
            page.component,
          )
        "
      /><slot />
    </main>
    <StaffFooter />
  </div>
</template>
