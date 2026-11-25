<?php
if (!defined('ABSPATH')) exit;

function rl_ops_allowed(): bool { return current_user_can('manage_woocommerce') || current_user_can('manage_options'); }
function rl_item_event($item): int {
    $saved=(int)$item->get_meta('_rl_concert_id');
    return $saved ?: (int)get_post_meta($item->get_product_id(), '_rl_ticket_concert', true);
}
function rl_item_remaining($order, $item): int { return max(0,(int)$item->get_quantity() - abs((int)$order->get_qty_refunded_for_item($item->get_id()))); }
function rl_order_snapshot($item): void {
    if (!$item instanceof WC_Order_Item_Product || $item->get_meta('_rl_concert_id')) return;
    $event=(int)get_post_meta($item->get_product_id(),'_rl_ticket_concert',true);
    if (!$event) {
        $events=get_posts(['post_type'=>'rl_concert','post_status'=>['publish','draft','private','future','trash'],'numberposts'=>2,'meta_key'=>'rl_product_id','meta_value'=>$item->get_product_id()]);
        if (count($events) !== 1) return;
        $event=(int)$events[0]->ID;rl_remember_ticket((int)$item->get_product_id(),$event);
    }
    $start=rl_timestamp((string)get_post_meta($event,'rl_start',true));
    foreach (['_rl_concert_id'=>$event,'_rl_start'=>$start,'_rl_doors'=>get_post_meta($event,'rl_doors',true),'_rl_public_venue'=>get_post_meta($event,'rl_venue',true)] as $key=>$value) $item->add_meta_data($key,$value,true);
    if ($start) $item->add_meta_data('Konzertbeginn',rl_date($start),true);
    if ($doors=get_post_meta($event,'rl_doors',true)) $item->add_meta_data('Einlass',$doors,true);
    if ($venue=get_post_meta($event,'rl_venue',true)) $item->add_meta_data('Ort (öffentlich)',$venue,true);
}
add_action('woocommerce_checkout_create_order_line_item',static function ($item) { rl_order_snapshot($item); });
add_action('woocommerce_new_order_item',static function ($id,$item) {
    if ($item instanceof WC_Order_Item_Product && !$item->get_meta('_rl_concert_id')) { rl_order_snapshot($item); $item->save(); }
},10,2);
function rl_event_orders(int $event): array {
    if (!function_exists('wc_get_order')) return [];
    global $wpdb;
    $product=(int)get_post_meta($event,'rl_product_id',true);
    $ids=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT i.order_id FROM {$wpdb->prefix}woocommerce_order_items i JOIN {$wpdb->prefix}woocommerce_order_itemmeta m ON m.order_item_id=i.order_item_id WHERE (m.meta_key='_rl_concert_id' AND m.meta_value=%s) OR (m.meta_key='_product_id' AND m.meta_value=%s)",(string)$event,$product ? (string)$product : '-1'));
    $orders=[];
    foreach ($ids as $id) { $order=wc_get_order($id); if ($order && $order->get_type()==='shop_order' && $order->get_status() !== 'trash') $orders[]=$order; }
    return $orders;
}
function rl_booking_recipients(int $event, string $audience, int $only_order=0): array {
    $statuses=match($audience) { 'buyers_paid'=>['processing','completed'], 'buyers_open'=>['pending','on-hold'], 'buyers_all'=>['pending','on-hold','processing','completed'], default=>[] };
    $contacts=[];
    foreach (rl_event_orders($event) as $order) {
        if ($only_order && $order->get_id() !== $only_order || !in_array($order->get_status(),$statuses,true)) continue;
        $qty=0;$detail=null;
        foreach ($order->get_items() as $item) if (rl_item_event($item)===$event && rl_item_remaining($order,$item)>0) { $qty+=rl_item_remaining($order,$item);$detail=$item; }
        $email=strtolower(trim($order->get_billing_email()));
        if (!$qty || !is_email($email)) continue;
        if (isset($contacts[$email])) { $contacts[$email]['tickets']+=$qty; continue; }
        $contacts[$email]=['email'=>$email,'first_name'=>$order->get_billing_first_name(),'last_name'=>$order->get_billing_last_name(),'order_id'=>$order->get_id(),'tickets'=>$qty,'number'=>$order->get_order_number(),
            'start'=>(int)($detail->get_meta('_rl_start') ?: rl_timestamp((string)get_post_meta($event,'rl_start',true))), 'doors'=>$detail->get_meta('_rl_doors') ?: get_post_meta($event,'rl_doors',true),
            'address'=>$audience==='buyers_paid' ? ($detail->get_meta('_rl_private_address') ?: get_post_meta($event,'rl_private_address',true) ?: get_post_meta($event,'rl_venue',true)) : ''];
    }
    return $contacts;
}
function rl_booking_values(array $contact, int $event): array {
    $values=rl_mail_values((object)(['id'=>0]+$contact),$event);
    $values['{Abmeldelink}']='';
    $values['{Bestellnummer}']=(string)$contact['number'];$values['{Tickets}']=(string)$contact['tickets'];$values['{Adresse}']=(string)$contact['address'];$values['{Einlass}']=(string)$contact['doors'];
    if ($contact['start']) { $values['{Datum}']=wp_date('d.m.Y',$contact['start']);$values['{Uhrzeit}']=wp_date('H:i',$contact['start']); }
    return $values;
}
function rl_booking_information(int $order_id, $provided_order=null): void {
    $order=$provided_order instanceof WC_Order ? $provided_order : wc_get_order($order_id);
    if (!$order || !in_array($order->get_status(),['processing','completed'],true)) return;
    $events=[];foreach ($order->get_items() as $item) if ($event=rl_item_event($item)) { $events[$event]=true; if (!$item->get_meta('_rl_private_address')) { $item->add_meta_data('_rl_private_address',get_post_meta($event,'rl_private_address',true),true);$item->save(); } }
    foreach (array_keys($events) as $event) {
        if (!rl_mail_concert_eligible($event)) continue;
        $body='<p>{Anrede}</p><p>Deine Buchung ist bezahlt. Hier sind die Informationen zu deinem Konzertabend:</p><h2>{Konzert}</h2><p>{Datum} · {Uhrzeit}<br>Einlass: {Einlass}<br>Plätze: {Tickets}<br>Bestellung: {Bestellnummer}</p><p>Adresse:<br>{Adresse}</p><p><a href="{Konzertlink}">Konzert ansehen</a></p><p>Bei Fragen antworte bitte auf diese E-Mail.</p>';
        rl_mail_queue($event,['Deine Buchung: {Konzert}',$body,'html',''],hash('sha256','booking-info:'.$order_id.':'.$event),'buyers_paid',$order_id);
    }
}
foreach (['woocommerce_order_status_processing','woocommerce_order_status_completed'] as $hook) add_action($hook,'rl_booking_information',10,2);

