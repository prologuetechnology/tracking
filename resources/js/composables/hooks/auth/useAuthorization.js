import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const useAuthorization = () => {
  const page = usePage()

  const auth = computed(() => page.props.auth ?? {})
  const user = computed(() => auth.value.user ?? null)
  const roles = computed(() => auth.value.roles ?? [])
  const permissions = computed(() => auth.value.permissions ?? [])

  const hasRole = (role) => roles.value.includes(role)
  const can = (permission) => permissions.value.includes(permission)
  const canAny = (permissionNames) =>
    permissionNames.some((permission) => can(permission))
  const cannot = (permission) => !can(permission)

  return {
    user,
    roles,
    permissions,
    hasRole,
    can,
    canAny,
    cannot,
  }
}

export default useAuthorization
