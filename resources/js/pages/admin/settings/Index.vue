<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

import { accessAreas } from '@/components/layout/navigation/accessAreas'
import AuthenticatedLayout from '@/components/layout/page/AuthenticatedLayout.vue'
</script>

<template>
  <AuthenticatedLayout title="Application Settings">
    <Head title="Application Settings" />
    <section
      aria-labelledby="settings-access"
      class="grid gap-5 lg:grid-cols-[15rem_minmax(0,48rem)] lg:gap-12"
    >
      <div>
        <h2 id="settings-access" class="text-xl font-semibold">Access</h2>
        <p class="mt-1 text-sm text-muted-foreground">
          Staff sign-in and authorization
        </p>
      </div>
      <ul class="grid gap-1">
        <li v-for="item in accessAreas" :key="item.area">
          <Link
            :href="route(item.route)"
            class="group flex items-center gap-4 rounded-md px-4 py-4 hover:bg-muted/60 focus-visible:outline-ring"
          >
            <component
              :is="item.icon"
              class="size-5 shrink-0 text-primary"
              aria-hidden="true"
            />
            <span class="grid flex-1 gap-1">
              <strong>
                {{ item.name }}
              </strong>
              <span class="text-sm text-muted-foreground">
                {{ item.description }}
              </span>
            </span>
            <ChevronRight
              class="size-4 shrink-0 text-muted-foreground"
              aria-hidden="true"
            />
          </Link>
        </li>
      </ul>
    </section>
  </AuthenticatedLayout>
</template>
