<script setup>
import { faTrashAlt } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useQueryClient } from '@tanstack/vue-query'
import { computed, ref } from 'vue'

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
import { useToast } from '@/components/ui/toast'
import { useCompanyClearImageAssetMutation } from '@/composables/mutations/company'

const props = defineProps({
  companyId: {
    type: Number,
    required: true,
  },
  imageName: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
})

const isOpen = ref(false)
const queryClient = useQueryClient()
const { toast } = useToast()

const assetLabel = computed(
  () => props.type.charAt(0).toUpperCase() + props.type.slice(1),
)

const invalidateCompanyQueries = async () => {
  await queryClient.invalidateQueries({
    queryKey: [`companies`, props.companyId],
    exact: true,
  })

  await queryClient.invalidateQueries({
    queryKey: [`companies`],
    exact: true,
  })

  await queryClient.invalidateQueries({
    queryKey: [`images`],
  })
}

const { mutate: clearImageAsset, isPending } =
  useCompanyClearImageAssetMutation({
    config: {
      onSuccess: async () => {
        await invalidateCompanyQueries()

        toast({
          title: `${assetLabel.value} Removed`,
          description: `${props.imageName} is still available in the shared image library.`,
        })

        isOpen.value = false
      },
    },
  })

const handleClear = () => {
  clearImageAsset({
    companyId: props.companyId,
    type: props.type,
  })
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button
        :dusk="`company-image-clear-${type}`"
        variant="destructive"
        size="sm"
      >
        <slot>
          <FontAwesomeIcon :icon="faTrashAlt" fixed-width />
        </slot>
      </Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Remove {{ assetLabel }}</DialogTitle>

        <DialogDescription>
          Remove <span class="font-semibold">{{ imageName }}</span> from this
          company’s {{ type }} slot. The image will remain in the shared library
          for other companies.
        </DialogDescription>
      </DialogHeader>

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button
          variant="secondary"
          size="sm"
          :disabled="isPending"
          @click="isOpen = false"
        >
          Cancel
        </Button>

        <Button
          :dusk="`company-image-clear-confirm-${type}`"
          size="sm"
          variant="destructive"
          :disabled="isPending"
          @click="handleClear"
        >
          Remove
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
