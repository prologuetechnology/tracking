const targetSelector = `[data-design-field], [data-section]`

export function attachStudioInspector(frameDocument, select, cancel) {
  const marked = new Map()
  const style = frameDocument.createElement(`style`)
  style.textContent = `
    [data-design-field], [data-section] { cursor: crosshair; }
    [data-design-field]:hover, [data-section]:hover, [data-design-field]:focus-visible, [data-section]:focus-visible {
      outline: 3px dashed hsl(var(--primary)); outline-offset: -3px;
    }
  `
  frameDocument.head.append(style)
  const markTargets = () => {
    for (const target of frameDocument.querySelectorAll(targetSelector)) {
      if (marked.has(target)) continue
      marked.set(target, target.getAttribute(`tabindex`))
      target.tabIndex = 0
    }
  }
  markTargets()
  const observer = new MutationObserver(markTargets)
  observer.observe(frameDocument.body, { childList: true, subtree: true })
  const choose = (event) => {
    const element = event.target.closest?.(targetSelector)
    if (!element) return
    event.preventDefault()
    event.stopPropagation()
    const field = element.dataset.designField
    select(field || `section`, field ? null : element.dataset.section)
  }
  const keydown = (event) => {
    if (event.key === `Escape`) {
      event.preventDefault()
      cancel()
    } else if (event.key === `Enter` || event.key === ` `) choose(event)
  }
  frameDocument.addEventListener(`click`, choose, true)
  frameDocument.addEventListener(`keydown`, keydown, true)
  return () => {
    observer.disconnect()
    frameDocument.removeEventListener(`click`, choose, true)
    frameDocument.removeEventListener(`keydown`, keydown, true)
    style.remove()
    for (const [target, original] of marked) {
      if (original === null) target.removeAttribute(`tabindex`)
      else target.setAttribute(`tabindex`, original)
    }
  }
}
