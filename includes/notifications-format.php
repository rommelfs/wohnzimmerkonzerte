<?php
if (!defined('ABSPATH')) exit;

function rl_mail_placeholders(): array {
    return ['{Anrede}'=>'Hallo Anna, / ohne Namen: Hallo,', '{Name}'=>'Vor- und Nachname / ohne Namen: Musikfan', '{Vorname}'=>'Vorname / ohne Namen: Musikfan', '{Nachname}'=>'Nachname / sonst leer', '{Email}'=>'E-Mail-Adresse', '{Konzert}'=>'Konzerttitel', '{Datum}'=>'Konzertdatum', '{Uhrzeit}'=>'Konzertbeginn', '{Konzertlink}'=>'Link zum Konzert', '{Website}'=>'Name der Website', '{Websitelink}'=>'Startseite', '{Abmeldelink}'=>'Persönlicher Abmeldelink (Newsletter)', '{Bestellnummer}'=>'Bestellnummer (Bestellermails)', '{Tickets}'=>'Gebuchte Plätze (Bestellermails)', '{Adresse}'=>'Private Adresse (nur bezahlte Besteller)', '{Einlass}'=>'Einlasszeit (Bestellermails)'];
}
function rl_mail_html(string $html): string {
    $attributes = ['style'=>true, 'title'=>true];
    $allowed = array_fill_keys(['p','div','span','h1','h2','h3','h4','strong','b','em','i','u','s','blockquote','ul','ol','li','br','hr'], $attributes);
    $allowed['a'] = $attributes + ['href'=>true, 'target'=>true, 'rel'=>true];
    $allowed['img'] = $attributes + ['src'=>true, 'alt'=>true, 'width'=>true, 'height'=>true];
    foreach (['table','tbody','thead','tr','td','th'] as $tag) $allowed[$tag] = $attributes + ['width'=>true,'height'=>true,'align'=>true,'valign'=>true,'cellpadding'=>true,'cellspacing'=>true,'border'=>true,'colspan'=>true,'rowspan'=>true,'role'=>true];
    // Normalize link-dialog prefixes without URL-encoding the placeholder braces.
    $html = preg_replace_callback('~\b(href|src)\s*=\s*(["\'])(?:https?://)?(?:\{|%7B)(Konzertlink|Websitelink|Abmeldelink)(?:\}|%7D)/?\2~i', static function ($match) {
        foreach (['Konzertlink','Websitelink','Abmeldelink'] as $token) if (strcasecmp($token, $match[3]) === 0) return strtolower($match[1]) . '="{' . $token . '}"';
        return $match[0];
    }, $html);
    return trim(wp_kses($html, $allowed, ['http','https','mailto']));
}
/** Preserve link destinations and image descriptions in the alternative, without another dependency. */
function rl_mail_html_to_text(string $html): string {
    $text = ''; $links = [];
    foreach (wp_html_split(rl_mail_html($html)) as $part) {
        if (!str_starts_with($part, '<')) { $text .= $part; continue; }
        if (preg_match('~^<a\b~i', $part)) {
            $tag = new WP_HTML_Tag_Processor($part); $tag->next_tag();
            $links[] = (string)$tag->get_attribute('href');
        } elseif (preg_match('~^</a\s*>~i', $part)) {
            $url = array_pop($links);
            if ($url) $text .= ' (' . $url . ')';
        } elseif (preg_match('~^<img\b~i', $part)) {
            $tag = new WP_HTML_Tag_Processor($part); $tag->next_tag();
            $alt = (string)$tag->get_attribute('alt'); $src = (string)$tag->get_attribute('src');
            $text .= "\n" . ($alt !== '' ? $alt : 'Bild') . ($src !== '' ? ' (' . $src . ')' : '') . "\n";
        } elseif (preg_match('~^</?(p|div|h[1-4]|blockquote|ul|ol|li|tr|br|hr)\b~i', $part)) $text .= "\n";
        elseif (preg_match('~^</(td|th)\b~i', $part)) $text .= ' ';
    }
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\n{3,}/', "\n\n", $text));
}
function rl_mail_profile(string $email, array $profile = []): array {
    $names = [];
    foreach (['first_name','last_name'] as $field) $names[$field] = sanitize_text_field(is_string($profile[$field] ?? null) ? $profile[$field] : '');
    if ($names['first_name'] === '' && $names['last_name'] === '') {
        $user = get_user_by('email', $email);
        if ($user && (!is_multisite() || is_user_member_of_blog($user->ID))) {
            $names['first_name'] = sanitize_text_field(get_user_meta($user->ID, 'first_name', true) ?: get_user_meta($user->ID, 'billing_first_name', true));
            $names['last_name'] = sanitize_text_field(get_user_meta($user->ID, 'last_name', true) ?: get_user_meta($user->ID, 'billing_last_name', true));
        }
    }
    foreach ($names as $key=>$name) { preg_match('/^.{0,100}/us', $name, $match); $names[$key] = $match[0] ?? ''; }
    return $names;
}
function rl_mail_values(object $subscriber, int $concert): array {
    $profile = rl_mail_profile($subscriber->email, ['first_name'=>$subscriber->first_name ?? '', 'last_name'=>$subscriber->last_name ?? '']);
    // Older guest buyers may have no stored subscription profile. Reuse only paid orders on this website.
    if ($profile['first_name'] === '' && $profile['last_name'] === '' && function_exists('wc_get_orders')) {
        $orders = wc_get_orders(['billing_email'=>$subscriber->email,'status'=>['wc-processing','wc-completed'],'limit'=>1,'orderby'=>'date','order'=>'DESC']);
        if ($orders) $profile = rl_mail_profile($subscriber->email, ['first_name'=>$orders[0]->get_billing_first_name(),'last_name'=>$orders[0]->get_billing_last_name()]);
    }
    $first = $profile['first_name']; $last = $profile['last_name']; $name = trim($first . ' ' . $last);
    $timestamp = rl_timestamp((string)get_post_meta($concert, 'rl_start', true));
    return ['{Anrede}'=>$first !== '' ? 'Hallo ' . $first . ',' : 'Hallo,', '{Name}'=>$name ?: 'Musikfan', '{Vorname}'=>$first ?: 'Musikfan', '{Nachname}'=>$last,
        '{Email}'=>$subscriber->email, '{Konzert}'=>wp_specialchars_decode(get_the_title($concert), ENT_QUOTES), '{Datum}'=>$timestamp ? wp_date('d.m.Y', $timestamp) : '', '{Uhrzeit}'=>$timestamp ? wp_date('H:i', $timestamp) : '',
        '{Konzertlink}'=>get_permalink($concert) ?: '', '{Website}'=>wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), '{Websitelink}'=>home_url('/'),
        '{Abmeldelink}'=>rl_mail_url('unsubscribe', (int)$subscriber->id, rl_mail_token((int)$subscriber->id))];
}
function rl_mail_render(object $campaign, array $values): array {
    $subject = sanitize_text_field(strtr($campaign->subject, $values));
    $html_mode = ($campaign->body_format ?? 'text') === 'html';
    $html = $html_mode ? rl_mail_html(strtr($campaign->body, array_map('esc_attr', $values))) : '';
    if ($html_mode) {
        $images = new WP_HTML_Tag_Processor($html);
        while ($images->next_tag('IMG')) $images->set_attribute('style', (string)$images->get_attribute('style') . ';max-width:100%;height:auto');
        $html = $images->get_updated_html();
    }
    $text = $html_mode ? (($campaign->text_body ?? '') !== '' ? strtr($campaign->text_body, $values) : rl_mail_html_to_text($html)) : strtr($campaign->body, $values);
    $transactional=!empty($campaign->transactional);
    $notice = $transactional ? 'Information zu deiner Konzertbuchung. Bei Fragen antworte bitte auf diese E-Mail.' : 'Du erhältst diese Nachricht, weil du Konzertbenachrichtigungen abonniert hast.';
    $text .= "\n\n" . $notice . ($transactional ? '' : "\nAbmelden:\n" . $values['{Abmeldelink}']);
    if ($html_mode && !$transactional) $html .= '<hr><p>' . esc_html($notice) . '<br><a href="' . esc_url($values['{Abmeldelink}']) . '">Konzertbenachrichtigungen abmelden</a></p>';
    if ($html_mode && $transactional) $html .= '<hr><p>'.esc_html($notice).'</p>';
    foreach (['imprint'=>'Impressum','privacy'=>'Datenschutz'] as $key=>$label) {
        if ($url = rl_setting($key)) {
            $text .= "\n$label: $url";
            if ($html_mode) $html .= '<p><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></p>';
        }
    }
    if ($html_mode) $html = '<!doctype html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family:Arial,sans-serif;line-height:1.6;color:#202725;background:#ffffff"><div style="max-width:680px;margin:0 auto;padding:24px">' . $html . '</div></body></html>';
    return ['subject'=>$subject,'html'=>$html,'text'=>$text];
}
function rl_mail_sender_settings($input): array {
    $input = is_array($input) ? $input : [];
    $name = sanitize_text_field(is_string($input['name'] ?? null) ? $input['name'] : '');
    $email = trim(is_string($input['email'] ?? null) ? $input['email'] : '');
    if ($email !== '' && !is_email($email)) {
        add_settings_error('rl_mail_sender', 'email', 'Bitte eine gültige Absenderadresse eingeben. Die bisherige Adresse bleibt erhalten.');
        $saved = get_option('rl_mail_sender', []);
        $email = is_array($saved) && is_email($saved['email'] ?? '') ? $saved['email'] : '';
    }
    return ['name'=>$name, 'email'=>$email];
}
/** PHPMailer builds multipart/alternative from HTML Body and AltBody, over the existing transport. */
function rl_mail_send(string $email, string $subject, string $html, string $text): bool {
    $sender = get_option('rl_mail_sender', []);
    $sender = is_array($sender) ? $sender : [];
    $from = static fn($existing) => !empty($sender['email']) && is_email($sender['email']) ? $sender['email'] : $existing;
    $from_name = static fn($existing) => !empty($sender['name']) && is_string($sender['name']) ? sanitize_text_field($sender['name']) : $existing;
    $mailer = null; $previous = '';
    $alternative = static function ($phpmailer) use ($html, $text, $subject, &$mailer, &$previous) {
        if ($html === '' || $phpmailer->Body !== $html || $phpmailer->Subject !== $subject) return;
        $mailer = $phpmailer; $previous = $phpmailer->AltBody;
        $phpmailer->isHTML(true);
        $phpmailer->AltBody = $text;
    };
    add_filter('wp_mail_from', $from, PHP_INT_MAX);
    add_filter('wp_mail_from_name', $from_name, PHP_INT_MAX);
    add_action('phpmailer_init', $alternative, PHP_INT_MAX);
    try { return wp_mail($email, $subject, $html !== '' ? $html : $text, ['Content-Type: ' . ($html !== '' ? 'text/html' : 'text/plain') . '; charset=UTF-8']); }
    finally {
        remove_filter('wp_mail_from', $from, PHP_INT_MAX);
        remove_filter('wp_mail_from_name', $from_name, PHP_INT_MAX);
        remove_action('phpmailer_init', $alternative, PHP_INT_MAX);
        if ($mailer) $mailer->AltBody = $previous;
    }
}
