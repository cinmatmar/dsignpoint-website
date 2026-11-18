// Site-wide settings. Edit here, not in the pages.
export const SITE_NAME = 'DsignPoint';
export const PROD_URL = 'https://dsignpoint.com';

// Google Analytics 4 measurement ID, e.g. 'G-ABC123XYZ'. Leave '' to disable.
export const GA_ID = '';

// Meta (Facebook) Pixel ID. Leave '' until the pixel exists.
export const META_PIXEL_ID = '';

// Form endpoint. Production runs the PHP file on Infomaniak.
// On staging (GitHub Pages has no PHP) the form runs in demo mode.
export const FORM_ENDPOINT = import.meta.env.PUBLIC_STAGING ? '' : 'form.php';

export const CAL_LINK = 'https://cal.com/dsignpoint/discovery-call';
export const IS_STAGING = !!import.meta.env.PUBLIC_STAGING;
