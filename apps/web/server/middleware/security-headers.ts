/**
 * Nitro Server Middleware: Security Headers
 *
 * Aggiunge intestazioni di sicurezza a TUTTE le risposte del frontend Nuxt.
 * Complementa il SecurityHeaders middleware di Laravel (che copre solo le API).
 *
 * Headers aggiunti:
 *   - X-Content-Type-Options: previene MIME sniffing
 *   - X-Frame-Options: previene clickjacking
 *   - X-XSS-Protection: protezione XSS del browser
 *   - Referrer-Policy: limita informazioni nel referer
 *   - Permissions-Policy: disabilita accesso a fotocamera/microfono
 *   - Strict-Transport-Security: forza HTTPS (solo se già su HTTPS)
 */
export default defineEventHandler((event) => {
  const headers = event.node.res

  headers.setHeader('X-Content-Type-Options', 'nosniff')
  headers.setHeader('X-Frame-Options', 'SAMEORIGIN')
  headers.setHeader('X-XSS-Protection', '1; mode=block')
  headers.setHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
  headers.setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)')

  // Content-Security-Policy — defense-in-depth (duplica nuxt-security CSP)
  headers.setHeader(
    'Content-Security-Policy',
    [
      "default-src 'self'",
      "script-src 'self' 'unsafe-inline' https://js.stripe.com",
      "style-src 'self' 'unsafe-inline'",
      "img-src 'self' data: https:",
      "font-src 'self'",
      "connect-src 'self' https://api.stripe.com https://*.trycloudflare.com https://nominatim.openstreetmap.org",
      "frame-src 'self' https://js.stripe.com https://hooks.stripe.com",
      "object-src 'none'",
      "base-uri 'self'",
      "form-action 'self'", // form solo verso il proprio dominio (anti-phishing)
      "frame-ancestors 'self'", // moderno equivalente di X-Frame-Options (browser nuovi)
      "upgrade-insecure-requests", // forza HTTPS su risorse miste in produzione
    ].join('; ')
  )

  // HSTS solo su connessioni HTTPS (in produzione dietro Cloudflare/proxy)
  // preload: requisito per essere inclusi nella HSTS preload list dei browser
  const proto = event.node.req.headers['x-forwarded-proto']
  if (proto === 'https') {
    headers.setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
  }
})
