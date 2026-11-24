<?php
// wp eval-file tests/wordpress.php — isolated test installations only.
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !function_exists('rl_events')) throw new RuntimeException('Activate WooCommerce and wohnzimmerkonzerte first.');
$ids = []; $product = null; $order = null;
$disable_mail = static fn() => true;
add_filter('pre_wp_mail', $disable_mail);
function rl_expect($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
try {
    rl_expect(rl_timestamp('2026-02-30T20:00') === 0, 'Invalid calendar dates rejected');
    rl_expect(rl_timestamp('2030-01-05T20:00') === (new DateTimeImmutable('2030-01-05 20:00', wp_timezone()))->getTimestamp(), 'Site timezone respected');
    $product = new WC_Product_Simple();
    $product->set_name('TEST Ticket'); $product->set_status('publish'); $product->set_virtual(true);
    $product->set_regular_price('20'); $product->set_manage_stock(true); $product->set_stock_quantity(2); $product->set_backorders('no'); $product->save();
    $id = wp_insert_post(['post_type'=>'rl_concert','post_status'=>'publish','post_title'=>'TEST Konzert']); $ids[] = $id;
    update_post_meta($id, 'rl_start', wp_date('Y-m-d\TH:i', time() + DAY_IN_SECONDS));
    rl_expect(rl_product_can_link($product->get_id(), $id), 'Managed virtual ticket accepted');
    update_post_meta($id, 'rl_product_id', $product->get_id());
    rl_expect($product->is_purchasable(), 'Future concert can be purchased');
    $second = wp_insert_post(['post_type'=>'rl_concert','post_status'=>'draft','post_title'=>'TEST second']); $ids[] = $second;
    rl_expect(!rl_product_can_link($product->get_id(), $second), 'Shared ticket product rejected');
    update_post_meta($id, 'rl_cancelled', '1');
    rl_expect(!$product->is_purchasable(), 'Cancelled concert cannot be purchased');
    update_post_meta($id, 'rl_cancelled', '');
    update_post_meta($id, 'rl_start', wp_date('Y-m-d\TH:i', time() - DAY_IN_SECONDS));
    rl_expect(!$product->is_purchasable(), 'Expired concert cannot be purchased');
    update_post_meta($id, 'rl_start', wp_date('Y-m-d\TH:i', time() + DAY_IN_SECONDS));
    wp_update_post(['ID'=>$id,'post_status'=>'draft']);
    rl_expect(!$product->is_purchasable(), 'Unpublished concert cannot be purchased');
    wp_update_post(['ID'=>$id,'post_status'=>'publish']);
    $order = wc_create_order();
    $order->add_product($product, 1); $order->calculate_totals();
    $order->payment_complete('local-test-no-external-payment');
    rl_expect((int)wc_get_product($product->get_id())->get_stock_quantity() === 1, 'Paid ticket reduces available capacity');
    $order->update_status('cancelled');
    rl_expect((int)wc_get_product($product->get_id())->get_stock_quantity() === 2, 'Cancellation restores capacity');
    $product->set_backorders('yes'); $product->save();
    rl_expect(!$product->is_purchasable(), 'Later inventory misconfiguration blocks sales');
} finally {
    foreach ($ids as $id) wp_delete_post($id, true);
    if ($order) $order->delete(true);
    if ($product) $product->delete(true);
    remove_filter('pre_wp_mail', $disable_mail);
}
