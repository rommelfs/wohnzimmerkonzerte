<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true)) throw new RuntimeException('Disposable test website required.');
global $wpdb;$s=rl_mail_table('subscribers');
if ($wpdb->get_var("SELECT id FROM $s LIMIT 1")) throw new RuntimeException('Empty subscriber table required.');
function rl_registration_expect($ok,string $label): void { if (!$ok) throw new RuntimeException($label);echo 'PASS: '.$label."\n"; }
class RL_Registration_Response extends RuntimeException {}
$die=static fn()=>static function($message){throw new RL_Registration_Response($message);};add_filter('wp_die_handler',$die);
$options=get_option('rl_mail_registration',null);$original_request=$_REQUEST;$method=$_SERVER['REQUEST_METHOD']??null;$messages=[];$accept=true;
$capture=static function($pre,$mail)use(&$messages,&$accept){$messages[]=$mail;return $accept;};add_filter('pre_wp_mail',$capture,PHP_INT_MAX,2);
$subscribe=static function($email)use(&$messages){rl_mail_subscribe($email,'form',['first_name'=>'Test']);$row=rl_mail_subscriber($email);preg_match('/token=([a-f0-9]{64})/',end($messages)['message'],$m);return [$row,$m[1]??''];};
ob_start();
try {
 update_option('rl_mail_registration',['notify_admin'=>false,'admin_email'=>'admin-registration@example.test']);
 // Simulate the exact v5 recipient structure with an unexpired previously delivered link.
 foreach(['token_created_at','reminded_at','confirmation_status','admin_notice_status'] as $column)$wpdb->query("ALTER TABLE $s DROP COLUMN $column");
 $old_consent='Nur neue Wohnzimmerkonzerte; keine Rückblicke.';$legacy_token=bin2hex(random_bytes(32));$when=time()-25*HOUR_IN_SECONDS;
 $wpdb->insert($s,['email'=>'legacy-registration@example.test','status'=>'pending','generation'=>9,'token_hash'=>hash('sha256',$legacy_token),'requested_at'=>$when,'consent'=>$old_consent,'first_name'=>'Altbestand','source'=>'form']);$legacy=(int)$wpdb->insert_id;
 update_option('rl_mail_schema','5');rl_mail_install();rl_mail_install();$row=rl_mail_subscriber('legacy-registration@example.test');
 rl_registration_expect(get_option('rl_mail_schema')===RL_MAIL_SCHEMA && $row->token_hash===hash('sha256',$legacy_token) && $row->status==='pending' && (int)$row->token_created_at===0 && !$messages,'Schema upgrade preserves pending signups and old links without sending or confirming');
 $_REQUEST=['mode'=>'confirm','id'=>(string)$legacy,'token'=>$legacy_token];$_SERVER['REQUEST_METHOD']='HEAD';try{rl_mail_link();}catch(RL_Registration_Response $response){}
 rl_registration_expect(rl_mail_subscriber($row->email)->status==='pending','HEAD checks do not consume the confirmation link');
 $_SERVER['REQUEST_METHOD']='GET';try{rl_mail_link();}catch(RL_Registration_Response $response){rl_registration_expect(str_contains($response->getMessage(),'Du bist angemeldet!')&&!str_contains($response->getMessage(),'method="post"'),'Previously sent links now complete signup with one click');}
 rl_registration_expect(rl_mail_subscriber($row->email)->consent===$old_consent && !rl_mail_confirm($legacy,$legacy_token),'One-click confirmation keeps the old consent scope and remains single-use');
 [$pending,$old_token]=$subscribe('reminder-registration@example.test');$sent=count($messages);
 rl_registration_expect(is_wp_error(rl_mail_remind((int)$pending->id)) && count($messages)===$sent,'Fresh signups are not reminded before 24 hours');
 $wpdb->update($s,['requested_at'=>$when,'consent'=>$old_consent,'pending_consent'=>''],['id'=>$pending->id]);$pending=rl_mail_subscriber($pending->email);
 rl_registration_expect(rl_mail_remind((int)$pending->id)===true,'Admin can request one reminder for a recent pending signup');
 $reminded=rl_mail_subscriber($pending->email);$mail=end($messages);preg_match('/token=([a-f0-9]{64})/',$mail['message'],$m);$fresh=$m[1];
 rl_registration_expect($reminded->status==='pending' && (int)$reminded->requested_at===$when && $reminded->first_name==='Test' && $reminded->source==='form' && $reminded->consent===$old_consent && str_contains($mail['message'],$old_consent),'Reminder preserves request proof, profile and exact consent; it does not activate the signup');
 rl_registration_expect(!rl_mail_confirm((int)$pending->id,$old_token) && $fresh!==$old_token && (int)$reminded->token_created_at>=time()-5,'Reminder replaces the old link with a fresh expiring token');
 $count=count($messages);rl_registration_expect(is_wp_error(rl_mail_remind((int)$pending->id)) && count($messages)===$count,'Duplicate reminder requests do not send another message');
 $wpdb->update($s,['token_created_at'=>time()-3*DAY_IN_SECONDS],['id'=>$pending->id]);rl_registration_expect(!rl_mail_confirm((int)$pending->id,$fresh),'Reminder links expire after 48 hours');
 [$old,$token]=$subscribe('too-old-registration@example.test');$wpdb->update($s,['requested_at'=>time()-6*DAY_IN_SECONDS],['id'=>$old->id]);rl_registration_expect(is_wp_error(rl_mail_remind((int)$old->id)),'Old pending signups are excluded from reminders');
 [$unsub,$token]=$subscribe('unsub-registration@example.test');rl_mail_unsubscribe((int)$unsub->id,rl_mail_token((int)$unsub->id));rl_registration_expect(is_wp_error(rl_mail_remind((int)$unsub->id)),'Unsubscribed recipients cannot be reminded');
 update_option('rl_mail_registration',['notify_admin'=>true,'admin_email'=>'admin-registration@example.test']);
 [$new,$token]=$subscribe('notice-registration@example.test');$before=count($messages);rl_registration_expect(rl_mail_confirm((int)$new->id,$token),'A new signup can be confirmed with admin notifications enabled');
 $notice=end($messages);rl_registration_expect(count($messages)===$before+1 && $notice['to']==='admin-registration@example.test' && str_contains($notice['message'],$new->email),'Confirmed signup sends one notice to the configured administrator');
 $before=count($messages);rl_mail_confirm((int)$new->id,$token);rl_mail_admin_signup_notice((int)$new->id);rl_registration_expect(count($messages)===$before,'Repeated links and repeated admin hooks do not duplicate the notice');
 [$failure,$token]=$subscribe('admin-failure-registration@example.test');$accept=false;rl_registration_expect(rl_mail_confirm((int)$failure->id,$token) && rl_mail_subscriber($failure->email)->status==='confirmed' && rl_mail_subscriber($failure->email)->admin_notice_status==='failed','Admin mail failure is visible but never reverses the completed customer signup');$accept=true;
 update_option('rl_mail_registration',['notify_admin'=>false]);[$disabled,$token]=$subscribe('disabled-registration@example.test');$before=count($messages);rl_mail_confirm((int)$disabled->id,$token);rl_registration_expect(count($messages)===$before,'Administrator notifications can be disabled');
 $export=rl_mail_privacy_export($new->email);rl_registration_expect(str_contains(wp_json_encode($export),'admin_notice_status')===false && str_contains(wp_json_encode($export),'Admin-Benachrichtigung'),'Privacy export includes confirmation and administrator delivery records without tokens');
 echo "Registration, reminders and administrator notice checks passed; no external emails.\n";
} finally {
 remove_filter('wp_die_handler',$die);remove_filter('pre_wp_mail',$capture,PHP_INT_MAX,2);$_REQUEST=$original_request;if($method===null)unset($_SERVER['REQUEST_METHOD']);else $_SERVER['REQUEST_METHOD']=$method;
 if($options===null)delete_option('rl_mail_registration');else update_option('rl_mail_registration',$options);$wpdb->query("DELETE FROM $s");ob_end_flush();
}
