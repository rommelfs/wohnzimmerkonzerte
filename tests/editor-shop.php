<?php
// Isolated WordPress/WooCommerce test installation only; creates and removes its own fixtures.
if (!defined('ABSPATH') || !function_exists('rl_sanitize_site') || !class_exists('WooCommerce')) throw new RuntimeException('Activate plugin and WooCommerce first.');
if (!in_array(wp_get_environment_type(), ['local','development'], true)) throw new RuntimeException('Disposable local test installation required.');
$previous_cart_page=get_option('woocommerce_cart_page_id',null);$page_ids=[];$previous_query=$GLOBALS['wp_query'] ?? null;
$original=get_option('rl_site',null); $ids=[]; $products=[]; $attachments=[]; $files=[]; $order=null;
$mail=static fn()=>true;add_filter('pre_wp_mail',$mail);
function rl_check_editor($ok,string $message): void { if (!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
try {
    $config=rl_site_defaults();
    $config['brand']='<script>bad()</script>Unsere Konzerte';
    $config['colors']['accent']='red; background:url(https://bad.example)';
    $config['width']=99999;$config['radius']=-10;
    $config['navigation']=[['label'=>'Uns','url'=>'javascript:alert(1)','enabled'=>true]];
    $a=rl_section_defaults('about','team');$a['title']='Unser Team';$a['body']='<p>Persönlich und nah</p><script>bad()</script>';
    $b=rl_section_defaults('text','team');$b['title']='Zweiter Bereich';$b['enabled']=false;
    $config['sections']=[$a,$b];
    $clean=rl_sanitize_site(wp_json_encode($config));
    rl_check_editor(!str_contains($clean['brand'],'<script>') && !str_contains($clean['sections'][0]['body'],'<script>'),'Backend text strips executable HTML');
    rl_check_editor($clean['colors']['accent']==='#f5ac7b' && $clean['navigation'][0]['url']==='','Unsafe CSS and navigation schemes rejected');
    rl_check_editor($clean['width']===1600 && $clean['radius']===0 && $clean['sections'][1]['id']==='team-2','Layout values bounded and section anchors unique');
    update_option('rl_site',$clean);$html=rl_render_home();
    rl_check_editor(str_contains($html,'Unser Team') && !str_contains($html,'Zweiter Bereich'),'Custom about section renders and disabled section stays hidden');
    $config=rl_site_defaults();$config['sections']=[$a,rl_section_defaults('text','intro')];
    $config['sections'][1]['title']='Begrüßung';
    update_option('rl_site',rl_sanitize_site($config));$html=rl_render_home();
    rl_check_editor(strpos($html,'Unser Team')<strpos($html,'Begrüßung'),'Backend section order determines page order');
    $config['sections']=array_reverse($config['sections']);
    update_option('rl_site',rl_sanitize_site($config));$html=rl_render_home();
    rl_check_editor(strpos($html,'Begrüßung')<strpos($html,'Unser Team'),'Reordered sections keep their content');

    $cart_page=wp_insert_post(['post_type'=>'page','post_title'=>'RL test cart','post_status'=>'publish','post_content'=>'[woocommerce_cart]']);$page_ids[]=$cart_page;
    update_option('woocommerce_cart_page_id',$cart_page);
    $GLOBALS['wp_query']=new WP_Query(['page_id'=>$cart_page]);
    // WooCommerce caches its page conditional per request; use its public override for this in-process query fixture.
    add_filter('woocommerce_is_cart','__return_true');
    rl_check_editor(apply_filters('template_include','normal.php')===RL_PLUGIN_DIR.'templates/commerce-page.php','Cart uses configurable concert design shell');
    $saved_config=rl_site();$saved_config['commerce_shell']=false;update_option('rl_site',$saved_config);
    rl_check_editor(apply_filters('template_include','normal.php')==='normal.php','Cart can retain the existing theme when requested');
    $saved_config['commerce_shell']=true;update_option('rl_site',$saved_config);$GLOBALS['wp_query']=$previous_query;remove_filter('woocommerce_is_cart','__return_true');

    foreach (['upcoming','past'] as $label) {
        $upload=wp_upload_bits('rl-test-'.$label.'-'.wp_generate_uuid4().'.png',null,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6gAAAABJRU5ErkJggg=='));
        if ($upload['error']) throw new RuntimeException($upload['error']);
        $files[]=$upload['file'];
        $attachment=wp_insert_attachment(['post_title'=>'RL test '.$label,'post_mime_type'=>'image/png','post_status'=>'inherit'],$upload['file']);
        $attachments[$label]=$attachment;
    }
    foreach ([['Ticket A',2*HOUR_IN_SECONDS],['Ticket B',3*HOUR_IN_SECONDS]] as [$title,$delay]) {
        $product=new WC_Product_Simple();$product->set_name($title);$product->set_status('publish');$product->set_virtual(true);
        $product->set_regular_price('20');$product->set_manage_stock(true);$product->set_stock_quantity(3);$product->set_backorders('no');$product->save();$products[]=$product;
        $id=wp_insert_post(['post_type'=>'rl_concert','post_title'=>$title,'post_status'=>'publish']);$ids[]=$id;
        update_post_meta($id,'rl_start',wp_date('Y-m-d\TH:i',time()+$delay));update_post_meta($id,'rl_product_id',$product->get_id());
    }
    $style=rl_sanitize_concert_design(['upcoming'=>['background'=>'#123456','image_id'=>$attachments['upcoming'],'layout'=>'stacked'],'past'=>['background'=>'#654321','image_id'=>$attachments['past'],'layout'=>'image-first']]);
    update_post_meta($ids[0],'rl_design',$style);
    ob_start();rl_image($ids[0]);$html=ob_get_clean();
    rl_check_editor(rl_concert_style($ids[0])['background']==='#123456' && str_contains($html,'rl-test-upcoming'),'Upcoming concert uses its own design and image');
    update_post_meta($ids[0],'rl_start',wp_date('Y-m-d\TH:i',time()-DAY_IN_SECONDS));
    ob_start();rl_image($ids[0]);$html=ob_get_clean();
    rl_check_editor(rl_concert_style($ids[0])['background']==='#654321' && str_contains($html,'rl-test-past'),'Past concert automatically uses separate design and image');
    update_post_meta($ids[0],'rl_start',wp_date('Y-m-d\TH:i',time()+2*HOUR_IN_SECONDS));
    $events=rl_shop_events();
    rl_check_editor(rl_shop_selection($events)['id']===$ids[0] && rl_shop_selection($events,$ids[1])['id']===$ids[1],'Ticket shop defaults to next concert and accepts explicit concert selection');
    $_GET['concert']=$ids[1];$html=rl_render_shop();
    rl_check_editor(preg_match('/<option value="'.$ids[1].'"[^>]*selected/', $html) && str_contains($html,'name="add-to-cart" value="'.$products[1]->get_id().'"'),'Shop renders selected concert and correct purchasable product');
    $nonce=wp_create_nonce('rl_add_ticket');
    if (!WC()->cart) wc_load_cart();
    WC()->cart->empty_cart();
    $_POST=['rl_ticket_origin'=>'1','rl_ticket_nonce'=>'invalid'];
    rl_check_editor(!apply_filters('woocommerce_add_to_cart_validation',true,$products[0]->get_id(),1),'Ticket form rejects invalid nonce');wc_clear_notices();
    $_POST=['rl_ticket_origin'=>'1','rl_ticket_nonce'=>$nonce];
    rl_check_editor(apply_filters('woocommerce_add_to_cart_validation',true,$products[0]->get_id(),1) && (bool)WC()->cart->add_to_cart($products[0]->get_id(),1),'First concert can be added to cart');
    rl_check_editor((bool)WC()->cart->add_to_cart($products[1]->get_id(),2) && count(WC()->cart->get_cart())===2 && WC()->cart->get_cart_contents_count()===3,'One WooCommerce cart holds tickets for multiple concerts');
    rl_check_editor(!WC()->cart->add_to_cart($products[1]->get_id(),2),'Combined cart quantities cannot exceed a concert capacity');wc_clear_notices();
    $order=wc_create_order();$order->add_product($products[0],1);$order->add_product($products[1],2);$order->calculate_totals();$order->update_status('on-hold');
    rl_check_editor((int)wc_get_product($products[0]->get_id())->get_stock_quantity()===2 && (int)wc_get_product($products[1]->get_id())->get_stock_quantity()===1,'One held bank-transfer order reserves both concert capacities');
    $order->update_status('cancelled');
    rl_check_editor((int)wc_get_product($products[0]->get_id())->get_stock_quantity()===3 && (int)wc_get_product($products[1]->get_id())->get_stock_quantity()===3,'Cancelling a mixed order restores both concert capacities');
    update_post_meta($ids[1],'rl_cancelled','1');
    rl_check_editor(!WC()->cart->add_to_cart($products[1]->get_id(),1),'Cancelled concert is rejected even from an existing shop form');
    wc_clear_notices();do_action('woocommerce_check_cart_items');
    rl_check_editor(wc_notice_count('error')>0,'Checkout blocks a cancelled concert already in the shared cart');
    $_GET['concert']=$ids[1];$html=rl_render_shop();
    rl_check_editor(str_contains($html,rl_ui('shop_selection_invalid')) && !str_contains($html,'name="add-to-cart" value="'.$products[1]->get_id().'"'),'Unavailable selection falls back without offering the cancelled product');
} finally {
    $_POST=[];unset($_GET['concert']);remove_filter('woocommerce_is_cart','__return_true');
    if (WC()->cart) WC()->cart->empty_cart();wc_clear_notices();
    if ($order) $order->delete(true);
    foreach ($products as $product) $product->delete(true);
    foreach (array_merge($ids,$page_ids) as $id) wp_delete_post($id,true);
    $GLOBALS['wp_query']=$previous_query;
    if ($previous_cart_page===null) delete_option('woocommerce_cart_page_id'); else update_option('woocommerce_cart_page_id',$previous_cart_page);
    foreach ($attachments as $id) wp_delete_attachment($id,true);
    foreach ($files as $file) if (file_exists($file)) unlink($file);
    if ($original===null) delete_option('rl_site'); else update_option('rl_site',$original);
    remove_filter('pre_wp_mail',$mail);
}
