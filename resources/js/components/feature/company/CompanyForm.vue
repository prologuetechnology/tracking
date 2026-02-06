<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import { watch } from 'vue'
import * as yup from 'yup'

import CompanyDestroyDialog from '@/components/feature/company/CompanyDestroyDialog.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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
  useCompanyCreateMutation,
  useCompanyUpdateMutation,
} from '@/composables/mutations/company'

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
  heading: {
    type: String,
    default: `Company Information`,
  },
})

const queryClient = useQueryClient()

const companyFormSchema = yup.object({
  name: yup.string().min(1).required(),
  pipeline_company_id: yup.number().min(1).required(),
  logo_image_id: yup.number().nullable(),
  website: yup.string().nullable(),
  phone: yup.string().nullable(),
  email: yup.string().nullable(),
  requires_brand: yup.boolean(),
  brand: yup
    .string()
    .nullable()
    .when(`requires_brand`, {
      is: (value) => value === true,
      then: (schema) =>
        schema.required(`Brand is required when requiring a brand.`),
      otherwise: (schema) => schema.nullable(),
    }),
})

const { isFieldDirty, handleSubmit, resetForm, values } = useForm({
  validationSchema: companyFormSchema,
  initialValues: {
    name: props.company?.name,
    pipeline_company_id: props.company?.pipeline_company_id,
    website: props.company?.website,
    phone: props.company?.phone,
    email: props.company?.email,
    requires_brand: Boolean(props.company?.requires_brand),
    brand: props.company?.brand,
  },
  keepValuesOnUnmount: true,
})

const isFormDirty = useIsFormDirty()

const { toast } = useToast()

const { mutate: createCompany, isPending: createCompanyIsPending } =
  useCompanyCreateMutation({
    config: {
      onSuccess: async (data) => {
        resetForm()

        await queryClient.invalidateQueries({
          queryKey: [`companies`],
        })

        toast({
          title: `Created company: ${data.name}`,
          description: `The company has been created successfully.`,
          duration: 5000,
        })

        router.visit(route(`admin.company.show`, data.uuid))
      },
    },
  })

const { mutate: updateCompany, isPending: updateCompanyIsPending } =
  useCompanyUpdateMutation({
    config: {
      onSuccess: async (data) => {
        await queryClient.invalidateQueries({
          queryKey: [`companies`],
        })

        toast({
          title: `Updated company: ${data.name}`,
          description: `The company has been updated successfully.`,
          duration: 5000,
        })

        router.visit(route(`admin.companies.index`))
      },
    },
  })

const onValidForm = (values) => {
  if (props.company) {
    updateCompany({
      id: props.company.id,
      formData: {
        ...values,
      },
    })
  } else {
    createCompany({
      formData: {
        ...values,
      },
    })
  }
}

const onInvalidForm = ({ values, errors, results }) => {
  console.error({ values, errors, results })
}

const submitForm = () => {
  handleSubmit(onValidForm, onInvalidForm)()
}

watch(
  () => props.company,
  (newCompany) => {
    if (newCompany) {
      resetForm({
        values: {
          name: newCompany.name,
          pipeline_company_id: newCompany.pipeline_company_id,
          website: newCompany.website,
          phone: newCompany.phone,
          email: newCompany.email,
          logo_image_id: `${newCompany.logo?.id}`,
          requires_brand: Boolean(newCompany.requires_brand),
          brand: newCompany?.brand,
        },
      })
    }
  },
)
</script>

<template>
  <!-- <h2 class="mt-8 text-lg font-semibold text-foreground">
    {{ heading }}
  </h2> -->

  <Card class="mt-8 w-full">
    <CardHeader>
      <CardTitle>Company Details</CardTitle>
    </CardHeader>

    <CardContent>
      <form
        id="companyForm"
        class="flex w-full flex-col space-y-4"
        @submit="submitForm"
      >
        <FormField
          v-slot="{ componentField }"
          name="name"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Name</FormLabel>

            <FormControl>
              <Input
                type="text"
                placeholder="ACME Inc."
                v-bind="componentField"
              />
            </FormControl>

            <FormDescription>The name of the company.</FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="pipeline_company_id"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Pipeline Company ID</FormLabel>

            <FormControl>
              <Input
                type="number"
                placeholder="123"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription>
              The ID of the company in Pipeline.
            </FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="website"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Website</FormLabel>

            <FormControl>
              <Input
                type="text"
                placeholder="https://acme.com"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription> The company's website URL. </FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="phone"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Phone Number</FormLabel>

            <FormControl>
              <Input
                type="tel"
                placeholder="(123) 456-7890"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription> The company's phone number. </FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="email"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>E-mail</FormLabel>

            <FormControl>
              <Input
                type="email"
                placeholder="info@acme.com"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription> The company's email address. </FormDescription>
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="requires_brand">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4"
          >
            <div class="space-y-0.5">
              <FormLabel class="text-base">Requires Brand</FormLabel>

              <FormDescription>
                Require that the tracking URL contain a brand query parameter.
              </FormDescription>
            </div>

            <FormControl>
              <Switch :checked="value" @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </FormField>

        <FormField
          v-if="values.requires_brand"
          v-slot="{ componentField }"
          name="brand"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Brand</FormLabel>

            <FormControl>
              <Input
                type="text"
                placeholder="acme_corp"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription> The company's brand string. </FormDescription>
          </FormItem>
        </FormField>

        <!-- <hr class="" /> -->

        <div
          class="mx-auto flex w-full max-w-3xl flex-row items-center justify-end space-x-2 py-2"
        >
          <div v-if="company?.id" class="mr-auto">
            <CompanyDestroyDialog :company="company" />
          </div>

          <Button
            variant="secondary"
            size="sm"
            :disabled="createCompanyIsPending || updateCompanyIsPending"
          >
            <Link :href="route(`admin.companies.index`)">Cancel</Link>
          </Button>

          <Button
            variant="default"
            size="sm"
            type="button"
            class=""
            :disabled="
              createCompanyIsPending || updateCompanyIsPending || !isFormDirty
            "
            @click="submitForm"
          >
            Save
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
