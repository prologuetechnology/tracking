<script setup>
import { faGlobe, faGlobePointer } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { usePage } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { h, reactive } from 'vue'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAllowedDomainsQuery } from '@/composables/queries/allowedDomain'

import AllowedDomainActiveStatusToggle from './AllowedDomainActiveStatusToggle.vue'
import AllowedDomainDestroyButton from './AllowedDomainDestroyButton.vue'
import AllowedDomainUpdateButton from './AllowedDomainUpdateButton.vue'

const { initialAllowedDomains } = usePage().props

const { data: allowedDomains, isError } = useAllowedDomainsQuery({
  config: {
    initialData: initialAllowedDomains,
  },
})

const columns = [
  {
    accessorKey: `favicon_url`,
    header: () =>
      h(FontAwesomeIcon, {
        icon: faGlobePointer,
      }),
    cell: ({ row }) => {
      const faviconUrl = row.getValue(`favicon_url`)
      return faviconUrl
        ? h(`img`, {
            src: faviconUrl,
            alt: `Favicon`,
            class: `h-4 w-4 text-sm`,
          })
        : h(FontAwesomeIcon, {
            icon: faGlobe,
            class: `text-sm text-muted-foreground`,
          })
    },
  },
  {
    accessorKey: `domain`,
    header: `Domain`,
    cell: ({ row }) => {
      return h(
        `span`,
        { class: `font-mono font-semibold` },
        row.getValue(`domain`),
      )
    },
  },
  {
    accessorKey: `active`,
    header: () => `Active`,
    cell: ({ row }) =>
      h(AllowedDomainActiveStatusToggle, {
        allowedDomain: row.original,
      }),
  },
  {
    accessorKey: `actions`,
    header: () => `Actions`,
    cell: ({ row }) => {
      return h(
        `div`,
        { class: `flex flex-row space-x-2 justify-start items-center` },
        [
          h(AllowedDomainUpdateButton, {
            allowedDomain: row.original,
            class: `mr-2`,
          }),
          h(AllowedDomainDestroyButton, {
            allowedDomain: row.original,
          }),
        ],
      )
    },
  },
]

const tableOptions = reactive({
  get data() {
    return allowedDomains
  },
  get columns() {
    return columns
  },
  getCoreRowModel: getCoreRowModel(),
})

const allowedDomainsTable = useVueTable(tableOptions)
</script>

<template>
  <div class="rounded border border-border">
    <Table v-if="allowedDomains && !isError">
      <TableHeader>
        <TableRow
          v-for="headerGroup in allowedDomainsTable.getHeaderGroups()"
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
        <template
          v-if="allowedDomainsTable.getRowModel().rows?.length && !isError"
        >
          <TableRow
            v-for="row in allowedDomainsTable.getRowModel().rows"
            :key="row.original.id"
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
</template>
