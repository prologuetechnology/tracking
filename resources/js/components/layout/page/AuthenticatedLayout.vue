<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { VueQueryDevtools } from '@tanstack/vue-query-devtools'
import { h, onMounted } from 'vue'

import { Button } from '@/components/ui/button'
import { Toaster, useToast } from '@/components/ui/toast'
import { useIsCurrentlyImpersonating } from '@/composables/hooks/auth'
import { useImpersonateUserStopMutation } from '@/composables/mutations/user'
import { useUserQuery } from '@/composables/queries/user'

import AuthenticatedNavbar from '../navigation/AuthenticatedNavbar.vue'

const { isCurrentlyImpersonating } = useIsCurrentlyImpersonating()

defineProps({
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

  <div class="grid min-h-screen grid-rows-[auto,1fr] items-start">
    <AuthenticatedNavbar />

    <main class="mx-auto h-full w-full max-w-3xl px-6 pb-20 pt-12 lg:px-0">
      <div
        class="mb-4 flex flex-col items-stretch justify-start space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0"
      >
        <h1 v-if="title" class="mb-4 text-2xl font-semibold">{{ title }}</h1>

        <component :is="action" v-if="action" />
      </div>

      <slot />
    </main>
  </div>

  <VueQueryDevtools />
</template>
