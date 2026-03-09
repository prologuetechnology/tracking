<script setup>
import { faPlus } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed, ref } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

import AllowedDomainForm from './AllowedDomainForm.vue'

const isOpen = ref(false)

const closeDialog = () => {
  isOpen.value = false
}

const allowedDomainForm = ref(null)

const allowedDomainFormIsPending = computed(
  () =>
    allowedDomainForm.value?.createAllowedDomainIsPending ||
    allowedDomainForm.value?.updateAllowedDomainIsPending,
)
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="default" size="sm" dusk="allowed-domain-open-create">
        <FontAwesomeIcon class="mr-2" :icon="faPlus" fixed-width />

        <span>Add Domain</span>
      </Button>
    </DialogTrigger>

    <DialogContent>
      <DialogHeader>
        <DialogTitle>Create Allowed Domain</DialogTitle>
      </DialogHeader>

      <AllowedDomainForm
        ref="allowedDomainForm"
        :allowed-domain="null"
        @is-success="closeDialog"
      />

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button
          variant="secondary"
          size="sm"
          :disabled="allowedDomainFormIsPending"
          @click="closeDialog"
        >
          Cancel
        </Button>

        <Button
          type="button"
          variant="default"
          size="sm"
          dusk="allowed-domain-submit-create"
          :disabled="
            allowedDomainFormIsPending || !allowedDomainForm?.isFormDirty
          "
          @click="allowedDomainForm?.submitForm"
          >Create</Button
        >
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
