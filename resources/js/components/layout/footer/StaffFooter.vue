<script setup>
import { Link, usePage } from '@inertiajs/vue3'
const page = usePage()
</script>
<template>
  <footer data-staff-footer class="staff-footer">
    <div>
      <span>
        <a
          href="https://prologuetechnology.com/"
          target="_blank"
          rel="noopener noreferrer"
        >
          Prologue Technology
        </a>
        <span aria-hidden="true">/</span>
        Staff workspace
      </span>
      <Link
        v-if="page.props.auth?.user && route().has('admin.portal-review.index')"
        :href="
          page.component === 'admin/portalReview/Workbench'
            ? route('admin.dashboard')
            : route('admin.portal-review.index')
        "
        >{{
          page.component === 'admin/portalReview/Workbench'
            ? 'Return to current admin'
            : 'Preview new staff portal'
        }}</Link
      >
      <span>
        {{ page.props.app.name }}
        <template v-if="page.props.app.env !== 'production'">
          · {{ page.props.app.env }}
        </template>
      </span>
    </div>
  </footer>
</template>
<style scoped>
.staff-footer {
  color-scheme: light;
  flex-shrink: 0;
  width: 100%;
  background: #f0f2ec;
  color: #4b564f;
  border-top: 1px solid #d6dbd3;
  font:
    500 0.75rem/1.4 Figtree,
    system-ui,
    sans-serif;
}
.staff-footer > div {
  min-height: 3rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.25rem 1rem;
  padding: 0.7rem clamp(1rem, 3vw, 3rem);
}
.staff-footer a {
  font-weight: 700;
  color: #29392f;
}
.staff-footer a:hover,
.staff-footer a:focus-visible {
  text-decoration: underline;
}
.staff-footer span[aria-hidden] {
  padding-inline: 0.25rem;
  color: #748375;
}
</style>