// A private address never appears in public concert metadata or unpaid order emails.
add_action('add_meta_boxes',static function () {
    add_meta_box('rl_private','Veranstaltungsbetrieb (intern)',static function ($post) {
        wp_nonce_field('rl_ops_concert','rl_ops_nonce');
        echo '<p><label for="rl_private_address">Private Adresse (nur in Informationen an bezahlte Besteller)</label><textarea class="widefat" id="rl_private_address" name="rl_private_address" rows="4">'.esc_textarea(get_post_meta($post->ID,'rl_private_address',true)).'</textarea></p><p><label for="rl_end">Konzertende (optional, für Kalender)</label><br><input type="datetime-local" id="rl_end" name="rl_end" value="'.esc_attr(get_post_meta($post->ID,'rl_end',true)).'"></p><p><a href="'.esc_url(admin_url('edit.php?post_type=rl_concert&page=rl-operations&concert='.$post->ID)).'">Gästeliste und Konzertübersicht öffnen</a></p>';
    },'rl_concert','normal');
});
add_action('save_post_rl_concert',static function ($id) {
    if (wp_is_post_revision($id) || !current_user_can('edit_post',$id) || !wp_verify_nonce(rl_mail_input($_POST,'rl_ops_nonce'),'rl_ops_concert')) return;
    update_post_meta($id,'rl_private_address',sanitize_textarea_field(rl_mail_input($_POST,'rl_private_address')));
    $end=rl_mail_input($_POST,'rl_end'); if ($end==='' || rl_timestamp($end)>rl_timestamp((string)get_post_meta($id,'rl_start',true))) update_post_meta($id,'rl_end',$end);
});
function rl_ops_options(): array { return array_replace(['transfer_days'=>3,'auto_cancel'=>false,'mail_paused'=>false],(array)get_option('rl_operations',[])); }
add_action('admin_init',static function () { register_setting('rl_operations','rl_operations',['sanitize_callback'=>static fn($in)=>['transfer_days'=>max(1,min(30,absint($in['transfer_days']??3))),'auto_cancel'=>!empty($in['auto_cancel']),'mail_paused'=>!empty($in['mail_paused'])]]); });
add_action('woocommerce_order_status_on-hold',static function ($id,$provided_order=null) {
    $order=$provided_order instanceof WC_Order ? $provided_order : wc_get_order($id);if (!$order || $order->get_payment_method()!=='bacs' || $order->get_meta('_rl_payment_deadline')) return;
    $starts=[];foreach ($order->get_items() as $item) if (rl_item_event($item)) { $start=(int)$item->get_meta('_rl_start');if ($start>time()) $starts[]=$start; }
    if (!$starts) return;
    $due=min(time()+rl_ops_options()['transfer_days']*DAY_IN_SECONDS,min($starts));$order->update_meta_data('_rl_payment_deadline',$due);$order->save_meta_data();
},10,2);
function rl_ops_tick(): void {
    if (!function_exists('wc_get_orders')) return;
    update_option('rl_ops_last_run',time(),false);
    $ids=wc_get_orders(['status'=>'on-hold','limit'=>-1,'return'=>'ids']);
    foreach ($ids as $id) {
        $order=wc_get_order($id);if (!$order || $order->get_status()!=='on-hold') continue;
        $due=(int)$order->get_meta('_rl_payment_deadline');if (!$due || $order->get_payment_method()!=='bacs') continue;
        $payable=true;foreach($order->get_items() as $item) if (($event=rl_item_event($item)) && (!rl_mail_concert_eligible($event) || rl_item_remaining($order,$item)===0)) $payable=false;
        if (!$payable) continue; // Absagen/archivierte Konzerte nicht zur Zahlung auffordern.
        if ($due<=time() && rl_ops_options()['auto_cancel']) { $order->update_status('cancelled','Konzertreservierung: konfigurierte Zahlungsfrist abgelaufen.'); continue; }
        if ($due<=time()+DAY_IN_SECONDS && !$order->get_meta('_rl_payment_reminder')) {
            $text='Bitte prüfe deine Überweisung für Bestellung '.$order->get_order_number().'. Zahlungsfrist: '.wp_date('d.m.Y H:i',$due).'. Bei Fragen antworte bitte auf diese E-Mail.'."\n".$order->get_checkout_order_received_url();
            if (rl_mail_send($order->get_billing_email(),'Zahlungserinnerung zu deiner Konzertbuchung','',$text)) { $order->update_meta_data('_rl_payment_reminder',time());$order->save_meta_data(); }
        }
    }
}
add_action('rl_ops_tick','rl_ops_tick');
add_action('init',static function () { if (!wp_next_scheduled('rl_ops_tick')) wp_schedule_event(time()+HOUR_IN_SECONDS,'hourly','rl_ops_tick'); },25);

