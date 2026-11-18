import { IS_STAGING, PROD_URL } from '../config';
export const GET = () =>
  new Response(
    IS_STAGING
      ? 'User-agent: *\nDisallow: /\n'
      : `User-agent: *\nAllow: /\n\nSitemap: ${PROD_URL}/sitemap-index.xml\n`,
    { headers: { 'Content-Type': 'text/plain; charset=utf-8' } },
  );
