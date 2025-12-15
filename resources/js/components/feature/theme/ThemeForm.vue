<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import { watch } from 'vue'
import * as yup from 'yup'

import { Button } from '@/components/ui/button'
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Slider } from '@/components/ui/slider'
import {
  useThemeCreateMutation,
  useThemeUpdateMutation,
} from '@/composables/mutations/theme'
import { cn } from '@/lib/utils'

import ThemeDestroyDialog from './ThemeDestroyDialog.vue'

const props = defineProps({
  theme: {
    type: Object,
    required: true,
  },
  heading: {
    type: String,
    default: `Theme Information`,
  },
})

const queryClient = useQueryClient()

const themeFormSchema = yup.object({
  name: yup.string().min(1).required(),
  primary_hue: yup.number().min(0).max(360).required(),
  primary_saturation: yup.number().min(0).max(100).required(),
  primary_lightness: yup.number().min(0).max(100).required(),
  accent_hue: yup.number().min(0).max(360).required(),
  accent_saturation: yup.number().min(0).max(100).required(),
  accent_lightness: yup.number().min(0).max(100).required(),
  derive_from: yup.string().oneOf([`primary`, `accent`]).required(),
})

const { isFieldDirty, handleSubmit, resetForm, values } = useForm({
  validationSchema: themeFormSchema,
  initialValues: {
    name: props.theme ? props.theme?.name : ``,
    primary_hue: props.theme
      ? [props.theme?.colors?.root.primary.split(` `)[0]]
      : [0],
    primary_saturation: props.theme
      ? [props.theme?.colors?.root.primary.split(` `)[1].replace(`%`, ``)]
      : [0],
    primary_lightness: props.theme
      ? [props.theme?.colors?.root.primary.split(` `)[2].replace(`%`, ``)]
      : [0],
    accent_hue: props.theme
      ? [props.theme?.colors?.root.accent.split(` `)[0]]
      : [0],
    accent_saturation: props.theme
      ? [props.theme?.colors?.root.accent.split(` `)[1].replace(`%`, ``)]
      : [0],
    accent_lightness: props.theme
      ? [props.theme?.colors?.root.accent.split(` `)[2].replace(`%`, ``)]
      : [0],
    derive_from: props.theme ? props.theme?.derive_from : `primary`,
  },
  keepValuesOnUnmount: true,
})

const isFormDirty = useIsFormDirty()

const { mutate: createTheme, isPending: createThemeIsPending } =
  useThemeCreateMutation({
    config: {
      onSuccess: (data) => {
        console.log(`Theme created`, data)
        queryClient.invalidateQueries(`themes`)

        router.visit(route(`admin.theme.show`, data.uuid))
      },
    },
  })

const { mutate: updateTheme, isPending: updateThemeIsPending } =
  useThemeUpdateMutation({
    config: {
      onSuccess: () => {
        queryClient.invalidateQueries(`themes`)

        router.visit(route(`admin.themes.index`))
      },
    },
  })

