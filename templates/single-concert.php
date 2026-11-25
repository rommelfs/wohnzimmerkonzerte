<?php if (!defined('ABSPATH')) exit; rl_header(); ?>
<main id="main"><?php while (have_posts()): the_post(); $id=get_the_ID(); $start=rl_timestamp((string)get_post_meta($id,'rl_start',true)); $cancelled=get_post_meta($id,'rl_cancelled',true)==='1'; $style=rl_concert_style($id); ?>
<article class="section rl-themed layout-<?php echo esc_attr($style['layout'] ?: 'split'); ?>" style="<?php echo esc_attr(rl_style_attributes($style)); ?>"><div class="wrap">
<p class="eyebrow"><?php echo esc_html(rl_ui($cancelled?'cancelled':($start>=time()?'upcoming':'past'))); ?></p><h1><?php the_title(); ?></h1>
<div class="concert-layout"><div class="concert-content"><div class="concert-image"><?php rl_image($id); ?></div><div class="prose"><?php the_content(); ?></div><?php the_tags('<p class="tags">',' ','</p>'); ?></div>
<aside class="ticket-box"><h2><?php echo esc_html(rl_ui('ticket_box')); ?></h2><?php if ($start): ?><p class="date"><?php echo esc_html(rl_date($start)); ?></p><?php endif; ?>
<p><a href="<?php echo esc_url(rl_calendar_url($id)); ?>">Im Kalender speichern</a></p>
<?php if ($doors=get_post_meta($id,'rl_doors',true)): ?><p><?php echo esc_html(rl_ui('doors')); ?>: <?php echo esc_html($doors); ?></p><?php endif; ?>
<?php if ($venue=get_post_meta($id,'rl_venue',true)): ?><p><?php echo esc_html($venue); ?></p><?php endif; ?>
<?php $product=rl_ticket_product($id);
if ($cancelled): ?><p><?php echo esc_html(rl_ui('cancelled_text')); ?></p>
<?php elseif (!$start || $start<time()): ?><p><?php echo esc_html(rl_ui('past_text')); ?></p><a href="<?php echo esc_url(rl_shop_url()); ?>"><?php echo esc_html(rl_ui('next_concerts')); ?></a>
<?php elseif ($product && $product->is_purchasable() && $product->is_in_stock()): ?>
<p class="price"><?php echo wp_kses_post($product->get_price_html()); ?></p><p><?php echo esc_html(rl_ui('ticket_hint')); ?></p><a class="button" href="<?php echo esc_url(rl_shop_url($id)); ?>"><?php echo esc_html(rl_ui('ticket_button')); ?></a>
<?php elseif ($product && !$product->is_in_stock()): ?><p class="pill"><?php echo esc_html(rl_ui('sold_out')); ?></p><p><?php echo esc_html(rl_ui('sold_out_text')); ?></p><?php echo rl_waitlist_form($id); ?><a href="<?php echo esc_url(rl_shop_url()); ?>"><?php echo esc_html(rl_ui('shop_other')); ?></a>
<?php else: ?><p><?php echo esc_html(rl_ui('not_open')); ?></p><?php endif; ?>
</aside></div></div></article><?php endwhile; ?></main><?php rl_footer(); ?>
