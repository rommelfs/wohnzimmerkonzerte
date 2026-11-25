<?php
if (!defined('ABSPATH')) exit;
add_action('admin_menu',static function () { add_submenu_page('edit.php?post_type=rl_concert','Konzertbetrieb','Konzertbetrieb','manage_woocommerce','rl-operations','rl_operations_page');
    if (current_user_can('manage_options') && !current_user_can('manage_woocommerce')) add_submenu_page('edit.php?post_type=rl_concert','Konzertbetrieb','Konzertbetrieb','manage_options','rl-operations','rl_operations_page');
});
function rl_guest_rows(int $event): array {
    $rows=[];
    foreach (rl_event_orders($event) as $order) foreach ($order->get_items() as $item) {
        if (rl_item_event($item)!==$event) continue;
        $remaining=rl_item_remaining($order,$item);$paid=in_array($order->get_status(),['processing','completed'],true);
        $rows[]=['order'=>$order,'item'=>$item,'name'=>trim($order->get_billing_first_name().' '.$order->get_billing_last_name()),'email'=>$order->get_billing_email(),'quantity'=>$remaining,'paid'=>$paid,'checked'=>min($remaining,(int)$item->get_meta('_rl_checked_in'))];
    }
    return $rows;
}
function rl_operations_page(): void {
    if (!rl_ops_allowed()) return;
    $event=absint(rl_mail_input($_GET,'concert'));$base=admin_url('edit.php?post_type=rl_concert&page=rl-operations');
    echo '<div class="wrap"><h1>Konzertbetrieb</h1><form method="get"><input type="hidden" name="post_type" value="rl_concert"><input type="hidden" name="page" value="rl-operations"><label for="rl-ops-event">Konzert</label> <select id="rl-ops-event" name="concert">';
    $events=get_posts(['post_type'=>'rl_concert','post_status'=>['publish','draft','private','future','trash'],'numberposts'=>-1]);
    foreach ($events as $post) echo '<option value="'.$post->ID.'" '.selected($event,$post->ID,false).'>'.esc_html($post->post_title.' · '.$post->post_status).'</option>';
    echo '</select> <button class="button">Übersicht öffnen</button></form>';
    if ($event && get_post_type($event)==='rl_concert') {
        $rows=rl_guest_rows($event);$paid=0;$open=0;$checked=0;$revenue=0;
        foreach ($rows as $row) { if ($row['paid']) { $paid+=$row['quantity'];$checked+=$row['checked'];$revenue+=max(0,(float)$row['item']->get_total()-abs((float)$row['order']->get_total_refunded_for_item($row['item']->get_id()))); } elseif (in_array($row['order']->get_status(),['on-hold','pending'],true)) $open+=$row['quantity']; }
        $wait=get_posts(['post_type'=>'rl_waitlist','post_status'=>'private','numberposts'=>-1,'meta_key'=>'_rl_wait_event','meta_value'=>$event]);$waiting=0;foreach($wait as $entry) if(get_post_meta($entry->ID,'_rl_wait_status',true)==='confirmed')$waiting++;
        $product=rl_ticket_product($event);$stock=$product ? max(0,(int)$product->get_stock_quantity()-(function_exists('wc_get_held_stock_quantity') ? (int)wc_get_held_stock_quantity($product) : 0)) : 0;
        echo '<h2>'.esc_html(get_the_title($event)).'</h2><p><strong>Bezahlt: '.$paid.' · Offen / reserviert: '.$open.' · Freier Bestand: '.$stock.' · Eingecheckt: '.$checked.'</strong></p><p>Bezahlter Positionsumsatz (netto, nach Erstattungen): '.wp_kses_post(wc_price($revenue)).'. Freiplätze und Künstlerplätze vorher vom Ticketbestand abziehen. Bestätigte Warteliste: '.$waiting.' (keine Platzreservierung).</p>';
        if (get_post_meta($event,'rl_cancelled',true)==='1') echo '<div class="notice notice-warning inline"><p>Dieses Konzert ist abgesagt. Verkauf bleibt gesperrt. Besteller können weiterhin gezielt informiert werden.</p></div>';
        echo '<p>';
        if (current_user_can('manage_options')) echo '<a class="button" href="'.esc_url(admin_url('edit.php?post_type=rl_concert&page=rl-notifications&concert='.$event.'&audience=buyers_all')).'">Betroffene Besteller informieren</a> ';
        else echo 'Bestellermails können Websiteadministratoren freigeben. ';
        echo '<a class="button" href="'.esc_url(wp_nonce_url(add_query_arg(['action'=>'rl_guest_csv','concert'=>$event],admin_url('admin-post.php')),'rl_guest_csv_'.$event)).'">Gästeliste als CSV</a> <button class="button" onclick="window.print()">Drucken</button></p><table class="widefat striped"><thead><tr><th>Bestellung / Gast</th><th>Zahlung</th><th>Plätze</th><th>Einlass</th><th>Bearbeiten</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $order=$row['order'];$due=(int)$order->get_meta('_rl_payment_deadline');
            echo '<tr><td>#'.esc_html($order->get_order_number()).' · '.esc_html($row['name']).'<br>'.esc_html($row['email']).'</td><td>'.esc_html(wc_get_order_status_name($order->get_status()));
            if ($due && $order->get_status()==='on-hold') echo '<br><strong>'.($due<time()?'Überfällig: ':'Frist: ').esc_html(wp_date('d.m.Y H:i',$due)).'</strong>';
            echo '</td><td>'.(int)$row['quantity'].'</td><td>';
            if ($row['paid'] && $row['quantity']) {
                echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_checkin"><input type="hidden" name="concert" value="'.$event.'"><input type="hidden" name="order" value="'.$order->get_id().'"><input type="hidden" name="item" value="'.$row['item']->get_id().'">';wp_nonce_field('rl_checkin_'.$row['item']->get_id());
                echo '<label>Da: <input type="number" name="quantity" min="0" max="'.$row['quantity'].'" value="'.$row['checked'].'" style="width:5em"></label> / '.$row['quantity'].' <button class="button">Speichern</button></form>';
            } else echo 'Kein Einlass ohne bezahlte Plätze';
            echo '<span class="rl-print-checkin">'.$row['checked'].' / '.$row['quantity'].'</span></td><td><a href="'.esc_url($order->get_edit_order_url()).'">Bestellung / Erstattung öffnen</a></td></tr>';
        }
        if (!$rows) echo '<tr><td colspan="5">Keine Buchungen für diesen Abend.</td></tr>';
        echo '</tbody></table>';
        if (current_user_can('edit_post',$event)) {
        echo '<details><summary>Absage / Änderungen</summary><p>Eine Absage sperrt den Verkauf. Danach die betroffenen Besteller über den obigen Link informieren. Erstattungen werden bewusst pro betroffener Konzertposition in WooCommerce ausgeführt; andere Konzerte derselben Bestellung bleiben erhalten.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_cancel_concert"><input type="hidden" name="concert" value="'.$event.'">';wp_nonce_field('rl_cancel_concert_'.$event);
        echo '<label><input type="checkbox" name="confirm" value="1" required> Konzert verbindlich als abgesagt markieren (keine automatische Erstattung)</label> <button class="button">Absage speichern</button></form></details>';
        }
    }
    if (current_user_can('manage_options')) {
        $o=rl_ops_options();echo '<details><summary>Überweisungsfristen und Betriebsstatus</summary><p>Neue Überweisungsbuchungen erhalten eine Frist bis spätestens Konzertbeginn. Eine Erinnerung erfolgt frühestens am Tag vor Fristende. Automatische Stornierung ist standardmäßig ausgeschaltet. Bei Aktivierung werden überfällige Überweisungsbestellungen vollständig storniert und ihre Bestände über WooCommerce freigegeben.</p><form action="options.php" method="post">';settings_fields('rl_operations');
        echo '<p><label>Zahlungsfrist (Tage): <input type="number" min="1" max="30" name="rl_operations[transfer_days]" value="'.(int)$o['transfer_days'].'"></label></p><p><label><input type="checkbox" name="rl_operations[auto_cancel]" value="1" '.checked($o['auto_cancel'],true,false).'> Überfällige Überweisungsbestellungen automatisch stornieren</label></p>';echo '<p><label><input type="checkbox" name="rl_operations[mail_paused]" value="1" '.checked($o['mail_paused'],true,false).'> Wartenden Newsletter-/Bestellerversand pausieren (Bestätigungen und Zahlungserinnerungen bleiben aktiv)</label></p>';submit_button('Betriebseinstellungen speichern','secondary');echo '</form><p>Nächste Betriebsprüfung: '.esc_html(wp_date('d.m.Y H:i',wp_next_scheduled('rl_ops_tick') ?: time())).'. Letzte tatsächliche Betriebsprüfung: '.esc_html(get_option('rl_ops_last_run') ? wp_date('d.m.Y H:i',(int)get_option('rl_ops_last_run')) : 'noch nicht gelaufen').'. Ein zuverlässiger serverseitiger WordPress-Cron ist erforderlich.</p></details>';
    }
    echo '</div>';
}
function rl_checkin(int $order_id,int $item_id,int $event,int $quantity): bool {
    $order=wc_get_order($order_id);$item=$order ? $order->get_item($item_id) : false;
    if (!$order || !$item || (int)$item->get_order_id() !== $order_id || rl_item_event($item)!==$event || !in_array($order->get_status(),['processing','completed'],true) || $quantity<0 || $quantity>rl_item_remaining($order,$item)) return false;
    $item->update_meta_data('_rl_checked_in',$quantity);$item->update_meta_data('_rl_checkin_by',get_current_user_id());$item->save();return true;
}
add_action('admin_post_rl_checkin',static function () {
    if (!rl_ops_allowed() || $_SERVER['REQUEST_METHOD']!=='POST') wp_die('Nicht erlaubt.','',['response'=>403]);
    $item=absint(rl_mail_input($_POST,'item'));$event=absint(rl_mail_input($_POST,'concert'));check_admin_referer('rl_checkin_'.$item);
    if (!rl_checkin(absint(rl_mail_input($_POST,'order')),$item,$event,(int)rl_mail_input($_POST,'quantity'))) wp_die('Einlassstatus nicht übernommen. Zahlungsstatus, Menge und Konzert prüfen.','',['response'=>400]);
    wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-operations&concert='.$event));exit;
});
add_action('admin_post_rl_cancel_concert',static function () {
    if (!rl_ops_allowed() || $_SERVER['REQUEST_METHOD']!=='POST') wp_die('Nicht erlaubt.','',['response'=>403]);
    $event=absint(rl_mail_input($_POST,'concert'));check_admin_referer('rl_cancel_concert_'.$event);
    if (get_post_type($event)!=='rl_concert' || !current_user_can('edit_post',$event) || rl_mail_input($_POST,'confirm')!=='1') wp_die('Absage nicht bestätigt.','',['response'=>400]);
    update_post_meta($event,'rl_cancelled','1');wp_safe_redirect(admin_url('edit.php?post_type=rl_concert&page=rl-operations&concert='.$event));exit;
});
function rl_guest_csv_cell(string $value): string { return preg_match('/^[\s\x00-\x1f]*[=+@\-]/u',$value) ? "'".$value : $value; }
add_action('admin_post_rl_guest_csv',static function () {
    if (!rl_ops_allowed()) wp_die('Nicht erlaubt.','',['response'=>403]);$event=absint(rl_mail_input($_GET,'concert'));check_admin_referer('rl_guest_csv_'.$event);
    nocache_headers();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="gaesteliste-'.$event.'.csv"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Bestellung','Name','E-Mail','Zahlungsstatus','Plätze','Eingecheckt'],';', '"', '');
    foreach (rl_guest_rows($event) as $r) fputcsv($out,array_map('rl_guest_csv_cell',[(string)$r['order']->get_order_number(),$r['name'],$r['email'],$r['order']->get_status(),(string)$r['quantity'],(string)$r['checked']]),';','"','');fclose($out);exit;
});
add_action('woocommerce_order_details_after_order_table',static function ($order) {
    foreach ($order->get_items() as $item) if (($event=rl_item_event($item)) && get_post_status($event)==='publish') echo '<p><a href="'.esc_url(rl_calendar_url($event)).'">'.esc_html(get_the_title($event)).' im Kalender speichern</a></p>';
});

add_action('admin_enqueue_scripts',static function () {
    if (rl_mail_input($_GET,'page')==='rl-operations') wp_add_inline_style('common','@media print{#adminmenumain,#wpadminbar,#wpfooter,#screen-meta-links,.wrap form,.wrap details,.wrap .button{display:none!important}#wpcontent{margin-left:0!important}.rl-print-checkin{display:inline!important}}.rl-print-checkin{display:none}');
});
