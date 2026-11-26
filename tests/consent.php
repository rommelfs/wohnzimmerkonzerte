<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true)) throw new RuntimeException('Isolated test site required.');
global $wpdb;$s=rl_mail_table('subscribers');$c=rl_mail_table('campaigns');$d=rl_mail_table('deliveries');
if($wpdb->get_var("SELECT id FROM $s LIMIT 1") || $wpdb->get_var("SELECT id FROM $c LIMIT 1"))throw new RuntimeException('Empty notification tables required.');
function rl_consent_expect($ok,string $m): void {if(!$ok)throw new RuntimeException($m);echo 'PASS: '.$m."\n";}
$messages=[];$capture=static function($pre,$mail)use(&$messages){$messages[]=$mail;return true;};add_filter('pre_wp_mail',$capture,PHP_INT_MAX,2);
$event=wp_insert_post(['post_type'=>'rl_concert','post_status'=>'publish','post_title'=>'Consent fixture']);update_post_meta($event,'rl_start',wp_date('Y-m-d\TH:i',time()-DAY_IN_SECONDS));
$old='Ich möchte per E-Mail über neue Wohnzimmerkonzerte informiert werden. Ich kann mich jederzeit abmelden.';
$mute_admin_signup=static fn()=>false;add_filter('rl_mail_admin_signup_notice_enabled',$mute_admin_signup);
try {
 $wpdb->insert($s,['email'=>'old-consent@example.test','status'=>'confirmed','generation'=>7,'first_name'=>'Anna','consent'=>$old]);$id=(int)$wpdb->insert_id;
 $wpdb->insert($s,['email'=>'new-consent@example.test','status'=>'confirmed','consent'=>rl_mail_consent()]);
 $ann=rl_mail_queue($event,['Ankündigung','Nur neue Termine.'],'','subscribers');$news=rl_mail_queue($event,['Neuigkeiten','Rückblick und Bilder.'],'','subscribers_updates');
 rl_consent_expect(!is_wp_error($ann) && !is_wp_error($news) && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $d WHERE campaign_id=%d",$ann))===2 && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $d WHERE campaign_id=%d",$news))===1,'Old announcement consent is excluded from the extended news audience');
 rl_mail_subscribe('old-consent@example.test','form',['first_name'=>'Unverified overwrite']);$row=rl_mail_subscriber('old-consent@example.test');preg_match('/token=([a-f0-9]{64})/',end($messages)['message'],$match);$token=$match[1]??'';
 rl_consent_expect($token && $row->status==='confirmed' && $row->consent===$old && $row->first_name==='Anna' && (int)$row->generation===7,'Requesting broader consent neither suspends the old subscription nor overwrites its proof or profile');
 $messages=[];rl_mail_batch();$old_news=array_filter($messages,static fn($m)=>$m['to']==='old-consent@example.test' && $m['subject']==='Neuigkeiten');
 rl_consent_expect(!$old_news && count(array_filter($messages,static fn($m)=>$m['to']==='old-consent@example.test' && $m['subject']==='Ankündigung'))===1,'Pending consent expansion cannot receive retrospective news but keeps existing announcement delivery');
 rl_consent_expect(rl_mail_confirm($id,$token) && !rl_mail_confirm($id,$token),'Consent expansion needs a correct single-use confirmation');
 rl_consent_expect(rl_mail_subscriber('old-consent@example.test')->consent===rl_mail_consent(),'Broader consent is recorded only after confirmation');
 $next=rl_mail_queue($event,['Neue Neuigkeiten','Neue Bilder.'],'','subscribers_updates');rl_consent_expect((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $d WHERE campaign_id=%d",$next))===2,'Newly confirmed expansion becomes eligible for future news');
 $wpdb->update($s,['consent'=>$old,'requested_at'=>0],['id'=>$id]);rl_mail_subscribe('old-consent@example.test','form');preg_match('/token=([a-f0-9]{64})/',end($messages)['message'],$match);rl_mail_unsubscribe($id,rl_mail_token($id));
 rl_consent_expect(!rl_mail_confirm($id,$match[1]),'Unsubscribe invalidates an outstanding expansion token');
 $legacy_token=bin2hex(random_bytes(32));$wpdb->insert($s,['email'=>'pending-legacy@example.test','status'=>'pending','consent'=>$old,'token_hash'=>hash('sha256',$legacy_token),'requested_at'=>time()]);$legacy_id=(int)$wpdb->insert_id;
 rl_consent_expect(rl_mail_confirm($legacy_id,$legacy_token) && rl_mail_subscriber('pending-legacy@example.test')->consent===$old,'A pre-update confirmation link preserves its original narrower consent text');
 echo "Consent-scope integration checks passed.\n";
} finally {
    remove_filter('rl_mail_admin_signup_notice_enabled',$mute_admin_signup);remove_filter('pre_wp_mail',$capture,PHP_INT_MAX);$wpdb->query("DELETE FROM $d");$wpdb->query("DELETE FROM $c");$wpdb->query("DELETE FROM $s");wp_delete_post($event,true);wp_clear_scheduled_hook('rl_mail_batch');}
