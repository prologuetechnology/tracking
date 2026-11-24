<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { ChevronRight, PanelLeft } from 'lucide-vue-next'
import { computed } from 'vue'

import StaffFooter from '@/components/layout/footer/StaffFooter.vue'
import TrackingStudioNavigation from '@/components/layout/navigation/TrackingStudioNavigation.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Toaster } from '@/components/ui/toast'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'

defineProps({ company: { type: Object, required: true } })
const page = usePage()
const staffName = computed(() => {
  const staff = page.props.auth.user
  return (
    [staff.first_name, staff.last_name].filter(Boolean).join(` `) || staff.email
  )
})
const { mutate: stopImpersonating } = useImpersonateUserStopMutation({
  config: {
    onSuccess: () => router.visit(route(`home`), { preserveState: false }),
  },
})
</script>

<template>
  <div
    class="studio-shell flex min-h-dvh flex-col bg-background text-foreground"
  >
    <Toaster />
    <a
      href="#studio-workspace"
      class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:p-3"
      >Skip to workspace</a
    >
    <div class="flex-1 lg:grid lg:grid-cols-[11.125rem_minmax(0,1fr)]">
      <aside
        class="sticky top-0 hidden h-[calc(100dvh-3rem)] min-h-0 flex-col overflow-y-auto bg-[#25252e] lg:col-start-1 lg:row-start-1 lg:flex"
      >
        <TrackingStudioNavigation :company="company" />
      </aside>
      <div class="min-w-0 lg:col-start-2 lg:row-start-1">
        <header
          class="flex min-h-12 items-center gap-2 border-b bg-card px-4 text-sm text-muted-foreground sm:px-6 xl:px-9"
        >
          <Dialog>
            <DialogTrigger as-child>
              <Button
                type="button"
                size="icon"
                variant="ghost"
                class="-ml-2 lg:hidden"
                aria-label="Open staff navigation"
                ><PanelLeft
                  class="size-5 shrink-0 stroke-[1.75]"
                  aria-hidden="true"
              /></Button>
            </DialogTrigger>
            <DialogContent
              class="left-0 top-0 flex h-dvh w-64 max-w-[85vw] translate-x-0 translate-y-0 flex-col gap-0 overflow-y-auto rounded-none border-none bg-[#25252e] p-0 text-white sm:rounded-none"
            >
              <DialogTitle class="sr-only">Staff navigation</DialogTitle>
              <DialogDescription class="sr-only"
                >Navigate the tracking workspace and your
                account.</DialogDescription
              >
              <TrackingStudioNavigation :company="company" />
            </DialogContent>
          </Dialog>
          <nav
            aria-label="Breadcrumb"
            class="flex min-w-0 items-center gap-1.5"
          >
            <Link
              :href="route('admin.dashboard')"
              class="hidden hover:underline sm:inline"
              >Workspace</Link
            >
            <ChevronRight
              aria-hidden="true"
              class="hidden size-3 shrink-0 sm:block"
            />
            <Link
              :href="route('admin.companies.index')"
              class="rounded-sm underline-offset-2 hover:underline focus-visible:outline-ring"
              >Companies</Link
            >
            <ChevronRight aria-hidden="true" class="size-3 shrink-0" />
            <Link
              :href="route('admin.companies.show', company.uuid)"
              class="truncate font-semibold text-foreground hover:underline"
              >{{ company.name }}</Link
            >
          </nav>
          <span class="ml-auto hidden max-w-[30%] truncate text-xs sm:block">{{
            staffName
          }}</span>
        </header>
        <div
          v-if="page.props.is_impersonating"
          class="flex flex-wrap items-center gap-3 border-b bg-amber-50 px-5 py-2 text-sm text-amber-950"
        >
          Acting as {{ staffName }}
          <Button
            type="button"
            size="sm"
            variant="outline"
            @click="stopImpersonating()"
            >Stop impersonation</Button
          >
        </div>
        <main
          id="studio-workspace"
          tabindex="-1"
          class="mx-auto w-full max-w-[105rem] px-4 pb-14 pt-5 sm:px-6 lg:pt-7 xl:px-9"
        >
          <slot />
        </main>
      </div>
    </div>
    <StaffFooter />
  </div>
</template>

<style scoped>
.studio-shell {
  color-scheme: light;
  --background: 60 6% 96%;
  --foreground: 235 10% 16%;
  --card: 0 0% 100%;
  --card-foreground: 235 10% 16%;
  --primary: 253 30% 34%;
  --primary-foreground: 0 0% 100%;
  --secondary: 243 10% 93%;
  --secondary-foreground: 248 14% 25%;
  --muted: 245 9% 94%;
  --muted-foreground: 235 6% 39%;
  --accent: 247 17% 92%;
  --accent-foreground: 248 18% 25%;
  --border: 240 4% 85%;
  --input: 240 4% 80%;
  --ring: 253 30% 34%;
}
</style>
