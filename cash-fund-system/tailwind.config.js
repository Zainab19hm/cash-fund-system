const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        bg:          'var(--bg)',
        surface:     'var(--surface)',
        primary:     'var(--primary)',
        accent:      'var(--accent)',
        text:        'var(--text)',
        'bdr':       'var(--border)',
        'muted':     'var(--muted)',
      },
      fontFamily: {
        heading: ['"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
        body:    ['"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
        tajawal: ['"Tajawal"', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [],
}
