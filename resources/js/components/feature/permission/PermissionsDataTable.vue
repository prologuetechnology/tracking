<script setup>
import { faEdit } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, usePage } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { h, reactive } from 'vue'

import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import usePermissionsQuery from '@/composables/queries/permission/usePermissionsQuery.js'

const { initialPermissions } = usePage().props

const { data, isError } = usePermissionsQuery({
  config: {
    initialData: initialPermissions,
  },
})

const columns = [
  {
    accessorKey: `name`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Name`),
    cell: ({ row }) => {
      return h(`span`, { class: `text-sm font-semibold` }, row.original.name)
    },
  },
  {
    accessorKey: `guard_name`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Guard`),
    cell: ({ row }) => {
      return h(
        `span`,
        { class: `text-sm font-semibold` },
        row.original.guard_name,
      )
    },
  },
  {
    accessorKey: `edit`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Edit`),
    cell: ({ row }) => {
      return h(
        Button,
        { variant: `outline`, size: `sm`, asChild: true },
        {
          default: () =>
            h(
              Link,
              { href: route(`admin.permissions.show`, row.original.id) },
              {
                default: () =>
                  h(FontAwesomeIcon, { icon: faEdit, fixedWidth: true }),
              },
            ),
        },
      )
    },
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

const companiesTable = useVueTable(tableOptions)
</script>

<template>
  <div class="rounded border border-border">
    <Table v-if="data && !isError">
      <TableHeader>
        <TableRow
          v-for="headerGroup in companiesTable.getHeaderGroups()"
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
        <template v-if="companiesTable.getRowModel().rows?.length">
          <TableRow
            v-for="row in companiesTable.getRowModel().rows"
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
