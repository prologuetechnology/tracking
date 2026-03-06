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
import { useToast } from '@/components/ui/toast'
import { useAllowedDomainDestroyMutation } from '@/composables/mutations/allowedDomain'

const props = defineProps({
  allowedDomain: {
    type: Object,
    required: true,
  },
})

const queryClient = useQueryClient()

const { toast } = useToast()

const isOpen = ref(false)

const closeDialog = () => {
  isOpen.value = false
}

const { mutate: deleteAllowedDomain } = useAllowedDomainDestroyMutation({
  config: {
    onSuccess: async () => {
      await queryClient
        .invalidateQueries({ queryKey: [`allowedDomains`] })
        .then(() => {
          closeDialog()
        })

      toast({
        title: `Allowed Domain Deleted`,
        description: `The allowed domain ${props.allowedDomain.domain} has been successfully deleted.`,
      })
    },
  },
})

const handleDelete = () => {
  deleteAllowedDomain(props.allowedDomain.id)
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="destructive" size="sm">
        <FontAwesomeIcon :icon="faTrashAlt" fixed-width />
      </Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete Allowed Domain</DialogTitle>

        <DialogDescription>
          Are you sure you want to delete the allowed domain
          <span class="font-mono font-semibold">{{ allowedDomain.domain }}</span
          >? This action cannot be undone.
        </DialogDescription>
      </DialogHeader>

      <DialogFooter>
        <Button variant="secondary" @click="closeDialog">Cancel</Button>

        <Button variant="destructive" @click="handleDelete">Delete</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
