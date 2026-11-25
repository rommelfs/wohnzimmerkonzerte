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
    echo '<div class="wrap"><h1>Konzertbenachrichtigungen</h1><p>Nur bestätigte Anmeldungen dieser Website erhalten Konzertmails. Der Versand nutzt den vorhandenen WordPress-Mailweg. Bestehende Kunden werden nicht automatisch angemeldet.</p>';
    if (isset($_GET['queued'])) echo '<div class="notice notice-success"><p>Der Versandauftrag wurde angelegt. Die Verarbeitung erfolgt über WordPress-Cron.</p></div>';
    if ($error = get_transient('rl_mail_admin_' . get_current_user_id())) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        delete_transient('rl_mail_admin_' . get_current_user_id());
    }
    $confirmed = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . rl_mail_table('subscribers') . " WHERE status='confirmed'");
    echo '<h2>Konzert ankündigen</h2><p>' . esc_html($confirmed) . ' bestätigte Empfänger. Pro Konzert ist ein Versandauftrag möglich. Abmeldungen vor der jeweiligen Übergabe werden berücksichtigt.</p><form method="get"><input type="hidden" name="post_type" value="rl_concert"><input type="hidden" name="page" value="rl-notifications"><label for="rl-mail-concert">Konzert</label> <select name="concert" id="rl-mail-concert">';
    $selected = absint(rl_mail_input($_GET, 'concert'));
    foreach (rl_events() as $event) {
        if (rl_mail_concert_eligible($event['id'])) echo '<option value="' . (int)$event['id'] . '" ' . selected($selected, $event['id'], false) . '>' . esc_html(get_the_title($event['id'])) . '</option>';
    }
    echo '</select> <button class="button">Vorschau anzeigen</button></form>';
    if ($selected && rl_mail_concert_eligible($selected)) {
        [$subject, $body] = rl_mail_campaign_content($selected);
        echo '<h3>' . esc_html($subject) . '</h3><pre style="white-space:pre-wrap;max-width:65em">' . esc_html($body) . '</pre><p>Jede E-Mail enthält zusätzlich einen persönlichen Abmeldelink und eure hinterlegten Impressums-/Datenschutzlinks.</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_queue"><input type="hidden" name="concert" value="' . $selected . '"><input type="hidden" name="preview" value="' . esc_attr(hash('sha256', $subject . "\n" . $body)) . '">';
        wp_nonce_field('rl_mail_queue');
        submit_button('Jetzt für bestätigte Empfänger freigeben', 'primary', 'submit', false);
        echo '</form>';
    }
    echo '<h2>Versandaufträge</h2><p>„An Mailserver übergeben“ bestätigt die Annahme durch den Mailweg, nicht die Zustellung im Postfach. Fehlgeschlagene Übergaben werden höchstens dreimal versucht; unklare Übergaben nach einem Prozessabbruch werden nicht automatisch wiederholt.</p><table class="widefat striped"><thead><tr><th>Konzert / Auftrag</th><th>Status</th></tr></thead><tbody>';
    $campaigns = $wpdb->get_results('SELECT * FROM ' . rl_mail_table('campaigns') . ' ORDER BY id DESC LIMIT 50');
    foreach ($campaigns as $campaign) {
        $statuses = $wpdb->get_results($wpdb->prepare('SELECT status, COUNT(*) AS total FROM ' . rl_mail_table('deliveries') . ' WHERE campaign_id=%d GROUP BY status', $campaign->id));
        $labels = array_map(static fn($row) => rl_mail_status_label($row->status) . ': ' . $row->total, $statuses);
        echo '<tr><td>' . esc_html($campaign->subject) . '<br>' . esc_html(wp_date('d.m.Y H:i', (int)$campaign->created_at)) . '</td><td>' . esc_html(implode(' · ', $labels)) . '</td></tr>';
    }
    if (!$campaigns) echo '<tr><td colspan="2">Noch keine Versandaufträge.</td></tr>';
    echo '</tbody></table><h2>Empfänger</h2><p>Einwilligung und Versanddaten lassen sich auch über die WordPress-Werkzeuge für personenbezogene Daten exportieren und löschen.</p><table class="widefat striped"><thead><tr><th>E-Mail</th><th>Status</th><th>Einwilligung</th><th>Aktion</th></tr></thead><tbody>';
    $page = max(1, absint(rl_mail_input($_GET, 'paged')));
    $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . rl_mail_table('subscribers') . ' ORDER BY id DESC LIMIT 50 OFFSET %d', ($page - 1) * 50));
    foreach ($rows as $row) {
        echo '<tr><td>' . esc_html($row->email) . '</td><td>' . esc_html(rl_mail_status_label($row->status)) . '</td><td>' . esc_html($row->source) . '<br>Angefordert: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->requested_at));
        if ($row->confirmed_at) echo '<br>Bestätigt: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->confirmed_at));
        if ($row->unsubscribed_at) echo '<br>Abgemeldet: ' . esc_html(wp_date('d.m.Y H:i', (int)$row->unsubscribed_at));
        echo '<details><summary>Einwilligungstext</summary>' . esc_html($row->consent) . '</details></td><td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rl_mail_delete"><input type="hidden" name="subscriber" value="' . (int)$row->id . '">';
        wp_nonce_field('rl_mail_delete_' . $row->id);
        echo '<button class="button" type="submit">Anmeldung und Versanddaten löschen</button></form></td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="4">Keine Anmeldungen vorhanden.</td></tr>';
    echo '</tbody></table>';
    $total = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . rl_mail_table('subscribers'));
    echo wp_kses_post(paginate_links(['base' => add_query_arg('paged', '%#%', $base), 'current' => $page, 'total' => (int)ceil($total / 50)]));
    echo '<p>Anmeldeformular für weitere Seiten: <code>[rommelfangen_notifications]</code>. Auf der Konzertstartseite wird es automatisch angezeigt.</p></div>';
}
add_action('admin_post_rl_mail_queue', function () {
    if (!current_user_can('manage_options') || $_SERVER['REQUEST_METHOD'] !== 'POST') wp_die('Nicht erlaubt.', '', ['response' => 403]);
    check_admin_referer('rl_mail_queue');
    $id = absint(rl_mail_input($_POST, 'concert'));
    [$subject, $body] = rl_mail_campaign_content($id);
    if (!hash_equals(hash('sha256', $subject . "\n" . $body), sanitize_text_field(rl_mail_input($_POST, 'preview')))) {
        $result = new WP_Error('changed', 'Das Konzert wurde geändert. Bitte prüfe die neue Vorschau.');
    } else $result = rl_mail_queue($id);
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
