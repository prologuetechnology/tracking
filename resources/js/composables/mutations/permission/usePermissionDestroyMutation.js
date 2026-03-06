import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

const destroyPermission = async (id) => {
  const { data } = await axios.delete(
    route(`api.admin.permissions.destroy`, id),
  )

  return data
}

const usePermissionDestroyMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ id }) => destroyPermission(id),

    ...config,
  })

export default usePermissionDestroyMutation
