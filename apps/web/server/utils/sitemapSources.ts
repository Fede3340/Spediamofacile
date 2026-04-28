/**
 * Helper condiviso per le sitemap sources dinamiche (Sprint 8.3).
 *
 * Chiama gli endpoint Laravel pubblici /api/public/{type} che restituiscono
 * la lista articoli pubblicati (guide/service) con columns ridotte
 * piu' created_at/updated_at per lastmod sitemap.
 *
 * Error-safe: in caso di backend down torna [] cosi' la sitemap.xml
 * contiene comunque le route statiche e non fallisce il build.
 */
import type { H3Event } from 'h3'

export type ArticleSitemapItem = {
  slug: string
  created_at?: string
  updated_at?: string
}

export type ArticleEndpointKind = 'guides' | 'services'

const normalizeBase = (value: string) => value.replace(/\/$/, '')

export const fetchArticleList = async (
  event: H3Event,
  kind: ArticleEndpointKind,
): Promise<ArticleSitemapItem[]> => {
  const config = useRuntimeConfig(event)
  const apiBase = normalizeBase(String(config.public.apiBase || 'http://127.0.0.1:8787'))
  const url = `${apiBase}/api/public/${kind}`

  try {
    const response = await $fetch<{ data?: ArticleSitemapItem[] } | ArticleSitemapItem[]>(url, {
      method: 'GET',
      headers: { accept: 'application/json' },
      // Timeout contenuto: la sitemap non deve bloccare > 5s per backend slow
      timeout: 5000,
    })

    const list = Array.isArray(response)
      ? response
      : Array.isArray(response?.data)
        ? response.data
        : []

    return list.filter((item): item is ArticleSitemapItem => Boolean(item?.slug))
  } catch (err) {
    // Log ma non throw: sitemap deve sempre generarsi
    console.warn(`[sitemap] fetch ${kind} failed:`, (err as Error)?.message || err)
    return []
  }
}
