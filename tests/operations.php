<?php
if (!defined('WP_CLI') || !WP_CLI || !in_array(wp_get_environment_type(),['local','development'],true) || !class_exists('WooCommerce')) throw new RuntimeException('Use an isolated development website.');
global $wpdb;
$s=rl_mail_table('subscribers');$c=rl_mail_table('campaigns');$d=rl_mail_table('deliveries');
if ($wpdb->get_var("SELECT id FROM $c LIMIT 1") || $wpdb->get_var("SELECT id FROM $s LIMIT 1")) throw new RuntimeException('Empty notification tables required.');
function rl_ops_expect($ok,string $message): void { if (!$ok) throw new RuntimeException($message);echo 'PASS: '.$message."\n"; }
$products=[];$events=[];$orders=[];$refunds=[];$messages=[];$original=get_option('rl_operations',null);$original_user=get_current_user_id();
$capture=static function ($pre,$mail) use (&$messages) { $messages[]=$mail;return true; };add_filter('pre_wp_mail',$capture,PHP_INT_MAX,2);
$make=static function ($title) use (&$products,&$events) {
 $p=new WC_Product_Simple();$p->set_name($title);$p->set_status('publish');$p->set_virtual(true);$p->set_regular_price('20');$p->set_manage_stock(true);$p->set_stock_quantity(10);$p->set_backorders('no');$p->save();$products[]=$p;
 $id=wp_insert_post(['post_type'=>'rl_concert','post_title'=>$title,'post_status'=>'publish']);$events[]=$id;update_post_meta($id,'rl_start',wp_date('Y-m-d\TH:i',time()+5*DAY_IN_SECONDS));update_post_meta($id,'rl_doors','19:00');update_post_meta($id,'rl_venue','Öffentliche Ortsangabe');update_post_meta($id,'rl_private_address','GEHEIM: private Wohnzimmeradresse');update_post_meta($id,'rl_product_id',$p->get_id());return [$id,$p];
};
ob_start();
try {
 // Upgrade a v3 queue containing a real historical delivery without losing its status.
 $wpdb->query("ALTER TABLE $d DROP INDEX campaign_recipient, DROP INDEX subscriber, DROP COLUMN recipient_key, DROP COLUMN email, DROP COLUMN first_name, DROP COLUMN last_name, DROP COLUMN order_id, ADD UNIQUE KEY recipient (campaign_id,subscriber_id)");
 $wpdb->query("ALTER TABLE $c DROP COLUMN audience, DROP COLUMN order_scope");
 $wpdb->insert($s,['email'=>'legacy-ops@example.test','status'=>'confirmed','consent'=>'Original consent']);$legacy_sub=(int)$wpdb->insert_id;
 $wpdb->insert($c,['concert_id'=>999,'subject'=>'Legacy job','body'=>'Retained','created_at'=>time(),'created_by'=>0]);$legacy_job=(int)$wpdb->insert_id;
 $wpdb->insert($d,['campaign_id'=>$legacy_job,'subscriber_id'=>$legacy_sub,'generation'=>1,'status'=>'failed','attempts'=>3]);$legacy_delivery=(int)$wpdb->insert_id;
 update_option('rl_mail_schema','3');rl_mail_install();rl_mail_install();
 rl_ops_expect($wpdb->get_var($wpdb->prepare("SELECT status FROM $d WHERE id=%d",$legacy_delivery))==='failed' && $wpdb->get_var($wpdb->prepare("SELECT audience FROM $c WHERE id=%d",$legacy_job))==='subscribers','Queue migration retains legacy recipients, campaigns and delivery status');
 $wpdb->delete($d,['id'=>$legacy_delivery]);$wpdb->delete($c,['id'=>$legacy_job]);$wpdb->delete($s,['id'=>$legacy_sub]);
 [$retired,$retired_product]=$make('Retirement diagnostic');update_post_meta($retired,'rl_start',wp_date('Y-m-d\TH:i',time()-DAY_IN_SECONDS));
 rl_ops_expect(!$retired_product->is_purchasable(),'Past ticket starts closed');wp_trash_post($retired);
 rl_ops_expect(!wc_get_product($retired_product->get_id())->is_purchasable(),'Trashing a concert cannot reopen its ticket');wp_delete_post($retired,true);
 rl_ops_expect(!wc_get_product($retired_product->get_id())->is_purchasable(),'Permanent concert deletion keeps the ticket closed');
 [$e,$p]=$make('Abend A');[$other,$p2]=$make('Abend B');
 require_once WC_ABSPATH.'includes/admin/class-wc-admin-duplicate-product.php';$duplicate=(new WC_Admin_Duplicate_Product())->product_duplicate($p);$products[]=$duplicate;
 rl_ops_expect(!get_post_meta($duplicate->get_id(),'_rl_ticket_concert',true) && rl_product_can_link($duplicate->get_id(),$other),'Duplicating a product creates a reusable new ticket template without reopening the original');
 rl_ops_expect(!rl_product_can_link($p->get_id(),$other),'Previously assigned ticket cannot silently change concert');
 $order=wc_create_order();$orders[]=$order;$order->set_billing_email('buyer@example.test');$order->set_billing_first_name('Anna');$order->set_payment_method('bacs');$item_id=$order->add_product($p,2);$item2=$order->add_product($p2,1);$order->calculate_totals();$order->save();
 $item=$order->get_item($item_id);
 rl_ops_expect(rl_item_event($item)===$e && $item->get_meta('Konzertbeginn') && !$item->get_meta('_rl_private_address'),'Order positions capture public concert details, never the private address before payment');
 $snapshot=$item->get_meta('_rl_start');update_post_meta($e,'rl_start',wp_date('Y-m-d\TH:i',time()+6*DAY_IN_SECONDS));
 rl_ops_expect($order->get_item($item_id)->get_meta('_rl_start')===$snapshot,'Later concert edits preserve the original order snapshot');
 $order->update_status('on-hold');$order=wc_get_order($order->get_id());
 rl_ops_expect((int)$order->get_meta('_rl_payment_deadline')>time() && count(rl_booking_recipients($e,'buyers_open'))===1 && !rl_booking_recipients($e,'buyers_paid'),'Bank-transfer orders get a deadline and remain separate from paid guests');
 $wpdb->insert($s,['email'=>'unrelated@example.test','status'=>'confirmed','consent'=>'Fixture']);
 $newsletter=rl_mail_queue($e,['Newsletter','Information']);$booking=rl_mail_queue($e,['Offene Buchung','Information'],'','buyers_open');
 rl_ops_expect(!is_wp_error($newsletter) && !is_wp_error($booking),'Newsletter and booking audiences can be queued separately');
 rl_ops_expect(str_contains(wp_json_encode(rl_mail_privacy_export('buyer@example.test')),'buyer@example.test'),'Booking-mail privacy export works without a newsletter subscription');
 $buyer_email=$wpdb->get_var($wpdb->prepare("SELECT email FROM $d WHERE campaign_id=%d",$booking));
 rl_ops_expect($buyer_email==='buyer@example.test' && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $d WHERE campaign_id=%d",$booking))===1,'Booking messages target buyers, not unrelated subscribers');
 rl_ops_expect(is_wp_error(rl_mail_queue($e,['Secret','{Adresse}'],'','buyers_open')) && is_wp_error(rl_mail_queue($e,['Secret','{Adresse}'])),'Private address is rejected for open bookings and newsletter recipients');
 update_post_meta($e,'rl_cancelled','1');
 rl_ops_expect(is_wp_error(rl_mail_queue($e,['News','Cancelled'])) && !is_wp_error(rl_mail_queue($e,['Absage','Wir müssen absagen.'],'','buyers_all')),'Cancelled events remain usable for targeted booking information');delete_post_meta($e,'rl_cancelled');
 $order->payment_complete('isolated-test-no-payment');$order=wc_get_order($order->get_id());
 rl_ops_expect(count(rl_booking_recipients($e,'buyers_paid'))===1 && $order->get_item($item_id)->get_meta('_rl_private_address'),'Paid bookings retain their private address snapshot');
 $auto=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $c WHERE order_scope=%d",$order->get_id()));rl_booking_information($order->get_id());
 rl_ops_expect($auto===2 && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $c WHERE order_scope=%d",$order->get_id()))===2,'Each paid concert queues one booking-info job; repeated status hooks cannot duplicate it');
 $wpdb->insert($s,['email'=>'buyer@example.test','status'=>'unsubscribed','consent'=>'Old consent']);
 $personal=rl_mail_queue($e,['Buchung {Bestellnummer}','<p>{Anrede}</p><p>{Tickets} Plätze – {Adresse}</p>','html',''],'','buyers_paid');$messages=[];update_option('rl_operations',['mail_paused'=>true]);rl_mail_batch();rl_ops_expect(!$messages,'Paused queue sends no newsletter or booking messages');update_option('rl_operations',['mail_paused'=>false]);rl_mail_batch();
 $buyer_mails=array_values(array_filter($messages,static fn($m)=>$m['to']==='buyer@example.test'));
 rl_ops_expect(count($buyer_mails)>=2 && count(array_filter($buyer_mails,static fn($m)=>str_contains($m['message'],'GEHEIM:')))>0,'Paid buyers receive their information despite a newsletter unsubscribe');
 rl_ops_expect(!str_contains(end($buyer_mails)['message'],'Konzertbenachrichtigungen abmelden'),'Transactional booking information has no misleading newsletter unsubscribe footer');
 $refund=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>20,'reason'=>'Partial concert refund fixture','line_items'=>[$item_id=>['qty'=>1,'refund_total'=>20]],'refund_payment'=>false,'restock_items'=>true]);if(is_wp_error($refund)) throw new RuntimeException($refund->get_error_message());$refunds[]=$refund;$order=wc_get_order($order->get_id());
 rl_ops_expect(rl_item_remaining($order,$order->get_item($item_id))===1 && rl_item_remaining($order,$order->get_item($item2))===1,'Partial refund adjusts only the selected concert position');
 rl_ops_expect(rl_checkin($order->get_id(),$item_id,$e,1) && !rl_checkin($order->get_id(),$item_id,$e,2) && !rl_checkin($order->get_id(),$item_id,$other,1),'Check-in respects paid remaining quantity and concert ownership');
 $rows=rl_guest_rows($e);rl_ops_expect(count($rows)===1 && $rows[0]['checked']===1,'Guest list includes checked-in quantities');
 $ics=rl_calendar_content($e);rl_ops_expect(str_contains($ics,'BEGIN:VCALENDAR') && str_contains($ics,'Öffentliche Ortsangabe') && !str_contains($ics,'GEHEIM:'),'Calendar downloads contain public details only');
 rl_ops_expect(rl_guest_csv_cell('=HYPERLINK("bad")')[0]==="'",'Guest CSV neutralizes spreadsheet formulas');
 $held=wc_create_order();$orders[]=$held;$held->set_billing_email('late@example.test');$held->set_payment_method('bacs');$held->add_product($p,1);$held->calculate_totals();$held->update_status('on-hold');$held->update_meta_data('_rl_payment_deadline',time()-1);$held->save_meta_data();
 update_option('rl_operations',['auto_cancel'=>false,'transfer_days'=>3]);rl_ops_tick();$reminders=count(array_filter($messages,static fn($m)=>str_starts_with($m['subject'],'Zahlungserinnerung')));rl_ops_tick();rl_ops_expect(count(array_filter($messages,static fn($m)=>str_starts_with($m['subject'],'Zahlungserinnerung')))===$reminders && $reminders>0,'Payment reminder is handed off once per overdue reservation');rl_ops_expect(wc_get_order($held->get_id())->get_status()==='on-hold','Automatic expiry remains off unless explicitly enabled');
 update_option('rl_operations',['auto_cancel'=>true,'transfer_days'=>3]);rl_ops_tick();rl_ops_expect(wc_get_order($held->get_id())->get_status()==='cancelled','Enabled expiry cancels overdue bank-transfer reservations'.(wc_get_order($held->get_id())->get_status()==='cancelled' ? '' : ': '.wp_json_encode(['id'=>$held->get_id(),'status'=>wc_get_order($held->get_id())->get_status(),'due'=>wc_get_order($held->get_id())->get_meta('_rl_payment_deadline'),'options'=>rl_ops_options(),'eligible'=>rl_mail_concert_eligible($e),'query'=>wc_get_orders(['status'=>'on-hold','limit'=>-1,'return'=>'ids'])])));
 $export=rl_mail_privacy_export('buyer@example.test');rl_ops_expect(count($export['data'])>1,'Privacy export includes booking-message records');
 wp_set_current_user(0);rl_ops_expect(!rl_ops_allowed(),'Anonymous users cannot access concert operations');
 echo "Concert operations integration checks passed; no external payments or mail.\n";
} finally {
 wp_set_current_user($original_user);remove_filter('pre_wp_mail',$capture,PHP_INT_MAX);
 foreach ($refunds as $refund) $refund->delete(true);foreach ($orders as $order) $order->delete(true);foreach ($events as $id) wp_delete_post($id,true);foreach ($products as $product) $product->delete(true);
 $wpdb->query("DELETE FROM $d");$wpdb->query("DELETE FROM $c");$wpdb->query("DELETE FROM $s");wp_clear_scheduled_hook('rl_mail_batch');
 if ($original===null)delete_option('rl_operations');else update_option('rl_operations',$original);
 ob_end_flush();
}
