<script setup>
import { faTrashAlt } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useThemeDestroyMutation } from '@/composables/mutations/theme'

const { userCan } = useRolesAndPermissions()

const props = defineProps({
  theme: {
    type: Object,
    required: true,
  },
})

const isOpen = ref(false)

const queryClient = useQueryClient()

const {
  mutate: destroyTheme,
  error,
  isPending,
  reset,
} = useThemeDestroyMutation({
  config: {
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: [`themes`],
      })

      router.visit(route(`admin.studio.styles.index`))
    },
  },
})

const handleDelete = () => {
  destroyTheme({ id: props.theme.id })
}

const cancelDialog = () => {
  reset()
  isOpen.value = false
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <template v-if="userCan(`theme:destroy`)">
      <DialogTrigger as-child>
        <Button variant="destructive" size="sm">
          <FontAwesomeIcon class="mr-2" :icon="faTrashAlt" fixed-width />

          <span>Delete</span>
        </Button>
      </DialogTrigger>
    </template>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete {{ theme.name }}</DialogTitle>

        <DialogDescription>
          Remove this style from the library? A style used by a company or saved
          revision must remain available.
        </DialogDescription>
      </DialogHeader>

      <p v-if="error" role="alert" class="text-sm text-destructive">
        {{
          error.response?.data?.errors?.theme?.[0] ??
          error.response?.data?.message ??
          'The style could not be deleted.'
        }}
      </p>
      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button variant="secondary" size="sm" @click="cancelDialog">
          Cancel
        </Button>

        <Button
          size="sm"
          type="button"
          variant="destructive"
          :disabled="isPending"
          @click="handleDelete"
        >
          Delete
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
