import useAuthorization from './useAuthorization'

const useRolesAndPermissions = () => {
  const { hasRole, can, cannot } = useAuthorization()

  return {
    userIs: hasRole,
    userCan: can,
    userCannot: cannot,
  }
}

export default useRolesAndPermissions
