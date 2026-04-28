/**
 * Sitemap source: /guide/*
 *
 * Restituisce un array di URL entries basato sulle guide pubblicate
 * (lastmod = updated_at reale). Usato dal modulo @nuxtjs/sitemap.
 */
import type { SitemapUrlInput } from '#sitemap/types'
import { fetchArticleList } from '../../utils/sitemapSources'

export default defineSitemapEventHandler(async (event: any): Promise<SitemapUrlInput[]> => {
  const items = await fetchArticleList(event, 'guides')

  return items.map((item) => ({
    loc: `/guide/${item.slug}`,
    lastmod: item.updated_at || item.created_at || undefined,
    changefreq: 'monthly',
    priority: 0.7,
  }))
})
