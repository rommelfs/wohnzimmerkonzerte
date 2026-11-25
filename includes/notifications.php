<?php
if (!defined('ABSPATH')) exit;

const RL_MAIL_SCHEMA = '2';
function rl_mail_table(string $name): string { global $wpdb; return $wpdb->prefix . 'rl_mail_' . $name; }
function rl_mail_input(array $input, string $key): string { return isset($input[$key]) && is_string($input[$key]) ? wp_unslash($input[$key]) : ''; }
function rl_mail_consent(): string { return 'Ich möchte per E-Mail über neue Wohnzimmerkonzerte informiert werden. Ich kann mich jederzeit abmelden.'; }

// Separate tables per website, including lazy initialization after updates/network activation.
function rl_mail_install(): void {
    if (get_option('rl_mail_schema') === RL_MAIL_SCHEMA) return;
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $collate = $wpdb->get_charset_collate();
    $subscribers = rl_mail_table('subscribers'); $campaigns = rl_mail_table('campaigns'); $deliveries = rl_mail_table('deliveries');
    dbDelta("CREATE TABLE $subscribers (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(254) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        generation bigint(20) unsigned NOT NULL DEFAULT 1,
        token_hash varchar(64) NOT NULL DEFAULT '',
        requested_at bigint(20) unsigned NOT NULL DEFAULT 0,
        confirmed_at bigint(20) unsigned NOT NULL DEFAULT 0,
        unsubscribed_at bigint(20) unsigned NOT NULL DEFAULT 0,
        source varchar(40) NOT NULL DEFAULT '',
        consent text NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB $collate;");
    dbDelta("CREATE TABLE $campaigns (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        concert_id bigint(20) unsigned NOT NULL,
        request_key varchar(64) DEFAULT NULL,
        subject text NOT NULL,
        body longtext NOT NULL,
        created_at bigint(20) unsigned NOT NULL,
        created_by bigint(20) unsigned NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY request_key (request_key),
        KEY concert (concert_id)
    ) ENGINE=InnoDB $collate;");
    dbDelta("CREATE TABLE $deliveries (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campaign_id bigint(20) unsigned NOT NULL,
        subscriber_id bigint(20) unsigned NOT NULL,
        generation bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'queued',
        attempts int unsigned NOT NULL DEFAULT 0,
        touched_at bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY recipient (campaign_id,subscriber_id),
        KEY work (status,touched_at)
    ) ENGINE=InnoDB $collate;");
    foreach ([$subscribers, $campaigns, $deliveries] as $table) {
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) return;
    }
    // dbDelta adds columns/indexes but does not remove the old one-campaign-per-concert index.
    if (!$wpdb->get_row("SHOW INDEX FROM $campaigns WHERE Key_name='request_key' AND Non_unique=0")) return;
    $legacy_index = $wpdb->get_row("SHOW INDEX FROM $campaigns WHERE Key_name='concert_id'");
    if ($legacy_index && $wpdb->query("ALTER TABLE $campaigns DROP INDEX concert_id") === false) return;
    update_option('rl_mail_schema', RL_MAIL_SCHEMA, false);
}
add_action('init', 'rl_mail_install', 21);
function rl_mail_subscriber(string $email): ?object {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . rl_mail_table('subscribers') . ' WHERE email=%s', strtolower(trim($email))));
}
function rl_mail_token(int $id): string {
    return hash_hmac('sha256', get_current_blog_id() . ':' . $id, wp_salt('auth'));
}
function rl_mail_url(string $action, int $id, string $token): string {
    return add_query_arg(['action' => 'rl_mail_link', 'mode' => $action, 'id' => $id, 'token' => $token], admin_url('admin-post.php'));
}
function rl_mail_headers(): array { return ['Content-Type: text/plain; charset=UTF-8']; }

