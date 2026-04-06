<script setup>
import {
  faBadgeCheck,
  faTriangleExclamation,
} from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import * as yup from 'yup'

import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { useToast } from '@/components/ui/toast'
import { useCompanyApiTokenStoreMutation } from '@/composables/mutations/companyApiToken'
import { useCompanyApiTokenValidateQuery } from '@/composables/queries/companyApiToken'

import CompanyApiTokenDeleteDialog from './CompanyApiTokenDeleteDialog.vue'

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
})

const queryClient = useQueryClient()

const { toast } = useToast()

const apiTokenSchema = yup.object({
  api_token: yup.string().required(`API token is required`),
  trackingNumber: yup.string().required(`Bill of Lading is required`),
})

const { handleSubmit, resetForm, isFieldDirty } = useForm({
  validationSchema: apiTokenSchema,
  initialValues: {
    api_token: null,
    trackingNumber: null,
  },
  keepValuesOnUnmount: true,
})

const isFormDirty = useIsFormDirty()

const { mutate: storeCompanyApiToken, isPending: companyApiTokenIsPending } =
  useCompanyApiTokenStoreMutation({
    config: {
      onSuccess: async () => {
        await queryClient.invalidateQueries({ queryKey: [`companies`] })

        await queryClient.invalidateQueries({
          queryKey: [`companyApiToken`, props.company?.id, `validate`],
        })

        resetForm()

        toast({
          title: `API Token Saved`,
          description: `The API token has been successfully saved.`,
          duration: 5000,
        })
      },

      onError: async (error) => {
        toast({
          title: `Error Saving API Token`,
          description:
            error.response.data.error ||
            `An error occurred while saving the API token.`,
          variant: `destructive`,
        })
      },
    },
  })

const { data: tokenIsValid } = useCompanyApiTokenValidateQuery({
  companyId: props.company?.id,

  config: {
    initialData: props.company?.api_token?.is_valid,

    enabled: !!props.company?.api_token?.api_token,
  },
})

const onValidSubmit = (values) => {
  storeCompanyApiToken({
    companyId: props.company?.id,
    apiToken: values.api_token,
    trackingNumber: values.trackingNumber,
  })
}

const onInvalidSubmit = ({ errors }) => {
  toast({
    title: `Validation Error`,
    description:
      errors.api_token ||
      errors.trackingNumber ||
      `Please fix the form errors.`,
    variant: `destructive`,
  })
}

const submitForm = () => {
  handleSubmit(onValidSubmit, onInvalidSubmit)()
}
</script>

<template>
  <Card class="mt-8 w-full">
    <CardHeader>
      <CardTitle>Pipeline Authentication</CardTitle>
    </CardHeader>

    <CardContent v-if="company?.api_token?.api_token">
      <div
        class="relative flex w-full flex-row items-center justify-start space-x-2"
      >
        <p class="w-full">
          <span class="font-mono text-sm">
            {{ company?.api_token?.api_token }}
          </span>
        </p>

        <FontAwesomeIcon
          v-if="tokenIsValid"
          class="text-green-600"
          :icon="faBadgeCheck"
          fixed-width
        />

        <FontAwesomeIcon
          v-else
          class="text-red-600"
          :icon="faTriangleExclamation"
          fixed-width
        />
      </div>
    </CardContent>

    <CardContent v-else>
      <form
        id="companyApiTokenForm"
        class="mt-4 flex w-full flex-col space-y-4"
        @submit.prevent="submitForm"
      >
        <FormField
          v-slot="{ componentField }"
          name="api_token"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>API Token</FormLabel>

            <FormControl>
              <Input
                type="text"
                placeholder="Enter API token"
                v-bind="componentField"
                :disabled="companyApiTokenIsPending"
              />
            </FormControl>

            <FormDescription>
              API token associated with
              <strong> {{ company.name }} </strong>.

              <br />

              Pipeline company ID:
              <strong> {{ company.pipeline_company_id }} </strong>.
            </FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="trackingNumber"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Bill of Lading</FormLabel>

            <FormControl>
              <Input
                type="text"
                placeholder="Bill of Lading"
                v-bind="componentField"
                :disabled="companyApiTokenIsPending"
              />
            </FormControl>

            <FormDescription>
              Bill of Lading associated with
              <strong> {{ company.name }} </strong>.
            </FormDescription>
          </FormItem>
        </FormField>
      </form>
    </CardContent>

    <CardFooter>
      <div class="flex w-full justify-end space-x-2 pt-4">
        <CompanyApiTokenDeleteDialog
          v-if="company?.api_token?.api_token"
          :company="company"
        />

        <Button
          v-else
          variant="default"
          size="sm"
          type="submit"
          form="companyApiTokenForm"
          :disabled="companyApiTokenIsPending || !isFormDirty"
        >
          Save API Token
        </Button>
      </div>
    </CardFooter>
  </Card>
</template>
