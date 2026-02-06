<script setup>
import { faImageSlash, faTrashAlt } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Head } from '@inertiajs/vue3'

import CompanyForm from '@/components/feature/company/CompanyForm.vue'
import CompanySetImageAsset from '@/components/feature/company/CompanySetImageAsset.vue'
import ToggleCompanyIsActive from '@/components/feature/company/ToggleCompanyIsActive.vue'
// import ToggleDocumentsSwitch from '@/components/feature/company/ToggleDocumentsSwitch.vue'
// import ToggleMapSwitch from '@/components/feature/company/ToggleMapSwitch.vue'
import CompanyApiTokenForm from '@/components/feature/companyApiToken/CompanyApiTokenForm.vue'
import CompanyFeatures from '@/components/feature/companyFeature/CompanyFeatures.vue'
import ImageDestroyDialog from '@/components/feature/image/ImageDestroyDialog.vue'
import SelectThemeDialog from '@/components/feature/theme/SelectThemeDialog.vue'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
import { Label } from '@/components/ui/label'
import { useCompanyFeatures } from '@/composables/helpers'
import { imageAssetUrl } from '@/composables/hooks/disks'
import { useCompanyQuery } from '@/composables/queries/company'

const props = defineProps({
  companyInitialValues: {
    type: Object,
    required: true,
  },
})

const { data: company, isError } = useCompanyQuery({
  id: props.companyInitialValues.id,

  config: {
    initialData: props.companyInitialValues,
  },
})
</script>

<template>
  <Head :title="`${company?.name} - Manage Company`" />

  <AuthenticatedLayout :title="company?.name">
    <div v-if="company && !isError" class="group relative mb-32 h-72">
      <div
        class="absolute left-8 top-40 mb-4 flex flex-col items-stretch justify-start space-y-4"
      >
        <div
          class="relative flex aspect-square h-40 w-40 flex-row items-center justify-center overflow-hidden rounded-lg border border-border bg-card p-4 shadow-lg"
        >
          <img
            v-if="company.logo?.file_path"
            :src="imageAssetUrl({ filePath: company.logo?.file_path })"
            :alt="company.logo?.name"
          />

          <div
            v-else
            class="absolute left-0 top-0 flex h-full w-full flex-row items-center justify-center overflow-hidden rounded-lg bg-muted"
          >
            <FontAwesomeIcon
              class="text-4xl text-muted-foreground"
              :icon="faImageSlash"
              fixed-width
            />
          </div>
        </div>

        <CompanySetImageAsset :company="company" type="logo">
          {{ company.logo?.file_path ? `Edit` : `Add` }} Logo
        </CompanySetImageAsset>
      </div>

      <div
        class="absolute right-2 top-2 flex flex-row items-center justify-end space-x-2"
      >
        <ImageDestroyDialog
          v-if="company.banner?.file_path"
          :image="company.banner"
        >
          <FontAwesomeIcon :icon="faTrashAlt" fixed-width />
        </ImageDestroyDialog>

        <CompanySetImageAsset :company="company" type="banner">
          {{ company.banner?.file_path ? `Edit` : `Add` }} Banner
        </CompanySetImageAsset>
      </div>

      <div
        v-if="company.banner?.file_path"
        class="absolute left-0 top-0 -z-10 h-full w-full overflow-hidden rounded-lg opacity-65 transition-opacity duration-500 ease-in-out group-hover:opacity-100"
      >
        <img
          :src="imageAssetUrl({ filePath: company.banner?.file_path })"
          :alt="company.banner?.name"
          class="h-full w-full object-cover"
        />
      </div>

      <div
        v-else
        class="absolute left-0 top-0 -z-10 flex h-full w-full flex-row items-center justify-center overflow-hidden rounded-lg bg-muted"
      >
        <FontAwesomeIcon
          class="text-4xl text-muted-foreground"
          :icon="faImageSlash"
          fixed-width
        />
      </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-4">
      <div
        class="col-span-2 flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4 lg:col-span-2"
      >
        <div class="w-full space-y-0.5">
          <Label class="text-base">Enabled</Label>

          <p class="text-sm text-muted-foreground">
            Enable company tracking portal.
          </p>
        </div>

        <div>
          <ToggleCompanyIsActive
            id="is_active"
            name="is_active"
            :company-id="company?.id"
            :value="Boolean(company?.is_active)"
          />
        </div>
      </div>

      <!-- <div
          class="flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4"
        >
          <div class="w-full space-y-0.5">
            <Label class="text-base">Tracking Map</Label>

            <p class="text-sm text-muted-foreground">
              Enable tracking map in portal.
            </p>
          </div>

          <div>
            <ToggleMapSwitch
              id="enable_map"
              name="enable_map"
              :company-id="company?.id"
              :value="hasCompanyFeature(company, `enable_map`)"
            />
          </div>
        </div>

        <div
          class="flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4"
        >
          <div class="w-full space-y-0.5">
            <Label class="text-base">Shipment Documents</Label>

            <p class="text-sm text-muted-foreground">
              Enable shipment documents in portal.
            </p>
          </div>

          <div>
            <ToggleDocumentsSwitch
              id="enable_documents"
              name="enable_documents"
              :company-id="company?.id"
              :value="hasCompanyFeature(company, `enable_documents`)"
            />
          </div>
        </div> -->

      <div
        class="col-span-2 flex w-full flex-row items-center justify-between space-x-8 rounded-lg border p-4 lg:col-span-2"
      >
        <div class="w-full space-y-0.5">
          <Label class="text-base">Theme</Label>

          <p class="text-sm text-muted-foreground">
            Set the color theme for the company's tracking portal.
          </p>
        </div>

        <div>
          <SelectThemeDialog
            :company-id="company?.id"
            :current-theme="company?.theme"
          />
        </div>
      </div>
    </div>

    <CompanyFeatures />

    <section v-if="company && !isError">
      <div class="mx-2 mt-4 md:mx-0">
        <Label>Footer</Label>

        <div class="relative mt-2 h-72">
          <div
            class="absolute right-2 top-2 flex flex-row items-center justify-end space-x-2"
          >
            <ImageDestroyDialog
              v-if="company.footer?.file_path"
              :image="company.footer"
            >
              <FontAwesomeIcon :icon="faTrashAlt" fixed-width />
            </ImageDestroyDialog>

            <CompanySetImageAsset :company="company" type="footer">
              {{ company.footer?.file_path ? `Edit` : `Add` }} Footer
            </CompanySetImageAsset>
          </div>

          <div
            v-if="company.footer?.file_path"
            class="absolute left-0 top-0 -z-10 h-full w-full overflow-hidden rounded-lg"
          >
            <img
              :src="imageAssetUrl({ filePath: company.footer?.file_path })"
              :alt="company.footer.name"
              class="h-full w-full object-cover"
            />
          </div>

          <div
            v-else
            class="absolute left-0 top-0 -z-10 flex h-full w-full flex-row items-center justify-center overflow-hidden rounded-lg bg-muted"
          >
            <FontAwesomeIcon
              class="text-4xl text-muted-foreground"
              :icon="faImageSlash"
              fixed-width
            />
          </div>
        </div>
      </div>

      <CompanyApiTokenForm v-if="company && !isError" :company="company" />

      <CompanyForm v-if="company && !isError" :company="company" />
    </section>
  </AuthenticatedLayout>
</template>