function rl_calendar_content(int $id): string {
    $escape=static fn($value)=>str_replace(["\\","\r","\n",',',';'],["\\\\",'',"\\n","\\,","\\;"],wp_strip_all_tags((string)$value));
    $start=rl_timestamp((string)get_post_meta($id,'rl_start',true));$end=rl_timestamp((string)get_post_meta($id,'rl_end',true));
    $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Rommelfangen//Konzerte//DE','BEGIN:VEVENT','UID:rl-'.get_current_blog_id().'-'.$id.'@'.wp_parse_url(home_url(),PHP_URL_HOST),'DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART:'.gmdate('Ymd\THis\Z',$start),'SUMMARY:'.$escape(get_the_title($id)),'LOCATION:'.$escape(get_post_meta($id,'rl_venue',true)),'URL:'.get_permalink($id)];
    if ($end>$start) $lines[]='DTEND:'.gmdate('Ymd\THis\Z',$end);
    if (get_post_meta($id,'rl_cancelled',true)==='1') $lines[]='STATUS:CANCELLED';
    $lines[]='END:VEVENT';$lines[]='END:VCALENDAR';
    $folded=[];foreach ($lines as $line) { while (strlen($line)>73) { preg_match('/^.{1,73}/us',$line,$part);$chunk=substr($line,0,73);while($chunk!=='' && !preg_match('//u',$chunk))$chunk=substr($chunk,0,-1);$folded[]=$chunk;$line=' '.substr($line,strlen($chunk)); }$folded[]=$line; }
    return implode("\r\n",$folded)."\r\n";
}
function rl_calendar_url(int $id): string { return add_query_arg(['action'=>'rl_calendar','concert'=>$id],admin_url('admin-post.php')); }
function rl_calendar_download(): void {
    $id=absint(rl_mail_input($_GET,'concert'));if (get_post_type($id)!=='rl_concert' || get_post_status($id)!=='publish' || !rl_timestamp((string)get_post_meta($id,'rl_start',true))) wp_die('Konzert nicht gefunden.','',['response'=>404]);
    nocache_headers();header('Content-Type: text/calendar; charset=UTF-8');header('Content-Disposition: attachment; filename="konzert-'.$id.'.ics"');echo rl_calendar_content($id);exit;
}
add_action('admin_post_rl_calendar','rl_calendar_download');add_action('admin_post_nopriv_rl_calendar','rl_calendar_download');