const onValidForm = (values) => {
  if (props.theme) {
    updateTheme({
      id: props.theme.id,
      formData: {
        ...values,
      },
    })
  } else {
    createTheme({
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
  () => props.theme,
  (newTheme) => {
    if (newTheme) {
      resetForm({
        values: {
          name: newTheme?.name,
          primary_hue: [newTheme?.colors?.root.primary.split(` `)[0]],
          primary_saturation: [
            newTheme?.colors?.root.primary.split(` `)[1].replace(`%`, ``),
          ],
          primary_lightness: [
            newTheme?.colors?.root.primary.split(` `)[2].replace(`%`, ``),
          ],
          accent_hue: [newTheme?.colors?.root.accent.split(` `)[0]],
          accent_saturation: [
            newTheme?.colors?.root.accent.split(` `)[1].replace(`%`, ``),
          ],
          accent_lightness: [
            newTheme?.colors?.root.accent.split(` `)[2].replace(`%`, ``),
          ],
          derive_from: newTheme?.derive_from,
        },
      })
    }
  },
)
</script>

<template>
  <form
    id="themeForm"
    class="mt-4 flex w-full flex-col space-y-4 rounded-lg border border-border p-4"
    @submit.prevent="submitForm"
  >
    <div class="flex flex-col justify-stretch space-y-4">
      <FormField
        v-slot="{ componentField }"
        name="name"
        :validate-on-blur="!isFieldDirty"
      >
        <FormItem>
          <div
            class="flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4"
          >
            <div class="w-full space-y-0.5">
              <FormLabel class="text-base">Name</FormLabel>

              <FormDescription class="text-sm text-muted-foreground">
                A catchy name for this theme or the name of the company it
                should be used for.
              </FormDescription>
            </div>

            <FormControl>
              <Input
                type="text"
                placeholder="Punchy Piña Colada"
                v-bind="componentField"
              />
            </FormControl>
          </div>
        </FormItem>
      </FormField>

      <FormField
        v-slot="{ componentField }"
        name="derive_from"
        :validate-on-blur="!isFieldDirty"
      >
        <FormItem>
          <div
            class="flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4"
          >
            <div class="w-full space-y-0.5">
              <FormLabel class="text-base">Derive From</FormLabel>

              <FormDescription class="text-sm text-muted-foreground">
                Select what color the accent should be derived from.
              </FormDescription>
            </div>

            <FormControl>
              <RadioGroup
                :orientation="`horizontal`"
                class="flex flex-row items-center justify-start space-x-4"
                v-bind="componentField"
              >
                <div class="flex items-center space-x-2">
                  <RadioGroupItem id="primary" value="primary" />

                  <Label for="primary" class="text-base">Primary</Label>
                </div>
                <div class="flex items-center space-x-2">
                  <RadioGroupItem id="accent" value="accent" />

                  <Label for="accent" class="text-base">Accent</Label>
                </div>
              </RadioGroup>
            </FormControl>
          </div>
        </FormItem>
      </FormField>

      <div class="grid grid-cols-[auto,1fr] gap-x-20 rounded-lg border p-4">
        <div class="self-center">
          <Label class="text-base">Primary Color</Label>

          <p class="mt-[0.125rem] text-sm text-muted-foreground">
            The color of the primary color.
          </p>

          <div
            class="z-10 mt-4 aspect-square h-16 w-16 rounded-full outline outline-[0.375rem] outline-background"
            :style="{
              backgroundColor: `hsl(${values.primary_hue[0]}, ${values.primary_saturation[0]}%, ${values.primary_lightness[0]}%)`,
            }"
          />
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-2">
          <FormField
            v-slot="{ componentField }"
            name="primary_hue"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Hue</FormLabel>

              <Slider
                v-bind="componentField"
                :max="360"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="360"
              />
            </FormItem>
          </FormField>

          <FormField
            v-slot="{ componentField }"
            name="primary_saturation"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Saturation</FormLabel>

              <Slider
                v-bind="componentField"
                :max="100"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="100"
              />
            </FormItem>
          </FormField>

          <FormField
            v-slot="{ componentField }"
            name="primary_lightness"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Lightness</FormLabel>

              <Slider
                v-bind="componentField"
                :max="100"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="100"
              />
            </FormItem>
          </FormField>
        </div>
      </div>

      <div class="grid grid-cols-[auto,1fr] gap-x-20 rounded-lg border p-4">
        <div class="self-center">
          <Label class="text-base">Accent Color</Label>

          <p class="mt-[0.125rem] text-sm text-muted-foreground">
            The color of the accent theme.
          </p>

          <div
            class="z-10 mt-4 aspect-square h-16 w-16 rounded-full outline outline-[0.375rem] outline-background"
            :style="{
              backgroundColor: `hsl(${values.accent_hue[0]}, ${values.accent_saturation[0]}%, ${values.accent_lightness[0]}%)`,
            }"
          />
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-2">
          <FormField
            v-slot="{ componentField }"
            name="accent_hue"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Hue</FormLabel>

              <Slider
                v-bind="componentField"
                :max="360"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="360"
              />
            </FormItem>
          </FormField>

          <FormField
            v-slot="{ componentField }"
            name="accent_saturation"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Saturation</FormLabel>

              <Slider
                v-bind="componentField"
                :max="100"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="100"
              />
            </FormItem>
          </FormField>

          <FormField
            v-slot="{ componentField }"
            name="accent_lightness"
            :validate-on-blur="!isFieldDirty"
          >
            <FormItem class="grid grid-cols-[4rem,1fr,auto] gap-x-8 gap-y-2">
              <FormLabel class="self-center text-sm">Lightness</FormLabel>

              <Slider
                v-bind="componentField"
                :max="100"
                :min="0"
                :class="cn('w-full', $attrs.class ?? ``)"
              />

              <Input
                v-bind="componentField"
                class="w-20"
                type="number"
                disabled
                :min="0"
                :max="100"
              />
            </FormItem>
          </FormField>
        </div>
      </div>
    </div>

    <hr class="mb-4 mt-6" />

    <div
      class="mx-auto flex w-full max-w-3xl flex-row items-center justify-end space-x-2 py-2"
    >
      <div v-if="theme?.id" class="mr-auto">
        <ThemeDestroyDialog :theme="theme" />
      </div>

      <Button
        variant="secondary"
        size="sm"
        :disabled="createThemeIsPending || updateThemeIsPending"
      >
        <Link :href="route(`admin.themes.index`)">Cancel</Link>
      </Button>

      <Button
        variant="default"
        size="sm"
        type="button"
        class=""
        :disabled="createThemeIsPending || updateThemeIsPending || !isFormDirty"
        @click="submitForm"
      >
        Save
      </Button>
    </div>

    <!-- <section
      class="mt-12 flex flex-row items-center justify-center -space-x-12"
    >
      <div
        class="z-10 aspect-square h-24 w-24 rounded-full outline outline-[0.375rem] outline-background"
        :style="{
          backgroundColor: `hsl(${values.primary_hue[0]}, ${values.primary_saturation[0]}%, ${values.primary_lightness[0]}%)`,
        }"
      />

      <div
        class="-z-0 aspect-square h-24 w-24 rounded-full"
        :style="{
          backgroundColor: `hsl(${values.accent_hue[0]}, ${values.accent_saturation[0]}%, ${values.accent_lightness[0]}%)`,
        }"
      />
    </section> -->
  </form>
</template>
