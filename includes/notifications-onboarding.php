<?php
if (!defined('ABSPATH')) exit;

function rl_mail_invalid_link(): void {
    $home=function_exists('rl_concert_home_url')?rl_concert_home_url():home_url('/');
    $url=rl_newsletter_anchor()?$home.'#'.rl_newsletter_anchor():$home;
    rl_mail_page('Dieser Link ist abgelaufen, wurde bereits benutzt oder durch einen neueren Link ersetzt. Wenn du schon „Du bist angemeldet!“ gesehen hast, ist deine Anmeldung abgeschlossen. Andernfalls fordere bitte eine neue Bestätigungs-E-Mail an.','<p><a class="button" href="'.esc_url($url).'">Neue Bestätigungs-E-Mail anfordern</a></p>','Dieser Link ist nicht mehr aktiv','rl-mail-link-expired');
}
function rl_mail_send_confirmation(object $row,string $token,bool $reminder=false): bool {
    $site=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
    $url=rl_mail_url('confirm',(int)$row->id,$token);
    $consent=(string)($row->pending_consent?:$row->consent);
    $intro=$reminder?'Du hast Konzertbenachrichtigungen angefordert. Deine Anmeldung ist noch nicht bestätigt.':'Danke für dein Interesse an unseren Wohnzimmerkonzerten!';
    $explanation=$row->status==='confirmed'?'Deine bisherige Anmeldung bleibt unverändert aktiv. Mit diesem Klick bestätigst du den unten beschriebenen erweiterten Themenumfang.':'Erst nach deinem Klick bist du angemeldet und erhältst Konzertbenachrichtigungen.';
    $text="$intro\n\nNur noch ein Klick: Öffne diesen Link, um deine Anmeldung bei $site abzuschließen:\n$url\n\nDu musst auf der Website nichts mehr anklicken. $explanation\n\n$consent\n\nDer Link gilt 48 Stunden. Falls du die Anmeldung nicht angefordert hast, ignoriere diese Nachricht.";
    $html='<h1>Nur noch ein Klick!</h1><p>'.esc_html($intro).'</p><p>Klicke auf den Button, um deine Anmeldung bei '.esc_html($site).' abzuschließen.</p><p style="margin:28px 0"><a href="'.esc_url($url).'" style="display:inline-block;padding:16px 24px;background:#263b31;color:#ffffff;font-weight:bold;text-decoration:none;border-radius:6px">Ja, Anmeldung bestätigen</a></p><p><strong>Danach bist du fertig.</strong> Auf der Website ist kein weiterer Klick nötig.</p><p>'.esc_html($explanation).'</p><p>'.esc_html($consent).'</p><p>Der Link gilt 48 Stunden. Falls du die Anmeldung nicht angefordert hast, ignoriere diese Nachricht.</p>';
    return rl_mail_send($row->email,($reminder?'Erinnerung: ':'').'Nur noch ein Klick – Konzertanmeldung bestätigen',$html,$text);
}
function rl_mail_reminder_error(object $row): ?WP_Error {
    if ($row->status!=='pending') return new WP_Error('status','Nur noch nicht bestätigte Anmeldungen können erinnert werden.');
    if ((int)$row->reminded_at) return new WP_Error('reminded','Für diese Anmeldung wurde bereits ein erneuter Versand angefordert.');
    if ((int)$row->requested_at>time()-DAY_IN_SECONDS) return new WP_Error('early','Eine einmalige Erinnerung ist frühestens 24 Stunden nach der Anmeldung möglich.');
    if ((int)$row->requested_at<time()-5*DAY_IN_SECONDS) return new WP_Error('old','Diese Anmeldung ist zu alt für eine Erinnerung. Bitte die Person bei einem persönlichen Kontakt auf das Anmeldeformular hinweisen.');
    return null;
}
function rl_mail_remind(int $id) {
    global $wpdb;$table=rl_mail_table('subscribers');
    $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));
    if (!$row) return new WP_Error('missing','Anmeldung nicht gefunden.');
    if ($error=rl_mail_reminder_error($row)) return $error;
    $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$now=time();
    // A single atomic claim prevents two admin requests from sending duplicate reminders.
    $claimed=$wpdb->update($table,['token_hash'=>$hash,'token_created_at'=>$now,'reminded_at'=>$now,'confirmation_status'=>'sending'],['id'=>$id,'status'=>'pending','generation'=>$row->generation,'token_hash'=>$row->token_hash,'requested_at'=>$row->requested_at,'reminded_at'=>0]);
    if (!$claimed) return new WP_Error('changed','Die Anmeldung wurde inzwischen verändert. Bitte die Empfängerliste neu laden.');
    // Preserve the original request time, source, profile and exact consent scope.
    $ok=rl_mail_send_confirmation($row,$token,true);
    $wpdb->update($table,['confirmation_status'=>$ok?'sent':'failed'],['id'=>$id,'token_hash'=>$hash]);
    return $ok?true:new WP_Error('mail','Die Erinnerung konnte nicht an den Mailserver übergeben werden. Bitte den Mailweg prüfen; es erfolgt keine automatische Wiederholung.');
}
function rl_mail_registration_options(): array {
    return array_replace(['notify_admin'=>true,'admin_email'=>''],(array)get_option('rl_mail_registration',[]));
}
function rl_mail_admin_signup_notice(int $id): void {
    $options=rl_mail_registration_options();
    if (!apply_filters('rl_mail_admin_signup_notice_enabled',$options['notify_admin'])) return;
    $email=$options['admin_email']?:get_option('admin_email');if (!is_email($email)) return;
    global $wpdb;$table=rl_mail_table('subscribers');
    $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));
    if (!$row || $row->status!=='confirmed') return;
    $claim=$wpdb->update($table,['admin_notice_status'=>'sending'],['id'=>$id,'status'=>'confirmed','generation'=>$row->generation,'admin_notice_status'=>'']);if (!$claim) return;
    $site=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);$admin=admin_url('edit.php?post_type=rl_concert&page=rl-notifications');
    $text="Eine neue Newsletter-Anmeldung bei $site ist vollständig bestätigt.\n\nE-Mail: ".$row->email."\nName: ".trim($row->first_name.' '.$row->last_name)."\nBestätigt: ".wp_date('d.m.Y H:i',(int)$row->confirmed_at)."\n\nEmpfänger verwalten:\n".$admin;
    try { $ok=rl_mail_send($email,'Neue bestätigte Konzertanmeldung: '.$site,'<h1>Neue bestätigte Anmeldung</h1><p>'.esc_html($row->email).'</p><p>'.esc_html(trim($row->first_name.' '.$row->last_name)).'</p><p>Die Anmeldung ist vollständig abgeschlossen.</p><p><a href="'.esc_url($admin).'">Empfänger verwalten</a></p>',$text); } catch (Throwable $error) { $ok=false; }
    $wpdb->update($table,['admin_notice_status'=>$ok?'sent':'failed'],['id'=>$id,'generation'=>$row->generation,'admin_notice_status'=>'sending']);
}
function rl_mail_registration_settings($input): array {
    $input=is_array($input)?$input:[];
    $email=trim(is_string($input['admin_email']??null)?$input['admin_email']:'');
    if ($email!=='' && !is_email($email)) {
        add_settings_error('rl_mail_registration','email','Bitte eine gültige Empfängeradresse eingeben. Die bisherige Adresse bleibt erhalten.');
        $email=rl_mail_registration_options()['admin_email'];
    }
    return ['notify_admin'=>!empty($input['notify_admin']),'admin_email'=>$email];
}
add_action('admin_init',static function () {
    register_setting('rl_mail_registration','rl_mail_registration',['type'=>'array','sanitize_callback'=>'rl_mail_registration_settings']);
});
function rl_mail_registration_form(): void {
    $options=rl_mail_registration_options();
    settings_errors('rl_mail_registration');
    echo '<details><summary><strong>Admin-Benachrichtigung bei neuen Anmeldungen</strong></summary><p>Nach jeder neu bestätigten Anmeldung wird einmalig eine Nachricht an diese Adresse übergeben. Offene Anmeldungen und nachträgliche Änderungen des Themenumfangs lösen keine solche Mail aus. Das Update verschickt keine rückwirkenden Benachrichtigungen.</p><form method="post" action="options.php">';
    settings_fields('rl_mail_registration');
    echo '<p><label><input type="checkbox" name="rl_mail_registration[notify_admin]" value="1" '.checked($options['notify_admin'],true,false).'> Admin nach erfolgreicher Anmeldung benachrichtigen</label></p><p><label for="rl-admin-signup-email">Empfängeradresse (leer = Website-Adminadresse)</label><br><input id="rl-admin-signup-email" type="email" class="regular-text" name="rl_mail_registration[admin_email]" value="'.esc_attr($options['admin_email']).'" placeholder="'.esc_attr(get_option('admin_email')).'"></p><p>Aktuelles Ziel: '.esc_html($options['admin_email']?:get_option('admin_email')).'</p>';
    submit_button('Admin-Benachrichtigung speichern');echo '</form></details>';
}
function rl_mail_reminder_action(object $row): void {
    if ($row->status!=='pending') return;
    $error=rl_mail_reminder_error($row);
    if ($error) { echo '<p class="description">'.esc_html($error->get_error_message()).'</p>';return; }
    echo '<details><summary>Einmalige Erinnerung senden</summary><p>Eine neue Bestätigungs-E-Mail an '.esc_html($row->email).'. Keine Konzertwerbung. Der alte Link wird ersetzt; die Anmeldung bleibt bis zum Linkklick offen.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_mail_remind"><input type="hidden" name="subscriber" value="'.(int)$row->id.'">';
    wp_nonce_field('rl_mail_remind_'.$row->id);echo '<button class="button" type="submit">Bestätigungslink erneut senden</button></form></details>';
}
add_action('admin_post_rl_mail_remind',static function () {
    if (!current_user_can('manage_options') || $_SERVER['REQUEST_METHOD']!=='POST') wp_die('Nicht erlaubt.','',['response'=>403]);
    $id=absint(rl_mail_input($_POST,'subscriber'));check_admin_referer('rl_mail_remind_'.$id);
    $result=rl_mail_remind($id);
    set_transient('rl_mail_admin_'.get_current_user_id(),is_wp_error($result)?$result->get_error_message():'Erinnerung an den Mailserver übergeben. Die Anmeldung bleibt bis zum Bestätigungslink-Klick offen.',MINUTE_IN_SECONDS);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-notifications'));exit;
});
