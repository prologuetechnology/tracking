<script setup>
import { faEdit } from '@fortawesome/pro-duotone-svg-icons'
import { faCircle } from '@fortawesome/pro-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useQueryClient } from '@tanstack/vue-query'
import { VisuallyHidden } from 'radix-vue'
import { useForm } from 'vee-validate'
import { ref, watch } from 'vue'
import * as yup from 'yup'

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
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompanyUpdateMutation } from '@/composables/mutations/company'
import { useImagesQuery } from '@/composables/queries/image'
import { useThemesQuery } from '@/composables/queries/theme'
import { hasCompanyFeature } from '@/composables/helpers/useCompanyFeatures'

import PermissionDestroyDialog from './PermissionDestroyDialog.vue'
import PermissionSetImageAsset from './PermissionSetImageAsset.vue'

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
})

const isOpen = ref(false)

const queryClient = useQueryClient()

const { data: themes } = useThemesQuery()

const { data: images } = useImagesQuery({
  imageType: `logos`,
  imageTypeId: 1,
})

const updateCompanyFormSchema = yup.object({
  name: yup.string().min(1).required(),
  pipeline_company_id: yup.number().min(1).required(),
  logo_image_id: yup.number().nullable(),
  website: yup.string().nullable(),
  phone: yup.string().nullable(),
  email: yup.string().nullable(),
  theme_id: yup.number().required(),
  enable_map: yup.boolean().required(),
})

const { isFieldDirty, handleSubmit, resetForm } = useForm({
  validationSchema: updateCompanyFormSchema,
  initialValues: {
    name: props.company.name,
    pipeline_company_id: props.company.pipeline_company_id,
    website: props.company.website,
    phone: props.company.phone,
    email: props.company.email,
    theme_id: `${props.company.theme_id}`,
    enable_map: hasCompanyFeature(props.company, `enable_map`),
    logo_image_id: props.company.logo?.id ? `${props.company.logo?.id}` : null,
  },
  keepValuesOnUnmount: true,
})

const { mutate: updateCompany } = useCompanyUpdateMutation({
  config: {
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: [`companies`],
      })

      isOpen.value = false
    },
  },
})

const onValidForm = (values) => {
  updateCompany({ companyId: props.company.id, formData: values })
}

const onInvalidForm = ({ values, errors, results }) => {
  console.log(values)
  console.log(errors)
  console.log(results)
}

const submitForm = () => {
  handleSubmit(onValidForm, onInvalidForm)()
}

watch(
  () => props.company, // Use a getter function to watch the company prop
  (newCompany) => {
    if (newCompany) {
      resetForm({
        values: {
          name: newCompany.name,
          pipeline_company_id: newCompany.pipeline_company_id,
          website: newCompany.website,
          phone: newCompany.phone,
          email: newCompany.email,
          theme_id: `${newCompany.theme_id}`,
          enable_map: hasCompanyFeature(newCompany, `enable_map`),
          logo_image_id: `${newCompany.logo?.id}`,
        },
      })
    }
  },
)

const cancelDialog = () => {
  isOpen.value = false
  resetForm({
    values: {
      name: props.company.name,
      pipeline_company_id: props.company.pipeline_company_id,
      website: props.company.website,
      phone: props.company.phone,
      email: props.company.email,
      theme_id: `${props.company.theme_id}`,
      enable_map: hasCompanyFeature(props.company, `enable_map`),
      logo_image_id: `${props.company.logo?.id}`,
    },
  })
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="outline" size="sm">
        <FontAwesomeIcon :icon="faEdit" fixed-width />
      </Button>
    </DialogTrigger>

    <DialogContent class="max-h-[85dvh] grid-rows-[auto_minmax(0,1fr)_auto]">
      <VisuallyHidden as-child>
        <DialogDescription>
          A dialog to update {{ company.name }}.
        </DialogDescription>
      </VisuallyHidden>

      <DialogHeader>
        <DialogTitle>Edit {{ company.name }}</DialogTitle>
      </DialogHeader>

      <form
        :id="`editCompanyForm_${company.uuid}`"
        class="flex w-full flex-col space-y-4 overflow-y-auto px-2"
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
              <Input type="number" placeholder="123" v-bind="componentField" />
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
              />
            </FormControl>

            <FormDescription> The company's email address. </FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="theme_id"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Theme</FormLabel>

            <Select v-bind="componentField">
              <FormControl>
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Select a theme" />
                </SelectTrigger>
              </FormControl>

              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Themes</SelectLabel>

                  <SelectItem
                    v-for="theme in themes"
                    :key="theme.uuid"
                    :value="`${theme.id}`"
                  >
                    <div class="flex flex-row items-center justify-between">
                      <div
                        class="mr-2 flex flex-row items-center justify-center"
                      >
                        <FontAwesomeIcon
                          :icon="faCircle"
                          class="z-10 text-lg"
                          fixed-width
                          :style="{
                            color: `hsl(${theme.colors.root.primary})`,
                          }"
                        />

                        <FontAwesomeIcon
                          :icon="faCircle"
                          class="-ml-3 text-lg"
                          fixed-width
                          :style="{
                            color: `hsl(${theme.colors.root.accent})`,
                          }"
                        />
                      </div>

                      <span>{{ theme.name }}</span>
                    </div>
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>

            <FormDescription> The company's theme. </FormDescription>
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="enable_map">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4"
          >
            <div class="space-y-0.5">
              <FormLabel class="text-base">Enable Map</FormLabel>

              <FormDescription>
                Enable the tracking/route map.
              </FormDescription>
            </div>

            <FormControl>
              <Switch :checked="value" @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="logo_image_id"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Logo</FormLabel>

            <div class="flex flex-row items-center justify-start space-x-2">
              <Select v-bind="componentField">
                <FormControl>
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select a logo" />
                  </SelectTrigger>
                </FormControl>
                <SelectContent>
                  <SelectGroup>
                    <SelectLabel>Logo</SelectLabel>

                    <SelectItem
                      v-for="image in images"
                      :key="image.uuid"
                      :value="`${image.id}`"
                    >
                      <div class="flex flex-row items-center justify-between">
                        <div
                          class="mr-2 flex flex-row items-center justify-center"
                        >
                          <div class="relative aspect-square w-8">
                            <img
                              :src="
                                imageAssetUrl({ filePath: image?.file_path })
                              "
                              class="absolute left-0 top-0 block h-full w-full scale-90 transform object-contain transition-all duration-300 group-hover:scale-95"
                            />
                          </div>
                        </div>

                        <span>{{ image.name }}</span>
                      </div>
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>

              <PermissionSetImageAsset icon-only />
            </div>

            <FormDescription>The company's logo.</FormDescription>
          </FormItem>
        </FormField>
      </form>

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <div class="mr-auto">
          <PermissionDestroyDialog :company="company" />
        </div>

        <Button variant="secondary" size="sm" @click="cancelDialog">
          Cancel
        </Button>

        <Button
          variant="default"
          size="sm"
          type="button"
          class=""
          @click="submitForm"
        >
          Save
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
