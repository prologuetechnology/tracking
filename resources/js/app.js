import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

import { ZiggyVue } from '../../vendor/tightenco/ziggy'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000,
      gcTime: 10 * 60 * 1000,
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
})

createInertiaApp({
  title: (title) => `${title}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob(`./pages/**/*.vue`),
    ),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(VueQueryPlugin, { queryClient, enableDevtoolsV6Plugin: true })
      .use(ZiggyVue)
      .mount(el)
  },
  progress: { color: `#4B5563` },
})
