<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useQueryClient } from '@tanstack/vue-query'
import { useForm, useIsFormDirty } from 'vee-validate'
import { watch } from 'vue'
import * as yup from 'yup'

import PermissionDestroyDialog from '@/components/feature/permission/PermissionDestroyDialog.vue'
import { Button } from '@/components/ui/button'
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { useToast } from '@/components/ui/toast'
import {
  usePermissionCreateMutation,
  usePermissionUpdateMutation,
} from '@/composables/mutations/permission'

const props = defineProps({
  permission: {
    type: Object,
    required: false,
    default: null,
  },
  heading: {
    type: String,
    default: `Permission Information`,
  },
})

const queryClient = useQueryClient()

const permissionFormSchema = yup.object({
  name: yup.string().min(1).required(),
  guard_name: yup.string().min(1).required(),
})

const { isFieldDirty, handleSubmit, resetForm } = useForm({
  validationSchema: permissionFormSchema,
  initialValues: {
    name: props.permission?.name,
    guard_name: props.permission?.guard_name,
  },
  keepValuesOnUnmount: true,
})

const isFormDirty = useIsFormDirty()

const { toast } = useToast()

const { mutate: createPermission, isPending: createPermissionIsPending } =
  usePermissionCreateMutation({
    config: {
      onSuccess: async (data) => {
        resetForm()

        await queryClient.invalidateQueries({
          queryKey: [`permissions`],
        })

        await toast({
          title: `Created permission: ${data.name}`,
          description: `The permission has been created successfully.`,
          duration: 5000,
        })

        router.visit(route(`admin.permissions.index`))
      },
    },
  })

const { mutate: updatePermission, isPending: updatePermissionIsPending } =
  usePermissionUpdateMutation({
    config: {
      onSuccess: async (data) => {
        await queryClient.invalidateQueries({
          queryKey: [`permissions`],
        })

        await toast({
          title: `Updated permission: ${data.name}`,
          description: `The permission has been updated successfully.`,
          duration: 5000,
        })

        router.visit(route(`admin.permissions.index`))
      },
    },
  })

const onValidForm = (values) => {
  if (props.permission) {
    updatePermission({
      id: props.permission.id,
      formData: {
        ...values,
      },
    })
  } else {
    createPermission({
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
  () => props.permission,
  (newpermission) => {
    if (newpermission) {
      resetForm({
        values: {
          name: newpermission.name,
          guard_name: newpermission.guard_name,
        },
      })
    }
  },
)
</script>

<template>
  <form
    id="permissionForm"
    dusk="permission-form"
    class="mt-4 flex w-full flex-col space-y-4 rounded-lg border border-border p-4"
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
            dusk="permission-name"
            type="text"
            placeholder="company.create"
            v-bind="componentField"
          />
        </FormControl>

        <FormDescription>The name of the permission.</FormDescription>
      </FormItem>
    </FormField>

    <FormField
      v-slot="{ componentField }"
      name="guard_name"
      :validate-on-blur="!isFieldDirty"
    >
      <FormItem>
        <FormLabel>Guard Name</FormLabel>

        <FormControl>
          <Input
            dusk="permission-guard"
            type="text"
            placeholder="web"
            v-bind="componentField"
          />
        </FormControl>

        <FormDescription>The name of the permission.</FormDescription>
      </FormItem>
    </FormField>

    <div
      class="mx-auto flex w-full max-w-3xl flex-row items-center justify-end space-x-2 py-2"
    >
      <div v-if="permission?.id" class="mr-auto">
        <PermissionDestroyDialog :permission="permission" />
      </div>

      <Button
        variant="secondary"
        size="sm"
        :disabled="createPermissionIsPending || updatePermissionIsPending"
      >
        <Link :href="route(`admin.permissions.index`)">Cancel</Link>
      </Button>

      <Button
        variant="default"
        size="sm"
        type="button"
        class=""
        dusk="permission-save"
        :disabled="
          createPermissionIsPending || updatePermissionIsPending || !isFormDirty
        "
        @click="submitForm"
      >
        Save
      </Button>
    </div>
  </form>
</template>
