<script setup>
import { faTrashAlt } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
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
import { toast } from '@/components/ui/toast/index.js'
import { useCompanyApiTokenDestroyMutation } from '@/composables/mutations/companyApiToken'

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
})

const isOpen = ref(false)

const queryClient = useQueryClient()

const { mutate: destroyCompanyApiToken, isPending: isDeleting } =
  useCompanyApiTokenDestroyMutation({
    config: {
      onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: [`companies`] })

        toast({
          title: `API Token Deleted`,
          description: `The API token has been successfully deleted.`,
          duration: 5000,
        })

        isOpen.value = false
      },
      onError: async (error) => {
        toast({
          title: `Error Deleting API Token`,
          description:
            error.response?.data?.error ??
            `An error occurred while deleting the API token.`,
          duration: 5000,
        })
      },
    },
  })

const handleDeleteToken = () => {
  destroyCompanyApiToken({
    id: props.company.api_token.id,
  })
}

const cancelDialog = () => {
  isOpen.value = false
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="destructive" size="sm" class="w-full">
        <FontAwesomeIcon class="mr-2" :icon="faTrashAlt" fixed-width />

        <span>Delete API Token</span>
      </Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete API Token</DialogTitle>

        <DialogDescription>
          <p>
            Are you sure you want to delete the API token for
            <span class="font-semibold">{{ company.name }}</span
            >? This action cannot be undone and will remove the current token
            from the system.
          </p>
        </DialogDescription>
      </DialogHeader>

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
          :disabled="isDeleting"
          @click="handleDeleteToken"
        >
          Delete Token
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
