<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { VueQueryDevtools } from '@tanstack/vue-query-devtools'
import { h, onMounted } from 'vue'

import { Button } from '@/components/ui/button'
import { Toaster, useToast } from '@/components/ui/toast'
import { useIsCurrentlyImpersonating } from '@/composables/hooks/auth'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'
import { useUserQuery } from '@/composables/queries/user'

import StaffFooter from '../footer/StaffFooter.vue'
import AuthenticatedNavbar from '../navigation/AuthenticatedNavbar.vue'

const { isCurrentlyImpersonating } = useIsCurrentlyImpersonating()

defineProps({
  wide: { type: Boolean, default: false },
  title: {
    type: String,
    required: false,
    default: null,
  },
  action: {
    type: [Function, Object],
    required: false,
    default: null,
  },
})

const { user: initialCurrentUser } = usePage().props.auth

const { data: currentUser } = useUserQuery({
  userId: initialCurrentUser.id,

  config: {
    initialData: initialCurrentUser,
  },
})

const { mutate: mutateImpersonateUserStop } = useImpersonateUserStopMutation({
  config: {
    onSuccess: () => {
      router.visit(route(`home`), {
        preserveState: false,
      })
    },
  },
})

const { toast, dismiss } = useToast()

onMounted(() => {
  const impersonationToast = toast({
    title: `Impersonation Active`,
    description: `You are now impersonating ${currentUser.value.email}.`,
    duration: 10000,
    open: false,
    action: h(
      Button,
      {
        variant: `outline`,
        size: `sm`,
        onClick: mutateImpersonateUserStop,
      },
      `Stop`,
    ),
  })

  if (isCurrentlyImpersonating) {
    impersonationToast.open = true
  } else {
    dismiss(impersonationToast.id)
  }
})
</script>

<template>
  <Toaster />

  <div class="grid min-h-dvh grid-rows-[auto,1fr,auto] items-start">
    <AuthenticatedNavbar :sticky="!wide" />

    <main
      class="mx-auto h-full w-full pb-20"
      :class="
        wide
          ? 'max-w-screen-2xl px-4 pt-5 sm:px-6 lg:px-8'
          : 'max-w-3xl px-6 pt-12 lg:px-0'
      "
    >
      <div
        v-if="!wide || title || action"
        class="mb-4 flex flex-col items-stretch justify-start space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0"
      >
        <h1 v-if="title" class="mb-4 text-2xl font-semibold">{{ title }}</h1>

        <component :is="action" v-if="action" />
      </div>

      <slot />
    </main>
    <StaffFooter />
  </div>

  <VueQueryDevtools />
</template>
