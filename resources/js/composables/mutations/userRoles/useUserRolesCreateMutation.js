import { useMutation } from '@tanstack/vue-query'
import axios from 'axios'

// API call to create a role
const createUserRole = async (formData) => {
  const { data } = await axios.post(route(`api.admin.roles.store`), formData)
  return data
}

// Composable wrapping vue-query mutation
const useUserRolesCreateMutation = ({ config = {} } = {}) =>
  useMutation({
    mutationFn: ({ formData }) => createUserRole(formData),

    ...config,
  })

export default useUserRolesCreateMutation
