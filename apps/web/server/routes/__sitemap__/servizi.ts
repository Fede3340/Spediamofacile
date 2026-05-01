/**
 * Sitemap source: /servizi/*
 *
 * Restituisce un array di URL entries basato sui servizi pubblicati
 * (lastmod = updated_at reale). Usato dal modulo @nuxtjs/sitemap.
 * Priorita' piu' alta (0.8) perche' i servizi sono core business.
 */
import type { SitemapUrlInput } from '#sitemap/types'
import type { H3Event } from 'h3'
import { fetchArticleList } from '../../utils/sitemapSources'

export default defineSitemapEventHandler(async (event: H3Event): Promise<SitemapUrlInput[]> => {
  const items = await fetchArticleList(event, 'services')

  return items.map((item) => ({
    loc: `/servizi/${item.slug}`,
    lastmod: item.updated_at || item.created_at || undefined,
    changefreq: 'monthly',
    priority: 0.8,
  }))
})
