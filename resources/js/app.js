import { createInertiaApp } from '@inertiajs/vue3'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

import { ZiggyVue } from '../../vendor/tightenco/ziggy'

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
      .use(VueQueryPlugin, { enableDevtoolsV6Plugin: true })
      .use(ZiggyVue)
      .mount(el)
  },
  progress: { color: `#4B5563` },
})
