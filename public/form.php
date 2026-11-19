<?php
// DsignPoint lead form endpoint (runs on Infomaniak PHP hosting).
// Receives JSON from /book-a-call/, saves the contact in Brevo, emails the lead
// a confirmation with the booking link, and notifies info@dsignpoint.com.
// The Brevo API key lives ONLY in form-config.php on the server (never in GitHub).

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $body): void { http_response_code($code); echo json_encode($body); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false]);

$cfgFile = __DIR__ . '/form-config.php';
if (!is_file($cfgFile)) out(500, ['ok' => false, 'error' => 'not_configured']);
$cfg = require $cfgFile;

// Only accept posts from our own site
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, $cfg['allowed_origins'], true)) out(403, ['ok' => false]);

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 20000) out(400, ['ok' => false]);
$d = json_decode($raw, true);
if (!is_array($d)) out(400, ['ok' => false]);

// Spam protection: honeypot + minimum fill time + simple per-IP rate limit
if (!empty($d['website'])) out(200, ['ok' => true]); // bots get a silent "success"
$ts = (int)($d['ts'] ?? 0);
if ($ts > 0 && (time() * 1000 - $ts) < 3000) out(200, ['ok' => true]);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
$rl = sys_get_temp_dir() . '/dp_rl_' . md5($ip);
$hits = is_file($rl) ? array_filter(array_map('intval', explode(',', (string)file_get_contents($rl))), fn($t) => $t > time() - 3600) : [];
if (count($hits) >= 10) out(429, ['ok' => false, 'error' => 'rate_limited']);
$hits[] = time(); @file_put_contents($rl, implode(',', $hits));

// Optional Cloudflare Turnstile
if (!empty($cfg['turnstile_secret'])) {
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => ['secret' => $cfg['turnstile_secret'], 'response' => (string)($d['turnstile'] ?? ''), 'remoteip' => $ip]]);
    $r = json_decode((string)curl_exec($ch), true); curl_close($ch);
    if (empty($r['success'])) out(400, ['ok' => false, 'error' => 'captcha']);
}

// Validate
$clean = fn($v, int $max) => mb_substr(trim(strip_tags((string)$v)), 0, $max);
$name = $clean($d['name'] ?? '', 120);
$email = $clean($d['email'] ?? '', 200);
$business = $clean($d['business'] ?? '', 200);
$stage = $clean($d['stage'] ?? '', 120);
$message = $clean($d['message'] ?? '', 3000);
if ($name === '' || $business === '' || $message === '' || empty($d['consent'])) out(422, ['ok' => false, 'error' => 'missing']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'email']);
$first = explode(' ', $name)[0];

function brevo(string $key, string $method, string $path, array $payload): array {
    $ch = curl_init('https://api.brevo.com/v3' . $path);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['accept: application/json', 'content-type: application/json', 'api-key: ' . $key],
        CURLOPT_POSTFIELDS => json_encode($payload)]);
    $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$code, json_decode((string)$body, true)];
}

$key = $cfg['brevo_api_key'];
$h = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// 1) Save / update the contact in the "Website leads" list
[$c1, $b1] = brevo($key, 'POST', '/contacts', [
    'email' => $email, 'listIds' => [(int)$cfg['list_id']], 'updateEnabled' => true,
    'attributes' => ['FIRSTNAME' => $first, 'LASTNAME' => trim(mb_substr($name, mb_strlen($first)))],
]);

// 2) Confirmation to the lead, with the booking link
$cal = $cfg['cal_link'];
$confirm = '<div style="font-family:Arial,Helvetica,sans-serif;background:#f8f1ea;padding:32px 16px;">'
 . '<div style="max-width:560px;margin:0 auto;background:#ffffff;padding:40px 36px;color:#2a1d1a;">'
 . '<p style="font-family:Georgia,serif;font-size:26px;line-height:1.2;margin:0 0 18px;color:#5a1626;">Thank you, ' . $h($first) . '. We&rsquo;ve got your request.</p>'
 . '<p style="font-size:16px;line-height:1.7;margin:0 0 16px;">I&rsquo;ve read what you shared about <strong>' . $h($business) . '</strong>, and I&rsquo;m looking forward to understanding where your business is and where you want to take it.</p>'
 . '<p style="font-size:16px;line-height:1.7;margin:0 0 26px;">The next step is to pick a time that suits you for your free 30-minute discovery call:</p>'
 . '<p style="margin:0 0 30px;"><a href="' . $h($cal) . '" style="display:inline-block;background:#5a1626;color:#f8f1ea;text-decoration:none;font-size:14px;letter-spacing:.08em;text-transform:uppercase;padding:16px 30px;">Book your call &rarr;</a></p>'
 . '<p style="font-size:15px;line-height:1.7;margin:0 0 6px;">You can simply reply to this email if you have any questions before we talk.</p>'
 . '<p style="font-size:15px;line-height:1.7;margin:24px 0 0;">Talk soon,<br><strong>Cindy</strong><br><span style="color:#7a6a62;">Founder, DsignPoint</span></p>'
 . '</div><p style="text-align:center;font-size:12px;color:#8a7a72;margin:18px 0 0;">DSIGNPOINT SC &middot; Torrevieja, Spain &middot; <a href="https://dsignpoint.com/privacy-policy/" style="color:#8a7a72;">Privacy Policy</a></p></div>';
[$c2, $b2] = brevo($key, 'POST', '/smtp/email', [
    'sender' => ['name' => $cfg['sender_name'], 'email' => $cfg['sender_email']],
    'to' => [['email' => $email, 'name' => $name]],
    'replyTo' => ['email' => $cfg['notify_email'], 'name' => $cfg['sender_name']],
    'subject' => 'Thanks ' . $first . ' — your DsignPoint discovery call link',
    'htmlContent' => $confirm,
]);

// 3) Notification to Cindy with the full request
$notify = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:#222;">'
 . '<h2 style="font-family:Georgia,serif;margin:0 0 14px;">New discovery-call request</h2>'
 . '<p><strong>Name:</strong> ' . $h($name) . '<br><strong>Email:</strong> ' . $h($email) . '<br><strong>Business:</strong> ' . $h($business) . '<br><strong>Stage:</strong> ' . $h($stage) . '</p>'
 . '<p><strong>Message:</strong><br>' . nl2br($h($message)) . '</p>'
 . '<p style="color:#777;font-size:13px;">The lead has been sent the booking link. They are saved in your Brevo list.</p></div>';
brevo($key, 'POST', '/smtp/email', [
    'sender' => ['name' => 'DsignPoint website', 'email' => $cfg['sender_email']],
    'to' => [['email' => $cfg['notify_email']]],
    'replyTo' => ['email' => $email, 'name' => $name],
    'subject' => 'New lead: ' . $name . ' (' . $business . ')',
    'htmlContent' => $notify,
]);

// The lead sees success as long as the confirmation email or the contact save worked
if (($c1 >= 200 && $c1 < 300) || ($c2 >= 200 && $c2 < 300)) out(200, ['ok' => true]);
error_log('DsignPoint form: Brevo failed ' . $c1 . '/' . $c2);
out(502, ['ok' => false, 'error' => 'upstream']);
