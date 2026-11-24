<script setup>
import { Head } from '@inertiajs/vue3'

import LoginButton from '@/components/feature/auth/LoginButton.vue'
import StaffFooter from '@/components/layout/footer/StaffFooter.vue'
import { Card } from '@/components/ui/card'
</script>
<template>
  <div class="signal-shell">
    <Head title="Staff sign in" />
    <main class="signal-signin">
      <Card class="p-6 sm:p-9">
        <div class="signal-wordmark">
          <span class="signal-mark" aria-hidden="true"><i /><i /><i /></span
          ><span>Prologue<small>Control room</small></span>
        </div>
        <h1>Staff sign in</h1>
        <p>Sign in to look up shipments and manage customer tracking pages.</p>
        <p
          v-if="$page.props.errors?.email"
          role="alert"
          class="text-destructive"
        >
          {{ $page.props.errors.email }}
        </p>
        <LoginButton provider="azure" size="lg" />
      </Card>
    </main>
    <StaffFooter />
  </div>
</template>
