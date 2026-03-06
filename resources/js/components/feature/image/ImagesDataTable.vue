<script setup>
import { faTrashAlt } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { computed, h, reactive, ref } from 'vue'

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useImagesQuery } from '@/composables/queries/image'

import ImageDestroyDialog from './ImageDestroyDialog.vue'
import ImageThumbnailHoverCard from './ImageThumbnailHoverCard.vue'

const props = defineProps({
  initialImages: {
    type: Array,
    required: true,
  },
  initialImageTypes: {
    type: Array,
    required: true,
  },
})

const selectedImageTypeId = ref(`all`)
const imageTypeId = computed(() =>
  selectedImageTypeId.value === `all`
    ? null
    : Number(selectedImageTypeId.value),
)

const { data, isError } = useImagesQuery({
  imageTypeId,
  config: computed(() => ({
    initialData:
      selectedImageTypeId.value === `all` ? props.initialImages : undefined,
  })),
})

const columns = [
  {
    accessorKey: `file_path`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, ``),
    cell: ({ row }) => {
      return h(ImageThumbnailHoverCard, {
        image: row.original,
        class: `flex items-center justify-center`,
      })
    },
  },
  {
    accessorKey: `name`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Name`),
    cell: ({ row }) => {
      return h(
        `div`,
        {
          class: `text-sm capitalize`,
        },
        row.original.name,
      )
    },
  },
  {
    accessorKey: `type`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Type`),
    cell: ({ row }) => {
      return h(
        `div`,
        {
          class: `text-sm capitalize`,
        },
        row.original.image_type?.name ?? `Unknown`,
      )
    },
  },
  {
    accessorKey: `delete`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Actions`),
    cell: ({ row }) => {
      return h(
        ImageDestroyDialog,
        {
          image: row.original,
        },
        () => {
          return h(FontAwesomeIcon, {
            icon: faTrashAlt,
            fixedWidth: true,
          })
        },
      )
    },
  },
]

const tableOptions = reactive({
  get data() {
    return data.value ?? []
  },
  get columns() {
    return columns
  },
  getCoreRowModel: getCoreRowModel(),
})

const imagesTable = useVueTable(tableOptions)
</script>

<template>
  <div class="space-y-4">
    <div
      class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <p class="text-sm font-medium">Image Library</p>
        <p class="text-sm text-muted-foreground">
          Upload shared assets and filter them by image type.
        </p>
      </div>

      <div class="w-full sm:w-56">
        <Select v-model="selectedImageTypeId">
          <SelectTrigger class="w-full">
            <SelectValue placeholder="Filter by image type" />
          </SelectTrigger>

          <SelectContent>
            <SelectGroup>
              <SelectLabel>Image Types</SelectLabel>
              <SelectItem value="all">All image types</SelectItem>
              <SelectItem
                v-for="imageType in initialImageTypes"
                :key="imageType.id"
                :value="`${imageType.id}`"
              >
                {{ imageType.name }}
              </SelectItem>
            </SelectGroup>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="overflow-hidden rounded border border-border">
      <Table v-if="data && !isError">
        <TableHeader>
          <TableRow
            v-for="headerGroup in imagesTable.getHeaderGroups()"
            :key="headerGroup.id"
          >
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <template v-if="imagesTable.getRowModel().rows?.length">
            <TableRow
              v-for="row in imagesTable.getRowModel().rows"
              :key="row.uuid"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </TableCell>
            </TableRow>
          </template>

          <template v-else>
            <TableRow>
              <TableCell :colspan="columns.length" class="h-24 text-center">
                No results.
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
