<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="main" class="wrap section"><?php while (have_posts()): the_post(); $id = get_the_ID(); $start = function_exists('rl_timestamp') ? rl_timestamp((string)get_post_meta($id, 'rl_start', true)) : 0; $cancelled = get_post_meta($id, 'rl_cancelled', true) === '1'; ?>
<article><p class="eyebrow"><?php echo $cancelled ? 'Konzert abgesagt' : ($start >= time() ? 'Bald im Wohnzimmer' : 'Rückblick'); ?></p><h1><?php the_title(); ?></h1>
<div class="concert-layout"><div><div class="concert-image"><?php rl_image($id); ?></div><div class="prose"><?php the_content(); ?></div><?php the_tags('<p class="tags">', ' ', '</p>'); ?></div>
<aside class="ticket-box"><h2>Dein Abend</h2><?php if ($start): ?><p class="date"><?php echo esc_html(rl_date($start)); ?></p><?php endif; ?>
<?php if ($doors = get_post_meta($id, 'rl_doors', true)): ?><p>Einlass: <?php echo esc_html($doors); ?></p><?php endif; ?>
<?php if ($venue = get_post_meta($id, 'rl_venue', true)): ?><p><?php echo esc_html($venue); ?></p><?php endif; ?>
<?php $product = function_exists('rl_ticket_product') ? rl_ticket_product($id) : false;
if ($cancelled): ?><p>Dieser Abend findet leider nicht statt. Bei bestehenden Buchungen melden wir uns bei euch.</p>
<?php elseif (!$start || $start < time()): ?><p>Dieser Abend liegt hinter uns. Danke an alle, die dabei waren.</p><a href="<?php echo esc_url(home_url('/#konzerte')); ?>">Nächste Konzerte entdecken</a>
<?php elseif ($product && $product->is_purchasable() && $product->is_in_stock()): ?>
<p class="price"><?php echo wp_kses_post($product->get_price_html()); ?></p><p>Tickets nach Verfügbarkeit. Die gewünschte Anzahl wählst du auf der Ticketseite.</p><a class="button" href="<?php echo esc_url($product->get_permalink()); ?>">Tickets buchen</a>
<?php elseif ($product && !$product->is_in_stock()): ?><p class="pill">Ausverkauft</p><p>Für diesen Abend sind alle Plätze vergeben.</p>
<?php else: ?><p>Der Ticketverkauf ist noch nicht geöffnet.</p><?php endif; ?>
</aside></div></article><?php endwhile; ?></main><?php get_footer(); ?>
