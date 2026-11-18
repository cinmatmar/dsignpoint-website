import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// SITE_URL / BASE_PATH are set by the GitHub staging workflow.
// Production (dsignpoint.com) uses the defaults.
const site = process.env.SITE_URL || 'https://dsignpoint.com';
const base = process.env.BASE_PATH || '/';

export default defineConfig({
  site,
  base,
  trailingSlash: 'always',
  build: { format: 'directory', inlineStylesheets: 'auto' },
  integrations: [sitemap()],
});
