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
import { hasCompanyFeature } from '@/composables/helpers/companyFeatures'
import { useRolesAndPermissions } from '@/composables/hooks/auth'
import { useCompaniesQuery } from '@/composables/queries/company'

import SelectThemeDialog from '../theme/SelectThemeDialog.vue'
import CompanyInfoCell from './CompanyInfoCell.vue'
import ToggleMapSwitch from './ToggleMapSwitch.vue'

const { initialCompanies } = usePage().props

const { userCan } = useRolesAndPermissions()

const { data, isError } = useCompaniesQuery({
  config: {
    initialData: initialCompanies,
  },
})

const columns = [
  {
    accessorKey: `name`,
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Name`),
    cell: ({ row }) => {
      return h(CompanyInfoCell, {
        company: row.original,
      })
    },
  },
  {
    id: `enable_map`,
    accessorFn: (row) => hasCompanyFeature(row, `enable_map`),
    header: () => h(`div`, { class: `text-sm font-semibold` }, `Tracking Map`),
    cell: ({ row }) => {
      return h(ToggleMapSwitch, {
        companyId: row.original.id,
        id: `enable_map`,
        name: `Enable Map`,
        value: hasCompanyFeature(row.original, `enable_map`),
      })
    },
  },
  {
    accessorKey: `theme_id`,
    header: () => h(`div`, { class: `text-base` }, `Theme`),
    cell: ({ row }) => {
      return h(SelectThemeDialog, {
        companyId: row.original.id,
        currentTheme: row.original.theme,
      })
    },
  },
  userCan(`company:edit`)
    ? {
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
                  { href: route(`admin.company.show`, row.original.uuid) },
                  {
                    default: () =>
                      h(FontAwesomeIcon, { icon: faEdit, fixedWidth: true }),
                  },
                ),
            },
          )
        },
      }
    : null,
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
