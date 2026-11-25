<?php
if (!defined('ABSPATH')) exit;
add_action('init',static function () { register_post_type('rl_waitlist',['public'=>false,'show_ui'=>false,'show_in_rest'=>false,'supports'=>[],'rewrite'=>false,'capabilities'=>['edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options','edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','create_posts'=>'manage_options'],'map_meta_cap'=>false]); });
function rl_waitlist_records(string $email): array { return get_posts(['post_type'=>'rl_waitlist','post_status'=>'private','numberposts'=>-1,'meta_key'=>'_rl_wait_email','meta_value'=>strtolower(trim($email))]); }
function rl_waitlist_token(int $id): string { return hash_hmac('sha256','wait:'.get_current_blog_id().':'.$id,wp_salt('auth')); }
function rl_waitlist_url(int $id,string $mode,string $token): string { return add_query_arg(['action'=>'rl_waitlist_link','entry'=>$id,'mode'=>$mode,'token'=>$token],admin_url('admin-post.php')); }
function rl_waitlist_form(int $event): string {
    $p=rl_ticket_product($event);
    if (!rl_mail_concert_eligible($event) || !$p || $p->is_in_stock()) return '';
    $id=wp_unique_id('rl-wait-');
    return '<form class="rl-waitlist" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><h3>Wieder Plätze frei?</h3><p>Einmalige Nachricht, wenn für diesen Abend wieder Tickets verfügbar sind. Keine Reservierung und keine Newsletter-Anmeldung.</p><input type="hidden" name="action" value="rl_waitlist_signup"><input type="hidden" name="concert" value="'.$event.'">'.wp_nonce_field('rl_waitlist_signup','_wpnonce',true,false).'<p><label for="'.esc_attr($id).'">E-Mail-Adresse</label><br><input id="'.esc_attr($id).'" type="email" name="email" required maxlength="254" autocomplete="email"></p><p><label><input type="checkbox" name="consent" value="1" required> Ich möchte einmalig über verfügbare Plätze für diesen Konzertabend informiert werden. Abmeldung jederzeit möglich.</label></p><button class="button">Auf die Warteliste</button></form>';
}
function rl_waitlist_subscribe(int $event,string $email): void {
    $email=strtolower(trim($email));if (!is_email($email) || !rl_mail_concert_eligible($event) || !($p=rl_ticket_product($event)) || $p->is_in_stock()) return;
    $lock='rl_wait_lock_'.hash('sha256',$event.':'.$email);$old=(int)get_option($lock,0);if ($old && $old<time()-5*MINUTE_IN_SECONDS) delete_option($lock);
    if (!add_option($lock,time(),'','no')) return;
    try {
        $existing=0;foreach (rl_waitlist_records($email) as $entry) if ((int)get_post_meta($entry->ID,'_rl_wait_event',true)===$event) { $existing=$entry->ID;break; }
        if ($existing && (in_array(get_post_meta($existing,'_rl_wait_status',true),['confirmed','notified'],true) || (int)get_post_meta($existing,'_rl_wait_requested',true)>time()-HOUR_IN_SECONDS)) return;
        $id=$existing ?: wp_insert_post(['post_type'=>'rl_waitlist','post_status'=>'private','post_title'=>'Warteliste Konzert '.$event]);if (!$id || is_wp_error($id)) return;
        $token=bin2hex(random_bytes(32));
        foreach (['_rl_wait_email'=>$email,'_rl_wait_event'=>$event,'_rl_wait_status'=>'pending','_rl_wait_requested'=>time(),'_rl_wait_hash'=>hash('sha256',$token),'_rl_wait_consent'=>'Einmaliger Verfügbarkeitshinweis für dieses Konzert; keine Reservierung und kein Newsletter.'] as $key=>$value) update_post_meta($id,$key,$value);
        $url=rl_waitlist_url($id,'confirm',$token);$text='Bitte bestätige deinen Wartelisteneintrag für '.get_the_title($event).":\n".$url."\nDer Link gilt 48 Stunden. Die Warteliste reserviert keine Plätze und ist keine Newsletter-Anmeldung.";
        rl_mail_send($email,'Warteliste bestätigen','<p>'.esc_html(get_the_title($event)).'</p><p><a href="'.esc_url($url).'">Warteliste bestätigen</a></p><p>Keine Platzreservierung und keine Newsletter-Anmeldung. Der Link gilt 48 Stunden.</p>',$text);
    } finally { delete_option($lock); }
}
function rl_waitlist_confirm(int $id,string $token): bool {
    if (get_post_type($id)!=='rl_waitlist' || get_post_meta($id,'_rl_wait_status',true)!=='pending' || (int)get_post_meta($id,'_rl_wait_requested',true)<time()-2*DAY_IN_SECONDS || !hash_equals((string)get_post_meta($id,'_rl_wait_hash',true),hash('sha256',$token))) return false;
    update_post_meta($id,'_rl_wait_status','confirmed');delete_post_meta($id,'_rl_wait_hash');update_post_meta($id,'_rl_wait_confirmed',time());return true;
}
function rl_waitlist_link(): void {
    $id=absint(rl_mail_input($_REQUEST,'entry'));$mode=rl_mail_input($_REQUEST,'mode');$token=rl_mail_input($_REQUEST,'token');
    if (get_post_type($id)!=='rl_waitlist' || !in_array($mode,['confirm','unsubscribe'],true) || !preg_match('/^[a-f0-9]{64}$/D',$token)) rl_mail_page('Ungültiger Wartelistenlink.');
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $ok=$mode==='confirm' ? rl_waitlist_confirm($id,$token) : hash_equals(rl_waitlist_token($id),$token);
        if ($ok && $mode==='unsubscribe') { update_post_meta($id,'_rl_wait_status','unsubscribed');delete_post_meta($id,'_rl_wait_hash'); }
        rl_mail_page($ok ? ($mode==='confirm' ? 'Dein Wartelisteneintrag ist bestätigt. Es werden keine Plätze reserviert.' : 'Du erhältst keinen weiteren Hinweis zu diesem Konzert.') : 'Dieser Wartelistenlink ist ungültig oder abgelaufen.');
    }
    $form='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="rl_waitlist_link"><input type="hidden" name="entry" value="'.$id.'"><input type="hidden" name="mode" value="'.esc_attr($mode).'"><input type="hidden" name="token" value="'.esc_attr($token).'"><button class="button">'.($mode==='confirm'?'Warteliste bestätigen':'Von der Warteliste abmelden').'</button></form>';
    rl_mail_page('Warteliste für '.get_the_title((int)get_post_meta($id,'_rl_wait_event',true)),$form);
}
foreach (['admin_post_','admin_post_nopriv_'] as $prefix) {
    add_action($prefix.'rl_waitlist_link','rl_waitlist_link');
    add_action($prefix.'rl_waitlist_signup',static function () {
        if ($_SERVER['REQUEST_METHOD']!=='POST') rl_mail_page('Bitte verwende das Wartelistenformular.');check_admin_referer('rl_waitlist_signup');
        $key='rl_wait_rate_'.hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),wp_salt('nonce'));$count=(int)get_transient($key);
        if ($count>=120) rl_mail_page('Zu viele Anmeldungen. Bitte später erneut versuchen.');set_transient($key,$count+1,HOUR_IN_SECONDS);
        if (($product=rl_ticket_product(absint(rl_mail_input($_POST,'concert')))) && $product->is_purchasable() && $product->is_in_stock()) rl_mail_page('Für diesen Abend sind wieder Tickets verfügbar.','<p><a class="button" href="'.esc_url(rl_shop_url(absint(rl_mail_input($_POST,'concert')))).'">Tickets ansehen</a></p>');
        if (rl_mail_input($_POST,'consent')==='1') rl_waitlist_subscribe(absint(rl_mail_input($_POST,'concert')),sanitize_email(rl_mail_input($_POST,'email')));
        rl_mail_page('Falls eine Bestätigung erforderlich ist, erhältst du eine E-Mail für die Warteliste.');
    });
}
function rl_waitlist_tick(): void {
    $remaining=[];
    foreach (get_posts(['post_type'=>'rl_waitlist','post_status'=>'private','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']) as $entry) {
        $id=$entry->ID;$event=(int)get_post_meta($id,'_rl_wait_event',true);$state=get_post_meta($id,'_rl_wait_status',true);$requested=(int)get_post_meta($id,'_rl_wait_requested',true);
        $start=rl_timestamp((string)get_post_meta($event,'rl_start',true));
        if ($state==='pending' && $requested<time()-7*DAY_IN_SECONDS || !$start || $start<time()-7*DAY_IN_SECONDS) { wp_delete_post($id,true);continue; }
        if ($state!=='confirmed' || !rl_mail_concert_eligible($event) || $start<=time()) continue;
        $product=rl_ticket_product($event);if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) continue;
        if (!isset($remaining[$event])) $remaining[$event]=min(20,max(0,(int)$product->get_stock_quantity()-(function_exists('wc_get_held_stock_quantity')?(int)wc_get_held_stock_quantity($product):0)));
        if ($remaining[$event]<=0) continue;
        $claim='rl_wait_send_'.$id;$old=(int)get_option($claim,0);if ($old && $old<time()-15*MINUTE_IN_SECONDS) { update_post_meta($id,'_rl_wait_status','uncertain');delete_option($claim);continue; }
        if (!add_option($claim,time(),'','no')) continue;
        try {
            $url=rl_shop_url($event);$unsubscribe=rl_waitlist_url($id,'unsubscribe',rl_waitlist_token($id));
            $text='Für '.get_the_title($event)." sind wieder Tickets verfügbar.\n".$url."\nDies ist keine Reservierung. Der Kauf richtet sich nach dem verfügbaren Bestand.\nAbmelden: ".$unsubscribe;
            $ok=rl_mail_send((string)get_post_meta($id,'_rl_wait_email',true),'Wieder Plätze frei: '.sanitize_text_field(get_the_title($event)),'<p>'.esc_html(get_the_title($event)).'</p><p><a href="'.esc_url($url).'">Tickets ansehen</a></p><p>Keine Reservierung; Verkauf nach verfügbarem Bestand.</p><p><a href="'.esc_url($unsubscribe).'">Warteliste abmelden</a></p>',$text);
            if ($ok) { update_post_meta($id,'_rl_wait_status','notified');update_post_meta($id,'_rl_wait_sent',time());$remaining[$event]--; }
        } finally { delete_option($claim); }
    }
}
add_action('rl_waitlist_tick','rl_waitlist_tick');add_action('rl_ops_tick','rl_waitlist_tick');
add_action('woocommerce_product_set_stock',static function () { if (!wp_next_scheduled('rl_waitlist_tick')) wp_schedule_single_event(time()+MINUTE_IN_SECONDS,'rl_waitlist_tick'); });
add_filter('wp_privacy_personal_data_exporters',static function ($exporters) {
    $exporters['rl-waitlist']=['exporter_friendly_name'=>'Konzertwartelisten','callback'=>static function ($email,$page=1) {
        $data=[];foreach (rl_waitlist_records($email) as $entry) $data[]=['group_id'=>'rl-waitlist','group_label'=>'Konzertwartelisten','item_id'=>'rl-wait-'.$entry->ID,'data'=>[['name'=>'E-Mail','value'=>$email],['name'=>'Konzert','value'=>get_the_title((int)get_post_meta($entry->ID,'_rl_wait_event',true))],['name'=>'Status','value'=>get_post_meta($entry->ID,'_rl_wait_status',true)],['name'=>'Einwilligung','value'=>get_post_meta($entry->ID,'_rl_wait_consent',true)]]];return ['data'=>$data,'done'=>true];
    }];return $exporters;
});
add_filter('wp_privacy_personal_data_erasers',static function ($erasers) { $erasers['rl-waitlist']=['eraser_friendly_name'=>'Konzertwartelisten','callback'=>static function ($email,$page=1) { $rows=rl_waitlist_records($email);foreach ($rows as $entry) wp_delete_post($entry->ID,true);return ['items_removed'=>(bool)$rows,'items_retained'=>false,'messages'=>[],'done'=>true]; }];return $erasers; });
