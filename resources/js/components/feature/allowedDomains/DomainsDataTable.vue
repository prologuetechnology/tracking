<script setup>
import { faTrash } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router, usePage } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { h, reactive } from 'vue'

import EditDomainDialog from '@/components/feature/allowedDomains/EditDomainDialog.vue'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAllowedDomainsQuery } from '@/composables/queries/allowedDomains' // <- You’ll need to create this composable

const { initialDomains } = usePage().props

const { data, isError } = useAllowedDomainsQuery({
  config: {
    initialData: initialDomains,
  },
})

const deleteDomain = (id) => {
  if (confirm(`Are you sure you want to delete this domain?`)) {
    router.delete(route(`admin.domain.destroy`, id))
  }
}

const columns = [
  {
    accessorKey: `domain`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Domain`),
    cell: ({ row }) => h(`span`, {}, row.getValue(`domain`)),
  },
  {
    accessorKey: `edit`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Edit`),
    cell: ({ row }) =>
      h(EditDomainDialog, {
        domain: row.original,
      }),
  },
  {
    accessorKey: `delete`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Delete`),
    cell: ({ row }) =>
      h(
        Button,
        {
          variant: `destructive`,
          size: `sm`,
          onClick: () => deleteDomain(row.original.id),
        },
        {
          default: () =>
            h(FontAwesomeIcon, { icon: faTrash, fixedWidth: true }),
        },
      ),
  },
]

const tableOptions = reactive({
  get data() {
    return data
  },
  get columns() {
    return columns
  },
  getCoreRowModel: getCoreRowModel(),
})

const domainsTable = useVueTable(tableOptions)
</script>

<template>
  <div class="overflow-hidden rounded-md">
    <Table v-if="data && !isError">
      <TableHeader>
        <TableRow
          v-for="headerGroup in domainsTable.getHeaderGroups()"
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
        <template v-if="domainsTable.getRowModel().rows?.length">
          <TableRow
            v-for="row in domainsTable.getRowModel().rows"
            :key="row.id"
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
              No allowed domains yet.
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>
  </div>
</template>

<style scoped></style>
