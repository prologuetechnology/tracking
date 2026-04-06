<script setup>
import { faImage, faUpload } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useQueryClient } from '@tanstack/vue-query'
import { VisuallyHidden } from 'radix-vue'
import { useForm } from 'vee-validate'
import { computed, ref, watch } from 'vue'
import * as yup from 'yup'

import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
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
import { ScrollArea } from '@/components/ui/scroll-area'
import { useToast } from '@/components/ui/toast'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompanySetImageAssetMutation } from '@/composables/mutations/company'
import { useImageStoreMutation } from '@/composables/mutations/image'
import { useImagesQuery } from '@/composables/queries/image'
import imageTypes from '@/lib/types/images'

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
  initialImageTypes: {
    type: Array,
    required: true,
  },
  type: {
    type: String,
    required: true,
    validator(value) {
      return Object.values(imageTypes).includes(value)
    },
  },
  iconOnly: {
    type: Boolean,
    default: false,
  },
})

const isOpen = ref(false)
const selectedImageId = ref(null)

const queryClient = useQueryClient()
const { toast } = useToast()

const uploadFormSchema = yup.object({
  name: yup.string().min(1).required(),
  image: yup
    .mixed()
    .required(`Image file is required`)
    .test(
      `image-file`,
      `Image file is required`,
      (value) => value instanceof File,
    ),
})

const currentAsset = computed(() => {
  switch (props.type) {
    case imageTypes.LOGO:
      return props.company.logo ?? null
    case imageTypes.BANNER:
      return props.company.banner ?? null
    case imageTypes.FOOTER:
      return props.company.footer ?? null
    default:
      return null
  }
})

const assetLabel = computed(
  () => props.type.charAt(0).toUpperCase() + props.type.slice(1),
)

const previewFrameClass = computed(() => {
  switch (props.type) {
    case imageTypes.BANNER:
      return `aspect-[16/6]`
    case imageTypes.FOOTER:
      return `aspect-[16/4]`
    default:
      return `aspect-square`
  }
})

const libraryGridClass = computed(() => {
  switch (props.type) {
    case imageTypes.BANNER:
    case imageTypes.FOOTER:
      return `grid grid-cols-1 gap-3`
    default:
      return `grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3`
  }
})

const currentImageTypeId = computed(() => {
  const imageType = (props.initialImageTypes ?? []).find(
    (item) => item.name === props.type,
  )

  return imageType?.id ?? null
})

const imageQueryConfig = computed(() => ({
  enabled: isOpen.value && currentImageTypeId.value !== null,
}))

const { data: libraryImages, isFetching: isLoadingLibrary } = useImagesQuery({
  imageTypeId: currentImageTypeId,
  config: imageQueryConfig,
})

const filteredLibraryImages = computed(() =>
  (libraryImages.value ?? []).filter(
    (image) => image.image_type?.name === props.type,
  ),
)

const { resetForm, isFieldDirty, handleSubmit } = useForm({
  validationSchema: uploadFormSchema,
  initialValues: {
    name: ``,
    image: null,
  },
})

