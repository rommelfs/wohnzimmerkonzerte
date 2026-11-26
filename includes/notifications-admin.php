<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=rl_concert', 'Konzertbenachrichtigungen', 'Benachrichtigungen', 'manage_options', 'rl-notifications', 'rl_mail_admin');
});
function rl_mail_status_label(string $status): string {
    return ['pending' => 'Bestätigung offen', 'confirmed' => 'Bestätigt', 'unsubscribed' => 'Abgemeldet', 'queued' => 'Wartend', 'sending' => 'Übergabe läuft', 'sent' => 'An Mailserver übergeben', 'failed' => 'Fehlgeschlagen', 'skipped' => 'Übersprungen', 'uncertain' => 'Übergabe unklar (Serverprotokoll prüfen)'][$status] ?? $status;
}
function rl_mail_admin(): void {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $base = admin_url('edit.php?post_type=rl_concert&page=rl-notifications');
    echo '<div class="wrap"><h1>Konzertbenachrichtigungen</h1><p>Newsletter gehen an bestätigte Anmeldungen; Buchungsinformationen gezielt an Besteller eines Konzerts. Der Versand nutzt den vorhandenen WordPress-Mailweg. Bestehende Kunden werden nicht automatisch angemeldet.</p>';
    if (isset($_GET['queued'])) echo '<div class="notice notice-success"><p>Der Versandauftrag wurde angelegt. Die Verarbeitung erfolgt über WordPress-Cron.</p></div>';
    if ($error = get_transient('rl_mail_admin_' . get_current_user_id())) {
        echo '<div class="notice '.((str_starts_with($error,'Testmail an den Versandweg') || str_starts_with($error,'Erinnerung an den Mailserver')) ? 'notice-success' : 'notice-error').'"><p>' . esc_html($error) . '</p></div>';
        delete_transient('rl_mail_admin_' . get_current_user_id());
    }
    rl_mail_sender_form();
    rl_mail_registration_form();
    if (function_exists('rl_ops_options') && rl_ops_options()['mail_paused']) echo '<div class="notice notice-warning"><p>Der Versand wartender Newsletter- und Bestelleraufträge ist pausiert. Anmeldebestätigungen, Anmeldeerinnerungen und Zahlungserinnerungen bleiben aktiv. Fortsetzen unter Konzertbetrieb.</p></div>';
    $confirmed = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . rl_mail_table('subscribers') . " WHERE status='confirmed'");
    echo '<h2>Nachricht zu einem Konzert</h2><p>' . esc_html($confirmed) . ' Newsletter-Abonnenten. Wähle einen kommenden oder vergangenen Abend und passe Betreff und Nachricht an, z. B. für eine Ankündigung, Fundsachen oder Fotolinks. Jede neue Freigabe erzeugt einen eigenen Auftrag.</p><form method="get"><input type="hidden" name="post_type" value="rl_concert"><input type="hidden" name="page" value="rl-notifications"><label for="rl-mail-concert">Konzert</label> <select name="concert" id="rl-mail-concert">';
    $selected = absint(rl_mail_input($_GET, 'concert'));
    $groups = ['Kommende Konzerte' => [], 'Vergangene Konzerte' => []];
    foreach (rl_events() as $event) {
        if (rl_mail_message_eligible($event['id'],'buyers_all')) $groups[$event['start'] > time() ? 'Kommende Konzerte' : 'Vergangene Konzerte'][] = $event;
    }
    foreach ($groups as $label => $events) {
        usort($events, static fn($a, $b) => $label === 'Kommende Konzerte' ? $a['start'] <=> $b['start'] : $b['start'] <=> $a['start']);
        if (!$events) continue;
        echo '<optgroup label="' . esc_attr($label) . '">';
        foreach ($events as $event) echo '<option value="' . (int)$event['id'] . '" ' . selected($selected, $event['id'], false) . '>' . esc_html(get_the_title($event['id']) . ' · ' . wp_date('d.m.Y', $event['start'])) . '</option>';
        echo '</optgroup>';
    }
    echo '</select> <button class="button">Nachricht vorbereiten</button></form>';
    if ($selected && rl_mail_message_eligible($selected,'buyers_all')) rl_mail_composer($selected, $base);
    echo '<h2>Versandaufträge</h2><p>„An Mailserver übergeben“ bestätigt die Annahme durch den Mailweg, nicht die Zustellung im Postfach. Fehlgeschlagene Übergaben werden höchstens dreimal versucht; unklare Übergaben nach einem Prozessabbruch werden nicht automatisch wiederholt.</p><table class="widefat striped"><thead><tr><th>Konzert / Auftrag</th><th>Status</th></tr></thead><tbody>';
    $campaigns = $wpdb->get_results('SELECT * FROM ' . rl_mail_table('campaigns') . ' ORDER BY id DESC LIMIT 50');
    foreach ($campaigns as $campaign) {
        $statuses = $wpdb->get_results($wpdb->prepare('SELECT status, COUNT(*) AS total FROM ' . rl_mail_table('deliveries') . ' WHERE campaign_id=%d GROUP BY status', $campaign->id));
        $labels = array_map(static fn($row) => rl_mail_status_label($row->status) . ': ' . $row->total, $statuses);
        echo '<tr><td>' . esc_html($campaign->subject) . '<br>Konzert: ' . esc_html(get_the_title((int)$campaign->concert_id)) . '<br>' . esc_html(wp_date('d.m.Y H:i', (int)$campaign->created_at)) . '</td><td>' . esc_html(implode(' · ', $labels)) . '</td></tr>';
    }
    if (!$campaigns) echo '<tr><td colspan="2">Noch keine Versandaufträge.</td></tr>';
    $pending=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.rl_mail_table('subscribers')." WHERE status='pending'");
    echo '</tbody></table><h2>Empfänger</h2><p><strong>'.esc_html($pending).' Bestätigungen offen.</strong> Diese Adressen erhalten noch keine Newsletter. Eine Stunde nach der Anmeldung wird automatisch einmalig ein neuer Bestätigungslink verschickt, sofern die Anmeldung noch offen ist. Der Versand läuft über WordPress-Cron und kann bei wenig Website-Traffic später erfolgen. Bereits erinnerte, bestätigte, abgemeldete oder mehr als fünf Tage alte Anmeldungen werden nicht erneut angeschrieben.</p><p>Einwilligung und Versanddaten lassen sich auch über die WordPress-Werkzeuge für personenbezogene Daten exportieren und löschen.</p><table class="widefat striped"><thead><tr><th>E-Mail</th><th>Status</th><th>Einwilligung</th><th>Aktion</th></tr></thead><tbody>';
    $page = max(1, absint(rl_mail_input($_GET, 'paged')));
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . rl_mail_table('subscribers') . ' ORDER BY id DESC LIMIT 50 OFFSET %d', ($page - 1) * 50));
    foreach ($rows as $row) {
        echo '<tr><td>' . esc_html($row->email) . '<br>' . esc_html(trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''))) . '</td><td>' . esc_html(rl_mail_status_label($row->status)) . '</td><td>' . esc_html($row->source) . '<br>Angefordert: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->requested_at));
        if ($row->confirmed_at) echo '<br>Bestätigt: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->confirmed_at));
        if ($row->unsubscribed_at) echo '<br>Abgemeldet: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->unsubscribed_at));
        if ($row->status==='pending') { $expires=max((int)($row->token_created_at?:$row->requested_at),!empty($row->reminder_token_hash)?(int)$row->reminded_at:0)+2*DAY_IN_SECONDS;echo '<br>'.esc_html($expires>time()?'Link gültig bis: '.wp_date('d.m.Y H:i',$expires):'Bestätigungslink abgelaufen'); }
        if (!empty($row->reminded_at)) echo '<br>Erneuter Versand angefordert: '.esc_html(wp_date('d.m.Y H:i',(int)$row->reminded_at));
        if (!empty($row->confirmation_status)) echo '<br>Bestätigungsmail: '.esc_html(rl_mail_status_label($row->confirmation_status));
        if (!empty($row->admin_notice_status)) echo '<br>Admin-Benachrichtigung: '.esc_html(rl_mail_status_label($row->admin_notice_status));
        echo '<details><summary>Einwilligungstext</summary>' . esc_html($row->consent) . '</details></td><td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_delete"><input type="hidden" name="subscriber" value="' . (int)$row->id . '">';
        wp_nonce_field('rl_mail_delete_' . $row->id);
        echo '<button class="button" type="submit">Anmeldung und Versanddaten löschen</button></form>';
        rl_mail_reminder_action($row);echo '</td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="4">Keine Anmeldungen vorhanden.</td></tr>';
    echo '</tbody></table>';
    $total = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . rl_mail_table('subscribers'));
    echo wp_kses_post(paginate_links(['base' => add_query_arg('paged', '%#%', $base), 'current' => $page, 'total' => (int)ceil($total / 50)]));
    echo '<p>Anmeldeformular für weitere Seiten: <code>[rommelfangen_notifications]</code>. Auf der Konzertstartseite wird es automatisch angezeigt.</p></div>';
}
add_action('admin_init', function () {
    register_setting('rl_mail_sender', 'rl_mail_sender', ['type'=>'array', 'sanitize_callback'=>'rl_mail_sender_settings', 'default'=>[]]);
});
function rl_mail_sender_form(): void {
    $sender = get_option('rl_mail_sender', []); $sender = is_array($sender) ? $sender : [];
    settings_errors('rl_mail_sender');
    echo '<details><summary><strong>Absender der Konzertmails</strong></summary><p>Gilt für Anmeldebestätigungen und Konzertnachrichten dieser Website. Leere Felder übernehmen die WordPress-Vorgabe.</p><form method="post" action="options.php">';
    settings_fields('rl_mail_sender');
    echo '<p><label for="rl-mail-from-name">Absendername</label><br><input class="regular-text" id="rl-mail-from-name" name="rl_mail_sender[name]" value="' . esc_attr($sender['name'] ?? '') . '" placeholder="Rommelfangen Konzerte"></p><p><label for="rl-mail-from-email">Absenderadresse</label><br><input class="regular-text" type="email" id="rl-mail-from-email" name="rl_mail_sender[email]" value="' . esc_attr($sender['email'] ?? '') . '" placeholder="konzerte@rommelfangen.de"></p>';
    submit_button('Absender speichern', 'secondary', 'submit', false);
    echo '</form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_test_mail">';wp_nonce_field('rl_test_mail');echo '<p>Testempfänger: '.esc_html(wp_get_current_user()->user_email).'</p><button class="button">Testmail an mein Administratorkonto senden</button></form></details>';
}
add_action('admin_enqueue_scripts', function () {
    if (rl_mail_input($_GET, 'page') === 'rl-notifications' && current_user_can('manage_options')) wp_enqueue_media();
});
/** Editing and explicit review are separate steps; only the reviewed text can be queued. */
function rl_mail_composer(int $id, string $base): void {
    [$subject, $plain] = rl_mail_campaign_content($id);
    $subject = (rl_timestamp((string)get_post_meta($id, 'rl_start', true)) <= time() ? 'Rückblick: ' : 'Neues Wohnzimmerkonzert: ') . '{Konzert}';
    $body = '<p>{Anrede}</p><h2>{Konzert}</h2><p>{Datum} · {Uhrzeit}</p>' . wpautop(esc_html(wp_specialchars_decode(wp_strip_all_tags(get_the_excerpt($id)), ENT_QUOTES))) . '<p><a href="{Konzertlink}">Konzert ansehen</a></p>';
    $audience=rl_mail_input($_GET,'audience') ?: (get_post_meta($id,'rl_cancelled',true)==='1' ? 'buyers_all' : 'subscribers');
    if ($audience==='subscribers' && rl_timestamp((string)get_post_meta($id,'rl_start',true))<time()) $audience='subscribers_updates';
    if (!in_array($audience,['subscribers','subscribers_updates','buyers_paid','buyers_open','buyers_all'],true)) $audience='subscribers';
    $format = 'html'; $text = ''; $preview = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && rl_mail_input($_POST, 'mail_step') === 'preview') {
        check_admin_referer('rl_mail_preview');
        $audience=rl_mail_input($_POST,'audience') ?: 'subscribers';
        if (!in_array($audience,['subscribers','subscribers_updates','buyers_paid','buyers_open','buyers_all'],true)) $audience='subscribers';
        $preview = rl_mail_message($_POST);
        $subject = sanitize_text_field(rl_mail_input($_POST, 'subject'));
        $format = rl_mail_input($_POST, 'body_format') ?: 'text';
        if (!in_array($format, ['html','text'], true)) $format = 'html';
        $body = $format === 'html' ? rl_mail_html(wpautop(rl_mail_input($_POST, 'body'))) : rl_mail_body(rl_mail_input($_POST, 'body'));
        $text = rl_mail_body(rl_mail_input($_POST, 'text_body'));
    }
    echo '<form method="post" action="' . esc_url(add_query_arg('concert', $id, $base)) . '"><input type="hidden" name="mail_step" value="preview">';
    wp_nonce_field('rl_mail_preview');
    echo '<p><label for="rl-mail-audience">Empfänger</label> <select id="rl-mail-audience" name="audience">';
    foreach (['subscribers'=>'Neue Konzertankündigungen (alle bestätigten Anmeldungen)','subscribers_updates'=>'Neuigkeiten / Rückblicke (erweiterte Einwilligung)','buyers_paid'=>'Bezahlte Besteller dieses Konzerts','buyers_open'=>'Offene Bestellungen dieses Konzerts','buyers_all'=>'Alle aktiven Besteller dieses Konzerts'] as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($audience,$key,false).'>'.esc_html($label).'</option>';
    echo '</select></p><p>Bestellermails sind Informationen zur Buchung und unabhängig vom Newsletter. Ältere Newsletter-Einwilligungen nur für Ankündigungen werden nicht automatisch erweitert. Über das Anmeldeformular kann die erweiterte Einwilligung erneut bestätigt werden. Die private Adresse ist nur für bezahlte Besteller verfügbar. Absagen können weiterhin an betroffene Besteller gesendet werden.</p>';
    echo '<p><label for="rl-mail-format">Nachrichtenformat</label> <select id="rl-mail-format" name="body_format"><option value="html" ' . selected($format, 'html', false) . '>HTML mit Textalternative</option><option value="text" ' . selected($format, 'text', false) . '>Nur Text</option></select></p><p><label for="rl-mail-subject">Betreff</label><br><input class="large-text" id="rl-mail-subject" name="subject" maxlength="200" required value="' . esc_attr($subject) . '"></p><p><label for="rl_mail_body_editor">Nachricht</label></p>';
    wp_editor($body, 'rl_mail_body_editor', ['textarea_name'=>'body','textarea_rows'=>14,'media_buttons'=>true,'tinymce'=>$format === 'html','quicktags'=>true]);
    echo '<p>Bilder über „Dateien hinzufügen“ aus der Mediathek wählen; Links über den Editor einfügen. Bei „Nur Text“ werden Formatierungen entfernt.</p><p><label for="rl-mail-text">Textalternative (optional; leer lassen für automatische Erstellung aus HTML)</label><br><textarea class="large-text" rows="6" id="rl-mail-text" name="text_body">' . esc_textarea($text) . '</textarea></p><details><summary>Verfügbare Platzhalter</summary><dl>';
    foreach (rl_mail_placeholders() as $token=>$description) echo '<dt><code>' . esc_html($token) . '</code></dt><dd>' . esc_html($description) . '</dd>';
    echo '</dl><p>Platzhalter funktionieren in Betreff, Nachricht und Textalternative. Link-Platzhalter können auch als Linkziel verwendet werden. Die Vorschau verwendet eine Beispielperson.</p></details>';
    submit_button('Vorschau prüfen', 'secondary', 'submit', false);
    echo '</form>';
    if ($preview === null) return;
    if (is_wp_error($preview)) {
        echo '<div class="notice notice-error"><p>' . esc_html($preview->get_error_message()) . '</p></div>';
        return;
    }
    $sample = (object)['id'=>0,'email'=>'anna@example.test','first_name'=>'Anna','last_name'=>'Muster'];
    $values = rl_mail_values($sample, $id);
    $values['{Abmeldelink}'] = home_url('/?beispiel-abmeldung=1');
    $values+=['{Bestellnummer}'=>'1234','{Tickets}'=>'2','{Adresse}'=>$audience==='buyers_paid' ? 'Beispieladresse (wird aus der bezahlten Buchung eingesetzt)' : '', '{Einlass}'=>'19:00'];
    $mail = rl_mail_render((object)['subject'=>$subject,'body'=>$body,'body_format'=>$format,'text_body'=>$text,'transactional'=>!in_array($audience,['subscribers','subscribers_updates'],true)], $values);
    $key = bin2hex(random_bytes(32));
    $recipient_count=in_array($audience,['subscribers','subscribers_updates'],true) ? (int)$GLOBALS['wpdb']->get_var('SELECT COUNT(*) FROM '.rl_mail_table('subscribers')." WHERE status='confirmed'".($audience==='subscribers_updates' ? $GLOBALS['wpdb']->prepare(' AND consent=%s',rl_mail_consent()) : '')) : count(rl_booking_recipients($id,$audience));
    echo '<p><strong>Ausgewählter Empfängerkreis: '.esc_html(in_array($audience,['subscribers','subscribers_updates'],true) ? 'Newsletter' : 'Besteller dieses Konzerts').' · aktuell '.$recipient_count.' Empfänger</strong></p>';
    echo '<h3>Versandvorschau: ' . esc_html($mail['subject']) . '</h3><p>Beispiel: Anna Muster (anna@example.test). Personendaten und Abmeldelink werden für jeden Empfänger ersetzt.</p>';
    if ($mail['html'] !== '') echo '<iframe title="HTML-Mailvorschau" sandbox="" referrerpolicy="no-referrer" style="width:100%;height:440px;border:1px solid #c3c4c7" srcdoc="' . esc_attr($mail['html']) . '"></iframe>';
    echo '<h4>Textalternative</h4><pre style="white-space:pre-wrap;max-width:65em">' . esc_html($mail['text']) . '</pre><p>Die Nachricht geht ausschließlich an den ausgewählten Empfängerkreis. Newsletter enthalten einen persönlichen Abmeldelink; Bestellermails sind davon getrennt. Eure hinterlegten Impressums-/Datenschutzlinks werden angehängt.</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_queue"><input type="hidden" name="concert" value="' . $id . '"><input type="hidden" name="audience" value="' . esc_attr($audience) . '"><input type="hidden" name="request_key" value="' . esc_attr($key) . '"><input type="hidden" name="preview" value="' . esc_attr(rl_mail_preview_signature($id, $preview, $key, $audience)) . '"><input type="hidden" name="subject" value="' . esc_attr($subject) . '"><input type="hidden" name="body_format" value="' . esc_attr($format) . '"><textarea hidden name="body">' . esc_textarea($body) . '</textarea><textarea hidden name="text_body">' . esc_textarea($text) . '</textarea>';
    wp_nonce_field('rl_mail_queue');
    submit_button('Jetzt für ausgewählte Empfänger freigeben', 'primary', 'submit', false);
    echo '</form>';
}
add_action('admin_post_rl_mail_queue', function () {
    if (!current_user_can('manage_options') || $_SERVER['REQUEST_METHOD'] !== 'POST') wp_die('Nicht erlaubt.', '', ['response' => 403]);
    check_admin_referer('rl_mail_queue');
    $result = rl_mail_queue_preview($_POST);
    if (is_wp_error($result)) set_transient('rl_mail_admin_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-notifications' . (is_wp_error($result) ? '' : '&queued=1')));
    exit;
});
add_action('admin_post_rl_mail_delete', function () {
    if (!current_user_can('manage_options') || $_SERVER['REQUEST_METHOD'] !== 'POST') wp_die('Nicht erlaubt.', '', ['response' => 403]);
    $id = absint(rl_mail_input($_POST, 'subscriber'));
    check_admin_referer('rl_mail_delete_' . $id);
    rl_mail_delete($id);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-notifications'));
    exit;
});

add_action('admin_post_rl_test_mail',static function () {
    if (!current_user_can('manage_options') || $_SERVER['REQUEST_METHOD']!=='POST') wp_die('Nicht erlaubt.','',['response'=>403]);check_admin_referer('rl_test_mail');
    $email=wp_get_current_user()->user_email;
    $ok=rl_mail_send($email,'Testmail: '.sanitize_text_field(get_bloginfo('name')),'<h1>Deine Konzert-Testmail</h1><p>HTML-Darstellung, Textalternative und Absender können jetzt im Postfach geprüft werden.</p>','Deine Konzert-Testmail. HTML-Darstellung, Textalternative und Absender können jetzt im Postfach geprüft werden.');
    set_transient('rl_mail_admin_'.get_current_user_id(),$ok ? 'Testmail an den Versandweg übergeben. Bitte den tatsächlichen Eingang im Postfach prüfen.' : 'Testmail konnte nicht übergeben werden. Mailkonfiguration prüfen.',MINUTE_IN_SECONDS);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-notifications'));exit;
});
