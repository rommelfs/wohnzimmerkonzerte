<?php
if (!defined('ABSPATH')) exit;
$events=rl_shop_events(); $requested=isset($_GET['concert']) && is_scalar($_GET['concert']) ? absint($_GET['concert']) : 0;
$selected=rl_shop_selection($events,$requested);
?>
<main id="main" class="wrap section rl-shop"><p class="eyebrow"><?php echo esc_html(rl_ui('nav_shop')); ?></p><h1><?php echo esc_html(rl_ui('shop_title')); ?></h1><p class="lead"><?php echo esc_html(rl_ui('shop_intro')); ?></p>
<?php if (!function_exists('WC')): ?><p class="empty"><?php echo esc_html(rl_ui('shop_inactive')); ?></p>
<?php else: wc_print_notices(); ?>
<div class="concert-layout"><div>
<?php if (!$events): ?><p class="empty"><?php echo esc_html(rl_ui('shop_empty')); ?></p>
<?php else: ?>
<?php if ($requested && $selected && $selected['id']!==$requested): ?><p role="status"><?php echo esc_html(rl_ui('shop_selection_invalid')); ?></p><?php endif; ?>
<form method="get" action="<?php echo esc_url(rl_shop_url()); ?>" class="rl-concert-select">
<?php if (!empty(rl_site()['shop_page_id']) && str_contains(rl_shop_url(),'?page_id=')): ?><input type="hidden" name="page_id" value="<?php echo (int)rl_site()['shop_page_id']; ?>"><?php endif; ?>
<?php if (str_contains(rl_shop_url(),'rl_ticket_shop=')): ?><input type="hidden" name="rl_ticket_shop" value="1"><?php endif; ?>
<label for="rl-concert-choice"><?php echo esc_html(rl_ui('shop_select')); ?></label><select id="rl-concert-choice" name="concert">
<?php foreach ($events as $event): ?><option value="<?php echo (int)$event['id']; ?>" <?php selected($selected['id'],$event['id']); ?>><?php echo esc_html(get_the_title($event['id']).' · '.rl_date($event['start'])); ?></option><?php endforeach; ?></select><button class="button" type="submit"><?php echo esc_html(rl_ui('shop_change')); ?></button></form>
<?php $id=$selected['id']; $style=rl_concert_style($id); $product=rl_ticket_product($id); ?>
<article class="rl-shop-concert rl-themed" style="<?php echo esc_attr(rl_style_attributes($style)); ?>"><div class="concert-image"><?php rl_image($id); ?></div><h2><?php echo esc_html(get_the_title($id)); ?></h2><p class="date"><?php echo esc_html(rl_date($selected['start'])); ?></p><p><?php echo esc_html(get_the_excerpt($id)); ?></p>
<?php if ($product && $product->is_purchasable() && $product->is_in_stock()): ?>
<p class="price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
<form method="post" action="<?php echo esc_url(rl_shop_url($id)); ?>" class="rl-ticket-form">
<?php wp_nonce_field('rl_add_ticket','rl_ticket_nonce'); ?><input type="hidden" name="rl_ticket_origin" value="1"><input type="hidden" name="add-to-cart" value="<?php echo (int)$product->get_id(); ?>">
<label for="rl-ticket-quantity"><?php echo esc_html(rl_ui('shop_quantity')); ?></label><input type="number" id="rl-ticket-quantity" name="quantity" min="1" max="<?php echo (int)$product->get_max_purchase_quantity(); ?>" step="1" value="1" required>
<button class="button" type="submit"><?php echo esc_html(rl_ui('shop_add')); ?></button></form>
<?php else: ?><p class="pill"><?php echo esc_html(rl_ui($product && !$product->is_in_stock() ? 'sold_out' : 'shop_unavailable')); ?></p><?php endif; ?>
</article><?php endif; ?></div>
<aside class="ticket-box"><h2><?php echo esc_html(rl_ui('cart_summary')); ?></h2>
<?php if (WC()->cart && !WC()->cart->is_empty()): ?><ul class="rl-cart-list"><?php foreach (WC()->cart->get_cart() as $item): ?><li><?php echo esc_html($item['data']->get_name()); ?> × <?php echo (int)$item['quantity']; ?></li><?php endforeach; ?></ul><p class="price"><?php echo wp_kses_post(WC()->cart->get_cart_total()); ?></p><div class="button-row"><a class="button" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php echo esc_html(rl_ui('nav_cart')); ?></a><a class="button secondary" href="<?php echo esc_url(wc_get_checkout_url()); ?>"><?php echo esc_html(rl_ui('checkout')); ?></a></div>
<?php else: ?><p><?php echo esc_html(rl_ui('cart_empty')); ?></p><?php endif; ?></aside></div>
<?php endif; ?></main>