/** Always return the same public result; never disclose whether an address is subscribed. */
function rl_mail_subscribe(string $email, string $source): void {
    global $wpdb;
    $email = strtolower(trim($email));
    if (!is_email($email) || strlen($email) > 254) return;
    $table = rl_mail_table('subscribers');
    $row = rl_mail_subscriber($email);
    if ($row && ($row->status === 'confirmed' || (int)$row->requested_at > time() - HOUR_IN_SECONDS)) return;
    $token = bin2hex(random_bytes(32));
    $data = ['status' => 'pending', 'token_hash' => hash('sha256', $token), 'requested_at' => time(), 'confirmed_at' => 0,
        'source' => sanitize_key($source), 'consent' => rl_mail_consent()];
    if ($row) {
        $data['generation'] = (int)$row->generation + 1;
        // Compare-and-swap protects a concurrent confirmation or duplicate signup.
        $ok = $wpdb->update($table, $data, ['id' => $row->id, 'generation' => $row->generation, 'status' => $row->status]);
        $id = (int)$row->id;
    } else {
        $ok = $wpdb->insert($table, $data + ['email' => $email]);
        $id = (int)$wpdb->insert_id;
    }
    if (!$ok) return;
    $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $body = "Bitte bestätige deine Anmeldung für Konzertbenachrichtigungen von $site:\n\n" . rl_mail_url('confirm', $id, $token)
        . "\n\n" . rl_mail_consent() . "\n\nDer Link gilt 48 Stunden. Ohne Bestätigung erhältst du keine Konzertmails. Falls du die Anmeldung nicht angefordert hast, ignoriere diese Nachricht.";
    wp_mail($email, 'Konzertbenachrichtigungen: Anmeldung bestätigen', $body, rl_mail_headers());
}
function rl_mail_confirm(int $id, string $token): bool {
    global $wpdb;
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
    return (bool)$wpdb->query($wpdb->prepare('UPDATE ' . rl_mail_table('subscribers') . " SET status='confirmed', confirmed_at=%d, token_hash='' WHERE id=%d AND status='pending' AND requested_at>=%d AND token_hash=%s", time(), $id, time() - 2 * DAY_IN_SECONDS, hash('sha256', $token)));
}
function rl_mail_unsubscribe(int $id, string $token): bool {
    global $wpdb;
    if (!hash_equals(rl_mail_token($id), $token)) return false;
    $wpdb->update(rl_mail_table('subscribers'), ['status' => 'unsubscribed', 'token_hash' => '', 'unsubscribed_at' => time()], ['id' => $id]);
    return true;
}
function rl_mail_page(string $message, string $form = ''): void {
    nocache_headers();
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
    wp_die('<p>' . esc_html($message) . '</p>' . $form . '<p><a href="' . esc_url(home_url('/')) . '">Zur Website</a></p>', 'Konzertbenachrichtigungen', ['response' => 200]);
}
function rl_mail_link(): void {
    $mode = sanitize_key(rl_mail_input($_REQUEST, 'mode'));
    $id = absint(rl_mail_input($_REQUEST, 'id'));
    $token = sanitize_text_field(rl_mail_input($_REQUEST, 'token'));
    if (!in_array($mode, ['confirm', 'unsubscribe'], true) || !$id || !preg_match('/^[a-f0-9]{64}$/D', $token)) rl_mail_page('Dieser Link ist ungültig.');
    // Link scanners may fetch GET links. Only an explicit form submission changes consent.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = $mode === 'confirm' ? rl_mail_confirm($id, $token) : rl_mail_unsubscribe($id, $token);
        rl_mail_page($ok ? ($mode === 'confirm' ? 'Deine Anmeldung ist bestätigt.' : 'Du erhältst keine weiteren Konzertbenachrichtigungen.') : 'Der Link ist ungültig oder bereits verwendet. Bitte melde dich bei Bedarf erneut an.');
    }
    $form = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_link"><input type="hidden" name="mode" value="' . esc_attr($mode) . '"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="token" value="' . esc_attr($token) . '"><button type="submit">' . ($mode === 'confirm' ? 'Anmeldung bestätigen' : 'Jetzt abmelden') . '</button></form>';
    rl_mail_page($mode === 'confirm' ? rl_mail_consent() : 'Möchtest du dich von den Konzertbenachrichtigungen abmelden?', $form);
}
add_action('admin_post_rl_mail_link', 'rl_mail_link');
add_action('admin_post_nopriv_rl_mail_link', 'rl_mail_link');