const invalidateCompanyQueries = async () => {
  await queryClient.invalidateQueries({
    queryKey: [`companies`, props.company.id],
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

const { mutate: setImageAsset, isPending: isAssigningImage } =
  useCompanySetImageAssetMutation({
    config: {
      onSuccess: async () => {
        await invalidateCompanyQueries()

        toast({
          title: `${assetLabel.value} Updated`,
          description: `The shared image has been assigned to this company.`,
        })

        isOpen.value = false
      },
    },
  })

const { mutate: storeImage, isPending: isUploadingImage } =
  useImageStoreMutation({
    config: {
      onSuccess: (data) => {
        resetForm()

        setImageAsset({
          companyId: props.company.id,
          imageId: data.id,
          type: props.type,
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

const assignExistingImage = () => {
  if (!selectedImageId.value) {
    toast({
      title: `Select an Image`,
      description: `Choose an image from the shared library first.`,
      variant: `destructive`,
    })

    return
  }

  setImageAsset({
    companyId: props.company.id,
    imageId: Number(selectedImageId.value),
    type: props.type,
  })
}

const onValidForm = (values) => {
  if (!currentImageTypeId.value) {
    toast({
      title: `Missing Image Type`,
      description: `The selected image type could not be resolved.`,
      variant: `destructive`,
    })

    return
  }

  const formData = new FormData()
  formData.append(`name`, values.name)
  formData.append(`image`, values.image)
  formData.append(`image_type_id`, `${currentImageTypeId.value}`)

  storeImage({
    formData,
  })
}

const onInvalidForm = ({ errors }) => {
  toast({
    title: `Validation Error`,
    description: errors.name || errors.image || `Please fix the form errors.`,
    variant: `destructive`,
  })
}

const submitUploadForm = () => {
  handleSubmit(onValidForm, onInvalidForm)()
}

const cancelDialog = () => {
  isOpen.value = false
  resetForm()
}

watch(
  () => [isOpen.value, currentAsset.value?.id],
  ([open, imageId]) => {
    if (!open) {
      return
    }

    selectedImageId.value = imageId ? `${imageId}` : null
  },
  { immediate: true },
)
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button
        :dusk="`company-image-open-${type}`"
        variant="outline"
        size="sm"
        :class="{
          'w-full': !iconOnly,
          'w-auto': iconOnly,
        }"
      >
        <FontAwesomeIcon
          :class="{
            'mr-2': !iconOnly,
          }"
          :icon="faImage"
          fixed-width
        />

        <span v-if="!iconOnly">
          <slot />
        </span>
      </Button>
    </DialogTrigger>

    <DialogContent
      class="max-h-[85dvh] w-[min(90vw,72rem)] max-w-[min(90vw,72rem)] grid-rows-[auto_minmax(0,1fr)_auto]"
    >
      <DialogHeader>
        <DialogTitle>{{ assetLabel }} Image Library</DialogTitle>

        <VisuallyHidden as-child>
          <DialogDescription>
            Select a shared image or upload a new {{ type }} to the library.
          </DialogDescription>
        </VisuallyHidden>
      </DialogHeader>

      <div class="grid gap-4 overflow-y-auto px-2 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Select From Shared Library</CardTitle>
            <CardDescription>
              Reuse an existing {{ type }} image for this company.
            </CardDescription>
          </CardHeader>

          <CardContent class="space-y-4">
            <ScrollArea class="h-80 rounded border border-border p-2">
              <div
                v-if="filteredLibraryImages.length"
                :class="libraryGridClass"
              >
                <button
                  v-for="image in filteredLibraryImages"
                  :key="image.id"
                  type="button"
                  :dusk="`company-image-library-select-${image.id}`"
                  class="rounded-lg border p-3 text-left transition hover:border-primary"
                  :class="{
                    'border-primary ring-2 ring-primary/20':
                      `${image.id}` === selectedImageId,
                    'border-border': `${image.id}` !== selectedImageId,
                  }"
                  @click="selectedImageId = `${image.id}`"
                >
                  <div
                    class="overflow-hidden rounded bg-muted"
                    :class="previewFrameClass"
                  >
                    <img
                      :src="imageAssetUrl({ filePath: image.file_path })"
                      :alt="image.name"
                      class="h-full w-full object-contain"
                    />
                  </div>

                  <div class="mt-3 space-y-1">
                    <p class="font-medium">{{ image.name }}</p>
                    <p class="text-sm capitalize text-muted-foreground">
                      Used by {{ image.company_usage_count }} compan{{
                        image.company_usage_count === 1 ? `y` : `ies`
                      }}
                    </p>
                  </div>
                </button>
              </div>

              <div
                v-else-if="!isLoadingLibrary"
                class="flex h-full items-center justify-center rounded border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
              >
                No shared {{ type }} images yet. Upload one below.
              </div>

              <div
                v-else
                class="flex h-full items-center justify-center p-6 text-sm text-muted-foreground"
              >
                Loading shared images...
              </div>
            </ScrollArea>

            <Button
              type="button"
              dusk="company-image-assign-existing"
              :disabled="
                isAssigningImage || isUploadingImage || !selectedImageId
              "
              @click="assignExistingImage"
            >
              Assign Selected Image
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Upload To Shared Library</CardTitle>
            <CardDescription>
              Upload a new {{ type }} image and assign it immediately.
            </CardDescription>
          </CardHeader>

          <CardContent>
            <form
              id="companyImageUploadForm"
              class="space-y-4"
              @submit.prevent="submitUploadForm"
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
                      :dusk="`company-image-upload-name-${type}`"
                      type="text"
                      placeholder="Shared brand asset"
                      v-bind="componentField"
                      :disabled="isAssigningImage || isUploadingImage"
                    />
                  </FormControl>

                  <FormDescription>
                    The image will be saved to the shared library.
                  </FormDescription>
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
                      :dusk="`company-image-upload-file-${type}`"
                      accept="image/*"
                      type="file"
                      :disabled="isAssigningImage || isUploadingImage"
                      @change="
                        (event) => handleChange(event.target.files?.[0] ?? null)
                      "
                    />
                  </FormControl>

                  <FormDescription>
                    Uploading here adds the image to the shared library and
                    assigns it to this company.
                  </FormDescription>
                </FormItem>
              </FormField>

              <Button
                type="submit"
                :dusk="`company-image-upload-save-${type}`"
                :disabled="isAssigningImage || isUploadingImage"
              >
                <FontAwesomeIcon class="mr-2" :icon="faUpload" fixed-width />
                Upload And Assign
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>

      <DialogFooter
        class="flex flex-row items-center justify-end space-x-2 pt-4"
      >
        <Button
          variant="secondary"
          size="sm"
          :disabled="isAssigningImage || isUploadingImage"
          @click="cancelDialog"
        >
          Cancel
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
