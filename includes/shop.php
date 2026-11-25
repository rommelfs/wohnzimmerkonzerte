<?php
if (!defined('ABSPATH')) exit;
function rl_shop_url(int $concert=0): string {
    $page=(int)(rl_site()['shop_page_id'] ?? 0);
    $post=$page ? get_post($page) : null;
    $url=$post && $post->post_status==='publish' && has_shortcode($post->post_content,'rommelfangen_tickets') ? get_permalink($post) : add_query_arg('rl_ticket_shop','1',home_url('/'));
    return $concert ? add_query_arg('concert',$concert,$url) : $url;
}
function rl_shop_events(): array {
    $events=array_values(array_filter(rl_events(),static fn($e)=>!$e['cancelled'] && $e['start']>time()));
    usort($events,static fn($a,$b)=>$a['start']<=>$b['start']);
    return $events;
}
function rl_shop_selection(array $events, int $requested=0): ?array {
    foreach ($events as $event) if ($event['id']===$requested) return $event;
    return $events[0] ?? null;
}
function rl_render_shop(): string {
    static $rendering=false; if ($rendering) return '';
    $rendering=true; ob_start();
    try { require RL_PLUGIN_DIR.'templates/ticket-content.php'; return ob_get_clean(); }
    catch (Throwable $error) { ob_end_clean(); throw $error; }
    finally { $rendering=false; }
}
// Standard WooCommerce handles the order, quantities, stock reservations and shared cart.
add_filter('woocommerce_add_to_cart_validation',function ($passed,$product_id) {
    if (!isset($_POST['rl_ticket_origin'])) return $passed;
    $nonce=isset($_POST['rl_ticket_nonce']) ? sanitize_text_field(wp_unslash($_POST['rl_ticket_nonce'])) : '';
    $product=wc_get_product($product_id);
    $allowed=array_map(static fn($e)=>(int)get_post_meta($e['id'],'rl_product_id',true),rl_shop_events());
    if (!wp_verify_nonce($nonce,'rl_add_ticket') || !$product || !in_array((int)$product_id,$allowed,true) || !rl_ticket_open($product)) {
        wc_add_notice(rl_ui('shop_unavailable'),'error'); return false;
    }
    return $passed;
},10,2);

add_filter('woocommerce_continue_shopping_redirect',function ($url) { return isset($_POST['rl_ticket_origin']) ? rl_shop_url() : $url; });
