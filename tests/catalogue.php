<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true)) throw new RuntimeException('Disposable test site required.');
function rl_catalogue_expect($ok,string $message): void { if (!$ok)throw new RuntimeException($message);echo 'PASS: '.$message."\n"; }
$original=get_option('rl_site',null);$original_layout=get_option('rl_layout_version',null);$products=[];$events=[];$messages=[];$entries=[];
$capture=static function ($pre,$mail) use (&$messages) {$messages[]=$mail;return true;};add_filter('pre_wp_mail',$capture,PHP_INT_MAX,2);
ob_start();
try {
 $legacy=rl_site_defaults();$legacy['brand']='Eigene Marke';$legacy['sections']=[rl_section_defaults('about','eigen')];$legacy['sections'][0]['body']='Unveränderte eigene Geschichte';update_option('rl_site',$legacy);delete_option('rl_layout_version');rl_layout_upgrade();rl_layout_upgrade();$upgraded=rl_site();
 rl_catalogue_expect($upgraded['brand']==='Eigene Marke' && $upgraded['sections'][0]['body']==='Unveränderte eigene Geschichte' && count(array_filter($upgraded['sections'],static fn($s)=>$s['type']==='newsletter'))===1,'Existing layout migration preserves content and inserts the newsletter only once');
 $upgraded['sections']=array_values(array_filter($upgraded['sections'],static fn($s)=>$s['type']!=='newsletter'));update_option('rl_site',$upgraded);rl_layout_upgrade();rl_catalogue_expect(!rl_newsletter_anchor(),'A deliberately removed newsletter section is not reinserted');
 $site=rl_site_defaults();$site['sections']=[rl_section_defaults('newsletter','frueh'),rl_section_defaults('testimonials','leer'),rl_section_defaults('about','team')];$site['sections'][2]['body']='Eigene Geschichte';update_option('rl_site',$site);
 $html=rl_render_home();rl_catalogue_expect(strpos($html,'class="rl-mail-signup"')<strpos($html,'Eigene Geschichte') && !str_contains($html,'id="leer"'),'Newsletter can be moved before the story and empty dynamic sections are hidden');
 rl_catalogue_expect(!rl_nav_link_visible('#leer') && rl_nav_link_visible('#team'),'Navigation skips hidden empty sections while preserving visible targets');
 rl_catalogue_expect(!preg_match('/<\/h[1-6]</',$html) && substr_count($html,'<h1>')===1,'Rendered homepage has proper closing heading tags and one main heading');
 $site['sections']=array_reverse($site['sections']);update_option('rl_site',$site);$html=rl_render_home();rl_catalogue_expect(strpos($html,'Eigene Geschichte')<strpos($html,'class="rl-mail-signup"'),'Saved newsletter ordering is respected');
 foreach ([['Ausverkauft',0,DAY_IN_SECONDS],['Verfügbar',3,2*DAY_IN_SECONDS]] as [$title,$stock,$delay]) {
  $p=new WC_Product_Simple();$p->set_name($title);$p->set_status('publish');$p->set_virtual(true);$p->set_regular_price('20');$p->set_manage_stock(true);$p->set_stock_quantity($stock);$p->set_stock_status($stock?'instock':'outofstock');$p->set_backorders('no');$p->save();$products[]=$p;
  $id=wp_insert_post(['post_type'=>'rl_concert','post_status'=>'publish','post_title'=>$title]);$events[]=$id;update_post_meta($id,'rl_start',wp_date('Y-m-d\TH:i',time()+$delay));update_post_meta($id,'rl_product_id',$p->get_id());
 }
 rl_catalogue_expect(rl_shop_selection(rl_shop_events())['id']===$events[1] && rl_shop_selection(rl_shop_events(),$events[0])['id']===$events[0],'General ticket link prefers a bookable concert; explicit sold-out selection is retained');
 rl_catalogue_expect(str_contains(rl_waitlist_form($events[0]),'keine Newsletter-Anmeldung') && rl_waitlist_form($events[1])==='','Sold-out concerts offer a separate waitlist without enrolling a newsletter');
 rl_waitlist_subscribe($events[0],'waiting@example.test');$records=rl_waitlist_records('waiting@example.test');$entry=$records[0]->ID;$entries[]=$entry;$mail=end($messages);
 preg_match('/token=([a-f0-9]{64})/',$mail['message'],$match);$token=$match[1]??'';
 rl_catalogue_expect($token && get_post_meta($entry,'_rl_wait_status',true)==='pending' && !rl_mail_subscriber('waiting@example.test'),'Waitlist signup requires independent confirmation and creates no newsletter subscriber');
 rl_catalogue_expect(!rl_waitlist_confirm($entry,str_repeat('0',64)) && rl_waitlist_confirm($entry,$token) && !rl_waitlist_confirm($entry,$token),'Waitlist confirmation token is correct, expiring and single use');
 $count=count($messages);rl_waitlist_subscribe($events[0],'waiting@example.test');rl_waitlist_tick();rl_catalogue_expect(count($messages)===$count,'Duplicate signup and sold-out inventory cause no extra emails');
 $products[0]->set_stock_quantity(1);$products[0]->set_stock_status('instock');$products[0]->save();rl_waitlist_tick();
 rl_catalogue_expect(count($messages)===$count+1 && get_post_meta($entry,'_rl_wait_status',true)==='notified' && str_contains(end($messages)['message'],'Keine Reservierung'),'Available stock triggers one clear non-reserving availability notice');
 rl_waitlist_tick();rl_catalogue_expect(count($messages)===$count+1,'Availability notification is not sent repeatedly');
 $exporters=apply_filters('wp_privacy_personal_data_exporters',[]);$export=$exporters['rl-waitlist']['callback']('waiting@example.test');rl_catalogue_expect(count($export['data'])===1,'Waitlist is included in privacy exports');
 $erasers=apply_filters('wp_privacy_personal_data_erasers',[]);$erasers['rl-waitlist']['callback']('waiting@example.test');rl_catalogue_expect(!rl_waitlist_records('waiting@example.test'),'Waitlist privacy erasure removes its personal data');
 echo "Catalogue, homepage and waitlist checks passed.\n";
} finally {
 remove_filter('pre_wp_mail',$capture,PHP_INT_MAX);foreach($entries as $id)wp_delete_post($id,true);foreach($events as $id)wp_delete_post($id,true);foreach($products as $p)$p->delete(true);if($original===null)delete_option('rl_site');else update_option('rl_site',$original);wp_clear_scheduled_hook('rl_waitlist_tick');if($original_layout===null)delete_option('rl_layout_version');else update_option('rl_layout_version',$original_layout);ob_end_flush();
}