function rl_mail_signup_form(): string {
    $user = wp_get_current_user();
    $email = $user->exists() ? $user->user_email : '';
    $id = wp_unique_id('rl-mail-');
    $privacy = rl_setting('privacy') ?: get_privacy_policy_url();
    return '<form class="rl-mail-signup" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_signup">'
        . wp_nonce_field('rl_mail_signup', 'rl_mail_nonce', true, false)
        . '<p><label for="' . esc_attr($id) . '">E-Mail-Adresse</label><br><input id="' . esc_attr($id) . '" type="email" name="email" autocomplete="email" maxlength="254" required value="' . esc_attr($email) . '"></p>'
        . '<p><label><input type="checkbox" name="consent" value="1" required> ' . esc_html(rl_mail_consent()) . '</label></p>'
        . ($privacy ? '<p><a href="' . esc_url($privacy) . '">Datenschutzhinweise</a></p>' : '')
        . '<button class="button" type="submit">Konzertbenachrichtigungen abonnieren</button></form>';
}
add_shortcode('rommelfangen_notifications', 'rl_mail_signup_form');
function rl_mail_signup(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !wp_verify_nonce(sanitize_text_field(rl_mail_input($_POST, 'rl_mail_nonce')), 'rl_mail_signup')) rl_mail_page('Bitte lade das Anmeldeformular neu und versuche es erneut.');
    if (($_POST['consent'] ?? '') === '1') {
        // Store only a salted hash for a short-lived per-IP abuse limit, never the IP itself.
        $key = 'rl_mail_rate_' . hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''), wp_salt('nonce'));
        $count = (int)get_transient($key);
        if ($count < 5) {
            set_transient($key, $count + 1, HOUR_IN_SECONDS);
            rl_mail_subscribe(sanitize_email(rl_mail_input($_POST, 'email')), 'form');
        }
    }
    rl_mail_page('Falls für diese Adresse eine Bestätigung erforderlich ist, erhältst du eine E-Mail. Bitte bestätige dort deine Anmeldung. Prüfe auch deinen Spamordner.');
}
add_action('admin_post_rl_mail_signup', 'rl_mail_signup');
add_action('admin_post_nopriv_rl_mail_signup', 'rl_mail_signup');

