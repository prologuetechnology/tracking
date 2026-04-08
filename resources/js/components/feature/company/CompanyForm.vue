<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import { computed, ref, watch } from 'vue'
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
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { useToast } from '@/components/ui/toast'
import {
  useCompanyCreateMutation,
  useCompanyUpdateMutation,
} from '@/composables/mutations/company'
import { useCompaniesQuery } from '@/composables/queries/company'

const props = defineProps({
  company: {
    type: Object,
    required: false,
    default: null,
  },
  heading: {
    type: String,
    default: `Company Information`,
  },
})

const queryClient = useQueryClient()
const siblingBrandAssignments = ref({})

const { data: companies } = useCompaniesQuery()

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

const {
  isFieldDirty,
  handleSubmit,
  resetForm,
  setErrors,
  setFieldValue,
  values,
} = useForm({
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

const pipelineCompanyId = computed(() => {
  const numericPipelineCompanyId = Number(values.pipeline_company_id)

  return Number.isFinite(numericPipelineCompanyId) &&
    numericPipelineCompanyId > 0
    ? numericPipelineCompanyId
    : null
})

const activePipelineSiblings = computed(() => {
  if (!pipelineCompanyId.value) {
    return []
  }

  return (companies.value ?? []).filter((company) => {
    return (
      company.is_active &&
      company.pipeline_company_id === pipelineCompanyId.value &&
      company.id !== props.company?.id
    )
  })
})

const isSharedPipelineCompanyId = computed(
  () => activePipelineSiblings.value.length > 0,
)

const siblingsMissingBrand = computed(() =>
  activePipelineSiblings.value.filter((company) => !company.brand),
)

const siblingBrandAssignmentsAreComplete = computed(() => {
  return siblingsMissingBrand.value.every((company) =>
    Boolean(`${siblingBrandAssignments.value[company.id] ?? ``}`.trim()),
  )
})

const buildSiblingBrandAssignments = () => {
  if (!isSharedPipelineCompanyId.value) {
    return []
  }

  return siblingsMissingBrand.value.map((company) => ({
    company_id: company.id,
    brand: siblingBrandAssignments.value[company.id] ?? ``,
  }))
}

const firstServerError = (errors) => {
  return Object.values(errors ?? {}).flat()[0] ?? `Please fix the form errors.`
}

const applyServerValidationErrors = (error) => {
  const errors = error.response?.data?.errors

  if (errors) {
    setErrors(
      Object.fromEntries(
        Object.entries(errors).map(([field, messages]) => [
          field,
          Array.isArray(messages) ? messages[0] : messages,
        ]),
      ),
    )
  }

  toast({
    title: `Could not save company`,
    description: firstServerError(errors),
    variant: `destructive`,
    duration: 5000,
  })
}

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

        router.visit(route(`admin.companies.show`, data.uuid))
      },
      onError: applyServerValidationErrors,
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
      onError: applyServerValidationErrors,
    },
  })

const formIsPending = computed(
  () => createCompanyIsPending.value || updateCompanyIsPending.value,
)

const saveIsDisabled = computed(() => {
  return (
    formIsPending.value ||
    !isFormDirty.value ||
    (isSharedPipelineCompanyId.value &&
      !siblingBrandAssignmentsAreComplete.value)
  )
})

const onValidForm = (values) => {
  const formData = {
    ...values,
    requires_brand: isSharedPipelineCompanyId.value
      ? true
      : values.requires_brand,
    sibling_brand_assignments: buildSiblingBrandAssignments(),
  }

  if (props.company) {
    updateCompany({
      id: props.company.id,
      formData,
    })
  } else {
    createCompany({
      formData,
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

watch(
  isSharedPipelineCompanyId,
  (isShared) => {
    if (isShared) {
      setFieldValue(`requires_brand`, true)
    }
  },
  { immediate: true },
)

watch(
  siblingsMissingBrand,
  (siblings) => {
    const nextAssignments = {}

    siblings.forEach((company) => {
      nextAssignments[company.id] =
        siblingBrandAssignments.value[company.id] ?? ``
    })

    siblingBrandAssignments.value = nextAssignments
  },
  { immediate: true },
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
        dusk="company-form"
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
                dusk="company-name"
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
                dusk="company-pipeline-id"
                type="number"
                placeholder="123"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription>
              The ID of the company in Pipeline.
            </FormDescription>
            <FormMessage />
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
                dusk="company-website"
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
                dusk="company-phone"
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
                dusk="company-email"
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
              <p
                v-if="isSharedPipelineCompanyId"
                class="text-sm text-muted-foreground"
              >
                This is required because another active company already uses
                this Pipeline Company ID.
              </p>
            </div>

            <FormControl>
              <Switch
                :checked="value || isSharedPipelineCompanyId"
                :disabled="isSharedPipelineCompanyId"
                @update:checked="handleChange"
              />
            </FormControl>
          </FormItem>
        </FormField>

        <FormField
          v-if="values.requires_brand || isSharedPipelineCompanyId"
          v-slot="{ componentField }"
          name="brand"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Brand</FormLabel>

            <FormControl>
              <Input
                dusk="company-brand"
                type="text"
                placeholder="acme_corp"
                v-bind="componentField"
                :disabled="createCompanyIsPending || updateCompanyIsPending"
              />
            </FormControl>

            <FormDescription> The company's brand string. </FormDescription>
            <FormMessage />
          </FormItem>
        </FormField>

        <div
          v-if="isSharedPipelineCompanyId"
          class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950"
        >
          <h3 class="text-base font-semibold">Shared Pipeline Company ID</h3>

          <p class="mt-1 text-sm">
            This Pipeline Company ID is already used by another active company.
            All active companies in this group will require unique brand query
            parameters before tracking can resolve them.
          </p>

          <div class="mt-4 space-y-4">
            <div
              v-for="sibling in activePipelineSiblings"
              :key="sibling.id"
              class="rounded-md border border-amber-200 bg-background p-3"
            >
              <div
                class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
              >
                <div>
                  <p class="font-medium">{{ sibling.name }}</p>
                  <p class="text-xs text-muted-foreground">
                    Current brand:
                    <span class="font-semibold">
                      {{ sibling.brand || `Not set` }}
                    </span>
                  </p>
                </div>
              </div>

              <div v-if="!sibling.brand" class="mt-3">
                <Label :for="`sibling-brand-${sibling.id}`">
                  Brand for {{ sibling.name }}
                </Label>

                <Input
                  :id="`sibling-brand-${sibling.id}`"
                  v-model="siblingBrandAssignments[sibling.id]"
                  class="mt-1"
                  type="text"
                  placeholder="ACME"
                  :disabled="formIsPending"
                />

                <p class="mt-1 text-xs text-muted-foreground">
                  Required to convert this shared Pipeline Company ID group.
                </p>
              </div>
            </div>
          </div>
        </div>

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
            dusk="company-form-save"
            :disabled="saveIsDisabled"
            @click="submitForm"
          >
            Save
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
