<script setup>
import { usePage } from '@inertiajs/vue3'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { h, reactive } from 'vue'

import UserAssignRoleDropdown from '@/components/feature/userManagement/UserAssignRoleDropdown.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table/index.js'
import { useUsersQuery } from '@/composables/queries/user'

import ImpersonateUserButton from './ImpersonateUserButton.vue'

const { initialUsers, initialRoles } = usePage().props

const { data, isError } = useUsersQuery({
  config: {
    initialData: initialUsers,
  },
})

const columns = [
  {
    accessorKey: `name`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Name`),
    cell: ({ row }) => {
      return h(
        `span`,
        { class: `text-sm font-semibold` },
        `${row.original.first_name} ${row.original.last_name}`,
      )
    },
  },
  {
    accessorKey: `email`,
    header: () => h(`div`, { class: `text-base` }, `Email`),
    cell: ({ row }) => {
      return h(`span`, { class: `text-sm font-semibold` }, row.original.email)
    },
  },
  {
    accessorKey: `edit`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Assign Role`),
    cell: ({ row }) => {
      return h(UserAssignRoleDropdown, {
        user: row.original,
        allRoles: initialRoles,
      })
    },
  },
  {
    accessorKey: `impersonate`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Impersonate`),
    cell: ({ row }) => {
      return h(ImpersonateUserButton, {
        user: row.original,
      })
    },
  },
].filter(Boolean)

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
</template>
