import { getProxyRequestHeaders, getRequestHeader, getRequestURL, proxyRequest } from 'h3'

const normalizeBase = (value: string) => value.replace(/\/$/, '')

const joinPath = (base: string, prefix: string, suffix?: string) => {
  const cleanBase = normalizeBase(base)
  const cleanPrefix = prefix.replace(/^\/+|\/+$/g, '')
  const cleanSuffix = (suffix || '').replace(/^\/+/, '')

  return cleanSuffix
    ? `${cleanBase}/${cleanPrefix}/${cleanSuffix}`
    : `${cleanBase}/${cleanPrefix}`
}

const resolveDirectBackendBase = (apiBase: string) => {
  try {
    const url = new URL(apiBase)
    if (!['127.0.0.1', 'localhost'].includes(url.hostname)) return null
    if (url.port !== '8787') return null
    url.port = '8000'
    return normalizeBase(url.toString())
  } catch {
    return null
  }
}

export const proxyToBackend = async (event: Parameters<typeof proxyRequest>[0], prefix: string) => {
  const config = useRuntimeConfig(event)
  const apiBase = normalizeBase(String(config.public.apiBase || 'http://127.0.0.1:8787'))
  const suffix = event.context.params?.path
  const search = getRequestURL(event).search || ''
  const directBackendBase = resolveDirectBackendBase(apiBase)
  const targets = [
    directBackendBase,
    apiBase,
  ].filter((value, index, list): value is string => Boolean(value) && list.indexOf(value) === index)

  // h3 proxyRequest strips the Accept header (it's in the ignoredHeaders set).
  // Without Accept: application/json, Laravel returns HTML redirects instead
  // of JSON 401 responses for unauthenticated API requests, which breaks
  // Sanctum session-based auth (the browser follows the redirect silently
  // and gets an HTML page instead of a proper 401 JSON error).
  const accept = getRequestHeader(event, 'accept') || 'application/json'

  // Sanctum's EnsureFrontendRequestsAreStateful middleware checks the Referer
  // or Origin header to determine if the request is "from the frontend".
  // If neither header is present (e.g. during SSR or direct server-to-server
  // calls), Sanctum treats the request as stateless and skips the session
  // middleware entirely, causing 401 on authenticated endpoints.
  // We ensure at least one of these headers is always present so Sanctum
  // correctly identifies the request as stateful and starts the session.
  const origin = getRequestHeader(event, 'origin')
  const referer = getRequestHeader(event, 'referer')
  const requestHeaders = {
    ...getProxyRequestHeaders(event),
    accept,
  } as Record<string, string>

  if (!origin) {
    requestHeaders.origin = apiBase
  }

  if (!referer) {
    requestHeaders.referer = `${apiBase}/`
  }

  let lastError: unknown = null

  for (const base of targets) {
    const target = `${joinPath(base, prefix, suffix)}${search}`
    try {
      return await proxyRequest(event, target, {
        fetchOptions: {
          headers: requestHeaders,
        },
      })
    } catch (error) {
      lastError = error
    }
  }

  throw lastError
}
