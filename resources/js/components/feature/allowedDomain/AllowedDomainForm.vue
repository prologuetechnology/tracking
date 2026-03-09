<script setup>
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import { watch } from 'vue'
import * as yup from 'yup'

import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { useToast } from '@/components/ui/toast'
import {
  useAllowedDomainStoreMutation,
  useAllowedDomainUpdateMutation,
} from '@/composables/mutations/allowedDomain'

const props = defineProps({
  allowedDomain: {
    type: Object,
    default: null,
  },
  heading: {
    type: String,
    default: `Allowed Domain Information`,
  },
})

const emit = defineEmits([`isSuccess`])

const queryClient = useQueryClient()

const allowedDomainFormSchema = yup.object({
  domain: yup
    .string()
    .matches(
      /^(?!:\/\/)([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/,
      `Enter a valid domain like example.com`,
    )
    .required(),
  is_active: yup.boolean(),
})

const { isFieldDirty, handleSubmit, resetForm } = useForm({
  validationSchema: allowedDomainFormSchema,
  initialValues: {
    domain: props.allowedDomain?.domain,
    is_active: props.allowedDomain?.is_active ?? true,
  },
  keepValuesOnUnmount: true,
})

const isFormDirty = useIsFormDirty()

const { toast } = useToast()

const { mutate: createAllowedDomain, isPending: createAllowedDomainIsPending } =
  useAllowedDomainStoreMutation({
    config: {
      onSuccess: async (data) => {
        await queryClient.invalidateQueries({ queryKey: [`allowedDomains`] })

        toast({
          title: `Allowed Domain Created`,
          description: `The allowed domain ${data.domain} has been successfully created.`,
        })

        resetForm()

        emit(`isSuccess`)
      },
      onError: (error) => {
        toast({
          variant: `destructive`,
          title: `Error creating allowed domain`,
          description: error.message,
        })
      },
    },
  })

const { mutate: updateAllowedDomain, isPending: updateAllowedDomainIsPending } =
  useAllowedDomainUpdateMutation({
    config: {
      onSuccess: async (data) => {
        await queryClient.invalidateQueries({ queryKey: [`allowedDomains`] })

        toast({
          title: `${data.domain} Updated`,
          description: `The allowed domain ${data.domain} has been successfully updated.`,
        })

        resetForm()

        emit(`isSuccess`)
      },
      onError: (error) => {
        toast({
          variant: `destructive`,
          title: `Error updating allowed domain`,
          description: error.message,
        })
      },
    },
  })

const onValidForm = (values) => {
  if (props.allowedDomain) {
    updateAllowedDomain({
      id: props.allowedDomain.id,
      formData: {
        ...values,
      },
    })
  } else {
    createAllowedDomain({
      formData: {
        ...values,
      },
    })
  }
}

const onInvalidForm = () => {}

const submitForm = () => {
  handleSubmit(onValidForm, onInvalidForm)()
}

watch(
  () => props.allowedDomain,
  (newAllowedDomain) => {
    if (newAllowedDomain) {
      resetForm({
        values: {
          domain: newAllowedDomain.domain,
          is_active: newAllowedDomain.is_active ?? true,
        },
      })
    }
  },
)

defineExpose({
  createAllowedDomainIsPending,
  updateAllowedDomainIsPending,
  isFormDirty,
  submitForm,
})
</script>

<template>
  <form
    id="allowedDomainForm"
    dusk="allowed-domain-form"
    class="mt-4 flex w-full flex-col space-y-4 rounded-lg border border-border p-4"
    @submit.prevent="submitForm"
  >
    <FormField
      v-slot="{ componentField }"
      name="domain"
      :validate-on-blur="!isFieldDirty"
    >
      <FormLabel>Domain</FormLabel>

      <FormControl>
        <Input
          dusk="allowed-domain-name"
          type="text"
          placeholder="acme.com"
          v-bind="componentField"
        />
      </FormControl>

      <FormDescription>
        <p>This domain will be allowed to access this application.</p>
      </FormDescription>
    </FormField>

    <FormField
      v-slot="{ value, handleChange }"
      name="is_active"
      :validate-on-blur="!isFieldDirty"
    >
      <FormItem
        class="flex flex-row items-center justify-between rounded-lg border p-4"
      >
        <div class="space-y-0.5">
          <FormLabel class="text-base">Active</FormLabel>

          <FormDescription>
            Toggle this switch to enable or disable the allowed domain.
          </FormDescription>
        </div>

        <FormControl>
          <Switch
            dusk="allowed-domain-active"
            :checked="value"
            @update:checked="handleChange"
          />
        </FormControl>
      </FormItem>
    </FormField>
  </form>
</template>