function rl_mail_campaign_content(int $id): array {
    $title = wp_specialchars_decode(get_the_title($id), ENT_QUOTES);
    $past = rl_timestamp((string)get_post_meta($id, 'rl_start', true)) <= time();
    $subject = ($past ? 'Rückblick: ' : 'Neues Wohnzimmerkonzert: ') . $title;
    $body = $title . "\n" . rl_date(rl_timestamp((string)get_post_meta($id, 'rl_start', true))) . "\n\n"
        . wp_specialchars_decode(wp_strip_all_tags(get_the_excerpt($id)), ENT_QUOTES) . ($past ? "\n\nKonzert & Rückblick:\n" : "\n\nKonzert & Tickets:\n") . get_permalink($id);
    return [str_replace(["\r", "\n"], ' ', $subject), $body];
}
function rl_mail_concert_eligible(int $id): bool {
    return get_post_type($id) === 'rl_concert' && get_post_status($id) === 'publish'
        && get_post_meta($id, 'rl_cancelled', true) !== '1'
        && rl_timestamp((string)get_post_meta($id, 'rl_start', true)) > 0;
}
function rl_mail_body(string $body): string {
    return trim(wp_check_invalid_utf8(str_replace(["\r\n", "\r"], "\n", wp_strip_all_tags($body))));
}
function rl_mail_message(array $input) {
    $subject = sanitize_text_field(rl_mail_input($input, 'subject'));
    $body = rl_mail_body(rl_mail_input($input, 'body'));
    if ($subject === '' || $body === '' || preg_match_all('/./u', $subject) > 200 || strlen($body) > 50000) return new WP_Error('message', 'Bitte Betreff (höchstens 200 Zeichen) und Nachricht (höchstens 50.000 Bytes) ausfüllen.');
    return [$subject, $body];
}
function rl_mail_preview_signature(int $id, array $content, string $request_key): string {
    return wp_hash(wp_json_encode([get_current_blog_id(), get_current_user_id(), $id, $content, $request_key]));
}
function rl_mail_queue_preview(array $input) {
    $id = absint(rl_mail_input($input, 'concert'));
    $content = rl_mail_message($input);
    if (is_wp_error($content)) return $content;
    $key = rl_mail_input($input, 'request_key');
    if (!preg_match('/^[a-f0-9]{64}$/D', $key) || !hash_equals(rl_mail_preview_signature($id, $content, $key), rl_mail_input($input, 'preview'))) return new WP_Error('preview', 'Bitte prüfe die Nachricht erneut in der Vorschau.');
    return rl_mail_queue($id, $content, $key);
}
function rl_mail_queue(int $id, ?array $content = null, string $request_key = '') {
    global $wpdb;
    if (!rl_mail_concert_eligible($id)) return new WP_Error('concert', 'Bitte ein veröffentlichtes, nicht abgesagtes Konzert mit gültigem Beginn auswählen. Vergangene Konzerte sind ebenfalls möglich.');
    $content = $content ?? rl_mail_campaign_content($id);
    $message = rl_mail_message(['subject' => wp_slash((string)($content[0] ?? '')), 'body' => wp_slash((string)($content[1] ?? ''))]);
    if (is_wp_error($message)) return $message;
    [$subject, $body] = $message;
    // The UI supplies a fresh key per reviewed message. Reposting that form keeps the same key.
    if ($request_key === '') $request_key = hash('sha256', wp_json_encode([$id, $message]));
    if (!preg_match('/^[a-f0-9]{64}$/D', $request_key)) return new WP_Error('request', 'Ungültiger Versandauftrag.');
    $campaigns = rl_mail_table('campaigns'); $deliveries = rl_mail_table('deliveries'); $subscribers = rl_mail_table('subscribers');
    if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $campaigns WHERE request_key=%s", $request_key))) return new WP_Error('duplicate', 'Dieser Versandauftrag wurde bereits freigegeben.');
    $wpdb->query('START TRANSACTION');
    // Unique request key prevents duplicate handoffs without preventing later messages about the same concert.
    if (!$wpdb->insert($campaigns, ['concert_id' => $id, 'request_key' => $request_key, 'subject' => $subject, 'body' => $body, 'created_at' => time(), 'created_by' => get_current_user_id()])) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('duplicate', 'Dieser Versandauftrag wurde bereits freigegeben oder konnte nicht gespeichert werden.');
    }
    $campaign = (int)$wpdb->insert_id;
    $count = $wpdb->query($wpdb->prepare("INSERT INTO $deliveries (campaign_id, subscriber_id, generation) SELECT %d, id, generation FROM $subscribers WHERE status='confirmed'", $campaign));
    if (!$count) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('empty', 'Keine bestätigten Empfänger vorhanden oder die Empfängerliste konnte nicht gespeichert werden.');
    }
    if ($wpdb->query('COMMIT') === false) return new WP_Error('database', 'Der Versandauftrag konnte nicht abgeschlossen werden.');
    rl_mail_schedule();
    return $campaign;
}
function rl_mail_schedule(): void {
    if (!wp_next_scheduled('rl_mail_batch')) wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'rl_mail_batch');
}
function rl_mail_batch(): void {
    global $wpdb;
    $d = rl_mail_table('deliveries'); $s = rl_mail_table('subscribers'); $c = rl_mail_table('campaigns');
    // A crashed mail handoff is ambiguous: mark for inspection, never automatically send it again.
    $wpdb->query($wpdb->prepare("UPDATE $d SET status='uncertain' WHERE status='sending' AND touched_at<%d", time() - 15 * MINUTE_IN_SECONDS));
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM $d WHERE status='queued' AND touched_at<=%d ORDER BY id LIMIT 20", time() - 5 * MINUTE_IN_SECONDS));
    foreach ($rows as $delivery) {
        $claimed = $wpdb->query($wpdb->prepare("UPDATE $d SET status='sending', attempts=attempts+1, touched_at=%d WHERE id=%d AND status='queued'", time(), $delivery->id));
        if (!$claimed) continue;
        $row = $wpdb->get_row($wpdb->prepare("SELECT d.*, s.email, s.status AS subscription_status, s.generation AS current_generation, c.concert_id, c.subject, c.body FROM $d d LEFT JOIN $s s ON s.id=d.subscriber_id JOIN $c c ON c.id=d.campaign_id WHERE d.id=%d", $delivery->id));
        if (!$row || $row->subscription_status !== 'confirmed' || (int)$row->generation !== (int)$row->current_generation || !rl_mail_concert_eligible((int)$row->concert_id)) {
            $wpdb->update($d, ['status' => 'skipped'], ['id' => $delivery->id]);
            continue;
        }
        $body = $row->body . "\n\nDu erhältst diese Nachricht, weil du Konzertbenachrichtigungen abonniert hast.\nAbmelden:\n"
            . rl_mail_url('unsubscribe', (int)$row->subscriber_id, rl_mail_token((int)$row->subscriber_id));
        foreach (['imprint' => 'Impressum', 'privacy' => 'Datenschutz'] as $key => $label) {
            if ($url = rl_setting($key)) $body .= "\n$label: $url";
        }
        $ok = wp_mail($row->email, $row->subject, $body, rl_mail_headers());
        $wpdb->update($d, ['status' => $ok ? 'sent' : ((int)$row->attempts < 3 ? 'queued' : 'failed'), 'touched_at' => time()], ['id' => $row->id]);
    }
    if ($wpdb->get_var("SELECT id FROM $d WHERE status IN ('queued','sending') LIMIT 1")) rl_mail_schedule();
    // Unconfirmed addresses are not retained indefinitely.
    $wpdb->query($wpdb->prepare("DELETE FROM $s WHERE status='pending' AND requested_at<%d", time() - 7 * DAY_IN_SECONDS));
}
add_action('rl_mail_batch', 'rl_mail_batch');
// Recover a missing scheduled event (e.g. after a plugin was temporarily deactivated).
add_action('init', function () {
    global $wpdb;
    if (get_option('rl_mail_schema') === RL_MAIL_SCHEMA && $wpdb->get_var('SELECT id FROM ' . rl_mail_table('deliveries') . " WHERE status IN ('queued','sending') LIMIT 1")) rl_mail_schedule();
    if (!wp_next_scheduled('rl_mail_cleanup')) wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'rl_mail_cleanup');
}, 22);
add_action('rl_mail_cleanup', function () {
    global $wpdb;
    $wpdb->query($wpdb->prepare('DELETE FROM ' . rl_mail_table('subscribers') . " WHERE status='pending' AND requested_at<%d", time() - 7 * DAY_IN_SECONDS));
});

// WordPress drops these alongside a deleted Multisite website when the plugin is loaded.
add_filter('wpmu_drop_tables', function ($tables, $site_id) {
    global $wpdb;
    foreach (['subscribers', 'campaigns', 'deliveries'] as $name) $tables[] = $wpdb->get_blog_prefix((int)$site_id) . 'rl_mail_' . $name;
    return array_unique($tables);
}, 10, 2);
