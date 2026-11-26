<?php if (!defined('ABSPATH')) exit; rl_header(); ?>
<main id="main"><?php while (have_posts()): the_post();
$id=get_the_ID();$start=rl_timestamp((string)get_post_meta($id,'rl_start',true));$cancelled=get_post_meta($id,'rl_cancelled',true)==='1';$past=$start>0 && $start<time();$style=rl_concert_style($id);$editorial=rl_editorial($id);
?>
<article class="section rl-concert-detail rl-themed <?php echo $past?'rl-review ':''; ?>layout-<?php echo esc_attr($style['layout'] ?: 'split'); ?>" style="<?php echo esc_attr(rl_style_attributes($style)); ?>"><div class="wrap">
<header class="rl-concert-heading"><p class="eyebrow"><?php echo esc_html(rl_ui($cancelled?'cancelled':($past?'past':'upcoming'))); ?></p><h1><?php echo esc_html($editorial['headline']?:get_the_title()); ?></h1>
<?php if ($editorial['subtitle']): ?><p class="rl-concert-subtitle"><?php echo esc_html($editorial['subtitle']); ?></p><?php endif; ?><?php if ($past): ?><p class="rl-review-meta"><?php echo esc_html(rl_date($start)); ?><?php if ($venue=get_post_meta($id,'rl_venue',true)) echo ' · '.esc_html($venue); ?></p><?php endif; ?></header>
<div class="concert-layout"><div class="concert-content">
<div class="concert-image"><?php rl_image($id); ?></div>
<div class="prose"><?php $text=$past && $editorial['review'] ? $editorial['review'] : $editorial['intro']; if ($text) rl_rich_text($text); the_content(); ?></div>
<?php rl_concert_media($id); the_tags('<p class="tags">',' ','</p>'); ?>
</div>
<aside class="ticket-box"><h2><?php echo esc_html($editorial['box_title']?:rl_ui($past?'review_box':'ticket_box')); ?></h2>
<?php if ($start && !$past): ?><p class="date"><?php echo esc_html(rl_date($start)); ?></p><?php endif; ?>
<?php if (!$past && !$cancelled && $start): ?><p><a href="<?php echo esc_url(rl_calendar_url($id)); ?>"><?php echo esc_html(rl_ui('calendar_save')); ?></a></p>
<?php if ($doors=get_post_meta($id,'rl_doors',true)): ?><p><?php echo esc_html(rl_ui('doors')); ?>: <?php echo esc_html($doors); ?></p><?php endif; endif; ?>
<?php if (!$past && ($venue=get_post_meta($id,'rl_venue',true))): ?><p><?php echo esc_html($venue); ?></p><?php endif; ?>
<?php $product=rl_ticket_product($id);
if ($cancelled): ?><p><?php echo nl2br(esc_html($editorial['status_text']?:rl_ui('cancelled_text'))); ?></p>
<?php elseif ($past): ?><p><?php echo nl2br(esc_html($editorial['status_text']?:rl_ui('past_text'))); ?></p>
<?php elseif ($product && $product->is_purchasable() && $product->is_in_stock()): ?>
<p class="price"><?php echo wp_kses_post($product->get_price_html()); ?></p><p><?php echo nl2br(esc_html($editorial['status_text']?:rl_ui('ticket_hint'))); ?></p><a class="button" href="<?php echo esc_url(rl_shop_url($id)); ?>"><?php echo esc_html(rl_ui('ticket_button')); ?></a>
<?php elseif ($product && !$product->is_in_stock()): ?><p class="pill"><?php echo esc_html(rl_ui('sold_out')); ?></p><p><?php echo nl2br(esc_html($editorial['status_text']?:rl_ui('sold_out_text'))); ?></p><?php echo rl_waitlist_form($id); ?><a href="<?php echo esc_url(get_post_type_archive_link('rl_concert')); ?>"><?php echo esc_html(rl_ui('shop_other')); ?></a>
<?php else: ?><p><?php echo nl2br(esc_html($editorial['status_text']?:rl_ui('not_open'))); ?></p><?php endif; ?>
<?php if ($past): ?><p><a class="button" href="<?php echo esc_url(get_post_type_archive_link('rl_concert')); ?>"><?php echo esc_html(rl_ui('next_concerts')); ?></a></p>
<?php if (!array_filter(rl_events(),static fn($event)=>!$event['cancelled'] && $event['start']>time())): ?><p><?php echo esc_html(rl_ui('empty_upcoming')); ?></p><?php endif; endif; ?>
</aside></div>
<?php if (rl_newsletter_anchor()): ?><section class="rl-concert-newsletter" aria-labelledby="rl-detail-newsletter"><h2 id="rl-detail-newsletter"><?php echo esc_html(rl_ui($past?'review_newsletter':'newsletter_title')); ?></h2><p><?php echo esc_html(rl_ui('newsletter_intro')); ?></p><?php echo rl_mail_signup_form(); ?></section><?php endif; ?>
</div></article><?php endwhile; ?></main><?php rl_footer(); ?>
