<script setup>
import { faDownload, faEye } from '@fortawesome/pro-duotone-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

defineProps({
  documents: {
    type: Array,
    required: true,
    default: () => [],
  },
  bolNumber: {
    type: String,
    required: true,
  },
})

const formatFileSize = (size) => {
  if (size === null || size === undefined) return `N/A`
  const num = Number(size)
  if (num < 1024) return `${num} B`
  if (num < 1024 * 1024) return `${(num / 1024).toFixed(2)} KB`
  if (num < 1024 * 1024 * 1024) return `${(num / 1024 / 1024).toFixed(2)} MB`
  return `${(num / 1024 / 1024 / 1024).toFixed(2)} GB`
}
</script>

<template>
  <Card class="group w-full shadow-lg">
    <CardHeader>
      <CardTitle>Shipment Documents</CardTitle>
    </CardHeader>

    <CardContent>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-32">Document</TableHead>

            <TableHead>URL</TableHead>

            <TableHead>Type</TableHead>

            <TableHead>Size</TableHead>

            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-for="document in documents" :key="document.url">
            <TableCell class="font-medium uppercase">
              {{ document.name }}
            </TableCell>

            <TableCell class="truncate">
              {{ document.url.split('/').pop() }}
            </TableCell>

            <TableCell class="uppercase">
              {{ document.type.split('/')[1] }}
            </TableCell>

            <TableCell>
              {{ formatFileSize(document.size) }}
            </TableCell>

            <TableCell
              class="flex flex-row items-center justify-end space-x-2 text-right"
            >
              <Button as-child variant="secondary" size="sm">
                <a
                  :href="document.url"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <FontAwesomeIcon :icon="faEye" fixed-width />
                </a>
              </Button>

              <Button as-child variant="secondary" size="sm">
                <a :href="document.url" download>
                  <FontAwesomeIcon :icon="faDownload" fixed-width />
                </a>
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
  </Card>
</template>
