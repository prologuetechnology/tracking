import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'
import { computed, unref } from 'vue'

const getImages = async (imageTypeId) => {
  const { data } = await axios.get(route(`api.images.index`), {
    params: {
      image_type_id: imageTypeId,
    },
  })

  return data
}

const useImagesQuery = ({ config = {}, imageTypeId = null } = {}) => {
  const resolvedImageTypeId = computed(() => unref(imageTypeId))
  const resolvedConfig = computed(() => unref(config) ?? {})

  return useQuery({
    queryKey: computed(() => [`images`, resolvedImageTypeId.value ?? `all`]),
    queryFn: () => getImages(resolvedImageTypeId.value),

    ...resolvedConfig.value,
  })
}

export default useImagesQuery
