<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { Monitor, Moon, Sun } from 'lucide-vue-next'
import { ref } from 'vue'

import {
  DropdownMenuPortal,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu'
import { useToast } from '@/components/ui/toast'

const page = usePage()
const saving = ref(false)
const { toast } = useToast()
const modes = [
  { value: `system`, name: `System`, icon: Monitor },
  { value: `light`, name: `Light`, icon: Sun },
  { value: `dark`, name: `Dark`, icon: Moon },
]
function change(appearance) {
  if (saving.value || appearance === page.props.staffAppearance) return
  saving.value = true
  router.patch(
    route(`admin.preferences.appearance`),
    { appearance },
    {
      preserveState: true,
      preserveScroll: true,
      only: [`staffAppearance`],
      onError: () => {
        toast({
          title: `Could not save appearance. Try again.`,
          variant: `destructive`,
        })
      },
      onFinish: () => {
        saving.value = false
      },
    },
  )
}
</script>
<template>
  <DropdownMenuSub>
    <DropdownMenuSubTrigger
      dusk="staff-appearance-menu"
      :disabled="saving || page.props.is_impersonating"
      class="gap-2"
    >
      <component
        :is="
          modes.find((mode) => mode.value === page.props.staffAppearance)
            ?.icon || Monitor
        "
        class="size-4"
        aria-hidden="true"
      />
      Appearance
    </DropdownMenuSubTrigger>
    <DropdownMenuPortal>
      <DropdownMenuSubContent :collision-padding="8" class="min-w-32">
        <DropdownMenuRadioGroup
          :model-value="page.props.staffAppearance"
          aria-label="Staff appearance"
          @update:model-value="change"
        >
          <DropdownMenuRadioItem
            v-for="mode in modes"
            :key="mode.value"
            :value="mode.value"
            :dusk="'appearance-' + mode.value"
            class="gap-2"
          >
            <component :is="mode.icon" class="size-4" aria-hidden="true" />
            {{ mode.name }}
          </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
      </DropdownMenuSubContent>
    </DropdownMenuPortal>
  </DropdownMenuSub>
</template>
