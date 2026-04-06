import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const createPermission = async (formData) => {
  const { data } = await axios.post(route(`api.admin.permissions.store`), {
    ...formData,
  })

  return data
}

const usePermissionCreateMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ formData }) => createPermission(formData),

    ...config,
  })

export default usePermissionCreateMutation
