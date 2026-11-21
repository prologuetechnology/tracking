import { readFile } from 'node:fs/promises'
import { join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium } from 'playwright-core'

const root = fileURLToPath(new URL('../../', import.meta.url))
const origin = 'https://tracking-proof.invalid'
const types = {
  js: 'text/javascript',
  css: 'text/css',
  ttf: 'font/ttf',
  woff2: 'font/woff2',
}
const mapHosts = new Set([
  'api.trimblemaps.com',
  'maps.trimble.com',
  'tiles.alk.com',
  'pcmiler.alk.com',
  'authws.alk.com',
])
const viewports = {
  desktop: { width: 1440, height: 900 },
  mobile: { width: 390, height: 844 },
}

async function serveAsset(route, html, images) {
  const req = route.request()
  const url = new URL(req.url())
  if (url.origin === origin) {
    if (url.pathname === '/' && req.isNavigationRequest())
      return route.fulfill({ contentType: 'text/html', body: html })
    if (url.pathname === '/Figtree.ttf')
      return route.fulfill({
        contentType: 'font/ttf',
        path: join(root, 'resources/fonts/Figtree.ttf'),
      })
    if (/^\/build\/assets\/[\w.-]+$/.test(url.pathname))
      return route.fulfill({
        contentType: types[url.pathname.split('.').pop()],
        path: join(root, 'public', url.pathname),
      })
    if (url.pathname.startsWith('/proof-brand/')) {
      const key = decodeURIComponent(url.pathname.slice('/proof-brand/'.length))
      if (Object.hasOwn(images, key)) {
        const body = Buffer.from(images[key], 'base64')
        // Chromium needs an explicit MIME type for SVG branding.
        return route.fulfill({
          contentType: /\.svg$/i.test(key)
            ? 'image/svg+xml'
            : 'application/octet-stream',
          body,
        })
      }
    }
  }
  if (
    url.protocol === 'https:' &&
    mapHosts.has(url.hostname) &&
    !req.isNavigationRequest() &&
    ['xhr', 'fetch', 'image'].includes(req.resourceType())
  ) {
    try {
      const response = await route.fetch({ maxRedirects: 0, timeout: 8000 })
      if (response.status() >= 300 && response.status() < 400)
        return route.abort()
      return await route.fulfill({ response })
    } catch {
      return route.abort()
    }
  }
  return route.abort()
}

async function captureView(
  browser,
  view,
  viewport,
  { page, images, timezone, html, directory },
) {
  const context = await browser.newContext({
    viewport,
    deviceScaleFactor: 1,
    timezoneId: timezone,
    locale: 'en-US',
    reducedMotion: 'reduce',
    serviceWorkers: 'block',
  })
  const tab = await context.newPage()
  let pageFailed = false
  tab.on('pageerror', () => {
    pageFailed = true
  })
  await tab.clock.setFixedTime(new Date(page.props.frozenAt))
  await context.route('**/*', (route) => serveAsset(route, html, images))
  await tab.goto(origin, { waitUntil: 'domcontentloaded' })
  await tab.locator('main').first().waitFor()
  await tab.evaluate(async () => {
    await document.fonts.ready
    await Promise.all(Array.from(document.images, (image) => image.decode()))
  })
  // An open map can still have zero height while its Vue chunk loads.
  const maps = tab.locator('[data-proof-map]:not(details:not([open]) *)')
  for (const map of await maps.all()) {
    await map
      .locator('[data-map-ready="true"]')
      .waitFor({ state: 'attached', timeout: 20000 })
  }
  if (pageFailed) throw new Error('Tracking page failed to render')
  await tab.screenshot({
    path: join(directory, view + '.png'),
    fullPage: true,
    animations: 'disabled',
    timeout: 20000,
  })
  await context.close()
}

async function render({ page, images, chrome, timezone }, directory) {
  const manifest = JSON.parse(
    await readFile(join(root, 'public/build/manifest.json'), 'utf8'),
  )
  const js = manifest['resources/js/app.js'].file
  const css = manifest['resources/css/app.css'].file
  const pageData = JSON.stringify({
    ...page,
    url: '/',
    version: null,
    clearHistory: false,
    encryptHistory: false,
  })
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll('<', '&lt;')
  const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/build/${css}"><style>@font-face{font-family:Figtree;src:url('/Figtree.ttf');font-weight:300 900} footer{position:static!important}</style>
    <script type="module" src="/build/${js}"></script></head><body class="font-sans antialiased"><div id="app" data-page="${pageData}"></div></body></html>`
  const browser = await chromium.launch({
    ...(chrome ? { executablePath: chrome } : { channel: 'chrome' }),
    chromiumSandbox: true,
    env: Object.fromEntries(
      ['HOME', 'PATH', 'TMPDIR', 'LANG', 'SYSTEMROOT', 'XDG_RUNTIME_DIR']
        .filter((key) => process.env[key])
        .map((key) => [key, process.env[key]]),
    ),
  })
  try {
    for (const [view, viewport] of Object.entries(viewports)) {
      await captureView(browser, view, viewport, {
        page,
        images,
        timezone,
        html,
        directory,
      })
    }
  } finally {
    await browser.close()
  }
}

try {
  const chunks = []
  for await (const chunk of process.stdin) chunks.push(chunk)
  await render(JSON.parse(Buffer.concat(chunks)), process.argv[2])
} catch (error) {
  // Browser exceptions can contain shipment data or private asset URLs.
  process.stderr.write('Proof renderer failed (' + error.name + ').\n')
  process.exitCode = 1
}
