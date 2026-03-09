<script setup>
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
import { useImageDestroyMutation } from '@/composables/mutations/image'

const props = defineProps({
  image: {
    type: Object,
    required: true,
  },
})

const isOpen = ref(false)

const queryClient = useQueryClient()

const { mutate: destroyImage } = useImageDestroyMutation({
  config: {
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: [`images`],
      })

      await queryClient.invalidateQueries({
        queryKey: [`companies`],
      })

      isOpen.value = false
    },
  },
})

const handleDelete = () => {
  destroyImage({ id: props.image.id })
}

const cancelDialog = () => {
  isOpen.value = false
}

const usageWarning = () => {
  if (!props.image.is_in_use) {
    return `Are you sure you want to delete this image?`
  }

  const companyCount = props.image.company_usage_count ?? 0

  return `This shared image is currently assigned to ${companyCount} compan${
    companyCount === 1 ? `y` : `ies`
  }. Deleting it will remove the image everywhere it is used.`
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button
        :dusk="`image-delete-open-${image.id}`"
        variant="destructive"
        size="sm"
      >
        <slot />
      </Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete {{ image.name }}</DialogTitle>

        <DialogDescription>
          {{ usageWarning() }}
        </DialogDescription>
      </DialogHeader>

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button variant="secondary" size="sm" @click="cancelDialog">
          Cancel
        </Button>

        <Button
          :dusk="`image-delete-confirm-${image.id}`"
          size="sm"
          type="button"
          variant="destructive"
          class=""
          @click="handleDelete"
        >
          Delete
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
