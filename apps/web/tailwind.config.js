// tailwind.config.js — design tokens SpediamoFacile
// Palette brand: teal primary + arancione accent. NO blu (regola prodotto).
// Usati come utility Tailwind: bg-brand-teal, text-brand-accent, ecc.
export default {
  theme: {
    extend: {
      colors: {
        brand: {
          teal: '#095866',
          'teal-hover': '#074a56',
          'teal-light': '#0b6d7d',
          'teal-soft-bg': '#f3f8f9',
          'teal-soft-border': '#c7d8de',
          'teal-soft-border-strong': '#98b4bc',
          'teal-soft-text': '#135a67',
          'teal-soft-text-strong': '#0a4954',
          accent: '#E44203',
          'accent-hover': '#c93800',
          'accent-dark': '#c73600',
          success: '#0a8a7a',
          error: '#ef4444',
          text: '#1D2738',
          'text-secondary': '#5A6474',
          'text-muted': '#6b7280',
          bg: '#ffffff',
          card: '#ffffff',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
        display: ['Montserrat', 'Inter', 'system-ui', 'sans-serif'],
      },
      backgroundImage: {
        'page-gradient': 'linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)',
      },
    },
  },
}
