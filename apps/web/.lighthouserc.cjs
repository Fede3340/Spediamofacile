// Lighthouse CI config — guardrail performance/accessibility/SEO per PR.
// Runner: `npm run lighthouse` (locale) o job CI `lighthouse` su GitHub Actions.
// Build prod richiesta a monte (`npm run build`); avviamo .output/server/index.mjs come preview.
module.exports = {
  ci: {
    collect: {
      // Server preview Nuxt 4 (output Nitro). PORT=3000 coerente con job Playwright.
      startServerCommand: 'node .output/server/index.mjs',
      startServerReadyPattern: 'Listening on',
      startServerReadyTimeout: 60000,
      url: [
        'http://localhost:3000/',
        'http://localhost:3000/preventivo',
        'http://localhost:3000/chi-siamo',
        'http://localhost:3000/faq',
        'http://localhost:3000/contatti',
      ],
      numberOfRuns: 2, // riduce variabilita
      settings: {
        preset: 'desktop',
        // Skip audit fragili in headless CI (SW/PWA non target del progetto).
        skipAudits: ['uses-http2', 'canonical'],
      },
    },
    assert: {
      assertions: {
        'categories:performance': ['warn', { minScore: 0.85 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:best-practices': ['warn', { minScore: 0.9 }],
        'categories:seo': ['error', { minScore: 0.95 }],
        // Core Web Vitals soft warnings
        'cumulative-layout-shift': ['warn', { maxNumericValue: 0.1 }],
        'largest-contentful-paint': ['warn', { maxNumericValue: 2500 }],
        'total-blocking-time': ['warn', { maxNumericValue: 300 }],
      },
    },
    upload: {
      target: 'temporary-public-storage',
    },
  },
};
