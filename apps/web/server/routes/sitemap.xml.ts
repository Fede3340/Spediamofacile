/**
 * ROUTE SERVER: /sitemap.xml
 *
 * Sitemap dinamica generata a runtime con lastmod = data ultima build.
 * Elenca solo le pagine pubbliche statiche note (no funnel, no auth, no API).
 *
 * Le pagine dinamiche (guide, servizi) sono gia' coperte dal modulo
 * @nuxtjs/sitemap che pubblica `/__sitemap__/<source>` come sitemap-index.
 * Questo handler ha priorita' nel router h3 e risponde a `/sitemap.xml`
 * direttamente, fornendo l'elenco core piu' affidabile per i crawler.
 *
 * Content-Type: application/xml (UTF-8).
 */
import { defineEventHandler, setHeader } from 'h3'

type SitemapEntry = {
  loc: string
  changefreq: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never'
  priority: number
}

// Pagine pubbliche note. Priority/changefreq calibrati su rilevanza SEO:
// - homepage e preventivo: massima (entry point conversione)
// - servizi/guide/traccia: alta (long-tail informativo)
// - faq/contatti/chi-siamo: media (supporto)
// - legali: bassa (rare modifiche)
const PUBLIC_PAGES: readonly SitemapEntry[] = [
  { loc: '/', changefreq: 'weekly', priority: 1.0 },
  { loc: '/preventivo', changefreq: 'weekly', priority: 0.9 },
  { loc: '/servizi', changefreq: 'monthly', priority: 0.8 },
  { loc: '/traccia-spedizione', changefreq: 'weekly', priority: 0.8 },
  { loc: '/guide', changefreq: 'weekly', priority: 0.7 },
  { loc: '/pudo', changefreq: 'monthly', priority: 0.7 },
  { loc: '/chi-siamo', changefreq: 'monthly', priority: 0.7 },
  { loc: '/contatti', changefreq: 'monthly', priority: 0.7 },
  { loc: '/faq', changefreq: 'monthly', priority: 0.6 },
  // -- ARCHIVIATO 2026-04-20: '/reclami' (_archive/frontend-simplification-2026-04-20/features/reclami-dedicato) --
  { loc: '/privacy-policy', changefreq: 'yearly', priority: 0.3 },
  { loc: '/termini-condizioni', changefreq: 'yearly', priority: 0.3 },
  { loc: '/cookie-policy', changefreq: 'yearly', priority: 0.3 },
] as const

// Data build congelata al momento dell'import del modulo (= cold start del server).
// Per Nitro su deploy statico questo coincide con il timestamp di build.
const BUILD_LASTMOD = new Date().toISOString().split('T')[0]

const escapeXml = (value: string): string =>
  value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')

const buildSitemap = (baseUrl: string): string => {
  const cleanBase = baseUrl.replace(/\/+$/, '')

  const urls = PUBLIC_PAGES.map((entry) => {
    const path = entry.loc === '/' ? '' : entry.loc
    const fullUrl = escapeXml(`${cleanBase}${path}`)
    return `  <url>
    <loc>${fullUrl}</loc>
    <lastmod>${BUILD_LASTMOD}</lastmod>
    <changefreq>${entry.changefreq}</changefreq>
    <priority>${entry.priority.toFixed(1)}</priority>
  </url>`
  }).join('\n')

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>
`
}

export default defineEventHandler((event) => {
  const runtimeConfig = useRuntimeConfig(event)
  const baseUrl =
    (runtimeConfig.public?.siteUrl as string | undefined) || 'https://spediamofacile.it'

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  // Cache 1h CDN + 1h browser: sitemap si aggiorna al massimo a ogni redeploy.
  setHeader(event, 'Cache-Control', 'public, max-age=3600, s-maxage=3600')

  return buildSitemap(baseUrl)
})
