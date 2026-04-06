import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'

const getTheme = async (themeId) => {
  const { data } = await axios.get(route(`api.themes.show`, themeId))

  return data
}

const useThemeQuery = ({ config = {}, id = null } = {}) =>
  useQuery({
    queryKey: [`themes`, id],
    queryFn: () => getTheme(id),

    ...config,
  })

export default useThemeQuery
