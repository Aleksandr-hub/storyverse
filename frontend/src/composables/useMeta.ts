import { onMounted, onUnmounted, watch, type Ref } from 'vue'

interface MetaOptions {
  title?: string | Ref<string | undefined>
  description?: string | Ref<string | undefined>
  image?: string | Ref<string | undefined>
  url?: string
  type?: 'website' | 'article'
}

const defaultTitle = 'StoryVerse - AI-Powered Creative Writing Platform'
const defaultDescription = 'Пиши фанфіки, створюй оригінальні історії з AI-помічником. Спільне писання з друзями, Adventure Mode з AI Dungeon Master.'

function setMeta(name: string, content: string, property = false) {
  const attr = property ? 'property' : 'name'
  let element = document.querySelector(`meta[${attr}="${name}"]`)

  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attr, name)
    document.head.appendChild(element)
  }

  element.setAttribute('content', content)
}

function updateMeta(options: MetaOptions) {
  const getValue = (val: string | Ref<string | undefined> | undefined) =>
    typeof val === 'object' && 'value' in val ? val.value : val

  const title = getValue(options.title) || defaultTitle
  const description = getValue(options.description) || defaultDescription
  const image = getValue(options.image) || '/og-image.png'
  const url = options.url || window.location.href
  const type = options.type || 'website'

  // Update document title
  document.title = title

  // Standard meta tags
  setMeta('description', description)

  // Open Graph
  setMeta('og:title', title, true)
  setMeta('og:description', description, true)
  setMeta('og:image', image, true)
  setMeta('og:url', url, true)
  setMeta('og:type', type, true)

  // Twitter
  setMeta('twitter:title', title, true)
  setMeta('twitter:description', description, true)
  setMeta('twitter:image', image, true)
}

export function useMeta(options: MetaOptions) {
  const stopWatchers: (() => void)[] = []

  onMounted(() => {
    updateMeta(options)

    // Watch for reactive changes
    const watchables = [options.title, options.description, options.image].filter(
      (v): v is Ref<string | undefined> => typeof v === 'object' && 'value' in v
    )

    watchables.forEach((ref) => {
      const stop = watch(ref, () => updateMeta(options))
      stopWatchers.push(stop)
    })
  })

  onUnmounted(() => {
    stopWatchers.forEach((stop) => stop())
    // Reset to defaults
    document.title = defaultTitle
    setMeta('description', defaultDescription)
    setMeta('og:title', defaultTitle, true)
    setMeta('og:description', defaultDescription, true)
    setMeta('og:image', '/og-image.png', true)
    setMeta('og:url', window.location.origin, true)
    setMeta('og:type', 'website', true)
    setMeta('twitter:title', defaultTitle, true)
    setMeta('twitter:description', defaultDescription, true)
    setMeta('twitter:image', '/og-image.png', true)
  })
}
