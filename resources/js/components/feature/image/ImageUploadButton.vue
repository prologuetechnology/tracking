<script setup>
import { faUpload } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useQueryClient } from '@tanstack/vue-query'
import { VisuallyHidden } from 'radix-vue'
import { useForm } from 'vee-validate'
import { ref } from 'vue'
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
import { useToast } from '@/components/ui/toast'
import { useImageStoreMutation } from '@/composables/mutations/image'
import { useImageTypesQuery } from '@/composables/queries/imageType'

const props = defineProps({
  initialImageTypes: {
    type: Array,
    required: true,
  },
})

const isOpen = ref(false)
const queryClient = useQueryClient()
const { toast } = useToast()

const imageUploadSchema = yup.object({
  name: yup.string().required(`Image name is required`),
  image_type_id: yup.string().required(`Image type is required`),
  image: yup
    .mixed()
    .required(`Image file is required`)
    .test(
      `image-file`,
      `Image file is required`,
      (value) => value instanceof File,
    ),
})

const { data: imageTypes } = useImageTypesQuery({
  config: {
    initialData: props.initialImageTypes,
  },
})

const { handleSubmit, isFieldDirty, resetForm } = useForm({
  validationSchema: imageUploadSchema,
  initialValues: {
    name: ``,
    image_type_id: ``,
    image: null,
  },
})

const { mutate: storeImage, isPending } = useImageStoreMutation({
  config: {
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: [`images`],
      })

      resetForm()
      isOpen.value = false

      toast({
        title: `Image Uploaded`,
        description: `The image has been added to the library.`,
        duration: 5000,
      })
    },
    onError: (error) => {
      toast({
        title: `Upload Failed`,
        description:
          error.response?.data?.error ??
          `An unexpected error occurred while uploading the image.`,
        variant: `destructive`,
      })
    },
  },
})

const onValidSubmit = (values) => {
  const formData = new FormData()
  formData.append(`name`, values.name)
  formData.append(`image_type_id`, values.image_type_id)
  formData.append(`image`, values.image)

  storeImage({ formData })
}

const submitForm = () => {
  handleSubmit(onValidSubmit)()
}

const cancelDialog = () => {
  isOpen.value = false
  resetForm()
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button size="sm" dusk="image-upload-open">
        <FontAwesomeIcon class="mr-2" :icon="faUpload" fixed-width />
        Upload Image
      </Button>
    </DialogTrigger>

    <DialogContent class="max-h-[85dvh] grid-rows-[auto_minmax(0,1fr)_auto]">
      <DialogHeader>
        <DialogTitle>Upload Image</DialogTitle>

        <VisuallyHidden as-child>
          <DialogDescription
            >Upload an image to the shared image library.</DialogDescription
          >
        </VisuallyHidden>
      </DialogHeader>

      <form
        id="imageUploadForm"
        dusk="image-upload-form"
        class="flex w-full flex-col space-y-4 overflow-y-auto px-2"
        @submit.prevent="submitForm"
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
                dusk="image-upload-name"
                type="text"
                placeholder="ACME Logo"
                v-bind="componentField"
                :disabled="isPending"
              />
            </FormControl>

            <FormDescription>The display name for this image.</FormDescription>
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ componentField }"
          name="image_type_id"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Image Type</FormLabel>

            <Select v-bind="componentField">
              <FormControl>
                <SelectTrigger class="w-full" dusk="image-upload-type-trigger">
                  <SelectValue placeholder="Select an image type" />
                </SelectTrigger>
              </FormControl>

              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Image Types</SelectLabel>

                  <SelectItem
                    v-for="imageType in imageTypes"
                    :key="imageType.id"
                    :value="`${imageType.id}`"
                    :dusk="`image-upload-type-${imageType.name}`"
                  >
                    {{ imageType.name }}
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>

            <FormDescription
              >The image category used by the app.</FormDescription
            >
          </FormItem>
        </FormField>

        <FormField
          v-slot="{ handleChange }"
          name="image"
          :validate-on-blur="!isFieldDirty"
        >
          <FormItem>
            <FormLabel>Image File</FormLabel>

            <FormControl>
              <Input
                dusk="image-upload-file"
                accept="image/*"
                type="file"
                :disabled="isPending"
                @change="
                  (event) => handleChange(event.target.files?.[0] ?? null)
                "
              />
            </FormControl>

            <FormDescription>PNG, JPG, JPEG, or SVG up to 2MB.</FormDescription>
          </FormItem>
        </FormField>
      </form>

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button
          variant="secondary"
          size="sm"
          :disabled="isPending"
          @click="cancelDialog"
        >
          Cancel
        </Button>

        <Button
          form="imageUploadForm"
          type="submit"
          size="sm"
          dusk="image-upload-save"
          :disabled="isPending"
        >
          Save
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
