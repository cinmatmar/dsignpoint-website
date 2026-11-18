# dsignpoint-website

Static site for [dsignpoint.com](https://dsignpoint.com), built with [Astro](https://astro.build).

## How it works
- `src/pages/` one folder per page (clean URLs, e.g. `/about/`).
- `src/layouts/Layout.astro` shared SEO tags, cookie banner, analytics loader, mobile menu.
- `src/config.ts` Google Analytics ID, Meta Pixel ID, Cal.com link.
- `public/form.php` receives the Book a Call form and talks to Brevo (runs on Infomaniak PHP).
- Every push to `main` publishes a hidden **staging** copy on GitHub Pages (noindex).
- Going live is a manual action: **Actions > Deploy to dsignpoint.com > Run workflow**. It waits for your approval.

## One-time setup on the server
1. Create `form-config.php` next to `index.html` on Infomaniak (copy `public/form-config.sample.php`, paste your Brevo API key and list ID). It is never stored in GitHub and deploys never touch it.
2. GitHub secrets (Settings > Secrets and variables > Actions): `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR`.

## Local
```
npm install
npm run dev
npm run build
```
