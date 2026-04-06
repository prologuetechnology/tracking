import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const updateUserRole = async ({ id, formData }) => {
  const { data } = await axios.put(
    route(`api.admin.roles.update`, id),
    formData,
  )
  return data
}

const useUserRolesUpdateMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ id, formData }) => updateUserRole({ id, formData }),
    ...config,
  })

export default useUserRolesUpdateMutation
