import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const deleteUserRole = async (id) => {
  const { data } = await axios.delete(route(`api.admin.roles.destroy`, id))
  return data
}

const useUserRolesDeleteMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: (id) => deleteUserRole(id),
    ...config,
  })

export default useUserRolesDeleteMutation
