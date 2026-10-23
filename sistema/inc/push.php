<?php
/* ============================================================
   VST Ponto — Web Push (phone notifications, even with the app closed).
   No library needed: pushes are sent WITHOUT payload (so nothing to
   encrypt) and signed with a VAPID key (ES256, PHP's openssl).
   The phone's service worker then fetches the pending rows from
   pt_notifications while signed in, and shows them.
   ============================================================ */

function b64url(string $bin): string { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }

/* VAPID key pair, created once and kept in pt_settings. Returns [privatePem, publicB64url] or null. */
function vapid_keys(): ?array {
    $priv = setting_raw('vapid_private');
    $pub  = setting_raw('vapid_public');
    if ($priv && $pub) return [$priv, $pub];

    $key = @openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    if (!$key) { error_log('VAPID: could not create EC key (openssl)'); return null; }
    openssl_pkey_export($key, $pem);
    $d = openssl_pkey_get_details($key)['ec'];
    $raw = "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    $pub = b64url($raw);
    $up = pt_db()->prepare("INSERT IGNORE INTO pt_settings (skey, svalue) VALUES (?, ?)");
    $up->execute(['vapid_private', $pem]);
    $up->execute(['vapid_public', $pub]);
    // Another request may have won the race: always use what is stored.
    return [setting_raw('vapid_private'), setting_raw('vapid_public')];
}
function setting_raw(string $key): string {
    $st = pt_db()->prepare("SELECT svalue FROM pt_settings WHERE skey = ?");
    $st->execute([$key]);
    return (string)$st->fetchColumn();
}

/* openssl gives an ASN.1 DER signature; JWT ES256 wants raw r||s (64 bytes). */
function der_to_raw(string $der): string {
    $pos = 2;                                   // skip SEQUENCE tag + length
    if (ord($der[1]) & 0x80) $pos += ord($der[1]) & 0x7f;
    $out = '';
    for ($i = 0; $i < 2; $i++) {
        $len = ord($der[$pos + 1]);
        $int = substr($der, $pos + 2, $len);
        $out .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
        $pos += 2 + $len;
    }
    return $out;
}

function vapid_header(string $endpoint, array $keys): string {
    $p = parse_url($endpoint);
    $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    $head = b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = b64url(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => 'https://vst-translation.com']));
    openssl_sign("$head.$claims", $der, $keys[0], OPENSSL_ALGO_SHA256);
    return 'vapid t=' . "$head.$claims." . b64url(der_to_raw($der)) . ', k=' . $keys[1];
}

/* Sends one empty push. Returns the HTTP status (0 = network error). Dead subscriptions are removed. */
function push_send(array $sub, array $keys): int {
    $ch = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER => ['TTL: 86400', 'Urgency: high', 'Content-Length: 0', 'Authorization: ' . vapid_header($sub['endpoint'], $keys)],
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code === 404 || $code === 410) {
        pt_db()->prepare("DELETE FROM pt_push_subscriptions WHERE id = ?")->execute([$sub['id']]);
    } elseif ($code >= 200 && $code < 300) {
        pt_db()->prepare("UPDATE pt_push_subscriptions SET last_ok_at = NOW() WHERE id = ?")->execute([$sub['id']]);
    } else {
        error_log("push to subscription {$sub['id']} failed: HTTP $code");
    }
    return $code;
}

/* Queue a notification for some people and wake their phones.
   The pushes go out after the page has been sent, so nobody waits for them. */
function notify(array $userIds, string $kind, string $title, string $body, string $url, ?string $refDate = null): void {
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if (!$userIds) return;
    try {
        $ins = pt_db()->prepare("INSERT INTO pt_notifications (user_id, kind, title, body, url, ref_date) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($userIds as $uid) {
            $ins->execute([$uid, $kind, mb_substr($title, 0, 160), mb_substr($body, 0, 255), $url, $refDate]);
        }
    } catch (PDOException $e) {
        return;   // notification tables not created yet (database not updated)
    }
    $in = implode(',', $userIds);
    $subs = pt_db()->query("SELECT * FROM pt_push_subscriptions WHERE user_id IN ($in)")->fetchAll();
    if (!$subs) return;

    $send = function () use ($subs) {
        $keys = vapid_keys();
        if (!$keys) return;
        foreach ($subs as $s) push_send($s, $keys);
    };
    if (PHP_SAPI === 'cli') { $send(); return; }
    register_shutdown_function(function () use ($send) {
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        $send();
    });
}
