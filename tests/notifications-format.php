<?php
// Real MIME construction with an in-memory transport: no SMTP, sendmail or external delivery.
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(), ['local','development'], true) || !function_exists('rl_mail_render')) throw new RuntimeException('Use the disposable integration website.');
global $wpdb, $phpmailer;
$s=rl_mail_table('subscribers'); $c=rl_mail_table('campaigns'); $d=rl_mail_table('deliveries');
if ((int)$wpdb->get_var("SELECT COUNT(*) FROM $s") || (int)$wpdb->get_var("SELECT COUNT(*) FROM $c")) throw new RuntimeException('Notification tables must be empty.');
function rl_format_expect($ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
require_once ABSPATH . WPINC . '/class-wp-phpmailer.php';
class RL_Test_Mime_Mailer extends WP_PHPMailer {
    public array $captured=[];
    public bool $fail=false;
    public function postSend() {
        $this->captured[]=['mime'=>$this->getSentMIMEMessage(),'subject'=>$this->Subject,'html'=>$this->Body,'text'=>$this->AltBody,'from'=>$this->From,'name'=>$this->FromName,'to'=>$this->getToAddresses()];
        if ($this->fail) throw new PHPMailer\PHPMailer\Exception('Simulated transport failure');
        return true;
    }
}
$original_mailer=$phpmailer ?? null; $phpmailer=new RL_Test_Mime_Mailer(true); $fake=$phpmailer;
$sender_before=get_option('rl_mail_sender', null);
$force_transport=static fn($pre)=>null;
add_filter('pre_wp_mail',$force_transport,PHP_INT_MAX);
$baseline_from=static fn($from)=>'wordpress@network.example.test';
add_filter('wp_mail_from',$baseline_from,5);
$profile_order=null;
$event=wp_insert_post(['post_type'=>'rl_concert','post_status'=>'publish','post_title'=>'Musik & Freunde']);
update_post_meta($event,'rl_start',wp_date('Y-m-d\TH:i',time()-DAY_IN_SECONDS));
ob_start();
try {
    // Upgrade the exact previous schema with real retained data.
    $wpdb->query("ALTER TABLE $s DROP COLUMN first_name, DROP COLUMN last_name");
    $wpdb->query("ALTER TABLE $c DROP COLUMN body_format, DROP COLUMN text_body");
    $wpdb->insert($s,['email'=>'legacy@example.test','status'=>'confirmed','consent'=>'Retained consent']); $legacy=(int)$wpdb->insert_id;
    $wpdb->insert($c,['concert_id'=>$event,'subject'=>'Legacy','body'=>'Plain legacy message','created_at'=>time(),'created_by'=>0]); $old=(int)$wpdb->insert_id;
    update_option('rl_mail_schema','2'); rl_mail_install(); rl_mail_install();
    $campaign=$wpdb->get_row($wpdb->prepare("SELECT * FROM $c WHERE id=%d",$old));
    rl_format_expect($campaign->body_format === 'text' && $campaign->body === 'Plain legacy message' && rl_mail_subscriber('legacy@example.test')->first_name === '', 'Migration preserves existing text campaigns and recipients');
    $wpdb->delete($s,['id'=>$legacy]); $wpdb->delete($c,['id'=>$old]);
    update_option('rl_mail_sender',['name'=>'Rommelfangen Konzerte','email'=>'konzerte@example.test']);
    rl_mail_subscribe('anna@example.test','test',['first_name'=>'Anna','last_name'=>'Muster']);
    $anna=rl_mail_subscriber('anna@example.test');
    rl_format_expect($anna->first_name === 'Anna' && $anna->last_name === 'Muster', 'Optional subscriber names are stored for personalization');
    $doi=end($fake->captured);
    rl_format_expect(str_contains($doi['mime'],'multipart/alternative') && str_contains($doi['mime'],'text/plain') && str_contains($doi['mime'],'text/html') && str_contains($doi['text'],'48 Stunden'), 'Confirmation mails contain actual HTML and text MIME parts');
    rl_format_expect($doi['from'] === 'konzerte@example.test' && $doi['name'] === 'Rommelfangen Konzerte', 'Confirmation mail uses configured sender address and display name');
    preg_match('/token=([a-f0-9]{64})/',$doi['text'],$match); rl_mail_confirm((int)$anna->id,$match[1]);
    rl_mail_subscribe('guest@example.test','test'); $guest=rl_mail_subscriber('guest@example.test');
    preg_match('/token=([a-f0-9]{64})/',end($fake->captured)['text'],$match); rl_mail_confirm((int)$guest->id,$match[1]);
    $template=['{Anrede} {Konzert}', '<p>{Anrede}</p><p>Für {Name} ({Email}) · {Datum} {Uhrzeit}</p><p><a href="{Konzertlink}">Konzert ansehen</a></p><img src="https://example.test/foto%20eins.jpg" alt="Bühne & Musik"><p><a href="{Abmeldelink}">Abmelden</a></p>', 'html', ''];
    $job=rl_mail_queue($event,$template);
    rl_format_expect(!is_wp_error($job),'HTML template can be queued for a past concert');
    $before=count($fake->captured); rl_mail_batch();
    $sent=array_slice($fake->captured,$before);
    rl_format_expect(count($sent) === 2,'Each recipient receives an individual MIME message');
    $a=$sent[0]; $g=$sent[1];
    rl_format_expect(str_contains($a['html'],'Hallo Anna,') && str_contains($a['html'],'Anna Muster') && str_contains($a['html'],'anna@example.test') && !str_contains($g['html'],'Anna Muster') && str_contains($g['html'],'Hallo,') && str_contains($g['html'],'Musikfan'), 'Personalization and neutral fallbacks are applied separately per recipient');
    rl_format_expect(str_contains($a['text'],'Konzert ansehen ('.get_permalink($event).')') && str_contains($a['text'],'Bühne & Musik (https://example.test/foto%20eins.jpg)') && !str_contains($a['text'],'<img'), 'Automatic text alternative preserves link targets and image descriptions');
    rl_format_expect(str_contains($a['mime'],'multipart/alternative') && str_contains($a['mime'],'Content-Type: text/html') && str_contains($a['mime'],'Content-Type: text/plain'), 'Announcement is encoded as multipart/alternative');
    rl_format_expect(str_contains($a['html'],rl_mail_token((int)$anna->id)) && str_contains($a['text'],rl_mail_token((int)$anna->id)) && !str_contains($g['html'],rl_mail_token((int)$anna->id)), 'Both alternatives contain only the current recipient unsubscribe link');
    rl_format_expect($a['from'] === 'konzerte@example.test' && $a['name'] === 'Rommelfangen Konzerte','Campaign mail uses site-specific sender settings');
    $custom=rl_mail_queue($event,['Für {Name}','<p>HTML für {Vorname}</p>','html','Eigener Text für {Name}: {Konzertlink}']); rl_mail_batch();
    $last=end($fake->captured);
    rl_format_expect(str_contains($last['text'],'Eigener Text für Musikfan: '.get_permalink($event)) && !str_contains($last['text'],'<p>'), 'Custom text alternative also expands placeholders');
    rl_format_expect($fake->AltBody === '', 'HTML alternative is removed from the shared mailer after each send');
    wp_mail('ordinary@example.test','Unrelated WordPress mail','Ordinary body'); $ordinary=end($fake->captured);
    rl_format_expect($ordinary['from'] !== 'konzerte@example.test' && $ordinary['name'] !== 'Rommelfangen Konzerte' && $ordinary['text'] === '' && !str_contains($ordinary['mime'],'multipart/alternative'), 'Unrelated WordPress mail retains its sender and has no leaked alternative');
    $clean=rl_mail_message(['subject'=>'Safe','body_format'=>'html','body'=>'<script>alert(1)</script><p onclick="alert(2)">Text <a href="javascript:alert(3)">Link</a><img src="https://example.test/a.jpg" onerror="alert(4)"></p>']);
    rl_format_expect(!str_contains($clean[1],'<script') && !str_contains($clean[1],'onclick') && !str_contains($clean[1],'onerror') && !str_contains($clean[1],'javascript:'), 'HTML sanitization removes scripts, event handlers and unsafe URL protocols');
    $values=rl_mail_values($anna,$event); $values['{Name}']='"><img src=x onerror=alert(1)>'; $values['{Konzertlink}']='javascript:alert(2)';
    $rendered=rl_mail_render((object)['subject'=>'{Name}','body'=>'<p>{Name}</p><a href="{Konzertlink}">Link</a>','body_format'=>'html','text_body'=>''], $values);
    rl_format_expect(!str_contains($rendered['html'],'<img src=x') && !str_contains($rendered['html'],'javascript:'), 'Placeholder values cannot inject markup or unsafe link destinations');
    $normalized=rl_mail_html('<a href="http://%7BKonzertlink%7D/">Konzert</a>');
    rl_format_expect(str_contains($normalized, 'href="{Konzertlink}"'), 'Link placeholders survive URL correction and brace encoding in the WordPress editor');
    $preview=['concert'=>(string)$event,'subject'=>wp_slash($template[0]),'body'=>wp_slash($template[1]),'body_format'=>'html','text_body'=>'Plain {Name}','request_key'=>bin2hex(random_bytes(32))];
    $content=rl_mail_message($preview); $preview['preview']=rl_mail_preview_signature($event,$content,$preview['request_key']);
    $modified=$preview; $modified['text_body']='Changed';
    rl_format_expect(is_wp_error(rl_mail_queue_preview($modified)), 'Preview signature protects HTML format and text alternative');
    $export=rl_mail_privacy_export('anna@example.test');
    rl_format_expect(str_contains(wp_json_encode($export),'Anna') && str_contains(wp_json_encode($export),'Muster'), 'Privacy export includes stored subscriber names');
    $invalid=rl_mail_sender_settings(['name'=>'New name','email'=>'not-an-address']);
    rl_format_expect($invalid['email'] === 'konzerte@example.test', 'Invalid sender address preserves the previous valid setting');
    $fake->fail=true; rl_format_expect(!rl_mail_send('anna@example.test','Failure','<p>HTML</p>','Alternative'),'Transport failures are reported without external delivery'); $fake->fail=false;
    rl_format_expect($fake->AltBody === '', 'Failed HTML sends also restore the shared mailer state');
    wp_mail('ordinary@example.test','After failure','Ordinary body');
    rl_format_expect(end($fake->captured)['from'] !== 'konzerte@example.test', 'Sender filters are removed after a failed send');
    $profile_order=wc_create_order();
    $profile_order->set_billing_email('previous-buyer@example.test'); $profile_order->set_billing_first_name('Bea'); $profile_order->set_billing_last_name('Alt'); $profile_order->set_status('completed'); $profile_order->save();
    $older=rl_mail_values((object)['id'=>0,'email'=>'previous-buyer@example.test','first_name'=>'','last_name'=>''],$event);
    rl_format_expect($older['{Name}'] === 'Bea Alt', 'Older guest subscribers can reuse names from their paid orders on this website');
    rl_mail_subscribe('anna@example.test','form',['first_name'=>'Forged','last_name'=>'Profile']);
    rl_format_expect(rl_mail_subscriber('anna@example.test')->first_name === 'Anna', 'Anonymous repeat signup cannot overwrite a confirmed recipient profile');
    echo "HTML, text alternative, personalization and sender checks passed; no external mail sent.\n";
} finally {
    remove_filter('pre_wp_mail',$force_transport,PHP_INT_MAX);
    remove_filter('wp_mail_from',$baseline_from,5);
    $phpmailer=$original_mailer;
    if ($sender_before === null) delete_option('rl_mail_sender'); else update_option('rl_mail_sender',$sender_before);
    $wpdb->query("DELETE FROM $d"); $wpdb->query("DELETE FROM $c"); $wpdb->query("DELETE FROM $s");
    if ($profile_order) $profile_order->delete(true);
    wp_delete_post($event,true); wp_clear_scheduled_hook('rl_mail_batch');
    ob_end_flush();
}
