<?php
// Copy this file to form-config.php ON THE SERVER (Infomaniak) and fill it in.
// form-config.php is never committed to GitHub and is never overwritten by deploys.
return [
    'brevo_api_key'   => 'PASTE-YOUR-BREVO-API-KEY-HERE',
    'list_id'         => 2,                          // numeric ID of the "Website leads" list in Brevo
    'sender_email'    => 'info@dsignpoint.com',      // must be a verified sender in Brevo
    'sender_name'     => 'Cindy at DsignPoint',
    'notify_email'    => 'info@dsignpoint.com',      // where new-lead notifications arrive
    'cal_link'        => 'https://cal.com/dsignpoint/discovery-call',
    'allowed_origins' => ['https://dsignpoint.com', 'https://www.dsignpoint.com'],
    'turnstile_secret' => '',                        // optional Cloudflare Turnstile secret
];
